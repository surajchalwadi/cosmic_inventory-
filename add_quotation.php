<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'sales'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';
$role = $_SESSION['user']['role'];

// Fetch active products for dropdown
$products = [];
$products_query = "SELECT product_id, product_name, price FROM products WHERE status = 'Active' ORDER BY product_name";
$products_result = mysqli_query($conn, $products_query);

if ($products_result) {
    while ($product = mysqli_fetch_assoc($products_result)) {
        $products[] = $product;
    }
} else {
    // If products table doesn't exist, create a note
    $products_error = "Products table not found. Please run product_setup.php first.";
}

// Generate unique reference number
$reference = 'QT' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
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
                <h4 class="form-title"><i class="fas fa-file-invoice me-2"></i>Quotation Add</h4>
                <div>
                    <a href="quotation_list.php" class="btn btn-secondary me-2">
                        <i class="fas fa-list me-1"></i> View Quotations
                    </a>
                </div>
            </div>

            <form action="quotation_save.php" method="POST">
                <!-- Customer Information Section -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Customer Name</label>
                        <div class="input-group">
                            <input type="text" name="customer_name" class="form-control" placeholder="Select Customer" required>
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#customerModal">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" placeholder="Phone Number">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Company</label>
                        <input type="text" name="company" class="form-control" placeholder="Company Name">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Contact Person</label>
                        <textarea name="contact_person" class="form-control" rows="3" placeholder="Contact Person Details"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3" placeholder="Customer Address"></textarea>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <label class="form-label">Additional Info</label>
                        <textarea name="additional_info" class="form-control" rows="3" placeholder="Additional Information"></textarea>
                    </div>
                </div>

                <!-- Product Section -->
                <h5 class="section-subtitle"><i class="fas fa-box-open me-2"></i>Product Items</h5>
                <div id="product-items">
                    <div class="row product-row align-items-end mb-3 bg-light p-3 rounded">
                        <div class="col-md-4 mb-2">
                            <label>Product Name</label>
                            <?php if (!empty($products)): ?>
                                <select name="product_name[]" class="form-control product-select" required onchange="updatePrice(this)">
                                    <option value="">Select Product</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?= htmlspecialchars($product['product_name']) ?>" 
                                                data-price="<?= $product['price'] ?>">
                                            <?= htmlspecialchars($product['product_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="text" name="product_name[]" class="form-control" required placeholder="Enter product name">
                                <small class="text-muted">No products found. <a href="product_setup.php">Setup products</a> or enter manually.</small>
                            <?php endif; ?>
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

                <!-- Financial Details -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Margin %</label>
                        <input type="number" name="margin_percent" class="form-control" step="0.01" min="0" max="100" value="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Discount %</label>
                        <input type="number" name="discount_percent" class="form-control" step="0.01" min="0" max="100" value="0">
                    </div>
                </div>

                <!-- Follow-up Section -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Follow-up Date</label>
                        <input type="date" name="follow_up_date" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Follow-up Method</label>
                        <select name="follow_up_method" class="form-control">
                            <option value="Call">Call</option>
                            <option value="Email">Email</option>
                            <option value="Visit">Visit</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Follow-up Notes</label>
                        <textarea name="follow_up_notes" class="form-control" rows="1" placeholder="Follow-up notes"></textarea>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-warning" id="save-draft-btn">
                        <i class="fas fa-save me-1"></i> Save as Draft
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane me-1"></i> Send Quotation
                    </button>
                    <a href="quotation_list.php" class="btn btn-secondary me-2">
                        <i class="fas fa-list me-1"></i> View Quotations
                    </a>
                </div>

                <input type="hidden" name="reference" value="<?= $reference ?>">
                <input type="hidden" name="action_type" value="send" id="action-type">
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

// Auto-populate price when product is selected (only works with dropdown)
function updatePrice(selectElement) {
    if (selectElement && selectElement.options) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const price = selectedOption.getAttribute('data-price');
        const row = selectElement.closest('.product-row');
        const priceInput = row.querySelector('input[name="price[]"]');
        
        if (price && priceInput) {
            priceInput.value = price;
        }
    }
}

// Handle Save as Draft button
$('#save-draft-btn').click(function() {
    // Set action type to draft
    $('#action-type').val('draft');
    
    // Remove required attributes temporarily for draft save
    $('input[required], select[required], textarea[required]').each(function() {
        $(this).removeAttr('required');
        $(this).attr('data-was-required', 'true');
    });
    
    // Submit the form
    $('form').submit();
});

// Restore required attributes when sending quotation
$('form').on('submit', function() {
    if ($('#action-type').val() === 'send') {
        $('input[data-was-required], select[data-was-required], textarea[data-was-required]').each(function() {
            $(this).attr('required', 'required');
            $(this).removeAttr('data-was-required');
        });
    }
});
</script>

</body>
</html>
