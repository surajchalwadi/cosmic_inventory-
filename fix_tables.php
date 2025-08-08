<?php
// Fix missing tables and ensure everything works
include 'config/db.php';

echo "<h2>🔧 Fixing Database Tables</h2>";

// Check and create products table if it doesn't exist
$products_check = "SHOW TABLES LIKE 'products'";
$products_result = mysqli_query($conn, $products_check);

if (mysqli_num_rows($products_result) == 0) {
    echo "<p>❌ Products table not found. Creating...</p>";
    
    $create_products = "
    CREATE TABLE products (
        product_id INT AUTO_INCREMENT PRIMARY KEY,
        product_name VARCHAR(255) NOT NULL UNIQUE,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        status ENUM('Active', 'Inactive') DEFAULT 'Active',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $create_products)) {
        echo "<p>✅ Products table created successfully!</p>";
    } else {
        echo "<p>❌ Error creating products table: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>✅ Products table exists</p>";
}

// Check and create quotations table if it doesn't exist
$quotations_check = "SHOW TABLES LIKE 'quotations'";
$quotations_result = mysqli_query($conn, $quotations_check);

if (mysqli_num_rows($quotations_result) == 0) {
    echo "<p>❌ Quotations table not found. Creating...</p>";
    
    $create_quotations = "
    CREATE TABLE quotations (
        quotation_id INT AUTO_INCREMENT PRIMARY KEY,
        reference VARCHAR(50) NOT NULL UNIQUE,
        customer_name VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        company VARCHAR(255),
        contact_person TEXT,
        address TEXT,
        additional_info TEXT,
        subtotal DECIMAL(10,2) DEFAULT 0,
        tax_amount DECIMAL(10,2) DEFAULT 0,
        discount DECIMAL(10,2) DEFAULT 0,
        total_amount DECIMAL(10,2) DEFAULT 0,
        follow_up_date DATE,
        status ENUM('Draft', 'Sent', 'Approved', 'Rejected') DEFAULT 'Draft',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $create_quotations)) {
        echo "<p>✅ Quotations table created successfully!</p>";
    } else {
        echo "<p>❌ Error creating quotations table: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>✅ Quotations table exists</p>";
}

// Check and create quotation_items table if it doesn't exist
$quotation_items_check = "SHOW TABLES LIKE 'quotation_items'";
$quotation_items_result = mysqli_query($conn, $quotation_items_check);

if (mysqli_num_rows($quotation_items_result) == 0) {
    echo "<p>❌ Quotation items table not found. Creating...</p>";
    
    $create_quotation_items = "
    CREATE TABLE quotation_items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        quotation_id INT NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        total DECIMAL(10,2) AS (quantity * price) STORED,
        FOREIGN KEY (quotation_id) REFERENCES quotations(quotation_id) ON DELETE CASCADE
    )";
    
    if (mysqli_query($conn, $create_quotation_items)) {
        echo "<p>✅ Quotation items table created successfully!</p>";
    } else {
        echo "<p>❌ Error creating quotation items table: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>✅ Quotation items table exists</p>";
}

// Add some sample products if products table is empty
$count_query = "SELECT COUNT(*) as count FROM products";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);

if ($count_row['count'] == 0) {
    echo "<p>📦 Adding sample products...</p>";
    
    $sample_products = [
        ['Laptop Computer', 'High-performance laptop for business use', 45000.00],
        ['Desktop Monitor', '24-inch LED monitor with full HD resolution', 12000.00],
        ['Wireless Mouse', 'Ergonomic wireless mouse with USB receiver', 800.00],
        ['Keyboard', 'Mechanical keyboard with backlight', 2500.00],
        ['Printer', 'All-in-one inkjet printer with scanner', 8500.00]
    ];
    
    foreach ($sample_products as $product) {
        $insert_query = "INSERT INTO products (product_name, description, price, status) VALUES (?, ?, ?, 'Active')";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "ssd", $product[0], $product[1], $product[2]);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<p>✅ Added: {$product[0]}</p>";
        } else {
            echo "<p>❌ Failed to add: {$product[0]}</p>";
        }
    }
}

echo "<hr>";
echo "<h3>🎉 Database Setup Complete!</h3>";
echo "<p><strong>What's Ready:</strong></p>";
echo "<ul>";
echo "<li>✅ Products table with sample data</li>";
echo "<li>✅ Quotations table ready for use</li>";
echo "<li>✅ Quotation items table with proper relations</li>";
echo "</ul>";

echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li><a href='add_product.php'>Add Product</a> - Should now work properly</li>";
echo "<li><a href='add_quotation.php'>Add Quotation</a> - Should show product dropdown</li>";
echo "<li><a href='product_list.php'>View Products</a> - Check your products</li>";
echo "</ol>";
?>
