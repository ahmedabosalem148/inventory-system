<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>أمر شراء {{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 12px;
            color: #333;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #16a34a;
        }
        .header h1 {
            font-size: 24px;
            color: #15803d;
            margin-bottom: 10px;
        }
        .order-number {
            font-size: 18px;
            font-weight: bold;
            color: #16a34a;
        }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .info-row { display: table-row; }
        .info-label {
            display: table-cell;
            width: 25%;
            padding: 8px;
            background-color: #dcfce7;
            font-weight: bold;
            border: 1px solid #86efac;
        }
        .info-value {
            display: table-cell;
            width: 25%;
            padding: 8px;
            border: 1px solid #86efac;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table thead {
            background-color: #16a34a;
            color: white;
        }
        .items-table th {
            padding: 10px;
            text-align: center;
            border: 1px solid #15803d;
        }
        .items-table td {
            padding: 8px;
            text-align: center;
            border: 1px solid #86efac;
        }
        .items-table tbody tr:nth-child(even) {
            background-color: #f0fdf4;
        }
        .totals {
            margin-top: 20px;
            float: left;
            width: 300px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px;
            border-bottom: 1px solid #86efac;
        }
        .total-row.grand {
            background-color: #16a34a;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        .signature-section {
            clear: both;
            margin-top: 50px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 33%;
            text-align: center;
            padding: 10px;
        }
        .signature-line {
            margin-top: 50px;
            border-top: 1px solid #333;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>أمر شراء</h1>
        <div class="order-number">رقم الأمر: {{ $order->order_number }}</div>
    </div>

    <div class="info-section">
        <div class="info-row">
            <div class="info-label">الفرع</div>
            <div class="info-value">{{ $order->branch->name }}</div>
            <div class="info-label">تاريخ الأمر</div>
            <div class="info-value">{{ \Carbon\Carbon::parse($order->order_date)->format('Y-m-d') }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">المورد</div>
            <div class="info-value">{{ $order->supplier?->name ?? 'غير محدد' }}</div>
            <div class="info-label">كود المورد</div>
            <div class="info-value">{{ $order->supplier?->code ?? '-' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">الحالة</div>
            <div class="info-value">
                @switch($order->status)
                    @case('PENDING') قيد الانتظار @break
                    @case('APPROVED') معتمد @break
                    @case('RECEIVED') مستلم @break
                    @case('CANCELLED') ملغي @break
                    @default {{ $order->status }}
                @endswitch
            </div>
            <div class="info-label">أُنشئ بواسطة</div>
            <div class="info-value">{{ $order->createdBy?->name ?? 'غير محدد' }}</div>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 40%;">المنتج</th>
                <th style="width: 15%;">الكمية المطلوبة</th>
                <th style="width: 10%;">الوحدة</th>
                <th style="width: 15%;">السعر</th>
                <th style="width: 15%;">الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td style="text-align: right;">
                    {{ $item->product?->name ?? $item->product_name }}
                    @if($item->product?->sku)
                        <br><small style="color: #6b7280;">SKU: {{ $item->product->sku }}</small>
                    @endif
                </td>
                <td>{{ number_format($item->quantity, 2) }}</td>
                <td>{{ $item->unit }}</td>
                <td>{{ number_format($item->unit_price, 2) }} ج.م</td>
                <td>{{ number_format($item->quantity * $item->unit_price, 2) }} ج.م</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="total-row">
            <span>عدد الأصناف:</span>
            <strong>{{ $order->items->count() }}</strong>
        </div>
        <div class="total-row">
            <span>إجمالي الكمية:</span>
            <strong>{{ number_format($order->items->sum('quantity'), 2) }}</strong>
        </div>
        <div class="total-row grand">
            <span>الإجمالي:</span>
            <strong>{{ number_format($order->total_amount, 2) }} ج.م</strong>
        </div>
    </div>

    @if($order->notes)
    <div style="clear: both; margin-top: 20px; padding: 10px; background-color: #fef3c7; border: 1px solid #fbbf24;">
        <strong>ملاحظات:</strong><br>{{ $order->notes }}
    </div>
    @endif

    <div class="signature-section">
        <div class="signature-box">
            <div>مدير المشتريات</div>
            <div class="signature-line"></div>
        </div>
        <div class="signature-box">
            <div>المدير المالي</div>
            <div class="signature-line"></div>
        </div>
        <div class="signature-box">
            <div>المدير التنفيذي</div>
            <div class="signature-line"></div>
        </div>
    </div>

    <div style="font-size: 10px; color: #6b7280; text-align: center; margin-top: 30px;">
        طُبع في: {{ now()->format('Y-m-d H:i:s') }} | عدد مرات الطباعة: {{ $order->print_count }}
    </div>
</body>
</html>
