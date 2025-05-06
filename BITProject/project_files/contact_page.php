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
    <title>Contact Us - MarketPlace Hub</title>
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
            max-width: 1200px;
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
        
        /* Contact Info Section */
        .contact-info {
            background-color: var(--white);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
        }
        
        .contact-info h3 {
            color: var(--primary-color);
            font-size: 1.2rem;
            margin-bottom: 1.2rem;
            font-weight: 600;
        }
        
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .contact-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .contact-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        
        .contact-label {
            font-weight: 600;
            color: var(--dark-gray);
        }
        
        .contact-value {
            color: #666;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            margin-bottom: 1rem;
            background-color: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .tab {
            flex: 1;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .tab i {
            font-size: 1.1rem;
        }
        
        .tab.active {
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        .tab:not(.active):hover {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
        }
        
        /* Tab Content */
        .tab-content {
            background-color: var(--white);
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .content-title {
            color: var(--primary-color);
            font-size: 1.2rem;
            margin-bottom: 1.2rem;
            font-weight: 600;
        }
        
        /* Form Elements */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-gray);
        }
        
        input, textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        input:focus, textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
            outline: none;
        }
        
        textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        /* Past Messages */
        .message-item {
            padding: 1.2rem;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .message-item:last-child {
            border-bottom: none;
        }
        
        .message-item:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.8rem;
        }
        
        .message-subject {
            font-weight: 600;
            color: var(--dark-gray);
            font-size: 1.1rem;
        }
        
        .message-date {
            color: #888;
            font-size: 0.9rem;
        }
        
        .message-content {
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .message-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
        }
        
        .status-pending {
            background-color: #f1f1f1;
            color: #777;
        }
        
        .status-replied {
            background-color: #e3f2fd;
            color: #2196F3;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            color: var(--dark-gray);
            margin-bottom: 1rem;
        }
        
        .empty-state p {
            color: #888;
            margin-bottom: 1.5rem;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .contact-info, .tab-content {
            animation: fadeIn 0.5s ease forwards;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .header-container, main {
                padding: 0 1.5rem;
            }
        }
        
        @media (max-width: 992px) {
            .contact-grid {
                grid-template-columns: repeat(2, 1fr);
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
            
            .contact-grid {
                grid-template-columns: 1fr;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .tab {
                padding: 0.8rem;
            }
            
            .message-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
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
            <h1 class="page-title">Contact Us</h1>
            <p class="page-subtitle">Get in touch with our support team for any questions or assistance</p>
        </div>

        <!-- Contact Information Section -->
        <div class="contact-info">
            <h3>Our Contact Information</h3>
            
            <div class="contact-grid">
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="contact-label">Address</div>
                    <div class="contact-value">123 Ad Drop, Godalla RD, Colombo, 15</div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <div class="contact-label">Phone</div>
                    <div class="contact-value">+94 11-234567</div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-label">Email</div>
                    <div class="contact-value">addrop@gmail.com</div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="contact-label">Hours</div>
                    <div class="contact-value">Monday - Friday: 9:00 AM - 5:00 PM</div>
                </div>
            </div>
        </div>

        <!-- Tabs for switching between sections -->
        <div class="tabs">
            <div id="tab-contact-form" class="tab active" onclick="showTab('contact-form')">
                <i class="fas fa-paper-plane"></i> Send Message
            </div>
            <div id="tab-past-messages" class="tab" onclick="showTab('past-messages')">
                <i class="fas fa-history"></i> Your Messages
            </div>
        </div>

        <!-- Contact Form Section -->
        <div id="contact-form" class="tab-content">
            <h3 class="content-title">Send Us a Message</h3>
            
            <form method="POST">
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" placeholder="Enter message subject" required>
                </div>
                
                <div class="form-group">
                    <label for="message">Your Message</label>
                    <textarea id="message" name="message" placeholder="Type your message here..." required></textarea>
                </div>
                
                <button type="submit" name="send_message" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>
        
        <!-- Past Messages Section -->
        <div id="past-messages" class="tab-content" style="display: none;">
            <h3 class="content-title">Your Previous Messages</h3>
            
            <?php if (count($past_messages) > 0): ?>
                <?php foreach ($past_messages as $msg): ?>
                    <div class="message-item" onclick="window.location.href='message_preview.php?id=<?php echo $msg['id']; ?>'">
                        <div class="message-header">
                            <div class="message-subject"><?php echo htmlspecialchars($msg['subject']); ?></div>
                            <div class="message-date">
                                <i class="far fa-calendar-alt"></i> 
                                <?php echo date('M d, Y', strtotime($msg['submission_date'])); ?>
                            </div>
                        </div>
                        
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars(substr($msg['message'], 0, 100))); ?>
                            <?php echo (strlen($msg['message']) > 100) ? '...' : ''; ?>
                        </div>
                        
                        <div class="message-status <?php echo $msg['reply'] ? 'status-replied' : 'status-pending'; ?>">
                            <i class="<?php echo $msg['reply'] ? 'fas fa-check-circle' : 'fas fa-clock'; ?>"></i>
                            <?php echo $msg['reply'] ? 'Replied' : 'Pending'; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-envelope-open"></i>
                    <h3>No Messages Found</h3>
                    <p>You haven't sent any messages yet. Use the form to contact us.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function showTab(tabName) {
            // Hide all tab content
            const tabContents = document.getElementsByClassName('tab-content');
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].style.display = 'none';
            }
            
            // Remove active class from all tabs
            const tabs = document.getElementsByClassName('tab');
            for (let i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
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
</body>
</html>