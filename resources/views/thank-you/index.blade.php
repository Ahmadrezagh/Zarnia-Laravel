<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تشکر از خرید - زرنیا</title>
    <link rel="stylesheet" href="{{ asset('thank-you/styles.css') }}">
    <script>
    !function (t, e, n) {
        t.yektanetAnalyticsObject = n, t[n] = t[n] || function () {
            t[n].q.push(arguments)
        }, t[n].q = t[n].q || [];
        var a = new Date, r = a.getFullYear().toString() + "0" + a.getMonth() + "0" + a.getDate() + "0" + a.getHours(),
            c = e.getElementsByTagName("script")[0], s = e.createElement("script");
        s.id = "ua-script-8ByW8wQG"; s.dataset.analyticsobject = n;
        s.async = 1; s.type = "text/javascript";
        s.src = "https://cdn.yektanet.com/rg_woebegone/scripts_v3/8ByW8wQG/rg.complete.js?v=" + r, c.parentNode.insertBefore(s, c)
    }(window, document, "yektanet");
</script>
</head>
<body>
<div class="thank-you-container">
    <div class="thank-you-content">
        <div class="thank-you-header">
            <div class="congratulations">مبارکتون باشه!</div>
            <div class="shipping-info" id="shippingInfo">
                اگر ارسال با پست رو انتخاب کرده باشید فردا با پست ویژه ارسال میشه و معمولا 2 تا 3 روز دیگه بسته بهتون میرسه
            </div>
        </div>

        <div class="order-details">
            <div class="order-details-list">
                <div class="detail-row">
                    <span class="label">شماره سفارش</span>
                    <span class="dots">............................</span>
                    <span class="value" id="orderNumber">{{ $order->id }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">تاریخ</span>
                    <span class="dots">............................</span>
                    <span class="value" id="orderDate">{{ \Morilog\Jalali\Jalalian::forge($order->created_at)->format('Y/m/d') }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">مبلغ نهایی</span>
                    <span class="dots">............................</span>
                    <span class="value" id="finalAmount">{{ number_format($order->final_amount) }} تومان</span>
                </div>
                <div class="detail-row">
                    <span class="label">روش پرداخت</span>
                    <span class="dots">............................</span>
                    <span class="value" id="paymentMethod">{{ $order->gateway->title }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">وضعیت</span>
                    <span class="dots">............................</span>
                    <span class="value" id="orderStatus">{{ $order->persianStatus }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">روش ارسال</span>
                    <span class="dots">............................</span>
                    <span class="value" id="shippingMethod">{{ $order->shippingName }}</span>
                </div>
            </div>

            <hr class="divider">

            <div class="action-buttons">
                <button class="download-btn" onclick="openUrl('{{route('order.print',$order->id)}}')">
                    <svg width="20" height="20" fill="none" viewBox="0 0 20 20">
                        <path d="M10 3v10m0 0l-3-3m3 3l3-3" stroke="#bca27b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <rect x="3" y="15" width="14" height="2" rx="1" fill="#bca27b"/>
                    </svg>
                    دانلود فاکتور
                </button>
                <button class="complete-btn" onclick="openUrl('https://zarniagoldgallery.ir/profile')">تکمیل خرید</button>
            </div>
        </div>
    </div>
</div>

<script src="script.js"></script>
<script>
    function openUrl(url){
        window.location = url;
    }
    // Function to convert English numbers to Persian numbers
    function toPersianNumber(num) {
        if (num === undefined || num === null) return '';
        return num.toString().replace(/[0-9]/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[parseInt(d, 10)]);
    }

    // Function to force all numbers on the page to Persian
    function forcePersianNumbers() {
        // Get all text nodes in the document
        const walker = document.createTreeWalker(
            document.body,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );

        let node;
        const textNodes = [];

        // Collect all text nodes
        while (node = walker.nextNode()) {
            textNodes.push(node);
        }

        // Process each text node
        textNodes.forEach(textNode => {
            const originalText = textNode.textContent;
            const persianText = originalText.replace(/[0-9]/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[parseInt(d, 10)]);

            if (originalText !== persianText) {
                textNode.textContent = persianText;
            }
        });

        // Also handle input fields and contenteditable elements
        const inputs = document.querySelectorAll('input, textarea, [contenteditable]');
        inputs.forEach(input => {
            if (input.value) {
                input.value = toPersianNumber(input.value);
            }
        });

        // Handle any dynamically added content
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === Node.TEXT_NODE) {
                            const originalText = node.textContent;
                            const persianText = originalText.replace(/[0-9]/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[parseInt(d, 10)]);
                            if (originalText !== persianText) {
                                node.textContent = persianText;
                            }
                        } else if (node.nodeType === Node.ELEMENT_NODE) {
                            // Process text nodes in the added element
                            const walker = document.createTreeWalker(
                                node,
                                NodeFilter.SHOW_TEXT,
                                null,
                                false
                            );

                            let textNode;
                            while (textNode = walker.nextNode()) {
                                const originalText = textNode.textContent;
                                const persianText = originalText.replace(/[0-9]/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[parseInt(d, 10)]);
                                if (originalText !== persianText) {
                                    textNode.textContent = persianText;
                                }
                            }
                        }
                    });
                }
            });
        });

        // Start observing
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Run the function when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        forcePersianNumbers();
    });

    // Also run it after a short delay to catch any dynamically loaded content
    setTimeout(forcePersianNumbers, 100);
</script>
</body>
</html>
