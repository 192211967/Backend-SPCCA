<?php
header("Content-Type: application/json");
require_once 'db.php';  // Ensure db.php is included and the connection is created

$response = ['success' => false, 'error' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get all input data
    $userId   = $_POST['user_id'] ?? '';
    $firstName = trim($_POST['First_Name'] ?? '');
    $lastName  = trim($_POST['Last_Name'] ?? '');
    $username  = trim($_POST['Username'] ?? '');
    $email     = trim($_POST['Email'] ?? '');
    $phone     = trim($_POST['Phone_No'] ?? '');

    // Validate required fields
    if (empty($userId) || empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($phone)) {
        $response['error'] = 'All fields are required';
        echo json_encode($response);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['error'] = 'Invalid email format';
        echo json_encode($response);
        exit;
    }

    // Validate phone number format
    if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $response['error'] = 'Invalid phone number';
        echo json_encode($response);
        exit;
    }

    // Create connection (using $conn from db.php)
    // Ensure $conn is already initialized from db.php

    try {
        // Check if email, phone, or username already exists for a different user
        $checkStmt = $conn->prepare("SELECT user_id FROM registration WHERE (Email = ? OR Phone_No = ? OR Username = ?) AND user_id != ?");
        $checkStmt->bind_param('sssi', $email, $phone, $username, $userId);  // Bind parameters to avoid SQL injection
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $response['error'] = 'Email, phone, or username already exists';
            echo json_encode($response);
            exit;
        }

        // Update user information
        $stmt = $conn->prepare("UPDATE registration SET 
            First_Name = ?, 
            Last_Name = ?, 
            Username = ?, 
            Email = ?, 
            Phone_No = ? 
            WHERE user_id = ?");
            
        $stmt->bind_param('sssssi', $firstName, $lastName, $username, $email, $phone, $userId);  // Bind parameters

        $success = $stmt->execute();

        if ($success) {
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'Profile updated successfully';
            } else {
                $response['error'] = 'No changes made or user not found';
            }
        } else {
            $response['error'] = 'Update failed';
        }
    } catch (Exception $e) {
        $response['error'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['error'] = 'Invalid request method';
}

echo json_encode($response);
?>
