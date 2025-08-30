<?php
require_once "auth_check.php";
require_once "classes/Studio.php";
require_once "classes/User.php";
require_once "classes/Booking.php";

$sessionUser = $_SESSION["user"];
$file = __DIR__ . "/data/users.json";
$users = json_decode(file_get_contents($file), true);
$currentUserData = null;
foreach ($users as $u) {
    if ($u["id"] === $sessionUser["id"]) {
        $currentUserData = $u;
        break;
    }
}

if (!$currentUserData) {
    die("User tidak ditemukan");
}
switch ($currentUserData["membership"]) {
    case "VIP":
        $user = new VIPUser($currentUserData["id"], $currentUserData["name"], $currentUserData["email"], "", "VIP", $currentUserData["saldo"]);
        break;
    case "VVIP":
        $user = new VVIPUser($currentUserData["id"], $currentUserData["name"], $currentUserData["email"], "", "VVIP", $currentUserData["saldo"]);
        break;
    default:
        $user = new RegularUser($currentUserData["id"], $currentUserData["name"], $currentUserData["email"], "", "Regular", $currentUserData["saldo"]);
        break;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$studios = [
    1 => new Studio(1, "Studio A", 50000, "Drum set, Gitar, Bass, Mic", "assets/img/studio1.jpg"),
    2 => new Studio(2, "Studio B", 100000, "Full Band Set + Mixer", "assets/img/studio2.jpg"),
    3 => new Studio(3, "Studio C", 200000, "Pro Equipment", "assets/img/studio3.jpg"),
];

$studio = $studios[$id] ?? null;
if (!$studio) {
    die("Studio tidak ditemukan");
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

                    <p><strong>Saldo Anda:</strong> Rp <?= number_format($user->getSaldo(), 0, ',', '.'); ?></p>

                    <?php if ($message): ?>
                        <div class="alert alert-info"><?= $message; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="date" class="form-label">Tanggal</label>
                            <input type="date" class="form-control" id="date" name="date" required>
                        </div>

                        <div class="mb-3">
                            <label for="time" class="form-label">Jam Mulai</label>
                            <input type="time" class="form-control" id="time" name="time" required>
                        </div>

                        <div class="mb-3">
                            <label for="duration" class="form-label">Durasi (jam)</label>
                            <input type="number" class="form-control" id="duration" name="duration" min="1" value="1" required>
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

            <p class="mt-3"><a href="index.php" class="btn btn-outline-secondary">Kembali ke Home</a></p>
        </div>
    </div>
</div>

</body>
</html>