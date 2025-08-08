<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Hash Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f6fa;
            padding: 40px;
        }
        .container {
            max-width: 500px;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        .hash-box {
            font-family: monospace;
            background: #f1f1f1;
            padding: 10px;
            border-radius: 5px;
            word-break: break-all;
        }
    </style>
</head>
<body>

<div class="container">
    <h3 class="mb-4">🔐 Password Hash Generator</h3>
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Enter Plain Password:</label>
            <input type="text" class="form-control" name="password" required placeholder="e.g., admin123">
        </div>
        <button class="btn btn-primary" type="submit">Generate Hash</button>
    </form>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['password'])): ?>
        <hr>
        <h5>Hashed Output:</h5>
        <div class="hash-box"><?= password_hash($_POST['password'], PASSWORD_DEFAULT) ?></div>
    <?php endif; ?>
</div>

</body>
</html>
