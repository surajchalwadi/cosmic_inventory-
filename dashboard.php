<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

include 'config/db.php';
$role = $_SESSION['user']['role'];
$name = $_SESSION['user']['name'];

// Get dynamic counts
$total_products = 0;
$pending_quotations = 0;
$open_invoices = 0;
$follow_ups = 0;

// Count total products
$products_query = "SELECT COUNT(*) as count FROM products WHERE status = 'Active'";
$products_result = mysqli_query($conn, $products_query);
if ($products_result) {
    $total_products = mysqli_fetch_assoc($products_result)['count'];
}

// Count pending quotations
$quotations_query = "SELECT COUNT(*) as count FROM estimates WHERE status IN ('Draft', 'Sent')";
$quotations_result = mysqli_query($conn, $quotations_query);
if ($quotations_result) {
    $pending_quotations = mysqli_fetch_assoc($quotations_result)['count'];
}

// Count open invoices (purchases)
$invoices_query = "SELECT COUNT(*) as count FROM purchase_invoices";
$invoices_result = mysqli_query($conn, $invoices_query);
if ($invoices_result) {
    $open_invoices = mysqli_fetch_assoc($invoices_result)['count'];
}

// Count follow-ups (quotations with follow-up dates)
$follow_ups_query = "SELECT COUNT(*) as count FROM estimates WHERE status = 'Sent'";
$follow_ups_result = mysqli_query($conn, $follow_ups_query);
if ($follow_ups_result) {
    $follow_ups = mysqli_fetch_assoc($follow_ups_result)['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'head.php';?>
<body>

<!-- Sidebar -->
<?php include 'sidebar.php';?>
<!-- Main Area -->
<div class="main">
   <?php include 'header.php';?>

    <!-- Role-specific dashboard content here -->
    <div class="container-fluid mt-4">
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card p-3 text-white bg-primary">
                    <h5>Total Products</h5>
                    <h3><?= $total_products ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 text-white bg-success">
                    <h5>Pending Quotations</h5>
                    <h3><?= $pending_quotations ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 text-white bg-warning">
                    <h5>Open Invoices</h5>
                    <h3><?= $open_invoices ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 text-white bg-danger">
                    <h5>Follow-ups</h5>
                    <h3><?= $follow_ups ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
