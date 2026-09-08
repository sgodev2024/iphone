<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SafeEmployeeDeletionTest extends TestCase
{
    private int $userSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createAuthorizationTablesForTests();
        $this->createSchema();
    }

    /**
     * @dataProvider deletableStatuses
     */
    public function test_administrator_can_hard_delete_unassigned_admin_store_regardless_of_status(string $status): void
    {
        $admin = $this->createUser(1);
        $adminStore = $this->createUser(2, branchId: null, status: $status);

        $this->actingAs($admin)
            ->deleteJson(route('admin.employees.destroy', $adminStore))
            ->assertOk()
            ->assertJsonPath('message', 'Xóa tài khoản thành công.');

        $this->assertDatabaseMissing('users', ['id' => $adminStore->id]);
    }

    public static function deletableStatuses(): array
    {
        return [
            'active' => ['active'],
            'inactive' => ['inactive'],
            'locked' => ['locked'],
        ];
    }

    public function test_admin_store_can_delete_only_empty_staff_from_its_branch(): void
    {
        $adminStore = $this->createUser(2, branchId: 10);
        $sameBranch = $this->createUser(3, branchId: 10);
        $otherBranch = $this->createUser(3, branchId: 20);
        $legacyWithoutBranch = $this->createUser(3, branchId: null);

        $this->actingAs($adminStore)
            ->deleteJson(route('admin.employees.destroy', $sameBranch))
            ->assertOk();

        $this->actingAs($adminStore)
            ->deleteJson(route('admin.employees.destroy', $otherBranch))
            ->assertNotFound();

        $this->actingAs($adminStore)
            ->deleteJson(route('admin.employees.destroy', $legacyWithoutBranch))
            ->assertNotFound();

        $this->assertDatabaseMissing('users', ['id' => $sameBranch->id]);
        $this->assertDatabaseHas('users', ['id' => $otherBranch->id]);
        $this->assertDatabaseHas('users', ['id' => $legacyWithoutBranch->id]);
    }

    public function test_admin_store_without_branch_and_non_manager_roles_are_denied(): void
    {
        $adminStoreWithoutBranch = $this->createUser(2, branchId: null);
        $staffActor = $this->createUser(3, branchId: 10);
        $warehouseActor = $this->createUser(4, branchId: 10);
        $target = $this->createUser(3, branchId: 10);

        $this->actingAs($adminStoreWithoutBranch)
            ->deleteJson(route('admin.employees.destroy', $target))
            ->assertForbidden();

        $this->actingAs($staffActor)
            ->deleteJson(route('admin.employees.destroy', $target))
            ->assertForbidden();

        $this->actingAs($warehouseActor)
            ->deleteJson(route('admin.employees.destroy', $target))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_administrator_cannot_self_delete_or_target_roles_outside_one_and_two(): void
    {
        $admin = $this->createUser(1);
        $otherAdmin = $this->createUser(1);
        $adminStore = $this->createUser(2, branchId: null);
        $staff = $this->createUser(3, branchId: 10);
        $warehouse = $this->createUser(4, branchId: 10);

        $this->actingAs($admin)
            ->deleteJson(route('admin.employees.destroy', $admin))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['employee']);

        $this->actingAs($admin)
            ->deleteJson(route('admin.employees.destroy', $otherAdmin))
            ->assertOk();

        $this->actingAs($admin)
            ->deleteJson(route('admin.employees.destroy', $adminStore))
            ->assertOk();

        $this->actingAs($admin)
            ->deleteJson(route('admin.employees.destroy', $staff))
            ->assertNotFound();

        $this->actingAs($admin)
            ->deleteJson(route('admin.employees.destroy', $warehouse))
            ->assertNotFound();

        $this->assertDatabaseMissing('users', ['id' => $otherAdmin->id]);
        $this->assertDatabaseMissing('users', ['id' => $adminStore->id]);
        $this->assertDatabaseHas('users', ['id' => $staff->id]);
        $this->assertDatabaseHas('users', ['id' => $warehouse->id]);
    }

    public function test_assigned_admin_store_is_blocked_with_specific_message(): void
    {
        $administrator = $this->createUser(1);
        $adminStore = $this->createUser(2, branchId: 10);
        DB::table('branches')->insert([
            'user_id' => $administrator->id,
            'admin_store_user_id' => $adminStore->id,
        ]);

        $this->actingAs($administrator)
            ->deleteJson(route('admin.employees.destroy', $adminStore))
            ->assertConflict()
            ->assertJsonPath(
                'message',
                'Không thể xóa Admin Store vì tài khoản đang được gán cho một chi nhánh.'
            );

        $this->assertDatabaseHas('users', ['id' => $adminStore->id]);
    }

    public function test_last_administrator_cannot_be_deleted(): void
    {
        $administrator = $this->createUser(1);

        $this->actingAs($administrator)
            ->deleteJson(route('admin.employees.destroy', $administrator))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('employee')
            ->assertJsonPath(
                'errors.employee.0',
                'Không thể xóa Administrator cuối cùng của hệ thống.'
            );

        $this->assertDatabaseHas('users', ['id' => $administrator->id]);
    }

    public function test_missing_employee_returns_not_found(): void
    {
        $admin = $this->createUser(1);

        $this->actingAs($admin)
            ->deleteJson('/admin/employees/999999')
            ->assertNotFound();
    }

    /**
     * @dataProvider businessReferences
     */
    public function test_every_audited_business_reference_blocks_delete(
        string $table,
        string $column,
        string $message
    ): void {
        $admin = $this->createUser(2, branchId: 10);
        $staff = $this->createUser(3, branchId: 10);
        $referenceId = DB::table($table)->insertGetId([$column => $staff->id]);

        $this->actingAs($admin)
            ->deleteJson(route('admin.employees.destroy', $staff))
            ->assertConflict()
            ->assertJsonPath('message', $message);

        $this->assertDatabaseHas('users', ['id' => $staff->id]);
        $this->assertDatabaseHas($table, ['id' => $referenceId, $column => $staff->id]);
    }

    public static function businessReferences(): array
    {
        return [
            'account creator' => ['accounts', 'created_by', 'Không thể xóa nhân viên vì đã phát sinh dữ liệu kế toán.'],
            'bank voucher owner' => ['bank_vouchers', 'owner_id', 'Không thể xóa nhân viên vì đã phát sinh phiếu thu/chi ngân hàng.'],
            'bank voucher creator' => ['bank_vouchers', 'created_by', 'Không thể xóa nhân viên vì đã phát sinh phiếu thu/chi ngân hàng.'],
            'branch owner' => ['branches', 'user_id', 'Không thể xóa nhân viên vì đang được liên kết với chi nhánh.'],
            'branch admin store' => ['branches', 'admin_store_user_id', 'Không thể xóa nhân viên vì đang được liên kết với chi nhánh.'],
            'cash voucher owner' => ['cash_vouchers', 'owner_id', 'Không thể xóa nhân viên vì đã phát sinh phiếu thu/chi tiền mặt.'],
            'cash voucher creator' => ['cash_vouchers', 'created_by', 'Không thể xóa nhân viên vì đã phát sinh phiếu thu/chi tiền mặt.'],
            'inventory check' => ['check_inventory', 'user_id', 'Không thể xóa nhân viên vì đã phát sinh dữ liệu kiểm kho.'],
            'client owner' => ['clients', 'user_id', 'Không thể xóa nhân viên vì đã phát sinh dữ liệu khách hàng.'],
            'company owner' => ['companies', 'user_id', 'Không thể xóa nhân viên vì đã phát sinh dữ liệu nhà cung cấp.'],
            'customer collection owner' => ['customer_debt_collections', 'owner_id', 'Không thể xóa nhân viên vì đã phát sinh thu công nợ khách hàng.'],
            'customer collection creator' => ['customer_debt_collections', 'created_by', 'Không thể xóa nhân viên vì đã phát sinh thu công nợ khách hàng.'],
            'customer debt state' => ['customer_debt_snapshot_states', 'owner_id', 'Không thể xóa nhân viên vì đã phát sinh dữ liệu công nợ khách hàng.'],
            'customer debt snapshot' => ['customer_debt_yearly_snapshots', 'owner_id', 'Không thể xóa nhân viên vì đã phát sinh dữ liệu công nợ khách hàng.'],
            'import coupon' => ['import_coupon', 'user_id', 'Không thể xóa nhân viên vì đã phát sinh phiếu nhập hàng.'],
            'order return owner' => ['order_returns', 'user_id', 'Không thể xóa nhân viên vì đã phát sinh dữ liệu trả hàng.'],
            'order return creator' => ['order_returns', 'created_by', 'Không thể xóa nhân viên vì đã phát sinh dữ liệu trả hàng.'],
            'order owner' => ['orders', 'user_id', 'Không thể xóa nhân viên vì đã phát sinh đơn hàng.'],
            'order creator without fk' => ['orders', 'created_by', 'Không thể xóa nhân viên vì đã phát sinh đơn hàng.'],
            'imei delete audit' => ['product_imeis', 'deleted_by', 'Không thể xóa nhân viên vì đã phát sinh lịch sử IMEI.'],
            'product owner' => ['products', 'user_id', 'Không thể xóa nhân viên vì đã phát sinh dữ liệu sản phẩm.'],
            'campaign detail' => ['sgo_campaign_details', 'user_id', 'Không thể xóa nhân viên vì đã phát sinh dữ liệu chiến dịch.'],
            'sgo transaction' => ['sgo_transactions', 'user_id', 'Không thể xóa nhân viên vì đã phát sinh giao dịch tài chính.'],
            'storage owner' => ['storages', 'user_id', 'Không thể xóa nhân viên vì đang được liên kết với kho hàng.'],
            'supplier debt state' => ['supplier_debt_snapshot_states', 'owner_id', 'Không thể xóa nhân viên vì đã phát sinh dữ liệu công nợ nhà cung cấp.'],
            'supplier debt snapshot' => ['supplier_debt_yearly_snapshots', 'owner_id', 'Không thể xóa nhân viên vì đã phát sinh dữ liệu công nợ nhà cung cấp.'],
            'transaction owner' => ['transactions', 'user_id', 'Không thể xóa nhân viên vì đã phát sinh giao dịch tài chính.'],
            'transaction creator' => ['transactions', 'created_by', 'Không thể xóa nhân viên vì đã phát sinh giao dịch tài chính.'],
            'user wallet' => ['user_wallet', 'user_id', 'Không thể xóa nhân viên vì đã phát sinh dữ liệu ví hoặc tài chính.'],
            'warehouse without fk' => ['warehouse', 'user_id', 'Không thể xóa nhân viên vì đã phát sinh dữ liệu kho.'],
        ];
    }

    public function test_technical_data_is_deleted_and_manager_assignment_is_detached_atomically(): void
    {
        $admin = $this->createUser(2, branchId: 10);
        $staff = $this->createUser(3, branchId: 10);
        $managedUser = $this->createUser(3, branchId: 10, managerId: $staff->id);

        DB::table('carts')->insert(['user_id' => $staff->id]);
        DB::table('config')->insert(['user_id' => $staff->id]);
        DB::table('user_info')->insert(['user_id' => $staff->id]);
        DB::table('personal_access_tokens')->insert([
            'tokenable_id' => $staff->id,
            'tokenable_type' => User::class,
        ]);
        DB::table('notifications')->insert([
            'notifiable_id' => $staff->id,
            'notifiable_type' => User::class,
        ]);
        DB::table('sessions')->insert(['user_id' => $staff->id]);

        $this->actingAs($admin)
            ->deleteJson(route('admin.employees.destroy', $staff))
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $staff->id]);
        $this->assertDatabaseHas('users', ['id' => $managedUser->id, 'manager_id' => null]);

        foreach (['carts', 'config', 'user_info', 'sessions'] as $table) {
            $this->assertDatabaseMissing($table, ['user_id' => $staff->id]);
        }

        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $staff->id]);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $staff->id]);
    }

    public function test_unexpected_database_rejection_returns_safe_message_and_rolls_back_cleanup(): void
    {
        $admin = $this->createUser(2, branchId: 10);
        $staff = $this->createUser(3, branchId: 10);

        DB::table('carts')->insert(['user_id' => $staff->id]);
        DB::table('unexpected_employee_references')->insert(['user_id' => $staff->id]);

        $this->actingAs($admin)
            ->deleteJson(route('admin.employees.destroy', $staff))
            ->assertInternalServerError()
            ->assertJsonPath(
                'message',
                'Không thể xóa nhân viên. Vui lòng kiểm tra dữ liệu liên quan.'
            )
            ->assertJsonMissingPath('exception');

        $this->assertDatabaseHas('users', ['id' => $staff->id]);
        $this->assertDatabaseHas('carts', ['user_id' => $staff->id]);
        $this->assertDatabaseHas('unexpected_employee_references', ['user_id' => $staff->id]);
    }

    public function test_employee_ui_renders_hard_delete_only_for_accounts_in_actor_scope_and_restores_button_state(): void
    {
        $admin = $this->createUser(2, branchId: 10);
        $staff = $this->createUser(3, branchId: 10);

        $html = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/admin/employees')
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('btn-delete-employee', $html);
        $this->assertStringContainsString(route('admin.employees.destroy', $staff), $html);
        $this->assertStringNotContainsString(route('admin.employees.destroy', $admin), $html);
        $this->assertStringContainsString('table-responsive', $html);
        $this->assertStringContainsString('min-width: 1550px', $html);
        $this->assertStringContainsString('d-flex flex-nowrap justify-content-center gap-1', $html);

        $script = file_get_contents(resource_path('views/admin/employee/index.blade.php'));

        $this->assertStringContainsString('Bạn có chắc muốn xóa tài khoản này?', $script);
        $this->assertStringContainsString('Chỉ có thể xóa nếu tài khoản chưa được gán chi nhánh và chưa phát sinh nghiệp vụ trong hệ thống.', $script);
        $this->assertStringContainsString("confirmButtonText: 'Xóa tài khoản'", $script);
        $this->assertStringContainsString("cancelButtonText: 'Hủy'", $script);
        $this->assertStringContainsString('if (!result.isConfirmed)', $script);
        $this->assertStringContainsString(".prop('disabled', false)", $script);
        $this->assertStringContainsString(".css('opacity', originalOpacity)", $script);
        $this->assertStringContainsString('.html(originalHtml)', $script);
        $this->assertStringContainsString('complete: () =>', $script);
        $this->assertStringContainsString(
            'Không thể xóa tài khoản. Vui lòng kiểm tra dữ liệu liên quan.',
            $script
        );
    }

    public function test_generic_bulk_action_remains_deactivation_not_hard_delete(): void
    {
        $admin = $this->createUser(2, branchId: 10);
        $staff = $this->createUser(3, branchId: 10);

        $this->actingAs($admin)
            ->postJson('/admin/bulk/delete', [
                'ids' => [$staff->id],
                'model' => 'User',
            ])
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'status' => 'inactive',
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('storage_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('storages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name')->nullable();
        });

        $businessTables = [
            'accounts' => ['created_by'],
            'bank_vouchers' => ['owner_id', 'created_by'],
            'branches' => ['user_id', 'admin_store_user_id'],
            'cash_vouchers' => ['owner_id', 'created_by'],
            'check_inventory' => ['user_id'],
            'clients' => ['user_id'],
            'companies' => ['user_id'],
            'customer_debt_collections' => ['owner_id', 'created_by'],
            'customer_debt_snapshot_states' => ['owner_id'],
            'customer_debt_yearly_snapshots' => ['owner_id'],
            'import_coupon' => ['user_id'],
            'order_returns' => ['user_id', 'created_by'],
            'orders' => ['user_id', 'created_by'],
            'product_imeis' => ['deleted_by'],
            'products' => ['user_id'],
            'sgo_campaign_details' => ['user_id'],
            'sgo_transactions' => ['user_id'],
            'supplier_debt_snapshot_states' => ['owner_id'],
            'supplier_debt_yearly_snapshots' => ['owner_id'],
            'transactions' => ['user_id', 'created_by'],
            'user_wallet' => ['user_id'],
            'warehouse' => ['user_id'],
        ];

        foreach ($businessTables as $tableName => $columns) {
            Schema::create($tableName, function (Blueprint $table) use ($columns): void {
                $table->id();

                foreach ($columns as $column) {
                    $table->unsignedBigInteger($column)->nullable();
                }
            });
        }

        foreach (['carts', 'config', 'user_info'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id');
            });
        }

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tokenable_id');
            $table->string('tokenable_type');
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('notifiable_id');
            $table->string('notifiable_type');
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
        });

        Schema::create('unexpected_employee_references', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();
        });
    }

    private function createUser(
        int $roleId,
        ?int $branchId = null,
        string $status = 'active',
        ?int $managerId = null
    ): User {
        $this->userSequence++;

        return User::create([
            'name' => "User {$this->userSequence}",
            'email' => "user{$this->userSequence}@example.com",
            'phone' => '090'.str_pad((string) $this->userSequence, 7, '0', STR_PAD_LEFT),
            'password' => 'password',
            'role_id' => $roleId,
            'branch_id' => $branchId,
            'manager_id' => $managerId,
            'status' => $status,
        ]);
    }
}
