<?php 
// index.php
session_start();
require_once 'config/db.php'; // Added database connection

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userRole = $_SESSION['role'];
$username = $_SESSION['username'];

// Added new things

// Fetch Quick Stats for the Dashboard Cards
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// Fetch Latest 5 Products to show some items on the front page
$latestProducts = $pdo->query("
    SELECT p.sku, p.product_name, p.current_quantity, c.category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    ORDER BY p.product_id DESC LIMIT 5
")->fetchAll();

// Fetch Low Stock Count (For Owner Only)
$lowStockCount = 0;
if ($userRole === 'owner') {
    $lowStockCount = $pdo->query("SELECT COUNT(*) FROM products WHERE current_quantity <= min_threshold")->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart Store Checker</title>
    <style>
        /* Base Styling */
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; display: flex; flex-direction: column; min-height: 100vh; }
        a { text-decoration: none; color: inherit;}

        /* Header */
        header { background-color: #004085; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .logout-btn { background-color: #dc3545; padding: 0.5rem 1rem; border-radius: 4px; color: white; font-weight: bold; }
        .logout-btn:hover { background-color: #c82333; }

        /* Main Layout */
        .container { display: flex; flex: 1; flex-direction: column; }

        /* Sidebar Navigation */
        .sidebar { background-color: #343a40; color: white; padding: 1rem; display: flex; flex-direction: row; flex-wrap: wrap; gap: 10px; justify-content: center; }
        .sidebar a { display: block; padding: 0.75rem 1rem; background-color: #495057; border-radius: 4px; text-align: center; flex: 1 1 calc(50% - 10px); min-width: 120px; }

        /* Content Area (editted) */
        .content { padding: 2rem; flex: 1; }

        /* Dashboard Cards (Added) */
        .dashboard-cards { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); flex: 1; min-width: 200px; text-align: center; border-top: 4px solid #007bff; }
        .stat-card h3 { margin: 0; font-size: 2em; color: #333; }
        .stat-card p { margin: 5px 0 0 0; color: #666; text-transform: uppercase; font-size: 0.9em; font-weight: bold; }
        .stat-alert { border-top: 4px solid #dc3545; }

        /* Tables (Added) */
        .card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 1.5rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #f8f9fa; color: #333; }
        .go-to-btn { display: inline-block; margin-top: 15px; padding: 10px 15px; background: #004085; color: white; border-radius: 4px; }
        .go-to-btn:hover { background: #002752; }
        
        /* Responsive Design for Desktop/Tablet */
        @media (min-width: 768px) {
            .container { flex-direction: row; }
            .sidebar { flex-direction: column; width: 250px; justify-content: flex-start; height: 100%; }
            .sidebar a { flex: none; text-align: left; }
        }
    </style>
</head>
<body>

    <header>
        <div>
            <h2>Smart Store Checker</h2>
            <small>Welcome, <?php echo htmlspecialchars($username); ?> (<?php echo ucfirst(htmlspecialchars($userRole)); ?>)</small>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </header>

    <div class="container">
        <!-- Sidebar Navigation -->
        <nav class="sidebar">
        <a href="index.php">Dashboard Home</a>
        <a href="search.php">Search & Update Stock</a>

            <!-- Owner-Only Links(Editted) -->
            <?php if ($userRole === 'owner'): ?>
                <a href="manage_products.php">Manage Products</a>
                <a href="reports.php">Reports & Alerts</a>
                <a href="settings.php">System Settings</a>
            <?php endif; ?>
        </nav>

        <!-- Main Content(Eddited + Added) -->
        <main class="content">
            
            <!-- Quick Stats Row(New) -->
            <div class="dashboard-cards">
                <div class="stat-card">
                    <h3><?php echo $totalProducts; ?></h3>
                    <p>Total Items in System</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $totalCategories; ?></h3>
                    <p>Active Categories</p>
                </div>
                <?php if ($userRole === 'owner'): ?>
                <div class="stat-card stat-alert">
                    <h3 style="color: #dc3545;"><?php echo $lowStockCount; ?></h3>
                    <p>Low Stock Alerts</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Recent Products Table(New) -->
            <div class="card">
                <h3 style="margin-top: 0;">Recently Added Inventory</h3>
                <?php if (count($latestProducts) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Current Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latestProducts as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['current_quantity']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No products added yet.</p>
                <?php endif; ?>
                <br>
                <a href="search.php" class="go-to-btn">Search Full Inventory →</a>
            </div>

        </main>
    </div>

</body>
</html>