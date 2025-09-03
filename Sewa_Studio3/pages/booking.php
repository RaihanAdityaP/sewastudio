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

// Get pre-filled data from URL parameters
$prefilledDate = isset($_GET['date']) ? $_GET['date'] : '';
$prefilledTime = isset($_GET['time']) ? $_GET['time'] : '';

if (!$studio) {
    die("Studio tidak ditemukan");
}

// Check if user can access this studio
$canAccess = $user->canAccessStudio($id);

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
        
        // Clear pre-filled data after successful booking
        $prefilledDate = '';
        $prefilledTime = '';
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
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="bg-dark text-white py-3 px-4 rounded mb-0">Booking Studio</h2>
                <a href="studio_schedule.php?studio_id=<?= $id; ?>" class="btn btn-info">Lihat Jadwal</a>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">Booking <?= htmlspecialchars($studio->getName()); ?></h5>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Membership:</strong> 
                                <span class="badge <?= $user->getMembership() == 'VVIP' ? 'bg-warning' : ($user->getMembership() == 'VIP' ? 'bg-info' : 'bg-secondary'); ?>">
                                    <?= $user->getMembership(); ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Saldo Anda:</strong> 
                                <span class="text-success fw-bold">Rp <?= number_format($user->getSaldo(), 0, ',', '.'); ?></span>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Pricing Info -->
                    <div class="alert alert-info">
                        <h6 class="alert-heading">💰 Informasi Harga</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Normal (08:00-17:00):</strong><br>
                                Rp <?= number_format($studio->getPrice(), 0, ',', '.'); ?>/jam
                            </div>
                            <div class="col-md-4">
                                <strong>Sore (17:00-22:00):</strong><br>
                                Rp <?= number_format($studio->getPrice() * 1.2, 0, ',', '.'); ?>/jam <small class="text-primary">(+20%)</small>
                            </div>
                            <div class="col-md-4">
                                <strong>Malam (22:00-08:00):</strong><br>
                                Rp <?= number_format($studio->getPrice() * 1.1, 0, ',', '.'); ?>/jam <small class="text-primary">(+10%)</small>
                            </div>
                        </div>
                        <hr>
                        <?php if (method_exists($user, 'getDiscount') && $user->getDiscount() > 0): ?>
                            <span class="badge bg-success me-2">Diskon <?= ($user->getDiscount() * 100); ?>%</span>
                        <?php endif; ?>
                        <?php if (method_exists($user, 'getCashback') && $user->getCashback() > 0): ?>
                            <span class="badge bg-warning text-dark">Cashback <?= ($user->getCashback() * 100); ?>%</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert <?= strpos($message, 'berhasil') !== false ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show">
                            <?= $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="bookingForm">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="date" class="form-label">📅 Tanggal</label>
                                    <input type="date" class="form-control" id="date" name="date" 
                                           min="<?= date('Y-m-d'); ?>" 
                                           value="<?= $prefilledDate; ?>" 
                                           onchange="checkAvailability()" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="time" class="form-label">⏰ Jam Mulai</label>
                                    <select class="form-select" id="time" name="time" 
                                            onchange="checkAvailability()" required>
                                        <option value="">Pilih jam mulai</option>
                                        <?php for ($hour = 8; $hour < 22; $hour++): ?>
                                            <?php $timeValue = sprintf("%02d:00", $hour); ?>
                                            <option value="<?= $timeValue; ?>" 
                                                    <?= $prefilledTime == $timeValue ? 'selected' : ''; ?>>
                                                <?= $timeValue; ?>
                                                <?php 
                                                // Show price multiplier
                                                if ($hour >= 17 && $hour < 22) {
                                                    echo " (+20%)";
                                                } elseif ($hour >= 22 || $hour < 8) {
                                                    echo " (+10%)";
                                                }
                                                ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="duration" class="form-label">⏱️ Durasi</label>
                                    <select class="form-select" id="duration" name="duration" 
                                            onchange="checkAvailability()" required>
                                        <?php for ($i = 1; $i <= 8; $i++): ?>
                                            <option value="<?= $i; ?>"><?= $i; ?> jam</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Availability Check Result -->
                        <div id="availability-check" class="mb-3"></div>

                        <!-- Price Calculation -->
                        <div id="price-calculation" class="mb-3"></div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg" id="bookingButton">
                                💳 Bayar & Booking Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Success Booking Info -->
            <?php if (!empty($info)): ?>
                <div class="card mt-4 border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">✅ Booking Berhasil!</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Detail Booking:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>User:</strong> <?= $info["User"]; ?></li>
                                    <li><strong>Studio:</strong> <?= $info["Studio"]; ?></li>
                                    <li><strong>Tanggal & Jam:</strong> <?= $info["Tanggal"]; ?></li>
                                    <li><strong>Durasi:</strong> <?= $info["Durasi"]; ?></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Pembayaran:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Total Harga:</strong> <span class="text-primary">Rp <?= number_format($info["Total Harga"], 0, ',', '.'); ?></span></li>
                                    <li><strong>Cashback:</strong> <span class="text-success">Rp <?= number_format($info["Cashback"], 0, ',', '.'); ?></span></li>
                                    <li><strong>Saldo Terkini:</strong> <span class="text-info">Rp <?= number_format($_SESSION["user"]["saldo"], 0, ',', '.'); ?></span></li>
                                </ul>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <a href="my_bookings.php" class="btn btn-primary w-100">
                                    📋 Lihat Booking Saya
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="index.php" class="btn btn-success w-100">
                                    🏠 Booking Studio Lain
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Navigation -->
            <div class="mt-4">
                <div class="row">
                    <div class="col-md-4">
                        <a href="index.php" class="btn btn-outline-secondary w-100">
                            🏠 Kembali ke Home
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="my_bookings.php" class="btn btn-outline-info w-100">
                            📋 Booking Saya
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="studio_schedule.php?studio_id=<?= $id; ?>" class="btn btn-outline-primary w-100">
                            📅 Jadwal Studio
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const studioPrice = <?= $studio->getPrice(); ?>;
const userDiscount = <?= $user->getDiscount(); ?>;
const userCashback = <?= $user->getCashback(); ?>;
const studioId = <?= $id; ?>;

