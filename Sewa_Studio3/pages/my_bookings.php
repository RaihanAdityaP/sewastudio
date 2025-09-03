<?php
require_once "../pages/auth_check.php";
require_once "../classes/BookingManager.php";
require_once "../classes/Auth.php";

$auth = new Auth();
$user = $auth->getUserById($_SESSION["user"]["id"]);
$bookingManager = new BookingManager();

if (!$user) {
    die("User tidak ditemukan");
}

$message = "";
$error = "";

// Handle booking cancellation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_booking'])) {
    $bookingId = $_POST['booking_id'];
    
    try {
        $result = $bookingManager->cancelBooking($bookingId, $user->getId());
        $message = "Booking berhasil dibatalkan! Refund: Rp " . number_format($result['refund_amount'], 0, ',', '.');
        
        // Update session saldo
        $user = $auth->getUserById($_SESSION["user"]["id"]); // Refresh user data
        $_SESSION["user"]["saldo"] = $user->getSaldo();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get bookings by category
$upcomingBookings = $bookingManager->getUpcomingBookings($user->getId());
$activeBookings = $bookingManager->getActiveBookings($user->getId());
$historyBookings = $bookingManager->getBookingHistory($user->getId());

function getStatusBadge($status) {
    switch ($status) {
        case 'upcoming':
            return '<span class="badge bg-primary">Akan Datang</span>';
        case 'active':
            return '<span class="badge bg-success">Sedang Berlangsung</span>';
        case 'completed':
            return '<span class="badge bg-secondary">Selesai</span>';
        case 'cancelled':
            return '<span class="badge bg-danger">Dibatalkan</span>';
        default:
            return '<span class="badge bg-light text-dark">Unknown</span>';
    }
}

function formatDateTime($date, $time) {
    $datetime = new DateTime($date . ' ' . $time);
    return $datetime->format('d/m/Y H:i');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Booking Saya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .booking-card {
            transition: transform 0.2s ease-in-out;
            border-left: 4px solid #007bff;
        }
        .booking-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .booking-card.upcoming {
            border-left-color: #007bff;
        }
        .booking-card.active {
            border-left-color: #28a745;
        }
        .booking-card.completed {
            border-left-color: #6c757d;
        }
        .booking-card.cancelled {
            border-left-color: #dc3545;
        }
        .nav-tabs .nav-link {
            color: #495057;
        }
        .nav-tabs .nav-link.active {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Booking Saya</h2>
    <div>
      <?php if (isset($_SESSION['user'])): ?>
        <span>Halo, <?= $_SESSION['user']['name']; ?></span>
        <span class="membership"><?= $_SESSION['user']['membership']; ?></span>
        <span class="saldo">Saldo: Rp <?= number_format($_SESSION['user']['saldo'] ?? 0, 0, ',', '.'); ?></span>
        <a href="my_bookings.php" class="btn btn-info">Booking Saya</a>
        <a href="topup.php" class="btn btn-primary">Top Up</a>
        <?php if ($user && $user->getMembership() != 'VVIP'): ?>
            <a href="upgrade.php" class="btn btn-warning">Upgrade</a>
        <?php endif; ?>
        <a href="logout.php" class="btn btn-danger">Logout</a>
      <?php else: ?>
        <a href="login.php" class="btn btn-light">Login</a>
      <?php endif; ?>
    </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs" id="bookingTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab">
                Akan Datang <span class="badge bg-light text-dark ms-1"><?= count($upcomingBookings); ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab">
                Sedang Berlangsung <span class="badge bg-light text-dark ms-1"><?= count($activeBookings); ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                Riwayat <span class="badge bg-light text-dark ms-1"><?= count($historyBookings); ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="bookingTabsContent">
        <!-- Upcoming Bookings -->
        <div class="tab-pane fade show active" id="upcoming" role="tabpanel">
            <div class="mt-4">
                <?php if (empty($upcomingBookings)): ?>
                    <div class="text-center py-5">
                        <h5 class="text-muted">Tidak ada booking yang akan datang</h5>
                        <p class="text-muted">Silakan booking studio untuk melihat booking Anda di sini</p>
                        <a href="index.php" class="btn btn-primary">Booking Studio</a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($upcomingBookings as $booking): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card booking-card upcoming">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h5 class="card-title"><?= $booking['studio_name']; ?></h5>
                                            <?= getStatusBadge('upcoming'); ?>
                                        </div>
                                        
                                        <p class="card-text">
                                            <strong>📅 Tanggal & Waktu:</strong><br>
                                            <?= formatDateTime($booking['booking_date'], $booking['booking_time']); ?>
                                        </p>
                                        
                                        <p class="card-text">
                                            <strong>⏱️ Durasi:</strong> <?= $booking['duration']; ?> jam<br>
                                            <strong>💰 Total Harga:</strong> Rp <?= number_format($booking['total_price'], 0, ',', '.'); ?><br>
                                            <strong>🎁 Cashback:</strong> Rp <?= number_format($booking['cashback'], 0, ',', '.'); ?>
                                        </p>
                                        
                                        <div class="mt-3">
                                            <?php if ($bookingManager->canUserCancelBooking($booking['id'], $user->getId())): ?>
                                                <button class="btn btn-danger btn-sm" onclick="confirmCancelBooking('<?= $booking['id']; ?>', '<?= $booking['studio_name']; ?>', '<?= formatDateTime($booking['booking_date'], $booking['booking_time']); ?>')">
                                                    Batalkan Booking
                                                </button>
                                            <?php else: ?>
                                                <small class="text-muted">Tidak dapat dibatalkan (kurang dari 2 jam sebelum booking)</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Active Bookings -->
        <div class="tab-pane fade" id="active" role="tabpanel">
            <div class="mt-4">
                <?php if (empty($activeBookings)): ?>
                    <div class="text-center py-5">
                        <h5 class="text-muted">Tidak ada booking yang sedang berlangsung</h5>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($activeBookings as $booking): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card booking-card active">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h5 class="card-title"><?= $booking['studio_name']; ?></h5>
                                            <?= getStatusBadge('active'); ?>
                                        </div>
                                        
                                        <p class="card-text">
                                            <strong>📅 Tanggal & Waktu:</strong><br>
                                            <?= formatDateTime($booking['booking_date'], $booking['booking_time']); ?>
                                        </p>
                                        
                                        <p class="card-text">
                                            <strong>⏱️ Durasi:</strong> <?= $booking['duration']; ?> jam<br>
                                            <strong>💰 Total Harga:</strong> Rp <?= number_format($booking['total_price'], 0, ',', '.'); ?>
                                        </p>
                                        
                                        <div class="alert alert-success">
                                            <small><strong>🎵 Selamat bermusik!</strong> Booking Anda sedang berlangsung.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- History -->
        <div class="tab-pane fade" id="history" role="tabpanel">
            <div class="mt-4">
                <?php if (empty($historyBookings)): ?>
                    <div class="text-center py-5">
                        <h5 class="text-muted">Belum ada riwayat booking</h5>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($historyBookings as $booking): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card booking-card <?= $booking['computed_status']; ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h5 class="card-title"><?= $booking['studio_name']; ?></h5>
                                            <?= getStatusBadge($booking['computed_status']); ?>
                                        </div>
                                        
                                        <p class="card-text">
                                            <strong>📅 Tanggal & Waktu:</strong><br>
                                            <?= formatDateTime($booking['booking_date'], $booking['booking_time']); ?>
                                        </p>
                                        
                                        <p class="card-text">
                                            <strong>⏱️ Durasi:</strong> <?= $booking['duration']; ?> jam<br>
                                            <strong>💰 Total Harga:</strong> Rp <?= number_format($booking['total_price'], 0, ',', '.'); ?>
                                            <?php if ($booking['cashback'] > 0): ?>
                                                <br><strong>🎁 Cashback:</strong> Rp <?= number_format($booking['cashback'], 0, ',', '.'); ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Booking Modal -->
<div class="modal fade" id="cancelBookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Pembatalan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin membatalkan booking ini?</p>
                <div class="alert alert-info">
                    <strong>Studio:</strong> <span id="modalStudioName"></span><br>
                    <strong>Waktu:</strong> <span id="modalDateTime"></span>
                </div>
                <div class="alert alert-warning">
                    <strong>Kebijakan Refund:</strong>
                    <ul class="mb-0">
                        <li>Batalkan 24+ jam sebelum booking: Refund 100%</li>
                        <li>Batalkan 2-24 jam sebelum booking: Refund 50%</li>
                        <li>Batalkan kurang dari 2 jam: Tidak ada refund</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="booking_id" id="modalBookingId">
                    <button type="submit" name="cancel_booking" class="btn btn-danger">Ya, Batalkan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmCancelBooking(bookingId, studioName, dateTime) {
    document.getElementById('modalBookingId').value = bookingId;
    document.getElementById('modalStudioName').textContent = studioName;
    document.getElementById('modalDateTime').textContent = dateTime;
    
    var modal = new bootstrap.Modal(document.getElementById('cancelBookingModal'));
    modal.show();
}
</script>

</body>
</html>