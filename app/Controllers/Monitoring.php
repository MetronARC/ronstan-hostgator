<?php

namespace App\Controllers;

use CodeIgniter\Database\Exceptions\DatabaseException;

class Monitoring extends BaseController
{
    public function index(): string
    {
        // Load the database
        $db = \Config\Database::connect();

        try {
            // Query to get all rows from tarea table
            $query = $db->query("SELECT * FROM tarea");
            $areas = $query->getResultArray();

            // Initialize an array to store area data
            $areaData = [];

            foreach ($areas as $area) {
                // Fetch the machine states for this area
                $machineStateQuery = $db->query("SELECT State FROM machine WHERE areaID = ?", [$area['areaID']]);
                $machineStates = $machineStateQuery->getResultArray();

                // Add the area data and machine states to the areaData array
                $areaData[] = [
                    'areaID' => $area['areaID'],
                    'areaName' => $area['areaName'],
                    'machines' => $machineStates
                ];
            }

            // Pass the data to the view
            $data['title'] = 'Monitoring Page';
            $data['sidebarData'] = 'monitoring';
            $data['areas'] = $areaData; // Pass area data to the view

        } catch (DatabaseException $e) {
            // Handle any database exceptions
            return $e->getMessage();
        }

        return view('user/monitoring/monitoring', $data);
    }


    public function activeMachine($areaId): string
    {
        $db = \Config\Database::connect();

        // Get area name
        $areaQuery = $db->query("SELECT areaName FROM tarea WHERE areaID = ?", [$areaId]);
        $area = $areaQuery->getRow();

        // Fetch machines for this area
        $query = $db->query("SELECT * FROM machine WHERE areaID = ?", [$areaId]);
        $results = $query->getResultArray();

        // Prepare data for the view
        $data['title'] = 'Active Machine Page';
        $data['sidebarData'] = 'monitoring';
        $data['areaName'] = $area->areaName;
        $data['areaId'] = $areaId;
        $data['machines'] = $results;

        return view('user/monitoring/activeMachine', $data);
    }

    public function machineDetails($machineId): string
    {
        $db = \Config\Database::connect();

        try {
            // Fetch machine details
            $query = $db->query("SELECT 
                m.MachineID as machineId,
                m.State,
                m.lastBeat,
                j.jobRFID,
                j.jobQR,
                j.job_number,
                j.job_name,
                j.job_description,
                w.weldMetalRFID,
                w.weldMetal_batchNumber,
                w.weldMetal_material,
                w.weldMetalQR,
                w.weldMetal_certNo,
                e.cardUID,
                e.Name,
                e.welder_image
            FROM machine m
            LEFT JOIN tjobdata j ON m.job_rfid = j.jobRFID
            LEFT JOIN tweldmetaldata w ON m.weld_metal_rfid = w.weldMetalRFID
            LEFT JOIN employee e ON m.user_rfid = e.cardUID
            WHERE m.MachineID = ?", [$machineId]);

            $results = $query->getResultArray();

            if (empty($results)) {
                return 'Machine not found';
            }

            // Prepare data for the view
            $data = [
                'title' => 'Active Machine Page',
                'sidebarData' => 'monitoring',
                'machineId' => $machineId,
                'machines' => $results,
            ];

            return view('user/monitoring/machineDetails', $data);

        } catch (\Exception $e) {
            return 'Error loading machine details';
        }
    }

    public function getArcTotal($machineId)
    {
        $db = \Config\Database::connect();

        try {
            // Fetch WeldID for the given machine
            $queryWeldID = $db->query("SELECT WeldID FROM machine WHERE id = ?", [$machineId]);
            $resultWeldID = $queryWeldID->getRowArray();

            if ($resultWeldID) {
                $weldID = $resultWeldID['WeldID'];

                // Fetch SUM of ArcTotal (TIME format)
                $queryArcTotal = $db->query("SELECT SEC_TO_TIME(SUM(TIME_TO_SEC(ArcTotal))) AS TotalArcTime FROM machinehistory WHERE WeldID = ?", [$weldID]);
                $arcTotal = $queryArcTotal->getRowArray();
            } else {
                $arcTotal = ['TotalArcTime' => '00:00:00']; // Default if no data found
            }

            return $this->response->setJSON(['arcTotal' => $arcTotal['TotalArcTime']]);

        } catch (\Exception $e) {
            return $this->response->setJSON(['arcTotal' => '00:00:00']);
        }
    }

    public function getMachineState($areaId)
    {
        $db = \Config\Database::connect();

        try {
            // Validate areaId
            if (empty($areaId)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false
                ]);
            }

            // Check if area exists
            $areaCheck = $db->query("SELECT areaID FROM tarea WHERE areaID = ?", [$areaId]);
            if ($areaCheck->getNumRows() === 0) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false
                ]);
            }

            // Fetch only the necessary fields to reduce payload size
            $query = $db->query("SELECT MachineID, lastBeat, State FROM machine WHERE areaID = ?", [$areaId]);
            
            if (!$query) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false
                ]);
            }

            $results = $query->getResultArray();

            // Return JSON response
            return $this->response->setJSON([
                'success' => true,
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false
            ]);
        }
    }

    public function getMachineStateDetails($machineId)
    {
        $db = \Config\Database::connect();

        try {
            // Validate machineId
            if (!is_numeric($machineId)) {
                log_message('error', "Invalid machine ID format: {$machineId}");
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false
                ]);
            }

            // Fetch only the necessary fields to reduce payload size
            $query = $db->query("SELECT id, lastBeat, State FROM machine WHERE id = ?", [$machineId]);
            
            if (!$query) {
                log_message('error', "Database query failed for machine {$machineId}: " . json_encode($db->error()));
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false
                ]);
            }

            $results = $query->getResultArray();
            log_message('debug', "Fetched machine state for machine {$machineId}: " . json_encode($results));

            return $this->response->setJSON([
                'success' => true,
                'data' => $results
            ]);

        } catch (\Exception $e) {
            log_message('error', "Error in getMachineStateDetails for machine {$machineId}: " . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false
            ]);
        }
    }
}
