<?php
// Remove sample products that were created during setup
include 'config/db.php';

echo "<h2>Removing Sample Products</h2>";

// List of sample products that were created during setup
$sample_products = [
    'Macbook Pro',
    'iPhone 14', 
    'Samsung Galaxy S23',
    'Apple AirPods',
    'Dell XPS 13',
    'iPad Air'
];

$removed_count = 0;
$not_found_count = 0;

foreach ($sample_products as $product_name) {
    // Check if product exists and get its details
    $check_query = "SELECT product_id, product_name FROM products WHERE product_name = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "s", $product_name);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Check if product is being used in quotations or purchases
        $usage_check = "
            SELECT 'quotation' as type, COUNT(*) as count FROM quotation_items WHERE product_name = ?
            UNION ALL
            SELECT 'purchase' as type, COUNT(*) as count FROM purchase_items WHERE product_name = ?
        ";
        $usage_stmt = mysqli_prepare($conn, $usage_check);
        mysqli_stmt_bind_param($usage_stmt, "ss", $product_name, $product_name);
        mysqli_stmt_execute($usage_stmt);
        $usage_result = mysqli_stmt_get_result($usage_stmt);
        
        $total_usage = 0;
        while ($usage_row = mysqli_fetch_assoc($usage_result)) {
            $total_usage += $usage_row['count'];
        }
        
        if ($total_usage > 0) {
            echo "<p style='color: orange;'>⚠️ Skipped '$product_name' - being used in $total_usage record(s)</p>";
        } else {
            // Delete the product
            $delete_query = "DELETE FROM products WHERE product_name = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "s", $product_name);
            
            if (mysqli_stmt_execute($delete_stmt) && mysqli_affected_rows($conn) > 0) {
                echo "<p style='color: green;'>✅ Removed '$product_name'</p>";
                $removed_count++;
            } else {
                echo "<p style='color: red;'>❌ Failed to remove '$product_name'</p>";
            }
        }
    } else {
        echo "<p style='color: gray;'>ℹ️ '$product_name' not found (may have been already removed)</p>";
        $not_found_count++;
    }
}

echo "<hr>";
echo "<h3>Cleanup Complete!</h3>";
echo "<p><strong>Summary:</strong></p>";
echo "<ul>";
echo "<li>✅ Removed: $removed_count products</li>";
echo "<li>ℹ️ Not found: $not_found_count products</li>";
echo "<li>⚠️ Skipped: " . (count($sample_products) - $removed_count - $not_found_count) . " products (in use)</li>";
echo "</ul>";

echo "<p>Your custom products have been preserved.</p>";
echo "<p><a href='product_list.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Product List</a></p>";
?>
