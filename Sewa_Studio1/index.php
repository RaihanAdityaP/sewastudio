<?php
session_start();
if (isset($_SESSION['user'])) {
    $usersFile = __DIR__ . "/data/users.json";
    $users = json_decode(file_get_contents($usersFile), true);

    foreach ($users as $u) {
        if ($u['email'] === $_SESSION['user']['email']) {
            $_SESSION['user'] = $u;
            break;
        }
    }
}
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
    }
    .btn-success { background: #28a745; }
    .btn-success:hover { background: #218838; }
    .btn-primary { background: #007bff; }
    .btn-primary:hover { background: #0056b3; }
    .btn-danger { background: #dc3545; }
    .btn-danger:hover { background: #c82333; }
    .btn-light { background: #f8f9fa; color: #000; border: 1px solid #ccc; }
    .btn-light:hover { background: #e2e6ea; }
  </style>
</head>
<body>
  <nav>
    <div><a href="index.php">Rental Studio Musik</a></div>
    <div>
      <?php if (isset($_SESSION['user'])): ?>
        <span>Halo, <?= $_SESSION['user']['name']; ?></span>
        <span class="saldo">Saldo: Rp <?= number_format($_SESSION['user']['saldo'] ?? 0, 0, ',', '.'); ?></span>
        <a href="topup.php" class="btn btn-primary">Top Up</a>
        <a href="logout.php" class="btn btn-danger">Logout</a>
      <?php else: ?>
        <a href="login.php" class="btn btn-light">Login</a>
      <?php endif; ?>
    </div>
  </nav>

  <div class="container">
    <h2>Daftar Studio</h2>
    <div class="row">
      <div class="card">
        <img src="assets/studio1.jpg" alt="Studio 1">
        <div class="card-body">
          <div>
            <h5>Studio 1</h5>
            <p><strong>Harga:</strong> Rp 50.000 / jam</p>
          </div>
          <div>
            <a href="booking.php?id=1" class="btn btn-success">Booking</a>
            <a href="detail.php?id=1" class="btn btn-primary">Detail</a>
          </div>
        </div>
      </div>

      <div class="card">
        <img src="assets/studio2.jpg" alt="Studio 2">
        <div class="card-body">
          <div>
            <h5>Studio 2</h5>
            <p><strong>Harga:</strong> Rp 100.000 / jam</p>
          </div>
          <div>
            <a href="booking.php?id=2" class="btn btn-success">Booking</a>
            <a href="detail.php?id=2" class="btn btn-primary">Detail</a>
          </div>
        </div>
      </div>

      <div class="card">
        <img src="assets/studio3.jpg" alt="Studio 3">
        <div class="card-body">
          <div>
            <h5>Studio 3</h5>
            <p><strong>Harga:</strong> Rp 200.000 / jam</p>
          </div>
          <div>
            <a href="booking.php?id=3" class="btn btn-success">Booking</a>
            <a href="detail.php?id=3" class="btn btn-primary">Detail</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
