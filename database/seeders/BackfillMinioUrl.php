<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Document;

class BackfillMinioUrl extends Seeder
{
    public function run(): void
    {
        $base   = rtrim(config('filesystems.disks.s3.url'), '/');
        $bucket = config('filesystems.disks.s3.bucket');

        $count = Document::whereNull('minio_url')->get()->each(function ($doc) use ($base, $bucket) {
            $doc->update([
                'minio_url' => $base . '/' . $bucket . '/' . ltrim($doc->file_path, '/'),
            ]);
        })->count();

        $this->command->info("minio_url mis à jour pour {$count} documents.");
    }
}
