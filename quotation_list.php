<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'sales'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';
$role = $_SESSION['user']['role'];

// Fetch quotations
$query = "SELECT * FROM quotations ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
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
                <div>
                    <h4 class="form-title"><i class="fas fa-file-invoice me-2"></i>Quotation List</h4>
                    <small class="text-muted">Manage your Quotations</small>
                </div>
                <a href="add_quotation.php" class="btn btn-warning">
                    <i class="fas fa-plus me-1"></i> Add Quotation
                </a>
            </div>

            <!-- Search Bar -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-warning text-white">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" placeholder="Search..." id="searchInput">
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group">
                        <button class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-file-pdf"></i>
                        </button>
                        <button class="btn btn-outline-success btn-sm">
                            <i class="fas fa-file-excel"></i>
                        </button>
                        <button class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-print"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quotations Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="quotationTable">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th width="5%"></th>
                            <th width="20%">Product Name</th>
                            <th width="15%">Reference</th>
                            <th width="20%">Customer Name</th>
                            <th width="10%">Status</th>
                            <th width="15%">Grand Total ($)</th>
                            <th width="10%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <?php
                                // Get first product name for this quotation
                                $product_query = "SELECT product_name FROM quotation_items WHERE quotation_id = {$row['quotation_id']} LIMIT 1";
                                $product_result = mysqli_query($conn, $product_query);
                                $product = mysqli_fetch_assoc($product_result);
                                
                                // Determine product icon
                                $product_name = $product['product_name'] ?? 'Unknown Product';
                                $icon = '📦';
                                if (strpos(strtolower($product_name), 'macbook') !== false) $icon = '💻';
                                elseif (strpos(strtolower($product_name), 'orange') !== false) $icon = '🍊';
                                elseif (strpos(strtolower($product_name), 'strawberry') !== false) $icon = '🍓';
                                elseif (strpos(strtolower($product_name), 'iphone') !== false) $icon = '📱';
                                elseif (strpos(strtolower($product_name), 'samsung') !== false) $icon = '📱';
                                elseif (strpos(strtolower($product_name), 'earpods') !== false) $icon = '🎧';
                                
                                // Status badge
                                $status_class = '';
                                switch ($row['status']) {
                                    case 'Sent': $status_class = 'bg-success'; break;
                                    case 'Delivered': $status_class = 'bg-warning text-dark'; break;
                                    case 'Pending': $status_class = 'bg-danger'; break;
                                }
                                ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input row-checkbox">
                                    </td>
                                    <td>
                                        <span style="font-size: 1.5em;"><?= $icon ?></span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($product_name) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?= htmlspecialchars($row['reference']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                    <td>
                                        <span class="badge <?= $status_class ?>"><?= $row['status'] ?></span>
                                    </td>
                                    <td>
                                        <strong>$<?= number_format($row['grand_total'], 2) ?></strong>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="quotation_edit.php?id=<?= $row['quotation_id'] ?>" 
                                               class="btn btn-outline-primary btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="quotation_delete.php?id=<?= $row['quotation_id'] ?>" 
                                               class="btn btn-outline-danger btn-sm" 
                                               onclick="return confirm('Are you sure you want to delete this quotation?')" 
                                               title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-file-invoice fa-3x mb-3"></i>
                                        <h5>No Quotations Found</h5>
                                        <p>Start by creating your first quotation</p>
                                        <a href="add_quotation.php" class="btn btn-warning">
                                            <i class="fas fa-plus me-1"></i> Add Quotation
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination (if needed) -->
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">
                        Showing <?= mysqli_num_rows($result) ?> quotations
                    </small>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1">Previous</a>
                            </li>
                            <li class="page-item active">
                                <a class="page-link" href="#">1</a>
                            </li>
                            <li class="page-item disabled">
                                <a class="page-link" href="#">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const tableRows = document.querySelectorAll('#quotationTable tbody tr');
    
    tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Select all checkbox functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Individual checkbox handling
document.querySelectorAll('.row-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const allCheckboxes = document.querySelectorAll('.row-checkbox');
        const checkedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
        const selectAllCheckbox = document.getElementById('selectAll');
        
        selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length;
        selectAllCheckbox.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < allCheckboxes.length;
    });
});
</script>

</body>
</html>
