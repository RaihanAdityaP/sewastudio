<?php
header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception("Booking ID required");
    }
    
    $bookingId = $_GET['id'];
    
    require_once __DIR__ . '/../config/Database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT b.*, s.name as studio_name, s.price as studio_price, 
              u.name as user_name, u.email as user_email, u.membership,
              DATE_FORMAT(b.created_at, '%d/%m/%Y %H:%i') as created_at_formatted
              FROM bookings b 
              JOIN studios s ON b.studio_id = s.id 
              JOIN users u ON b.user_id = u.id 
              WHERE b.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$bookingId]);
    
    if ($stmt->rowCount() > 0) {
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'booking' => [
                'id' => $booking['id'],
                'user_name' => $booking['user_name'],
                'user_email' => $booking['user_email'],
                'membership' => $booking['membership'],
                'studio_name' => $booking['studio_name'],
                'booking_date' => date('d/m/Y', strtotime($booking['booking_date'])),
                'booking_time' => date('H:i', strtotime($booking['booking_time'])),
                'duration' => $booking['duration'],
                'total_price' => $booking['total_price'],
                'cashback' => $booking['cashback'],
                'status' => ucfirst($booking['status']),
                'created_at' => $booking['created_at_formatted']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Booking not found'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>