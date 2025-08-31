<?php
require_once __DIR__ . '/../config/Database.php';
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
    }

    public static function getById($id) {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT * FROM studios WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

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
        return null;
    }

    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getPrice() { return $this->price; }
    public function getFacilities() { return $this->facilities; }
    public function getImage() { return $this->image; }
}