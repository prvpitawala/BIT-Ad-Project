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
    <title>Edit Ad</title>
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

        /* Form container */
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

        /* Form elements */
        form {
            max-width: 600px;
            margin: 0 auto;
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

        .tag-info {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }

        input, 
        textarea, 
        select {
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
        textarea:focus, 
        select:focus {
            border-color: rgb(40, 137, 167);
        }

        textarea {
            resize: vertical;
            height: 120px;
        }

        /* Current image section */
        .current-image {
            margin-bottom: 15px;
        }
        
        .current-image img {
            max-width: 100%;
            max-height: 200px;
            display: block;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
        }

        /* Select element styling */
        select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            cursor: pointer;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 1em;
        }

        /* File upload styling */
        input[type="file"] {
            padding: 8px;
            background-color: #fafafa;
            border: 2px solid #ccc;
            font-size: 15px;
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
        <div> Edit ad </div>
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
        <h2>Edit Advertisement</h2>
        <form method="POST" enctype="multipart/form-data">
            <!-- Ad Title -->
            <div class="form-group">
                <label for="title">Ad Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($ad['title']); ?>" required>
            </div>
            
            <!-- Ad Category -->
            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $categoryItem): ?>
                        <option value="<?php echo htmlspecialchars($categoryItem['category']); ?>"
                            <?php echo ($ad['category'] == $categoryItem['category']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($categoryItem['category']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Advertiser Name -->
            <div class="form-group">
                <label for="advertiser">Advertiser Name</label>
                <input type="text" id="advertiser" name="advertiser" value="<?php echo htmlspecialchars($ad['advertiser']); ?>" required>
            </div>
            
            <!-- Contact Number -->
            <div class="form-group">
                <label for="contact">Contact Number</label>
                <input type="tel" id="contact" name="contact" value="<?php echo htmlspecialchars($ad['contact']); ?>" required>
            </div>
            
            <!-- Ad Expiry Date -->
            <div class="form-group">
                <label for="expiry_date">Expiry Date</label>
                <input type="date" id="expiry_date" name="expiry_date" value="<?php echo htmlspecialchars($ad['expiry_date']); ?>" required>
            </div>

            <!-- Ad Tags -->
            <div class="form-group">
                <label for="tags">Tags</label>
                <input type="text" id="tags" name="tags" value="<?php echo htmlspecialchars($ad['tags']); ?>" placeholder="Enter tags separated by commas">
                <div class="tag-info">Example: new, affordable, premium, urgent</div>
            </div>

            <!-- Ad Description -->
            <div class="form-group">
                <label for="description">Ad Description</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($ad['description']); ?></textarea>
            </div>

            <!-- Current Image Display -->
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

            <!-- Image Upload -->
            <div class="form-group">
                <label for="image">Upload New Image (Optional)</label>
                <input type="file" name="image" id="image" accept="image/*">
                <div class="tag-info">Leave empty to keep current image</div>
            </div>
            
            <!-- Submit Button -->
            <button type="submit" name="update_ad">Update Advertisement</button>
        </form>
    </div>

</body>
</html>