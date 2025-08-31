<?php
require_once "../pages/auth_check.php";
require_once "../classes/Studio.php";
require_once "../classes/Auth.php";
require_once "../classes/Booking.php";

$auth = new Auth();
$user = $auth->getUserById($_SESSION["user"]["id"]);

if (!$user) {
    die("User tidak ditemukan");
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$studio = Studio::getById($id);

if (!$studio) {
    die("Studio tidak ditemukan");
}

// Check if user can access this studio (with fallback for older user objects)
$canAccess = false;
if (method_exists($user, 'canAccessStudio')) {
    $canAccess = $user->canAccessStudio($id);
} else {
    // Fallback logic
    switch($user->getMembership()) {
        case 'Regular':
            $canAccess = ($id == 1);
            break;
        case 'VIP':
            $canAccess = in_array($id, [1, 2]);
            break;
        case 'VVIP':
            $canAccess = in_array($id, [1, 2, 3]);
            break;
    }
}

if (!$canAccess) {
    die("Maaf, membership Anda (" . $user->getMembership() . ") tidak dapat mengakses studio ini. Silakan upgrade membership terlebih dahulu.");
}

$info = [];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST["date"];
    $time = $_POST["time"];
    $duration = (int) $_POST["duration"];
    $datetime = $date . " " . $time;

    $booking = new Booking(uniqid(), $studio, $user, $datetime, $duration);
    try {
        $saldoAkhir = $booking->processPayment();
        $info = $booking->getBookingInfo();
        $message = "Pembayaran berhasil. Saldo akhir: Rp " . number_format($saldoAkhir, 0, ',', '.');
        
        // Update session with new saldo
        $_SESSION["user"]["saldo"] = $saldoAkhir;
    } catch (Exception $e) {
        $message = "Gagal: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Booking Studio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="text-center mb-4 bg-dark text-white py-3 rounded">Booking Studio</h2>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">Booking <?= htmlspecialchars($studio->getName()); ?></h5>

                    <p><strong>Membership:</strong> <?= $user->getMembership(); ?></p>
                    <p><strong>Saldo Anda:</strong> Rp <?= number_format($user->getSaldo(), 0, ',', '.'); ?></p>
                    
                    <!-- Pricing Info -->
                    <div class="alert alert-info">
                        <strong>Informasi Harga:</strong><br>
                        - Harga dasar: Rp <?= number_format($studio->getPrice(), 0, ',', '.'); ?>/jam<br>
                        - Jam 17:00-22:00: +20%<br>
                        - Jam 22:00-08:00: +10%<br>
                        <?php if (method_exists($user, 'getDiscount') && $user->getDiscount() > 0): ?>
                            - Diskon membership: <?= ($user->getDiscount() * 100); ?>%<br>
                        <?php endif; ?>
                        <?php if (method_exists($user, 'getCashback') && $user->getCashback() > 0): ?>
                            - Cashback: <?= ($user->getCashback() * 100); ?>%<br>
                        <?php endif; ?>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert <?= strpos($message, 'berhasil') !== false ? 'alert-success' : 'alert-danger'; ?>">
                            <?= $message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="date" class="form-label">Tanggal</label>
                            <input type="date" class="form-control" id="date" name="date" min="<?= date('Y-m-d'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="time" class="form-label">Jam Mulai</label>
                            <input type="time" class="form-control" id="time" name="time" required>
                        </div>

                        <div class="mb-3">
                            <label for="duration" class="form-label">Durasi (jam)</label>
                            <input type="number" class="form-control" id="duration" name="duration" min="1" max="12" value="1" required>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Bayar & Booking</button>
                    </form>
                </div>
            </div>

            <?php if (!empty($info)): ?>
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Ringkasan Booking</h5>
                        <ul>
                            <li>User: <?= $info["User"]; ?></li>
                            <li>Studio: <?= $info["Studio"]; ?></li>
                            <li>Tanggal & Jam: <?= $info["Tanggal"]; ?></li>
                            <li>Durasi: <?= $info["Durasi"]; ?></li>
                            <li>Total Harga: Rp <?= number_format($info["Total Harga"], 0, ',', '.'); ?></li>
                            <li>Cashback: Rp <?= number_format($info["Cashback"], 0, ',', '.'); ?></li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <p class="mt-3">
                <a href="../pages/index.php" class="btn btn-outline-secondary">Kembali ke Home</a>
            </p>
        </div>
    </div>
</div>

</body>
</html>