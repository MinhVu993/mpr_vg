@echo off
:: Thiết lập encoding UTF-8 để hỗ trợ hiển thị tiếng Việt
chcp 65001 > nul

echo ==================================================
echo              HỆ THỐNG CẬP NHẬT GIT
echo ==================================================
echo.

:: 1. Kéo code mới nhất về trước để tránh xung đột
echo [*] Đang kéo code mới nhất từ GitHub (git pull)...
git pull origin main
if %ERRORLEVEL% neq 0 (
    echo [!] Lỗi khi kéo code về. Vui lòng kiểm tra lại xung đột (conflict) hoặc kết nối.
    goto end
)
echo.

:: 2. Thêm tất cả thay đổi vào hàng đợi
echo [*] Đang chuẩn bị các file thay đổi (git add)...
git add .
echo.

:: 3. Nhập nội dung commit
set /p commit_msg="[*] Nhập nội dung commit (Nhấn Enter để dùng mặc định: 'Update code'): "
if "%commit_msg%"=="" set commit_msg=Update code
echo.

:: 4. Commit code
echo [*] Đang commit với nội dung: "%commit_msg%"...
git commit -m "%commit_msg%"
if %ERRORLEVEL% neq 0 (
    echo [!] Không có thay đổi nào được commit hoặc xảy ra lỗi.
    goto end
)
echo.

:: 5. Đẩy code lên remote branch main
echo [*] Đang đẩy code lên GitHub (git push)...
git push origin main
if %ERRORLEVEL% neq 0 (
    echo [!] Đẩy code thất bại.
    goto end
)

echo.
echo ==================================================
echo               CẬP NHẬT THÀNH CÔNG!
echo ==================================================

:end
pause
