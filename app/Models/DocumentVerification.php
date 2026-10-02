<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVerification extends Model
{

    // Les attributs pouvant être assignés en masse
    protected $fillable = [
        'document_id',
        'verification_code',
        'verified_at',
        'device_info',
        'ip_address',
        'user_agent',
    ];

    // Les attributs à caster
    protected $casts = [
        'verified_at' => 'datetime',
        'device_info' => 'array',
    ];

    // Alphabet sans caractères ambigus (0/O, 1/I/L) : 31 symboles → ≈ 158 bits pour 32 caractères
    public const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    // Longueur du code court imprimé sous le QR code pour la vérification manuelle (≈ 60 bits)
    public const SHORT_LENGTH = 12;

    // Relation avec le document (y compris supprimé : la page de vérification doit le signaler)
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class)->withTrashed();
    }

    /**
     * Nouveau code de vérification, aléatoire (générateur cryptographique) et unique.
     */
    public static function generateCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 32; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (static::where('verification_code', 'like', substr($code, 0, self::SHORT_LENGTH) . '%')->exists());

        return $code;
    }

    /**
     * Code court lisible imprimé sur le document : « 7KQM-X2PA-9RTD ».
     */
    public static function displayCode(string $code): string
    {
        return implode('-', str_split(substr($code, 0, self::SHORT_LENGTH), 4));
    }

    /**
     * Retrouve une vérification à partir d'un code saisi à la main (code court ou complet).
     */
    public static function findByInput(?string $input): ?self
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $input));
        if (strlen($code) < self::SHORT_LENGTH || strlen($code) > 64) {
            return null;
        }

        if ($exact = static::where('verification_code', $code)->first()) {
            return $exact;
        }

        // Code court : il doit désigner un seul document
        $matches = static::where('verification_code', 'like', substr($code, 0, self::SHORT_LENGTH) . '%')->limit(2)->get();
        return $matches->count() === 1 ? $matches->first() : null;
    }
}
