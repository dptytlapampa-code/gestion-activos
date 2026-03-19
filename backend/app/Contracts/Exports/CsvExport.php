<?php

namespace App\Contracts\Exports;

use App\Support\Exports\CsvColumn;
use Illuminate\Database\Eloquent\Builder;

interface CsvExport
{
    public function fileName(): string;

    public function query(): Builder;

    public function chunkSize(): int;

    /**
     * @return array<int, CsvColumn>
     */
    public function columns(): array;
}
