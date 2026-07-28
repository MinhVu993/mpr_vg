@echo off
chcp 65001 > nul
echo ==========================================
echo        GIT QUICK COMMIT ^& PUSH TOOL
echo ==========================================
echo.

echo [1/3] Kiem tra trang thai Git...
git status

echo.
set "msg="
set /p msg=Nhap commit message (an Enter de dung mac dinh "update code"): 
if "%msg%"=="" set "msg=update code"

echo.
echo [2/3] Dang add va commit...
git add .
git commit -m "%msg%"

echo.
echo [3/3] Dang push len repo...
git push

echo.
echo ==========================================
echo Direct push hoan thanh!
echo ==========================================
pause
