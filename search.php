<?php 
session_start();
require_once 'config/db.php';

// Check if user is logged in (both owner and staff can access this page)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Initialize variables for search and filter 
$searchKeyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$categoryFilter = isset($_GET['category_id']) ? $_GET['category_id'] : '';

// Fetch all categories for filter dropdown
$categoriesStmt = $pdo->query("SELECT * FROM categories");
$categories = $categoriesStmt->fetchAll();

// Build the dynamic SQL query based on user input
$sql = "SELECT p.product_id, p.sku, p.product_name, p.current_quantity, c.category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE 1=1";
$params = [];

// If a keyword is provied, search both SKU and Product Name
if ($searchKeyword !== '') {
    $sql .= " AND (p.product_name LIKE ? OR p.sku LIKE ?)";
    $params[] = "%$searchKeyword";
    $params[] = "%$searchKeyword";
}

// If a category is selected, filter by it[cite: 1]
if ($categoryFilter !== '') {
    $sql .= " AND p.category_id = ?";
    $params[] = $categoryFilter;
}

// Execute the query securely using prepared statements
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Products - Smart Store Checker</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f9; padding: 20px; margin: 0; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .search-form { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        input[type="text"], select { padding: 10px; flex: 1; min-height: 200px; border: 1px solid; #ccc; border-radius: 4px; }
        button { padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #004085; color: white; }
        .update-btn { background-color: #28a745; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 0.9em; }
        .update-btn:hover { background-color: #218838; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #555; text-decoration: none; }

        /* Responsive Table */
        @media (max-width: 600px) {
            table, thead, tbody, th, td, tr { display: block; }
            th { display: none; }
            td { position: relative; padding-left: 50%; text-align: right; }
            td::before { content: attr(data-label); position: absolute; left: 10px; width: 45%; text-align: left; font-weight: bold; }
        }
    </style>
</head>
<body>

    <div class="container">
        <a href="index.php" class="back-link">← Back to Dashboard</a>
        <h2>Search & Filter Products</h2>

        <!-- Search Form -->
         <form method="GET" action="search.php" class="search-form">
            <input type="text" name="keyword" placeholder="Search by name or SKU..." value="<?php echo htmlspecialchars($searchKeyword); ?>">

            <select name="category_id">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['category_id']; ?>" <?php echo ($categoryFilter == $cat['category_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['category_name']); ?>
                </option>
            <?php endforeach; ?>
            </select>

            <button type="submit">Search</button>
            <a href="search.php" style="padding: 10px 20px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px;">Clear</a>
         </form>

         <!-- Search Results -->
    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Name</th>
                <th>Category</th>
                <th>Quantity</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td data-label="SKU"><?php echo htmlspecialchars($p['sku']); ?></td>
                        <td data-label="Name"><?php echo htmlspecialchars($p['product_name']); ?></td>
                        <td data-label="Category"><?php echo htmlspecialchars($p['category_name']); ?></td>
                        <td data-label="Quantity"><strong><?php echo htmlspecialchars($p['current_quantity']); ?></strong></td>
                        <td data-label="Action">
                            <a href="stock_update.php?id=<?php echo $p['product_id']; ?>" class="update-btn">Update Stock</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No products found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>