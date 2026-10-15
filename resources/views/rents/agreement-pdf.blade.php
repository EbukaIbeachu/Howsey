<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tenancy Agreement - {{ $rent->tenant->full_name }}</title>
    <style>
        @page {
            margin: 24mm 18mm;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            line-height: 1.55;
            color: #111111;
        }
        .title {
            text-align: center;
            text-transform: uppercase;
            text-decoration: underline;
            font-size: 15pt;
            font-weight: bold;
            margin: 0 0 24px;
        }
        .section {
            margin-bottom: 14px;
            text-align: justify;
        }
        .center-line {
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            margin: 18px 0;
        }
        .bold {
            font-weight: bold;
        }
        .uppercase {
            text-transform: uppercase;
        }
        .clause-list,
        .sub-clause {
            margin: 8px 0 0 18px;
            padding: 0;
        }
        .clause-list li,
        .sub-clause li {
            margin-bottom: 8px;
        }
        .sub-clause {
            list-style-type: lower-alpha;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 28px;
        }
        .signature-table td {
            width: 50%;
            vertical-align: top;
            padding-right: 24px;
        }
        .signature-line {
            margin-top: 44px;
            border-top: 1px solid #000000;
            padding-top: 6px;
        }
        .signature-logo {
            max-width: 120px;
            max-height: 70px;
            display: block;
            margin-bottom: 10px;
            object-fit: contain;
            filter: grayscale(100%) contrast(1.05);
        }
        .small-note {
            font-size: 10pt;
            font-style: italic;
        }
        .watermark {
            position: fixed;
            top: 42%;
            left: 8%;
            right: 8%;
            text-align: center;
            transform: rotate(-28deg);
            font-size: 26pt;
            font-weight: bold;
            letter-spacing: 0.22em;
            color: rgba(124, 58, 237, 0.14);
            z-index: -1;
        }
    </style>
