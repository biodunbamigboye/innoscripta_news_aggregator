<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory, HasUlids;

    protected $guarded = ['id', 'created_at'];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
