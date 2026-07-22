#!/bin/bash

echo "=================================================="
echo "             HỆ THỐNG ĐẨY CODE LÊN GIT"
echo "=================================================="
echo ""

# 1. Thêm tất cả thay đổi vào hàng đợi
echo "[*] Đang chuẩn bị các file thay đổi (git add)..."
git add .
echo ""

# 2. Nhập nội dung commit
read -p "[*] Nhập nội dung commit (Nhấn Enter để dùng mặc định: 'Update code'): " commit_msg
if [ -z "$commit_msg" ]; then
    commit_msg="Update code"
fi
echo ""

# 3. Commit code
echo "[*] Đang commit với nội dung: \"$commit_msg\"..."
git commit -m "$commit_msg"
if [ $? -ne 0 ]; then
    echo "[!] Không có thay đổi nào được commit hoặc xảy ra lỗi."
    exit 1
fi
echo ""

# 4. Đẩy code lên các remote
echo "[*] Đang đẩy code lên GitHub của MinhVu993..."
git push https://github.com/MinhVu993/mpr.git main
if [ $? -ne 0 ]; then
    echo "[!] Đẩy code lên MinhVu993 thất bại."
fi

echo ""
echo "[*] Đang đẩy code lên GitHub của SPG-VN..."
git push https://github.com/SPG-VN/mpr_vg.git main
if [ $? -ne 0 ]; then
    echo "[!] Đẩy code lên SPG-VN thất bại."
fi

echo ""
echo "=================================================="
echo "               ĐẨY CODE THÀNH CÔNG!"
echo "=================================================="
