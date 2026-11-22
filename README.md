# CSV Audit

Check CSV exports before importing them into a database or handing them to someone else. CSV Audit reports duplicate or blank column names, rows with the wrong number of fields, and empty cells in columns you mark as required.

## Quick start

Requires PHP 8.2 or later. No Composer install is needed.

```sh
php bin/csv-audit examples/customers.csv --required=email
```

```text
3 data records checked, 2 issues.
Record 3 [required_value] Required column "email" is blank.
Record 4 [row_width] Expected 3 columns, found 2.
```

## Options

| Option | What it does |
| --- | --- |
| `--required=NAME` | Require a nonblank value in a column. Repeat for multiple columns. |
| `--delimiter=;` | Use a semicolon, a single-byte delimiter, or `--delimiter=tab`. |
| `--json` | Print a JSON report with `records`, `issue_count`, `issues`, and `omitted`. |
| `--help` | Show command usage. |

The first record is the header. Names are trimmed and matched case-sensitively. Record numbers include the header and count a quoted multiline value as part of one record. Rows with the wrong width are reported once and skipped by the required-value checks. Reports retain the first 100 issues and count all issues. Files are streamed one record at a time.

The CLI exits with `0` for a clean file, `1` for audit findings, and `2` for usage or file errors. It uses PHP's CSV parser and checks data structure and required values.

## Development

```sh
php tests/run.php
```
