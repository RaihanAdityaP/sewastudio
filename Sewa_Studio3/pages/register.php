<?php
require_once "../classes/Auth.php";
session_start();

$auth = new Auth();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    // Force membership to Regular - user cannot choose
    $membership = "Regular";

    if ($auth->register($name, $email, $password, $membership, 0)) {
        $_SESSION["success"] = "Registrasi berhasil! Silakan login.";
        header("Location: login.php");
        exit;
    } else {
        $error = "Email sudah digunakan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4">Register</h3>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error; ?></div>
                    <?php endif; ?>

                    <div class="alert alert-info">
                        <strong>Info:</strong> Semua user baru akan terdaftar sebagai Regular member. Anda dapat upgrade membership setelah login.
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Masukkan nama" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Masukkan email" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Membership</label>
                            <input type="text" class="form-control" value="Regular (Default)" disabled>
                            <small class="text-muted">Membership dapat diupgrade setelah login</small>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Register</button>
                    </form>

                    <p class="text-center mt-3">
                        Sudah punya akun? <a href="login.php" class="text-decoration-none">Login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>