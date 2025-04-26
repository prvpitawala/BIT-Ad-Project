<?php
    session_start(); // Start the session

    // Check if user is logged in and is an admin
    if (!isset($_SESSION['username']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
        // If not admin, redirect to the login page
        header("Location: user_login.php");
        exit();
    }

    // Check if the logout button was clicked
    if (isset($_POST['logout'])) {
        // Destroy session and log the user out
        session_unset();  
        session_destroy();
        header("Location: user_login.php");
        exit();
    }

    // Database connection
    $conn = new mysqli("localhost", "root", "", "userdb");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Function to fetch all users or search by ID
    function getAllUsers($conn, $search_id = null) {
        $users = [];
        
        if ($search_id) {
            $sql = "SELECT id, username, email FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $search_id);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $sql = "SELECT id, username, email FROM users";
            $result = $conn->query($sql);
        }
        
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        return $users;
    }

    // Function to fetch all ads or search by ID
    function getAllAds($conn, $search_id = null) {
        $ads = [];
        
        if ($search_id) {
            $sql = "SELECT a.*, u.username FROM ads a 
                    JOIN users u ON a.user_id = u.id 
                    WHERE a.id = ?
                    ORDER BY a.created_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $search_id);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $sql = "SELECT a.*, u.username FROM ads a 
                    JOIN users u ON a.user_id = u.id 
                    ORDER BY a.created_at DESC";
            $result = $conn->query($sql);
        }
        
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $ads[] = $row;
            }
        }
        return $ads;
    }

    // Function to fetch all messages
    function getAllMessages($conn) {
        $messages = [];
        $sql = "SELECT m.*, u.username FROM contact_messages m 
                JOIN users u ON m.user_id = u.id 
                ORDER BY m.submission_date DESC";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $messages[] = $row;
            }
        }
        return $messages;
    }

    // function to fetch all categories
    function getAllCategories($conn){
        $categories =[];
        $sql ="SELECT id, category FROM categories ORDER BY id ASC";
        $result = $conn->query($sql);

        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $categories[] = $row;
            }
        }
        return $categories;
    }

    // Handle message reply submission
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply_submit'])) {
        $message_id = $_POST['message_id'];
        $reply = $_POST['reply'];
        
        $sql = "UPDATE contact_messages SET reply = ?, reply_date = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $reply, $message_id);
        
        if ($stmt->execute()) {
            echo "<script>alert('Reply sent successfully!');</script>";
        } else {
            echo "<script>alert('Error sending reply: " . $stmt->error . "');</script>";
        }
        
        $stmt->close();
    }


    // Handle user deletion
    if (isset($_GET['delete_user'])) {
        $user_id = $_GET['delete_user'];
        
        // Start a transaction
        $conn->begin_transaction();
        
        try {
            // First delete all contact messages for this user
            $sql = "DELETE FROM contact_messages WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            
            // Then delete the user
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            
            // If we got here, both operations were successful
            $conn->commit();
            echo "<script>alert('User deleted successfully!'); window.location.href = 'admin_dashboard.php';</script>";
        } catch (Exception $e) {
            // Something went wrong, rollback the transaction
            $conn->rollback();
            echo "<script>alert('Error deleting user: " . $e->getMessage() . "');</script>";
        }
    }

    // Handle ad deletion
    if (isset($_GET['delete_ad'])) {
        $ad_id = $_GET['delete_ad'];
        
        $sql = "DELETE FROM ads WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $ad_id);
        
        if ($stmt->execute()) {
            echo "<script>alert('Ad deleted successfully!'); window.location.href = 'admin_dashboard.php?tab=ads';</script>";
        } else {
            echo "<script>alert('Error deleting ad: " . $stmt->error . "');</script>";
        }
        
        $stmt->close();
    }

    // Handle category deletion
    if (isset($_GET['delete_category'])){
        $category_id = $_GET['delete_category'];
        echo($category_id);

        $sql = "DELETE FROM categories WHERE id =?";
        $stmt = $conn->prepare($sql);
        $stmt ->bind_param("i", $category_id);

        if ($stmt->execute()) {
            echo "<script>alert('Category deleted successfully!'); window.location.href = 'admin_dashboard.php?tab=categories';</script>";
        } else {
            echo "<script>alert('Error deleting category: " . $stmt->error . "');</script>";
        }
        
        $stmt->close();
    }

    // Handle new category submission
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category_submit'])) {
        $new_category = trim($_POST['new_category']);
        
        // Validate the input
        if (empty($new_category)) {
            echo "<script>alert('Category name cannot be empty!');</script>";
        } else {
            // Database connection
            $conn = new mysqli("localhost", "root", "", "userdb");
            
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            
            // Insert the new category
            $sql = "INSERT INTO categories (category) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $new_category);
            
            if ($stmt->execute()) {
                echo "<script>alert('Category added successfully!'); window.location.href = 'admin_dashboard.php?tab=categories';</script>";
            } else {
                // Check for duplicate entry
                if ($conn->errno == 1062) {
                    echo "<script>alert('Error: Category already exists!');</script>";
                } else {
                    echo "<script>alert('Error adding category: " . $stmt->error . "');</script>";
                }
            }
            
            $stmt->close();
            $conn->close();
        }
    }

    // Handle search
    $user_search_id = isset($_GET['user_search_id']) ? $_GET['user_search_id'] : null;
    $ad_search_id = isset($_GET['ad_search_id']) ? $_GET['ad_search_id'] : null;
    
    // Clear search
    if (isset($_GET['clear_user_search'])) {
        header("Location: admin_dashboard.php?tab=users");
        exit();
    }
    
    if (isset($_GET['clear_ad_search'])) {
        header("Location: admin_dashboard.php?tab=ads");
        exit();
    }

    // Get all data
    $users = getAllUsers($conn, $user_search_id);
    $ads = getAllAds($conn, $ad_search_id);
    $messages = getAllMessages($conn);
    $categories = getAllCategories($conn);
    
    $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MarketPlace Hub</title>
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
        
        /* Main Content */
        .main-content {
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

        /* Tabs */
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
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .tab.active {
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        .tab:hover {
            background-color: var(--secondary-color);
            color: var(--white);
        }
        
        /* Search form */
        .search-form {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .search-form input {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 50px;
            flex-grow: 1;
            outline: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        .search-form input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        /* Button styles */
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
        
        button[type="submit"] {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        button[type="submit"]:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .search {
            border-radius: 50px;
            padding: 12px 25px;
        }
        
        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .data-table th, .data-table td {
            text-align: left;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .data-table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .data-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        /* Action buttons */
        .action-btn {
            padding: 8px 15px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 5px;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }
        
        .action-btn i {
            margin-right: 5px;
        }
        
        .view-btn {
            background-color: #4CAF50;
            color: white;
        }
        
        .view-btn:hover {
            background-color: #3d8b40;
            transform: translateY(-2px);
        }
        
        .reply-btn {
            background-color: var(--primary-color);
            color: white;
        }
        
        .reply-btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .delete-btn {
            background-color: var(--accent-color);
            color: white;
        }
        
        .delete-btn:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }
        
        .add-btn {
            background-color: #4CAF50;
            color: white;
        }
        
        .add-btn:hover {
            background-color: #3d8b40;
            transform: translateY(-2px);
        }
        
        /* Reply modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            animation: fadeIn 0.3s ease;
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 25px;
            border: 1px solid #888;
            width: 60%;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            animation: slideDown 0.4s ease;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .close:hover {
            color: var(--accent-color);
        }
        
        /* Form elements */
        textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #eee;
            border-radius: 10px;
            font-size: 16px;
            outline: none;
            height: 150px;
            resize: vertical;
            margin-bottom: 20px;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        
        textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.1);
        }
        
        input[type="text"] {
            width: 100%;
            padding: 15px;
            border: 2px solid #eee;
            border-radius: 10px;
            font-size: 16px;
            outline: none;
            margin-bottom: 20px;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        
        input[type="text"]:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.1);
        }
        
        button[type="submit"] {
            display: block;
            width: 100%;
            background: var(--primary-color);
            color: white;
            padding: 12px;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        button[type="submit"]:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        /* Status indicators */
        .status-indicator {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-replied {
            color: #4CAF50;
            background-color: #E8F5E9;
        }
        
        .status-pending {
            color: #888;
            background-color: #f0f0f0;
        }
        
        /* Message details */
        .message-details {
            margin-bottom: 25px;
        }
        
        .message-details h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 1.4rem;
        }
        
        .message-details p {
            margin: 10px 0;
            font-size: 1rem;
        }
        
        .message-text {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 10px;
            margin-top: 10px;
            white-space: pre-wrap;
            font-size: 0.95rem;
            line-height: 1.6;
            color: #555;
            border-left: 4px solid var(--primary-color);
        }
        
        .no-reply {
            color: #777;
            font-style: italic;
            padding: 15px;
            background-color: #f8f8f8;
            border-radius: 8px;
            text-align: center;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .header-container, .main-content {
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
            
            .main-content {
                padding: 0 1rem;
                margin: 1rem auto;
            }
            
            .modal-content {
                width: 90%;
                margin: 20% auto;
            }
            
            .data-table {
                display: block;
                overflow-x: auto;
            }
            
            .tabs {
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 5px;
            }
            
            .tab {
                padding: 8px 15px;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 576px) {
            .action-btn {
                padding: 6px 12px;
                font-size: 0.8rem;
                margin-bottom: 5px;
                display: block;
            }
            
            .search-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-form button {
                width: 100%;
            }
        }
    </style>
    <script>
        // Show the selected tab
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
            
            // Update URL with the tab parameter
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('tab', tabName);
            window.history.replaceState({}, '', `?${urlParams.toString()}`);
        }
        
        // Open the reply modal
        function openReplyModal(messageId, subject, messageText) {
            document.getElementById('message_id').value = messageId;
            document.getElementById('message-subject').textContent = subject;
            document.getElementById('original-message').textContent = messageText;
            document.getElementById('reply-modal').style.display = 'block';
            
            // Load existing reply if available
            const messages = <?php echo json_encode($messages); ?>;
            const message = messages.find(m => m.id === messageId);
            if (message && message.reply) {
                document.getElementById('reply').value = message.reply;
            } else {
                document.getElementById('reply').value = '';
            }
        }
        
        // Close the reply modal
        function closeReplyModal() {
            document.getElementById('reply-modal').style.display = 'none';
        }

        // Open the add category modal
        function openAdCategoryModal() {
            document.getElementById('add-category-modal').style.display = 'block'; 
        }
        
        // Close the add category modal
        function closeAdCategoryModal() {
            document.getElementById('add-category-modal').style.display = 'none';
        }

        // Confirm before deleting
        function confirmDelete(type, id) {
            if (confirm(`Are you sure you want to delete this ${type}?`)) {
                if (type === 'user') {
                    window.location.href = `admin_dashboard.php?delete_user=${id}`;
                } else if (type === 'ad') {
                    window.location.href = `admin_dashboard.php?tab=ads&delete_ad=${id}`;
                } else if (type === 'category'){
                    window.location.href = `admin_dashboard.php?tab=categories&delete_category=${id}`;
                }
            }
        }
        
        // Initialize the page to show the correct tab based on URL parameter
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            
            if (tabParam && ['users', 'ads', 'messages','categories'].includes(tabParam)) {
                showTab(tabParam);
            } else {
                showTab('users');
            }
        });
    </script>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="main_page.php" class="logo">
                <i class="fas fa-store"></i> AdDrop (Admin)
            </a>
            
            <div class="nav-links">
                <a href="main_page.php" class="nav-link">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="admin_dashboard.php" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
            
            <div class="user-area">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo substr($_SESSION['username'], 0, 1); ?>
                    </div>
                    <span class="username"><?php echo $_SESSION['username']; ?> (Admin)</span>
                </div>
                
                <form method="POST" action="">
                    <button type="submit" name="logout" class="btn btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </header>

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Admin Dashboard</h1>
            <p class="page-subtitle">Manage users, advertisements, messages and categories</p>
        </div>
        
        <!-- Tabs for switching between users, ads, and messages -->
        <div class="tabs">
            <div id="tab-users" class="tab" onclick="showTab('users')">
                <i class="fas fa-users"></i> Users
            </div>
            <div id="tab-ads" class="tab" onclick="showTab('ads')">
                <i class="fas fa-ad-bullhorn"></i> Advertisements
            </div>
            <div id="tab-messages" class="tab" onclick="showTab('messages')">
                <i class="fas fa-envelope"></i> Messages
            </div>
            <div id="tab-categories" class="tab" onclick="showTab('categories')">
                <i class="fas fa-tags"></i> Ad Categories
            </div>
        </div>
        
        <!-- Users Tab Content -->
        <div id="users" class="tab-content">
            <!-- User Search Form -->
            <form class="search-form" method="GET" action="">
                <input type="hidden" name="tab" value="users">
                <input type="number" name="user_search_id" placeholder="Search by ID" value="<?php echo $user_search_id ?? ''; ?>" min="1">
                <button class="search" type="submit">Search</button>
                <?php if ($user_search_id): ?>
                    <a href="admin_dashboard.php?tab=users&clear_user_search=1" class="btn btn-primary">Clear Search</a>
                <?php endif; ?>
            </form>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <a href="#" class="action-btn delete-btn" onclick="confirmDelete('user', <?php echo $user['id']; ?>)">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Ads Tab Content -->
        <div id="ads" class="tab-content" style="display: none;">
            <!-- Ad Search Form -->
            <form class="search-form" method="GET" action="">
                <input type="hidden" name="tab" value="ads">
                <input type="number" name="ad_search_id" placeholder="Search by ID" value="<?php echo $ad_search_id ?? ''; ?>" min="1">
                <button class="search" type="submit">Search</button>
                <?php if ($ad_search_id): ?>
                    <a href="admin_dashboard.php?tab=ads&clear_ad_search=1" class="btn btn-primary">Clear Search</a>
                <?php endif; ?>
            </form>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Advertiser</th>
                        <th>Posted By</th>
                        <th>Expiry Date</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($ads) > 0): ?>
                        <?php foreach ($ads as $ad): ?>
                            <tr>
                                <td><?php echo $ad['id']; ?></td>
                                <td><?php echo htmlspecialchars($ad['title']); ?></td>
                                <td><?php echo htmlspecialchars($ad['category']); ?></td>
                                <td><?php echo htmlspecialchars($ad['advertiser']); ?></td>
                                <td><?php echo htmlspecialchars($ad['username']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($ad['expiry_date'])); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($ad['created_at'])); ?></td>
                                <td>
                                    <a href="ad_preview_by_admin.php?id=<?php echo $ad['id']; ?>" class="action-btn view-btn">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="#" class="action-btn delete-btn" onclick="confirmDelete('ad', <?php echo $ad['id']; ?>)">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">No advertisements found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Messages Tab Content -->
        <div id="messages" class="tab-content" style="display: none;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>From</th>
                        <th>Subject</th>
                        <th>Date Submitted</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($messages) > 0): ?>
                        <?php foreach ($messages as $msg): ?>
                            <tr>
                                <td><?php echo $msg['id']; ?></td>
                                <td><?php echo htmlspecialchars($msg['username']); ?></td>
                                <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($msg['submission_date'])); ?></td>
                                <td>
                                    <div class="status-indicator <?php echo ($msg['reply'] ? 'status-replied' : 'status-pending'); ?>">
                                        <?php echo ($msg['reply'] ? 'Replied' : 'Pending'); ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="#" class="action-btn reply-btn" onclick="openReplyModal(<?php echo $msg['id']; ?>, '<?php echo addslashes(htmlspecialchars($msg['subject'])); ?>', '<?php echo addslashes(htmlspecialchars($msg['message'])); ?>')">
                                        <i class="fas fa-reply"></i> <?php echo ($msg['reply'] ? 'Edit' : 'Reply'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No messages found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Categories Tab Content -->
        <div id="categories" class="tab-content" style="display: none;">
            <div style="margin-bottom: 20px; text-align: right;">
                <a href="#" class="btn btn-primary add-btn" onclick="openAdCategoryModal()">
                    <i class="fas fa-plus"></i> Add New Category
                </a>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ad Category</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($categories) > 0): ?>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td><?php echo htmlspecialchars($category['category']); ?></td>
                                <td>
                                    <a href="#" class="action-btn delete-btn" onclick="confirmDelete('category', <?php echo $category['id']; ?>)">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center;">No categories found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Reply Modal -->
    <div id="reply-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeReplyModal()">&times;</span>
            <div class="message-details">
                <h3>Reply to Message</h3>
                <p><strong>Subject:</strong> <span id="message-subject"></span></p>
                <p><strong>User Message:</strong></p>
                <div class="message-text" id="original-message"></div>
            </div>
            <form method="POST" action="">
                <input type="hidden" id="message_id" name="message_id">
                <div>
                    <label for="reply"><strong>Your Reply:</strong></label>
                    <textarea id="reply" name="reply" required></textarea>
                </div>
                <button type="submit" name="reply_submit">Send Reply</button>
            </form>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div id="add-category-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAdCategoryModal()">&times;</span>
            <div class="message-details">
                <h3>Add New Category</h3>
            </div>
            <form method="POST" action="">
                <div>
                    <label for="new_category"><strong>Category Name:</strong></label>
                    <input type="text" id="new_category" name="new_category" required placeholder="Enter new category name">
                </div>
                <button type="submit" name="add_category_submit">Add Category</button>
            </form>
        </div>
    </div>
</body>
</html>