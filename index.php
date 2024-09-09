<?php

require 'vendor/autoload.php';

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

const FORMAT = 'd-m-Y';

// Define lambda functions for bonus and payment days
$getBonusDay = fn(Carbon $day) => $day->isWeekend() ? $day->next(Carbon::WEDNESDAY)->format(FORMAT) : $day->format(FORMAT);
$getPaymentDay = fn(Carbon $day) => $day->isWeekend() ? $day->previous(Carbon::FRIDAY)->format(FORMAT) : $day->format(FORMAT);

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
    $currentMonth = $today->copy();

    // Current month logic
    $bonusDay = $currentMonth->copy()->day(15);
    $endOfMonth = $currentMonth->copy()->endOfMonth();
    $payment = $today->isWeekend() && Carbon::parse($getPaymentDay($endOfMonth)) < $today ? '-' : $getPaymentDay($endOfMonth);

    $data[] = [
        'month' => $currentMonth->format('F'),
        'payment' => $payment,
        'bonus' => $today->day <= 15 ? $getBonusDay($bonusDay) : '-',
    ];

    // Not current month logic for remaining months;
    for ($i = 1; $i <= 12 - $today->month; $i++) {
        // Add one month sequentially
        $currentMonth->addMonthNoOverflow();
        $bonusDay = $currentMonth->copy()->day(15);
        $endOfMonth = $currentMonth->copy()->endOfMonth();
        $paymentDay = $getPaymentDay($endOfMonth);

        $data[] = [
            'month' => $currentMonth->format('F'),
            'payment' => $paymentDay,
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
