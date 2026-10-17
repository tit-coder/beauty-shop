<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

$redirect = $_GET['redirect'] ?? '';
// Bảo vệ chống open redirect
if ($redirect && (strpos($redirect, 'http') === 0 || strpos($redirect, '//') === 0)) {
    $redirect = '';
}

// Nếu đã đăng nhập rồi thì điều hướng thẳng
if (is_logged_in()) {
    if (is_admin()) {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: " . ($redirect ?: "index.php"));
    }
    exit;
}

$error = '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input'] ?? '');
    $password    = $_POST['password'] ?? '';
    $redirect_to = trim($_POST['redirect'] ?? '');

    if (empty($login_input) || empty($password)) {
        $error = "Vui lòng nhập tên đăng nhập/email và mật khẩu.";
    } else {
        try {
            // Tìm theo username, email hoặc số điện thoại
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? OR phone = ? LIMIT 1");
            $stmt->execute([$login_input, $login_input, $login_input]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if (isset($user['status']) && $user['status'] === 'banned') {
                    $error = "Tài khoản của bạn đã bị tạm khóa do vi phạm chính sách. Vui lòng liên hệ quản trị viên.";
                } else {
                    // Phòng tránh session fixation
                    session_regenerate_id(true);

                    $user_id = $user['user_id'] ?? $user['id'];
                    $_SESSION['user_id']   = (int)$user_id;
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['full_name'] = !empty($user['full_name']) ? $user['full_name'] : $user['username'];
                    $_SESSION['role']       = $user['role'] ?? 'customer';
                    $_SESSION['avatar']     = $user['avatar'] ?? 'default-avatar.png';

                    if ($_SESSION['role'] === 'admin') {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: " . (!empty($redirect_to) ? $redirect_to : "index.php"));
                    }
                    exit;
                }
            } else {
                $error = "Thông tin đăng nhập hoặc mật khẩu không chính xác!";
            }
        } catch (Exception $e) {
            $error = "Lỗi hệ thống khi đăng nhập: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng nhập - BeautyShop Mỹ Phẩm Chính Hãng</title>
  
  <link rel="icon" type="image/png" href="../images/cosmetics.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --primary-color: #d47a88;
      --primary-hover: #b86270;
      --primary-dark: #3b1e28;
      --bg-cream: #fff9f8;
      --accent-gold: #c59d5f;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: linear-gradient(135deg, #fcf6f5 0%, #faecee 50%, #f7dfdf 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
    }

    .font-serif {
      font-family: 'Playfair Display', serif;
    }

    .auth-card {
      background: white;
      border-radius: 24px;
      box-shadow: 0 20px 45px rgba(212, 122, 136, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.8);
      overflow: hidden;
      max-width: 900px;
      width: 100%;
    }

    .auth-banner {
      background: linear-gradient(135deg, #3b1e28 0%, #5c2c3d 100%);
      color: white;
      padding: 3rem 2.5rem;
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .auth-banner::after {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle, rgba(212,122,136,0.2) 0%, transparent 70%);
      pointer-events: none;
    }

    .form-control {
      border-radius: 12px;
      padding: 0.75rem 1rem;
      border: 1.5px solid #eed9dc;
      font-size: 0.95rem;
      transition: all 0.25s;
    }

    .form-control:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 4px rgba(212, 122, 136, 0.15);
    }

    .btn-beauty {
      background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
      color: white;
      border: none;
      border-radius: 12px;
      padding: 0.85rem;
      font-weight: 600;
      letter-spacing: 0.5px;
      transition: all 0.3s;
    }

    .btn-beauty:hover {
      background: linear-gradient(135deg, var(--primary-hover), var(--primary-dark));
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(212, 122, 136, 0.3);
    }

    .demo-pill {
      cursor: pointer;
      font-size: 0.8rem;
      padding: 6px 12px;
      border-radius: 50px;
      background: #fdf2f4;
      border: 1px solid #f1c7cf;
      color: #9e4760;
      transition: all 0.2s;
    }

    .demo-pill:hover {
      background: var(--primary-color);
      color: white;
      border-color: var(--primary-color);
    }
  </style>
</head>
<body>

<div class="auth-card">
  <div class="row g-0">
    <!-- CỘT BÊN TRÁI: BANNER THƯƠNG HIỆU -->
    <div class="col-lg-5 d-none d-lg-flex auth-banner">
      <div>
        <a href="index.php" class="text-white text-decoration-none d-flex align-items-center gap-2 mb-4">
          <div class="rounded-circle d-flex align-items-center justify-content-center text-white" 
               style="width: 42px; height: 42px; background: rgba(255,255,255,0.15); backdrop-filter: blur(5px);">
            <i class="bi bi-flower1 fs-4 text-warning"></i>
          </div>
          <div>
            <div class="fw-bold tracking-wide font-serif" style="font-size: 1.4rem;">BEAUTYSHOP</div>
            <div style="font-size: 0.7rem; color: #ffccd5; letter-spacing: 2px;">LUXURY COSMETICS</div>
          </div>
        </a>

        <h3 class="font-serif fw-bold mt-4 mb-3" style="line-height: 1.3;">Khơi nguồn vẻ đẹp rạng ngời cùng bạn</h3>
        <p class="text-white-50 small mb-4">Đăng nhập để nhận ưu đãi thành viên độc quyền, theo dõi đơn hàng và trải nghiệm tư vấn thông minh từ AI Beauty Advisor.</p>
        
        <div class="d-flex flex-column gap-3">
          <div class="d-flex align-items-center gap-3">
            <i class="bi bi-patch-check-fill text-warning fs-5"></i>
            <span class="small text-white-75">100% Mỹ phẩm chính hãng có hóa đơn</span>
          </div>
          <div class="d-flex align-items-center gap-3">
            <i class="bi bi-robot text-info fs-5"></i>
            <span class="small text-white-75">Trợ lý AI phân tích loại da & phác đồ chuẩn</span>
          </div>
          <div class="d-flex align-items-center gap-3">
            <i class="bi bi-truck text-success fs-5"></i>
            <span class="small text-white-75">Miễn phí giao hàng toàn quốc từ 499.000₫</span>
          </div>
        </div>
      </div>

      <div class="pt-4 border-top border-white-10 text-white-50 small">
        © 2026 BeautyShop. Đồ án Thực tập Tốt nghiệp.
      </div>
    </div>

    <!-- CỘT BÊN PHẢI: FORM ĐĂNG NHẬP -->
    <div class="col-lg-7 p-4 p-md-5">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="font-serif fw-bold text-dark mb-1">Đăng Nhập</h2>
          <p class="text-muted small mb-0">Chào mừng bạn quay trở lại với BeautyShop</p>
        </div>
        <a href="index.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
          <i class="bi bi-house me-1"></i> Trang chủ
        </a>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 small py-2 d-flex align-items-center gap-2 mb-4" role="alert">
          <i class="bi bi-exclamation-triangle-fill fs-5"></i>
          <div><?= htmlspecialchars($error) ?></div>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 small py-2 d-flex align-items-center gap-2 mb-4" role="alert">
          <i class="bi bi-check-circle-fill fs-5"></i>
          <div><?= htmlspecialchars($success) ?></div>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php" autocomplete="on">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

        <div class="mb-3">
          <label class="form-label small fw-semibold text-secondary">Tên đăng nhập / Email / SĐT</label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted" style="border-radius: 12px 0 0 12px;">
              <i class="bi bi-person"></i>
            </span>
            <input type="text" name="login_input" id="login_input" class="form-control border-start-0" 
                   placeholder="Nhập username, email hoặc số điện thoại" required autofocus 
                   style="border-radius: 0 12px 12px 0;">
          </div>
        </div>

        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="form-label small fw-semibold text-secondary mb-0">Mật khẩu</label>
            <a href="javascript:void(0)" onclick="alert('Vui lòng liên hệ Admin qua hotline 1900 6868 để cấp lại mật khẩu.')" 
               class="small text-decoration-none" style="color: var(--primary-color);">Quên mật khẩu?</a>
          </div>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted" style="border-radius: 12px 0 0 12px;">
              <i class="bi bi-lock"></i>
            </span>
            <input type="password" name="password" id="password" class="form-control border-start-0 border-end-0" 
                   placeholder="Nhập mật khẩu của bạn" required>
            <button class="btn btn-light border border-start-0 text-muted" type="button" id="togglePasswordBtn"
                    style="border-radius: 0 12px 12px 0;">
              <i class="bi bi-eye"></i>
            </button>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="rememberMe" checked>
            <label class="form-check-label small text-muted" for="rememberMe">Ghi nhớ đăng nhập</label>
          </div>
        </div>

        <button type="submit" class="btn btn-beauty w-100 mb-3 shadow-sm">
          <i class="bi bi-box-arrow-in-right me-2"></i> Đăng Nhập
        </button>

        <!-- TÀI KHOẢN MẪU DÀNH CHO BẢO VỆ ĐỒ ÁN / DEMO -->
        <div class="p-3 bg-light rounded-3 mb-4 border">
          <div class="small fw-bold text-secondary mb-2 d-flex align-items-center gap-1">
            <i class="bi bi-key-fill text-warning"></i> Tài khoản mẫu dùng thử:
          </div>
          <div class="d-flex flex-wrap gap-2">
            <span class="demo-pill" onclick="fillAccount('admin', 'admin123')">
              <i class="bi bi-shield-lock me-1"></i> Admin (admin / admin123)
            </span>
            <span class="demo-pill" onclick="fillAccount('lananh', 'customer123')">
              <i class="bi bi-person-heart me-1"></i> Khách 1 (lananh / customer123)
            </span>
            <span class="demo-pill" onclick="fillAccount('minhthu', 'customer123')">
              <i class="bi bi-person me-1"></i> Khách 2 (minhthu / customer123)
            </span>
          </div>
        </div>

        <div class="text-center small text-muted">
          Chưa có tài khoản thành viên? 
          <a href="register.php" class="fw-bold text-decoration-none" style="color: var(--primary-color);">Đăng ký ngay</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Chức năng ẩn hiện mật khẩu
  const toggleBtn = document.getElementById('togglePasswordBtn');
  const passInput = document.getElementById('password');
  toggleBtn.addEventListener('click', function() {
    const isPassword = passInput.getAttribute('type') === 'password';
    passInput.setAttribute('type', isPassword ? 'text' : 'password');
    this.innerHTML = isPassword ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
  });

  // Chức năng tự động điền tài khoản demo
  function fillAccount(username, password) {
    document.getElementById('login_input').value = username;
    document.getElementById('password').value = password;
  }
</script>
</body>
</html>