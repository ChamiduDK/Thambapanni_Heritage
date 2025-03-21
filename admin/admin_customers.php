<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$errors = [];
$success = '';

// Search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build the SQL query
$sql = "SELECT id, first_name, last_name, username, email, password, phone_number, address, created_at 
        FROM customers WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $sql .= " AND (username LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$sql .= " ORDER BY created_at DESC";

// Prepare and execute the query
$stmt = $connection->prepare($sql);
if ($stmt === false) {
    $errors[] = "Prepare failed: " . $connection->error;
    $customers = [];
} else {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $customers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?? [];
    $stmt->close();
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Customers - Thambapanni Heritage</title>
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

  .filter-form {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  .filter-form input {
    padding: 12px;
    border: 2px solid var(--saffron);
    border-radius: 8px;
    font-size: 1rem;
    background: var(--white);
    transition: var(--transition);
    color: var(--dark-maroon);
    max-width: 200px;
  }

  .filter-form input:focus {
    border-color: var(--gold);
    outline: none;
    box-shadow: 0 0 5px rgba(255, 193, 7, 0.5);
  }

  .filter-form button {
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

  .filter-form button:hover {
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

  .view-more-btn {
    background: var(--saffron);
    color: var(--dark-maroon);
    padding: 6px 12px;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    transition: var(--transition);
  }

  .view-more-btn:hover {
    background: var(--gold);
    transform: translateY(-2px);
  }

  .details {
    display: none;
    padding: 10px;
    background: var(--light-saffron);
    border-radius: 8px;
    margin-top: 5px;
  }

  .details.active {
    display: block;
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

    .filter-form input {
      max-width: 150px;
      padding: 10px;
      font-size: 0.95rem;
    }

    .filter-form button {
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

    .filter-form input {
      max-width: 120px;
      padding: 8px;
      font-size: 0.9rem;
    }

    .filter-form button {
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
    <!-- Sidebar -->
    <nav class="sidebar">
      <div class="sidebar-header">
        <h3>Admin Panel</h3>
        <i class="fas fa-times close-sidebar" id="closeSidebar"></i>
      </div>
      <ul class="sidebar-menu">
        <li><a href="admin_dashboard.php"><i class="fas fa-users"></i> Sellers</a></li>
        <li><a href="admin_customers.php"><i class="fas fa-user-friends"></i> Customers</a></li>
        <li><a href="admin_categories.php"><i class="fas fa-list"></i> Categories<a></a></li>
        <li><a href="admin_gigs.php"><i class="fas fa-briefcase"></i> Gigs</a></li>
        <li><a href="admin_products.php"><i class="fas fa-box"></i> Products</a></li>
        <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
      <div class="header">
        <i class="fas fa-bars menu-toggle" id="menuToggle"></i>
        <h1>Admin Customers</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</p>
      </div>

      <?php if (!empty($errors)) { ?>
      <div class="error-message"><?php echo implode('<br>', $errors); ?></div>
      <?php } ?>
      <?php if (!empty($success)) { ?>
      <div class="success-message"><?php echo $success; ?></div>
      <?php } ?>

      <div class="content-sections">
        <section id="customers" class="section">
          <h2>Registered Customers</h2>

          <!-- Search Form -->
          <form class="filter-form" method="GET" action="admin_customers.php">
            <input type="text" name="search" placeholder="Search by username or email"
              value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Filter <i class="fas fa-filter"></i></button>
          </form>

          <?php if (empty($customers)) { ?>
          <p>No customers found matching your criteria.</p>
          <?php } else { ?>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Username</th>
                  <th>Email</th>
                  <th>Details</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($customers as $customer) { ?>
                <tr>
                  <td><?php echo htmlspecialchars($customer['id']); ?></td>
                  <td><?php echo htmlspecialchars($customer['username']); ?></td>
                  <td><?php echo htmlspecialchars($customer['email']); ?></td>
                  <td>
                    <button class="view-more-btn" onclick="toggleDetails('details-<?php echo $customer['id']; ?>')">View
                      More</button>
                    <div id="details-<?php echo $customer['id']; ?>" class="details">
                      <p><strong>First Name:</strong> <?php echo htmlspecialchars($customer['first_name']); ?></p>
                      <p><strong>Last Name:</strong> <?php echo htmlspecialchars($customer['last_name']); ?></p>
                      <p><strong>Password:</strong> <?php echo htmlspecialchars($customer['password']); ?></p>
                      <p><strong>Phone:</strong> <?php echo htmlspecialchars($customer['phone_number'] ?? 'N/A'); ?></p>
                      <p><strong>Address:</strong> <?php echo htmlspecialchars($customer['address'] ?? 'N/A'); ?></p>
                      <p><strong>Created At:</strong> <?php echo htmlspecialchars($customer['created_at']); ?></p>
                    </div>
                  </td>
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

  function toggleDetails(id) {
    const details = document.getElementById(id);
    details.classList.toggle('active');
  }
  </script>
</body>

</html>