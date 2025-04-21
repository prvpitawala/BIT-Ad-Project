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

    // Handle ad creation
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Get the ad details from the form
        $title = $_POST['title'];
        $category = $_POST['category'];
        $advertiser = $_POST['advertiser'];
        $contact = $_POST['contact'];
        $expiry_date = $_POST['expiry_date'];
        $description = $_POST['description'];
        $user_id = $_SESSION['user_id']; // Get the logged-in user's ID
        
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
        
        // Handle image upload
        $image = null;
        if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            // Read the image file content
            $image = file_get_contents($_FILES['image']['tmp_name']);
        } else {
            echo "<script>alert('Error: Image upload failed.');</script>";
            // You might want to exit or continue based on your requirements
        }

        // Database connection
        $conn = new mysqli("localhost", "root", "", "userdb");

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Prepare SQL statement to insert ad including image and tags
        $sql = "INSERT INTO ads (user_id, title, category, advertiser, contact, expiry_date, description, image, tags)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssssss", $user_id, $title, $category, $advertiser, $contact, $expiry_date, $description, $image, $tags);

        // Execute the query
        if ($stmt->execute()) {
            echo "<script>
            window.location.href='loged_main_page.php';
            </script>";
        } else {
            echo "<script>alert('Error: Could not create ad. " . $stmt->error . "');</script>";
        }

        // Close connection
        $stmt->close();
        $conn->close();
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Ad</title>
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
        <div>Welcome <?php echo $_SESSION['username']; ?> !</div>
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
        <h2>Create a New Ad</h2>
        <form method="POST" enctype="multipart/form-data">
            <!-- Ad Title -->
            <div class="form-group">
                <label for="title">Ad Title</label>
                <input type="text" id="title" name="title" placeholder="Enter ad title" required>
            </div>
            
            <!-- Ad Category -->
            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $categoryItem): ?>
                        <option value="<?php echo htmlspecialchars($categoryItem['category']); ?>">
                            <?php echo htmlspecialchars($categoryItem['category']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Advertiser Name -->
            <div class="form-group">
                <label for="advertiser">Advertiser Name</label>
                <input type="text" id="advertiser" name="advertiser" placeholder="Enter advertiser name" required>
            </div>
            
            <!-- Contact Number -->
            <div class="form-group">
                <label for="contact">Contact Number</label>
                <input type="tel" id="contact" name="contact" placeholder="Enter contact number" required>
            </div>
            
            <!-- Ad Expiry Date -->
            <div class="form-group">
                <label for="expiry_date">Expiry Date</label>
                <input type="date" id="expiry_date" name="expiry_date" required>
            </div>

            <!-- Ad Tags -->
            <div class="form-group">
                <label for="tags">Tags</label>
                <input type="text" id="tags" name="tags" placeholder="Enter tags separated by commas">
                <div class="tag-info">Example: new, affordable, premium, urgent</div>
            </div>

            <!-- Ad Description -->
            <div class="form-group">
                <label for="description">Ad Description</label>
                <textarea id="description" name="description" placeholder="Enter ad description" required></textarea>
            </div>

            <!-- Image Upload -->
            <div class="form-group">
                <label for="image">Upload Image</label>
                <input type="file" name="image" id="image" accept="image/*" required>
            </div>
            
            <!-- Submit Button -->
            <button type="submit">Create Ad</button>
        </form>
    </div>

</body>
</html>