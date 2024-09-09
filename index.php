<?php

require 'vendor/autoload.php';

use Carbon\Carbon;
use Carbon\CarbonInterface as Week;
use Carbon\Exceptions\InvalidFormatException;

const FORMAT = 'd-m-Y';

function getBonusDay($day)
{
    return $day->isWeekday() ? $day->next(Week::WEDNESDAY)->format(FORMAT) : $day->format(FORMAT);
}

function getPaymentDay($day)
{
    return $day->isWeekday() ? $day->previous(Week::FRIDAY) : $day;
}

$data = [];

$today = Carbon::now();
$endOfMonth = $today->copy()->endOfMonth();
if(isset($argv[1])){
    try {
        $today = Carbon::parse($argv[1]);
        $endOfMonth = $today->copy()->endOfMonth();
    } catch (InvalidFormatException $e) {
        echo "\033[31m" . $e->getMessage() . "\033[0m" . PHP_EOL;
        die();
    }
}

// Current month logic
$bonusDay = $today->copy()->day(15);
$data[] = [
    'month' => $today->format('F'),
    'payment' => $today->isWeekday() && getPaymentDay($endOfMonth) < $today ? '-' : getPaymentDay($endOfMonth)->format(FORMAT),
    'bonus' => $today->day <= 15 ? getBonusDay($bonusDay) : '-'
];

// Not current month logic
for ($i = $today->month + 1; $i <= 12; $i++) {

    $bonusDay = $today->copy()->month($i)->day(15);
    $endOfMonth = $today->copy()->month($i)->endOfMonth();

    $data[] = [
        'month' => $today->copy()->month($i)->day(1)->format('F'),
        'payment' => getPaymentDay($endOfMonth)->format(FORMAT),
        'bonus' => getBonusDay($bonusDay)
    ];
}

// output headers so that the file is downloaded rather than displayed
header('Content-type: text/csv');
header('Content-Disposition: attachment; filename="demo.csv"');

// do not cache the file
header('Pragma: no-cache');
header('Expires: 0');

// create a file pointer connected to the output stream
$file = fopen('php://output', 'w');

// send the column headers
fputcsv($file, ['Month', 'Payment', 'Bonus']);

// output each row of the data
foreach ($data as $row) {
    fputcsv($file, $row);
}

exit();