 <?php
// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['user']['role'] ?? '';
$name = $_SESSION['user']['name'] ?? 'User';
?>

<!-- Topbar -->
<div class="topbar">
        <h5>Welcome, <?= ucfirst($role) ?> - <?= htmlspecialchars($name) ?></h5>
        <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
    </div>