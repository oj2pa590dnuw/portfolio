<?php
// likod/add_patient_backend.php

header('Content-Type: application/json');

require 'session_utils.php';
require 'db_con.php';
require 'activity_logger.php';

enforce_login(true);

$data = $_POST;

if (empty($data)) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

try {
    // Database connection
    global $conn;

    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Get data
    $firstName = $conn->real_escape_string($data['firstName'] ?? '');
    $middleName = $conn->real_escape_string($data['middleName'] ?? '');
    $lastName = $conn->real_escape_string($data['lastName'] ?? '');
    $age = (int) ($data['age'] ?? 0);
    $location = $conn->real_escape_string($data['location'] ?? '');

    // Check if patient with same name already exists
    if (!empty($firstName) && !empty($lastName)) {
        $check_sql = "SELECT id FROM patients WHERE 
                     fullName = '$firstName' AND 
                     last_name = '$lastName'";

        // Add middle name check if provided
        if (!empty($middleName)) {
            $check_sql .= " AND middle_name = '$middleName'";
        }

        $check_result = $conn->query($check_sql);

        if ($check_result && $check_result->num_rows > 0) {
            // Patient already exists
            $existing = $check_result->fetch_assoc();
            echo json_encode([
                'success' => false,
                'message' => "Patient '$firstName $middleName $lastName' already exists in the system. Patient ID: " . $existing['id']
            ]);
            exit;
        }
    }

    // Generate ID and code if not duplicate
    $patient_id = md5(uniqid(rand(), true));
    $patient_code = 'TEMP-' . $patient_id;

    // Get remaining data
    $contactNumber = $conn->real_escape_string($data['contactNumber'] ?? '');
    $birthDate = $conn->real_escape_string($data['birthDate'] ?? '0000-00-00');
    $philhealth_id = $conn->real_escape_string($data['philhealth_id'] ?? '');
    $local_patient_id = $conn->real_escape_string($data['local_patient_id'] ?? '');
    $weight = $conn->real_escape_string($data['weight'] ?? '');
    $height = $conn->real_escape_string($data['height'] ?? '');
    $warning = $conn->real_escape_string($data['warning'] ?? '');
    $bloodPressure = $conn->real_escape_string($data['bloodPressure'] ?? '');
    $heartRate = $conn->real_escape_string($data['heartRate'] ?? '');
    $respiratoryRate = $conn->real_escape_string($data['respiratoryRate'] ?? '');
    $temperature = $conn->real_escape_string($data['temperature'] ?? '');
    $clinicalNotes = $conn->real_escape_string($data['clinicalNotes'] ?? '');
    $title = $conn->real_escape_string($data['title'] ?? '');
    $description = $conn->real_escape_string($data['description'] ?? '');
    $otherInfo = $conn->real_escape_string($data['otherInfo'] ?? '');
    $time = $conn->real_escape_string($data['time'] ?? '');
    $normalRanges = $conn->real_escape_string($data['normalRanges'] ?? '');
    $lastCheckup = $conn->real_escape_string($data['lastCheckup'] ?? date('Y-m-d'));

    // Checkbox fields
    $isCritical = isset($data['isCritical']) && $data['isCritical'] == '1' ? 1 : 0;
    $isPregnant = isset($data['isPregnant']) && $data['isPregnant'] == '1' ? 1 : 0;
    $isElderly = isset($data['isElderly']) && $data['isElderly'] == '1' ? 1 : 0;
    $isWarningFlag = isset($data['isWarningFlag']) && $data['isWarningFlag'] == '1' ? 1 : 0;
    $isStable = isset($data['isStable']) && $data['isStable'] == '1' ? 1 : 0;
    $hasHighBP = isset($data['hasHighBP']) && $data['hasHighBP'] == '1' ? 1 : 0;
    $needsMedication = isset($data['needsMedication']) && $data['needsMedication'] == '1' ? 1 : 0;
    $needsAppointment = isset($data['needsAppointment']) && $data['needsAppointment'] == '1' ? 1 : 0;

    $registered_by_user_id = $_SESSION['user_id'];

    // SQL query
    $sql = "INSERT INTO patients (
        id, patient_code, fullName, middle_name, last_name, age, birthDate, 
        philhealth_id, local_patient_id, contactNumber, location, lastCheckup, 
        warning, bloodPressure, heartRate, respiratoryRate, temperature, 
        clinicalNotes, isCritical, isPregnant, isElderly, isWarningFlag, 
        isStable, registered_by_user_id, weight, height, title, description, 
        otherInfo, time, normalRanges, hasHighBP, needsMedication, needsAppointment
    ) VALUES (
        '$patient_id',
        '$patient_code',
        '$firstName',
        '$middleName',
        '$lastName',
        $age,
        '$birthDate',
        '$philhealth_id',
        '$local_patient_id',
        '$contactNumber',
        '$location',
        '$lastCheckup',
        '$warning',
        '$bloodPressure',
        '$heartRate',
        '$respiratoryRate',
        '$temperature',
        '$clinicalNotes',
        $isCritical,
        $isPregnant,
        $isElderly,
        $isWarningFlag,
        $isStable,
        $registered_by_user_id,
        '$weight',
        '$height',
        '$title',
        '$description',
        '$otherInfo',
        '$time',
        '$normalRanges',
        $hasHighBP,
        $needsMedication,
        $needsAppointment
    )";

    if ($conn->query($sql) === TRUE) {
        $logger = new ActivityLogger();
        $logger->log($_SESSION['user_id'], 'PATIENT_ADDED', 'Added new patient', 'patients', $patient_id);

        echo json_encode([
            'success' => true,
            'message' => 'Patient registered successfully!',
            'patient_id' => $patient_id,
            'patient_code' => $patient_code
        ]);
    } else {
        error_log("SQL Error: " . $conn->error);
        error_log("SQL Query: " . $sql);

        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $conn->error
        ]);
    }

} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
    exit;
}
?>