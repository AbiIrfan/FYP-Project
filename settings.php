<?php

session_start();
require_once 'config/db.php';

// Enforce Role-Based Access Control: Only owners can access settings
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    die("Access Denied. Only the store owner can access this page.");
}

$message = '';
$error = '';

// Handle Create: Add a new category
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    
    if (!empty($category_name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (category_name) VALUES (?)");
            $stmt->execute([$category_name]);
            $message = "Category added successfully!";
        } catch (PDOException $e) {
            // Error 23000 is a duplicate entry (Unique constraint violation)
            if ($e->getCode() == 23000) {
                $error = "This category already exists.";
            } else {
                $error = "Error adding category: " . $e->getMessage();
            }
        }
    } else {
        $error = "Category name cannot be empty.";
    }
}

// Handle Delete: Remove a category
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->execute([$delete_id]);
        $message = "Category deleted successfully!";
    } catch (PDOException $e) {
        // Error 23000 here usually means a foreign key constraint failed (products are using this category)
        if ($e->getCode() == 23000) {
            $error = "Cannot delete this category because it is currently assigned to one or more products. Reassign or delete those products first.";
        } else {
            $error = "Error deleting category: " . $e->getMessage();
        }
    }
}

// Fetch all existing categories
$categoriesStmt = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC");
$categories = $categoriesStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Smart Store Checker</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f9; padding: 20px; margin: 0; }
        .container { max-width: 800px; margin: auto; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h2, h3 { margin-top: 0; color: #333; }
        
        .form-group { display: flex; gap: 10px; margin-top: 15px; }
        input[type="text"] { flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 10px 20px; background-color: #004085; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #002752; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #f8f9fa; color: #333; }
        .delete-btn { color: #dc3545; text-decoration: none; font-weight: bold; }
        .delete-btn:hover { text-decoration: underline; }
        
        .alert-success { color: #155724; background-color: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-error { color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #555; text-decoration: none; }
    </style>
</head>
<body>

<div class="container">
    <a href="index.php" class="back-link">← Back to Dashboard</a>
    <h2>System Settings</h2>

    <?php if ($message): ?>
        <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <h3>Manage Product Categories</h3>
        <p>Categories help retail staff filter products quickly during daily store operations[cite: 1].</p>
        
        <!-- Add Category Form -->
        <form method="POST" action="settings.php" class="form-group">
            <input type="text" name="category_name" placeholder="Enter new category name (e.g., Electronics, Apparel)" required>
            <button type="submit" name="add_category">Add Category</button>
        </form>

        <!-- Categories List -->
        <table>
            <thead>
                <tr>
                    <th>Category ID</th>
                    <th>Category Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($categories) > 0): ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cat['category_id']); ?></td>
                            <td><?php echo htmlspecialchars($cat['category_name']); ?></td>
                            <td>
                                <a href="settings.php?delete_id=<?php echo $cat['category_id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this category?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align: center;">No categories found. Add one above.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>