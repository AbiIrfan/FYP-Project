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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Base Styling */
        * { box-sizing: border-box; }
        body { 
            margin: 0; 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
            min-height: 100vh;
            color: #334155;
            line-height: 1.6;
        }
        a { text-decoration: none; color: inherit; transition: all 0.2s ease; }
        a:hover { transform: translateY(-1px); }

        /* Header - Warm and Welcoming */
        header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 1.25rem 2rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        header h2 { margin: 0; font-size: 1.5rem; font-weight: 600; letter-spacing: -0.5px; }
        header small { opacity: 0.9; font-weight: 400; }
        .logout-btn { 
            background: rgba(255,255,255,0.2); 
            padding: 0.6rem 1.25rem; 
            border-radius: 50px; 
            color: white; 
            font-weight: 500;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
        }
        .logout-btn:hover { 
            background: rgba(255,255,255,0.3); 
            transform: scale(1.05);
        }

        /* Main Layout */
        .container { display: flex; flex: 1; flex-direction: column; max-width: 1400px; margin: 0 auto; width: 100%; }

        /* Sidebar Navigation - Friendly Cards */
        .sidebar { 
            background: white; 
            padding: 1.5rem; 
            display: flex; 
            flex-direction: row; 
            flex-wrap: wrap; 
            gap: 12px; 
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        .sidebar a { 
            display: block; 
            padding: 0.875rem 1.25rem; 
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px; 
            text-align: center; 
            flex: 1 1 calc(50% - 12px); 
            min-width: 140px;
            font-weight: 500;
            color: #495057;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid transparent;
        }
        .sidebar a:hover { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transform: translateY(-2px);
        }
        .sidebar a:first-child {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        /* Content Area */
        .content { padding: 0 1.5rem 2rem; flex: 1; }

        /* Welcome Message */
        .welcome-section {
            margin-bottom: 2rem;
        }
        .welcome-section h1 {
            font-size: 1.75rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 0.5rem 0;
        }
        .welcome-section p {
            color: #64748b;
            margin: 0;
        }

        /* Dashboard Cards - Softer and More Inviting */
        .dashboard-cards { display: flex; gap: 24px; margin-bottom: 2rem; flex-wrap: wrap; }
        .stat-card { 
            background: white; 
            padding: 1.75rem; 
            border-radius: 16px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            flex: 1; 
            min-width: 220px; 
            text-align: center;
            border: 1px solid rgba(102, 126, 234, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.2);
        }
        .stat-card h3 { 
            margin: 0.5rem 0 0 0; 
            font-size: 2.5rem; 
            font-weight: 700; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .stat-card p { 
            margin: 0.5rem 0 0 0; 
            color: #64748b; 
            font-size: 0.875rem; 
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-alert { 
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        .stat-alert::before {
            background: linear-gradient(90deg, #ef4444 0%, #f97316 100%);
        }
        .stat-alert h3 {
            background: linear-gradient(135deg, #ef4444 0%, #f97316 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Tables - Modern Card Design */
        .card { 
            background: white; 
            padding: 2rem; 
            border-radius: 16px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }
        .card h3 { 
            margin: 0 0 1.5rem 0; 
            font-size: 1.25rem; 
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .card h3::before {
            content: '📦';
            font-size: 1.5rem;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { 
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #495057; 
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 8px 8px 0 0;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }
        .go-to-btn { 
            display: inline-block; 
            margin-top: 1.5rem; 
            padding: 0.875rem 1.5rem; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; 
            border-radius: 50px;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .go-to-btn:hover { 
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
            transform: translateY(-2px);
        }
        
        /* Responsive Design for Desktop/Tablet */
        @media (min-width: 768px) {
            .container { flex-direction: row; }
            .sidebar { 
                flex-direction: column; 
                width: 260px; 
                justify-content: flex-start; 
                height: calc(100vh - 80px);
                position: sticky;
                top: 80px;
                margin-bottom: 0;
                border-radius: 16px;
                margin-right: 2rem;
            }
            .sidebar a { 
                flex: none; 
                text-align: left;
                border-radius: 10px;
            }
            .content { padding: 0 0 2rem; }
        }
    </style>
</head>
<body>

    <header>
        <div>
            <h2>✨ Smart Store Checker</h2>
            <small>Welcome back, <?php echo htmlspecialchars($username); ?>! • <?php echo ucfirst(htmlspecialchars($userRole)); ?></small>
        </div>
        <a href="logout.php" class="logout-btn">Sign Out</a>
    </header>

    <div class="container">
        <!-- Sidebar Navigation -->
        <nav class="sidebar">
            <a href="index.php">🏠 Dashboard Home</a>
            <a href="search.php">🔍 Search & Update Stock</a>

            <!-- Owner-Only Links -->
            <?php if ($userRole === 'owner'): ?>
                <a href="manage_products.php">📋 Manage Products</a>
                <a href="reports.php">📊 Reports & Alerts</a>
                <a href="settings.php">⚙️ System Settings</a>
            <?php endif; ?>
        </nav>

        <!-- Main Content -->
        <main class="content">
            
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1>Good day, <?php echo htmlspecialchars($username); ?>! 👋</h1>
                <p>Here's what's happening in your store today.</p>
            </div>

            <!-- Quick Stats Row -->
            <div class="dashboard-cards">
                <div class="stat-card">
                    <h3><?php echo $totalProducts; ?></h3>
                    <p>Total Products</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $totalCategories; ?></h3>
                    <p>Categories</p>
                </div>
                <?php if ($userRole === 'owner'): ?>
                <div class="stat-card stat-alert">
                    <h3><?php echo $lowStockCount; ?></h3>
                    <p>Low Stock Alerts</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Recent Products Table -->
            <div class="card">
                <h3>Recently Added Inventory</h3>
                <?php if (count($latestProducts) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latestProducts as $item): ?>
                                <tr>
                                    <td><code style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.875rem;"><?php echo htmlspecialchars($item['sku']); ?></code></td>
                                    <td><strong style="color: #1e293b;"><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
                                    <td><span style="background: #e0e7ff; color: #667eea; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 500;"><?php echo htmlspecialchars($item['category_name']); ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($item['current_quantity']); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #64748b; text-align: center; padding: 2rem;">No products added yet. Start by adding some inventory!</p>
                <?php endif; ?>
                <a href="search.php" class="go-to-btn">Browse All Products →</a>
            </div>

        </main>
    </div>

</body>
</html>