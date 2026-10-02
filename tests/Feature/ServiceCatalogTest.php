<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Tenant;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Services communs : gérés par le super admin, utilisés par toutes les entreprises.
 */
class ServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = $this->organization('Alpha SA', 'ALP');
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

    private function user(string $role, ?Organization $org = null): User
    {
        $user = User::factory()->create(['organization_id' => ($org ?? $this->org)->id]);
        $user->roles()->attach(Role::where('name', $role)->first());
        return $user;
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['is_super_admin' => true, 'organization_id' => null]);
    }

    public function test_seeder_creates_common_services_once(): void
    {
        $this->seed(DepartmentSeeder::class);
        $this->seed(DepartmentSeeder::class);

        $this->assertSame(count(DepartmentSeeder::SERVICES), Department::count());
        $this->assertTrue(Department::where('name', 'Ressources Humaines')->exists());
    }

    public function test_super_admin_creates_renames_and_lists_services(): void
    {
        $super = $this->superAdmin();

        $this->actingAs($super)->post('/super-admin/departments', ['name' => 'Contrôle de gestion'])->assertRedirect();
        $department = Department::where('name', 'Contrôle de gestion')->firstOrFail();

        $this->post('/super-admin/departments', ['name' => 'Contrôle de gestion'])->assertSessionHasErrors('name');

        $this->put("/super-admin/departments/{$department->id}", ['name' => 'Contrôle de Gestion', 'description' => 'Tableaux de bord'])->assertRedirect();
        $this->assertSame('Contrôle de Gestion', $department->fresh()->name);

        $this->get('/super-admin/departments')->assertOk()->assertSee('Contrôle de Gestion');
    }

    public function test_service_is_shared_by_all_organizations(): void
    {
        $department = Department::create(['name' => 'Juridique']);
        $other = $this->organization('Beta SARL', 'BET');

        Tenant::set($this->org);
        $this->actingAs($this->user('admin'))->get('/departments')->assertOk()->assertSee('Juridique');
        Tenant::set($other);
        $this->actingAs($this->user('admin', $other))->get('/departments')->assertOk()->assertSee('Juridique');
    }

    public function test_deleting_a_service_with_members_requires_a_replacement(): void
    {
        $rh     = Department::create(['name' => 'RH']);
        $target = Department::create(['name' => 'Direction Générale']);
        $member = $this->user('viewer');
        $cat    = Category::create(['organization_id' => $this->org->id, 'name' => 'Paie', 'slug' => 'paie']);
        $rh->users()->attach($member->id);
        $rh->categories()->attach($cat->id, ['access_level' => 'edit']);
        $rh->setManager($this->org->id, $member->id);

        $super = $this->superAdmin();
        $this->actingAs($super)->delete("/super-admin/departments/{$rh->id}")->assertSessionHasErrors('merge_into');
        $this->assertNotNull($rh->fresh());

        $this->delete("/super-admin/departments/{$rh->id}", ['merge_into' => $target->id])->assertRedirect();

        $this->assertNull($rh->fresh());
        // Membres, droits et responsable transférés : personne ne se retrouve sans service
        $this->assertTrue(DB::table('department_user')->where(['department_id' => $target->id, 'user_id' => $member->id])->exists());
        $this->assertSame('edit', DB::table('category_department')->where(['department_id' => $target->id, 'category_id' => $cat->id])->value('access_level'));
        $this->assertSame($member->id, $target->managerFor($this->org->id)->id);
    }

    public function test_empty_service_can_be_deleted_directly(): void
    {
        $department = Department::create(['name' => 'Inutilisé']);

        $this->actingAs($this->superAdmin())->delete("/super-admin/departments/{$department->id}")->assertRedirect();
        $this->assertNull($department->fresh());
    }

    public function test_company_admin_must_assign_new_users_to_a_service(): void
    {
        $department = Department::create(['name' => 'Comptabilité']);
        $admin = $this->user('admin');
        $payload = [
            'full_name' => 'Awa Traoré', 'email' => 'awa@alpha.test',
            'password' => 'motdepasse', 'password_confirmation' => 'motdepasse',
        ];

        $this->actingAs($admin)->post('/users', $payload)->assertSessionHasErrors('departments');
        $this->assertFalse(User::where('email', 'awa@alpha.test')->exists());

        $this->post('/users', $payload + ['departments' => [$department->id]])->assertRedirect(route('users.index'));
        $user = User::where('email', 'awa@alpha.test')->firstOrFail();
        $this->assertSame([$department->id], $user->departments()->pluck('departments.id')->all());

        // En modification, on ne peut pas retirer tous ses services
        $this->put("/users/{$user->id}", [
            'full_name' => 'Awa Traoré', 'email' => 'awa@alpha.test', 'manage_departments' => 1, 'departments' => [],
        ])->assertSessionHasErrors('departments');
        $this->assertSame(1, $user->departments()->count());
    }

    public function test_super_admin_assigns_a_service_when_adding_a_user(): void
    {
        $department = Department::create(['name' => 'Informatique']);
        $super = $this->superAdmin();
        $payload = ['full_name' => 'Ali Coulibaly', 'email' => 'ali@alpha.test', 'password' => 'motdepasse', 'role_id' => Role::where('name', 'editor')->value('id')];

        $this->actingAs($super)->post("/super-admin/organizations/{$this->org->id}/users", $payload)->assertSessionHasErrors('department_id');

        $this->post("/super-admin/organizations/{$this->org->id}/users", $payload + ['department_id' => $department->id])->assertRedirect();
        $this->assertSame(1, User::withoutGlobalScopes()->where('email', 'ali@alpha.test')->firstOrFail()->departments()->count());
    }
}
