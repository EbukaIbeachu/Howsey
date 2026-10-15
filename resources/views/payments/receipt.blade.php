<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payment Receipt {{ $receiptNumber }}</title>
    @php
        $brandName = $branding['app_name'] ?? optional(optional(optional($payment->rent)->unit)->building)->company->name ?? 'HOWSEY';
        $brandColor = $branding['primary_color'] ?? '#7c3aed';
        $brandTextColor = $branding['primary_text_color'] ?? '#ffffff';
        $receiptHeader = $branding['receipt_header'] ?? 'Official Payment Receipt';
    @endphp
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
            background: #f8fafc;
            padding: 24px;
        }
        * {
            box-sizing: border-box;
        }
        .receipt-shell {
            background: #ffffff;
            max-width: 880px;
            margin: 0 auto;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }
        .toolbar {
            max-width: 880px;
            margin: 0 auto 16px;
            display: flex;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 12px;
        }
        .toolbar button,
        .toolbar a {
            border: 0;
            border-radius: 999px;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            text-align: center;
        }
        .toolbar button {
            background: {{ $brandColor }};
            color: {{ $brandTextColor }};
        }
        .toolbar a {
            background: #e5e7eb;
            color: #111827;
        }
        .receipt-header {
            border-bottom: 2px solid {{ $brandColor }};
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .brand {
            font-size: 24px;
            font-weight: 700;
            color: {{ $brandColor }};
            margin: 0 0 4px;
        }
        .subtitle {
            color: #6b7280;
            margin: 0;
        }
        .meta-table,
        .details-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            width: 50%;
            vertical-align: top;
            padding: 0 0 14px;
        }
        .label {
            color: #6b7280;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 4px;
        }
        .value {
            font-size: 13px;
            font-weight: 600;
        }
        .details-table th,
        .details-table td {
            border: 1px solid #e5e7eb;
            padding: 10px 12px;
            text-align: left;
        }
        .details-table th {
            background: #f5f3ff;
            color: #4c1d95;
            font-size: 11px;
            text-transform: uppercase;
        }
        .amount {
            font-size: 22px;
            font-weight: 700;
            color: #16a34a;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
            font-size: 11px;
            font-weight: 700;
        }
        .footer {
            margin-top: 28px;
            color: #6b7280;
            font-size: 11px;
        }
        .details-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        @media (max-width: 640px) {
            body {
                padding: 12px;
            }
            .receipt-shell {
                padding: 18px 14px;
                border-radius: 10px;
            }
            .toolbar {
                justify-content: stretch;
                gap: 8px;
            }
            .toolbar button,
            .toolbar a {
                flex: 1 1 100%;
                width: 100%;
            }
            .brand {
                font-size: 20px;
            }
            .meta-table,
            .meta-table tbody,
            .meta-table tr,
            .meta-table td {
                display: block;
                width: 100%;
            }
            .meta-table td {
                padding: 0 0 12px;
            }
            .details-table {
                min-width: 560px;
            }
            .amount {
                font-size: 18px;
            }
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .toolbar {
                display: none;
            }
            .receipt-shell {
                box-shadow: none;
                border-radius: 0;
                border: 0;
                max-width: none;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    @if(($renderMode ?? 'web') === 'web')
    <div class="toolbar">
        <a href="{{ route('payments.receipt', ['payment' => $payment, 'download' => 1]) }}" class="btn-download-pdf">Download PDF</a>
        <button type="button" class="btn-download-png" style="display:none;background:#16a34a;color:#fff;">Download Image (PNG)</button>
        <button type="button" onclick="window.print()">Print Receipt</button>
        <a href="{{ $backUrl }}">Close Receipt</a>
    </div>
    @endif

    <div class="receipt-shell">
        <div class="receipt-header">
            <h1 class="brand">{{ $brandName }}</h1>
            <p class="subtitle">{{ $receiptHeader }}</p>
        </div>

        <table class="meta-table">
            <tr>
                <td>
                    <div class="label">Receipt Number</div>
                    <div class="value">{{ $receiptNumber }}</div>
                </td>
                <td>
                    <div class="label">Payment Status</div>
                    <div class="value"><span class="badge">{{ strtoupper((string) $payment->status) }}</span></div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">Tenant</div>
                    <div class="value">{{ $issuedTo }}</div>
                </td>
                <td>
                    <div class="label">Payment Date</div>
                    <div class="value">{{ optional($payment->payment_date)->format('F d, Y') ?? 'N/A' }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">Building</div>
                    <div class="value">{{ optional(optional(optional($payment->rent)->unit)->building)->name ?? 'N/A' }}</div>
                </td>
                <td>
                    <div class="label">Unit</div>
                    <div class="value">{{ optional(optional($payment->rent)->unit)->unit_number ?? 'N/A' }}</div>
                </td>
            </tr>
        </table>

        <div class="details-wrap">
            <table class="details-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Reference</th>
                        <th>Method</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Rent payment receipt</td>
                        <td>{{ $payment->reference ?: 'PAY-'.$payment->id }}</td>
                        <td>{{ $payment->payment_method ? ucfirst(str_replace('_', ' ', $payment->payment_method)) : 'N/A' }}</td>
                        <td>{{ optional($payment->due_date)->format('F d, Y') ?? 'N/A' }}</td>
                        <td class="amount">N{{ number_format((float) $payment->amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="footer">
            <div>Generated by: {{ $generatedBy }}</div>
            <div>Generated on: {{ now()->format('F d, Y h:i A') }}</div>
        </div>
    </div>

    @if(($renderMode ?? 'web') === 'web')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        (function () {
            const backUrl = @json($backUrl);
            const paymentId = {{ $payment->id }};
            let shouldReturnAfterPrint = false;

            const isMobile = window.matchMedia('(max-width: 768px)').matches || /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent);

            function returnToPreviousPage() {
                if (window.history.length > 1) {
                    window.history.back();
                    return;
                }

                window.location.href = backUrl;
            }

            window.addEventListener('afterprint', function () {
                if (shouldReturnAfterPrint) {
                    returnToPreviousPage();
                }
            });

            const printButton = document.querySelector('.toolbar button');
            if (printButton) {
                printButton.addEventListener('click', function () {
                    shouldReturnAfterPrint = true;
                });
            }

            // On mobile, replace the PDF download with a PNG capture of the receipt.
            const pdfBtn = document.querySelector('.btn-download-pdf');
            const pngBtn = document.querySelector('.btn-download-png');

            if (isMobile && pdfBtn && pngBtn) {
                pdfBtn.style.display = 'none';
                pngBtn.style.display = 'inline-block';

                pngBtn.addEventListener('click', function () {
                    const shell = document.querySelector('.receipt-shell');
                    if (!shell || !window.html2canvas) {
                        alert('Image capture is not available right now. Please use Print instead.');
                        return;
                    }

                    // On mobile the details table overflows the narrow shell and
                    // would be clipped in the capture. Temporarily widen the shell
                    // so every column (including the amount) is rendered.
                    const wrap = shell.querySelector('.details-wrap');
                    const table = shell.querySelector('.details-table');
                    const captureWidth = Math.max(880, (table ? table.scrollWidth : 0) + 56);

                    const saved = {
                        wrapOverflow: wrap ? wrap.style.overflow : '',
                        shellWidth: shell.style.width,
                        shellMaxWidth: shell.style.maxWidth,
                        shellPosition: shell.style.position,
                        shellLeft: shell.style.left,
                        shellTop: shell.style.top,
                        shellZIndex: shell.style.zIndex
                    };

                    if (wrap) {
                        wrap.style.overflow = 'visible';
                    }
                    shell.style.position = 'absolute';
                    shell.style.left = '-9999px';
                    shell.style.top = '0';
                    shell.style.zIndex = '-1';
                    shell.style.maxWidth = 'none';
                    shell.style.width = captureWidth + 'px';

                    const originalText = pngBtn.textContent;
                    pngBtn.disabled = true;
                    pngBtn.textContent = 'Generating image...';

                    html2canvas(shell, {
                        scale: 2,
                        useCORS: true,
                        backgroundColor: '#ffffff',
                        windowWidth: captureWidth
                    }).then(function (canvas) {
                        const link = document.createElement('a');
                        link.download = 'payment-receipt-' + paymentId + '.png';
                        link.href = canvas.toDataURL('image/png');
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }).catch(function (err) {
                        console.error('Receipt PNG generation failed:', err);
                        alert('Could not generate the receipt image. Please try again or use Print.');
                    }).finally(function () {
                        if (wrap) {
                            wrap.style.overflow = saved.wrapOverflow;
                        }
                        shell.style.position = saved.shellPosition;
                        shell.style.left = saved.shellLeft;
                        shell.style.top = saved.shellTop;
                        shell.style.zIndex = saved.shellZIndex;
                        shell.style.maxWidth = saved.shellMaxWidth;
                        shell.style.width = saved.shellWidth;
                        pngBtn.disabled = false;
                        pngBtn.textContent = originalText;
                    });
                });
            }
        })();
    </script>
    @endif
</body>
</html>
