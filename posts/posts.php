<?php

include_once '../php/all.php';
include_once '../php/database.php';

try {
    $conn = getConnection();
} catch (Exception $e) {
    error_log($e->getMessage());
    header('Location: ../maintenance/');
    exit;
}

session_start();

// Validate request method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate session and action
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401); // Unauthorized
        $conn->close();
        exit();
    }

    $userId = $_SESSION['user_id'];

    // Disable autocommit to start a new transaction
    $conn->autocommit(false);

    try {
        // Validate JSON data
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        validateJsonData($data, ['action']);
        $action = $data['action'];
        switch ($action) {
            case 'upvote':
            case 'downvote':
                validateJsonData($data, ['post_id']);
                $postId = $data['post_id'];
                handleVote($conn, $userId, $postId, $action);
                break;
            default:
                throw new InvalidArgumentException("Invalid action provided.");
        }

        // Commit the transaction if no exceptions occur
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        handleException($e);
    } finally {
        // Re-enable autocommit
        $conn->autocommit(true);
    }
}

// Set character encoding to UTF-8
header('Content-Type: application/json; charset=utf-8');

// Return posts as JSON
if (isset($_SESSION['user_id'])) {
    echo json_encode(getPosts($conn));
} else {
    echo json_encode(getPosts($conn));
}

// Close the database connection
$conn->close();
