<?php

namespace App\Controllers;

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

            // New query to get rows where ArcTotal > '11:00:00'
            $arcTimeQuery = $this->db->query("
            SELECT ArcOn, ArcOff, ArcTotal 
            FROM $tableName 
            WHERE ArcTotal > '11:00:00'");

            $arcTimes = $arcTimeQuery->getResultArray();

            // Loop through the rows and calculate the time difference
            // Loop through the rows and calculate the time difference
            foreach ($arcTimes as $arcTime) {
                $arcOn = new \DateTime($arcTime['ArcOn']);
                $arcOff = new \DateTime($arcTime['ArcOff']);

                // If ArcOff is earlier than ArcOn (crossing midnight), add 24 hours to ArcOff
                if ($arcOff < $arcOn) {
                    $arcOff->modify('+1 day');
                }

                // Calculate the time difference in seconds
                $timeDifference = $arcOff->getTimestamp() - $arcOn->getTimestamp();

                // Convert the time difference back to H:i:s format
                $hours = floor($timeDifference / 3600);
                $minutes = floor(($timeDifference % 3600) / 60);
                $seconds = $timeDifference % 60;
                $newArcTotal = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

                // Update the ArcTotal column with the new value
                $this->db->query(
                    "
        UPDATE $tableName 
        SET ArcTotal = ? 
        WHERE ArcOn = ? AND ArcOff = ?",
                    [$newArcTotal, $arcTime['ArcOn'], $arcTime['ArcOff']]
                );
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
}

// 1. select a sql table of machine, take all the row there and the value of the column "MachineID"
// 2. use the value there to pinpoint a table of machinehistory1, and select all the row with the each of the corresponding "MachineID" column value from the table "machine" to the "MachineID" column from the table machinehistory1
// 3. With that fetched rows, I want to select all the row after the very last row with descending order that has the value of the column "ArcOff" of NULL for each of the "MachineID". So each "MachineID" ideally will have all the rows that has the value of NULL but NOT the LATEST row
// 4. With each of the respective row that is taken from each of the "MachineID", I want to update the "ArcOff" column value that is previously NULL with the value of the "ArcCheck" column in that specific row. And then, I want to update the column "ArcTotal" with the time difference between the column of "ArcOn" in that row, and the column of "ArcOff" in that row.