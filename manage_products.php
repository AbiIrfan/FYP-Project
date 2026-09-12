<?php 
session_start();
require_once 'config/db.php';

if  (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    die("Access Denied. Only the store owner can access this page.");
}

$message = '';

// Handle Create (Add Product)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $sku = trim($_POST['sku']);
    $name = trim($_POST['product_name']);
    $category_id = $_POST['category_id'];
    $quantity = $_POST['current_quantity'];
    $threshold = $_POST['min_threshold'];

    try {
        $stmt = $pdo->prepare("INSERT INTO products (sku, product_name, category_id, current_quantity, min_threshold) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$sku, $name, $category_id, $quantity, $threshold]);
        $message = "Product added successfully!";
    } catch (PDOException $e) {
        $message = "Error adding product: " . $e->getMessage();
    }
}

// Handle Read
$productsStmt = $pdo->query("
    SELECT p.product_id, p.sku, p.product_name, p.current_quantity, p.min_threshold, c.category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
");
$product = $productsStmt->fetchAll();

// Fetch categories for the dropdown form
$categoriesStmt = $pdo->query("SELECT * FROM categories");
$categories = $categoriesStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products - Smart Store Checker</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f9; padding: 20px;} 
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .form-container { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; }
        input, select { padding: 8px; margin: 5px 0 15px 0; width: 100%; box-sizing: border-box; }
        button { background-color: #28a745; color: white; border: none; padding: 10px 15px; cursor: pointer; }
    </style>
</head>
<body>
    <a href="index.php">← Back to Dashboard</a>
    <h2>Manage Products</h2>

    <?php if ($message): ?>
        <p style="color: green; font-weight: bold;"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <div class="form-container">
        <h3>Add New Product</h3>
        <form method="POST" action="manage_products.php">
            <label>SKU:</label>
            <input type="text" name="sku" required>

            <label>Product Name:</label>
            <input type="text" name="product_name" required>

            <label>Category:</label>
            <select name="category_id" required>
                <option value="">Select a Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                <?php endforeach; ?>
            </select>

            <label>Starting Quantity</label>
            <input type="number" name="current_quantity" value="0" required>

            <label>Minimum Stock Threshold</label>
            <input type="number" name="min_threshold" value="10" required>

            <button type="submit" name="add_product">Add Product</button>
        </form>
    </div>

    <h3>Current Inventory</h3>
    <table>
        <thread>
            <tr>
                <th>SKU</th>
                <th>Name</th>
                <th>Category</th>
                <th>Qty</th>
                <th>Min Threshold</th>
                <th>Actions</th>
            </tr>
        </thread>
        <tbody>
            <?php foreach ($product as $p): ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['sku']); ?></td>
                    <td><?php echo htmlspecialchars($p['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($p['category_name']); ?></td>
                    <td><?php echo htmlspecialchars($p['current_quantity']); ?></td>
                    <td><?php echo htmlspecialchars($p['min_threshold']); ?></td>
                    <td>
                        <a href="edit_product.php?id=<?php echo $p['product_id']; ?>" style="color: blue; margin-right: 10px; text-decoration: none; font-weight: bold;">Edit</a>
                        <a href="manage_products.php?delete_id=<?php echo $p['product_id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>