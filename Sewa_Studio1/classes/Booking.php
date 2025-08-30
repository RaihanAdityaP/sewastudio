<?php
require_once "User.php";

class Booking {
    private $id;
    private $studio;
    private $user;
    private $date;
    private $duration;
    private $totalPrice;
    private $cashback;

    public function __construct($id, $studio, $user, $date, $duration) {
        $this->id = $id;
        $this->studio = $studio;
        $this->user = $user;
        $this->date = $date;
        $this->duration = $duration;
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
        $newSaldo = $saldo - $this->totalPrice + $this->cashback;
        $this->user->setSaldo($newSaldo);
        $this->updateUserSaldo($this->user->getId(), $newSaldo);

        return $newSaldo;
    }

    private function updateUserSaldo($userId, $newSaldo) {
        $file = __DIR__ . '/../data/users.json';
        $users = json_decode(file_get_contents($file), true);

        foreach ($users as &$u) {
            if ($u["id"] == $userId) {
                $u["saldo"] = $newSaldo;
                break;
            }
        }

        file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));
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
?>