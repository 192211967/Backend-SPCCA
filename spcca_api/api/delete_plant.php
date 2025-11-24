<?php
header("Content-Type: application/json");

require_once 'db.php'; // Ensure this defines DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $plantId = (int) ($_POST['plant_id'] ?? 0);

    if ($userId > 0 && $plantId > 0) {
        $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

        if (!$conn) {
            http_response_code(500);
            $response['message'] = 'Database connection failed: ' . mysqli_connect_error();
            echo json_encode($response);
            exit;
        }

        try {
            // Verify ownership
            $query = "SELECT user_id FROM my_plants WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $query)) {
                mysqli_stmt_bind_param($stmt, "i", $plantId);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $plant = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);

                if ($plant && (int)$plant['user_id'] === $userId) {
                    // Proceed to delete
                    $query = "DELETE FROM my_plants WHERE id = ?";
                    if ($stmt = mysqli_prepare($conn, $query)) {
                        mysqli_stmt_bind_param($stmt, "i", $plantId);
                        mysqli_stmt_execute($stmt);

                        if (mysqli_stmt_affected_rows($stmt) > 0) {
                            http_response_code(200);
                            $response['success'] = true;
                            $response['message'] = 'Plant deleted successfully';
                        } else {
                            http_response_code(404);
                            $response['message'] = 'No rows affected - plant may not exist';
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                        http_response_code(500);
                        $response['message'] = 'Failed to prepare delete statement: ' . mysqli_error($conn);
                    }
                } else {
                    http_response_code(403);
                    $response['message'] = 'Plant not found or not owned by user';
                    error_log("Unauthorized delete attempt by user $userId for plant $plantId");
                }
            } else {
                http_response_code(500);
                $response['message'] = 'Failed to prepare select statement: ' . mysqli_error($conn);
            }
        } catch (Exception $e) {
            http_response_code(500);
            $response['message'] = 'Database error: ' . $e->getMessage();
        } finally {
            mysqli_close($conn);
        }
    } else {
        http_response_code(400);
        $response['message'] = 'Missing or invalid parameters';
    }
} else {
    http_response_code(405);
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
