<?php
require_once "../classes/Auth.php";
session_start();

$auth = new Auth();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $user = $auth->login($email, $password);

    if ($user) {
        $_SESSION["user"] = [
            "id" => $user->getId(),
            "name" => $user->getName(),
            "email" => $user->getEmail(),
            "membership" => $user->getMembership(),
            "saldo" => $user->getSaldo()
        ];
        header("Location: ../pages/index.php");
        exit;
    } else {
        $error = "Email atau password salah!";
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4">Login</h3>

                    <?php if (!empty($_SESSION["success"])): ?>
                        <div class="alert alert-success"><?= $_SESSION["success"]; ?></div>
                        <?php unset($_SESSION["success"]); ?>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Masukkan email" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>

                    <!-- Demo Accounts Information -->
                    <div class="card mt-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">Demo Accounts:</h6>
                        </div>
                        <div class="card-body">
                            <small>
                                <strong>Regular:</strong> regular@example.com / password<br>
                                <strong>VIP:</strong> vip@example.com / password<br>
                                <strong>VVIP:</strong> admin@example.com / password
                            </small>
                        </div>
                    </div>

                    <p class="text-center mt-3">
                        Belum punya akun? <a href="register.php" class="text-decoration-none">Register</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>