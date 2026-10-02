<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DemoRequest;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlatformActivityLog;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SaasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');

        foreach (['admin', 'editor', 'viewer'] as $name) {
            Role::create(['name' => $name, 'display_name' => ucfirst($name)]);
        }
    }

    private function organization(string $name, string $plan = 'entreprise', ?string $endsAt = null): Organization
    {
        $org = Organization::create(['name' => $name, 'reference_prefix' => strtoupper(substr($name, 0, 3))]);
        Subscription::create([
            'organization_id' => $org->id,
            'plan_id'         => Plan::where('slug', $plan)->value('id'),
            'starts_at'       => today()->subMonth(),
            'ends_at'         => $endsAt ?? today()->addYear(),
        ]);
        return $org;
    }

    private function member(Organization $org, string $role = 'admin'): User
    {
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->roles()->attach(Role::where('name', $role)->value('id'));
        return $user;
    }

    private function document(User $creator): Document
    {
        $ref = 'DOC-' . strtoupper(fake()->unique()->bothify('??####'));
        $path = Organization::find($creator->organization_id)->documentsDir() . "{$ref}.docx";
        Storage::disk('s3')->put($path, 'x');

        return Document::create([
            'organization_id' => $creator->organization_id,
            'reference'       => $ref,
            'title'           => 'Doc ' . $ref,
            'file_path'       => $path,
            'creator_id'      => $creator->id,
        ]);
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['is_super_admin' => true, 'organization_id' => null]);
    }

    // --- Cloisonnement ---

    public function test_an_organization_never_sees_another_organizations_data(): void
    {
        $adminA = $this->member($this->organization('Alpha'));
        $adminB = $this->member($this->organization('Beta'));
        $docB   = $this->document($adminB);
        Category::create(['organization_id' => $adminB->organization_id, 'name' => 'Secret B', 'slug' => 'secret-b']);

        // Même un admin (qui « voit tout ») ne voit que son entreprise
        $this->actingAs($adminA)->get("/documents/{$docB->id}")->assertNotFound();
        $this->actingAs($adminA)->get("/documents/{$docB->id}/download")->assertNotFound();
        $this->actingAs($adminA)->get('/documents')->assertOk()->assertDontSee($docB->reference);
        $this->actingAs($adminA)->get('/categories')->assertOk()->assertDontSee('Secret B');
        $this->actingAs($adminA)->get('/users')->assertOk()->assertDontSee($adminB->email);
        $this->actingAs($adminA)->getJson('/api/documents/search?q=Doc')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($adminA)->get("/users/{$adminB->id}/edit")->assertNotFound();
    }

    public function test_cannot_reference_users_or_categories_of_another_organization(): void
    {
        $adminA = $this->member($this->organization('Alpha'));
        $adminB = $this->member($this->organization('Beta'));
        $docA   = $this->document($adminA);
        $catB   = Category::create(['organization_id' => $adminB->organization_id, 'name' => 'B', 'slug' => 'b']);

        $this->actingAs($adminA)
            ->post("/documents/{$docA->id}/approval/setup", ['approvers' => [$adminB->id]])
            ->assertSessionHasErrors('approvers.0');

        $this->actingAs($adminA)
            ->post("/documents/{$docA->id}/shares", ['shared_with' => $adminB->id, 'access_level' => 'view'])
            ->assertSessionHasErrors('shared_with');

        $this->actingAs($adminA)
            ->put("/documents/{$docA->id}", ['title' => 'X', 'category_id' => $catB->id])
            ->assertSessionHasErrors('category_id');
    }

    public function test_settings_are_separate_per_organization(): void
    {
        $alpha = $this->organization('Alpha');
        $beta  = $this->organization('Beta');
        $super = $this->superAdmin();

        $this->actingAs($super)->post("/super-admin/organizations/{$alpha->id}/settings", [
            'mail_host' => 'smtp.alpha.test', 'mail_enabled' => 1, 'lock_timeout_min' => '',
        ])->assertRedirect();

        $this->assertSame('smtp.alpha.test', appSettings($alpha->id)['mail_host']);
        // B hérite des valeurs de la plateforme, jamais de celles d'Alpha
        $this->assertNotSame('smtp.alpha.test', appSettings($beta->id)['mail_host'] ?? null);
        $this->actingAs($super)->get("/super-admin/organizations/{$beta->id}/settings")->assertOk()->assertDontSee('smtp.alpha.test');
    }

    public function test_empty_organization_setting_falls_back_to_platform(): void
    {
        $org = $this->organization('Alpha');
        $this->actingAs($this->superAdmin())->post('/super-admin/settings', ['lock_timeout_min' => '45']);
        $this->post("/super-admin/organizations/{$org->id}/settings", ['lock_timeout_min' => '']);
        $this->assertSame('45', appSettings($org->id)['lock_timeout_min']);

        $this->post("/super-admin/organizations/{$org->id}/settings", ['lock_timeout_min' => '10']);
        $this->assertSame('10', appSettings($org->id)['lock_timeout_min']);
    }

    public function test_organization_settings_ignore_unknown_keys_and_encrypt_smtp_password(): void
    {
        $org = $this->organization('Alpha');
        $this->actingAs($this->superAdmin())
            ->post("/super-admin/organizations/{$org->id}/settings", [
                'mail_password' => 'secret', 'mail_host' => 'smtp.test', 'mail_enabled' => 1, 'arbitrary_key' => 'x',
            ])->assertRedirect();

        $this->assertDatabaseMissing('settings', ['key' => 'arbitrary_key']);
        $stored = DB::table('settings')->where('organization_id', $org->id)->where('key', 'mail_password')->value('value');
        $this->assertNotSame('secret', $stored);
        $this->assertSame('secret', appSettings($org->id)['mail_password']);
    }

    public function test_configuration_page_is_not_in_company_space(): void
    {
        $admin = $this->member($this->organization('Alpha'));
        $this->actingAs($admin)->get('/settings')->assertNotFound();
        $this->actingAs($admin)->get("/super-admin/organizations/{$admin->organization_id}/settings")->assertForbidden();
    }

    public function test_categories_can_share_names_across_organizations(): void
    {
        $adminA = $this->member($this->organization('Alpha'));
        $adminB = $this->member($this->organization('Beta'));

        foreach ([$adminA, $adminB] as $admin) {
            $this->actingAs($admin)
                ->post('/categories', ['name' => 'Factures', 'slug' => 'factures'])
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(2, Category::withoutGlobalScopes()->where('slug', 'factures')->count());
    }

    // --- Abonnements ---

    public function test_expired_subscription_blocks_access(): void
    {
        $admin = $this->member($this->organization('Alpha', 'entreprise', today()->subDay()->toDateString()));

        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('subscription.expired'));
        $this->actingAs($admin)->get('/subscription/expired')->assertOk()->assertSee(config('saas.support_email'));
    }

    public function test_suspended_organization_is_blocked(): void
    {
        $org   = $this->organization('Alpha');
        $admin = $this->member($org);
        $org->update(['status' => 'suspended', 'suspended_reason' => 'Impayé']);

        $this->actingAs($admin)->get('/documents')->assertRedirect(route('subscription.expired'));
        $this->actingAs($admin)->get('/subscription/expired')->assertSee('Impayé');
    }

    public function test_plan_user_limit_is_enforced(): void
    {
        $org   = $this->organization('Alpha', 'essentiel'); // 5 utilisateurs
        $admin = $this->member($org);
        User::factory()->count(4)->create(['organization_id' => $org->id]);

        $this->actingAs($admin)->post('/users', [
            'full_name' => 'Sixième', 'email' => 'six@alpha.test',
            'password' => 'password1', 'password_confirmation' => 'password1',
        ])->assertSessionHas('error');

        $this->assertDatabaseMissing('users', ['email' => 'six@alpha.test']);
    }

    public function test_plan_storage_limit_is_enforced(): void
    {
        Plan::where('slug', 'essentiel')->update(['max_storage_mb' => 1]);
        $admin = $this->member($this->organization('Alpha', 'essentiel'));

        $this->actingAs($admin)->post('/documents/create', [
            'title'       => 'Gros fichier',
            'import_mode' => 1,
            'import_file' => UploadedFile::fake()->createWithContent('gros.pdf', "%PDF-1.4\n" . str_repeat(' ', 2048 * 1024)), // 2 Mo
        ])->assertSessionHasErrors('import_file');

        $this->assertSame(0, Document::withoutGlobalScopes()->count());
    }

    public function test_new_documents_use_the_organization_prefix_and_folder(): void
    {
        $admin = $this->member($this->organization('Alpha'));

        $this->actingAs($admin)->post('/documents/create', [
            'title'       => 'Contrat',
            'import_mode' => 1,
            'import_file' => UploadedFile::fake()->createWithContent('contrat.pdf', "%PDF-1.4\n%%EOF\n"),
        ]);

        $doc = Document::withoutGlobalScopes()->firstOrFail();
        $this->assertSame($admin->organization_id, $doc->organization_id);
        $this->assertStringStartsWith('ALP-', $doc->reference);
        $this->assertStringStartsWith('alpha/documents/', $doc->file_path);
    }

    // --- Super admin ---

    public function test_super_admin_area_is_reserved(): void
    {
        $admin = $this->member($this->organization('Alpha'));

        $this->actingAs($admin)->get('/super-admin')->assertForbidden();
        $this->actingAs($admin)->get('/super-admin/organizations')->assertForbidden();
        $this->actingAs($this->superAdmin())->get('/super-admin')->assertOk();
    }

    public function test_super_admin_without_organization_is_sent_to_his_area(): void
    {
        $this->actingAs($this->superAdmin())->get('/dashboard')->assertRedirect(route('super.dashboard'));
    }

    public function test_super_admin_creates_an_organization_with_admin_and_trial(): void
    {
        $this->actingAs($this->superAdmin())->post('/super-admin/organizations', [
            'name'             => 'Gamma SARL',
            'reference_prefix' => 'GAM',
            'admin_name'       => 'Awa Traoré',
            'admin_email'      => 'awa@gamma.test',
            'admin_password'   => 'motdepasse',
            'plan_id'          => Plan::where('slug', 'pro')->value('id'),
            'mode'             => 'trial',
            'storage_bucket'   => 'ged-gamma',
            'storage_create_bucket' => 1,
        ])->assertRedirect();

        $org   = Organization::where('name', 'Gamma SARL')->firstOrFail();
        $this->assertSame('ged-gamma', $org->storage_bucket);
        $admin = User::where('email', 'awa@gamma.test')->firstOrFail();

        $this->assertSame($org->id, $admin->organization_id);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertSame('trial', $org->activeSubscription()->status);
        $this->assertTrue(PlatformActivityLog::where('action', 'organization_created')->exists());

        $this->post('/logout');
        $this->post('/login', ['email' => 'awa@gamma.test', 'password' => 'motdepasse'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk();
    }

    // --- Bucket MinIO par entreprise ---

    private function newOrganizationPayload(array $overrides = []): array
    {
        return $overrides + [
            'name'             => 'Delta SA',
            'reference_prefix' => 'DEL',
            'admin_name'       => 'Moussa Keita',
            'admin_email'      => 'moussa@delta.test',
            'admin_password'   => 'motdepasse',
            'plan_id'          => Plan::where('slug', 'pro')->value('id'),
            'mode'             => 'trial',
            'storage_bucket'   => 'ged-delta',
        ];
    }

    public function test_organization_bucket_is_optional_but_valid_and_unique(): void
    {
        $super = $this->actingAs($this->superAdmin());
        Organization::create(['name' => 'Prise', 'reference_prefix' => 'PRI', 'storage_bucket' => 'ged-pris']);

        config(['filesystems.disks.s3.bucket' => 'ged']);

        // Bucket séparé facultatif, mais s'il est renseigné il doit être valide, libre et différent du bucket partagé
        foreach (['Majuscules', 'GED', 'a', 'ged_delta', 'ged-pris', 'ged'] as $bucket) {
            $super->post('/super-admin/organizations', $this->newOrganizationPayload(['storage_bucket' => $bucket]))
                ->assertSessionHasErrors('storage_bucket');
        }
        $this->assertFalse(Organization::where('name', 'Delta SA')->exists());
    }

    // --- Dossier privé par entreprise dans le bucket partagé ---

    public function test_new_organization_gets_its_own_folder_in_the_shared_bucket(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/super-admin/organizations', $this->newOrganizationPayload(['storage_bucket' => '']))
            ->assertRedirect();

        $org = Organization::where('name', 'Delta SA')->firstOrFail();
        $this->assertFalse($org->hasDedicatedBucket());
        $this->assertSame('delta-sa', $org->storage_folder);
        $this->assertSame('delta-sa/documents/', $org->documentsDir());
        Storage::disk('s3')->assertExists('delta-sa/documents/.keep');
    }

    public function test_storage_folder_is_unique_and_cannot_be_mass_assigned(): void
    {
        $first  = Organization::create(['name' => 'Delta SA', 'reference_prefix' => 'DEL']);
        $second = Organization::create(['name' => 'Delta SA', 'reference_prefix' => 'DE2', 'storage_folder' => $first->storage_folder]);

        $this->assertNotSame($first->storage_folder, $second->storage_folder);

        $first->update(['storage_folder' => $second->storage_folder]);
        $this->assertSame('delta-sa', $first->fresh()->storage_folder);
    }

    public function test_documents_of_two_organizations_never_share_a_folder(): void
    {
        $alpha = $this->organization('Alpha');
        $beta  = $this->organization('Beta');

        $a = $this->document($this->member($alpha));
        $b = $this->document($this->member($beta));

        $this->assertStringStartsWith('alpha/documents/', $a->file_path);
        $this->assertStringStartsWith('beta/documents/', $b->file_path);
        $this->assertFalse($alpha->ownsPath($b->file_path));
        $this->assertFalse($alpha->ownsPath('alpha/../beta/documents/x.pdf'));
    }

    public function test_a_document_cannot_point_to_another_organizations_folder(): void
    {
        $alpha = $this->organization('Alpha');
        $this->organization('Beta');

        $this->expectException(\RuntimeException::class);
        $this->document($this->member($alpha))->update(['file_path' => 'beta/documents/secret.pdf']);
    }

    public function test_deleting_an_organization_removes_its_folder_only(): void
    {
        $alpha = $this->organization('Alpha');
        $beta  = $this->organization('Beta');
        $this->document($this->member($alpha));
        $kept = $this->document($this->member($beta));
        Storage::disk('s3')->put('alpha/documents/previews/x.pdf', 'x');

        $this->actingAs($this->superAdmin())
            ->delete("/super-admin/organizations/{$alpha->id}", ['confirm_name' => 'Alpha'])
            ->assertRedirect();

        $this->assertSame([], Storage::disk('s3')->allFiles('alpha'));
        Storage::disk('s3')->assertExists($kept->file_path);
    }

    public function test_dedicated_server_requires_credentials_and_encrypts_secret(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/super-admin/organizations', $this->newOrganizationPayload(['storage_dedicated' => 1]))
            ->assertSessionHasErrors(['storage_endpoint', 'storage_key', 'storage_secret']);

        $this->post('/super-admin/organizations', $this->newOrganizationPayload([
            'storage_dedicated' => 1,
            'storage_endpoint'  => 'https://minio.delta.test',
            'storage_key'       => 'delta',
            'storage_secret'    => 'secret-delta',
        ]))->assertRedirect();

        $org = Organization::where('name', 'Delta SA')->firstOrFail();
        $this->assertSame('secret-delta', $org->storage_secret);
        $this->assertNotSame('secret-delta', DB::table('organizations')->where('id', $org->id)->value('storage_secret'));
        $this->assertSame('https://minio.delta.test', $org->storageConfig()['endpoint']);
        $this->assertSame('ged-delta', $org->storageConfig()['bucket']);
    }

    public function test_documents_of_an_organization_go_to_its_own_bucket(): void
    {
        $org   = Organization::create(['name' => 'Omega', 'reference_prefix' => 'OME', 'storage_bucket' => 'ged-omega']);
        Subscription::create([
            'organization_id' => $org->id,
            'plan_id'         => Plan::where('slug', 'entreprise')->value('id'),
            'starts_at'       => today()->subMonth(),
            'ends_at'         => today()->addYear(),
        ]);
        $admin = $this->member($org);

        $this->actingAs($admin)->post('/documents/create', [
            'title'       => 'Contrat',
            'import_mode' => 1,
            'import_file' => UploadedFile::fake()->createWithContent('contrat.pdf', "%PDF-1.4\n%%EOF\n"),
        ]);

        $doc = Document::withoutGlobalScopes()->where('organization_id', $org->id)->firstOrFail();
        $this->assertStringStartsWith('documents/', $doc->file_path);
        $org->fresh()->disk()->assertExists($doc->file_path);
        Storage::disk('s3')->assertMissing($doc->file_path);
        Storage::disk('s3')->assertExists('ged-omega/' . $doc->file_path);
    }

    public function test_bucket_cannot_change_once_the_organization_has_documents(): void
    {
        $org = $this->organization('Alpha');
        $org->update(['storage_bucket' => 'ged-alpha']);
        $this->document($this->member($org));

        $this->actingAs($this->superAdmin())->put("/super-admin/organizations/{$org->id}", [
            'name' => 'Alpha', 'reference_prefix' => 'ALP', 'storage_bucket' => 'ged-autre',
        ])->assertSessionHasErrors('storage_bucket');

        $this->assertSame('ged-alpha', $org->fresh()->storage_bucket);
    }

    public function test_super_admin_renews_a_subscription(): void
    {
        $org = $this->organization('Alpha', 'entreprise', today()->subDay()->toDateString());

        $this->actingAs($this->superAdmin())->post("/super-admin/organizations/{$org->id}/subscriptions", [
            'plan_id'        => Plan::where('slug', 'pro')->value('id'),
            'status'         => 'active',
            'starts_at'      => '2026-11-01',
            'months'         => 12,
            'amount'         => 900000,
            'payment_method' => 'orange_money',
        ])->assertRedirect();

        $sub = Subscription::where('organization_id', $org->id)->latest('id')->first();
        $this->assertSame('2027-10-31', $sub->ends_at->toDateString());
        $this->assertSame(900000, $sub->amount);
    }

    public function test_super_admin_can_enter_an_organization_and_actions_are_logged(): void
    {
        $adminA = $this->member($this->organization('Alpha'));
        $docA   = $this->document($adminA);
        $docB   = $this->document($this->member($this->organization('Beta')));
        $super  = $this->superAdmin();

        $this->actingAs($super)->post("/super-admin/organizations/{$adminA->organization_id}/enter")->assertRedirect('/dashboard');

        $this->get("/documents/{$docA->id}")->assertOk()->assertSee('Super admin');
        $this->get("/documents/{$docB->id}")->assertNotFound();
        $this->assertTrue(PlatformActivityLog::where('action', 'organization_entered')->where('organization_id', $adminA->organization_id)->exists());

        $this->post('/super-admin/leave');
        $this->get('/dashboard')->assertRedirect(route('super.dashboard'));
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $admin = $this->member($this->organization('Alpha'));
        $this->actingAs($this->superAdmin())->post("/super-admin/users/{$admin->id}/toggle-active");
        $this->assertFalse($admin->fresh()->is_active);

        $this->post('/logout');
        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deleting_an_organization_removes_its_files(): void
    {
        $org   = $this->organization('Alpha');
        $doc   = $this->document($this->member($org));

        $this->actingAs($this->superAdmin())
            ->delete("/super-admin/organizations/{$org->id}", ['confirm_name' => 'Alpha'])
            ->assertRedirect(route('super.organizations.index'));

        $this->assertDatabaseMissing('organizations', ['id' => $org->id]);
        $this->assertDatabaseMissing('documents', ['id' => $doc->id]);
        Storage::disk('s3')->assertMissing($doc->file_path);
    }

    public function test_expiring_subscription_reminder_reaches_org_admins(): void
    {
        $admin = $this->member($this->organization('Alpha', 'entreprise', today()->addDays(7)->toDateString()));

        $this->artisan('subscriptions:notify-expiring')->assertSuccessful();

        $this->assertTrue(DB::table('ged_notifications')->where('user_id', $admin->id)->where('type', 'subscription_expiring')->exists());
    }

    public function test_due_date_report_lists_all_organizations_without_deleting(): void
    {
        $docA = $this->document($this->member($this->organization('Alpha')));
        $docB = $this->document($this->member($this->organization('Beta')));
        Document::withoutGlobalScopes()->whereIn('id', [$docA->id, $docB->id])->update(['expires_at' => now()->subDay()]);

        $this->artisan('documents:cleanup-expired')
            ->expectsOutputToContain('2 document(s)')
            ->expectsOutputToContain($docB->reference)
            ->assertSuccessful();

        $this->assertNotSoftDeleted('documents', ['id' => $docA->id]);
        $this->assertNotSoftDeleted('documents', ['id' => $docB->id]);
    }

    public function test_every_super_admin_page_renders(): void
    {
        $org = $this->organization('Alpha');
        $this->document($this->member($org));
        $super = $this->superAdmin();
        $this->actingAs($super)->post("/super-admin/organizations/{$org->id}/enter");
        $this->post('/super-admin/leave');

        $plan = Plan::first();
        foreach ([
            '/super-admin', '/super-admin/organizations', '/super-admin/organizations?status=expired',
            '/super-admin/organizations/create', "/super-admin/organizations/{$org->id}",
            "/super-admin/organizations/{$org->id}/edit", "/super-admin/organizations/{$org->id}/settings", '/super-admin/subscriptions',
            "/super-admin/organizations/{$org->id}/subscriptions/create", '/super-admin/plans',
            '/super-admin/plans/create', "/super-admin/plans/{$plan->id}/edit", '/super-admin/users',
            '/super-admin/activity', '/super-admin/settings', '/super-admin/demo-requests',
        ] as $url) {
            $this->actingAs($super)->get($url)->assertOk();
        }

        // Pages métier vues par le super admin dans l'entreprise (bandeau)
        $this->actingAs($super)->post("/super-admin/organizations/{$org->id}/enter");
        foreach (['/dashboard', '/documents', '/categories', '/users', '/reports', '/trash'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    // --- Page d'accueil ---

    public function test_home_page_shows_both_formulas_and_saas_plans(): void
    {
        Plan::where('slug', 'pro')->update(['price' => 123456]);

        $this->get('/')->assertOk()
            ->assertSee('SaaS')
            ->assertSee('SingleEntity')
            ->assertSee('123 456')
            ->assertSee(config('saas.vendor_name'))
            ->assertDontSee('Groupe Bama');
    }

    public function test_demo_request_is_saved_and_super_admin_notified(): void
    {
        $super = $this->superAdmin();

        $this->post('/demo-request', [
            'formula' => 'single_entity', 'company' => 'Banque Test', 'contact_name' => 'Moussa Keita',
            'email' => 'moussa@banque.test', 'phone' => '+223 70 00 00 00', 'company_size' => '51-200',
            'message' => '300 utilisateurs, hébergement interne obligatoire',
        ])->assertRedirect(url('/') . '#contact')->assertSessionHas('demo_success');

        $this->assertDatabaseHas('demo_requests', ['company' => 'Banque Test', 'formula' => 'single_entity', 'status' => 'new']);
        $this->assertTrue(DB::table('ged_notifications')->where('user_id', $super->id)->where('type', 'demo_request')->exists());

        $this->actingAs($super)->get('/super-admin/demo-requests')->assertOk()->assertSee('Banque Test');
        $demo = DemoRequest::first();
        $this->put("/super-admin/demo-requests/{$demo->id}", ['status' => 'contacted', 'notes' => 'Rappel lundi'])->assertRedirect();
        $this->assertSame('contacted', $demo->fresh()->status);
    }

    public function test_demo_request_honeypot_discards_bots(): void
    {
        $this->post('/demo-request', [
            'formula' => 'saas', 'company' => 'Spam', 'contact_name' => 'Bot', 'email' => 'bot@spam.test', 'website' => 'http://spam.test',
        ])->assertSessionHas('demo_success');

        $this->assertSame(0, DemoRequest::count());
    }
}
