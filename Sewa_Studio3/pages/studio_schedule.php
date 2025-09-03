<?php
require_once "../pages/auth_check.php";
require_once "../classes/Studio.php";
require_once "../classes/BookingManager.php";

$studioId = isset($_GET['studio_id']) ? (int)$_GET['studio_id'] : 1;
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$studio = Studio::getById($studioId);
$bookingManager = new BookingManager();

if (!$studio) {
    die("Studio tidak ditemukan");
}

// Get all studios for dropdown
$allStudios = Studio::getAll();

// Get bookings for selected studio and date
$query = "SELECT * FROM bookings 
          WHERE studio_id = ? 
          AND booking_date = ? 
          AND status = 'confirmed' 
          ORDER BY booking_time";

$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare($query);
$stmt->execute([$studioId, $selectedDate]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create time slots (8:00 - 24:00, kemudian 00:00 - 07:00)
$timeSlots = [];

// Jam 08:00 - 23:00 (normal operating hours)
for ($hour = 8; $hour < 24; $hour++) {
    $timeSlots[] = sprintf("%02d:00", $hour);
}

// Jam 00:00 - 07:00 (late night/early morning)
for ($hour = 0; $hour < 8; $hour++) {
    $timeSlots[] = sprintf("%02d:00", $hour);
}

function isSlotBooked($time, $bookings) {
    foreach ($bookings as $booking) {
        $bookingStart = new DateTime($booking['booking_date'] . ' ' . $booking['booking_time']);
        $bookingEnd = clone $bookingStart;
        $bookingEnd->add(new DateInterval('PT' . $booking['duration'] . 'H'));
        
        $slotTime = new DateTime($booking['booking_date'] . ' ' . $time);
        
        if ($slotTime >= $bookingStart && $slotTime < $bookingEnd) {
            return $booking;
        }
    }
    return false;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Studio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .time-slot {
            height: 60px;
            border: 1px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2px;
            border-radius: 4px;
        }
        .time-slot.available {
            background-color: #d4edda;
            color: #155724;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .time-slot.available:hover {
            background-color: #c3e6cb;
        }
        .time-slot.booked {
            background-color: #f8d7da;
            color: #721c24;
        }
        .time-slot.past {
            background-color: #f6f6f6;
            color: #6c757d;
        }
        .legend {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Jadwal Studio</h2>
        <a href="index.php" class="btn btn-outline-secondary">Kembali ke Home</a>
    </div>

    <!-- Studio and Date Selection -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label for="studio-select" class="form-label">Pilih Studio:</label>
                    <select id="studio-select" class="form-select" onchange="changeStudio()">
                        <?php foreach ($allStudios as $s): ?>
                            <option value="<?= $s->getId(); ?>" <?= $s->getId() == $studioId ? 'selected' : ''; ?>>
                                <?= $s->getName(); ?> - Rp <?= number_format($s->getPrice(), 0, ',', '.'); ?>/jam
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="date-select" class="form-label">Pilih Tanggal:</label>
                    <input type="date" id="date-select" class="form-control" 
                           value="<?= $selectedDate; ?>" 
                           min="<?= date('Y-m-d'); ?>" 
                           onchange="changeDate()">
                </div>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="legend">
        <div class="legend-item">
            <div class="legend-color" style="background-color: #d4edda;"></div>
            <span>Tersedia</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background-color: #f8d7da;"></div>
            <span>Sudah Dibooking</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background-color: #f6f6f6;"></div>
            <span>Waktu Berlalu</span>
        </div>
    </div>

    <!-- Schedule Grid -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                Jadwal <?= $studio->getName(); ?> - 
                <?= date('d/m/Y', strtotime($selectedDate)); ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php 
                $now = new DateTime();
                $currentDate = $now->format('Y-m-d');
                $currentHour = $now->format('H');
                
                foreach ($timeSlots as $time): 
                    $slotDateTime = new DateTime($selectedDate . ' ' . $time);
                    $isPast = ($selectedDate == $currentDate && (int)substr($time, 0, 2) <= (int)$currentHour) || $selectedDate < $currentDate;
                    $bookedInfo = isSlotBooked($time, $bookings);
                ?>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <?php if ($isPast): ?>
                            <div class="time-slot past">
                                <div class="text-center">
                                    <strong><?= $time; ?></strong><br>
                                    <small>Waktu berlalu</small>
                                </div>
                            </div>
                        <?php elseif ($bookedInfo): ?>
                            <div class="time-slot booked" title="Dibooking oleh user lain">
                                <div class="text-center">
                                    <strong><?= $time; ?></strong><br>
                                    <small>Sudah dibooking</small><br>
                                    <small><?= $bookedInfo['duration']; ?> jam</small>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="time-slot available" onclick="bookingSlot('<?= $time; ?>')">
                                <div class="text-center">
                                    <strong><?= $time; ?></strong><br>
                                    <small>Tersedia</small><br>
                                    <small>Klik untuk booking</small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Pricing Info -->
    <div class="card mt-4">
        <div class="card-header">
            <h6 class="mb-0">Informasi Harga</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <strong>Harga Normal (08:00-17:00):</strong><br>
                    Rp <?= number_format($studio->getPrice(), 0, ',', '.'); ?>/jam
                </div>
                <div class="col-md-4">
                    <strong>Harga Sore (17:00-22:00):</strong><br>
                    Rp <?= number_format($studio->getPrice() * 1.2, 0, ',', '.'); ?>/jam (+20%)
                </div>
                <div class="col-md-4">
                    <strong>Harga Malam (22:00-08:00):</strong><br>
                    Rp <?= number_format($studio->getPrice() * 1.1, 0, ',', '.'); ?>/jam (+10%)
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function changeStudio() {
    const studioId = document.getElementById('studio-select').value;
    const date = document.getElementById('date-select').value;
    window.location.href = `studio_schedule.php?studio_id=${studioId}&date=${date}`;
}

function changeDate() {
    const studioId = document.getElementById('studio-select').value;
    const date = document.getElementById('date-select').value;
    window.location.href = `studio_schedule.php?studio_id=${studioId}&date=${date}`;
}

function bookingSlot(time) {
    const studioId = document.getElementById('studio-select').value;
    const date = document.getElementById('date-select').value;
    const datetime = date + ' ' + time;
    
    // Redirect to booking page with pre-filled data
    window.location.href = `booking.php?id=${studioId}&date=${date}&time=${time}`;
}
</script>

</body>
</html>