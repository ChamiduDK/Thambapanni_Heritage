<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!file_exists('db_connect.php')) {
    die("Error: db_connect.php not found. Please upload it to the server.");
}
require_once 'db_connect.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Check user status
$user_status_query = $connection->prepare("SELECT status FROM users WHERE username = ?");
if ($user_status_query === false) {
    die("Prepare failed: " . $connection->error);
}
$user_status_query->bind_param("s", $_SESSION['username']);
$user_status_query->execute();
$user_status_result = $user_status_query->get_result()->fetch_assoc();
$user_status = $user_status_result['status'] ?? 'pending';
$user_status_query->close();

function sanitize($data, $conn) {
    return htmlspecialchars(strip_tags(trim(mysqli_real_escape_string($conn, $data))));
}

$errors = [];
$success = '';
$user_id = null;

// Fetch user_id
$user_id_query = $connection->prepare("SELECT id FROM users WHERE username = ?");
if ($user_id_query === false) {
    $errors[] = "Prepare failed: " . $connection->error;
} else {
    $user_id_query->bind_param("s", $_SESSION['username']);
    $user_id_query->execute();
    $user_id_result = $user_id_query->get_result()->fetch_assoc();
    $user_id = $user_id_result['id'] ?? null;
    $user_id_query->close();
}

if (!$user_id) {
    $errors[] = "User not found.";
}

