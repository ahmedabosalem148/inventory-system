<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إذن صرف رقم {{ $voucher->voucher_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
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
            border-bottom: 3px solid #2563eb;
        }
        .header h1 {
            font-size: 24px;
            color: #1e40af;
            margin-bottom: 10px;
        }
        .header .voucher-number {
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
            background-color: #f3f4f6;
            font-weight: bold;
            border: 1px solid #d1d5db;
        }
        .info-value {
            display: table-cell;
            width: 25%;
            padding: 8px;
            border: 1px solid #d1d5db;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table thead {
            background-color: #2563eb;
            color: white;
        }
        .items-table th {
            padding: 10px;
            text-align: center;
            border: 1px solid #1e40af;
            font-weight: bold;
        }
        .items-table td {
            padding: 8px;
            text-align: center;
            border: 1px solid #d1d5db;
        }
        .items-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
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
            border-bottom: 1px solid #d1d5db;
        }
        .total-row.grand {
            background-color: #2563eb;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        .footer {
            clear: both;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #d1d5db;
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
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(37, 99, 235, 0.1);
            z-index: -1;
        }
        .print-info {
            font-size: 10px;
            color: #6b7280;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="watermark">{{ $voucher->branch->name }}</div>

    <div class="header">
        <h1>إذن صرف</h1>
        <div class="voucher-number">رقم الإذن: {{ $voucher->voucher_number }}</div>
    </div>

    <div class="info-section">
        <div class="info-row">
            <div class="info-label">الفرع</div>
            <div class="info-value">{{ $voucher->branch->name }}</div>
            <div class="info-label">التاريخ</div>
            <div class="info-value">{{ \Carbon\Carbon::parse($voucher->issue_date)->format('Y-m-d') }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">العميل</div>
            <div class="info-value">{{ $voucher->customer?->name ?? $voucher->customer_name }}</div>
            <div class="info-label">كود العميل</div>
            <div class="info-value">{{ $voucher->customer?->code ?? 'غير محدد' }}</div>
        </div>
        @if($voucher->customer?->phone)
        <div class="info-row">
            <div class="info-label">رقم الهاتف</div>
            <div class="info-value" colspan="3">{{ $voucher->customer->phone }}</div>
        </div>
        @endif
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
        @if($voucher->approvedBy)
        <div class="info-row">
            <div class="info-label">اعتمد بواسطة</div>
            <div class="info-value">{{ $voucher->approvedBy->name }}</div>
            <div class="info-label">تاريخ الاعتماد</div>
            <div class="info-value">{{ $voucher->approved_at ? \Carbon\Carbon::parse($voucher->approved_at)->format('Y-m-d H:i') : '' }}</div>
        </div>
        @endif
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 30%;">المنتج</th>
                <th style="width: 15%;">التصنيف</th>
                <th style="width: 10%;">الكمية</th>
                <th style="width: 10%;">الوحدة</th>
                <th style="width: 12%;">السعر</th>
                <th style="width: 18%;">الإجمالي</th>
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
                <td>
                    @if($item->product?->product_classification)
                        {{ \App\Models\Product::CLASSIFICATION_LABELS[$item->product->product_classification] ?? $item->product->product_classification }}
                    @else
                        -
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
            <span>إجمالي الأصناف:</span>
            <strong>{{ $voucher->items->count() }} صنف</strong>
        </div>
        <div class="total-row">
            <span>إجمالي الكمية:</span>
            <strong>{{ number_format($voucher->items->sum('quantity'), 2) }}</strong>
        </div>
        <div class="total-row grand">
            <span>الإجمالي النهائي:</span>
            <strong>{{ number_format($voucher->total_amount, 2) }} ج.م</strong>
        </div>
    </div>

    @if($voucher->notes)
    <div style="clear: both; margin-top: 20px; padding: 10px; background-color: #fef3c7; border: 1px solid #fbbf24; border-radius: 4px;">
        <strong>ملاحظات:</strong><br>
        {{ $voucher->notes }}
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

        <div class="print-info">
            طُبع في: {{ now()->format('Y-m-d H:i:s') }} | 
            عدد مرات الطباعة: {{ $voucher->print_count }} | 
            آخر طباعة: {{ $voucher->last_printed_at ? \Carbon\Carbon::parse($voucher->last_printed_at)->format('Y-m-d H:i') : 'الأولى' }}
        </div>
    </div>
</body>
</html>
