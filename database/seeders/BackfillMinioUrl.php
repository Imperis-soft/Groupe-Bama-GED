<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Document;

class BackfillMinioUrl extends Seeder
{
    public function run(): void
    {
        // URL construite à partir du bucket de l'entreprise de chaque document
        $count = Document::withoutGlobalScopes()->with('organization')->whereNull('minio_url')->get()->each(function ($doc) {
            $base   = rtrim(config('filesystems.disks.s3.url'), '/');
            $bucket = config('filesystems.disks.s3.bucket');

            $doc->update([
                'minio_url' => $doc->organization
                    ? $doc->organization->fileUrl($doc->file_path)
                    : $base . '/' . $bucket . '/' . ltrim($doc->file_path, '/'),
            ]);
        })->count();

        $this->command->info("minio_url mis à jour pour {$count} documents.");
    }
}
