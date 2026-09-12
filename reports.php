<?php

session_start();
require_once 'config/db.php';

// Enforce Role-Based Access Control: Only owners can view reports[cite: 1]
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    die("Access Denied. Only the store owner can access this page.");
}

// 1. Fetch Low-Stock Alerts
// Identifies products where the current quantity is less than or equal to the minimum threshold
$lowStockStmt = $pdo->query("
    SELECT p.sku, p.product_name, p.current_quantity, p.min_threshold, c.category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.current_quantity <= p.min_threshold
    ORDER BY p.current_quantity ASC
");
$lowStockProducts = $lowStockStmt->fetchAll();

// 2. Fetch Recent Stock Movement
// Joins stock_movement with products and users to provide a complete audit trail
$movementStmt = $pdo->query("
    SELECT sm.movement_type, sm.quantity_changed, sm.remarks, sm.movement_date, 
           p.sku, p.product_name, 
           u.username 
    FROM stock_movement sm 
    JOIN products p ON sm.product_id = p.product_id 
    JOIN users u ON sm.user_id = u.user_id 
    ORDER BY sm.movement_date DESC 
    LIMIT 50
");
$movements = $movementStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Reports - Smart Store Checker</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f9; padding: 20px; margin: 0; }
        .container { max-width: 1200px; margin: auto; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .card-header { border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; color: #333; }
        .alert-card { border-left: 5px solid #dc3545; } /* Red border for alerts */
        .movement-card { border-left: 5px solid #007bff; } /* Blue border for movements */
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #f8f9fa; color: #333; font-weight: bold; }
        .low-stock-qty { color: #dc3545; font-weight: bold; }
        
        .badge { padding: 5px 10px; border-radius: 12px; font-size: 0.85em; font-weight: bold; color: white; }
        .badge-received { background-color: #28a745; }
        .badge-sold { background-color: #007bff; }
        .badge-damaged { background-color: #dc3545; }
        .badge-adjusted { background-color: #ffc107; color: black; }

        .back-link { display: inline-block; margin-bottom: 20px; color: #555; text-decoration: none; }
        
        /* Responsive Table */
        @media (max-width: 768px) {
            table, thead, tbody, th, td, tr { display: block; }
            th { display: none; }
            td { position: relative; padding-left: 50%; text-align: right; border-bottom: none; }
            td::before { content: attr(data-label); position: absolute; left: 10px; width: 45%; text-align: left; font-weight: bold; }
            tr { border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 10px; }
        }
    </style>
</head>
<body>

<div class="container">
    <a href="index.php" class="back-link">← Back to Dashboard</a>
    <h2>Inventory Reports & Analytics</h2>

    <!-- Low Stock Alerts Section -->
    <div class="card alert-card">
        <h3 class="card-header">Low-Stock Alerts</h3>
        <?php if (count($lowStockProducts) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Current Quantity</th>
                        <th>Min Threshold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lowStockProducts as $alert): ?>
                        <tr>
                            <td data-label="SKU"><?php echo htmlspecialchars($alert['sku']); ?></td>
                            <td data-label="Product Name"><?php echo htmlspecialchars($alert['product_name']); ?></td>
                            <td data-label="Category"><?php echo htmlspecialchars($alert['category_name'] ?? 'N/A'); ?></td>
                            <td data-label="Current Quantity" class="low-stock-qty"><?php echo htmlspecialchars($alert['current_quantity']); ?></td>
                            <td data-label="Min Threshold"><?php echo htmlspecialchars($alert['min_threshold']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: #28a745; font-weight: bold;">All product quantities are above their minimum thresholds.</p>
        <?php endif; ?>
    </div>

    <!-- Recent Stock Movement Section -->
    <div class="card movement-card">
        <h3 class="card-header">Recent Stock Movement (Audit Trail)</h3>
        <?php if (count($movements) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Qty Changed</th>
                        <th>User</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movements as $mov): ?>
                        <tr>
                            <td data-label="Date & Time"><?php echo htmlspecialchars($mov['movement_date']); ?></td>
                            <td data-label="Product"><?php echo htmlspecialchars($mov['sku'] . ' - ' . $mov['product_name']); ?></td>
                            <td data-label="Type">
                                <span class="badge badge-<?php echo htmlspecialchars($mov['movement_type']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($mov['movement_type'])); ?>
                                </span>
                            </td>
                            <td data-label="Qty Changed"><?php echo htmlspecialchars($mov['quantity_changed']); ?></td>
                            <td data-label="User"><?php echo htmlspecialchars($mov['username']); ?></td>
                            <td data-label="Remarks"><?php echo htmlspecialchars($mov['remarks'] ?: '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No stock movements recorded yet.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>