<?php
interface PaymentInterface {
    public function getDiscount();
    public function getCashback();
    public function canAccessStudio($studioId);
    public function getUpgradeCost();
    public function getNextMembership();
}
?>