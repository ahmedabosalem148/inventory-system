<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=80mm">
    <title>إذن صرف {{ $voucher->voucher_number }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 5mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            direction: rtl;
            text-align: center;
            font-size: 10px;
            width: 80mm;
            color: #000;
        }
        .header {
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px dashed #333;
        }
        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .voucher-number {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
        }
        .info-line {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            font-size: 9px;
        }
        .info-label {
            font-weight: bold;
        }
        .divider {
            border-top: 1px dashed #666;
            margin: 8px 0;
        }
        .items-section {
            margin: 10px 0;
        }
        .item {
            text-align: right;
            padding: 5px 0;
            border-bottom: 1px dotted #ccc;
        }
        .item-name {
            font-weight: bold;
            margin-bottom: 3px;
        }
        .item-details {
            font-size: 9px;
            color: #555;
        }
        .item-total {
            display: flex;
            justify-content: space-between;
            margin-top: 3px;
            font-weight: bold;
        }
        .totals {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 2px solid #333;
        }
        .total-line {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 11px;
        }
        .grand-total {
            font-size: 14px;
            font-weight: bold;
            padding: 8px 0;
            border-top: 2px double #000;
            border-bottom: 2px double #000;
        }
        .footer {
            margin-top: 15px;
            font-size: 8px;
            color: #666;
            text-align: center;
        }
        .signature {
            margin-top: 20px;
            border-top: 1px solid #000;
            padding-top: 5px;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $voucher->branch->name }}</h1>
        <div style="font-size: 11px;">إذن صرف</div>
        <div class="voucher-number">{{ $voucher->voucher_number }}</div>
    </div>

    <div class="info-line">
        <span class="info-label">التاريخ:</span>
        <span>{{ \Carbon\Carbon::parse($voucher->issue_date)->format('Y-m-d') }}</span>
    </div>

    <div class="info-line">
        <span class="info-label">العميل:</span>
        <span>{{ $voucher->customer?->name ?? $voucher->customer_name }}</span>
    </div>

    @if($voucher->customer?->code)
    <div class="info-line">
        <span class="info-label">كود العميل:</span>
        <span>{{ $voucher->customer->code }}</span>
    </div>
    @endif

    <div class="divider"></div>

    <div class="items-section">
        @foreach($voucher->items as $index => $item)
        <div class="item">
            <div class="item-name">{{ $index + 1 }}. {{ $item->product?->name ?? $item->product_name }}</div>
            <div class="item-details">
                @if($item->product?->sku)
                <span>SKU: {{ $item->product->sku }}</span> | 
                @endif
                <span>{{ number_format($item->quantity, 2) }} {{ $item->unit }}</span> × 
                <span>{{ number_format($item->unit_price, 2) }} ج.م</span>
            </div>
            <div class="item-total">
                <span>الإجمالي:</span>
                <span>{{ number_format($item->quantity * $item->unit_price, 2) }} ج.م</span>
            </div>
        </div>
        @endforeach
    </div>

    <div class="totals">
        <div class="total-line">
            <span>عدد الأصناف:</span>
            <strong>{{ $voucher->items->count() }} صنف</strong>
        </div>
        <div class="total-line">
            <span>إجمالي الكمية:</span>
            <strong>{{ number_format($voucher->items->sum('quantity'), 2) }}</strong>
        </div>
        <div class="divider"></div>
        <div class="grand-total">
            <div style="display: flex; justify-content: space-between;">
                <span>الإجمالي:</span>
                <strong>{{ number_format($voucher->total_amount, 2) }} ج.م</strong>
            </div>
        </div>
    </div>

    @if($voucher->notes)
    <div style="margin-top: 10px; padding: 5px; border: 1px solid #666; font-size: 9px; text-align: right;">
        <strong>ملاحظات:</strong> {{ $voucher->notes }}
    </div>
    @endif

    <div class="signature">
        <div>توقيع المستلم</div>
        <div style="margin-top: 15px; border-top: 1px solid #000; width: 60%; margin-left: auto; margin-right: auto;"></div>
    </div>

    <div class="footer">
        <div>طُبع: {{ now()->format('Y-m-d H:i') }}</div>
        <div>عدد مرات الطباعة: {{ $voucher->print_count }}</div>
        <div style="margin-top: 5px;">{{ $voucher->createdBy?->name }}</div>
    </div>
</body>
</html>
