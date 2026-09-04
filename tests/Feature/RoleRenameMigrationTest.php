<?php

namespace Tests\Feature;

use App\Models\Roles;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RoleRenameMigrationTest extends TestCase
{
    public function test_role_rename_preserves_ids_users_permissions_and_rolls_back(): void
    {
        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();
        });

        DB::table('roles')->whereIn('id', [1, 2, 3])->update([
            'name' => DB::raw("CASE id WHEN 1 THEN 'store' WHEN 2 THEN 'admin' ELSE 'staff' END"),
        ]);

        $userId = DB::table('users')->insertGetId([
            'name' => 'Legacy administrator',
            'email' => 'legacy-administrator@example.test',
            'password' => 'password',
            'role_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $permissionId = DB::table('permissions')->insertGetId([
            'module' => 'Role migration',
            'permission_key' => 'role.rename.migration',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('role_permission')->insert([
            'guard_name' => 'web',
            'role_id' => 2,
            'permission_id' => $permissionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_08_20_000000_rename_legacy_roles_to_canonical_keys.php');
        $migration->up();

        $this->assertDatabaseHas('roles', ['id' => 1, 'name' => Roles::ADMINISTRATOR]);
        $this->assertDatabaseHas('roles', ['id' => 2, 'name' => Roles::ADMIN_STORE]);
        $this->assertDatabaseHas('roles', ['id' => 3, 'name' => Roles::STAFF]);
        $this->assertDatabaseHas('users', ['id' => $userId, 'role_id' => 1]);
        $this->assertDatabaseHas('role_permission', [
            'role_id' => 2,
            'permission_id' => $permissionId,
        ]);
        $this->assertTrue(User::findOrFail($userId)->hasFullAccess());

        $migration->down();

        $this->assertDatabaseHas('roles', ['id' => 1, 'name' => 'store']);
        $this->assertDatabaseHas('roles', ['id' => 2, 'name' => 'admin']);
    }
}
