<?php
$conn = new mysqli("localhost", "root", "", "userdb");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if search term exists
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
// Check if category filter exists
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';

// Get all available categories from the categories table
$categoriesQuery = "SELECT category FROM categories ORDER BY category";
$categoriesResult = $conn->query($categoriesQuery);
$categories = [];
if ($categoriesResult->num_rows > 0) {
    while ($row = $categoriesResult->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

// Modify SQL query based on search term and category
if (!empty($searchTerm) && !empty($categoryFilter)) {
    // Filter by both search term and category
    $sql = "SELECT * FROM ads WHERE tags LIKE ? AND category = ?";
    $stmt = $conn->prepare($sql);
    $searchParam = "%" . $searchTerm . "%";
    $stmt->bind_param("ss", $searchParam, $categoryFilter);
} elseif (!empty($searchTerm)) {
    // Filter by search term only
    $sql = "SELECT * FROM ads WHERE tags LIKE ?";
    $stmt = $conn->prepare($sql);
    $searchParam = "%" . $searchTerm . "%";
    $stmt->bind_param("s", $searchParam);
} elseif (!empty($categoryFilter)) {
    // Filter by category only
    $sql = "SELECT * FROM ads WHERE category = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $categoryFilter);
} else {
    // If no filters, fetch all ads
    $sql = "SELECT * FROM ads";
    $stmt = $conn->prepare($sql);
}

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
    <title>MarketPlace Hub</title>
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
        }
        
        .logo i {
            margin-right: 10px;
            font-size: 2rem;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
        }
        
        .search-container {
            position: relative;
            margin-right: 1.5rem;
        }
        
        .search-bar {
            padding: 0.7rem 1rem 0.7rem 2.5rem;
            width: 300px;
            border: none;
            border-radius: 50px;
            background-color: rgba(255, 255, 255, 0.2);
            color: var(--white);
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .search-bar::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .search-bar:focus {
            background-color: rgba(255, 255, 255, 0.3);
            outline: none;
            width: 350px;
        }
        
        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
        }
        
        .auth-buttons {
            display: flex;
            gap: 1rem;
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
        
        .btn-login {
            background-color: transparent;
            color: var(--white);
            border: 2px solid var(--white);
        }
        
        .btn-register {
            background-color: var(--white);
            color: var(--primary-color);
            border: 2px solid var(--white);
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-login:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .btn-register:hover {
            background-color: rgba(255, 255, 255, 0.9);
        }
        
        /* Main Content Area */
        main {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        /* Filter Section */
        .filter-section {
            background-color: var(--white);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .filter-container {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }
        
        .filter-heading {
            display: flex;
            align-items: center;
            margin-right: 1rem;
            color: var(--dark-gray);
            font-weight: 600;
        }
        
        .filter-heading i {
            margin-right: 8px;
            color: var(--primary-color);
        }
        
        .filter-input {
            flex-grow: 1;
            position: relative;
        }
        
        .filter-input i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }
        
        .search-input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.5rem;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        
        .category-dropdown {
            padding: 0.8rem 1rem;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            min-width: 200px;
            background-color: var(--white);
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: calc(100% - 1rem) center;
            padding-right: 2.5rem;
        }
        
        .category-dropdown:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        
        .search-button {
            padding: 0.8rem 1.8rem;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        .search-button i {
            margin-right: 8px;
        }
        
        .search-button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .search-button:active {
            transform: translateY(0);
        }
        
        /* Active Filters */
        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            margin-top: 1.2rem;
        }
        
        .filter-tag {
            background-color: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.3);
            color: var(--primary-color);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }
        
        .filter-tag i {
            font-size: 0.8rem;
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
        
        .remove-filter {
            cursor: pointer;
            color: var(--primary-color);
            margin-left: 5px;
            font-size: 1.1rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            transition: all 0.2s ease;
        }
        
        .remove-filter:hover {
            background-color: rgba(52, 152, 219, 0.2);
        }
        
        /* Ads Grid */
        .ads-heading {
            font-size: 1.5rem;
            margin: 0 0 1.5rem;
            color: var(--dark-gray);
            font-weight: 600;
        }
        
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
            transition: all 0.3s ease;
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
            margin-bottom: 0.5rem;
            line-height: 1.3;
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
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .header-container, main {
                padding: 0 1.5rem;
            }
            
            .search-bar {
                width: 250px;
            }
            
            .search-bar:focus {
                width: 300px;
            }
        }
        
        @media (max-width: 992px) {
            .ads-container {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 1.5rem;
            }
            
            .filter-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-heading {
                margin-bottom: 0.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            .logo {
                margin-bottom: 0.5rem;
            }
            
            .nav-links {
                width: 100%;
                flex-direction: column;
                gap: 1rem;
            }
            
            .search-container {
                width: 100%;
                margin-right: 0;
            }
            
            .search-bar, .search-bar:focus {
                width: 100%;
            }
            
            .auth-buttons {
                width: 100%;
            }
            
            .btn {
                flex: 1;
            }
            
            main {
                padding: 0 1rem;
                margin: 1rem auto;
            }
            
            .filter-section {
                padding: 1rem;
            }
            
            .ads-container {
                grid-template-columns: repeat(auto-fill, minmax(100%, 1fr));
                gap: 1rem;
            }
            
            .ad-card {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <i class="fas fa-store"></i> AdDrop
            </div>
            
            <div class="nav-links">
                
                <div class="auth-buttons">
                    <a href="user_login.php" class="btn btn-login">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="user_register.php" class="btn btn-register">
                        <i class="fas fa-user-plus"></i> Register
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <main>
        <section class="filter-section">
            <div class="filter-container">
                <div class="filter-heading">
                    <i class="fas fa-filter"></i> Filter Items
                </div>
                
                <div class="filter-input">
                    <i class="fas fa-search"></i>
                    <input type="text" id="tagSearch" class="search-input" placeholder="Search by tags (e.g., electronics, furniture)" value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                
                <select id="categorySelect" class="category-dropdown">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>" <?php echo ($categoryFilter === $category) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button class="search-button" onclick="applyFilters()">
                    <i class="fas fa-search"></i> Apply Filters
                </button>
            </div>
            
            <!-- Display active filters -->
            <?php if (!empty($searchTerm) || !empty($categoryFilter)): ?>
                <div class="active-filters">
                    <?php if (!empty($searchTerm)): ?>
                        <div class="filter-tag">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($searchTerm); ?>
                            <span class="remove-filter" onclick="removeSearchFilter()">×</span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($categoryFilter)): ?>
                        <div class="filter-tag">
                            <i class="fas fa-folder"></i> <?php echo htmlspecialchars($categoryFilter); ?>
                            <span class="remove-filter" onclick="removeCategoryFilter()">×</span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
        
        <h2 class="ads-heading">
            <?php if (!empty($searchTerm) || !empty($categoryFilter)): ?>
                Search Results
            <?php else: ?>
                Latest Listings
            <?php endif; ?>
        </h2>
        
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
                    <i class="fas fa-search"></i>
                    <h3>No listings found</h3>
                    <p>We couldn't find any listings matching your search criteria. Try adjusting your filters or browse all listings.</p>
                    <button class="btn" onclick="window.location.href='main_page.php'">
                        <i class="fas fa-redo"></i> Show All Listings
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <script>
        // Function to go to ad preview
        function viewAd(adId) {
            window.location.href = "ad_preview_by_guest.php?id=" + adId;
        }
        
        // Function to apply all filters
        function applyFilters() {
            const searchTerm = document.getElementById('tagSearch').value.trim();
            const category = document.getElementById('categorySelect').value.trim();
            
            let url = 'main_page.php?';
            let params = [];
            
            if (searchTerm) {
                params.push('search=' + encodeURIComponent(searchTerm));
            }
            
            if (category) {
                params.push('category=' + encodeURIComponent(category));
            }
            
            window.location.href = url + params.join('&');
        }
        
        // Function to remove search filter
        function removeSearchFilter() {
            const category = document.getElementById('categorySelect').value.trim();
            if (category) {
                window.location.href = 'main_page.php?category=' + encodeURIComponent(category);
            } else {
                window.location.href = 'main_page.php';
            }
        }
        
        // Function to remove category filter
        function removeCategoryFilter() {
            const searchTerm = document.getElementById('tagSearch').value.trim();
            if (searchTerm) {
                window.location.href = 'main_page.php?search=' + encodeURIComponent(searchTerm);
            } else {
                window.location.href = 'main_page.php';
            }
        }
        
        // Allow search on Enter key press
        document.getElementById('tagSearch').addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                applyFilters();
            }
        });
        
        // Header search functionality 
        document.getElementById('headerSearch').addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                const searchTerm = this.value.trim();
                window.location.href = 'main_page.php?search=' + encodeURIComponent(searchTerm);
            }
        });
    </script>
</body>
</html>