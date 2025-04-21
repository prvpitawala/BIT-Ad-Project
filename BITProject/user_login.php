<?php
session_start(); // Start session for user authentication

// Database Connection
$conn = new mysqli("localhost", "root", "", "userdb");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // First check in users table
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists in users table
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['is_admin'] = 0; // Regular user

            // Redirect to main page
            echo "<script>window.location.href='loged_main_page.php';</script>";
            exit();
        } else {
            $user_error = true;
        }
    } else {
        $user_error = true;
    }

    $stmt->close();

    // If user not found or password incorrect, check admins table
    if (isset($user_error)) {
        $sql = "SELECT * FROM admins WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if user exists in admins table
        if ($result->num_rows == 1) {
            $admin = $result->fetch_assoc();
            
            // Verify the password
            if (password_verify($password, $admin['password'])) {
                // Set session variables
                $_SESSION['username'] = $admin['username'];
                $_SESSION['email'] = $admin['email'];
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['is_admin'] = 1; // Admin user

                // Redirect to admin dashboard
                echo "<script>window.location.href='admin_dashboard.php';</script>";
                exit();
            } else {
                echo "<script>alert('Incorrect password. Please try again.');</script>";
            }
        } else {
            echo "<script>alert('User not found. Please check your username.');</script>";
        }

        $stmt->close();
    }
}

$conn->close();
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
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            width: 350px;
            text-align: center;
        }
        h2 {
            text-align: center;
        }
        input {
            width: 93%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            width: 100%;
            padding: 10px;
            background:rgb(40, 137, 167);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 5px;
        }
        button:hover {
            background:rgb(33, 103, 136);
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <form action="user_login.php" method="POST">
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <button type="submit">Login</button>
        </form>
        <button onclick="window.location.href='user_register.php'">Register</button>
    </div>
</body>
</html>