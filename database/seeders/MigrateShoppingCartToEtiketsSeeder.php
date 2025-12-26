<?php

namespace Database\Seeders;

use App\Models\Etiket;
use App\Models\Product;
use App\Models\ShoppingCartItem;
use Illuminate\Database\Seeder;

class MigrateShoppingCartToEtiketsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all shopping cart items that don't have an etiket_id yet
        $cartItems = ShoppingCartItem::whereNull('etiket_id')
            ->with('product')
            ->orderBy('user_id')
            ->orderBy('product_id')
            ->get();

        if ($cartItems->isEmpty()) {
            $this->command->info('No shopping cart items to migrate.');
            return;
        }

        $this->command->info("Found {$cartItems->count()} shopping cart items to migrate.");

        $migrated = 0;
        $deleted = 0;

        // Group items by user_id and product_id to handle them together
        $groupedItems = $cartItems->groupBy(function ($item) {
            return $item->user_id . '_' . $item->product_id;
        });

        foreach ($groupedItems as $groupKey => $items) {
            $firstItem = $items->first();
            $userId = $firstItem->user_id;
            $product = $firstItem->product;

            if (!$product) {
                // Product doesn't exist, delete these items
                $this->command->warn("Product not found for cart items. Deleting {$items->count()} items.");
                foreach ($items as $item) {
                    $item->delete();
                    $deleted++;
                }
                continue;
            }

            // Get all etiket IDs already assigned to this user (to prevent duplicates)
            $userAssignedEtiketIds = ShoppingCartItem::where('user_id', $userId)
                ->whereNotNull('etiket_id')
                ->pluck('etiket_id')
                ->toArray();

            // Find available etikets for this product
            $availableEtikets = $this->getAvailableEtiketsForProduct($product, $userAssignedEtiketIds);

            if ($availableEtikets->isEmpty()) {
                // No available etikets, delete all items for this product
                $this->command->warn("No available etikets for product {$product->id} ({$product->name}). Deleting {$items->count()} cart items.");
                foreach ($items as $item) {
                    $item->delete();
                    $deleted++;
                }
                continue;
            }

            // Calculate total number of etikets needed (sum of all counts)
            $totalNeeded = $items->sum('count');

            // Limit to available etikets
            $etiketsToAssign = $availableEtikets->take($totalNeeded);

            if ($etiketsToAssign->count() < $totalNeeded) {
                $this->command->warn("Only {$etiketsToAssign->count()} available etikets for product {$product->id}, but {$totalNeeded} needed. Removing extra items.");
            }

            // Delete all existing items for this user/product combination
            // We'll recreate them with etikets
            foreach ($items as $item) {
                $item->delete();
            }

            // Create new cart items, one per etiket
            $etiketsAssigned = 0;
            foreach ($etiketsToAssign as $etiket) {
                ShoppingCartItem::create([
                    'user_id' => $userId,
                    'product_id' => $product->id,
                    'etiket_id' => $etiket->id,
                    'count' => 1,
                ]);
                $etiketsAssigned++;
                $migrated++;
            }

            // If we needed more etikets than available, those items are lost (deleted above)
            if ($totalNeeded > $etiketsToAssign->count()) {
                $deleted += ($totalNeeded - $etiketsToAssign->count());
            }
        }

        $this->command->info("Migration completed:");
        $this->command->info("- Migrated: {$migrated} cart items (with etikets)");
        $this->command->info("- Deleted: {$deleted} cart items (no available etikets or exceeded available count)");
    }

    /**
     * Get available etikets for a product (including children's etikets)
     * Excludes etikets already assigned to the user
     */
    private function getAvailableEtiketsForProduct(Product $product, array $excludeEtiketIds = []): \Illuminate\Support\Collection
    {
        // First, get direct etikets for this product
        $directEtikets = Etiket::where('product_id', $product->id)
            ->where('is_mojood', 1)
            ->whereNotIn('id', $excludeEtiketIds)
            ->get();

        // If product has children, also get their etikets
        $childrenEtikets = collect();
        if ($product->children()->exists()) {
            $childrenProductIds = $product->children()->pluck('id')->toArray();
            $childrenEtikets = Etiket::whereIn('product_id', $childrenProductIds)
                ->where('is_mojood', 1)
                ->whereNotIn('id', $excludeEtiketIds)
                ->get();
        }

        // Combine and return unique etikets
        return $directEtikets->merge($childrenEtikets)->unique('id')->values();
    }
}
