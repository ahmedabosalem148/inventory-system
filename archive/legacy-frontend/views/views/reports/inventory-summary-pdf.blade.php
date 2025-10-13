<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>تقرير إجمالي المخزون</title>
    <style>
        @page {
            margin: 15mm;
            size: A4 landscape;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            direction: rtl;
            text-align: right;
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .info-box {
            background: #ecf0f1;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .info-box table {
            width: 100%;
        }
        .info-box td {
            padding: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #bdc3c7;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #34495e;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .status-normal {
            color: #27ae60;
            font-weight: bold;
        }
        .status-warning {
            color: #f39c12;
            font-weight: bold;
        }
        .status-danger {
            color: #e74c3c;
            font-weight: bold;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9pt;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <h1>تقرير إجمالي المخزون</h1>

    <div class="info-box">
        <table>
            <tr>
                <td><strong>إجمالي الأصناف:</strong> {{ number_format($stats['total_items']) }}</td>
                <td><strong>إجمالي الكميات:</strong> {{ number_format($stats['total_quantity']) }}</td>
                <td><strong>أقل من الحد الأدنى:</strong> {{ number_format($stats['below_min_count']) }}</td>
                <td><strong>تاريخ التقرير:</strong> {{ now()->format('Y-m-d H:i') }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 10%;">الكود</th>
                <th style="width: 25%;">المنتج</th>
                <th style="width: 15%;">التصنيف</th>
                <th style="width: 12%;">الفرع</th>
                <th style="width: 12%;">الرصيد الحالي</th>
                <th style="width: 12%;">الحد الأدنى</th>
                <th style="width: 14%;">الحالة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($inventory as $item)
                @php
                    $status = 'طبيعي';
                    $statusClass = 'status-normal';
                    
                    if ($item->current_stock == 0) {
                        $status = 'نفذ من المخزن';
                        $statusClass = 'status-danger';
                    } elseif ($item->current_stock < $item->product->min_stock) {
                        $status = 'أقل من الحد الأدنى';
                        $statusClass = 'status-warning';
                    }
                @endphp
                <tr>
                    <td>{{ $item->product->sku }}</td>
                    <td style="text-align: right;">{{ $item->product->name }}</td>
                    <td>{{ $item->product->category->name ?? '-' }}</td>
                    <td>{{ $item->branch->name }}</td>
                    <td><strong>{{ number_format($item->current_stock) }}</strong></td>
                    <td>{{ number_format($item->product->min_stock) }}</td>
                    <td class="{{ $statusClass }}">{{ $status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        نظام إدارة المخزون - طباعة في {{ now()->format('Y-m-d H:i:s') }}
    </div>
</body>
</html>
