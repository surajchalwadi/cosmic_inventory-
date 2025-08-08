<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'inventory'])) {
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

// No auto-generation needed - user will type manually
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
                <h4 class="form-title"><i class="fas fa-truck me-2"></i>Purchase Inward Entry</h4>
            </div>

            <form action="purchase_save.php" method="POST">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Party Name</label>
                        <input type="text" name="party_name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label>Invoice No. <span class="text-danger">*</span></label>
                        <input type="text" name="invoice_no" class="form-control" placeholder="Enter invoice number" required>
                        <small class="text-muted">Enter unique invoice number (e.g., INV-2024-001)</small>
                    </div>
                    <div class="col-md-4">
                        <label>Delivery Date</label>
                        <input type="date" name="delivery_date" class="form-control" required>
                    </div>
                </div>

                <h5 class="section-subtitle"><i class="fas fa-box-open me-2"></i>Product Items</h5>
                <div id="product-items">
                    <div class="row product-row align-items-end mb-3 bg-light p-3 rounded">
                        <div class="col-md-4 mb-2">
                            <label>Product Name</label>
                            <select name="product_name[]" class="form-control product-select" required onchange="updatePrice(this)">
                                <option value="">Select Product</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?= htmlspecialchars($product['product_name']) ?>" 
                                            data-price="<?= $product['price'] ?>">
                                        <?= htmlspecialchars($product['product_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
                            <button type="button" class="btn btn-outline-danger btn-sm remove-row"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>

                <button type="button" id="add-row" class="btn btn-outline-primary btn-sm mb-4"><i class="fas fa-plus"></i> Add More Items</button>

                <div class="mb-3">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Optional..."></textarea>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Save Purchase</button>
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

// Real-time invoice number validation
$('input[name="invoice_no"]').on('blur', function() {
    const invoiceNo = $(this).val().trim();
    if (invoiceNo) {
        $.ajax({
            url: 'check_invoice.php',
            type: 'POST',
            data: { invoice_no: invoiceNo },
            success: function(response) {
                if (!response.available) {
                    $('input[name="invoice_no"]').addClass('is-invalid');
                    $('input[name="invoice_no"]').next('.invalid-feedback').remove();
                    $('input[name="invoice_no"]').after('<div class="invalid-feedback">This invoice number already exists.</div>');
                } else {
                    $('input[name="invoice_no"]').removeClass('is-invalid');
                    $('input[name="invoice_no"]').next('.invalid-feedback').remove();
                }
            }
        });
    }
});
</script>
</body>
</html>
