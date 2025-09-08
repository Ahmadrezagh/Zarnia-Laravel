<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndexBanner;
use App\Models\InvoicePosition;
use App\Models\InvoiceTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

class InvoiceTemplateController extends Controller
{
    public function index() {
        $templates = InvoiceTemplate::query()->latest()->paginate();
        return view('admin.invoice-templates.index', compact('templates'));
    }

    public function create() {
        return view('admin.invoice-templates.create');
    }

    public function store(Request $request) {
        $request->validate([
            'name' => 'required',
            'background' => 'file|mimes:pdf,jpg,png|max:2048', // PDF یا تصویر
        ]);

        $template = InvoiceTemplate::create(['name' => $request->name]);

        if ($request->hasFile('background')) {
            $path = $request->file('background')->store('invoice-backgrounds', 'public');
            $template->update(['background_path' => $path]);
        }

        // ثابت‌های پیش‌فرض بر اساس تصویر فاکتور (می‌تونی دستی اضافه کنی)
        $fixedKeys = [
            ['key' => 'invoice_id', 'type' => 'fixed', 'value' => 'شماره فاکتور', 'x' => 100, 'y' => 20],
            ['key' => 'receiver_name', 'type' => 'fixed', 'value' => 'نام خریدار', 'x' => 20, 'y' => 150],
            ['key' => 'purchase_date', 'type' => 'fixed', 'value' => 'تاریخ', 'x' => 20, 'y' => 150],
            ['key' => 'gold_price', 'type' => 'fixed', 'value' => 'قیمت روز طلا', 'x' => 20, 'y' => 150],
            ['key' => 'total_label', 'type' => 'fixed', 'value' => 'جمع کل', 'x' => 300, 'y' => 400],
            ['key' => 'notes_label', 'type' => 'fixed', 'value' => 'یادداشت', 'x' => 20, 'y' => 500],
            ['key' => 'notes_label2', 'type' => 'fixed', 'value' => 'یادداشت', 'x' => 20, 'y' => 500],
            ['key' => 'invoice_number', 'type' => 'fixed', 'value' => 'شماره سفارش', 'x' => 20, 'y' => 500],
            ['key' => 'previous_purchase_count', 'type' => 'fixed', 'value' => 'تعداد خرید قبلی', 'x' => 20, 'y' => 500],
            ['key' => 'purchase_type', 'type' => 'fixed', 'value' => 'نوع سفارش', 'x' => 20, 'y' => 500],
            ['key' => 'purchase_date2', 'type' => 'fixed', 'value' => 'تاریخ سفارش', 'x' => 20, 'y' => 500],
            ['key' => 'receiver_name2', 'type' => 'fixed', 'value' => 'نام', 'x' => 20, 'y' => 500],
            ['key' => 'receiver_phone', 'type' => 'fixed', 'value' => 'شماره تماس', 'x' => 20, 'y' => 500],
            ['key' => 'postal_code', 'type' => 'fixed', 'value' => 'کد پستی', 'x' => 20, 'y' => 500],
            ['key' => 'address', 'type' => 'fixed', 'value' => 'آدرس', 'x' => 20, 'y' => 500],
        ];

        foreach ($fixedKeys as $fixed) {
            InvoicePosition::create(array_merge($fixed, ['template_id' => $template->id]));
        }

        return response()->json();
    }

    public function edit($id) {
        $template = InvoiceTemplate::findOrFail($id);
        return view('admin.invoice-templates.edit', compact('template'));
    }

    public function update(Request $request, $id) {
        $template = InvoiceTemplate::findOrFail($id);
        $updated = $request->all(); // array of {id, x, y}
        foreach ($updated as $update) {
            InvoicePosition::find($update['id'])->update(['x' => $update['x'], 'y' => $update['y']]);
        }
        return response()->json(['success' => true]);
    }

    public function destroy()
    {
        
    }


    public function table()
    {
        $templates = InvoiceTemplate::query()->latest()->paginate();


        // Loop through users and render the Blade string for each
        foreach ($templates as $template) {
            $slotContent .= Blade::render(
                <<<'BLADE'
             <x-modal.destroy id="modal-destroy-{{$template->id}}" title="حذف بنر" action="{{route('templates.destroy', $template->id)}}" title="{{$template->title}}" />

                <x-modal.update id="modal-edit-{{$template->id}}" title="ویرایش بنر" action="{{route('templates.update',$template->id)}}" >
                    <x-form.input title="نام"  name="name" :value="$template->name" />
                    <x-form.file-input title="فایل بک گراند" name="background" />
                </x-modal.update>
            BLADE,
                ['template' => $template, 'permissions']
            );
        }

        return view('components.table', [
            'id' => 'templates-table',
            'columns' => [
                ['label' => 'نام', 'key' => 'name', 'type' => 'text'],
            ],
            'url' => route('table.templates'),
            'items' => $templates,
            'actions' => [
                ['label' => 'ویرایش', 'type' => 'modal-edit'],
                ['label' => 'حذف', 'type' => 'modal-destroy']
            ],
            'slot' => $slotContent
        ]);
    }
}