// Fetch data only if user_id is valid
if ($user_id) {
    if ($user_status === 'active') {
        $gig_categories = $connection->query("SELECT * FROM categories WHERE type = 'gig'")->fetch_all(MYSQLI_ASSOC) ?? [];
        $product_categories = $connection->query("SELECT * FROM categories WHERE type = 'product'")->fetch_all(MYSQLI_ASSOC) ?? [];

        // Handle gig submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_gig'])) {
            $title = sanitize($_POST['title'], $connection);
            $description = sanitize($_POST['description'], $connection);
            $price = floatval($_POST['price']);
            $category_id = intval($_POST['category_id']);
            $upload_dir = 'uploads/';
            $image = '';

            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                $errors[] = "Failed to create upload directory.";
            } elseif (!is_writable($upload_dir)) {
                $errors[] = "Upload directory is not writable.";
            }

            if (empty($title) || empty($description) || $price <= 0 || $category_id <= 0) {
                $errors[] = "All gig fields are required, price must be positive, and a category must be selected.";
            } else {
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $image = $upload_dir . uniqid() . '.' . $file_ext;
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $image)) {
                        $errors[] = "Failed to upload gig image.";
                    }
                }

                if (empty($errors)) {
                    $stmt = $connection->prepare("INSERT INTO gigs (user_id, title, description, price, category_id, image) VALUES (?, ?, ?, ?, ?, ?)");
                    if ($stmt === false) {
                        $errors[] = "Prepare failed: " . $connection->error;
                    } else {
                        $stmt->bind_param("issdis", $user_id, $title, $description, $price, $category_id, $image);
                        if ($stmt->execute()) {
                            $success = "Gig added successfully!";
                        } else {
                            $errors[] = "Failed to add gig: " . $connection->error;
                        }
                        $stmt->close();
                    }
                }
            }
        }

        // Handle product submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
            $name = sanitize($_POST['name'], $connection);
            $description = sanitize($_POST['description'], $connection);
            $price = floatval($_POST['price']);
            $category_id = intval($_POST['category_id']);
            $upload_dir = 'uploads/';
            $image = '';

            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                $errors[] = "Failed to create upload directory.";
            } elseif (!is_writable($upload_dir)) {
                $errors[] = "Upload directory is not writable.";
            }

            if (empty($name) || empty($description) || $price <= 0 || $category_id <= 0) {
                $errors[] = "All product fields are required, price must be positive, and a category must be selected.";
            } else {
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $image = $upload_dir . uniqid() . '.' . $file_ext;
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $image)) {
                        $errors[] = "Failed to upload product image.";
                    }
                }

                if (empty($errors)) {
                    $stmt = $connection->prepare("INSERT INTO products (user_id, name, description, price, image, category_id) VALUES (?, ?, ?, ?, ?, ?)");
                    if ($stmt === false) {
                        $errors[] = "Prepare failed: " . $connection->error;
                    } else {
                        $stmt->bind_param("issdsi", $user_id, $name, $description, $price, $image, $category_id);
                        if ($stmt->execute()) {
                            $success = "Product added successfully!";
                        } else {
                            $errors[] = "Failed to add product: " . $connection->error;
                        }
                        $stmt->close();
                    }
                }
            }
        }

        // Handle gig response
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond_request'])) {
            $request_id = intval($_POST['request_id']);
            $response = sanitize($_POST['seller_response'], $connection);
            $price = floatval($_POST['price']);

            if (!empty($response) && $price > 0) {
                $stmt = $connection->prepare("UPDATE gig_requests SET seller_response = ?, price = ?, status = 'responded' WHERE id = ? AND gig_id IN (SELECT id FROM gigs WHERE user_id = ?)");
                if ($stmt === false) {
                    $errors[] = "Prepare failed: " . $connection->error;
                } else {
                    $stmt->bind_param("sdii", $response, $price, $request_id, $user_id);
                    if ($stmt->execute()) {
                        $success = "Response sent successfully!";
                    } else {
                        $errors[] = "Failed to send response: " . $connection->error;
                    }
                    $stmt->close();
                }
            } else {
                $errors[] = "Response and price are required.";
            }
        }

        // Handle order status update
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
            $order_id = intval($_POST['order_id']);
            $new_status = sanitize($_POST['status'], $connection);
            $delivery_info = isset($_POST['delivery_info']) ? sanitize($_POST['delivery_info'], $connection) : '';

            // Define valid statuses and their sequence
            $status_sequence = [
                '' => 'confirmed',         // Handle empty/NULL status
                'pending' => 'confirmed',
                'paid' => 'confirmed',
                'confirmed' => 'packing',
                'packing' => 'delivered'
            ];
            $valid_statuses = ['confirmed', 'packing', 'delivered'];

            // Fetch current status, default to '' if NULL or not set
            $current_status_query = $connection->prepare("SELECT status FROM orders WHERE id = ? AND id IN (SELECT order_id FROM order_items WHERE seller_username = ?)");
            if ($current_status_query === false) {
                $errors[] = "Prepare failed: " . $connection->error;
            } else {
                $current_status_query->bind_param("is", $order_id, $_SESSION['username']);
                $current_status_query->execute();
                $result = $current_status_query->get_result()->fetch_assoc();
                $current_status = $result['status'] ?? ''; // Default to empty string if NULL
                $current_status_query->close();

                // Validate new status
                if (!in_array($new_status, $valid_statuses)) {
                    $errors[] = "Invalid status selected: '$new_status'.";
                } elseif ($new_status === 'delivered' && empty($delivery_info)) {
                    $errors[] = "Delivery info is required when marking an order as Delivered.";
                } elseif (!isset($status_sequence[$current_status]) || $status_sequence[$current_status] !== $new_status) {
                    $errors[] = "Invalid status transition: Cannot move from '$current_status' to '$new_status'.";
                } else {
                    $update_stmt = $connection->prepare("UPDATE orders SET status = ?, delivery_info = ? WHERE id = ? AND id IN (SELECT order_id FROM order_items WHERE seller_username = ?)");
                    if ($update_stmt === false) {
                        $errors[] = "Prepare failed: " . $connection->error;
                    } else {
                        $update_stmt->bind_param("ssis", $new_status, $delivery_info, $order_id, $_SESSION['username']);
                        if ($update_stmt->execute()) {
                            $success = "Order #$order_id status updated to '" . ucfirst($new_status) . "' successfully!";
                            // Refresh orders
                            $refresh_stmt = $connection->prepare("
                                SELECT o.id, o.customer_id, o.total_amount, o.status, o.created_at, o.delivery_info, u.username AS customer_name,
                                       GROUP_CONCAT(CONCAT(oi.item_name, ' (Qty: ', oi.quantity, ')') SEPARATOR ', ') AS items
                                FROM orders o
                                JOIN order_items oi ON o.id = oi.order_id
                                JOIN users u ON o.customer_id = u.id
                                WHERE oi.seller_username = ?
                                GROUP BY o.id, o.customer_id, o.total_amount, o.status, o.created_at, o.delivery_info, u.username
                            ");
                            $refresh_stmt->bind_param("s", $_SESSION['username']);
                            $refresh_stmt->execute();
                            $orders = $refresh_stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?? [];
                            $refresh_stmt->close();
                        } else {
                            $errors[] = "Failed to update order status: " . $connection->error;
                        }
                        $update_stmt->close();
                    }
                }
            }
        }
    }

    // Fetch gigs, products, requests, and orders
    $gigs = $connection->query("SELECT g.*, c.name AS category_name FROM gigs g LEFT JOIN categories c ON g.category_id = c.id WHERE g.user_id = $user_id")->fetch_all(MYSQLI_ASSOC) ?? [];
    $products = $connection->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.user_id = $user_id")->fetch_all(MYSQLI_ASSOC) ?? [];

    $stmt = $connection->prepare("SELECT gr.*, g.title, u.username AS customer_name FROM gig_requests gr JOIN gigs g ON gr.gig_id = g.id JOIN users u ON gr.customer_id = u.id WHERE g.user_id = ?");
    if ($stmt === false) {
        $errors[] = "Prepare failed: " . $connection->error;
        $requests = [];
    } else {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?? [];
        $stmt->close();
    }

    // Fetch orders with explicit status check
    $stmt = $connection->prepare("
        SELECT o.id, o.customer_id, o.total_amount, o.status, o.created_at, o.delivery_info, u.username AS customer_name,
               GROUP_CONCAT(CONCAT(oi.item_name, ' (Qty: ', oi.quantity, ')') SEPARATOR ', ') AS items
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN users u ON o.customer_id = u.id
        WHERE oi.seller_username = ?
        GROUP BY o.id, o.customer_id, o.total_amount, o.status, o.created_at, o.delivery_info, u.username
    ");
    if ($stmt === false) {
        $errors[] = "Order query prepare failed: " . $connection->error;
        $orders = [];
    } else {
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();
        $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?? [];
        if (empty($orders)) {
            $errors[] = "No orders found for seller '" . htmlspecialchars($_SESSION['username']) . "'.";
        }
        $stmt->close();
    }
} else {
    $gigs = [];
    $products = [];
    $requests = [];
    $orders = [];
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thambapanni Heritage - Seller Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/sd-styles.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h3>Dashboard</h3>
                <i class="fas fa-times close-sidebar" id="closeSidebar"></i>
            </div>
            <ul class="sidebar-menu">
                <?php if ($user_status === 'active') { ?>
                    <li class="active" data-section="add-gig"><i class="fas fa-plus-circle"></i> Add Gig</li>
                    <li data-section="add-product"><i class="fas fa-shopping-bag"></i> Add Product</li>
                <?php } ?>
                <li data-section="your-gigs"><i class="fas fa-briefcase"></i> Your Gigs</li>
                <li data-section="your-products"><i class="fas fa-box"></i> Your Products</li>
                <li data-section="gig-requests"><i class="fas fa-envelope"></i> Gig Requests</li>
                <li data-section="your-orders"><i class="fas fa-shopping-cart"></i> Your Orders</li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <i class="fas fa-bars menu-toggle" id="menuToggle"></i>
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
                <p>Manage your gigs, products, and orders showcasing Sri Lankan cultural craftsmanship.</p>
            </div>

            <?php if (!empty($errors)) { ?>
                <div class="error-message"><?php echo implode('<br>', $errors); ?></div>
            <?php } ?>
            <?php if (!empty($success)) { ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php } ?>

            <!-- Sections -->
            <div class="content-sections">
                <?php if ($user_status === 'active') { ?>
                    <section id="add-gig" class="section active">
                        <h2>Add a Gig</h2>
                        <form action="dashboard.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="add_gig" value="1">
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category_id" required>
                                    <option value="">Select a category</option>
                                    <?php foreach ($gig_categories as $cat) { ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="title" required>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Price (LKR)</label>
                                <input type="number" name="price" required step="0.01" min="0">
                            </div>
                            <div class="form-group">
                                <label>Image</label>
                                <input type="file" name="image" accept="image/*">
                            </div>
                            <button type="submit">Add Gig <i class="fas fa-plus"></i></button>
                        </form>
                    </section>

                    <section id="add-product" class="section">
                        <h2>Add a Product</h2>
                        <form action="dashboard.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="add_product" value="1">
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category_id" required>
                                    <option value="">Select a category</option>
                                    <?php foreach ($product_categories as $cat) { ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" name="name" required>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Price (LKR)</label>
                                <input type="number" name="price" required step="0.01" min="0">
                            </div>
                            <div class="form-group">
                                <label>Image</label>
                                <input type="file" name="image" accept="image/*">
                            </div>
                            <button type="submit">Add Product <i class="fas fa-plus"></i></button>
                        </form>
                    </section>
                <?php } else { ?>
                    <section id="account-status" class="section active">
                        <h2>Account Status</h2>
                        <p>Your account is currently <strong><?php echo htmlspecialchars($user_status); ?></strong>. You cannot add gigs, products, or manage orders until your account is activated by an admin.</p>
                    </section>
                <?php } ?>

                <section id="your-gigs" class="section <?php echo $user_status !== 'active' ? 'active' : ''; ?>">
                    <h2>Your Gigs</h2>
                    <?php if (empty($gigs)) { ?>
                        <p>No gigs added yet.</p>
                    <?php } else { ?>
                        <ul class="item-list">
                            <?php foreach ($gigs as $gig) { ?>
                                <li>
                                    <strong><?php echo htmlspecialchars($gig['title']); ?></strong> - LKR <?php echo number_format($gig['price'], 2); ?><br>
                                    Category: <?php echo htmlspecialchars($gig['category_name']); ?><br>
                                    <?php echo htmlspecialchars($gig['description']); ?><br>
                                    <?php if (!empty($gig['image'])) { ?>
                                        <img src="<?php echo htmlspecialchars($gig['image']); ?>" alt="Gig Image">
                                    <?php } ?>
                                    <br><small>Posted on: <?php echo $gig['created_at']; ?></small>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } ?>
                </section>

                <section id="your-products" class="section">
                    <h2>Your Products</h2>
                    <?php if (empty($products)) { ?>
                        <p>No products added yet.</p>
                    <?php } else { ?>
                        <ul class="item-list">
                            <?php foreach ($products as $product) { ?>
                                <li>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong> - LKR <?php echo number_format($product['price'], 2); ?><br>
                                    Category: <?php echo htmlspecialchars($product['category_name']); ?><br>
                                    <?php echo htmlspecialchars($product['description']); ?><br>
                                    <?php if (!empty($product['image'])) { ?>
                                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image">
                                    <?php } ?>
                                    <br><small>Posted on: <?php echo $product['created_at']; ?></small>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } ?>
                </section>

                <section id="gig-requests" class="section">
                    <h2>Gig Requests</h2>
                    <?php if (empty($requests)) { ?>
                        <p>No gig requests yet.</p>
                    <?php } else { ?>
                        <ul class="item-list">
                            <?php foreach ($requests as $request) { ?>
                                <li>
                                    <strong>Gig:</strong> <?php echo htmlspecialchars($request['title']); ?><br>
                                    <strong>Customer:</strong> <?php echo htmlspecialchars($request['customer_name']); ?><br>
                                    <strong>Requirements:</strong> <?php echo htmlspecialchars($request['requirements']); ?><br>
                                    <strong>Status:</strong> <?php echo htmlspecialchars($request['status']); ?><br>
                                    <div class="request-response <?php echo $request['status'] === 'responded' ? 'sent' : ''; ?>">
                                        <?php if ($request['status'] === 'responded') { ?>
                                            <p><strong>Response:</strong> <?php echo htmlspecialchars($request['seller_response']); ?></p>
                                            <p><strong>Price:</strong> LKR <?php echo number_format($request['price'], 2); ?></p>
                                        <?php } elseif ($user_status === 'active') { ?>
                                            <form method="POST">
                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                <div class="form-group">
                                                    <label>Response</label>
                                                    <textarea name="seller_response" required></textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label>Price (LKR)</label>
                                                    <input type="number" name="price" required step="0.01" min="0">
                                                </div>
                                                <button type="submit" name="respond_request">Send Response <i class="fas fa-paper-plane"></i></button>
                                            </form>
                                        <?php } ?>
                                    </div>
                                    <small>Requested on: <?php echo $request['created_at']; ?></small>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } ?>
                </section>

                <section id="your-orders" class="section">
                    <h2>Your Orders</h2>
                    <?php if (empty($orders)) { ?>
                        <p>No orders found. Add items to your shop or gigs, and have a customer place an order via cart.php to see them here.</p>
                    <?php } else { ?>
                        <ul class="item-list">
                            <?php foreach ($orders as $order) { ?>
                                <li>
                                    <strong>Order ID:</strong> #<?php echo $order['id']; ?><br>
                                    <strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                    <strong>Items:</strong> <?php echo htmlspecialchars($order['items']); ?><br>
                                    <strong>Total:</strong> LKR <?php echo number_format($order['total_amount'], 2); ?><br>
                                    <strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($order['status'] ?? 'Pending')); ?><br>
                                    <?php if (!empty($order['delivery_info'])) { ?>
                                        <strong>Delivery Info:</strong> <?php echo htmlspecialchars($order['delivery_info']); ?><br>
                                    <?php } ?>
                                    <small>Ordered on: <?php echo $order['created_at']; ?></small>

                                    <?php if ($user_status === 'active' && ($order['status'] ?? '') !== 'delivered') { ?>
                                        <form method="POST" class="update-status-form">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <div class="form-group">
                                                <label>Update Status</label>
                                                <select name="status" onchange="toggleDeliveryInfo(this, <?php echo $order['id']; ?>)">
                                                    <option value="">Select Next Step</option>
                                                    <?php
                                                    $current_status = strtolower($order['status'] ?? '');
                                                    if ($current_status === '' || $current_status === 'pending' || $current_status === 'paid') {
                                                        echo '<option value="confirmed">Confirm</option>';
                                                    } elseif ($current_status === 'confirmed') {
                                                        echo '<option value="packing">Pack</option>';
                                                    } elseif ($current_status === 'packing') {
                                                        echo '<option value="delivered">Deliver</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group delivery-info" id="delivery-info-<?php echo $order['id']; ?>" style="display: none;">
                                                <label>Delivery Info (Required for Deliver)</label>
                                                <input type="text" name="delivery_info" placeholder="e.g., Tracking Number SL123456" required>
                                            </div>
                                            <button type="submit" name="update_order_status">Update Status <i class="fas fa-sync-alt"></i></button>
                                        </form>
                                    <?php } elseif (($order['status'] ?? '') === 'delivered') { ?>
                                        <p><em>Order delivered successfully.</em></p>
                                    <?php } ?>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } ?>
                </section>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuItems = document.querySelectorAll('.sidebar-menu li[data-section]');
            const sections = document.querySelectorAll('.section');
            const menuToggle = document.getElementById('menuToggle');
            const closeSidebar = document.getElementById('closeSidebar');
            const sidebar = document.querySelector('.sidebar');

            menuItems.forEach(item => {
                item.addEventListener('click', function() {
                    const sectionId = this.getAttribute('data-section');
                    menuItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    sections.forEach(section => {
                        section.classList.remove('active');
                        if (section.id === sectionId) {
                            section.classList.add('active');
                        }
                    });
                    if (window.innerWidth <= 768) {
                        sidebar.classList.remove('active');
                    }
                });
            });

            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });

            closeSidebar.addEventListener('click', function() {
                sidebar.classList.remove('active');
            });
        });

        function toggleDeliveryInfo(select, orderId) {
            const deliveryInfoDiv = document.getElementById('delivery-info-' + orderId);
            if (select.value === 'delivered') {
                deliveryInfoDiv.style.display = 'block';
                deliveryInfoDiv.querySelector('input').setAttribute('required', 'required');
            } else {
                deliveryInfoDiv.style.display = 'none';
                deliveryInfoDiv.querySelector('input').removeAttribute('required');
            }
        }
    </script>

    <style>
        .error-message, .success-message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
        }
        .item-list {
            list-style: none;
            padding: 0;
        }
        .item-list li {
            background: #fff;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .update-status-form {
            margin-top: 10px;
        }
        .update-status-form select, .update-status-form input[type="text"] {
            padding: 5px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        .update-status-form select {
            width: 150px;
        }
        .update-status-form button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        .update-status-form button:hover {
            background-color: #218838;
        }
        .delivery-info {
            margin-top: 10px;
        }
        .delivery-info label {
            font-weight: bold;
            color: #333;
        }
        .delivery-info input {
            width: 250px;
        }
    </style>
</body>
</html>