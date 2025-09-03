<?php
interface BookingManagerInterface {
    public function getUserBookings($userId);
    public function getBookingById($bookingId);
    public function cancelBooking($bookingId, $userId);
    public function isBookingActive($bookingId);
    public function checkBookingConflict($studioId, $bookingDate, $bookingTime, $duration, $excludeBookingId = null);
    public function getBookingStatus($bookingId);
    public function processRefund($bookingId);
}
?>