<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Support\ArchivePathService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArchivePathServiceTest extends TestCase
{
    public function test_archived_csv_files_are_saved_in_company_subfolders(): void
    {
        $company = new Company([
            'slug' => 'noyb-international',
        ]);

        Storage::shouldReceive('disk')
            ->once()
            ->with('private')
            ->andReturnSelf();
        Storage::shouldReceive('put')
            ->once()
            ->with(
                'archives/noyb-international/tenant_eviction_15_sample.csv',
                "header,value\nstatus,archived\n"
            )
            ->andReturnTrue();

        $path = app(ArchivePathService::class)->storeCsv(
            $company,
            'tenant_eviction_15_sample.csv',
            "header,value\nstatus,archived\n"
        );

        $this->assertSame('archives/noyb-international/tenant_eviction_15_sample.csv', $path);
    }
}
