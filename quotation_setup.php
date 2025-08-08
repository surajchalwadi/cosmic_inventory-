<?php
// Quotation database setup
include 'config/db.php';

echo "<h2>Setting up Quotation Tables</h2>";

// Create quotations table
$create_quotations = "
CREATE TABLE IF NOT EXISTS quotations (
    quotation_id INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(100) NOT NULL UNIQUE,
    customer_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    company VARCHAR(255),
    contact_person VARCHAR(255),
    address TEXT,
    additional_info TEXT,
    margin_percent DECIMAL(5,2) DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    follow_up_date DATE,
    follow_up_method ENUM('Call', 'Email', 'Visit') DEFAULT 'Call',
    follow_up_notes TEXT,
    status ENUM('Sent', 'Delivered', 'Pending') DEFAULT 'Pending',
    grand_total DECIMAL(10,2) DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $create_quotations)) {
    echo "<p style='color: green;'>✅ Quotations table created successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating quotations table: " . mysqli_error($conn) . "</p>";
}

// Create quotation_items table
$create_quotation_items = "
CREATE TABLE IF NOT EXISTS quotation_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    quotation_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    model VARCHAR(100),
    quantity INT NOT NULL,
    base_price DECIMAL(10,2) NOT NULL,
    final_price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (quotation_id) REFERENCES quotations(quotation_id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $create_quotation_items)) {
    echo "<p style='color: green;'>✅ Quotation items table created successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating quotation items table: " . mysqli_error($conn) . "</p>";
}

echo "<p><a href='quotation_list.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Quotation List</a></p>";
echo "<p><a href='add_quotation.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Add New Quotation</a></p>";
?>
