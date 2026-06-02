#!/usr/bin/env php
<?php

/**
 * ============================================================
 *  IMPERIS SARL — Générateur de Licences GED
 * ============================================================
 *  Usage :
 *    php generate-license.php                    → interactif
 *    php generate-license.php --client="Groupe Bama" --months=12
 *    php generate-license.php --client="Groupe Bama" --months=6
 *    php generate-license.php --verify=IMPERIS-XXXX-XXXX-XXXX-EXP20270601
 *    php generate-license.php --list                → historique local
 * ============================================================
 *  NE PAS DISTRIBUER CE FICHIER AU CLIENT.
 *  Usage exclusif : Imperis SARL
 * ============================================================
 */

define('IMPERIS_SECRET', 'IMPERIS-SARL-2024-GED-SECRET-KEY');
define('HISTORY_FILE',   __DIR__ . '/licenses-history.json');
define('VERSION',        '1.0.0');

// ─── Couleurs terminal ────────────────────────────────────────
function c(string $text, string $color): string {
    $colors = [
        'red'     => "\033[0;31m",
        'green'   => "\033[0;32m",
        'yellow'  => "\033[0;33m",
        'blue'    => "\033[0;34m",
        'magenta' => "\033[0;35m",
        'cyan'    => "\033[0;36m",
        'white'   => "\033[1;37m",
        'bold'    => "\033[1m",
        'dim'     => "\033[2m",
        'reset'   => "\033[0m",
    ];
    return ($colors[$color] ?? '') . $text . $colors['reset'];
}

function line(string $text = ''): void { echo $text . PHP_EOL; }
function hr(string $char = '─', int $len = 60): void { line(c(str_repeat($char, $len), 'dim')); }

// ─── Bannière ─────────────────────────────────────────────────
function banner(): void {
    line();
    hr('═');
    line(c('  ██╗███╗   ███╗██████╗ ███████╗██████╗ ██╗███████╗', 'cyan'));
    line(c('  ██║████╗ ████║██╔══██╗██╔════╝██╔══██╗██║██╔════╝', 'cyan'));
    line(c('  ██║██╔████╔██║██████╔╝█████╗  ██████╔╝██║███████╗', 'cyan'));
    line(c('  ██║██║╚██╔╝██║██╔═══╝ ██╔══╝  ██╔══██╗██║╚════██║', 'cyan'));
    line(c('  ██║██║ ╚═╝ ██║██║     ███████╗██║  ██║██║███████║', 'cyan'));
    line(c('  ╚═╝╚═╝     ╚═╝╚═╝     ╚══════╝╚═╝  ╚═╝╚═╝╚══════╝', 'cyan'));
    line();
    line(c('  Générateur de Licences GED', 'white') . c('  v' . VERSION, 'dim'));
    line(c('  Hamdalaye ACI, Bamako, Mali  ·  contact@imperis.com', 'dim'));
    hr('═');
    line();
}

// ─── Générer une clé ──────────────────────────────────────────
function generateKey(string $client, int $months): array {
    $issuedAt  = new DateTime();
    $expiresAt = (new DateTime())->modify("+{$months} months");

    // Segments aléatoires alphanumériques
    $seg1 = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 4));
    $seg2 = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 4));
    $seg3 = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 4));
    $expStr = $expiresAt->format('Ymd');

    $rawKey = "IMPERIS-{$seg1}-{$seg2}-{$seg3}-EXP{$expStr}";

    // Checksum HMAC pour vérification d'authenticité
    $checksum = strtoupper(substr(hash_hmac('sha256', $rawKey . $client, IMPERIS_SECRET), 0, 8));

    return [
        'key'        => $rawKey,
        'checksum'   => $checksum,
        'full_key'   => $rawKey . '-' . $checksum,
        'client'     => $client,
        'issued_at'  => $issuedAt->format('d/m/Y H:i'),
        'expires_at' => $expiresAt->format('d/m/Y'),
        'months'     => $months,
        'issued_by'  => 'Imperis SARL',
    ];
}

