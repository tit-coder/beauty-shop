<?php
$page_title = 'Admin Dashboard - Thống Kê & Báo Cáo Doanh Thu';
$admin_section_title = 'Tổng Quan Hệ Thống & Thống Kê Doanh Thu';

require_once __DIR__ . '/admin_header.php';

// 1. Thống kê tổng số lượng từ CSDL
$total_products = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
$total_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_users = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();

// Doanh thu thực tế: Tính từ các đơn hàng thành công hoặc đang thực hiện (loại bỏ đơn đã hủy)
$total_revenue = (float)$pdo->query("
    SELECT COALESCE(SUM(total_amount), 0) 
    FROM orders 
    WHERE status IN ('completed', 'confirmed', 'shipping')
")->fetchColumn();

// Đơn hàng đang chờ xử lý
$pending_orders_count = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

// Sản phẩm sắp hết hàng (tồn kho dưới 15)
$low_stock_products = $pdo->query("
    SELECT product_id, product_name, price, quantity, image 
    FROM products 
    WHERE quantity <= 15 AND is_active = 1 
    ORDER BY quantity ASC 
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// 2. Thống kê đơn hàng theo trạng thái
$status_counts = $pdo->query("
    SELECT status, COUNT(*) as cnt 
    FROM orders 
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

// 3. Top 5 sản phẩm bán chạy nhất
$top_sellers = $pdo->query("
    SELECT product_id, product_name, price, sold_count, image, quantity 
    FROM products 
    ORDER BY sold_count DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// 4. Đơn hàng gần đây
$recent_orders = $pdo->query("
    SELECT * FROM orders 
    ORDER BY order_id DESC 
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// 5. Thống kê doanh thu 6 tháng gần nhất cho biểu đồ Chart.js
$monthly_revenue = [];
for ($i = 5; $i >= 0; $i--) {
    $month_str = date('Y-m', strtotime("-$i months"));
    $display_label = 'Tháng ' . date('m/Y', strtotime("-$i months"));
    
    $stmt_m = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as rev 
        FROM orders 
        WHERE status IN ('completed', 'confirmed', 'shipping') 
          AND DATE_FORMAT(created_at, '%Y-%m') = ?
    ");
    $stmt_m->execute([$month_str]);
    $rev = (float)$stmt_m->fetchColumn();
    $monthly_revenue[] = [
        'label' => $display_label,
        'revenue' => $rev
    ];
}

$chart_labels = json_encode(array_column($monthly_revenue, 'label'), JSON_UNESCAPED_UNICODE);
$chart_data = json_encode(array_column($monthly_revenue, 'revenue'));

$order_statuses = ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'];
$order_status_data = json_encode(array_map(fn($st) => $status_counts[$st] ?? 0, $order_statuses));
?>

<!-- 4 THẺ THỐNG KÊ TỔNG QUAN -->
<div class="row g-3 mb-4">
  
  <!-- Doanh thu -->
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card-modern">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted small fw-bold text-uppercase">Doanh Thu Thực Tế</span>
        <div class="rounded-circle d-flex align-items-center justify-content-center text-white" 
             style="width: 44px; height: 44px; background: linear-gradient(135deg, #28a745, #20c997);">
          <i class="bi bi-currency-dollar fs-5"></i>
        </div>
      </div>
      <h3 class="fw-bold mb-1 text-dark"><?= format_price($total_revenue) ?></h3>
      <small class="text-success"><i class="bi bi-graph-up-arrow me-1"></i> Không tính đơn đã hủy</small>
    </div>
  </div>

  <!-- Tổng đơn hàng -->
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card-modern">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted small fw-bold text-uppercase">Tổng Đơn Hàng</span>
        <div class="rounded-circle d-flex align-items-center justify-content-center text-white" 
             style="width: 44px; height: 44px; background: linear-gradient(135deg, #17a2b8, #007bff);">
          <i class="bi bi-cart-check-fill fs-5"></i>
        </div>
      </div>
      <h3 class="fw-bold mb-1 text-dark"><?= number_format($total_orders) ?></h3>
      <small class="text-warning fw-semibold">
        <i class="bi bi-clock-history me-1"></i> Có <?= $pending_orders_count ?> đơn chờ xác nhận
      </small>
    </div>
  </div>

  <!-- Tổng sản phẩm -->
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card-modern">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted small fw-bold text-uppercase">Sản Phẩm Đang Bán</span>
        <div class="rounded-circle d-flex align-items-center justify-content-center text-white" 
             style="width: 44px; height: 44px; background: linear-gradient(135deg, #d47a88, #9e4760);">
          <i class="bi bi-box-seam-fill fs-5"></i>
        </div>
      </div>
      <h3 class="fw-bold mb-1 text-dark"><?= number_format($total_products) ?></h3>
      <small class="text-muted"><i class="bi bi-check-circle me-1"></i> 7 Danh mục & 12 Thương hiệu</small>
    </div>
  </div>

  <!-- Tổng khách hàng -->
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card-modern">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted small fw-bold text-uppercase">Khách Hàng</span>
        <div class="rounded-circle d-flex align-items-center justify-content-center text-white" 
             style="width: 44px; height: 44px; background: linear-gradient(135deg, #fd7e14, #ffc107);">
          <i class="bi bi-people-fill fs-5"></i>
        </div>
      </div>
      <h3 class="fw-bold mb-1 text-dark"><?= number_format($total_users) ?></h3>
      <small class="text-muted"><i class="bi bi-person-check me-1"></i> Đã đăng ký tài khoản</small>
    </div>
  </div>

</div>

<!-- 2 KHỐI BIỂU ĐỒ: DOANH THU & TRẠNG THÁI ĐƠN HÀNG -->
<div class="row g-4 mb-4">
  
  <!-- Biểu đồ cột Doanh thu theo tháng -->
  <div class="col-lg-8">
    <div class="card border-0 rounded-4 shadow-sm bg-white p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h5 class="fw-bold text-dark mb-0">Biểu Đồ Doanh Thu 6 Tháng Gần Nhất</h5>
          <small class="text-muted">Tính từ các đơn hàng đã thanh toán và giao dịch thành công</small>
        </div>
        <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-2 fw-semibold">
          Dữ liệu trực tiếp từ MySQL
        </span>
      </div>
      <div style="height: 300px;">
        <canvas id="revenueChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Biểu đồ tròn Trạng thái đơn hàng -->
  <div class="col-lg-4">
    <div class="card border-0 rounded-4 shadow-sm bg-white p-4 h-100">
      <div class="mb-4">
        <h5 class="fw-bold text-dark mb-0">Cơ Cấu Trạng Thái Đơn</h5>
        <small class="text-muted">Phân bổ tỷ lệ đơn hàng hiện tại</small>
      </div>
      <div style="height: 250px;" class="d-flex align-items-center justify-content-center">
        <canvas id="orderStatusChart"></canvas>
      </div>
    </div>
  </div>

</div>

<!-- 2 BẢNG THÔNG TIN: ĐƠN HÀNG MỚI & SẢN PHẨM SẮP HẾT HÀNG -->
<div class="row g-4 mb-4">
  
  <!-- Đơn hàng gần đây -->
  <div class="col-lg-8">
    <div class="card border-0 rounded-4 shadow-sm bg-white p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-dark mb-0">Đơn Hàng Gần Đây Cần Xử Lý</h5>
        <a href="manage_orders.php" class="btn btn-sm btn-outline-danger rounded-pill px-3">
          Xem tất cả <i class="bi bi-chevron-right"></i>
        </a>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light small">
            <tr>
              <th>MÃ ĐƠN</th>
              <th>KHÁCH HÀNG</th>
              <th>THANH TOÁN</th>
              <th>TỔNG TIỀN</th>
              <th>TRẠNG THÁI</th>
              <th class="text-end">THAO TÁC</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php foreach ($recent_orders as $ord): ?>
              <tr>
                <td class="fw-bold text-dark">#<?= htmlspecialchars($ord['order_code']) ?></td>
                <td>
                  <div><?= htmlspecialchars($ord['customer_name']) ?></div>
                  <small class="text-muted"><?= htmlspecialchars($ord['customer_phone']) ?></small>
                </td>
                <td>
                  <span class="badge bg-light text-dark border">
                    <?= $ord['payment_method'] === 'cod' ? 'COD' : 'VietQR' ?>
                  </span>
                </td>
                <td class="fw-bold text-danger"><?= format_price($ord['total_amount']) ?></td>
                <td>
                  <?php
                    $badge = match($ord['status']) {
                      'pending'   => '<span class="badge bg-warning text-dark">Chờ duyệt</span>',
                      'confirmed' => '<span class="badge bg-info text-white">Đã duyệt</span>',
                      'shipping'  => '<span class="badge bg-primary">Đang giao</span>',
                      'completed' => '<span class="badge bg-success">Hoàn thành</span>',
                      'cancelled' => '<span class="badge bg-danger">Đã hủy</span>',
                      default     => '<span class="badge bg-secondary">' . $ord['status'] . '</span>'
                    };
                    echo $badge;
                  ?>
                </td>
                <td class="text-end">
                  <a href="manage_orders.php?order_id=<?= $ord['order_id'] ?>" class="btn btn-xs btn-outline-secondary py-1 px-2">
                    Xử lý
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Sản phẩm bán chạy & Cảnh báo tồn kho -->
  <div class="col-lg-4">
    
    <!-- Top Bán Chạy -->
    <div class="card border-0 rounded-4 shadow-sm bg-white p-4 mb-4">
      <h6 class="fw-bold text-dark mb-3">Top Sản Phẩm Bán Chạy</h6>
      <div class="d-flex flex-column gap-3">
        <?php foreach ($top_sellers as $ts): ?>
          <div class="d-flex align-items-center gap-3">
            <img src="../images/products/<?= htmlspecialchars($ts['image']) ?>" alt="" 
                 class="rounded-3 border object-fit-cover shadow-xs" style="width: 44px; height: 44px;">
            <div class="flex-grow-1 overflow-hidden">
              <div class="fw-bold text-truncate small" title="<?= htmlspecialchars($ts['product_name']) ?>">
                <?= htmlspecialchars($ts['product_name']) ?>
              </div>
              <small class="text-danger fw-semibold"><?= format_price($ts['price']) ?></small>
            </div>
            <span class="badge bg-light text-dark border small">Đã bán <?= $ts['sold_count'] ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Cảnh báo tồn kho thấp -->
    <?php if (!empty($low_stock_products)): ?>
      <div class="card border-0 rounded-4 shadow-sm bg-white p-4 border-warning border-start border-4">
        <h6 class="fw-bold text-danger mb-2">
          <i class="bi bi-exclamation-triangle-fill me-1"></i> Cảnh Báo Tồn Kho Thấp
        </h6>
        <small class="text-muted d-block mb-3">Các sản phẩm còn dưới 15 sản phẩm:</small>
        <div class="d-flex flex-column gap-2">
          <?php foreach ($low_stock_products as $lsp): ?>
            <div class="d-flex justify-content-between align-items-center small py-1 border-bottom">
              <span class="text-truncate" style="max-width: 180px;"><?= htmlspecialchars($lsp['product_name']) ?></span>
              <span class="badge bg-danger rounded-pill">Còn <?= $lsp['quantity'] ?> cái</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

  </div>

</div>

<!-- SCRIPT VẼ BIỂU ĐỒ CHART.JS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // 1. Biểu đồ doanh thu
  const revCtx = document.getElementById('revenueChart').getContext('2d');
  new Chart(revCtx, {
    type: 'bar',
    data: {
      labels: <?= $chart_labels ?>,
      datasets: [{
        label: 'Doanh thu (VNĐ)',
        data: <?= $chart_data ?>,
        backgroundColor: 'rgba(212, 122, 136, 0.75)',
        borderColor: '#d47a88',
        borderWidth: 2,
        borderRadius: 8,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(val) {
              return (val / 1000000).toFixed(1) + ' tr';
            }
          }
        }
      }
    }
  });

  // 2. Biểu đồ tròn trạng thái đơn
  const statusCtx = document.getElementById('orderStatusChart').getContext('2d');
  new Chart(statusCtx, {
    type: 'doughnut',
    data: {
      labels: ['Chờ duyệt', 'Đã duyệt', 'Đang giao', 'Hoàn thành', 'Đã hủy'],
      datasets: [{
        data: <?= $order_status_data ?>,
        backgroundColor: ['#ffc107', '#17a2b8', '#007bff', '#28a745', '#dc3545'],
        borderWidth: 2,
        borderColor: '#ffffff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom' }
      }
    }
  });
});
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
