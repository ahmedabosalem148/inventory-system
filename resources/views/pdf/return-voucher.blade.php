<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>إذن مرتجع - {{ $voucher->voucher_number }}</title>
    <style>
        @page {
            margin: 15mm;
            size: A4 portrait;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #333;
            direction: rtl;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #dc2626;
        }
        
        .header h1 {
            font-size: 24pt;
            color: #991b1b;
            margin-bottom: 5px;
        }
        
        .header .subtitle {
            font-size: 10pt;
            color: #64748b;
        }
        
        .voucher-info {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border: 2px solid #fecaca;
            border-radius: 8px;
            background-color: #fef2f2;
        }
        
        .voucher-info-row {
            display: table-row;
        }
        
        .voucher-info-cell {
            display: table-cell;
            padding: 8px 12px;
            border-bottom: 1px solid #fecaca;
            width: 50%;
        }
        
        .voucher-info-row:last-child .voucher-info-cell {
            border-bottom: none;
        }
        
        .voucher-info-label {
            font-weight: bold;
            color: #7c2d12;
            display: inline-block;
            width: 120px;
        }
        
        .voucher-info-value {
            color: #1e293b;
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
            padding: 10px 8px;
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
            border: 1px solid #991b1b;
        }
        
        .items-table td {
            padding: 8px;
            text-align: center;
            border: 1px solid #fecaca;
            font-size: 10pt;
        }
        
        .items-table tbody tr:nth-child(even) {
            background-color: #fef2f2;
        }
        
        .items-table tbody tr:hover {
            background-color: #fee2e2;
        }
        
        .text-right {
            text-align: right !important;
        }
        
        .text-left {
            text-align: left !important;
        }
        
        .totals {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .totals-row {
            display: table-row;
        }
        
        .totals-label {
            display: table-cell;
            width: 70%;
            text-align: left;
            padding: 8px 12px;
            font-weight: bold;
            color: #7c2d12;
            border-top: 1px solid #fecaca;
        }
        
        .totals-value {
            display: table-cell;
            width: 30%;
            text-align: center;
            padding: 8px 12px;
            font-weight: bold;
            color: #1e293b;
            border-top: 1px solid #fecaca;
            background-color: #fef2f2;
        }
        
        .grand-total .totals-label,
        .grand-total .totals-value {
            background-color: #fee2e2;
            color: #991b1b;
            font-size: 12pt;
            border-top: 3px solid #dc2626;
        }
        
        .notes {
            margin-bottom: 30px;
            padding: 12px;
            background-color: #fffbeb;
            border: 1px solid #fbbf24;
            border-radius: 6px;
        }
        
        .notes-label {
            font-weight: bold;
            color: #92400e;
            margin-bottom: 5px;
        }
        
        .notes-text {
            color: #78350f;
            white-space: pre-wrap;
        }
        
        .signatures {
            display: table;
            width: 100%;
            margin-top: 40px;
            page-break-inside: avoid;
        }
        
        .signature-row {
            display: table-row;
        }
        
        .signature-cell {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 10px;
            vertical-align: bottom;
        }
        
        .signature-line {
            border-top: 2px solid #000;
            margin-top: 50px;
            padding-top: 8px;
            font-weight: bold;
            color: #7c2d12;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #94a3b8;
            padding: 10px;
            border-top: 1px solid #fecaca;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 9pt;
            font-weight: bold;
        }
        
        .badge-return {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .badge-approved {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .text-bold {
            font-weight: bold;
        }
        
        .text-muted {
            color: #64748b;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <h1>إذن مرتجع</h1>
        <p class="subtitle">نظام إدارة المخزون</p>
    </div>

    {{-- Voucher Information --}}
    <div class="voucher-info">
        <div class="voucher-info-row">
            <div class="voucher-info-cell">
                <span class="voucher-info-label">رقم الإذن:</span>
                <span class="voucher-info-value text-bold">{{ $voucher->voucher_number }}</span>
            </div>
            <div class="voucher-info-cell">
                <span class="voucher-info-label">التاريخ:</span>
                <span class="voucher-info-value">{{ \Carbon\Carbon::parse($voucher->return_date)->format('Y-m-d') }}</span>
            </div>
        </div>
        
        <div class="voucher-info-row">
            <div class="voucher-info-cell">
                <span class="voucher-info-label">نوع الإذن:</span>
                <span class="badge badge-return">مرتجع</span>
            </div>
            <div class="voucher-info-cell">
                <span class="voucher-info-label">الحالة:</span>
                @if($voucher->status === 'approved')
                    <span class="badge badge-approved">معتمد</span>
                @else
                    <span class="badge badge-pending">قيد الانتظار</span>
                @endif
            </div>
        </div>
        
        <div class="voucher-info-row">
            <div class="voucher-info-cell">
                <span class="voucher-info-label">الفرع:</span>
                <span class="voucher-info-value">{{ $voucher->branch->name ?? 'غير محدد' }}</span>
            </div>
            @if($voucher->customer)
            <div class="voucher-info-cell">
                <span class="voucher-info-label">العميل:</span>
                <span class="voucher-info-value">{{ $voucher->customer->name }}</span>
            </div>
            @endif
        </div>
        
        @if($voucher->creator)
        <div class="voucher-info-row">
            <div class="voucher-info-cell">
                <span class="voucher-info-label">أنشأ بواسطة:</span>
                <span class="voucher-info-value">{{ $voucher->creator->name }}</span>
            </div>
            <div class="voucher-info-cell">
                <span class="voucher-info-label">تاريخ الإنشاء:</span>
                <span class="voucher-info-value">{{ \Carbon\Carbon::parse($voucher->created_at)->format('Y-m-d H:i') }}</span>
            </div>
        </div>
        @endif
    </div>

    {{-- Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 35%;">الصنف</th>
                <th style="width: 12%;">الكمية المرتجعة</th>
                <th style="width: 12%;">الوحدة</th>
                <th style="width: 13%;">سعر الوحدة</th>
                <th style="width: 13%;">الإجمالي</th>
                <th style="width: 10%;">الحالة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($voucher->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="text-right">
                    <strong>{{ $item->product->name }}</strong>
                    @if($item->product->name_en)
                        <br><span class="text-muted">{{ $item->product->name_en }}</span>
                    @endif
                    @if($item->reason)
                        <br><span class="text-muted" style="font-size: 9pt;">سبب الإرجاع: {{ $item->reason }}</span>
                    @endif
                </td>
                <td>{{ number_format($item->quantity, 2) }}</td>
                <td>{{ $item->product->unit ?? 'وحدة' }}</td>
                <td>{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-bold">{{ number_format($item->total_price, 2) }}</td>
                <td>
                    @if($item->condition === 'good')
                        <span style="color: #059669;">صالح</span>
                    @elseif($item->condition === 'damaged')
                        <span style="color: #dc2626;">تالف</span>
                    @else
                        <span style="color: #f59e0b;">متوسط</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals">
        <div class="totals-row grand-total">
            <div class="totals-label">إجمالي قيمة المرتجعات:</div>
            <div class="totals-value">{{ number_format($voucher->total_amount, 2) }} ج.م</div>
        </div>
    </div>

    {{-- Return Reason --}}
    @if($voucher->return_reason)
    <div class="notes">
        <div class="notes-label">سبب الإرجاع:</div>
        <div class="notes-text">{{ $voucher->return_reason }}</div>
    </div>
    @endif

    {{-- Notes --}}
    @if($voucher->notes)
    <div class="notes">
        <div class="notes-label">ملاحظات:</div>
        <div class="notes-text">{{ $voucher->notes }}</div>
    </div>
    @endif

    {{-- Signatures --}}
    <div class="signatures">
        <div class="signature-row">
            <div class="signature-cell">
                <div class="signature-line">المحاسب</div>
            </div>
            <div class="signature-cell">
                <div class="signature-line">المدير</div>
            </div>
            <div class="signature-cell">
                <div class="signature-line">المُرجع</div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>تم الطباعة بتاريخ: {{ \Carbon\Carbon::now()->format('Y-m-d H:i') }}</p>
        <p>نظام إدارة المخزون - جميع الحقوق محفوظة</p>
    </div>
</body>
</html>
