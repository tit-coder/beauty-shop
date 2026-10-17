@echo off
chcp 65001 >nul
title Khởi Động Website BeautyShop
color 0D

echo ========================================================
echo        HỆ THỐNG KHỞI ĐỘNG WEBSITE BEAUTYSHOP
echo ========================================================
echo.

:: 1. Kiểm tra MySQL
echo [*] Đang kiểm tra kết nối MySQL (Port 3306)...
netstat -ano | findstr 127.0.0.1:3306 >nul
if %errorlevel% neq 0 (
    echo [!] MySQL chưa chạy. Đang tự động bật MySQL qua XAMPP...
    if exist "C:\xampp\mysql_start.bat" (
        start /b "" "C:\xampp\mysql_start.bat"
        timeout /t 3 >nul
    ) else (
        echo [!] Không tìm thấy C:\xampp\mysql_start.bat. Vui lòng bật MySQL trong XAMPP Control Panel!
    )
) else (
    echo [OK] MySQL đã sẵn sàng hoạt động!
)

:: 2. Tự động kiểm tra / Khởi tạo CSDL nếu cần
echo [*] Kiểm tra dữ liệu database...
"C:\xampp\php\php.exe" "database\setup_db.php" >nul 2>&1

:: 3. Mở trình duyệt web
echo [*] Đang mở trình duyệt đến http://localhost:8000 ...
timeout /t 2 >nul
start http://localhost:8000/

:: 4. Chạy Web Server PHP
echo.
echo ========================================================
echo   WEBSITE ĐANG CHẠY TẠI: http://localhost:8000
echo   TRANG QUẢN TRỊ ADMIN:  http://localhost:8000/php/admin_dashboard.php
echo.
echo   Tài khoản Admin:  admin    / admin123
echo   Tài khoản Khách:  lananh   / customer123
echo.
echo   Nhấn Ctrl + C hoặc đóng cửa sổ này để dừng website.
echo ========================================================
echo.

"C:\xampp\php\php.exe" -S 127.0.0.1:8000 -t "%~dp0."

pause

