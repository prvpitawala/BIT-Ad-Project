<?php
    session_start(); // Start the session

    // Check if user is logged in by verifying session variables
    if (!isset($_SESSION['username'])) {
        // If not logged in, redirect to the login page
        header("Location: user_login.php");
        exit();
    }

    // Check if the logout button was clicked
    // if (isset($_POST['logout'])) {
    //     // Destroy session and log the user out
    //     session_unset();  // Removes all session variables
    //     session_destroy(); // Destroys the session
    //     header("Location: user_login.php"); // Redirect to login page after logging out
    //     exit();
    // }

    // Database connection
    $conn = new mysqli("localhost", "root", "", "userdb");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Handle message submission
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
        // Get the message details from the form
        $subject = $_POST['subject'];
        $message = $_POST['message'];
        $user_id = $_SESSION['user_id']; // Get the logged-in user's ID
        
        // Prepare SQL statement to insert message
        $sql = "INSERT INTO contact_messages (user_id, subject, message, submission_date) 
                VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $user_id, $subject, $message);

        // Execute the query
        if ($stmt->execute()) {
            echo "<script>alert('Your message has been sent successfully!');</script>";
        } else {
            echo "<script>alert('Error: Could not send message. " . $stmt->error . "');</script>";
        }

        $stmt->close();
    }

    // Fetch past messages for the current user
    $user_id = $_SESSION['user_id'];
    $past_messages = [];
    
    // Modified query to exclude the status column
    $sql = "SELECT id, subject, message, submission_date, reply FROM contact_messages 
        WHERE user_id = ? ORDER BY submission_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $past_messages[] = $row;
    }
    
    $stmt->close();
    $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
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

        .banner-buttons {
            display: flex;
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

        /* Contact info section */
        .contact-info {
            background-color: white;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .contact-info h3 {
            color: rgb(40, 137, 167);
            margin-top: 0;
            margin-bottom: 15px;
        }

        .contact-info p {
            margin: 10px 0;
            line-height: 1.5;
        }

        .contact-info .contact-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .contact-info .label {
            font-weight: bold;
            min-width: 100px;
        }

        /* Form elements */
        .contact-form {
            background-color: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        input, 
        textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            outline: none;
            transition: border-color 0.3s ease-in-out;
            box-sizing: border-box;
        }

        input:focus, 
        textarea:focus {
            border-color: rgb(40, 137, 167);
        }

        textarea {
            resize: vertical;
            height: 150px;
        }

        /* Button styles */
        button {
            padding: 10px 25px;
            background: white;
            color: rgb(40, 137, 167);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #f0f0f0;
        }

        button[type="submit"] {
            display: block;
            width: 100%;
            background: rgb(40, 137, 167);
            color: white;
            padding: 12px;
            font-size: 18px;
            border-radius: 5px;
            transition: background 0.3s ease-in-out;
            margin-top: 20px;
        }

        button[type="submit"]:hover {
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

        /* Past messages section */
        .past-messages {
            background-color: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .past-messages h3 {
            color: rgb(40, 137, 167);
            margin-top: 0;
            margin-bottom: 15px;
        }

        .message-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }

        .message-item:last-child {
            border-bottom: none;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .message-subject {
            font-weight: bold;
            color: #333;
        }

        .message-date {
            color: #777;
            font-size: 14px;
        }

        .message-content {
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .message-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .no-messages {
            text-align: center;
            color: #777;
            padding: 20px 0;
        }

        /* Tabs for switching between contact form and past messages */
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            background-color: #f1f1f1;
            border-radius: 5px 5px 0 0;
            margin-right: 5px;
        }

        .tab.active {
            background-color: rgb(40, 137, 167);
            color: white;
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
    <script>
        function showTab(tabName) {
            // Hide all tab content
            const tabs = document.getElementsByClassName('tab-content');
            for (let i = 0; i < tabs.length; i++) {
                tabs[i].style.display = 'none';
            }
            
            // Remove active class from all tabs
            const tabButtons = document.getElementsByClassName('tab');
            for (let i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove('active');
            }
            
            // Show the selected tab content and mark the button as active
            document.getElementById(tabName).style.display = 'block';
            document.getElementById('tab-' + tabName).classList.add('active');
        }
        
        // Initialize the page to show the contact form by default
        window.onload = function() {
            showTab('contact-form');
        };
    </script>
</head>
<body>

    <div class="banner">
        <div>Contact us</div>
        <div class="banner-controls">
            <div class="banner-buttons">
                <!-- Log Out Button Form -->
                <!-- <form method="POST" action="">
                    <button type="submit" name="logout">Log Out</button>
                </form> -->
            </div>
        </div>
    </div>

    <div class="main-content">
        <h2>Contact Us</h2>
        
        <!-- Contact Information Section -->
        <div class="contact-info">
            <h3>Our Contact Information</h3>
            
            <div class="contact-item">
                <div class="label">Address:</div>
                <div>123 Ad Avenue, Marketing District, City, 12345</div>
            </div>
            
            <div class="contact-item">
                <div class="label">Phone:</div>
                <div>+1 (555) 123-4567</div>
            </div>
            
            <div class="contact-item">
                <div class="label">Email:</div>
                <div>support@adwebsite.com</div>
            </div>
            
            <div class="contact-item">
                <div class="label">Hours:</div>
                <div>Monday - Friday: 9:00 AM - 5:00 PM</div>
            </div>
        </div>
        
        <!-- Tabs for switching between contact form and past messages -->
        <div class="tabs">
            <div id="tab-contact-form" class="tab active" onclick="showTab('contact-form')">Send Message</div>
            <div id="tab-past-messages" class="tab" onclick="showTab('past-messages')">View Past Messages</div>
        </div>
        
        <!-- Contact Form Section -->
        <div id="contact-form" class="tab-content contact-form">
            <h3>Send Us a Message</h3>
            <form method="POST">
                <!-- Subject -->
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" placeholder="Enter message subject" required>
                </div>
                
                <!-- Message -->
                <div class="form-group">
                    <label for="message">Your Message</label>
                    <textarea id="message" name="message" placeholder="Type your message here..." required></textarea>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" name="send_message">Send Message</button>
            </form>
        </div>
        
        
        <!-- Past Messages Section -->
        <!-- Past Messages Section -->
        <div id="past-messages" class="tab-content past-messages" style="display: none;">
            <h3>Your Past Messages</h3>
            
            <?php if (count($past_messages) > 0): ?>
                <?php foreach ($past_messages as $msg): ?>
                    <div class="message-item" onclick="window.location.href='message_preview.php?id=<?php echo $msg['id']; ?>'" style="cursor: pointer;">
                        <div class="message-header">
                            <div class="message-subject"><?php echo htmlspecialchars($msg['subject']); ?></div>
                            <div class="message-date"><?php echo date('F j, Y, g:i a', strtotime($msg['submission_date'])); ?></div>
                        </div>
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars(substr($msg['message'], 0, 100))); ?>
                            <?php echo (strlen($msg['message']) > 100) ? '...' : ''; ?>
                        </div>
                        <!-- <div class="message-status" style="color: #888; background-color: #f0f0f0; padding: 3px 8px; border-radius: 3px; font-size: 12px; margin-top: 5px; display: inline-block;">
                            Pending
                        </div> -->
                        <div class="message-status" style="color: <?php echo ($msg['reply'] ? '#4CAF50' : '#888'); ?>; 
                            background-color: <?php echo ($msg['reply'] ? '#E8F5E9' : '#f0f0f0'); ?>; 
                            padding: 3px 8px; border-radius: 3px; font-size: 12px; margin-top: 5px; display: inline-block;">
                            <?php echo ($msg['reply'] ? 'Replied' : 'Pending'); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-messages">You haven't sent any messages yet.</div>
            <?php endif; ?>
        </div>
        
        <!-- Navigation Links -->
        <div class="nav-links">
            <a href="loged_main_page.php">Back to Home</a>
        </div>
    </div>

</body>
</html>