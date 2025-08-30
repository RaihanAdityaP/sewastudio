<?php
class Studio {
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
        $data = json_decode(file_get_contents(__DIR__ . '/../data/studios.json'), true);
        $studios = [];
        foreach ($data as $s) {
            $studios[] = new Studio($s['id'], $s['name'], $s['price'], $s['facilities'], $s['image']);
        }
        return $studios;
    }

    public static function getById($id) {
        $studios = self::getAll();
        foreach ($studios as $studio) {
            if ($studio->getId() == $id) {
                return $studio;
            }
        }
        return null;
    }

    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getPrice() { return $this->price; }
    public function getFacilities() { return $this->facilities; }
    public function getImage() { return $this->image; }
}