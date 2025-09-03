<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception("Invalid JSON input");
    }
    
    if (!isset($input['studio_id']) || !isset($input['date']) || !isset($input['time']) || !isset($input['duration'])) {
        throw new Exception("Missing required parameters");
    }
    
    $studioId = (int)$input['studio_id'];
    $date = $input['date'];
    $time = $input['time'];
    $duration = (int)$input['duration'];
    
    // Validate input
    if ($studioId <= 0 || $duration <= 0) {
        throw new Exception("Invalid parameters");
    }
    
    require_once __DIR__ . '/../classes/BookingManager.php';
    $bookingManager = new BookingManager();
    
    // Check if time slot is available
    $available = $bookingManager->isTimeSlotAvailable($studioId, $date, $time, $duration);
    
    echo json_encode([
        'success' => true,
        'available' => $available,
        'studio_id' => $studioId,
        'date' => $date,
        'time' => $time,
        'duration' => $duration,
        'message' => $available ? 'Waktu tersedia' : 'Waktu sudah dibooking'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'available' => false,
        'debug' => [
            'file' => __FILE__,
            'line' => __LINE__,
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?>