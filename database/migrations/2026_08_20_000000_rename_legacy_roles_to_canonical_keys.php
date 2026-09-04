<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $this->renameRole(1, 'store', 'administrator');
            $this->renameRole(2, 'admin', 'admin_store');
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $this->renameRole(1, 'administrator', 'store');
            $this->renameRole(2, 'admin_store', 'admin');
        });
    }

    private function renameRole(int $id, string $from, string $to): void
    {
        $role = DB::table('roles')->where('id', $id)->first(['id', 'name']);

        if ($role === null) {
            throw new \RuntimeException("Role {$id} is required for canonical role migration.");
        }

        if ($role->name === $to) {
            return;
        }

        if ($role->name !== $from) {
            throw new \RuntimeException("Role {$id} must be named {$from} before it can be renamed.");
        }

        if (DB::table('roles')->where('name', $to)->where('id', '<>', $id)->exists()) {
            throw new \RuntimeException("Cannot rename role {$id}; {$to} is already used by another role.");
        }

        DB::table('roles')
            ->where('id', $id)
            ->update([
                'name' => $to,
                'updated_at' => now(),
            ]);
    }
};
