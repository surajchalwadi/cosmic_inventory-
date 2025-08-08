<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'sales'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';
$role = $_SESSION['user']['role'];

// Fetch active products for dropdown
$products_query = "SELECT product_id, product_name, price FROM products WHERE status = 'Active' ORDER BY product_name";
$products_result = mysqli_query($conn, $products_query);
$products = [];
while ($product = mysqli_fetch_assoc($products_result)) {
    $products[] = $product;
}

// Get quotation ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid quotation ID.";
    header("Location: quotation_list.php");
    exit;
}

$quotation_id = intval($_GET['id']);

// Fetch quotation data
$quotation_query = "SELECT * FROM quotations WHERE quotation_id = ?";
$stmt = mysqli_prepare($conn, $quotation_query);
mysqli_stmt_bind_param($stmt, "i", $quotation_id);
mysqli_stmt_execute($stmt);
$quotation_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($quotation_result) == 0) {
    $_SESSION['error'] = "Quotation not found.";
    header("Location: quotation_list.php");
    exit;
}

$quotation = mysqli_fetch_assoc($quotation_result);

// Fetch quotation items
$items_query = "SELECT * FROM quotation_items WHERE quotation_id = ?";
$items_stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($items_stmt, "i", $quotation_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);
$items = [];
while ($row = mysqli_fetch_assoc($items_result)) {
    $items[] = $row;
}
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
                <h4 class="form-title"><i class="fas fa-edit me-2"></i>Edit Quotation</h4>
                <div>
                    <a href="quotation_list.php" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                    <small class="text-muted">Reference: <?= htmlspecialchars($quotation['reference']) ?></small>
                </div>
            </div>

            <form action="quotation_update.php" method="POST">
                <!-- Customer Information Section -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Customer Name</label>
                        <div class="input-group">
                            <input type="text" name="customer_name" class="form-control" 
                                   value="<?= htmlspecialchars($quotation['customer_name']) ?>" required>
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#customerModal">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" 
                               value="<?= htmlspecialchars($quotation['phone']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Company</label>
                        <input type="text" name="company" class="form-control" 
                               value="<?= htmlspecialchars($quotation['company']) ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Contact Person</label>
                        <textarea name="contact_person" class="form-control" rows="3"><?= htmlspecialchars($quotation['contact_person']) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($quotation['address']) ?></textarea>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <label class="form-label">Additional Info</label>
                        <textarea name="additional_info" class="form-control" rows="3"><?= htmlspecialchars($quotation['additional_info']) ?></textarea>
                    </div>
                </div>

                <!-- Product Section -->
                <h5 class="section-subtitle"><i class="fas fa-box-open me-2"></i>Product Items</h5>
                <div id="product-items">
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $index => $item): ?>
                            <div class="row product-row align-items-end mb-3 bg-light p-3 rounded">
                                <div class="col-md-4 mb-2">
                                    <label>Product Name</label>
                                    <select name="product_name[]" class="form-control product-select" required onchange="updatePrice(this)">
                                        <option value="">Select Product</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?= htmlspecialchars($product['product_name']) ?>" 
                                                    data-price="<?= $product['price'] ?>"
                                                    <?= $product['product_name'] == $item['product_name'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($product['product_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label>Quantity</label>
                                    <input type="number" name="quantity[]" class="form-control" 
                                           value="<?= $item['quantity'] ?>" required>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label>Price (₹)</label>
                                    <input type="number" step="0.01" name="price[]" class="form-control" 
                                           value="<?= $item['price'] ?>" required>
                                </div>
                                <div class="col-md-2 text-end">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-row">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="row product-row align-items-end mb-3 bg-light p-3 rounded">
                            <div class="col-md-4 mb-2">
                                <label>Product Name</label>
                                <input type="text" name="product_name[]" class="form-control" required>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label>Quantity</label>
                                <input type="number" name="quantity[]" class="form-control" required>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label>Price (₹)</label>
                                <input type="number" step="0.01" name="price[]" class="form-control" required>
                            </div>
                            <div class="col-md-2 text-end">
                                <button type="button" class="btn btn-outline-danger btn-sm remove-row">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="button" id="add-row" class="btn btn-outline-primary btn-sm mb-4">
                    <i class="fas fa-plus"></i> Add More Items
                </button>

                <!-- Financial Details -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Margin %</label>
                        <input type="number" name="margin_percent" class="form-control" step="0.01" min="0" max="100" 
                               value="<?= $quotation['margin_percent'] ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Discount %</label>
                        <input type="number" name="discount_percent" class="form-control" step="0.01" min="0" max="100" 
                               value="<?= $quotation['discount_percent'] ?>">
                    </div>
                </div>

                <!-- Follow-up Section -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Follow-up Date</label>
                        <input type="date" name="follow_up_date" class="form-control" 
                               value="<?= $quotation['follow_up_date'] ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Follow-up Method</label>
                        <select name="follow_up_method" class="form-control">
                            <option value="Call" <?= $quotation['follow_up_method'] == 'Call' ? 'selected' : '' ?>>Call</option>
                            <option value="Email" <?= $quotation['follow_up_method'] == 'Email' ? 'selected' : '' ?>>Email</option>
                            <option value="Visit" <?= $quotation['follow_up_method'] == 'Visit' ? 'selected' : '' ?>>Visit</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Follow-up Notes</label>
                        <textarea name="follow_up_notes" class="form-control" rows="1"><?= htmlspecialchars($quotation['follow_up_notes']) ?></textarea>
                    </div>
                </div>

                <!-- Status Section -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="Pending" <?= $quotation['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Sent" <?= $quotation['status'] == 'Sent' ? 'selected' : '' ?>>Sent</option>
                            <option value="Delivered" <?= $quotation['status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                        </select>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-end gap-2">
                    <a href="quotation_list.php" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Update Quotation
                    </button>
                </div>

                <input type="hidden" name="quotation_id" value="<?= $quotation_id ?>">
                <input type="hidden" name="reference" value="<?= htmlspecialchars($quotation['reference']) ?>">
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
    row.find('select').val('');
    $('#product-items').append(row);
});

$(document).on('click', '.remove-row', function () {
    if ($('.product-row').length > 1) {
        $(this).closest('.product-row').remove();
    }
});

// Auto-populate price when product is selected
function updatePrice(selectElement) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const price = selectedOption.getAttribute('data-price');
    const row = selectElement.closest('.product-row');
    const priceInput = row.querySelector('input[name="price[]"]');
    
    if (price && priceInput) {
        priceInput.value = price;
    }
}
</script>

</body>
</html>
