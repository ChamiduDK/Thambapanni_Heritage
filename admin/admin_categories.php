<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$errors = [];
$success = '';

// Handle category addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $type = $_POST['type'];

    if (empty($name)) {
        $errors[] = "Category name is required.";
    } elseif (!in_array($type, ['gig', 'product'])) {
        $errors[] = "Invalid category type.";
    } else {
        $stmt = $connection->prepare("INSERT INTO categories (name, type) VALUES (?, ?)");
        if ($stmt === false) {
            $errors[] = "Prepare failed: " . $connection->error;
        } else {
            $stmt->bind_param("ss", $name, $type);
            if ($stmt->execute()) {
                $success = "Category added successfully!";
            } else {
                $errors[] = "Failed to add category: " . $connection->error;
            }
            $stmt->close();
        }
    }
}

// Search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build the SQL query
$sql = "SELECT id, name, type FROM categories WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $sql .= " AND name LIKE ?";
    $search_param = "%$search%";
    $params[] = $search_param;
    $types .= "s";
}

$sql .= " ORDER BY id ASC";

// Prepare and execute the query
$stmt = $connection->prepare($sql);
if ($stmt === false) {
    $errors[] = "Prepare failed: " . $connection->error;
    $categories = [];
} else {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?? [];
    $stmt->close();
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Categories - Thambapanni Heritage</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  :root {
    --saffron: #FF9933;
    --green: #00843D;
    --maroon: #8C2A3C;
    --gold: #FFC107;
    --white: #FFFFFF;
    --gray: #F5F5F5;
    --dark-maroon: #5C1A28;
    --light-saffron: #FFDAB3;
    --shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
  }

  body {
    font-family: 'Roboto', sans-serif;
    background: linear-gradient(135deg, var(--gray) 0%, var(--white) 100%);
    color: var(--dark-maroon);
    line-height: 1.6;
    overflow-x: hidden;
  }

  .wrapper {
    display: flex;
    min-height: 100vh;
  }

  .sidebar {
    width: 250px;
    background: var(--maroon);
    color: var(--white);
    position: fixed;
    top: 0;
    left: -250px;
    height: 100%;
    transition: var(--transition);
    z-index: 1000;
  }

  .sidebar.active {
    left: 0;
  }

  .sidebar-header {
    padding: 20px;
    background: var(--saffron);
    color: var(--dark-maroon);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .sidebar-header h3 {
    font-size: 1.5rem;
    font-weight: 700;
  }

  .close-sidebar {
    display: none;
    font-size: 1.5rem;
    cursor: pointer;
  }

  .sidebar-menu {
    list-style: none;
    padding: 10px 0;
  }

  .sidebar-menu li {
    padding: 15px 20px;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .sidebar-menu li:hover,
  .sidebar-menu li.active {
    background: var(--gold);
    color: var(--dark-maroon);
  }

  .sidebar-menu li a {
    color: inherit;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
  }

  .main-content {
    flex: 1;
    padding: 20px;
    margin-left: 0;
    transition: var(--transition);
  }

  .main-content.shifted {
    margin-left: 250px;
  }

  .header {
    text-align: center;
    margin-bottom: 20px;
  }

  .menu-toggle {
    display: none;
    font-size: 1.8rem;
    color: var(--maroon);
    cursor: pointer;
    position: absolute;
    top: 20px;
    left: 20px;
  }

  h1 {
    color: var(--maroon);
    font-size: 2rem;
    margin: 10px 0;
    text-transform: uppercase;
    letter-spacing: 1px;
  }

  p {
    color: var(--dark-maroon);
    font-size: 1rem;
    opacity: 0.8;
  }

  .error-message,
  .success-message {
    padding: 12px;
    margin: 15px 0;
    border-radius: 8px;
    text-align: center;
    font-size: 0.95rem;
    font-weight: 500;
  }

  .error-message {
    background: var(--light-saffron);
    color: var(--dark-maroon);
    border: 1px solid var(--saffron);
  }

  .success-message {
    background: var(--green);
    color: var(--white);
    border: 1px solid var(--green);
  }

  .content-sections {
    background: var(--white);
    border-radius: 15px;
    box-shadow: var(--shadow);
    padding: 20px;
  }

  .section {
    display: block;
  }

  h2 {
    color: var(--maroon);
    font-size: 1.8rem;
    margin-bottom: 20px;
  }

  .filter-form,
  .add-form {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  .filter-form input,
  .add-form input,
  .add-form select {
    padding: 12px;
    border: 2px solid var(--saffron);
    border-radius: 8px;
    font-size: 1rem;
    background: var(--white);
    transition: var(--transition);
    color: var(--dark-maroon);
    max-width: 200px;
  }

  .filter-form input:focus,
  .add-form input:focus,
  .add-form select:focus {
    border-color: var(--gold);
    outline: none;
    box-shadow: 0 0 5px rgba(255, 193, 7, 0.5);
  }

  .filter-form button,
  .add-form button {
    background: var(--green);
    color: var(--white);
    padding: 12px 25px;
    border: none;
    border-radius: 25px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
  }

  .filter-form button:hover,
  .add-form button:hover {
    background: var(--gold);
    color: var(--dark-maroon);
    transform: translateY(-2px);
  }

  .table-container {
    overflow-x: auto;
    margin-top: 20px;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
  }

  th,
  td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid var(--saffron);
  }

  th {
    background: var(--maroon);
    color: var(--white);
    font-weight: 500;
  }

  tr:hover {
    background: var(--light-saffron);
  }

  @media (min-width: 769px) {
    .sidebar {
      left: 0;
    }

    .main-content {
      margin-left: 250px;
    }

    .menu-toggle {
      display: none;
    }
  }

  @media (max-width: 768px) {
    .sidebar {
      width: 200px;
      left: -200px;
    }

    .sidebar.active {
      left: 0;
    }

    .close-sidebar {
      display: block;
    }

    .menu-toggle {
      display: block;
    }

    .main-content {
      padding: 15px;
    }

    h1 {
      font-size: 1.8rem;
    }

    h2 {
      font-size: 1.5rem;
    }

    .filter-form input,
    .add-form input,
    .add-form select {
      max-width: 150px;
      padding: 10px;
      font-size: 0.95rem;
    }

    .filter-form button,
    .add-form button {
      padding: 10px 20px;
      font-size: 0.95rem;
    }

    table {
      font-size: 0.85rem;
    }

    th,
    td {
      padding: 8px;
    }
  }

  @media (max-width: 480px) {
    .sidebar {
      width: 180px;
      left: -180px;
    }

    h1 {
      font-size: 1.5rem;
    }

    h2 {
      font-size: 1.3rem;
    }

    p {
      font-size: 0.9rem;
    }

    .filter-form input,
    .add-form input,
    .add-form select {
      max-width: 120px;
      padding: 8px;
      font-size: 0.9rem;
    }

    .filter-form button,
    .add-form button {
      padding: 8px 15px;
      font-size: 0.9rem;
    }

    table {
      font-size: 0.8rem;
    }

    th,
    td {
      padding: 6px;
    }
  }
  </style>
</head>

<body>
  <div class="wrapper">
    <nav class="sidebar">
      <div class="sidebar-header">
        <h3>Admin Panel</h3>
        <i class="fas fa-times close-sidebar" id="closeSidebar"></i>
      </div>
      <ul class="sidebar-menu">
        <li><a href="admin_dashboard.php"><i class="fas fa-users"></i> Sellers</a></li>
        <li><a href="admin_customers.php"><i class="fas fa-user-friends"></i> Customers</a></li>
        <li class="active" data-section="categories"><i class="fas fa-list"></i> Categories</li>
        <li><a href="admin_gigs.php"><i class="fas fa-briefcase"></i> Gigs</a></li>
        <li><a href="admin_products.php"><i class="fas fa-box"></i> Products</a></li>
        <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </nav>
    <div class="main-content">
      <div class="header">
        <i class="fas fa-bars menu-toggle" id="menuToggle"></i>
        <h1>Admin Categories</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</p>
      </div>
      <?php if (!empty($errors)) { ?>
      <div class="error-message"><?php echo implode('<br>', $errors); ?></div>
      <?php } ?>
      <?php if (!empty($success)) { ?>
      <div class="success-message"><?php echo $success; ?></div>
      <?php } ?>
      <div class="content-sections">
        <section id="categories" class="section">
          <h2>Categories</h2>

          <!-- Add Category Form -->
          <form class="add-form" method="POST" action="admin_categories.php">
            <input type="text" name="name" placeholder="Category Name" required>
            <select name="type" required>
              <option value="">Select Type</option>
              <option value="gig">Gig</option>
              <option value="product">Product</option>
            </select>
            <button type="submit" name="add_category">Add Category <i class="fas fa-plus"></i></button>
          </form>

          <!-- Search Form -->
          <form class="filter-form" method="GET" action="admin_categories.php">
            <input type="text" name="search" placeholder="Search by name"
              value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Filter <i class="fas fa-filter"></i></button>
          </form>

          <?php if (empty($categories)) { ?>
          <p>No categories found matching your criteria.</p>
          <?php } else { ?>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Type</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($categories as $category) { ?>
                <tr>
                  <td><?php echo htmlspecialchars($category['id']); ?></td>
                  <td><?php echo htmlspecialchars($category['name']); ?></td>
                  <td><?php echo htmlspecialchars($category['type']); ?></td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
          <?php } ?>
        </section>
      </div>
    </div>
  </div>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const closeSidebar = document.getElementById('closeSidebar');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    menuToggle.addEventListener('click', function() {
      sidebar.classList.toggle('active');
      mainContent.classList.toggle('shifted');
    });

    closeSidebar.addEventListener('click', function() {
      sidebar.classList.remove('active');
      mainContent.classList.remove('shifted');
    });
  });
  </script>
</body>

</html>