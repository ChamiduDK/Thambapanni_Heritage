<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$errors = [];
$success = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $user_id = intval($_POST['user_id']);
    $status = $_POST['status'];

    $stmt = $connection->prepare("UPDATE users SET status = ? WHERE id = ?");
    if ($stmt === false) {
        $errors[] = "Prepare failed: " . $connection->error;
    } else {
        $stmt->bind_param("si", $status, $user_id);
        if ($stmt->execute()) {
            $success = "User status updated successfully!";
        } else {
            $errors[] = "Failed to update status: " . $connection->error;
        }
        $stmt->close();
    }
}

// Search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$gender_filter = isset($_GET['gender']) ? $_GET['gender'] : '';

// Build the SQL query
$sql = "SELECT id, first_name, last_name, username, email, password, dob, gender, phone_number, address, 
               nic_number, nic_front, nic_back, about, created_at, chatra_operator_id, jivochat_agent_id, status 
        FROM users WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $sql .= " AND (username LIKE ? OR email LIKE ? OR nic_number LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if (!empty($status_filter)) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($gender_filter)) {
    $sql .= " AND gender = ?";
    $params[] = $gender_filter;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

// Prepare and execute the query
$stmt = $connection->prepare($sql);
if ($stmt === false) {
    $errors[] = "Prepare failed: " . $connection->error;
    $sellers = [];
} else {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $sellers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?? [];
    $stmt->close();
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Thambapanni Heritage</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css">
  <link rel="JavaScript" href="js/scripts.js">
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
        <li class="active" data-section="sellers"><i class="fas fa-users"></i> Sellers</li>
        <li><a href="admin_customers.php"><i class="fas fa-user-friends"></i> Customers</a></li>
        <li><a href="admin_categories.php" data-section="categories"><i class="fas fa-list"></i> Categories</a></li>
        <li><a href="admin_gigs.php"><i class="fas fa-briefcase"></i> Gigs</a></li>
        <li><a href="admin_products.php"><i class="fas fa-box"></i> Products</a></li>
        <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
      <div class="header">
        <i class="fas fa-bars menu-toggle" id="menuToggle"></i>
        <h1>Admin Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</p>
      </div>

      <?php if (!empty($errors)) { ?>
      <div class="error-message"><?php echo implode('<br>', $errors); ?></div>
      <?php } ?>
      <?php if (!empty($success)) { ?>
      <div class="success-message"><?php echo $success; ?></div>
      <?php } ?>

      <div class="content-sections">
        <section id="sellers" class="section">
          <h2>Registered Sellers</h2>

          <!-- Search and Filter Form -->
          <form class="filter-form" method="GET" action="admin_dashboard.php">
            <input type="text" name="search" placeholder="Search by username, email, or NIC"
              value="<?php echo htmlspecialchars($search); ?>">
            <select name="status">
              <option value="">All Statuses</option>
              <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
              <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
              <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended
              </option>
            </select>
            <!--<select name="gender">-->
            <!--    <option value="">All Genders</option>-->
            <!--    <option value="Male" <?php echo $gender_filter === 'Male' ? 'selected' : ''; ?>>Male</option>-->
            <!--    <option value="Female" <?php echo $gender_filter === 'Female' ? 'selected' : ''; ?>>Female</option>-->
            <!--    <option value="Other" <?php echo $gender_filter === 'Other' ? 'selected' : ''; ?>>Other</option>-->
            <!--</select>-->
            <button type="submit">Filter <i class="fas fa-filter"></i></button>
          </form>

          <?php if (empty($sellers)) { ?>
          <p>No sellers found matching your criteria.</p>
          <?php } else { ?>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Username</th>
                  <th>Email</th>
                  <th>Status</th>
                  <th>Action</th>
                  <th>Details</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($sellers as $seller) { ?>
                <tr>
                  <td><?php echo htmlspecialchars($seller['id']); ?></td>
                  <td><?php echo htmlspecialchars($seller['username']); ?></td>
                  <td><?php echo htmlspecialchars($seller['email']); ?></td>
                  <td><?php echo htmlspecialchars($seller['status']); ?></td>
                  <td>
                    <form action="admin_dashboard.php" method="POST">
                      <input type="hidden" name="user_id" value="<?php echo $seller['id']; ?>">
                      <select name="status" onchange="this.form.submit()">
                        <option value="active" <?php echo $seller['status'] === 'active' ? 'selected' : ''; ?>>Active
                        </option>
                        <option value="pending" <?php echo $seller['status'] === 'pending' ? 'selected' : ''; ?>>Pending
                        </option>
                        <option value="suspended" <?php echo $seller['status'] === 'suspended' ? 'selected' : ''; ?>>
                          Suspended</option>
                      </select>
                      <input type="hidden" name="update_status" value="1">
                    </form>
                  </td>
                  <td>
                    <button class="view-more-btn" onclick="toggleDetails('details-<?php echo $seller['id']; ?>')">View
                      More</button>
                    <div id="details-<?php echo $seller['id']; ?>" class="details">
                      <p><strong>First Name:</strong> <?php echo htmlspecialchars($seller['first_name']); ?></p>
                      <p><strong>Last Name:</strong> <?php echo htmlspecialchars($seller['last_name']); ?></p>
                      <p><strong>Password:</strong> <?php echo htmlspecialchars($seller['password']); ?></p>
                      <p><strong>DOB:</strong> <?php echo htmlspecialchars($seller['dob']); ?></p>
                      <p><strong>Gender:</strong> <?php echo htmlspecialchars($seller['gender']); ?></p>
                      <p><strong>Phone:</strong> <?php echo htmlspecialchars($seller['phone_number']); ?></p>
                      <p><strong>Address:</strong> <?php echo htmlspecialchars($seller['address']); ?></p>
                      <p><strong>NIC Number:</strong> <?php echo htmlspecialchars($seller['nic_number']); ?></p>
                      <p><strong>NIC Front:</strong>
                        <?php 
    $nic_front_path = "" . htmlspecialchars($seller['nic_front']);
    if (!empty($seller['nic_front']) && file_exists($nic_front_path)) { ?>
                        <a href="<?php echo $nic_front_path; ?>" target="_blank">
                          <img src="<?php echo $nic_front_path; ?>" alt="NIC Front" style="max-width: 100px;">
                        </a>
                        <?php } else { ?>
                        N/A <?php echo !empty($seller['nic_front']) ? "(File '$nic_front_path' missing)" : ""; ?>
                        <?php } ?>
                      </p>
                      <p><strong>NIC Back:</strong>
                        <?php 
    $nic_back_path = "" . htmlspecialchars($seller['nic_back']);
    if (!empty($seller['nic_back']) && file_exists($nic_back_path)) { ?>
                        <a href="<?php echo $nic_back_path; ?>" target="_blank">
                          <img src="<?php echo $nic_back_path; ?>" alt="NIC Back" style="max-width: 100px;">
                        </a>
                        <?php } else { ?>
                        N/A <?php echo !empty($seller['nic_back']) ? "(File '$nic_back_path' missing)" : ""; ?>
                        <?php } ?>
                      </p>
                      <p><strong>About:</strong> <?php echo htmlspecialchars($seller['about']); ?></p>
                      <p><strong>Created At:</strong> <?php echo htmlspecialchars($seller['created_at']); ?></p>
                      <p><strong>Chatra ID:</strong>
                        <?php echo htmlspecialchars($seller['chatra_operator_id'] ?? 'N/A'); ?></p>
                      <p><strong>Jivochat ID:</strong>
                        <?php echo htmlspecialchars($seller['jivochat_agent_id'] ?? 'N/A'); ?></p>
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
</body>
</html>