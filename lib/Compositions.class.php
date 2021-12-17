<?php
class Water_Compositions extends ExcelParserFillable {
	protected $table = 'water_compositions';

	protected $fieldsSettings = array(
		'type_id' =>            FILTER_VALIDATE_INT,
		'well_id' =>            FILTER_VALIDATE_INT,
        'surface_object_id' =>  FILTER_VALIDATE_INT,
        'file_id' =>            FILTER_VALIDATE_INT,
		'measure_time' =>       FILTER_VALIDATE_INT,
		'receiving_date' =>     FILTER_SANITIZE_STRING,
		'analysis_date' =>      FILTER_SANITIZE_STRING,
		'density' =>            FILTER_VALIDATE_FLOAT,
		'cl' =>                 FILTER_VALIDATE_FLOAT,
		'so4' =>                FILTER_VALIDATE_FLOAT,
		'hco3' =>               FILTER_VALIDATE_FLOAT,
		'co3' =>                FILTER_VALIDATE_FLOAT,
		'ca' =>                 FILTER_VALIDATE_FLOAT,
		'mg' =>                 FILTER_VALIDATE_FLOAT,
		'na_k' =>               FILTER_VALIDATE_FLOAT,
		'mineralization' =>     FILTER_VALIDATE_FLOAT,
		'hardness' =>           FILTER_VALIDATE_FLOAT,
		'conductivity' =>       FILTER_VALIDATE_FLOAT,
		'ph' =>                 FILTER_VALIDATE_FLOAT,
		'activity_index' =>     FILTER_VALIDATE_INT,
        'suspended_solids' =>   FILTER_VALIDATE_FLOAT,
        'oil_products' =>       FILTER_VALIDATE_FLOAT,
        'temperature' =>        FILTER_VALIDATE_FLOAT,
        'sulfide' =>            FILTER_VALIDATE_FLOAT,
        'co2' =>                FILTER_VALIDATE_FLOAT,
        'o2' =>                 FILTER_VALIDATE_FLOAT,
        'fe3' =>                FILTER_VALIDATE_FLOAT,
        'fe' =>                 FILTER_VALIDATE_FLOAT,
        'mn' =>                 FILTER_VALIDATE_FLOAT,
		'h2s' =>                FILTER_VALIDATE_FLOAT,
		'svb' =>                FILTER_VALIDATE_FLOAT,
		'svb_express' =>        FILTER_VALIDATE_FLOAT,
		'ndpi_date' =>          FILTER_SANITIZE_STRING,
		'is_synthetic_ndpi' =>  FILTER_VALIDATE_INT,
		'note' =>               FILTER_SANITIZE_STRING,
		'laborant' =>           FILTER_SANITIZE_STRING,
	);

	protected $keyFields = [
		'type_id', 'well_id', 'surface_object_id', 'measure_time'
	];
	
	protected $requiredFields = array(
	);

	const TSNIPR_IONS =         1;
	const FIELD_LAB =           2;
	const TSNIPR_CORROSION =    3;
	const TSNIPR_BASE =         4;
	//const TYPE_LONG_2016_DEC =  5;     // вероятно, больше не используется. TODO: Посмотреть файл, который ожидался. Удалить константу из кода.

	protected $analysisTypes = [
		self::TSNIPR_IONS      =>   'ЦНИПР физ-хим состав',
		self::FIELD_LAB        =>   'Промысловая лаб-я',
		self::TSNIPR_CORROSION =>   'ЦНИПР коррозионный',
		self::TSNIPR_BASE      =>   'ЦНИПР базовый', // NICETODO: переименовать в "ЦНИПР базовый н+ппд" (совмещенный), добавить "ЦНИПР базовый ппд" (в него придется добавить требование об отсутствии определенных колонок)
	];

	protected $identificationPatterns = [
		self::TSNIPR_IONS => [
			'по результатам определения химического состава попутнодобываемых вод',
			'Содержание  ионов  в  воде',
		],
		self::FIELD_LAB => [
			'Промысловая химико - аналитическая лаборатория',
			'по результатам определения состава воды',
		],
		self::TSNIPR_CORROSION => [
			'Лаборатория коррозионных  исследований',
		],
		self::TSNIPR_BASE => [
			'по результатам исследования проб воды и нефти',
			'Содержание нефтепродуктов, мг/л'
		],
	];

	protected $rowsInMeasure = [
		self::TSNIPR_IONS => 3,
		self::FIELD_LAB => 1,
		self::TSNIPR_CORROSION => 1,
		self::TSNIPR_BASE => 1,
	];

