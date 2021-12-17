<?php

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

ini_set('memory_limit', '2G');

class Water_CompositionsManagement_Controller extends ControllerBase
{
    protected $permission_name = 'water_compositions';
    protected $permission_type = Permissions::WRITE;
    protected $check_permission = true;

    public function deleteItemAction()
    {
        $id = get_param($_POST, 'id');

        if (is_numeric($id)) {
            $itemClass = new Water_Compositions();
            $itemClass->delete($id);
        } else {
            echo json_encode([
                'error' => 'Неизвестный идентификатор записи',
            ]);
        }
    }

    public function uploadAction () {
        $tz = date_default_timezone_get();
        date_default_timezone_set('Asia/Yekaterinburg');
        try {
            $excelParserClass = new ExcelParser(new Water_Compositions());
            $excelParserClass->getAllSheets('uploads/water_compositions/{FILENAME}');
            $excelParserClass->parseAllData();
        } catch (LogicException $e) {
            printStringError("Ошибка: " . $e->getMessage());
        }
        date_default_timezone_set($tz);
    }
}