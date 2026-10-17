<?php
session_start();
require_once __DIR__ . '/config.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$page_title = 'Danh Sách Yêu Thích - BeautyShop';

// Lấy danh sách sản phẩm yêu thích của người dùng
$stmt = $pdo->prepare("
    SELECT p.*, b.brand_name, w.created_at as saved_at
    FROM wishlists w
    JOIN products p ON w.product_id = p.product_id
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    WHERE w.user_id = ?
    ORDER BY w.id DESC
");
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<div style="height: 125px;"></div>

<div class="py-5" style="background: #fffdfb; min-height: 70vh;">
  <div class="container-fluid px-lg-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-bold mb-1" style="color: #4a2835;">
          <i class="bi bi-heart-fill text-danger me-2"></i> Danh Sách Yêu Thích Của Bạn
        </h2>
        <p class="text-muted small">Lưu lại những món mỹ phẩm yêu thích để mua sắm bất cứ lúc nào</p>
      </div>
      <a href="products.php" class="btn btn-outline-danger btn-sm rounded-pill px-4">
        Tiếp tục mua sắm
      </a>
    </div>

    <?php if (empty($wishlist_items)): ?>
      <div class="text-center py-5 bg-white rounded-4 border p-5 shadow-xs">
        <i class="bi bi-heartbreak text-muted" style="font-size: 4rem;"></i>
        <h4 class="fw-bold mt-3" style="color: #4a2835;">Chưa có sản phẩm yêu thích nào</h4>
        <p class="text-muted small mb-4">Hãy duyệt qua các dòng mỹ phẩm và nhấn vào biểu tượng trái tim để lưu lại bạn nhé!</p>
        <a href="products.php" class="btn btn-danger rounded-pill px-4 py-2 text-white" style="background: #d47a88; border:none;">
          Khám phá sản phẩm ngay
        </a>
      </div>
    <?php else: ?>
      <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4" id="wishlistGrid">
        <?php foreach ($wishlist_items as $p): 
          $discount_pct = ($p['original_price'] > $p['price']) ? round((($p['original_price'] - $p['price']) / $p['original_price']) * 100) : 0;
        ?>
          <div class="col" id="wlItem-<?= $p['product_id'] ?>">
            <div class="beauty-card h-100 d-flex flex-column p-3">
              <button onclick="removeWishlistItem(<?= $p['product_id'] ?>)" class="position-absolute top-0 end-0 m-2 btn btn-sm btn-light rounded-circle shadow-xs text-danger" title="Xóa khỏi danh sách">
                <i class="bi bi-x-lg"></i>
              </button>

              <a href="product_detail.php?id=<?= $p['product_id'] ?>" class="text-decoration-none flex-grow-1">
                <div class="img-box rounded-3 mb-3">
                  <img src="../images/products/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['product_name']) ?>">
                </div>
                <div class="text-muted small mb-1"><?= htmlspecialchars($p['brand_name'] ?? 'Chính hãng') ?></div>
                <h6 class="text-dark fw-bold line-clamp-2 mb-2"><?= htmlspecialchars($p['product_name']) ?></h6>
                <div class="d-flex align-items-baseline gap-2 mb-3">
                  <span class="fw-bold text-danger fs-5"><?= format_price($p['price']) ?></span>
                  <?php if ($p['original_price'] > $p['price']): ?>
                    <span class="text-muted text-decoration-line-through small"><?= format_price($p['original_price']) ?></span>
                  <?php endif; ?>
                </div>
              </a>

              <div class="d-flex gap-2 mt-auto">
                <button onclick="addToCart(<?= $p['product_id'] ?>, 1)" class="btn btn-danger btn-sm rounded-pill w-100 py-2 text-white" style="background: #d47a88; border:none;">
                  <i class="bi bi-cart-plus me-1"></i> Thêm vào giỏ
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<script>
function removeWishlistItem(productId) {
  fetch('api_wishlist.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ product_id: productId })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success && data.action === 'removed') {
      const el = document.getElementById('wlItem-' + productId);
      if (el) el.remove();
      showNotification('Đã xóa sản phẩm khỏi danh sách yêu thích', 'info');
      if (document.querySelectorAll('#wishlistGrid .col').length === 0) {
        location.reload();
      }
    }
  });
}
</script>

<?php include 'footer.php'; ?>

