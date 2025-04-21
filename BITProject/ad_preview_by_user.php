<?php
    session_start(); // Start the session

    // Check if user is logged in
    if (!isset($_SESSION['username'])) {
        // If not logged in, redirect to the login page
        header("Location: user_login.php");
        exit();
    }

    // Check if ad ID is provided
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        echo "Invalid advertisement ID";
        exit();
    }

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
    
    $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ad Preview - <?php echo htmlspecialchars($ad['title']); ?></title>
    <style>
        /* Reset and base styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333;
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
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Ad details */
        .ad-title {
            font-size: 28px;
            color: rgb(40, 137, 167);
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .ad-metadata {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }

        .metadata-item {
            margin-bottom: 8px;
        }

        .metadata-label {
            font-weight: bold;
            color: #555;
        }

        .ad-description {
            line-height: 1.6;
            white-space: pre-line;
            margin-bottom: 0px;
        }

        .description-title {
            font-size: 20px;
            color: rgb(40, 137, 167);
            margin-bottom: 10px;
        }

        .image-container {
            text-align: center;
            margin: 20px 0;
        }

        .ad-image {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        /* Buttons */
        .button-container {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s ease;
        }

        .back-btn {
            background-color: #555;
            color: white;
        }

        .back-btn:hover {
            background-color: #444;
        }

        /* Status indicator */
        .expiry-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            margin-left: 10px;
        }

        .active {
            background-color: #E8F5E9;
            color: #4CAF50;
        }

        .expired {
            background-color: #FFEBEE;
            color: #F44336;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
                margin: 20px 10px;
            }
            
            .ad-metadata {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="banner">
        <div>Advertisement Details</div>
        <div class="banner-controls">
            <!-- <a href="admin_page.php?tab=ads" class="btn back-btn">Back to Admin Panel</a> -->
        </div>
    </div>

    <div class="main-content">
        <h1 class="ad-title">
            <?php echo htmlspecialchars($ad['title']); ?>
            <?php 
                $today = date('Y-m-d');
                $expiry = $ad['expiry_date'];
                $status_class = ($today <= $expiry) ? 'active' : 'expired';
                $status_text = ($today <= $expiry) ? 'Active' : 'Expired';
            ?>
            <span class="expiry-status <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
        </h1>
        
        <?php if ($ad['image']): ?>
        <div class="image-container">
            <img class="ad-image" src="data:image/jpeg;base64,<?php echo base64_encode($ad['image']); ?>" alt="Advertisement Image">
        </div>
        <?php endif; ?>
        
        <h3 class="description-title">Description</h3>
        <div class="ad-description">
            <div class="ad-metadata">
                <?php echo nl2br(htmlspecialchars($ad['description'])); ?>
            </div>
        </div>
        
        <h3 class="description-title">Details</h3>
        
        <div class="ad-metadata">
            <div class="metadata-item">
                <span class="metadata-label">Category:</span> 
                <?php echo htmlspecialchars($ad['category']); ?>
            </div>
            
            <div class="metadata-item">
                <span class="metadata-label">Advertiser:</span> 
                <?php echo htmlspecialchars($ad['advertiser']); ?>
            </div>
            
            <div class="metadata-item">
                <span class="metadata-label">Contact:</span> 
                <?php echo htmlspecialchars($ad['contact']); ?>
            </div>
            
            <div class="metadata-item">
                <span class="metadata-label">Posted by:</span> 
                <?php echo htmlspecialchars($ad['username']); ?>
            </div>
            
            <div class="metadata-item">
                <span class="metadata-label">Created at:</span> 
                <?php echo date('Y-m-d H:i', strtotime($ad['created_at'])); ?>
            </div>
            
            <div class="metadata-item">
                <span class="metadata-label">Expires on:</span> 
                <?php echo date('Y-m-d', strtotime($ad['expiry_date'])); ?>
            </div>

            <div class="metadata-item">
                <span class="metadata-label">Ad ID:</span> 
                <?php echo htmlspecialchars ($ad['id']); ?>
            </div>
            <div class="metadata-item">
                <span class="metadata-label">Tags:</span> 
                <?php echo htmlspecialchars($ad['tags']); ?>
            </div>
        </div>
        
        
        <div class="button-container">
            <a href="edit_ad_by_user.php?id=<?php echo $ad['id']; ?>" class="btn edit-btn" style="padding-left: 10px; color: blue;">Edit Advertisement</a>
            <a href="#" class="btn delete-btn" style="padding-left: 0; color: red;" onclick="confirmDelete(<?php echo $ad['id']; ?>)">Delete Advertisement</a>
        </div>
    </div>

    <script>
        function confirmDelete(adId) {
            if (confirm("Are you sure you want to delete this advertisement?")) {
                window.location.href = `loged_main_page.php?delete_ad=${adId}`;
            }
        }
    </script>
</body>
</html>