<?php

namespace App\Models;

use CodeIgniter\Model;

class ItemModel extends Model
{
    protected $table = 'itemlist';
    protected $primaryKey = 'ID';
    protected $allowedFields = ['itemUID', 'itemName', 'itemEquipType', 'itemCertNo', 'itemLocation', 'itemStatus'];

    // Method to truncate a specified table
    public function truncateTable($tableName)
    {
        return $this->db->query("TRUNCATE TABLE " . $this->db->escapeIdentifier($tableName));
    }
}
