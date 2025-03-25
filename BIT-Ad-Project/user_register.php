<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
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
        }
        button:hover {
            background:rgb(33, 103, 136);
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>User Registration</h2>
        <form action="user_register.php" method="POST">
            <input type="text" name="username" placeholder="Enter Username" required>
            <input type="email" name="email" placeholder="Enter Email" required>
            <input type="password" name="password" placeholder="Enter Password" required>
            <button type="submit">Register</button>
        </form>
    </div>

</body>
</html>

<?php
// Database Connection
$conn = new mysqli("localhost", "root", "", "userdb");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Disable MySQLi exceptions and handle errors manually
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Encrypt password

    // Prepare SQL statement
    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sss", $username, $email, $password);

        try {
            // Try to execute the statement
            if ($stmt->execute()) {
                // Registration Successful
                echo "<script>
                alert('Registration Successful!');
                window.location.href='user_login.php';
                </script>";
            }
        } catch (mysqli_sql_exception $e) {
            // Handle error for duplicate email (error code 1062)
            if ($e->getCode() == 1062) {  // 1062 is the MySQL error code for duplicate entry
                echo "<script>alert('Error: The email is already registered.');</script>";
            } else {
                echo "<script>alert('Error during registration: " . $e->getMessage() . "');</script>";
            }
        }

        // Close statement
        $stmt->close();
    } else {
        echo "<script>alert('Error: Failed to prepare SQL statement.');</script>";
    }

    // Close database connection
    $conn->close();
}
?>
