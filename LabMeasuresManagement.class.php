<?php

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

ini_set('memory_limit', '256M');
set_time_limit(0);

class LabMeasuresManagement_Controller extends ControllerBase
{
    protected $permission_name = 'labmeasures';
    protected $permission_type = Permissions::WRITE;
    protected $check_permission = true;

    public function deleteItemAction()
    {
        $id = get_param($_POST, 'id');
        if (is_numeric($id)) {
            $itemClass = new LabMeasures();
            $itemClass->delete($id);
        } else {
            echo json_encode([
                'error' => 'Неизвестный идентификатор записи',
            ]);
        }
    }

    public function uploadAction()
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set('Asia/Yekaterinburg');
        try {
            $excelParserClass = new ExcelParser(new LabMeasures());
            $excelParserClass->getAllSheets('uploads/lab_measures/{FILENAME}');
            $excelParserClass->parseAllData();
        } catch (LogicException $e) {
            printStringError("Ошибка: " . $e->getMessage());
        }
        date_default_timezone_set($tz);
    }


}
