<?php
/**
 * BeautyShop - Floating AI Beauty Advisor Widget
 * Nhúng tiện ích chat bong bóng nổi ở góc màn hình trên mọi trang
 */
?>
<!-- Floating AI Chat Widget Button -->
<div id="aiFloatingBtn" class="position-fixed shadow-lg" 
     style="bottom: 25px; right: 25px; z-index: 9999; cursor: pointer; transition: all 0.3s ease;">
  <div class="d-flex align-items-center gap-2 px-3 py-2 rounded-pill text-white shadow"
       style="background: linear-gradient(135deg, #d47a88, #9e4760); border: 2px solid #fff;">
    <div class="rounded-circle d-flex align-items-center justify-content-center bg-white text-danger" style="width: 32px; height: 32px;">
      <i class="bi bi-robot fs-5"></i>
    </div>
    <span class="fw-bold d-none d-sm-inline" style="font-size: 0.9rem;">AI Tư Vấn Da</span>
    <span class="badge rounded-pill bg-warning text-dark py-1 px-2" style="font-size: 0.65rem;">MỚI</span>
  </div>
</div>

<!-- Floating Chat Box Modal / Drawer -->
<div id="aiChatDrawer" class="position-fixed shadow-2xl rounded-4 overflow-hidden d-none flex-column"
     style="bottom: 90px; right: 25px; width: 380px; max-width: calc(100vw - 30px); height: 550px; z-index: 10000; background: white; border: 1px solid #f0e6e8; transition: all 0.3s ease;">
  
  <!-- Drawer Header -->
  <div class="p-3 text-white d-flex justify-content-between align-items-center"
       style="background: linear-gradient(135deg, #d47a88, #9e4760);">
    <div class="d-flex align-items-center gap-2">
      <div class="rounded-circle bg-white text-danger d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
        <i class="bi bi-robot"></i>
      </div>
      <div>
        <div class="fw-bold" style="font-size: 0.95rem;">AI Beauty Advisor</div>
        <small class="opacity-75" style="font-size: 0.75rem;">Trợ lý làm đẹp BeautyShop</small>
      </div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="ai_advisor.php" class="text-white opacity-75 hover-opacity-100" title="Mở trang đầy đủ" target="_blank">
        <i class="bi bi-arrows-angle-expand"></i>
      </a>
      <button type="button" class="btn-close btn-close-white" id="btnCloseAiDrawer" aria-label="Đóng"></button>
    </div>
  </div>

  <!-- Drawer Message Feed -->
  <div class="flex-grow-1 p-3 overflow-y-auto" id="widgetChatFeed" style="background: #faf7f5; font-size: 0.88rem;">
    <div class="chat-message mb-3">
      <div class="p-2 px-3 rounded-3 bg-white border shadow-xs" style="color: #444;">
        🌸 Chào bạn! Bạn cần tư vấn sản phẩm cho loại da nào, hoặc thắc mắc về thành phần mỹ phẩm nào cứ nhắn cho mình nhé!
      </div>
    </div>
  </div>

  <!-- Drawer Typing Indicator -->
  <div class="px-3 py-1 text-muted small d-none" id="widgetTypingIndicator" style="background: #faf7f5; font-size: 0.75rem;">
    <div class="d-flex align-items-center gap-1">
      <span class="spinner-border spinner-border-sm text-danger" style="width: 10px; height: 10px;"></span>
      <span>Đang tra cứu kho mỹ phẩm...</span>
    </div>
  </div>

  <!-- Drawer Footer Input -->
  <div class="p-2 border-top bg-white">
    <form id="widgetChatForm" class="d-flex gap-1">
      <input type="text" id="widgetUserInput" class="form-control form-control-sm rounded-pill px-3" 
             placeholder="Nhập câu hỏi... (VD: Da dầu mụn dùng gì?)" autocomplete="off">
      <button type="submit" class="btn btn-sm btn-danger rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
              style="width: 32px; height: 32px; background: #d47a88; border-color: #d47a88;">
        <i class="bi bi-send-fill" style="font-size: 0.8rem;"></i>
      </button>
    </form>
  </div>
</div>

<script>
(function() {
  const btnFloat = document.getElementById('aiFloatingBtn');
  const drawer = document.getElementById('aiChatDrawer');
  const btnClose = document.getElementById('btnCloseAiDrawer');
  const chatFeed = document.getElementById('widgetChatFeed');
  const chatForm = document.getElementById('widgetChatForm');
  const userInput = document.getElementById('widgetUserInput');
  const typing = document.getElementById('widgetTypingIndicator');

  let widgetSession = 'widget_' + Math.random().toString(36).substring(2, 10);

  if (!btnFloat || !drawer) return;

  btnFloat.addEventListener('click', function() {
    drawer.classList.toggle('d-none');
    drawer.classList.toggle('d-flex');
    if (!drawer.classList.contains('d-none')) {
      userInput.focus();
    }
  });

  btnClose.addEventListener('click', function() {
    drawer.classList.add('d-none');
    drawer.classList.remove('d-flex');
  });

  chatForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const msg = userInput.value.trim();
    if (!msg) return;

    // User message
    const userDiv = document.createElement('div');
    userDiv.className = 'd-flex justify-content-end mb-2';
    userDiv.innerHTML = `<div class="p-2 px-3 rounded-3 text-white shadow-xs" style="background:#d47a88; max-width:85%;">${escapeHtml(msg)}</div>`;
    chatFeed.appendChild(userDiv);
    userInput.value = '';
    chatFeed.scrollTop = chatFeed.scrollHeight;

    // Typing
    typing.classList.remove('d-none');

    fetch('api_ai_advisor.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: msg, session_token: widgetSession })
    })
    .then(r => r.json())
    .then(data => {
      typing.classList.add('d-none');
      if (data.success) {
        let textHtml = data.reply
          .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
          .replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" class="text-danger fw-bold">$1</a>')
          .replace(/\n/g, '<br>');

        let aiDiv = document.createElement('div');
        aiDiv.className = 'mb-2';
        aiDiv.innerHTML = `<div class="p-2 px-3 rounded-3 bg-white border shadow-xs" style="max-width:90%;">${textHtml}</div>`;
        chatFeed.appendChild(aiDiv);
      } else {
        let errDiv = document.createElement('div');
        errDiv.className = 'mb-2';
        errDiv.innerHTML = `<div class="p-2 px-3 rounded-3 bg-light text-danger border" style="max-width:90%;">Lỗi: ${data.message}</div>`;
        chatFeed.appendChild(errDiv);
      }
      chatFeed.scrollTop = chatFeed.scrollHeight;
    })
    .catch(err => {
      typing.classList.add('d-none');
      let errDiv = document.createElement('div');
      errDiv.className = 'mb-2';
      errDiv.innerHTML = `<div class="p-2 px-3 rounded-3 bg-light text-danger border">Lỗi kết nối máy chủ!</div>`;
      chatFeed.appendChild(errDiv);
      chatFeed.scrollTop = chatFeed.scrollHeight;
    });
  });

  function escapeHtml(t) {
    return t ? t.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;") : '';
  }
})();
</script>

