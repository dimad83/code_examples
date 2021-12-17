<?php
class LabMeasures extends ExcelParserFillable {
	protected $table = 'lab_measures';
	
	protected $fieldsSettings = array(
		'type_id' =>                                FILTER_VALIDATE_INT,
		'well_id' =>                                FILTER_VALIDATE_INT,
		'surface_object_id' =>                      FILTER_VALIDATE_INT,
		'file_id' =>                                FILTER_VALIDATE_INT,
		'measure_time' =>                           FILTER_VALIDATE_INT,
		'receiving_date' =>                         FILTER_SANITIZE_STRING,
		'analysis_date' =>                          FILTER_SANITIZE_STRING,
		'water_percent' =>                          FILTER_VALIDATE_FLOAT,
		'water_percent_is_water_volume_ratio' =>    FILTER_VALIDATE_INT,
		'water_content_mass' =>                     FILTER_VALIDATE_FLOAT,
		'mechanical_amount_oil' =>                  FILTER_VALIDATE_FLOAT,
		'mechanical_percent_oil' =>                 FILTER_VALIDATE_FLOAT,
		'chloride_amount' =>                        FILTER_VALIDATE_FLOAT,
		'sulfur' =>                                 FILTER_VALIDATE_FLOAT,
		'oil_density' =>                            FILTER_VALIDATE_FLOAT,
		'ndpi_date' =>                              FILTER_SANITIZE_STRING,
		'is_synthetic_ndpi' =>                      FILTER_VALIDATE_INT,
		'note' =>                                   FILTER_SANITIZE_STRING,
        'laborant' =>                               FILTER_SANITIZE_STRING,
        "viscosity_kinematic_20" =>                 FILTER_VALIDATE_FLOAT,
        "viscosity_kinematic_50" =>                 FILTER_VALIDATE_FLOAT,
        "t_boiling_start" =>                        FILTER_VALIDATE_FLOAT,
        "fractional_composition_100" =>             FILTER_VALIDATE_FLOAT,
        "fractional_composition_150" =>             FILTER_VALIDATE_FLOAT,
        "fractional_composition_200" =>             FILTER_VALIDATE_FLOAT,
        "fractional_composition_250" =>             FILTER_VALIDATE_FLOAT,
        "fractional_composition_300" =>             FILTER_VALIDATE_FLOAT,
        "t_boiling_finish" =>                       FILTER_SANITIZE_STRING,
        "components_asphaltenes" =>                 FILTER_VALIDATE_FLOAT,
        "components_gums" =>                        FILTER_VALIDATE_FLOAT,
        "components_parafins" =>                    FILTER_VALIDATE_FLOAT,
	);

	protected $keyFields = [
		'type_id', 'well_id', 'surface_object_id', 'measure_time'
	];
	
	protected $requiredFields = array();

	protected const LIQUID_NAME =     'Нефть';

	/* Типы файлов с анализами */
	const FIELD_LAB =               1;
	const TSNIPR_BASE =             2;
	const TSNIPR_CHLORIDES =        3;
	const TSNIPR_PROPERTIES_WIDE =  4;

	protected $analysisTypes = [
		self::FIELD_LAB =>              'Промысловая лаб-я',
		self::TSNIPR_BASE =>            'ЦНИПР базовый',
		self::TSNIPR_CHLORIDES =>       'ЦНИПР хлористые соли',
		self::TSNIPR_PROPERTIES_WIDE => 'ЦНИПР св-ва нефти широкий',
	];


	protected $identificationPatterns = [
		self::FIELD_LAB => [
			'по результатам определения обводненности, плотности нефти, механических примесей в нефти',
			'Отчет',
			'Плотность нефти при 20оС, г/см3',
		],
		self::TSNIPR_BASE => [
			'Результаты',
			'анализа проб нефти за', // дальше идет еще текст
		],
		self::TSNIPR_CHLORIDES => [
			'Лаборатория физико-химических исследований г. Урай',
			'по результатам определения хлористых солей в нефти',
		],
		self::TSNIPR_PROPERTIES_WIDE => [
			'Лаборатория физико-химических исследований г. Урай',
			'по результатам определения физико-химических свойств нефтей',
		],
	];

