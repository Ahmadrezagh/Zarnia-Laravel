<?php
namespace App\Traits\Scopes;
use Illuminate\Database\Eloquent\Builder;

trait Search{
    public function scopeSearch(Builder $query,$search = null)
    {
        if($search){
            // Normalize search value (convert Persian/Arabic digits to English)
            $normalized = $this->normalizeSearchValue($search);
            
            return $query->where(function ($q) use ($search, $normalized) {
                // Search in product name
                $q->where('name', 'like', '%'.$search.'%')
                  // Also search in etiket codes (exact match)
                  ->orWhereHas('etikets', function ($sub) use ($normalized) {
                      $sub->where('code', $normalized);
                  })
                  // Also search in children's etiket codes (exact match)
                  ->orWhereHas('children.etikets', function ($sub) use ($normalized) {
                      $sub->where('code', $normalized);
                  });
            });
        }
        return $query;
    }
    
    /**
     * Normalize search value (convert Persian/Arabic digits to English)
     */
    protected function normalizeSearchValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $search = trim($value);

        $persianDigits  = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $arabicDigits   = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $englishDigits  = ['0','1','2','3','4','5','6','7','8','9'];

        $search = str_replace($persianDigits, $englishDigits, $search);
        $search = str_replace($arabicDigits, $englishDigits, $search);

        return $search;
    }
}