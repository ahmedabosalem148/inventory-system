<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إذن ارتجاع - {{ $voucher->voucher_number }}</title>
    <style>
        @page {
            margin: 20mm;
            size: A4 portrait;
        }
        
        * {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
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
            border-bottom: 3px solid #c0392b;
        }
        
        .header h1 {
            font-size: 24pt;
            color: #c0392b;
            margin-bottom: 10px;
        }
        
        .header h2 {
            font-size: 16pt;
            color: #e74c3c;
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
            color: #c0392b;
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
            background-color: #c0392b;
            color: white;
        }
        
        table th {
            padding: 12px 8px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #a93226;
        }
        
        table td {
            padding: 10px 8px;
            border: 1px solid #bdc3c7;
            text-align: center;
        }
        
        table tbody tr:nth-child(even) {
            background-color: #fadbd8;
        }
        
        table tbody tr:hover {
            background-color: #f5b7b1;
        }
        
        .total-section {
            margin-top: 30px;
            padding: 15px;
            background-color: #fadbd8;
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
            color: #c0392b;
            border-top: 2px solid #c0392b;
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
            border-top: 2px solid #c0392b;
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
        
        .return-badge {
            display: inline-block;
            padding: 5px 15px;
            background-color: #e74c3c;
            color: white;
            border-radius: 5px;
            font-size: 11pt;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <h1>
            <span class="return-badge">ارتجاع</span>
            إذن ارتجاع بضاعة
        </h1>
        <h2>Return Voucher</h2>
    </div>

    {{-- Voucher Information --}}
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">رقم الإذن:</span>
            <span class="info-value">{{ $voucher->voucher_number }}</span>
            <span class="info-label">التاريخ:</span>
            <span class="info-value">{{ $voucher->return_date->format('Y-m-d') }}</span>
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
                <th style="width: 35%">المنتج</th>
                <th style="width: 15%">الكمية</th>
                <th style="width: 15%">السعر</th>
                <th style="width: 15%">الخصم</th>
                <th style="width: 15%">الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @foreach($voucher->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td style="text-align: right; padding-right: 10px;">{{ $item->product->name }}</td>
                <td>{{ $item->qty_units }}</td>
                <td>{{ number_format($item->unit_price, 2) }} ج.م</td>
                <td>{{ number_format($item->discount, 2) }} ج.م</td>
                <td>{{ number_format($item->total_price, 2) }} ج.م</td>
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
            <span>إجمالي الكمية:</span>
            <span>{{ $voucher->items->sum('qty_units') }} وحدة</span>
        </div>
        <div class="total-row grand-total">
            <span>الإجمالي الكلي:</span>
            <span>{{ number_format($voucher->items->sum('total_price'), 2) }} ج.م</span>
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
            <div class="signature-line">المُرتجع</div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>تم الطباعة بتاريخ: {{ now()->format('Y-m-d H:i') }}</p>
        <p>نظام إدارة المخزون والعملاء والحسابات</p>
    </div>
</body>
</html>
