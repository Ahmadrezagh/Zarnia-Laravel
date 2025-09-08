{{-- resources/views/pdf/print.blade.php --}}
        <!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فاکتور #{{ $order->id }}</title>
    <script src="{{ asset('pdfEditor/pdf.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js"></script>
    <style>
        body { margin: 0; padding: 0; }
        #editor-container {
            position: relative;
            width: 800px;
            height: 1120px; /* A4 تقریبی برای نمایش */
            margin: auto;
            overflow: hidden; /* Prevent overflow on screen */
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
                position: relative; /* Reset positioning for print */
            }
            #editor-container {
                width: 210mm !important; /* Exact A4 width */
                height: 297mm !important; /* Exact A4 height */
                margin: 0 auto;
                padding: 20px; /* Preserve existing padding */
                box-sizing: border-box; /* Ensure padding fits within size */
                overflow: hidden !important; /* Prevent overflow on print */
                transform-origin: top center;
                transform: scale(1); /* Fit exact size */
            }
            #pdf-canvas, #bg-image {
                width: 100% !important;
                height: 100% !important;
            }
            .field {
                position: absolute !important; /* Ensure fields retain absolute positioning */
                top: auto !important; /* Reset top for recalculation */
                left: auto !important; /* Reset left for recalculation */
                right: {{ $pos->x }}px !important; /* Adjust for RTL */
                top: {{ $pos->y }}px !important; /* Maintain original Y position */
                font-family: {{ $pos->font_family ?? 'tahoma' }} !important;
                font-size: {{ $pos->font_size ?? 12 }}px !important;
                color: {{ $pos->color ?? '#000' }} !important;
            }
            .print-button {
                display: none;
            }
            @page {
                size: A4;
                margin: 0;
            }
            html, body {
                height: 297mm;
                width: 210mm;
            }
        }
    </style>
</head>
<body>
@php
    $template = \App\Models\InvoiceTemplate::with('positions')->latest()->first();
    $map = [
        'invoice_id' => $order->id,
        'receiver_name' => $order->address->receiver_name ?? '',
        'purchase_date' => jdate($order->created_at)->format('Y/m/d'),
        'gold_price' => number_format($order->gold_price ?? 6640400),
        'total_label' => number_format($order->total_amount),
        'notes_label' => $order->notes ?? '',
        'invoice_number' => $order->number ?? '',
        'previous_purchase_count' => 1,
        'purchase_type' => $order->type ?? 'آنلاین',
        'receiver_phone' => $order->address->receiver_phone ?? '',
        'postal_code' => $order->address->postal_code ?? '',
        'address' => $order->address->address ?? '',
    ];
@endphp

<div id="editor-container" style="position: relative; margin: auto;" >
    {{-- پس‌زمینه --}}
    @if (str_ends_with($template->background_path, '.pdf'))
        <canvas id="pdf-canvas"></canvas>
    @else
        <img src="{{ Storage::url($template->background_path) }}" id="bg-image">
    @endif

    <div id="overlay-container" style="position: absolute; top: 0; left: 0;"></div>
    {{-- فیلدها --}}
    @foreach ($template->positions as $pos)
        <div class="field"
             style="
                    top: {{ $pos->y }}px;
                    left: {{ $pos->x }}px;
                    font-family: {{ $pos->font_family ?? 'tahoma' }};
                    font-size: {{ $pos->font_size ?? 12 }}px;
                    color: {{ $pos->color ?? '#000' }};
                 ">
            {{ $map[$pos->key] ?? $pos->value }}
        </div>
    @endforeach
</div>

<!-- Print Button -->
<button class="print-button" onclick="printDiv('editor-container')">چاپ فاکتور</button>

@if (str_ends_with($template->background_path, '.pdf'))
    <script>
        const url = '{{ Storage::url($template->background_path) }}';
        pdfjsLib.getDocument(url).promise.then(pdf => {
            pdf.getPage(1).then(page => {
                const viewport = page.getViewport({ scale: 1.5 });
                const canvas = document.getElementById('pdf-canvas');
                const container = document.getElementById('editor-container');
                const overlay = document.getElementById('overlay-container');

                // match sizes
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                container.style.width = viewport.width + 'px';
                container.style.height = viewport.height + 'px';
                overlay.style.width = viewport.width + 'px';
                overlay.style.height = viewport.height + 'px';

                // render PDF
                page.render({ canvasContext: canvas.getContext('2d'), viewport });
            });
        });
    </script>
@endif

<script>
    // تبدیل اعداد انگلیسی به فارسی
    function toPersianDigits(str) {
        return str.toString().replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
    }

    // بعد از لود صفحه همه متن‌ها رو چک کن
    document.addEventListener("DOMContentLoaded", () => {
        document.querySelectorAll("body *").forEach(el => {
            if (el.childNodes.length === 1 && el.childNodes[0].nodeType === 3) { // فقط متن خالص
                el.textContent = toPersianDigits(el.textContent);
            }
        });
    });

    // تابع چاپ
    function printDiv(divId) {
        window.print();
    }
</script>
</body>
</html>