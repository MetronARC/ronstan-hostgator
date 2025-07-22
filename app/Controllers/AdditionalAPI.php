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
                    
                    // Debug information
                    $debugInfo = [
                        'MachineID' => $MachineID,
                        'State' => 'maintenance',
                        'existingRecord' => $existingRecord ? 'YES' : 'NO',
                        'database_error' => $db->error(),
                        'last_query' => $db->getLastQuery()
                    ];
                    
                    if ($existingRecord) {
                        // Use string values for varchar field
                        $updateResult = $ledBuilder->where('State', 'maintenance')->where('MachineID', $MachineID)->update(['ledStatus' => 'true']);
                        
                        // Additional debug info for update
                        $debugInfo['update_result'] = $updateResult;
                        $debugInfo['update_error'] = $db->error();
                        $debugInfo['update_query'] = $db->getLastQuery();
                        
                        if ($updateResult === false) {
                            $ledUpdateResult = 'LED update failed: ' . $db->error() . ' | Debug: ' . json_encode($debugInfo);
                        } else {
                            $ledUpdateResult = 'LED updated successfully (affected rows: ' . $updateResult . ') | Debug: ' . json_encode($debugInfo);
                        }
                    } else {
                        // If record doesn't exist, create it
                        $insertResult = $ledBuilder->insert([
                            'MachineID' => $MachineID,
                            'State' => 'maintenance',
                            'ledStatus' => 'true'
                        ]);
                        
                        // Additional debug info for insert
                        $debugInfo['insert_result'] = $insertResult;
                        $debugInfo['insert_error'] = $db->error();
                        $debugInfo['insert_query'] = $db->getLastQuery();
                        
                        if ($insertResult === false) {
                            $ledUpdateResult = 'LED insert failed: ' . $db->error() . ' | Debug: ' . json_encode($debugInfo);
                        } else {
                            $ledUpdateResult = 'LED record created and updated (ID: ' . $db->insertID() . ') | Debug: ' . json_encode($debugInfo);
                        }
                    }
                } catch (\Exception $e) {
                    $ledUpdateResult = 'LED operation failed: ' . $e->getMessage() . ' | Debug: ' . json_encode($debugInfo ?? []);
                }

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
                    return $this->response->setBody('Maintenance On Initiated - ' . $ledUpdateResult);
                } else {
                    return $this->response->setStatusCode(500)->setBody('Error inserting ArcOn data: ' . $db->error());
                }
            } else if ($Status == "maintenanceOff") {
                // Update LED state for maintenance OFF
                $ledBuilder = $db->table('ledstate');
                
                try {
                    // First check if the record exists
                    $existingRecord = $ledBuilder->where('State', 'maintenance')->where('MachineID', $MachineID)->get()->getRow();
                    
                    if ($existingRecord) {
                        // Use string values for varchar field
                        $updateResult = $ledBuilder->where('State', 'maintenance')->where('MachineID', $MachineID)->update(['ledStatus' => 'false']);
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
                            'ledStatus' => 'false'
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
                        // Remove area table update for maintenanceOff
                        return $this->response->setBody('Data successfully updated for Maintenance Off - ' . $ledUpdateResult);
                    } else {
                        return $this->response->setStatusCode(500)->setBody('Error updating Maintenance Off data: ' . $db->error());
                    }
                } else {
                    return $this->response->setBody('No ArcOn record found to calculate ArcTotal.');
                }
            } else if ($Status == "toolingOn") {
                // Update LED state for tooling ON
                $ledBuilder = $db->table('ledstate');
                
                try {
                    // First check if the record exists
                    $existingRecord = $ledBuilder->where('State', 'tooling')->where('MachineID', $MachineID)->get()->getRow();
                    
                    if ($existingRecord) {
                        // Use string values for varchar field
                        $updateResult = $ledBuilder->where('State', 'tooling')->where('MachineID', $MachineID)->update(['ledStatus' => 'true']);
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
                            'ledStatus' => 'true'
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
                    return $this->response->setBody('Tooling On Initiated - ' . $ledUpdateResult);
                } else {
                    return $this->response->setStatusCode(500)->setBody('Error inserting ArcOn data: ' . $db->error());
                }
            } else if ($Status == "toolingOff") {
                // Update LED state for tooling OFF
                $ledBuilder = $db->table('ledstate');
                
                try {
                    // First check if the record exists
                    $existingRecord = $ledBuilder->where('State', 'tooling')->where('MachineID', $MachineID)->get()->getRow();
                    
                    if ($existingRecord) {
                        // Use string values for varchar field
                        $updateResult = $ledBuilder->where('State', 'tooling')->where('MachineID', $MachineID)->update(['ledStatus' => 'false']);
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
                            'ledStatus' => 'false'
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
                        // Remove area table update for toolingOff
                        return $this->response->setBody('Data successfully updated for Tooling Off - ' . $ledUpdateResult);
                    } else {
                        return $this->response->setStatusCode(500)->setBody('Error updating Tooling Off data: ' . $db->error());
                    }
                } else {
                    return $this->response->setBody('No ArcOn record found to calculate ArcTotal.');
                }
            } else if ($Status == "setupOn") {
                // Update LED state for setup ON
                $ledBuilder = $db->table('ledstate');
                
                try {
                    // First check if the record exists
                    $existingRecord = $ledBuilder->where('State', 'setup')->where('MachineID', $MachineID)->get()->getRow();
                    
                    if ($existingRecord) {
                        // Use string values for varchar field
                        $updateResult = $ledBuilder->where('State', 'setup')->where('MachineID', $MachineID)->update(['ledStatus' => 'true']);
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
                            'ledStatus' => 'true'
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
                    return $this->response->setBody('Setup On Initiated - ' . $ledUpdateResult);
                } else {
                    return $this->response->setStatusCode(500)->setBody('Error inserting ArcOn data: ' . $db->error());
                }
            } else if ($Status == "setupOff") {
                // Update LED state for setup OFF
                $ledBuilder = $db->table('ledstate');
                
                try {
                    // First check if the record exists
                    $existingRecord = $ledBuilder->where('State', 'setup')->where('MachineID', $MachineID)->get()->getRow();
                    
                    if ($existingRecord) {
                        // Use string values for varchar field
                        $updateResult = $ledBuilder->where('State', 'setup')->where('MachineID', $MachineID)->update(['ledStatus' => 'false']);
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
                            'ledStatus' => 'false'
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
                        // Remove area table update for setupOff
                        return $this->response->setBody('Data successfully updated for Setup Off - ' . $ledUpdateResult);
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
            if ($row->State && $row->ledStatus === 'true') {
                $response[$row->State] = true;
            } else if ($row->State) {
                $response[$row->State] = false;
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
                'ledStatus' => $row->ledStatus === 'true'
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
                'ledStatus' => $ledStatus,
                'existingRecord' => $existingRecord ? true : false,
                'operation' => ''
            ];
            
            if ($existingRecord) {
                // Use string values for varchar field
                $updateResult = $ledBuilder->where('State', $State)->where('MachineID', $MachineID)->update(['ledStatus' => $ledStatus]);
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
                    'ledStatus' => $ledStatus
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
                'ledStatus' => $verifyRecord->ledStatus === 'true'
            ] : null;
            
        } catch (\Exception $e) {
            $response = [
                'MachineID' => $MachineID,
                'State' => $State,
                'ledStatus' => $ledStatus,
                'operation' => 'LED operation failed: ' . $e->getMessage(),
                'success' => false
            ];
        }
        
        // Return JSON response
        return $this->response->setJSON($response);
    }
}
