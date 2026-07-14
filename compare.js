const fs = require('fs');
const xlsx = require('xlsx');

const csvPath = 'c:\\Users\\vu.huynh\\Desktop\\New folder\\deputies.csv';
const excelPath = 'c:\\Users\\vu.huynh\\Desktop\\New folder\\Copy of DL CNV NGUOI THAY THE  01.2026.xlsx';

function main() {
    console.log("Đang đọc file deputies.csv...");
    
    // Đọc CSV
    const csvWorkbook = xlsx.readFile(csvPath);
    const csvSheet = csvWorkbook.Sheets[csvWorkbook.SheetNames[0]];
    const csvData = xlsx.utils.sheet_to_json(csvSheet);
    
    const deputiesMap = new Map();
    
    // Parse dữ liệu từ CSV
    csvData.forEach((row) => {
        ['first_dep', 'second_dep'].forEach(col => {
            const val = row[col];
            if (val) {
                try {
                    const deps = JSON.parse(val);
                    deps.forEach(dep => {
                        if (dep.empno) {
                            deputiesMap.set(String(dep.empno).trim(), {
                                name: dep.name,
                                viPos: dep.viPos,
                                type: col,
                                row_id: row.id
                            });
                        }
                    });
                } catch (e) {
                    // Bỏ qua lỗi parse nếu có
                }
            }
        });
    });
    
    console.log(`Đã trích xuất ${deputiesMap.size} nhân sự (first_dep/second_dep) từ file CSV.`);
    
    console.log("\nĐang đọc file Excel (Copy of DL CNV NGUOI THAY THE  01.2026.xlsx)...");
    let excelWorkbook;
    try {
        excelWorkbook = xlsx.readFile(excelPath);
    } catch (err) {
        console.error("Lỗi khi đọc file Excel. Vui lòng đảm bảo file đang không được mở bởi chương trình khác.", err.message);
        return;
    }

    const excelSheet = excelWorkbook.Sheets[excelWorkbook.SheetNames[0]];
    // Đọc theo dạng mảng 2 chiều để dễ lấy cột G
    const excelData = xlsx.utils.sheet_to_json(excelSheet, { header: 1 }); 
    
    // Tìm cột chứa mã nhân viên (do không chắc chắn tên cột)
    let empnoColIdx = -1;
    let maxMatches = 0;
    
    const maxCols = excelData.reduce((max, row) => Math.max(max, row.length), 0);
    
    for (let c = 0; c < maxCols; c++) {
        let matches = 0;
        for (let r = 0; r < excelData.length; r++) {
            if (excelData[r] && excelData[r][c] !== undefined) {
                const val = String(excelData[r][c]).trim();
                if (deputiesMap.has(val)) {
                    matches++;
                }
            }
        }
        if (matches > maxMatches) {
            maxMatches = matches;
            empnoColIdx = c;
        }
    }
    
    if (empnoColIdx === -1) {
        console.error("Không tìm thấy cột Mã nhân viên trong file Excel có dữ liệu khớp với deputies.csv.");
        return;
    }
    
    console.log(`Đã tự động tìm thấy cột [Mã nhân viên] ở cột: ${String.fromCharCode(65 + empnoColIdx)}`);
    console.log(`Tiến hành so sánh với [Cột G] chức vụ trong Excel...`);
    
    const chucVuColIdx = 6; // Cột G tương ứng với index 6 (A=0, B=1, ... G=6)
    const mismatches = [];
    let matchedCount = 0;
    
    for (let r = 0; r < excelData.length; r++) {
        const row = excelData[r];
        if (!row || row[empnoColIdx] === undefined) continue;
        
        const empno = String(row[empnoColIdx]).trim();
        if (deputiesMap.has(empno)) {
            const csvDep = deputiesMap.get(empno);
            const csvPos = String(csvDep.viPos || "").trim();
            const excelPos = String(row[chucVuColIdx] || "").trim();
            
            // So sánh không phân biệt hoa thường và khoảng trắng
            if (csvPos.toLowerCase() !== excelPos.toLowerCase()) {
                mismatches.push({
                    empno: empno,
                    name: csvDep.name,
                    csvPos: csvPos,
                    excelPos: excelPos,
                    type: csvDep.type
                });
            } else {
                matchedCount++;
            }
        }
    }
    
    console.log(`\n--- KẾT QUẢ ĐỐI CHIẾU ---`);
    console.log(`Số lượng nhân sự khớp chức vụ: ${matchedCount}`);
    console.log(`Số lượng nhân sự KHÔNG KHỚP : ${mismatches.length}\n`);
    
    if (mismatches.length > 0) {
        console.log("=== CHI TIẾT DANH SÁCH DỮ LIỆU KHÔNG KHỚP ===");
        mismatches.forEach(m => {
            console.log(`Mã NV: ${m.empno} | Tên: ${m.name} (${m.type})`);
            console.log(`  - Chức vụ trong deputies.csv (viPos): ${m.csvPos}`);
            console.log(`  - Chức vụ trong Excel (cột G)       : ${m.excelPos}`);
            console.log("-".repeat(60));
        });
        
        // Lưu ra file báo cáo
        const reportPath = 'c:\\Users\\vu.huynh\\Desktop\\New folder\\mismatch_report.txt';
        let reportContent = "Danh sách không khớp chức vụ giữa CSV và Excel:\n\n";
        mismatches.forEach(m => {
            reportContent += `Mã NV: ${m.empno} | Tên: ${m.name} (${m.type})\n`;
            reportContent += `  - CSV (viPos): ${m.csvPos}\n`;
            reportContent += `  - Excel (cột G): ${m.excelPos}\n`;
            reportContent += "-".repeat(60) + "\n";
        });
        fs.writeFileSync(reportPath, reportContent, 'utf-8');
        console.log(`\nĐã xuất chi tiết danh sách lỗi ra file: ${reportPath}`);
    }
}

main();
