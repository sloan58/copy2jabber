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
        try {
            return decrypt($value);
        } catch(\Exception $e) {
            logger()->debug('Ucm@getPasswordAttribute', ['message' => $e->getMessage()]);
            return $value;
        }
    }
}
