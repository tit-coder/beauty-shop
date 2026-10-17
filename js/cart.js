// Hàm thêm sản phẩm vào giỏ hàng
function addToCart(productId, quantity = 1) {
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hiển thị thông báo thành công
            showNotification(data.message, 'success');
            
            // Cập nhật số lượng giỏ hàng trên header
            updateCartCount(data.cart_count);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Có lỗi xảy ra, vui lòng thử lại!', 'error');
    });
}

// Hiển thị thông báo
function showNotification(message, type = 'success') {
    // Xóa thông báo cũ nếu có
    const oldNotif = document.querySelector('.cart-notification');
    if (oldNotif) {
        oldNotif.remove();
    }

    // Tạo thông báo mới
    const notification = document.createElement('div');
    notification.className = `cart-notification ${type}`;
    notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);

    // Hiển thị thông báo
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);

    // Ẩn sau 3 giây
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Cập nhật số lượng giỏ hàng
function updateCartCount(count) {
    const cartBadge = document.querySelector('.cart-count');
    if (cartBadge) {
        cartBadge.textContent = count;
        cartBadge.style.display = count > 0 ? 'inline-block' : 'none';
    }
}

// Lấy số lượng giỏ hàng khi load trang
function loadCartCount() {
    fetch('get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartCount(data.count);
            }
        })
        .catch(error => console.error('Error:', error));
}

// Load cart count khi trang được tải
document.addEventListener('DOMContentLoaded', function() {
    loadCartCount();
});
