<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>แพ็คของ</title>
    <style>
        @font-face {
            font-family: 'Sarabun';
            src: url('{{ $fontPath }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        body {
            font-family: Sarabun, DejaVu Sans, sans-serif;
            font-size: 13px;
            color: #1f2937;
        }

        h1 {
            font-size: 22px;
            margin: 0 0 4px;
            font-weight: normal;
        }

        .channel {
            margin: 0;
            color: #4b5563;
            font-size: 13px;
        }

        .sheet-head {
            display: table;
            width: 100%;
            margin: 0 0 14px;
        }

        .sheet-head .title {
            display: table-cell;
            vertical-align: top;
        }

        .sheet-head .barcode-wrap {
            display: table-cell;
            vertical-align: top;
            text-align: right;
            width: 180px;
        }

        .qr {
            width: 72px;
            height: 72px;
            margin: 0 0 6px;
        }

        .barcode {
            height: 36px;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 14px;
        }

        .meta th,
        .meta td {
            text-align: left;
            vertical-align: top;
            padding: 3px 8px 3px 0;
        }

        .meta th {
            width: 120px;
            color: #4b5563;
            font-weight: normal;
        }

        .address {
            white-space: pre-wrap;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
        }

        .items th,
        .items td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }

        .items th {
            background: #f3f4f6;
            font-weight: normal;
        }

        .tick-col {
            width: 28px;
            text-align: center;
        }

        .tick {
            width: 14px;
            height: 14px;
            border: 1.5px solid #111;
        }

        .qty {
            width: 64px;
            text-align: right;
        }

        .choices {
            margin: 0;
            padding-left: 1.1em;
            color: #4b5563;
        }

        .sheet {
            page-break-after: always;
        }

        .sheet:last-child {
            page-break-after: auto;
        }

        .empty {
            color: #4b5563;
        }
    </style>
</head>
<body>
    @forelse ($orders as $order)
        <div class="sheet">
            <div class="sheet-head">
                <div class="title">
                    <h1>{{ $order->number }}</h1>
                    <p class="channel">{{ $order->fulfillment->label() }}</p>
                </div>
                <div class="barcode-wrap">
                    <img class="barcode" src="{{ $barcodes[$order->id] }}" alt="{{ $order->number }}">
                    <img class="qr" src="{{ $qrs[$order->id] }}" alt="{{ $order->number }}">
                </div>
            </div>
            <table class="meta">
                <tr>
                    <th>ผู้จอง</th>
                    <td>{{ $order->full_name }}</td>
                </tr>
                <tr>
                    <th>รหัสนักศึกษา</th>
                    <td>{{ $order->student_id }}</td>
                </tr>
                <tr>
                    <th>โทร</th>
                    <td>{{ $order->phone }}</td>
                </tr>
                <tr>
                    <th>คณะ</th>
                    <td>{{ $order->faculty }}</td>
                </tr>
                <tr>
                    <th>สาขาวิชา</th>
                    <td>{{ $order->major }}</td>
                </tr>
                @if ($order->fulfillment === \App\Enums\FulfillmentMethod::Post)
                    <tr>
                        <th>ที่อยู่จัดส่ง</th>
                        <td class="address">{{ $order->address ?: '—' }}</td>
                    </tr>
                @else
                    <tr>
                        <th>จุดรับ</th>
                        <td>{{ $order->fulfillment->label() }}</td>
                    </tr>
                @endif
            </table>
            <table class="items">
                <thead>
                    <tr>
                        <th class="tick-col"></th>
                        <th>รายการ</th>
                        <th>ตัวเลือก</th>
                        <th class="qty">จำนวน</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="tick-col"><div class="tick"></div></td>
                            <td>{{ $item->name }}</td>
                            <td>
                                @if (($item->choices ?? []) !== [])
                                    <ul class="choices">
                                        @foreach ($item->choices as $choice)
                                            <li>{{ $choice['label'] ?? '' }} · {{ $choice['value'] ?? '' }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                            <td class="qty">{{ $item->qty }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <h1>แพ็คของ</h1>
        <p class="empty">ไม่มีออเดอร์ในตัวกรองนี้ · รอบจอง: {{ $roundName }} · คณะ: {{ $faculty }} · ช่องทาง: {{ $channelLabel }}</p>
    @endforelse
</body>
</html>
