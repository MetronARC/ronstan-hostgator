<?php

namespace App\Models;

use CodeIgniter\Model;

class RfidModel extends Model
{
    protected $table = 'tmprfid';
    protected $primaryKey = 'ID'; // Change this if your primary key is different
    protected $allowedFields = ['tmpUID', 'created_at'];
}