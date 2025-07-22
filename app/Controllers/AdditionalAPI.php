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
                        $updateResult = $ledBuilder->where('State', 'maintenance')->where('MachineID', $MachineID)->update(['led_status' => 'true']);
                        
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
                            'led_status' => 'true'
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
            'tooling' => false,
            'debug_info' => []
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
                
                // Add debug info
                $response['debug_info'][] = [
                    'State' => $row->State,
                    'led_status_raw' => $ledStatus,
                    'led_status_length' => strlen($ledStatus),
                    'led_status_hex' => bin2hex($ledStatus),
                    'is_true' => $isTrue,
                    'exact_match_true' => $ledStatus === 'true',
                    'trim_lower_match_true' => strtolower(trim($ledStatus)) === 'true'
                ];
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

    public function checkDatabaseInfo()
    {
        $apiKey = $this->request->getGet('apiKey');

        if ($apiKey !== $this->apiKey) {
            return $this->response->setStatusCode(400)->setBody("API key invalid.");
        }

        // Load the database connection
        $db = \Config\Database::connect();
        
        try {
            $response = [
                'database_info' => [],
                'table_structure' => [],
                'permissions_test' => [],
                'exact_values' => []
            ];
            
            // Get database info
            $response['database_info'] = [
                'database_name' => $db->getDatabase(),
                'status' => 'Connected'
            ];
            
            // Check table structure
            $tableQuery = $db->query("DESCRIBE ledstate");
            $tableStructure = $tableQuery->getResultArray();
            $response['table_structure'] = $tableStructure;
            
            // Test permissions by trying to update a record
            $testUpdate = $db->table('ledstate')->where('ID', 1)->update(['led_status' => 'false']);
            $response['permissions_test']['update_test'] = [
                'result' => $testUpdate,
                'error' => $db->error(),
                'last_query' => $db->getLastQuery()
            ];
            
            // Test insert permissions
            $testInsert = $db->table('ledstate')->insert([
                'MachineID' => 'TEST-MACHINE',
                'State' => 'test',
                'led_status' => 'false'
            ]);
            $response['permissions_test']['insert_test'] = [
                'result' => $testInsert,
                'error' => $db->error(),
                'last_query' => $db->getLastQuery()
            ];
            
            // Clean up test record
            $db->table('ledstate')->where('MachineID', 'TEST-MACHINE')->delete();
            
        } catch (\Exception $e) {
            $response = [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
        
        // Return JSON response
        return $this->response->setJSON($response);
    }

    public function checkExactValues()
    {
        $apiKey = $this->request->getGet('apiKey');
        $MachineID = $this->request->getGet('MachineID');

        if ($apiKey !== $this->apiKey) {
            return $this->response->setStatusCode(400)->setBody("API key invalid.");
        }

        // Load the database connection
        $db = \Config\Database::connect();
        
        try {
            // Query all LED states for the MachineID
            $query = $db->table('ledstate')->where('MachineID', $MachineID)->get();
            $results = $query->getResult();
            
            $response = [
                'MachineID' => $MachineID,
                'records' => [],
                'comparison_tests' => []
            ];
            
            foreach ($results as $row) {
                $ledStatus = $row->led_status;
                
                // Check exact string values and lengths
                $response['records'][] = [
                    'ID' => $row->ID,
                    'MachineID' => $row->MachineID,
                    'State' => $row->State,
                    'led_status_raw' => $ledStatus,
                    'led_status_length' => strlen($ledStatus),
                    'led_status_hex' => bin2hex($ledStatus),
                    'is_true_exact' => $ledStatus === 'true',
                    'is_false_exact' => $ledStatus === 'false',
                    'is_true_trim' => trim($ledStatus) === 'true',
                    'is_false_trim' => trim($ledStatus) === 'false',
                    'is_true_lower' => strtolower($ledStatus) === 'true',
                    'is_false_lower' => strtolower($ledStatus) === 'false'
                ];
            }
            
            // Test direct update and check result
            if (!empty($results)) {
                $firstRecord = $results[0];
                $testValue = $firstRecord->led_status === 'true' ? 'false' : 'true';
                
                // Update the record
                $updateResult = $db->table('ledstate')
                    ->where('ID', $firstRecord->ID)
                    ->update(['led_status' => $testValue]);
                
                // Check the result immediately
                $verifyQuery = $db->table('ledstate')->where('ID', $firstRecord->ID)->get();
                $verifyResult = $verifyQuery->getRow();
                
                $response['comparison_tests'] = [
                    'original_value' => $firstRecord->led_status,
                    'test_value' => $testValue,
                    'update_result' => $updateResult,
                    'update_error' => $db->error(),
                    'verify_value' => $verifyResult ? $verifyResult->led_status : 'null',
                    'verify_length' => $verifyResult ? strlen($verifyResult->led_status) : 0,
                    'verify_hex' => $verifyResult ? bin2hex($verifyResult->led_status) : '',
                    'is_expected' => $verifyResult ? $verifyResult->led_status === $testValue : false
                ];
                
                // Restore original value
                $db->table('ledstate')
                    ->where('ID', $firstRecord->ID)
                    ->update(['led_status' => $firstRecord->led_status]);
            }
            
        } catch (\Exception $e) {
            $response = [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
        
        // Return JSON response
        return $this->response->setJSON($response);
    }

    public function forceRefreshLedStatus()
    {
        $apiKey = $this->request->getGet('apiKey');
        $MachineID = $this->request->getGet('MachineID');

        if ($apiKey !== $this->apiKey) {
            return $this->response->setStatusCode(400)->setBody("API key invalid.");
        }

        // Load the database connection
        $db = \Config\Database::connect();
        
        // Force a fresh query with no caching
        $query = $db->table('ledstate')
            ->where('MachineID', $MachineID)
            ->get();
        $results = $query->getResult();
        
        $response = [
            'MachineID' => $MachineID,
            'timestamp' => date('Y-m-d H:i:s'),
            'maintenance' => false,
            'setup' => false,
            'tooling' => false,
            'raw_data' => []
        ];
        
        foreach ($results as $row) {
            $ledStatus = $row->led_status;
            $isTrue = ($ledStatus === 'true');
            
            if ($row->State === 'maintenance') {
                $response['maintenance'] = $isTrue;
            } elseif ($row->State === 'setup') {
                $response['setup'] = $isTrue;
            } elseif ($row->State === 'tooling') {
                $response['tooling'] = $isTrue;
            }
            
            $response['raw_data'][] = [
                'State' => $row->State,
                'led_status' => $ledStatus,
                'is_true' => $isTrue
            ];
        }
        
        // Return JSON response with no-cache headers
        return $this->response
            ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setJSON($response);
    }

    public function testDirectSQL()
    {
        $apiKey = $this->request->getGet('apiKey');
        $MachineID = $this->request->getGet('MachineID');

        if ($apiKey !== $this->apiKey) {
            return $this->response->setStatusCode(400)->setBody("API key invalid.");
        }

        // Load the database connection
        $db = \Config\Database::connect();
        
        try {
            $response = [
                'MachineID' => $MachineID,
                'tests' => []
            ];
            
            // Test 1: Check if record exists
            $checkQuery = $db->query("SELECT * FROM ledstate WHERE MachineID = ?", [$MachineID]);
            $checkResult = $checkQuery->getResultArray();
            $response['tests']['record_exists'] = [
                'found_records' => count($checkResult),
                'records' => $checkResult
            ];
            
            // Test 2: Try direct UPDATE with exact MachineID
            $updateQuery = $db->query("UPDATE ledstate SET led_status = 'true' WHERE MachineID = ?", [$MachineID]);
            $response['tests']['direct_update'] = [
                'affected_rows' => $db->affectedRows(),
                'error' => $db->error(),
                'last_query' => $db->getLastQuery()
            ];
            
            // Test 3: Check result after update
            $verifyQuery = $db->query("SELECT * FROM ledstate WHERE MachineID = ?", [$MachineID]);
            $verifyResult = $verifyQuery->getResultArray();
            $response['tests']['verify_after_update'] = [
                'records' => $verifyResult
            ];
            
            // Test 4: Try with different WHERE conditions
            $updateQuery2 = $db->query("UPDATE ledstate SET led_status = 'false' WHERE MachineID = ? AND State = 'maintenance'", [$MachineID]);
            $response['tests']['update_with_state'] = [
                'affected_rows' => $db->affectedRows(),
                'error' => $db->error(),
                'last_query' => $db->getLastQuery()
            ];
            
            // Test 5: Check table structure and permissions
            $tableInfo = $db->query("SHOW TABLE STATUS LIKE 'ledstate'");
            $response['tests']['table_info'] = $tableInfo->getResultArray();
            
            // Test 6: Check for triggers
            $triggers = $db->query("SHOW TRIGGERS WHERE `Table` = 'ledstate'");
            $response['tests']['triggers'] = $triggers->getResultArray();
            
            // Test 7: Check user permissions
            $permissions = $db->query("SHOW GRANTS");
            $response['tests']['user_permissions'] = $permissions->getResultArray();
            
        } catch (\Exception $e) {
            $response = [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
        
        // Return JSON response
        return $this->response->setJSON($response);
    }
}
