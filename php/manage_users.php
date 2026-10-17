<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

// Bảo vệ admin
if (!is_logged_in() || !is_admin()) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$page_title = 'Quản Lý Người Dùng & Khách Hàng - BeautyShop Admin';
$admin_section_title = 'Khách Hàng & Tài Khoản';

$current_admin_id = get_current_user_id();
$message = '';
$error = '';

// ==========================================
// XỬ LÝ ACTIONS TÀI KHOẢN
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $act = $_POST['action'];

    if ($act === 'toggle_status') {
        $target_id = (int)$_POST['target_user_id'];
        if ($target_id === $current_admin_id) {
            $error = "Bạn không thể tự khóa tài khoản của chính mình!";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET status = IF(status = 'active', 'banned', 'active') WHERE user_id = ?");
            $stmt->execute([$target_id]);
            $message = "Đã cập nhật trạng thái hoạt động của tài khoản #$target_id.";
        }
    } elseif ($act === 'toggle_role') {
        $target_id = (int)$_POST['target_user_id'];
        if ($target_id === $current_admin_id) {
            $error = "Bạn không thể tự thay đổi vai trò của chính mình!";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET role = IF(role = 'admin', 'customer', 'admin') WHERE user_id = ?");
            $stmt->execute([$target_id]);
            $message = "Đã thay đổi quyền hạn tài khoản #$target_id.";
        }
    } elseif ($act === 'reset_password') {
        $target_id = (int)$_POST['target_user_id'];
        $new_pass = trim($_POST['new_password'] ?? '');
        if (strlen($new_pass) < 6) {
            $error = "Mật khẩu mới phải từ 6 ký tự trở lên.";
        } else {
            $hash = password_hash($new_pass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$hash, $target_id]);
            $message = "Đã đặt lại mật khẩu mới cho tài khoản #$target_id thành công!";
        }
    } elseif ($act === 'create_user') {
        $username = trim($_POST['username'] ?? '');
        $fullname = trim($_POST['fullname'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = in_array($_POST['role'], ['customer', 'admin']) ? $_POST['role'] : 'customer';

        if (empty($username) || empty($phone) || strlen($password) < 6) {
            $error = "Vui lòng điền đủ tên đăng nhập, số điện thoại và mật khẩu từ 6 ký tự.";
        } else {
            try {
                // Kiểm tra trùng username, phone
                $check = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR phone = ?");
                $check->execute([$username, $phone]);
                if ($check->fetch()) {
                    $error = "Tên đăng nhập hoặc số điện thoại đã tồn tại trong hệ thống!";
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, full_name, phone, email, password, role, status, avatar) VALUES (?, ?, ?, ?, ?, ?, 'active', 'default-avatar.png')");
                    $stmt->execute([$username, $fullname ?: $username, $phone, $email ?: null, $hash, $role]);
                    $message = "Đã tạo tài khoản thành viên '$username' thành công!";
                }
            } catch (Exception $e) {
                $error = "Lỗi tạo tài khoản: " . $e->getMessage();
            }
        }
    }
}

// ==========================================
// BỘ LỌC VÀ TRUY VẤN DỮ LIỆU
// ==========================================
$role_filter = $_GET['role'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$where_clauses = ["1=1"];
$params = [];

if ($role_filter !== 'all') {
    $where_clauses[] = "u.role = ?";
    $params[] = $role_filter;
}

if ($status_filter !== 'all') {
    $where_clauses[] = "u.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_clauses[] = "(u.username LIKE ? OR u.full_name LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)";
    $p = "%$search%";
    $params[] = $p; $params[] = $p; $params[] = $p; $params[] = $p;
}

$where_sql = implode(" AND ", $where_clauses);

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users u WHERE $where_sql");
$count_stmt->execute($params);
$total_users = $count_stmt->fetchColumn();
$total_pages = ceil($total_users / $limit);

// Lấy danh sách users kèm tổng số đơn và tổng chi tiêu
$sql = "
    SELECT u.*,
           COUNT(o.order_id) as total_orders,
           COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE 0 END), 0) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.user_id = o.user_id
    WHERE $where_sql
    GROUP BY u.user_id
    ORDER BY u.user_id DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Thống kê nhanh
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_cnt,
        SUM(CASE WHEN role = 'customer' THEN 1 ELSE 0 END) as customer_cnt,
        SUM(CASE WHEN status = 'banned' THEN 1 ELSE 0 END) as banned_cnt
    FROM users
")->fetch();

include 'admin_header.php';
?>

