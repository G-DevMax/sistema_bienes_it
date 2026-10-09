<?php
require 'vendor/autoload.php';
$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xls');
$spreadsheet = $reader->load('resources/plantillas/fc11_planilla_movimiento.xls');
$sheet = $spreadsheet->getSheet(0);
for ($i=50; $i<=65; $i++) {
    for ($j='A'; $j<='I'; $j++) {
        $val = $sheet->getCell($j.$i)->getValue();
        if ($val) {
            echo "Cell {$j}{$i}: {$val}\n";
        }
    }
}
