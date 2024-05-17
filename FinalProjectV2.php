<?php
session_start();

// Check if user is already logged in, if yes, redirect to dashboard

// Database connection settings
define('DB_hostname', "localhost");
define('DB_user', "kotlyar"); // Your username
define('DB_pass', "larry5936"); // Your password
define('DB_name', "kotlyar_"); // Your database

function registerUser($conn, $username, $email, $password) {
    // Check if username already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If username does not exist, insert new user
    if(!$user) {
        // Hash password before storing
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user into database
        $stmt = $conn->prepare("INSERT INTO users (username, email_address, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashed_password]); // Include email address
        return true; // User registration successful
    } else {
        return false; // Username already exists
    }
}

// Check if form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Retrieve form data
    $username = $_POST['username'];
    $email = $_POST['email']; // Add email retrieval
    $password = $_POST['password'];
    
    // If register button is clicked, attempt to register new user
    if(isset($_POST['register'])) {
        try {
            // Create connection
            $conn = new PDO("mysql:host=" . DB_hostname . ";dbname=" . DB_name, DB_user, DB_pass);
            // Set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Attempt to register new user
            $registration_success = registerUser($conn, $username, $email, $password); // Pass email argument
            
            if($registration_success) {
                $registration_message = "Registration successful. Please log in.";
            } else {
                $registration_error = "Username already exists. Please choose a different username.";
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    } else { // If login button is clicked, attempt to login
        try {
            // Create connection
            $conn = new PDO("mysql:host=" . DB_hostname . ";dbname=" . DB_name, DB_user, DB_pass);
            // Set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Prepare statement to fetch user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify user password
            if($user && password_verify($password, $user['password_hash'])) {
                // Store user data in session
                $_SESSION['userid'] = $user['userid'];
                $_SESSION['username'] = $user['username'];
                
                // Redirect to dashboard
                header("Location: Dashboard.php");
                exit;
            } else {
                $login_error = "Invalid username or password";
            }
        } catch(PDOException $e) {
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
    <title>Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-image: url('techbg.jpg');
            background-size: cover; /* Adjust as needed */
            background-position: center; /* Adjust as needed */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 400px;
            max-width: 90%;
            margin-bottom: 20px; /* Add margin between containers */
            margin-left: 50px
        }

        .container:last-child {
            margin-bottom: 0; /* Remove margin for the last container */
        }

        h2 {
            margin-top: 0;
            color: #333;
            margin-bottom: 10px; /* Add space below each h2 tag */
        }

        form {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button[type="submit"] {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }

        button[type="submit"]:hover {
            background-color: #0056b3;
        }

        p {
            color: red;
        }

        a {
            color: #007bff;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .post {
            background-color: #f9f9f9;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Login</h2>
    <form action="" method="post">
        <label for="username">Username:</label><br>
        <input type="text" id="username" name="username" required><br>
        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" required><br>
        <button type="submit" name="login">Login</button>
    </form>
    <?php if(isset($login_error)) echo "<p>$login_error</p>"; ?>
</div>

<div class="container">
    <h2>Register</h2>
    <form action="" method="post">
        <label for="reg_username">Username:</label><br>
        <input type="text" id="reg_username" name="username" required><br>
        <label for="email">Email Address:</label><br>
        <input type="email" id="email" name="email" required><br> <!-- Add email input field -->
        <label for="reg_password">Password:</label><br>
        <input type="password" id="reg_password" name="password" required><br>
        <button type="submit" name="register">Register</button>
    </form>
    <?php if(isset($registration_message)) echo "<p style='color: green;'>$registration_message</p>"; ?>
    <?php if(isset($registration_error)) echo "<p>$registration_error</p>"; ?>
</div>

<!-- View Comments and Add Comments Form -->
<?php
// Fetch posts from database and display
try {
    // Create connection
    $conn = new PDO("mysql:host=" . DB_hostname . ";dbname=" . DB_name, DB_user, DB_pass);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch 5 most recent posts
    $stmt = $conn->prepare("SELECT * FROM posts ORDER BY post_id DESC LIMIT 5");
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

</body>
</html>