// ─── Vérifier une clé ─────────────────────────────────────────
function verifyKey(string $fullKey, string $client = ''): array {
    // Format avec checksum : IMPERIS-XXXX-XXXX-XXXX-EXPYYYYMMDD-CHECKSUM
    // Format sans checksum : IMPERIS-XXXX-XXXX-XXXX-EXPYYYYMMDD
    $parts = explode('-', $fullKey);

    if (count($parts) === 6) {
        // Avec checksum
        $checksum = array_pop($parts);
        $rawKey   = implode('-', $parts);
    } elseif (count($parts) === 5) {
        $rawKey   = $fullKey;
        $checksum = null;
    } else {
        return ['valid' => false, 'error' => 'Format de clé invalide.'];
    }

    // Valider le format de base
    if (!preg_match('/^IMPERIS-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-EXP(\d{8})$/', $rawKey, $m)) {
        return ['valid' => false, 'error' => 'Format de clé invalide.'];
    }

    // Extraire la date d'expiration
    $expDate = DateTime::createFromFormat('Ymd', $m[1]);
    if (!$expDate) {
        return ['valid' => false, 'error' => 'Date d\'expiration invalide.'];
    }

    $now     = new DateTime();
    $expired = $expDate < $now;
    $days    = (int) $now->diff($expDate)->days * ($expired ? -1 : 1);

    $result = [
        'valid'      => !$expired,
        'raw_key'    => $rawKey,
        'expires_at' => $expDate->format('d/m/Y'),
        'expired'    => $expired,
        'days'       => $days,
        'checksum_ok'=> null,
    ];

    // Vérifier le checksum si fourni et si client connu
    if ($checksum && $client) {
        $expected = strtoupper(substr(hash_hmac('sha256', $rawKey . $client, IMPERIS_SECRET), 0, 8));
        $result['checksum_ok'] = ($checksum === $expected);
    }

    return $result;
}

