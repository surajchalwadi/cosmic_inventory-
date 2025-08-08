<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'inventory'])) {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['user']['role'];

// Get purchase ID from URL
$purchase_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$purchase_id) {
    header("Location: purchase_list.php");
    exit;
}

// Get purchase details
$sql = "SELECT pi.*, u.name as created_by_name 
        FROM purchase_invoices pi 
        LEFT JOIN users u ON pi.created_by = u.user_id 
        WHERE pi.purchase_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $purchase_id);
mysqli_stmt_execute($stmt);
$purchase = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$purchase) {
    header("Location: purchase_list.php");
    exit;
}

// Get purchase items
$sql = "SELECT * FROM purchase_items WHERE purchase_id = ? ORDER BY item_id";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $purchase_id);
mysqli_stmt_execute($stmt);
$items = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'head.php'; ?>
<body>

<?php include 'sidebar.php'; ?>
<div class="main">
    <?php include 'header.php'; ?>
    <div class="container-fluid py-4">
        <div class="form-section">
            <div class="form-header d-flex justify-content-between align-items-center mb-4">
                <h4 class="form-title"><i class="fas fa-eye me-2"></i>Purchase Details</h4>
                <a href="purchase_list.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to List
                </a>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Purchase Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Party Name:</strong> <?= htmlspecialchars($purchase['party_name']) ?></p>
                                    <p><strong>Invoice No:</strong> <?= htmlspecialchars($purchase['invoice_no']) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Delivery Date:</strong> <?= date('d-m-Y', strtotime($purchase['delivery_date'])) ?></p>
                                    <p><strong>Created By:</strong> <?= htmlspecialchars($purchase['created_by_name']) ?></p>
                                </div>
                            </div>
                            <?php if (!empty($purchase['notes'])): ?>
                                <div class="mt-3">
                                    <strong>Notes:</strong>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($purchase['notes'])) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Summary</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Total Items:</strong> <?= mysqli_num_rows($items) ?></p>
                            <p><strong>Created Date:</strong> <?= date('d-m-Y H:i', strtotime($purchase['created_at'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Product Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th>#</th>
                                    <th>Product Name</th>
                                    <th>Quantity</th>
                                    <th>Price (₹)</th>
                                    <th>Total (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $i = 1;
                                $grand_total = 0;
                                while ($item = mysqli_fetch_assoc($items)): 
                                    $grand_total += $item['total_price'];
                                ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td><?= number_format($item['price'], 2) ?></td>
                                    <td><?= number_format($item['total_price'], 2) ?></td>
                                </tr>
                                <?php endwhile; ?>
                                <tr class="table-info">
                                    <td colspan="4" class="text-end"><strong>Grand Total:</strong></td>
                                    <td><strong>₹<?= number_format($grand_total, 2) ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html> 