<?php
interface StudioInterface {
    public function getId();
    public function getName();
    public function getPrice();
    public function getFacilities();
    public function getImage();
    public static function getAll();
    public static function getById($id);
}
?>