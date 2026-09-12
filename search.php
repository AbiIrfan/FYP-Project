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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
            padding: 20px; 
            margin: 0;
            color: #334155;
            line-height: 1.6;
        }
        .container { 
            max-width: 1100px; 
            margin: auto; 
            background: white; 
            padding: 2rem; 
            border-radius: 16px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .search-form { 
            display: flex; 
            gap: 12px; 
            flex-wrap: wrap; 
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
        }
        input[type="text"], select { 
            padding: 0.75rem 1rem; 
            flex: 1; 
            min-width: 200px;
            border: 2px solid #e2e8f0; 
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.2s ease;
        }
        input[type="text"]:focus, select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        button { 
            padding: 0.75rem 1.5rem; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; 
            border: none; 
            border-radius: 10px; 
            cursor: pointer;
            font-weight: 500;
            font-family: inherit;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        button:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 1.5rem;
            background: white;
        }
        th, td { 
            padding: 1rem 1.25rem; 
            border-bottom: 1px solid #e2e8f0; 
            text-align: left; 
        }
        th { 
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #495057;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 8px 8px 0 0;
        }
        tr:hover td { background: #f8fafc; }
        tr:last-child td { border-bottom: none; }
        .update-btn { 
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white; 
            padding: 0.5rem 1rem; 
            text-decoration: none; 
            border-radius: 8px; 
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }
        .update-btn:hover { 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        .back-link { 
            display: inline-block; 
            margin-bottom: 1.5rem; 
            color: #667eea; 
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            background: #e0e7ff;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .back-link:hover {
            background: #667eea;
            color: white;
            transform: translateX(-4px);
        }
        h2 {
            margin: 0 0 1.5rem 0;
            color: #1e293b;
            font-size: 1.5rem;
        }

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
        <h2>🔍 Search & Filter Products</h2>

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
            <a href="search.php" style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); color: white; text-decoration: none; border-radius: 10px; font-weight: 500; font-family: inherit; box-shadow: 0 4px 15px rgba(107, 114, 128, 0.4);">Clear</a>
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
                        <td data-label="SKU"><code style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.875rem;"><?php echo htmlspecialchars($p['sku']); ?></code></td>
                        <td data-label="Name"><strong><?php echo htmlspecialchars($p['product_name']); ?></strong></td>
                        <td data-label="Category"><span style="background: #e0e7ff; color: #667eea; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 500;"><?php echo htmlspecialchars($p['category_name']); ?></span></td>
                        <td data-label="Quantity"><strong><?php echo htmlspecialchars($p['current_quantity']); ?></strong></td>
                        <td data-label="Action">
                            <a href="stock_update.php?id=<?php echo $p['product_id']; ?>" class="update-btn">Update Stock</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 2rem; color: #64748b;">No products found. Try adjusting your search criteria.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>