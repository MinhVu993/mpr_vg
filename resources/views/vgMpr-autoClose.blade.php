<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial; line-height: 1.6; }
        .header { background: #2E7D32; color: white; padding: 12px 20px; max-width: 800px; }
        .content { padding: 20px; max-width: 800px; }
        .info { background: #E8F5E9; padding: 12px 20px; margin: 15px 0; max-width: 800px; border-left: 4px solid #2E7D32; }
        .success-box { background: #E8F5E9; border-left: 4px solid #2E7D32; padding: 10px; margin: 10px 0; }
        table {
            border: 2px solid black;
            border-collapse: collapse;
        }
        table th {
            color: #000000;
            /* background-color: #4CAF50; */
            font-weight: bold;
            border: 1px solid black;
            border-collapse: collapse;
            padding: 8px;
            white-space: nowrap;
        }
        table td {
            border: 1px solid black;
            border-collapse: collapse;
            padding: 8px;
            white-space: nowrap;
        }
         .language-section {
      margin-top: 10px;
      margin-bottom: 10px;
      padding: 10px;
      border-bottom: 1px solid #cccccc;
    }
     .section-title {
      font-weight: bold;
      font-size: 1.1em;
      margin-bottom: 10px;
    }
    </style>
</head>
<body>
    <!-- 中文 -->
    <div class="language-section">
        <div class="section-title">親愛的 {{ $templateData['requester']['name'] }} ，</div>
        <div>您的人員增補申請（MPR）自 {{ \Carbon\Carbon::parse($templateData['request']['hold_date'])->format('Y-m-d') }} 起暫停招募後，已超過三個月未處理，系統已自動結案。<br>
        若仍有需求，請重新提交申請。</div>
        <table>
            <tr>
                <th>表單編號</th>
                <th>申請單位</th>
                <th>申請職位</th>
                <th>需求人數</th>
                <th>已招募人數</th>
                <th>申請暫停日期</th>
            </tr>
            <tr>
                <td>{{ $templateData['request']['id'] }}</td>
                <td>{{ $templateData['request']['unit'] }}</td>
                <td>{{ $templateData['request']['position'] }}</td>
                <td>{{ $templateData['request']['stocknumber'] ?? 0 }}</td>
                <td>{{ $templateData['request']['recruited'] ?? 0 }}</td>
                <td>{{ \Carbon\Carbon::parse($templateData['request']['hold_date'])->format('Y-m-d') }}</td>
            </tr>
        </table>
    </div>

    <!-- Tiếng Việt -->
    <div class="language-section">
        <div class="section-title">Thân gửi {{ $templateData['requester']['name'] }},</div>
        <div>Đơn yêu cầu bổ sung nhân sự (MPR) của bạn đã "Tạm ngưng tuyển dụng" kể từ {{ \Carbon\Carbon::parse($templateData['request']['hold_date'])->format('Y-m-d') }} và đã quá 3 tháng không được xử lý, hệ thống đã tự động kết thúc yêu cầu này.<br>
        Nếu vẫn còn nhu cầu, vui lòng tạo lại yêu cầu mới.</div>
        <table>
            <tr>
                <th>Mã đơn</th>
                <th>Đơn vị</th>
                <th>Vị trí</th>
                <th>Số lượng yêu cầu</th>
                <th>Số lượng đã tuyển</th>
                <th>Ngày tạm ngưng tuyển dụng</th>
            </tr>
            <tr>
                <td>{{ $templateData['request']['id'] }}</td>
                <td>{{ $templateData['request']['unit'] }}</td>
                <td>{{ $templateData['request']['position'] }}</td>
                <td>{{ $templateData['request']['stocknumber'] ?? 0 }}</td>
                <td>{{ $templateData['request']['recruited'] ?? 0 }}</td>
                <td>{{ \Carbon\Carbon::parse($templateData['request']['hold_date'])->format('Y-m-d') }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
