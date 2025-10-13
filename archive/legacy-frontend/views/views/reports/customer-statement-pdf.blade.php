<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>كشف حساب عميل</title>
    <style>
        @page {
            margin: 20mm;
            size: A4 portrait;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11pt;
            direction: rtl;
            text-align: right;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 20pt;
        }
        .header p {
            margin: 5px 0;
            color: #7f8c8d;
        }
        .customer-info {
            background: #ecf0f1;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .customer-info table {
            width: 100%;
        }
        .customer-info td {
            padding: 5px;
            font-size: 11pt;
        }
        .customer-info strong {
            color: #2c3e50;
        }
        .summary-box {
            background: #3498db;
            color: white;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .summary-box table {
            width: 100%;
            color: white;
        }
        .summary-box td {
            padding: 8px;
            font-size: 12pt;
        }
        table.ledger {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.ledger th, table.ledger td {
            border: 1px solid #bdc3c7;
            padding: 8px;
            text-align: center;
        }
        table.ledger th {
            background-color: #34495e;
            color: white;
            font-weight: bold;
        }
        table.ledger tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .debit {
            color: #27ae60;
            font-weight: bold;
        }
        .credit {
            color: #e74c3c;
            font-weight: bold;
        }
        .balance-positive {
            color: #27ae60;
            font-weight: bold;
        }
        .balance-negative {
            color: #e74c3c;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9pt;
            color: #7f8c8d;
            border-top: 1px solid #bdc3c7;
            padding-top: 10px;
        }
        .signatures {
            margin-top: 40px;
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
            border-top: 1px solid #000;
            margin-top: 50px;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>كشف حساب عميل</h1>
        <p>نظام إدارة المخزون والحسابات</p>
    </div>

    <div class="customer-info">
        <table>
            <tr>
                <td><strong>كود العميل:</strong> {{ $customer->code }}</td>
                <td><strong>اسم العميل:</strong> {{ $customer->name }}</td>
            </tr>
            <tr>
                <td><strong>الهاتف:</strong> {{ $customer->phone ?? '-' }}</td>
                <td><strong>العنوان:</strong> {{ $customer->address ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>من تاريخ:</strong> {{ $request->date_from ?? 'البداية' }}</td>
                <td><strong>إلى تاريخ:</strong> {{ $request->date_to ?? 'النهاية' }}</td>
            </tr>
        </table>
    </div>

    <div class="summary-box">
        <table>
            <tr>
                <td><strong>إجمالي علية:</strong> {{ number_format($stats['total_debit'], 2) }} ج.م</td>
                <td><strong>إجمالي له:</strong> {{ number_format($stats['total_credit'], 2) }} ج.م</td>
                <td>
                    <strong>الرصيد النهائي:</strong> 
                    <span class="{{ $stats['final_balance'] >= 0 ? 'balance-positive' : 'balance-negative' }}">
                        {{ number_format($stats['final_balance'], 2) }} ج.م
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <table class="ledger">
        <thead>
            <tr>
                <th style="width: 10%;">التاريخ</th>
                <th style="width: 35%;">البيان</th>
                <th style="width: 15%;">علية (مدين)</th>
                <th style="width: 15%;">له (دائن)</th>
                <th style="width: 15%;">الرصيد</th>
                <th style="width: 10%;">المرجع</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td>{{ $entry->date->format('Y-m-d') }}</td>
                    <td style="text-align: right;">{{ $entry->description }}</td>
                    <td class="debit">
                        @if($entry->debit_aliah > 0)
                            {{ number_format($entry->debit_aliah, 2) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="credit">
                        @if($entry->credit_lah > 0)
                            {{ number_format($entry->credit_lah, 2) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="{{ $entry->running_balance >= 0 ? 'balance-positive' : 'balance-negative' }}">
                        {{ number_format($entry->running_balance, 2) }}
                    </td>
                    <td style="font-size: 8pt;">{{ $entry->ref_table }}-{{ $entry->ref_id }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">
                        لا توجد حركات في الفترة المحددة
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($entries->count() > 0)
            <tfoot>
                <tr style="background-color: #34495e; color: white; font-weight: bold;">
                    <td colspan="2" style="text-align: center;">الإجمالي</td>
                    <td class="debit">{{ number_format($stats['total_debit'], 2) }}</td>
                    <td class="credit">{{ number_format($stats['total_credit'], 2) }}</td>
                    <td class="{{ $stats['final_balance'] >= 0 ? 'balance-positive' : 'balance-negative' }}">
                        {{ number_format($stats['final_balance'], 2) }}
                    </td>
                    <td>-</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">
                المحاسب
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                المدير المالي
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                العميل
            </div>
        </div>
    </div>

    <div class="footer">
        طباعة في {{ now()->format('Y-m-d H:i:s') }} | نظام إدارة المخزون والحسابات
        @if($entries->count() > 0)
            <br>عدد الحركات: {{ $stats['entries_count'] }}
        @endif
    </div>
</body>
</html>
