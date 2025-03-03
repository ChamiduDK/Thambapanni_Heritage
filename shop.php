<?php
session_start();
require_once 'db_connect.php';

// Get filters
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$categories = $connection->query("SELECT * FROM categories WHERE type = 'product'")->fetch_all(MYSQLI_ASSOC);

// Build the product query
$query = "SELECT p.*, c.name AS category_name, u.username, u.id AS user_id 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN users u ON p.user_id = u.id 
          WHERE 1=1";

$params = array();
$types = '';

if ($product_id > 0) {
    $query .= " AND p.id = ?";
    $params[] = $product_id;
    $types .= 'i';
} elseif ($selected_category > 0) {
    $query .= " AND p.category_id = ?";
    $params[] = $selected_category;
    $types .= 'i';
}

if (!empty($search_query) && $product_id == 0) {
    $search_query = $connection->real_escape_string($search_query);
    $query .= " AND (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $types .= 'sss';
}

// Prepare and execute the query
$stmt = $connection->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();


// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['customer_id'])) {
        header("Location: customer_login.php?redirect=shop.php");
        exit;
    }
    
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $customer_id = $_SESSION['customer_id'];

    $stmt = $connection->prepare("INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
    $stmt->bind_param("iiii", $customer_id, $product_id, $quantity, $quantity);
    if ($stmt->execute()) {
        header("Location: shop.php?success=Added to cart!");
    } else {
        $error = "Failed to add to cart: " . $connection->error;
    }
    $stmt->close();
}

// Get cart items count
$cart_items = 0;
if (isset($_SESSION['customer_id'])) {
    $cart_items = $connection->query("SELECT COUNT(*) as count FROM cart WHERE customer_id = " . $_SESSION['customer_id'])->fetch_assoc()['count'];
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thambapanni Heritage - Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/shop-styles.css.">
</head>
<body>
    <div class="container">
        <div class="nav">
            <button class="nav-toggle"><i class="fas fa-bars"></i></button>
            <div class="nav-links">
                <a href="gigs.php">Gigs</a>
                <a href="cart.php" class="cart-link">Cart <i class="fas fa-shopping-cart"></i> (<?php echo $cart_items; ?>)</a>
                <a href="customer_dashboard.php">Dashboard</a>
                <?php if (isset($_SESSION['customer_id'])) { ?>
                    <a href="logout.php">Logout</a>
                <?php } else { ?>
                    <a href="customer_login.php">Login</a>
                <?php } ?>
            </div>
        </div>

        <h1>Thambapanni Heritage Shop</h1>
        <p class="intro-text">Discover Sri Lanka's rich cultural diversity and traditional craftsmanship.</p>
        
        <?php if (isset($_GET['success']) && isset($_SESSION['customer_id'])) { ?>
            <div class="success-message"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php } ?>
        <?php if (isset($error)) { ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <div class="search-bar">
            <form action="shop.php" method="GET">
                <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>

        <div class="categories">
            <a href="shop.php" class="<?php echo $selected_category == 0 ? 'active' : ''; ?>">All</a>
            <?php foreach ($categories as $cat) { ?>
                <a href="shop.php?category=<?php echo $cat['id']; ?>" class="<?php echo $selected_category == $cat['id'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php } ?>
        </div>

        <div class="product-grid">
            <?php if (empty($products)) { ?>
                <p>No products found.</p>
            <?php } else { ?>
                <?php foreach ($products as $product) { ?>
                    <div class="product">
                        <div class="product-image <?php echo empty($product['image']) ? 'placeholder' : ''; ?>">
                            <?php if (!empty($product['image'])) { ?>
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php } else { ?>
                                No Image
                            <?php } ?>
                        </div>
                        <div class="product-details">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="description"><?php echo htmlspecialchars($product['description']); ?></p>
                            <p><strong>Price:</strong> LKR <?php echo number_format($product['price'], 2); ?></p>
                            <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name']); ?></p>
                            <p><strong>Seller:</strong> <?php echo htmlspecialchars($product['username']); ?></p>
                            <form action="shop.php" method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="number" name="quantity" value="1" min="1">
                                <button type="submit" name="add_to_cart">Add to Cart <i class="fas fa-cart-plus"></i></button>
                            </form>
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