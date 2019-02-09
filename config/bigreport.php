<?php
use Samdevbr\Bigreport\Writer\Csv;

return [
    'csv' => [
        'delimiter' => ',',
        'enclosure', '"',
        'line_ending' => PHP_EOL
    ],

    /**
     * Extension Mapping
     *
     * Tells which writer each extension should use
     * to generate the report.
     *
     */
    'extension_mapping' => [
        'csv' => Csv::class
    ]
];
