<?php
require_once __DIR__ . '/../interfaces/MembershipInterface.php';

class MembershipManager implements MembershipInterface {
    private $membership;

    public function __construct($membership) {
        $this->membership = $membership;
    }

    public function getMembershipLevel() {
        $levels = [
            'Regular' => 1,
            'VIP' => 2,
            'VVIP' => 3
        ];
        return $levels[$this->membership] ?? 1;
    }

    public function getMembershipBenefits() {
        switch($this->membership) {
            case 'Regular':
                return [
                    'studios' => [1],
                    'discount' => 0,
                    'cashback' => 0,
                    'description' => 'Akses Studio 1'
                ];
            case 'VIP':
                return [
                    'studios' => [1, 2],
                    'discount' => 10,
                    'cashback' => 5,
                    'description' => 'Akses Studio 1 & 2, Diskon 10%, Cashback 5%'
                ];
            case 'VVIP':
                return [
                    'studios' => [1, 2, 3],
                    'discount' => 20,
                    'cashback' => 10,
                    'description' => 'Akses Semua Studio, Diskon 20%, Cashback 10%'
                ];
            default:
                return $this->getMembershipBenefits();
        }
    }

    public function canUpgrade() {
        return $this->membership !== 'VVIP';
    }

    public function getUpgradeCost() {
        switch($this->membership) {
            case 'Regular':
                return 100000;
            case 'VIP':
                return 200000;
            default:
                return 0;
        }
    }

    public function getNextMembership() {
        switch($this->membership) {
            case 'Regular':
                return 'VIP';
            case 'VIP':
                return 'VVIP';
            default:
                return 'VVIP';
        }
    }
}
?>