<?php
session_start();
require_once __DIR__ . '/config.php';

$page_title = 'Routine Builder - Thiết Kế Quy Trình Dưỡng Da Cá Nhân Hóa';

$skin_types = $pdo->query("SELECT * FROM skin_types ORDER BY id ASC")->fetchAll();

$selected_skin_id = isset($_GET['skin_type']) ? (int)$_GET['skin_type'] : 1;
$selected_goal = isset($_GET['goal']) ? trim($_GET['goal']) : 'oil_acne';

// Hàm lấy sản phẩm phù hợp cho từng bước trong quy trình
function getProductForStep($pdo, $category_id, $skin_id) {
    $stmt = $pdo->prepare("
        SELECT p.*, b.brand_name
        FROM products p
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        LEFT JOIN product_skin_types pst ON p.product_id = pst.product_id
        WHERE p.category_id = ? AND p.is_active = 1 
          AND (pst.skin_type_id = ? OR p.skin_type_summary LIKE '%Mọi loại da%')
        ORDER BY p.rating_avg DESC, p.sold_count DESC
        LIMIT 1
    ");
    $stmt->execute([$category_id, $skin_id]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fallback nếu không có sản phẩm theo loại da cụ thể
    if (!$prod) {
        $fb = $pdo->prepare("
            SELECT p.*, b.brand_name
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.brand_id
            WHERE p.category_id = ? AND p.is_active = 1
            ORDER BY p.sold_count DESC LIMIT 1
        ");
        $fb->execute([$category_id]);
        $prod = $fb->fetch(PDO::FETCH_ASSOC);
    }
    return $prod;
}

// 1. Quy trình buổi sáng (Làm sạch -> Cân bằng -> Dưỡng chất -> Khóa ẩm -> Chống nắng)
$morning_steps = [
    ['step' => 'Bước 1', 'name' => 'Làm sạch buổi sáng', 'cat_id' => 2, 'icon' => 'bi-droplet-half', 'note' => 'Rửa trôi bã nhờn tích tụ sau một đêm'],
    ['step' => 'Bước 2', 'name' => 'Cân bằng & Cấp ẩm', 'cat_id' => 1, 'icon' => 'bi-water', 'note' => 'Cân bằng độ pH và mở đường cho tinh chất'],
    ['step' => 'Bước 3', 'name' => 'Serum bảo vệ & Dưỡng sáng', 'cat_id' => 3, 'icon' => 'bi-stars', 'note' => 'Bổ sung chất chống oxy hóa'],
    ['step' => 'Bước 4', 'name' => 'Kem dưỡng dịu nhẹ', 'cat_id' => 4, 'icon' => 'bi-cloud-sun', 'note' => 'Khóa ẩm mỏng nhẹ không gây bóng nhờn'],
    ['step' => 'Bước 5', 'name' => 'Kem chống nắng SPF50+', 'cat_id' => 5, 'icon' => 'bi-shield-shaded', 'note' => 'Bắt buộc để bảo vệ da trước tia UV']
];

foreach ($morning_steps as &$s) {
    $s['product'] = getProductForStep($pdo, $s['cat_id'], $selected_skin_id);
}

// 2. Quy trình buổi tối (Tẩy trang -> Sữa rửa mặt -> Cân bằng -> Tinh chất phục hồi -> Kem dưỡng)
$evening_steps = [
    ['step' => 'Bước 1', 'name' => 'Tẩy trang làm sạch sâu', 'cat_id' => 2, 'icon' => 'bi-flower2', 'note' => 'Hút sạch kem chống nắng và bụi mịn'],
    ['step' => 'Bước 2', 'name' => 'Sữa rửa mặt tạo bọt', 'cat_id' => 2, 'icon' => 'bi-droplet', 'note' => 'Làm sạch kép (Double Cleansing)'],
    ['step' => 'Bước 3', 'name' => 'Toner cân bằng màng ẩm', 'cat_id' => 1, 'icon' => 'bi-water', 'note' => 'Làm dịu làn da sau ngày dài căng thẳng'],
    ['step' => 'Bước 4', 'name' => 'Serum phục hồi chuyên sâu', 'cat_id' => 3, 'icon' => 'bi-moon-stars', 'note' => 'Thời điểm vàng để tế bào da tái tạo'],
    ['step' => 'Bước 5', 'name' => 'Kem dưỡng khóa ẩm ban đêm', 'cat_id' => 4, 'icon' => 'bi-cloud-moon', 'note' => 'Củng cố màng lipid, ngăn thoát nước qua đêm']
];

foreach ($evening_steps as &$s) {
    $s['product'] = getProductForStep($pdo, $s['cat_id'], $selected_skin_id);
}

include 'header.php';
?>

<div style="height: 125px;"></div>

<div class="py-5" style="background: linear-gradient(180deg, #fffaf6 0%, #fff 100%); min-height: 85vh;">
  <div class="container-fluid px-lg-5">
    
    <!-- Hero Header -->
    <div class="text-center mb-5">
      <span class="badge rounded-pill px-3 py-2 text-white mb-2" style="background: linear-gradient(135deg, #d47a88, #9e4760);">
        <i class="bi bi-magic me-1"></i> SMART SKINCARE ROUTINE
      </span>
      <h1 class="display-6 fw-bold mb-2" style="color: #4a2835;">Thiết Kế Quy Trình Chăm Sóc Da Cá Nhân Hóa</h1>
      <p class="text-muted" style="max-width: 650px; margin: 0 auto;">
        Chọn loại da của bạn để hệ thống tự động phối hợp các sản phẩm chính hãng đang có sẵn tại BeautyShop thành lộ trình chuẩn chuyên gia da liễu.
      </p>
    </div>

    <!-- Thanh chọn loại da -->
    <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-5">
      <div class="row align-items-center g-3">
        <div class="col-md-3">
          <label class="form-label fw-bold text-dark small text-uppercase mb-0">Loại da của bạn:</label>
        </div>
        <div class="col-md-9">
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($skin_types as $st): ?>
              <a href="routine_builder.php?skin_type=<?= $st['id'] ?>" 
                 class="btn btn-sm rounded-pill px-4 py-2 <?= $selected_skin_id == $st['id'] ? 'btn-danger text-white' : 'btn-outline-secondary bg-light' ?>"
                 style="<?= $selected_skin_id == $st['id'] ? 'background:#d47a88; border-color:#d47a88;' : '' ?>">
                <i class="bi <?= $st['icon'] ?: 'bi-stars' ?> me-1"></i> <?= htmlspecialchars($st['name']) ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Hai Cột Lộ Trình: Sáng và Tối -->
    <div class="row g-4 mb-5">
      
      <!-- Cột Buổi Sáng -->
      <div class="col-lg-6">
        <div class="card border-0 rounded-4 shadow-sm bg-white p-4 h-100">
          <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-warning" style="width: 44px; height: 44px; background: #fff8e7;">
              <i class="bi bi-sun-fill fs-4"></i>
            </div>
            <div>
              <h5 class="fw-bold mb-0 text-dark">Quy Trình Buổi Sáng (Morning Routine)</h5>
              <small class="text-muted">Mục tiêu: Làm sạch dịu nhẹ + Dưỡng ẩm nhẹ + Bảo vệ chống nắng</small>
            </div>
          </div>

          <div class="d-flex flex-column gap-3">
            <?php foreach ($morning_steps as $step): $p = $step['product']; ?>
              <div class="p-3 rounded-4 border bg-light shadow-xs d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                  <span class="badge rounded-pill bg-white text-dark border px-3 py-2 fw-bold text-nowrap">
                    <?= $step['step'] ?>
                  </span>
                  <?php if ($p): ?>
                    <img src="../images/products/<?= htmlspecialchars($p['image']) ?>" alt="" class="rounded-3 border object-fit-cover shadow-xs" style="width: 55px; height: 55px;">
                    <div>
                      <small class="text-danger fw-bold d-block"><?= $step['name'] ?></small>
                      <a href="product_detail.php?id=<?= $p['product_id'] ?>" class="text-decoration-none fw-semibold text-dark line-clamp-1 small">
                        <?= htmlspecialchars($p['product_name']) ?>
                      </a>
                      <small class="text-muted d-block"><?= format_price($p['price']) ?> • <?= htmlspecialchars($p['brand_name'] ?? 'Chính hãng') ?></small>
                    </div>
                  <?php endif; ?>
                </div>

                <?php if ($p): ?>
                  <button onclick="addToCart(<?= $p['product_id'] ?>, 1)" class="btn btn-sm btn-outline-danger rounded-pill px-3 text-nowrap flex-shrink-0" style="font-size: 0.75rem;">
                    <i class="bi bi-cart-plus"></i> Chọn
                  </button>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Cột Buổi Tối -->
      <div class="col-lg-6">
        <div class="card border-0 rounded-4 shadow-sm bg-white p-4 h-100">
          <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-primary" style="width: 44px; height: 44px; background: #eef4ff;">
              <i class="bi bi-moon-stars-fill fs-4"></i>
            </div>
            <div>
              <h5 class="fw-bold mb-0 text-dark">Quy Trình Buổi Tối (Night Routine)</h5>
              <small class="text-muted">Mục tiêu: Double Cleansing sạch sâu + Điều trị & Khóa ẩm phục hồi</small>
            </div>
          </div>

          <div class="d-flex flex-column gap-3">
            <?php foreach ($evening_steps as $step): $p = $step['product']; ?>
              <div class="p-3 rounded-4 border bg-light shadow-xs d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                  <span class="badge rounded-pill bg-white text-dark border px-3 py-2 fw-bold text-nowrap">
                    <?= $step['step'] ?>
                  </span>
                  <?php if ($p): ?>
                    <img src="../images/products/<?= htmlspecialchars($p['image']) ?>" alt="" class="rounded-3 border object-fit-cover shadow-xs" style="width: 55px; height: 55px;">
                    <div>
                      <small class="text-primary fw-bold d-block"><?= $step['name'] ?></small>
                      <a href="product_detail.php?id=<?= $p['product_id'] ?>" class="text-decoration-none fw-semibold text-dark line-clamp-1 small">
                        <?= htmlspecialchars($p['product_name']) ?>
                      </a>
                      <small class="text-muted d-block"><?= format_price($p['price']) ?> • <?= htmlspecialchars($p['brand_name'] ?? 'Chính hãng') ?></small>
                    </div>
                  <?php endif; ?>
                </div>

                <?php if ($p): ?>
                  <button onclick="addToCart(<?= $p['product_id'] ?>, 1)" class="btn btn-sm btn-outline-danger rounded-pill px-3 text-nowrap flex-shrink-0" style="font-size: 0.75rem;">
                    <i class="bi bi-cart-plus"></i> Chọn
                  </button>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div>

    <!-- Banner Kết Nối AI -->
    <div class="p-4 rounded-4 text-white text-center shadow-sm" style="background: linear-gradient(135deg, #d47a88, #9e4760);">
      <h4 class="fw-bold mb-2">Bạn Có Tình Trạng Da Đặc Biệt Hoặc Đang Dùng Treatment?</h4>
      <p class="small text-white-50 mb-3" style="max-width: 600px; margin: 0 auto;">
        Trò chuyện trực tiếp với AI Beauty Advisor để được tinh chỉnh routine chi tiết theo từng hoạt chất và nồng độ.
      </p>
      <a href="ai_advisor.php" class="btn btn-light rounded-pill px-4 py-2 fw-bold text-danger">
        <i class="bi bi-stars me-1"></i> Trò chuyện với AI Advisor
      </a>
    </div>

  </div>
</div>

<?php include 'footer.php'; ?>

