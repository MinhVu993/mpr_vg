<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            line-height: 1.5;
            color: #333;
            background-color: #ffffff;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            font-size: 18px;
            font-weight: bold;
            color: #009688;
            margin-bottom: 15px;
            border-bottom: 2px solid #009688;
            padding-bottom: 10px;
        }
        .content {
            margin-bottom: 20px;
        }
        /* Table Styles */
        .mpr-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 14px;
        }
        .mpr-table th, .mpr-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center; /* Căn giữa cho số liệu */
        }
        .mpr-table th {
            background-color: #f2f2f2;
            color: #333;
            font-weight: bold;
            vertical-align: middle;
        }
        .text-left {
            text-align: left !important;
        }
        .highlight-red {
             color: #d32f2f;
             font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            font-size: 13px;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <p>各位主管好，</p>
            <p>
                2025 年度招募結算作業已啟動，以下為處級單位今年度的人員增補狀況.<br>
                請各位主管檢視並於 12/27（六）前登入 MPR 系統，依提示確認該增補單於新年度是否需要繼續招募。
                <br>
                <a href="http://gmo021.cansportsvg.com/vg/hr/mpr">[連結]</a>
            </p>

            <table class="mpr-table">
                <thead>
                    <tr>
                        <th class="text-left">
                            Req ID<br>
                            表單編號
                        </th>
                        <th>
                            Unit<br>
                            單位代號
                        </th>
                        <th>
                            Total Qty<br>
                            需求
                        </th>
                        <th>
                            Recruited<br>
                            入職人數
                        </th>
                        <th>
                            Resign<br>
                            離職人數
                        </th>
                        <th>
                            Stocknumber<br>
                            剩餘名額
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($requests) && count($requests) > 0)
                        @foreach($requests as $req)
                        <tr>
                            <td class="text-left">{{ $req['req_id'] }}</td>
                            <td>{{ $req['unit'] }}</td>
                            <td>{{ $req['total_qty_req'] }}</td>
                            <td>{{ $req['recruited'] ?? 0 }}</td>
                            <td>{{ $req['resign'] ?? 0 }}</td>
                            <td class="highlight-red">{{ $req['stocknumber'] }}</td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6">No data available / 無資料</td>
                        </tr>
                    @endif
                </tbody>
            </table>
             <p>
                如未於期限內完成確認，系統將自動視為取消招募並結案.<br>
               若新年度仍有招募需求，請重新提交新的申請。
                <br>
                感謝您的協助。
            </p>

        </div>
    </div>
</body>
</html>