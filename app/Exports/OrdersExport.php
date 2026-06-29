<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Morilog\Jalali\Jalalian;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Order::query()
            ->with(['user', 'address.province', 'address.city', 'gateway', 'shipping', 'orderItems.product'])
            ->filterByTransactionId($this->filters['transaction_id'] ?? null)
            ->filterByStatus($this->filters['status'] ?? null)
            ->filterByPhone($this->filters['phone'] ?? null);

        if (! empty($this->filters['search'])) {
            $query->search($this->filters['search']);
        }

        return $query->orderByStatusPriority();
    }

    public function headings(): array
    {
        return [
            'شماره سفارش',
            'شماره تماس مشتری',
            'نام مشتری',
            'استان',
            'شهر',
            'وزن کل (گرم)',
            'مقدار تخفیف (تومان)',
            'کد تخفیف',
            'قیمت لحظه‌ای طلا',
            'مرحله سفارش',
            'درگاه پرداخت',
            'تاریخ',
            'کد پیگیری',
            'مبلغ کل (تومان)',
            'مبلغ نهایی (تومان)',
            'محصولات',
            'کد اتیکت',
            'نوع ارسال',
            'منبع',
        ];
    }

    public function map($order): array
    {
        $productNames = $order->orderItems
            ->map(fn ($item) => $item->name ?: ($item->product?->name))
            ->filter()
            ->unique()
            ->implode('، ');

        $etiketCodes = $order->orderItems
            ->pluck('etiket')
            ->filter()
            ->unique()
            ->implode('، ');

        return [
            $order->id,
            $order->user?->phone ?? $order->address?->receiver_phone ?? '-',
            trim($order->user?->full_name ?? $order->user?->name ?? $order->address?->receiver_name ?? '') ?: '-',
            $order->address?->province?->name ?? 'خرید حضوری',
            $order->address?->city?->name ?? '-',
            $order->weight,
            $order->discount_price ?? 0,
            $order->discount_code ?? '-',
            $order->gold_price ?? '-',
            Order::$PERSIAN_STATUSES[$order->status] ?? $order->status,
            $order->gateway?->title ?? '-',
            Jalalian::forge($order->created_at)->format('Y/m/d H:i:s'),
            $order->transaction_id ?? '-',
            $order->total_amount ?? 0,
            $order->final_amount ?? 0,
            $productNames ?: '-',
            $etiketCodes ?: '-',
            $order->shipping?->title ?? 'بدون ارسال',
            $order->reference ?? 'مستقیم',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
