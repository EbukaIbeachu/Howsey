<?php

namespace App\Http\Controllers;

use App\Models\AccountingEntry;
use App\Models\Building;
use App\Models\Company;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Rent;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Support\AuditLogService;
use App\Support\AutoEmailGenerator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataTransferController extends Controller
{
    /**
     * Only administrators may move data in or out of a workspace.
     */
    public function __construct()
    {
        $this->middleware('admin.export');
    }

    public function index()
    {
        return view('data.index', [
            'importTypes' => $this->importDefinitions(),
            'exportTables' => array_keys($this->exportDefinitions()),
        ]);
    }

    /**
     * Download a CSV template for a single record type.
     */
    public function template(Request $request, string $type)
    {
        $definitions = $this->importDefinitions();

        abort_unless(array_key_exists($type, $definitions), 404);

        $definition = $definitions[$type];
        $filename = 'howsey-'.$type.'-template.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = $definition['columns'];
        $example = $definition['example'];

        if (ob_get_level()) {
            ob_end_clean();
        }

        return response()->stream(function () use ($columns, $example) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns, ',', '"', '');
            fputcsv($file, $example, ',', '"', '');
            fclose($file);
        }, 200, $headers);
    }

    /**
     * Import uploaded CSVs in dependency order: Buildings -> Units -> Tenants -> Rents.
     */
    public function import(Request $request)
    {
        $definitions = $this->importDefinitions();

        $rules = [];
        foreach (array_keys($definitions) as $type) {
            $rules[$type] = 'nullable|file|max:8192';
        }

        $request->validate($rules);

        $report = [];
        $uploaded = [];

        foreach (array_keys($definitions) as $type) {
            if ($request->hasFile($type)) {
                $uploaded[$type] = $this->readCsvRows($request->file($type)->getRealPath());
            }
        }

        if (empty($uploaded)) {
            return back()->with('error', 'Please choose at least one CSV file to import.');
        }

        $company = Company::find(auth()->user()->company_id);

        foreach ($definitions as $type => $definition) {
            if (! isset($uploaded[$type])) {
                continue;
            }

            $result = ['type' => $definition['label'], 'created' => 0, 'skipped' => 0, 'errors' => []];

            foreach ($uploaded[$type] as $index => $row) {
                $line = $index + 2; // +1 header, +1 for one-based rows
                try {
                    $outcome = $this->importRow($type, $row, $company);
                } catch (\Throwable $e) {
                    $outcome = ['status' => 'error', 'message' => $e->getMessage()];
                }

                if ($outcome['status'] === 'created') {
                    $result['created']++;
                } elseif ($outcome['status'] === 'skipped') {
                    $result['skipped']++;
                    $result['skipped_notes'][] = "Row $line: ".$outcome['message'];
                } else {
                    $result['errors'][] = "Row $line: ".$outcome['message'];
                }
            }

            $report[] = $result;
        }

        app(AuditLogService::class)->log('data_imported', null, null, [
            'report' => $report,
        ]);

        return back()->with('import_report', $report);
    }

    /**
     * Export the whole workspace as a ZIP containing one CSV per table plus a manifest.
     */
    public function export()
    {
        $companyId = auth()->user()->company_id;
        $company = Company::find($companyId);

        $datasets = $this->exportDefinitions();
        $rows = [];

        foreach ($datasets as $name => $builder) {
            $rows[$name] = $builder($companyId);
        }

        $tmpBase = tempnam(sys_get_temp_dir(), 'howsey-export-');
        $tmpPath = $tmpBase.'.zip';
        @unlink($tmpBase);

        $zip = new \ZipArchive();
        $zip->open($tmpPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($rows as $name => $data) {
            $zip->addFromString($name.'.csv', $this->buildCsv($data['columns'], $data['rows']));
        }

        $zip->addFromString('manifest.txt', $this->buildManifest($company, $rows));
        $zip->close();

        $slug = $company?->slug ?: 'workspace';
        $filename = 'howsey-export-'.$slug.'-'.date('Y-m-d-His').'.zip';

        app(AuditLogService::class)->log('data_exported', null, null, [
            'tables' => array_keys($rows),
            'row_counts' => array_map(fn ($set) => count($set['rows']), $rows),
        ]);

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Import a single row for the given type.
     *
     * @return array{status: string, message?: string}
     */
    private function importRow(string $type, array $row, ?Company $company): array
    {
        return match ($type) {
            'buildings' => $this->importBuilding($row),
            'units' => $this->importUnit($row),
            'tenants' => $this->importTenant($row, $company),
            'rents' => $this->importRent($row),
            default => ['status' => 'error', 'message' => 'Unknown record type.'],
        };
    }

    private function importBuilding(array $row): array
    {
        $name = $this->value($row, 'name');
        if ($name === '') {
            return ['status' => 'error', 'message' => 'Missing building name.'];
        }

        if ($this->findBuildingByName($name)) {
            return ['status' => 'skipped', 'message' => "Building \"$name\" already exists."];
        }

        $building = Building::create([
            'name' => $name,
            'address_line1' => $this->value($row, 'address_line1') ?: 'N/A',
            'address_line2' => $this->value($row, 'address_line2') ?: null,
            'city' => $this->value($row, 'city') ?: 'N/A',
            'state' => $this->value($row, 'state') ?: 'N/A',
            'postal_code' => $this->value($row, 'postal_code') ?: null,
            'total_units' => max(1, (int) ($this->value($row, 'total_units') ?: 1)),
            'total_floors' => max(1, (int) ($this->value($row, 'total_floors') ?: 1)),
        ]);

        app(AuditLogService::class)->log('building_created', $building, null, $building->toArray());

        return ['status' => 'created'];
    }

    private function importUnit(array $row): array
    {
        $buildingName = $this->value($row, 'building_name');
        $unitNumber = $this->value($row, 'unit_number');

        if ($buildingName === '' || $unitNumber === '') {
            return ['status' => 'error', 'message' => 'Both building_name and unit_number are required.'];
        }

        $building = $this->findBuildingByName($buildingName);
        if (! $building) {
            return ['status' => 'error', 'message' => "Building \"$buildingName\" was not found."];
        }

        if ($this->findUnit($building, $unitNumber)) {
            return ['status' => 'skipped', 'message' => "Unit \"$unitNumber\" already exists in \"$buildingName\"."];
        }

        $status = strtoupper($this->value($row, 'status'));
        if (! in_array($status, ['AVAILABLE', 'OCCUPIED', 'MAINTENANCE'], true)) {
            $status = 'AVAILABLE';
        }

        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => $unitNumber,
            'floor' => $this->value($row, 'floor') ?: null,
            'bedrooms' => (int) ($this->value($row, 'bedrooms') ?: 0),
            'bathrooms' => (int) ($this->value($row, 'bathrooms') ?: 0),
            'status' => $status,
        ]);

        app(AuditLogService::class)->log('unit_created', $unit, null, $unit->toArray());

        return ['status' => 'created'];
    }

    private function importTenant(array $row, ?Company $company): array
    {
        $fullName = $this->value($row, 'full_name');
        $buildingName = $this->value($row, 'building_name');
        $unitNumber = $this->value($row, 'unit_number');

        if ($fullName === '' || $buildingName === '' || $unitNumber === '') {
            return ['status' => 'error', 'message' => 'full_name, building_name and unit_number are required.'];
        }

        $building = $this->findBuildingByName($buildingName);
        if (! $building) {
            return ['status' => 'error', 'message' => "Building \"$buildingName\" was not found."];
        }

        $unit = $this->findUnit($building, $unitNumber);
        if (! $unit) {
            return ['status' => 'error', 'message' => "Unit \"$unitNumber\" was not found in \"$buildingName\"."];
        }

        $existing = Tenant::where('unit_id', $unit->id)
            ->whereRaw('LOWER(full_name) = ?', [mb_strtolower($fullName)])
            ->first();
        if ($existing) {
            return ['status' => 'skipped', 'message' => "\"$fullName\" is already registered against unit \"$unitNumber\"."];
        }

        $active = $this->parseBool($this->value($row, 'active'), true);
        $moveIn = $this->parseDate($this->value($row, 'move_in_date')) ?: now()->toDateString();
        $moveOut = $this->parseDate($this->value($row, 'move_out_date'));

        if ($active && $unit->status !== 'AVAILABLE') {
            return ['status' => 'skipped', 'message' => "Unit \"$unitNumber\" is not available (status: {$unit->status})."];
        }

        $email = $this->value($row, 'email');
        if ($email === '') {
            $email = AutoEmailGenerator::generate(
                $fullName,
                'tenant',
                AutoEmailGenerator::domainForCompany($company)
            );
        }

        $attributes = [
            'unit_id' => $unit->id,
            'full_name' => $fullName,
            'phone' => $this->value($row, 'phone') ?: null,
            'email' => $email,
            'move_in_date' => $moveIn,
            'move_out_date' => $moveOut,
            'active' => $active,
        ];

        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $attributes['user_id'] = $existingUser->id;
        }

        $tenant = Tenant::create($attributes);
        app(AuditLogService::class)->log('tenant_created', $tenant, null, $tenant->toArray());

        if ($tenant->active) {
            $unit->update(['status' => 'OCCUPIED']);
        }

        return ['status' => 'created'];
    }

    private function importRent(array $row): array
    {
        $tenantName = $this->value($row, 'tenant_full_name');
        $buildingName = $this->value($row, 'building_name');
        $unitNumber = $this->value($row, 'unit_number');

        if ($tenantName === '' || $buildingName === '' || $unitNumber === '') {
            return ['status' => 'error', 'message' => 'tenant_full_name, building_name and unit_number are required.'];
        }

        $building = $this->findBuildingByName($buildingName);
        if (! $building) {
            return ['status' => 'error', 'message' => "Building \"$buildingName\" was not found."];
        }

        $unit = $this->findUnit($building, $unitNumber);
        if (! $unit) {
            return ['status' => 'error', 'message' => "Unit \"$unitNumber\" was not found in \"$buildingName\"."];
        }

        $tenant = Tenant::where('unit_id', $unit->id)
            ->whereRaw('LOWER(full_name) = ?', [mb_strtolower($tenantName)])
            ->first();

        if (! $tenant) {
            return ['status' => 'error', 'message' => "\"$tenantName\" was not found on unit \"$unitNumber\". Import tenants first."];
        }

        if (Rent::where('tenant_id', $tenant->id)->where('status', 'ACTIVE')->exists()) {
            return ['status' => 'skipped', 'message' => "\"$tenantName\" already has an active rental agreement."];
        }

        $amount = $this->parseAmount($this->value($row, 'annual_amount'));
        if ($amount === null) {
            return ['status' => 'error', 'message' => 'annual_amount is required and must be a number.'];
        }

        $duration = strtolower($this->value($row, 'rent_duration'));
        if (! in_array($duration, Rent::DURATIONS, true)) {
            $duration = Rent::DURATION_ANNUALLY;
        }

        $startDate = $this->parseDate($this->value($row, 'start_date'));
        if (! $startDate) {
            return ['status' => 'error', 'message' => 'start_date is required and must be a valid date.'];
        }

        $endDate = $this->parseDate($this->value($row, 'end_date'));

        $status = strtoupper($this->value($row, 'status'));
        if (! in_array($status, ['ACTIVE', 'TERMINATED', 'EXPIRED'], true)) {
            $status = 'ACTIVE';
        }

        $rent = Rent::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'annual_amount' => $amount,
            'rent_duration' => $duration,
            'due_day' => $this->deriveDueDay($startDate, $duration),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status,
        ]);

        app(AuditLogService::class)->log('rent_created', $rent, null, $rent->toArray());

        if ($rent->status === 'ACTIVE') {
            $unit->update(['status' => 'OCCUPIED']);
            $tenant->update(['unit_id' => $unit->id]);
        }

        return ['status' => 'created'];
    }

    private function findBuildingByName(string $name): ?Building
    {
        return Building::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])->first();
    }

    private function findUnit(Building $building, string $unitNumber): ?Unit
    {
        $target = mb_strtolower(trim($unitNumber));

        return Unit::where('building_id', $building->id)
            ->get()
            ->first(function ($unit) use ($target) {
                return mb_strtolower(trim((string) $unit->unit_number)) === $target;
            });
    }

    private function deriveDueDay(string $startDate, string $rentDuration): int
    {
        $start = Carbon::parse($startDate);

        return match ($rentDuration) {
            Rent::DURATION_DAILY => 1,
            Rent::DURATION_WEEKLY => (int) $start->dayOfWeekIso,
            default => (int) $start->day,
        };
    }

    /**
     * Read a CSV file into an array of associative rows keyed by the header.
     */
    private function readCsvRows(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');

        if (! $handle) {
            return $rows;
        }

        $header = null;
        while (($data = fgetcsv($handle, null, ',', '"', '')) !== false) {
            if ($header === null) {
                if (isset($data[0])) {
                    $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $data[0]);
                }
                $header = array_map(fn ($h) => strtolower(trim((string) $h)), $data);
                continue;
            }

            if (count($data) === 1 && trim((string) $data[0]) === '') {
                continue;
            }

            $row = [];
            foreach ($header as $index => $key) {
                if ($key === '') {
                    continue;
                }
                $row[$key] = $data[$index] ?? null;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function buildCsv(array $columns, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($handle, $columns, ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn ($value) => $value ?? '', $row), ',', '"', '');
        }
        rewind($handle);
        $contents = stream_get_contents($handle);
        fclose($handle);

        return $contents;
    }

    private function buildManifest(?Company $company, array $rows): string
    {
        $lines = [];
        $lines[] = 'HOWSEY data export';
        $lines[] = 'Workspace: '.($company?->name ?? 'Unknown');
        $lines[] = 'Workspace slug: '.($company?->slug ?? '-');
        $lines[] = 'Generated: '.now()->toDateTimeString();
        $lines[] = 'Generated by: '.(auth()->user()->email ?? '-');
        $lines[] = '';
        $lines[] = 'Files included:';
        foreach ($rows as $name => $data) {
            $lines[] = sprintf('  %s.csv - %d row(s)', $name, count($data['rows']));
        }
        $lines[] = '';
        $lines[] = 'All files are UTF-8 encoded CSV (comma separated) with a header row.';

        return implode("\n", $lines)."\n";
    }

    /**
     * @return array<string, callable(int): array{columns: array<int, string>, rows: array<int, array<int, mixed>>}>
     */
    private function exportDefinitions(): array
    {
        return [
            'buildings' => function (int $companyId) {
                $rows = Building::query()->orderBy('id')->get()->map(fn (Building $b) => [
                    $b->id,
                    $b->name,
                    $b->address_line1,
                    $b->address_line2,
                    $b->city,
                    $b->state,
                    $b->postal_code,
                    $b->total_units,
                    $b->total_floors,
                    optional($b->manager)->name,
                    optional($b->created_at)->toDateTimeString(),
                ])->all();

                return [
                    'columns' => ['id', 'name', 'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'total_units', 'total_floors', 'manager', 'created_at'],
                    'rows' => $rows,
                ];
            },
            'units' => function (int $companyId) {
                $rows = Unit::with('building')->orderBy('id')->get()->map(fn (Unit $u) => [
                    $u->id,
                    optional($u->building)->name,
                    $u->unit_number,
                    $u->floor,
                    $u->bedrooms,
                    $u->bathrooms,
                    $u->status,
                    optional($u->created_at)->toDateTimeString(),
                ])->all();

                return [
                    'columns' => ['id', 'building', 'unit_number', 'floor', 'bedrooms', 'bathrooms', 'status', 'created_at'],
                    'rows' => $rows,
                ];
            },
            'tenants' => function (int $companyId) {
                $rows = Tenant::with('unit.building')->orderBy('id')->get()->map(fn (Tenant $t) => [
                    $t->id,
                    $t->full_name,
                    $t->phone,
                    $t->email,
                    optional(optional($t->unit)->building)->name,
                    optional($t->unit)->unit_number,
                    optional($t->move_in_date)->toDateString(),
                    optional($t->move_out_date)->toDateString(),
                    $t->active ? 'yes' : 'no',
                    optional($t->created_at)->toDateTimeString(),
                ])->all();

                return [
                    'columns' => ['id', 'full_name', 'phone', 'email', 'building', 'unit_number', 'move_in_date', 'move_out_date', 'active', 'created_at'],
                    'rows' => $rows,
                ];
            },
            'rents' => function (int $companyId) {
                $rows = Rent::with(['tenant', 'unit.building'])->orderBy('id')->get()->map(fn (Rent $r) => [
                    $r->id,
                    optional($r->tenant)->full_name,
                    optional(optional($r->unit)->building)->name,
                    optional($r->unit)->unit_number,
                    $r->annual_amount,
                    $r->rent_duration,
                    $r->due_day,
                    optional($r->start_date)->toDateString(),
                    optional($r->end_date)->toDateString(),
                    $r->status,
                    optional($r->created_at)->toDateTimeString(),
                ])->all();

                return [
                    'columns' => ['id', 'tenant', 'building', 'unit_number', 'annual_amount', 'rent_duration', 'due_day', 'start_date', 'end_date', 'status', 'created_at'],
                    'rows' => $rows,
                ];
            },
            'payments' => function (int $companyId) {
                $rows = Payment::with(['rent.tenant', 'rent.unit.building'])->orderBy('id')->get()->map(fn (Payment $p) => [
                    $p->id,
                    optional(optional($p->rent)->tenant)->full_name,
                    optional(optional(optional($p->rent)->unit)->building)->name,
                    optional(optional($p->rent)->unit)->unit_number,
                    optional($p->payment_date)->toDateString(),
                    optional($p->due_date)->toDateString(),
                    $p->amount,
                    $p->payment_method,
                    $p->status,
                    $p->reference,
                    $p->notes,
                    optional($p->created_at)->toDateTimeString(),
                ])->all();

                return [
                    'columns' => ['id', 'tenant', 'building', 'unit_number', 'payment_date', 'due_date', 'amount', 'payment_method', 'status', 'reference', 'notes', 'created_at'],
                    'rows' => $rows,
                ];
            },
            'maintenance_requests' => function (int $companyId) {
                $rows = MaintenanceRequest::with(['unit.building', 'tenant'])->orderBy('id')->get()->map(fn (MaintenanceRequest $m) => [
                    $m->id,
                    optional(optional($m->unit)->building)->name,
                    optional($m->unit)->unit_number,
                    optional($m->tenant)->full_name,
                    $m->title,
                    $m->type,
                    $m->priority,
                    $m->status,
                    optional($m->created_at)->toDateTimeString(),
                    optional($m->resolved_at)->toDateTimeString(),
                ])->all();

                return [
                    'columns' => ['id', 'building', 'unit_number', 'tenant', 'title', 'type', 'priority', 'status', 'created_at', 'resolved_at'],
                    'rows' => $rows,
                ];
            },
            'accounting_entries' => function (int $companyId) {
                $rows = AccountingEntry::with('building')->orderBy('id')->get()->map(fn (AccountingEntry $e) => [
                    $e->id,
                    optional($e->entry_date)->toDateString(),
                    $e->type,
                    $e->category,
                    $e->description,
                    $e->amount,
                    $e->status,
                    optional($e->building)->name,
                    $e->is_locked ? 'yes' : 'no',
                    optional($e->created_at)->toDateTimeString(),
                ])->all();

                return [
                    'columns' => ['id', 'entry_date', 'type', 'category', 'description', 'amount', 'status', 'building', 'is_locked', 'created_at'],
                    'rows' => $rows,
                ];
            },
            'staff' => function (int $companyId) {
                $rows = User::with('role')->where('company_id', $companyId)->orderBy('id')->get()->map(fn (User $u) => [
                    $u->id,
                    $u->name,
                    $u->email,
                    $u->phone,
                    optional($u->role)->name,
                    $u->is_active ? 'yes' : 'no',
                    optional($u->created_at)->toDateTimeString(),
                ])->all();

                return [
                    'columns' => ['id', 'name', 'email', 'phone', 'role', 'is_active', 'created_at'],
                    'rows' => $rows,
                ];
            },
        ];
    }

    /**
     * @return array<string, array{label: string, columns: array<int, string>, example: array<int, string>}>
     */
    private function importDefinitions(): array
    {
        return [
            'buildings' => [
                'label' => 'Buildings',
                'columns' => ['name', 'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'total_units', 'total_floors'],
                'example' => ['Sunrise Apartments', '12 Adeniyi Jones', 'Off Allen Avenue', 'Ikeja', 'Lagos', '100281', '24', '4'],
            ],
            'units' => [
                'label' => 'Units',
                'columns' => ['building_name', 'unit_number', 'floor', 'bedrooms', 'bathrooms', 'status'],
                'example' => ['Sunrise Apartments', '101', '1', '2', '2', 'AVAILABLE'],
            ],
            'tenants' => [
                'label' => 'Tenants',
                'columns' => ['full_name', 'phone', 'email', 'building_name', 'unit_number', 'move_in_date', 'move_out_date', 'active'],
                'example' => ['Chinedu Okafor', '08031234567', '', 'Sunrise Apartments', '101', '2026-01-01', '', 'yes'],
            ],
            'rents' => [
                'label' => 'Rental Agreements',
                'columns' => ['tenant_full_name', 'building_name', 'unit_number', 'annual_amount', 'rent_duration', 'start_date', 'end_date', 'status'],
                'example' => ['Chinedu Okafor', 'Sunrise Apartments', '101', '2500000', 'annually', '2026-01-01', '2026-12-31', 'ACTIVE'],
            ],
        ];
    }

    private function value(array $row, string $key): string
    {
        $value = $row[$key] ?? '';

        return trim((string) $value);
    }

    private function parseAmount(string $value): ?float
    {
        if ($value === '') {
            return null;
        }

        $cleaned = preg_replace('/[^0-9.\-]/', '', $value);

        if ($cleaned === '' || $cleaned === '-' || $cleaned === '.') {
            return null;
        }

        return (float) $cleaned;
    }

    private function parseDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function parseBool(string $value, bool $default): bool
    {
        if ($value === '') {
            return $default;
        }

        $normalised = strtolower($value);

        if (in_array($normalised, ['1', 'yes', 'true', 'y', 'active'], true)) {
            return true;
        }

        if (in_array($normalised, ['0', 'no', 'false', 'n', 'inactive'], true)) {
            return false;
        }

        return $default;
    }
}
