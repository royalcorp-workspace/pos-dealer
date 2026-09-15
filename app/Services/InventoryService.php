<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryService
{
    public static function getWebImgChannelId(): string
    {
        $channelId = DB::table('store_channel')
            ->where('code', 'WEB_IMG')
            ->orWhere('name', 'Web IMG')
            ->value('id');

        if ($channelId) {
            return $channelId;
        }

        // Fallback or create
        $storeId = DB::table('stores')->where('code', 'ONLINE_RETAIL')->value('id');
        if (!$storeId) {
            $storeGroupId = DB::table('store_group')->value('id');
            $storeId = Str::uuid()->toString();
            DB::table('stores')->insert([
                'id' => $storeId,
                'store_group_id' => $storeGroupId,
                'code' => 'ONLINE_RETAIL',
                'name' => 'Online Retail',
                'credit_limit' => 0,
                'outstanding_balance' => 0,
                'payment_term' => 0,
                'status' => true,
                'sort_order' => 1,
                'deleted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $channelGroupId = DB::table('store_channel_group')->value('id');
        $channelId = Str::uuid()->toString();
        DB::table('store_channel')->insert([
            'id' => $channelId,
            'store_id' => $storeId,
            'store_channel_group_id' => $channelGroupId,
            'code' => 'WEB_IMG',
            'name' => 'Web IMG',
            'description' => 'Kanal Penjualan Website Resmi IMG',
            'status' => true,
            'sort_order' => 1,
            'deleted' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $channelId;
    }

    public static function recordWebOrder(string $variantId, int $quantity): bool
    {
        $channelId = self::getWebImgChannelId();

        $inventory = DB::table('inventories')
            ->where('product_variant_id', $variantId)
            ->where('store_channel_id', $channelId)
            ->where('deleted', false)
            ->first();

        if (!$inventory) {
            $variant = DB::table('product_variants')->where('id', $variantId)->first();
            if (!$variant) return false;

            $warehouseId = DB::table('warehouses')->where('code', 'GD-JKT01')->value('id')
                ?? DB::table('warehouses')->value('id');
            $storeId = DB::table('store_channel')->where('id', $channelId)->value('store_id');

            $onStock = 0;
            $newOnOrder = $quantity;
            $newAvailable = max(0, $onStock - $newOnOrder);

            DB::table('inventories')->insert([
                'id' => Str::uuid()->toString(),
                'product_id' => $variant->product_id,
                'product_variant_id' => $variantId,
                'warehouse_id' => $warehouseId,
                'store_id' => $storeId,
                'store_channel_id' => $channelId,
                'on_stock' => $onStock,
                'incoming' => 0,
                'on_order' => $newOnOrder,
                'outgoing' => 0,
                'available' => $newAvailable,
                'quantity' => $newAvailable,
                'creator' => 'Web IMG Order',
                'editor' => 'Web IMG Order',
                'deleted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $onStock = (int) ($inventory->on_stock ?? 0);
            $outgoing = (int) ($inventory->outgoing ?? 0);
            $newOnOrder = ((int)$inventory->on_order) + $quantity;
            $newAvailable = max(0, $onStock - $newOnOrder - $outgoing);

            DB::table('inventories')
                ->where('id', $inventory->id)
                ->update([
                    'on_stock' => $onStock,
                    'on_order' => $newOnOrder,
                    'available' => $newAvailable,
                    'quantity' => $newAvailable,
                    'editor' => 'Web IMG Order',
                    'updated_at' => now(),
                ]);
        }

        // Keep product_variants stock_quantity in sync
        $totalAvailable = DB::table('inventories')
            ->where('product_variant_id', $variantId)
            ->where('deleted', false)
            ->sum('available');

        DB::table('product_variants')
            ->where('id', $variantId)
            ->update(['stock_quantity' => (int)$totalAvailable]);

        return true;
    }
}
