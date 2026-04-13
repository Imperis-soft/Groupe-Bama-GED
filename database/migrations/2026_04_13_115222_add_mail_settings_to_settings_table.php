<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Insérer les clés de config mail/notification dans la table settings
        $defaults = [
            ['key' => 'mail_enabled',       'value' => '0'],
            ['key' => 'mail_mailer',        'value' => 'smtp'],
            ['key' => 'mail_host',          'value' => ''],
            ['key' => 'mail_port',          'value' => '587'],
            ['key' => 'mail_username',      'value' => ''],
            ['key' => 'mail_password',      'value' => ''],
            ['key' => 'mail_encryption',    'value' => 'tls'],
            ['key' => 'mail_from_address',  'value' => ''],
            ['key' => 'mail_from_name',     'value' => 'Groupe Bama GED'],
            ['key' => 'notif_approval',     'value' => '1'],
            ['key' => 'notif_share',        'value' => '1'],
            ['key' => 'notif_expiry',       'value' => '1'],
            ['key' => 'notif_comment',      'value' => '1'],
            ['key' => 'notif_expiry_days',  'value' => '30'],
            ['key' => 'bulk_max_select',    'value' => '50'],
            ['key' => 'lock_timeout_min',   'value' => '30'],
            ['key' => 'require_approval',   'value' => '0'],
            ['key' => 'require_signature',  'value' => '0'],
        ];

        foreach ($defaults as $setting) {
            DB::table('settings')->insertOrIgnore($setting);
        }
    }

    public function down(): void
    {
        $keys = ['mail_enabled','mail_mailer','mail_host','mail_port','mail_username',
                 'mail_password','mail_encryption','mail_from_address','mail_from_name',
                 'notif_approval','notif_share','notif_expiry','notif_comment',
                 'notif_expiry_days','bulk_max_select','lock_timeout_min',
                 'require_approval','require_signature'];
        DB::table('settings')->whereIn('key', $keys)->delete();
    }
};