	protected $rowsInMeasure = 1; // но в воде будет массив

	protected $columnsMap =[
		self::FIELD_LAB => [
			self::NECESSARY => [ // уровень нужности колонки, не является уровнем нужности значения в ячейке
				"point_name" =>             [["Место отбора", "скважина"]],
				"date" =>                   [["Дата отбора"]],  // но можно и [["Дата отбора", "Дата отбора"]], т.к. это смердженная ячейка
				"time" =>                   [["Время отбора"]],
				"oil_density" =>            [["Плотность нефти при 20оС, г/см3"]],
				"mechanical_percent_oil" => [["Содержание мех.примесей нефть, %"]],
				"note" =>                   [["Примечание к колонке содержание воды"]],
			],
			self::OPTIONAL => [
				"water_percent" =>          [["Содержание воды, %"]],
				"water_content_mass" =>     [["Массовая доля воды, %"]],
				"water_volume_ratio" =>     [["Объемная доля воды, %"]], // сохраняется, как water_percent, но с флагом water_percent_is_water_volume_ratio
				"mechanical_amount_oil" =>  [["Кол-во мех.примесей нефть, мг/л"]],
				"chloride_amount" =>        [["Хлористые соли, мг/дм3"]],
				"sulfur" =>                 [["Массовая доля серы в товарной нефти, %"]],
				"laborant" =>               [["Ф.И.О. лаборанта"]],
				"receiving_date" =>         [["Дата поступления"]],
				"analysis_date" =>          [["Дата анализа"]],
			],
			self::IGNORE => [
				"measure_id" => [
					// TODO пример многоуровневого варианта, верхний эл-т вставлен для проверки корректности findColumnPositions(). Удилить эл-т после тестов.
					["Общая информация", "№ п/п",],
					["№ п/п"],
				],
				"pad" =>                    [["Место отбора", "куст"]],
				"field" =>                  [["ЛУ"]],
			],
		],
		self::TSNIPR_BASE => [
			self::NECESSARY => [
				"point_name" =>             [["Место отбора", "скважина"]],
				"date" =>                   [["Дата отбора"]],
				"time" =>                   [["Время отбора"]],
				"mechanical_amount_oil" =>  [["Кол-во мех.примесей, мг/л"]],
			],
			self::OPTIONAL => [
				"water_percent" =>          [["Содержание воды, %"]],
				"oil_density" =>            [["Плотность нефти при 20оС, г/см3"]],
				"mechanical_percent_oil" => [["Содержание мех.примесей, %"]],
			],
			self::IGNORE => [
				"measure_id" =>             [["№ п/п"]],
				"pad" =>                    [["Место отбора", "куст"]],
				"field" =>                  [["ЛУ"]],
				"water_density" =>          [["Плотность воды при 20оС, г/см3"]],
				"water_oil_products" =>     [["Содержание нефтепродуктов, мг/л"]],
			],
		],
		self::TSNIPR_CHLORIDES => [
			self::NECESSARY => [
				"point_name" =>             [["Место отбора"]],
				"date" =>                   [["Дата отбора"]],
				"time" =>                   [["Время отбора"]],
				"chloride_amount" =>        [["Хлористые соли, мг/дм3"], ["Содержание хлористых солей, мг/дм3"]],
			],
            self::OPTIONAL => [
                "note" =>                   [["Примечание"]],
            ],
		],
		self::TSNIPR_PROPERTIES_WIDE => [
			self::NECESSARY => [
				"point_name" =>             [["Место отбора"]],
				"date" =>                   [["Дата отбора"]],
				"time" =>                   [["Время отбора"]],
                "oil_density" =>            [['Плотность нефти, г/см3']],
                "viscosity_kinematic_20" => [['Вязкость кинематическая, сСт', '20оС']],
                "viscosity_kinematic_50" => [['Вязкость кинематическая, сСт', '50оС']],
                "t_boiling_start" =>        [['Температура начала кипения, оС']],
                "fractional_composition_100" => [['Фракционный состав, %об.', '100оС']],
                "fractional_composition_150" => [['Фракционный состав, %об.', '150оС']],
                "fractional_composition_200" => [['Фракционный состав, %об.', '200оС']],
                "fractional_composition_250" => [['Фракционный состав, %об.', '250оС']],
                "fractional_composition_300" => [['Фракционный состав, %об.', '300оС']],
                "t_boiling_finish" =>       [['Температура конца кипения, оС']],
                "components_asphaltenes" => [['Содержание компонентов, %вес.', 'асфальтены']],
                "components_gums" =>        [['Содержание компонентов, %вес.', 'смолы силик.']],
                "components_parafins" =>    [['Содержание компонентов, %вес.', 'парафины']],
				"chloride_amount" =>        [["Хлористые соли, мг/дм3"], ["Содержание хлористых солей, мг/дм3"]],
			],
            self::OPTIONAL => [
                "note" =>                   [["Примечание"]],
            ],
			self::IGNORE => [
				"measure_id" =>             [["№ п/п"]],
				"pad" =>                    [["Место отбора", "куст"]],
				"field" =>                  [["Название лицензионного участка"]],
				"delivery_date" =>          [["Дата поступления в лабораторию"]],

			],
		],
	];

