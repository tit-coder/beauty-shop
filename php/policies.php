<?php
session_start();
require_once __DIR__ . '/config.php';

$policy = $_GET['p'] ?? 'guarantee';

$policies = [
    'guarantee' => [
        'title' => 'Cam Kết Mỹ Phẩm Chính Hãng 100%',
        'icon'  => 'bi-patch-check-fill',
        'content' => '
            <p>Tại <strong>BeautyShop</strong>, chúng tôi hiểu rằng mỹ phẩm ảnh hưởng trực tiếp đến sức khỏe và vẻ đẹp làn da của bạn. Do đó, chúng tôi cam kết:</p>
            <ul>
                <li><strong>100% Sản phẩm chính hãng:</strong> Tất cả sản phẩm đều được nhập khẩu chính ngạch từ các tập đoàn và nhà phân phối độc quyền tại Việt Nam (L\'Oréal, Bioderma, CeraVe, Cocoon, Eucerin, Klairs...).</li>
                <li><strong>Đầy đủ hóa đơn & tem phụ tiếng Việt:</strong> Hàng hóa luôn có tem phụ rõ ràng về nguồn gốc xuất xứ, hạn sử dụng và thành phần.</li>
                <li><strong>Bồi thường gấp đôi:</strong> Nếu phát hiện bất kỳ sản phẩm nào là hàng giả, hàng nhái từ BeautyShop, chúng tôi cam kết hoàn tiền 200% giá trị đơn hàng.</li>
            </ul>
        '
    ],
    'shipping' => [
        'title' => 'Chính Sách Giao Hàng & Vận Chuyển',
        'icon'  => 'bi-truck',
        'content' => '
            <p>BeautyShop hợp tác với các đơn vị vận chuyển uy tín hàng đầu (Viettel Post, Giao Hàng Nhanh, J&T Express) nhằm mang sản phẩm đến tay bạn nhanh nhất:</p>
            <ul>
                <li><strong>Miễn phí vận chuyển toàn quốc:</strong> Áp dụng cho mọi đơn hàng có giá trị từ <strong>150.000₫</strong> trở lên (hoặc khi áp dụng mã <code>FREESHIP</code>).</li>
                <li><strong>Phí ship tiêu chuẩn:</strong> Đồng giá <strong>30.000₫</strong> cho các đơn hàng dưới 150.000₫ trên toàn quốc.</li>
                <li><strong>Thời gian giao hàng dự kiến:</strong>
                    <ul>
                        <li>Nội thành Đà Nẵng, Hà Nội, TP.HCM: 1 - 2 ngày làm việc.</li>
                        <li>Các tỉnh thành khác: 2 - 4 ngày làm việc.</li>
                    </ul>
                </li>
                <li><strong>Đóng gói an toàn:</strong> Toàn bộ sản phẩm được bọc bóng khí chống va đập và đóng hộp carton kiên cố.</li>
            </ul>
        '
    ],
    'return' => [
        'title' => 'Chính Sách Đổi Trả Trong 7 Ngày',
        'icon'  => 'bi-arrow-repeat',
        'content' => '
            <p>Để đảm bảo sự an tâm tuyệt đối khi mua sắm online, BeautyShop hỗ trợ chính sách đổi trả linh hoạt:</p>
            <ul>
                <li><strong>Thời hạn đổi trả:</strong> Trong vòng <strong>07 ngày</strong> kể từ khi khách hàng nhận được kiện hàng.</li>
                <li><strong>Điều kiện áp dụng:</strong>
                    <ul>
                        <li>Sản phẩm giao sai mẫu, sai dung tích hoặc thiếu sản phẩm so với đơn đặt hàng.</li>
                        <li>Sản phẩm bị nứt, vỡ, đổ móp trong quá trình vận chuyển.</li>
                        <li>Sản phẩm có dấu hiệu hư hỏng chất lượng từ nhà sản xuất.</li>
                    </ul>
                </li>
                <li><strong>Quy trình xử lý:</strong> Khách hàng chỉ cần chụp ảnh tình trạng sản phẩm và liên hệ hotline <strong>1900 1234</strong> hoặc email <strong>support@beautyshop.vn</strong> để được hỗ trợ gửi sản phẩm mới hoàn toàn miễn phí.</li>
            </ul>
        '
    ],
    'privacy' => [
        'title' => 'Chính Sách Bảo Mật Thông Tin Khách Hàng',
        'icon'  => 'bi-shield-lock-fill',
        'content' => '
            <p>BeautyShop coi trọng việc bảo vệ thông tin cá nhân của khách hàng:</p>
            <ul>
                <li><strong>Thu thập thông tin:</strong> Chúng tôi chỉ thu thập họ tên, số điện thoại, email và địa chỉ nhằm mục đích xử lý đơn hàng và giao sản phẩm.</li>
                <li><strong>Bảo mật mật khẩu:</strong> Mật khẩu tài khoản được mã hóa một chiều bằng thuật toán Bcrypt tiêu chuẩn cao, không ai (kể cả quản trị viên) có thể xem được mật khẩu gốc.</li>
                <li><strong>Không chia sẻ dữ liệu:</strong> Chúng tôi cam kết không bán, không trao đổi hay tiết lộ dữ liệu của khách hàng cho bất kỳ bên thứ ba nào vì mục đích thương mại.</li>
            </ul>
        '
    ]
];

$curr_policy = $policies[$policy] ?? $policies['guarantee'];
$page_title = $curr_policy['title'] . ' - BeautyShop';

include 'header.php';
?>

<div style="height: 125px;"></div>

<div class="py-5" style="background: #fffdfb; min-height: 80vh;">
  <div class="container" style="max-width: 900px;">
    
    <div class="card border-0 rounded-4 shadow-sm bg-white p-4 p-md-5">
      <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
        <div class="rounded-circle d-flex align-items-center justify-content-center text-white"
             style="width: 50px; height: 50px; background: linear-gradient(135deg, #d47a88, #9e4760);">
          <i class="bi <?= $curr_policy['icon'] ?> fs-4"></i>
        </div>
        <h3 class="fw-bold mb-0" style="color: #4a2835;"><?= htmlspecialchars($curr_policy['title']) ?></h3>
      </div>

      <div class="policy-content" style="line-height: 1.8; font-size: 1rem; color: #444;">
        <?= $curr_policy['content'] ?>
      </div>

      <div class="mt-5 pt-4 border-top d-flex justify-content-between align-items-center">
        <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4">
          <i class="bi bi-arrow-left me-1"></i> Về trang chủ
        </a>
        <a href="ai_advisor.php" class="btn btn-danger rounded-pill px-4 text-white" style="background:#d47a88; border:none;">
          <i class="bi bi-stars me-1"></i> Tư vấn mỹ phẩm cùng AI
        </a>
      </div>
    </div>

  </div>
</div>

<?php include 'footer.php'; ?>