function checkAvailability() {
    const date = document.getElementById('date').value;
    const time = document.getElementById('time').value;
    const duration = document.getElementById('duration').value;
    
    if (!date || !time || !duration) {
        document.getElementById('availability-check').innerHTML = '';
        document.getElementById('price-calculation').innerHTML = '';
        document.getElementById('bookingButton').disabled = false;
        return;
    }

    // Show loading state
    document.getElementById('availability-check').innerHTML = '<div class="alert alert-info">🔄 Memeriksa ketersediaan...</div>';
    document.getElementById('bookingButton').disabled = true;

    // Check availability via AJAX
    fetch('../api/check_availability.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            studio_id: studioId,
            date: date,
            time: time,
            duration: parseInt(duration)
        })
    })
    .then(response => response.json())
    .then(data => {
        const availabilityDiv = document.getElementById('availability-check');
        const bookingButton = document.getElementById('bookingButton');
        
        if (data.available) {
            availabilityDiv.innerHTML = '<div class="alert alert-success"><strong>✅ Waktu tersedia!</strong> Anda dapat melakukan booking untuk waktu ini.</div>';
            bookingButton.disabled = false;
        } else {
            availabilityDiv.innerHTML = '<div class="alert alert-danger"><strong>❌ Waktu tidak tersedia!</strong> Waktu ini sudah dibooking oleh user lain atau bentrok dengan booking yang ada.</div>';
            bookingButton.disabled = true;
        }
        
        // Calculate and show price
        calculatePrice();
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('availability-check').innerHTML = '<div class="alert alert-warning"><strong>⚠️ Tidak dapat memeriksa ketersediaan</strong><br>Silakan coba lagi atau lanjutkan booking (sistem akan memvalidasi ulang).</div>';
        document.getElementById('bookingButton').disabled = false;
        calculatePrice();
    });
}

