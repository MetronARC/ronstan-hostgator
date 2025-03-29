<?php

namespace App\Models;

use CodeIgniter\Model;

class tmpRFID extends Model
{
    protected $table = 'tmprfid'; // Name of the table
    protected $primaryKey = 'id'; // Adjust this if your primary key is different
    protected $allowedFields = ['tmpUID', 'created_at']; // Add other fields as necessary
}
