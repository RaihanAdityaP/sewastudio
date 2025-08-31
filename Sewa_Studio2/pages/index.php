<?php
session_start();
require_once "../classes/Studio.php";
require_once "../classes/Auth.php";

$user = null;
if (isset($_SESSION['user'])) {
    $auth = new Auth();
    $user = $auth->getUserById($_SESSION['user']['id']);
    if ($user) {
        $_SESSION['user'] = [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'membership' => $user->getMembership(),
            'saldo' => $user->getSaldo()
        ];
    }
}

$studios = Studio::getAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Rental Studio</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background: #f2f2f2;
    }
    nav {
      background: #222;
      padding: 10px 20px;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    nav a {
      color: white;
      text-decoration: none;
      margin-left: 15px;
      font-weight: bold;
    }
    .saldo {
      background: #007bff;
      padding: 5px 10px;
      border-radius: 5px;
      font-size: 14px;
      margin-left: 15px;
    }
    .membership {
      background: #28a745;
      padding: 5px 10px;
      border-radius: 5px;
      font-size: 14px;
      margin-left: 15px;
    }
    .container {
      width: 90%;
      max-width: 1100px;
      margin: 20px auto;
    }
    h2 {
      text-align: center;
      margin-bottom: 30px;
    }
    .row {
      display: flex;
      gap: 20px;
      flex-wrap: wrap;
      justify-content: center;
    }
    .card {
      background: white;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      width: 300px;
      min-height: 380px;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      transition: all 0.3s ease;
    }
    .card:hover {
      transform: translateY(-8px);
      box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    }
    .card img {
      width: 100%;
      height: 180px;
      object-fit: cover;
    }
    .card-body {
      flex: 1;
      padding: 15px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .card-body h5 {
      margin: 0 0 10px 0;
      font-size: 18px;
    }
    .card-body p {
      margin: 5px 0 15px;
      color: #333;
    }
    .btn {
      display: inline-block;
      padding: 8px 12px;
      border-radius: 5px;
      text-decoration: none;
      color: white;
      font-size: 14px;
      margin-right: 5px;
      transition: background 0.3s ease;
      text-align: center;
    }
    .btn-success { background: #28a745; }
    .btn-success:hover { background: #218838; }
    .btn-primary { background: #007bff; }
    .btn-primary:hover { background: #0056b3; }
    .btn-danger { background: #dc3545; }
    .btn-danger:hover { background: #c82333; }
    .btn-warning { background: #ffc107; color: #000; }
    .btn-warning:hover { background: #e0a800; }
    .btn-light { background: #f8f9fa; color: #000; border: 1px solid #ccc; }
    .btn-light:hover { background: #e2e6ea; }
    .btn-disabled { background: #6c757d; cursor: not-allowed; }
    .access-info {
      font-size: 12px;
      color: #666;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>
  <nav>
    <div><a href="index.php">Rental Studio Musik</a></div>
    <div>
      <?php if (isset($_SESSION['user'])): ?>
        <span>Halo, <?= $_SESSION['user']['name']; ?></span>
        <span class="membership"><?= $_SESSION['user']['membership']; ?></span>
        <span class="saldo">Saldo: Rp <?= number_format($_SESSION['user']['saldo'] ?? 0, 0, ',', '.'); ?></span>
        <a href="topup.php" class="btn btn-primary">Top Up</a>
        <?php if ($user && $user->getMembership() != 'VVIP'): ?>
            <a href="upgrade.php" class="btn btn-warning">Upgrade</a>
        <?php endif; ?>
        <a href="logout.php" class="btn btn-danger">Logout</a>
      <?php else: ?>
        <a href="login.php" class="btn btn-light">Login</a>
      <?php endif; ?>
    </div>
  </nav>

  <div class="container">
    <h2>Daftar Studio</h2>
    <div class="row">
      <?php foreach ($studios as $studio): ?>
        <div class="card">
          <img src="../<?= $studio->getImage(); ?>" alt="<?= $studio->getName(); ?>" onerror="this.src='../assets/img/default.jpg'">
          <div class="card-body">
            <div>
              <h5><?= $studio->getName(); ?></h5>
              <p><strong>Harga:</strong> Rp <?= number_format($studio->getPrice(), 0, ',', '.'); ?> / jam</p>
              
              <?php if (isset($_SESSION['user'])): ?>
                <div class="access-info">
                  <?php if ($user && $user->canAccessStudio($studio->getId())): ?>
                    ✅ Anda dapat mengakses studio ini
                  <?php else: ?>
                    ❌ Perlu upgrade membership
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
            <div>
              <?php if (isset($_SESSION['user']) && $user && $user->canAccessStudio($studio->getId())): ?>
                <a href="booking.php?id=<?= $studio->getId(); ?>" class="btn btn-success">Booking</a>
              <?php elseif (isset($_SESSION['user'])): ?>
                <span class="btn btn-disabled">Perlu Upgrade</span>
              <?php else: ?>
                <a href="login.php" class="btn btn-success">Login untuk Booking</a>
              <?php endif; ?>
              <a href="detail.php?id=<?= $studio->getId(); ?>" class="btn btn-primary">Detail</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>