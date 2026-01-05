<?php

namespace App\Exports;

use App\Models\Etiket;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EtiketsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Etiket::query()->with('product.categories')->select('etikets.*');
        
        // Filter by availability if specified
        if (isset($this->filters['is_mojood'])) {
            $isMojood = $this->filters['is_mojood'];
            if ($isMojood === '1' || $isMojood === 1) {
                $query->where('etikets.is_mojood', 1);
            } elseif ($isMojood === '0' || $isMojood === 0) {
                $query->where('etikets.is_mojood', 0);
            }
        }

        // Apply filters
        if (!empty($this->filters['name'])) {
            $query->where('etikets.name', 'like', '%' . $this->filters['name'] . '%');
        }

        if (!empty($this->filters['code'])) {
            $query->where('etikets.code', 'like', '%' . $this->filters['code'] . '%');
        }

        if (!empty($this->filters['weight'])) {
            $query->where('etikets.weight', $this->filters['weight']);
        }

        if (!empty($this->filters['weight_from'])) {
            $query->where('etikets.weight', '>=', $this->filters['weight_from']);
        }

        if (!empty($this->filters['weight_to'])) {
            $query->where('etikets.weight', '<=', $this->filters['weight_to']);
        }

        if (!empty($this->filters['category_ids'])) {
            $categoryIds = is_array($this->filters['category_ids']) 
                ? $this->filters['category_ids'] 
                : explode(',', $this->filters['category_ids']);
            $query->whereHas('product.categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            });
        }

        return $query->orderBy('etikets.id', 'desc')->get();
    }

    /**
     * Define the headings for the Excel file
     */
    public function headings(): array
    {
        return [
            'کد',
            'اسم',
            'وزن',
            'قیمت',
            'محصول',
            'دسته بندی ها',
            'موجودی',
            'درصد اجرت',
            'درصد خرید',
            'تاریخ ایجاد',
        ];
    }

    /**
     * Map data for each row
     */
    public function map($etiket): array
    {
        $product = $etiket->product;
        $categories = $product ? $product->categories->pluck('title')->join('، ') : '-';
        
        return [
            $etiket->code,
            $etiket->name,
            $etiket->weight,
            number_format($etiket->price / 10), // Divide by 10 as per requirement
            $product ? $product->name : '-',
            $categories,
            $etiket->is_mojood ? 'موجود' : 'ناموجود',
            $etiket->ojrat ?? '-',
            $etiket->darsad_kharid ?? '-',
            $etiket->created_at ? $etiket->created_at->format('Y/m/d H:i') : '-',
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}

