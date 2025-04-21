<?php
// Database Connection
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "root", "", "userdb");

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Disable MySQLi exceptions and handle errors manually
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Process form data
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Server-side validation to ensure passwords match
    if ($password !== $confirm_password) {
        echo "<script>
        alert('Error: Passwords do not match!');
        history.back();
        </script>";
        exit();
    }
    
    // Check if username already exists
    $check_username = "SELECT * FROM users WHERE username = ?";
    $stmt_check_username = $conn->prepare($check_username);
    $stmt_check_username->bind_param("s", $username);
    $stmt_check_username->execute();
    $result_username = $stmt_check_username->get_result();
    
    if ($result_username->num_rows > 0) {
        echo "<script>
        alert('Error: Username already exists. Please choose a different username.');
        history.back();
        </script>";
        $stmt_check_username->close();
        $conn->close();
        exit();
    }
    $stmt_check_username->close();
    
    // Check if email already exists
    $check_email = "SELECT * FROM users WHERE email = ?";
    $stmt_check_email = $conn->prepare($check_email);
    $stmt_check_email->bind_param("s", $email);
    $stmt_check_email->execute();
    $result_email = $stmt_check_email->get_result();
    
    if ($result_email->num_rows > 0) {
        echo "<script>
        alert('Error: The email is already registered.');
        history.back();
        </script>";
        $stmt_check_email->close();
        $conn->close();
        exit();
    }
    $stmt_check_email->close();
    
    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Prepare SQL statement for insertion
    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sss", $username, $email, $password_hash);

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
            // Handle unexpected errors
            echo "<script>alert('Error during registration: " . $e->getMessage() . "');</script>";
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
        .error-message {
            color: red;
            font-size: 14px;
            margin-top: 2px;
            display: none;
        }
        button {
            width: 100%;
            padding: 10px;
            background:rgb(40, 137, 167);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background:rgb(33, 103, 136);
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>User Registration</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" id="registrationForm" onsubmit="return validateForm()">
            <input type="text" name="username" placeholder="Enter Username" required>
            <input type="email" name="email" placeholder="Enter Email" required>
            <input type="password" name="password" id="password" placeholder="Enter Password" required>
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
            <div id="passwordError" class="error-message">Passwords do not match!</div>
            <button type="submit">Register</button>
        </form>
    </div>

    <script>
        function validateForm() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const passwordError = document.getElementById('passwordError');
            
            if (password !== confirmPassword) {
                passwordError.style.display = 'block';
                return false;
            } else {
                passwordError.style.display = 'none';
                return true;
            }
        }
    </script>

</body>
</html>