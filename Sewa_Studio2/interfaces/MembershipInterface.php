<?php
interface MembershipInterface {
    public function getMembershipLevel();
    public function getMembershipBenefits();
    public function canUpgrade();
}
?>