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
$sql = "SELECT * FROM purchase_invoices WHERE purchase_id = ?";
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
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <div class="form-header d-flex justify-content-between align-items-center mb-4">
                <h4 class="form-title"><i class="fas fa-edit me-2"></i>Edit Purchase</h4>
                <a href="purchase_list.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to List
                </a>
            </div>

            <form action="purchase_update.php" method="POST">
                <input type="hidden" name="purchase_id" value="<?= $purchase_id ?>">
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Party Name</label>
                        <input type="text" name="party_name" class="form-control" value="<?= htmlspecialchars($purchase['party_name']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label>Invoice No. <span class="text-danger">*</span></label>
                        <input type="text" name="invoice_no" class="form-control" value="<?= htmlspecialchars($purchase['invoice_no']) ?>" required>
                        <small class="text-muted">Enter unique invoice number</small>
                    </div>
                    <div class="col-md-4">
                        <label>Delivery Date</label>
                        <input type="date" name="delivery_date" class="form-control" value="<?= $purchase['delivery_date'] ?>" required>
                    </div>
                </div>

                <h5 class="section-subtitle"><i class="fas fa-box-open me-2"></i>Product Items</h5>
                <div id="product-items">
                    <?php 
                    $item_count = 0;
                    while ($item = mysqli_fetch_assoc($items)): 
                        $item_count++;
                    ?>
                    <div class="row product-row align-items-end mb-3 bg-light p-3 rounded">
                        <input type="hidden" name="item_id[]" value="<?= $item['item_id'] ?>">
                        <div class="col-md-4 mb-2">
                            <label>Product Name</label>
                            <input type="text" name="product_name[]" class="form-control" value="<?= htmlspecialchars($item['product_name']) ?>" required>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Quantity</label>
                            <input type="number" name="quantity[]" class="form-control" value="<?= $item['quantity'] ?>" required>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Price (₹)</label>
                            <input type="number" step="0.01" name="price[]" class="form-control" value="<?= $item['price'] ?>" required>
                        </div>
                        <div class="col-md-2 text-end">
                            <?php if ($item_count > 1): ?>
                                <button type="button" class="btn btn-outline-danger btn-sm remove-row"><i class="fas fa-trash"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <button type="button" id="add-row" class="btn btn-outline-primary btn-sm mb-4"><i class="fas fa-plus"></i> Add More Items</button>

                <div class="mb-3">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Optional..."><?= htmlspecialchars($purchase['notes']) ?></textarea>
                </div>

                <div class="text-end">
                    <a href="purchase_list.php" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Update Purchase</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$('#add-row').click(function() {
    let row = $('.product-row').first().clone();
    row.find('input').val('');
    row.find('input[name="item_id[]"]').val(''); // Clear item_id for new rows
    row.find('.remove-row').show(); // Show remove button for new rows
    $('#product-items').append(row);
});

$(document).on('click', '.remove-row', function () {
    if ($('.product-row').length > 1) {
        $(this).closest('.product-row').remove();
    }
});
</script>
</body>
</html> 