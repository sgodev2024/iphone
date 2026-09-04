<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAuthorizationTablesForTests();
    }

    protected function createAuthorizationTablesForTests(): void
    {
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'administrator', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'admin_store', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'staff', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'warehouse', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('module');
            $table->string('permission_key')->unique();
            $table->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $table): void {
            $table->id();
            $table->string('guard_name');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });

        $permissionKeys = collect(app('router')->getRoutes()->getRoutes())
            ->flatMap(fn ($route) => $route->gatherMiddleware())
            ->filter(fn ($middleware) => is_string($middleware))
            ->map(function (string $middleware): ?string {
                [$name, $arguments] = array_pad(explode(':', $middleware, 2), 2, null);

                if ($name !== 'permission' && ! str_ends_with($name, 'PermissionMiddleware')) {
                    return null;
                }

                return $arguments !== null ? trim(explode(',', $arguments)[0]) : null;
            })
            ->filter()
            ->unique()
            ->values();

        foreach ($permissionKeys as $permissionKey) {
            $permissionId = DB::table('permissions')->insertGetId([
                'module' => 'Test',
                'permission_key' => $permissionKey,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('role_permission')->insert([
                [
                    'guard_name' => 'web',
                    'role_id' => 2,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'guard_name' => 'web',
                    'role_id' => 3,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'guard_name' => 'web',
                    'role_id' => 4,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
