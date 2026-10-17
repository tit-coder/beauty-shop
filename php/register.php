<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username         = trim($_POST['username'] ?? '');
    $full_name        = trim($_POST['full_name'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate dữ liệu
    if (empty($username) || empty($phone) || empty($password)) {
        $error = "Vui lòng điền đầy đủ các trường bắt buộc (Tên đăng nhập, SĐT, Mật khẩu).";
    } elseif (strlen($username) < 3 || strlen($username) > 30) {
        $error = "Tên đăng nhập phải từ 3 đến 30 ký tự.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = "Tên đăng nhập chỉ chứa chữ cái không dấu, số và dấu gạch dưới.";
    } elseif (!preg_match('/^[0-9]{9,12}$/', $phone)) {
        $error = "Số điện thoại không hợp lệ (cần từ 9 đến 12 chữ số).";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Địa chỉ email không đúng định dạng.";
    } elseif (strlen($password) < 6) {
        $error = "Mật khẩu phải có tối thiểu 6 ký tự để đảm bảo an toàn.";
    } elseif ($password !== $confirm_password) {
        $error = "Xác nhận mật khẩu không trùng khớp.";
    } else {
        try {
            // Kiểm tra trùng username, phone hoặc email
            $check_stmt = $pdo->prepare("SELECT user_id, username, phone, email FROM users WHERE username = ? OR phone = ? OR (email = ? AND email != '') LIMIT 1");
            $check_stmt->execute([$username, $phone, $email]);
            $existing = $check_stmt->fetch();

            if ($existing) {
                if ($existing['username'] === $username) {
                    $error = "Tên đăng nhập '$username' đã được sử dụng. Vui lòng chọn tên khác.";
                } elseif ($existing['phone'] === $phone) {
                    $error = "Số điện thoại '$phone' đã được đăng ký tài khoản.";
                } else {
                    $error = "Email '$email' đã tồn tại trong hệ thống.";
                }
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $insert_stmt = $pdo->prepare("INSERT INTO users (username, full_name, phone, email, password, role, status, avatar) VALUES (?, ?, ?, ?, ?, 'customer', 'active', 'default-avatar.png')");
                $insert_stmt->execute([
                    $username,
                    !empty($full_name) ? $full_name : $username,
                    $phone,
                    !empty($email) ? $email : null,
                    $hash
                ]);

                $_SESSION['success'] = "Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.";
                header("Location: login.php");
                exit;
            }
        } catch (Exception $e) {
            $error = "Lỗi khi tạo tài khoản: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng ký tài khoản - BeautyShop Mỹ Phẩm Cao Cấp</title>
  
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
      max-width: 960px;
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

    .form-control {
      border-radius: 12px;
      padding: 0.7rem 1rem;
      border: 1.5px solid #eed9dc;
      font-size: 0.92rem;
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

        <h3 class="font-serif fw-bold mt-4 mb-3" style="line-height: 1.3;">Gia nhập cộng đồng làm đẹp thông minh</h3>
        <p class="text-white-50 small mb-4">Tạo tài khoản miễn phí để nhận ngay Voucher 10% cho đơn hàng đầu tiên, lưu sản phẩm yêu thích và lưu trữ lịch sử tư vấn AI.</p>
        
        <div class="d-flex flex-column gap-3">
          <div class="d-flex align-items-center gap-3">
            <i class="bi bi-gift-fill text-warning fs-5"></i>
            <span class="small text-white-75">Tặng ngay Coupon BEAUTYNEW 10%</span>
          </div>
          <div class="d-flex align-items-center gap-3">
            <i class="bi bi-stars text-info fs-5"></i>
            <span class="small text-white-75">Cá nhân hóa chu trình dưỡng da Routine Builder</span>
          </div>
          <div class="d-flex align-items-center gap-3">
            <i class="bi bi-shield-check text-success fs-5"></i>
            <span class="small text-white-75">Bảo mật thông tin & tích điểm đổi quà VIP</span>
          </div>
        </div>
      </div>

      <div class="pt-4 border-top border-white-10 text-white-50 small">
        © 2026 BeautyShop. Đồ án Thực tập Tốt nghiệp.
      </div>
    </div>

    <!-- CỘT BÊN PHẢI: FORM ĐĂNG KÝ -->
    <div class="col-lg-7 p-4 p-md-5">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="font-serif fw-bold text-dark mb-1">Tạo Tài Khoản</h2>
          <p class="text-muted small mb-0">Đăng ký thành viên trong vòng 30 giây</p>
        </div>
        <a href="login.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
          <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập
        </a>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 small py-2 d-flex align-items-center gap-2 mb-4" role="alert">
          <i class="bi bi-exclamation-triangle-fill fs-5"></i>
          <div><?= htmlspecialchars($error) ?></div>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <form method="POST" action="register.php">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small fw-semibold text-secondary">Tên đăng nhập <span class="text-danger">*</span></label>
            <input type="text" name="username" class="form-control" placeholder="ví dụ: huonglan99" 
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-semibold text-secondary">Họ và tên</label>
            <input type="text" name="full_name" class="form-control" placeholder="Nguyễn Hương Lan" 
                   value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-semibold text-secondary">Số điện thoại <span class="text-danger">*</span></label>
            <input type="tel" name="phone" class="form-control" placeholder="0901234567" 
                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-semibold text-secondary">Email nhận thông báo</label>
            <input type="email" name="email" class="form-control" placeholder="lan@example.com" 
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-semibold text-secondary">Mật khẩu <span class="text-danger">*</span></label>
            <input type="password" name="password" id="regPassword" class="form-control" placeholder="Tối thiểu 6 ký tự" required>
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-semibold text-secondary">Xác nhận mật khẩu <span class="text-danger">*</span></label>
            <input type="password" name="confirm_password" id="regConfirm" class="form-control" placeholder="Nhập lại mật khẩu" required>
          </div>
        </div>

        <div class="form-check mt-3 mb-4">
          <input class="form-check-input" type="checkbox" id="termsCheck" required checked>
          <label class="form-check-label small text-muted" for="termsCheck">
            Tôi đồng ý với <a href="policies.php" target="_blank" class="text-decoration-none" style="color: var(--primary-color);">Điều khoản dịch vụ & Chính sách bảo mật</a> của BeautyShop
          </label>
        </div>

        <button type="submit" class="btn btn-beauty w-100 mb-3 shadow-sm">
          <i class="bi bi-person-plus-fill me-2"></i> Hoàn Tất Đăng Ký
        </button>

        <div class="text-center small text-muted">
          Đã có tài khoản? 
          <a href="login.php" class="fw-bold text-decoration-none" style="color: var(--primary-color);">Đăng nhập ngay</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>