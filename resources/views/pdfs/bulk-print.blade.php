<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>طباعة جماعية - {{ $type }}</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            direction: rtl;
            font-size: 11px;
            color: #333;
        }
        .page-break {
            page-break-after: always;
        }
        .document {
            padding: 20px;
            margin-bottom: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2563eb;
        }
        .header h2 {
            font-size: 18px;
            color: #1e40af;
            margin-bottom: 5px;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            width: 25%;
            padding: 5px;
            background-color: #f3f4f6;
            font-weight: bold;
            border: 1px solid #d1d5db;
            font-size: 10px;
        }
        .info-value {
            display: table-cell;
            width: 25%;
            padding: 5px;
            border: 1px solid #d1d5db;
            font-size: 10px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9px;
        }
        .items-table thead {
            background-color: #2563eb;
            color: white;
        }
        .items-table th {
            padding: 6px 4px;
            text-align: center;
            border: 1px solid #1e40af;
        }
        .items-table td {
            padding: 5px 4px;
            text-align: center;
            border: 1px solid #d1d5db;
        }
        .total-box {
            float: left;
            width: 200px;
            padding: 8px;
            background-color: #2563eb;
            color: white;
            font-weight: bold;
            text-align: center;
            font-size: 12px;
        }
        .bulk-summary {
            clear: both;
            margin: 30px 0;
            padding: 15px;
            background-color: #f0f9ff;
            border: 2px solid #2563eb;
            text-align: center;
        }
        .bulk-summary h1 {
            font-size: 22px;
            color: #1e40af;
            margin-bottom: 15px;
        }
        .summary-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 15px;
        }
        .stat-box {
            padding: 10px 20px;
            background-color: white;
            border: 1px solid #93c5fd;
            border-radius: 4px;
        }
        .stat-label {
            font-size: 10px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
        }
    </style>
</head>
<body>
    <!-- Summary Page -->
    <div class="bulk-summary">
        <h1>تقرير الطباعة الجماعية</h1>
        <div style="font-size: 14px; margin: 10px 0;">
            النوع: 
            @switch($type)
                @case('issue-voucher') أذون صرف @break
                @case('return-voucher') أذون مرتجع @break
                @case('purchase-order') أوامر شراء @break
                @default {{ $type }}
            @endswitch
        </div>
        <div class="summary-stats">
            <div class="stat-box">
                <div class="stat-label">عدد المستندات</div>
                <div class="stat-value">{{ $documents->count() }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">إجمالي القيمة</div>
                <div class="stat-value">{{ number_format($documents->sum('total_amount'), 2) }} ج.م</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">تاريخ الطباعة</div>
                <div class="stat-value" style="font-size: 12px;">{{ now()->format('Y-m-d H:i') }}</div>
            </div>
        </div>
    </div>

    <div class="page-break"></div>

    <!-- Documents -->
    @foreach($documents as $index => $doc)
        <div class="document {{ $index < $documents->count() - 1 ? 'page-break' : '' }}">
            <div class="header">
                <h2>
                    @if(isset($doc->voucher_number))
                        @if($type === 'issue-voucher')
                            إذن صرف رقم {{ $doc->voucher_number }}
                        @elseif($type === 'return-voucher')
                            إذن مرتجع رقم {{ $doc->voucher_number }}
                        @endif
                    @elseif(isset($doc->order_number))
                        أمر شراء رقم {{ $doc->order_number }}
                    @endif
                </h2>
                <div style="font-size: 11px; color: #6b7280;">
                    المستند {{ $index + 1 }} من {{ $documents->count() }}
                </div>
            </div>

            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">الفرع</div>
                    <div class="info-value">{{ $doc->branch->name }}</div>
                    <div class="info-label">التاريخ</div>
                    <div class="info-value">
                        {{ \Carbon\Carbon::parse($doc->issue_date ?? $doc->return_date ?? $doc->order_date)->format('Y-m-d') }}
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">العميل/المورد</div>
                    <div class="info-value" colspan="3">
                        {{ $doc->customer?->name ?? $doc->supplier?->name ?? $doc->customer_name ?? 'غير محدد' }}
                    </div>
                </div>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 45%;">المنتج</th>
                        <th style="width: 12%;">الكمية</th>
                        <th style="width: 10%;">الوحدة</th>
                        <th style="width: 13%;">السعر</th>
                        <th style="width: 15%;">الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($doc->items as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td style="text-align: right;">{{ $item->product?->name ?? $item->product_name }}</td>
                        <td>{{ number_format($item->quantity, 2) }}</td>
                        <td>{{ $item->unit }}</td>
                        <td>{{ number_format($item->unit_price, 2) }}</td>
                        <td>{{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="total-box">
                الإجمالي: {{ number_format($doc->total_amount, 2) }} ج.م
            </div>
        </div>
    @endforeach
</body>
</html>
