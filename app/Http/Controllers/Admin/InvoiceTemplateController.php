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
            ['key' => 'invoice_number', 'type' => 'fixed', 'value' => 'شماره سفارش', 'x' => 20, 'y' => 500],
            ['key' => 'previous_purchase_count', 'type' => 'fixed', 'value' => 'تعداد خرید قبلی', 'x' => 20, 'y' => 500],
            ['key' => 'shipping', 'type' => 'fixed', 'value' => 'نوع حمل', 'x' => 20, 'y' => 500],
            ['key' => 'purchase_date2', 'type' => 'fixed', 'value' => 'تاریخ سفارش', 'x' => 20, 'y' => 500],
            ['key' => 'receiver_name2', 'type' => 'fixed', 'value' => 'نام', 'x' => 20, 'y' => 500],
            ['key' => 'receiver_phone', 'type' => 'fixed', 'value' => 'شماره تماس', 'x' => 20, 'y' => 500],
            ['key' => 'postal_code', 'type' => 'fixed', 'value' => 'کد پستی', 'x' => 20, 'y' => 500],
            ['key' => 'address', 'type' => 'fixed', 'value' => 'آدرس', 'x' => 20, 'y' => 500],
            ['key' => 'sum_of_prev_purchase', 'type' => 'fixed', 'value' => 'مبلغ خرید قبلی', 'x' => 20, 'y' => 500],

            ['key' => 'product_1_image', 'type' => 'fixed', 'value' => 'تصویر محصول اول', 'x' => 20, 'y' => 500],
            ['key' => 'product_1_title', 'type' => 'fixed', 'value' => 'شرح محصول اول', 'x' => 20, 'y' => 500],
            ['key' => 'product_1_count', 'type' => 'fixed', 'value' => 'تعداد محصول اول', 'x' => 20, 'y' => 500],
            ['key' => 'product_1_weight', 'type' => 'fixed', 'value' => 'وزن محصول اول', 'x' => 20, 'y' => 500],
            ['key' => 'product_1_weight_2', 'type' => 'fixed', 'value' => 'وزن محصول اول', 'x' => 20, 'y' => 500],
            ['key' => 'product_1_ayar', 'type' => 'fixed', 'value' => 'عیار محصول اول', 'x' => 20, 'y' => 500],
            ['key' => 'product_1_etiket', 'type' => 'fixed', 'value' => 'اتیکت محصول اول', 'x' => 20, 'y' => 500],
            ['key' => 'product_1_price', 'type' => 'fixed', 'value' => 'قیمت محصول اول', 'x' => 20, 'y' => 500],

            ['key' => 'product_2_image', 'type' => 'fixed', 'value' => 'تصویر محصول دوم', 'x' => 20, 'y' => 500],
            ['key' => 'product_2_title', 'type' => 'fixed', 'value' => 'شرح محصول دوم', 'x' => 20, 'y' => 500],
            ['key' => 'product_2_count', 'type' => 'fixed', 'value' => 'تعداد محصول دوم', 'x' => 20, 'y' => 500],
            ['key' => 'product_2_weight', 'type' => 'fixed', 'value' => 'وزن محصول دوم', 'x' => 20, 'y' => 500],
            ['key' => 'product_2_weight_2', 'type' => 'fixed', 'value' => 'وزن محصول دوم', 'x' => 20, 'y' => 500],
            ['key' => 'product_2_ayar', 'type' => 'fixed', 'value' => 'عیار محصول دوم', 'x' => 20, 'y' => 500],
            ['key' => 'product_2_etiket', 'type' => 'fixed', 'value' => 'اتیکت محصول دوم', 'x' => 20, 'y' => 500],
            ['key' => 'product_2_price', 'type' => 'fixed', 'value' => 'قیمت محصول دوم', 'x' => 20, 'y' => 500],

            ['key' => 'product_3_image', 'type' => 'fixed', 'value' => 'تصویر محصول سوم', 'x' => 20, 'y' => 500],
            ['key' => 'product_3_title', 'type' => 'fixed', 'value' => 'شرح محصول سوم', 'x' => 20, 'y' => 500],
            ['key' => 'product_3_count', 'type' => 'fixed', 'value' => 'تعداد محصول سوم', 'x' => 20, 'y' => 500],
            ['key' => 'product_3_weight', 'type' => 'fixed', 'value' => 'وزن محصول سوم', 'x' => 20, 'y' => 500],
            ['key' => 'product_3_weight_2', 'type' => 'fixed', 'value' => 'وزن محصول سوم', 'x' => 20, 'y' => 500],
            ['key' => 'product_3_ayar', 'type' => 'fixed', 'value' => 'عیار محصول سوم', 'x' => 20, 'y' => 500],
            ['key' => 'product_3_etiket', 'type' => 'fixed', 'value' => 'اتیکت محصول سوم', 'x' => 20, 'y' => 500],
            ['key' => 'product_3_price', 'type' => 'fixed', 'value' => 'قیمت محصول سوم', 'x' => 20, 'y' => 500],

            ['key' => 'product_4_image', 'type' => 'fixed', 'value' => 'تصویر محصول چهارم', 'x' => 20, 'y' => 500],
            ['key' => 'product_4_title', 'type' => 'fixed', 'value' => 'شرح محصول چهارم', 'x' => 20, 'y' => 500],
            ['key' => 'product_4_count', 'type' => 'fixed', 'value' => 'تعداد محصول چهارم', 'x' => 20, 'y' => 500],
            ['key' => 'product_4_weight', 'type' => 'fixed', 'value' => 'وزن محصول چهارم', 'x' => 20, 'y' => 500],
            ['key' => 'product_4_weight_2', 'type' => 'fixed', 'value' => 'وزن محصول چهارم', 'x' => 20, 'y' => 500],
            ['key' => 'product_4_ayar', 'type' => 'fixed', 'value' => 'عیار محصول چهارم', 'x' => 20, 'y' => 500],
            ['key' => 'product_4_etiket', 'type' => 'fixed', 'value' => 'اتیکت محصول چهارم', 'x' => 20, 'y' => 500],
            ['key' => 'product_4_price', 'type' => 'fixed', 'value' => 'قیمت محصول چهارم', 'x' => 20, 'y' => 500],
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

    public function destroy($id)
    {
        $template = InvoiceTemplate::findOrFail($id);
        $template->delete();
        return response()->json(['success' => true]);
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
