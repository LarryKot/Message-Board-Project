<?php

// Start the session
session_start();

// Check if user is logged in, if not, redirect to login page
if (!isset($_SESSION['userid'])) {
    header("Location: FianlProjectV2.php");
    exit;
}

// Database connection settings
define('DB_hostname', "localhost");
define('DB_user', "kotlyar"); // Your username
define('DB_pass', "larry5936"); // Your password
define('DB_name', "kotlyar_"); // Your database name

// Function to add a reply to a post
function addReply($conn, $post_id, $body, $userid)
{
    try {
        // Insert new reply into database
        $stmt = $conn->prepare("INSERT INTO replies (post_id, body, userid, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$post_id, $body, $userid]);
        return true;
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return false;
    }
}

// Function to fetch comments for a post
function getComments($conn, $post_id)
{
    try {
        // Fetch comments for the specified post along with user information
        $stmt = $conn->prepare("SELECT r.body AS comment_body, u.username AS commenter_name 
                                FROM replies r 
                                INNER JOIN users u ON r.userid = u.userid 
                                WHERE r.post_id = ?");
        $stmt->execute([$post_id]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $comments;
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return [];
    }
}

// Check if form is submitted to add new post or reply
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['submit_post'])) {
        // Add new post
        $title = $_POST['title'];
        $body = $_POST['body'];
        $userid = $_SESSION['userid'];

        try {
            // Create connection
            $conn = new PDO("mysql:host=" . DB_hostname . ";dbname=" . DB_name, DB_user, DB_pass);
            // Set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Insert new post into database
            $stmt = $conn->prepare("INSERT INTO posts (title, body, userid, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$title, $body, $userid]);
            $post_added = true;
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    } elseif (isset($_POST['submit_reply'])) {
        // Add new reply
        $post_id = $_POST['post_id'];
        $body = $_POST['reply_body'];
        $userid = $_SESSION['userid'];

        try {
            // Create connection
            $conn = new PDO("mysql:host=" . DB_hostname . ";dbname=" . DB_name, DB_user, DB_pass);
            // Set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Call addReply function
            $reply_added = addReply($conn, $post_id, $body, $userid);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-image: url('dashboardbg.jpg');
            background-size: cover; /* Adjust as needed */
            background-position: center; /* Adjust as needed */
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        a {
            color: #007bff;
            text-decoration: none;
            margin-left: 20px;
        }

        .post {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .post h3 {
            color: #333;
            margin-top: 0;
        }

        .post p {
            color: #666;
        }

        .post .comment {
            margin-left: 20px;
            font-style: italic;
            color: #888;
        }

        form {
            margin-top: 20px;
        }

        label {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            display: block;
        }

        input[type="text"],
        input[type="password"],
        textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        button[type="submit"] {
            width: auto;
        }
    </style>
</head>

<body>

    <h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2>
    <a href="FinalProjectV2.php">Logout</a>
    <div class="container">
    <h3>Create New Post</h3>
    <form action="" method="post">
        <label for="title">Title:</label><br>
        <input type="text" id="title" name="title" required><br>
        <label for="body">Body:</label><br>
        <textarea id="body" name="body" rows="4" cols="50" required></textarea><br>
        <button type="submit" name="submit_post">Submit</button>
    </form>
    </div>


    <h3>Existing Posts</h3>
    <?php
    // Fetch posts from database and display
    try {
        // Create connection
        $conn = new PDO("mysql:host=" . DB_hostname . ";dbname=" . DB_name, DB_user, DB_pass);
        // Set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch all posts along with user information
        $stmt = $conn->query("SELECT p.*, u.username AS poster_name FROM posts p INNER JOIN users u ON p.userid = u.userid");
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Display posts
        foreach ($posts as $post) {
            echo "<div class='post'>";
            echo "<h3>" . $post['title'] . "</h3>";
            echo "<p>" . $post['body'] . "</p>";
            echo "<p>Posted by: " . $post['poster_name'] . "</p>";

            // Display comments for this post
            $comments = getComments($conn, $post['post_id']);
            if (!empty($comments)) {
                echo "<h4>Comments</h4>";
                echo "<ul>";
                foreach ($comments as $comment) {
                    echo "<li class='comment'>" . $comment['commenter_name'] . ": " . $comment['comment_body'] . "</li>";
                }
                echo "</ul>";
            }

            // Reply form for each post
            echo "<form action='' method='post'>";
            echo "<input type='hidden' name='post_id' value='" . $post['post_id'] . "'>";
            echo "<label for='reply_body'>Add Reply:</label><br>";
            echo "<textarea id='reply_body' name='reply_body' rows='2' cols='50' required></textarea><br>";
            echo "<button type='submit' name='submit_reply'>Reply</button>";
            echo "</form>";

            echo "</div>";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
    ?>

</body>

</html>
