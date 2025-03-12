<?php

namespace App\Contracts;

use App\Models\DataSource;

interface DataSourceContract
{
    public function getModel(): DataSource;
}
