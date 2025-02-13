// Initialize cart data
let cart = [];

// AJAX function to interact with server-side PHP for database actions
function ajaxRequest(data, callback) {
    const xhr = new XMLHttpRequest();
    
    // Update the URL to be relative to the web root
    xhr.open('POST', 'cartHandler.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    callback(response);
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    console.log('Raw Response:', xhr.responseText);
                }
            } else {
                console.error('HTTP Error:', xhr.status);
                console.log('Response Text:', xhr.responseText);
            }
        }
    };
    
    xhr.onerror = function(e) {
        console.error('XHR Error:', e);
    };
    
    xhr.send(JSON.stringify(data));
}

// Purchase function to add item to cart in database
function purchase(imageURL, productName, price) {
    console.log(`Purchasing ${productName} for $${price}`);

    // Show alert that item was added
    alert(productName + ' has been added to your cart!');

    // Prepare data for server
    const productData = {
        action: 'add',
        image: imageURL,
        name: productName,
        price: price,
        quantity: 1,
        category: "Phones" // Replace with actual product category
    };

    // Send request to add product to the database
    ajaxRequest(productData, function(response) {
        if (response.success) {
            updateCartUI();  // Refresh cart display after update
        } else {
            console.error('Error adding item to cart:', response.error);
        }
    });
}

// Function to update the cart UI
function updateCartUI() {
    // Send request to get current cart items
    ajaxRequest({ action: 'fetch' }, function(response) {
        if (response.success) {
            cart = response.cartItems;
            const cartItemsElement = document.querySelector('.cart-items');
            cartItemsElement.innerHTML = '';

            let subtotal = 0;
            let shipping = 10;

            cart.forEach((item, index) => {
                const cartItem = document.createElement('div');
                cartItem.classList.add('row', 'mb-3');
                cartItem.innerHTML = `
                    <div class="col-md-3">
                        <img src="${item.image}" class="img-fluid" alt="${item.name}">
                    </div>
                    <div class="col-md-6">
                        <h6>${item.name}</h6>
                        <p class="text-muted">${item.category}</p>
                        <div class="input-group" style="width: 120px;">
                            <button class="btn btn-outline-secondary cart-quantity-btn" data-index="${index}" type="button">-</button>
                            <input type="number" class="form-control text-center cart-quantity" data-index="${index}" value="${item.quantity}">
                            <button class="btn btn-outline-secondary cart-quantity-btn" data-index="${index}" type="button">+</button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <h6 class="text-end">$${(item.price * item.quantity).toFixed(2)}</h6>
                        <button class="btn btn-sm btn-outline-danger float-end cart-remove" data-index="${index}">Remove</button>
                    </div>
                `;
                cartItemsElement.appendChild(cartItem);

                subtotal += item.price * item.quantity;
            });

            document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
            document.getElementById('shipping').textContent = `$${shipping.toFixed(2)}`;
            document.getElementById('total').textContent = `$${(subtotal + shipping).toFixed(2)}`;

            attachEventListeners();  // Reattach events after UI update
        } else {
            console.error('Error fetching cart items:', response.error);
        }
    });
}

// Helper function to attach event listeners
function attachEventListeners() {
    const quantityInputs = document.querySelectorAll('.cart-quantity');
    const removeButtons = document.querySelectorAll('.cart-remove');
    const quantityButtons = document.querySelectorAll('.cart-quantity-btn');

    quantityInputs.forEach(input => {
        input.addEventListener('change', () => {
            const index = parseInt(input.dataset.index);
            updateQuantity(index, parseInt(input.value));
        });
    });

    removeButtons.forEach(button => {
        button.addEventListener('click', () => {
            const index = parseInt(button.dataset.index);
            removeItem(index);
        });
    });

    quantityButtons.forEach(button => {
        button.addEventListener('click', (event) => {
            const index = parseInt(button.dataset.index);
            const input = document.querySelector(`input[data-index="${index}"]`);
            let newQuantity = cart[index].quantity + (event.target.textContent === "+" ? 1 : -1);
            if (newQuantity > 0) {
                updateQuantity(index, newQuantity);
                input.value = newQuantity;
            }
        });
    });
}

// Function to update item quantity in the database
function updateQuantity(index, quantity) {
    const item = cart[index];
    ajaxRequest({ action: 'update', id: item.id, quantity: quantity }, function(response) {
        if (response.success) {
            updateCartUI();
        } else {
            console.error('Error updating quantity:', response.error);
        }
    });
}

// Function to remove item from the cart in the database
function removeItem(index) {
    const item = cart[index];
    ajaxRequest({ action: 'remove', id: item.id }, function(response) {
        if (response.success) {
            updateCartUI();
        } else {
            console.error('Error removing item:', response.error);
        }
    });
}

// Initialize cart UI on page load
document.addEventListener('DOMContentLoaded', updateCartUI);
