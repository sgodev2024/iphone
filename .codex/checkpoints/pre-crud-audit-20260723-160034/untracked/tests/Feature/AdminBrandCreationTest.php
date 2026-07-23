<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminBrandCreationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    public function test_admin_can_create_brand_with_logo(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post('/admin/brand', [
            'name' => 'Apple',
            'description' => 'Thiet bi di dong',
            'status' => '1',
            'logo' => UploadedFile::fake()->image('apple.png', 120, 120),
        ], $this->ajaxHeaders());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Apple')
            ->assertJsonPath('data.status', true);

        $brand = Brand::where('name', 'Apple')->first();

        $this->assertNotNull($brand);
        $this->assertSame('Thiet bi di dong', $brand->description);
        $this->assertTrue((bool) $brand->status);
        $this->assertNotEmpty($brand->logo);
        Storage::disk('public')->assertExists($brand->logo);
    }

    public function test_admin_can_create_brand_without_logo(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post('/admin/brand', [
            'name' => 'Samsung',
            'description' => 'Khong co logo',
            'status' => '0',
        ], $this->ajaxHeaders());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Samsung')
            ->assertJsonPath('data.status', false);

        $this->assertDatabaseHas('brands', [
            'name' => 'Samsung',
            'description' => 'Khong co logo',
            'status' => 0,
            'logo' => null,
        ]);
    }

    public function test_missing_brand_name_returns_validation_error(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post('/admin/brand', [
            'name' => '',
            'description' => 'Thieu ten',
            'status' => '1',
        ], $this->ajaxHeaders());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_duplicate_brand_name_returns_validation_error(): void
    {
        $admin = $this->createAdmin();
        Brand::create([
            'name' => 'Xiaomi',
            'status' => 1,
        ]);

        $response = $this->actingAs($admin)->post('/admin/brand', [
            'name' => 'Xiaomi',
            'description' => 'Ten trung',
            'status' => '1',
        ], $this->ajaxHeaders());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_invalid_logo_file_returns_validation_error(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post('/admin/brand', [
            'name' => 'Oppo',
            'description' => 'Logo sai dinh dang',
            'status' => '1',
            'logo' => UploadedFile::fake()->create('logo.txt', 8, 'text/plain'),
        ], $this->ajaxHeaders());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['logo']);

        $this->assertDatabaseMissing('brands', [
            'name' => 'Oppo',
        ]);
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

    private function ajaxHeaders(): array
    {
        return [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ];
    }

    private function createSchema(): void
    {
        Schema::dropIfExists('brands');
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

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }
}
