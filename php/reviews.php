<?php
if (!isset($_SESSION)) session_start();

// ==== FIX: LẤY PRODUCT ID AN TOÀN ====
$product_id = $product_id ?? ($_GET['id'] ?? ($_POST['product_id'] ?? null));

if (!isset($pdo) || !$product_id) {
    echo '<div class="alert alert-danger text-center">Lỗi tải đánh giá (thiếu product_id).</div>';
} 

// === XỬ LÝ GỬI ĐÁNH GIÁ ===
$review_submitted = false;
$review_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review']) && isset($_SESSION['user_id'])) {

    // FIX: đảm bảo product_id luôn có khi submit
    $product_id = $_POST['product_id'] ?? $product_id;

    if (!$product_id) {
        $review_error = "Không xác định được sản phẩm!";
    } 
    else {

        if (!isset($_SESSION['reviewed_' . $product_id])) {

            $rating = max(1, min(5, (int)($_POST['rating'] ?? 0)));
            $comment = trim($_POST['comment'] ?? '');

            if ($rating < 1 || empty($comment)) {
                $review_error = 'Vui lòng chọn sao và nhập nội dung đánh giá!';
            } else {

                // XỬ LÝ ẢNH / VIDEO
                $images = [];
                $upload_dir = 'uploads/reviews/';
                $allowed = ['jpg','jpeg','png','gif','webp','mp4','mov','avi'];

                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                if (!empty($_FILES['images']['name'][0])) {
                    foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
                        if ($_FILES['images']['error'][$i] !== 0) continue;

                        $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
                        if (!in_array($ext, $allowed)) continue;

                        $filename = 'review_'.$product_id.'_'.time().'_'.mt_rand(1000,9999).'.'.$ext;

                        if (move_uploaded_file($tmp, $upload_dir . $filename)) {
                            $images[] = $filename;
                            if (count($images) >= 6) break;
                        }
                    }
                }

                try {
                    $stmt = $pdo->prepare("
    INSERT INTO reviews (
        product_id, 
        user_id, 
        order_id,
        rating, 
        comment, 
        reply,
        replied_at,
        created_at, 
        updated_at,
        images
    ) 
    VALUES (?, ?, ?, ?, ?, NULL, NULL, NOW(), NOW(), ?)
");


                    $stmt->execute([
    $product_id,
    $_SESSION['user_id'],
    null,               // order_id  
    $rating,
    $comment,
    !empty($images) ? json_encode($images, JSON_UNESCAPED_SLASHES) : null
]);


                    $_SESSION['reviewed_' . $product_id] = true;
                    $review_submitted = true;

                } catch (Exception $e) {
                    $review_error = "Lỗi hệ thống. Không thể lưu đánh giá!";
                    error_log("Review insert error: " . $e->getMessage());
                }
            }
        } else {
            $review_submitted = true; // đã đánh giá rồi
        }
    }
}
?>


