<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\Database\Exceptions\DatabaseException;

class NFCRead extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index(): string
    {
        $data['title'] = 'NFC Read';
        $data['sidebarData'] = 'nfc';
        return view('nfc/read', $data);
    }

    public function receive()
    {
        $db = \Config\Database::connect();
        $data = $this->request->getJSON(true); // Get JSON data as an associative array

        if (!isset($data['serialNumber'])) {
            return $this->response->setJSON(['message' => 'No serial number received'])->setStatusCode(400);
        }

        try {
            $builder = $db->table('tmpnfc');
            $builder->where('ID', 1);
            $builder->update(['nfcData' => $data['serialNumber']]);

            return $this->response->setJSON(['message' => 'Serial number updated successfully']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['message' => 'Database error: ' . $e->getMessage()])->setStatusCode(500);
        }
    }
}
