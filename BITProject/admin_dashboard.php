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
            echo "<script>alert('category deleted successfully!'); window.location.href = 'admin_dashboard.php?tab=categories';</script>";
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
    <title>Admin Dashboard</title>
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
            max-width: 1200px;
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
        }

        .tab.active {
            background-color: rgb(40, 137, 167);
            color: white;
        }

        /* Search form */
        .search-form {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search-form input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            flex-grow: 1;
        }

        .search-form button {
            padding: 8px 15px;
            background-color: rgb(40, 137, 167);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .search-form .clear-search {
            padding: 8px 15px;
            background-color: #888;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }

        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .data-table th, .data-table td {
            text-align: left;
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
        }

        .data-table th {
            background-color: rgb(40, 137, 167);
            color: white;
            font-weight: bold;
        }

        .data-table tr:hover {
            background-color: #f9f9f9;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        /* Action buttons */
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin-right: 5px;
        }

        .view-btn {
            background-color: #4CAF50;
            color: white;
        }

        .reply-btn {
            background-color: rgb(40, 137, 167);
            color: white;
        }

        .delete-btn {
            background-color: #f44336;
            color: white;
        }

        .add-btn {
            background-color: #4CAF50;
            color: white;
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
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 60%;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        /* Form elements */
        textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            outline: none;
            height: 150px;
            resize: vertical;
            margin-bottom: 20px;
            box-sizing: border-box;
        }

        textarea:focus {
            border-color: rgb(40, 137, 167);
        }

        button[type="submit"] {
            display: block;
            width: 100%;
            background: rgb(40, 137, 167);
            color: white;
            padding: 8px;
            font-size: 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease-in-out;
            
        }

        .search {
            margin-right: 800px;
        }

        button[type="submit"]:hover {
            background: rgb(30, 117, 147);
        }

        /* Status indicators */
        .status-indicator {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
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
            margin-bottom: 20px;
        }

        .message-details p {
            margin: 5px 0;
        }

        .message-text {
            background-color: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            white-space: pre-wrap;
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

        /* Responsive styles */
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }

            .data-table {
                display: block;
                overflow-x: auto;
            }

            .modal-content {
                width: 90%;
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
            document.getElementById('message_id').value = messageId; // Fixed ID here
            document.getElementById('message-subject').textContent = subject;
            document.getElementById('original-message').textContent = messageText;

            // console.log("praveen");
            // document.getElementById('reply').value= 'praveen';
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
                    console.log("done");
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
    <div class="banner">
        <div>Admin Dashboard</div>
        <div class="banner-controls">
            <div class="banner-buttons">
                <!-- Log Out Button Form -->
                <form method="POST" action="">
                    <button type="submit" name="logout">Log Out</button>
                </form>
            </div>
        </div>
    </div>

    <div class="main-content">
        <h2>Admin Dashboard</h2>
        
        <!-- Tabs for switching between users, ads, and messages -->
        <div class="tabs">
            <div id="tab-users" class="tab" onclick="showTab('users')">Users</div>
            <div id="tab-ads" class="tab" onclick="showTab('ads')">Advertisements</div>
            <div id="tab-messages" class="tab" onclick="showTab('messages')">Messages</div>
            <div id="tab-categories" class="tab" onclick ="showTab('categories')">Ad Categories</div>
        </div>
        
        <!-- Users Tab Content -->
        <div id="users" class="tab-content">
            <!-- User Search Form -->
            <form class="search-form" method="GET" action="">
                <input type="hidden" name="tab" value="users">
                <input type="number" name="user_search_id" placeholder="Search by ID" value="<?php echo $user_search_id ?? ''; ?>" min="1">
                <button class="search" type="submit">Search</button>
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
                                    <a href="#" class="action-btn delete-btn" onclick="confirmDelete('user', <?php echo $user['id']; ?>)">Delete</a>
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
                                    <a href="ad_preview_by_admin.php?id=<?php echo $ad['id']; ?>" class="action-btn view-btn">View</a>
                                    <a href="#" class="action-btn delete-btn" onclick="confirmDelete('ad', <?php echo $ad['id']; ?>)">Delete</a>
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
                                        <?php echo ($msg['reply'] ? 'Edit' : 'Reply'); ?>
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

        <!-- categories Tab Content -->
        <div id="categories" class="tab-content" style="display: none;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ad Category</th>
                        <th>Action
                            <div>
                            <a href="#" class="action-btn add-btn" onclick="openAdCategoryModal()">add +</a>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($categories) > 0): ?>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td><?php echo htmlspecialchars($category['category']); ?></td>
                                <td>
                                    <a href="#" class="action-btn delete-btn" onclick="confirmDelete('category', <?php echo $category['id']; ?>)">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No categories found.</td>
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

    <!-- Add category modal -->
    <div id="add-category-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAdCategoryModal()">&times;</span>
            <div class="message-details">
                <h3>Add New Category</h3>
            </div>
            <form method="POST" action="">
                <div style="margin-bottom: 20px;">
                    <label for="new_category"><strong>Category Name:</strong></label>
                    <input type="text" id="new_category" name="new_category" required 
                        placeholder="Enter new category name"
                        style="width: 98%; padding: 10px; border: 1px solid #ccc; 
                                border-radius: 5px; font-size: 16px; margin-top: 10px;">
                </div>
                <button type="submit" name="add_category_submit">Add Category</button>
            </form>
        </div>
    </div>
</body>
</html>