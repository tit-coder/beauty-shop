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

$page_title = 'Quản Lý Đánh Giá Khách Hàng - BeautyShop Admin';
$admin_section_title = 'Đánh Giá Sản Phẩm';

$message = '';
$error = '';

// Helper tính toán lại rating trên products
function recalculate_product_rating($pdo, $product_id) {
    $stmt = $pdo->prepare("
        UPDATE products 
        SET rating_avg = (SELECT COALESCE(ROUND(AVG(rating), 2), 5.0) FROM reviews WHERE product_id = ? AND is_approved = 1),
            rating_count = (SELECT COUNT(*) FROM reviews WHERE product_id = ? AND is_approved = 1)
        WHERE product_id = ?
    ");
    $stmt->execute([$product_id, $product_id, $product_id]);
}

// ==========================================
// XỬ LÝ ACTIONS ĐÁNH GIÁ
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $act = $_POST['action'];

    if ($act === 'toggle_approval') {
        $review_id = (int)$_POST['review_id'];
        $rev = $pdo->prepare("SELECT product_id, is_approved FROM reviews WHERE review_id = ?");
        $rev->execute([$review_id]);
        $row = $rev->fetch();

        if ($row) {
            $new_appr = $row['is_approved'] ? 0 : 1;
            $pdo->prepare("UPDATE reviews SET is_approved = ? WHERE review_id = ?")->execute([$new_appr, $review_id]);
            recalculate_product_rating($pdo, $row['product_id']);
            $message = "Đã cập nhật trạng thái duyệt của đánh giá #" . $review_id;
        }
    } elseif ($act === 'reply') {
        $review_id = (int)$_POST['review_id'];
        $reply_text = trim($_POST['reply_text'] ?? '');

        if ($review_id > 0 && !empty($reply_text)) {
            $stmt = $pdo->prepare("UPDATE reviews SET reply = ?, replied_at = NOW() WHERE review_id = ?");
            $stmt->execute([$reply_text, $review_id]);
            $message = "Đã gửi câu trả lời phản hồi cho khách hàng thành công!";
        } else {
            $error = "Vui lòng nhập nội dung câu trả lời.";
        }
    } elseif ($act === 'delete') {
        $review_id = (int)$_POST['review_id'];
        $rev = $pdo->prepare("SELECT product_id FROM reviews WHERE review_id = ?");
        $rev->execute([$review_id]);
        $row = $rev->fetch();

        if ($row) {
            $pdo->prepare("DELETE FROM reviews WHERE review_id = ?")->execute([$review_id]);
            recalculate_product_rating($pdo, $row['product_id']);
            $message = "Đã xóa đánh giá thành công!";
        }
    }
}

