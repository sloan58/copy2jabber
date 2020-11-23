<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ucm extends Model
{
    use HasFactory;

    public $connection = 'phoenix';

    /**
     * Decrypt the Password when accessing
     *
     * @param $value
     * @return string
     */
    public function getPasswordAttribute($value)
    {
        return decrypt($value);
    }
}
