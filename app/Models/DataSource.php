<?php

namespace App\Models;

use Database\Factories\DataSourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataSource extends Model
{
    /** @use HasFactory<DataSourceFactory> */
    use HasFactory;

    protected function casts(): array {
        return [
            'last_sync_at' => 'datetime',
            'next_sync_publication_date' => 'datetime',
            'api_key' => 'encrypted',
        ];
    }
}
