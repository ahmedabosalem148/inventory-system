<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>شيك {{ $payment->cheque_number }}</title>
    <style>
        @page {
            size: 216mm 102mm; /* Standard cheque size */
            margin: 0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            direction: rtl;
            width: 216mm;
            height: 102mm;
            position: relative;
            padding: 15mm 20mm;
        }
        .cheque-container {
            width: 100%;
            height: 100%;
            position: relative;
        }
        .date-section {
            position: absolute;
            top: 15mm;
            left: 20mm;
            font-size: 12px;
        }
        .amount-words {
            position: absolute;
            top: 35mm;
            right: 40mm;
            font-size: 14px;
            font-weight: bold;
            max-width: 140mm;
        }
        .amount-numbers {
            position: absolute;
            top: 35mm;
            left: 20mm;
            font-size: 16px;
            font-weight: bold;
            border-bottom: 2px solid #000;
            padding-bottom: 2px;
        }
        .payee {
            position: absolute;
            top: 50mm;
            right: 50mm;
            font-size: 13px;
            font-weight: bold;
        }
        .signature {
            position: absolute;
            bottom: 15mm;
            left: 30mm;
            font-size: 11px;
            text-align: center;
        }
        .signature-line {
            width: 60mm;
            border-top: 1px solid #000;
            margin-top: 5mm;
        }
        .cheque-number {
            position: absolute;
            top: 10mm;
            right: 20mm;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="cheque-container">
        <div class="cheque-number">
            رقم الشيك: {{ $payment->cheque_number }}
        </div>

        <div class="date-section">
            {{ \Carbon\Carbon::parse($payment->payment_date)->format('d / m / Y') }}
        </div>

        <div class="amount-words">
            {{ \App\Helpers\NumberToWords::convert($payment->amount) }} جنيه مصري فقط
        </div>

        <div class="amount-numbers">
            {{ number_format($payment->amount, 2) }} ج.م
        </div>

        <div class="payee">
            {{ $payment->customer?->name ?? 'النقد' }}
        </div>

        <div class="signature">
            التوقيع
            <div class="signature-line"></div>
        </div>
    </div>
</body>
</html>
