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
    <title>Register - MarketPlace Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --accent-color: #e74c3c;
            --light-gray: #f5f7fa;
            --dark-gray: #34495e;
            --text-color: #333;
            --white: #ffffff;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--light-gray);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header Styles */
        header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--white);
        }
        
        .logo i {
            margin-right: 10px;
            font-size: 2rem;
        }
        
        /* Main Content */
        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        
        .register-container {
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            padding: 2.5rem;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-header h2 {
            font-size: 1.8rem;
            color: var(--dark-gray);
            margin-bottom: 0.5rem;
            font-weight: 600;
            position: relative;
        }
        
        .register-header h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 3px;
        }
        
        .register-header p {
            color: #888;
            font-size: 0.95rem;
            margin-top: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark-gray);
            font-size: 0.95rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.5rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }
        
        .form-group i {
            position: absolute;
            left: 12px;
            top: 38px;
            color: #aaa;
        }
        
        .form-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
            background-color: var(--white);
        }
        
        .error-message {
            color: var(--accent-color);
            font-size: 0.85rem;
            margin-top: 0.5rem;
            display: none;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            font-size: 1rem;
            text-align: center;
            border: none;
            width: 100%;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary i {
            margin-right: 8px;
        }
        
        .register-footer {
            text-align: center;
            margin-top: 2rem;
            color: #888;
            font-size: 0.9rem;
        }
        
        .register-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .register-footer a:hover {
            text-decoration: underline;
        }
        
        /* Responsive styles */
        @media (max-width: 576px) {
            .register-container {
                padding: 1.5rem;
            }
            
            main {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="main_page.php" class="logo">
                <i class="fas fa-store"></i> AdDrop
            </a>
        </div>
    </header>
    
    <main>
        <div class="register-container">
            <div class="register-header">
                <h2>Create Account</h2>
                <p>Join our marketplace community today</p>
            </div>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" id="registrationForm" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" class="form-input" placeholder="Choose a username" required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-input" placeholder="Create a password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <i class="fas fa-check"></i>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Confirm your password" required>
                    <div id="passwordError" class="error-message">
                        <i class="fas fa-exclamation-circle"></i> Passwords do not match!
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Register Account
                </button>
            </form>
            
            <div class="register-footer">
                Already have an account? <a href="user_login.php">Sign In</a> | <a href="main_page.php">Return to Marketplace</a>
            </div>
        </div>
    </main>
    
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
        
        // Enhance form experience with real-time validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const passwordError = document.getElementById('passwordError');
            
            if (password !== confirmPassword) {
                passwordError.style.display = 'block';
            } else {
                passwordError.style.display = 'none';
            }
        });
    </script>
</body>
</html>