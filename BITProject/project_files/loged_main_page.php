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
    <title>My Dashboard - MarketPlace Hub</title>
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
            max-width: 1400px;
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
        
        /* Dashboard Stats */
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background-color: var(--white);
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .stat-title {
            font-size: 0.9rem;
            color: #888;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-gray);
            margin-bottom: 0.5rem;
        }
        
        .stat-description {
            color: #888;
            font-size: 0.9rem;
        }
        
        /* Ads Section */
        .ads-section {
            margin-top: 2.5rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-gray);
        }
        
        .filter-controls {
            display: flex;
            gap: 1rem;
        }
        
        .filter-btn {
            padding: 0.6rem 1.2rem;
            background-color: var(--white);
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-btn:hover, .filter-btn.active {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .filter-btn.active {
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .filter-btn i {
            font-size: 0.9rem;
        }
        
        /* Ads Grid */
        .ads-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .ad-card {
            background-color: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .ad-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .ad-image-container {
            height: 200px;
            overflow: hidden;
            position: relative;
            background-color: #f9f9f9;
        }
        
        .ad-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .ad-card:hover .ad-image {
            transform: scale(1.05);
        }
        
        .no-image {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #aaa;
            font-size: 0.9rem;
        }
        
        .no-image i {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            opacity: 0.5;
        }
        
        .ad-content {
            padding: 1.2rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .ad-category {
            margin-bottom: 0.8rem;
        }
        
        .category-badge {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
        }
        
        .category-badge i {
            margin-right: 5px;
            font-size: 0.7rem;
        }
        
        .ad-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark-gray);
            margin-bottom: 0.8rem;
            line-height: 1.3;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .ad-description {
            color: #777;
            font-size: 0.95rem;
            margin-bottom: 1rem;
            flex-grow: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .ad-info {
            margin-top: auto;
            padding-top: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: #888;
            border-top: 1px solid #eee;
        }
        
        .ad-author {
            display: flex;
            align-items: center;
        }
        
        .ad-author i {
            margin-right: 5px;
            color: var(--primary-color);
        }
        
        .ad-date {
            display: flex;
            align-items: center;
        }
        
        .ad-date i {
            margin-right: 5px;
            color: #aaa;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3rem;
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
            grid-column: 1 / -1;
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
        
        .empty-state .btn {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            margin: 0 auto;
            display: inline-flex;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .header-container, main {
                padding: 0 1.5rem;
            }
        }
        
        @media (max-width: 992px) {
            .ads-container {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 1.5rem;
            }
            
            .dashboard-stats {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
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
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .filter-controls {
                width: 100%;
                overflow-x: auto;
                padding-bottom: 10px;
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
            
            .dashboard-stats {
                grid-template-columns: 1fr;
            }
            
            .ads-container {
                grid-template-columns: 1fr;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .page-subtitle {
                font-size: 0.9rem;
            }
        }
        
        /* Modified Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .dashboard-stats .stat-card {
            opacity: 0;
            animation: fadeIn 0.5s ease forwards;
        }
        
        .ads-container .ad-card {
            opacity: 0;
            animation: fadeIn 0.5s ease forwards;
        }
        
        .stat-card:nth-child(2) { animation-delay: 0.1s; }
        .ad-card:nth-child(2) { animation-delay: 0.1s; }
        .ad-card:nth-child(3) { animation-delay: 0.2s; }
        .ad-card:nth-child(4) { animation-delay: 0.3s; }
        .ad-card:nth-child(5) { animation-delay: 0.4s; }
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
            <h1 class="page-title">My Dashboard</h1>
            <p class="page-subtitle">Manage your advertisements and track their performance</p>
        </div>

        <!-- Dashboard Statistics -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-title">TOTAL ADS</h3>
                    <div class="stat-icon">
                        <i class="fas fa-ad"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo count($ads); ?></div>
                <div class="stat-description">Active advertisements</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-title">LATEST LISTING</h3>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-value">
                    <?php
                    if (!empty($ads)) {
                        $latestAd = end($ads);
                        echo date("M d", strtotime($latestAd['created_at']));
                    } else {
                        echo "-";
                    }
                    ?>
                </div>
                <div class="stat-description">Date of most recent ad</div>
            </div>
        </div>



        <!-- Ads Section -->
        <section class="ads-section">
            <div class="section-header">
                <h2 class="section-title">My Advertisements</h2>
            </div>

            <div class="ads-container">
                <?php if (!empty($ads)): ?>
                    <?php foreach (array_reverse($ads) as $ad): ?>
                        <div class="ad-card" onclick="viewAd(<?php echo $ad['id']; ?>)">
                            <div class="ad-image-container">
                                <?php if (!empty($ad['image'])): ?>
                                    <?php 
                                    // Convert BLOB to base64 for displaying in HTML
                                    $imageData = base64_encode($ad['image']);
                                    $src = 'data:image/jpeg;base64,' . $imageData;
                                    ?>
                                    <img src="<?php echo $src; ?>" alt="<?php echo htmlspecialchars($ad['title']); ?>" class="ad-image">
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fas fa-image"></i>
                                        <div>No image available</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="ad-content">
                                <?php if (!empty($ad['category'])): ?>
                                    <div class="ad-category">
                                        <span class="category-badge">
                                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($ad['category']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <h3 class="ad-title"><?php echo htmlspecialchars($ad['title']); ?></h3>
                                <p class="ad-description"><?php echo htmlspecialchars($ad['description']); ?></p>
                                
                                <div class="ad-info">
                                    <div class="ad-author">
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($ad['advertiser']); ?>
                                    </div>
                                    
                                    <div class="ad-date">
                                        <i class="far fa-calendar-alt"></i> <?php echo date("M d, Y", strtotime($ad['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <h3>No advertisements found</h3>
                        <p>Get started by creating your first advertisement!</p>
                        <a href="create_ad.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Ad
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script>
        // Function to view ad details
        function viewAd(adId) {
            window.location.href = "ad_preview_by_user.php?id=" + adId;
        }

        // Filter buttons functionality (for demo purposes)
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // In a real application, you would implement actual filtering here
                });
            });
        });
    </script>
</body>
</html>