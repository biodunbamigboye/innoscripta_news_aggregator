<?php

namespace App\Contracts;

use App\Models\DataSource;

interface DataSourceContract
{
    public function getModel(): DataSource;

    public function resolveParameters(DataSource $dataSource): array;

    public function storeToDatabase(DataSource $dataSource, array $news): void;
}
