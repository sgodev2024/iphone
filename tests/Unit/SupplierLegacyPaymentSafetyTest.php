<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\BankTransactionController;
use App\Http\Controllers\Admin\CashTransactionController;
use App\Http\Controllers\Admin\ExpenseController;
use Illuminate\Http\Request;
use ReflectionClass;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SupplierLegacyPaymentSafetyTest extends TestCase
{
    public function test_expense_supplier_payment_route_is_gone_without_mutating_legacy_history(): void
    {
        $this->assertLegacyRouteIsGone(ExpenseController::class, 'addSubmit');
    }

    public function test_cash_supplier_payment_route_is_gone_until_canonical_payment_service_exists(): void
    {
        $this->assertLegacyRouteIsGone(CashTransactionController::class, 'store');
    }

    public function test_bank_supplier_payment_route_is_gone_until_canonical_payment_service_exists(): void
    {
        $this->assertLegacyRouteIsGone(BankTransactionController::class, 'store');
    }

    private function assertLegacyRouteIsGone(string $controllerClass, string $methodName): void
    {
        $reflection = new ReflectionClass($controllerClass);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        try {
            $method->invoke($controller, Request::create('/legacy-supplier-payment', 'POST', [
                'obj_type' => 'supplier',
            ]));
            $this->fail('The legacy supplier payment route must be disabled.');
        } catch (HttpException $exception) {
            $this->assertSame(410, $exception->getStatusCode());
        }
    }
}
