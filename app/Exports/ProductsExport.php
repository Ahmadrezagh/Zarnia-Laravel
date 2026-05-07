<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
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
        $query = Product::query()
            ->main()
            ->FilterProduct($this->filters['filter'] ?? null)
            ->MultipleSearch([
                $this->filters['searchKey'] ?? null,
                $this->filters['searchVal'] ?? null
            ]);

        if (!empty($this->filters['category_ids'])) {
            $query->categories($this->filters['category_ids']);
        }

        return $query->get();
    }

    /**
     * Define the headings for the Excel file
     */
    public function headings(): array
    {
        return [
            'نام محصول',
            'آدرس محصول',
            'وزن',
            'قیمت',
            'موجودی',
            'درصد تخفیف',
        ];
    }

    /**
     * Map data for each row
     */
    public function map($product): array
    {
        return [
            $product->name,
            $product->frontend_url,
            $product->weight,
            $product->price,
            $product->count,
            $product->discount_percentage,
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

