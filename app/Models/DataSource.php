<?php

namespace App\Models;

use Database\Factories\DataSourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataSource extends Model
{
    /** @use HasFactory<DataSourceFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at'];

    protected function casts(): array
    {
        return [
            'last_sync_at' => 'datetime',
            'is_active' => 'boolean',
            'sync_start_time' => 'time',
            'last_published_at' => 'datetime',
            'filters' => 'array',
        ];
    }

    public function canBeDispatched(): bool
    {
        if ($this->last_sync_at === null) {
            return true;
        }

        return now()->diffInMinutes($this->last_sync_at) >= $this->sync_interval;
    }
}
