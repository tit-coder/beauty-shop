<?php
session_start();
require_once __DIR__ . '/config.php';

$page_title = 'AI Beauty Advisor - Tư vấn Mỹ phẩm & Chăm sóc da thông minh';
$is_logged_in = is_logged_in();
$username = $_SESSION['username'] ?? '';
$role = $_SESSION['role'] ?? '';

// Lấy danh sách loại da để hiển thị bộ lọc nhanh
$skin_types = $pdo->query("SELECT * FROM skin_types ORDER BY id ASC")->fetchAll();

include 'header.php';
?>

<!-- Khoảng cách bù header cố định -->
<div style="height: 125px;"></div>

<div class="ai-advisor-page py-4" style="background: linear-gradient(180deg, #fffaf6 0%, #fff 100%); min-height: 85vh;">
  <div class="container-fluid px-lg-5">
    
    <!-- Banner Giới Thiệu Tính Năng AI -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="ai-banner p-4 p-md-5 rounded-4 position-relative overflow-hidden shadow-sm" 
             style="background: linear-gradient(135deg, #fcedf2 0%, #fde8ef 50%, #fef5f8 100%); border: 1px solid rgba(212, 122, 136, 0.2);">
          <div class="row align-items-center">
            <div class="col-lg-8">
              <span class="badge rounded-pill px-3 py-2 mb-2 text-white" style="background: linear-gradient(135deg, #d47a88, #9e4760); font-weight: 600; font-size: 0.85rem;">
                <i class="bi bi-stars me-1"></i> TRÍ TUỆ NHÂN TẠO CHĂM SÓC DA
              </span>
              <h1 class="fw-bold mb-2 display-6" style="color: #4a2835;">AI Beauty Advisor – Trợ Lý Mỹ Phẩm Cá Nhân</h1>
              <p class="text-muted mb-3" style="font-size: 1.05rem; max-width: 680px;">
                Phân tích tình trạng da, đề xuất quy trình Skincare khoa học và gợi ý sản phẩm chính hãng thực tế từ kho dữ liệu BeautyShop phù hợp với ngân sách của bạn.
              </p>
              <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-xs">
                  <i class="bi bi-check-circle-fill text-success me-1"></i> Dữ liệu sản phẩm thật 100%
                </span>
                <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-xs">
                  <i class="bi bi-check-circle-fill text-success me-1"></i> Phân tích chuẩn thành phần
                </span>
                <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-xs">
                  <i class="bi bi-shield-check text-primary me-1"></i> Bảo mật & Riêng tư
                </span>
              </div>
            </div>
            <div class="col-lg-4 text-center d-none d-lg-block">
              <div class="ai-avatar-badge position-relative d-inline-block">
                <div class="rounded-circle d-flex align-items-center justify-content-center shadow-lg"
                     style="width: 140px; height: 140px; background: linear-gradient(135deg, #e8a598, #d47a88); border: 5px solid white;">
                  <i class="bi bi-magic text-white" style="font-size: 4rem;"></i>
                </div>
                <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-success px-3 py-2 border border-2 border-white shadow-sm">
                  Online 24/7
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Khung Chat Trực Tuyến -->
    <div class="row g-4">
      
      <!-- Cột Trái: Bộ lọc loại da & Câu hỏi gợi ý nhanh -->
      <div class="col-lg-4 col-xl-3">
        
        <!-- Chọn loại da -->
        <div class="card border-0 rounded-4 shadow-sm p-4 mb-4" style="background: white;">
          <h6 class="fw-bold mb-3 d-flex align-items-center" style="color: #4a2835;">
            <i class="bi bi-person-badge me-2" style="color: #d47a88;"></i> Loại da của bạn
          </h6>
          <div class="d-flex flex-column gap-2" id="skinTypeSelectors">
            <button class="btn btn-outline-secondary text-start rounded-3 py-2 px-3 skin-btn active" data-skin-id="">
              <i class="bi bi-circle-fill me-2" style="font-size: 0.6rem;"></i> Chưa xác định / Mọi loại da
            </button>
            <?php foreach ($skin_types as $st): ?>
              <button class="btn btn-outline-secondary text-start rounded-3 py-2 px-3 skin-btn" data-skin-id="<?= $st['id'] ?>" data-skin-name="<?= htmlspecialchars($st['name']) ?>">
                <i class="bi <?= $st['icon'] ?: 'bi-stars' ?> me-2 text-danger"></i> <?= htmlspecialchars($st['name']) ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Câu hỏi mẫu gợi ý -->
        <div class="card border-0 rounded-4 shadow-sm p-4" style="background: white;">
          <h6 class="fw-bold mb-3 d-flex align-items-center" style="color: #4a2835;">
            <i class="bi bi-chat-quote me-2" style="color: #d47a88;"></i> Câu hỏi mẫu phổ biến
          </h6>
          <div class="d-flex flex-column gap-2">
            <button class="btn btn-light text-start border rounded-3 p-2 small prompt-pill hover-shadow">
              <i class="bi bi-arrow-right-short text-danger"></i> Tư vấn quy trình cho da dầu mụn
            </button>
            <button class="btn btn-light text-start border rounded-3 p-2 small prompt-pill hover-shadow">
              <i class="bi bi-arrow-right-short text-danger"></i> Tìm kem chống nắng mỏng nhẹ nâng tông
            </button>
            <button class="btn btn-light text-start border rounded-3 p-2 small prompt-pill hover-shadow">
              <i class="bi bi-arrow-right-short text-danger"></i> Da nhạy cảm cần phục hồi sau mụn
            </button>
            <button class="btn btn-light text-start border rounded-3 p-2 small prompt-pill hover-shadow">
              <i class="bi bi-arrow-right-short text-danger"></i> Gợi ý serum cấp ẩm sáng da dưới 400k
            </button>
            <button class="btn btn-light text-start border rounded-3 p-2 small prompt-pill hover-shadow">
              <i class="bi bi-arrow-right-short text-danger"></i> Phân tích công dụng của Niacinamide và BHA
            </button>
          </div>
        </div>

      </div>

      <!-- Cột Phải: Khung Hội Thoại Chat -->
      <div class="col-lg-8 col-xl-9">
        <div class="card border-0 rounded-4 shadow-sm d-flex flex-column" style="height: 680px; background: white;">
          
          <!-- Chat Header -->
          <div class="p-3 px-4 border-bottom d-flex justify-content-between align-items-center" 
               style="background: linear-gradient(90deg, #fffafc, #fff);">
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center text-white"
                   style="width: 44px; height: 44px; background: linear-gradient(135deg, #d47a88, #9e4760);">
                <i class="bi bi-robot fs-5"></i>
              </div>
              <div>
                <div class="fw-bold" style="color: #4a2835;">Beauty Advisor AI</div>
                <div class="small text-muted d-flex align-items-center">
                  <span class="spinner-grow spinner-grow-sm text-success me-1" style="width: 8px; height: 8px;" role="status"></span>
                  Sẵn sàng giải đáp mọi thắc mắc làm đẹp
                </div>
              </div>
            </div>
            
            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="btnClearChat" title="Bắt đầu hội thoại mới">
              <i class="bi bi-arrow-clockwise me-1"></i> Làm mới
            </button>
          </div>

          <!-- Chat Body (Messages Feed) -->
          <div class="chat-messages-container flex-grow-1 p-4 overflow-y-auto" id="chatFeed" style="background: #faf7f5;">
            
            <!-- Tin nhắn chào mở đầu -->
            <div class="chat-message assistant-message mb-4">
              <div class="d-flex gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0"
                     style="width: 38px; height: 38px; background: linear-gradient(135deg, #d47a88, #9e4760);">
                  <i class="bi bi-robot"></i>
                </div>
                <div class="message-bubble p-3 rounded-4 shadow-xs" style="background: white; border: 1px solid #f0e6e8; max-width: 85%;">
                  <p class="mb-2">
                    Chào bạn! Mình là <strong>Beauty Advisor</strong> – chuyên viên tư vấn sắc đẹp thông minh của BeautyShop. 🌸
                  </p>
                  <p class="mb-2">
                    Mình có thể giúp bạn:
                  </p>
                  <ul class="mb-2 ps-3 small text-muted">
                    <li>Khám phá quy trình chăm sóc da phù hợp cho từng loại da (dầu, khô, nhạy cảm, hỗn hợp).</li>
                    <li>Giải thích chi tiết công dụng từng thành phần hoạt chất (B5, Niacinamide, BHA, Retinol,...).</li>
                    <li>Gợi ý các sản phẩm chính hãng đang có sẵn tại cửa hàng khớp với ngân sách của bạn.</li>
                  </ul>
                  <p class="mb-0 text-muted small">
                    Hãy chia sẻ tình trạng làn da hiện tại hoặc bấm vào các câu hỏi gợi ý bên trái để bắt đầu nhé!
                  </p>
                </div>
              </div>
            </div>

          </div>

          <!-- Typing Indicator (ẩn mặc định) -->
          <div class="typing-indicator px-4 py-2 text-muted small d-none" id="typingIndicator" style="background: #faf7f5;">
            <div class="d-flex align-items-center gap-2">
              <span class="spinner-border spinner-border-sm text-danger" role="status"></span>
              <span>Beauty Advisor đang suy nghĩ và tra cứu kho sản phẩm...</span>
            </div>
          </div>

          <!-- Chat Footer (Input Form) -->
          <div class="p-3 border-top bg-white rounded-bottom-4">
            <form id="aiChatForm" class="d-flex gap-2 align-items-center">
              <div class="input-group">
                <input type="text" id="userInput" class="form-control rounded-pill px-4 py-2 border" 
                       placeholder="Nhập câu hỏi của bạn (ví dụ: Da dầu mụn nên dùng sữa rửa mặt và serum nào?)..." 
                       autocomplete="off" style="font-size: 0.95rem;">
                <button type="submit" class="btn text-white rounded-pill px-4 ms-2 shadow-sm" id="btnSend"
                        style="background: linear-gradient(135deg, #d47a88, #9e4760); border: none;">
                  <i class="bi bi-send-fill me-1"></i> Gửi
                </button>
              </div>
            </form>
            <div class="mt-2 text-center">
              <small class="text-muted" style="font-size: 0.75rem;">
                <i class="bi bi-info-circle me-1"></i> Tư vấn dựa trên dữ liệu sản phẩm thực tế. Lời khuyên không thay thế chẩn đoán y khoa chuyên sâu.
              </small>
            </div>
          </div>

        </div>
      </div>

    </div>

  </div>
