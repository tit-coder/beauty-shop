<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: index.php"); // trang chủ khách hàng
        }
        exit;
    } else {
        $error = "Sai tên đăng nhập hoặc mật khẩu!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Đăng nhập - Beauty Cosmetics</title>
  <link rel="stylesheet" href="../css/login.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="logo">Beauty Cosmetics</div>
      <p class="tagline">Đăng nhập cùng Beauty Cosmetics</p>

      <?php if (isset($error)): ?>
        <div style="background:#fee; color:#c33; padding:12px; border-radius:8px; margin:15px 0;">
          <?= $error ?>
        </div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label>Tên đăng nhập</label>
          <input type="text" name="username" placeholder="Nhập tên đăng nhập hoặc email" required />
        </div>

        <div class="form-group">
          <label>Mật khẩu</label>
          <div class="input-group">
            <input type="password" name="password" placeholder="••••••••••••" required />
            <i class='bx bx-hide toggle-password' onclick="togglePass(this)"></i>
          </div>
        </div>

        <div class="checkbox-group">
          <input type="checkbox" id="remember" />
          <label for="remember">Nhớ mật khẩu</label>
        </div>

        <button type="submit" class="btn">Đăng nhập</button>
      </form>

      <div class="links">
        Bạn chưa có tài khoản? <a href="register.php">Đăng ký</a>
      </div>
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