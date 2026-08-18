<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
try {
    if (!\Schema::hasColumn('categories', 'has_warranty')) {
        \Schema::table('categories', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->boolean('has_warranty')->default(false)->after('is_active');
        });
        echo "Added has_warranty to categories.\n";
    }
    if (!\Schema::hasColumn('products', 'warranty_duration')) {
        \Schema::table('products', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->string('warranty_duration')->nullable()->after('description');
        });
        echo "Added warranty_duration to products.\n";
    }
    echo "Success.";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