</head>
<body>
    @php
        $agreementDate = $rent->start_date ?? $rent->created_at ?? now();
        $agreementCompany = $rent->company ?? $rent->unit?->building?->company;
        $agreementCompanyName = $agreementCompany?->name ?: 'HOWSEY PROPERTY MANAGEMENT';
        $agreementCompanyLogoPath = null;

        if ($agreementCompany?->settings?->logo_path) {
            $candidateLogoPath = storage_path('app/public/'.$agreementCompany->settings->logo_path);
            if (file_exists($candidateLogoPath)) {
                $agreementCompanyLogoPath = $candidateLogoPath;
            }
        }

        $rentIntervalText = match ($rent->rent_duration_value) {
            \App\Models\Rent::DURATION_DAILY => 'per day',
            \App\Models\Rent::DURATION_WEEKLY => 'per week',
            \App\Models\Rent::DURATION_MONTHLY => 'per month',
            default => 'per year',
        };
    @endphp

    @if(!empty($agreementWatermarkText))
        <div class="watermark">{{ $agreementWatermarkText }}</div>
    @endif

    <div class="title">Tenancy Agreement</div>

    <div class="section">
        THIS TENANCY AGREEMENT is made this <span class="bold">{{ $agreementDate->format('jS') }}</span> day of <span class="bold">{{ $agreementDate->format('F, Y') }}</span>.
    </div>

    <div class="center-line">Between</div>

    <div class="section">
        <span class="bold uppercase">{{ $agreementCompanyName }}</span> (Acting as Attorney/Agent to the Landlord) of {{ $rent->unit->building->address }} (hereinafter referred to as <span class="bold">"THE LANDLORD"</span> which expression shall where the context so admits include its successors-in-title and assigns) of the <span class="bold">ONE PART</span>.
    </div>

    <div class="center-line">And</div>

    <div class="section">
        <span class="bold uppercase">{{ $rent->tenant->full_name }}</span> of {{ $rent->unit->building->address }} (hereinafter referred to as <span class="bold">"THE TENANT"</span> which expression shall where the context so admits include his/her heirs, personal representatives, and assigns) of the <span class="bold">OTHER PART</span>.
    </div>

    <div class="section">
        <span class="bold">WHEREAS:</span>
        <ol class="clause-list">
            <li>The Landlord is the beneficial owner of the property known as <span class="bold">{{ $rent->unit->building->name }}, Unit {{ $rent->unit->unit_number }}</span> situate at <span class="bold">{{ $rent->unit->building->address }}</span> (hereinafter referred to as <span class="bold">"THE DEMISED PREMISES"</span>).</li>
            <li>
                The Landlord has agreed to let and the Tenant has agreed to take the Demised Premises
                @if($rent->end_date)
                    for the lease period commencing on <span class="bold">{{ $rent->start_date->format('jS F, Y') }}</span> and ending on <span class="bold">{{ $rent->end_date->format('jS F, Y') }}</span>.
                @else
                    on an ongoing basis commencing on <span class="bold">{{ $rent->start_date->format('jS F, Y') }}</span>.
                @endif
            </li>
        </ol>
    </div>

    <div class="section">
        <span class="bold">NOW THIS AGREEMENT WITNESSETH AS FOLLOWS:</span>
    </div>

    <div class="section">
        1. <span class="bold">CONSIDERATION</span><br>
        IN CONSIDERATION of the Rent sum of <span class="bold">N{{ number_format($rent->annual_amount, 2) }} ({{ ucwords(\App\Support\NumberSpellout::spellout($rent->annual_amount)) }} Naira Only)</span> {{ $rentIntervalText }} paid by the Tenant to the Landlord (the receipt whereof the Landlord hereby acknowledges), the Landlord hereby lets the Demised Premises to the Tenant.
    </div>

    <div class="section">
        2. <span class="bold">THE TENANT COVENANTS WITH THE LANDLORD AS FOLLOWS:</span>
        <ol class="sub-clause">
            <li>To pay the reserved rent in advance on the due date without any deduction or set-off. <span class="bold">Rent duration is {{ strtolower($rent->rent_duration_label) }}, and payment is due {{ strtolower($rent->due_schedule_label) }}.</span></li>
            <li>To pay all utility bills including electricity, water, waste disposal, and service charges consumed on the premises during the tenancy.</li>
            <li>To keep the interior of the Demised Premises, fixtures, and fittings in good and tenantable repair and condition (fair wear and tear excepted).</li>
            <li>Not to make any structural alteration or addition to the Demised Premises without the prior written consent of the Landlord.</li>
            <li>Not to assign, sublet, or part with possession of the Demised Premises or any part thereof without the written consent of the Landlord.</li>
            <li>To use the Demised Premises for RESIDENTIAL PURPOSES ONLY and not for any illegal or immoral purpose.</li>
            <li>To permit the Landlord or his agents at reasonable hours of the day to enter upon and view the condition of the Demised Premises.</li>
            <li>Not to do or permit to be done on the premises anything which may become a nuisance or annoyance to the Landlord or occupiers of adjoining premises.</li>
            <li>To yield up the Demised Premises at the expiration or sooner determination of the tenancy in good and tenantable repair.</li>
        </ol>
    </div>

    <div class="section">
        3. <span class="bold">THE LANDLORD COVENANTS WITH THE TENANT AS FOLLOWS:</span>
        <ol class="sub-clause">
            <li>To pay all rates, ground rents, and taxes payable on the property.</li>
            <li>That the Tenant paying the rent and performing the covenants herein contained shall peaceably hold and enjoy the Demised Premises during the term without any interruption by the Landlord or any person claiming through him.</li>
            <li>To keep the exterior and structural parts of the building in good repair.</li>
        </ol>
    </div>

    <div class="section">
        4. <span class="bold">PROVISO FOR RE-ENTRY</span><br>
        PROVIDED ALWAYS that if the rent reserved or any part thereof shall be in arrears for twenty-one (21) days after becoming due (whether legally demanded or not) or if there be any breach of the Tenant's covenants, it shall be lawful for the Landlord to re-enter the Demised Premises and the tenancy shall determine, but without prejudice to the right of action of the Landlord in respect of any antecedent breach.
    </div>

    <div class="section">
        5. <span class="bold">NOTICE TO QUIT</span><br>
        Either party may terminate this tenancy upon the expiration of the term or by giving <span class="bold">Six (6) Months</span> written notice to the other party.
    </div>

    <div class="section">
        6. <span class="bold">GOVERNING LAW</span><br>
        This Agreement shall be governed by and construed in accordance with the Laws of the Federation of Nigeria and the Tenancy Laws of the State.
    </div>

    <div class="section">
        <span class="bold">IN WITNESS WHEREOF</span> the parties have hereunto set their hands and seals the day and year first above written.
    </div>

    <table class="signature-table">
        <tr>
            <td>
                SIGNED by the within named LANDLORD
                <div class="signature-line">
                    @if($agreementCompanyLogoPath)
                        <img src="{{ $agreementCompanyLogoPath }}" alt="{{ $agreementCompanyName }} signature logo" class="signature-logo">
                    @endif
                    <span class="bold">{{ $agreementCompanyName }}</span>
                </div>
            </td>
            <td>
                IN THE PRESENCE OF:<br><br>
                <span class="bold">Noyb Fundamentals</span><br>
                <span class="small-note">(System Creator & Proxy)</span>
            </td>
        </tr>
        <tr>
            <td style="padding-top: 30px;">
                SIGNED by the within named TENANT
                <div class="signature-line">
                    <span class="bold">{{ $rent->tenant->full_name }}</span>
                </div>
            </td>
            <td style="padding-top: 30px;">
                IN THE PRESENCE OF:<br><br>
                <span class="bold">Noyb Fundamentals</span><br>
                <span class="small-note">(System Creator & Proxy)</span>
            </td>
        </tr>
    </table>
</body>
</html>
