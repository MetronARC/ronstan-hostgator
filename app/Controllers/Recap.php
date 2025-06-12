<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;

class Recap extends BaseController
{
    use ResponseTrait; // Include the ResponseTrait to use respond()

    protected $db, $builder;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->builder = $this->db->table('machine'); // Connect to the 'machine' table
    }

    public function index(): string
    {
        // Fetch all machine names from the 'machine' table
        $machines = $this->builder->select('MachineID')->get()->getResultArray();

        // Pass the machine names to the view
        $data = [
            'title' => 'Machine Recap',
            'sidebarData' => 'recap',
            'machines' => $machines
        ];

        return view('user/recap', $data);
    }

    public function fetchMachineData()
    {
        // Get POST data
        $input = $this->request->getJSON(true);
        $machineName = $input['machineName'] ?? '';
        $date = $input['date'] ?? '';

        // Query the machine history based on machineID and date
        $historyBuilder = $this->db->table('machinehistory1');
        $data = $historyBuilder->select('ArcOn, ArcOff')
            ->where('MachineID', $machineName)
            ->where('Date', $date)
            ->get()
            ->getResultArray();

        return $this->respond($data);
    }

    public function calculateUsagePercentage()
    {
        try {
            // Get JSON input data
            $input = $this->request->getJSON(true);

            // Validate required input data
            if (!isset($input['machineName'], $input['date'], $input['startTime'], $input['endTime'])) {
                throw new \Exception('Invalid input data');
            }

            $machineName = $input['machineName'];
            $date = $input['date'];
            $startTime = $input['startTime'];
            $endTime = $input['endTime'];

            $machineID = $machineName;

            // Step 2: Sum ArcTotal within the specified time range
            $historyBuilder = $this->db->table('machinehistory1');
            $result = $historyBuilder->select('SUM(TIME_TO_SEC(ArcTotal)) AS totalArcTimeInSeconds')
                ->where('MachineID', $machineID)
                ->where('Date', $date)
                ->where('ArcOn >=', $startTime)
                ->where('ArcOff <=', $endTime)
                ->get()
                ->getRow();

            $totalArcTimeInSeconds = (int)($result->totalArcTimeInSeconds ?? 0);

            // Step 3: Calculate the total seconds in the given time range
            $startDateTime = new \DateTime("$date $startTime");
            $endDateTime = new \DateTime("$date $endTime");
            $timeDifferenceInSeconds = $endDateTime->getTimestamp() - $startDateTime->getTimestamp();

            // Validate time range
            if ($timeDifferenceInSeconds <= 0) {
                throw new \Exception('Invalid time range. End time must be after start time.');
            }

            // Step 4: Calculate the usage percentage
            $usagePercentage = ($totalArcTimeInSeconds / $timeDifferenceInSeconds) * 100;

            // Return JSON response with usage data
            return $this->respond([
                'totalArcTime' => $totalArcTimeInSeconds,
                'usagePercentage' => round($usagePercentage, 2) // Rounded to two decimal places
            ]);
        } catch (\Exception $e) {
            // Return error response if an exception occurs
            return $this->respond(['error' => $e->getMessage()], 400);
        }
    }

    public function allCharts()
    {
        $date = $this->request->getPost('date'); // Get the date from POST data instead of GET

        // If no date is provided, redirect back with error
        if (empty($date)) {
            session()->setFlashdata('error', 'Please select a date');
            return redirect()->back();
        }

        // Set timezone to Asia/Jakarta
        $timezone = new \DateTimeZone('Asia/Jakarta');
        $currentDateTime = new \DateTime($date, $timezone);
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

        // Pass the date to the view
        $data = [
            'title' => 'All Machine Charts',
            'sidebarData' => 'recap',
            'date' => $date, // Pass the date to the view
            'arcTime' => $formattedArcTime
        ];

        return view('user/allChart', $data);
    }

    public function fetchChartData()
    {
        // Get the date from the request
        $input = $this->request->getJSON();
        $date = $input->date ?? '';

        if (empty($date)) {
            return $this->response->setJSON(['error' => 'Date is required'])->setStatusCode(400);
        }

        $data = [];

        // 1. Fetch 'ON' states from machinehistory1
        $sql1 = "SELECT m.MachineID, mh.ArcOn, mh.ArcOff, mh.State
            FROM machine m
            JOIN machinehistory1 mh ON m.MachineID = mh.MachineID
            WHERE mh.Date = ? AND UPPER(mh.State) = 'ON'";
        $stmt1 = $this->db->connID->prepare($sql1);
        $stmt1->bind_param("s", $date);
        $stmt1->execute();
        $result1 = $stmt1->get_result();
        while ($row = $result1->fetch_assoc()) {
            $data[$row['MachineID']][] = [
                'ArcOn' => $row['ArcOn'],
                'ArcOff' => $row['ArcOff'],
                'State' => $row['State'] ?? null
            ];
        }
        $stmt1->close();

        // 2. Fetch 'MAINTENANCE', 'SETUP', 'TOOLING' from additionalhistory
        $sql2 = "SELECT m.MachineID, ah.ArcOn, ah.ArcOff, ah.State
            FROM machine m
            JOIN additionalhistory ah ON m.MachineID = ah.MachineID
            WHERE ah.Date = ? AND UPPER(ah.State) IN ('MAINTENANCE', 'SETUP', 'TOOLING')";
        $stmt2 = $this->db->connID->prepare($sql2);
        $stmt2->bind_param("s", $date);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        while ($row = $result2->fetch_assoc()) {
            $data[$row['MachineID']][] = [
                'ArcOn' => $row['ArcOn'],
                'ArcOff' => $row['ArcOff'],
                'State' => $row['State'] ?? null
            ];
        }
        $stmt2->close();

        // Return merged data
        $response = [
            'date' => $date,
            'data' => $data
        ];
        return $this->response->setJSON($response);
    }
}
