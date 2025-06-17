<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApiToken extends Model
{
    use HasFactory,softDeletes;

    protected $fillable = [
      'identifier' ,'service','access_token','refresh_token','expires_at'
    ];
}
