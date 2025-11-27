<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected ?string $search;

    public function __construct(?string $search = null)
    {
        $this->search = $search;
    }

    public function query()
    {
        return User::query()
            ->users()
            ->with('addresses')
            ->when($this->search, function (Builder $query) {
                $query->search($this->search);
            })
            ->latest();
    }

    public function headings(): array
    {
        return [
            'نام',
            'شماره تماس',
            'آدرس‌ها',
        ];
    }

    public function map($user): array
    {
        $name = trim(($user->full_name ?? '') ?: ($user->name ?? ''));

        return [
            $name !== '' ? $name : '-',
            $user->phone ?? '-',
            $user->all_addresses,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}

