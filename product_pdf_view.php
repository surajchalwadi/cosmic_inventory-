<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';

// Check if selected products are passed
$selected_products = [];
$is_selected_only = false;

if (isset($_GET['selected']) && !empty($_GET['selected'])) {
    $selected_data = json_decode(urldecode($_GET['selected']), true);
    if ($selected_data && is_array($selected_data)) {
        $selected_products = $selected_data;
        $is_selected_only = true;
    }
}

// If no selected products, fetch all products
if (!$is_selected_only) {
    $products_query = "SELECT * FROM products ORDER BY created_at DESC";
    $products_result = mysqli_query($conn, $products_query);
    
    if (!$products_result) {
        echo 'Error fetching products';
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products List - Cosmic Solutions</title>
    <style>
        /* A4 canvas tuned for html2pdf (jsPDF A4 portrait) */
        html, body { margin: 0; padding: 0; background: #ffffff; font-family: Arial, Helvetica, sans-serif; color: #111; }
        #pdf-root { width: 210mm; min-height: 297mm; padding: 14mm 12mm; box-sizing: border-box; }
        .header { background: #ffffff; color: #1d1d1f; padding: 30px; border-bottom: 3px solid #155ba3; }
        .company-info { display: flex; justify-content: space-between; }
        .company-logo { font-size: 24px; font-weight: bold; color: #155ba3; }
        .report-title { text-align: right; font-size: 28px; font-weight: bold; color: #155ba3; }
        .content { padding: 30px; }
        .products-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .products-table th { background: #155ba3; color: white; padding: 12px; text-align: left; }
        .products-table td { padding: 12px; border-bottom: 1px solid #eee; }
        .summary { background: #f8f9fa; padding: 20px; border-radius: 6px; margin-top: 30px; }
        .status-active { color: #28a745; font-weight: bold; }
        .status-inactive { color: #6c757d; font-weight: bold; }
    </style>
</head>
<body>
    <div id="pdf-root">
        <div class="header">
            <div class="company-info">
                <div>
                    <?php 
                        $logoCandidates = ['assets/img/logo.png', 'assets/img/logo-cosmic.png'];
                        $logoPath = null;
                        foreach ($logoCandidates as $candidate) {
                            if (file_exists($candidate)) { $logoPath = $candidate; break; }
                        }
                    ?>
                    <?php if ($logoPath): ?>
                        <img src="<?= $logoPath ?>?v=<?= @filemtime($logoPath) ?: time() ?>" alt="Cosmic Solutions" style="height:60px; margin-bottom:10px; display:block;">
                    <?php endif; ?>
                    <div class="company-logo">Cosmic Solutions</div>
                    <div style="font-size: 12px; line-height: 1.4;">
                        EF-102, 1st Floor, E-boshan Building<br>
                        Boshan Hotels, Opp. Bodgeshwar Temple<br>
                        Mapusa - Goa. GSTN: 30AAMFC9553C1ZN<br>
                        Goa 403507<br>
                        Email: prajyot@cosmicsolutions.co.in<br>
                        Phone: 8390831122
                    </div>
                </div>
                <div>
                    <div class="report-title">PRODUCTS LIST</div>
                                         <div style="text-align: right; font-size: 14px;">
                         <div><strong>Generated Date:</strong> <?= date('d-m-Y') ?></div>
                         <div><strong>Generated Time:</strong> <?= date('H:i:s') ?></div>
                         <div><strong>Total Products:</strong> <?= $is_selected_only ? count($selected_products) : mysqli_num_rows($products_result) ?></div>
                         <?php if ($is_selected_only): ?>
                         <div><strong>Type:</strong> Selected Products Only</div>
                         <?php endif; ?>
                     </div>
                </div>
            </div>
        </div>

        <div class="content">
            <table class="products-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 25%;">Product Name</th>
                        <th style="width: 40%;">Description</th>
                        <th style="width: 15%;">Price (₹)</th>
                        <th style="width: 15%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    $total_value = 0;
                    $active_count = 0;
                    $inactive_count = 0;
                    
                    if ($is_selected_only) {
                        // Display selected products
                        foreach ($selected_products as $product) {
                            $price_value = (float) str_replace(['₹', ','], '', $product['price']);
                            $total_value += $price_value;
                            if ($product['status'] == 'Active') {
                                $active_count++;
                            } else {
                                $inactive_count++;
                            }
                    ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td style="font-weight: bold; color: #155ba3;"><?= htmlspecialchars($product['name']) ?></td>
                            <td style="font-size: 13px; color: #666;"><?= htmlspecialchars($product['description']) ?></td>
                            <td style="text-align: right;"><?= $product['price'] ?></td>
                            <td class="<?= $product['status'] == 'Active' ? 'status-active' : 'status-inactive' ?>">
                                <?= htmlspecialchars($product['status']) ?>
                            </td>
                        </tr>
                    <?php 
                        }
                    } else {
                        // Display all products from database
                        while ($product = mysqli_fetch_assoc($products_result)): 
                            $total_value += $product['price'];
                            if ($product['status'] == 'Active') {
                                $active_count++;
                            } else {
                                $inactive_count++;
                            }
                    ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td style="font-weight: bold; color: #155ba3;"><?= htmlspecialchars($product['product_name']) ?></td>
                            <td style="font-size: 13px; color: #666;"><?= htmlspecialchars($product['description']) ?></td>
                            <td style="text-align: right;">₹<?= number_format($product['price'], 2) ?></td>
                            <td class="<?= $product['status'] == 'Active' ? 'status-active' : 'status-inactive' ?>">
                                <?= htmlspecialchars($product['status']) ?>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    }
                    ?>
                </tbody>
            </table>

            <div class="summary">
                <h3 style="color: #155ba3; margin-bottom: 20px;">Summary</h3>
                <div style="display: flex; justify-content: space-between;">
                    <div>
                        <p><strong>Total Products:</strong> <?= $is_selected_only ? count($selected_products) : mysqli_num_rows($products_result) ?></p>
                        <p><strong>Active Products:</strong> <?= $active_count ?></p>
                        <p><strong>Inactive Products:</strong> <?= $inactive_count ?></p>
                    </div>
                    <div>
                        <p><strong>Total Inventory Value:</strong> ₹<?= number_format($total_value, 2) ?></p>
                        <p><strong>Average Price:</strong> ₹<?= 
                            ($is_selected_only ? count($selected_products) : mysqli_num_rows($products_result)) > 0 
                            ? number_format($total_value / ($is_selected_only ? count($selected_products) : mysqli_num_rows($products_result)), 2) 
                            : '0.00' 
                        ?></p>
                    </div>
                </div>
            </div>

            <div style="text-align: center; padding: 20px; background: #f8f9fa; border-top: 1px solid #eee; margin-top: 30px; font-size: 14px; color: #666;">
                This report was generated by Cosmic Solutions Inventory Management System
            </div>
        </div>
    </div>
</body>
</html>
