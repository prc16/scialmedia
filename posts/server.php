<?php
include '../config.php';

// Function to get all posts from the database
function getPosts()
{
    global $conn;
    $sql = "SELECT * FROM posts";
    $result = $conn->query($sql);

    $posts = array();
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }

    return $posts;
}

// Function to create a new post in the database
function createPost($title, $content)
{
    global $conn;

    // Retrieve user_id from session variable
    session_start();
    $user_id = $_SESSION['user_id'];

    $title = $conn->real_escape_string($title);
    $content = $conn->real_escape_string($content);

    $sql = "INSERT INTO posts (title, content, votes, user_id) VALUES ('$title', '$content', 0, '$user_id')";
    $conn->query($sql);
}

// Function to update post votes in the database
function updatePostVotes($postId, $voteIncrement)
{
    global $conn;
    $postId = (int)$postId;

    $sql = "UPDATE posts SET votes = votes + $voteIncrement WHERE id = $postId";
    $conn->query($sql);
}


// Function to create a new vote in the database
function createVote($userId, $postId, $voteType)
{
    global $conn;
    $userId = $conn->real_escape_string($userId);
    $postId = (int)$postId;
    $voteType = $conn->real_escape_string($voteType);

    $sql = "INSERT INTO votes (user_id, post_id, vote_type) VALUES ('$userId', $postId, '$voteType')";
    $conn->query($sql);
}

// Function to check if a user has already voted on a post
function getUserVoteType($userId, $postId)
{
    global $conn;
    $userId = $conn->real_escape_string($userId);
    $postId = (int)$postId;

    $sql = "SELECT vote_type FROM votes WHERE user_id = '$userId' AND post_id = $postId";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['vote_type'];
    }

    return null;
}

// Function to remove a user's vote on a post
function removeUserVote($userId, $postId)
{
    global $conn;
    $userId = $conn->real_escape_string($userId);
    $postId = (int)$postId;

    $sql = "DELETE FROM votes WHERE user_id = '$userId' AND post_id = $postId";
    $conn->query($sql);
}

// Function to handle upvote and downvote
function handleVote($postId, $voteType)
{
    session_start();
    $userId = $_SESSION['user_id'];

    $currentVoteType = getUserVoteType($userId, $postId);

    if ($currentVoteType === $voteType) {
        // User is trying to upvote/downvote again on the same post, remove the vote
        removeUserVote($userId, $postId);
        updatePostVotes($postId, ($voteType === 'upvote' ? -1 : 1));
    } else {
        // User is either changing their vote or voting for the first time
        if ($currentVoteType !== null) {
            // User has already voted on this post, remove the old vote
            removeUserVote($userId, $postId);
            updatePostVotes($postId, ($currentVoteType === 'upvote' ? -1 : 1));
        }

        // Update post votes and insert the new vote
        updatePostVotes($postId, ($voteType === 'upvote' ? 1 : -1));
        createVote($userId, $postId, $voteType);
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start(); // Start the session

    if (isset($_POST['action']) && isset($_SESSION['user_id'])) {
        // Check if user is logged in
        switch ($_POST['action']) {
            case 'create':
                if (isset($_POST['title']) && isset($_POST['content'])) {
                    createPost($_POST['title'], $_POST['content']);
                }
                break;
            case 'upvote':
            case 'downvote':
                if (isset($_POST['postId'])) {
                    handleVote($_POST['postId'], $_POST['action']);
                }
                break;
        }
    } else {
        // Handle unauthorized access (e.g., redirect to login page or return an error)
        header('HTTP/1.0 401 Unauthorized');
        exit();
    }
}

// Return posts as JSON
header('Content-Type: application/json');
echo json_encode(getPosts());

$conn->close();
