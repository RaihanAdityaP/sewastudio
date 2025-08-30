<?php
require_once __DIR__ . "/classes/Studio.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$studio = Studio::getById($id);

if (!$studio) {
    die("Studio tidak ditemukan!");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Detail Studio</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin:0; padding:0; }
        header { background: #333; color: white; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; }
        .container { max-width: 800px; margin: 30px auto; background: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.2); overflow: hidden; }
        .container img { width:100%; height:300px; object-fit:cover; display: block; border-bottom: 1px solid #ddd; }
        .info { padding: 20px; }
        .info h2 { margin: 0 0 10px; }
        .info p { margin: 5px 0; }
        .btn { display: inline-block; background: #555; color: white; padding: 8px 16px; text-decoration: none; border-radius: 5px; margin-top: 15px; }
        .btn:hover { background: #222; }
    </style>
</head>
<body>
    <header>Detail Studio</header>
    <div class="container">
        <img src="<?= $studio->getImage(); ?>" alt="<?= $studio->getName(); ?>">
        <div class="info">
            <h2><?= $studio->getName(); ?></h2>
            <p><strong>Harga:</strong> Rp <?= number_format($studio->getPrice(), 0, ',', '.'); ?> / jam</p>
            <p><strong>Fasilitas Lengkap:</strong> <?= $studio->getFacilities(); ?></p>
            <a href="index.php" class="btn">Kembali</a>
        </div>
    </div>
</body>
</html>