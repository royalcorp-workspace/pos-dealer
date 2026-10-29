<?php

declare(strict_types=1);

namespace App\Traits;

trait HasSchemaTable
{
    /**
     * Map of model namespaces / classes to PostgreSQL domain schemas.
     *
     * @var array<string, string>
     */
    public static array $domainSchemaMap = [
        // Event and EventPopup belong to promo schema
        'App\Models\Frontend\Event' => 'promo',
        'App\Models\Frontend\EventPopup' => 'promo',

        // Promo domain
        'App\Models\Frontend\Promo\\' => 'promo',

        // Products Catalog domain
        'App\Models\Frontend\ProductsCatalog\Wishlist' => 'customer',
        'App\Models\Frontend\ProductsCatalog\\' => 'finish_goods',
        'App\Models\Frontend\Product' => 'finish_goods',
        'App\Models\Frontend\ProductVariant' => 'finish_goods',
        'App\Models\Frontend\Brand' => 'finish_goods',
        'App\Models\Frontend\Category' => 'finish_goods',
        'App\Models\Frontend\Review' => 'finish_goods',

        // Order & Finance domain
        'App\Models\Frontend\Order\\' => 'sales',
        'App\Models\Frontend\Order' => 'sales',
        'App\Models\Frontend\OrderItem' => 'sales',
        'App\Models\Frontend\OrderLog' => 'sales',
        'App\Models\Payment' => 'sales',
        'App\Models\PaymentMethod' => 'sales',
        'App\Models\Settlement' => 'sales',
        'App\Models\VoidOrder' => 'sales',
        'App\Models\CreditMemo' => 'sales',

        // Customer domain
        'App\Models\Frontend\Customer\\' => 'customer',
        'App\Models\Customer' => 'customer',

        // Shipping & Location domain
        'App\Models\Frontend\Shipping\\' => 'shipping',
        'App\Models\Frontend\Location\\' => 'shipping',

        // Buffer & Inventory domain
        'App\Models\Frontend\Buffer\\' => 'inventory',

        // Content domain
        'App\Models\Frontend\Banner' => 'content',
        'App\Models\Frontend\BlogPost' => 'content',
        'App\Models\Frontend\Faq' => 'content',
        'App\Models\Frontend\AboutUs' => 'content',
        'App\Models\Frontend\HomepageSection' => 'content',
        'App\Models\Frontend\HowToReturn' => 'content',
        'App\Models\Frontend\PrivacyPolicy' => 'content',
        'App\Models\Frontend\TermsAndCondition' => 'content',
        'App\Models\Frontend\WarrantyClaim' => 'content',
        'App\Models\Frontend\Notification' => 'content',
        'App\Models\Conversation' => 'content',
        'App\Models\Message' => 'content',

        // User Management domain
        'App\Models\User' => 'user_managements',
        'App\Models\ActivityLog' => 'user_managements',
        'App\Models\DeviceSession' => 'user_managements',
        'App\Models\EmailVerification' => 'user_managements',
        'App\Models\LoginAttempt' => 'user_managements',
        'App\Models\PasswordReset' => 'user_managements',
        'App\Models\RefreshToken' => 'user_managements',
    ];

    /**
     * Get the table associated with the model, prefixed with PostgreSQL schema if configured.
     *
     * @return string
     */
    public function getTable()
    {
        $table = parent::getTable();

        // Only prefix with schema if database driver is PostgreSQL
        try {
            $driver = $this->getConnection()->getDriverName();
        } catch (\Throwable $e) {
            $driver = config('database.default') === 'pgsql' ? 'pgsql' : null;
        }

        if ($driver === 'pgsql') {
            $schema = $this->getModelSchema();
            if (!empty($schema) && !str_contains($table, '.')) {
                return "{$schema}.{$table}";
            }
        }

        return $table;
    }

    /**
     * Determine the database schema for this model.
     *
     * @return string
     */
    public function getModelSchema(): string
    {
        // 1. If model explicitly defines a $schema property
        if (isset($this->schema) && !empty($this->schema)) {
            return (string) $this->schema;
        }

        // 2. Check namespace/class mapping
        $class = static::class;
        foreach (static::$domainSchemaMap as $prefix => $schema) {
            if ($class === $prefix || (str_ends_with($prefix, '\\') && str_starts_with($class, $prefix))) {
                return $schema;
            }
        }

        // 3. Fallback to default DB_SCHEMA or 'public'
        return config('database.connections.pgsql.schema') ?? env('DB_SCHEMA', 'public');
    }

    /**
     * Resolve the configured database schema for PostgreSQL.
     *
     * @return string|null
     */
    public static function getDatabaseSchema(): ?string
    {
        return config('database.connections.pgsql.schema') ?? env('DB_SCHEMA', 'public');
    }

    /**
     * Format a given table name with the database schema if applicable.
     *
     * @param string $tableName
     * @param string|null $schema
     * @return string
     */
    public static function withSchema(string $tableName, ?string $schema = null): string
    {
        $schema = $schema ?? static::getDatabaseSchema();

        if (!empty($schema) && !str_contains($tableName, '.')) {
            return "{$schema}.{$tableName}";
        }

        return $tableName;
    }
}
