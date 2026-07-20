@echo off

echo ==================================================
echo              HE THONG DAY CODE LEN GIT
echo ==================================================
echo.

:: 1. Them tat ca thay doi vao hang doi
echo [*] Dang chuan bi cac file thay doi (git add)...
git add .
echo.

:: 2. Nhap noi dung commit
set /p commit_msg="[*] Nhap noi dung commit (Nhan Enter de dung mac dinh: 'Update code'): "
if "%commit_msg%"=="" set commit_msg=Update code
echo.

:: 3. Commit code
echo [*] Dang commit voi noi dung: "%commit_msg%"...
git commit -m "%commit_msg%"
if %ERRORLEVEL% neq 0 (
    echo [!] Khong co thay doi nao duoc commit hoac xay ra loi.
    goto end
)
echo.

:: 4. Day code len remote branch main
echo [*] Dang day code len GitHub (git push)...
git push origin main
if %ERRORLEVEL% neq 0 (
    echo [!] Day code that bai. Vui long kiem tra lai ket noi hoac quyen truy cap.
    goto end
)

echo.
echo ==================================================
echo               DAY CODE THANH CONG!
echo ==================================================

:end
pause
