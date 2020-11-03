<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ucm extends Model
{
    use HasFactory;

    public $connection = 'phoenix';
}