function calculatePrice() {
    const time = document.getElementById('time').value;
    const duration = parseInt(document.getElementById('duration').value);
    
    if (!time || !duration) {
        document.getElementById('price-calculation').innerHTML = '';
        return;
    }
    
    const hour = parseInt(time.split(':')[0]);
    let hourMultiplier = 1;
    let timeCategory = 'Normal';
    
    // Fix multiplier sesuai dengan server-side logic
    if (hour >= 17 && hour < 22) {
        hourMultiplier = 1.2;
        timeCategory = 'Sore';
    } else if (hour >= 22 || hour < 8) {
        hourMultiplier = 1.1;
        timeCategory = 'Malam';
    }
    
    const basePrice = studioPrice * duration;
    const adjustedPrice = basePrice * hourMultiplier;
    const discountAmount = adjustedPrice * userDiscount;
    const totalPrice = adjustedPrice - discountAmount;
    const cashbackAmount = adjustedPrice * userCashback;
    const netAmount = totalPrice - cashbackAmount;
    
    const priceDiv = document.getElementById('price-calculation');
    priceDiv.innerHTML = `
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">💰 Kalkulasi Harga Detail</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <td><strong>Harga Dasar:</strong></td>
                                <td>Rp ${studioPrice.toLocaleString('id-ID')} x ${duration} jam</td>
                                <td class="text-end"><strong>Rp ${basePrice.toLocaleString('id-ID')}</strong></td>
                            </tr>
                            ${hourMultiplier > 1 ? `
                            <tr class="table-info">
                                <td><strong>Kategori Waktu:</strong></td>
                                <td>${timeCategory} (x${hourMultiplier})</td>
                                <td class="text-end"><strong>Rp ${adjustedPrice.toLocaleString('id-ID')}</strong></td>
                            </tr>
                            ` : ''}
                            ${userDiscount > 0 ? `
                            <tr class="table-success">
                                <td><strong>Diskon Membership:</strong></td>
                                <td>${(userDiscount*100)}% dari Rp ${adjustedPrice.toLocaleString('id-ID')}</td>
                                <td class="text-end text-success"><strong>-Rp ${discountAmount.toLocaleString('id-ID')}</strong></td>
                            </tr>
                            ` : ''}
                            <tr class="table-primary">
                                <td><strong>Total yang Dibayar:</strong></td>
                                <td></td>
                                <td class="text-end"><strong>Rp ${totalPrice.toLocaleString('id-ID')}</strong></td>
                            </tr>
                            ${userCashback > 0 ? `
                            <tr class="table-warning">
                                <td><strong>Cashback:</strong></td>
                                <td>${(userCashback*100)}% dari Rp ${adjustedPrice.toLocaleString('id-ID')}</td>
                                <td class="text-end text-warning"><strong>+Rp ${cashbackAmount.toLocaleString('id-ID')}</strong></td>
                            </tr>
                            ` : ''}
                            <tr class="table-dark">
                                <td><strong>Net dari Saldo:</strong></td>
                                <td><em>Yang benar-benar keluar dari saldo Anda</em></td>
                                <td class="text-end"><strong>Rp ${netAmount.toLocaleString('id-ID')}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-2">
                    <small class="text-muted">
                        <strong>Keterangan Waktu:</strong><br>
                        • 08:00-16:59: Normal<br>
                        • 17:00-21:59: Sore (+20%)<br>
                        • 22:00-07:59: Malam (+10%)
                    </small>
                </div>
            </div>
        </div>
    `;
}

// Auto-check availability when page loads with pre-filled data
document.addEventListener('DOMContentLoaded', function() {
    // Set default duration if not set
    if (!document.getElementById('duration').value) {
        document.getElementById('duration').value = '1';
    }
    
    // Check availability if pre-filled
    if (document.getElementById('date').value && document.getElementById('time').value) {
        setTimeout(() => {
            checkAvailability();
        }, 500);
    }
});

// Real-time validation when form is submitted
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    const date = document.getElementById('date').value;
    const time = document.getElementById('time').value;
    const duration = document.getElementById('duration').value;
    
    if (!date || !time || !duration) {
        e.preventDefault();
        alert('⚠️ Silakan lengkapi semua field booking!');
        return false;
    }
    
    // Additional client-side validation
    const bookingDateTime = new Date(date + 'T' + time);
    const now = new Date();
    
    if (bookingDateTime <= now) {
        e.preventDefault();
        alert('⚠️ Tidak dapat booking untuk waktu yang sudah berlalu!');
        return false;
    }
});
</script>

</body>
</html>