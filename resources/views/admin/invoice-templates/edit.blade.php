<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <script src="{{ asset('pdfEditor/pdf.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/interactjs@1/dist/interact.min.js"></script>
    <style>
        body { margin: 0; padding: 0; }
        #editor-container {
            position: relative;
            width: 210mm; /* Exact A4 width */
            height: 297mm; /* Exact A4 height */
            margin: auto;
            overflow: hidden; /* Prevent overflow */
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
    </style>
    <title>editor</title>
</head>
<body>
<div id="editor-container" style="position: relative; margin: auto;">
    @if (str_ends_with($template->background_path, '.pdf'))
        <canvas id="pdf-canvas"></canvas>
    @else
        <img src="{{ Storage::url($template->background_path) }}" id="bg-image" style="width: 100%; height: 100%;">
    @endif
    <div id="overlay-container" style="position: absolute; top: 0; left: 0;"></div>
</div>

<button id="save-btn">ذخیره موقعیت‌ها</button>

<script>
    @if (str_ends_with($template->background_path, '.pdf'))
    const url = '{{ Storage::url($template->background_path) }}';
    pdfjsLib.getDocument(url).promise.then(pdf => {
        pdf.getPage(1).then(page => {
            const viewport = page.getViewport({ scale: 1.0 }); // Adjusted scale to fit A4
            const canvas = document.getElementById('pdf-canvas');
            const container = document.getElementById('editor-container');
            const overlay = document.getElementById('overlay-container');

            // match sizes to A4, adjusting scale if needed
            const a4WidthMm = 210; // mm
            const a4HeightMm = 297; // mm
            const dpi = 96; // standard screen DPI
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

            // render PDF
            page.render({ canvasContext: canvas.getContext('2d'), viewport: scaledViewport });

            // now add positions
            addPositions();
        });
    });
    @else
    addPositions(); // if image background
    @endif

    function addPositions() {
        const positions = @json($template->positions->where('type', 'fixed'));
        positions.forEach(pos => {
            const elem = document.createElement('div');
            elem.className = 'draggable-fixed';
            elem.textContent = pos.value || pos.key;
            elem.style.position = 'absolute';
            elem.style.left = `${pos.x}px`;
            elem.style.top = `${pos.y}px`;
            elem.setAttribute('data-id', pos.id);
            document.getElementById('overlay-container').appendChild(elem);

            interact(elem).draggable({
                onmove: event => {
                    const x = (parseFloat(event.target.style.left) + event.dx);
                    const y = (parseFloat(event.target.style.top) + event.dy);
                    event.target.style.left = `${x}px`;
                    event.target.style.top = `${y}px`;
                }
            });
        });
    }

    // ذخیره با AJAX
    document.getElementById('save-btn').addEventListener('click', () => {
        const updatedPositions = [];
        document.querySelectorAll('.draggable-fixed').forEach(elem => {
            updatedPositions.push({
                id: elem.getAttribute('data-id'),
                x: parseFloat(elem.style.left),
                y: parseFloat(elem.style.top)
            });
        });
        fetch('{{ route('invoice_templates.update', $template->id) }}', {
            method: 'PUT',
            body: JSON.stringify(updatedPositions),
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(response => response.json()).then(data => alert('ذخیره شد'));
    });
</script>

<style>
    .draggable-fixed {
        background: rgba(0, 255, 0, 0.3);
        padding: 5px;
        border: 1px dashed green;
        cursor: move;
        direction: rtl;
    }
</style>
</body>
</html>