<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Product;

class ProductObserver
{
    private array $watch = [
        'name','code','size','color','rental_price',
        'stock_total','stock_available','status','category_id',
    ];

    public function created(Product $product): void
    {
        ActivityLog::record('create_product',
            "Produk baru: {$product->name} ({$product->code})",
            $product, null, $product->only($this->watch)
        );
    }

    public function updated(Product $product): void
    {
        $dirty = collect($product->getDirty())->only($this->watch)->toArray();
        if (empty($dirty)) return;

        $old = [];
        foreach (array_keys($dirty) as $col) {
            $old[$col] = $product->getOriginal($col);
        }

        $action = (isset($dirty['stock_available']) || isset($dirty['stock_total']))
            ? 'update_stock' : 'update_product';

        ActivityLog::record($action,
            "Produk diubah: {$product->name}",
            $product, $old, $dirty
        );
    }

    public function deleted(Product $product): void
    {
        ActivityLog::record('delete_product',
            "Produk dihapus: {$product->name} ({$product->code})",
            $product, $product->only($this->watch), null
        );
    }
}