	/*
	 * Из строки с собранными на Worksheet данными, получить строку, подобную строке в $table в БД (но с несколькими лишними колонками)
	 * Входные форматы:
	 * $row:
	 *      [column_key1 => mix1, column_key2 => mix2, ],
	 *      где mix - скалярное значение или объект типа DateTime
	 * $column_types:
	 *      [column_key1 => column_type1, column_key2 => column_type2, ],
	 *      где column_type принимимает значение одной из констант, имеющих маску ExcelParserFillable::COLUMN_...
	 * $row_index:
	 *      НазваниеЛиста #НомерСтроки
	 * $non_key_fields
	 */
	public function prepareRowForDB($row, $column_types, $row_index, $non_key_fields)
	{
		$datetime = $this->concatDateTime($row, 'date', 'time', $row_index);
		$row['measure_time'] = $datetime ? $datetime->getTimestamp() : null; //как вариант для другой таблицы format('Y-m-d H:i:s')
		if (empty($row['measure_time'])) {
            // нет ни даты замера, ни подозрения на корректную точку замера
            if (empty($row['point_name']) || $this->point_recognizer->getPointRecognized($row['point_name'])->error)
                return null; // ошибки нет, пропускаем строку
            else ExcelParser::throwLogicException("Не задана Дата отбора", $row_index);
        }

		/* И "Объемная доля воды, %", и "Содержание воды, %" сохраняем в water_percent. "Объемная доля воды, %"
		 * приоритетнее. Поэтому если в файле есть оба замера, то в БД сохраним только "Объемная доля воды, %".
		 * Для различения этих параметров используем флаг water_percent_is_water_volume_ratio. */
		if (!empty($row['water_volume_ratio'])) { // water_volume_ratio дальше не нужен, но пока его не удаляем, т.к. позже он будет уничтожен в другом месте
			$row['water_percent'] = $row['water_volume_ratio'];
			$row['water_percent_is_water_volume_ratio'] = 1;
		}
		// эта зависимость введена по запросу Максима Ворончихина, т.к. по его словам при отсуствии плотности % содержания мех примесей - филькина грамота
		if (empty($row['oil_density'])) $row['mechanical_percent_oil'] = null;

		return parent::prepareRowForDB($row, $column_types, $row_index, $non_key_fields);
	}

	/*
	 * Поля, которые есть в $columnsMap, но которых нет в таблице БД
	 */
	protected function getOnlyMapFields() {
		return['date', 'time', 'point_name', 'water_volume_ratio'];
	}

