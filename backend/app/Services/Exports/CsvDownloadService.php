<?php

namespace App\Services\Exports;

use App\Contracts\Exports\CsvExport;
use App\Support\Exports\CsvColumn;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvDownloadService
{
    private const DELIMITER = ';';

    public function download(CsvExport $export): StreamedResponse
    {
        $fileName = $export->fileName();

        return response()->streamDownload(function () use ($export): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                return;
            }

            $columns = $export->columns();

            fwrite($output, "\xEF\xBB\xBF");

            fputcsv(
                $output,
                array_map(
                    static fn (CsvColumn $column): string => $column->heading,
                    $columns
                ),
                separator: self::DELIMITER,
                enclosure: '"',
                escape: '\\',
                eol: "\r\n"
            );

            foreach ($export->query()->lazy($export->chunkSize()) as $row) {
                fputcsv(
                    $output,
                    array_map(
                        static fn (CsvColumn $column): string => $column->value($row),
                        $columns
                    ),
                    separator: self::DELIMITER,
                    enclosure: '"',
                    escape: '\\',
                    eol: "\r\n"
                );
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
