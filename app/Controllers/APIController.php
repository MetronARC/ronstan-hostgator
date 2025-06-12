<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\Database\Exceptions\DataException;

class APIController extends BaseController
{
    private $apiKey;

    public function __construct()
    {
        // Load the API key from the .env file  
        $this->apiKey = getenv('api.API_KEY');
    }
    // checkTime.php
    public function updateLastSeen()
    {
        // Retrieve the GET parameters  
        $apiKey = $this->request->getGet('apiKey');
        $state = $this->request->getGet('State');
        $machineID = $this->request->getGet('MachineID');
        $WeldID = $this->request->getGet('weldID');

        // Validate the API key  
        if ($apiKey !== $this->apiKey) {
            return $this->response->setStatusCode(403)->setBody(json_encode(["error" => "API key invalid."]));
        }

        // Validate state  
        if ($state !== "heartBeat") {
            return $this->response->setStatusCode(400)->setBody(json_encode(["error" => "State invalid"]));
        }

        // Validate the MachineID  
        if (!$machineID) {
            return $this->response->setStatusCode(400)->setBody(json_encode(["error" => "Invalid MachineID"]));
        }

        // Validate the WeldID  
        if (!$WeldID) {
            return $this->response->setStatusCode(400)->setBody(json_encode(["error" => "Invalid WeldID"]));
        }

        // Load the database service  
        $db = \Config\Database::connect();

        // Set timezone and current time  
        date_default_timezone_set('Asia/Jakarta');
        $currentDateTime = date("Y-m-d H:i:s");

        // Begin transaction  
        $db->transStart();

        // Update the lastSeen column in the machine table  
        $machineBuilder = $db->table('machine');
        try {
            $machineBuilder->where('MachineID', $machineID)
                ->update(['lastSeen' => $currentDateTime]);

            // Commit the transaction if the update is successful  
            if ($db->transComplete()) {
                return $this->response->setStatusCode(200)->setBody(json_encode(["message" => "lastSeen updated successfully."]));
            } else {
                // Get the last error and query  
                $error = $db->error();
                $lastQuery = $db->getLastQuery();
                return $this->response->setStatusCode(400)->setBody(json_encode([
                    "error" => "Update failed or no changes made.",
                    "mysql_error" => $error,
                    "last_query" => (string)$lastQuery
                ]));
            }
        } catch (\Exception $e) {
            // Rollback transaction in case of any errors  
            $db->transRollback();
            return $this->response->setStatusCode(500)->setBody(json_encode([
                "error" => "Error updating records: " . $e->getMessage(),
                "last_query" => (string)$db->getLastQuery()
            ]));
        }
    }

    // checkWeldID.php
    public function updateWeldID()
    {
        // Get the incoming request data
        $machineID = $this->request->getGet('MachineID');
        $apiKey = $this->request->getGet('apiKey');

        // Validate the API key
        if ($apiKey !== $this->apiKey) {
            return $this->response->setStatusCode(403)->setBody("API key invalid.");
        }

        // Load the database connection
        $db = \Config\Database::connect();

        // Fetch the row with the largest WeldID value for the specified MachineID
        $builder = $db->table('machine');
        $builder->selectMax('WeldID');
        $builder->where('MachineID', $machineID);
        $query = $builder->get();

        if ($query->getNumRows() > 0) {
            // Fetch the result as an array
            $row = $query->getRowArray();
            $maxWeldID = $row['WeldID'];
            $newWeldID = $maxWeldID + 1;

            // Output the new WeldID value
            echo $machineID . '.' . $newWeldID;

            // Update the WeldID in the machine table
            $updateBuilder = $db->table('machine');
            $updateData = [
                'WeldID' => $newWeldID
            ];

            $updateBuilder->where('MachineID', $machineID);
            if ($updateBuilder->update($updateData)) {
                // Successful update, you can handle any success response here
            } else {
                // Handle the error during the update
                echo "Error updating WeldID.";
            }
        } else {
            echo "No records found for MachineID " . $machineID;
        }

        // Close the database connection
        $db->close();
    }

