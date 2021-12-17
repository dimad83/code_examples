<?php

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ExcelParser
{
	private $sheets;
	private $counters;
	private $errors;
	private $object_to_fill;
	private $passed_warnings;
	private $file_id;

	static function throwLogicException($message, $row_index = null)
	{
		$message = $row_index ? "$row_index: $message" : $message;
		throw new LogicException($message);
	}

	function __construct(ExcelParserFillable $object_to_fill)
	{
		$this->passed_warnings = 0;
		$this->object_to_fill = $object_to_fill;
		$this->object_to_fill->checkColumnsMap();
	}


	public function parseAllData()
	{
		set_time_limit(0);
		printString("<b>Лог разбора:</b><br>");
		try {
			$file_data = [];
			foreach ($this->sheets as $sheet) {
				list($type_id, $rows_in_measure) = $this->findTypeAndPrint($sheet);
				if (is_null($type_id))
					continue;
				list($data_map, $row_after_headers) = $this->findColumnPositions($sheet, $type_id);
				if (($data_start_row = $this->findFirstDataRow($sheet, $row_after_headers)) == null)
					continue;
				$column_types = $this->getColumnTypes($data_map);
				$sheet_data = $this->readDataBlock($sheet, $data_map, $column_types, $data_start_row, $rows_in_measure);
				$this->prepareDataBlockForDB($sheet_data, $column_types, $type_id);
				// собранные на листе данные поместим в общий массив
				$file_data = array_merge($file_data, $sheet_data);
			}
			$file_data = clearedStrings($file_data); // заменяет пробельные и пустые строки на null
			$this->removeDuplicates($file_data);
			$this->save($file_data);
		} catch (LogicException $e) {
			printStringError("Ошибка: " . $e->getMessage());
		}
		return $file_data;
		// сбор данных из листов экселя в массив завершен, пока ничего никуда не сохраняли
	}

	private function save($data)
	{
		//var_dump($data);
		$old_data = $this->object_to_fill->getArrayForUniqString($data);
		// file_id убираем из ключей, т.к. он не о содержимом файла. Вернем file_id перед самым сохранением, потому что он
		// будет нужен для контроля за качеством содержимого загруженных файлов.
		$old_uniq_keys = $this->object_to_fill->getUniqKeysList($old_data);
		unset($old_data);

		$editor_fields_cached = array_diff($this->object_to_fill->getEditorFieldsWtId(), ['file_id']);

		// наклевывается проблема с переписыванием ndpi_date и is_synthetic_ndpi, можно будет решить ее через массив nonPaddableFields
		$this->padDataWithNulls($data, $editor_fields_cached);

		$key_fields = $this->object_to_fill->getKeyFields();
		$updated = $added = $duplicates = $updated_file_id = 0;
		if (count($data)) {
			foreach ($data as $rowIndex => $row) {
				$uniq_key = $this->object_to_fill->getUniqString($row, $key_fields);
				$id = $old_uniq_keys[ $uniq_key ] ?? null;
				if (isset($id)) {
					// обновим и учтем статус изменения. Affected_rows будет 0 только при выполнении обоих условий:
					// 1. в таблице нет mtime (т.к. его автоматом возводит update() нашего движка)
					// 2. при update строка не изменилась, даже если какие-то параметры из-за разного округления различались
					if (($affected_rows = $this->object_to_fill->updateAndGetAffectedRows($row, $id)) > 0)
						$updated += $affected_rows;
					else
						$duplicates++;
					// отдельно обновим file_id
					$updated_file_id += $this->object_to_fill->updateAndGetAffectedRows(['file_id' => $this->file_id], $id);
				} else {
					// записи по такому ключу еще нет
					$row['file_id'] = $this->file_id; //  file_id пригодится для контроля за качеством содержимого загруженных файлов
					$this->object_to_fill->addData($row);
					$added++;
				}
			}
		}
		printString('<b>Итого:</b>');
		$sum = $added + $updated + $duplicates;
		printStringInfo("Успешно прочитано из файла записей: <b>$sum</b>");
		printStringInfo("Результат по ним:");
        printStringSuccess( "<span style='margin-left: 20px'>добавлено на портал: <b>$added</b></span>");
        printStringSuccess( "<span style='margin-left: 20px'>изменено на портале: <b>$updated</b></span>");
		printStringSuccess( "<span style='margin-left: 20px'>пропущено, т.к. записи уже есть на портале: <b>$duplicates</b></span>");
		printStringInfo( "Дополнительно:");
		printStringSuccess( "<span style='margin-left: 20px'>file_id обновлен у <b>$updated_file_id</b> записей</span>");

		$message = "Пропущено из-за проблем: <b>{$this->passed_warnings}</b>";
		$this->passed_warnings ? printStringError($message) : printStringSuccess($message);
        printString();
		printString("Вы можете изменить/исправить файл и загрузить его заново, изменения будут применены. Ограничения:");
		printString("<span style='margin-left: 20px'>1. Удаленные из файла строки удалены с портала не будут, их нужно будет удалить на портале вручную.</span>");
		printString("<span style='margin-left: 20px'>2. Строки с измененными датой/временем воспримутся как новые, при этом старые удалены из БД не будут.</span>");
	}

	private function removeDuplicates(&$data)
	{
		printStringInfo("Проверка собранных записей на повторы ключевых параметров...");
		$duplicates = [];
		$key_fields = $this->object_to_fill->getKeyFields();
		if (count($data)) {
			foreach ($data as $row_index => $row) {
				$uniq_key = $this->object_to_fill->getUniqString($row, $key_fields);
				$duplicates[$uniq_key][] = $row_index;
			}
			foreach ($duplicates as $row_indexes) {
				if (count($row_indexes) > 1) {
					foreach ($row_indexes as $row_index) {
						unset($data[$row_index]);
						$this->passed_warnings++;
					}
					printStringError(join(', ', $row_indexes) . ": конфликт из-за одинакового ключа в строках - скорее всего, совпадающие скважина и время замера");
				}
			}
		}
		printStringInfo("Завершено");
		printStringInfo();
	}

	private function padDataWithNulls(&$data, $template)
	{
		foreach ($data as &$row) {
			foreach ($template as $field) {
				if (!array_key_exists($field, $row))
					$row[$field]  = null;
			}
		}
		unset($row);
	}

	private function prepareDataBlockForDB(&$data, $column_types, $type_id)
	{
		$num_warnings = 0;
		$non_key_fields = $this->object_to_fill->getNonKeyFields();
		foreach ($data as $row_index => $row) {
			try {
				$data[ $row_index ] = $this->object_to_fill->prepareRowForDB($row, $column_types, $row_index, $non_key_fields);
				if (empty($data[ $row_index ])) { // избавляемся от записей, которые считаем строками без данных
					unset($data[ $row_index ]);
					continue;
				}
				$data[ $row_index ][ 'type_id' ] = $type_id;
				//$data[ $row_index ][ 'file_id' ] = $this->file_id; - проставим сручную в методе save в зависимости от разных факторов, т.к. это поле абсолютно всегда обновлено
			}
			catch (LogicException $e) {
				// Список ошибок про кривые данные на листе будет после каждого "Лист ... разбираем ..."
				// А вот ошибки про всякие дубли с разных страниц будут сильно ниже
				printStringError($e->getMessage());
				$num_warnings++;
				unset($data[$row_index]);
			}
		}
		printStringSuccess("Взято в работу записей: " . count($data));
		if ($num_warnings) {
			printStringError("Проблемных записей пропущено: " . $num_warnings);
			$this->passed_warnings += $num_warnings;
		}
		printString();
	}

	public function getCounters(): array
	{
		return $this->counters;
	}

	public function getErrors(): array
	{
		return $this->errors;
	}

	/*
	 * Распечатать информацию по найденному для листа типу анализа и количеству строк в анализе
	 */
	private function printType(Worksheet $sheet, $type_id, $rows_in_measure){
		if ($type_id) {
			$type_name = $this->object_to_fill->getAnalysisTypeName($type_id);
			printStringInfo("Лист <strong>#" . $sheet->getTitle() . "</strong> разбираем как $rows_in_measure-строчный анализ, тип анализа: <strong>$type_name</strong>...");
		}
		else {
			printStringWarning("Лист <strong>#" . $sheet->getTitle() . "</strong> пропущен");
			printStringWarning();
		}
	}

	/*
	 * Определить тип анализа листа и написать про него.
	 * Возвращает [$found_type_id, $rows_in_measure]
	 */
	public function findTypeAndPrint(Worksheet $sheet)
	{
		$patterns_list = $this->object_to_fill->getIdentificationPatterns();
		$rowIterator = $sheet->getRowIterator();
		$found_type_id = null;
		$rows_in_measure = null;

		// инициализируем массив найденных соответствий
		$matches = [];
		foreach ($patterns_list as $type_id => $patterns){
			if (empty($patterns)) // если для типа паттерны не созданы, то этот тип и не рассматриваем
				continue;
			$matches[$type_id] = [];
			$matches[$type_id]['num_checks'] = 0;
			foreach ($patterns as $pattern){
				$matches[$type_id]['num_checks'] ++;
				$matches[$type_id]['found_matches'][$pattern] = 0;
			}
		}

		// ищем паттерны в первых скольки-то строках
		foreach ($rowIterator as $row) {
			$row_num = $row->getRowIndex();
			if ($row_num > 15)
				break;
			$cellIterator = $row->getCellIterator();
			try {
				$cellIterator->setIterateOnlyExistingCells(true);
			} catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
				continue; // строка пустая, пропускаем
			}
			foreach ($cellIterator as $cell) {
				//echo $cell->getCoordinate() . ": '" . $cell->getValue() . "'<br>";
				$cell_value = $cell->getValue();
				foreach ($matches as $type_id => $match_info) {
					foreach (array_keys($match_info['found_matches']) as $pattern) {
						if (0 === stripos($cell_value, $pattern)) { // если подстрока $pattern найдена в начале строки (без учета регистра)
							$matches[ $type_id ]['found_matches'][ $pattern ] = 1;
						}
					}
				}
			}
		}
		foreach ($matches as $type_id => $match_info){
			$num_found = 0;
			foreach ($match_info['found_matches'] as $pattern => $is_found){
				if ($is_found)
					$num_found++;
			}
			if ($num_found > 0 && $num_found == $match_info['num_checks']){
				$found_type_id = $type_id;
				$rows_in_measure = $this->object_to_fill->getRowsInMeasure($type_id);
				break; // foreach ($matches
			}
		}
		$this->printType($sheet, $found_type_id, $rows_in_measure);
		return [$found_type_id, $rows_in_measure];
	}

	/*
	 * Принять файл, подцепить к нему Excel-разборщик, в $this->sheets поместить все найденные листы для дальшнейшей работы с ними
	 */
	public function getAllSheets(string $dest)
	{
		$uploadClass = new Upload();
		$uploadClass->setFieldName('xls_file');
		$uploadClass->setTypes(array('application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'));
		$uploadClass->setDestination($dest);
		$error = $uploadClass->load();

		$file = $uploadClass->getUploadedFilePath();

		if (empty($file)) {
			http_response_code(500);
			echo 'Error while uploading file';
			die();
		}

		switch ($uploadClass->getType()) {
			case 'application/vnd.ms-excel':
			case 'application/vnd.ms-office':
				$objReader = new Xls();
				break;
			case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
				$objReader = new Xlsx();
				break;
		}
		$objReader->setReadDataOnly(false); // false позволяет получать списки смердженных ячеек через $sheet->getMergeCells()
		$objPHPExcel = $objReader->load($file);

		$this->sheets = $objPHPExcel->getAllSheets();
		$this->file_id = $uploadClass->getFileID();
	}

	/*
	 * Возвращает значение ячейки. Если ячейка входит в мердж, то возвращается значение первой ячейки из мерджа.
	 */
	private function getValueMergedCell(Worksheet $sheet, Cell $cell)
	{
		// идем по всем смердженным ячейкам листа
		foreach ($sheet->getMergeCells() as $merged_cells_range) {
			if ($cell->isInRange($merged_cells_range)) {
				if ($merged_cells_arr = explode(':', $merged_cells_range)) {
					return $sheet->getCell($merged_cells_arr[0])->getValue();
				} else {
					return null;
				}
			}
		}
		// если ячейка оказалась не смердженной, то возращаем ее личное значение
		return $cell->getValue();
	}


	/*
	 * Проверить $data_map на наличие всех NECESSARY колонок.
	 * Предупредить о неведомых разборщику колонках, если таковые нашлись на листе.
	 */
	private function checkColumnsCompleteness($sheet, $type_id, $data_map, $header_first_row, $headers_max_height)
	{
		$columns_map = $this->object_to_fill->getColumnsSubmap($type_id);

		// 1. Проверяем $data_map на наличие всех NECESSARY колонок. При отсутствии - эксепшн с ошибкой.
		$absent_keys = [];
		foreach (array_keys($columns_map[$this->object_to_fill::NECESSARY]) as $necessary_key) {
			if (empty($data_map[$necessary_key]))
				$absent_keys[] = $necessary_key;
		}
		if (count($absent_keys)) {
			throw new Exception("На листе не найдены обязательные для данного типа анализа параметры: <strong>" . join(', ', $absent_keys) . "</strong>");
		}

		// 2. Собираем все заголовки с координатами, отсутствующими в $data_map, но присутствующими на странице. Выводим списком в виде предупреждения.
		$unused_headers = [];
		$rowIterator = $sheet->getRowIterator();
		$mapped_columns = array_values($data_map); // список задейстованных в $data_map колонок
		foreach($rowIterator as $row) {
			$row_num = $row->getRowIndex();
			if ($row_num >= $header_first_row) {
				$level = $row_num - $header_first_row;
				$cellIterator = $row->getCellIterator();
				foreach ($cellIterator as $cell_0) { // проход по всем ячейкам строки
					$cell_column = $cell_0->getColumn();
					if (!in_array($cell_column, $mapped_columns)) { // запоминаем только незадейстованные в $data_map
						$value = clean_spaces($this->getValueMergedCell($sheet, $cell_0));
						if (strlen($value) > 0) {
							// запоминаем без повторений (повторения могут возникать из-за вертикального мерджа ячеек)
							if ($level == 0 || $value != $unused_headers[$cell_column][$level - 1])
								$unused_headers[$cell_column][$level] = $this->getValueMergedCell($sheet, $cell_0);
						}
					}
				}
			}
			// Если заголовки в файле просмотрены на всю предполагаемую типом высоту, то просмотр строк завершаем
			if ($row_num >= $header_first_row + $headers_max_height - 1)
				break;
		}
		if (count($unused_headers)) {
			$message = "В листе <strong>". $sheet->getTitle(). "</strong> найдены колонки, неизвестные типу анализа <strong>" . $this->object_to_fill->getAnalysisTypeName($type_id) . "</strong>: ";
            $sub_messages = [];
            foreach ($unused_headers as $column => $header_arr) {
				$sub_messages[] = "'$column' - '" . join("' -> '", $header_arr) . "'";
			}
            $message .= join(', ', $sub_messages). ". ";
			$message .= "Для отладки: file_id={$this->file_id}";
			printStringWarning($message);
			notify_developer($message, __CLASS__ . " warning");
		}
	}

	/*
	 * Удалить все IGNORE колонки из $data_map в соответствии с типом анализа
	 */
	private function removeIgnoredColumns($type_id, &$data_map)
	{
		$columns_map = $this->object_to_fill->getColumnsSubmap($type_id);
		foreach (array_keys($columns_map[$this->object_to_fill::IGNORE]) as $ignore_key) {
			unset($data_map[$ignore_key]);
		}
	}

	/*
	 * Найти положение всех нужных колонок на листе в соответствии с типом анализа
	 * Проверить $data_map на наличие требуемых по конфигурации колонок, почистить его от лишних колонок
	 * Вернуть [$data_map, $row_after_headers]
	 */
	public function findColumnPositions(Worksheet $sheet, int $type_id)
	{
		$header_first_row = 0;
		$columns_map_flat = $this->object_to_fill->getColumnsSubmapFlat($type_id);
		$data_map = [];
		$rowIterator = $sheet->getRowIterator();
		foreach($rowIterator as $row) {
			$cellIterator = $row->getCellIterator();
			try {
				$cellIterator->setIterateOnlyExistingCells(true);
			} catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
				continue; // строка пустая, пропускаем
			}
			$header_first_row = $row->getRowIndex();
			foreach ($cellIterator as $cell_0) { // проход по всем ячейкам строки
				// сохраняем $cell_column здесь из-за того, что $cell_0 переключится на другую ячейку после вызова getValueMergedCell
				$cell_column = $cell_0->getColumn();
				$cell_0_value = clean_spaces($this->getValueMergedCell($sheet, $cell_0));
				$headers_on_sheet[$cell_column][] = $cell_0_value;
				foreach ($columns_map_flat as $key => $header_blocks) {
					foreach ($header_blocks as $headers_arr) {
						if ($headers_arr[0] == $cell_0_value) {
							// сравниваем остальные части заголовка с ячейками, находящимися ниже первой части заголовка
							$headers_height = count($headers_arr);
							for ($i = 1; $i < $headers_height; $i++) {
								$header = $headers_arr[$i];
								// WARNING Здесь не будет отыскиваться шаблон, в котором НЕЯВНО подразумевается несколько смердженных вертикально ячеек и ниже еще ячейк(а/и)
								// При появлении такого файла проблему можно исправить путем контроля дубликатов либо повторения несколько раз в карте смердженных ячеек
								$cell_i = $sheet->getCell($cell_column . ($header_first_row + $i));
								if ($header != clean_spaces($this->getValueMergedCell($sheet, $cell_i)))
									break; //полного соотвестствия не случилось, идем к следующему $headers_arr
							}
							if ($i == $headers_height) { // если все части заголовка колонки найдены
								$data_map[ $key ] = $cell_column;
								break 2; // идем к слудующей колонке в файле
							}
						}
					}
				}
			}
			// Считаем, что все заголовки начинаются с единого уровня. Поэтому, если хотя бы один заголовок найден, то после просмотра всей строки считаем, что все заголовки просмотрены.
			if (count($data_map))
				break;
		}
		$headers_max_height = $this->object_to_fill->getHeadersMaxHeight($type_id);
		$this->checkColumnsCompleteness($sheet, $type_id, $data_map, $header_first_row, $headers_max_height);
		$this->removeIgnoredColumns($type_id, $data_map);

		$row_after_headers = $header_first_row + $headers_max_height;
		return [$data_map, $row_after_headers];
	}

	/*
	 * Найти первую строку с данными, следующую после заголовков
	 */
	private function findFirstDataRow(Worksheet $sheet, int $row_after_headers)
	{
		// Поиск основан на поиске любой даты. Обычно этого должно хватать.
		// TODO Можно добавить на вход колонку с датой из ключа и проверять дату только в ней
		$rowIterator = $sheet->getRowIterator();
		foreach ($rowIterator as $row) {
			$row_num = $row->getRowIndex();
			if ($row_num < $row_after_headers) continue; // пропустим заголовки и всё, что до них
			$cellIterator = $row->getCellIterator();
			$current_row = $row->getRowIndex();
			foreach ($cellIterator as $cell) {
				if ((Date::isDateTime($cell)) && is_numeric($cell->getValue())) {
					return $current_row;
				}
			}
		}
		printStringWarning("Данные не найдены. Пропущен.");
		printStringWarning();
		return null;
	}

	private function getColumnTypes($data_map)
	{
		$column_types = [];
		foreach (array_keys($data_map) as $column_key) {
			$column_types[$column_key] = $this->object_to_fill->getColumnType($column_key);
		}
		return $column_types;
	}

	private function readDataBlock(Worksheet $sheet, $data_map, $column_types, $data_start_row, $rows_in_measure)
	{
		// для воды будет 1 тип анализа с 3 строками. Все похожее с однострочным оставим здесь, а отличающееся вынесем в lib/Water_Compositions
		// либо здесь же добавлю ветвление на количество строк в одном анилизе- в общем пака
		$rowIterator = $sheet->getRowIterator();
		$search_here = $data_start_row;
		foreach ($rowIterator as $row) {
			$row_num = $row->getRowIndex();
			if ($row_num < $search_here)
				continue; // skip titles and no-data rows
			$row_index = $sheet->getTitle() . ' строка ' . $row_num;
			foreach ($data_map as $column_key => $letter) {
				$cell = $sheet->getCell($letter . $row_num);
				$value = $cell->getValue();
				$column_type = $column_types[$column_key];
				if (is_numeric($value) && ExcelParserFillable::isDateOrTimeType($column_type))
					$value = Date::excelToDateTimeObject($value);
				else if (is_object($value)) { // в частности для нормальной обработки RichText, который приходит из комментариев со спецсимволами
					$value = $value->__toString();
				}
				$data[ $row_index ][ $column_key ] = $value;
			}
			$search_here += $rows_in_measure;
		}
		return $data;
	}

}