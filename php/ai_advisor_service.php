<?php
/**
 * BeautyShop - AI Beauty Advisor Service
 * Tích hợp Google Gemini API + Grounding CSDL MySQL thực tế
 */

require_once __DIR__ . '/config.php';

class AIBeautyAdvisorService {
    private $pdo;
    private $apiKey;
    private $model;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->apiKey = get_gemini_api_key();
        $this->model = getenv('GEMINI_MODEL') ?: 'gemini-1.5-flash';
    }

    /**
     * Tìm kiếm sản phẩm liên quan từ MySQL theo nhu cầu người dùng
     */
    public function searchRelevantProducts($userMessage, $skinType = null, $budgetMax = null, $limit = 6) {
        $msgLower = mb_strtolower($userMessage, 'UTF-8');
        $params = [];
        $whereClauses = ["p.is_active = 1"];

        // 1. Nhận diện từ khóa loại da
        $skinKeywords = [
            'dầu' => 1, 'nhờn' => 1, 'mụn' => 1,
            'khô' => 2, 'bong tróc' => 2, 'nứt nẻ' => 2,
            'nhạy cảm' => 3, 'kích ứng' => 3, 'đỏ rát' => 3, 'yếu' => 3,
            'hỗn hợp' => 4,
            'thường' => 5
        ];

        $detectedSkinTypeId = $skinType;
        if (!$detectedSkinTypeId) {
            foreach ($skinKeywords as $kw => $stId) {
                if (str_contains($msgLower, $kw)) {
                    $detectedSkinTypeId = $stId;
                    break;
                }
            }
        }

        // 2. Nhận diện danh mục
        $catKeywords = [
            'toner' => 1, 'nước hoa hồng' => 1, 'nước cân bằng' => 1,
            'sữa rửa mặt' => 2, 'rửa mặt' => 2, 'tẩy trang' => 2, 'làm sạch' => 2,
            'serum' => 3, 'tinh chất' => 3, 'ampoule' => 3, 'phục hồi' => 3,
            'kem dưỡng' => 4, 'dưỡng ẩm' => 4, 'cấp ẩm' => 4, 'khóa ẩm' => 4,
            'chống nắng' => 5, 'kem chống nắng' => 5, 'spf' => 5, 'sunscreen' => 5,
            'mặt nạ' => 6, 'mask' => 6, 'đắp mặt' => 6, 'pad' => 6,
            'son' => 7, 'son môi' => 7, 'lipstick' => 7, 'tint' => 7
        ];

        $detectedCatId = null;
        foreach ($catKeywords as $kw => $catId) {
            if (str_contains($msgLower, $kw)) {
                $detectedCatId = $catId;
                break;
            }
        }

        // 3. Nhận diện ngân sách
        if (preg_match('/(\d+)\s*(k|nghìn|ngàn|tram|trăm|triệu|tr)/i', $msgLower, $matches)) {
            $num = (int)$matches[1];
            $unit = mb_strtolower($matches[2]);
            if (in_array($unit, ['k', 'nghìn', 'ngàn'])) {
                $budgetMax = $num * 1000;
            } elseif (in_array($unit, ['tr', 'triệu'])) {
                $budgetMax = $num * 1000000;
            } elseif (in_array($unit, ['tram', 'trăm'])) {
                $budgetMax = $num * 100000;
            }
        }

        $sql = "
            SELECT DISTINCT p.product_id, p.product_name, p.price, p.original_price, p.capacity,
                            p.image, p.skin_type_summary, p.ingredients_text, p.description,
                            c.category_name, b.brand_name, p.rating_avg, p.sold_count
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN brands b ON p.brand_id = b.brand_id
        ";

        if ($detectedSkinTypeId) {
            $sql .= " LEFT JOIN product_skin_types pst ON p.product_id = pst.product_id ";
            $whereClauses[] = "(pst.skin_type_id = ? OR p.skin_type_summary LIKE '%Mọi loại da%')";
            $params[] = $detectedSkinTypeId;
        }

        if ($detectedCatId) {
            $whereClauses[] = "p.category_id = ?";
            $params[] = $detectedCatId;
        }

        if ($budgetMax && $budgetMax > 0) {
            $whereClauses[] = "p.price <= ?";
            $params[] = $budgetMax;
        }

        // Tìm kiếm theo từ khóa tên hoặc thành phần
        $searchTerms = ['b5', 'rau má', 'centella', 'niacinamide', 'ha', 'hyaluronic', 'vitamin c', 'tràm trà', 'bha', 'ceramide', 'nghệ'];
        $foundTerms = [];
        foreach ($searchTerms as $term) {
            if (str_contains($msgLower, $term)) {
                $foundTerms[] = $term;
            }
        }

        if (!empty($foundTerms)) {
            $termClauses = [];
            foreach ($foundTerms as $term) {
                $termClauses[] = "(p.product_name LIKE ? OR p.ingredients_text LIKE ? OR p.description LIKE ?)";
                $params[] = "%$term%";
                $params[] = "%$term%";
                $params[] = "%$term%";
            }
            $whereClauses[] = "(" . implode(" OR ", $termClauses) . ")";
        }

        $sql .= " WHERE " . implode(" AND ", $whereClauses);
        $sql .= " ORDER BY p.rating_avg DESC, p.sold_count DESC LIMIT " . (int)$limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Nếu quá ít kết quả do lọc quá hẹp, lấy thêm sản phẩm nổi bật
        if (count($products) < 2) {
            $fallbackStmt = $this->pdo->query("
                SELECT p.product_id, p.product_name, p.price, p.original_price, p.capacity,
                       p.image, p.skin_type_summary, p.ingredients_text, p.description,
                       c.category_name, b.brand_name, p.rating_avg, p.sold_count
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                LEFT JOIN brands b ON p.brand_id = b.brand_id
                WHERE p.is_active = 1 AND p.is_featured = 1
                ORDER BY p.sold_count DESC LIMIT 4
            ");
            $fallbacks = $fallbackStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($fallbacks as $fb) {
                $exists = false;
                foreach ($products as $p) {
                    if ($p['product_id'] === $fb['product_id']) { $exists = true; break; }
                }
                if (!$exists) {
                    $products[] = $fb;
                    if (count($products) >= $limit) break;
                }
            }
        }

        return $products;
    }

    /**
     * Tạo phản hồi tư vấn thông minh
     */
    public function getAdvice($userMessage, $chatHistory = [], $skinType = null, $budget = null) {
        // 1. Truy xuất các sản phẩm thực tế liên quan từ database
        $relevantProducts = $this->searchRelevantProducts($userMessage, $skinType, $budget, 5);

        // 2. Nếu có Gemini API Key, gọi trực tiếp Gemini API
        if (!empty($this->apiKey) && $this->apiKey !== 'YOUR_GEMINI_API_KEY_HERE') {
            return $this->callGeminiAPI($userMessage, $chatHistory, $relevantProducts);
        }

        // 3. Fallback thông minh: Rule-Based Database Grounding Engine
        return $this->generateRuleBasedAdvice($userMessage, $relevantProducts, $skinType);
    }

    /**
     * Gọi Google Gemini API với System Instructions và Grounding Catalog
     */
    private function callGeminiAPI($userMessage, $chatHistory, $products) {
        $catalogContext = "DANH MỤC SẢN PHẨM THỰC TẾ TRONG KHO BEAUTYSHOP:\n";
        foreach ($products as $p) {
            $catalogContext .= sprintf(
                "- ID: %d | Tên: %s | Thương hiệu: %s | Giá bán: %s (Giá gốc: %s) | Dung tích: %s | Loại da: %s | Thành phần: %s | Công dụng: %s\n",
                $p['product_id'],
                $p['product_name'],
                $p['brand_name'] ?? 'Chính hãng',
                format_price($p['price']),
                format_price($p['original_price']),
                $p['capacity'],
                $p['skin_type_summary'],
                $p['ingredients_text'],
                mb_substr(strip_tags($p['description']), 0, 120)
            );
        }

        $systemInstruction = <<<TEXT
Bạn là "Beauty Advisor" - Chuyên viên tư vấn mỹ phẩm và chăm sóc da cao cấp của BeautyShop (Việt Nam).
Tôn chỉ tư vấn:
1. Luôn ân cần, chuyên nghiệp, thanh lịch và am hiểu chuyên sâu về da liễu & mỹ phẩm.
2. CHỈ ĐỀ XUẤT CÁC SẢN PHẨM CÓ TRONG DANH MỤC THỰC TẾ ĐƯỢC CUNG CẤP. Tuyệt đối không tự bịa ra sản phẩm, giá bán hay thương hiệu không có trong kho.
3. Khi đề xuất sản phẩm, hãy nêu rõ Tên, Giá bán và lý do vì sao sản phẩm đó phù hợp với làn da của khách hàng. Hãy dẫn link sản phẩm theo định dạng: [Xem chi tiết](product_detail.php?id={product_id}).
4. Đưa ra quy trình chăm sóc da khoa học (Sáng / Tối) theo các bước: Làm sạch -> Cân bằng -> Điều trị/Dưỡng chất -> Khóa ẩm -> Bảo vệ chống nắng.
5. Giải thích công dụng các hoạt chất (Niacinamide, BHA, HA, Vitamin B5, Centella, Vitamin C,...) một cách khoa học, dễ hiểu.
6. LƯU Ý Y KHOA: Lời khuyên chỉ mang tính chất tư vấn mỹ phẩm. Nếu khách hàng có tình trạng viêm da cơ địa nặng, nhiễm trùng hoặc kích ứng nghiêm trọng, hãy khuyên họ thăm khám bác sĩ da liễu.
TEXT;

        // Chuẩn bị payload cho Gemini
        $contents = [];

        // Thêm lịch sử nếu có
        foreach ($chatHistory as $msg) {
            $contents[] = [
                'role' => $msg['sender'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $msg['message']]]
            ];
        }

        // Thêm prompt hiện tại kèm catalog context
        $promptWithContext = "Bối cảnh sản phẩm hiện có tại cửa hàng:\n$catalogContext\n\nKhách hàng hỏi: $userMessage";
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $promptWithContext]]
        ];

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemInstruction]]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'topP' => 0.95,
                'maxOutputTokens' => 1200,
            ]
        ];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 25,
            CURLOPT_SSL_VERIFYPEER => false // Giúp chạy mượt mà trên môi trường Windows local
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $resData = json_decode($response, true);
            $aiText = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';
            if (!empty($aiText)) {
                return [
                    'source' => 'gemini_api',
                    'text' => $aiText,
                    'products' => $products,
                    'disclaimer' => 'Tư vấn được hỗ trợ bởi BeautyShop AI Advisor & Gemini Engine. Lời khuyên mang tính chất tham khảo mỹ phẩm.'
                ];
            }
        }

        // Nếu API lỗi hoặc hết quota, chuyển sang Fallback Engine
        return $this->generateRuleBasedAdvice($userMessage, $products, null, "Lưu ý: Kết nối AI API tạm gián đoạn ($httpCode), BeautyShop chuyển sang chế độ tư vấn trực tiếp từ kho CSDL.");
    }

    /**
     * Fallback Engine: Tư vấn dựa trên dữ liệu sản phẩm MySQL và quy trình chăm sóc da chuẩn
     */
    private function generateRuleBasedAdvice($userMessage, $products, $skinType = null, $extraNotice = null) {
        $msgLower = mb_strtolower($userMessage, 'UTF-8');
        
        $greeting = "Chào bạn! Mình là **Beauty Advisor** của BeautyShop. Rất vui được đồng hành cùng bạn trên hành trình chăm sóc làn da khỏe đẹp! 🌸\n\n";

        $adviceBody = "";
        
        // Phân tích chủ đề
        if (str_contains($msgLower, 'dầu') || str_contains($msgLower, 'mụn')) {
            $adviceBody .= "### 🌿 Chăm sóc làn da Dầu & Mụn\n";
            $adviceBody .= "- **Nguyên tắc cốt lõi:** Làm sạch sâu dịu nhẹ, cân bằng dầu - nước, không chà xát mạnh làm vỡ nang lông và luôn cấp nước để da không tiết dầu bù trừ.\n";
            $adviceBody .= "- **Hoạt chất khuyên dùng:** *Centella Asiatica (Rau má)* làm dịu sưng đỏ, *Niacinamide* điều tiết bã nhờn, *Salicylic Acid (BHA)* làm sạch bã nhờn sâu trong lỗ chân lông.\n\n";
        } elseif (str_contains($msgLower, 'khô') || str_contains($msgLower, 'bong tróc')) {
            $adviceBody .= "### 💧 Chăm sóc làn da Khô & Mất nước\n";
            $adviceBody .= "- **Nguyên tắc cốt lõi:** Bổ sung độ ẩm đa tầng và củng cố hàng rào lipid bảo vệ da để ngăn chặn hiện tượng mất nước qua biểu bì.\n";
            $adviceBody .= "- **Hoạt chất khuyên dùng:** *Hyaluronic Acid (HA)* ngậm nước gấp 1000 lần, *Ceramide NP* phục hồi màng ẩm, *Vitamin B5 (Panthenol)* xoa dịu cảm giác căng rát.\n\n";
        } elseif (str_contains($msgLower, 'nhạy cảm') || str_contains($msgLower, 'kích ứng') || str_contains($msgLower, 'phục hồi')) {
            $adviceBody .= "### 🛡️ Phục hồi da Nhạy Cảm & Tổn Thương\n";
            $adviceBody .= "- **Nguyên tắc cốt lõi:** Tối giản quy trình dưỡng da (Less is more), tránh xa cồn xấu, hương liệu và xà phòng nồng độ cao.\n";
            $adviceBody .= "- **Hoạt chất khuyên dùng:** *Centella Asiatica*, *Vitamin B5*, *Ceramides* giúp tái thiết lập hàng rào sinh học bảo vệ da.\n\n";
        } elseif (str_contains($msgLower, 'sáng') || str_contains($msgLower, 'thâm') || str_contains($msgLower, 'nám') || str_contains($msgLower, 'vitamin c')) {
            $adviceBody .= "### ✨ Dưỡng Sáng & Mờ Thâm Sau Mụn\n";
            $adviceBody .= "- **Nguyên tắc cốt lõi:** Kết hợp chống oxy hóa vào buổi sáng và dưỡng chất ức chế melanin vào buổi tối. Bắt buộc phải thoa kem chống nắng kỹ lưỡng để không bị sạm lại.\n";
            $adviceBody .= "- **Hoạt chất khuyên dùng:** *Vitamin C*, *Niacinamide*, *AHA Glycolic Acid* nhẹ nhàng loại bỏ tế bào sừng xỉn màu.\n\n";
        } else {
            $adviceBody .= "### 🌸 Tư vấn Chăm Sóc Da Cá Nhân Hóa\n";
            $adviceBody .= "Để có một làn da căng mịn, việc kết hợp đúng thứ tự sản phẩm từ lỏng đến đặc (Làm sạch ➔ Cân bằng ➔ Tinh chất ➔ Khóa ẩm ➔ Chống nắng) là chìa khóa quan trọng nhất.\n\n";
        }

        // Đề xuất sản phẩm thực tế từ Database
        $adviceBody .= "### 🛍️ Gợi Ý Sản Phẩm Phù Hợp Đang Có Sẵn Tại Cửa Hàng:\n";
        if (!empty($products)) {
            foreach ($products as $idx => $p) {
                $adviceBody .= sprintf(
                    "%d. **[%s](product_detail.php?id=%d)**\n" .
                    "   - *Thương hiệu:* %s | *Dung tích:* %s\n" .
                    "   - *Giá ưu đãi:* **%s** ~~(Giá gốc: %s)~~\n" .
                    "   - *Thành phần tiêu biểu:* %s\n" .
                    "   - *Loại da phù hợp:* %s\n" .
                    "   - 👉 [Xem chi tiết sản phẩm](product_detail.php?id=%d)\n\n",
                    $idx + 1,
                    $p['product_name'],
                    $p['product_id'],
                    $p['brand_name'] ?? 'Chính hãng',
                    $p['capacity'],
                    format_price($p['price']),
                    format_price($p['original_price']),
                    $p['ingredients_text'] ?: 'Thành phần an toàn lành tính',
                    $p['skin_type_summary'],
                    $p['product_id']
                );
            }
        } else {
            $adviceBody .= "Hiện tại hệ thống chưa tìm thấy sản phẩm khớp hoàn toàn với tiêu chí ngân sách hoặc loại da này. Bạn có thể xem thêm danh sách đầy đủ tại mục [Sản Phẩm](index.php#products-section) nhé!\n\n";
        }

        $notice = $extraNotice ?: (!get_gemini_api_key() ? "💡 *Gợi ý cho Quản trị viên: Điền GEMINI_API_KEY vào file `.env` để kích hoạt trải nghiệm trí tuệ nhân tạo Gemini AI trực tiếp.*" : "");

        return [
            'source' => 'database_grounding_engine',
            'text' => $greeting . $adviceBody . ($notice ? "\n" . $notice : ""),
            'products' => $products,
            'disclaimer' => 'Tư vấn được trích xuất từ dữ liệu sản phẩm thực tế của BeautyShop. Lời khuyên mang tính chất tham khảo mỹ phẩm.'
        ];
    }

    /**
     * Lưu tin nhắn vào CSDL
     */
    public function logMessage($sessionToken, $userId, $sender, $message, $recommendedProducts = null) {
        try {
            // Lấy hoặc tạo session
            $stmt = $this->pdo->prepare("SELECT id FROM chat_sessions WHERE session_token = ?");
            $stmt->execute([$sessionToken]);
            $session = $stmt->fetch();

            if (!$session) {
                $stmt = $this->pdo->prepare("INSERT INTO chat_sessions (session_token, user_id, title) VALUES (?, ?, ?)");
                $title = 'Tư vấn ngày ' . date('d/m/Y H:i');
                $stmt->execute([$sessionToken, $userId, $title]);
                $sessionId = $this->pdo->lastInsertId();
            } else {
                $sessionId = $session['id'];
                if ($userId) {
                    $this->pdo->prepare("UPDATE chat_sessions SET user_id = ? WHERE id = ?")->execute([$userId, $sessionId]);
                }
            }

            // Lưu tin nhắn
            $stmt = $this->pdo->prepare("
                INSERT INTO chat_messages (session_id, sender, message, recommended_products, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $prodJson = !empty($recommendedProducts) ? json_encode($recommendedProducts, JSON_UNESCAPED_UNICODE) : null;
            $stmt->execute([$sessionId, $sender, $message, $prodJson]);

            return true;
        } catch (Exception $e) {
            error_log("Chat log error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy lịch sử tin nhắn của một session
     */
    public function getSessionMessages($sessionToken) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT m.* FROM chat_messages m
                JOIN chat_sessions s ON m.session_id = s.id
                WHERE s.session_token = ?
                ORDER BY m.id ASC
            ");
            $stmt->execute([$sessionToken]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}