    // checkFirst.php
    public function insertHeartBeat()
    {
        // Retrieve the GET parameters
        $machineID = $this->request->getGet('MachineID');
        $area = $this->request->getGet('Area');
        $UID = $this->request->getGet('UID');
        $weldID = $this->request->getGet('weldID');
        $apiKey = $this->request->getGet('apiKey');

        // Validate the API key
        if ($apiKey !== $this->apiKey) {
            return $this->response->setStatusCode(403)->setBody("API key invalid.");
        }

        // Set the timezone to Asia/Jakarta
        date_default_timezone_set('Asia/Jakarta');

        // Get the current date and time in DATETIME format
        $currentDateTime = date("Y-m-d H:i:s");

        // Load the database service
        $db = \Config\Database::connect();

        // Check if $UID exists in the employee table
        $employeeBuilder = $db->table('employee');
        $employeeQuery = $employeeBuilder->select('Name')
            ->where('cardUID', $UID)
            ->get();

        if ($employeeQuery->getNumRows() > 0) {
            // Fetch the Name from the employee table
            $employeeRow = $employeeQuery->getRowArray();
            $employeeName = $employeeRow['Name'];

            // Prepare the insert query for heartBeatTable
            $heartbeatBuilder = $db->table('heartbeattable');
            $heartbeatData = [
                'MachineID'  => $machineID,
                'WeldID'     => $weldID,
                'Area'       => $area,
                'startBeat'  => $currentDateTime,
                'Name'       => $employeeName,
            ];

            // Insert the new heartbeat record
            if ($heartbeatBuilder->insert($heartbeatData)) {
                return $this->response->setStatusCode(200)->setBody("New heartbeat record inserted successfully.");
            } else {
                return $this->response->setStatusCode(500)->setBody("Error inserting heartbeat record.");
            }
        } else {
            return $this->response->setStatusCode(404)->setBody("No employee found with UID: $UID.");
        }
    }

