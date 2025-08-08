<?php
// Product management database setup
include 'config/db.php';

echo "<h2>Setting up Product Management Tables</h2>";

// Create products table
$create_products = "
CREATE TABLE IF NOT EXISTS products (
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
    echo "<p style='color: green;'>✅ Products table created successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating products table: " . mysqli_error($conn) . "</p>";
}

// Insert some sample products
$sample_products = [
    ['Macbook Pro', 'Apple MacBook Pro 13-inch with M2 chip', 1299.99],
    ['iPhone 14', 'Apple iPhone 14 128GB', 799.99],
    ['Samsung Galaxy S23', 'Samsung Galaxy S23 256GB', 899.99],
    ['Apple AirPods', 'Apple AirPods Pro 2nd Generation', 249.99],
    ['Dell XPS 13', 'Dell XPS 13 Laptop with Intel i7', 1199.99],
    ['iPad Air', 'Apple iPad Air 10.9-inch 64GB', 599.99]
];

$insert_query = "INSERT IGNORE INTO products (product_name, description, price, created_by) VALUES (?, ?, ?, 1)";
$stmt = mysqli_prepare($conn, $insert_query);

$inserted_count = 0;
foreach ($sample_products as $product) {
    mysqli_stmt_bind_param($stmt, "ssd", $product[0], $product[1], $product[2]);
    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_affected_rows($conn) > 0) {
            $inserted_count++;
        }
    }
}

if ($inserted_count > 0) {
    echo "<p style='color: green;'>✅ Inserted $inserted_count sample products!</p>";
} else {
    echo "<p style='color: blue;'>ℹ️ Sample products already exist.</p>";
}

echo "<hr>";
echo "<h3>Product Management Setup Complete!</h3>";
echo "<p><strong>Features Available:</strong></p>";
echo "<ul>";
echo "<li>Add new products with name, description, and price</li>";
echo "<li>View all products in a clean table format</li>";
echo "<li>Edit and delete products</li>";
echo "<li>Product dropdown integration in quotations and purchases</li>";
echo "</ul>";

echo "<p><a href='add_product.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Add Product</a></p>";
echo "<p><a href='product_list.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Product List</a></p>";
?>
