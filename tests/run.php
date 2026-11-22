<?php

declare(strict_types=1);

require __DIR__ . '/../src/CsvAudit.php';

use CsvAudit\CsvAudit;

$cases = [
    'quoted fields with BOM and multiline values' => [
        "\xEF\xBB\xBF\"name\",email\r\n\"Doe,\nJane\",jane@example.test\r\n",
        ',', ['name', 'email'], 1, [],
    ],
    'empty file' => ['', ',', [], 0, ['missing_header']],
    'duplicate, blank and missing headers' => [
        "name,name,\nAlice,Bob,value\n", ',', ['name', 'email'], 1,
        ['duplicate_header', 'blank_header', 'missing_column'],
    ],
    'width, blank record and required values' => [
        "name;id\nAlice\n\nBob; \nCharles;0\n", ';', ['id'], 4,
        ['row_width', 'blank_record', 'required_value'],
    ],
    'bounded findings' => [
        "name,id\n" . str_repeat("Alice,\n", 105), ',', ['id'], 105,
        array_fill(0, 105, 'required_value'),
    ],
];

foreach ($cases as $label => [$csv, $delimiter, $required, $records, $codes]) {
    $stream = fopen('php://memory', 'w+b');
    fwrite($stream, $csv);
    rewind($stream);
    $report = (new CsvAudit())->inspect($stream, $delimiter, $required);
    fclose($stream);

    if ($report['records'] !== $records
        || $report['issue_count'] !== count($codes)
        || $report['omitted'] !== max(0, count($codes) - 100)
        || array_column($report['issues'], 'code') !== array_slice($codes, 0, 100)
    ) {
        fwrite(STDERR, "FAIL: $label\n" . var_export($report, true) . "\n");
        exit(1);
    }
    echo "PASS: $label\n";
}
