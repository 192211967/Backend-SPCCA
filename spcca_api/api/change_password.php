<?php
header("Content-Type: application/json");
require_once 'db.php';

$response = ['success' => false, 'error' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'];
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];

    // 1. Verify current password
    $query = "SELECT password FROM registration WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $dbPassword);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if (!$dbPassword) {
        $response['error'] = 'User not found';
    } elseif ($currentPassword !== $dbPassword) {
        $response['error'] = 'Current password is incorrect';
    } else {
        // 2. Update to new password (plain text)
        $updateQuery = "UPDATE registration SET password = ? WHERE user_id = ?";
        $updateStmt = mysqli_prepare($conn, $updateQuery);
        mysqli_stmt_bind_param($updateStmt, 'si', $newPassword, $userId);
        $success = mysqli_stmt_execute($updateStmt);
        mysqli_stmt_close($updateStmt);

        if ($success) {
            $response['success'] = true;
        } else {
            $response['error'] = 'Failed to update password';
        }
    }
} else {
    $response['error'] = 'Invalid request method';
}

echo json_encode($response);
?>
