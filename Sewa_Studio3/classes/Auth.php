<?php
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../interfaces/AuthInterface.php';

class Auth implements AuthInterface {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function register($name, $email, $password, $membership = "Regular", $saldo = 0) {
        // Force membership to Regular for new registrations
        $membership = "Regular";
        
        // Check if email already exists
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return false; // Email already exists
        }

        // Insert new user
        $query = "INSERT INTO users (name, email, password, membership, saldo) VALUES (:name, :email, :password, :membership, :saldo)";
        $stmt = $this->db->prepare($query);
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':membership', $membership);
        $stmt->bindParam(':saldo', $saldo);

        return $stmt->execute();
    }

    public function login($email, $password) {
        $query = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $user['password'])) {
                // Create appropriate user object based on membership
                switch ($user['membership']) {
                    case 'VIP':
                        return new VIPUser(
                            $user['id'],
                            $user['name'],
                            $user['email'],
                            $user['password'],
                            $user['membership'],
                            $user['saldo']
                        );
                    case 'VVIP':
                        return new VVIPUser(
                            $user['id'],
                            $user['name'],
                            $user['email'],
                            $user['password'],
                            $user['membership'],
                            $user['saldo']
                        );
                    default:
                        return new RegularUser(
                            $user['id'],
                            $user['name'],
                            $user['email'],
                            $user['password'],
                            $user['membership'],
                            $user['saldo']
                        );
                }
            }
        }
        return null;
    }

    public function getUserById($id) {
        $query = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            switch ($userData['membership']) {
                case 'VIP':
                    return new VIPUser(
                        $userData['id'],
                        $userData['name'],
                        $userData['email'],
                        $userData['password'],
                        $userData['membership'],
                        $userData['saldo']
                    );
                case 'VVIP':
                    return new VVIPUser(
                        $userData['id'],
                        $userData['name'],
                        $userData['email'],
                        $userData['password'],
                        $userData['membership'],
                        $userData['saldo']
                    );
                default:
                    return new RegularUser(
                        $userData['id'],
                        $userData['name'],
                        $userData['email'],
                        $userData['password'],
                        $userData['membership'],
                        $userData['saldo']
                    );
            }
        }
        return null;
    }
}
?>