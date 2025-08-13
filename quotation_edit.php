<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'sales'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';
$role = $_SESSION['user']['role'];

// Get estimate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid estimate ID.";
    header("Location: quotation_list.php");
    exit;
}

$estimate_id = intval($_GET['id']);

// Fetch estimate data
$estimate_query = "SELECT * FROM estimates WHERE estimate_id = ?";
$stmt = mysqli_prepare($conn, $estimate_query);
mysqli_stmt_bind_param($stmt, "i", $estimate_id);
mysqli_stmt_execute($stmt);
$estimate_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($estimate_result) == 0) {
    $_SESSION['error'] = "Estimate not found.";
    header("Location: quotation_list.php");
    exit;
}

$estimate = mysqli_fetch_assoc($estimate_result);

// Fetch estimate items
$items_query = "SELECT * FROM estimate_items WHERE estimate_id = ?";
$items_stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($items_stmt, "i", $estimate_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);

// Fetch active products for dropdown
$products = [];
$products_query = "SELECT product_id, product_name, price FROM products WHERE status = 'Active' ORDER BY product_name";
$products_result = mysqli_query($conn, $products_query);

if ($products_result) {
    while ($product = mysqli_fetch_assoc($products_result)) {
        $products[] = $product;
    }
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
                        <i class="fas fa-list me-1"></i> View Quotations
                    </a>
                </div>
            </div>
            <small class="text-muted">Reference: <?= htmlspecialchars($estimate['estimate_number']) ?></small>

            <form action="quotation_update.php" method="POST">
                <!-- Client Details Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Client Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Client Name</label>
                                <input type="text" name="bill_client_name" class="form-control" 
                                value="<?= htmlspecialchars($estimate['bill_client_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" 
                                value="<?= htmlspecialchars($estimate['phone'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Company</label>
                                <input type="text" name="bill_company" class="form-control" 
                                value="<?= htmlspecialchars($estimate['bill_company']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" 
                                value="<?= htmlspecialchars($estimate['email'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label class="form-label">Address</label>
                                <textarea name="bill_address" class="form-control" rows="3"><?= htmlspecialchars($estimate['bill_address']) ?></textarea>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label class="form-label">Additional Information</label>
                                <textarea name="estimate_comments" class="form-control" rows="3"><?= htmlspecialchars($estimate['estimate_comments']) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quotation Details Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Quotation Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Quotation #</label>
                                <input type="text" name="estimate_number" class="form-control" 
                                value="<?= htmlspecialchars($estimate['estimate_number']) ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Quotation Date</label>
                                <input type="date" name="estimate_date" class="form-control" 
                                value="<?= $estimate['estimate_date'] ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="Draft" <?= $estimate['status'] == 'Draft' ? 'selected' : '' ?>>Draft</option>
                                    <option value="Sent" <?= $estimate['status'] == 'Sent' ? 'selected' : '' ?>>Sent</option>
                                    <option value="Approved" <?= $estimate['status'] == 'Approved' ? 'selected' : '' ?>>Approved</option>
                                    <option value="Rejected" <?= $estimate['status'] == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <label class="form-label">Currency</label>
                                <select name="currency" class="form-control">
                                    <option value="INR" <?= $estimate['currency'] == 'INR' ? 'selected' : '' ?>>₹ (INR) India Rupees</option>
                                    <option value="USD" <?= $estimate['currency'] == 'USD' ? 'selected' : '' ?>>$ (USD) US Dollar</option>
                                    <option value="EUR" <?= $estimate['currency'] == 'EUR' ? 'selected' : '' ?>>€ (EUR) Euro</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Global Tax (%)</label>
                                <input type="number" name="global_tax" class="form-control" step="0.01" 
                                value="<?= $estimate['global_tax'] ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Salesperson</label>
                                <input type="text" name="salesperson" class="form-control" 
                                value="<?= htmlspecialchars($estimate['salesperson']) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-list me-2"></i>Items</h6>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addItem()">
                            <i class="fas fa-plus me-1"></i> Add Item
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="30%">Description</th>
                                        <th width="10%">Quantity</th>
                                        <th width="15%">Unit Price</th>
                                        <th width="10%">Tax/Discount</th>
                                        <th width="15%">Amount</th>
                                        <th width="10%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                                    <tr>
                                        <td>
                                            <textarea name="items[description][]" class="form-control" rows="2" required><?= htmlspecialchars($item['product_description']) ?></textarea>
                                        </td>
                                        <td>
                                            <input type="number" name="items[quantity][]" class="form-control quantity" step="0.01" 
                                            value="<?= $item['quantity'] ?>" required>
                                        </td>
                                        <td>
                                            <input type="number" name="items[unit_price][]" class="form-control unit-price" step="0.01" 
                                            value="<?= $item['unit_price'] ?>" required>
                                        </td>
                                        <td>
                                            <select name="items[tax_discount_type][]" class="form-control tax-discount-type">
                                                <option value="Select" <?= $item['tax_discount_type'] == 'Select' ? 'selected' : '' ?>>Select</option>
                                                <option value="Tax" <?= $item['tax_discount_type'] == 'Tax' ? 'selected' : '' ?>>Tax</option>
                                                <option value="Discount" <?= $item['tax_discount_type'] == 'Discount' ? 'selected' : '' ?>>Discount</option>
                                            </select>
                                            <input type="number" name="items[tax_discount_value][]" class="form-control mt-1" step="0.01" 
                                            value="<?= $item['tax_discount_value'] ?>">
                                        </td>
                                        <td>
                                            <input type="number" name="items[amount][]" class="form-control amount" step="0.01" 
                                            value="<?= $item['amount'] ?>" readonly>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Totals Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-calculator me-2"></i>Totals</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 offset-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Subtotal:</strong></td>
                                        <td class="text-end">
                                            <input type="number" name="subtotal" class="form-control text-end" step="0.01" 
                                            value="<?= $estimate['subtotal'] ?>" readonly>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tax Amount:</strong></td>
                                        <td class="text-end">
                                            <input type="number" name="tax_amount" class="form-control text-end" step="0.01" 
                                            value="<?= $estimate['tax_amount'] ?>" readonly>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Discount Amount:</strong></td>
                                        <td class="text-end">
                                            <input type="number" name="discount_amount" class="form-control text-end" step="0.01" 
                                            value="<?= $estimate['discount_amount'] ?>" readonly>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total Amount:</strong></td>
                                        <td class="text-end">
                                            <input type="number" name="total_amount" class="form-control text-end" step="0.01" 
                                            value="<?= $estimate['total_amount'] ?>" readonly>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between">
                    <a href="quotation_list.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i> Update Quotation
                    </button>
                </div>

                <input type="hidden" name="estimate_id" value="<?= $estimate_id ?>">
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ... existing code ...
</script>

</body>
</html>
