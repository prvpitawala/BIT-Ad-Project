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
        
        /* Main Content Area */
        main {
            max-width: 1100px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        /* Ad Details Content */
        .ad-container {
            background-color: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        
        .ad-header {
            padding: 1.5rem 2rem;
            background: linear-gradient(to right, rgba(52, 152, 219, 0.05), rgba(52, 152, 219, 0.15));
            border-bottom: 1px solid rgba(52, 152, 219, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .ad-title {
            font-size: 1.8rem;
            color: var(--dark-gray);
            font-weight: 700;
            margin: 0;
            flex-grow: 1;
        }
        
        .expiry-status {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            margin-left: 1rem;
            display: inline-flex;
            align-items: center;
        }
        
        .status-active {
            background-color: rgba(39, 174, 96, 0.1);
            color: #27ae60;
        }
        
        .status-expired {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }
        
        .expiry-status i {
            margin-right: 6px;
        }
        
        .ad-image-container {
            width: 100%;
            max-height: 500px;
            overflow: hidden;
            display: flex;
            justify-content: center;
            background-color: #f9f9f9;
            position: relative;
        }
        
        .ad-image {
            width: 100%;
            height: auto;
            object-fit: contain;
            max-height: 500px;
            display: block;
        }
        
        .no-image {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 300px;
            width: 100%;
            color: #aaa;
            font-size: 1rem;
            flex-direction: column;
        }
        
        .no-image i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .ad-content {
            padding: 2rem;
        }
        
        .section-title {
            font-size: 1.4rem;
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
        
        .description-content {
            background-color: var(--light-gray);
            padding: 1.5rem;
            border-radius: 10px;
            line-height: 1.7;
            margin-bottom: 2rem;
            white-space: pre-line;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .detail-item {
            display: flex;
            align-items: flex-start;
            padding: 1rem;
            background-color: var(--light-gray);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .detail-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }
        
        .detail-icon {
            margin-right: 1rem;
            color: var(--primary-color);
            font-size: 1.2rem;
            padding-top: 0.2rem;
        }
        
        .detail-content {
            flex-grow: 1;
        }
        
        .detail-label {
            font-size: 0.85rem;
            color: #777;
            margin-bottom: 0.3rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-value {
            font-weight: 600;
            color: var(--dark-gray);
            word-break: break-word;
        }
        
        .actions-container {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
        
        .tag-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .tag {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
        }
        
        .tag i {
            margin-right: 5px;
            font-size: 0.7rem;
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
        }
        
        .btn-back {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
        }
        
        .btn-back:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-delete {
            background-color: transparent;
            color: var(--accent-color);
            border: 2px solid var(--accent-color);
        }
        
        .btn-delete:hover {
            background-color: rgba(231, 76, 60, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .details-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            main {
                padding: 0 1rem;
                margin: 1rem auto;
            }
            
            .ad-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
                padding: 1.2rem;
            }
            
            .expiry-status {
                margin-left: 0;
            }
            
            .ad-title {
                font-size: 1.5rem;
            }
            
            .ad-content {
                padding: 1.2rem;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .actions-container {
                flex-direction: column;
                gap: 1rem;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="admin_dashboard.php" class="logo">
                <i class="fas fa-store"></i> AdDrop (Admin)
            </a>
        </div>
    </header>
    
    <main>
        <div class="ad-container">
            <div class="ad-header">
                <h1 class="ad-title"><?php echo htmlspecialchars($ad['title']); ?></h1>
                <?php 
                    $today = date('Y-m-d');
                    $expiry = $ad['expiry_date'];
                    $status_class = ($today <= $expiry) ? 'status-active' : 'status-expired';
                    $status_icon = ($today <= $expiry) ? 'fa-check-circle' : 'fa-clock';
                    $status_text = ($today <= $expiry) ? 'Active' : 'Expired';
                ?>
                <span class="expiry-status <?php echo $status_class; ?>">
                    <i class="fas <?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
                </span>
            </div>
            
            <div class="ad-image-container">
                <?php if (!empty($ad['image'])): ?>
                    <img class="ad-image" src="data:image/jpeg;base64,<?php echo base64_encode($ad['image']); ?>" alt="<?php echo htmlspecialchars($ad['title']); ?>">
                <?php else: ?>
                    <div class="no-image">
                        <i class="fas fa-image"></i>
                        <div>No image available</div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="ad-content">
                <h2 class="section-title">
                    <i class="fas fa-align-left"></i> Description
                </h2>
                <div class="description-content">
                    <?php echo nl2br(htmlspecialchars($ad['description'])); ?>
                </div>
                
                <h2 class="section-title">
                    <i class="fas fa-info-circle"></i> Details
                </h2>
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-tag"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Category</div>
                            <div class="detail-value"><?php echo htmlspecialchars($ad['category']); ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Advertiser</div>
                            <div class="detail-value"><?php echo htmlspecialchars($ad['advertiser']); ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Contact</div>
                            <div class="detail-value"><?php echo htmlspecialchars($ad['contact']); ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-user-edit"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Posted by</div>
                            <div class="detail-value"><?php echo htmlspecialchars($ad['username']); ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="far fa-calendar-plus"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Created at</div>
                            <div class="detail-value"><?php echo date('Y-m-d H:i', strtotime($ad['created_at'])); ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="far fa-calendar-times"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Expires on</div>
                            <div class="detail-value"><?php echo date('Y-m-d', strtotime($ad['expiry_date'])); ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-hashtag"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Ad ID</div>
                            <div class="detail-value"><?php echo htmlspecialchars($ad['id']); ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Tags</div>
                            <div class="tag-container">
                                <?php
                                    $tags = explode(',', $ad['tags']);
                                    foreach ($tags as $tag) {
                                        $tag = trim($tag);
                                        if (!empty($tag)) {
                                            echo '<span class="tag"><i class="fas fa-tag"></i>' . htmlspecialchars($tag) . '</span>';
                                        }
                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="actions-container">
                    <a href="admin_dashboard.php?tab=ads" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Back to Admin Panel
                    </a>
                    
                    <?php if ($_SESSION['is_admin'] == 1): ?>
                    <a href="#" class="btn btn-delete" onclick="confirmDelete(<?php echo $ad['id']; ?>)">
                        <i class="fas fa-trash-alt"></i> Delete Advertisement
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        function confirmDelete(adId) {
            if (confirm("Are you sure you want to delete this advertisement?")) {
                window.location.href = `admin_dashboard.php?tab=ads&delete_ad=${adId}`;
            }
        }
    </script>
</body>
</html>