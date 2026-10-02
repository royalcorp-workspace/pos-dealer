<?php

declare(strict_types=1);

namespace App\Models\Frontend\Promo;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Voucher extends Model
{
    use HasUuids;

    protected $table = 'vouchers';

    protected $fillable = [
        'code',
        'title',
        'description',
        'type',
        'scope',
        'allow_stacking',
        'value',
        'min_purchase',
        'max_discount',
        'usage_limit',
        'usage_limit_per_user',
        'used_count',
        'start_date',
        'end_date',
        'valid_for_new_customer',
        'is_active',
        'show_on_web',
        'visibility',
        'store_id',
        'require_follow',
        'creator',
        'editor',
        'deleted',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'integer',
            'scope' => 'integer',
            'allow_stacking' => 'boolean',
            'value' => 'decimal:2',
            'min_purchase' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'usage_limit' => 'integer',
            'usage_limit_per_user' => 'integer',
            'used_count' => 'integer',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'valid_for_new_customer' => 'boolean',
            'is_active' => 'boolean',
            'show_on_web' => 'boolean',
            'require_follow' => 'boolean',
            'deleted' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('active', function ($query) {
            $table = $query->getModel()->getTable();
            $query->where($table . '.is_active', true)
                ->where($table . '.deleted', false)
                ->where(function ($q) use ($table) {
                    $q->whereNull($table . '.start_date')->orWhere($table . '.start_date', '<=', now());
                })
                ->where(function ($q) use ($table) {
                    $q->whereNull($table . '.end_date')->orWhere($table . '.end_date', '>=', now());
                });
        });
    }

    public function scopeActive($query)
    {
        $table = $query->getModel()->getTable();
        return $query->where($table . '.is_active', true)
            ->where($table . '.deleted', false)
            ->where(function ($q) use ($table) {
                $q->whereNull($table . '.start_date')->orWhere($table . '.start_date', '<=', now());
            })
            ->where(function ($q) use ($table) {
                $q->whereNull($table . '.end_date')->orWhere($table . '.end_date', '>=', now());
            });
    }

