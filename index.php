<?php

require 'vendor/autoload.php';

use Carbon\Carbon;
use Carbon\CarbonInterface as Week;
use Carbon\Exceptions\InvalidFormatException;


function getBonusDay($day)
{
    return $day->isWeekday() ? $day->next(Week::WEDNESDAY) : $day;
}

function getPaymentDay($day)
{
    return $day->isWeekday() ? $day->previous(Week::FRIDAY) : $day;
}

$data = [];

$today = Carbon::now();
$endOfMonth = Carbon::now()->endOfMonth();
if(isset($argv[1])){
    try {
        Carbon::parse($argv[1]);
        $today = Carbon::createFromFormat('d-m-Y', $argv[1]);
        $endOfMonth = Carbon::createFromFormat('d-m-Y', $argv[1])->endOfMonth();
    } catch (InvalidFormatException $e) {
        echo 'invalid date format';
        die();
    }
}

// Current month logic
$bonusDay = Carbon::parse($today->year . '-' . $today->month . '-15');
$data[] = [
    'month' => $today->format('F'),
    'payment' => $today->isWeekday() && getPaymentDay($endOfMonth) < $today ? '-' : getPaymentDay($endOfMonth)->format('d-m-Y'),
    'bonus' => $today->day <= 15 ? getBonusDay($bonusDay)->format('d-m-Y') : '-'
];
// Not current month logic
for ($i = $today->month + 1; $i <= 12; $i++) {

    $startOfMonth = $today->copy()->startOfYear()->month($i)->day(1);
    $bonusDay = $today->copy()->startOfYear()->month($i)->day(15);
    $endOfMonth = $today->copy()->startOfYear()->month($i)->endOfMonth();

    $data[] = [
        'month' => $startOfMonth->format('F'),
        'payment' => getPaymentDay($endOfMonth)->format('d-m-Y'),
        'bonus' => getBonusDay($bonusDay)->format('d-m-Y')
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