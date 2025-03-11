<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$errors = [];
$success = '';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_products.php");
    exit;
}

$product_id = $_GET['id'];

// Fetch product data
$stmt = $connection->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: admin_products.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category_id = $_POST['category_id'] ?? '';

    if (empty($name)) $errors[] = "Name is required";
    if (empty($description)) $errors[] = "Description is required";
    if ($price <= 0) $errors[] = "Price must be greater than 0";

    if (empty($errors)) {
        $stmt = $connection->prepare("UPDATE products SET name = ?, description = ?, price = ?, category_id = ? WHERE id = ?");
        $stmt->bind_param("ssdii", $name, $description, $price, $category_id, $product_id);
        
        if ($stmt->execute()) {
            $success = "Product updated successfully";
            $stmt->close();
            $stmt = $connection->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
        } else {
            $errors[] = "Error updating product: " . $connection->error;
        }
        $stmt->close();
    }
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Thambapanni Heritage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
        body { font-family: 'Roboto', sans-serif; background: linear-gradient(135deg, var(--gray) 0%, var(--white) 100%); color: var(--dark-maroon); line-height: 1.6; }
        .wrapper { min-height: 100vh; }
        .main-content { padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        h1 { color: var(--maroon); font-size: 2rem; margin: 10px 0; text-transform: uppercase; letter-spacing: 1px; }
        .error-message, .success-message { padding: 12px; margin: 15px 0; border-radius: 8px; text-align: center; font-size: 0.95rem; font-weight: 500; }
        .error-message { background: var(--light-saffron); color: var(--dark-maroon); border: 1px solid var(--saffron); }
        .success-message { background: var(--green); color: var(--white); border: 1px solid var(--green); }
        .content-sections { background: var(--white); border-radius: 15px; box-shadow: var(--shadow); padding: 20px; }
        form { display: flex; flex-direction: column; gap: 15px; max-width: 600px; margin: 0 auto; }
        label { font-weight: 500; margin-bottom: 5px; display: block; }
        input, textarea { 
            width: 100%; 
            padding: 10px; 
            border: 2px solid var(--saffron); 
            border-radius: 8px; 
            font-size: 1rem; 
            background: var(--white); 
            transition: var(--transition); 
            color: var(--dark-maroon); 
        }
        textarea { min-height: 100px; resize: vertical; }
        input:focus, textarea:focus { border-color: var(--gold); outline: none; box-shadow: 0 0 5px rgba(255, 193, 7, 0.5); }
        button, .back-btn { 
            padding: 12px 25px; 
            border: none; 
            border-radius: 25px; 
            font-size: 1rem; 
            font-weight: 500; 
            cursor: pointer; 
            transition: var(--transition); 
            text-decoration: none; 
            display: inline-block; 
            margin: 5px;
        }
        button { background: var(--green); color: var(--white); }
        button:hover { background: var(--gold); color: var(--dark-maroon); transform: translateY(-2px); }
        .back-btn { background: var(--saffron); color: var(--dark-maroon); }
        .back-btn:hover { background: var(--gold); transform: translateY(-2px); }
        @media (max-width: 768px) { 
            .main-content { padding: 15px; } 
            h1 { font-size: 1.8rem; } 
            input, textarea { font-size: 0.95rem; } 
            button, .back-btn { padding: 10px 20px; font-size: 0.95rem; } 
        }
        @media (max-width: 480px) { 
            h1 { font-size: 1.5rem; } 
            input, textarea { font-size: 0.9rem; } 
            button, .back-btn { padding: 8px 15px; font-size: 0.9rem; } 
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main-content">
            <div class="header">
                <h1>Edit Product</h1>
            </div>
            <?php if (!empty($errors)) { ?>
                <div class="error-message"><?php echo implode('<br>', $errors); ?></div>
            <?php } ?>
            <?php if (!empty($success)) { ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php } ?>
            <div class="content-sections">
                <form method="POST">
                    <div>
                        <label>Name:</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                    </div>
                    <div>
                        <label>Description:</label>
                        <textarea name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>
                    <div>
                        <label>Price:</label>
                        <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                    </div>
                    <div>
                        <label>Category ID:</label>
                        <input type="number" name="category_id" value="<?php echo htmlspecialchars($product['category_id']); ?>">
                    </div>
                    <div>
                        <button type="submit">Update Product</button>
                        <a href="admin_products.php" class="back-btn">Back to Products</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>