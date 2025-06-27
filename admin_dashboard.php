<?php
//ini_set('display_errors', 1);
//error_reporting(E_ALL);
include('function.php');
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Database Connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventory_management_system";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if Admin is Logged In
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch data from the database with JOINs for better display
$products = $conn->query("
    SELECT p.*, c.category_name, s.supplier_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id 
    ORDER BY p.product_id
");
$selectedOrderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;
$orderDetails = null;
$orderItems = null;
if ($selectedOrderId) {
    // Get order header information
    $orderDetails = $conn->query("
        SELECT o.order_id, o.created_at, o.order_status, u.username, u.user_id
        FROM orders o 
        JOIN users u ON o.user_id = u.user_id 
        WHERE o.order_id = $selectedOrderId
    ")->fetch_assoc();
    
    // Get order items (assuming you have an order_items table)
    // If you don't have order_items table, this query might need adjustment
    $orderItems = $conn->query("
        SELECT 
            oi.quantity,
            p.product_name,
            p.price,
            (oi.quantity * p.price) as total_price
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = $selectedOrderId
    ");
    
    // If order_items table doesn't exist, use this alternative query:
    /*
    $orderItems = $conn->query("
        SELECT 
            o.quantity,
            p.product_name,
            p.price,
            (o.quantity * p.price) as total_price
        FROM orders o
        JOIN products p ON o.product_id = p.product_id
        WHERE o.order_id = $selectedOrderId
    ");
    */
}
$ordersWithDetails = $conn->query("
    SELECT DISTINCT o.order_id, u.username, o.order_status, o.created_at
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    ORDER BY o.created_at DESC
");

$categories = $conn->query("SELECT * FROM categories ORDER BY category_name");
$suppliers = $conn->query("SELECT * FROM suppliers ORDER BY supplier_name");
$users = $conn->query("SELECT * FROM users");
$orders = getOrders($conn);

// Fetch summary statistics
$totalProducts = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$totalCategories = $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'];
$totalSuppliers = $conn->query("SELECT COUNT(*) as count FROM suppliers")->fetch_assoc()['count'];
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
$totalOrders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];

$lowStockProducts = $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity < 10")->fetch_assoc()['count'];
$totalRevenue = $conn->query("SELECT SUM(p.price * oi.quantity) as revenue FROM order_items oi JOIN products p ON oi.product_id = p.product_id JOIN orders o ON oi.order_id = o.order_id WHERE o.order_status = 'delivered'")->fetch_assoc()['revenue'] ?? 0;
$processingOrders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'processing'")->fetch_assoc()['count'];
$shippedOrders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'shipped'")->fetch_assoc()['count'];
$deliveredOrders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'delivered'")->fetch_assoc()['count'];

// Recent orders for dashboard
$recentOrders = $conn->query("SELECT o.order_id, u.username, o.order_status, o.created_at FROM orders o JOIN users u ON o.user_id = u.user_id ORDER BY o.created_at DESC LIMIT 5");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['order_status'];

    if (updateOrderStatus($conn, $orderId, $newStatus)) {
        header("Location: admin_dashboard.php"); // Refresh the page
        exit();
    } else {
        echo "<script>alert('Failed to update order status.');</script>";
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: #A7BEAE;
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
        }
        input, select, button {
            width: 50%;
            padding: 5px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        .sidebar {
            width: 280px;
            background: #333;
            color: white;
            padding: 20px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
        }
        .sidebar a {
            display: block;
            color: white;
            padding: 10px;
            text-decoration: none;
        }
        .sidebar a:hover {
            background: #0056b3;
            border-radius: 5px;
        }
        .container {
            margin-left: 120px;
            padding: 20px;
            width: calc(100% - 360px);
        }
        .section {
            display: none;
        }
        .active {
            display: block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
            text-align: left;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ccc;
        }
        th {
            background: #404041;
            color: white;
        }
        a {
            text-decoration: none;
            color: red;
        }
        a:hover {
            text-decoration: underline;
        }
        
        /* Dashboard specific styles */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .stat-label {
            font-size: 1.1em;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .stat-card.orders { border-top: 4px solid #007bff; }
        .stat-card.customers { border-top: 4px solid#28a745; }
        .stat-card.products { border-top: 4px solid #ffc107; }
        .stat-card.revenue { border-top: 4px solid #dc3545; }
        .stat-card.suppliers { border-top: 4px solid #6f42c1; }
        .stat-card.categories { border-top: 4px solid #20c997; }
        .stat-card.lowstock { border-top: 4px solid #e83e8c; }
        
        .dashboard-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        .recent-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .recent-section h3 {
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .recent-orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        .recent-orders-table th,
        .recent-orders-table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .recent-orders-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-processing { background: #d1ecf1; color: #0c5460; }
        .status-shipped { background: #d4edda; color: #155724; }
        .status-delivered { background: #d1ecf1; color: #0c5460; }
        .status-pending { background: #fff3cd; color: #856404; }

        /* Low stock warning */
        .low-stock {
            background-color: #ffebee;
            color: #c62828;
            font-weight: bold;
        }
        .section {
        display: none;
        padding: 20px;
    }

    .section.active {
        display: block;
    }

    .chart-container {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 20px;
    }
        .chart-box {
        flex: 1 1 45%;
        min-width: 300px;
        background: #f9f9f9;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    canvas {
        width: 100% !important;
        height: auto !important;
    }
    .order-detail-container {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.order-header {
    text-align: center;
    margin-bottom: 30px;
}

.order-header h2 {
    font-size: 2.5em;
    margin-bottom: 10px;
    color: #333;
}

.order-info {
    font-size: 1.3em;
    color: #666;
    margin-bottom: 20px;
}

.order-items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.order-items-table th {
    background: #404041;
    color: white;
    padding: 15px;
    text-align: left;
    font-size: 1.1em;
}

.order-items-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #ddd;
    font-size: 1em;
}

.order-items-table tr:nth-child(even) {
    background-color: #f9f9f9;
}

.order-total {
    text-align: right;
    font-size: 1.3em;
    font-weight: bold;
    margin: 20px 0;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 5px;
}

.back-button {
    display: inline-block;
    background: #007bff;
    color: white;
    padding: 12px 25px;
    text-decoration: none;
    border-radius: 5px;
    font-size: 1.1em;
    margin-top: 20px;
    transition: background 0.3s;
}

.back-button:hover {
    background: #0056b3;
    color: white;
}

.view-details-btn {
    background: #28a745;
    color: white;
    padding: 5px 10px;
    text-decoration: none;
    border-radius: 3px;
    font-size: 0.9em;
}

.view-details-btn:hover {
    background: #218838;
    color: white;
}
     


    </style>
    <script>
        function showSection(sectionId) {
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });
            document.getElementById(sectionId).classList.add('active');
            localStorage.setItem('activeSection', sectionId);
        }

        document.addEventListener("DOMContentLoaded", function () {
            let activeSection = localStorage.getItem('activeSection');
            
            // Check if there are user management alerts - if so, show users section
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('error') || (urlParams.has('message') && urlParams.get('message') === 'User added successfully')) {
                activeSection = 'usersSection';
            }
            
            if (activeSection) {
                showSection(activeSection);
            } else {
                showSection('dashboardSection'); // Default to Dashboard
            }
        });
    </script>
</head>
<body>
    

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="#" onclick="showSection('dashboardSection')">Dashboard</a>
    <a href="#" onclick="showSection('productsSection')">Manage Products</a>
    <a href="#" onclick="showSection('usersSection')">Manage Users</a>
    <a href="#" onclick="showSection('categoriesSection')">Manage Categories</a>
    <a href="#" onclick="showSection('suppliersSection')">Manage Suppliers</a>
    <a href="#" onclick="showSection('ordersTrackingSection')">Track Orders</a>
    <a href="#" onclick="showSection('orderDetailsSection')">Order Details</a>
    <a href="#" onclick="showSection('reportSection')">Report Generation</a>
    <a href="logout.php" class="logout">Logout</a>
</div>

<div class="container">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Admin)</h1>

    <!-- Dashboard Overview -->
    <div id="dashboardSection" class="section active">
        <!--<h2>System Overview</h2>-->
        
        <div class="dashboard-grid">
            <div class="stat-card orders">
                <div class="stat-number"><?php echo $totalOrders; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            
            <div class="stat-card customers">
                <div class="stat-number"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Customers</div>
            </div>
            
            <div class="stat-card products">
                <div class="stat-number"><?php echo $totalProducts; ?></div>
                <div class="stat-label">Products</div>
            </div>
            
            <div class="stat-card revenue">
                <div class="stat-number">Rs.<?php echo number_format($totalRevenue, 2); ?></div>
                <div class="stat-label">Revenue</div>
            </div>
            
            <div class="stat-card suppliers">
                <div class="stat-number"><?php echo $totalSuppliers; ?></div>
                <div class="stat-label">Suppliers</div>
            </div>
            
            <div class="stat-card categories">
                <div class="stat-number"><?php echo $totalCategories; ?></div>
                <div class="stat-label">Categories</div>
            </div>
            
            <div class="stat-card lowstock">
                <div class="stat-number"><?php echo $lowStockProducts; ?></div>
                <div class="stat-label">Low Stock Items</div>
            </div>
        </div>
        
        <div class="dashboard-section">
            <div class="recent-section">
                <h3>Recent Orders</h3>
                <table class="recent-orders-table">
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                    <?php if ($recentOrders->num_rows > 0): ?>
                        <?php while ($order = $recentOrders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $order['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                                        <?php echo htmlspecialchars($order['order_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #666;">No orders found</td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
            
            <div class="recent-section">
                <h3>Quick Actions</h3>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <button onclick="showSection('productsSection')" style="width: 100%; padding: 15px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">Add New Product</button>
                    <button onclick="showSection('ordersTrackingSection')" style="width: 100%; padding: 15px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">View All Orders</button>
                    <button onclick="showSection('usersSection')" style="width: 100%; padding: 15px; background: #ffc107; color: black; border: none; border-radius: 5px; cursor: pointer;">Manage Users</button>
                    <button onclick="showSection('suppliersSection')" style="width: 100%; padding: 15px; background: #6f42c1; color: white; border: none; border-radius: 5px; cursor: pointer;">Add Supplier</button>
                </div>
                
                <?php if ($lowStockProducts > 0): ?>
                    <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">
                        <strong>⚠️ Alert:</strong> You have <?php echo $lowStockProducts; ?> product(s) with low stock (less than 10 items).
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Manage Products -->
    <div id="productsSection" class="section">
        <h2>Manage Products</h2>
        
        <form action="add_product.php" method="POST">
            <input type="text" name="product_name" placeholder="Product Name" required><br>
            <select name="category_id" required>
                <option value="">Select Category</option>
                <?php
                $categories->data_seek(0);
                while ($category = $categories->fetch_assoc()): ?>
                    <option value="<?php echo $category['category_id']; ?>">
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select><br>
            <select name="supplier_id" required>
                <option value="">Select Supplier</option>
                <?php 
                $suppliers->data_seek(0);
                while ($supplier = $suppliers->fetch_assoc()): ?>
                    <option value="<?php echo $supplier['supplier_id']; ?>">
                        <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select><br>
            <input type="number" name="quantity" placeholder="Quantity" min="0" required><br>
            <input type="number" step="0.01" name="price" placeholder="Price" min="0" required><br>
            <button type="submit">Add Product</button>
        </form>

        <h3>Existing Products</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Category</th>
                <th>Supplier</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Action</th>
            </tr>
            <?php 
            if ($products && $products->num_rows > 0) {
                while ($product = $products->fetch_assoc()): 
                    $rowClass = ($product['quantity'] < 10) ? 'low-stock' : '';
                ?>
                    <tr class="<?php echo $rowClass; ?>">
                        <td><?php echo $product['product_id']; ?></td>
                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($product['supplier_name'] ?? 'N/A'); ?></td>
                        <td>
                            <?php echo $product['quantity']; ?>
                            <?php if ($product['quantity'] < 10): ?>
                                <span style="color: red; font-size: 0.8em;"> (Low Stock!)</span>
                            <?php endif; ?>
                        </td>
                        <td>Rs.<?php echo number_format($product['price'], 2); ?> per piece</td>
                        <td><a href="delete_product.php?id=<?php echo $product['product_id']; ?>" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a></td>
                    </tr>
                <?php 
                endwhile;
            } else {
                echo "<tr><td colspan='7' style='text-align: center; color: #666;'>No products found</td></tr>";
            }
            ?>
        </table>
    </div>

    <!-- Manage Users -->
    <div id="usersSection" class="section">
        <h2>Manage Users</h2>
        
       

        
        <form action="add_user.php" method="POST">
            <input type="number" name="user_id" placeholder="User ID" required><br>
            <input type="text" name="user_name" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <select name="role">
                <option value="admin">Admin</option>
                <option value="user">User</option>
            </select><br>
            <button type="submit">Add User</button>
        </form>

        <h3>Existing Users</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Role</th>
                <th>Action</th>
            </tr>
            <?php 
            if ($users && $users->num_rows > 0) {
                while ($user = $users->fetch_assoc()): ?>
                   <tr>
    <td><?php echo $user['user_id']; ?></td>
    <td><?php echo htmlspecialchars($user['username']); ?></td> <!-- fixed line -->
    <td><?php echo htmlspecialchars($user['role']); ?></td>
    <td>
        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
            <a href="delete_user.php?id=<?php echo $user['user_id']; ?>" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
        <?php else: ?>
            <span style="color: gray;">Current User</span>
        <?php endif; ?>
    </td>
</tr>

                <?php 
                endwhile;
            } else {
                echo "<tr><td colspan='4' style='text-align: center; color: #666;'>No users found</td></tr>";
            }
            ?>
        </table>
    </div>

    <!-- Manage Categories -->
    <div id="categoriesSection" class="section">
        <h2>Manage Categories</h2>
        <form action="add_category.php" method="POST">
            <input type="text" name="category_name" placeholder="Category Name" required><br>
            <button type="submit">Add Category</button>
        </form>

        <h3>Existing Categories</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th>Action</th>
            </tr>
            <?php
            $categories->data_seek(0);
            if ($categories && $categories->num_rows > 0) {
                while ($category = $categories->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $category['category_id']; ?></td>
                        <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                        <td><a href="delete_category.php?id=<?php echo $category['category_id']; ?>" onclick="return confirm('Are you sure you want to delete this category?')">Delete</a></td>
                    </tr>
                <?php 
                endwhile;
            } else {
                echo "<tr><td colspan='3' style='text-align: center; color: #666;'>No categories found</td></tr>";
            }
            ?>
        </table>
    </div>

    <!-- Manage Suppliers -->
    <div id="suppliersSection" class="section">
        <h2>Manage Suppliers</h2>
        <form action="add_supplier.php" method="POST">
            <input type="text" name="supplier_name" placeholder="Supplier Name" required><br>
            <input type="text" name="contact_person" placeholder="Contact Person" required><br>
            <input type="text" name="phone" placeholder="Phone Number" required><br>
            <input type="email" name="email" placeholder="Email" required><br>
            <input type="text" name="address" placeholder="Address" required><br>
            <button type="submit">Add Supplier</button>
        </form>

        <h3>Existing Suppliers</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Supplier Name</th>
                <th>Contact Person</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Address</th>
                <th>Action</th>
            </tr>
            <?php
            $suppliers->data_seek(0);
            if ($suppliers && $suppliers->num_rows > 0) {
                while ($supplier = $suppliers->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $supplier['supplier_id']; ?></td>
                        <td><?php echo htmlspecialchars($supplier['supplier_name']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['contact_person']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['phone']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['email']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['address']); ?></td>
                        <td><span style="color: gray;">Cannot delete</span></td>
                    </tr>
                <?php 
                endwhile;
            } else {
                echo "<tr><td colspan='7' style='text-align: center; color: #666;'>No suppliers found</td></tr>";
            }
            ?>
        </table>
    </div>

    <!-- Track Orders -->
    <div id="ordersTrackingSection" class="section">
        <h2>Track Orders</h2>
        <table>
            <tr>
                <th>Order ID</th>
                <th>Username</th>
                <th>Order Status</th>
                <th>Update Status</th>
            </tr>
            <?php 
            if ($orders && $orders->num_rows > 0) {
                while ($row = $orders->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['order_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower($row['order_status']); ?>">
                                <?php echo htmlspecialchars($row['order_status']); ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?php echo $row['order_id']; ?>">
                                <?php if (strtolower($row['order_status']) !== "delivered") { ?>
                                    <select name="order_status" required>
                                        <option value="">Select New Status</option>
                                        <?php
                                        $statusOptions = [];
                                        switch (strtolower($row['order_status'])) {
                                            case 'pending':
                                                $statusOptions = ['Processing', 'Shipped', 'Delivered'];
                                                break;
                                            case 'processing':
                                                $statusOptions = ['Shipped', 'Delivered'];
                                                break;
                                            case 'shipped':
                                                $statusOptions = ['Delivered'];
                                                break;
                                            default:
                                                $statusOptions = ['Processing', 'Shipped', 'Delivered'];
                                        }
                                        foreach ($statusOptions as $status) {
                                            echo "<option value=\"$status\">$status</option>";
                                        }
                                        ?>
                                    </select>
                                    <button type="submit" name="update_status" value="1">Update</button>
                                <?php } else {
                                    echo "<span style='color: green; font-weight: bold;'>Delivered</span>";
                                } ?>
                            </form>
                        </td>
                    </tr>
                <?php } 
            } else {
                echo "<tr><td colspan='4' style='text-align: center; color: #666;'>No orders found</td></tr>";
            }
            ?>
        </table>
    </div>
    <!-- Analytics -->
    <div id="reportSection" class="section">
        <h2>Report visualization of the current data</h2>
        <div class="chart-container">
            <div class="chart-box">
                <canvas id="summaryBarChart"></canvas>
            </div>
            <div class="chart-box">
                <canvas id="orderStatusChart"></canvas>
            </div>
        </div>
        
        <a href="export_excel.php" class="btn">Export to Excel</a>
    </div>
    

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function switchSection(sectionId) {
            document.querySelectorAll('.section').forEach(sec => sec.classList.remove('active'));
            document.getElementById(sectionId).classList.add('active');
        }

        const summaryBarChart = new Chart(document.getElementById('summaryBarChart'), {
            type: 'bar',
            data: {
                labels: ['Products', 'Categories', 'Suppliers', 'Users', 'Orders'],
                datasets: [{
                    label: 'Counts',
                    data: [<?= $totalProducts ?>, <?= $totalCategories ?>, <?= $totalSuppliers ?>, <?= $totalUsers ?>, <?= $totalOrders ?>],
                    backgroundColor: ['#4CAF50', '#2196F3', '#FFC107', '#FF5722', '#9C27B0']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: { display: true, text: 'Summary Counts' },
                    legend: { display: false }
                }
            }
        });

        const orderStatusChart = new Chart(document.getElementById('orderStatusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Processing', 'Shipped', 'Delivered'],
        datasets: [{
            data: [<?= $processingOrders ?>, <?= $shippedOrders ?>, <?= $deliveredOrders ?>],
            backgroundColor: ['#FF9800', '#03A9F4', '#4CAF50']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: { display: true, text: 'Order Status Distribution' }
        }
    }
});

        
    </script>
    
    
    <div id="orderDetailsSection" class="section">
    <?php if ($selectedOrderId && $orderDetails): ?>
        <!-- Order Detail View -->
        <div class="order-detail-container">
            <div class="order-header">
                <h2>Order Details</h2>
                <div class="order-info">
                    Order #<?php echo $orderDetails['order_id']; ?> - Date: <?php echo date('Y-m-d H:i:s', strtotime($orderDetails['created_at'])); ?>
                </div>
            </div>
            
            <table class="order-items-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Price (Rs.)</th>
                        <th>Total Price (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $orderTotal = 0;
                    if ($orderItems && $orderItems->num_rows > 0):
                        while ($item = $orderItems->fetch_assoc()): 
                            $orderTotal += $item['total_price'];
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo number_format($item['total_price'], 2); ?></td>
                        </tr>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #666;">No items found for this order</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="order-total">
                Order Total: <?php echo number_format($orderTotal, 2); ?>
            </div>
            
            <div style="text-align: center;">
                <a href="admin_dashboard.php" class="back-button">Back to Orders</a>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Orders List View -->
        <h2>Track Orders</h2>
        <table>
            <tr>
                <th>Order ID</th>
                <th>Username</th>
                <th>Order Status</th>
                <th>Order Date</th>
                <th>Actions</th>
            </tr>
            <?php 
            if ($ordersWithDetails && $ordersWithDetails->num_rows > 0) {
                while ($row = $ordersWithDetails->fetch_assoc()) { ?>
                    <tr>
                        <td>#<?php echo $row['order_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower($row['order_status']); ?>">
                                <?php echo htmlspecialchars($row['order_status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y g:i A', strtotime($row['created_at'])); ?></td>
                        <td>
                            <a href="admin_dashboard.php?order_id=<?php echo $row['order_id']; ?>" class="view-details-btn">View Details</a>
                            
                           
                        </td>
                    </tr>
                <?php } 
            } else {
                echo "<tr><td colspan='5' style='text-align: center; color: #666;'>No orders found</td></tr>";
            }
            ?>
        </table>
    <?php endif; ?>
</div>
  


</div>

</body>
</html>

<?php $conn->close(); ?>