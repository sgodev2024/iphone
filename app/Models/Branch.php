<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'admin_store_user_id',
        'name',
        'address',
        'phone',
        'email',
        'manager_name',
        'status'
    ];
    protected function statusText(): Attribute
    {
        return Attribute::get(fn() => $this->status == 1 ? 'Hoạt động' : 'Không hoạt động');
    }

    public function adminStore(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_store_user_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function storages()
    {
        return $this->hasMany(Storage::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Categories::class);
    }
}