// ─── Sauvegarder dans l'historique ────────────────────────────
function saveToHistory(array $data): void {
    $history = [];
    if (file_exists(HISTORY_FILE)) {
        $history = json_decode(file_get_contents(HISTORY_FILE), true) ?? [];
    }
    $history[] = array_merge($data, ['generated_at' => date('Y-m-d H:i:s')]);
    file_put_contents(HISTORY_FILE, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ─── Afficher l'historique ────────────────────────────────────
function showHistory(): void {
    if (!file_exists(HISTORY_FILE)) {
        line(c('  Aucun historique trouvé.', 'yellow'));
        return;
    }

    $history = json_decode(file_get_contents(HISTORY_FILE), true) ?? [];

    if (empty($history)) {
        line(c('  Historique vide.', 'yellow'));
        return;
    }

    line(c('  📋 Historique des licences générées (' . count($history) . ')', 'white'));
    line();

    foreach (array_reverse($history) as $i => $entry) {
        $expDate = DateTime::createFromFormat('d/m/Y', $entry['expires_at']);
        $expired = $expDate && $expDate < new DateTime();
        $status  = $expired ? c('EXPIRÉE', 'red') : c('VALIDE', 'green');

        line(c('  #' . (count($history) - $i), 'dim') . '  ' . c($entry['full_key'] ?? $entry['key'], 'cyan'));
        line(c('      Client   : ', 'dim') . c($entry['client'], 'white'));
        line(c('      Expire   : ', 'dim') . $entry['expires_at'] . '  ' . $status);
        line(c('      Généré   : ', 'dim') . ($entry['generated_at'] ?? '—'));
        line();
    }
}

// ─── Afficher le résultat d'une clé générée ───────────────────
function displayResult(array $data): void {
    line();
    hr();
    line(c('  ✅  LICENCE GÉNÉRÉE AVEC SUCCÈS', 'green'));
    hr();
    line();
    line(c('  🔑  Clé complète (avec checksum) :', 'white'));
    line();
    line('  ' . c($data['full_key'], 'cyan'));
    line();
    line(c('  🔑  Clé simple (sans checksum) :', 'dim'));
    line('  ' . c($data['key'], 'dim'));
    line();
    hr('─', 60);
    line(c('  Client     : ', 'dim') . c($data['client'], 'white'));
    line(c('  Émise par  : ', 'dim') . c($data['issued_by'], 'white'));
    line(c('  Émise le   : ', 'dim') . $data['issued_at']);
    line(c('  Expire le  : ', 'dim') . c($data['expires_at'], 'yellow'));
    line(c('  Durée      : ', 'dim') . $data['months'] . ' mois');
    hr('─', 60);
    line();
    line(c('  📧  À envoyer au client :', 'white'));
    line();
    line(c('  ┌─────────────────────────────────────────────────────┐', 'dim'));
    line(c('  │  ', 'dim') . 'Bonjour,');
    line(c('  │  ', 'dim'));
    line(c('  │  ', 'dim') . 'Voici votre clé de licence GED :');
    line(c('  │  ', 'dim'));
    line(c('  │  ', 'dim') . c($data['key'], 'cyan'));
    line(c('  │  ', 'dim'));
    line(c('  │  ', 'dim') . 'Valide jusqu\'au : ' . c($data['expires_at'], 'yellow'));
    line(c('  │  ', 'dim'));
    line(c('  │  ', 'dim') . 'Pour activer : rendez-vous sur votre plateforme');
    line(c('  │  ', 'dim') . 'GED → page de licence expirée → onglet "Activer".');
    line(c('  │  ', 'dim'));
    line(c('  │  ', 'dim') . 'Cordialement, Imperis SARL');
    line(c('  └─────────────────────────────────────────────────────┘', 'dim'));
    line();
}

// ─── Saisie interactive ───────────────────────────────────────
function prompt(string $question, string $default = ''): string {
    $hint = $default ? c(" [{$default}]", 'dim') : '';
    echo c('  → ', 'cyan') . $question . $hint . ' : ';
    $input = trim(fgets(STDIN));
    return $input === '' ? $default : $input;
}

function promptInt(string $question, int $default = 12, array $allowed = []): int {
    while (true) {
        $val = (int) prompt($question, (string) $default);
        if (empty($allowed) || in_array($val, $allowed)) return $val;
        line(c('  ⚠  Valeur invalide. Choisissez parmi : ' . implode(', ', $allowed), 'yellow'));
    }
}

// ─── Parse des arguments CLI ──────────────────────────────────
function parseArgs(array $argv): array {
    $args = [];
    foreach (array_slice($argv, 1) as $arg) {
        if (preg_match('/^--(\w+)(?:=(.+))?$/', $arg, $m)) {
            $args[$m[1]] = $m[2] ?? true;
        }
    }
    return $args;
}

// ══════════════════════════════════════════════════════════════
//  POINT D'ENTRÉE
// ══════════════════════════════════════════════════════════════
banner();

$args = parseArgs($argv);

// ─── Mode : vérifier une clé ──────────────────────────────────
if (isset($args['verify'])) {
    $key    = $args['verify'];
    $client = $args['client'] ?? '';

    line(c('  🔍  Vérification de la clé :', 'white'));
    line('  ' . c($key, 'cyan'));
    line();

    $result = verifyKey($key, $client);

    if (!$result['valid'] && isset($result['error'])) {
        line(c('  ❌  ' . $result['error'], 'red'));
    } else {
        $status = $result['expired']
            ? c('  ❌  EXPIRÉE', 'red') . c(' (depuis ' . abs($result['days']) . ' jours)', 'dim')
            : c('  ✅  VALIDE', 'green') . c(' (encore ' . $result['days'] . ' jours)', 'dim');

        line($status);
        line(c('  Expire le : ', 'dim') . $result['expires_at']);

        if ($result['checksum_ok'] === true) {
            line(c('  Checksum  : ', 'dim') . c('✅ Authentique', 'green'));
        } elseif ($result['checksum_ok'] === false) {
            line(c('  Checksum  : ', 'dim') . c('❌ Invalide — clé potentiellement falsifiée', 'red'));
        }
    }

    line();
    exit(0);
}

// ─── Mode : historique ────────────────────────────────────────
if (isset($args['list'])) {
    showHistory();
    exit(0);
}

// ─── Mode : génération directe (args CLI) ─────────────────────
if (isset($args['client']) && isset($args['months'])) {
    $client = $args['client'];
    $months = (int) $args['months'];

    if ($months < 1 || $months > 36) {
        line(c('  ❌  Durée invalide. Entre 1 et 36 mois.', 'red'));
        exit(1);
    }

    $data = generateKey($client, $months);
    displayResult($data);
    saveToHistory($data);
    exit(0);
}

// ─── Mode : interactif ────────────────────────────────────────
line(c('  Mode interactif', 'white'));
line(c('  Remplissez les informations pour générer une licence.', 'dim'));
line();

// Client
$client = prompt('Nom du client', 'Groupe Bama');
if (empty($client)) {
    line(c('  ❌  Le nom du client est requis.', 'red'));
    exit(1);
}

// Durée
line();
line(c('  Durée de la licence :', 'white'));
line(c('    1) 6 mois', 'dim'));
line(c('    2) 12 mois (1 an)  ← recommandé', 'dim'));
line(c('    3) 24 mois (2 ans)', 'dim'));
line(c('    4) Personnalisée', 'dim'));
line();

$choice = promptInt('Votre choix', 2, [1, 2, 3, 4]);

$months = match($choice) {
    1 => 6,
    2 => 12,
    3 => 24,
    4 => promptInt('Nombre de mois (1-36)', 12),
    default => 12,
};

// Confirmation
line();
$expiresAt = (new DateTime())->modify("+{$months} months")->format('d/m/Y');
line(c('  Récapitulatif :', 'white'));
line(c('    Client  : ', 'dim') . c($client, 'white'));
line(c('    Durée   : ', 'dim') . $months . ' mois');
line(c('    Expire  : ', 'dim') . c($expiresAt, 'yellow'));
line();

$confirm = strtolower(prompt('Confirmer la génération ? (o/n)', 'o'));
if (!in_array($confirm, ['o', 'oui', 'y', 'yes'])) {
    line(c('  Annulé.', 'yellow'));
    exit(0);
}

// Génération
$data = generateKey($client, $months);
displayResult($data);
saveToHistory($data);

line(c('  💾  Sauvegardé dans : ' . HISTORY_FILE, 'dim'));
line();
