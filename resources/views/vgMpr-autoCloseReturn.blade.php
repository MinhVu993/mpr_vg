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
        <div>您的人員增補申請已退回超過一個月未處理，系統已自動作廢。<br>
       若仍有需求，請重新提交申請。<br>
         <a href="http://gmo021.cansportsvg.com/vg/hr/mpr">[連結]</a>
    </div>
        <table>
            <tr>
                <th>表單編號</th>
                <th>申請單位</th>
                <th>申請職位</th>
                <th>需求人數</th>
               
            </tr>
            <tr>
                  <td style="text-align:center">{{ $templateData['request']['id'] }}</td>
                <td style="text-align:center">{{ $templateData['request']['unit'] }}</td>
                <td style="text-align:center">{{ $templateData['request']['position']['cn'] }}</td>
                <td style="text-align:center">{{ $templateData['request']['stocknumber'] ?? 0 }}</td>
            </tr>
        </table>
    </div>

    <!-- Tiếng Việt -->
    <div class="language-section">
        <div class="section-title">Thân gửi {{ $templateData['requester']['name'] }},</div>
        <div>Đơn yêu cầu bổ sung nhân sự (MPR) của bạn đã bị trả lại và đã quá một tháng không được xử lý.<br>
       Hệ thống đã tự động đóng yêu cầu này.<br>
       Nếu vẫn còn nhu cầu, vui lòng tạo lại yêu cầu mới. <br>
       <a href="http://gmo021.cansportsvg.com/vg/hr/mpr">[Liên kết]</a>
    </div>
        <table>
            <tr>
                <th>Mã đơn</th>
                <th>Đơn vị</th>
                <th>Vị trí</th>
                <th>Lý do yêu cầu</th>
            </tr>
            <tr>
                  <td style="text-align:center">{{ $templateData['request']['id'] }}</td>
                <td style="text-align:center">{{ $templateData['request']['unit'] }}</td>
                <td style="text-align:center">{{ $templateData['request']['position']['vi'] }}</td>
                <td style="text-align:center">{{ $templateData['request']['stocknumber'] ?? 0 }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
