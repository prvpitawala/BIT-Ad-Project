<?php
    session_start(); // Start the session

    // Check if user is logged in by verifying session variables
    if (!isset($_SESSION['username'])) {
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

    // Database connection for fetching categories
    $conn = new mysqli("localhost", "root", "", "userdb");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get all available categories from the categories table
    $categoriesQuery = "SELECT id, category FROM categories ORDER BY category";
    $categoriesResult = $conn->query($categoriesQuery);
    $categories = [];
    if ($categoriesResult->num_rows > 0) {
        while ($row = $categoriesResult->fetch_assoc()) {
            $categories[] = $row;
        }
    }

    // Check if ad ID is provided
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        echo "Invalid advertisement ID";
        exit();
    }

    // get ad id
    $ad_id = $_GET['id'];
    
    // Database connection
    $conn = new mysqli("localhost", "root", "", "userdb");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Prepare and execute query to get ad details
    $sql = "SELECT a.*, u.username FROM ads a 
            JOIN users u ON a.user_id = u.id 
            WHERE a.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ad_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if ad exists
    if ($result->num_rows === 0) {
        echo "Advertisement not found";
        $conn->close();
        exit();
    }

    // Fetch ad details
    $ad = $result->fetch_assoc();
    
    // Verify that the current user owns this ad
    if ($ad['user_id'] != $_SESSION['user_id']) {
        echo "You don't have permission to edit this advertisement";
        $conn->close();
        exit();
    }

    // Handle form submission for updating ad
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_ad'])) {
        // Get the ad details from the form
        $title = $_POST['title'];
        $category = $_POST['category'];
        $advertiser = $_POST['advertiser'];
        $contact = $_POST['contact'];
        $expiry_date = $_POST['expiry_date'];
        $description = $_POST['description'];
        
        // Process tags - convert to comma-separated string
        $tags = '';
        if(isset($_POST['tags']) && !empty($_POST['tags'])) {
            // Remove any extra spaces and convert to lowercase for consistency
            $tagsArray = array_map('trim', explode(',', strtolower($_POST['tags'])));
            // Filter out empty tags
            $tagsArray = array_filter($tagsArray);
            // Convert back to comma-separated string
            $tags = implode(',', $tagsArray);
        }
        
        // Handle image upload if a new image is provided
        $imageSQL = "";
        $imageParam = "";
        $types = "ssssss";
        $params = array($title, $category, $advertiser, $contact, $expiry_date, $description);
        
        if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            // Read the image file content
            $image = file_get_contents($_FILES['image']['tmp_name']);
            $imageSQL = ", image = ?";
            $imageParam = $image;
            $types .= "s";
            $params[] = $imageParam;
        }
        
        // Add tags and ad_id to params
        $types .= "si";
        $params[] = $tags;
        $params[] = $ad_id;
        
        // Prepare SQL statement to update ad
        $sql = "UPDATE ads SET 
                title = ?, 
                category = ?, 
                advertiser = ?, 
                contact = ?, 
                expiry_date = ?, 
                description = ?
                $imageSQL, 
                tags = ?
                WHERE id = ?";
                
        $stmt = $conn->prepare($sql);
        
        // Dynamically bind parameters
        $stmt->bind_param($types, ...$params);

        // Execute the query
        if ($stmt->execute()) {
            echo "<script>
            alert('Advertisement updated successfully!');
            window.location.href='loged_main_page.php';
            </script>";
        } else {
            echo "<script>alert('Error: Could not update ad. " . $stmt->error . "');</script>";
        }

        // Close statement
        $stmt->close();
    }
    
    // Close connection
    $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Advertisement - MarketPlace Hub</title>
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
            --success-color: #27ae60;
            --warning-color: #e74c3c;
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
        
        /* Main Content Area */
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
        
        /* Form Container */
        .form-container {
            background-color: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .form-header {
            padding: 1.5rem 2rem;
            background: linear-gradient(to right, rgba(52, 152, 219, 0.05), rgba(52, 152, 219, 0.15));
            border-bottom: 1px solid rgba(52, 152, 219, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .form-title {
            font-size: 1.8rem;
            color: var(--dark-gray);
            font-weight: 700;
            margin: 0;
            flex-grow: 1;
        }
        
        .form-content {
            padding: 1.5rem;
        }
        
        /* Form Elements */
        .form-section {
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            font-size: 1.2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
            opacity: 0.7;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group:last-child {
            margin-bottom: 0;
        }
        
        .form-grid .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-gray);
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 150px;
        }
        
        .form-group input[type="file"] {
            padding: 0.8rem 1rem;
            cursor: pointer;
        }
        
        .form-group .tag-info {
            margin-top: 0.4rem;
            font-size: 0.85rem;
            color: #777;
            font-style: italic;
        }
        
        /* Current image display */
        .current-image img {
            max-width: 100%;
            max-height: 200px;
            display: block;
            margin-top: 10px;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            padding: 5px;
        }
        
        /* Buttons */
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
        
        .btn-back {
            background-color: transparent;
            color: var(--dark-gray);
            border: 2px solid var(--dark-gray);
        }
        
        .btn-back:hover {
            background-color: rgba(52, 73, 94, 0.1);
            transform: translateY(-2px);
        }
        
        .btn-primary, .btn-submit, .btn-update {
            background-color: var(--primary-color);
            color: var(--white);
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.2);
        }
        
        .btn-primary:hover, .btn-submit:hover, .btn-update:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(52, 152, 219, 0.3);
        }
        
        /* Actions Container */
        .actions-container {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-container {
            animation: fadeIn 0.5s ease forwards;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .header-container, main {
                padding: 0 1.5rem;
            }
        }
        
        @media (max-width: 992px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
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
            
            .form-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
                padding: 1.2rem;
            }
            
            .form-title {
                font-size: 1.5rem;
            }
            
            .form-content {
                padding: 1.2rem;
            }
            
            .actions-container {
                flex-direction: column;
                gap: 1rem;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
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
                <a href="loged_main_page.php" class="nav-link active">
                    <i class="fas fa-th-large"></i> My Ads
                </a>
                <a href="create_ad.php" class="nav-link">
                    <i class="fas fa-plus-circle"></i> Create Ad
                </a>
                <a href="contact_page.php" class="nav-link">
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
            <h1 class="page-title">Edit Advertisement</h1>
            <p class="page-subtitle">Update your existing ad for the marketplace</p>
        </div>
        
        <div class="form-container">
            <div class="form-header">
                <h1 class="form-title">Edit Advertisement</h1>
            </div>
            
            <div class="form-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-info-circle"></i> Basic Information
                        </h2>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="title">Advertisement Title</label>
                                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($ad['title']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select id="category" name="category" required>
                                    <option value="">Select a category</option>
                                    <?php foreach ($categories as $categoryItem): ?>
                                        <option value="<?php echo htmlspecialchars($categoryItem['category']); ?>"
                                            <?php echo ($ad['category'] == $categoryItem['category']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoryItem['category']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="advertiser">Advertiser Name</label>
                                <input type="text" id="advertiser" name="advertiser" value="<?php echo htmlspecialchars($ad['advertiser']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="contact">Contact Number</label>
                                <input type="tel" id="contact" name="contact" value="<?php echo htmlspecialchars($ad['contact']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="expiry_date">Expiry Date</label>
                                <input type="date" id="expiry_date" name="expiry_date" value="<?php echo htmlspecialchars($ad['expiry_date']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="tags">Tags</label>
                                <input type="text" id="tags" name="tags" value="<?php echo htmlspecialchars($ad['tags']); ?>" placeholder="e.g. new, affordable, premium, urgent">
                                <div class="tag-info">Separate tags with commas</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-align-left"></i> Description
                        </h2>
                        
                        <div class="form-group">
                            <label for="description">Advertisement Description</label>
                            <textarea id="description" name="description" placeholder="Provide detailed information about your advertisement" required><?php echo htmlspecialchars($ad['description']); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-image"></i> Image
                        </h2>
                        
                        <div class="form-group">
                            <div class="current-image">
                                <label>Current Image</label>
                                <?php if(!empty($ad['image'])): ?>
                                    <img src="data:image/jpeg;base64,<?php echo base64_encode($ad['image']); ?>" alt="Current Ad Image">
                                <?php else: ?>
                                    <p>No image currently uploaded</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Upload New Image (Optional)</label>
                            <input type="file" name="image" id="image" accept="image/*">
                            <div class="tag-info">Leave empty to keep the current image</div>
                        </div>
                    </div>
                    
                    <div class="actions-container">
                        <a href="loged_main_page.php" class="btn btn-back">
                            <i class="fas fa-arrow-left"></i> Back to Listings
                        </a>
                        
                        <button type="submit" name="update_ad" class="btn btn-update">
                            <i class="fas fa-save"></i> Update Advertisement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <script>
        // Set minimum date for expiry date to today
        window.addEventListener('DOMContentLoaded', (event) => {
            const today = new Date();
            const dd = String(today.getDate()).padStart(2, '0');
            const mm = String(today.getMonth() + 1).padStart(2, '0'); // January is 0!
            const yyyy = today.getFullYear();
            
            const todayFormatted = yyyy + '-' + mm + '-' + dd;
            document.getElementById('expiry_date').setAttribute('min', todayFormatted);
        });
    </script>
</body>
</html>