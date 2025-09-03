<?php
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../interfaces/BookingInterface.php';
require_once __DIR__ . '/../classes/BookingManager.php';

class Booking implements BookingInterface {
    private $id;
    private $studio;
    private $user;
    private $date;
    private $duration;
    private $totalPrice;
    private $cashback;
    private $db;
    private $bookingManager;

    public function __construct($id, $studio, $user, $date, $duration) {
        $this->id = $id;
        $this->studio = $studio;
        $this->user = $user;
        $this->date = $date;
        $this->duration = $duration;
        
        $database = new Database();
        $this->db = $database->getConnection();
        $this->bookingManager = new BookingManager();
        
        $this->calculatePrice();
    }

    private function calculatePrice() {
        $startTime = strtotime($this->date);
        $hour = (int)date("H", $startTime);

        $basePrice = $this->studio->getPrice() * $this->duration;
        $hourMultiplier = 1;
        
        // Fix multiplier logic sesuai dengan yang di display
        if ($hour >= 17 && $hour < 22) {
            $hourMultiplier = 1.2; // +20% untuk jam 17:00-21:59
        } elseif ($hour >= 22 || $hour < 8) {
            $hourMultiplier = 1.1; // +10% untuk jam 22:00-07:59
        }
        // Jam 08:00-16:59 = normal (multiplier 1)

        $adjustedPrice = $basePrice * $hourMultiplier;
        $discount = $this->user->getDiscount();
        $cashback = $this->user->getCashback();

        $this->totalPrice = $adjustedPrice - ($adjustedPrice * $discount);
        $this->cashback = $adjustedPrice * $cashback;
    }

    public function processPayment() {
        $saldo = $this->user->getSaldo();

        if ($saldo < $this->totalPrice) {
            throw new Exception("Saldo tidak cukup untuk melakukan booking!");
        }

        // Check if user can access this studio
        if (!$this->user->canAccessStudio($this->studio->getId())) {
            throw new Exception("Membership Anda tidak dapat mengakses studio ini!");
        }

        // Validate booking time and check conflicts
        $dateTime = new DateTime($this->date);
        $bookingDate = $dateTime->format('Y-m-d');
        $bookingTime = $dateTime->format('H:i:s');
        
        $this->bookingManager->validateBookingTime(
            $this->studio->getId(), 
            $bookingDate, 
            $bookingTime, 
            $this->duration
        );

        $newSaldo = $saldo - $this->totalPrice + $this->cashback;
        $this->user->setSaldo($newSaldo);
        
        // Save booking to database
        $this->saveBooking();

        return $newSaldo;
    }

    private function saveBooking() {
        try {
            $query = "INSERT INTO bookings (id, user_id, studio_id, booking_date, booking_time, duration, total_price, cashback, status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')";
            
            $stmt = $this->db->prepare($query);
            
            $dateTime = new DateTime($this->date);
            $bookingDate = $dateTime->format('Y-m-d');
            $bookingTime = $dateTime->format('H:i:s');
            
            return $stmt->execute([
                $this->id,
                $this->user->getId(),
                $this->studio->getId(),
                $bookingDate,
                $bookingTime,
                $this->duration,
                $this->totalPrice,
                $this->cashback
            ]);
            
        } catch (Exception $e) {
            error_log("Booking save error: " . $e->getMessage());
            throw new Exception("Gagal menyimpan booking: " . $e->getMessage());
        }
    }

    public function getBookingInfo() {
        return [
            "User" => $this->user->getName(),
            "Studio" => $this->studio->getName(),
            "Tanggal" => $this->date,
            "Durasi" => $this->duration . " jam",
            "Total Harga" => $this->totalPrice,
            "Cashback" => $this->cashback
        ];
    }

    // Getter methods
    public function getId() { return $this->id; }
    public function getStudio() { return $this->studio; }
    public function getUser() { return $this->user; }
    public function getDate() { return $this->date; }
    public function getDuration() { return $this->duration; }
    public function getTotalPrice() { return $this->totalPrice; }
    public function getCashback() { return $this->cashback; }
}
?>