<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uploaded Agreement - {{ $rent->tenant->full_name }}</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f3ff;
            color: #1f2937;
        }
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 20px;
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 5;
        }
        .toolbar-title {
            font-weight: 700;
        }
        .toolbar-meta {
            font-size: 0.92rem;
            color: #6b7280;
        }
        .toolbar-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .toolbar-actions a,
        .toolbar-actions button {
            border: 0;
            border-radius: 999px;
            padding: 10px 16px;
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-view {
            background: #e0f2fe;
            color: #0369a1;
        }
        .btn-download {
            background: #dcfce7;
            color: #047857;
        }
        .btn-request {
            background: #fef3c7;
            color: #b45309;
        }
        .btn-delete {
            background: #fee2e2;
            color: #b91c1c;
        }
        .viewer-shell {
            position: relative;
            min-height: calc(100vh - 90px);
            padding: 16px;
        }
        .viewer-frame,
        .viewer-image {
            width: 100%;
            min-height: calc(100vh - 122px);
            border: 0;
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 16px 38px rgba(15, 23, 42, 0.08);
        }
        .viewer-image {
            object-fit: contain;
        }
        .viewer-watermark {
            position: absolute;
            inset: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            overflow: hidden;
            border-radius: 18px;
        }
        .viewer-watermark span {
            transform: rotate(-28deg);
            font-size: clamp(24px, 4vw, 46px);
            font-weight: 800;
            letter-spacing: 0.24em;
            color: rgba(124, 58, 237, 0.16);
            text-align: center;
            white-space: nowrap;
        }
        @media (max-width: 767.98px) {
            .toolbar {
                padding: 14px;
            }
            .viewer-shell {
                padding: 12px;
            }
            .viewer-frame,
            .viewer-image {
                min-height: calc(100vh - 168px);
            }
            .viewer-watermark span {
                white-space: normal;
                line-height: 1.25;
                letter-spacing: 0.12em;
                padding: 0 12px;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <div class="toolbar-title">Uploaded Agreement File</div>
            <div class="toolbar-meta">{{ $rent->tenant->full_name }} • Agreement #{{ $rent->id }}</div>
        </div>
        <div class="toolbar-actions">
            <a href="{{ route('rents.agreement', $rent) }}" class="btn-view">Back to Agreement</a>
            @if($canDownloadUploadedAgreement)
                <a href="{{ route('rents.download-agreement', $rent) }}" class="btn-download">Download File</a>
            @elseif($canRequestAgreementDownload)
                <form action="{{ route('manager-edit-requests.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="record_type" value="rent">
                    <input type="hidden" name="record_id" value="{{ $rent->id }}">
                    <input type="hidden" name="request_kind" value="agreement_download">
                    <input type="hidden" name="reason" value="Manager requested permission to download the uploaded agreement file.">
                    <button type="submit" class="btn-request">Request Download</button>
                </form>
            @endif
            @if($canDeleteUploadedAgreement)
                <form action="{{ route('rents.delete-uploaded-agreement', $rent) }}" method="POST" onsubmit="return confirm('Delete the uploaded agreement file from this lease record?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-delete">Delete File</button>
                </form>
            @elseif($canRequestAgreementDelete)
                <form action="{{ route('manager-edit-requests.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="record_type" value="rent">
                    <input type="hidden" name="record_id" value="{{ $rent->id }}">
                    <input type="hidden" name="request_kind" value="agreement_delete">
                    <input type="hidden" name="reason" value="Manager requested permission to delete the uploaded agreement file.">
                    <button type="submit" class="btn-delete">Request Delete</button>
                </form>
            @endif
        </div>
    </div>

    <div class="viewer-shell">
        @if($isPdf)
            <iframe src="{{ $fileUrl }}#toolbar=1&navpanes=0" class="viewer-frame" title="Uploaded agreement preview"></iframe>
        @elseif($isImage)
            <img src="{{ $fileUrl }}" alt="Uploaded agreement preview" class="viewer-image">
        @endif

        @if(!empty($agreementWatermarkText))
            <div class="viewer-watermark">
                <span>{{ $agreementWatermarkText }}</span>
            </div>
        @endif
    </div>
</body>
</html>
