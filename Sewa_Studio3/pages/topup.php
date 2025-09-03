<?php
require_once "../pages/auth_check.php";
require_once "../classes/Auth.php";

$auth = new Auth();
$user = $auth->getUserById($_SESSION["user"]["id"]);
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $amount = (int) $_POST["amount"];
    
    if ($amount < 50000) {
        $message = "Minimal top up Rp 50.000";
    } else {
        $newSaldo = $user->getSaldo() + $amount;
        $user->setSaldo($newSaldo);
        $_SESSION["user"]["saldo"] = $newSaldo;
        $message = "Top up berhasil! Saldo sekarang: Rp " . number_format($newSaldo, 0, ',', '.');
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Top Up Saldo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .nominal-btn {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            color: #495057;
            padding: 12px 20px;
            margin: 5px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
            text-align: center;
            font-weight: 500;
        }
        .nominal-btn:hover {
            background: #007bff;
            border-color: #007bff;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,123,255,0.3);
        }
        .nominal-btn.active {
            background: #007bff;
            border-color: #007bff;
            color: white;
        }
        .nominal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="text-center mb-4 bg-dark text-white py-3 rounded">Top Up Saldo</h2>

            <?php if ($message): ?>
                <div class="alert alert-info"><?= $message; ?></div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h5>Saldo Anda</h5>
                    <h2 class="text-success">Rp <?= number_format($user->getSaldo(), 0, ',', '.'); ?></h2>

                    <form method="POST" class="mt-4">
                        <h6 class="mb-3">Pilih Nominal Top Up</h6>
                        
                        <div class="nominal-grid">
                            <div class="nominal-btn" onclick="selectAmount(50000)">Rp 50.000</div>
                            <div class="nominal-btn" onclick="selectAmount(100000)">Rp 100.000</div>
                            <div class="nominal-btn" onclick="selectAmount(200000)">Rp 200.000</div>
                            <div class="nominal-btn" onclick="selectAmount(500000)">Rp 500.000</div>
                            <div class="nominal-btn" onclick="selectAmount(1000000)">Rp 1.000.000</div>
                            <div class="nominal-btn" onclick="selectAmount(2000000)">Rp 2.000.000</div>
                        </div>

                        <div class="mb-3">
                            <label for="amount" class="form-label">Atau masukkan nominal custom (min. Rp 50.000)</label>
                            <input type="number" class="form-control" id="amount" name="amount" min="50000" step="50000" required>
                            <small class="text-muted">Minimal top up Rp 50.000, kelipatan Rp 50.000</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Top Up Sekarang</button>
                    </form>
                </div>
            </div>

            <p class="mt-3"><a href="../pages/index.php" class="btn btn-outline-secondary w-100">Kembali</a></p>
        </div>
    </div>
</div>

<script>
function selectAmount(amount) {
    // Remove active class from all buttons
    document.querySelectorAll('.nominal-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Add active class to clicked button
    event.target.classList.add('active');
    
    // Set the amount in the input field
    document.getElementById('amount').value = amount;
}
</script>

</body>
</html>