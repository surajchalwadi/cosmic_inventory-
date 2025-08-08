<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';
$role = $_SESSION['user']['role'];

// Fetch products
$query = "SELECT * FROM products ORDER BY created_at DESC";
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
                    <h4 class="form-title"><i class="fas fa-cube me-2"></i>Product List</h4>
                    <small class="text-muted">Manage your Products</small>
                </div>
                <a href="add_product.php" class="btn btn-warning">
                    <i class="fas fa-plus me-1"></i> Add Product
                </a>
            </div>

            <!-- Search Bar -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-warning text-white">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" placeholder="Search products..." id="searchInput">
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

            <!-- Products Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="productTable">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th width="5%"></th>
                            <th width="25%">Product Name</th>
                            <th width="35%">Description</th>
                            <th width="15%">Price (₹)</th>
                            <th width="10%">Status</th>
                            <th width="10%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <?php
                                // Determine product icon based on product name
                                $product_name = $row['product_name'];
                                $icon = '📦';
                                if (stripos($product_name, 'macbook') !== false || stripos($product_name, 'laptop') !== false) $icon = '💻';
                                elseif (stripos($product_name, 'iphone') !== false || stripos($product_name, 'phone') !== false) $icon = '📱';
                                elseif (stripos($product_name, 'samsung') !== false) $icon = '📱';
                                elseif (stripos($product_name, 'ipad') !== false || stripos($product_name, 'tablet') !== false) $icon = '📱';
                                elseif (stripos($product_name, 'airpods') !== false || stripos($product_name, 'headphone') !== false) $icon = '🎧';
                                elseif (stripos($product_name, 'watch') !== false) $icon = '⌚';
                                elseif (stripos($product_name, 'tv') !== false || stripos($product_name, 'monitor') !== false) $icon = '📺';
                                
                                // Status badge
                                $status_class = $row['status'] == 'Active' ? 'bg-success' : 'bg-secondary';
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
                                        <span class="text-muted">
                                            <?= htmlspecialchars(substr($row['description'], 0, 80)) ?>
                                            <?= strlen($row['description']) > 80 ? '...' : '' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong>₹<?= number_format($row['price'], 2) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge <?= $status_class ?>"><?= $row['status'] ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="product_edit.php?id=<?= $row['product_id'] ?>" 
                                               class="btn btn-outline-primary btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="product_delete.php?id=<?= $row['product_id'] ?>" 
                                               class="btn btn-outline-danger btn-sm" 
                                               onclick="return confirm('Are you sure you want to delete this product?')" 
                                               title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-cube fa-3x mb-3"></i>
                                        <h5>No Products Found</h5>
                                        <p>Start by adding your first product</p>
                                        <a href="add_product.php" class="btn btn-warning">
                                            <i class="fas fa-plus me-1"></i> Add Product
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">
                        Showing <?= mysqli_num_rows($result) ?> products
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
    const tableRows = document.querySelectorAll('#productTable tbody tr');
    
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
