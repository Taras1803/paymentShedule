<?php

require 'vendor/autoload.php';

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

const WEEKEND = [Carbon::SATURDAY, Carbon::SUNDAY];

function getBonusDay($day)
{
    return in_array($day->dayOfWeek, WEEKEND) ? $day->next(Carbon::WEDNESDAY) : $day;
}

function getPaymentDay($day)
{
    return in_array($day->dayOfWeek, WEEKEND) ? $day->previous(Carbon::FRIDAY) : $day;
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
    'payment' => in_array($today->dayOfWeek, WEEKEND) && getPaymentDay($endOfMonth) < $today ? '-' : getPaymentDay($endOfMonth)->format('d-m-Y'),
    'bonus' => $today->day <= 15 ? getBonusDay($bonusDay)->format('d-m-Y') : '-'
];
// Not current month logic
for ($i = $today->month + 1; $i <= 12; $i++) {
    $startOfMonth = Carbon::parse($today->year . '-' . $i . '-01');
    $bonusDay = Carbon::parse($today->year . '-' . $i . '-15');
    $endOfMonth = Carbon::parse($today->year . '-' . $i . '-01')->endOfMonth();
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