<?php
session_start();
include 'config/db.php';

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ? AND status = 1");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        // ✅ Use only hashed password check now
        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['user_id'],
                'name' => $user['name'],
                'role' => $user['role']
            ];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "Invalid email or account disabled.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'head.php';?>
<body class="login-body">

<div class="login-container">
  <div class="login-card">
    <h3 class="login-title"><i class="fas fa-lock"></i> Cosmic Inventory Login</h3>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" name="email" class="form-control" placeholder="Enter email" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
      </div>
      <button type="submit" class="btn btn-login w-100">Login</button>
    </form>

    <p class="login-footer">© <?= date('Y') ?> Cosmic Inventory System</p>
  </div>
</div>

</body>
</html>