// ==========================================
// BỘ LỌC VÀ TRUY VẤN DỮ LIỆU
// ==========================================
$rating_filter = $_GET['rating'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$where_clauses = ["1=1"];
$params = [];

if ($rating_filter !== 'all') {
    $where_clauses[] = "r.rating = ?";
    $params[] = (int)$rating_filter;
}

if ($status_filter === 'approved') {
    $where_clauses[] = "r.is_approved = 1";
} elseif ($status_filter === 'pending') {
    $where_clauses[] = "r.is_approved = 0";
}

if (!empty($search)) {
    $where_clauses[] = "(p.product_name LIKE ? OR u.username LIKE ? OR u.full_name LIKE ? OR r.comment LIKE ?)";
    $p = "%$search%";
    $params[] = $p; $params[] = $p; $params[] = $p; $params[] = $p;
}

$where_sql = implode(" AND ", $where_clauses);

$count_stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM reviews r
    JOIN products p ON r.product_id = p.product_id
    JOIN users u ON r.user_id = u.user_id
    WHERE $where_sql
");
$count_stmt->execute($params);
$total_reviews = $count_stmt->fetchColumn();
$total_pages = ceil($total_reviews / $limit);

// Lấy danh sách reviews
$sql = "
    SELECT r.*, p.product_name, p.image as product_image, u.username, u.full_name, u.avatar as user_avatar
    FROM reviews r
    JOIN products p ON r.product_id = p.product_id
    JOIN users u ON r.user_id = u.user_id
    WHERE $where_sql
    ORDER BY r.review_id DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Thống kê nhanh
$stat = $pdo->query("
    SELECT 
        COUNT(*) as total,
        AVG(rating) as avg_rating,
        SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved_cnt,
        SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as pending_cnt,
        SUM(CASE WHEN reply IS NOT NULL AND reply != '' THEN 1 ELSE 0 END) as replied_cnt
    FROM reviews
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

<!-- 1. THỐNG KÊ NHANH ĐÁNH GIÁ -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card-modern p-3 text-center">
      <div class="text-muted small fw-semibold">Tổng Đánh Giá</div>
      <div class="fs-4 fw-bold text-dark mt-1"><?= number_format($stat['total'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card-modern p-3 text-center border-start border-warning border-4">
      <div class="text-warning small fw-semibold">Điểm TB Hệ Thống</div>
      <div class="fs-4 fw-bold text-warning mt-1">
        <?= number_format($stat['avg_rating'] ?? 5, 1) ?> <i class="bi bi-star-fill text-warning fs-6"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card-modern p-3 text-center border-start border-success border-4">
      <div class="text-success small fw-semibold">Đã Duyệt Công Khai</div>
      <div class="fs-4 fw-bold text-success mt-1"><?= number_format($stat['approved_cnt'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card-modern p-3 text-center border-start border-info border-4">
      <div class="text-info small fw-semibold">Đã Phản Hồi Khách</div>
      <div class="fs-4 fw-bold text-info mt-1"><?= number_format($stat['replied_cnt'] ?? 0) ?></div>
    </div>
  </div>
</div>

<!-- 2. THANH CÔNG CỤ VÀ BỘ LỌC -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
  <div class="card-body p-3">
    <div class="fw-bold text-dark fs-5 mb-3">
      <i class="bi bi-star me-2 text-warning"></i>Quản Lý Nhận Xét & Phản Hồi Khách Hàng
    </div>

    <form method="GET" action="manage_reviews.php" class="row g-2 align-items-center">
      <div class="col-md-5">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="search" class="form-control border-start-0" 
                 placeholder="Tìm theo tên sản phẩm, tên khách hoặc nội dung đánh giá..." 
                 value="<?= htmlspecialchars($search) ?>">
        </div>
      </div>

      <div class="col-md-3">
        <select name="rating" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="all" <?= $rating_filter === 'all' ? 'selected' : '' ?>>Tất cả số sao</option>
          <option value="5" <?= $rating_filter === '5' ? 'selected' : '' ?>>5 Sao (Rất tốt)</option>
          <option value="4" <?= $rating_filter === '4' ? 'selected' : '' ?>>4 Sao (Hài lòng)</option>
          <option value="3" <?= $rating_filter === '3' ? 'selected' : '' ?>>3 Sao (Bình thường)</option>
          <option value="2" <?= $rating_filter === '2' ? 'selected' : '' ?>>2 Sao (Chưa ưng ý)</option>
          <option value="1" <?= $rating_filter === '1' ? 'selected' : '' ?>>1 Sao (Kém)</option>
        </select>
      </div>

      <div class="col-md-2">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Tất cả trạng thái</option>
          <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Đã hiển thị</option>
          <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Đang ẩn / Chờ duyệt</option>
        </select>
      </div>

      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-sm btn-dark rounded-pill px-3">
          <i class="bi bi-funnel me-1"></i> Lọc
        </button>
        <?php if (!empty($search) || $rating_filter !== 'all' || $status_filter !== 'all'): ?>
          <a href="manage_reviews.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-counterclockwise"></i> Xóa lọc
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- 3. DANH SÁCH ĐÁNH GIÁ -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light text-uppercase text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
        <tr>
          <th class="ps-4" style="width: 250px;">Sản Phẩm</th>
          <th style="width: 180px;">Khách Hàng</th>
          <th style="width: 120px;">Đánh Giá</th>
          <th>Nội Dung & Phản Hồi</th>
          <th style="width: 130px;">Trạng Thái</th>
          <th class="text-end pe-4" style="width: 120px;">Thao Tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($reviews)): ?>
          <tr>
            <td colspan="6" class="text-center py-5 text-muted">Không tìm thấy đánh giá nào.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($reviews as $r): 
            $prod_img = (strpos($r['product_image'], 'http') === 0) ? $r['product_image'] : '../images/products/' . $r['product_image'];
          ?>
            <tr>
              <td class="ps-4">
                <div class="d-flex align-items-center gap-2">
                  <img src="<?= htmlspecialchars($prod_img) ?>" class="rounded-2 border" style="width: 44px; height: 44px; object-fit: cover;" onerror="this.src='../images/products/kem-duong-phuc-hoi-b5.jpg'">
                  <div>
                    <a href="product_detail.php?id=<?= $r['product_id'] ?>" target="_blank" class="fw-bold text-dark text-decoration-none text-truncate d-block" style="max-width: 180px;">
                      <?= htmlspecialchars($r['product_name']) ?>
                    </a>
                    <small class="text-muted">ID SP: #<?= $r['product_id'] ?></small>
                  </div>
                </div>
              </td>
              <td>
                <div class="fw-semibold text-dark"><?= htmlspecialchars($r['full_name'] ?: $r['username']) ?></div>
                <small class="text-muted">@<?= htmlspecialchars($r['username']) ?></small>
              </td>
              <td>
                <div class="text-warning">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi bi-star<?= $i <= $r['rating'] ? '-fill' : '' ?>" style="font-size: 0.85rem;"></i>
                  <?php endfor; ?>
                </div>
                <small class="text-muted d-block" style="font-size: 0.7rem;"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></small>
              </td>
              <td>
                <p class="mb-2 text-dark small" style="line-height: 1.4;">
                  <?= nl2br(htmlspecialchars($r['comment'])) ?>
                </p>

                <?php if (!empty($r['reply'])): ?>
                  <div class="p-2 bg-light rounded-3 border-start border-danger border-3 small">
                    <div class="fw-bold text-danger d-flex align-items-center gap-1" style="font-size: 0.75rem;">
                      <i class="bi bi-reply-fill"></i> Phản hồi từ BeautyShop:
                    </div>
                    <div class="text-muted mt-1"><?= htmlspecialchars($r['reply']) ?></div>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="toggle_approval">
                  <input type="hidden" name="review_id" value="<?= $r['review_id'] ?>">
                  <button type="submit" class="badge rounded-pill border-0 px-3 py-2 <?= $r['is_approved'] ? 'bg-success' : 'bg-secondary' ?>" 
                          title="Bấm để ẩn hoặc duyệt công khai">
                    <?= $r['is_approved'] ? 'Hiển thị' : 'Đang ẩn' ?>
                  </button>
                </form>
              </td>
              <td class="text-end pe-4">
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-outline-primary rounded-pill me-1" 
                          onclick="openReplyModal(<?= $r['review_id'] ?>, '<?= htmlspecialchars(addslashes($r['comment'])) ?>', '<?= htmlspecialchars(addslashes($r['reply'] ?? '')) ?>')"
                          title="Trả lời đánh giá">
                    <i class="bi bi-chat-dots"></i>
                  </button>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa đánh giá này?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="review_id" value="<?= $r['review_id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill" title="Xóa">
                      <i class="bi bi-trash3"></i>
                    </button>
                  </form>
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
              <a class="page-link" href="manage_reviews.php?rating=<?= urlencode($rating_filter) ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
  <?php endif; ?>
</div>

<!-- MODAL PHẢN HỒI ĐÁNH GIÁ -->
<div class="modal fade" id="replyReviewModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content rounded-4 border-0 shadow">
      <form method="POST">
        <input type="hidden" name="action" value="reply">
        <input type="hidden" name="review_id" id="reply_review_id">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Phản Hồi Đánh Giá Khách Hàng</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3 p-3 bg-light rounded-3 small">
            <span class="text-muted d-block mb-1">Nhận xét của khách:</span>
            <strong id="modal_customer_comment" class="text-dark"></strong>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Nội dung câu trả lời của BeautyShop <span class="text-danger">*</span></label>
            <textarea name="reply_text" id="modal_reply_text" class="form-control rounded-3" rows="4" 
                      placeholder="Cảm ơn bạn đã tin tưởng và đánh giá sản phẩm của BeautyShop..." required></textarea>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4" style="background:#d47a88; border-color:#d47a88;">Gửi Phản Hồi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openReplyModal(revId, comment, currentReply) {
  document.getElementById('reply_review_id').value = revId;
  document.getElementById('modal_customer_comment').innerText = comment;
  document.getElementById('modal_reply_text').value = currentReply;
  new bootstrap.Modal(document.getElementById('replyReviewModal')).show();
}
</script>

<?php include 'admin_footer.php'; ?>