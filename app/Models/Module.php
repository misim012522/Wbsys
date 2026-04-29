<?php

namespace App\Models;

use App\Models\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model
{
    use HasFactory;
    use UsesCentralConnection;

    protected $fillable = ['name', 'slug', 'description', 'price_monthly', 'is_active', 'plan_id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'price_monthly' => 'decimal:2'];
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_modules');
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_modules');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
