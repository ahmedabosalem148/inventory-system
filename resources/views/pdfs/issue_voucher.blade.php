<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>إذن صرف - {{ $voucher->voucher_number }}</title>
    <style>
        @page {
            margin: 15mm;
            size: A4 portrait;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
            direction: rtl;
            text-align: right;
            unicode-bidi: embed;
        }
            font-size: 12pt;
            line-height: 1.6;
            color: #333;
            direction: rtl;
            text-align: right;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #2c3e50;
        }
        
        .header h1 {
            font-size: 24pt;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .header h2 {
            font-size: 16pt;
            color: #7f8c8d;
            font-weight: normal;
        }
        
        .info-section {
            margin-bottom: 25px;
            display: table;
            width: 100%;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 25%;
            padding: 8px 0;
            color: #2c3e50;
        }
        
        .info-value {
            display: table-cell;
            padding: 8px 0;
            width: 25%;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        table thead {
            background-color: #34495e;
            color: white;
        }
        
        table th {
            padding: 12px 8px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #2c3e50;
        }
        
        table td {
            padding: 10px 8px;
            border: 1px solid #bdc3c7;
            text-align: center;
        }
        
        table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        table tbody tr:hover {
            background-color: #ecf0f1;
        }
        
        .total-section {
            margin-top: 30px;
            padding: 15px;
            background-color: #ecf0f1;
            border-radius: 5px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14pt;
        }
        
        .total-row.grand-total {
            font-size: 16pt;
            font-weight: bold;
            color: #2c3e50;
            border-top: 2px solid #2c3e50;
            padding-top: 12px;
            margin-top: 8px;
        }
        
        .signatures {
            margin-top: 60px;
            display: table;
            width: 100%;
        }
        
        .signature-box {
            display: table-cell;
            width: 33%;
            text-align: center;
            padding: 20px 10px;
        }
        
        .signature-line {
            border-top: 2px solid #2c3e50;
            margin-top: 60px;
            padding-top: 10px;
            font-weight: bold;
        }
        
        .notes {
            margin-top: 20px;
            padding: 15px;
            background-color: #fff3cd;
            border-right: 4px solid #ffc107;
        }
        
        .notes h4 {
            margin-bottom: 8px;
            color: #856404;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10pt;
            color: #7f8c8d;
            padding-top: 20px;
            border-top: 1px solid #bdc3c7;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <h1>إذن صرف بضاعة</h1>
        <h2>Issue Voucher</h2>
    </div>

    {{-- Voucher Information --}}
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">رقم الإذن:</span>
            <span class="info-value">{{ $voucher->voucher_number }}</span>
            <span class="info-label">التاريخ:</span>
            <span class="info-value">{{ $voucher->issue_date->format('Y-m-d') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">العميل:</span>
            <span class="info-value">{{ $voucher->customer_name }}</span>
            <span class="info-label">الفرع:</span>
            <span class="info-value">{{ $voucher->branch->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">الحالة:</span>
            <span class="info-value">
                @if($voucher->status === 'pending')
                    قيد المعالجة
                @elseif($voucher->status === 'completed')
                    مكتمل
                @else
                    ملغي
                @endif
            </span>
            <span class="info-label">أنشأ بواسطة:</span>
            <span class="info-value">{{ $voucher->creator->name ?? 'غير معروف' }}</span>
        </div>
    </div>

    {{-- Items Table --}}
    <table>
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 25%">المنتج</th>
                <th style="width: 10%">الكمية</th>
                <th style="width: 10%">السعر</th>
                <th style="width: 10%">المجموع</th>
                <th style="width: 10%">نوع الخصم</th>
                <th style="width: 10%">قيمة الخصم</th>
                <th style="width: 10%">الصافي</th>
            </tr>
        </thead>
        <tbody>
            @foreach($voucher->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td style="text-align: right; padding-right: 10px;">{{ $item->product->name }}</td>
                <td>{{ $item->quantity }} {{ $item->product->unit }}</td>
                <td>{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ number_format($item->total_price, 2) }}</td>
                <td>
                    @if($item->discount_type === 'percentage')
                        نسبة
                    @elseif($item->discount_type === 'fixed')
                        مبلغ
                    @else
                        -
                    @endif
                </td>
                <td>
                    @if($item->discount_type === 'percentage')
                        {{ number_format($item->discount_value, 2) }}%
                    @elseif($item->discount_type === 'fixed')
                        {{ number_format($item->discount_value, 2) }}
                    @else
                        -
                    @endif
                </td>
                <td><strong>{{ number_format($item->net_price ?? $item->total_price, 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Total Section --}}
    <div class="total-section">
        <div class="total-row">
            <span>إجمالي الأصناف:</span>
            <span>{{ $voucher->items->count() }} صنف</span>
        </div>
        <div class="total-row">
            <span>المجموع الفرعي:</span>
            <span>{{ number_format($voucher->subtotal ?? $voucher->items->sum('net_price'), 2) }} ج.م</span>
        </div>
        @if($voucher->discount_type && $voucher->discount_type !== 'none')
        <div class="total-row">
            <span>خصم الفاتورة 
                @if($voucher->discount_type === 'percentage')
                    ({{ number_format($voucher->discount_value, 2) }}%)
                @elseif($voucher->discount_type === 'fixed')
                    ({{ number_format($voucher->discount_value, 2) }} ج.م)
                @endif
            :</span>
            <span style="color: #c0392b;">- {{ number_format($voucher->discount_amount, 2) }} ج.م</span>
        </div>
        @endif
        <div class="total-row grand-total">
            <span>الإجمالي النهائي:</span>
            <span>{{ number_format($voucher->net_total ?? $voucher->total_amount, 2) }} ج.م</span>
        </div>
    </div>

    {{-- Notes --}}
    @if($voucher->notes)
    <div class="notes">
        <h4>ملاحظات:</h4>
        <p>{{ $voucher->notes }}</p>
    </div>
    @endif

    {{-- Signatures --}}
    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">المحاسب</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">أمين المخزن</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">المستلم</div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>تم الطباعة بتاريخ: {{ now()->format('Y-m-d H:i') }}</p>
        <p>نظام إدارة المخزون والعملاء والحسابات</p>
    </div>
</body>
</html>
