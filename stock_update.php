<?php
// stock_update.php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';

// Check if a product ID is provided
if (!isset($_GET['id']) && !isset($_POST['product_id'])) {
    die("Product ID not specified.");
}
$product_id = isset($_GET['id']) ? $_GET['id'] : $_POST['product_id'];

// Handle the Form Submission (Stock Update)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_stock'])) {
    $movement_type = $_POST['movement_type'];
    $quantity_changed = (int)$_POST['quantity_changed'];
    $remarks = trim($_POST['remarks']);
    $user_id = $_SESSION['user_id'];

    // Determine the actual quantity to add or subtract
    $adjustment = 0;
    if ($movement_type === 'received') {
        $adjustment = $quantity_changed;
    } elseif ($movement_type === 'sold' || $movement_type === 'damaged') {
        $adjustment = -$quantity_changed;
    } elseif ($movement_type === 'adjusted') {
        $adjustment = $quantity_changed;
    }

    try {
        // Begin a transaction to ensure both tables update safely
        $pdo->beginTransaction();

        // 1. Update the current quantity in the products table
        $updateStmt = $pdo->prepare("UPDATE products SET current_quantity = current_quantity + ? WHERE product_id = ?");
        $updateStmt->execute([$adjustment, $product_id]);

        // 2. Insert the record into the stock_movement audit table
        $insertStmt = $pdo->prepare("INSERT INTO stock_movement (product_id, user_id, movement_type, quantity_changed, remarks) VALUES (?, ?, ?, ?, ?)");
        $insertStmt->execute([$product_id, $user_id, $movement_type, $quantity_changed, $remarks]);

        // Commit the transaction
        $pdo->commit();
        $message = "Stock updated successfully!";
    } catch (PDOException $e) {
        // Rollback the transaction if something fails
        $pdo->rollBack();
        $error = "Error updating stock: " . $e->getMessage();
    }
}

// Fetch current product details to display on the form
$stmt = $pdo->prepare("SELECT p.sku, p.product_name, p.current_quantity, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    die("Product not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Stock - Smart Store Checker</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f9; padding: 20px; margin: 0; }
        .container { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .product-info { background: #e9ecef; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .product-info p { margin: 5px 0; }
        label { display: block; margin-top: 15px; font-weight: bold; }
        input[type="number"], select, input[type="text"] { width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { margin-top: 20px; padding: 12px; width: 100%; background-color: #28a745; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
        button:hover { background-color: #218838; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #555; text-decoration: none; }
        .alert-success { color: #155724; background-color: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-error { color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="container">
    <a href="search.php" class="back-link">← Back to Search</a>
    <h2>Update Stock Quantity</h2>

    <?php if ($message): ?>
        <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="product-info">
        <p><strong>SKU:</strong> <?php echo htmlspecialchars($product['sku']); ?></p>
        <p><strong>Product Name:</strong> <?php echo htmlspecialchars($product['product_name']); ?></p>
        <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name']); ?></p>
        <p><strong>Current Quantity:</strong> <span style="font-size: 1.2em; font-weight: bold; color: #004085;"><?php echo htmlspecialchars($product['current_quantity']); ?></span></p>
    </div>

    <form method="POST" action="stock_update.php">
        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">

        <label for="movement_type">Type of Movement:</label>
        <select name="movement_type" id="movement_type" required>
            <option value="">Select an action...</option>
            <option value="received">Received (Adds to stock)</option>
            <option value="sold">Sold (Subtracts from stock)</option>
            <option value="damaged">Damaged (Subtracts from stock)</option>
            <option value="adjusted">Adjusted (Use +/- for corrections)</option>
        </select>

        <label for="quantity_changed">Quantity:</label>
        <input type="number" name="quantity_changed" id="quantity_changed" placeholder="Enter quantity (e.g., 5)" required>
        <small style="color: #666;">For 'Sold' or 'Damaged', just enter a positive number (e.g., 5). The system will subtract it automatically.</small>

        <label for="remarks">Remarks (Optional):</label>
        <input type="text" name="remarks" id="remarks" placeholder="Enter reason, supplier info, or receipt number">

        <button type="submit" name="update_stock">Update Inventory</button>
    </form>
</div>

</body>
</html>