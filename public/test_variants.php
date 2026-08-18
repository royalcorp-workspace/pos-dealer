<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
$products = \App\Models\Frontend\ProductsCatalog\Product::has('variants')->with('variants')->take(5)->get();
foreach ($products as $p) {
    echo "Product: " . $p->name . "\n";
    $v = $p->variants->pluck('variant_name')->toArray();
    echo "Original: " . implode(', ', $v) . "\n";
    
    $sorted = $p->variants->sortBy('variant_name', SORT_NATURAL | SORT_FLAG_CASE)->values()->pluck('variant_name')->toArray();
    echo "Sorted: " . implode(', ', $sorted) . "\n\n";
}
