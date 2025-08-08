<?php
// Fix quotation database schema to match current add quotation page
include 'config/db.php';

echo "<h2>Fixing Quotation Database Schema</h2>";

// Drop and recreate quotation_items table with correct structure
$drop_table = "DROP TABLE IF EXISTS quotation_items";
if (mysqli_query($conn, $drop_table)) {
    echo "<p style='color: green;'>✅ Old quotation_items table dropped successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Error dropping table: " . mysqli_error($conn) . "</p>";
}

// Create new quotation_items table with simplified structure (matching purchase page)
$create_quotation_items = "
CREATE TABLE quotation_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    quotation_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (quotation_id) REFERENCES quotations(quotation_id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $create_quotation_items)) {
    echo "<p style='color: green;'>✅ New quotation_items table created successfully!</p>";
    echo "<p><strong>New Structure:</strong></p>";
    echo "<ul>";
    echo "<li>product_name - VARCHAR(255)</li>";
    echo "<li>quantity - INT</li>";
    echo "<li>price - DECIMAL(10,2)</li>";
    echo "<li>total_amount - DECIMAL(10,2)</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red;'>❌ Error creating quotation_items table: " . mysqli_error($conn) . "</p>";
}

echo "<hr>";
echo "<h3>Schema Fix Complete!</h3>";
echo "<p>The quotation_items table now matches the current Add Quotation page structure.</p>";
echo "<p><a href='add_quotation.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Add Quotation</a></p>";
echo "<p><a href='quotation_list.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Quotation List</a></p>";
?>
