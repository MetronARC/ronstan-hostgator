<?php

namespace App\Controllers;

use App\Models\ItemModel;

class User extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index(): string
    {
        // Initialize variables for Active Machine Count
        $totalRowCount = 0;

        // Query to get all tables that start with 'area'
        $tablesQuery = $this->db->query("SHOW TABLES LIKE 'area%'");
        $areaTables = $tablesQuery->getResultArray();

        // Loop through the table names and count the rows
        foreach ($areaTables as $table) {
            $tableName = current($table); // Get the table name from result
            $rowCountQuery = $this->db->query("SELECT COUNT(*) as count FROM $tableName WHERE State = 'ON'");
            $rowCount = $rowCountQuery->getRow()->count;
            $totalRowCount += $rowCount;
        }

        // Set timezone to Asia/Jakarta
        $timezone = new \DateTimeZone('Asia/Jakarta');
        $currentDateTime = new \DateTime('now', $timezone);
        $dateString = $currentDateTime->format('Y-m-d'); // Format to match your database date format

        // Initialize total arc time in seconds
        $totalArcTimeInSeconds = 0;

        // Query to get all tables that start with 'machinehistory'
        $historyTablesQuery = $this->db->query("SHOW TABLES LIKE 'machinehistory%'");
        $historyTables = $historyTablesQuery->getResultArray();

        // Loop through the machinehistory tables and sum ArcTotal for today
        foreach ($historyTables as $table) {
            $tableName = current($table); // Get the table name from result

            // Fetch all rows with today's date and sum ArcTotal
            $arcTotalQuery = $this->db->query("
                SELECT TIME_TO_SEC(ArcTotal) as ArcTotalSeconds 
                FROM $tableName 
                WHERE DATE(Date) = ?", [$dateString]);
            
            $arcTotals = $arcTotalQuery->getResultArray();
            
            // Sum up the ArcTotal in seconds
            foreach ($arcTotals as $arcRow) {
                $totalArcTimeInSeconds += $arcRow['ArcTotalSeconds'];
            }
        }

        // Convert total seconds to H:i:s format
        $totalArcHours = floor($totalArcTimeInSeconds / 3600);
        $totalArcMinutes = floor(($totalArcTimeInSeconds % 3600) / 60);
        $totalArcSeconds = $totalArcTimeInSeconds % 60;

        // Format the time as H:i:s, even if hours exceed 24
        $formattedArcTime = sprintf('%02d:%02d:%02d', $totalArcHours, $totalArcMinutes, $totalArcSeconds);

        // Additional logic to handle lastSeen comparison
        $machineQuery = $this->db->query("SELECT MachineID, lastSeen FROM machine");
        $machines = $machineQuery->getResultArray();

        foreach ($machines as $machine) {
            // Convert lastSeen to Asia/Jakarta timezone if it is not null
            $lastSeen = $machine['lastSeen'] ? new \DateTime($machine['lastSeen'], $timezone) : null;
            
            if ($lastSeen) {
                $interval = $currentDateTime->getTimestamp() - $lastSeen->getTimestamp();
                
                if ($interval > 120) {
                    $machineID = $machine['MachineID'];
                    
                    // Update the State column to "OFF" in the area1 table where MachineID matches
                    $this->db->query("UPDATE area1 SET State = 'OFF' WHERE MachineID = ?", [$machineID]);

                    // Update lastSeen in the machine table to NULL and increment WeldID
                    $this->db->query("UPDATE machine SET lastSeen = NULL, WeldID = WeldID + 1 WHERE MachineID = ?", [$machineID]);
                }
            }
        }

        // Pass the total row count and machine uptime to the view
        $data['title'] = 'Dashboard';
        $data['sidebarData'] = 'dashboard';
        $data['activeMachineCount'] = $totalRowCount; // Pass the active machine count
        $data['machineUptime'] = $formattedArcTime; // Pass the total machine uptime for today

        return view('user/index', $data);
    }

    public function record(): string
    {
        // Prepare data for the view
        $data['title'] = 'Create Record Page';
        $data['sidebarData'] = 'create-record';

        return view('user/record/add', $data);
    }

    public function search()
    {
        $model = new ItemModel();

        // Get search criteria from the form
        $uid = $this->request->getPost('uid');
        $itemName = $this->request->getPost('item-name');
        $equipType = $this->request->getPost('equip-type');
        $certNo = $this->request->getPost('cert-no');
        $location = $this->request->getPost('location');
        $status = $this->request->getPost('status');

        // Build the query
        $builder = $model->builder();
        if ($uid) {
            $builder->like('itemUID', $uid);
        }
        if ($itemName) {
            $builder->like('itemName', $itemName);
        }
        if ($equipType) {
            $builder->like('itemEquipType', $equipType);
        }
        if ($certNo) {
            $builder->like('itemCertNo', $certNo);
        }
        if ($location) {
            $builder->like('itemLocation', $location);
        }
        if ($status) {
            $builder->like('itemStatus', $status);
        }

        // Execute the query and get results
        $items = $builder->get()->getResultArray();

        // Return results as JSON
        return $this->response->setJSON($items);
    }
}