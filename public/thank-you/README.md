# Thank You Page - Standalone HTML/CSS Version

This is a standalone HTML/CSS version of the Zarnia Gold Gallery thank-you page that can be easily integrated into a Laravel application.

## Files Structure

```
thank-you-standalone/
├── index.html          # Main HTML file
├── styles.css          # CSS styles
├── script.js           # JavaScript functionality
├── fonts/              # Persian font files
│   └── IRANSansWeb.ttf
└── README.md           # This file
```

## Features

- **Responsive Design**: Works on desktop, tablet, and mobile devices
- **Persian Language Support**: RTL layout with Persian font
- **Sample Data**: Includes realistic sample order data
- **Interactive Elements**: Download invoice and complete purchase buttons
- **Laravel Ready**: Easy integration with Laravel backend

## Laravel Integration

### 1. Copy Files to Laravel

Copy all files to your Laravel `public` directory or create a dedicated folder:

```bash
# Copy to Laravel public directory
cp -r thank-you-standalone/ /path/to/laravel/public/thank-you/
```

### 2. Create Laravel Route

Add this route to your `web.php`:

```php
Route::get('/thank-you/{orderId}', function($orderId) {
    // Get order data from database
    $order = Order::with(['user', 'address', 'shipping', 'gateway'])->find($orderId);
    
    if (!$order) {
        return redirect()->route('home');
    }
    
    // Pass order data to view
    return view('thank-you', compact('order'));
});
```

### 3. Create Laravel Blade Template

Create `resources/views/thank-you.blade.php`:

```php
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تشکر از خرید - زرنیا</title>
    <link rel="stylesheet" href="{{ asset('thank-you/styles.css') }}">
</head>
<body>
    <div class="thank-you-container">
        <div class="thank-you-content">
            <div class="thank-you-header">
                <div class="congratulations">مبارکتون باشه!</div>
                <div class="shipping-info" id="shippingInfo">
                    @if($order->shipping->title === 'پست')
                        اگر ارسال با پست رو انتخاب کرده باشید فردا با پست ویژه ارسال میشه و معمولا 2 تا 3 روز دیگه بسته بهتون میرسه
                    @else
                        سفارش شما با {{ $order->shipping->title }} ارسال خواهد شد
                    @endif
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
                        <span class="value" id="orderDate">{{ $order->created_at->format('Y/m/d') }}</span>
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
                        <span class="value" id="orderStatus">{{ $order->status }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">روش ارسال</span>
                        <span class="dots">............................</span>
                        <span class="value" id="shippingMethod">{{ $order->shipping->title }}</span>
                    </div>
                </div>
                
                <hr class="divider">
                
                <div class="action-buttons">
                    <a href="{{ route('invoice.download', $order->id) }}" class="download-btn">
                        <svg width="20" height="20" fill="none" viewBox="0 0 20 20">
                            <path d="M10 3v10m0 0l-3-3m3 3l3-3" stroke="#bca27b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <rect x="3" y="15" width="14" height="2" rx="1" fill="#bca27b"/>
                        </svg>
                        دانلود فاکتور
                    </a>
                    <a href="{{ route('home') }}" class="complete-btn">تکمیل خرید</a>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('thank-you/script.js') }}"></script>
</body>
</html>
```

### 4. Add Invoice Download Route

Add this route for invoice download:

```php
Route::get('/invoice/{orderId}', function($orderId) {
    $order = Order::find($orderId);
    
    if (!$order) {
        abort(404);
    }
    
    // Generate PDF invoice (using a package like barryvdh/laravel-dompdf)
    $pdf = PDF::loadView('invoice', compact('order'));
    
    return $pdf->download('invoice-' . $orderId . '.pdf');
})->name('invoice.download');
```

### 5. Customize for Your Laravel App

- Update the sample data in `script.js` to match your database structure
- Modify the CSS colors and styling to match your brand
- Add your Laravel routes and controllers
- Implement proper invoice generation
- Add proper error handling and validation

## Sample Data Structure

The JavaScript file includes sample order data with the following structure:

```javascript
{
    id: 123456,
    user: { name, last_name, phone, email },
    address: { receiver_name, receiver_phone, address, province, city, postal_code },
    shipping: { title, price, times, image },
    gateway: { title, sub_title, image },
    status: "پرداخت شده",
    discount_code: "WELCOME10",
    discount_percentage: 10,
    discount_price: 250000,
    total_amount: 2500000,
    final_amount: 2250000,
    paid_at: "2024-12-15T10:30:00Z",
    note: "سفارش ویژه"
}
```

## Testing

To test the standalone version:

1. Open `index.html` in a web browser
2. The page should display with sample data
3. Test the responsive design by resizing the browser window
4. Test the button interactions

## Browser Support

- Chrome/Edge: Full support
- Firefox: Full support
- Safari: Full support
- Mobile browsers: Full support

## Notes

- The page uses Persian numbers and RTL layout
- All text is in Persian (Farsi)
- The design matches the original Next.js version
- Ready for Laravel integration with minimal modifications
