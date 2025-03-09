<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
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
$user_status = $user_status_result['status'] ?? 'pending'; // Default to 'pending' if null
$user_status_query->close();

function sanitize($data, $conn) {
    return htmlspecialchars(strip_tags(trim(mysqli_real_escape_string($conn, $data))));
}

$errors = [];
$success = '';
$user_id = null; // Initialize $user_id

// Fetch user_id and handle active status logic
$user_id_query = $connection->prepare("SELECT id FROM users WHERE username = ?");
if ($user_id_query === false) {
    die("Prepare failed: " . $connection->error);
}
$user_id_query->bind_param("s", $_SESSION['username']);
$user_id_query->execute();
$user_id_result = $user_id_query->get_result()->fetch_assoc();
$user_id = $user_id_result['id'] ?? null;
$user_id_query->close();

if (!$user_id) {
    $errors[] = "User not found.";
}

// Only fetch categories and handle submissions if status is active and user_id is valid
if ($user_status === 'active' && $user_id) {
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
}

// Fetch gigs, products, and requests (safe even if $user_id is null)
if ($user_id) {
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
} else {
    $gigs = [];
    $products = [];
    $requests = [];
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thambapanni Heritage - Dashboard</title>
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
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <i class="fas fa-bars menu-toggle" id="menuToggle"></i>
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
                <p>Manage your gigs and products showcasing Sri Lankan cultural craftsmanship.</p>
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
                        <p>Your account is currently <strong><?php echo htmlspecialchars($user_status); ?></strong>. You cannot add gigs or products until your account is activated by an admin.</p>
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

            // Sidebar navigation
            menuItems.forEach(item => {
                item.addEventListener('click', function() {
                    const sectionId = this.getAttribute('data-section');
                    
                    // Update active menu item
                    menuItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');

                    // Show selected section
                    sections.forEach(section => {
                        section.classList.remove('active');
                        if (section.id === sectionId) {
                            section.classList.add('active');
                        }
                    });

                    // Close sidebar on mobile after selection
                    if (window.innerWidth <= 768) {
                        sidebar.classList.remove('active');
                    }
                });
            });

            // Toggle sidebar on mobile
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