<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            line-height: 1.5;
            color: #333;
            background-color: #ffffff; /* Nền trắng đơn giản */
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            /* Bỏ background-color, box-shadow, border-radius của container */
        }
        .header {
            font-size: 18px; /* Giảm kích thước font tiêu đề */
            font-weight: bold;
            color: #009688;
            margin-bottom: 20px;
            /* Bỏ border-bottom, text-align center */
        }
        .content {
            font-size: 15px;
        }
        .date-block {
            margin-bottom: 15px;
            padding-left: 15px; /* Thụt đầu dòng nhẹ thay vì border-left đậm */
            border-left: 3px solid #009688; /* Viền trái mảnh hơn */
        }
        .date-highlight {
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
            <p>年度招募結算作業時程如下：</p>

            <div class="date-block">
                <div class="date-highlight">12/08（一）起</div>
                <div>HR 停止受理新增補單，已核准需求持續招募至年底。</div>
            </div>

            <div class="date-block">
                <div class="date-highlight">12/23（二）</div>
                <div>系統將寄發年度結算通知信，請屆時依通知完成確認。</div>
            </div>

            <p>感謝您的配合。</p>
        </div>
    </div>
</body>
</html>