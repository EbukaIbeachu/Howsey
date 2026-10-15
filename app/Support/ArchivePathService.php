<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArchivePathService
{
    public function disk(): string
    {
        return 'private';
    }

    public function companyDirectory(?Company $company): string
    {
        $slug = trim((string) ($company?->slug ?? ''));

        if ($slug === '') {
            $slug = $company?->id ? 'company-'.$company->id : 'unassigned-company';
        }

        return 'archives/'.Str::slug($slug);
    }

    public function storeCsv(?Company $company, string $fileName, string $csvContent): string
    {
        $path = $this->companyDirectory($company).'/'.$fileName;

        Storage::disk($this->disk())->put($path, $csvContent);

        return $path;
    }

    public function exists(string $path): bool
    {
        return Storage::disk($this->disk())->exists($path);
    }

    public function pathBelongsToCompany(?Company $company, string $path): bool
    {
        return Str::startsWith($path, $this->companyDirectory($company).'/');
    }

    public function download(string $path, string $filename)
    {
        return Storage::disk($this->disk())->download($path, $filename);
    }
}
