<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\SettingGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * SetDefaultSettingSeeder
 * 
 * This seeder adds the "دیفالت نمایش فروشگاه" (Default Shop Display) setting
 * to control the default sorting behavior of products in the API.
 * 
 * ============================================================================
 * AVAILABLE SORT OPTIONS:
 * ============================================================================
 * 
 * 1. latest         - جدیدترین محصولات (Sort by newest products first)
 * 2. oldest         - قدیمی‌ترین محصولات (Sort by oldest products first)
 * 3. price_asc      - قیمت صعودی (Sort by price: low to high)
 * 4. price_desc     - قیمت نزولی (Sort by price: high to low)
 * 5. name_asc       - نام الفبایی (Sort by name: A to Z)
 * 6. name_desc      - نام الفبایی معکوس (Sort by name: Z to A)
 * 7. random         - تصادفی (Random order)
 * 
 * ============================================================================
 * HOW TO USE IN API QUERIES:
 * ============================================================================
 * 
 * 1. Use Default Setting (from database):
 *    GET /api/v1/products
 *    => Uses the value set in admin panel (default: 'latest')
 * 
 * 2. Override with sort_by parameter:
 *    GET /api/v1/products?sort_by=price_asc
 *    GET /api/v1/products?sort_by=latest
 *    GET /api/v1/products?sort_by=random
 * 
 * 3. Legacy support (price_dir still works):
 *    GET /api/v1/products?price_dir=asc    => Maps to price_asc
 *    GET /api/v1/products?price_dir=desc   => Maps to price_desc
 * 
 * 4. Random parameter (still works):
 *    GET /api/v1/products?random=1         => Random order
 * 
 * 5. Combine with other filters:
 *    GET /api/v1/products?sort_by=price_asc&category_ids=1,2&search=gold
 *    GET /api/v1/products?sort_by=latest&minPrice=1000&maxPrice=5000
 *    GET /api/v1/products?sort_by=name_asc&hasDiscount=1
 * 
 * ============================================================================
 * HOW TO USE IN CODE:
 * ============================================================================
 * 
 * 1. In Controllers:
 *    $products = Product::query()
 *        ->main()
 *        ->applyDefaultSort()           // Uses setting from database
 *        ->get();
 * 
 *    $products = Product::query()
 *        ->main()
 *        ->applyDefaultSort('price_asc') // Override with specific sort
 *        ->get();
 * 
 * 2. Get Setting Value:
 *    $defaultSort = setting('default_shop_display'); // Returns: 'latest'
 * 
 * 3. Change Setting Value (in Admin Panel or Code):
 *    Setting::where('key', 'default_shop_display')->update(['value' => 'price_asc']);
 * 
 * ============================================================================
 * PRIORITY ORDER:
 * ============================================================================
 * 
 * When determining sort order, the system checks in this order:
 * 1. ?random=1          → Use random order
 * 2. ?price_dir=asc     → Use price ascending (legacy)
 * 3. ?sort_by=latest    → Use sort_by parameter (new)
 * 4. [Setting Value]    → Use default from database
 * 
 * ============================================================================
 */
class SetDefaultSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if "فروشگاه" group exists, if not create it
        $shopGroup = SettingGroup::where('title', 'فروشگاه')->first();
        
        if (!$shopGroup) {
            $shopGroup = SettingGroup::create([
                'title' => 'فروشگاه'
            ]);
        }

        // Check if setting already exists
        $existingSetting = Setting::where('key', 'default_shop_display')->first();
        
        if (!$existingSetting) {
            Setting::create([
                'key' => 'default_shop_display',
                'type' => 'string',
                'title' => 'دیفالت نمایش فروشگاه (latest, oldest, price_asc, price_desc, name_asc, name_desc, random)',
                'value' => 'latest',
                'setting_group_id' => $shopGroup->id
            ]);
        }
    }
}

