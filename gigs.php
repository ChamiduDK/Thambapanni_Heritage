<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit;
}

$selected_category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : ''; // Get search term
$categories = $connection->query("SELECT * FROM categories WHERE type = 'gig'")->fetch_all(MYSQLI_ASSOC);

$query = "SELECT g.*, c.name AS category_name, u.username, u.id AS user_id 
          FROM gigs g 
          LEFT JOIN categories c ON g.category_id = c.id 
          LEFT JOIN users u ON g.user_id = u.id WHERE 1=1"; // Base query with 1=1 for easy appending
if ($selected_category > 0) {
    $query .= " AND g.category_id = $selected_category";
}
if (!empty($search_query)) {
    $search_query = $connection->real_escape_string($search_query); // Prevent SQL injection
    $query .= " AND (g.title LIKE '%$search_query%' OR g.description LIKE '%$search_query%' OR c.name LIKE '%$search_query%')";
}
$gigs = $connection->query($query)->fetch_all(MYSQLI_ASSOC);

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thambapanni Heritage - Gigs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/gig-styles.css">
</head>
<body>
    <div class="container">
        <div class="nav">
            <button class="nav-toggle"><i class="fas fa-bars"></i></button>
            <div class="nav-links">
                <a href="shop.php">Shop</a>
                <a href="cart.php" class="cart-link">Cart <i class="fas fa-shopping-cart"></i> (<?php echo $cart_items; ?>)</a>
                <a href="customer_dashboard.php">Dashboard</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>

        <h1>Thambapanni Heritage Gigs</h1>
        <p class="intro-text">Explore services from Sri Lankan artists, showcasing our rich cultural diversity and traditional craftsmanship.</p>

        <div class="search-bar">
            <form action="gigs.php" method="GET">
                <input type="text" name="search" placeholder="Search gigs..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>

        <div class="categories">
            <a href="gigs.php" class="<?php echo $selected_category == 0 ? 'active' : ''; ?>">All</a>
            <?php foreach ($categories as $cat) { ?>
                <a href="gigs.php?category=<?php echo $cat['id']; ?>" class="<?php echo $selected_category == $cat['id'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php } ?>
        </div>

        <div class="gig-grid">
            <?php if (empty($gigs)) { ?>
                <p>No gigs found.</p>
            <?php } else { ?>
                <?php foreach ($gigs as $gig) { ?>
                    <div class="gig">
                        <div class="gig-image <?php echo empty($gig['image']) ? 'placeholder' : ''; ?>">
                            <?php if (!empty($gig['image'])) { ?>
                                <img src="<?php echo htmlspecialchars($gig['image']); ?>" alt="<?php echo htmlspecialchars($gig['title']); ?>">
                            <?php } else { ?>
                                No Image
                            <?php } ?>
                        </div>
                        <div class="gig-details">
                            <h3><?php echo htmlspecialchars($gig['title']); ?></h3>
                            <p class="description"><?php echo htmlspecialchars($gig['description']); ?></p>
                            <p><strong>Price:</strong> LKR <?php echo number_format($gig['price'], 2); ?></p>
                            <p><strong>Category:</strong> <?php echo htmlspecialchars($gig['category_name']); ?></p>
                            <p><strong>Provider:</strong> <?php echo htmlspecialchars($gig['username']); ?></p>
                            <a href="customer_dashboard.php?gig_id=<?php echo $gig['id']; ?>">
                                <button>Choose Gig <i class="fas fa-arrow-right"></i></button>
                            </a>
                        </div>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>
    </div>

    <script>
        document.querySelector('.nav-toggle').addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('active');
        });
    </script>
</body>
</html>