</div>

<style>
/* CSS Tùy biến cho khung chat AI */
.hover-shadow:hover {
  background: #fdf2f4 !important;
  border-color: #d47a88 !important;
  color: #9e4760 !important;
  transform: translateX(4px);
  transition: all 0.2s ease;
}
.skin-btn.active {
  background: linear-gradient(135deg, #d47a88, #9e4760) !important;
  color: white !important;
  border-color: transparent !important;
}
.skin-btn.active i {
  color: white !important;
}
.user-message .message-bubble {
  background: linear-gradient(135deg, #d47a88, #9e4760) !important;
  color: white !important;
  border: none !important;
}
.chat-product-card {
  transition: transform 0.2s, box-shadow 0.2s;
}
.chat-product-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.08) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const chatFeed = document.getElementById('chatFeed');
  const chatForm = document.getElementById('aiChatForm');
  const userInput = document.getElementById('userInput');
  const btnSend = document.getElementById('btnSend');
  const typingIndicator = document.getElementById('typingIndicator');
  const btnClearChat = document.getElementById('btnClearChat');
  
  let currentSkinId = '';
  let sessionToken = 'chat_' + Math.random().toString(36).substring(2, 15) + Date.now();

  // Xử lý chọn loại da
  document.querySelectorAll('.skin-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.skin-btn').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      currentSkinId = this.getAttribute('data-skin-id');
    });
  });

  // Xử lý bấm câu hỏi mẫu
  document.querySelectorAll('.prompt-pill').forEach(pill => {
    pill.addEventListener('click', function() {
      const text = this.innerText.trim();
      userInput.value = text;
      chatForm.dispatchEvent(new Event('submit'));
    });
  });

  // Bắt đầu hội thoại mới
  btnClearChat.addEventListener('click', function() {
    if (confirm('Bạn có chắc muốn làm mới cuộc trò chuyện?')) {
      sessionToken = 'chat_' + Math.random().toString(36).substring(2, 15) + Date.now();
      chatFeed.innerHTML = `
        <div class="chat-message assistant-message mb-4">
          <div class="d-flex gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0"
                 style="width: 38px; height: 38px; background: linear-gradient(135deg, #d47a88, #9e4760);">
              <i class="bi bi-robot"></i>
            </div>
            <div class="message-bubble p-3 rounded-4 shadow-xs" style="background: white; border: 1px solid #f0e6e8; max-width: 85%;">
              <p class="mb-0">Đã làm mới cuộc hội thoại! Hãy nhập câu hỏi hoặc chọn loại da để mình tư vấn cho bạn nhé. 🌸</p>
            </div>
          </div>
        </div>
      `;
    }
  });

  // Gửi tin nhắn
  chatForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const message = userInput.value.trim();
    if (!message) return;

    // 1. Thêm tin nhắn của User vào giao diện
    appendUserMessage(message);
    userInput.value = '';
    userInput.focus();

    // 2. Hiển thị loading
    typingIndicator.classList.remove('d-none');
    btnSend.disabled = true;
    chatFeed.scrollTop = chatFeed.scrollHeight;

    // 3. Gửi request tới API
    fetch('api_ai_advisor.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        message: message,
        skin_type: currentSkinId,
        session_token: sessionToken
      })
    })
    .then(res => res.json())
    .then(data => {
      typingIndicator.classList.add('d-none');
      btnSend.disabled = false;

      if (data.success) {
        appendAssistantMessage(data.reply, data.products, data.source);
      } else {
        appendAssistantMessage('Xin lỗi bạn, đã xảy ra lỗi: ' + (data.message || 'Không thể kết nối máy chủ'));
      }
      chatFeed.scrollTop = chatFeed.scrollHeight;
    })
    .catch(err => {
      typingIndicator.classList.add('d-none');
      btnSend.disabled = false;
      appendAssistantMessage('Lỗi kết nối mạng, vui lòng kiểm tra lại!');
      chatFeed.scrollTop = chatFeed.scrollHeight;
    });
  });

  function appendUserMessage(text) {
    const div = document.createElement('div');
    div.className = 'chat-message user-message mb-4 d-flex justify-content-end';
    div.innerHTML = `
      <div class="message-bubble p-3 rounded-4 shadow-xs" style="max-width: 80%;">
        <div style="white-space: pre-wrap;">${escapeHtml(text)}</div>
      </div>
    `;
    chatFeed.appendChild(div);
    chatFeed.scrollTop = chatFeed.scrollHeight;
  }

  function appendAssistantMessage(replyText, products = [], source = '') {
    const div = document.createElement('div');
    div.className = 'chat-message assistant-message mb-4';

    // Format Markdown cơ bản (Bold, Heading, List)
    let formattedHtml = replyText
      .replace(/### (.*?)\n/g, '<h6 class="fw-bold mt-2 mb-2" style="color:#4a2835;">$1</h6>')
      .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
      .replace(/\*(.*?)\*/g, '<em>$1</em>')
      .replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" class="text-danger fw-bold text-decoration-none">$1</a>')
      .replace(/\n\n/g, '<p class="mb-2"></p>')
      .replace(/\n/g, '<br>');

    // Tạo HTML cho thẻ sản phẩm gợi ý nếu có
    let productsHtml = '';
    if (products && products.length > 0) {
      productsHtml = `
        <div class="mt-3 pt-3 border-top">
          <div class="fw-bold mb-2 small text-muted"><i class="bi bi-bag-check-fill text-danger me-1"></i> SẢN PHẨM KHUYÊN DÙNG CÓ SẴN TRONG KHO:</div>
          <div class="row row-cols-1 row-cols-sm-2 g-2">
      `;
      products.forEach(p => {
        const formattedPrice = new Intl.NumberFormat('vi-VN').format(p.price) + ' ₫';
        productsHtml += `
          <div class="col">
            <div class="card h-100 border rounded-3 p-2 chat-product-card shadow-xs bg-white">
              <div class="d-flex gap-2 align-items-center">
                <img src="../images/products/${p.image}" alt="${escapeHtml(p.product_name)}" 
                     class="rounded-2 object-fit-cover" style="width: 65px; height: 65px;">
                <div class="flex-grow-1 overflow-hidden">
                  <div class="fw-bold text-truncate small" title="${escapeHtml(p.product_name)}">
                    ${escapeHtml(p.product_name)}
                  </div>
                  <div class="text-muted" style="font-size: 0.75rem;">${escapeHtml(p.brand_name || 'Chính hãng')} • ${escapeHtml(p.capacity || '')}</div>
                  <div class="fw-bold text-danger small">${formattedPrice}</div>
                </div>
              </div>
              <div class="d-flex gap-1 mt-2">
                <a href="product_detail.php?id=${p.product_id}" class="btn btn-xs btn-outline-secondary w-50 py-1" style="font-size: 0.75rem;">
                  <i class="bi bi-eye"></i> Chi tiết
                </a>
                <button onclick="addToCart(${p.product_id}, 1)" class="btn btn-xs btn-danger w-50 py-1" style="font-size: 0.75rem; background:#d47a88; border-color:#d47a88;">
                  <i class="bi bi-cart-plus"></i> Thêm giỏ
                </button>
              </div>
            </div>
          </div>
        `;
      });
      productsHtml += `</div></div>`;
    }

    div.innerHTML = `
      <div class="d-flex gap-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0"
             style="width: 38px; height: 38px; background: linear-gradient(135deg, #d47a88, #9e4760);">
          <i class="bi bi-robot"></i>
        </div>
        <div class="message-bubble p-3 rounded-4 shadow-xs" style="background: white; border: 1px solid #f0e6e8; max-width: 88%;">
          <div style="font-size: 0.95rem; line-height: 1.6;">${formattedHtml}</div>
          ${productsHtml}
        </div>
      </div>
    `;

    chatFeed.appendChild(div);
  }

  function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
  }
});
</script>

<?php include 'footer.php'; ?>

