<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إذن مرتجع {{ $voucher->voucher_number }}</title>
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
            border-bottom: 3px solid #dc2626;
        }
        .header h1 {
            font-size: 24px;
            color: #991b1b;
            margin-bottom: 10px;
        }
        .voucher-number {
            font-size: 18px;
            font-weight: bold;
            color: #dc2626;
        }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            width: 25%;
            padding: 8px;
            background-color: #fee2e2;
            font-weight: bold;
            border: 1px solid #fca5a5;
        }
        .info-value {
            display: table-cell;
            width: 25%;
            padding: 8px;
            border: 1px solid #fca5a5;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table thead {
            background-color: #dc2626;
            color: white;
        }
        .items-table th {
            padding: 10px;
            text-align: center;
            border: 1px solid #991b1b;
            font-weight: bold;
        }
        .items-table td {
            padding: 8px;
            text-align: center;
            border: 1px solid #fca5a5;
        }
        .items-table tbody tr:nth-child(even) {
            background-color: #fef2f2;
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
            border-bottom: 1px solid #fca5a5;
        }
        .total-row.grand {
            background-color: #dc2626;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        .footer {
            clear: both;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #fca5a5;
        }
        .signature-section {
            display: table;
            width: 100%;
            margin-top: 30px;
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
        <h1>إذن مرتجع</h1>
        <div class="voucher-number">رقم الإذن: {{ $voucher->voucher_number }}</div>
    </div>

    <div class="info-section">
        <div class="info-row">
            <div class="info-label">الفرع</div>
            <div class="info-value">{{ $voucher->branch->name }}</div>
            <div class="info-label">التاريخ</div>
            <div class="info-value">{{ \Carbon\Carbon::parse($voucher->return_date)->format('Y-m-d') }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">العميل</div>
            <div class="info-value">{{ $voucher->customer?->name ?? $voucher->customer_name }}</div>
            <div class="info-label">كود العميل</div>
            <div class="info-value">{{ $voucher->customer?->code ?? 'غير محدد' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">الحالة</div>
            <div class="info-value">
                @switch($voucher->status)
                    @case('PENDING') في الانتظار @break
                    @case('APPROVED') معتمد @break
                    @case('REJECTED') مرفوض @break
                    @default {{ $voucher->status }}
                @endswitch
            </div>
            <div class="info-label">أُنشئ بواسطة</div>
            <div class="info-value">{{ $voucher->createdBy?->name ?? 'غير محدد' }}</div>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 35%;">المنتج</th>
                <th style="width: 12%;">الكمية المرتجعة</th>
                <th style="width: 10%;">الوحدة</th>
                <th style="width: 12%;">السعر</th>
                <th style="width: 15%;">الإجمالي</th>
                <th style="width: 11%;">السبب</th>
            </tr>
        </thead>
        <tbody>
            @foreach($voucher->items as $index => $item)
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
                <td style="font-size: 10px;">{{ $item->return_reason ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="total-row">
            <span>إجمالي الأصناف:</span>
            <strong>{{ $voucher->items->count() }} صنف</strong>
        </div>
        <div class="total-row">
            <span>إجمالي الكمية:</span>
            <strong>{{ number_format($voucher->items->sum('quantity'), 2) }}</strong>
        </div>
        <div class="total-row grand">
            <span>الإجمالي المرتجع:</span>
            <strong>{{ number_format($voucher->total_amount, 2) }} ج.م</strong>
        </div>
    </div>

    @if($voucher->notes)
    <div style="clear: both; margin-top: 20px; padding: 10px; background-color: #fef3c7; border: 1px solid #fbbf24;">
        <strong>ملاحظات:</strong><br>{{ $voucher->notes }}
    </div>
    @endif

    <div class="footer">
        <div class="signature-section">
            <div class="signature-box">
                <div>المحاسب</div>
                <div class="signature-line"></div>
            </div>
            <div class="signature-box">
                <div>مدير المخزن</div>
                <div class="signature-line"></div>
            </div>
            <div class="signature-box">
                <div>المستلم</div>
                <div class="signature-line"></div>
            </div>
        </div>

        <div style="font-size: 10px; color: #6b7280; text-align: center; margin-top: 20px;">
            طُبع في: {{ now()->format('Y-m-d H:i:s') }} | 
            عدد مرات الطباعة: {{ $voucher->print_count }}
        </div>
    </div>
</body>
</html>
