<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\Database\Exceptions\DataException;

class AdditionalAPI extends BaseController
{
    private $apiKey;

    public function __construct()
    {
        // Load the API key from the .env file  
        $this->apiKey = getenv('api.API_KEY');
    }

    public function updateAdditionalData()
    {
        // Load the database connection
        $db = \Config\Database::connect();

        // Read input data from GET request
        $Area = $this->request->getGet('Area');
        $UID = $this->request->getGet('UID');
        $MachineID = $this->request->getGet('MachineID');
        $Status = $this->request->getGet('Status');
        $WeldID = $this->request->getGet('weldID');
        $apiKey = $this->request->getGet('apiKey');

        if ($apiKey !== $this->apiKey) {
            return $this->response->setStatusCode(400)->setBody("API key invalid.");
        }

        // Set timezone and get current time
        date_default_timezone_set('Asia/Jakarta');
        $Time = date('H:i:s');  // TIME format
        $Date = date('Y-m-d');  // DATE format
        $DateTime = date('Y-m-d H:i:s');  // DATETIME format

        // Check if the UID exists in the employee table
        $builder = $db->table('employee');
        $query = $builder->select('Name')->where('cardUID', $UID)->get();

        if ($query->getNumRows() > 0) {
            // UID exists, retrieve the Name value
            $row = $query->getRow();
            $Name = $row->Name;
        } else {
            // UID does not exist, set Name to an empty string
            return $this->response->setBody('Card not in the database');
        }

        // Handle different areas
        if ($Area == "1" || $Area == "2") {
            $tableArea = $Area == "1" ? "area1" : "area2";
            $tableHistory = $Area == "1" ? "additionalhistory" : "machinehistory2";

            if ($Status == "maintenanceOn") {
                // Update LED state for maintenance ON
                $ledBuilder = $db->table('ledstate');
                $ledBuilder->where('State', 'maintenance')->where('MachineID', $MachineID)->update(['ledStatus' => 1]);

                // Insert into machine history for ArcOn
                $builder = $db->table($tableHistory);
                $dataArcOn = [
                    'State' => 'MAINTENANCE',
                    'Area' => $Area,
                    'MachineID' => $MachineID,
                    'WeldID' => $WeldID,
                    'Name' => $Name,
                    'ArcOn' => $Time,
                    'Date' => $Date
                ];
                if ($builder->insert($dataArcOn)) {
                    // Update the area table with State ON and other details
                    $areaBuilder = $db->table($tableArea);
                    $areaBuilder->where('MachineID', $MachineID)->update([
                        'State' => 'MAINTENANCE',
                        'WeldID' => $WeldID,
                        'Area' => $Area,
                        'UID' => $UID,
                        'Name' => $Name,
                        'Date' => $Date,
                        'Login' => $Time,
                        'Status' => 'Active'
                    ]);
                    return $this->response->setBody('Maintenance On Initiated');
                } else {
                    return $this->response->setStatusCode(500)->setBody('Error inserting ArcOn data: ' . $db->error());
                }
            } else if ($Status == "maintenanceOff") {
                // Update LED state for maintenance OFF
                $ledBuilder = $db->table('ledstate');
                $ledBuilder->where('State', 'maintenance')->where('MachineID', $MachineID)->update(['ledStatus' => 0]);

                // Fetch the ArcOn time to calculate ArcTotal
                $builder = $db->table($tableHistory);

                // First get the latest record ID
                $latestRecord = $builder->select('id, ArcOn')
                    ->where('MachineID', $MachineID)
                    ->where('Area', $Area)
                    ->orderBy('id', 'DESC')
                    ->limit(1)
                    ->get()
                    ->getRow();

                if ($latestRecord) {
                    $ArcOn = $latestRecord->ArcOn;
                    // Calculate the ArcTotal as the difference between ArcOff and ArcOn
                    $ArcTotal = date_diff(date_create($ArcOn), date_create($Time))->format('%H:%I:%S');

                    // Update for ArcOff using the specific ID
                    $dataArcOff = [
                        'ArcOff' => $Time,
                        'ArcTotal' => $ArcTotal,
                    ];

                    if ($builder->where('id', $latestRecord->id)->update($dataArcOff)) {
                        // Update the 'State' in area table to 'IDLE'
                        $areaBuilder = $db->table($tableArea);
                        $areaBuilder->where('MachineID', $MachineID)->update([
                            
                        ]);

                        return $this->response->setBody('Data successfully updated for Maintenance Off');
                    } else {
                        return $this->response->setStatusCode(500)->setBody('Error updating Maintenance Off data: ' . $db->error());
                    }
                } else {
                    return $this->response->setBody('No ArcOn record found to calculate ArcTotal.');
                }
            } else if ($Status == "toolingOn") {
                // Update LED state for tooling ON
                $ledBuilder = $db->table('ledstate');
                $ledBuilder->where('State', 'tooling')->where('MachineID', $MachineID)->update(['ledStatus' => 1]);

                // Insert into machine history for Tooling
                $builder = $db->table($tableHistory);
                $dataArcOn = [
                    'State' => 'TOOLING',
                    'Area' => $Area,
                    'MachineID' => $MachineID,
                    'WeldID' => $WeldID,
                    'Name' => $Name,
                    'ArcOn' => $Time,
                    'Date' => $Date
                ];
                if ($builder->insert($dataArcOn)) {
                    // Update the area table with State TOOLING and other details
                    $areaBuilder = $db->table($tableArea);
                    $areaBuilder->where('MachineID', $MachineID)->update([
                        'State' => 'TOOLING',
                        'WeldID' => $WeldID,
                        'Area' => $Area,
                        'UID' => $UID,
                        'Name' => $Name,
                        'Date' => $Date,
                        'Login' => $Time,
                        'Status' => 'Active'
                    ]);
                    return $this->response->setBody('Tooling On Initiated');
                } else {
                    return $this->response->setStatusCode(500)->setBody('Error inserting ArcOn data: ' . $db->error());
                }
            } else if ($Status == "toolingOff") {
                // Update LED state for tooling OFF
                $ledBuilder = $db->table('ledstate');
                $ledBuilder->where('State', 'tooling')->where('MachineID', $MachineID)->update(['ledStatus' => 0]);

                // Fetch the ArcOn time to calculate ArcTotal
                $builder = $db->table($tableHistory);

                $latestRecord = $builder->select('id, ArcOn')
                    ->where('MachineID', $MachineID)
                    ->where('Area', $Area)
                    ->orderBy('id', 'DESC')
                    ->limit(1)
                    ->get()
                    ->getRow();

                if ($latestRecord) {
                    $ArcOn = $latestRecord->ArcOn;
                    $ArcTotal = date_diff(date_create($ArcOn), date_create($Time))->format('%H:%I:%S');

                    $dataArcOff = [
                        'ArcOff' => $Time,
                        'ArcTotal' => $ArcTotal,
                    ];

                    if ($builder->where('id', $latestRecord->id)->update($dataArcOff)) {
                        $areaBuilder = $db->table($tableArea);
                        $areaBuilder->where('MachineID', $MachineID)->update([
                         
                        ]);

                        return $this->response->setBody('Data successfully updated for Tooling Off');
                    } else {
                        return $this->response->setStatusCode(500)->setBody('Error updating Tooling Off data: ' . $db->error());
                    }
                } else {
                    return $this->response->setBody('No ArcOn record found to calculate ArcTotal.');
                }
            } else if ($Status == "setupOn") {
                // Update LED state for setup ON
                $ledBuilder = $db->table('ledstate');
                $ledBuilder->where('State', 'setup')->where('MachineID', $MachineID)->update(['ledStatus' => 1]);

                // Insert into machine history for Setup
                $builder = $db->table($tableHistory);
                $dataArcOn = [
                    'State' => 'SETUP',
                    'Area' => $Area,
                    'MachineID' => $MachineID,
                    'WeldID' => $WeldID,
                    'Name' => $Name,
                    'ArcOn' => $Time,
                    'Date' => $Date
                ];
                if ($builder->insert($dataArcOn)) {
                    // Update the area table with State SETUP and other details
                    $areaBuilder = $db->table($tableArea);
                    $areaBuilder->where('MachineID', $MachineID)->update([
                        'State' => 'SETUP',
                        'WeldID' => $WeldID,
                        'Area' => $Area,
                        'UID' => $UID,
                        'Name' => $Name,
                        'Date' => $Date,
                        'Login' => $Time,
                        'Status' => 'Active'
                    ]);
                    return $this->response->setBody('Setup On Initiated');
                } else {
                    return $this->response->setStatusCode(500)->setBody('Error inserting ArcOn data: ' . $db->error());
                }
            } else if ($Status == "setupOff") {
                // Update LED state for setup OFF
                $ledBuilder = $db->table('ledstate');
                $ledBuilder->where('State', 'setup')->where('MachineID', $MachineID)->update(['ledStatus' => 0]);

                // Fetch the ArcOn time to calculate ArcTotal
                $builder = $db->table($tableHistory);

                $latestRecord = $builder->select('id, ArcOn')
                    ->where('MachineID', $MachineID)
                    ->where('Area', $Area)
                    ->orderBy('id', 'DESC')
                    ->limit(1)
                    ->get()
                    ->getRow();

                if ($latestRecord) {
                    $ArcOn = $latestRecord->ArcOn;
                    $ArcTotal = date_diff(date_create($ArcOn), date_create($Time))->format('%H:%I:%S');

                    $dataArcOff = [
                        'ArcOff' => $Time,
                        'ArcTotal' => $ArcTotal,
                    ];

                    if ($builder->where('id', $latestRecord->id)->update($dataArcOff)) {
                        $areaBuilder = $db->table($tableArea);
                        $areaBuilder->where('MachineID', $MachineID)->update([
                            
                        ]);

                        return $this->response->setBody('Data successfully updated for Setup Off');
                    } else {
                        return $this->response->setStatusCode(500)->setBody('Error updating Setup Off data: ' . $db->error());
                    }
                } else {
                    return $this->response->setBody('No ArcOn record found to calculate ArcTotal.');
                }
            } else if ($Status == "ArcCheck") {
                // Handle ArcCheck
                $builder = $db->table($tableHistory);
                $querySelect = $builder->select('id, ArcTotal')
                    ->where('MachineID', $MachineID)
                    ->where('Area', $Area)
                    ->orderBy('id', 'DESC')
                    ->limit(1)
                    ->get();

                if ($querySelect->getNumRows() > 0) {
                    $row = $querySelect->getRow();
                    $id = $row->id;
                    $ArcTotal = $row->ArcTotal;

                    // Convert ArcTotal to seconds and increment by 6
                    $ArcTotalSeconds = strtotime($ArcTotal) - strtotime('TODAY') + 6;
                    $newArcTotal = gmdate('H:i:s', $ArcTotalSeconds);

                    $dataUpdateArcCheck = [
                        'ArcTotal' => $newArcTotal,
                        'ArcCheck' => $Time
                    ];
                    $builder->where('id', $id)->update($dataUpdateArcCheck);

                    return $this->response->setBody('ArcTotal and ArcCheck successfully updated');
                } else {
                    return $this->response->setBody('No matching records found for ArcCheck');
                }
            }
        }

        return $this->response->setStatusCode(400)->setBody('Invalid Area or Status provided');
    }

    public function getLedStatus()
    {
        $apiKey = $this->request->getGet('apiKey');
        $MachineID = $this->request->getGet('MachineID');

        if ($apiKey !== $this->apiKey) {
            return $this->response->setStatusCode(400)->setBody("API key invalid.");
        }

        // Load the database connection
        $db = \Config\Database::connect();
        
        // Query the ledState table with MachineID filter
        $query = $db->table('ledstate')->where('MachineID', $MachineID)->get();
        $results = $query->getResult();
        
        // Initialize the response array
        $response = [
            'maintenance' => false,
            'setup' => false,
            'tooling' => false
        ];
        
        // Process the results
        foreach ($results as $row) {
            if ($row->State && $row->ledStatus) {
                $response[$row->State] = (bool)$row->ledStatus;
            }
        }
        
        // Return JSON response
        return $this->response->setJSON($response);
    }
}
