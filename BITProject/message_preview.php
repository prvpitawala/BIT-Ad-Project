<?php
    session_start(); // Start the session

    // Check if user is logged in by verifying session variables
    if (!isset($_SESSION['username'])) {
        // If not logged in, redirect to the login page
        header("Location: user_login.php");
        exit();
    }

    // Check if the message ID is provided
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        // If no valid ID is provided, redirect to the contact page
        header("Location: contact_us.php");
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
        header("Location: contact_us.php");
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
    <title>Message Preview</title>
    <style>
        /* Reset and base styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        /* Banner styles */
        .banner {
            background-color: rgb(40, 137, 167);
            color: white;
            padding: 15px 20px;
            font-size: 24px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .banner-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Content container */
        .main-content {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }

        /* Typography */
        h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: rgb(40, 137, 167);
            text-align: center;
        }

        /* Message preview section */
        .message-preview {
            background-color: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .message-preview h3 {
            color: rgb(40, 137, 167);
            margin-top: 0;
            margin-bottom: 15px;
        }

        .message-info {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .message-subject {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .message-date {
            color: #777;
            font-size: 14px;
            margin-bottom: 15px;
        }

        /* Message and reply shared styles */
        .message-content, .reply-content {
            background-color: #f8f8f8;
            padding: 15px;
            border-radius: 5px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        /* Message section */
        .message-header {
            font-size: 18px;
            font-weight: bold;
            color: rgb(40, 137, 167);
            margin-bottom: 10px;
        }

        /* Reply section */
        .reply-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .reply-header {
            font-size: 18px;
            font-weight: bold;
            color: rgb(40, 137, 167);
            margin-bottom: 10px;
        }

        .reply-date {
            color: #777;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .no-reply {
            color: #777;
            font-style: italic;
        }

        /* Button styles */
        button {
            padding: 10px 25px;
            background: rgb(40, 137, 167);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        button:hover {
            background: rgb(30, 117, 147);
        }

        /* Navigation links */
        .nav-links {
            text-align: center;
            margin-top: 20px;
        }

        .nav-links a {
            color: rgb(40, 137, 167);
            text-decoration: none;
            margin: 0 10px;
        }

        .nav-links a:hover {
            text-decoration: underline;
        }

        /* Responsive styles */
        @media (max-width: 680px) {
            .main-content {
                padding: 15px;
            }

            .banner {
                font-size: 18px;
            }
            
            button {
                padding: 8px 15px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

    <div class="banner">
        <div>Message</div>
        <div class="banner-controls">
            <form method="POST" action="contact_us.php">
                <button type="submit" name="logout">Log Out</button>
            </form>
        </div>
    </div>

    <div class="main-content">
        <h2>Message Preview</h2>
        
        <?php if ($message_data): ?>
            <div class="message-preview">
                <div class="message-info">
                    <div class="message-subject"><?php echo htmlspecialchars($message_data['subject']); ?></div>
                    <div class="message-date">Sent on <?php echo date('F j, Y, g:i a', strtotime($message_data['submission_date'])); ?></div>
                </div>
                
                <!-- Message Section with Header -->
                <div class="message-header">Your Message</div>
                <div class="message-content">
                    <?php echo nl2br(htmlspecialchars($message_data['message'])); ?>
                </div>
                
                <!-- Reply Section -->
                <div class="reply-section">
                    <div class="reply-header">Reply</div>
                    <?php if (!empty($message_data['reply'])): ?>
                        <?php if (!empty($message_data['reply_date'])): ?>
                            <div class="reply-date">Replied on <?php echo date('F j, Y, g:i a', strtotime($message_data['reply_date'])); ?></div>
                        <?php endif; ?>
                        <div class="reply-content">
                            <?php echo nl2br(htmlspecialchars($message_data['reply'])); ?>
                        </div>
                    <?php else: ?>
                        <div class="no-reply">No reply has been received yet.</div>
                    <?php endif; ?>
                </div>
                
                <button onclick="window.location.href='contact_page.php'">Back to Messages</button>
            </div>
        <?php else: ?>
            <div class="message-preview">
                <p>Message not found or access denied.</p>
                <button onclick="window.location.href='contact_us.php'">Back to Messages</button>
            </div>
        <?php endif; ?>
        
        <!-- Navigation Links -->
        <div class="nav-links">
            <a href="contact_page.php">Back to Contact Page</a>
            <a href="loged_main_page.php">Back to Home</a>
        </div>
    </div>

</body>
</html>