<?php
// === THỐNG KÊ & LỌC ĐÁNH GIÁ ===
$stats = $pdo->prepare("
    SELECT COALESCE(AVG(rating),0) as avg_rating, COUNT(*) as total,
    SUM(CASE WHEN rating=5 THEN 1 ELSE 0 END) as s5,
    SUM(CASE WHEN rating=4 THEN 1 ELSE 0 END) as s4,
    SUM(CASE WHEN rating=3 THEN 1 ELSE 0 END) as s3,
    SUM(CASE WHEN rating=2 THEN 1 ELSE 0 END) as s2,
    SUM(CASE WHEN rating=1 THEN 1 ELSE 0 END) as s1,
    SUM(CASE WHEN images IS NOT NULL AND images!='[]' AND images!='' THEN 1 ELSE 0 END) as with_media
    FROM reviews WHERE product_id = ?
");
$stats->execute([$product_id]);
$s = $stats->fetch(PDO::FETCH_ASSOC);

$avg = round((float)$s['avg_rating'], 1);
$total = (int)$s['total'];
$withMedia = (int)$s['with_media'];
$bars = [5=>(int)$s['s5'], 4=>(int)$s['s4'], 3=>(int)$s['s3'], 2=>(int)$s['s2'], 1=>(int)$s['s1']];

$filter = $_GET['filter'] ?? 'all';
$where = "WHERE r.product_id = :product_id";
$params = [':product_id' => $product_id];

if ($filter !== 'all') {
    if ($filter === 'media') {
        $where .= " AND r.images IS NOT NULL AND r.images != '' AND r.images != '[]'";
    } elseif (in_array($filter, ['1','2','3','4','5'])) {
        $where .= " AND r.rating = :rating";
        $params[':rating'] = (int)$filter;
    }
}

$reviews = $pdo->prepare("SELECT r.*, u.username FROM reviews r LEFT JOIN users u ON r.user_id = u.id $where ORDER BY r.created_at DESC LIMIT 50");
$reviews->execute($params);
$reviews = $reviews->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container my-5" id="reviews-section">

    <!-- Thông báo thành công (khi vừa gửi hoặc reload lại trang) -->
    <?php if ($review_submitted || isset($_GET['review_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4">
            Cảm ơn bạn! Đánh giá đã được gửi thành công
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($review_error): ?>
        <div class="alert alert-danger rounded-4 mb-4"><?= htmlspecialchars($review_error) ?></div>
    <?php endif; ?>

    <!-- Thống kê sao -->
    <div class="bg-white rounded-4 shadow p-4 mb-4" style="border:3px solid #ffe4f0;">
        <div class="row align-items-center">
            <div class="col-md-3 text-center">
                <h1 class="display-4 fw-bold mb-0" style="color:#ff1493;"><?= $avg ?></h1>
                <p class="text-muted small">trên 5 sao</p>
                <div class="mb-2">
                    <?php for($i=1; $i<=5; $i++): ?>
                        <i class="bi <?= $i <= $avg ? 'bi-star-fill' : 'bi-star' ?> fs-3" style="color:#ffaa00ff;"></i>
                    <?php endfor; ?>
                </div>
                <small class="text-muted"><?= $total ?> đánh giá</small>
            </div>
            <div class="col-md-9">
                <div class="d-flex flex-wrap gap-2">
                    <a href="product_detail.php?id=<?= $product_id ?>#reviews-section" 
                       class="btn <?= $filter==='all'?'btn-danger':'btn-outline-danger' ?> rounded-pill px-4">
                        Tất cả (<?= $total ?>)
                    </a>
                    <?php for($i=5; $i>=1; $i--): ?>
                        <a href="product_detail.php?id=<?= $product_id ?>&filter=<?= $i ?>#reviews-section" 
                           class="btn <?= $filter==$i?'btn-danger':'btn-outline-danger' ?> rounded-pill px-3">
                           <?= $i ?> sao (<?= $bars[$i] ?>)
                        </a>
                    <?php endfor; ?>
                    <a href="product_detail.php?id=<?= $product_id ?>&filter=media#reviews-section" 
                       class="btn <?= $filter==='media'?'btn-danger':'btn-outline-danger' ?> rounded-pill px-3">
                        Có ảnh/video (<?= $withMedia ?>)
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- FORM ĐÁNH GIÁ -->
    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="bg-white rounded-4 shadow-lg p-5 mb-5" style="border:4px solid #ff69b4;background:linear-gradient(135deg,#fff5f8,#ffe4f0);">
        <form method="POST" enctype="multipart/form-data" id="reviewForm" novalidate>
            <input type="hidden" name="submit_review" value="1">

            <!-- Chọn sao -->
            <div class="text-center mb-5">
                <div class="d-inline-flex flex-row-reverse gap-4 star-rating" role="radiogroup" aria-label="Chọn đánh giá sao">
                    <?php for($i=5; $i>=1; $i--): ?>
                        <label class="star-label" tabindex="0">
                            <input type="radio" name="rating" value="<?= $i ?>" required style="display:none;">
                            <i class="bi bi-star-fill"></i>
                            <span class="visually-hidden"><?= $i ?> sao</span>
                        </label>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Textarea -->
            <div class="mb-4">
                <textarea name="comment" class="form-control rounded-4" rows="6" 
                          placeholder="Chia sẻ cảm nhận của bạn về sản phẩm này nhé..." 
                          style="border:3px solid #ff69b4;resize:none;font-size:1rem;" required></textarea>
            </div>

            <!-- Upload ảnh/video -->
            <div class="border rounded-4 p-3" style="background:#fff8fb; min-height:100px; position:relative;">
                <div id="previewContainer" class="d-flex flex-wrap gap-3"></div>

                <div class="mt-3 d-flex gap-3 align-items-center">
                    <label class="btn rounded-3 px-4 py-3" style="background:#fff; border:2px dashed #ff69b4; color:#ff1493; font-weight:600; cursor:pointer;">
                        Thêm ảnh
                        <input type="file" name="images[]" multiple accept="image/*" onchange="previewFiles(this.files)" style="display:none;">
                    </label>
                    <label class="btn rounded-3 px-4 py-3" style="background:#fff; border:2px dashed #ff1493; color:#ff1493; font-weight:600; cursor:pointer;">
                        Thêm video
                        <input type="file" name="images[]" accept="video/*" onchange="previewFiles(this.files)" style="display:none;">
                    </label>
                    <small class="text-muted ms-3">Tối đa 6 file, hỗ trợ ảnh & video</small>
                </div>
            </div>

            <!-- Nút gửi -->
            <div class="text-end mt-4">
                <button type="submit" class="btn btn-lg px-5 py-3 text-white fw-bold rounded-pill shadow"
                        style="background:linear-gradient(135deg,#ff1493,#ff69b4);font-size:1.1rem;">
                    GỬI ĐÁNH GIÁ
                </button>
            </div>
        </form>
    </div>

    <style>
        .star-rating { z-index: 20; }
        .star-label { font-size: 3.8rem; cursor: pointer; transition: all .25s; }
        .star-label i { color: #e0e0e0; transition: all .25s; }
        .star-label:hover i, .star-label:hover ~ .star-label i,
        .star-label.active i, .star-label.active ~ .star-label i {
            color: #ffaa00ff !important; transform: scale(1.35);
             filter: drop-shadow(0 0 15px #ffbc2cff); 
        }
        .star-rated { color: #ffaa00ff !important; }
.star-empty { color: #ddd !important; }
   

        .preview-item {
            position: relative; width: 100px; height: 100px; border-radius: 12px; overflow: hidden;
            border: 3px solid #ffaa00ff; box-shadow: 0 4px 12px rgba(255,105,180,.4);
        }
        .preview-item img, .preview-item video {
            width: 100%; height: 100%; object-fit: cover; background:#000;
        }
        .preview-remove {
            position: absolute; top: -8px; right: -8px; width: 28px; height: 28px;
            background:#ffaa00ff; color: white; border-radius: 50%; font-size: 14px;
            display: flex; align-items: center; justify-content: center; cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,.3); z-index: 10;
        }
        .preview-count {
            position: absolute; bottom: 4px; right: 4px; background: rgba(0,0,0,.7);
            color: white; padding: 2px 6px; border-radius: 6px; font-size: 11px;
        }
    </style>

    <script>
        const previewContainer = document.getElementById('previewContainer');
        let fileCount = 0;

        function previewFiles(files) {
            if (fileCount >= 6) {
                alert('Chỉ được tải lên tối đa 6 file!');
                return;
            }
            Array.from(files).slice(0, 6 - fileCount).forEach(file => {
                if (file.size > 50 * 1024 * 1024) {
                    alert(`File "${file.name}" quá lớn! Tối đa 50MB.`);
                    return;
                }
                const reader = new FileReader();
                reader.onload = e => {
                    const div = document.createElement('div');
                    div.className = 'preview-item';
                    div.innerHTML = `
                        ${file.type.startsWith('video/') 
                            ? `<video controls loop muted><source src="${e.target.result}" type="${file.type}"></video>`
                            : `<img src="${e.target.result}">`}
                        <div class="preview-remove" onclick="this.parentElement.remove(); fileCount--;">×</div>
                        <div class="preview-count">${++fileCount}/6</div>
                    `;
                    previewContainer.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        }

        document.querySelectorAll('.star-label').forEach((label, index, labels) => {
            label.addEventListener('click', () => {
                labels.forEach(l => l.classList.remove('active'));
                for (let i = index; i < labels.length; i++) {
                    labels[i].classList.add('active');
                }
            });
        });

        // TỰ ĐỘNG RELOAD SAU KHI GỬI THÀNH CÔNG ĐỂ HIỆN ĐÁNH GIÁ MỚI + THÔNG BÁO
        <?php if ($review_submitted): ?>
            if (!location.href.includes('review_success')) {
                const separator = location.href.includes('?') ? '&' : '?';
                location.href = location.href + separator + 'review_success=1#reviews-section';
            }
        <?php endif; ?>
    </script>

    <?php else: ?>
    <div class="text-center py-5 bg-white rounded-4 shadow">
        <a href="login.php" class="btn btn-danger rounded-pill px-5 py-3 fs-5">Đăng nhập để đánh giá</a>
    </div>
    <?php endif; ?>

    <!-- Danh sách đánh giá -->
    <?php foreach($reviews as $r): 
        $imgs = $r['images'] ? json_decode($r['images'], true) : [];
        if (!is_array($imgs)) $imgs = [];
    ?>
    <div class="bg-white rounded-4 p-4 mb-3 shadow-sm" style="border:2px solid #ffe4f0;">
        <div class="d-flex gap-3">
            <div class="text-white rounded-circle d-flex align-items-center justify-content-center fw-bold"
                 style="width:48px;height:48px;background:#ff69b4;font-size:1.2rem;flex-shrink:0;">
                <?= strtoupper(substr($r['username'] ?? 'K', 0, 1)) ?>
            </div>
            <div class="flex-grow-1">
                <strong style="color:#ff1493;"><?= htmlspecialchars($r['username'] ?? 'Khách') ?></strong>
                <div class="d-inline-block ms-2">
                    <?php for($i=1; $i<=5; $i++): ?>
                       <i class="bi <?= $i<=$r['rating'] ? 'bi-star-fill star-rated' : 'bi-star star-empty' ?>"></i>
                    <?php endfor; ?>
                </div>
                <small class="text-muted d-block"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></small>
              <p class="mt-3 mb-0"><?= nl2br(htmlspecialchars($r['comment'])) ?></p>

<!-- ẢNH/VIDEO KHÁCH GỬI (chỉ để lại đúng 1 đoạn này) -->
<?php if ($imgs): ?>
<div class="d-flex flex-wrap gap-3 mt-3">
    <?php foreach($imgs as $img): 
        $file = "uploads/reviews/" . basename($img);
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['mp4','mov','avi'])): ?>
            <video controls class="rounded shadow-sm" style="width:140px;height:140px;object-fit:cover;background:#000;">
                <source src="<?= $file ?>" type="video/mp4">
            </video>
        <?php else: ?>
            <img src="<?= $file ?>" class="rounded shadow-sm"
                 style="width:140px;height:140px;object-fit:cover;border:3px solid #ff69b4;cursor:pointer;"
                 onclick="window.open(this.src,'_blank')">
        <?php endif; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- PHẢN HỒI TỪ SHOP (nằm dưới cùng) -->
<?php if (!empty($r['reply'])): ?>
<div class="mt-4 p-3 rounded-4" style="background:#fff8fb; border-left:5px solid #ff69b4; font-size:0.95rem;">
    <div class="d-flex align-items-center gap-2 mb-2">
        <i class="bi bi-shop" style="color:#ff1493;"></i>
        <strong style="color:#ff1493;">Phản hồi từ Shop</strong>
        <small class="text-muted ms-auto">
            <?= date('d/m/Y H:i', strtotime($r['replied_at'])) ?>
        </small>
    </div>
    <div style="color:#333; line-height:1.6;">
        <?= nl2br(htmlspecialchars($r['reply'])) ?>
    </div>
</div>
<?php endif; ?>
                
              </div>
    </div>
</div>
<?php endforeach; ?>   
</div>