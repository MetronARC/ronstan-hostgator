<?php

namespace App\Controllers;

class Record extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        // Fetch data from "tjobdata" table
        $query = $this->db->table('tjobdata')->select('*')->get();
        $data['jobRecords'] = $query->getResultArray(); // Convert results to array

        $query = $this->db->table('tweldmetaldata')->select('*')->get();
        $data['weldMetalRecords'] = $query->getResultArray(); // Convert results to array

        $query = $this->db->table('employee')->select('*')->get();
        $data['welderRecords'] = $query->getResultArray(); // Convert results to array

        $query = $this->db->table('tinspector')->select('*')->get();
        $data['inspectorRecords'] = $query->getResultArray(); // Convert results to array

        // Pass data to the view
        $data['title'] = 'Records';
        $data['sidebarData'] = 'record';

        return view('record/index', $data);
    }

    public function createProject()
    {

        $data = [
            'title'       => 'Create Record Page',
            'sidebarData' => 'record',
            'postValue'   => $this->request->getPost('postValue') ?? 'Error',
            'tmpQR'       => $result->tmpQR ?? '',  // Set empty string if null
            'tmpRFID'     => $result->tmpRFID ?? '' // Set empty string if null
        ];

        return view('record/project/createProject', $data);
    }

    public function fetchTmpDataProject()
    {
        $db = \Config\Database::connect();
        $query = $db->table('tmpdata')
            ->select('tmpQR, tmpRFID')
            ->where('scanType', 'jobScan')
            ->get();

        $result = $query->getRow();

        return $this->response->setJSON([
            'tmpQR'   => $result->tmpQR ?? '',
            'tmpRFID' => $result->tmpRFID ?? ''
        ]);
    }

    public function resetTmpDataProject()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tmpdata');

        // Update tmpQR and tmpRFID to empty strings where scanType is "jobScan"
        $builder->where('scanType', 'jobScan')->update([
            'tmpQR' => '',
            'tmpRFID' => ''
        ]);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function insertProject()
    {
        date_default_timezone_set('Asia/Jakarta'); // Set timezone

        $jobQR = $this->request->getPost('jobQR');
        $jobRFID = $this->request->getPost('rfidData');
        $jobNumber = $this->request->getPost('projectNumber');
        $jobName = $this->request->getPost('projectName');
        $jobDescription = $this->request->getPost('projectDescription');
        $jobCreationDate = date('Y-m-d H:i:s'); // Current timestamp

        // Log received values
        log_message('error', "JobQR: " . print_r($jobQR, true));

        // Validate required fields
        if (empty($jobNumber) || empty($jobName) || empty($jobDescription) || empty($jobQR)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'All fields are required.']);
        }

        // Insert data into database
        $db = \Config\Database::connect();
        $builder = $db->table('tjobdata');
        $insertData = [
            'jobQR'            => $jobQR,
            'jobRFID'          => $jobRFID,
            'jobNumber'        => $jobNumber,
            'jobName'          => $jobName,
            'jobDescription'   => $jobDescription,
            'jobCreationDate'  => $jobCreationDate
        ];

        if ($builder->insert($insertData)) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Record inserted successfully.']);
        } else {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to insert record.']);
        }
    }

    public function createWeldMetal(): string
    {
        $db = \Config\Database::connect(); // Load database connection
        $query = $db->table('tmpdata')
            ->select('tmpQR, tmpRFID')
            ->where('scanType', 'weldMetalScan')
            ->get();

        $result = $query->getRow(); // Fetch the first result

        $data = [
            'title'       => 'Create Record Page',
            'sidebarData' => 'record',
            'postValue'   => $this->request->getPost('postValue') ?? 'Error',
            'tmpQR'       => $result->tmpQR ?? '',  // Set empty string if null
            'tmpRFID'     => $result->tmpRFID ?? '' // Set empty string if null
        ];

        return view('record/weldMetal/createWeldMetal', $data);
    }

    public function fetchTmpDataWeldMetal()
    {
        $db = \Config\Database::connect();
        $query = $db->table('tmpdata')
            ->select('tmpQR, tmpRFID')
            ->where('scanType', 'weldMetalScan')
            ->get();

        $result = $query->getRow();

        return $this->response->setJSON([
            'tmpQR'   => $result->tmpQR ?? '',
            'tmpRFID' => $result->tmpRFID ?? ''
        ]);
    }

    public function resetTmpDataWeldMetal()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tmpdata');

        // Update tmpQR and tmpRFID to empty strings where scanType is "jobScan"
        $builder->where('scanType', 'weldMetalScan')->update([
            'tmpQR' => '',
            'tmpRFID' => ''
        ]);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function insertWeldMetal()
    {
        date_default_timezone_set('Asia/Jakarta'); // Set timezone

        // Get form data
        $WeldMetalQR = $this->request->getPost('weldMetalQR');
        $WeldMetalRFID = $this->request->getPost('rfidData');
        $WeldMetalNumber = $this->request->getPost('weldMetalBatchNumber');
        $WeldMetalType = $this->request->getPost('weldMetalType');
        $WeldMetalCertNumber = $this->request->getPost('weldMetalCertNumber');
        $WeldMetalDateOfDelivery = $this->request->getPost('weldMetalDateOfDelivery');
        $WeldMetalPONumber = $this->request->getPost('weldMetalPONumber');
        $WeldMetalMaterial = $this->request->getPost('weldMetalMaterial');
        $WeldMetalCreationDate = date('Y-m-d H:i:s'); // Current timestamp

        // Handle file upload
        $file = $this->request->getFile('weldMetalFile'); // Ensure the name matches the form input
        $fileName = '';

        // Debugging: Log file data
        log_message('error', 'File received: ' . print_r($file, true));

        if ($file && $file->isValid() && !$file->hasMoved()) {
            $fileName = $file->getName(); // Get the file name
            log_message('error', 'File name: ' . $fileName); // Log the file name
            $file->move(WRITEPATH . 'uploads'); // Move the file to the uploads directory
        } else {
            log_message('error', 'File upload failed or no file uploaded.');
            return $this->response->setJSON(['status' => 'error', 'message' => 'Please upload a valid file.']);
        }

        // Insert data into database
        $db = \Config\Database::connect();
        $builder = $db->table('tweldmetaldata');
        $insertData = [
            'weldMetalQR'            => $WeldMetalQR,
            'weldMetalRFID'          => $WeldMetalRFID,
            'weldMetal_batchNumber'  => $WeldMetalNumber,
            'weldMetal_type'         => $WeldMetalType,
            'weldMetal_certNo'       => $WeldMetalCertNumber,
            'weldMetal_DoD'          => $WeldMetalDateOfDelivery,
            'weldMetal_PO'           => $WeldMetalPONumber,
            'weldMetal_material'     => $WeldMetalMaterial,
            'weldMetal_image'        => $fileName, // Store the file name
            'weldMetal_creationDateTime' => $WeldMetalCreationDate
        ];

        if ($builder->insert($insertData)) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Record inserted successfully.']);
        } else {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to insert record.']);
        }
    }
}
