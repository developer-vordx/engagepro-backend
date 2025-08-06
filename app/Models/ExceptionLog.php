<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExceptionLog extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'message', 'trace', 'file', 'line', 'url', 'input', 'user_id','code',
    ];
}
