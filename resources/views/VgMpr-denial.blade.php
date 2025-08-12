<!DOCTYPE html>
<html>
<head>
    <style>
       
        .denial-box { background: #ffebee; border-left: 4px solid #f44336; padding: 10px; margin: 10px 0; }
       
        table {
      border: 2px solid black;
      border-collapse: collapse;
    }

    table th {
      color: #000000;
      /* background-color: #f44336; */
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
        <div class="section-title">親愛的 {{ $requester['name'] }}，</div>
        <div>您提交的人員增補單（MPR）已被否決，若有疑問，請與相關主管確認，謝謝。</div>
        <div><a href="http://gmo021.cansportsvg.com/vg/hr/mpr">[連結]</a></div>
        <table>
            <tr>
                <th>表單編號</th>
                <th>申請單位</th>
                <th>申請職位</th>
                <th>需求原因</th>
                <th>需求人數</th>
                <th>否決原因</th>
            </tr>
            <tr>
                <td>{{ $request['id'] }}</td>
                <td>{{ $request['unit'] }}</td>
                <td>{{ is_array(json_decode($requester['position'], true)) ? json_decode($requester['position'], true)['cn'] : $requester['position'] }}</td>
                <td>{{ is_array(json_decode($request['reason'], true)) ? json_decode($request['reason'], true)['cn'] : $request['reason'] }}</td>
                <td>{{ is_array($request['qty']) ? (int)$request['qty']['workers'] + (int)$request['qty']['staff'] + (int)$request['qty']['staff_lang'] + (int)$request['qty']['leader'] + (int)$request['qty']['tranlator'] + (int)$request['qty']['engineers'] : $request['qty'] }}</td>
                <td>{{ $request['reason_denied'] }}</td>
            </tr>
        </table>
    </div>
    
    <!-- Tiếng Việt -->
    <div class="language-section">
        <div class="section-title">Thân gửi {{ $requester['name'] }},</div>
        <div>Đơn xin bổ sung công nhân-nhân viên (MPR) của bạn đã bị từ chối. Nếu có thắc mắc, vui lòng liên hệ với bộ phận liên quan.</div>
        <div><a href="http://gmo021.cansportsvg.com/vg/hr/mpr">[Liên kết]</a></div>
        <table>
            <tr>
                <th>Mã đơn</th>
                <th>Đơn vị</th>
                <th>Vị trí</th>
                <th>Lý do</th>
                <th>Số lượng</th>
                <th>Lý do từ chối</th>
            </tr>
            <tr>
                <td>{{ $request['id'] }}</td>
                <td>{{ $request['unit'] }}</td>
                <td>{{ is_array(json_decode($requester['position'], true)) ? json_decode($requester['position'], true)['vi'] : $requester['position'] }}</td>
               <td>{{ is_array(json_decode($request['reason'], true)) ? json_decode($request['reason'], true)['vi'] : $request['reason'] }}</td>
                <td>{{ is_array($request['qty']) ? (int)$request['qty']['workers'] + (int)$request['qty']['staff'] + (int)$request['qty']['staff_lang'] + (int)$request['qty']['leader'] + (int)$request['qty']['tranlator'] + (int)$request['qty']['engineers'] : $request['qty'] }}</td>
                <td>{{ $request['reason_denied'] }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
