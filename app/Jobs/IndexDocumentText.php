<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Exception;

class IndexDocumentText implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $documentId;

    public $timeout = 120;

    // Constructor
    public function __construct(int $documentId)
    {
        $this->documentId = $documentId;
    }

    // Handle the job
    public function handle()
    {
        $doc = Document::find($this->documentId);
        if (! $doc) return;

        $path = $doc->file_path;

        try {
            // Download file to temp
            $tmp = tempnam(sys_get_temp_dir(), 'docidx_');
            $stream = Storage::disk('s3')->getDriver()->readStream($path);
            if ($stream === false) {
                throw new Exception('Impossible d ouvrir le flux distant.');
            }
            $out = fopen($tmp, 'w');
            while (! feof($stream)) {
                fwrite($out, fread($stream, 8192));
            }
            fclose($out);
            if (is_resource($stream)) fclose($stream);

            $text = '';
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if ($ext === 'docx') {
                $text = $this->extractTextFromDocx($tmp);
            } elseif ($ext === 'pdf') {
                // try pdftotext
                $pdftotext = trim(shell_exec('which pdftotext'));
                if ($pdftotext) {
                    $outTxt = $tmp . '.txt';
                    @shell_exec("pdftotext " . escapeshellarg($tmp) . " " . escapeshellarg($outTxt));
                    if (file_exists($outTxt)) {
                        $text = file_get_contents($outTxt);
                        @unlink($outTxt);
                    }
                }
                // fallback empty
            } else {
                // Try tesseract for images or generic fallback
                $tesseract = trim(shell_exec('which tesseract'));
                if ($tesseract) {
                    $outTxt = $tmp . '.txt';
                    @shell_exec("tesseract " . escapeshellarg($tmp) . " " . escapeshellarg($tmp) . " 2>/dev/null");
                    if (file_exists($outTxt)) {
                        $text = file_get_contents($outTxt);
                        @unlink($outTxt);
                    }
                }
            }

            // Save extracted text and update search vector via trigger
            $doc->content_text = $text ?: null;
            $doc->save();

            @unlink($tmp);

        } catch (Exception $e) {
            \Log::error('IndexDocumentText failed: ' . $e->getMessage());
        }
    }

    // Extract text from DOCX files
    protected function extractTextFromDocx(string $filePath): string
    {
        $text = '';
        $zip = new \ZipArchive();
        if ($zip->open($filePath) === true) {
            if (($idx = $zip->locateName('word/document.xml')) !== false) {
                $data = $zip->getFromIndex($idx);
                // Strip xml tags
                $data = preg_replace('/<w:[^>]+>/', ' ', $data);
                $data = strip_tags($data);
                $text = html_entity_decode($data, ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
            $zip->close();
        }
        return $text;
    }
}
