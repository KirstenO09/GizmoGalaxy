document.addEventListener('DOMContentLoaded', function() {
    // Generate a random order ID
    const orderId = 'ORD-' + Math.random().toString(36).substr(2, 9).toUpperCase();
    document.getElementById('orderId').textContent = orderId;

    // Get the order total from sessionStorage
    const orderTotal = sessionStorage.getItem('orderTotal') || '0.00';
    document.getElementById('orderTotal').textContent = `$${orderTotal}`;

    // Clear the sessionStorage
    sessionStorage.removeItem('orderTotal');
});



function emailReceipt() {
    // Get total amount or other order details you want to include
    const total = document.getElementById('orderTotal').textContent;
    
    // Create email content
    const subject = 'Your Order Receipt';
    const body = `Thank you for your order!\n\nOrder Total: ${total}\n\nBest regards,\nGizmo Galaxy`;
    
    // Open default email client
    window.location.href = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
}