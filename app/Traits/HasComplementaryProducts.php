<?php
namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait HasComplementaryProducts
{
    public function syncComplementary(array $selections)
    {
        // Remove old relations for this source model
        DB::table('complementary_products')
            ->where('source_type', get_class($this))
            ->where('source_id', $this->id)
            ->delete();

        $now = now();
        $inserts = [];

        foreach ($selections as $item) {
            [$modelType, $id] = explode(':', $item);

            if (!in_array($modelType, ['Product', 'Category'])) {
                continue;
            }

            $modelClass = "App\\Models\\{$modelType}";

            $inserts[] = [
                'source_type' => get_class($this),
                'source_id'   => $this->id,
                'target_type' => $modelClass,
                'target_id'   => $id,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        if ($inserts) {
            DB::table('complementary_products')->insert($inserts);
        }
    }
}
