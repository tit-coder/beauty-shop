<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $phone    = trim($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, phone, password, role) VALUES (?, ?, ?, 'customer')");
        $stmt->execute([$username, $phone, $password]);
        $_SESSION['success'] = "Đăng ký thành công! Hãy đăng nhập.";
        header("Location: login.php");
        exit;
    } catch(PDOException $e) {
        $error = "Tên đăng nhập đã tồn tại!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng ký - Beauty Cosmetics</title>
  <link rel="stylesheet" href="../css/register.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
<div class="container">
  <div class="card">
    <h1 class="title">Beauty Cosmetics</h1>
    <p class="subtitle">Đăng ký tài khoản mới</p>

    <?php if (isset($error)): ?>
      <div style="background:#fee; color:#c33; padding:12px; border-radius:8px; margin:15px 0;">
        <?= $error ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Tên đăng nhập</label>
        <input type="text" name="username" placeholder="Nhập tên đăng nhập" required>
      </div>

      <div class="form-group">
        <label>Số điện thoại</label>
        <input type="tel" name="phone" placeholder="Nhập số điện thoại" required>
      </div>

      <div class="form-group">
        <label>Mật khẩu</label>
        <div class="input-group">
          <input type="password" name="password" placeholder="Nhập mật khẩu mạnh" required>
          <i class='bx bx-hide toggle-password' onclick="togglePass(this)"></i>
        </div>
      </div>

      <button type="submit" class="btn">Đăng ký</button>

      <p class="login-text">
        Bạn đã có tài khoản? <a href="login.php">Đăng nhập</a>
      </p>
    </form>
  </div>
</div>

<script>
  function togglePass(icon) {
    const input = icon.previousElementSibling;
    if (input.type === "password") {
      input.type = "text";
      icon.classList.replace("bx-hide", "bx-show");
    } else {
      input.type = "password";
      icon.classList.replace("bx-show", "bx-hide");
    }
  }
</script>
</body>
</html>