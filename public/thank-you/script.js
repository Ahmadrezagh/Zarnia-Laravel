// Persian Number Conversion Utility
function toPersianNumber(num) {
    if (num === undefined || num === null) return '';
    return num.toString().replace(/[0-9]/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[parseInt(d, 10)]);
}

// Sample Order Data
const sampleOrderData = {
    id: 123456,
    user: {
        name: 'احمد',
        last_name: 'رضایی',
        phone: '09123456789',
        email: 'ahmad@example.com'
    },
    address: {
        id: 1,
        receiver_name: 'احمد رضایی',
        receiver_phone: '09123456789',
        address: 'خیابان ولیعصر، پلاک 123',
        province: 'تهران',
        city: 'تهران',
        postal_code: '1234567890'
    },
    shipping: {
        id: 1,
        title: 'پست',
        price: 15000,
        times: [],
        image: ''
    },
    shipping_time: null,
    gateway: {
        id: 1,
        title: 'درگاه زرین پال',
        sub_title: 'پرداخت امن',
        image: ''
    },
    status: 'پرداخت شده',
    discount_code: 'WELCOME10',
    discount_percentage: 10,
    discount_price: 250000,
    total_amount: 2500000,
    final_amount: 2250000,
    paid_at: '2024-12-15T10:30:00Z',
    note: 'سفارش ویژه'
};

// Format currency in Persian
function formatCurrency(amount) {
    return toPersianNumber(amount.toLocaleString('fa-IR')) + ' تومان';
}

// Get current Persian date
function getCurrentPersianDate() {
    const now = new Date();
    const persianDate = new Intl.DateTimeFormat('fa-IR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    }).format(now);
    return toPersianNumber(persianDate.replace(/\//g, '/'));
}

// Update page with order data
function updatePageWithOrderData(orderData) {
    // Update order number
    document.getElementById('orderNumber').textContent = toPersianNumber(orderData.id.toString());
    
    // Update date
    document.getElementById('orderDate').textContent = getCurrentPersianDate();
    
    // Update final amount
    document.getElementById('finalAmount').textContent = formatCurrency(orderData.final_amount);
    
    // Update payment method
    document.getElementById('paymentMethod').textContent = orderData.gateway.title;
    
    // Update order status
    document.getElementById('orderStatus').textContent = orderData.status;
    
    // Update shipping method
    document.getElementById('shippingMethod').textContent = orderData.shipping.title;
    
    // Update shipping info based on method
    const shippingInfo = document.getElementById('shippingInfo');
    if (orderData.shipping.title === 'پست') {
        shippingInfo.textContent = 'اگر ارسال با پست رو انتخاب کرده باشید فردا با پست ویژه ارسال میشه و معمولا 2 تا 3 روز دیگه بسته بهتون میرسه';
    } else {
        shippingInfo.textContent = `سفارش شما با ${orderData.shipping.title} ارسال خواهد شد`;
    }
}

// Download invoice function
function downloadInvoice() {
    // In a real Laravel application, this would generate and download a PDF
    alert('در حال دانلود فاکتور...');
    console.log('Invoice download requested for order:', sampleOrderData.id);
}

// Complete purchase function
function completePurchase() {
    // In a real Laravel application, this would redirect to home page or dashboard
    alert('خرید شما با موفقیت تکمیل شد!');
    console.log('Purchase completed for order:', sampleOrderData.id);
}

// Initialize page when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if order data exists in localStorage/sessionStorage (for Laravel integration)
    let orderData = null;
    
    // Try to get order data from sessionStorage (if coming from Laravel)
    if (typeof Storage !== "undefined") {
        const storedOrderData = sessionStorage.getItem('orderData');
        if (storedOrderData) {
            try {
                orderData = JSON.parse(storedOrderData);
                // Clear the data after using it
                sessionStorage.removeItem('orderData');
            } catch (e) {
                console.error('Error parsing order data:', e);
                orderData = sampleOrderData;
            }
        } else {
            orderData = sampleOrderData;
        }
    } else {
        orderData = sampleOrderData;
    }
    
    // Update page with order data
    updatePageWithOrderData(orderData);
    
    console.log('Thank you page initialized with order data:', orderData);
});

// Export functions for Laravel integration
window.ThankYouPage = {
    updatePageWithOrderData,
    toPersianNumber,
    formatCurrency,
    getCurrentPersianDate,
    downloadInvoice,
    completePurchase
};
