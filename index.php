<?php

require 'vendor/autoload.php';

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

const FORMAT = 'd-m-Y';

$getBonusDay = fn(Carbon $day) => $day->isWeekend() ? $day->next(Carbon::WEDNESDAY)->format(FORMAT) : $day->format(FORMAT);
$getPaymentDay = fn(Carbon $day) => $day->isWeekend() ? $day->previous(Carbon::FRIDAY) : $day;

/**
 * Generates the data for the current and remaining months in the year.
 *
 * @param Carbon $today
 * @param callable $getBonusDay
 * @param callable $getPaymentDay
 * @return array
 */
function generateData(Carbon $today, callable $getBonusDay, callable $getPaymentDay): array
{
    $data = [];
    $endOfMonth = $today->copy()->endOfMonth();

    // Current month logic
    $bonusDay = $today->copy()->day(15);
    $payment = $today->isWeekend() && $getPaymentDay($endOfMonth) < $today ? '-' : $getPaymentDay($endOfMonth)->format(FORMAT);
    $data[] = [
        'month' => $today->format('F'),
        'payment' => $payment,
        'bonus' => $today->day <= 15 ? $getBonusDay($bonusDay) : '-',
    ];

    // Not current month logic for remaining months
    for ($month = $today->month + 1; $month <= 12; $month++) {
        $bonusDay = $today->copy()->month($month)->day(15);
        $endOfMonth = $today->copy()->month($month)->endOfMonth();
        $data[] = [
            'month' => $today->copy()->month($month)->day(1)->format('F'),
            'payment' => $getPaymentDay($endOfMonth)->format(FORMAT),
            'bonus' => $getBonusDay($bonusDay),
        ];
    }

    return $data;
}

/**
 * Outputs the data as a CSV file for download.
 *
 * @param array $data
 * @return void
 */
function outputCSV(array $data): void
{
    header('Content-type: text/csv');
    header('Content-Disposition: attachment; filename="demo.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $file = fopen('php://output', 'w');
    fputcsv($file, ['Month', 'Payment', 'Bonus']);

    foreach ($data as $row) {
        fputcsv($file, $row);
    }

    fclose($file);
}

try {
    $today = isset($argv[1]) ? Carbon::parse($argv[1]) : Carbon::now();
} catch (InvalidFormatException $e) {
    echo "\033[31m{$e->getMessage()}\033[0m" . PHP_EOL;
    exit(1);
}

// Generate data using the defined arrow functions.
$data = generateData($today, $getBonusDay, $getPaymentDay);

// Output the data as CSV.
outputCSV($data);

exit();
