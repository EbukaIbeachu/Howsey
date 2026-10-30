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
            margin: 18px auto 0;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .toolbar button,
        .toolbar a {
            border: 0;
            border-radius: 999px;
            padding: 11px 20px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            text-align: center;
            display: inline-block;
        }
        .toolbar .btn-download-pdf {
            background: #16a34a;
            color: #ffffff;
        }
        .toolbar .btn-download-png {
            background: #0f766e;
            color: #ffffff;
        }
        .toolbar .btn-print-receipt {
            background: {{ $brandColor }};
            color: {{ $brandTextColor }};
        }
        .toolbar .btn-close-receipt {
            background: #e5e7eb;
            color: #111827;
        }
        .toolbar .btn-download-pdf:hover,
        .toolbar .btn-download-png:hover,
        .toolbar .btn-print-receipt:hover,
        .toolbar .btn-close-receipt:hover {
            opacity: 0.9;
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
                justify-content: center;
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
            .details-wrap {
                overflow-x: visible;
            }
            .details-table,
            .details-table tbody,
            .details-table tr,
            .details-table td {
                display: block;
                width: 100%;
                min-width: 0;
            }
            .details-table thead {
                display: none;
            }
            .details-table tr {
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                overflow: hidden;
            }
            .details-table td {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 12px;
                border: 0;
                border-bottom: 1px solid #e5e7eb;
            }
            .details-table td:last-child {
                border-bottom: 0;
            }
            .details-table td::before {
                content: attr(data-label);
                color: #6b7280;
                font-size: 10px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                flex: 0 0 auto;
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
                        <td data-label="Description">Rent payment receipt</td>
                        <td data-label="Reference">{{ $payment->reference ?: 'PAY-'.$payment->id }}</td>
                        <td data-label="Method">{{ $payment->payment_method ? ucfirst(str_replace('_', ' ', $payment->payment_method)) : 'N/A' }}</td>
                        <td data-label="Due Date">{{ optional($payment->due_date)->format('F d, Y') ?? 'N/A' }}</td>
                        <td data-label="Amount" class="amount">₦{{ number_format((float) $payment->amount, 2) }}</td>
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
    <div class="toolbar">
        <a href="{{ route('payments.receipt', ['payment' => $payment, 'download' => 1]) }}" class="btn-download-pdf" data-filename="payment-receipt-{{ $payment->id }}.pdf">Download Receipt (PDF)</a>
        <button type="button" class="btn-download-png" style="display:none;">Download Image (PNG)</button>
        <button type="button" class="btn-print-receipt">Print Receipt</button>
        <a href="{{ $backUrl }}" class="btn-close-receipt">Close Receipt</a>
    </div>
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

            const printButton = document.querySelector('.btn-print-receipt');
            if (printButton) {
                printButton.addEventListener('click', function () {
                    shouldReturnAfterPrint = true;
                    window.print();
                });
            }

            // Mobile browsers (especially in-app webviews) often ignore a PDF
            // download triggered by a plain navigation, so the tap appears to
            // do nothing. Fetch the PDF and hand it to the browser as a blob,
            // falling back to the plain link if anything goes wrong.
            const pdfLink = document.querySelector('.btn-download-pdf');
            if (pdfLink && window.fetch && window.URL && URL.createObjectURL) {
                pdfLink.addEventListener('click', function (event) {
                    event.preventDefault();

                    const originalText = pdfLink.textContent;
                    pdfLink.textContent = 'Preparing download...';

                    fetch(pdfLink.href, { credentials: 'same-origin' })
                        .then(function (response) {
                            const contentType = response.headers.get('content-type') || '';

                            if (!response.ok || contentType.indexOf('pdf') === -1) {
                                throw new Error('Receipt download failed with status ' + response.status);
                            }

                            return response.blob();
                        })
                        .then(function (blob) {
                            const url = URL.createObjectURL(blob);
                            const link = document.createElement('a');
                            link.href = url;
                            link.download = pdfLink.getAttribute('data-filename') || 'payment-receipt-' + paymentId + '.pdf';
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                            setTimeout(function () {
                                URL.revokeObjectURL(url);
                            }, 10000);
                        })
                        .catch(function (err) {
                            console.error('Receipt download failed:', err);
                            window.location.href = pdfLink.href;
                        })
                        .finally(function () {
                            pdfLink.textContent = originalText;
                        });
                });
            }

            // On mobile, also offer a PNG capture of the receipt alongside the PDF.
            const pngBtn = document.querySelector('.btn-download-png');

            if (isMobile && pngBtn) {
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