    // kirimInfo2.php
    public function handleArea()
    {
        try {
            // Retrieve input parameters
            $area = $this->request->getGet('Area');
            $UID = $this->request->getGet('UID');
            $status = $this->request->getGet('Status');
            $mode = $this->request->getGet('Mode');
            $machineID = $this->request->getGet('MachineID');
            $weldID = $this->request->getGet('weldID');
            $apiKey = $this->request->getGet('apiKey');

            // Validate the API key
            if ($apiKey !== $this->apiKey) {
                return $this->response->setStatusCode(403)->setBody("API key invalid.");
            }

            // Load the database service
            $db = \Config\Database::connect();

            // Function to get employee name by UID
            function getEmployeeName($db, $UID)
            {
                $employeeBuilder = $db->table('employee');
                $query = $employeeBuilder->select('Name')->where('cardUID', $UID)->get();

                if ($query->getNumRows() > 0) {
                    $row = $query->getRow();
                    return $row->Name;
                }
                return ''; // Return empty string if no data found
            }

            $name = getEmployeeName($db, $UID);

            // Set timezone and get current date and time
            date_default_timezone_set('Asia/Jakarta');
            $date = date('Y-m-d');
            $time = date('H:i:s');

            // Debugging: Log received parameters
            log_message('debug', "Area: $area, UID: $UID, Status: $status, Mode: $mode, MachineID: $machineID, WeldID: $weldID");

            // Check if the area is valid
            if (in_array($area, ["1", "2", "3", "4", "5"])) {

                // Get the appropriate table for the area
                $areaTable = 'area' . $area;

                if ($status === "Inactive") {
                    // Check if a row with the same MachineID exists
                    $areaBuilder = $db->table($areaTable);
                    $check = $areaBuilder->where('MachineID', $machineID)->get();

                    if ($check->getNumRows() > 0) {
                        // Update the row if the MachineID exists
                        $areaBuilder->where('MachineID', $machineID)
                            ->update(['WeldID' => $weldID, 'Date' => $date]);
                        log_message('debug', "Update on Inactive: $machineID");
                        return $this->response->setStatusCode(200)->setBody("Query executed successfully: Update on Inactive");
                    } else {
                        // Insert a new row if the MachineID does not exist
                        $areaBuilder->insert([
                            'Area'      => $area,
                            'UID'       => $UID,
                            'Status'    => $status,
                            'Mode'      => $mode,
                            'MachineID' => $machineID,
                            'WeldID'    => $weldID,
                            'Date'      => $date,
                            'Name'      => $name
                        ]);
                        log_message('debug', "Insert on Inactive: $machineID");
                        return $this->response->setStatusCode(200)->setBody("Query executed successfully: Insert on Inactive");
                    }
                } elseif ($status == "Active") {
                    // Update area for 'Active' status
                    $areaBuilder = $db->table($areaTable);
                    $areaBuilder->where('MachineID', $machineID)
                        ->update([
                            'Name'      => $name,
                            'UID'       => $UID,
                            'Status'    => $status,
                            'Mode'      => $mode,
                            'WeldID'    => $weldID,
                            'Date'      => $date,
                            'State'     => 'IDLE'
                        ]);

                    // Handle login and logout modes
                    if ($mode == "Login") {
                        // Update with the current datetime
                        $currentDateTime = date('Y-m-d H:i:s');
                        $areaBuilder->where('MachineID', $machineID)
                            ->update(['Login' => $currentDateTime]);
                        log_message('debug', "Login mode: $machineID");
                        return $this->response->setStatusCode(200)->setBody("Query executed successfully: Login mode");
                    } elseif ($mode == "Logout") {
                        $areaBuilder->where('MachineID', $machineID)
                            ->update(['Logout' => $time]);
                        log_message('debug', "Logout mode: $machineID");
                        return $this->response->setStatusCode(200)->setBody("Query executed successfully: Logout mode");
                    }
                } elseif ($status == "Done") {
                    // Handle 'Done' status
                    $areaBuilder = $db->table($areaTable);
                    $weldIDQuery = $areaBuilder->select('WeldID')
                        ->where('MachineID', $machineID)
                        ->get();

                    if ($weldIDQuery->getNumRows() > 0) {
                        $weldIDRow = $weldIDQuery->getRow();
                        $weldID = $weldIDRow->WeldID;

                        // Delete data from the area
                        $areaBuilder->where('MachineID', $machineID)->delete();
                        log_message('debug', "Delete on Done: $machineID");

                        // Increment WeldID in the machine table
                        $machineBuilder = $db->table('machine');
                        $machineBuilder->where('MachineID', $machineID)
                            ->set('WeldID', 'WeldID + 1', false)
                            ->update();
                        log_message('debug', "Increment WeldID: $machineID");

                        // Calculate the total ArcTime from machinehistory1
                        $historyBuilder = $db->table('machinehistory1');
                        $arcTotalQuery = $historyBuilder->select('SEC_TO_TIME(SUM(TIME_TO_SEC(ArcTotal))) AS totalArcTime')
                            ->where('WeldID', $weldID)
                            ->get();

                        if ($arcTotalQuery->getNumRows() > 0) {
                            $totalArcTime = $arcTotalQuery->getRow()->totalArcTime;

                            // Update the corresponding row with the calculated totalArcTime
                            $areaBuilder->where('WeldID', $weldID)
                                ->update(['upTime' => $totalArcTime]);
                            log_message('debug', "Update totalArcTime: $weldID");
                            return $this->response->setStatusCode(200)->setBody("Query executed successfully: Update totalArcTime");
                        } else {
                            log_message('error', "Error calculating total ArcTime for WeldID: $weldID");
                            return $this->response->setStatusCode(500)->setBody("Error calculating total ArcTime.");
                        }
                    } else {
                        log_message('error', "Error retrieving WeldID for MachineID: $machineID");
                        return $this->response->setStatusCode(500)->setBody("Error retrieving WeldID.");
                    }
                }
            } else {
                return $this->response->setStatusCode(400)->setBody("Invalid area!");
            }
        } catch (\Exception $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setBody("Internal Server Error.");
        }
    }

