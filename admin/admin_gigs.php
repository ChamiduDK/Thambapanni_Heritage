<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$errors = [];
$success = '';

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $gig_id = $_GET['delete'];
    $stmt = $connection->prepare("DELETE FROM gigs WHERE id = ?");
    $stmt->bind_param("i", $gig_id);
    if ($stmt->execute()) {
        $success = "Gig deleted successfully";
        $stmt->close();
    } else {
        $errors[] = "Error deleting gig: " . $connection->error;
    }
}

// Search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build the SQL query
$sql = "SELECT g.id, g.user_id, g.title, g.description, g.price, g.created_at, g.category_id, g.image, u.username 
        FROM gigs g LEFT JOIN users u ON g.user_id = u.id WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $sql .= " AND (g.title LIKE ? OR u.username LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$sql .= " ORDER BY g.created_at DESC";

// Prepare and execute the query
$stmt = $connection->prepare($sql);
if ($stmt === false) {
    $errors[] = "Prepare failed: " . $connection->error;
    $gigs = [];
} else {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $gigs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?? [];
    $stmt->close();
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- [Previous head content remains unchanged] -->
    <style>
        /* [Previous styles remain unchanged] */
        /* Add new styles for edit/delete buttons */
        .action-btn { 
            padding: 6px 12px; 
            border: none; 
            border-radius: 25px; 
            cursor: pointer; 
            margin: 0 5px; 
            transition: var(--transition);
            font-size: 0.9rem;
        }
        .edit-btn { 
            background: var(--gold); 
            color: var(--dark-maroon);
        }
        .edit-btn:hover { 
            background: var(--saffron); 
            transform: translateY(-2px);
        }
        .delete-btn { 
            background: var(--maroon); 
            color: var(--white);
        }
        .delete-btn:hover { 
            background: var(--dark-maroon); 
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- [Sidebar content remains unchanged] -->
        <div class="main-content">
            <!-- [Header and messages remain unchanged] -->
            <div class="content-sections">
                <section id="gigs" class="section">
                    <h2>Gigs</h2>
                    <form class="filter-form" method="GET" action="admin_gigs.php">
                        <input type="text" name="search" placeholder="Search by title or username" value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit">Filter <i class="fas fa-filter"></i></button>
                    </form>
                    <?php if (empty($gigs)) { ?>
                        <p>No gigs found matching your criteria.</p>
                    <?php } else { ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Username</th>
                                        <th>Price</th>
                                        <th>Details</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($gigs as $gig) { ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($gig['id']); ?></td>
                                            <td><?php echo htmlspecialchars($gig['title']); ?></td>
                                            <td><?php echo htmlspecialchars($gig['username'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars(number_format($gig['price'], 2)); ?></td>
                                            <td>
                                                <button class="view-more-btn" onclick="toggleDetails('details-<?php echo $gig['id']; ?>')">View More</button>
                                                <div id="details-<?php echo $gig['id']; ?>" class="details">
                                                    <p><strong>User ID:</strong> <?php echo htmlspecialchars($gig['user_id']); ?></p>
                                                    <p><strong>Description:</strong> <?php echo htmlspecialchars($gig['description']); ?></p>
                                                    <p><strong>Created At:</strong> <?php echo htmlspecialchars($gig['created_at']); ?></p>
                                                    <p><strong>Category ID:</strong> <?php echo htmlspecialchars($gig['category_id'] ?? 'N/A'); ?></p>
                                                    <p><strong>Image:</strong> 
                                                        <?php if (!empty($gig['image'])) { ?>
                                                            <img src="../uploads/<?php echo htmlspecialchars($gig['image']); ?>" alt="Gig Image">
                                                        <?php } else { ?>
                                                            N/A
                                                        <?php } ?>
                                                    </p>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="edit_gig.php?id=<?php echo $gig['id']; ?>" class="action-btn edit-btn">Edit</a>
                                                <button class="action-btn delete-btn" onclick="if(confirm('Are you sure you want to delete this gig?')) window.location.href='admin_gigs.php?delete=<?php echo $gig['id']; ?>'">Delete</button>
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