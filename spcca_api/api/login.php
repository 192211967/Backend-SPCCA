<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "db.php";

if (!$conn) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit;
}

// Get and validate input
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$username = trim($input['Username'] ?? '');
$password = trim($input['Password'] ?? '');

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Username and password are required"]);
    exit;
}

try {
    // Determine login field type
    $field = "Username";
    if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $field = "Email";
    } elseif (preg_match('/^\d{10,15}$/', $username)) {
        $field = "Phone_No";
    }

    // Get user data
    $stmt = $conn->prepare("SELECT user_id, Username, Email, Phone_No, Password, login_count FROM registration WHERE $field = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(["success" => false, "error" => "User not found"]);
        exit;
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // Verify password (switch to password_verify() for hashed passwords)
    if ($password !== $user['Password']) {
        http_response_code(401);
        echo json_encode(["success" => false, "error" => "Incorrect password"]);
        exit;
    }

    // Update login count
    $newCount = $user['login_count'] + 1;
    $updateStmt = $conn->prepare("UPDATE registration SET login_count = ? WHERE user_id = ?");
    $updateStmt->bind_param("is", $newCount, $user['user_id']);
    $updateStmt->execute();
    $updateStmt->close();

    // Successful response
    echo json_encode([
        "success" => true,
        "user_id" => $user['user_id'],
        "username" => $user['Username'],
        "email" => $user['Email'],
        "phone" => $user['Phone_No'],
        "login_count" => $newCount,
        "is_first_login" => ($newCount === 1)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Server error: " . $e->getMessage()]);
    error_log("Login error: " . $e->getMessage());
} finally {
    $conn->close();
}
?>