<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DepartmentAccessTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');

        $this->org = $this->organization('Test SA', 'TST');

        foreach (['admin', 'editor', 'viewer'] as $name) {
            Role::create(['name' => $name, 'display_name' => ucfirst($name)]);
        }
    }

    protected function tearDown(): void
    {
        Tenant::clear();
        parent::tearDown();
    }

    private function organization(string $name, string $prefix): Organization
    {
        $org = Organization::create(['name' => $name, 'reference_prefix' => $prefix]);
        Subscription::create([
            'organization_id' => $org->id,
            'plan_id'         => Plan::where('slug', 'entreprise')->value('id'),
            'starts_at'       => today(),
            'ends_at'         => today()->addYear(),
        ]);

        return $org;
    }

    private function userWithRole(string $role, ?Organization $org = null): User
    {
        $user = User::factory()->create(['organization_id' => ($org ?? $this->org)->id]);
        $user->roles()->attach(Role::where('name', $role)->first());
        return $user;
    }

    private function category(string $name, ?Category $parent = null, bool $public = false): Category
    {
        return Category::create([
            'organization_id' => $this->org->id,
            'name'      => $name,
            'slug'      => str($name)->slug(),
            'parent_id' => $parent?->id,
            'is_public' => $public,
        ]);
    }

    private function department(string $name, array $members, array $access = []): Department
    {
        $department = Department::create(['organization_id' => $this->org->id, 'name' => $name]);
        $department->users()->sync(collect($members)->pluck('id'));
        $department->categories()->sync(collect($access)->map(fn ($level) => ['access_level' => $level]));
        return $department;
    }

    private function documentIn(?Category $category, User $creator, bool $confidential = false): Document
    {
        $ref = 'TST-' . strtoupper(fake()->unique()->bothify('??####'));
        $path = $this->org->documentsDir() . "{$ref}.docx";
        Storage::disk('s3')->put($path, 'contenu');

        return Document::create([
            'organization_id' => $this->org->id,
            'reference'       => $ref,
            'title'           => 'Doc ' . $ref,
            'file_path'       => $path,
            'version'         => 1,
            'status'          => 'draft',
            'creator_id'      => $creator->id,
            'category_id'     => $category?->id,
            'is_confidential' => $confidential,
        ]);
    }

    private function visibleIds(User $user): array
    {
        return Tenant::run($this->org, fn () => Document::visibleTo($user->id)->pluck('id')->all());
    }

    public function test_department_members_see_documents_of_open_categories(): void
    {
        $author  = $this->userWithRole('editor');
        $member  = $this->userWithRole('viewer');
        $outside = $this->userWithRole('viewer');
        $rh      = $this->category('RH');
        $this->department('Ressources humaines', [$member], [$rh->id => 'view']);
        $doc = $this->documentIn($rh, $author);

        $this->assertContains($doc->id, $this->visibleIds($member));
        $this->assertNotContains($doc->id, $this->visibleIds($outside));

        $this->actingAs($member)->get("/documents/{$doc->id}")->assertOk();
        $this->actingAs($outside)->get("/documents/{$doc->id}")->assertForbidden();
    }

    public function test_access_is_inherited_by_subcategories(): void
    {
        $member = $this->userWithRole('viewer');
        $compta = $this->category('Comptabilité');
        $factures = $this->category('Factures', $compta);
        $this->department('Compta', [$member], [$compta->id => 'view']);
        $doc = $this->documentIn($factures, $this->userWithRole('editor'));

        $this->assertTrue($doc->canView($member->id));
    }

    public function test_confidential_documents_are_not_opened_by_department(): void
    {
        $member = $this->userWithRole('editor');
        $rh     = $this->category('RH');
        $this->department('RH', [$member], [$rh->id => 'edit']);
        $doc = $this->documentIn($rh, $this->userWithRole('editor'), confidential: true);

        $this->assertNotContains($doc->id, $this->visibleIds($member));
        $this->assertFalse($doc->canView($member->id));
    }

    public function test_edit_access_lets_editors_edit_but_not_viewers(): void
    {
        $editor = $this->userWithRole('editor');
        $viewer = $this->userWithRole('viewer');
        $juridique = $this->category('Juridique');
        $this->department('Juridique', [$editor, $viewer], [$juridique->id => 'edit']);
        $doc = $this->documentIn($juridique, $this->userWithRole('editor'));

        $this->assertTrue($doc->canEdit($editor->id));
        $this->assertFalse($doc->canEdit($viewer->id));
        $this->assertTrue($doc->canView($viewer->id));
        $this->assertFalse($doc->canManage($editor->id));

        $this->actingAs($editor)
            ->put("/documents/{$doc->id}", ['title' => 'Contrat révisé', 'category_id' => $juridique->id])
            ->assertSessionHasNoErrors();
        $this->assertSame('Contrat révisé', $doc->fresh()->title);
    }

    public function test_public_category_is_visible_to_everyone(): void
    {
        $notes = $this->category('Notes de service', public: true);
        $doc = $this->documentIn($notes, $this->userWithRole('admin'));
        $anyone = $this->userWithRole('viewer');

        $this->assertTrue($doc->canView($anyone->id));
        $this->assertFalse($doc->canEdit($anyone->id));
    }

    public function test_restricted_category_requires_edit_access_to_file_documents(): void
    {
        $member   = $this->userWithRole('editor');
        $outsider = $this->userWithRole('editor');
        $compta   = $this->category('Comptabilité');
        $libre    = $this->category('Divers');
        $this->department('Compta', [$member], [$compta->id => 'edit']);

        $this->assertTrue($member->canFileInCategory($compta->id));
        $this->assertFalse($outsider->canFileInCategory($compta->id));
        // Une catégorie sans règle reste ouverte comme avant
        $this->assertTrue($outsider->canFileInCategory($libre->id));

        $this->actingAs($outsider)
            ->post('/documents/create', ['title' => 'Facture', 'category_id' => $compta->id,
                'import_file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('facture.pdf', "%PDF-1.4\n%%EOF\n")])
            ->assertSessionHasErrors('category_id');

        // Ne peut pas non plus y déplacer un de ses documents
        $own = $this->documentIn($libre, $outsider);
        $this->actingAs($outsider)
            ->put("/documents/{$own->id}", ['title' => $own->title, 'category_id' => $compta->id])
            ->assertSessionHasErrors('category_id');
        $this->assertSame($libre->id, $own->fresh()->category_id);
    }

    public function test_category_page_only_lists_visible_documents(): void
    {
        $viewer = $this->userWithRole('viewer');
        $cat    = $this->category('Archives');
        $hidden = $this->documentIn($cat, $this->userWithRole('editor'));

        $this->actingAs($viewer)->get("/categories/{$cat->id}")
            ->assertOk()
            ->assertDontSee($hidden->reference);
    }

    public function test_admin_configures_a_common_department_for_his_organization(): void
    {
        $admin  = $this->userWithRole('admin');
        $member = $this->userWithRole('viewer');
        $boss   = $this->userWithRole('editor');
        $cat    = $this->category('RH');
        $department = Department::create(['name' => 'Ressources humaines']);

        $this->actingAs($admin)->put("/departments/{$department->id}", [
            'manager_id' => $boss->id,
            'members'    => [$member->id],
            'access'     => [$cat->id => 'view'],
        ])->assertRedirect('/departments');

        // Le responsable est automatiquement membre
        $this->assertEqualsCanonicalizing([$member->id, $boss->id], $department->users()->pluck('users.id')->all());
        $this->assertSame('view', $department->categories()->first()->pivot->access_level);
        $this->assertSame($boss->id, $department->managerFor($this->org->id)->id);

        $this->actingAs($admin)->get('/departments')->assertOk()->assertSee('Ressources humaines')->assertSee($boss->full_name);
        $this->actingAs($admin)->get("/departments/{$department->id}/edit")->assertOk();
    }

    public function test_configuring_a_department_never_touches_other_organizations(): void
    {
        $department = Department::create(['name' => 'Comptabilité']);

        // Entreprise B : membre, responsable et droit sur sa catégorie
        $other      = $this->organization('Autre SARL', 'AUT');
        $outsider   = $this->userWithRole('editor', $other);
        $otherCat   = Category::create(['organization_id' => $other->id, 'name' => 'Factures', 'slug' => 'factures']);
        $department->users()->attach($outsider->id);
        $department->categories()->attach($otherCat->id, ['access_level' => 'edit']);
        $department->setManager($other->id, $outsider->id);

        // Entreprise A vide complètement le service chez elle
        $this->actingAs($this->userWithRole('admin'))
            ->put("/departments/{$department->id}", ['members' => [], 'access' => []])
            ->assertRedirect('/departments');

        $this->assertTrue(\DB::table('department_user')->where(['department_id' => $department->id, 'user_id' => $outsider->id])->exists());
        $this->assertSame('edit', \DB::table('category_department')->where(['department_id' => $department->id, 'category_id' => $otherCat->id])->value('access_level'));
        $this->assertSame($outsider->id, $department->managerFor($other->id)->id);
        $this->assertNull($department->managerFor($this->org->id));
    }

    public function test_company_admin_cannot_create_rename_or_delete_departments(): void
    {
        $admin = $this->userWithRole('admin');
        $department = Department::create(['name' => 'Juridique']);

        $this->actingAs($admin)->post('/departments', ['name' => 'Pirates'])->assertStatus(405);
        $this->actingAs($admin)->delete("/departments/{$department->id}")->assertStatus(405);
        $this->actingAs($admin)->put("/departments/{$department->id}", ['name' => 'Renommé', 'members' => []]);
        $this->assertSame('Juridique', $department->fresh()->name);
        $this->actingAs($admin)->get('/super-admin/departments')->assertForbidden();

        $this->assertSame(1, Department::count());
    }

    public function test_non_admin_cannot_manage_departments(): void
    {
        $editor = $this->userWithRole('editor');
        $department = Department::create(['name' => 'Direction']);

        $this->actingAs($editor)->get('/departments')->assertForbidden();
        $this->actingAs($editor)->put("/departments/{$department->id}", ['members' => [$editor->id]])->assertForbidden();
    }

    public function test_cannot_add_users_from_another_organization(): void
    {
        $admin    = $this->userWithRole('admin');
        $stranger = $this->userWithRole('editor', $this->organization('Autre SARL', 'AUT'));
        $department = Department::create(['name' => 'Direction']);

        $this->actingAs($admin)
            ->put("/departments/{$department->id}", ['members' => [$stranger->id]])
            ->assertSessionHasErrors('members.0');
        $this->actingAs($admin)
            ->put("/departments/{$department->id}", ['manager_id' => $stranger->id])
            ->assertSessionHasErrors('manager_id');
    }

    public function test_quick_category_edit_keeps_existing_access_rules(): void
    {
        $admin = $this->userWithRole('admin');
        $cat   = $this->category('RH', public: true);
        $department = $this->department('RH', [], [$cat->id => 'edit']);

        // Modale rapide de la liste : pas de champ manage_access
        $this->actingAs($admin)->put("/categories/{$cat->id}", ['name' => 'RH & Paie', 'slug' => 'rh-paie'])->assertRedirect();
        $this->assertTrue($cat->fresh()->is_public);
        $this->assertSame(1, $cat->departments()->count());

        // Page complète : les droits sont remplacés
        $this->actingAs($admin)->put("/categories/{$cat->id}", [
            'name' => 'RH & Paie', 'slug' => 'rh-paie', 'manage_access' => 1, 'is_public' => 0,
            'access' => [$department->id => 'view'],
        ])->assertRedirect();
        $this->assertFalse($cat->fresh()->is_public);
        $this->assertSame('view', $cat->departments()->first()->pivot->access_level);
    }

    public function test_user_departments_are_set_from_user_form(): void
    {
        $admin = $this->userWithRole('admin');
        $department = $this->department('Compta', []);
        $user = $this->userWithRole('viewer');

        $this->actingAs($admin)->put("/users/{$user->id}", [
            'full_name' => $user->full_name, 'email' => $user->email,
            'manage_departments' => 1, 'departments' => [$department->id],
        ])->assertRedirect();

        $this->assertSame([$department->id], $user->departments()->pluck('departments.id')->all());
    }
}