	// Только для использования в Хим. лаборатория - нефть. Для всяких watercuts не подходит, т.к. тут
	// `water_percent` из таблицы БД переводится в `water_volume_ratio` либо `water_percent` - в зависимости от
	// `water_percent_is_water_volume_ratio`
	public function getList($filter = array(), $order_by = 'measure_time DESC', $page = 0, $count = 0, $fields = '*')
	{
		$days = 30;
		$where = $this->getWhereByFilter($filter, $order_by, false);
		// к анализам нефти присоединяем ближайший анализ плотности воды из water_compositions, т.к. с середины 2017г.
		// в lab_measures плотность воды не вносят, а внесенной раньше плотности воды нельзя доверять
		$query = sql_pholder("
			WITH lab AS (
			SELECT
			    RANK() OVER (PARTITION BY lab_measures.id ORDER BY wc.measure_time DESC) AS r,
				lab_measures.*,	
				IF((lab_measures.water_percent_is_water_volume_ratio = 1), NULL, lab_measures.water_percent) AS water_content,
				IF((lab_measures.water_percent_is_water_volume_ratio = 1), lab_measures.water_percent, NULL) AS water_volume_ratio,
				ROUND(
					IF((lab_measures.water_percent_is_water_volume_ratio = 1), lab_measures.water_percent, NULL) -- water_volume_ratio
					/ 100 * wc.density + 
					(   1 - 
						IF((lab_measures.water_percent_is_water_volume_ratio = 1), lab_measures.water_percent, NULL) -- water_volume_ratio
						 / 100
					)
					* oil_density
					, 4) as liquid_density_calculated,
			wc.density          AS water_density_by_wc,
				wc.measure_time     AS water_density_time_by_wc
			FROM lab_measures
			LEFT JOIN water_compositions wc 
			  ON wc.well_id = lab_measures.well_id AND wc.density IS NOT NULL 
			  AND (wc.measure_time BETWEEN lab_measures.measure_time - 24 * 3600 * $days AND lab_measures.measure_time )
			WHERE 1 $where
			)
			SELECT
				lab.*,
				COALESCE(wells.name, surface_objects.name) as well_name,
				pads.name as pad_name, 
				fields.name as field_name
			FROM lab
			LEFT JOIN wells ON wells.id = lab.well_id
			LEFT JOIN surface_objects ON surface_objects.id = lab.surface_object_id
			LEFT JOIN pads ON pads.id = coalesce(wells.pad_id, surface_objects.pad_id)
			LEFT JOIN fields ON fields.id = wells.field_id
			WHERE lab.r = 1
		");
		$data = $this->db_ep->fetchAll($query);
		return $data;
	}

	// Только для использования в Хим. лаборатория - нефть. Для всяких watercuts не подходит, т.к. тут
	// `water_percent` из таблицы БД переводится в `water_volume_ratio` либо `water_percent` в зависимости от
	// `water_percent_is_water_volume_ratio`
	public function getLabMeasuresData($filter, $field) {
		if (count($filter) == 0 || empty($field))
			return [];
		$filter[$field] = ['>', 0];
		$data = $this->getList($filter);
		return $data;
	}

	public function addData($data, $additional = '', $update_old = false) {
		global $config;
		parent::addData($data, $additional);
		$res = $this->db_ep->getAffectedRows();
        if ($res!=0) { // if not duplicate
            //lets save watercuts
            $wcutClass = new Wells_Watercut();
            $wcutClass->updateFromLab($data);
        }
		return $res;
	}

	public function getLastLabMeasuresData($time, $filter = array()){
		$query = sql_pholder('
		SELECT lab_measures.*
		   FROM lab_measures
		   INNER JOIN
		  (
		  SELECT well_id, MAX(measure_time) as measure_time
		  FROM lab_measures WHERE COALESCE(water_content_mass, water_percent) IS NOT NULL AND measure_time < ? GROUP BY well_id
		  )tab2 USING(well_id, measure_time)
		  WHERE 1
		  ', $time);
		$query .= $this->getWhereByFilter($filter);
		$data = $this->db_ep->fetchAll($query);

		return $data;
	}

    /**
     * @param $time integer
     * @param $filter array
     * @return array|null
     */
    public function getLastOilDensity($time, $filter)
    {
        return $this->getLastByParameter("oil_density", $time, $filter);
    }

    /**
     * @param $time integer
     * @param $filter array
     * @return array|null
     */
    public function getLastWatercut($time, $filter)
    {
	    return $this->getLastByParameter("water_percent", $time, $filter);
    }

    private function getLastByParameter($parameter, $time, $filter)
    {
        if (!isset($parameter, $this->fieldsSettings)) {
            return [];
        }

        $query = sql_pholder("
            SELECT 
                lab_measures.*
            FROM lab_measures
            INNER JOIN (
                SELECT 
                    well_id, MAX(measure_time) as measure_time
                FROM lab_measures 
                WHERE {$parameter} > 0 AND measure_time < ? GROUP BY well_id
            )tab2 USING(well_id, measure_time)
            WHERE 1
        ", $time);

        $query .= $this->getWhereByFilter($filter);
        return $this->current_db->fetchAll($query);
	}

    /**
     * Растягиваем плотности нефти на требуемый период, используя для каждой даты наиболее свежий замер
     * @param $wells
     * @param $date_start
     * @param $date_finish
     * @return array
     */
    public function getOilDensityAllocatedByWellsPeriod($wells, $date_start, $date_finish) {
        $query = sql_pholder("
			SELECT measures.well_id, 
			  dates_of_period.date,  
			  SUBSTRING_INDEX(
				  GROUP_CONCAT(
					  measures.oil_density * 1000
				  ORDER BY measures.measure_first_actual_date DESC -- смотрим на свежесть
				), ',', 1) AS oil_density,  
			  GROUP_CONCAT(
				  CONCAT(measures.measure_first_actual_date, ' oil_density:', measures.oil_density) 
				ORDER BY measures.measure_first_actual_date DESC -- здесь та же сортировка, что и для предыдущего параметра 
				SEPARATOR ', '
			  ) AS debug_string
			FROM (
				SELECT 
				    well_id,
                    measure_first_actual_date,
                    DATE_ADD(measure_first_actual_date, INTERVAL 1 YEAR) as measure_last_actual_date,   
                    oil_density
				FROM (
				    SELECT 
				        well_id,
                        DATE(FROM_UNIXTIME(measure_time)) as measure_first_actual_date,   
                        AVG(oil_density) as oil_density
                    FROM lab_measures
                    WHERE oil_density IS NOT NULL AND well_id IN(?@wells)
                    GROUP BY well_id, DATE(FROM_UNIXTIME(measure_time))
				) t_inn
				ORDER BY well_id, measure_first_actual_date ASC
			) measures /* Все замеры по требуемым скважинам */
			JOIN (
				/*64 тысячи дней (175 лет) - максимальная длина периода. Захотите зачем-то больше - юзайте generator_1m, но здесь будет безбожно тормозить */
				SELECT DATE(ADDDATE(?date_start, INTERVAL n DAY)) AS date FROM generator_64k 
				WHERE n <= DATEDIFF(?date_finish, ?date_start)
			) dates_of_period /* Список всех дат от ?date_start до ?date_finish включительно */
			ON dates_of_period.date BETWEEN measures.measure_first_actual_date AND measures.measure_last_actual_date
			GROUP BY measures.well_id, dates_of_period.date;
		", [
                'wells' => $wells,
                'date_start' => $date_start,
                'date_finish'=> $date_finish
            ]
        );
        $data = $this->db_ep->fetchAll($query);
        $result = [];
        foreach ($data as $v) {
            $result [$v['date']] [$v['well_id']] = $v['oil_density'];
        }
        return $result;
    }
}