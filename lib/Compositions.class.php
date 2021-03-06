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
	//const TYPE_LONG_2016_DEC =  5;     // ????????????????, ???????????? ???? ????????????????????????. TODO: ???????????????????? ????????, ?????????????? ????????????????. ?????????????? ?????????????????? ???? ????????.

	protected $analysisTypes = [
		self::TSNIPR_IONS      =>   '?????????? ??????-?????? ????????????',
		self::FIELD_LAB        =>   '?????????????????????? ??????-??',
		self::TSNIPR_CORROSION =>   '?????????? ????????????????????????',
		self::TSNIPR_BASE      =>   '?????????? ??????????????', // NICETODO: ?????????????????????????? ?? "?????????? ?????????????? ??+??????" (??????????????????????), ???????????????? "?????????? ?????????????? ??????" (?? ???????? ???????????????? ???????????????? ???????????????????? ???? ???????????????????? ???????????????????????? ??????????????)
	];

	protected $identificationPatterns = [
		self::TSNIPR_IONS => [
			'???? ?????????????????????? ?????????????????????? ?????????????????????? ?????????????? ?????????????????????????????????? ??????',
			'????????????????????  ??????????  ??  ????????',
		],
		self::FIELD_LAB => [
			'?????????????????????? ???????????? - ?????????????????????????? ??????????????????????',
			'???? ?????????????????????? ?????????????????????? ?????????????? ????????',
		],
		self::TSNIPR_CORROSION => [
			'?????????????????????? ????????????????????????  ????????????????????????',
		],
		self::TSNIPR_BASE => [
			'???? ?????????????????????? ???????????????????????? ???????? ???????? ?? ??????????',
			'???????????????????? ????????????????????????????, ????/??'
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
				"point_name" =>             [["??????????", "????????????"]],
				"date" =>                   [["????????", "????????????"]],
				"time" =>                   [["??????????", "????????????"]],
				"density" =>                [["??????????????????,", "????????, ??/????3"]],
				"ph" =>                     [["pH"]],
				"cl" =>                     [["???????????????????? ?????????? ?? ????????", "Cl-"]],
				"so4" =>                    [["???????????????????? ?????????? ?? ????????", "SO4 2-"]],
				"hco3" =>                   [["???????????????????? ?????????? ?? ????????", "HCO3-"]],
				"co3" =>                    [["???????????????????? ?????????? ?? ????????", "CO32-"]],
				"ca" =>                     [["???????????????????? ?????????? ?? ????????", "Ca2+"]],
				"mg" =>                     [["???????????????????? ?????????? ?? ????????", "Mg2+"]],
				"na_k" =>                   [["???????????????????? ?????????? ?? ????????", "Na+ + K+"]],
				"mineralization" =>         [["??????????", "??????????????."],["??????????", "??????????????????????????"]],

			],
			self::OPTIONAL => [
				"hardness" =>               [["??????????????????,", "??????????/????3"]],
                "analysis_date" =>          [["????????", "??????????????"]],
			],
			self::IGNORE => [
				"unit" =>                   [["????.??????."]],
				"sul_cl" =>                 [["???????????????????????????? ???? ????????????", "Na/Cl"]],
				"sul_so4" =>                [["???????????????????????????? ???? ????????????", "(Na-Cl)/SO4"], ["", "(Na-Cl)/SO4"]],
				"sul_mg" =>                 [["???????????????????????????? ???? ????????????", "(Cl-Na)/Mg"], ["", "(Cl-Na)/Mg"]],
				"type" =>                   [["??????", "????????"]],
			],
		],
		self::FIELD_LAB => [
			self::NECESSARY => [
				"point_name" =>             [["?????????? ????????????"]],
				"date" =>                   [["???????? ????????????"]],
				"time" =>                   [["?????????? ????????????"]],
				"note" =>                   [["????????????????????"]],
				"density" =>                [["?????????????????? ????????, ??/????3"]],
			],
			self::OPTIONAL => [
				"ph" =>                     [["pH"]],
				"cl" =>                     [["???????????????????? ?????????? ?? ????????", "Cl-"]],
				"suspended_solids" =>       [["??????"]],
				"svb_express" =>            [["?????? ???????????????? ????????????, ????/????3"]],
				"receiving_date" =>         [["???????? ??????????????????????"]],
				"laborant" =>               [["??.??.??. ??????????????????"]],
				"temperature" =>            [["T", "C"]],
				"analysis_date" =>          [["???????? ??????????????"]],
			],
			self::IGNORE => [
				"pad" =>                    [["?????????? ????????????", "????????"]],
				"field" =>                  [["????"]],
				"measure_id" =>             [["??? ??/??"]],
				"liquid_density" =>         [["?????????????????? ????????????????, ??/????3"]],
				"unit" =>                   [["????.??????."]],
			],
		],
		self::TSNIPR_CORROSION => [
			self::NECESSARY => [
				"point_name" =>             [["?????????? ???????????? ??????????"]],
				"date" =>                   [["???????? ????????????"]],
				"ph" =>                     [["????, ????"]],
				"activity_index" =>         [["?? ,????"]],
				"co2" => 	                [["????2, ????/????3"]],
				"h2s" =>                    [["H2S, ????/????3"]],
				"svb" => 	                [["?????? ????/????3"]],
				"fe" =>                 	[["Fe ??????????, ????/????3"]],
				"mn" => 	                [["??n, ????/????3"]],
				"so4" =>                 	[["S??4 , ????/????3"]],
				"o2" => 	                [["??2, ????/????3"]],
				"note" =>                 	[["???????????????????????????? ???????????????????????? ?????????????????????????? ???????? ?? ????????????????????????"]],
			],
			self::OPTIONAL => [
				"fe3" => 	                [["Fe3+, ????/????3"]],
			],
			self::IGNORE => [
				"measure_id" =>             [["??? ??/??"]],
			],
		],
		self::TSNIPR_BASE => [
			self::NECESSARY => [
				"point_name" =>             [["?????????? ????????????", "????????????????"]],
				"date" =>                   [["???????? ????????????"]],
				"time" =>                   [["?????????? ????????????"]],
				"density" =>                [["?????????????????? ???????? ?????? 20????, ??/????3"]],
				"oil_products" =>           [["???????????????????? ????????????????????????????, ????/??"]],
			],
			self::OPTIONAL => [
                "suspended_solids" =>       [["??????-???? ??????, ??????.????????????????, ????/??"]],
                "analysis_date" =>          [["???????? ??????????????"]],
            ],
			self::IGNORE => [
				"measure_id" =>             [["??? ??/??"]],
				"pad" =>                    [["?????????? ????????????", "????????"]],
				"field" =>                  [["????"]],
				"water_percent" =>          [["???????????????????? ????????, %"]],
				"oil_density" =>            [["?????????????????? ?????????? ?????? 20????, ??/????3"]],
				"mechanical_amount_oil" =>  [["??????-???? ??????.????????????????, ????/??"]],
				"sulfur" =>                 [["???????????????????? ??????.????????????????, %"]],
			],
		],
	];

	/*
	 * ???? ???????????? ?? ???????????????????? ???? Worksheet ??????????????, ???????????????? ????????????, ???????????????? ???????????? ?? $table ?? ???? (???? ?? ?????????????????????? ?????????????? ??????????????????)
	 * ?????????????? ??????????????:
	 * $row:
	 *      [column_key1 => mix1, column_key2 => mix2, ],
	 *      ?????? mix - ?????????????????? ???????????????? ?????? ???????????? ???????? DateTime
	 * $column_types:
	 *      [column_key1 => column_type1, column_key2 => column_type2, ],
	 *      ?????? column_type ?????????????????????? ???????????????? ?????????? ???? ????????????????, ?????????????? ?????????? ExcelParserFillable::COLUMN_...
	 * $row_index:
	 *      ?????????????????????????? #??????????????????????
	 * $non_key_fields
	 */
	public function prepareRowForDB($row, $column_types, $row_index, $non_key_fields)
	{
		$datetime = $this->concatDateTime($row, 'date', 'time', $row_index);
		$row['measure_time'] = $datetime ? $datetime->getTimestamp() : null;
		if (empty($row['measure_time'])) {
            // ?????? ???? ???????? ????????????, ???? ???????????????????? ???? ???????????????????? ?????????? ????????????
			if (empty($row['point_name']) || $this->point_recognizer->getPointRecognized($row['point_name'])->error)
				return null; // ???????????? ??????, ???????????????????? ????????????
			else ExcelParser::throwLogicException("???? ???????????? ???????? ????????????", $row_index);
		}

		return parent::prepareRowForDB($row, $column_types, $row_index, $non_key_fields);
	}

	/*
	 * ????????, ?????????????? ???????? ?? $columnsMap, ???? ?????????????? ?????? ?? ?????????????? ????
	 */
	protected function getOnlyMapFields() {
		return['date', 'time', 'point_name'];
	}

	const UNIT_MG_EQV_L = 1;
	const UNIT_MG_L = 2;
	const UNIT_PERCEQV = 3;

	static $units = [
		self::UNIT_MG_EQV_L => '????-??????/??',
		self::UNIT_MG_L => '????/??',
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