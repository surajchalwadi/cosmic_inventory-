<div class="sidebar">
    <h4 class="text-center py-3">🌐 Cosmic Panel</h4>
    <a href="dashboard.php"><i class="fas fa-home me-2"></i> Dashboard</a>

    <?php if ($role == 'inventory'): ?>
        <a href="add_purchase.php"><i class="fas fa-truck me-2"></i> Purchase</a>
        <a href="purchase_list.php"><i class="fas fa-truck me-2"></i> Purchase List</a>

    <?php elseif ($role == 'sales'): ?>
        <div class="sidebar-dropdown">
            <a href="javascript:void(0)" onclick="toggleDropdown(this)">
                <span><i class="fas fa-file-invoice me-2"></i> Quotation</span>
                <i class="fas fa-chevron-down dropdown-icon"></i>
            </a>
            <div class="sidebar-submenu">
                <a href="add_quotation.php"><i class="fas fa-plus me-2"></i> Add Quotation</a>
                <a href="quotation_list.php"><i class="fas fa-list me-2"></i> Quotation List</a>
            </div>
        </div>
        <a href="invoice.php"><i class="fas fa-file-invoice-dollar me-2"></i> Invoice</a>
        <a href="clients.php"><i class="fas fa-users me-2"></i> Clients</a>

    <?php elseif ($role == 'admin'): ?>
        <a href="add_purchase.php"><i class="fas fa-truck me-2"></i> Purchase</a>
        <a href="purchase_list.php"><i class="fas fa-truck me-2"></i> Purchase List</a>
        <a href="inventory_finalize.php"><i class="fas fa-boxes me-2"></i> Inventory Finalize</a>
        <div class="sidebar-dropdown">
            <a href="javascript:void(0)" onclick="toggleDropdown(this)">
                <span><i class="fas fa-file-invoice me-2"></i> Quotation</span>
                <i class="fas fa-chevron-down dropdown-icon"></i>
            </a>
            <div class="sidebar-submenu">
                <a href="add_quotation.php"><i class="fas fa-plus me-2"></i> Add Quotation</a>
                <a href="quotation_list.php"><i class="fas fa-list me-2"></i> Quotation List</a>
            </div>
        </div>
        <a href="invoice.php"><i class="fas fa-file-invoice-dollar me-2"></i> Invoice</a>
        <div class="sidebar-dropdown">
            <a href="javascript:void(0)" onclick="toggleDropdown(this)">
                <span><i class="fas fa-cube me-2"></i> Products</span>
                <i class="fas fa-chevron-down dropdown-icon"></i>
            </a>
            <div class="sidebar-submenu">
                <a href="add_product.php"><i class="fas fa-plus me-2"></i> Add Product</a>
                <a href="product_list.php"><i class="fas fa-list me-2"></i> Product List</a>
            </div>
        </div>
        <a href="clients.php"><i class="fas fa-users me-2"></i> Clients</a>
        <a href="reports.php"><i class="fas fa-chart-bar me-2"></i> Reports</a>
        <a href="admin_users.php"><i class="fas fa-user-cog me-2"></i> User Control</a>
    <?php endif; ?>

    <a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
</div>

<script>
function toggleDropdown(element) {
    const dropdown = element.parentElement;
    const isActive = dropdown.classList.contains('active');
    
    // Close all other dropdowns
    document.querySelectorAll('.sidebar-dropdown.active').forEach(item => {
        item.classList.remove('active');
    });
    
    // Toggle current dropdown
    if (!isActive) {
        dropdown.classList.add('active');
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.sidebar-dropdown')) {
        document.querySelectorAll('.sidebar-dropdown.active').forEach(item => {
            item.classList.remove('active');
        });
    }
});
</script>