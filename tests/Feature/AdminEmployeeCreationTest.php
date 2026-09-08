<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureAccountIsActive;
use App\Mail\SendMailInfo;
use App\Models\Storage;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage as StorageFacade;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AdminEmployeeCreationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'pos.default_storage_id' => null,
            'pos.default_storage_name' => 'Kho A',
        ]);

        $this->createSchema();
    }

    public function test_administrator_creates_unassigned_admin_store_even_when_account_email_fails(): void
    {
        $admin = $this->createAdmin();

        Mail::shouldReceive('to')
            ->once()
            ->with('admin-store@example.com')
            ->andReturn(new class
            {
                public function send(SendMailInfo $mail): void
                {
                    throw new RuntimeException('SMTP is down');
                }
            });

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to send employee account email.', Mockery::on(function (array $context) {
                return ($context['email'] ?? null) === 'admin-store@example.com'
                    && ($context['error'] ?? null) === 'SMTP is down';
            }));

        $response = $this->actingAs($admin)->postJson('/admin/employees', [
            'name' => 'Admin Store mới',
            'email' => 'admin-store@example.com',
            'phone' => '0901234567',
            'password' => 'secret123',
            'role_id' => 2,
            'address' => 'Hà Nội',
            'status' => 'active',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.redirect', '/admin/employees');

        $employee = User::where('email', 'admin-store@example.com')->first();

        $this->assertNotNull($employee);
        $this->assertSame(2, (int) $employee->role_id);
        $this->assertNull($employee->manager_id);
        $this->assertNull($employee->branch_id);
        $this->assertNull($employee->storage_id);
        $this->assertSame('active', $employee->status);
        $this->assertTrue(Hash::check('secret123', $employee->password));
    }

    public function test_employee_validation_returns_field_errors(): void
    {
        $admin = $this->createAdmin();
        $storage = Storage::create([
            'user_id' => $admin->id,
            'name' => 'Kho bán hàng',
        ]);
        User::create([
            'name' => 'Existing',
            'email' => 'taken@example.com',
            'phone' => '0909999999',
            'password' => 'password',
            'role_id' => 3,
            'manager_id' => $admin->id,
            'storage_id' => $storage->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/employees', [
            'name' => '',
            'email' => 'taken@example.com',
            'phone' => '0909999999',
            'storage_id' => $storage->id,
            'status' => 'active',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'phone', 'password']);
    }

    public function test_create_accepts_supported_avatar_formats_up_to_ten_megabytes(): void
    {
        Mail::fake();
        StorageFacade::fake('public');
        $admin = $this->createAdmin();
        $avatars = [
            UploadedFile::fake()->image('avatar-1mb.jpg')->size(1024),
            UploadedFile::fake()->image('avatar-5mb.png')->size(5 * 1024),
            $this->fakeWebp('avatar-9mb.webp', 9 * 1024),
            UploadedFile::fake()->image('avatar-10mb.jpg')->size(10 * 1024),
        ];

        foreach ($avatars as $index => $avatar) {
            $response = $this->actingAs($admin)
                ->withHeader('X-Requested-With', 'XMLHttpRequest')
                ->post('/admin/employees', [
                    'name' => 'Admin Store Avatar '.$index,
                    'email' => "avatar-{$index}@example.com",
                    'phone' => '09100000'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                    'password' => 'secret123',
                    'role_id' => 2,
                    'status' => 'active',
                    'img_url' => $avatar,
                ]);

            $response->assertCreated()
                ->assertJsonPath('success', true);

            $path = User::where('email', "avatar-{$index}@example.com")
                ->value('img_url');

            $this->assertNotNull($path);
            $this->assertStringStartsWith('avatar/', $path);
            StorageFacade::disk('public')->assertExists($path);
        }
    }

    public function test_create_rejects_oversized_and_unsafe_avatar_files_with_clear_messages(): void
    {
        Mail::fake();
        StorageFacade::fake('public');
        $admin = $this->createAdmin();
        $cases = [
            [
                UploadedFile::fake()->image('too-large.jpg')->size((10 * 1024) + 1),
                config('uploads.avatar.size_message'),
            ],
            [
                UploadedFile::fake()
                    ->createWithContent('renamed.jpg', 'This is a text file.')
                    ->mimeType('text/plain'),
                config('uploads.avatar.format_message'),
            ],
            [
                UploadedFile::fake()
                    ->createWithContent('shell.php', '<?php echo "unsafe";')
                    ->mimeType('application/x-httpd-php'),
                config('uploads.avatar.format_message'),
            ],
        ];

        foreach ($cases as $index => [$avatar, $message]) {
            $response = $this->actingAs($admin)
                ->withHeader('X-Requested-With', 'XMLHttpRequest')
                ->post('/admin/employees', [
                    'name' => 'Rejected Avatar '.$index,
                    'email' => "rejected-avatar-{$index}@example.com",
                    'phone' => '09200000'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                    'password' => 'secret123',
                    'role_id' => 2,
                    'status' => 'active',
                    'img_url' => $avatar,
                ]);

            $response->assertUnprocessable()
                ->assertJsonPath('message', $message)
                ->assertJsonPath('errors.img_url.0', $message);

            $this->assertDatabaseMissing('users', [
                'email' => "rejected-avatar-{$index}@example.com",
            ]);
        }
    }

    public function test_update_uses_the_same_ten_megabyte_limit_and_keeps_avatar_optional(): void
    {
        StorageFacade::fake('public');
        $admin = $this->createAdmin();

        $accepted = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post('/admin/employees/'.$admin->id, [
                '_method' => 'PUT',
                'name' => $admin->name,
                'email' => $admin->email,
                'phone' => $admin->phone,
                'status' => 'active',
                'img_url' => UploadedFile::fake()->image('new-avatar.jpg')->size(10 * 1024),
            ]);

        $accepted->assertOk();
        $acceptedPath = $admin->fresh()->getRawOriginal('img_url');
        $this->assertNotNull($acceptedPath);
        StorageFacade::disk('public')->assertExists($acceptedPath);

        $rejected = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post('/admin/employees/'.$admin->id, [
                '_method' => 'PUT',
                'name' => $admin->name,
                'email' => $admin->email,
                'phone' => $admin->phone,
                'status' => 'active',
                'img_url' => UploadedFile::fake()->image('too-large.jpg')->size((10 * 1024) + 1),
            ]);

        $rejected->assertUnprocessable()
            ->assertJsonPath('message', config('uploads.avatar.size_message'))
            ->assertJsonValidationErrors('img_url');
        $this->assertSame($acceptedPath, $admin->fresh()->getRawOriginal('img_url'));

        $withoutAvatar = $this->actingAs($admin)->putJson('/admin/employees/'.$admin->id, [
            'name' => 'Admin Without New Avatar',
            'email' => $admin->email,
            'phone' => $admin->phone,
            'status' => 'active',
        ]);

        $withoutAvatar->assertOk();
        $this->assertSame($acceptedPath, $admin->fresh()->getRawOriginal('img_url'));
    }

    public function test_employee_form_exposes_the_shared_avatar_limit_and_formats(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->get('/admin/employees/create')
            ->assertOk();

        $response->assertSee('accept="image/jpeg,image/png,image/webp"', false)
            ->assertSee('data-avatar-max-bytes="10485760"', false)
            ->assertSee(config('uploads.avatar.size_message'))
            ->assertSee(config('uploads.avatar.format_message'))
            ->assertSee('file.size > maxBytes', false);
    }

    public function test_ajax_post_too_large_exception_returns_safe_json_message(): void
    {
        $request = \Illuminate\Http\Request::create('/admin/employees', 'POST');
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = app(\Illuminate\Contracts\Debug\ExceptionHandler::class)
            ->render($request, new \Illuminate\Http\Exceptions\PostTooLargeException);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(413, $response->getStatusCode());
        $this->assertSame('Tệp tải lên vượt quá dung lượng cho phép.', $payload['message']);
        $this->assertArrayNotHasKey('errors', $payload);
    }

    public function test_employee_credentials_login_to_staff_area(): void
    {
        User::create([
            'name' => 'Nhân viên bán hàng',
            'email' => 'staff-login@example.com',
            'phone' => '0901234567',
            'password' => 'secret123',
            'role_id' => 3,
            'status' => 'active',
        ]);

        $response = $this->postJson('/login', [
            'email' => 'staff-login@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', route('staff.index', absolute: false));
    }

    public function test_admin_store_cannot_assign_employee_storage_from_another_branch(): void
    {
        $admin = User::create([
            'name' => 'Admin Store A',
            'email' => 'store-a@example.com',
            'phone' => '0901111100',
            'password' => 'password',
            'role_id' => 2,
            'branch_id' => 10,
            'status' => 'active',
        ]);
        $otherStorage = Storage::create([
            'branch_id' => 20,
            'name' => 'Kho của admin khác',
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/employees', [
            'name' => 'Nhân viên bán hàng',
            'email' => 'staff@example.com',
            'phone' => '0901234567',
            'password' => 'secret123',
            'storage_id' => $otherStorage->id,
            'status' => 'active',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['storage_id']);

        $this->assertDatabaseMissing('users', [
            'email' => 'staff@example.com',
        ]);
    }

    public function test_staff_requires_storage_and_administrator_cannot_create_staff(): void
    {
        Mail::fake();
        $adminStore = User::create([
            'name' => 'Admin Store',
            'email' => 'storage-manager@example.com',
            'phone' => '0901234568',
            'password' => 'password',
            'role_id' => 2,
            'branch_id' => 10,
            'status' => 'active',
        ]);

        $this->actingAs($adminStore)->postJson('/admin/employees', [
            'name' => 'Nhân viên thiếu kho',
            'email' => 'missing-storage@example.com',
            'phone' => '0901234569',
            'password' => 'secret123',
            'status' => 'active',
        ])->assertUnprocessable()->assertJsonValidationErrors('storage_id');

        $admin = $this->createAdmin();
        $this->actingAs($admin)->postJson('/admin/employees', [
            'name' => 'Forbidden Staff',
            'email' => 'forbidden-staff@example.com',
            'phone' => '0901234570',
            'password' => 'secret123',
            'role_id' => 3,
            'status' => 'active',
        ])->assertUnprocessable()->assertJsonValidationErrors('role_id');

        $this->assertDatabaseMissing('users', ['email' => 'missing-storage@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'forbidden-staff@example.com']);
    }

    public function test_administrator_index_shows_only_administrators_and_admin_stores(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = $this->createAdmin('other.system@example.com', '0902222222');
        $storage = Storage::create([
            'user_id' => $admin->id,
            'name' => 'Kho A',
        ]);
        $employee = User::create([
            'name' => 'Nhân viên thuộc quyền',
            'email' => 'visible-staff@example.com',
            'phone' => '0903333333',
            'password' => 'password',
            'role_id' => 3,
            'manager_id' => $admin->id,
            'storage_id' => $storage->id,
            'status' => 'active',
        ]);

        $adminStore = User::create([
            'name' => 'Admin Store hiển thị',
            'email' => 'admin-store-listed@example.com',
            'phone' => '0904444444',
            'password' => 'password',
            'role_id' => 2,
            'manager_id' => $admin->id,
            'status' => 'active',
        ]);

        $html = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/admin/employees')
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('Administrator', $html);
        $this->assertStringContainsString('Admin Store', $html);
        $this->assertStringContainsString('Toàn hệ thống', $html);
        $this->assertStringContainsString($admin->email, $html);
        $this->assertStringContainsString($otherAdmin->email, $html);
        $this->assertStringContainsString($adminStore->email, $html);
        $this->assertStringNotContainsString($employee->email, $html);
        $this->assertStringNotContainsString('value="'.$admin->id.'"', $html);
        $this->assertStringNotContainsString('data-id="'.$admin->id.'"', $html);
        $this->assertStringContainsString('value="'.$otherAdmin->id.'"', $html);
        $this->assertLessThan(strpos($html, $otherAdmin->email), strpos($html, $admin->email));

        $adminSearchHtml = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/admin/employees?s='.urlencode($otherAdmin->email))
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString($otherAdmin->email, $adminSearchHtml);
        $this->assertStringNotContainsString($admin->email, $adminSearchHtml);
    }

    public function test_administrator_edit_form_keeps_role_readonly_and_hides_storage(): void
    {
        $admin = $this->createAdmin();
        Storage::create([
            'user_id' => $admin->id,
            'name' => 'Kho A',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/employees/'.$admin->id.'/edit')
            ->assertOk();

        $response->assertSee('Administrator', false);
        $response->assertSee('data-role-readonly', false);
        $response->assertSee('Toàn hệ thống', false);
        $response->assertDontSee('name="storage_id"', false);
        $response->assertSee('name="status"', false);
        $response->assertDontSee('name="role_id"', false);
    }

    public function test_admin_can_update_only_own_allowed_fields_including_avatar(): void
    {
        $admin = $this->createAdmin();
        Storage::create([
            'user_id' => $admin->id,
            'name' => 'Kho A',
        ]);

        $response = $this->actingAs($admin)->post('/admin/employees/'.$admin->id, [
            '_method' => 'PUT',
            'name' => 'Admin Updated',
            'email' => 'admin-updated@example.com',
            'phone' => '0905555555',
            'password' => 'newsecret',
            'address' => 'Hà Nội',
            'status' => 'active',
            'img_url' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $admin->refresh();

        $this->assertSame('Admin Updated', $admin->name);
        $this->assertSame('admin-updated@example.com', $admin->email);
        $this->assertSame('0905555555', $admin->phone);
        $this->assertTrue(Hash::check('newsecret', $admin->password));
        $this->assertSame(1, (int) $admin->role_id);
        $this->assertNull($admin->manager_id);
        $this->assertNull($admin->storage_id);
        $this->assertSame('active', $admin->status);
        $this->assertNotNull($admin->getRawOriginal('img_url'));
        $this->assertStringStartsWith('avatar/', $admin->getRawOriginal('img_url'));
        StorageFacade::disk('public')->assertExists($admin->getRawOriginal('img_url'));
        StorageFacade::disk('public')->delete($admin->getRawOriginal('img_url'));
    }

    public function test_admin_update_rejects_forged_role_manager_storage_and_status_fields(): void
    {
        $admin = $this->createAdmin();
        $storage = Storage::create([
            'user_id' => $admin->id,
            'name' => 'Kho A',
        ]);

        $response = $this->actingAs($admin)->putJson('/admin/employees/'.$admin->id, [
            'name' => 'Admin Forged',
            'email' => 'admin-forged@example.com',
            'phone' => '0906666666',
            'role_id' => 3,
            'manager_id' => 999,
            'storage_id' => $storage->id,
            'status' => 'inactive',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['role_id', 'manager_id', 'storage_id']);

        $admin->refresh();

        $this->assertSame('Admin', $admin->name);
        $this->assertSame('admin@example.com', $admin->email);
        $this->assertSame('0901111111', $admin->phone);
        $this->assertSame(1, (int) $admin->role_id);
        $this->assertNull($admin->manager_id);
        $this->assertNull($admin->storage_id);
        $this->assertSame('active', $admin->status);
    }

    public function test_administrator_can_edit_role_one_or_two_but_cannot_edit_staff(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = $this->createAdmin('other-admin@example.com', '0907777777');

        $this->actingAs($admin)
            ->get('/admin/employees/'.$otherAdmin->id.'/edit')
            ->assertOk()
            ->assertSee('Administrator');

        $this->actingAs($admin)
            ->putJson('/admin/employees/'.$otherAdmin->id, [
                'name' => 'Other Admin Edited',
                'email' => 'other-admin-edited@example.com',
                'phone' => '0908888888',
                'status' => 'active',
            ])
            ->assertOk();

        $this->assertSame('other-admin-edited@example.com', $otherAdmin->fresh()->email);

        $staff = User::create([
            'name' => 'Staff hidden from Administrator',
            'email' => 'hidden-staff@example.com',
            'phone' => '0908888889',
            'password' => 'password',
            'role_id' => 3,
            'branch_id' => 10,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get('/admin/employees/'.$staff->id.'/edit')
            ->assertNotFound();

        $this->actingAs($admin)
            ->putJson('/admin/employees/'.$staff->id, [
                'name' => 'Tampered Staff',
                'email' => $staff->email,
                'phone' => $staff->phone,
                'status' => 'active',
            ])
            ->assertNotFound();
    }

    public function test_administrator_bulk_deactivates_managed_admin_accounts_but_not_self(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = $this->createAdmin('other-admin@example.com', '0907777777');

        $this->actingAs($admin)->postJson('/admin/bulk/delete', [
            'ids' => [$admin->id],
            'model' => 'User',
        ])->assertUnprocessable();

        $this->actingAs($admin)->postJson('/admin/bulk/delete', [
            'ids' => [$otherAdmin->id],
            'model' => 'User',
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role_id' => 1,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $otherAdmin->id,
            'role_id' => 1,
            'status' => 'inactive',
        ]);
    }

    public function test_bulk_delete_deactivates_managed_staff_without_losing_history(): void
    {
        $admin = User::create([
            'name' => 'Admin Store',
            'email' => 'history-admin-store@example.com',
            'phone' => '0909999998',
            'password' => 'password',
            'role_id' => 2,
            'branch_id' => 10,
            'status' => 'active',
        ]);
        $storage = Storage::create([
            'user_id' => $admin->id,
            'branch_id' => 10,
            'name' => 'Kho A',
        ]);
        $employee = User::create([
            'name' => 'Nhân viên có lịch sử',
            'email' => 'history-staff@example.com',
            'phone' => '0909999999',
            'password' => 'password',
            'role_id' => 3,
            'manager_id' => $admin->id,
            'branch_id' => 10,
            'storage_id' => $storage->id,
            'status' => 'active',
        ]);

        DB::table('orders')->insert([
            'id' => 1,
            'user_id' => $employee->id,
            'notification' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('import_coupon')->insert([
            'id' => 1,
            'user_id' => $employee->id,
            'status' => 'created',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('transactions')->insert([
            'id' => 1,
            'user_id' => $employee->id,
            'created_by' => $employee->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)->postJson('/admin/bulk/delete', [
            'ids' => [$employee->id],
            'model' => 'User',
        ])->assertOk()
            ->assertJsonPath('message', 'Ngừng hoạt động tài khoản thành công!');

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'status' => 'inactive',
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => 1,
            'user_id' => $employee->id,
        ]);
        $this->assertDatabaseHas('import_coupon', [
            'id' => 1,
            'user_id' => $employee->id,
        ]);
        $this->assertDatabaseHas('transactions', [
            'id' => 1,
            'user_id' => $employee->id,
            'created_by' => $employee->id,
        ]);
    }

    public function test_administrator_cannot_bulk_deactivate_staff(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = $this->createAdmin('other-admin@example.com', '0907777777');
        $otherStorage = Storage::create([
            'user_id' => $otherAdmin->id,
            'name' => 'Kho A',
        ]);
        $outsideEmployee = User::create([
            'name' => 'Nhân viên ngoài phạm vi',
            'email' => 'outside-scope@example.com',
            'phone' => '0910000000',
            'password' => 'password',
            'role_id' => 3,
            'manager_id' => $otherAdmin->id,
            'storage_id' => $otherStorage->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)->postJson('/admin/bulk/delete', [
            'ids' => [$outsideEmployee->id],
            'model' => 'User',
        ])->assertForbidden();

        $this->assertSame('active', $outsideEmployee->fresh()->status);
    }

    public function test_non_admin_cannot_bulk_deactivate_users(): void
    {
        $admin = $this->createAdmin();
        $storage = Storage::create([
            'user_id' => $admin->id,
            'name' => 'Kho A',
        ]);
        $staff = User::create([
            'name' => 'Staff actor',
            'email' => 'staff-actor@example.com',
            'phone' => '0911000001',
            'password' => 'password',
            'role_id' => 3,
            'manager_id' => $admin->id,
            'storage_id' => $storage->id,
            'status' => 'active',
        ]);
        $target = User::create([
            'name' => 'Staff target',
            'email' => 'staff-target@example.com',
            'phone' => '0911000002',
            'password' => 'password',
            'role_id' => 3,
            'manager_id' => $admin->id,
            'storage_id' => $storage->id,
            'status' => 'active',
        ]);

        $this->actingAs($staff)->postJson('/admin/bulk/delete', [
            'ids' => [$target->id],
            'model' => 'User',
        ])->assertForbidden();

        $this->assertSame('active', $target->fresh()->status);
    }

    public function test_bulk_action_rejects_unlisted_model_names(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->postJson('/admin/bulk/delete', [
            'ids' => [$admin->id],
            'model' => 'SuperAdmin',
        ])->assertBadRequest();
    }

    public function test_employee_index_uses_management_scope_with_search_and_pagination(): void
    {
        $admin = $this->createAdmin();
        for ($i = 1; $i <= 11; $i++) {
            User::create([
                'name' => sprintf('Managed Admin Store %02d', $i),
                'email' => sprintf('managed-store-%02d@example.com', $i),
                'phone' => sprintf('09100000%02d', $i),
                'password' => 'password',
                'role_id' => 2,
                'branch_id' => null,
                'storage_id' => null,
                'status' => 'active',
            ]);
        }

        $staff = User::create([
            'name' => 'Staff must stay hidden',
            'email' => 'hidden-from-administrator@example.com',
            'phone' => '0911000001',
            'password' => 'password',
            'role_id' => 3,
            'branch_id' => 10,
            'status' => 'active',
        ]);

        $html = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/admin/employees')
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('page=2', $html);
        $this->assertStringNotContainsString($staff->email, $html);

        $managedHtml = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/admin/employees?s=managed-store-11%40example.com')
            ->assertOk()
            ->json('html');
        $this->assertStringContainsString('managed-store-11@example.com', $managedHtml);

        $emptyHtml = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/admin/employees?s=hidden-from-administrator%40example.com')
            ->assertOk()
            ->json('html');
        $this->assertStringContainsString('Không có dữ liệu', $emptyHtml);

        $this->actingAs($admin)
            ->get("/admin/employees/{$staff->id}/edit")
            ->assertNotFound();
    }

    public function test_create_forms_are_role_aware(): void
    {
        $administrator = $this->createAdmin();
        $administratorHtml = $this->actingAs($administrator)
            ->get('/admin/employees/create')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="role_id"', $administratorHtml);
        $this->assertStringContainsString('<option value="1"', $administratorHtml);
        $this->assertStringContainsString('<option value="2"', $administratorHtml);
        $this->assertStringNotContainsString('<option value="3"', $administratorHtml);
        $this->assertStringNotContainsString('name="storage_id"', $administratorHtml);
        $this->assertStringNotContainsString('name="branch_id"', $administratorHtml);

        $adminStore = User::create([
            'name' => 'Store Form',
            'email' => 'store-form@example.com',
            'phone' => '0906900001',
            'password' => 'password',
            'role_id' => 2,
            'branch_id' => 50,
            'status' => 'active',
        ]);
        DB::table('branches')->insert([
            'id' => 50,
            'name' => 'Branch Form',
            'admin_store_user_id' => $adminStore->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Storage::create(['branch_id' => 50, 'name' => 'Storage Form']);

        $adminStoreHtml = $this->actingAs($adminStore)
            ->get('/admin/employees/create')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('name="role_id"', $adminStoreHtml);
        $this->assertStringNotContainsString('name="branch_id"', $adminStoreHtml);
        $this->assertStringContainsString('data-branch-readonly', $adminStoreHtml);
        $this->assertStringContainsString('Branch Form', $adminStoreHtml);
        $this->assertStringContainsString('name="storage_id"', $adminStoreHtml);
    }

    public function test_administrator_creates_only_role_one_or_two_unassigned(): void
    {
        Mail::fake();
        $administrator = $this->createAdmin();

        foreach ([1, 2] as $roleId) {
            $email = "created-role-{$roleId}@example.com";
            $this->actingAs($administrator)->postJson('/admin/employees', [
                'name' => "Created role {$roleId}",
                'email' => $email,
                'phone' => "090680000{$roleId}",
                'password' => 'secret123',
                'role_id' => $roleId,
                'address' => 'Hà Nội',
                'status' => 'active',
            ])->assertCreated();

            $this->assertDatabaseHas('users', [
                'email' => $email,
                'role_id' => $roleId,
                'manager_id' => null,
                'branch_id' => null,
                'storage_id' => null,
            ]);
        }

        foreach ([3, 999] as $roleId) {
            $this->actingAs($administrator)->postJson('/admin/employees', [
                'name' => 'Invalid role',
                'email' => "invalid-role-{$roleId}@example.com",
                'phone' => "0906799{$roleId}",
                'password' => 'secret123',
                'role_id' => $roleId,
                'status' => 'active',
            ])->assertUnprocessable()->assertJsonValidationErrors('role_id');
        }
    }

    public function test_admin_store_creates_staff_in_own_branch_and_rejects_forged_branch(): void
    {
        Mail::fake();
        $adminStore = User::create([
            'name' => 'Store A',
            'email' => 'store-a@example.com',
            'phone' => '0907000001',
            'password' => 'password',
            'role_id' => 2,
            'branch_id' => 101,
            'status' => 'active',
        ]);
        $storage = Storage::create([
            'user_id' => $adminStore->id,
            'branch_id' => 101,
            'name' => 'Kho A',
        ]);

        $payload = [
            'name' => 'Staff A',
            'email' => 'phase3-staff-a@example.com',
            'phone' => '0907000002',
            'password' => 'secret123',
            'storage_id' => $storage->id,
            'status' => 'active',
            'role_id' => 1,
        ];

        $this->actingAs($adminStore)->postJson('/admin/employees', $payload)
            ->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'phase3-staff-a@example.com',
            'role_id' => 3,
            'manager_id' => $adminStore->id,
            'branch_id' => 101,
            'storage_id' => $storage->id,
        ]);

        $this->actingAs($adminStore)->postJson('/admin/employees', array_merge($payload, [
            'email' => 'forced-staff-role-two@example.com',
            'phone' => '0907000004',
            'role_id' => 2,
        ]))->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'forced-staff-role-two@example.com',
            'role_id' => 3,
            'branch_id' => 101,
            'storage_id' => $storage->id,
        ]);

        $this->actingAs($adminStore)->postJson('/admin/employees', array_merge($payload, [
            'email' => 'forged-branch@example.com',
            'phone' => '0907000003',
            'branch_id' => 202,
        ]))->assertUnprocessable()->assertJsonValidationErrors('branch_id');
    }

    public function test_admin_store_cannot_view_update_or_bulk_deactivate_staff_from_another_branch(): void
    {
        $adminStore = User::create([
            'name' => 'Store A',
            'email' => 'scope-store-a@example.com',
            'phone' => '0907100001',
            'password' => 'password',
            'role_id' => 2,
            'branch_id' => 11,
            'status' => 'active',
        ]);
        $storageA = Storage::create(['branch_id' => 11, 'name' => 'Kho A']);
        $storageB = Storage::create(['branch_id' => 22, 'name' => 'Kho B']);
        $staffB = User::create([
            'name' => 'Staff B',
            'email' => 'scope-staff-b@example.com',
            'phone' => '0907100002',
            'password' => 'password',
            'role_id' => 3,
            'branch_id' => 22,
            'storage_id' => $storageB->id,
            'status' => 'active',
        ]);

        $this->actingAs($adminStore)
            ->get("/admin/employees/{$staffB->id}/edit")
            ->assertNotFound();

        $this->actingAs($adminStore)->putJson("/admin/employees/{$staffB->id}", [
            'name' => 'Tampered',
            'email' => $staffB->email,
            'phone' => $staffB->phone,
            'storage_id' => $storageA->id,
            'status' => 'active',
        ])->assertNotFound();

        $this->actingAs($adminStore)->postJson('/admin/bulk/delete', [
            'ids' => [$staffB->id],
            'model' => 'User',
        ])->assertForbidden();

        $this->assertSame('active', $staffB->fresh()->status);
        $this->assertSame(22, (int) $staffB->fresh()->branch_id);
    }

    public function test_admin_store_cannot_promote_staff_or_move_staff_to_another_branch_storage(): void
    {
        $adminStore = User::create([
            'name' => 'Store A',
            'email' => 'guard-store-a@example.com',
            'phone' => '0907200001',
            'password' => 'password',
            'role_id' => 2,
            'branch_id' => 31,
            'status' => 'active',
        ]);
        $storageA = Storage::create(['branch_id' => 31, 'name' => 'Kho A']);
        $storageB = Storage::create(['branch_id' => 32, 'name' => 'Kho B']);
        $staff = User::create([
            'name' => 'Staff A',
            'email' => 'guard-staff-a@example.com',
            'phone' => '0907200002',
            'password' => 'password',
            'role_id' => 3,
            'branch_id' => 31,
            'storage_id' => $storageA->id,
            'status' => 'active',
        ]);
        $payload = [
            'name' => $staff->name,
            'email' => $staff->email,
            'phone' => $staff->phone,
            'storage_id' => $storageA->id,
            'status' => 'active',
        ];

        $this->actingAs($adminStore)->putJson("/admin/employees/{$staff->id}", array_merge($payload, [
            'role_id' => 1,
        ]))->assertUnprocessable()->assertJsonValidationErrors('role_id');

        $this->actingAs($adminStore)->putJson("/admin/employees/{$staff->id}", array_merge($payload, [
            'storage_id' => $storageB->id,
        ]))->assertUnprocessable()->assertJsonValidationErrors('storage_id');

        $this->assertSame(3, (int) $staff->fresh()->role_id);
        $this->assertSame($storageA->id, (int) $staff->fresh()->storage_id);
    }

    public function test_staff_cannot_access_employee_management_endpoints(): void
    {
        $staff = User::create([
            'name' => 'Staff actor',
            'email' => 'forbidden-staff-actor@example.com',
            'phone' => '0907250001',
            'password' => 'password',
            'role_id' => 3,
            'branch_id' => 31,
            'status' => 'active',
        ]);
        $target = User::create([
            'name' => 'Staff target',
            'email' => 'forbidden-staff-target@example.com',
            'phone' => '0907250002',
            'password' => 'password',
            'role_id' => 3,
            'branch_id' => 31,
            'status' => 'active',
        ]);

        $this->actingAs($staff)->get('/admin/employees')->assertForbidden();
        $this->actingAs($staff)->get('/admin/employees/create')->assertForbidden();
        $this->actingAs($staff)->postJson('/admin/employees', [])->assertForbidden();
        $this->actingAs($staff)->get("/admin/employees/{$target->id}/edit")->assertForbidden();
        $this->actingAs($staff)->putJson("/admin/employees/{$target->id}", [])->assertForbidden();
        $this->actingAs($staff)->deleteJson("/admin/employees/{$target->id}")->assertForbidden();
    }

    public function test_admin_store_employee_list_is_branch_scoped_and_null_branch_fails_closed(): void
    {
        $adminStore = User::create([
            'name' => 'Store A',
            'email' => 'list-store-a@example.com',
            'phone' => '0907300001',
            'password' => 'password',
            'role_id' => 2,
            'branch_id' => 41,
            'status' => 'active',
        ]);
        User::create([
            'name' => 'Visible Staff A',
            'email' => 'visible-a@example.com',
            'phone' => '0907300002',
            'password' => 'password',
            'role_id' => 3,
            'branch_id' => 41,
            'status' => 'active',
        ]);
        User::create([
            'name' => 'Hidden Staff B',
            'email' => 'hidden-b@example.com',
            'phone' => '0907300003',
            'password' => 'password',
            'role_id' => 3,
            'branch_id' => 42,
            'status' => 'active',
        ]);
        User::create([
            'name' => 'Legacy Staff',
            'email' => 'legacy-staff@example.com',
            'phone' => '0907300004',
            'password' => 'password',
            'role_id' => 3,
            'branch_id' => null,
            'status' => 'active',
        ]);

        $html = $this->actingAs($adminStore)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/admin/employees')
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('Visible Staff A', $html);
        $this->assertStringNotContainsString('Hidden Staff B', $html);
        $this->assertStringNotContainsString('Legacy Staff', $html);

        $adminStore->update(['branch_id' => null]);

        $this->actingAs($adminStore)
            ->get('/admin/employees')
            ->assertForbidden();

        $this->actingAs($adminStore)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/admin/employees')
            ->assertForbidden();

        $this->actingAs($adminStore)->postJson('/admin/employees', [
            'name' => 'Blocked Staff',
            'email' => 'blocked-null-branch@example.com',
            'phone' => '0907300005',
            'password' => 'secret123',
            'storage_id' => 1,
            'status' => 'active',
        ])->assertForbidden();
    }

    public function test_employee_status_endpoint_applies_explicit_transitions_and_preserves_assignment(): void
    {
        $adminStore = User::create([
            'name' => 'Status manager',
            'email' => 'status-manager@example.com',
            'phone' => '0908100001',
            'password' => 'password',
            'role_id' => 2,
            'branch_id' => 81,
            'status' => 'active',
        ]);
        $storage = Storage::create(['branch_id' => 81, 'name' => 'Kho status']);
        $employees = collect(['active', 'inactive', 'locked'])->mapWithKeys(function (string $status, int $index) use ($adminStore, $storage) {
            $employee = User::create([
                'name' => 'Status '.$status,
                'email' => 'status-'.$status.'@example.com',
                'phone' => '090810000'.($index + 2),
                'password' => 'password',
                'role_id' => 3,
                'manager_id' => $adminStore->id,
                'branch_id' => 81,
                'storage_id' => $storage->id,
                'status' => $status,
            ]);

            return [$status => $employee];
        });

        $this->actingAs($adminStore)
            ->patchJson('/admin/employees/'.$employees['active']->id.'/status', ['status' => 'inactive'])
            ->assertOk()
            ->assertJsonPath('message', 'Ngừng hoạt động tài khoản thành công.');
        $this->actingAs($adminStore)
            ->patchJson('/admin/employees/'.$employees['inactive']->id.'/status', ['status' => 'active'])
            ->assertOk()
            ->assertJsonPath('message', 'Kích hoạt lại tài khoản thành công.');
        $this->actingAs($adminStore)
            ->patchJson('/admin/employees/'.$employees['locked']->id.'/status', ['status' => 'active'])
            ->assertOk()
            ->assertJsonPath('message', 'Mở khóa tài khoản thành công.');

        $this->assertSame('inactive', $employees['active']->fresh()->status);
        $this->assertSame('active', $employees['inactive']->fresh()->status);
        $this->assertSame('active', $employees['locked']->fresh()->status);

        foreach ($employees as $employee) {
            $employee->refresh();
            $this->assertSame(81, (int) $employee->branch_id);
            $this->assertSame($storage->id, (int) $employee->storage_id);
            $this->assertSame($adminStore->id, (int) $employee->manager_id);
        }
    }

    public function test_employee_status_ui_matches_all_three_badges_and_renders_one_status_action_each(): void
    {
        $adminStore = User::create([
            'name' => 'UI manager',
            'email' => 'ui-manager@example.com',
            'phone' => '0908200001',
            'password' => 'password',
            'role_id' => 2,
            'branch_id' => 82,
            'status' => 'active',
        ]);

        foreach (['active', 'inactive', 'locked'] as $index => $status) {
            User::create([
                'name' => 'UI '.$status,
                'email' => 'ui-'.$status.'@example.com',
                'phone' => '090820000'.($index + 2),
                'password' => 'password',
                'role_id' => 3,
                'branch_id' => 82,
                'status' => $status,
            ]);
        }

        $html = $this->actingAs($adminStore)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/admin/employees')
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('<span class="badge bg-success">Kích hoạt</span>', $html);
        $this->assertStringContainsString('<span class="badge bg-secondary">Không kích hoạt</span>', $html);
        $this->assertStringContainsString('<span class="badge bg-danger">Bị khóa</span>', $html);
        $this->assertSame(3, substr_count($html, 'btn-employee-status'));
        $this->assertSame(1, substr_count($html, 'data-target-status="inactive"'));
        $this->assertSame(2, substr_count($html, 'data-target-status="active"'));
        $this->assertStringContainsString('btn-warning btn-sm btn-employee-status', $html);
        $this->assertStringContainsString('fa-user-slash', $html);
        $this->assertStringContainsString('title="Ngừng hoạt động"', $html);
        $this->assertStringContainsString('btn-success btn-sm btn-employee-status', $html);
        $this->assertStringContainsString('fa-user-check', $html);
        $this->assertStringContainsString('title="Kích hoạt"', $html);
        $this->assertStringContainsString('btn-info btn-sm btn-employee-status', $html);
        $this->assertStringContainsString('fa-unlock', $html);
        $this->assertStringContainsString('title="Mở khóa"', $html);
        $this->assertStringContainsString('Bạn có chắc muốn ngừng hoạt động tài khoản này?', $html);
        $this->assertStringContainsString('Bạn có chắc muốn kích hoạt lại tài khoản này?', $html);
        $this->assertStringContainsString('Bạn có chắc muốn mở khóa tài khoản này?', $html);

        $script = file_get_contents(resource_path('views/admin/employee/index.blade.php'));
        $this->assertStringContainsString("method: 'PATCH'", $script);
        $this->assertStringContainsString('xhr.responseJSON?.message', $script);
        $this->assertStringContainsString('complete: () =>', $script);
        $this->assertStringContainsString(".prop('disabled', false)", $script);
    }

    public function test_employee_status_endpoint_enforces_scope_self_protection_and_transition_validation(): void
    {
        $administrator = $this->createAdmin('scope-status-admin@example.com', '0908300001');
        $adminStore = User::create([
            'name' => 'Scope store',
            'email' => 'scope-status-store@example.com',
            'phone' => '0908300002',
            'password' => 'password',
            'role_id' => 2,
            'branch_id' => 83,
            'status' => 'active',
        ]);
        $sameBranchStaff = User::create([
            'name' => 'Same branch staff',
            'email' => 'same-status-staff@example.com',
            'phone' => '0908300003',
            'password' => 'password',
            'role_id' => 3,
            'branch_id' => 83,
            'status' => 'active',
        ]);
        $otherBranchStaff = User::create([
            'name' => 'Other branch staff',
            'email' => 'other-status-staff@example.com',
            'phone' => '0908300004',
            'password' => 'password',
            'role_id' => 3,
            'branch_id' => 84,
            'status' => 'active',
        ]);

        $this->actingAs($administrator)
            ->patchJson('/admin/employees/'.$sameBranchStaff->id.'/status', ['status' => 'inactive'])
            ->assertNotFound();
        $this->actingAs($adminStore)
            ->patchJson('/admin/employees/'.$otherBranchStaff->id.'/status', ['status' => 'inactive'])
            ->assertNotFound();
        $this->actingAs($administrator)
            ->patchJson('/admin/employees/'.$administrator->id.'/status', ['status' => 'inactive'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
        $this->actingAs($administrator)
            ->putJson('/admin/employees/'.$administrator->id, [
                'name' => $administrator->name,
                'email' => $administrator->email,
                'phone' => $administrator->phone,
                'status' => 'inactive',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
        $this->actingAs($adminStore)
            ->patchJson('/admin/employees/'.$sameBranchStaff->id.'/status', ['status' => 'active'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
        $this->actingAs($adminStore)
            ->patchJson('/admin/employees/'.$sameBranchStaff->id.'/status', ['status' => 'locked'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
        $this->actingAs($adminStore)
            ->patchJson('/admin/employees/'.$sameBranchStaff->id.'/status', ['status' => 'deleted'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
        $this->actingAs($sameBranchStaff)
            ->patchJson('/admin/employees/'.$otherBranchStaff->id.'/status', ['status' => 'inactive'])
            ->assertForbidden();

        $this->assertSame('active', $sameBranchStaff->fresh()->status);
        $this->assertSame('active', $otherBranchStaff->fresh()->status);
        $this->assertSame('active', $administrator->fresh()->status);
    }

    public function test_single_and_bulk_status_actions_keep_one_active_administrator(): void
    {
        $this->withoutMiddleware(EnsureAccountIsActive::class);

        $inactiveAdministrator = $this->createAdmin('inactive-status-admin@example.com', '0908400001');
        $inactiveAdministrator->update(['status' => 'inactive']);
        $lastActiveAdministrator = $this->createAdmin('last-active-status-admin@example.com', '0908400002');
        $adminStore = User::create([
            'name' => 'Atomic bulk Admin Store',
            'email' => 'atomic-bulk-admin-store@example.com',
            'phone' => '0908400003',
            'password' => 'password',
            'role_id' => 2,
            'status' => 'active',
        ]);

        $this->actingAs($inactiveAdministrator)
            ->patchJson('/admin/employees/'.$lastActiveAdministrator->id.'/status', ['status' => 'inactive'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->actingAs($inactiveAdministrator)
            ->putJson('/admin/employees/'.$lastActiveAdministrator->id, [
                'name' => $lastActiveAdministrator->name,
                'email' => $lastActiveAdministrator->email,
                'phone' => $lastActiveAdministrator->phone,
                'status' => 'inactive',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->actingAs($inactiveAdministrator)
            ->postJson('/admin/bulk/delete', [
                'ids' => [$lastActiveAdministrator->id, $adminStore->id],
                'model' => 'User',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Không thể ngừng hoạt động Administrator cuối cùng của hệ thống.');

        $this->assertSame('active', $lastActiveAdministrator->fresh()->status);
        $this->assertSame('active', $adminStore->fresh()->status);
    }

    private function createSchema(): void
    {
        Schema::dropAllTables();
        $this->createAuthorizationTablesForTests();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('img_url')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->string('address')->nullable();
            $table->unsignedBigInteger('storage_id')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('role_id')->default(3);
            $table->unsignedBigInteger('branch_id')->nullable()->default(1);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('admin_store_user_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('storages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable()->default(1);
            $table->string('name');
            $table->string('location')->nullable();
            $table->timestamps();
        });

        Schema::create('config', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('receiver')->nullable();
            $table->string('logo')->nullable();
            $table->string('qr')->nullable();
            $table->timestamps();
        });

        Schema::create('user_info', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('img_url')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('notification')->default(false);
            $table->timestamps();
        });

        Schema::create('import_coupon', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    private function createAdmin(string $email = 'admin@example.com', string $phone = '0901111111'): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => $email,
            'phone' => $phone,
            'password' => 'password',
            'role_id' => 1,
            'status' => 'active',
        ]);
    }

    private function fakeWebp(string $name, int $kilobytes): UploadedFile
    {
        $image = imagecreatetruecolor(2, 2);
        ob_start();
        imagewebp($image);
        $contents = ob_get_clean();
        imagedestroy($image);

        return UploadedFile::fake()
            ->createWithContent($name, $contents)
            ->size($kilobytes);
    }
}
