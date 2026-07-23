<?php

namespace Tests\Feature;

use App\Models\Categories;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminCategoryManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    public function test_admin_can_create_category(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->postJson('/admin/category', [
            'name' => 'Phu kien dien thoai',
            'description' => 'Cap sac, op lung',
            'status' => '1',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Phu kien dien thoai');

        $this->assertDatabaseHas('categories', [
            'name' => 'Phu kien dien thoai',
            'description' => 'Cap sac, op lung',
            'status' => 1,
        ]);
    }

    public function test_admin_can_update_category(): void
    {
        $admin = $this->createAdmin();
        $category = Categories::create([
            'name' => 'Dien thoai',
            'description' => 'Mo ta cu',
            'status' => 1,
        ]);

        $response = $this->actingAs($admin)->putJson("/admin/category/{$category->id}", [
            'name' => 'Dien thoai cao cap',
            'description' => 'Mo ta moi',
            'status' => '0',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Dien thoai cao cap');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Dien thoai cao cap',
            'description' => 'Mo ta moi',
            'status' => 0,
        ]);
    }

    public function test_duplicate_category_name_returns_validation_error(): void
    {
        $admin = $this->createAdmin();
        Categories::create([
            'name' => 'Tablet',
            'status' => 1,
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/category', [
            'name' => 'Tablet',
            'description' => 'Ten trung',
            'status' => '1',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_missing_category_name_returns_validation_error(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->postJson('/admin/category', [
            'name' => '',
            'description' => 'Thieu ten',
            'status' => '1',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'phone' => '0900000000',
            'password' => 'password',
            'role_id' => 1,
            'status' => 'active',
        ]);
    }

    private function createSchema(): void
    {
        Schema::dropIfExists('categories');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->unsignedBigInteger('role_id')->default(1);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }
}
