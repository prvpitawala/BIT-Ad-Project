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

// Fetch ads from the database for the logged-in user
$conn = new mysqli("localhost", "root", "", "userdb");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the logged-in user's ID from the session
$user_id = $_SESSION['user_id'];

// Handle ad deletion
if (isset($_GET['delete_ad'])) {
    $ad_id = $_GET['delete_ad'];
    
    $sql = "DELETE FROM ads WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ad_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Ad deleted successfully!'); window.location.href = 'loged_main_page.php';</script>";
    } else {
        echo "<script>alert('Error deleting ad: " . $stmt->error . "');</script>";
    }
    
    $stmt->close();
}

// Modify SQL query to fetch only ads created by the logged-in user
$sql = "SELECT * FROM ads WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id); // Use the user's ID to filter ads
$stmt->execute();
$result = $stmt->get_result();

$ads = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ads[] = $row;
    }
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            text-align: center;
        }

        .banner {
            background-color: rgb(40, 137, 167);
            color: white;
            padding: 10px;
            font-size: 24px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
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

        button {
            padding: 8px 20px;
            background: white;
            color: rgb(40, 137, 167);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }


        button:hover {
            background: #f0f0f0;
        }

        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .ads-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            max-width: 1200px;
            margin-top: 20px;
            padding-bottom: 20px;
            overflow-y: auto;
        }

        /* Ad Card Styling */
        .ad-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            width: 250px; /* Fixed width */
            height: 350px; /* Increased height to accommodate image */
            padding: 20px;
            text-align: left;
            transition: transform 0.2s ease-in-out;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            cursor: pointer;
        }

        .ad-card:hover {
            transform: translateY(-10px);
        }

        .ad-card h3 {
            margin-top: 0;
            font-size: 18px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 10px;
        }

        .ad-card p {
            font-size: 14px;
            color: #555;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3; /* Reduced to make room for image */
            -webkit-box-orient: vertical;
            margin-bottom: 0;
        }

        .ad-image-container {
            width: 100%;
            height: 200px; /* Fixed height for images */
            margin-bottom: 10px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f9f9f9;
            border-radius: 5px;
        }

        .ad-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .no-image {
            color: #aaa;
            font-size: 12px;
        }

        /* Additional details */
        .ad-info {
            font-size: 12px;
            color: #777;
            margin-top: 10px;
        }

        /* Custom scrollbar styles */
        .ads-container::-webkit-scrollbar {
            width: 8px;
        }

        .ads-container::-webkit-scrollbar-thumb {
            background-color: rgb(40, 137, 167);
            border-radius: 10px;
        }

        .ads-container::-webkit-scrollbar-track {
            background-color: #f4f4f4;
        }

    </style>
</head>
<body>

    <div class="banner">
        <div>Welcome <?php echo $_SESSION['username']; ?>!</div>
        <div class="banner-controls">
            <div class="banner-buttons">
                <!-- Create Ad Button -->
                <button onclick="window.location.href='create_ad.php'">Create Ad</button>
                <!-- Go to main page -->
                <button onclick="window.location.href='main_page.php'">Main Page</button>
                <!-- Go to contact page -->
                <button onclick="window.location.href='contact_page.php'">Contact Page</button>
                <!-- Log Out Button Form -->
                <form method="POST" action="">
                    <button type="submit" name="logout">Log Out</button>
                </form>
                
            </div>
        </div>
    </div>

    <div class="container">
        <p>Enjoy our services. You are logged in, <?php echo $_SESSION['username']; ?>!</p>
        
        <div class="ads-container">
            <?php if (!empty($ads)): ?>
                <?php foreach (array_reverse($ads) as $ad): ?>
                    <div class="ad-card" onclick="viewAd(<?php echo $ad['id']; ?>)">
                        <!-- Image container -->
                        <div class="ad-image-container">
                            <?php if (!empty($ad['image'])): ?>
                                <?php 
                                // Convert BLOB to base64 for displaying in HTML
                                $imageData = base64_encode($ad['image']);
                                $src = 'data:image/jpeg;base64,' . $imageData;
                                ?>
                                <img src="<?php echo $src; ?>" alt="<?php echo htmlspecialchars($ad['title']); ?>" class="ad-image">
                            <?php else: ?>
                                <div class="no-image">No image available</div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Ad content -->
                        <div>
                            <h3><?php echo htmlspecialchars($ad['title']); ?></h3>
                            <p><?php echo htmlspecialchars($ad['description']); ?></p>
                        </div>
                        
                        <!-- Ad info -->
                        <div class="ad-info">
                            <?php if (!empty($ad['category'])): ?>
                                Category: <?php echo htmlspecialchars($ad['category']); ?> | 
                            <?php endif; ?>
                            Posted: <?php echo date("M d, Y", strtotime($ad['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No ads found. Please create a new ad!</p>
            <?php endif; ?>
        </div>

    </div>

    <script>
        // function to go to edit ads
        function viewAd(adId) {
            window.location.href = "ad_preview_by_user.php?id=" + adId;
        }
    </script>

</body>
</html>