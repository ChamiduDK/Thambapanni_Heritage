<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];

// Fetch cart items (updated to include gig image)
$cart_items = $connection->query("
    SELECT c.*, 
           CASE 
               WHEN c.type = 'product' THEN p.name 
               WHEN c.type = 'gig' THEN g.title 
           END AS item_name,
           CASE 
               WHEN c.type = 'product' THEN p.price 
               WHEN c.type = 'gig' THEN gr.price 
           END AS price,
           c.type,
           p.image AS product_image,
           g.image AS gig_image
    FROM cart c
    LEFT JOIN products p ON c.product_id = p.id AND c.type = 'product'
    LEFT JOIN gig_requests gr ON c.gig_request_id = gr.id AND c.type = 'gig'
    LEFT JOIN gigs g ON gr.gig_id = g.id
    WHERE c.customer_id = $customer_id
")->fetch_all(MYSQLI_ASSOC);

// Handle cart update or checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $cart_id => $quantity) {
        $quantity = intval($quantity);
        if ($quantity > 0) {
            $stmt = $connection->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND customer_id = ?");
            $stmt->bind_param("iii", $quantity, $cart_id, $customer_id);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $connection->prepare("DELETE FROM cart WHERE id = ? AND customer_id = ?");
            $stmt->bind_param("ii", $cart_id, $customer_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: cart.php?success=Cart updated!");
}

// Placeholder for payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $success = "Payment processing placeholder - implement this!";
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thambapanni Heritage - Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="cart-styles.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h3>Your Cart</h3>
                <i class="fas fa-times close-sidebar" id="closeSidebar"></i>
            </div>
            <ul class="sidebar-menu">
                <li><a href="gigs.php"><i class="fas fa-briefcase"></i> Gigs</a></li>
                <li><a href="shop.php"><i class="fas fa-shopping-bag"></i> Shop</a></li>
                <li><a href="customer_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <i class="fas fa-bars menu-toggle" id="menuToggle"></i>
                <h1>Your Cart</h1>
            </div>

            <?php if (isset($_GET['success']) || isset($success)) { echo "<div class='success'>" . (isset($success) ? $success : htmlspecialchars($_GET['success'])) . "</div>"; } ?>

            <div class="content-sections">
                <?php if (empty($cart_items)) { ?>
                    <p>Your cart is empty.</p>
                <?php } else { ?>
                    <form method="POST">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Type</th>
                                    <th>Price (LKR)</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th>Image</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $grand_total = 0; ?>
                                <?php foreach ($cart_items as $item) { ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['type']); ?></td>
                                        <td><?php echo number_format($item['price'], 2); ?></td>
                                        <td><input type="number" name="quantity[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="0"></td>
                                        <td><?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        <td>
                                            <?php if ($item['type'] === 'product' && !empty($item['product_image'])) { ?>
                                                <img src="<?php echo htmlspecialchars($item['product_image']); ?>" alt="Product Image">
                                            <?php } elseif ($item['type'] === 'gig' && !empty($item['gig_image'])) { ?>
                                                <img src="<?php echo htmlspecialchars($item['gig_image']); ?>" alt="Gig Image">
                                            <?php } else { ?>
                                                <div class="placeholder">No Image</div>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <?php $grand_total += $item['price'] * $item['quantity']; ?>
                                <?php } ?>
                                <tr>
                                    <td colspan="4" style="text-align: right;"><strong>Grand Total:</strong></td>
                                    <td><strong>LKR <?php echo number_format($grand_total, 2); ?></strong></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="button-group">
                            <button type="submit" name="update_cart">Update Cart <i class="fas fa-sync-alt"></i></button>
                            <button type="submit" name="checkout">Proceed to Checkout <i class="fas fa-credit-card"></i></button>
                        </div>
                    </form>
                <?php } ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const closeSidebar = document.getElementById('closeSidebar');
            const sidebar = document.querySelector('.sidebar');

            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });

            closeSidebar.addEventListener('click', function() {
                sidebar.classList.remove('active');
            });
        });
    </script>
</body>
</html>