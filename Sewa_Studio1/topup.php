<?php
require_once "auth_check.php";

$usersFile = __DIR__ . '/data/users.json';
$sessionUser = $_SESSION["user"];
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $amount = (int) $_POST["amount"];
    
    if ($amount < 50000) {
        $message = "Minimal top up Rp 50.000";
    } else {
        // Load users.json
        $users = json_decode(file_get_contents($usersFile), true);

        // Cari user berdasarkan id
        foreach ($users as &$user) {
            if ($user['id'] === $sessionUser['id']) {
                $user['saldo'] += $amount;
                $_SESSION["user"]["saldo"] = $user['saldo']; // update session
                $message = "Top up berhasil! Saldo sekarang: Rp " . number_format($user['saldo'], 0, ',', '.');
                break;
            }
        }

        // Simpan kembali ke users.json
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Top Up Saldo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <h2 class="text-center mb-4 bg-dark text-white py-3 rounded">Top Up Saldo</h2>

            <?php if ($message): ?>
                <div class="alert alert-info"><?= $message; ?></div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h5>Saldo Anda</h5>
                    <h2 class="text-success">Rp <?= number_format($sessionUser['saldo'], 0, ',', '.'); ?></h2>

                    <form method="POST" class="mt-3">
                        <div class="mb-3">
                            <label for="amount" class="form-label">Jumlah Top Up</label>
                            <input type="number" class="form-control" id="amount" name="amount" min="50000" step="50000" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Top Up Sekarang</button>
                    </form>
                </div>
            </div>

            <p class="mt-3"><a href="index.php" class="btn btn-outline-secondary w-100">Kembali</a></p>
        </div>
    </div>
</div>

</body>
</html>
