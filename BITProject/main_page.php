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

        .search-bar {
            padding: 8px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            width: 200px;
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
            margin: 0px;
            margin-left: 5px;
        }

        .ad-card p {
            font-size: 14px;
            color: #555;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3; /* Reduced line count to make room for image */
            -webkit-box-orient: vertical;
        }

        .ad-tags {
            margin-top: 10px;
            font-size: 12px;
            color: #2889a7;
        }
        
        .ad-category {
            margin-top: 5px;
            font-size: 12px;
            color: #333;
            background-color: #f0f0f0;
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
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
        
        .ad-info {
            font-size: 12px;
            color: #777;
            margin-top: 10px;
        }

        .no-image {
            color: #aaa;
            font-size: 12px;
        }

        .search-container {
            margin: 20px 0;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-input {
            padding: 10px;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .category-dropdown {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            min-width: 180px;
        }

        .search-button {
            padding: 10px 20px;
            background-color: rgb(40, 137, 167);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        /* Filter display area */
        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 10px 0;
            justify-content: center;
        }

        .filter-tag {
            background-color: rgb(40, 137, 167);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .remove-filter {
            cursor: pointer;
            font-weight: bold;
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
        <div>Welcome to Our Website</div>
        <div class="banner-controls">
            <div class="banner-buttons">
                <button onclick="window.location.href='user_login.php'">Login</button>
                <button onclick="window.location.href='user_register.php'">Register</button>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- <h2>Browse Ads</h2> -->
        
        <!-- Search and Filter section -->
        <div class="search-container">
            <input type="text" id="tagSearch" class="search-input" placeholder="Search by tags (e.g., electronics, furniture)" value="<?php echo htmlspecialchars($searchTerm); ?>">
            
            <!-- Category dropdown -->
            <select id="categorySelect" class="category-dropdown">
                <option value="">All Categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category); ?>" <?php echo ($categoryFilter === $category) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button class="search-button" onclick="applyFilters()">Apply Filters</button>
        </div>
        
        <!-- Display active filters -->
        <?php if (!empty($searchTerm) || !empty($categoryFilter)): ?>
            <div class="active-filters">
                <?php if (!empty($searchTerm)): ?>
                    <div class="filter-tag">
                        Search: <?php echo htmlspecialchars($searchTerm); ?>
                        <span class="remove-filter" onclick="removeSearchFilter()">×</span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($categoryFilter)): ?>
                    <div class="filter-tag">
                        Category: <?php echo htmlspecialchars($categoryFilter); ?>
                        <span class="remove-filter" onclick="removeCategoryFilter()">×</span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
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
                            <?php if (!empty($ad['category'])): ?>
                                <div class="ad-category">
                                    <?php echo htmlspecialchars($ad['category']); ?>
                                </div>
                            <?php endif; ?>
                            <!-- <?php if (!empty($ad['tags'])): ?>
                                <div class="ad-tags">
                                    <strong>Tags:</strong> <?php echo htmlspecialchars($ad['tags']); ?>
                                </div>
                            <?php endif; ?> -->
                        </div>
                        <div class="ad-info">
                            <small><?php echo htmlspecialchars($ad['advertiser']); ?> | 
                                   <?php echo date("M d, Y", strtotime($ad['created_at'])); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>
                <p>No ads found matching your search criteria. Try different tags or browse all ads.</p>
            <?php endif; ?>
        </div>
    </div>
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
        
        // Apply filters when category changes
        document.getElementById('categorySelect').addEventListener('change', function() {
            applyFilters();
        });
        
        // Also make the banner search bar functional
        document.querySelector('.search-bar')?.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                const searchTerm = this.value.trim();
                window.location.href = 'main_page.php?search=' + encodeURIComponent(searchTerm);
            }
        });
    </script>
</body>
</html>