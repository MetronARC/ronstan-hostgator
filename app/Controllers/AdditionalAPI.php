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
                
                try {
                    // First check if the record exists
                    $existingRecord = $ledBuilder->where('State', 'maintenance')->where('MachineID', $MachineID)->get()->getRow();
                    
                    if ($existingRecord) {
                        // Use string values for varchar field
                        $updateResult = $ledBuilder->where('State', 'maintenance')->where('MachineID', $MachineID)->update(['led_status' => 'true']);
                        
                        if ($updateResult === false) {
                            $ledUpdateResult = 'LED update failed: ' . $db->error();
                        } else {
                            $ledUpdateResult = 'LED updated successfully (affected rows: ' . $updateResult . ')';
                        }
                    } else {
                        // If record doesn't exist, create it
                        $insertResult = $ledBuilder->insert([
                            'MachineID' => $MachineID,
                            'State' => 'maintenance',
                            'led_status' => 'true'
                        ]);
                        
                        if ($insertResult === false) {
                            $ledUpdateResult = 'LED insert failed: ' . $db->error();
                        } else {
                            $ledUpdateResult = 'LED record created and updated (ID: ' . $db->insertID() . ')';
                        }
                    }
                } catch (\Exception $e) {
                    $ledUpdateResult = 'LED operation failed: ' . $e->getMessage();
                }

                // Check if there's already an active maintenance session (ArcOff is NULL)
                $builder = $db->table($tableHistory);
                $existingActiveSession = $builder->where('MachineID', $MachineID)
                    ->where('Area', $Area)
                    ->where('State', 'MAINTENANCE')
                    ->where('ArcOff IS NULL')
                    ->get()
                    ->getRow();

                if ($existingActiveSession) {
                    // Active session already exists, do nothing for the history table
                    $historyResult = 'Active maintenance session already exists, no new record created';
                } else {
                    // Insert into machine history for ArcOn
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
                        $historyResult = 'New maintenance session started';
                    } else {
                        return $this->response->setStatusCode(500)->setBody('Error inserting ArcOn data: ' . $db->error());
                    }
                }

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
                return $this->response->setBody('Maintenance On Initiated - ' . $ledUpdateResult . ' - ' . $historyResult);
            } else if ($Status == "maintenanceOff") {
                // Update LED state for maintenance OFF
                $ledBuilder = $db->table('ledstate');
                
                try {
                    // First check if the record exists
                    $existingRecord = $ledBuilder->where('State', 'maintenance')->where('MachineID', $MachineID)->get()->getRow();
                    
                    if ($existingRecord) {
                        // Use string values for varchar field
                        $updateResult = $ledBuilder->where('State', 'maintenance')->where('MachineID', $MachineID)->update(['led_status' => 'false']);
                        if ($updateResult === false) {
                            $ledUpdateResult = 'LED update failed: ' . $db->error();
                        } else {
                            $ledUpdateResult = 'LED updated successfully (affected rows: ' . $updateResult . ')';
                        }
                    } else {
                        // If record doesn't exist, create it
                        $insertResult = $ledBuilder->insert([
                            'MachineID' => $MachineID,
                            'State' => 'maintenance',
                            'led_status' => 'false'
                        ]);
                        if ($insertResult === false) {
                            $ledUpdateResult = 'LED insert failed: ' . $db->error();
                        } else {
                            $ledUpdateResult = 'LED record created and updated (ID: ' . $db->insertID() . ')';
                        }
                    }
                } catch (\Exception $e) {
                    $ledUpdateResult = 'LED operation failed: ' . $e->getMessage();
                }

                // Check if there's an active maintenance session to close
                $builder = $db->table($tableHistory);
                $activeSession = $builder->select('ID, ArcOn')
                    ->where('MachineID', $MachineID)
                    ->where('Area', $Area)
                    ->where('State', 'MAINTENANCE')
                    ->where('ArcOff IS NULL')
                    ->get()
                    ->getRow();

                if ($activeSession) {
                    // Calculate the ArcTotal as the difference between ArcOff and ArcOn
                    $ArcTotal = date_diff(date_create($activeSession->ArcOn), date_create($Time))->format('%H:%I:%S');

                    // Update for ArcOff using the specific ID
                    $dataArcOff = [
                        'ArcOff' => $Time,
                        'ArcTotal' => $ArcTotal,
                    ];

                    if ($builder->where('ID', $activeSession->ID)->update($dataArcOff)) {
                        $historyResult = 'Maintenance session closed successfully';
                    } else {
                        return $this->response->setStatusCode(500)->setBody('Error updating Maintenance Off data: ' . $db->error());
                    }
                } else {
                    $historyResult = 'No active maintenance session found to close';
                }

                return $this->response->setBody('Data successfully updated for Maintenance Off - ' . $ledUpdateResult . ' - ' . $historyResult);
            } else if ($Status == "toolingOn") {
                // Update LED state for tooling ON
                $ledBuilder = $db->table('ledstate');
                
                try {
                    // First check if the record exists
                    $existingRecord = $ledBuilder->where('State', 'tooling')->where('MachineID', $MachineID)->get()->getRow();
                    
                    if ($existingRecord) {
                        // Use string values for varchar field
                        $updateResult = $ledBuilder->where('State', 'tooling')->where('MachineID', $MachineID)->update(['led_status' => 'true']);
                        if ($updateResult === false) {
                            $ledUpdateResult = 'LED update failed: ' . $db->error();
                        } else {
                            $ledUpdateResult = 'LED updated successfully (affected rows: ' . $updateResult . ')';
                        }
                    } else {
                        // If record doesn't exist, create it
                        $insertResult = $ledBuilder->insert([
                            'MachineID' => $MachineID,
                            'State' => 'tooling',
                            'led_status' => 'true'
                        ]);
                        if ($insertResult === false) {
                            $ledUpdateResult = 'LED insert failed: ' . $db->error();
                        } else {
                            $ledUpdateResult = 'LED record created and updated (ID: ' . $db->insertID() . ')';
                        }
                    }
                } catch (\Exception $e) {
                    $ledUpdateResult = 'LED operation failed: ' . $e->getMessage();
                }

                // Check if there's already an active tooling session (ArcOff is NULL)
                $builder = $db->table($tableHistory);
                $existingActiveSession = $builder->where('MachineID', $MachineID)
                    ->where('Area', $Area)
                    ->where('State', 'TOOLING')
                    ->where('ArcOff IS NULL')
                    ->get()
                    ->getRow();

                if ($existingActiveSession) {
                    // Active session already exists, do nothing for the history table
                    $historyResult = 'Active tooling session already exists, no new record created';
                } else {
                    // Insert into machine history for Tooling
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
                        $historyResult = 'New tooling session started';
                    } else {
                        return $this->response->setStatusCode(500)->setBody('Error inserting ArcOn data: ' . $db->error());
                    }
                }

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
                return $this->response->setBody('Tooling On Initiated - ' . $ledUpdateResult . ' - ' . $historyResult);
            } else if ($Status == "toolingOff") {
                // Update LED state for tooling OFF
                $ledBuilder = $db->table('ledstate');
                
                try {
                    // First check if the record exists
                    $existingRecord = $ledBuilder->where('State', 'tooling')->where('MachineID', $MachineID)->get()->getRow();
                    
                    if ($existingRecord) {
                        // Use string values for varchar field
                        $updateResult = $ledBuilder->where('State', 'tooling')->where('MachineID', $MachineID)->update(['led_status' => 'false']);
                        if ($updateResult === false) {
                            $ledUpdateResult = 'LED update failed: ' . $db->error();
                        } else {
                            $ledUpdateResult = 'LED updated successfully (affected rows: ' . $updateResult . ')';
                        }
                    } else {
                        // If record doesn't exist, create it
                        $insertResult = $ledBuilder->insert([
                            'MachineID' => $MachineID,
                            'State' => 'tooling',
                            'led_status' => 'false'
                        ]);
                        if ($insertResult === false) {
                            $ledUpdateResult = 'LED insert failed: ' . $db->error();
                        } else {
                            $ledUpdateResult = 'LED record created and updated (ID: ' . $db->insertID() . ')';
                        }
                    }
                } catch (\Exception $e) {
                    $ledUpdateResult = 'LED operation failed: ' . $e->getMessage();
                }

                // Check if there's an active tooling session to close
                $builder = $db->table($tableHistory);
                $activeSession = $builder->select('ID, ArcOn')
                    ->where('MachineID', $MachineID)
                    ->where('Area', $Area)
                    ->where('State', 'TOOLING')
                    ->where('ArcOff IS NULL')
                    ->get()
                    ->getRow();

                if ($activeSession) {
                    // Calculate the ArcTotal as the difference between ArcOff and ArcOn
                    $ArcTotal = date_diff(date_create($activeSession->ArcOn), date_create($Time))->format('%H:%I:%S');

                    // Update for ArcOff using the specific ID
                    $dataArcOff = [
                        'ArcOff' => $Time,
                        'ArcTotal' => $ArcTotal,
                    ];

                    if ($builder->where('ID', $activeSession->ID)->update($dataArcOff)) {
                        $historyResult = 'Tooling session closed successfully';
                    } else {
                        return $this->response->setStatusCode(500)->setBody('Error updating Tooling Off data: ' . $db->error());
                    }
                } else {
                    $historyResult = 'No active tooling session found to close';
                }

                return $this->response->setBody('Data successfully updated for Tooling Off - ' . $ledUpdateResult . ' - ' . $historyResult);
            } else if ($Status == "setupOn") {
                // Update LED state for setup ON
                $ledBuilder = $db->table('ledstate');
                
                try {
                    // First check if the record exists
                    $existingRecord = $ledBuilder->where('State', 'setup')->where('MachineID', $MachineID)->get()->getRow();
                    
                    if ($existingRecord) {
                        // Use string values for varchar field
                        $updateResult = $ledBuilder->where('State', 'setup')->where('MachineID', $MachineID)->update(['led_status' => 'true']);
                        if ($updateResult === false) {
                            $ledUpdateResult = 'LED update failed: ' . $db->error();
                        } else {
                            $ledUpdateResult = 'LED updated successfully (affected rows: ' . $updateResult . ')';
                        }
                    } else {
                        // If record doesn't exist, create it
                        $insertResult = $ledBuilder->insert([
                            'MachineID' => $MachineID,
                            'State' => 'setup',
                            'led_status' => 'true'
                        ]);
                        if ($insertResult === false) {
                            $ledUpdateResult = 'LED insert failed: ' . $db->error();
                        } else {
                            $ledUpdateResult = 'LED record created and updated (ID: ' . $db->insertID() . ')';
                        }
                    }
                } catch (\Exception $e) {
                    $ledUpdateResult = 'LED operation failed: ' . $e->getMessage();
                }

                // Check if there's already an active setup session (ArcOff is NULL)
                $builder = $db->table($tableHistory);
                $existingActiveSession = $builder->where('MachineID', $MachineID)
                    ->where('Area', $Area)
                    ->where('State', 'SETUP')
                    ->where('ArcOff IS NULL')
                    ->get()
                    ->getRow();

                if ($existingActiveSession) {
                    // Active session already exists, do nothing for the history table
                    $historyResult = 'Active setup session already exists, no new record created';
                } else {
                    // Insert into machine history for Setup
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
                        $historyResult = 'New setup session started';
                    } else {
                        return $this->response->setStatusCode(500)->setBody('Error inserting ArcOn data: ' . $db->error());
                    }
                }

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
                return $this->response->setBody('Setup On Initiated - ' . $ledUpdateResult . ' - ' . $historyResult);
            } else if ($Status == "setupOff") {
                // Update LED state for setup OFF
                $ledBuilder = $db->table('ledstate');
                
                try {
                    // First check if the record exists
                    $existingRecord = $ledBuilder->where('State', 'setup')->where('MachineID', $MachineID)->get()->getRow();
                    
                    if ($existingRecord) {
                        // Use string values for varchar field
                        $updateResult = $ledBuilder->where('State', 'setup')->where('MachineID', $MachineID)->update(['led_status' => 'false']);
                        if ($updateResult === false) {
                            $ledUpdateResult = 'LED update failed: ' . $db->error();
                        } else {
                            $ledUpdateResult = 'LED updated successfully (affected rows: ' . $updateResult . ')';
                        }
                    } else {
                        // If record doesn't exist, create it
                        $insertResult = $ledBuilder->insert([
                            'MachineID' => $MachineID,
                            'State' => 'setup',
                            'led_status' => 'false'
                        ]);
                        if ($insertResult === false) {
                            $ledUpdateResult = 'LED insert failed: ' . $db->error();
                        } else {
                            $ledUpdateResult = 'LED record created and updated (ID: ' . $db->insertID() . ')';
                        }
                    }
                } catch (\Exception $e) {
                    $ledUpdateResult = 'LED operation failed: ' . $e->getMessage();
                }

                // Check if there's an active setup session to close
                $builder = $db->table($tableHistory);
                $activeSession = $builder->select('ID, ArcOn')
                    ->where('MachineID', $MachineID)
                    ->where('Area', $Area)
                    ->where('State', 'SETUP')
                    ->where('ArcOff IS NULL')
                    ->get()
                    ->getRow();

                if ($activeSession) {
                    // Calculate the ArcTotal as the difference between ArcOff and ArcOn
                    $ArcTotal = date_diff(date_create($activeSession->ArcOn), date_create($Time))->format('%H:%I:%S');

                    // Update for ArcOff using the specific ID
                    $dataArcOff = [
                        'ArcOff' => $Time,
                        'ArcTotal' => $ArcTotal,
                    ];

                    if ($builder->where('ID', $activeSession->ID)->update($dataArcOff)) {
                        $historyResult = 'Setup session closed successfully';
                    } else {
                        return $this->response->setStatusCode(500)->setBody('Error updating Setup Off data: ' . $db->error());
                    }
                } else {
                    $historyResult = 'No active setup session found to close';
                }

                return $this->response->setBody('Data successfully updated for Setup Off - ' . $ledUpdateResult . ' - ' . $historyResult);
            } else if ($Status == "ArcCheck") {
                // Handle ArcCheck
                $builder = $db->table($tableHistory);
                $querySelect = $builder->select('ID, ArcTotal')
                    ->where('MachineID', $MachineID)
                    ->where('Area', $Area)
                    ->orderBy('id', 'DESC')
                    ->limit(1)
                    ->get();

                if ($querySelect->getNumRows() > 0) {
                    $row = $querySelect->getRow();
                    $id = $row->ID;
                    $ArcTotal = $row->ArcTotal;

                    // Convert ArcTotal to seconds and increment by 6
                    $ArcTotalSeconds = strtotime($ArcTotal) - strtotime('TODAY') + 6;
                    $newArcTotal = gmdate('H:i:s', $ArcTotalSeconds);

                    $dataUpdateArcCheck = [
                        'ArcTotal' => $newArcTotal,
                        'ArcCheck' => $Time
                    ];
                    $builder->where('ID', $id)->update($dataUpdateArcCheck);

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
            if ($row->State) {
                $ledStatus = $row->led_status;
                
                // More robust value checking
                $isTrue = (
                    $ledStatus === 'true' || 
                    strtolower(trim($ledStatus)) === 'true' ||
                    $ledStatus === '1' ||
                    $ledStatus === 'TRUE'
                );
                
                $response[$row->State] = $isTrue;
            }
        }
        
        // Return JSON response
        return $this->response->setJSON($response);
    }

    public function debugLedState()
    {
        $apiKey = $this->request->getGet('apiKey');
        $MachineID = $this->request->getGet('MachineID');

        if ($apiKey !== $this->apiKey) {
            return $this->response->setStatusCode(400)->setBody("API key invalid.");
        }

        // Load the database connection
        $db = \Config\Database::connect();
        
        // Query all LED states for the MachineID
        $query = $db->table('ledstate')->where('MachineID', $MachineID)->get();
        $results = $query->getResult();
        
        $response = [
            'MachineID' => $MachineID,
            'records' => []
        ];
        
        foreach ($results as $row) {
            $response['records'][] = [
                'ID' => $row->ID,
                'MachineID' => $row->MachineID,
                'State' => $row->State,
                'led_status' => $row->led_status === 'true'
            ];
        }
        
        // Return JSON response
        return $this->response->setJSON($response);
    }

    public function testLedUpdate()
    {
        $apiKey = $this->request->getGet('apiKey');
        $MachineID = $this->request->getGet('MachineID');
        $State = $this->request->getGet('State'); // maintenance, setup, tooling
        $ledStatus = $this->request->getGet('ledStatus'); // true or false

        if ($apiKey !== $this->apiKey) {
            return $this->response->setStatusCode(400)->setBody("API key invalid.");
        }

        if (!$MachineID || !$State || !in_array($ledStatus, ['true', 'false'])) {
            return $this->response->setStatusCode(400)->setBody("Missing or invalid parameters. Required: MachineID, State, ledStatus (true or false)");
        }

        // Load the database connection
        $db = \Config\Database::connect();
        
        try {
            $ledBuilder = $db->table('ledstate');
            
            // First check if the record exists
            $existingRecord = $ledBuilder->where('State', $State)->where('MachineID', $MachineID)->get()->getRow();
            
            $response = [
                'MachineID' => $MachineID,
                'State' => $State,
                'led_status' => $ledStatus,
                'existingRecord' => $existingRecord ? true : false,
                'operation' => ''
            ];
            
            if ($existingRecord) {
                // Use string values for varchar field
                $updateResult = $ledBuilder->where('State', $State)->where('MachineID', $MachineID)->update(['led_status' => $ledStatus]);
                if ($updateResult === false) {
                    $response['operation'] = 'LED update failed: ' . $db->error();
                    $response['success'] = false;
                } else {
                    $response['operation'] = 'LED updated successfully (affected rows: ' . $updateResult . ')';
                    $response['success'] = true;
                }
            } else {
                // If record doesn't exist, create it
                $insertResult = $ledBuilder->insert([
                    'MachineID' => $MachineID,
                    'State' => $State,
                    'led_status' => $ledStatus
                ]);
                if ($insertResult === false) {
                    $response['operation'] = 'LED insert failed: ' . $db->error();
                    $response['success'] = false;
                } else {
                    $response['operation'] = 'LED record created and updated (ID: ' . $db->insertID() . ')';
                    $response['success'] = true;
                }
            }
            
            // Verify the update by querying the record again
            $verifyRecord = $ledBuilder->where('State', $State)->where('MachineID', $MachineID)->get()->getRow();
            $response['verification'] = $verifyRecord ? [
                'ID' => $verifyRecord->ID,
                'MachineID' => $verifyRecord->MachineID,
                'State' => $verifyRecord->State,
                'led_status' => $verifyRecord->led_status === 'true'
            ] : null;
            
        } catch (\Exception $e) {
            $response = [
                'MachineID' => $MachineID,
                'State' => $State,
                'led_status' => $ledStatus,
                'operation' => 'LED operation failed: ' . $e->getMessage(),
                'success' => false
            ];
        }
        
        // Return JSON response
        return $this->response->setJSON($response);
    }
}