<?php if (!empty($message)): ?>
  <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div><?= htmlspecialchars($message) ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div><?= htmlspecialchars($error) ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- 1. THỐNG KÊ NHANH TÀI KHOẢN -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card-modern p-3 text-center">
      <div class="text-muted small fw-semibold">Tổng Tài Khoản</div>
      <div class="fs-4 fw-bold text-dark mt-1"><?= number_format($stats['total'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card-modern p-3 text-center border-start border-danger border-4">
      <div class="text-danger small fw-semibold">Quản Trị Viên (Admins)</div>
      <div class="fs-4 fw-bold text-danger mt-1"><?= number_format($stats['admin_cnt'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card-modern p-3 text-center border-start border-success border-4">
      <div class="text-success small fw-semibold">Khách Hàng Thành Viên</div>
      <div class="fs-4 fw-bold text-success mt-1"><?= number_format($stats['customer_cnt'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card-modern p-3 text-center border-start border-secondary border-4">
      <div class="text-secondary small fw-semibold">Tài Khoản Đang Khóa</div>
      <div class="fs-4 fw-bold text-secondary mt-1"><?= number_format($stats['banned_cnt'] ?? 0) ?></div>
    </div>
  </div>
</div>

<!-- 2. THANH CÔNG CỤ VÀ BỘ LỌC -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
  <div class="card-body p-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <div class="fw-bold text-dark fs-5">
        <i class="bi bi-people me-2 text-danger"></i>Danh Sách Thành Viên & Quản Trị
      </div>
      <button class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createUserModal" style="background: #d47a88; border-color: #d47a88;">
        <i class="bi bi-person-plus-fill me-1"></i> Tạo Tài Khoản Mới
      </button>
    </div>

    <form method="GET" action="manage_users.php" class="row g-2 align-items-center">
      <div class="col-md-5">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="search" class="form-control border-start-0" 
                 placeholder="Tìm theo username, họ tên, số điện thoại, email..." 
                 value="<?= htmlspecialchars($search) ?>">
        </div>
      </div>

      <div class="col-md-3">
        <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="all" <?= $role_filter === 'all' ? 'selected' : '' ?>>Tất cả vai trò</option>
          <option value="customer" <?= $role_filter === 'customer' ? 'selected' : '' ?>>Khách hàng (customer)</option>
          <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Quản trị viên (admin)</option>
        </select>
      </div>

      <div class="col-md-2">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Tất cả trạng thái</option>
          <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Hoạt động</option>
          <option value="banned" <?= $status_filter === 'banned' ? 'selected' : '' ?>>Bị khóa</option>
        </select>
      </div>

      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-sm btn-dark rounded-pill px-3">
          <i class="bi bi-funnel me-1"></i> Lọc
        </button>
        <?php if (!empty($search) || $role_filter !== 'all' || $status_filter !== 'all'): ?>
          <a href="manage_users.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-counterclockwise"></i> Xóa lọc
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- 3. BẢNG DANH SÁCH TÀI KHOẢN -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light text-uppercase text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
        <tr>
          <th class="ps-4">Thành Viên</th>
          <th>Liên Hệ</th>
          <th>Vai Trò</th>
          <th>Đơn Đã Đặt</th>
          <th>Tổng Chi Tiêu</th>
          <th>Trạng Thái</th>
          <th class="text-end pe-4">Thao Tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr>
            <td colspan="7" class="text-center py-5 text-muted">Không tìm thấy người dùng nào phù hợp.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($users as $u): 
            $is_self = ((int)$u['user_id'] === $current_admin_id);
          ?>
            <tr>
              <td class="ps-4">
                <div class="d-flex align-items-center gap-3">
                  <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                       style="width: 40px; height: 40px; background: linear-gradient(135deg, #d47a88, #9e4760);">
                    <?= strtoupper(substr($u['username'], 0, 1)) ?>
                  </div>
                  <div>
                    <div class="fw-bold text-dark">
                      <?= htmlspecialchars($u['full_name'] ?: $u['username']) ?>
                      <?php if ($is_self): ?>
                        <span class="badge bg-danger ms-1" style="font-size: 0.65rem;">(Bạn)</span>
                      <?php endif; ?>
                    </div>
                    <small class="text-muted">@<?= htmlspecialchars($u['username']) ?> • ID: #<?= $u['user_id'] ?></small>
                  </div>
                </div>
              </td>
              <td>
                <div><i class="bi bi-telephone me-1 text-muted"></i><?= htmlspecialchars($u['phone']) ?></div>
                <div class="small text-muted"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($u['email'] ?: 'Chưa cập nhật') ?></div>
              </td>
              <td>
                <span class="badge <?= $u['role'] === 'admin' ? 'bg-danger text-white' : 'bg-primary-subtle text-primary border border-primary-subtle' ?> rounded-pill px-3 py-2">
                  <?= $u['role'] === 'admin' ? '<i class="bi bi-shield-lock me-1"></i> Quản trị viên' : '<i class="bi bi-person me-1"></i> Khách hàng' ?>
                </span>
              </td>
              <td>
                <span class="fw-semibold text-secondary"><?= $u['total_orders'] ?> đơn</span>
              </td>
              <td>
                <span class="fw-bold text-danger"><?= format_price($u['total_spent']) ?></span>
              </td>
              <td>
                <span class="badge rounded-pill <?= $u['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?> px-3 py-2">
                  <?= $u['status'] === 'active' ? 'Hoạt động' : 'Bị khóa' ?>
                </span>
              </td>
              <td class="text-end pe-4">
                <div class="btn-group">
                  <!-- Đổi mật khẩu modal -->
                  <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill me-1" 
                          onclick="openResetPassword(<?= $u['user_id'] ?>, '<?= htmlspecialchars($u['username']) ?>')" title="Đổi mật khẩu">
                    <i class="bi bi-key"></i>
                  </button>

                  <?php if (!$is_self): ?>
                    <!-- Nút khóa / mở khóa -->
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="action" value="toggle_status">
                      <input type="hidden" name="target_user_id" value="<?= $u['user_id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-warning rounded-pill me-1" 
                              title="<?= $u['status'] === 'active' ? 'Khóa tài khoản này' : 'Mở khóa tài khoản' ?>"
                              onclick="return confirm('Bạn có chắc muốn <?= $u['status'] === 'active' ? 'KHÓA' : 'MỞ KHÓA' ?> tài khoản @<?= htmlspecialchars($u['username']) ?>?');">
                        <i class="bi bi-<?= $u['status'] === 'active' ? 'lock' : 'unlock' ?>"></i>
                      </button>
                    </form>

                    <!-- Chuyển vai trò admin / customer -->
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="action" value="toggle_role">
                      <input type="hidden" name="target_user_id" value="<?= $u['user_id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-info rounded-pill" 
                              title="Chuyển sang <?= $u['role'] === 'admin' ? 'Khách hàng' : 'Quản trị viên' ?>"
                              onclick="return confirm('Chuyển vai trò của tài khoản này sang <?= $u['role'] === 'admin' ? 'Khách hàng' : 'Quản trị viên' ?>?');">
                        <i class="bi bi-arrow-repeat"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- PHÂN TRANG -->
  <?php if ($total_pages > 1): ?>
    <div class="card-footer bg-white border-0 py-3 d-flex justify-content-between align-items-center">
      <div class="small text-muted">Hiển thị trang <strong><?= $page ?></strong> trên <strong><?= $total_pages ?></strong></div>
      <nav>
        <ul class="pagination pagination-sm mb-0">
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
              <a class="page-link" href="manage_users.php?role=<?= urlencode($role_filter) ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
  <?php endif; ?>
</div>

<!-- MODAL TẠO TÀI KHOẢN MỚI -->
<div class="modal fade" id="createUserModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content rounded-4 border-0 shadow">
      <form method="POST">
        <input type="hidden" name="action" value="create_user">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Tạo Tài Khoản Người Dùng Mới</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Tên đăng nhập <span class="text-danger">*</span></label>
            <input type="text" name="username" class="form-control" placeholder="ví dụ: huongthao" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Họ và tên</label>
            <input type="text" name="fullname" class="form-control" placeholder="Trần Hương Thảo">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Số điện thoại <span class="text-danger">*</span></label>
            <input type="tel" name="phone" class="form-control" placeholder="0912345678" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Email</label>
            <input type="email" name="email" class="form-control" placeholder="thao@gmail.com">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Mật khẩu khởi tạo <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" placeholder="Tối thiểu 6 ký tự" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Vai trò hệ thống</label>
            <select name="role" class="form-select">
              <option value="customer">Khách hàng thông thường</option>
              <option value="admin">Quản trị viên (Admin)</option>
            </select>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4" style="background:#d47a88; border-color:#d47a88;">Tạo Tài Khoản</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL ĐẶT LẠI MẬT KHẨU -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content rounded-4 border-0 shadow">
      <form method="POST">
        <input type="hidden" name="action" value="reset_password">
        <input type="hidden" name="target_user_id" id="reset_user_id">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Đặt Lại Mật Khẩu Thành Viên</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <p class="small text-muted mb-3">Bạn đang đổi mật khẩu cho tài khoản: <strong id="reset_username_label" class="text-dark"></strong></p>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Mật khẩu mới <span class="text-danger">*</span></label>
            <input type="password" name="new_password" class="form-control" placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)" required minlength="6">
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4" style="background:#d47a88; border-color:#d47a88;">Xác Nhận Đổi Mật Khẩu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openResetPassword(userId, username) {
  document.getElementById('reset_user_id').value = userId;
  document.getElementById('reset_username_label').innerText = '@' + username;
  new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}
</script>

<?php include 'admin_footer.php'; ?>
