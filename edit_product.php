<?php 
session_start();
require_once 'config/db.php';

if  (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    die("Access Denied. Only the store owner can access this page.");
}

$message = '';

// Check if an ID was provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Product ID not specified.");
}
$product_id = $_GET['id'];

// Handle the Form Submission (Update)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['i[update_product'])) {
    $sku = trim($_POST['sku']);
    $name = trim($_POST['product_name']);
    $category_id = $_POST['category_id'];
    $threshold = $_POST['min_threshold'];

    try {
        $stmt = $pdo->prepare("UPDATE products SET sku = ?, product_name = ?, category_id = ?, min_threshold = ? WHERE product_id = ?");
        $stmt->execute([$sku, $name, $category_id, $threshold, $product_id]);
        $message = "Product updated succesfully!";
    } catch (PDOException $e) {
        $message = "Error updating product: " . $e->getMessage();
    }
}

// Fetch current product details to populate the form
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    die("Produc not found");
}

// Fetch categories for the dropdown
$categoriesStmt = $pdo->query("SELECT * FROM categories");
$categories = $categoriesStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product - Smart Store Checker</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f9; padding: 20px; }
        .form-container { background: white; padding: 20px; max-width: 500px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        input, select { padding: 8px; margin: 5px 0 15px 0; width: 100%; box-sizing: border-box; }
        button { background-color: #007bff; color: white; border: none; padding: 10px 15px; cursor: pointer; border-radius: 4px; }
        button.hover { background-color: #0056b3; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #555; text-decoration: none; }
    </style>
</head>
<body>
    <a href="manage_products.php" class="back-link">← Back to Manage Products</a>

    <div class="form-container">
        <h3>Edit Product</h3>

        <?php if ($message): ?>
            <p style="color: green; font-weight: bold;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form method="POST" action="edit_product.php?id=<?php echo htmlspecialchars($product_id); ?>">
            <label>SKU:</label>
            <input type="text" name="sku" value="<?php echo htmlspecialchars($product['sku']); ?>" required>

            <label>Product Name:</label>
            <input type="text" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>

            <label>Category:</label>
            <select name="category_id" required>
                <option value="">Select a Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>" <?php echo ($cat['category_id'] == $product['category_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Minimun Stock Threshold:</label>
            <input type="number" name="min_threshold" value="<?php echo htmlspecialchars($product['min_threshold']); ?>" required>

            <!-- Note: Quantity is not updated here to enforce using the stock movement tracker -->

            <button type="submit" name="update_product">Save Changes</button>
        </form>
    </div>
</body>
</html>