-- ===================================================================
-- BEAUTY SHOP - DATABASE SEED DATA (DỮ LIỆU MẪU MỸ PHẨM CHÍNH HÃNG)
-- ===================================================================

USE `beauty_shop`;

-- Tắt kiểm tra khóa ngoại tạm thời để xóa/nạp sạch dữ liệu
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `order_status_history`;
TRUNCATE TABLE `coupon_usages`;
TRUNCATE TABLE `order_items`;
TRUNCATE TABLE `orders`;
TRUNCATE TABLE `cart_items`;
TRUNCATE TABLE `cart`;
TRUNCATE TABLE `reviews`;
TRUNCATE TABLE `wishlists`;
TRUNCATE TABLE `flash_sale_items`;
TRUNCATE TABLE `flash_sales`;
TRUNCATE TABLE `coupons`;
TRUNCATE TABLE `product_ingredients`;
TRUNCATE TABLE `product_skin_types`;
TRUNCATE TABLE `product_images`;
TRUNCATE TABLE `products`;
TRUNCATE TABLE `ingredients`;
TRUNCATE TABLE `skin_types`;
TRUNCATE TABLE `brands`;
TRUNCATE TABLE `categories`;
TRUNCATE TABLE `addresses`;
TRUNCATE TABLE `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. USERS (Mật khẩu admin: admin123, Mật khẩu customer: customer123)
INSERT INTO `users` (`id`, `username`, `email`, `phone`, `password`, `full_name`, `avatar`, `role`, `status`) VALUES
(1, 'admin', 'admin@beautyshop.vn', '0909888999', '$2y$10$BhWnZJzMvQHzk4ynbEo/TuaSy22RZ2pRnjtiICzOF0d.2UyNAO9hq', 'Quản Trị Viên Hệ Thống', 'default-avatar.png', 'admin', 'active'),
(2, 'manager', 'manager@beautyshop.vn', '0912345678', '$2y$10$BhWnZJzMvQHzk4ynbEo/TuaSy22RZ2pRnjtiICzOF0d.2UyNAO9hq', 'Nguyễn Thu Trang - Store Manager', 'default-avatar.png', 'admin', 'active'),
(3, 'lananh', 'lananh@gmail.com', '0987654321', '$2y$10$DhAmDR29LcK628jMTThjreJVV/RUea.bCSHb2mcs4w05llo5AVywG', 'Nguyễn Lan Anh', 'default-avatar.png', 'customer', 'active'),
(4, 'minhthu', 'minhthu@gmail.com', '0978123456', '$2y$10$DhAmDR29LcK628jMTThjreJVV/RUea.bCSHb2mcs4w05llo5AVywG', 'Trần Minh Thư', 'default-avatar.png', 'customer', 'active');

-- 2. ADDRESSES
INSERT INTO `addresses` (`id`, `user_id`, `receiver_name`, `receiver_phone`, `province`, `district`, `ward`, `street_address`, `is_default`) VALUES
(1, 3, 'Nguyễn Lan Anh', '0987654321', 'TP. Hồ Chí Minh', 'Quận 1', 'Phường Bến Nghé', '123 Đường Lê Lợi, Tòa nhà Sunwah', 1),
(2, 4, 'Trần Minh Thư', '0978123456', 'Đà Nẵng', 'Quận Ngũ Hành Sơn', 'Phường Hòa Quý', '470 Trần Đại Nghĩa, KTX Đại học CNTT', 1);

-- 3. CATEGORIES
INSERT INTO `categories` (`category_id`, `category_name`, `slug`, `image`, `description`, `sort_order`, `is_active`) VALUES
(1, 'Nước Hoa Hồng & Toner', 'nuoc-hoa-hong-toner', 'cosmetics.png', 'Cân bằng độ pH, cấp ẩm tức thì và làm sạch sâu lỗ chân lông.', 1, 1),
(2, 'Sữa Rửa Mặt & Làm Sạch', 'sua-rua-mat-lam-sach', 'cosmetics.png', 'Làm sạch bụi bẩn, dầu thừa và bã nhờn mà vẫn giữ màng ẩm tự nhiên.', 2, 1),
(3, 'Serum & Tinh Chất Phục Hồi', 'serum-tinh-chat-phuc-hoi', 'cosmetics.png', 'Đặc trị các vấn đề thâm nám, mụn, lão hóa và phục hồi hàng rào bảo vệ da.', 3, 1),
(4, 'Kem Dưỡng & Cấp Ẩm', 'kem-duong-cap-am', 'cosmetics.png', 'Khóa ẩm, củng cố lớp màng lipid, giúp da luôn căng mọng và mềm mịn.', 4, 1),
(5, 'Kem Chống Nắng', 'kem-chong-nang', 'cosmetics.png', 'Bảo vệ da toàn diện trước tia UVA, UVB và ánh sáng xanh.', 5, 1),
(6, 'Mặt Nạ Dưỡng Da', 'mat-na-duong-da', 'cosmetics.png', 'Bổ sung dưỡng chất cô đặc, thư giãn và phục hồi sức sống cho làn da.', 6, 1),
(7, 'Son Môi & Trang Điểm', 'son-moi-trang-diem', 'cosmetics.png', 'Tôn vinh vẻ đẹp tự nhiên, rạng rỡ và sắc màu cuốn hút cả ngày dài.', 7, 1);

-- 4. BRANDS
INSERT INTO `brands` (`brand_id`, `brand_name`, `slug`, `origin_country`, `description`, `is_active`) VALUES
(1, 'Cocoon', 'cocoon', 'Việt Nam', 'Thương hiệu mỹ phẩm thuần chay 100% Việt Nam, từ nông sản bản địa.', 1),
(2, 'L\'Oréal Paris', 'loreal-paris', 'Pháp', 'Tập đoàn mỹ phẩm hàng đầu thế giới với công nghệ chăm sóc da tiên tiến.', 1),
(3, 'Simple', 'simple', 'Anh Quốc', 'Chuyên gia chăm sóc da lành tính, dịu nhẹ số 1 cho làn da nhạy cảm.', 1),
(4, 'Bioderma', 'bioderma', 'Pháp', 'Dược mỹ phẩm sinh học bảo vệ và tái tạo sức khỏe sinh học làn da.', 1),
(5, 'Eucerin', 'eucerin', 'Đức', 'Dược mỹ phẩm y khoa hàng đầu, khuyên dùng bởi các bác sĩ da liễu.', 1),
(6, 'Klairs', 'klairs', 'Hàn Quốc', 'Mỹ phẩm dịu nhẹ không chứa hương liệu nhân tạo, thân thiện với môi trường.', 1),
(7, 'Hada Labo', 'hada-labo', 'Nhật Bản', 'Hoàn hảo và đơn giản với công nghệ Hyaluronic Acid dưỡng ẩm nổi tiếng.', 1),
(8, 'Torriden', 'torriden', 'Hàn Quốc', 'Thương hiệu top 1 giải thưởng làm đẹp Hàn Quốc về cấp ẩm sâu đa tầng.', 1),
(9, 'Beplain', 'beplain', 'Hàn Quốc', 'Mỹ phẩm sạch thuần khiết, thân thiện với làn da và đại dương.', 1),
(10, 'Skin1004', 'skin1004', 'Hàn Quốc', 'Chiết xuất 100% rau má hữu cơ tinh khiết từ vùng đất Madagascar.', 1),
(11, 'CeraVe', 'cerave', 'Mỹ', 'Dưỡng ẩm phục hồi với phức hợp 3 loại Ceramide thiết yếu cho da.', 1),
(12, 'Garnier', 'garnier', 'Pháp', 'Chiết xuất từ thiên nhiên với công nghệ làm sáng và làm sạch micellar vượt trội.', 1);

-- 5. SKIN_TYPES
INSERT INTO `skin_types` (`id`, `name`, `code`, `description`, `icon`) VALUES
(1, 'Da Dầu & Mụn', 'oily', 'Tuyến bã nhờn hoạt động mạnh, bóng nhờn vùng chữ T, dễ bít tắc lỗ chân lông.', 'bi-droplet-half'),
(2, 'Da Khô & Bong Tróc', 'dry', 'Thiếu ẩm, thô ráp, xuất hiện vảy khô, dễ căng rát sau khi rửa mặt.', 'bi-sun'),
(3, 'Da Nhạy Cảm & Dễ Kích Ứng', 'sensitive', 'Dễ mẩn đỏ, ngứa ngáy khi thời tiết thay đổi hoặc tiếp xúc thành phần lạ.', 'bi-shield-check'),
(4, 'Da Hỗn Hợp Thiên Dầu', 'combination', 'Đổ dầu nhiều ở trán, mũi, cằm (vùng T) nhưng hai bên má lại khô ráo.', 'bi-pie-chart'),
(5, 'Da Thường & Khỏe Mạnh', 'normal', 'Làn da cân bằng, độ ẩm mượt tự nhiên, lỗ chân lông nhỏ, ít khuyết điểm.', 'bi-stars');

-- 6. INGREDIENTS
INSERT INTO `ingredients` (`id`, `name`, `scientific_name`, `safety_rating`, `benefit_description`) VALUES
(1, 'Niacinamide (Vitamin B3)', 'Nicotinamide', 'Cực kỳ an toàn (1-2 EWG)', 'Kiểm soát dầu thừa, se khít lỗ chân lông, củng cố hàng rào ceramide và làm mờ thâm.'),
(2, 'Hyaluronic Acid (HA)', 'Sodium Hyaluronate', 'Lành tính tuyệt đối (1 EWG)', 'Khả năng giữ nước gấp 1000 lần trọng lượng phân tử, cấp ẩm sâu đa tầng cho da.'),
(3, 'Chiết xuất Rau Má Madagascar', 'Centella Asiatica Extract', 'Thảo mộc tự nhiên', 'Kháng viêm, làm dịu kích ứng, tái tạo mô sẹo và phục hồi vùng da bị tổn thương.'),
(4, 'Salicylic Acid (BHA)', 'Salicylic Acid', 'Dược mỹ phẩm tiêu chuẩn', 'Tan trong dầu, len lỏi sâu vào lỗ chân lông để bẻ gãy bã nhờn và tiêu diệt mụn ẩn.'),
(5, 'Glycolic Acid (AHA)', 'Glycolic Acid', 'Tẩy da chết hóa học', 'Loại bỏ tế bào sừng già cỗi bề mặt, thúc đẩy tăng sinh tế bào mới giúp da sáng mịn.'),
(6, 'Vitamin C (E-AA / Pure L-AA)', '3-O-Ethyl Ascorbic Acid', 'Chống oxy hóa mạnh', 'Ức chế melanin hình thành, dưỡng sáng đều màu da và kích thích sản sinh collagen.'),
(7, 'Ceramide NP', 'Ceramide 3', 'Thành phần sinh học thiết yếu', 'Thành phần cấu tạo nên 50% hàng rào bảo vệ da, khóa ẩm và chống thoát nước biểu bì.'),
(8, 'Chiết xuất Bí Đao & Tràm Trà', 'Benincasa Cerifera & Melaleuca', 'Thảo dược lành tính', 'Làm mát dịu vết mụn đỏ, kháng khuẩn tự nhiên mà không gây khô rát.'),
(9, 'Panthenol (Vitamin B5)', 'D-Panthenol', 'Phục hồi cấp tốc', 'Cấp nước, làm lành vết trầy xước vi mô, giảm đỏ rát sau nặn mụn hoặc peel da.'),
(10, 'Chiết xuất Cà Phê Đắk Lắk', 'Coffea Robusta Seed Powder', 'Nông sản Việt Nam thuần chay', 'Chống oxy hóa mạnh mẽ, làm sạch tế bào chết cơ thể, giúp da sáng bóng mịn màng.');

-- 7. PRODUCTS (36 sản phẩm thực tế ăn khớp 100% với file ảnh có sẵn trong images/products/)
INSERT INTO `products` (`product_id`, `category_id`, `brand_id`, `product_name`, `slug`, `original_price`, `price`, `quantity`, `sold_count`, `image`, `description`, `ingredients_text`, `usage_instructions`, `capacity`, `skin_type_summary`, `is_featured`, `is_new`, `is_active`, `rating_avg`, `rating_count`) VALUES
-- 1. Toner & Nước cân bằng
(1, 1, 6, 'Nước Hoa Hồng Không Mùi Klairs Supple Preparation Unscented Toner', 'nuoc-hoa-hong-khong-mui-klairs-180ml', 390000, 315000, 85, 142, '1764435606_klairs.webp', 'Nước hoa hồng cấp ẩm sâu, cân bằng độ pH không cồn, không hương liệu, dành riêng cho làn da nhạy cảm dễ kích ứng.', 'Hyaluronic Acid, Centella Asiatica, Lipidure, Phyto-Oligo', 'Sau khi rửa mặt, đổ một lượng vừa đủ ra bông tẩy trang hoặc lòng bàn tay, vỗ nhẹ đều khắp mặt.', '180ml', 'Da nhạy cảm, Da khô, Mọi loại da', 1, 1, 1, 4.90, 89),

(2, 1, 5, 'Nước Hoa Hồng Dành Cho Da Dầu Mụn Eucerin ProAcne Toner', 'nuoc-hoa-hong-da-mun-eucerin-proacne-200ml', 420000, 349000, 60, 98, '1764438154_toner-eucerin.png', 'Chứa 2% Lactic Acid tự nhiên giúp làm sạch sâu lỗ chân lông, ngăn ngừa vi khuẩn gây mụn mà không làm khô da.', '2% Lactic Acid, Aqua, Alcohol Denat, Butylene Glycol, PEG-40 Hydrogenated Castor Oil', 'Dùng mỗi sáng và tối sau bước rửa mặt, thấm vào bông cotton và lau nhẹ nhàng vùng chữ T.', '200ml', 'Da dầu & mụn, Da hỗn hợp', 1, 0, 1, 4.80, 52),

(3, 1, 7, 'Dung Dịch Dưỡng Ẩm Chuyên Sâu Hada Labo Gokujyun Premium Lotion', 'dung-dich-duong-am-hada-labo-gokujyun-premium-170ml', 330000, 275000, 120, 215, '1764438627_tonerhaladabo.jpg', 'Chứa 7 loại Hyaluronic Acid đa tầng, công thức dưỡng ẩm vàng của Nhật Bản giúp hồi sinh làn da khô cằn tức thì.', '7 loại Hyaluronic Acid (HA, Super HA, Nano HA, 3D HA...), Algae Extract', 'Lấy 3-4 giọt ra lòng bàn tay, xoa ấm và áp nhẹ lên da cho đến khi tinh chất thẩm thấu hoàn toàn.', '170ml', 'Da khô, Da hỗn hợp khô, Da thiếu nước', 1, 0, 1, 4.95, 140),

(4, 1, 8, 'Nước Cân Bằng Cấp Ẩm Đa Tầng Torriden DIVE-IN Low Molecular HA Toner', 'nuoc-can-bang-torriden-dive-in-300ml', 450000, 365000, 75, 180, '1764486719_tori.jpg', 'Dòng toner đoạt giải số 1 Hwahae Hàn Quốc với 5 loại HA phân tử siêu nhỏ len lỏi sâu làm dịu và cấp nước không bết rít.', '5D-Complex Hyaluronic Acid, Allantoin, Malachite Extract, Ceramide NP', 'Thấm đều ra bông toner pad đắp lotion mask trong 3-5 phút để cấp cứu làn da khô ráp tức thì.', '300ml', 'Mọi loại da, Da dầu thiếu ẩm, Da nhạy cảm', 1, 1, 1, 4.90, 115),

(5, 1, 1, 'Nước Cân Bằng Hoa Cúc Làm Dịu Da Cocoon Winter Melon Toner', 'nuoc-can-bang-hoa-cuc-cocoon-140ml', 195000, 165000, 90, 85, '1764252106_tonerhoacc.jpg', 'Nước hoa cúc thuần chay với những cánh hoa cúc tươi ngâm ủ, kết hợp vitamin B5 giúp kháng viêm và se khít chân lông.', 'Chiết xuất hoa cúc Calendula, Tinh chất tràm trà, Vitamin B5, Hyaluronic Acid', 'Dùng sau sữa rửa mặt, vỗ nhẹ trực tiếp lên da ngày 2 lần.', '140ml', 'Da dầu mụn, Da nhạy cảm', 0, 1, 1, 4.75, 48),

(6, 1, 10, 'Nước Cân Bằng Trị Mụn Dịu Da Caryophy Portulaca Toner', 'nuoc-can-bang-tri-mun-caryophy-portulaca-300ml', 420000, 350000, 45, 67, '1764252331_tonercary.png', 'Chiết xuất rau sam và rau má kháng khuẩn, gom cồi mụn nhanh và hạn chế thâm đỏ sau mụn.', 'Portulaca Oleracea (Rau sam), Centella Asiatica (Rau má), Glycerin, Ginseng Extract', 'Thấm ra bông lau sạch mặt hoặc chiết ra chai xịt khoáng dùng dặm lại trong ngày.', '300ml', 'Da dầu mụn, Da hỗn hợp', 0, 0, 1, 4.70, 39),

-- 2. Sữa Rửa Mặt & Làm Sạch
(7, 2, 3, 'Sữa Rửa Mặt Dịu Nhẹ Cho Da Nhạy Cảm Simple Kind to Skin Refreshing Facial Wash', 'sua-rua-mat-diu-nhe-simple-150ml', 145000, 115000, 150, 350, '1764483028_simple.jpeg', 'Công thức 100% không xà phòng, độ pH 5.5 chuẩn sinh học nhẹ nhàng loại bỏ dầu thừa mà không gây khô căng.', 'Pro-Vitamin B5, Vitamin E, Nước tinh lọc 3 lần', 'Làm ướt mặt, lấy lượng gel vừa đủ tạo bọt nhẹ và massage đều trong 60 giây, rửa sạch với nước ấm.', '150ml', 'Da nhạy cảm, Da thường, Da khô', 1, 0, 1, 4.85, 230),

(8, 2, 4, 'Gel Rửa Mặt Tạo Bọt Dành Cho Da Dầu Mụn Bioderma Sébium Gel Moussant', 'gel-rua-mat-bioderma-sebium-200ml', 380000, 295000, 80, 160, '1764483183_bio.jpg', 'Phức hợp Fluidactiv được cấp bằng sáng chế giúp điều hòa chất lượng bã nhờn, làm sạch sâu và ngăn mụn tái phát.', 'Kẽm Gluconate, Đồng Sulfate, Ginkgo Biloba, Phức hợp D.A.F tự nhiên', 'Dùng mỗi ngày 2 lần sáng và tối, tạo bọt mịn trước khi áp lên da.', '200ml', 'Da dầu & mụn, Da hỗn hợp thiên dầu', 1, 0, 1, 4.90, 145),

(9, 2, 1, 'Gel Rửa Mặt Bí Đao Giảm Dầu Mụn Thuần Chay Cocoon', 'gel-rua-mat-bi-dao-cocoon-140ml', 195000, 165000, 110, 210, '1764483700_cocoon.jpg', 'Chiết xuất bí đao tươi mát, tràm trà và rau má giúp kháng khuẩn, giảm bít tắc và ngăn mụn sưng đỏ hiệu quả.', 'Chiết xuất bí đao, Tinh dầu tràm trà hữu cơ, Rau má, Vitamin B3', 'Lấy một lượng gel cỡ hạt đậu, tạo bọt nhẹ nhàng mát-xa khắp khuôn mặt và rửa sạch.', '140ml', 'Da dầu mụn, Da hỗn hợp', 1, 1, 1, 4.80, 178),

(10, 2, 11, 'Sữa Rửa Mặt Cấp Ẩm Cho Da Khô Đến Da Thường CeraVe Hydrating Cleanser', 'sua-rua-mat-cap-am-cerave-hydrating-473ml', 450000, 385000, 70, 195, '1764485169_cera.jpg', 'Công nghệ MVE giải phóng dưỡng chất suốt 24h kết hợp 3 Ceramide thiết yếu giúp củng cố màng ẩm sinh học.', 'Ceramide 1, 3, 6-II, Hyaluronic Acid, Glycerin, Cholesterol', 'Thoa trực tiếp lên da ẩm, massage nhẹ theo chuyển động tròn và rửa sạch lại với nước.', '473ml', 'Da khô, Da thường, Da nhạy cảm', 1, 0, 1, 4.95, 160),

(11, 2, 11, 'Sữa Rửa Mặt Tạo Bọt Cho Da Dầu Mụn CeraVe Foaming Facial Cleanser', 'sua-rua-mat-tao-bot-cerave-foaming-473ml', 450000, 385000, 65, 220, '1764485217_xanh.jpg', 'Tạo bọt nhẹ nhàng cuốn trôi dầu nhờn dư thừa và cặn trang điểm, đồng thời bổ sung Niacinamide làm dịu da tức thì.', 'Ceramide NP, AP, EOP, Niacinamide, Hyaluronic Acid', 'Lấy một lượng nhỏ tạo bọt cùng nước, mát-xa nhẹ nhàng và xả sạch.', '473ml', 'Da dầu, Da mụn, Da hỗn hợp thiên dầu', 1, 0, 1, 4.90, 185),

(12, 2, 9, 'Sữa Rửa Mặt Đậu Xanh Làm Sạch Sâu Lỗ Chân Lông Beplain Greenful Foam', 'sua-rua-mat-dau-xanh-beplain-greenful-160ml', 350000, 289000, 85, 130, '1764486147_beplain.jpg', 'Chiết xuất hơn 32% bột đậu xanh hữu cơ nguyên chất giúp hút sạch bụi mịn và bã nhờn mà cực kỳ êm dịu.', 'Bột đậu xanh nguyên chất, Chiết xuất trà xanh, Allantoin, Glycerin', 'Tạo bọt với nước ấm, massage nhẹ vùng chữ T và hai bên má trong 1 phút.', '160ml', 'Mọi loại da, Da có mụn đầu đen, Da dầu', 0, 1, 1, 4.85, 95),

-- 3. Nước Tẩy Trang & Làm Sạch Sâu
(13, 2, 4, 'Nước Tẩy Trang Dành Cho Da Nhạy Cảm Bioderma Sensibio H2O Nắp Hồng', 'nuoc-tay-trang-bioderma-sensibio-h2o-500ml', 490000, 395000, 130, 420, '1764485264_bio.jpg', 'Nước tẩy trang micellar số 1 thế giới được các chuyên gia da liễu khuyên dùng cho mọi làn da mẫn cảm nhất.', 'Hạt Micelle tinh khiết, Chiết xuất dưa leo làm dịu, Nước tinh khiết chuẩn dược điển', 'Thấm ướt bông tẩy trang, nhẹ nhàng lau khắp mặt và cổ cho đến khi miếng bông trắng sạch hoàn toàn.', '500ml', 'Da nhạy cảm, Da mụn, Mọi loại da', 1, 0, 1, 5.00, 310),

(14, 2, 12, 'Nước Tẩy Trang Làm Sạch Dịu Nhẹ Garnier Micellar Cleansing Water', 'nuoc-tay-trang-garnier-micellar-hong-400ml', 180000, 139000, 100, 280, '1764483523_garnie.jpg', 'Công nghệ Micelles hoạt động như nam châm hút sạch bụi bẩn và cặn trang điểm trong 1 bước duy nhất.', 'Micellar Technology, Glycerin, Hexylene Glycol, Không cồn, Không paraben', 'Lau nhẹ nhàng từ trong ra ngoài, không cần chà xát mạnh hay rửa lại với nước.', '400ml', 'Da nhạy cảm, Da thường, Da khô', 0, 0, 1, 4.80, 190),

(15, 2, 2, 'Nước Tẩy Trang Làm Sạch Sâu Đa Năng L\'Oréal Paris Micellar Deep Cleansing', 'nuoc-tay-trang-loreal-paris-micellar-400ml', 240000, 185000, 95, 240, '1764483437_loreal.jpg', 'Kết cấu 2 lớp bi-phase đột phá làm sạch sâu cả lớp trang điểm chống thấm nước cứng đầu nhất chỉ trong vài giây.', 'Lớp nước Micellar và lớp dầu khoáng tinh lọc, Glycerin dưỡng mềm da', 'Lắc đều trước khi sử dụng để 2 lớp dầu và nước hòa quyện, lau sạch bằng bông cotton.', '400ml', 'Mọi loại da, Người hay trang điểm chống trôi', 0, 0, 1, 4.85, 170),

(16, 2, 1, 'Tẩy Tế Bào Chết Cơ Thể Cà Phê Đắk Lắk Cocoon Body Polish', 'tay-da-chet-body-ca-phe-cocoon-200ml', 165000, 135000, 140, 380, '1764483754_coco.jpg', 'Hạt cà phê Đắk Lắk xay nhuyễn kết hợp bơ ca cao Tiền Giang giúp lấy đi tế bào sừng chết, làm sáng mịn da toàn thân.', 'Cà phê nguyên chất Đắk Lắk, Bơ ca cao Tiền Giang, Dầu dừa Bến Tre, Vitamin E', 'Thoa đều lên da cơ thể ướt, mát-xa nhẹ nhàng theo hình tròn từ 5-10 phút rồi tắm sạch lại.', '200ml', 'Mọi loại da cơ thể, Da viêm nang lông', 1, 0, 1, 4.95, 290),

-- 4. Serum & Tinh Chất Phục Hồi
(17, 3, 2, 'Serum Cấp Ẩm Căng Mọng Da L\'Oréal Paris Revitalift Pure HA 1.5%', 'serum-loreal-revitalift-pure-ha-30ml', 499000, 389000, 90, 290, '1764226232_loreal.jpg', 'Phức hợp 1.5% Hyaluronic Acid kép bao gồm Macro HA và Micro HA cấp nước tức thì và giảm nếp nhăn mảnh.', '1.5% Pure Hyaluronic Acid, Glycerin, Hydroxyethylpiperazine Ethane Sulfonic Acid', 'Nhỏ 3-4 giọt lên mặt và cổ, vỗ đều cho dưỡng chất thẩm thấu trước bước kem dưỡng.', '30ml', 'Da khô, Da lão hóa sớm, Da thiếu ẩm', 1, 1, 1, 4.90, 210),

(18, 3, 10, 'Tinh Chất Rau Má Làm Dịu Da Skin1004 Madagascar Centella Ampoule', 'tinh-chat-rau-ma-skin1004-centella-ampoule-100ml', 480000, 365000, 115, 340, '1764483365_skin.jpg', 'Chứa 100% chiết xuất rau má cô đặc từ Madagascar giúp kháng viêm, làm dịu da mụn đỏ và tăng cường hàng rào ẩm.', '100% Centella Asiatica Extract Madagascar', 'Dùng sau bước toner, nhỏ vài giọt và massage nhẹ nhàng khắp mặt ngày 2 lần.', '100ml', 'Da dầu mụn, Da nhạy cảm, Da kích ứng', 1, 1, 1, 4.95, 275),

(19, 3, 8, 'Tinh Chất Cấp Nước Phục Hồi Torriden DIVE-IN Low Molecule HA Serum', 'tinh-chat-torriden-dive-in-ha-serum-50ml', 460000, 375000, 85, 260, '1764483308_tori.jpg', 'Serum dưỡng ẩm quốc dân Hàn Quốc với màu xanh ngọc bích tự nhiên từ khoáng chất Malachite, cấp nước không bóng nhờn.', '5D Complex Hyaluronic Acid, D-Panthenol, Allantoin, Ceramide NP, Malachite Extract', 'Bơm 1-2 lần tinh chất thoa đều lên da sau bước làm sạch và toner.', '50ml', 'Da dầu thiếu nước, Da hỗn hợp, Da nhạy cảm', 1, 1, 1, 4.90, 195),

(20, 3, 12, 'Tinh Chất Dưỡng Sáng Mờ Thâm Garnier Bright Complete 30x Vitamin C', 'tinh-chat-duong-sang-garnier-vitamin-c-30ml', 320000, 249000, 95, 210, '1764485625_ga.jpg', 'Công thức đậm đặc gấp 30 lần Vitamin C kết hợp Niacinamide giúp mờ thâm mụn chỉ sau 3 ngày sử dụng đều đặn.', '30x Vitamin C Booster, Niacinamide, Yuzu Lemon Extract, Salicylic Acid', 'Dùng 3-4 giọt mát-xa đều lên mặt, chú ý thoa kem chống nắng vào ban ngày.', '30ml', 'Da thâm mụn, Da xỉn màu không đều màu', 1, 0, 1, 4.75, 160),

(21, 3, 2, 'Serum Dưỡng Sáng Mờ Nám L\'Oréal Paris Glycolic-Bright Instant Glowing', 'serum-loreal-glycolic-bright-30ml', 450000, 349000, 70, 150, '1764485672_loreal.jpg', 'Chứa 1% Glycolic Acid (AHA) tinh khiết giúp đẩy lùi hắc sắc tố melanin, làm sáng rạng rỡ bề mặt da nhanh chóng.', '1% Glycolic Acid (AHA), Niacinamide, Symwhite, Vitamin C Derivative', 'Thoa vào buổi tối sau bước làm sạch, khóa lại bằng kem dưỡng ẩm.', '30ml', 'Da nám, Da tàn nhang, Da sạm màu', 0, 1, 1, 4.80, 110),

(22, 3, 1, 'Tinh Chất Nghệ Hưng Yên Dưỡng Sáng Đều Màu Da Cocoon', 'tinh-chat-nghe-hung-yen-cocoon-30ml', 325000, 275000, 60, 130, '1764486214_caro.png', 'Dầu nghệ Hưng Yên giàu curcuminoid kết hợp 4% Vitamin C thế hệ mới và 10% Niacinamide dưỡng sáng da lành tính.', 'Chiết xuất củ nghệ Hưng Yên, 4% Vitamin C (EAA), 10% Niacinamide, 2% NAG', 'Dùng sáng và tối sau bước rửa mặt và lau khô, vỗ nhẹ cho tinh chất thấm sâu.', '30ml', 'Da thâm sau mụn, Da xỉn màu', 0, 1, 1, 4.85, 92),

-- 5. Kem Dưỡng & Cấp Ẩm
(23, 4, 11, 'Kem Dưỡng Ẩm Chuyên Sâu Dành Cho Da Khô CeraVe Moisturizing Cream', 'kem-duong-am-cerave-moisturizing-cream-454g', 490000, 420000, 50, 170, '1764486638_u.jpg', 'Chất kem giàu dưỡng chất củng cố hàng rào bảo vệ tự nhiên, dưỡng ẩm suốt 48h cho cả mặt và toàn thân.', '3 Ceramides thiết yếu (1, 3, 6-II), Hyaluronic Acid, Petrolatum', 'Thoa một lượng kem vừa đủ lên vùng da khô rát, massage đều cho kem thẩm thấu.', '454g', 'Da khô, Da nứt nẻ, Da vảy nến dị ứng', 1, 0, 1, 4.95, 140),

(24, 4, 8, 'Kem Dưỡng Dạng Gel Làm Dịu Da Torriden DIVE-IN Soothing Cream', 'kem-duong-torriden-dive-in-soothing-cream-100ml', 460000, 375000, 75, 190, '1764485770_tori.jpg', 'Kết cấu dạng gel nước tan trên da ngay lập tức, làm mát dịu và không gây bí tắc chân lông cho làn da dầu mụn.', '5D Hyaluronic Acid, Trehalose, Panthenol, Allantoin, Chiết xuất rau sam', 'Lấy lượng kem vừa đủ thoa đều khắp mặt và cổ ở bước cuối cùng của chu trình skincare.', '100ml', 'Da dầu mụn, Da hỗn hợp thiên dầu', 1, 1, 1, 4.90, 165),

(25, 4, 1, 'Thạch Hoa Hồng Dưỡng Ẩm Căng Mọng Cocoon True Rose Aqua Gel', 'thach-hoa-hong-duong-am-cocoon-30ml', 215000, 185000, 80, 120, '1764486917_cocoon.png', 'Thạch hoa hồng hữu cơ Cao Bằng ngậm nước, bổ sung amino acid và HA giúp da căng mướt như sương sớm.', 'Nước cất hoa hồng hữu cơ Cao Bằng, Hyaluronic Acid, Pentavitin, Ectoin', 'Thoa đều lên mặt vào mỗi buổi sáng và tối, làm lớp lót trang điểm mềm mượt.', '30ml', 'Da khô, Da mất nước, Da thường', 0, 1, 1, 4.80, 88),

(26, 4, 2, 'Kem Dưỡng Siêu Cấp Ẩm L\'Oréal Paris Revitalift Hyaluronic Acid Cream', 'kem-duong-loreal-revitalift-hyaluronic-acid-50ml', 420000, 329000, 65, 140, '1764487033_loreal.jpeg', 'Khóa ẩm siêu việt giúp làm đầy rãnh nhăn nông, mang lại làn da bóng khỏe và căng tràn sức sống.', 'Micro Hyaluronic Acid, Chiết xuất hạt lúa mì tự nhiên, Glycerin', 'Thoa kem lên 5 điểm trên mặt (trán, 2 má, mũi, cằm) rồi massage từ trong ra ngoài.', '50ml', 'Da khô, Da lão hóa, Da hỗn hợp', 0, 0, 1, 4.85, 115),

-- 6. Kem Chống Nắng
(27, 5, 10, 'Kem Chống Nắng Rau Má Nâng Tông Tự Nhiên Skin1004 Air-Fit Suncream SPF50+', 'kem-chong-nang-rau-ma-skin1004-air-fit-50ml', 390000, 295000, 95, 310, '1764485987_cen.jpeg', 'Kem chống nắng vật lý mỏng nhẹ, chiết xuất rau má làm dịu, kiềm dầu tốt và nâng tông trắng hồng nhẹ nhàng.', 'Chiết xuất rau má Madagascar, Zinc Oxide, Titanium Dioxide, Niacinamide', 'Thoa trước khi ra ngoài 20 phút. Thoa lại sau mỗi 3-4 tiếng nếu hoạt động ngoài trời.', '50ml', 'Da dầu mụn, Da nhạy cảm', 1, 1, 1, 4.90, 240),

(28, 5, 9, 'Kem Chống Nắng Vật Lý Dịu Nhẹ Beplain Sunmuse Mineral Sunscreen SPF50+', 'kem-chong-nang-vat-ly-beplain-sunmuse-50ml', 420000, 345000, 70, 160, '1764486052_beplain.jpg', 'Chống nắng 100% khoáng chất non-nano an toàn cho mẹ bầu và da nhạy cảm nhất, kháng nước và mồ hôi tối ưu.', 'Non-Nano Zinc Oxide, Chiết xuất cải xoăn Kale, Bắp cải tím, Dầu hạt chùm ngây', 'Chấm đều các điểm trên mặt và vỗ nhẹ cho lớp chống nắng tệp vào da.', '50ml', 'Da nhạy cảm, Da điều trị treatment, Da khô', 0, 1, 1, 4.85, 125),

(29, 5, 12, 'Kem Chống Nắng Mỏng Nhẹ Garnier Super UV Invisible Sunscreen SPF50+', 'kem-chong-nang-garnier-super-uv-30ml', 249000, 199000, 80, 180, '1764485872_gar.jpg', 'Chống nắng quang phổ rộng bảo vệ hoàn hảo trước tia UVA/UVB với kết cấu thoáng nhẹ không để lại vệt trắng.', 'Màng lọc Mexoryl SX/XL, Vitamin C, Vitamin E, Glycerin', 'Lắc đều trước khi bôi, dùng hàng ngày mỗi buổi sáng.', '30ml', 'Mọi loại da, Da dầu', 0, 0, 1, 4.75, 130),

-- 7. Mặt Nạ Dưỡng Da
(30, 6, 8, 'Mặt Nạ Cấp Ẩm Phục Hồi Chuyên Sâu Wonjin Effect Hydro Vial Mask', 'mat-na-cap-am-wonjin-effect-hydro-vial-hop-10-mieng', 350000, 265000, 90, 240, '1764486346_wonjin.jpeg', 'Hộp 10 miếng mặt nạ chứa 6 loại Hyaluronic Acid và phức hợp SK-Collagen phục hồi da sau tổn thương cực đỉnh.', '6 loại Hyaluronic Acid đa kích thước, Collagen thủy phân, Chiết xuất cúc la mã', 'Đắp mặt nạ từ 15-20 phút, tháo ra vỗ nhẹ tinh chất còn lại cho thấm đều vào da.', 'Hộp 10 miếng', 'Da khô rát, Da sau nặn mụn, Da thiếu ẩm', 1, 0, 1, 4.95, 195),

(31, 6, 8, 'Mặt Nạ Vitamin Cấp Ẩm BNBG Vita Genic Hydrating Jelly Mask', 'mat-na-vitamin-cap-am-bnbg-hop-10-mieng', 280000, 195000, 110, 310, '1764486296_bnbg.jpg', 'Chứa hàm lượng vitamin đậm đặc 40.000ppm cùng khoáng chất đảo Jeju giúp da ngậm nước căng bóng tức thì.', 'Vitamin Complex 40,000ppm, Khoáng chất nước ngầm đảo Jeju, Niacinamide, Adenosine', 'Rửa mặt sạch, mở gói lấy mặt nạ đắp vừa vặn lên mặt trong 15-20 phút.', 'Hộp 10 miếng', 'Mọi loại da, Da mệt mỏi thiếu sức sống', 0, 0, 1, 4.85, 230),

(32, 6, 1, 'Mặt Nạ Đất Sét Thu Nhỏ Lỗ Chân Lông Kiehl\'s Rare Earth Masque', 'mat-na-dat-set-kiehls-rare-earth-125ml', 890000, 750000, 40, 95, '1764486252_no.jpg', 'Đất sét trắng Amazon quý hiếm hút dầu thừa, độc tố và bụi mịn, làm sạch sâu và thu nhỏ lỗ chân lông rõ rệt.', 'Amazonian White Clay (Đất sét trắng Amazon), Chiết xuất lô hội tự nhiên, Bột yến mạch', 'Thoa một lớp mỏng lên da ẩm sau khi rửa mặt, để khô 10 phút rồi rửa sạch với nước ấm (dùng 1-2 lần/tuần).', '125ml', 'Da dầu, Da có mụn đầu đen, Da lỗ chân lông to', 1, 1, 1, 4.95, 80),

(33, 6, 3, 'Bông Dưỡng Da Cấp Ẩm Làm Dịu Skinfood Carrot Calming Water Pad', 'bong-duong-da-carot-skinfood-pad-60-mieng', 460000, 375000, 65, 140, '1764483105_pad.jpg', 'Hộp 60 miếng pad cotton dày dặn tẩm đẫm chiết xuất dầu hạt cà rốt giàu Beta-Carotene làm dịu da tức thì.', 'Chiết xuất rễ cà rốt hữu cơ Jeju, Beta-Carotene tự nhiên, Glycerin, Allantoin', 'Sau khi rửa mặt, dùng 2 miếng pad đắp lên 2 bên má và trán trong 5-10 phút như mặt nạ cấp ẩm mini.', 'Hộp 60 miếng', 'Da nhạy cảm, Da đỏ rát, Da dầu', 0, 1, 1, 4.90, 112),

-- 8. Son Môi & Trang Điểm
(34, 7, 6, 'Son Kem Lì Mịn Môi Romand Zero Velvet Tint - Màu 06 Deepsoul', 'son-kem-li-romand-zero-velvet-tint-06-deepsoul', 220000, 169000, 100, 320, '1764483867_color.jpeg', 'Chất son xốp mịn lướt nhẹ trên môi như lớp nhung, che mờ rãnh môi hoàn hảo cùng tông đỏ nâu trầm quyến rũ.', 'Dimethicone, Dimethicone Crosspolymer, Polyglyceryl-2 Triisostearate, Hạt màu tự nhiên', 'Thoa lòng môi hoặc thoa đầy môi tùy sở thích. Dùng cọ tán đều viền môi để tạo hiệu ứng mờ ảo.', '5.5g', 'Mọi loại da, Phù hợp mọi tone da', 1, 0, 1, 4.90, 260),

(35, 7, 6, 'Son Tint Bóng Thuần Chay Romand Glasting Water Tint - Màu 04 Vintage Ocean', 'son-tint-bong-romand-glasting-water-tint-04', 230000, 179000, 85, 270, '1764486584_son.jpeg', 'Lớp màng bóng trong suốt như nước tách biệt với lớp màu bên dưới, giúp môi căng mọng quyến rũ không bết dính.', 'Nước tinh khiết, Dầu hạt hướng dương, Dầu hạnh nhân, Hạt màu hữu cơ', 'Thoa một lượng vừa phải lên môi, đợi 3-5 giây để lớp bóng nước tự nhiên nổi lên bề mặt.', '4g', 'Mọi loại da môi', 0, 1, 1, 4.85, 210),

(36, 7, 6, 'Son Thỏi Lì Cao Cấp 3CE Mood Recipe Matte Lip Color - Màu 220 Hit Me Up', 'son-thoi-li-3ce-mood-recipe-220-hit-me-up', 380000, 310000, 60, 150, '1764486534_hongcung.jpg', 'Màu cam cháy thời thượng, chất son lì mịn không bột, giữ màu chuẩn đẹp suốt cả ngày dài năng động.', 'Silica, Isononyl Isononanoate, Microcrystalline Wax, Pigment Red', 'Thoa trực tiếp lên môi từ tâm môi ra hai bên khóe môi.', '3.5g', 'Mọi loại da môi', 1, 0, 1, 4.90, 130);

-- 8. PRODUCT_SKIN_TYPES (Mối quan hệ Sản phẩm - Loại da)
INSERT INTO `product_skin_types` (`product_id`, `skin_type_id`) VALUES
-- Klairs Toner
(1, 2), (1, 3), (1, 5),
-- Eucerin Toner
(2, 1), (2, 4),
-- Hada Labo Lotion
(3, 2), (3, 3), (3, 5),
-- Torriden Toner
(4, 1), (4, 2), (4, 3), (4, 4), (4, 5),
-- Cocoon Toner
(5, 1), (5, 3), (5, 4),
-- Caryophy Toner
(6, 1), (6, 4),
-- Simple Cleanser
(7, 2), (7, 3), (7, 5),
-- Bioderma Gel
(8, 1), (8, 4),
-- Cocoon Cleanser
(9, 1), (9, 3), (9, 4),
-- CeraVe Hydrating
(10, 2), (10, 3), (10, 5),
-- CeraVe Foaming
(11, 1), (11, 4),
-- Beplain Foam
(12, 1), (12, 4), (12, 5),
-- Bioderma Micellar
(13, 1), (13, 2), (13, 3), (13, 4), (13, 5),
-- Garnier Micellar
(14, 2), (14, 3), (14, 5),
-- L'Oreal Micellar
(15, 1), (15, 4), (15, 5),
-- Cocoon Body
(16, 1), (16, 2), (16, 5),
-- L'Oreal HA Serum
(17, 2), (17, 3), (17, 5),
-- Skin1004 Centella
(18, 1), (18, 3), (18, 4),
-- Torriden HA Serum
(19, 1), (19, 2), (19, 3), (19, 4), (19, 5),
-- Garnier Vit C
(20, 1), (20, 4), (20, 5),
-- L'Oreal Glycolic
(21, 2), (21, 4), (21, 5),
-- Cocoon Turmeric
(22, 1), (22, 4), (22, 5),
-- CeraVe Cream
(23, 2), (23, 3),
-- Torriden Cream
(24, 1), (24, 3), (24, 4),
-- Cocoon Rose
(25, 2), (25, 3), (25, 5),
-- L'Oreal Cream
(26, 2), (26, 5),
-- Skin1004 Sunscreen
(27, 1), (27, 3), (27, 4),
-- Beplain Sunscreen
(28, 2), (28, 3), (28, 5),
-- Garnier Sunscreen
(29, 1), (29, 4), (29, 5),
-- Wonjin Mask
(30, 2), (30, 3), (30, 5),
-- BNBG Mask
(31, 1), (31, 2), (31, 5),
-- Kiehl's Mask
(32, 1), (32, 4),
-- Carrot Pad
(33, 1), (33, 3), (33, 4);

-- 9. PRODUCT_INGREDIENTS (Mối quan hệ Sản phẩm - Thành phần)
INSERT INTO `product_ingredients` (`product_id`, `ingredient_id`) VALUES
(1, 2), (1, 3),
(2, 4),
(3, 2),
(4, 2), (4, 7), (4, 9),
(5, 8), (5, 9),
(6, 3), (6, 8),
(7, 9),
(8, 1), (8, 4),
(9, 3), (9, 8),
(10, 2), (10, 7),
(11, 1), (11, 2), (11, 7),
(12, 1),
(13, 2),
(14, 2),
(15, 2),
(16, 10),
(17, 2),
(18, 3),
(19, 2), (19, 7), (19, 9),
(20, 1), (20, 6), (20, 4),
(21, 5), (21, 1), (21, 6),
(22, 1), (22, 6),
(23, 2), (23, 7),
(24, 2), (24, 9),
(25, 2),
(26, 2),
(27, 3), (27, 1),
(28, 7), (28, 9),
(29, 6),
(30, 2), (30, 9),
(31, 1), (31, 6),
(32, 5),
(33, 3), (33, 9);

-- 10. COUPONS
INSERT INTO `coupons` (`id`, `code`, `description`, `discount_type`, `discount_value`, `min_order_amount`, `max_discount_amount`, `start_date`, `end_date`, `usage_limit`, `used_count`, `is_active`) VALUES
(1, 'BEAUTYNEW', 'Giảm ngay 10% cho khách hàng mới', 'percent', 10.00, 200000.00, 50000.00, '2025-01-01 00:00:00', '2027-12-31 23:59:59', 500, 12, 1),
(2, 'FREESHIP', 'Miễn phí vận chuyển toàn quốc', 'fixed', 30000.00, 150000.00, 30000.00, '2025-01-01 00:00:00', '2027-12-31 23:59:59', 1000, 45, 1),
(3, 'XINCHAO50', 'Giảm trực tiếp 50.000₫ cho đơn từ 300.000₫', 'fixed', 50000.00, 300000.00, 50000.00, '2025-01-01 00:00:00', '2027-12-31 23:59:59', 200, 28, 1),
(4, 'VIP20', 'Đặc quyền thành viên VIP giảm 20%', 'percent', 20.00, 500000.00, 120000.00, '2025-01-01 00:00:00', '2027-12-31 23:59:59', 100, 8, 1),
(5, 'FLASH15', 'Mã ưu đãi Flash Sale giảm 15%', 'percent', 15.00, 250000.00, 80000.00, '2025-01-01 00:00:00', '2027-12-31 23:59:59', 300, 19, 1);

-- 11. FLASH_SALES & FLASH_SALE_ITEMS
INSERT INTO `flash_sales` (`id`, `title`, `start_time`, `end_time`, `is_active`) VALUES
(1, 'DEAL XỊN ĐẦU MÙA - GIẢM SỐC ĐẾN 40%', NOW() - INTERVAL 1 DAY, NOW() + INTERVAL 7 DAY, 1);

INSERT INTO `flash_sale_items` (`flash_sale_id`, `product_id`, `flash_price`, `quantity_limit`, `sold_count`) VALUES
(1, 1, 289000.00, 30, 14),
(1, 7, 99000.00, 50, 32),
(1, 13, 355000.00, 40, 21),
(1, 18, 329000.00, 35, 18),
(1, 19, 335000.00, 30, 12),
(1, 27, 269000.00, 25, 9);

-- 12. SAMPLE ORDERS (Tạo đơn hàng mẫu với các trạng thái khác nhau cho thống kê thực tế)
INSERT INTO `orders` (`order_id`, `order_code`, `user_id`, `customer_name`, `customer_phone`, `customer_email`, `customer_address`, `shipping_note`, `payment_method`, `payment_status`, `shipping_fee`, `discount_amount`, `coupon_code`, `total_amount`, `status`, `created_at`) VALUES
(1, 'ORD-20261001-001', 3, 'Nguyễn Lan Anh', '0987654321', 'lananh@gmail.com', '123 Đường Lê Lợi, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh', 'Giao giờ hành chính giúp em', 'cod', 'paid', 30000.00, 30000.00, 'FREESHIP', 710000.00, 'completed', NOW() - INTERVAL 12 DAY),
(2, 'ORD-20261005-002', 4, 'Trần Minh Thư', '0978123456', 'minhthu@gmail.com', '470 Trần Đại Nghĩa, Phường Hòa Quý, Quận Ngũ Hành Sơn, Đà Nẵng', 'Gọi trước khi giao 15 phút', 'bank_transfer', 'paid', 30000.00, 50000.00, 'XINCHAO50', 644000.00, 'completed', NOW() - INTERVAL 7 DAY),
(3, 'ORD-20261010-003', 3, 'Nguyễn Lan Anh', '0987654321', 'lananh@gmail.com', '123 Đường Lê Lợi, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh', 'Giao tầng 5 tòa nhà Sunwah', 'cod', 'unpaid', 30000.00, 0.00, NULL, 660000.00, 'shipping', NOW() - INTERVAL 2 DAY),
(4, 'ORD-20261015-004', 4, 'Trần Minh Thư', '0978123456', 'minhthu@gmail.com', '470 Trần Đại Nghĩa, Phường Hòa Quý, Quận Ngũ Hành Sơn, Đà Nẵng', '', 'cod', 'unpaid', 30000.00, 0.00, NULL, 345000.00, 'confirmed', NOW() - INTERVAL 1 DAY),
(5, 'ORD-20261017-005', 3, 'Nguyễn Lan Anh', '0987654321', 'lananh@gmail.com', '123 Đường Lê Lợi, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh', 'Đóng gói bọc bóng khí cẩn thận', 'cod', 'unpaid', 30000.00, 0.00, NULL, 534000.00, 'pending', NOW());

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `subtotal`, `image`) VALUES
(1, 1, 1, 'Nước Hoa Hồng Không Mùi Klairs Supple Preparation Unscented Toner', 315000.00, 1, 315000.00, '1764435606_klairs.webp'),
(2, 1, 13, 'Nước Tẩy Trang Dành Cho Da Nhạy Cảm Bioderma Sensibio H2O Nắp Hồng', 395000.00, 1, 395000.00, '1764485264_bio.jpg'),
(3, 2, 18, 'Tinh Chất Rau Má Làm Dịu Da Skin1004 Madagascar Centella Ampoule', 365000.00, 1, 365000.00, '1764483365_skin.jpg'),
(4, 2, 27, 'Kem Chống Nắng Rau Má Nâng Tông Tự Nhiên Skin1004 Air-Fit Suncream SPF50+', 295000.00, 1, 295000.00, '1764485987_cen.jpeg'),
(5, 3, 8, 'Gel Rửa Mặt Tạo Bọt Dành Cho Da Dầu Mụn Bioderma Sébium Gel Moussant', 295000.00, 1, 295000.00, '1764483183_bio.jpg'),
(6, 3, 19, 'Tinh Chất Cấp Nước Phục Hồi Torriden DIVE-IN Low Molecule HA Serum', 375000.00, 1, 375000.00, '1764483308_tori.jpg'),
(7, 4, 1, 'Nước Hoa Hồng Không Mùi Klairs Supple Preparation Unscented Toner', 315000.00, 1, 315000.00, '1764435606_klairs.webp'),
(8, 5, 34, 'Son Kem Lì Mịn Môi Romand Zero Velvet Tint - Màu 06 Deepsoul', 169000.00, 1, 169000.00, '1764483867_color.jpeg'),
(9, 5, 18, 'Tinh Chất Rau Má Làm Dịu Da Skin1004 Madagascar Centella Ampoule', 365000.00, 1, 365000.00, '1764483365_skin.jpg');

INSERT INTO `order_status_history` (`order_id`, `old_status`, `new_status`, `note`, `changed_by`) VALUES
(1, 'pending', 'confirmed', 'Admin đã xác nhận đơn hàng', 1),
(1, 'confirmed', 'shipping', 'Bàn giao cho đơn vị vận chuyển Viettel Post', 1),
(1, 'shipping', 'completed', 'Khách hàng đã nhận hàng và thanh toán thành công', 1),
(2, 'pending', 'confirmed', 'Khách hàng chuyển khoản hoàn tất', 1),
(2, 'confirmed', 'shipping', 'Đơn hàng đang trên đường giao', 1),
(2, 'shipping', 'completed', 'Giao hàng thành công', 1),
(3, 'pending', 'confirmed', 'Đã chuẩn bị hàng xong', 1),
(3, 'confirmed', 'shipping', 'Đang giao hàng', 1),
(4, 'pending', 'confirmed', 'Đơn hàng đã được duyệt', 1);

-- 13. SAMPLE REVIEWS
INSERT INTO `reviews` (`review_id`, `product_id`, `user_id`, `rating`, `comment`, `reply`, `replied_at`, `is_approved`, `created_at`) VALUES
(1, 1, 3, 5, 'Toner Klairs bản không mùi này dùng cực kỳ êm luôn! Da mình nhạy cảm dễ đỏ mà dùng xong thấy da dịu hẳn, mềm ẩm rất thích.', 'BeautyShop cảm ơn bạn Lan Anh nhiều nhé! Chúc bạn luôn có làn da rạng rỡ!', NOW() - INTERVAL 10 DAY, 1, NOW() - INTERVAL 11 DAY),
(2, 13, 3, 5, 'Bioderma nắp hồng thì khỏi phải bàn rồi, chai to 500ml dùng được lâu, tẩy trang sạch nhẹ nhàng không cay mắt chút nào. Shop đóng gói bọc kỹ 10 điểm!', 'Cảm ơn bạn đã luôn tin tưởng và ủng hộ sản phẩm chính hãng tại BeautyShop ạ!', NOW() - INTERVAL 9 DAY, 1, NOW() - INTERVAL 10 DAY),
(3, 18, 4, 5, 'Centella của Skin1004 làm dịu mụn sưng viêm đỉnh chóp luôn, chất lỏng nhẹ thấm nhanh không hề bết rít. Đáng tiền lắm mọi người ơi.', 'BeautyShop rất vui khi tinh chất rau má hợp với làn da của bạn. Mong sớm được phục vụ bạn ở các đơn hàng tiếp theo!', NOW() - INTERVAL 5 DAY, 1, NOW() - INTERVAL 6 DAY),
(4, 7, 3, 5, 'Sữa rửa mặt Simple dùng buổi sáng nhẹ dịu, da mềm ẩm không bị khô căng tẹo nào.', NULL, NULL, 1, NOW() - INTERVAL 3 DAY),
(5, 19, 4, 5, 'Serum Torriden cấp nước siêu đỉnh, màu xanh biển đẹp mê, bôi lên da mát rượi, hôm sau ngủ dậy da căng bóng mịn màng.', NULL, NULL, 1, NOW() - INTERVAL 1 DAY);

