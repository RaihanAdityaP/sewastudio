<?php
require_once "../pages/auth_check.php";
require_once "../classes/Auth.php";

$auth = new Auth();
$user = $auth->getUserById($_SESSION["user"]["id"]);

// Only VVIP users can access admin dashboard
if (!$user || $user->getMembership() !== 'VVIP') {
    die("Akses ditolak. Hanya VVIP yang dapat mengakses halaman ini.");
}

require_once "../config/Database.php";
require_once "../classes/BookingManager.php";

$database = new Database();
$db = $database->getConnection();
$bookingManager = new BookingManager();

// Handle actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'cancel_booking':
                $bookingId = $_POST['booking_id'];
                try {
                    // Admin can cancel any booking (force cancel)
                    $query = "UPDATE bookings SET status = 'cancelled' WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$bookingId]);
                    $message = "Booking berhasil dibatalkan oleh admin.";
                } catch (Exception $e) {
                    $error = "Gagal membatalkan booking: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get all bookings with user and studio info
$query = "SELECT b.*, s.name as studio_name, s.price as studio_price, u.name as user_name, u.membership 
          FROM bookings b 
          JOIN studios s ON b.studio_id = s.id 
          JOIN users u ON b.user_id = u.id 
          ORDER BY b.created_at DESC 
          LIMIT 100";

$stmt = $db->prepare($query);
$stmt->execute();
$allBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total_bookings,
    COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_bookings,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings,
    SUM(CASE WHEN status = 'confirmed' THEN total_price ELSE 0 END) as total_revenue,
    COUNT(CASE WHEN booking_date = CURDATE() THEN 1 END) as today_bookings,
    COUNT(CASE WHEN booking_date >= CURDATE() AND status = 'confirmed' THEN 1 END) as upcoming_bookings
    FROM bookings 
    WHERE created_at >= CURDATE() - INTERVAL 30 DAY";

$stmt = $db->prepare($statsQuery);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user statistics
$userStatsQuery = "SELECT 
    membership,
    COUNT(*) as count,
    AVG(saldo) as avg_saldo
    FROM users 
    GROUP BY membership";

$stmt = $db->prepare($userStatsQuery);
$stmt->execute();
$userStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get studio utilization
$studioUtilQuery = "SELECT 
    s.name as studio_name,
    s.id as studio_id,
    COUNT(b.id) as booking_count,
    SUM(b.total_price) as revenue
    FROM studios s
    LEFT JOIN bookings b ON s.id = b.studio_id AND b.status = 'confirmed'
    AND b.booking_date >= CURDATE() - INTERVAL 30 DAY
    GROUP BY s.id, s.name
    ORDER BY booking_count DESC";

$stmt = $db->prepare($studioUtilQuery);
$stmt->execute();
$studioStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getBookingStatus($booking) {
    $now = new DateTime();
    $bookingDateTime = new DateTime($booking['booking_date'] . ' ' . $booking['booking_time']);
    $endDateTime = clone $bookingDateTime;
    $endDateTime->add(new DateInterval('PT' . $booking['duration'] . 'H'));

    if ($booking['status'] === 'cancelled') {
        return 'cancelled';
    } elseif ($now < $bookingDateTime) {
        return 'upcoming';
    } elseif ($now >= $bookingDateTime && $now < $endDateTime) {
        return 'active';
    } else {
        return 'completed';
    }
}

function getStatusBadge($status) {
    switch ($status) {
        case 'upcoming':
            return '<span class="badge bg-primary">Akan Datang</span>';
        case 'active':
            return '<span class="badge bg-success">Berlangsung</span>';
        case 'completed':
            return '<span class="badge bg-secondary">Selesai</span>';
        case 'cancelled':
            return '<span class="badge bg-danger">Dibatalkan</span>';
        default:
            return '<span class="badge bg-light text-dark">Unknown</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - VVIP Only</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .metric-card {
            transition: transform 0.2s;
        }
        .metric-card:hover {
            transform: translateY(-2px);
        }
        .admin-actions {
            min-width: 120px;
        }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>👑 Admin Dashboard</h2>
            <p class="text-muted mb-0">VVIP Monitoring & Management Panel</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">Kembali ke Home</a>
    </div>

    <?php if (isset($message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center metric-card">
                <div class="card-body">
                    <h4 class="card-title text-primary"><?= $stats['total_bookings']; ?></h4>
                    <p class="card-text small">Total Booking<br>(30 hari)</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center metric-card">
                <div class="card-body">
                    <h4 class="card-title text-success"><?= $stats['confirmed_bookings']; ?></h4>
                    <p class="card-text small">Confirmed</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center metric-card">
                <div class="card-body">
                    <h4 class="card-title text-danger"><?= $stats['cancelled_bookings']; ?></h4>
                    <p class="card-text small">Cancelled</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center metric-card">
                <div class="card-body">
                    <h4 class="card-title text-warning"><?= $stats['today_bookings']; ?></h4>
                    <p class="card-text small">Hari Ini</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center metric-card">
                <div class="card-body">
                    <h4 class="card-title text-info"><?= $stats['upcoming_bookings']; ?></h4>
                    <p class="card-text small">Upcoming</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center metric-card">
                <div class="card-body">
                    <h5 class="card-title text-success">Rp <?= number_format($stats['total_revenue'], 0, ',', '.'); ?></h5>
                    <p class="card-text small">Revenue<br>(30 hari)</p>
                </div>
            </div>
        </div>
    </div>

    <!-- User & Studio Analytics -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">👥 User Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Membership</th>
                                    <th>Count</th>
                                    <th>Avg Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userStats as $stat): ?>
                                <tr>
                                    <td>
                                        <span class="badge <?= $stat['membership'] == 'VVIP' ? 'bg-warning' : ($stat['membership'] == 'VIP' ? 'bg-info' : 'bg-secondary'); ?>">
                                            <?= $stat['membership']; ?>
                                        </span>
                                    </td>
                                    <td><?= $stat['count']; ?> users</td>
                                    <td>Rp <?= number_format($stat['avg_saldo'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">🏢 Studio Utilization (30 hari)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                <?php foreach ($studioStats as $stat): ?>
                                <tr>
                                    <td><?= $stat['studio_name']; ?></td>
                                    <td><?= $stat['booking_count']; ?></td>
                                    <td>Rp <?= number_format($stat['revenue'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- All Bookings Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">📋 All Bookings Management (100 Terbaru)</h5>
            <button class="btn btn-sm btn-outline-primary" onclick="refreshData()">🔄 Refresh</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Membership</th>
                            <th>Studio</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Durasi</th>
                            <th>Total</th>
                            <th>Cashback</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allBookings as $booking): ?>
                            <?php $status = getBookingStatus($booking); ?>
                            <tr>
                                <td>
                                    <small class="font-monospace"><?= substr($booking['id'], 0, 8); ?>...</small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($booking['user_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge <?= $booking['membership'] == 'VVIP' ? 'bg-warning' : ($booking['membership'] == 'VIP' ? 'bg-info' : 'bg-secondary'); ?>">
                                        <?= $booking['membership']; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($booking['studio_name']); ?></strong>
                                </td>
                                <td><?= date('d/m/Y', strtotime($booking['booking_date'])); ?></td>
                                <td><?= date('H:i', strtotime($booking['booking_time'])); ?></td>
                                <td><?= $booking['duration']; ?>h</td>
                                <td class="text-success">
                                    <strong>Rp <?= number_format($booking['total_price'], 0, ',', '.'); ?></strong>
                                </td>
                                <td class="text-warning">
                                    <strong>Rp <?= number_format($booking['cashback'], 0, ',', '.'); ?></strong>
                                </td>
                                <td><?= getStatusBadge($status); ?></td>
                                <td class="admin-actions">
                                    <div class="btn-group-vertical btn-group-sm">
                                        <?php if ($status == 'upcoming' && $booking['status'] == 'confirmed'): ?>
                                            <button class="btn btn-outline-danger btn-sm" 
                                                    onclick="adminCancelBooking('<?= $booking['id']; ?>', '<?= $booking['user_name']; ?>', '<?= $booking['studio_name']; ?>')"
                                                    title="Cancel Booking">
                                                ❌ Cancel
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-outline-info btn-sm" 
                                                onclick="viewBookingDetails('<?= $booking['id']; ?>')"
                                                title="View Details">
                                            👁️ View
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Stats Footer -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <h6>🛠️ Admin Tools & Monitoring Guide:</h6>
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Real-time Monitoring:</strong>
                            <ul class="small mb-0">
                                <li>Live booking status tracking</li>
                                <li>User activity monitoring</li>
                                <li>Revenue analytics</li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <strong>Management Actions:</strong>
                            <ul class="small mb-0">
                                <li>Cancel bookings (force)</li>
                                <li>View detailed booking info</li>
                                <li>Monitor user patterns</li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <strong>Analytics:</strong>
                            <ul class="small mb-0">
                                <li>30-day performance metrics</li>
                                <li>Studio utilization rates</li>
                                <li>Membership distribution</li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <strong>Access Control:</strong>
                            <ul class="small mb-0">
                                <li>VVIP only access</li>
                                <li>Secure authentication</li>
                                <li>Action logging</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Admin Cancel Modal -->
<div class="modal fade" id="adminCancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">⚠️ Admin Force Cancel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <strong>⚠️ Warning:</strong> Ini adalah force cancel oleh admin. Booking akan dibatalkan tanpa refund otomatis.
                </div>
                <p><strong>User:</strong> <span id="modalUserName"></span></p>
                <p><strong>Studio:</strong> <span id="modalStudioName"></span></p>
                <p><strong>Booking ID:</strong> <span id="modalBookingId"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="cancel_booking">
                    <input type="hidden" name="booking_id" id="cancelBookingId">
                    <button type="submit" class="btn btn-danger">Ya, Cancel Booking</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Booking Details Modal -->
<div class="modal fade" id="bookingDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">📋 Booking Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="bookingDetailsContent">
                <!-- Content loaded via JavaScript -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function adminCancelBooking(bookingId, userName, studioName) {
    document.getElementById('modalUserName').textContent = userName;
    document.getElementById('modalStudioName').textContent = studioName;
    document.getElementById('modalBookingId').textContent = bookingId;
    document.getElementById('cancelBookingId').value = bookingId;
    
    var modal = new bootstrap.Modal(document.getElementById('adminCancelModal'));
    modal.show();
}

function viewBookingDetails(bookingId) {
    // Load booking details via AJAX
    fetch(`../api/get_booking_details.php?id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const booking = data.booking;
                const content = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>👤 User Information:</h6>
                            <ul class="list-unstyled">
                                <li><strong>Name:</strong> ${booking.user_name}</li>
                                <li><strong>Email:</strong> ${booking.user_email || 'N/A'}</li>
                                <li><strong>Membership:</strong> <span class="badge bg-info">${booking.membership}</span></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>🏢 Booking Information:</h6>
                            <ul class="list-unstyled">
                                <li><strong>Studio:</strong> ${booking.studio_name}</li>
                                <li><strong>Date:</strong> ${booking.booking_date}</li>
                                <li><strong>Time:</strong> ${booking.booking_time}</li>
                                <li><strong>Duration:</strong> ${booking.duration} hours</li>
                            </ul>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>💰 Payment Details:</h6>
                            <ul class="list-unstyled">
                                <li><strong>Total Price:</strong> Rp ${parseInt(booking.total_price).toLocaleString('id-ID')}</li>
                                <li><strong>Cashback:</strong> Rp ${parseInt(booking.cashback).toLocaleString('id-ID')}</li>
                                <li><strong>Net Paid:</strong> Rp ${(parseInt(booking.total_price) - parseInt(booking.cashback)).toLocaleString('id-ID')}</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>📊 Status Information:</h6>
                            <ul class="list-unstyled">
                                <li><strong>Status:</strong> <span class="badge bg-success">${booking.status}</span></li>
                                <li><strong>Created:</strong> ${booking.created_at}</li>
                                <li><strong>Booking ID:</strong> <small class="font-monospace">${booking.id}</small></li>
                            </ul>
                        </div>
                    </div>
                `;
                
                document.getElementById('bookingDetailsContent').innerHTML = content;
            } else {
                document.getElementById('bookingDetailsContent').innerHTML = '<div class="alert alert-danger">Error loading booking details</div>';
            }
        })
        .catch(error => {
            document.getElementById('bookingDetailsContent').innerHTML = '<div class="alert alert-danger">Network error</div>';
        });
    
    var modal = new bootstrap.Modal(document.getElementById('bookingDetailsModal'));
    modal.show();
}

function refreshData() {
    location.reload();
}

// Auto refresh every 30 seconds
setInterval(function() {
    // Update only the statistics without full page reload
    fetch('admin_dashboard.php')
        .then(response => response.text())
        .then(data => {
            // You could implement partial updates here
            console.log('Auto refresh completed');
        });
}, 30000);
</script>

</body>
</html><?php
require_once "auth_check.php";
require_once "../classes/Auth.php";

$auth = new Auth();
$user = $auth->getUserById($_SESSION["user"]["id"]);

// Only VVIP users can access admin dashboard
if (!$user || $user->getMembership() !== 'VVIP') {
    die("Akses ditolak. Hanya VVIP yang dapat mengakses halaman ini.");
}

require_once "../config/Database.php";

$database = new Database();
$db = $database->getConnection();

// Get all bookings with user and studio info
$query = "SELECT b.*, s.name as studio_name, u.name as user_name, u.membership 
          FROM bookings b 
          JOIN studios s ON b.studio_id = s.id 
          JOIN users u ON b.user_id = u.id 
          ORDER BY b.booking_date DESC, b.booking_time DESC 
          LIMIT 50";

$stmt = $db->prepare($query);
$stmt->execute();
$allBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total_bookings,
    COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_bookings,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings,
    SUM(CASE WHEN status = 'confirmed' THEN total_price ELSE 0 END) as total_revenue
    FROM bookings 
    WHERE booking_date >= CURDATE() - INTERVAL 30 DAY";

$stmt = $db->prepare($statsQuery);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

function getBookingStatus($booking) {
    $now = new DateTime();
    $bookingDateTime = new DateTime($booking['booking_date'] . ' ' . $booking['booking_time']);
    $endDateTime = clone $bookingDateTime;
    $endDateTime->add(new DateInterval('PT' . $booking['duration'] . 'H'));

    if ($booking['status'] === 'cancelled') {
        return 'cancelled';
    } elseif ($now < $bookingDateTime) {
        return 'upcoming';
    } elseif ($now >= $bookingDateTime && $now < $endDateTime) {
        return 'active';
    } else {
        return 'completed';
    }
}

function getStatusBadge($status) {
    switch ($status) {
        case 'upcoming':
            return '<span class="badge bg-primary">Akan Datang</span>';
        case 'active':
            return '<span class="badge bg-success">Berlangsung</span>';
        case 'completed':
            return '<span class="badge bg-secondary">Selesai</span>';
        case 'cancelled':
            return '<span class="badge bg-danger">Dibatalkan</span>';
        default:
            return '<span class="badge bg-light text-dark">Unknown</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Admin Dashboard</h2>
        <a href="index.php" class="btn btn-outline-secondary">Kembali ke Home</a>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-primary"><?= $stats['total_bookings']; ?></h5>
                    <p class="card-text">Total Booking (30 hari)</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-success"><?= $stats['confirmed_bookings']; ?></h5>
                    <p class="card-text">Booking Confirmed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-danger"><?= $stats['cancelled_bookings']; ?></h5>
                    <p class="card-text">Booking Cancelled</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-warning">Rp <?= number_format($stats['total_revenue'], 0, ',', '.'); ?></h5>
                    <p class="card-text">Total Revenue</p>
                </div>
            </div>
        </div>
    </div>

    <!-- All Bookings Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Semua Booking (50 Terbaru)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Membership</th>
                            <th>Studio</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Durasi</th>
                            <th>Total Harga</th>
                            <th>Cashback</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allBookings as $booking): ?>
                            <?php $status = getBookingStatus($booking); ?>
                            <tr>
                                <td><small><?= substr($booking['id'], 0, 8); ?>...</small></td>
                                <td><?= htmlspecialchars($booking['user_name']); ?></td>
                                <td>
                                    <span class="badge <?= $booking['membership'] == 'VVIP' ? 'bg-warning' : ($booking['membership'] == 'VIP' ? 'bg-info' : 'bg-secondary'); ?>">
                                        <?= $booking['membership']; ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($booking['studio_name']); ?></td>
                                <td><?= date('d/m/Y', strtotime($booking['booking_date'])); ?></td>
                                <td><?= date('H:i', strtotime($booking['booking_time'])); ?></td>
                                <td><?= $booking['duration']; ?> jam</td>
                                <td>Rp <?= number_format($booking['total_price'], 0, ',', '.'); ?></td>
                                <td>Rp <?= number_format($booking['cashback'], 0, ',', '.'); ?></td>
                                <td><?= getStatusBadge($status); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>