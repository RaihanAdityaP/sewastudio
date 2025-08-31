<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../interfaces/StudioInterface.php';

class Studio implements StudioInterface {
    private $id;
    private $name;
    private $price;
    private $facilities;
    private $image;

    public function __construct($id, $name, $price, $facilities, $image) {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->facilities = $facilities;
        $this->image = $image;
    }

    public static function getAll() {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "SELECT * FROM studios ORDER BY id";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $studios = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $studios[] = new Studio(
                    $row['id'],
                    $row['name'],
                    $row['price'],
                    $row['facilities'],
                    $row['image']
                );
            }
            
            return $studios;
        } catch (Exception $e) {
            // Fallback data if database fails
            return self::getFallbackStudios();
        }
    }

    public static function getById($id) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "SELECT * FROM studios WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return new Studio(
                    $row['id'],
                    $row['name'],
                    $row['price'],
                    $row['facilities'],
                    $row['image']
                );
            }
        } catch (Exception $e) {
            // Fallback data if database fails
            $fallbackStudios = self::getFallbackStudios();
            return $fallbackStudios[$id - 1] ?? null;
        }
        return null;
    }

    private static function getFallbackStudios() {
        return [
            new Studio(1, "Studio A", 50000, "Drum set, Gitar, Bass, Mic", "assets/img/studio1.jpg"),
            new Studio(2, "Studio B", 100000, "Full Band Set + Mixer", "assets/img/studio2.jpg"),
            new Studio(3, "Studio C", 200000, "Pro Equipment", "assets/img/studio3.jpg"),
        ];
    }

    // StudioInterface implementation
    public function getId() { 
        return $this->id; 
    }
    
    public function getName() { 
        return $this->name; 
    }
    
    public function getPrice() { 
        return $this->price; 
    }
    
    public function getFacilities() { 
        return $this->facilities; 
    }
    
    public function getImage() { 
        return $this->image; 
    }
}
?>