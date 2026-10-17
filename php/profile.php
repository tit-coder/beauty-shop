<?php
session_start();
require_once __DIR__ . '/config.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$page_title = 'Tài Khoản & Địa Chỉ - BeautyShop';

$msg = '';
$msg_type = 'success';

// 1. Cập nhật thông tin cá nhân
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    try {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, email = ? WHERE id = ?");
        $stmt->execute([$full_name, $phone, $email, $user_id]);
        $msg = "Cập nhật thông tin cá nhân thành công!";
    } catch (Exception $e) {
        $msg = "Lỗi: " . $e->getMessage();
        $msg_type = 'danger';
    }
}

// 2. Đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $old_pass = $_POST['old_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    $uStmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $uStmt->execute([$user_id]);
    $curr_hash = $uStmt->fetchColumn();

    if (!password_verify($old_pass, $curr_hash)) {
        $msg = "Mật khẩu hiện tại không chính xác!";
        $msg_type = 'danger';
    } elseif (strlen($new_pass) < 6) {
        $msg = "Mật khẩu mới phải có ít nhất 6 ký tự!";
        $msg_type = 'danger';
    } elseif ($new_pass !== $confirm_pass) {
        $msg = "Xác nhận mật khẩu mới không khớp!";
        $msg_type = 'danger';
    } else {
        $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$new_hash, $user_id]);
        $msg = "Đổi mật khẩu thành công!";
    }
}

// 3. Thêm địa chỉ mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_address') {
    $receiver_name = trim($_POST['receiver_name'] ?? '');
    $receiver_phone = trim($_POST['receiver_phone'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $ward = trim($_POST['ward'] ?? '');
    $street_address = trim($_POST['street_address'] ?? '');
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    if (!empty($receiver_name) && !empty($receiver_phone) && !empty($street_address)) {
        if ($is_default) {
            $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
        }
        $stmt = $pdo->prepare("
            INSERT INTO addresses (user_id, receiver_name, receiver_phone, province, district, ward, street_address, is_default, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $receiver_name, $receiver_phone, $province, $district, $ward, $street_address, $is_default]);
        $msg = "Thêm địa chỉ giao hàng mới thành công!";
    }
}

// 4. Xóa địa chỉ
if (isset($_GET['delete_addr'])) {
    $addr_id = (int)$_GET['delete_addr'];
    $pdo->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?")->execute([$addr_id, $user_id]);
    header("Location: profile.php");
    exit;
}

// Lấy thông tin user hiện tại
$user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$user_id]);
$u = $user->fetch(PDO::FETCH_ASSOC);

// Lấy danh sách địa chỉ
$addresses = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
$addresses->execute([$user_id]);
$user_addresses = $addresses->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<div style="height: 125px;"></div>

<!-- Breadcrumb -->
<div class="py-2 bg-light border-bottom">
  <div class="container-fluid px-lg-5">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Trang chủ</a></li>
        <li class="breadcrumb-item active" style="color: #9e4760;">Thông tin tài khoản & Địa chỉ</li>
      </ol>
    </nav>
  </div>
</div>