    // kirimWaktu2.php
    public function updateMachineData()
    {
        // Load the database connection
        $db = \Config\Database::connect();

        // Read input data from GET request
        $Area = $this->request->getGet('Area');
        $UID = $this->request->getGet('UID');
        $MachineID = $this->request->getGet('MachineID');
        $Status = $this->request->getGet('Status');
        $WeldID = $this->request->getGet('weldID');
        $CurrentDC = $this->request->getGet('currentDC');
        $VoltageDC = $this->request->getGet('voltageAverage');
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
            $tableHistory = $Area == "1" ? "machinehistory1" : "machinehistory2";

            if ($Status == "ArcOn") {
                // Set all ledstate ledStatus to false
                $db->query("UPDATE ledstate SET ledStatus = 'false'");

                // First check for any unfinished MAINTENANCE, TOOLING, or SETUP states in additionalhistory
                $addHistoryBuilder = $db->table('additionalhistory');
                $unfinishedState = $addHistoryBuilder->select('id, ArcOn')
                    ->where('MachineID', $MachineID)
                    ->where('Area', $Area)
                    ->whereIn('State', ['MAINTENANCE', 'TOOLING', 'SETUP'])
                    ->where('ArcOff', null)
                    ->orderBy('id', 'DESC')
                    ->limit(1)
                    ->get()
                    ->getRow();

                if ($unfinishedState && isset($unfinishedState->id)) {
                    // Calculate ArcTotal for the unfinished state
                    $ArcTotal = date_diff(date_create($unfinishedState->ArcOn), date_create($Time))->format('%H:%I:%S');

                    // Update the unfinished state with ArcOff time
                    $addHistoryBuilder->where('id', $unfinishedState->id)->update([
                        'ArcOff' => $Time,
                        'ArcTotal' => $ArcTotal
                    ]);
                }

                // Then proceed with normal ArcOn operation
                $builder = $db->table($tableHistory);
                $dataArcOn = [
                    'State' => 'ON',
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
                        'State' => 'ON',
                        'lastBeat' => $DateTime,
                        'WeldID' => $WeldID,
                        'Area' => $Area,
                        'UID' => $UID,
                        'Name' => $Name,
                        'Date' => $Date,
                        'Login' => $Time,
                        'Status' => 'Active'
                    ]);
                    return $this->response->setBody('Data successfully updated or inserted');
                } else {
                    return $this->response->setStatusCode(500)->setBody('Error inserting ArcOn data: ' . $db->error());
                }
            } else if ($Status == "ArcOff") {
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
                        'CurrentDC' => $CurrentDC,
                        'Voltage' => $VoltageDC
                    ];

                    if ($builder->where('id', $latestRecord->id)->update($dataArcOff)) {
                        // Update the 'State' in area table to 'IDLE'
                        $areaBuilder = $db->table($tableArea);
                        $areaBuilder->where('MachineID', $MachineID)->update([
                            'State' => 'IDLE',
                            'lastBeat' => $DateTime
                        ]);

                        return $this->response->setBody('Data successfully updated for ArcOff');
                    } else {
                        return $this->response->setStatusCode(500)->setBody('Error updating ArcOff data: ' . $db->error());
                    }
                } else {
                    return $this->response->setBody('No ArcOn record found to calculate ArcTotal.');
                }
            } else if ($Status == "ArcCheck") {
                // Set all ledstate ledStatus to false
                $db->query("UPDATE ledstate SET ledStatus = 'false'");

                // First check for any unfinished MAINTENANCE, TOOLING, or SETUP states in additionalhistory
                $addHistoryBuilder = $db->table('additionalhistory');
                $unfinishedState = $addHistoryBuilder->select('id, ArcOn')
                    ->where('MachineID', $MachineID)
                    ->where('Area', $Area)
                    ->whereIn('State', ['MAINTENANCE', 'TOOLING', 'SETUP'])
                    ->where('ArcOff', null)
                    ->orderBy('id', 'DESC')
                    ->limit(1)
                    ->get()
                    ->getRow();

                if ($unfinishedState && isset($unfinishedState->id)) {
                    // Calculate ArcTotal for the unfinished state
                    $ArcTotal = date_diff(date_create($unfinishedState->ArcOn), date_create($Time))->format('%H:%I:%S');

                    // Update the unfinished state with ArcOff time
                    $addHistoryBuilder->where('id', $unfinishedState->id)->update([
                        'ArcOff' => $Time,
                        'ArcTotal' => $ArcTotal
                    ]);
                }

                // Handle ArcCheck on the correct machinehistory table
                $machineHistoryTable = $Area == "1" ? "machinehistory1" : "machinehistory2";
                $machineHistoryBuilder = $db->table($machineHistoryTable);
                $querySelect = $machineHistoryBuilder->select('id, ArcTotal')
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
                    $machineHistoryBuilder->where('id', $id)->update($dataUpdateArcCheck);

                    return $this->response->setBody('ArcTotal and ArcCheck successfully updated');
                } else {
                    return $this->response->setBody('No matching records found for ArcCheck');
                }
            }
        }

        return $this->response->setStatusCode(400)->setBody('Invalid Area or Status provided');
    }
}
