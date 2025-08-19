<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log'); // Log errors to a file in the same directory
session_start();
include 'conexao.php'; // Assuming this file handles the database connection

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Update the tour_completed status to 0 for the current user
    $stmt = $conn->prepare("UPDATE users SET has_completed_tour = 0 WHERE id = ?");
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Tour status reset successfully.';
        $_SESSION['show_tour'] = true; // Also update session to reflect the change
    } else {
        $response['message'] = 'Failed to reset tour status: ' . $stmt->error;
    }
    $stmt->close();
} else {
    $response['message'] = 'User not logged in.';
}

$conn->close();
echo json_encode($response);
?>