<div class="py-5" style="background: #fffdfb; min-height: 80vh;">
  <div class="container-fluid px-lg-5">
    
    <div class="mb-4">
      <h2 class="fw-bold mb-1" style="color: #4a2835;">
        <i class="bi bi-person-gear text-danger me-2"></i> Quản Lý Tài Khoản Cá Nhân
      </h2>
      <p class="text-muted small">Cập nhật thông tin nhận hàng, đổi mật khẩu và quản lý sổ địa chỉ của bạn</p>
    </div>

    <?php if ($msg): ?>
      <div class="alert alert-<?= $msg_type ?> rounded-4 p-3 mb-4 shadow-xs">
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      
      <!-- Cột Trái: Cập nhật thông tin & Đổi mật khẩu -->
      <div class="col-lg-6">
        
        <!-- Form Thông tin cá nhân -->
        <div class="card border-0 rounded-4 shadow-sm bg-white p-4 mb-4">
          <h5 class="fw-bold mb-3" style="color: #4a2835;">Thông Tin Hồ Sơ</h5>
          <form method="POST">
            <input type="hidden" name="action" value="update_profile">

            <div class="mb-3">
              <label class="form-label small fw-bold text-muted">Tên đăng nhập:</label>
              <input type="text" class="form-control rounded-3 bg-light" value="<?= htmlspecialchars($u['username']) ?>" readonly>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold text-muted">Họ và tên hiển thị:</label>
              <input type="text" name="full_name" class="form-control rounded-3" value="<?= htmlspecialchars($u['full_name'] ?? '') ?>" placeholder="Nguyễn Văn A">
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold text-muted">Số điện thoại:</label>
                <input type="tel" name="phone" class="form-control rounded-3" value="<?= htmlspecialchars($u['phone']) ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold text-muted">Email:</label>
                <input type="email" name="email" class="form-control rounded-3" value="<?= htmlspecialchars($u['email'] ?? '') ?>" placeholder="email@gmail.com">
              </div>
            </div>

            <button type="submit" class="btn btn-danger rounded-pill px-4 py-2 text-white" style="background:#d47a88; border:none;">
              Lưu thay đổi hồ sơ
            </button>
          </form>
        </div>

        <!-- Form Đổi mật khẩu -->
        <div class="card border-0 rounded-4 shadow-sm bg-white p-4">
          <h5 class="fw-bold mb-3" style="color: #4a2835;">Đổi Mật Khẩu</h5>
          <form method="POST">
            <input type="hidden" name="action" value="change_password">

            <div class="mb-3">
              <label class="form-label small fw-bold text-muted">Mật khẩu hiện tại:</label>
              <input type="password" name="old_password" class="form-control rounded-3" required>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold text-muted">Mật khẩu mới:</label>
                <input type="password" name="new_password" class="form-control rounded-3" minlength="6" required>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold text-muted">Xác nhận mật khẩu mới:</label>
                <input type="password" name="confirm_password" class="form-control rounded-3" minlength="6" required>
              </div>
            </div>

            <button type="submit" class="btn btn-outline-danger rounded-pill px-4 py-2" style="border-color:#d47a88; color:#d47a88;">
              Cập nhật mật khẩu mới
            </button>
          </form>
        </div>

      </div>

      <!-- Cột Phải: Sổ địa chỉ nhận hàng -->
      <div class="col-lg-6">
        <div class="card border-0 rounded-4 shadow-sm bg-white p-4 mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0" style="color: #4a2835;">Sổ Địa Chỉ Giao Hàng</h5>
            <button class="btn btn-sm btn-outline-danger rounded-pill px-3" data-bs-toggle="collapse" data-bs-target="#addAddrBox">
              <i class="bi bi-plus-lg me-1"></i> Thêm địa chỉ mới
            </button>
          </div>

          <!-- Form Thêm địa chỉ (Ẩn/Hiện) -->
          <div class="collapse mb-4 p-3 bg-light rounded-4 border" id="addAddrBox">
            <h6 class="fw-bold mb-3 text-dark">Thêm Địa Chỉ Nhận Hàng Mới</h6>
            <form method="POST">
              <input type="hidden" name="action" value="add_address">
              
              <div class="row g-2 mb-2">
                <div class="col-md-6">
                  <input type="text" name="receiver_name" class="form-control form-control-sm rounded-2" placeholder="Họ tên người nhận *" required>
                </div>
                <div class="col-md-6">
                  <input type="tel" name="receiver_phone" class="form-control form-control-sm rounded-2" placeholder="Số điện thoại *" required>
                </div>
              </div>

              <div class="row g-2 mb-2">
                <div class="col-md-4">
                  <input type="text" name="province" class="form-control form-control-sm rounded-2" placeholder="Tỉnh / Thành phố *" required>
                </div>
                <div class="col-md-4">
                  <input type="text" name="district" class="form-control form-control-sm rounded-2" placeholder="Quận / Huyện *" required>
                </div>
                <div class="col-md-4">
                  <input type="text" name="ward" class="form-control form-control-sm rounded-2" placeholder="Phường / Xã *" required>
                </div>
              </div>

              <div class="mb-2">
                <input type="text" name="street_address" class="form-control form-control-sm rounded-2" placeholder="Số nhà, tên đường chi tiết *" required>
              </div>

              <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="is_default" id="chkDef" checked>
                <label class="form-check-label small" for="chkDef">Đặt làm địa chỉ giao hàng mặc định</label>
              </div>

              <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 text-white" style="background:#d47a88; border:none;">
                Lưu địa chỉ
              </button>
            </form>
          </div>

          <!-- Danh sách địa chỉ đã lưu -->
          <div class="d-flex flex-column gap-3">
            <?php if (empty($user_addresses)): ?>
              <p class="text-muted small mb-0">Bạn chưa lưu địa chỉ nhận hàng nào. Hãy thêm địa chỉ để thanh toán nhanh hơn!</p>
            <?php else: ?>
              <?php foreach ($user_addresses as $addr): ?>
                <div class="p-3 rounded-4 border bg-white shadow-xs position-relative <?= $addr['is_default'] ? 'border-danger border-opacity-50' : '' ?>">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <strong class="text-dark"><?= htmlspecialchars($addr['receiver_name']) ?> (<?= htmlspecialchars($addr['receiver_phone']) ?>)</strong>
                    <?php if ($addr['is_default']): ?>
                      <span class="badge bg-danger rounded-pill small">Mặc định</span>
                    <?php endif; ?>
                  </div>
                  <p class="text-muted small mb-2">
                    <?= htmlspecialchars($addr['street_address'] . ', ' . $addr['ward'] . ', ' . $addr['district'] . ', ' . $addr['province']) ?>
                  </p>
                  <div class="d-flex justify-content-end">
                    <a href="profile.php?delete_addr=<?= $addr['id'] ?>" class="btn btn-xs btn-outline-danger py-1 px-2 small" onclick="return confirm('Xóa địa chỉ này?')" style="font-size:0.75rem;">
                      <i class="bi bi-trash"></i> Xóa
                    </a>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

        </div>
      </div>

    </div>

  </div>
</div>

<?php include 'footer.php'; ?>

