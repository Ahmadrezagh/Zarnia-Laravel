{{-- resources/views/pdf/print.blade.php --}}
        <!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فاکتور #{{ $order->id }}</title>
    <script src="{{ asset('pdfEditor/pdf.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js"></script>
    @php
        $containerWidth = '210mm';
        $containerHeight = '297mm';

    @endphp
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        #editor-container {
            position: relative;
            width: {{ $containerWidth }}px; /* A4 width in pixels */
            height: {{ $containerHeight }}px; /* A4 height in pixels */
            margin: auto;
            overflow: hidden;
            box-sizing: border-box;
        }
        #pdf-canvas, #bg-image {
            width: 100%;
            height: 100%;
            display: block;
        }
        .field {
            position: absolute;
            white-space: nowrap;
            direction: rtl;
        }
        .print-button {
            margin: 20px auto;
            display: block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-family: tahoma, sans-serif;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            #editor-container, #editor-container * {
                visibility: visible;
            }

            .print-button {
                display: none;
            }
            @page {
                size: A4;
                margin: 0;
            }
            html, body {
                height: auto;
                width: 100%;
            }
            #pdf-canvas{
                visibility: hidden;
                display: none;
            }
        }
    </style>
</head>
<body>
@php
    $template = \App\Models\InvoiceTemplate::with('positions')->latest()->first();
    $a4WidthMm = 210;
    $a4HeightMm = 297;
    $dpi = 96;
    $mmToPx = fn($mm) => $mm * $dpi / 25.4;
    $containerWidth = $mmToPx($a4WidthMm); // 793.7px
    $containerHeight = $mmToPx($a4HeightMm); // 1122.5px
    $map = [
        'invoice_id' => $order->id,
        'receiver_name' => $order->address->receiver_name ?? '',
        'receiver_name2' => $order->address->receiver_name ?? '',
        'purchase_date' => jdate($order->created_at)->format('Y/m/d'),
        'purchase_date2' => jdate($order->created_at)->format('Y/m/d'),
        'gold_price' => number_format(get_gold_price()/10),
        'total_label' => number_format($order->total_amount),
        'notes_label' => $order->note ?? '',
        'invoice_number' => $order->id ?? '',
        'previous_purchase_count' => \App\Models\Order::query()->where('user_id','=',$order->user_id)->where('id','!=',$order->id)->count(),
        'shipping' => $order->shipping->title ?? 'آنلاین',
        'receiver_phone' => $order->address->receiver_phone ?? '',
        'postal_code' => $order->address->postal_code ?? '',
        'address' => $order->address->address ?? '',
        'sum_of_prev_purchase' => \App\Models\Order::query()->where('user_id','=',$order->user_id)->where('id','!=',$order->id)->sum('final_amount'),


    ];
    foreach ($order->orderItems as $index => $orderItem){
        $idx = $index + 1;
        $map['product_'.$idx.'_image'] = "<img src='$orderItem->product->image' ></img>";
        $map['product_'.$idx.'_title'] = $orderItem->name;
        $map['product_'.$idx.'_count'] = $orderItem->count;
        $map['product_'.$idx.'_weight'] = $orderItem->product->weight;
        $map['product_'.$idx.'_weight2'] = $orderItem->product->weight;
        $map['product_'.$idx.'_ayar'] = '';
        $map['product_'.$idx.'_etiket'] = $orderItem->etiket;
        $map['product_'.$idx.'_price'] = $orderItem->price;
    }
@endphp

<div id="editor-container" style="position: relative; margin: auto; width: {{ $containerWidth }}px; height: {{ $containerHeight }}px;">
    @if (str_ends_with($template->background_path, '.pdf'))
        <canvas id="pdf-canvas"></canvas>
    @else
        <img src="{{ Storage::url($template->background_path) }}" id="bg-image" style="width: {{ $containerWidth }}px; height: {{ $containerHeight }}px;">
    @endif
    <div id="overlay-container" style="position: absolute; top: 0; left: 0; width: {{ $containerWidth }}px; height: {{ $containerHeight }}px;"></div>
    @foreach ($template->positions as $pos)
        <div class="field"
             style="
                    position: absolute;
                    right: {{ $containerWidth - $pos->x }}px;
                    top: {{ $pos->y }}px;
                    font-family: {{ $pos->font_family ?? 'tahoma' }};
                    font-size: {{ $pos->font_size ?? 14 }}px;
                    color: {{ $pos->color ?? '#000' }};
                    white-space: nowrap;
                    direction: rtl;
                    --x-pos: {{ $pos->x }}px;
                    --y-pos: {{ $pos->y }}px;
                    ">
            {{ $map[$pos->key] ?? $pos->value }}
        </div>
    @endforeach
</div>

<button class="print-button" onclick="printDiv('editor-container')">چاپ فاکتور</button>

@if (str_ends_with($template->background_path, '.pdf'))
    <script>
        const url = '{{ Storage::url($template->background_path) }}';
        pdfjsLib.getDocument(url).promise.then(pdf => {
            pdf.getPage(1).then(page => {
                const viewport = page.getViewport({ scale: 1.0 });
                const canvas = document.getElementById('pdf-canvas');
                const container = document.getElementById('editor-container');
                const overlay = document.getElementById('overlay-container');

                const a4WidthMm = 210;
                const a4HeightMm = 297;
                const dpi = 96;
                const mmToPx = mm => mm * dpi / 25.4;
                const targetWidthPx = mmToPx(a4WidthMm);
                const targetHeightPx = mmToPx(a4HeightMm);
                const scale = Math.min(targetWidthPx / viewport.width, targetHeightPx / viewport.height);

                const scaledViewport = page.getViewport({ scale: scale });
                canvas.height = scaledViewport.height;
                canvas.width = scaledViewport.width;
                container.style.width = targetWidthPx + 'px';
                container.style.height = targetHeightPx + 'px';
                overlay.style.width = targetWidthPx + 'px';
                overlay.style.height = targetHeightPx + 'px';

                page.render({ canvasContext: canvas.getContext('2d'), viewport: scaledViewport });
            });
        });
    </script>
@endif

<script>
    function toPersianDigits(str) {
        return str.toString().replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
    }

    document.addEventListener("DOMContentLoaded", () => {
        document.querySelectorAll("body *").forEach(el => {
            if (el.childNodes.length === 1 && el.childNodes[0].nodeType === 3) {
                el.textContent = toPersianDigits(el.textContent);
            }
        });
    });

    function printDiv(divId) {
        window.print();
    }
</script>
</body>
</html>