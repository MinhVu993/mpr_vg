<!DOCTYPE html>
<html>
<head>
    <style>
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
      padding: 15px;
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
        <div class="section-title">親愛的主管，</div>
        <div>您先前核准的人員增補單（MPR）已提出需求變更申請。<br>
        請點選連結以查看詳細資訊並進行簽核： <a href="http://gmo021.cansportsvg.com/vg/hr/mpr">[連結]</a></div>
        <table>
            <tr>
                <th>表單編號</th>
                <th>申請單位</th>
                <th>申請職位</th>
                <th>變更原因</th>
            </tr>
            <tr>
                <td>{{ $request['id'] ?? '' }}</td>
                <td>{{ $request['unit'] }}</td>
                <td>{{ is_array(json_decode($requester['position'], true)) ? json_decode($requester['position'], true)['cn'] : $requester['position'] }}</td>
                <td>{{ is_array($request['reason_change']) ? $request['reason_change']['cn'] : ($request['reason_change'] ?? '') }}</td>
            </tr>
        </table>
    </div>

    <!-- English -->
    <div class="language-section">
        <div class="section-title">Dear Manager,</div>
        <div>A manpower request (MPR) you previously approved has been submitted for amendment.<br>
        Please click the link below to view and approve the updated request: <a href="http://gmo021.cansportsvg.com/vg/hr/mpr">[Link]</a></div>
        <table>
            <tr>
                <th>Form No.</th>
                <th>Unit</th>
                <th>Position</th>
                <th>Amendment Reason</th>
            </tr>
            <tr>
                <td>{{ $request['id'] ?? '' }}</td>
                <td>{{ $request['unit'] }}</td>
                <td>{{ is_array(json_decode($requester['position'], true)) ? json_decode($requester['position'], true)['en'] : $requester['position'] }}</td>
                <td>{{ is_array($request['reason_change']) ? $request['reason_change']['en'] : ($request['reason_change'] ?? '') }}</td>
            </tr>
        </table>
    </div>

    <!-- Tiếng Việt -->
    <div class="language-section">
        <div class="section-title">Thân gửi Quản lý,</div>
        <div>Một đơn MPR mà bạn đã phê duyệt trước đó hiện đã được yêu cầu điều chỉnh.<br>
        Vui lòng nhấn vào liên kết sau để xem chi tiết và phê duyệt: <a href="http://gmo021.cansportsvg.com/vg/hr/mpr">[Liên kết]</a></div>
        <table>
            <tr>
                <th>Mã đơn</th>
                <th>Đơn vị</th>
                <th>Vị trí</th>
                <th>Lý do điều chỉnh</th>
            </tr>
            <tr>
                <td>{{ $request['id'] ?? '' }}</td>
                <td>{{ $request['unit'] }}</td>
                <td>{{ is_array(json_decode($requester['position'], true)) ? json_decode($requester['position'], true)['vi'] : $requester['position'] }}</td>
                <td>{{ is_array($request['reason_change']) ? $request['reason_change']['vi'] : ($request['reason_change'] ?? '') }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