	protected $columnsMap =[
		self::TSNIPR_IONS => [
			self::NECESSARY => [
				"point_name" =>             [["Место", "отбора"]],
				"date" =>                   [["Дата", "отбора"]],
				"time" =>                   [["Время", "отбора"]],
				"density" =>                [["Плотность,", "воды, г/см3"]],
				"ph" =>                     [["pH"]],
				"cl" =>                     [["Содержание ионов в воде", "Cl-"]],
				"so4" =>                    [["Содержание ионов в воде", "SO4 2-"]],
				"hco3" =>                   [["Содержание ионов в воде", "HCO3-"]],
				"co3" =>                    [["Содержание ионов в воде", "CO32-"]],
				"ca" =>                     [["Содержание ионов в воде", "Ca2+"]],
				"mg" =>                     [["Содержание ионов в воде", "Mg2+"]],
				"na_k" =>                   [["Содержание ионов в воде", "Na+ + K+"]],
				"mineralization" =>         [["Общая", "минерал."],["Общая", "минерализация"]],

			],
			self::OPTIONAL => [
				"hardness" =>               [["Жесткость,", "ммоль/дм3"]],
                "analysis_date" =>          [["Дата", "анализа"]],
			],
			self::IGNORE => [
				"unit" =>                   [["Ед.изм."]],
				"sul_cl" =>                 [["Характеристика по Сулину", "Na/Cl"]],
				"sul_so4" =>                [["Характеристика по Сулину", "(Na-Cl)/SO4"], ["", "(Na-Cl)/SO4"]],
				"sul_mg" =>                 [["Характеристика по Сулину", "(Cl-Na)/Mg"], ["", "(Cl-Na)/Mg"]],
				"type" =>                   [["Тип", "воды"]],
			],
		],
		self::FIELD_LAB => [
			self::NECESSARY => [
				"point_name" =>             [["Место отбора"]],
				"date" =>                   [["Дата отбора"]],
				"time" =>                   [["Время отбора"]],
				"note" =>                   [["Примечание"]],
				"density" =>                [["Плотность воды, г/см3"]],
			],
			self::OPTIONAL => [
				"ph" =>                     [["pH"]],
				"cl" =>                     [["Содержание ионов в воде", "Cl-"]],
				"suspended_solids" =>       [["КВЧ"]],
				"svb_express" =>            [["СВБ экспресс анализ, кл/см3"]],
				"receiving_date" =>         [["Дата поступления"]],
				"laborant" =>               [["Ф.И.О. лаборанта"]],
				"temperature" =>            [["T", "C"]],
				"analysis_date" =>          [["Дата анализа"]],
			],
			self::IGNORE => [
				"pad" =>                    [["Место отбора", "куст"]],
				"field" =>                  [["ЛУ"]],
				"measure_id" =>             [["№ п/п"]],
				"liquid_density" =>         [["Плотность жидкости, г/см3"]],
				"unit" =>                   [["Ед.изм."]],
			],
		],
		self::TSNIPR_CORROSION => [
			self::NECESSARY => [
				"point_name" =>             [["Место отбора пробы"]],
				"date" =>                   [["Дата отбора"]],
				"ph" =>                     [["рН, ед"]],
				"activity_index" =>         [["А ,ед"]],
				"co2" => 	                [["СО2, мг/дм3"]],
				"h2s" =>                    [["H2S, мг/дм3"]],
				"svb" => 	                [["СВБ кл/см3"]],
				"fe" =>                 	[["Fe общее, мг/дм3"]],
				"mn" => 	                [["Мn, мг/дм3"]],
				"so4" =>                 	[["SО4 , мг/дм3"]],
				"o2" => 	                [["О2, мг/дм3"]],
				"note" =>                 	[["Характеристика коррозионный агрессивности воды и рекомендации"]],
			],
			self::OPTIONAL => [
				"fe3" => 	                [["Fe3+, мг/дм3"]],
			],
			self::IGNORE => [
				"measure_id" =>             [["№ п/п"]],
			],
		],
		self::TSNIPR_BASE => [
			self::NECESSARY => [
				"point_name" =>             [["Место отбора", "скважина"]],
				"date" =>                   [["Дата отбора"]],
				"time" =>                   [["Время отбора"]],
				"density" =>                [["Плотность воды при 20оС, г/см3"]],
				"oil_products" =>           [["Содержание нефтепродуктов, мг/л"]],
			],
			self::OPTIONAL => [
                "suspended_solids" =>       [["Кол-во КВЧ, мех.примесей, мг/л"]],
                "analysis_date" =>          [["Дата анализа"]],
            ],
			self::IGNORE => [
				"measure_id" =>             [["№ п/п"]],
				"pad" =>                    [["Место отбора", "куст"]],
				"field" =>                  [["ЛУ"]],
				"water_percent" =>          [["Содержание воды, %"]],
				"oil_density" =>            [["Плотность нефти при 20оС, г/см3"]],
				"mechanical_amount_oil" =>  [["Кол-во мех.примесей, мг/л"]],
				"sulfur" =>                 [["Содержание мех.примесей, %"]],
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
		$row['measure_time'] = $datetime ? $datetime->getTimestamp() : null;
		if (empty($row['measure_time'])) {
            // нет ни даты замера, ни подозрения на корректную точку замера
			if (empty($row['point_name']) || $this->point_recognizer->getPointRecognized($row['point_name'])->error)
				return null; // ошибки нет, пропускаем строку
			else ExcelParser::throwLogicException("Не задана Дата отбора", $row_index);
		}

		return parent::prepareRowForDB($row, $column_types, $row_index, $non_key_fields);
	}

	/*
	 * Поля, которые есть в $columnsMap, но которых нет в таблице БД
	 */
	protected function getOnlyMapFields() {
		return['date', 'time', 'point_name'];
	}

	const UNIT_MG_EQV_L = 1;
	const UNIT_MG_L = 2;
	const UNIT_PERCEQV = 3;

	static $units = [
		self::UNIT_MG_EQV_L => 'мг-экв/л',
		self::UNIT_MG_L => 'мг/л',
	];

	public function getList($filter = array(), $order_by = 'measure_time DESC', $page = 0, $count = 0, $fields = '*'){
		$query = sql_pholder('
			SELECT
				water_compositions.*,
				COALESCE(wells.name, surface_objects.name) as well_name,
				pads.name as pad_name, 
				fields.name as field_name
			FROM water_compositions
			LEFT JOIN wells ON wells.id = water_compositions.well_id
			LEFT JOIN surface_objects ON surface_objects.id = water_compositions.surface_object_id
			LEFT JOIN pads ON pads.id = coalesce(wells.pad_id, ep.surface_objects.pad_id)
			LEFT JOIN fields ON fields.id = wells.field_id
			WHERE 1
		');
		$query .= $this->getWhereByFilter($filter, $order_by, false);
		$data = $this->db_ep->fetchAll($query);
		
		return $data;
	}
}