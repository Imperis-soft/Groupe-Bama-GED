<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentLock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentLockController extends Controller
{
    public function acquire(Document $document)
    {
        $timeoutMin = (int)(DB::table('settings')->where('key', 'lock_timeout_min')->value('value') ?? 30);

        // Vérifier si déjà verrouillé par quelqu'un d'autre
        $existing = $document->lock;
        if ($existing && !$existing->isExpired() && $existing->locked_by !== auth()->id()) {
            return response()->json([
                'locked'     => true,
                'locked_by'  => $existing->lockedBy->full_name,
                'expires_at' => $existing->expires_at->format('H:i'),
            ], 423);
        }

        // Créer ou renouveler le lock
        DocumentLock::updateOrCreate(
            ['document_id' => $document->id],
            [
                'locked_by'  => auth()->id(),
                'lock_token' => DocumentLock::generateToken(),
                'locked_at'  => now(),
                'expires_at' => now()->addMinutes($timeoutMin),
            ]
        );

        return response()->json(['locked' => false, 'message' => 'Lock acquis.']);
    }

    public function release(Document $document)
    {
        $lock = $document->lock;
        if ($lock && ($lock->locked_by === auth()->id() || auth()->user()->hasRole('admin'))) {
            $lock->delete();
        }

        return response()->json(['released' => true]);
    }

    public function status(Document $document)
    {
        $lock = $document->lock;
        if (!$lock || $lock->isExpired()) {
            return response()->json(['locked' => false]);
        }

        return response()->json([
            'locked'     => true,
            'by_me'      => $lock->locked_by === auth()->id(),
            'locked_by'  => $lock->lockedBy->full_name,
            'expires_at' => $lock->expires_at->format('H:i'),
        ]);
    }
}
