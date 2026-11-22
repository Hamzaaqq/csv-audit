<?php

declare(strict_types=1);

namespace CsvAudit;

use RuntimeException;

final class CsvAudit
{
    /** @param resource $stream */
    public function inspect($stream, string $delimiter, array $required): array
    {
        $report = ['records' => 0, 'issue_count' => 0, 'issues' => [], 'omitted' => 0];
        $addIssue = static function (int $record, string $code, string $message) use (&$report): void {
            $report['issue_count']++;
            if (count($report['issues']) < 100) {
                $report['issues'][] = compact('record', 'code', 'message');
            } else {
                $report['omitted']++;
            }
        };

        // Read past a UTF-8 BOM before parsing a quoted first field.
        if (fread($stream, 3) !== "\xEF\xBB\xBF" && !rewind($stream)) {
            throw new RuntimeException('Could not rewind input.');
        }

        $header = fgetcsv($stream, 0, $delimiter, '"', '');
        if ($header === false) {
            if (!feof($stream)) {
                throw new RuntimeException('Could not read the header.');
            }
            $addIssue(1, 'missing_header', 'Expected a header record.');
            return $report;
        }

        $positions = [];
        $ambiguous = [];
        foreach ($header as $index => $value) {
            $name = trim((string) $value);
            if ($name === '') {
                $addIssue(1, 'blank_header', sprintf('Column %d has no name.', $index + 1));
            } elseif (array_key_exists($name, $positions)) {
                $addIssue(1, 'duplicate_header', sprintf('Column "%s" appears more than once.', $name));
                $ambiguous[$name] = true;
            } else {
                $positions[$name] = $index;
            }
        }

        $requiredPositions = [];
        foreach (array_unique($required) as $name) {
            if (!array_key_exists($name, $positions)) {
                $addIssue(1, 'missing_column', sprintf('Required column "%s" is missing.', $name));
            } elseif (!isset($ambiguous[$name])) {
                $requiredPositions[$name] = $positions[$name];
            }
        }

        $width = count($header);
        while (($row = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
            $record = ++$report['records'] + 1;
            if ($row === [null]) {
                $addIssue($record, 'blank_record', 'Record is empty.');
                continue;
            }
            if (count($row) !== $width) {
                $addIssue($record, 'row_width', sprintf('Expected %d columns, found %d.', $width, count($row)));
                continue;
            }

            foreach ($requiredPositions as $name => $index) {
                if (trim($row[$index]) === '') {
                    $addIssue($record, 'required_value', sprintf('Required column "%s" is blank.', $name));
                }
            }
        }

        if (!feof($stream)) {
            throw new RuntimeException('Could not finish reading input.');
        }

        return $report;
    }
}
