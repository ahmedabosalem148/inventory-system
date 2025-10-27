<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>كشف حساب {{ $customer->code }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 11px;
            color: #333;
            padding: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #6366f1;
        }
        .header h1 {
            font-size: 22px;
            color: #4338ca;
            margin-bottom: 8px;
        }
        .customer-info {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            background-color: #f0f1ff;
            padding: 10px;
            border-radius: 4px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            width: 20%;
            font-weight: bold;
            padding: 5px;
        }
        .info-value {
            display: table-cell;
            width: 30%;
            padding: 5px;
        }
        .period {
            text-align: center;
            font-size: 12px;
            margin-bottom: 15px;
            padding: 8px;
            background-color: #fef3c7;
            border: 1px solid #fbbf24;
        }
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }
        .ledger-table thead {
            background-color: #6366f1;
            color: white;
        }
        .ledger-table th {
            padding: 8px 5px;
            text-align: center;
            border: 1px solid #4338ca;
            font-size: 11px;
        }
        .ledger-table td {
            padding: 6px 5px;
            text-align: center;
            border: 1px solid #c7d2fe;
        }
        .ledger-table tbody tr:nth-child(even) {
            background-color: #f5f7ff;
        }
        .debit { color: #dc2626; font-weight: bold; }
        .credit { color: #16a34a; font-weight: bold; }
        .balance-positive { color: #16a34a; }
        .balance-negative { color: #dc2626; }
        .opening-balance {
            background-color: #dbeafe !important;
            font-weight: bold;
        }
        .summary {
            margin-top: 15px;
            padding: 15px;
            background-color: #f8fafc;
            border: 2px solid #6366f1;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 12px;
        }
        .summary-row.total {
            font-size: 14px;
            font-weight: bold;
            border-top: 2px solid #4338ca;
            padding-top: 10px;
            margin-top: 10px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>كشف حساب العميل</h1>
    </div>

    <div class="customer-info">
        <div class="info-row">
            <div class="info-label">كود العميل:</div>
            <div class="info-value">{{ $customer->code }}</div>
            <div class="info-label">اسم العميل:</div>
            <div class="info-value">{{ $customer->name }}</div>
        </div>
        @if($customer->phone || $customer->email)
        <div class="info-row">
            @if($customer->phone)
                <div class="info-label">الهاتف:</div>
                <div class="info-value">{{ $customer->phone }}</div>
            @endif
            @if($customer->email)
                <div class="info-label">البريد الإلكتروني:</div>
                <div class="info-value">{{ $customer->email }}</div>
            @endif
        </div>
        @endif
    </div>

    <div class="period">
        الفترة من <strong>{{ $from_date }}</strong> إلى <strong>{{ $to_date }}</strong>
    </div>

    <table class="ledger-table">
        <thead>
            <tr>
                <th style="width: 8%;">#</th>
                <th style="width: 12%;">التاريخ</th>
                <th style="width: 40%;">الوصف</th>
                <th style="width: 10%;">مدين</th>
                <th style="width: 10%;">دائن</th>
                <th style="width: 10%;">الرصيد</th>
                <th style="width: 10%;">المرجع</th>
            </tr>
        </thead>
        <tbody>
            <!-- Opening Balance -->
            <tr class="opening-balance">
                <td colspan="3" style="text-align: right; padding-right: 10px;">
                    <strong>الرصيد الافتتاحي</strong>
                </td>
                <td>-</td>
                <td>-</td>
                <td class="{{ $opening_balance >= 0 ? 'balance-positive' : 'balance-negative' }}">
                    {{ number_format(abs($opening_balance), 2) }} 
                    {{ $opening_balance >= 0 ? 'دائن' : 'مدين' }}
                </td>
                <td>-</td>
            </tr>

            @php
                $running_balance = $opening_balance;
            @endphp

            @foreach($entries as $index => $entry)
                @php
                    $running_balance += ($entry->debit - $entry->credit);
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($entry->date)->format('Y-m-d') }}</td>
                    <td style="text-align: right;">{{ $entry->description }}</td>
                    <td class="debit">{{ $entry->debit > 0 ? number_format($entry->debit, 2) : '-' }}</td>
                    <td class="credit">{{ $entry->credit > 0 ? number_format($entry->credit, 2) : '-' }}</td>
                    <td class="{{ $running_balance >= 0 ? 'balance-positive' : 'balance-negative' }}">
                        {{ number_format(abs($running_balance), 2) }}
                    </td>
                    <td style="font-size: 9px;">{{ $entry->reference ?? '-' }}</td>
                </tr>
            @endforeach

            @if($entries->isEmpty())
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px; color: #6b7280;">
                        لا توجد حركات خلال هذه الفترة
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-row">
            <span>الرصيد الافتتاحي:</span>
            <strong class="{{ $opening_balance >= 0 ? 'balance-positive' : 'balance-negative' }}">
                {{ number_format(abs($opening_balance), 2) }} ج.م
                ({{ $opening_balance >= 0 ? 'دائن' : 'مدين' }})
            </strong>
        </div>
        <div class="summary-row">
            <span>إجمالي المدين:</span>
            <strong class="debit">{{ number_format($entries->sum('debit'), 2) }} ج.م</strong>
        </div>
        <div class="summary-row">
            <span>إجمالي الدائن:</span>
            <strong class="credit">{{ number_format($entries->sum('credit'), 2) }} ج.م</strong>
        </div>
        <div class="summary-row total">
            <span>الرصيد الختامي:</span>
            @php
                $closing_balance = $opening_balance + $entries->sum('debit') - $entries->sum('credit');
            @endphp
            <strong class="{{ $closing_balance >= 0 ? 'balance-positive' : 'balance-negative' }}">
                {{ number_format(abs($closing_balance), 2) }} ج.م
                ({{ $closing_balance >= 0 ? 'دائن' : 'مدين' }})
            </strong>
        </div>
    </div>

    <div class="footer">
        <div>طُبع بواسطة: {{ auth()->user()->name }}</div>
        <div>تاريخ الطباعة: {{ now()->format('Y-m-d H:i:s') }}</div>
    </div>
</body>
</html>