    public function isValid(): bool
    {
        if ($this->deleted || !$this->is_active) return false;
        if ($this->start_date && $this->start_date->isFuture()) return false;
        if ($this->end_date && $this->end_date->isPast()) return false;
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) return false;
        return true;
    }

    public function resolveUserIdentifiers(?string $userId): array
    {
        if (!$userId) return [[], []];

        $userIds = [$userId];
        $customerIds = [];

        $customer = \App\Models\Frontend\Customer\Customer::where(function ($q) use ($userId) {
            $q->where('user_id', $userId)->orWhere('id', $userId);
        })->first();

        if ($customer) {
            $customerIds[] = $customer->id;
            if ($customer->user_id) {
                $userIds[] = $customer->user_id;
            }
        }

        return [
            array_values(array_unique(array_filter($userIds))),
            array_values(array_unique(array_filter($customerIds))),
        ];
    }

    public function canBeUsedBy(?string $userId): bool
    {
        if (!$this->isValid()) return false;
        if ($this->valid_for_new_customer && $userId) return false;

        [$userIds, $customerIds] = $this->resolveUserIdentifiers($userId);
        $allIdentities = array_values(array_unique(array_merge($userIds, $customerIds)));

        // Usage limit per user check
        if ($this->usage_limit_per_user && !empty($allIdentities)) {
            $userUsages = $this->usages()
                ->where(function ($uq) use ($userIds, $customerIds) {
                    if (!empty($userIds)) {
                        $uq->whereIn('voucher_usages.user_id', $userIds);
                    }
                    if (!empty($customerIds)) {
                        $uq->orWhereExists(function ($oq) use ($customerIds) {
                            $oq->select(DB::raw(1))
                                ->from('orders')
                                ->where(function ($q) {
                                    $q->whereRaw('CAST(orders.id AS VARCHAR) = voucher_usages.order_id')
                                      ->orWhereRaw('orders.order_number = voucher_usages.order_id');
                                })
                                ->whereIn('orders.customer_id', $customerIds);
                        });
                    }
                })
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('orders')
                        ->where(function ($q) {
                            $q->whereRaw('CAST(orders.id AS VARCHAR) = voucher_usages.order_id')
                              ->orWhereRaw('orders.order_number = voucher_usages.order_id');
                        })
                        ->where('orders.status', '!=', 6) // Exclude cancelled orders
                        ->where('orders.deleted', false);
                })
                ->count();

            if ($userUsages >= $this->usage_limit_per_user) return false;
        }

        // Scope 2: Customer tertentu
        if ((int) $this->scope === 2) {
            if (empty($allIdentities)) return false;
            $hasAccess = $this->customers()
                ->where(function ($q) use ($allIdentities) {
                    $q->whereIn('customers.id', $allIdentities)
                      ->orWhereIn('customers.user_id', $allIdentities);
                })
                ->exists();
            if (!$hasAccess) return false;
        }

        // Scope 7: Group customer (Karyawan / Reseller)
        if ((int) $this->scope === 7) {
            if (empty($allIdentities)) return false;
            $hasAccess = $this->customerGroups()
                ->whereHas('members', function ($q) use ($allIdentities) {
                    $q->whereIn('customers.id', $allIdentities)
                      ->orWhereIn('customers.user_id', $allIdentities);
                })
                ->exists();
            if (!$hasAccess) return false;
        }

        // Claimable voucher check: Must be claimed first if visibility is claimable
        if (($this->visibility ?? 'public') === 'claimable') {
            if (empty($allIdentities)) return false;
            $hasClaimed = $this->claims()
                ->where(function ($q) use ($allIdentities) {
                    $q->whereIn('customer_id', $allIdentities)
                      ->orWhereExists(function ($cq) use ($allIdentities) {
                          $cq->select(DB::raw(1))
                             ->from('customers')
                             ->whereRaw('customers.id = voucher_claims.customer_id')
                             ->whereIn('customers.user_id', $allIdentities);
                      });
                })
                ->exists();
            if (!$hasClaimed) return false;
        }

        // Store follow check
        if ($this->require_follow && $this->store_id) {
            if (empty($allIdentities)) return false;
            $isFollowing = DB::table('store_followers')
                ->where('store_id', $this->store_id)
                ->where(function ($q) use ($allIdentities) {
                    $q->whereIn('customer_id', $allIdentities)
                      ->orWhereExists(function ($sq) use ($allIdentities) {
                          $sq->select(DB::raw(1))
                             ->from('customers')
                             ->whereRaw('customers.id = store_followers.customer_id')
                             ->whereIn('customers.user_id', $allIdentities);
                      });
                })
                ->exists();
            if (!$isFollowing) return false;
        }

        return true;
    }

    public function getEligibleProductIds(array $cart): array
    {
        $cartProductIds = collect($cart)->pluck('product_id')->filter()->unique()->values()->all();
        if (empty($cartProductIds)) return [];

        // Scope 1, 2, 7: all products in cart are eligible
        if (in_array((int) $this->scope, [1, 2, 7], true)) {
            return $cartProductIds;
        }

        // Scope 3: Kategori tertentu
        if ((int) $this->scope === 3) {
            if ($this->relationLoaded('categories')) {
                $catProductIds = $this->categories->where('deleted', false)
                    ->flatMap(fn($cat) => $cat->products ? $cat->products->where('deleted', false)->pluck('id') : [])
                    ->unique()
                    ->toArray();
            } else {
                $catProductIds = $this->categories()
                    ->where('product_category.deleted', false)
                    ->with('products')
                    ->get()
                    ->flatMap(fn($cat) => $cat->products->where('deleted', false)->pluck('id'))
                    ->unique()
                    ->toArray();
            }
            return array_values(array_intersect($cartProductIds, $catProductIds));
        }

        // Scope 4: Produk Tertentu (per Artikel)
        if ((int) $this->scope === 4) {
            $eligibleProductIds = $this->relationLoaded('products')
                ? $this->products->where('deleted', false)->pluck('id')->toArray()
                : $this->products()->where('products.deleted', false)->pluck('products.id')->toArray();
            return array_values(array_intersect($cartProductIds, $eligibleProductIds));
        }

        // Scope 5: Brand Tertentu
        if ((int) $this->scope === 5) {
            $brandIds = $this->relationLoaded('brands')
                ? $this->brands->where('deleted', false)->pluck('id')->toArray()
                : $this->brands()->where('brands.deleted', false)->pluck('brands.id')->toArray();
            $brandProductIds = \App\Models\Frontend\ProductsCatalog\Product::whereIn('brand_id', $brandIds)
                ->where('deleted', false)
                ->pluck('id')
                ->toArray();
            return array_values(array_intersect($cartProductIds, $brandProductIds));
        }

        // Scope 6: Brand & Artikel Tertentu
        if ((int) $this->scope === 6) {
            $directProductIds = $this->relationLoaded('products')
                ? $this->products->where('deleted', false)->pluck('id')->toArray()
                : $this->products()->where('products.deleted', false)->pluck('products.id')->toArray();
            $brandIds = $this->relationLoaded('brands')
                ? $this->brands->where('deleted', false)->pluck('id')->toArray()
                : $this->brands()->where('brands.deleted', false)->pluck('brands.id')->toArray();
            $brandProductIds = \App\Models\Frontend\ProductsCatalog\Product::whereIn('brand_id', $brandIds)
                ->where('deleted', false)
                ->pluck('id')
                ->toArray();
            $allEligible = array_values(array_unique(array_merge($directProductIds, $brandProductIds)));
            return array_values(array_intersect($cartProductIds, $allEligible));
        }

        return $cartProductIds;
    }

    public function getEligibleSubtotal(array $cart): float
    {
        if (empty($cart)) return 0.0;

        $eligibleProductIds = $this->getEligibleProductIds($cart);

        if (in_array((int) $this->scope, [3, 4, 5, 6], true) && empty($eligibleProductIds)) {
            return 0.0;
        }

        return (float) collect($cart)
            ->filter(fn($item) => in_array($item['product_id'] ?? null, $eligibleProductIds, true))
            ->sum(fn($item) => ((float)($item['sell_price'] ?? $item['price'] ?? 0)) * ((int)($item['quantity'] ?? 0)));
    }

    public function appliesToCart(array $cart, ?string $userId = null): bool
    {
        if (empty($cart)) return false;
        if (!$this->canBeUsedBy($userId)) return false;

        if (in_array((int) $this->scope, [3, 4, 5, 6], true)) {
            $eligibleIds = $this->getEligibleProductIds($cart);
            if (empty($eligibleIds)) return false;
            if ($this->getEligibleSubtotal($cart) <= 0) return false;
        }

        return true;
    }

    public function calculateDiscountValue(float $eligibleSubtotal, float $shippingCost = 0.0): float
    {
        $voucherValue = (float) $this->value;

        if ((int) $this->type === 1) {
            $maxDiscount = ($this->max_discount !== null && (float) $this->max_discount > 0) ? (float) $this->max_discount : PHP_FLOAT_MAX;
            return (float) min(($eligibleSubtotal * $voucherValue / 100), $maxDiscount);
        }

        if ((int) $this->type === 2) {
            return (float) min($voucherValue, $eligibleSubtotal);
        }

        if ((int) $this->type === 3) {
            return (float) min($voucherValue, $shippingCost);
        }

        return 0.0;
    }

    public function isStackable(): bool
    {
        return (int) $this->type === 3 && (bool) $this->allow_stacking;
    }

    public function canBeStackedWith(Voucher $other): bool
    {
        if (!empty($this->id) && !empty($other->id) && $this->id === $other->id) {
            return false;
        }

        $isThisShipping = (int) $this->type === 3;
        $isOtherShipping = (int) $other->type === 3;

        if ($isThisShipping === $isOtherShipping) {
            return false;
        }

        if ($isThisShipping && !$this->isStackable()) {
            return false;
        }
        if ($isOtherShipping && !$other->isStackable()) {
            return false;
        }

        return true;
    }

    public static function validateVoucherCombination(iterable $vouchers): bool
    {
        $shippingCount = 0;
        $nonShippingCount = 0;

        foreach ($vouchers as $voucher) {
            if ((int) $voucher->type === 3) {
                if (!$voucher->isStackable()) {
                    return false;
                }
                $shippingCount++;
            } else {
                $nonShippingCount++;
            }
        }

        return $shippingCount <= 1 && $nonShippingCount <= 1;
    }

    public function scopeLabel(): string
    {
        return match ((int) $this->scope) {
            2 => 'Customer tertentu',
            3 => 'Kategori tertentu',
            4 => 'Produk tertentu',
            5 => 'Brand tertentu',
            6 => 'Brand & Artikel',
            7 => 'Group Customer',
            default => 'Semua Produk Aktif (Voucher Toko / Web)',
        };
    }

    public function discountLabel(): string
    {
        return match ((int) $this->type) {
            1 => 'Persentase (%)',
            2 => 'Nominal (Rp)',
            3 => 'Diskon Ongkir (Rp)',
            4 => 'Bonus Produk (pcs)',
            default => 'Tidak diketahui',
        };
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Frontend\Customer\Customer::class, 'voucher_customers', 'voucher_id', 'customer_id')
            ->withPivot('creator', 'editor', 'deleted');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Frontend\ProductsCatalog\ProductCategory::class, 'voucher_categories', 'voucher_id', 'category_id')
            ->wherePivot('deleted', false)
            ->where('product_category.deleted', false)
            ->withPivot('creator', 'editor', 'deleted');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Frontend\ProductsCatalog\Product::class, 'voucher_products', 'voucher_id', 'product_id')
            ->wherePivot('deleted', false)
            ->where('products.deleted', false)
            ->withPivot('creator', 'editor', 'deleted');
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Frontend\ProductsCatalog\Brand::class, 'voucher_brands', 'voucher_id', 'brand_id')
            ->wherePivot('deleted', false)
            ->where('brands.deleted', false)
            ->withPivot('creator', 'editor', 'deleted');
    }

    public function customerGroups(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Frontend\Customer\CustomerGroup::class, 'voucher_customer_groups', 'voucher_id', 'customer_group_id')
            ->wherePivot('deleted', false)
            ->withPivot('creator', 'editor', 'deleted');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(VoucherClaim::class, 'voucher_id', 'id');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(VoucherUsage::class, 'voucher_id', 'id');
    }
}