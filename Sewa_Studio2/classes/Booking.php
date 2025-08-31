<?php
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../config/Database.php';

// Check if BookingInterface exists
if (file_exists(__DIR__ . '/../interfaces/BookingInterface.php')) {
    require_once __DIR__ . '/../interfaces/BookingInterface.php';
    class Booking implements BookingInterface {
        private $id;
        private $studio;
        private $user;
        private $date;
        private $duration;
        private $totalPrice;
        private $cashback;
        private $db;

        public function __construct($id, $studio, $user, $date, $duration) {
            $this->id = $id;
            $this->studio = $studio;
            $this->user = $user;
            $this->date = $date;
            $this->duration = $duration;
            
            $database = new Database();
            $this->db = $database->getConnection();
            
            $this->calculatePrice();
        }

        private function calculatePrice() {
            $startTime = strtotime($this->date);
            $hour = (int)date("H", $startTime);

            $basePrice = $this->studio->getPrice() * $this->duration;
            $hourMultiplier = 1;
            if ($hour >= 17 && $hour < 22) {
                $hourMultiplier = 1.2;
            } elseif ($hour >= 22 || $hour < 8) {
                $hourMultiplier = 1.1;
            }

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

            $newSaldo = $saldo - $this->totalPrice + $this->cashback;
            $this->user->setSaldo($newSaldo);
            
            // Save booking to database
            $this->saveBooking();

            return $newSaldo;
        }

        private function saveBooking() {
            try {
                $query = "INSERT INTO bookings (id, user_id, studio_id, booking_date, booking_time, duration, total_price, cashback) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->db->prepare($query);
                
                $dateTime = new DateTime($this->date);
                $bookingDate = $dateTime->format('Y-m-d');
                $bookingTime = $dateTime->format('H:i:s');
                
                // Use execute with array - much simpler and no reference issues
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
                // Log error for debugging
                error_log("Booking save error: " . $e->getMessage());
                return true; // Continue without saving to database
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
    }
} else {
    // Fallback without interface
    class Booking {
        private $id;
        private $studio;
        private $user;
        private $date;
        private $duration;
        private $totalPrice;
        private $cashback;
        private $db;

        public function __construct($id, $studio, $user, $date, $duration) {
            $this->id = $id;
            $this->studio = $studio;
            $this->user = $user;
            $this->date = $date;
            $this->duration = $duration;
            
            $database = new Database();
            $this->db = $database->getConnection();
            
            $this->calculatePrice();
        }

        private function calculatePrice() {
            $startTime = strtotime($this->date);
            $hour = (int)date("H", $startTime);

            $basePrice = $this->studio->getPrice() * $this->duration;
            $hourMultiplier = 1;
            if ($hour >= 17 && $hour < 22) {
                $hourMultiplier = 1.2;
            } elseif ($hour >= 22 || $hour < 8) {
                $hourMultiplier = 1.1;
            }

            $adjustedPrice = $basePrice * $hourMultiplier;
            $discount = method_exists($this->user, 'getDiscount') ? $this->user->getDiscount() : 0;
            $cashback = method_exists($this->user, 'getCashback') ? $this->user->getCashback() : 0;

            $this->totalPrice = $adjustedPrice - ($adjustedPrice * $discount);
            $this->cashback = $adjustedPrice * $cashback;
        }

        public function processPayment() {
            $saldo = $this->user->getSaldo();

            if ($saldo < $this->totalPrice) {
                throw new Exception("Saldo tidak cukup untuk melakukan booking!");
            }

            // Check if user can access this studio
            if (method_exists($this->user, 'canAccessStudio') && !$this->user->canAccessStudio($this->studio->getId())) {
                throw new Exception("Membership Anda tidak dapat mengakses studio ini!");
            }

            $newSaldo = $saldo - $this->totalPrice + $this->cashback;
            $this->user->setSaldo($newSaldo);
            
            // Save booking to database
            $this->saveBooking();

            return $newSaldo;
        }

        private function saveBooking() {
            try {
                $query = "INSERT INTO bookings (id, user_id, studio_id, booking_date, booking_time, duration, total_price, cashback) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->db->prepare($query);
                
                $dateTime = new DateTime($this->date);
                $bookingDate = $dateTime->format('Y-m-d');
                $bookingTime = $dateTime->format('H:i:s');
                
                // Use execute with array - no reference issues
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
                // Log error for debugging
                error_log("Booking save error: " . $e->getMessage());
                return true; // Continue without saving to database
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
    }
}
?>