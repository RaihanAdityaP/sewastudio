<?php
require_once __DIR__ . '/../interfaces/BookingManagerInterface.php';
require_once __DIR__ . '/../interfaces/BookingValidationInterface.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Studio.php';
require_once __DIR__ . '/../classes/PaymentProcessor.php';

class BookingManager implements BookingManagerInterface, BookingValidationInterface {
    private $db;
    private $paymentProcessor;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->paymentProcessor = new PaymentProcessor();
    }

    // BookingManagerInterface implementation
    public function getUserBookings($userId) {
        $query = "SELECT b.*, s.name as studio_name, s.price as studio_price, u.name as user_name 
                  FROM bookings b 
                  JOIN studios s ON b.studio_id = s.id 
                  JOIN users u ON b.user_id = u.id 
                  WHERE b.user_id = ? 
                  ORDER BY b.booking_date DESC, b.booking_time DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBookingById($bookingId) {
        $query = "SELECT b.*, s.name as studio_name, s.price as studio_price, u.name as user_name 
                  FROM bookings b 
                  JOIN studios s ON b.studio_id = s.id 
                  JOIN users u ON b.user_id = u.id 
                  WHERE b.id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$bookingId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function cancelBooking($bookingId, $userId) {
        // Check if user owns this booking
        $booking = $this->getBookingById($bookingId);
        if (!$booking || $booking['user_id'] != $userId) {
            throw new Exception("Booking tidak ditemukan atau bukan milik Anda!");
        }

        // Check if booking can be cancelled
        if (!$this->canUserCancelBooking($bookingId, $userId)) {
            throw new Exception("Booking tidak dapat dibatalkan!");
        }

        // Process refund
        $refundAmount = $this->processRefund($bookingId);

        // Update booking status to cancelled
        $query = "UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([$bookingId, $userId]);

        if ($result) {
            return [
                'success' => true,
                'refund_amount' => $refundAmount,
                'booking' => $booking
            ];
        }

        throw new Exception("Gagal membatalkan booking!");
    }

    public function isBookingActive($bookingId) {
        $booking = $this->getBookingById($bookingId);
        if (!$booking) return false;

        $now = new DateTime();
        $bookingDateTime = new DateTime($booking['booking_date'] . ' ' . $booking['booking_time']);
        $endDateTime = clone $bookingDateTime;
        $endDateTime->add(new DateInterval('PT' . $booking['duration'] . 'H'));

        return $booking['status'] === 'confirmed' && $now < $endDateTime;
    }

    public function checkBookingConflict($studioId, $bookingDate, $bookingTime, $duration, $excludeBookingId = null) {
        $query = "SELECT * FROM bookings 
                  WHERE studio_id = ? 
                  AND booking_date = ? 
                  AND status = 'confirmed'";
        
        $params = [$studioId, $bookingDate];
        
        if ($excludeBookingId) {
            $query .= " AND id != ?";
            $params[] = $excludeBookingId;
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $existingBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $newStart = new DateTime($bookingDate . ' ' . $bookingTime);
        $newEnd = clone $newStart;
        $newEnd->add(new DateInterval('PT' . $duration . 'H'));

        foreach ($existingBookings as $existing) {
            $existingStart = new DateTime($existing['booking_date'] . ' ' . $existing['booking_time']);
            $existingEnd = clone $existingStart;
            $existingEnd->add(new DateInterval('PT' . $existing['duration'] . 'H'));

            // Check for time overlap
            if ($newStart < $existingEnd && $newEnd > $existingStart) {
                return true; // Conflict found
            }
        }

        return false; // No conflict
    }

    public function getBookingStatus($bookingId) {
        $booking = $this->getBookingById($bookingId);
        if (!$booking) return 'not_found';

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

    public function processRefund($bookingId) {
        $booking = $this->getBookingById($bookingId);
        if (!$booking) {
            throw new Exception("Booking tidak ditemukan!");
        }

        $refundAmount = $this->calculateRefundAmount($bookingId);
        
        if ($refundAmount > 0) {
            // Add refund to user's balance
            $this->paymentProcessor->processTopUp($booking['user_id'], $refundAmount);
        }

        return $refundAmount;
    }

    // BookingValidationInterface implementation
    public function validateBookingTime($studioId, $bookingDate, $bookingTime, $duration) {
        // Check if booking is in the future
        $now = new DateTime();
        $bookingDateTime = new DateTime($bookingDate . ' ' . $bookingTime);
        
        if ($bookingDateTime <= $now) {
            throw new Exception("Tidak dapat booking untuk waktu yang sudah berlalu!");
        }

        // Check for conflicts
        if ($this->checkBookingConflict($studioId, $bookingDate, $bookingTime, $duration)) {
            throw new Exception("Waktu booking bentrok dengan booking lain!");
        }

        return true;
    }

    public function isTimeSlotAvailable($studioId, $bookingDate, $bookingTime, $duration) {
        try {
            $this->validateBookingTime($studioId, $bookingDate, $bookingTime, $duration);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function canUserCancelBooking($bookingId, $userId) {
        $booking = $this->getBookingById($bookingId);
        if (!$booking || $booking['user_id'] != $userId) {
            return false;
        }

        // Can only cancel if status is confirmed and booking hasn't started yet
        if ($booking['status'] !== 'confirmed') {
            return false;
        }

        $now = new DateTime();
        $bookingDateTime = new DateTime($booking['booking_date'] . ' ' . $booking['booking_time']);
        
        // Must cancel at least 2 hours before booking time
        $cancelDeadline = clone $bookingDateTime;
        $cancelDeadline->sub(new DateInterval('PT2H'));
        
        return $now <= $cancelDeadline;
    }

    public function calculateRefundAmount($bookingId) {
        $booking = $this->getBookingById($bookingId);
        if (!$booking) return 0;

        $now = new DateTime();
        $bookingDateTime = new DateTime($booking['booking_date'] . ' ' . $booking['booking_time']);
        
        // Calculate hours until booking
        $hoursUntilBooking = ($bookingDateTime->getTimestamp() - $now->getTimestamp()) / 3600;
        
        $totalPaid = $booking['total_price'] - $booking['cashback']; // Actual amount paid
        
        if ($hoursUntilBooking >= 24) {
            // Full refund if cancelled 24+ hours before
            return $totalPaid;
        } elseif ($hoursUntilBooking >= 2) {
            // 50% refund if cancelled 2-24 hours before
            return $totalPaid * 0.5;
        } else {
            // No refund if cancelled less than 2 hours before
            return 0;
        }
    }

    // Additional helper methods
    public function getUpcomingBookings($userId) {
        $bookings = $this->getUserBookings($userId);
        $upcoming = [];
        
        foreach ($bookings as $booking) {
            if ($this->getBookingStatus($booking['id']) === 'upcoming') {
                $upcoming[] = $booking;
            }
        }
        
        return $upcoming;
    }

    public function getBookingHistory($userId) {
        $bookings = $this->getUserBookings($userId);
        $history = [];
        
        foreach ($bookings as $booking) {
            $status = $this->getBookingStatus($booking['id']);
            if (in_array($status, ['completed', 'cancelled'])) {
                $booking['computed_status'] = $status;
                $history[] = $booking;
            }
        }
        
        return $history;
    }

    public function getActiveBookings($userId) {
        $bookings = $this->getUserBookings($userId);
        $active = [];
        
        foreach ($bookings as $booking) {
            if ($this->getBookingStatus($booking['id']) === 'active') {
                $active[] = $booking;
            }
        }
        
        return $active;
    }
}
?>