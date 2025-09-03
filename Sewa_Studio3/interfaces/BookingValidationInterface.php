<?php
interface BookingValidationInterface {
    public function validateBookingTime($studioId, $bookingDate, $bookingTime, $duration);
    public function isTimeSlotAvailable($studioId, $bookingDate, $bookingTime, $duration);
    public function canUserCancelBooking($bookingId, $userId);
    public function calculateRefundAmount($bookingId);
}
?>