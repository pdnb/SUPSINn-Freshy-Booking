<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>สรุปยอดผลิต</title>
    <style>
        @font-face {
            font-family: 'Sarabun';
            src: url('{{ $fontPath }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        body {
            font-family: Sarabun, DejaVu Sans, sans-serif;
            font-size: 14px;
            color: #1f2937;
        }

        h1 {
            font-size: 20px;
            margin: 0 0 8px;
        }

        p {
            margin: 0 0 16px;
            color: #4b5563;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            text-align: left;
        }

        th {
            background: #f3f4f6;
        }
    </style>
</head>
<body>
    <h1>สรุปยอดผลิต</h1>
    <p>รอบจอง: {{ $roundName }} · คณะ: {{ $faculty }}</p>
    <table>
        <thead>
            <tr>
                <th>สินค้า</th>
                <th>ตัวเลือก</th>
                <th>ค่า</th>
                <th>จำนวน</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['product_name'] }}</td>
                    <td>{{ $row['choice_label'] !== '' ? $row['choice_label'] : '—' }}</td>
                    <td>{{ $row['choice_value'] !== '' ? $row['choice_value'] : '—' }}</td>
                    <td>{{ $row['qty'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">ยังไม่มียอดจากออเดอร์ที่ยืนยันแล้ว</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
