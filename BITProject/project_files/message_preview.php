<?php
    session_start(); // Start the session

    // Check if user is logged in by verifying session variables
    if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
        // If not logged in, redirect to the login page
        header("Location: user_login.php");
        exit();
    }

    // Check if the logout button was clicked
    if (isset($_POST['logout'])) {
        // Destroy session and log the user out
        session_unset();  // Removes all session variables
        session_destroy(); // Destroys the session
        header("Location: user_login.php"); // Redirect to login page after logging out
        exit();
    }

    // Check if the message ID is provided
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        // If no valid ID is provided, redirect to the contact page
        header("Location: contact_page.php");
        exit();
    }

    // Database connection
    $conn = new mysqli("localhost", "root", "", "userdb");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch the requested message
    $message_id = $_GET['id'];
    $user_id = $_SESSION['user_id']; // Get the logged-in user's ID
    $message_data = null;
    
    // Prepare SQL statement to fetch the message (ensure user_id matches to prevent unauthorized access)
    $sql = "SELECT id, subject, message, reply, submission_date, reply_date FROM contact_messages 
            WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $message_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $message_data = $result->fetch_assoc();
    } else {
        // If no message is found or it doesn't belong to the user, redirect to contact page
        header("Location: contact_page.php");
        exit();
    }
    
    $stmt->close();
    $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Preview - MarketPlace Hub</title>
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
        }
        
        /* Header Styles */
        header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
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
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .nav-link {
            color: var(--white);
            text-decoration: none;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .nav-link i {
            font-size: 1.1rem;
        }
        
        .user-area {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--white);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .username {
            font-weight: 600;
        }
        
        .btn {
            padding: 0.7rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            border: none;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-logout {
            background-color: transparent;
            color: var(--white);
            border: 2px solid var(--white);
        }
        
        .btn-logout:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.2);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(52, 152, 219, 0.3);
        }

        /* Main Content */
        main {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            color: var(--dark-gray);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: #777;
            font-size: 1.1rem;
        }
        
        /* Message Preview Section */
        .message-preview {
            background-color: var(--white);
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            animation: fadeIn 0.5s ease forwards;
        }
        
        .message-info {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .message-subject {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--dark-gray);
        }
        
        .message-date {
            color: #777;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .message-header, .reply-header {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.8rem;
        }
        
        .message-content, .reply-content {
            background-color: #f8f8f8;
            padding: 1rem;
            border-radius: 8px;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            color: #555;
        }
        
        .reply-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
        
        .reply-date {
            color: #777;
            font-size: 0.9rem;
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .no-reply {
            color: #777;
            font-style: italic;
            padding: 1rem;
            background-color: #f8f8f8;
            border-radius: 8px;
            text-align: center;
        }
        
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
        }
        
        .back-link {
            text-align: center;
            margin-top: 1rem;
        }
        
        .back-link a {
            color: var(--primary-color);
            text-decoration: none;
            margin: 0 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .back-link a:hover {
            text-decoration: underline;
            color: var(--secondary-color);
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .header-container, main {
                padding: 0 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            .nav-links {
                width: 100%;
                justify-content: center;
                margin: 0.5rem 0;
            }
            
            .user-area {
                width: 100%;
                justify-content: space-between;
            }
            
            main {
                padding: 0 1rem;
                margin: 1rem auto;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .page-subtitle {
                font-size: 0.9rem;
            }
            
            .message-subject {
                font-size: 1.2rem;
            }
        }
        
        @media (max-width: 576px) {
            .nav-links {
                gap: 0.5rem;
            }
            
            .nav-link {
                padding: 0.4rem 0.8rem;
                font-size: 0.9rem;
            }
            
            .user-area {
                flex-direction: column;
                gap: 0.8rem;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 1rem;
            }
            
            .action-buttons .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="main_page.php" class="logo">
                <i class="fas fa-store"></i> MarketPlace Hub
            </a>
            
            <div class="nav-links">
                <a href="main_page.php" class="nav-link">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="loged_main_page.php" class="nav-link">
                    <i class="fas fa-th-large"></i> My Ads
                </a>
                <a href="create_ad.php" class="nav-link">
                    <i class="fas fa-plus-circle"></i> Create Ad
                </a>
                <a href="contact_page.php" class="nav-link active">
                    <i class="fas fa-envelope"></i> Contact
                </a>
            </div>
            
            <div class="user-area">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo substr($_SESSION['username'], 0, 1); ?>
                    </div>
                    <span class="username"><?php echo $_SESSION['username']; ?></span>
                </div>
                
                <form method="POST" action="">
                    <button type="submit" name="logout" class="btn btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main>
        <div class="page-header">
            <h1 class="page-title">Message Preview</h1>
            <p class="page-subtitle">View your message and any replies from our support team</p>
        </div>

        <?php if ($message_data): ?>
            <div class="message-preview">
                <div class="message-info">
                    <div class="message-subject"><?php echo htmlspecialchars($message_data['subject']); ?></div>
                    <div class="message-date">
                        <i class="far fa-calendar-alt"></i>
                        Sent on <?php echo date('F j, Y, g:i a', strtotime($message_data['submission_date'])); ?>
                    </div>
                </div>
                
                <!-- Message Section with Header -->
                <div class="message-header">
                    <i class="fas fa-paper-plane"></i> Your Message
                </div>
                <div class="message-content">
                    <?php echo nl2br(htmlspecialchars($message_data['message'])); ?>
                </div>
                
                <!-- Reply Section -->
                <div class="reply-section">
                    <div class="reply-header">
                        <i class="fas fa-reply"></i> Support Team Reply
                    </div>
                    <?php if (!empty($message_data['reply'])): ?>
                        <?php if (!empty($message_data['reply_date'])): ?>
                            <div class="reply-date">
                                <i class="far fa-calendar-alt"></i>
                                Replied on <?php echo date('F j, Y, g:i a', strtotime($message_data['reply_date'])); ?>
                            </div>
                        <?php endif; ?>
                        <div class="reply-content">
                            <?php echo nl2br(htmlspecialchars($message_data['reply'])); ?>
                        </div>
                    <?php else: ?>
                        <div class="no-reply">
                            <i class="fas fa-clock"></i> No reply has been received yet. Our team will get back to you soon.
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="action-buttons">
                    <a href="contact_page.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Messages
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="message-preview">
                <p>Message not found or access denied.</p>
                <div class="action-buttons">
                    <a href="contact_page.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Messages
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Navigation Links -->
        <div class="back-link">
            <a href="loged_main_page.php">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </main>
</body>
</html>