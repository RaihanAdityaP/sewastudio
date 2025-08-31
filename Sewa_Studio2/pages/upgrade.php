<?php
require_once "../pages/auth_check.php";
require_once "../classes/Auth.php";

$auth = new Auth();
$user = $auth->getUserById($_SESSION["user"]["id"]);

if (!$user) {
    die("User tidak ditemukan");
}

$message = "";
$error = "";

if ($user->getMembership() == 'VVIP') {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if ($user->upgradeMembership()) {
            $_SESSION["user"]["membership"] = $user->getMembership();
            $_SESSION["user"]["saldo"] = $user->getSaldo();
            $message = "Upgrade membership berhasil! Selamat, Anda sekarang adalah " . $user->getMembership();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$upgradeCost = $user->getUpgradeCost();
$nextMembership = $user->getNextMembership();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Upgrade Membership</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="text-center mb-4 bg-dark text-white py-3 rounded">Upgrade Membership</h2>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= $message; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error; ?></div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h5>Membership Saat Ini: <?= $user->getMembership(); ?></h5>
                    <h4 class="text-success mb-3">Saldo: Rp <?= number_format($user->getSaldo(), 0, ',', '.'); ?></h4>

                    <div class="mb-4">
                        <h6>Upgrade ke <?= $nextMembership; ?></h6>
                        <p class="text-muted">Biaya Upgrade: Rp <?= number_format($upgradeCost, 0, ',', '.'); ?></p>
                    </div>

                    <div class="mb-4">
                        <h6>Keuntungan <?= $nextMembership; ?>:</h6>
                        <ul class="list-unstyled">
                            <?php if ($nextMembership == 'VIP'): ?>
                                <li>✅ Akses Studio 1 & Studio 2</li>
                                <li>✅ Diskon 10%</li>
                                <li>✅ Cashback 5%</li>
                            <?php elseif ($nextMembership == 'VVIP'): ?>
                                <li>✅ Akses Semua Studio (1, 2, 3)</li>
                                <li>✅ Diskon 20%</li>
                                <li>✅ Cashback 10%</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <form method="POST" class="mt-3">
                        <?php if ($user->getSaldo() >= $upgradeCost): ?>
                            <button type="submit" class="btn btn-warning w-100">Upgrade Sekarang</button>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary w-100" disabled>Saldo Tidak Cukup</button>
                            <p class="mt-2 text-danger">Anda perlu top up minimal Rp <?= number_format($upgradeCost - $user->getSaldo(), 0, ',', '.'); ?></p>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="mt-3">
                <a href="index.php" class="btn btn-outline-secondary w-100">Kembali</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>