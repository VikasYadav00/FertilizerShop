// ============================================================
// script.js — IFFDC Shop Frontend Logic (DB-integrated version)
// Works with PRODUCTS_DATA injected from index.php
// ============================================================

let cart    = [];
let allProds = [];
const ITEMS_PER_PAGE = 9;
let currentPage      = 1;
let currentFilter    = 'All';
let searchQuery      = '';

// ---- Initialize on DOM Load ----
document.addEventListener('DOMContentLoaded', () => {
    // PRODUCTS_DATA is injected by index.php from MySQL
    if (typeof PRODUCTS_DATA !== 'undefined') {
        allProds = PRODUCTS_DATA;
    }

    loadProducts();

    // Filter buttons
    const filterContainer = document.getElementById('filters');
    if (filterContainer) {
        filterContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('filter-btn')) {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                currentFilter = e.target.dataset.cat;
                currentPage   = 1;
                loadProducts();
            }
        });
    }

    // Search
    const searchInput = document.getElementById('product-search');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            searchQuery = searchInput.value.toLowerCase().trim();
            currentPage = 1;
            loadProducts();
        });
    }

    // Cart toggle
    const cartBtn  = document.getElementById('cart-btn');
    const closeBtn = document.getElementById('close-cart');
    const overlay  = document.getElementById('cart-overlay');
    if (cartBtn)  cartBtn.addEventListener('click',  () => toggleCart(true));
    if (closeBtn) closeBtn.addEventListener('click', () => toggleCart(false));
    if (overlay)  overlay.addEventListener('click',  () => toggleCart(false));
});

// ---- Product Loading ----
function loadProducts() {
    const grid = document.getElementById('product-list');
    if (!grid) return;

    // Filter + search
    let filtered = allProds.filter(p => {
        const matchCat    = currentFilter === 'All' || p.category_name === currentFilter;
        const matchSearch = !searchQuery || p.name.toLowerCase().includes(searchQuery) || (p.description && p.description.toLowerCase().includes(searchQuery));
        return matchCat && matchSearch;
    });

    // Pagination
    const totalPages = Math.ceil(filtered.length / ITEMS_PER_PAGE) || 1;
    const start      = (currentPage - 1) * ITEMS_PER_PAGE;
    const paged      = filtered.slice(start, start + ITEMS_PER_PAGE);

    if (paged.length === 0) {
        grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:4rem;color:#9ca3af;">
            <i class="fas fa-seedling" style="font-size:3rem;display:block;margin-bottom:1rem;"></i>
            <p>No products found.</p></div>`;
        document.getElementById('pagination').innerHTML = '';
        return;
    }

    grid.innerHTML = paged.map(p => buildProductCard(p)).join('');
    buildPagination(totalPages);
}

function buildProductCard(p) {
    const inStock      = p.stock_status === 'in_stock' && parseInt(p.stock_qty) > 0;
    const hasDiscount  = parseFloat(p.discount) > 0;
    const offerPrice   = parseFloat(p.offer_price) || parseFloat(p.price);
    const price        = parseFloat(p.price);
    const imgSrc       = (p.image && p.image.startsWith('prod_')) ? `uploads/${p.image}` : `images/${p.image}`;

    return `<div class="card" data-id="${p.id}">
        <div class="card-img-wrap">
            <img src="${imgSrc}" alt="${escHtml(p.name)}" onerror="this.src='https://placehold.co/200x200/dcfce7/15803d?text=No+Image'" style="width:100%;height:200px;object-fit:contain;background:#fdfdfd;padding:15px;box-sizing:border-box;">
            ${hasDiscount ? `<span class="discount-badge">${p.discount}% OFF</span>` : ''}
            ${!inStock ? `<div class="out-of-stock-overlay"><span>Out of Stock</span></div>` : ''}
        </div>
        <div class="card-content">
            <small style="color:#15803d;font-weight:bold;">${escHtml(p.category_name)}</small>
            <h3 style="margin:.4rem 0;">${escHtml(p.name)}</h3>
            <p style="color:#666;font-size:.85rem;margin:.3rem 0;">${escHtml(p.unit)}</p>
            ${p.description ? `<p style="color:#4b5563;font-size:.8rem;margin:.3rem 0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.4;" title="${escHtml(p.description)}">${escHtml(p.description)}</p>` : ''}
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:.8rem;">
                <div>
                    ${hasDiscount ? `<span class="price-original">₹${price.toFixed(0)}</span>` : ''}
                    <span class="price-offer">₹${offerPrice.toFixed(0)}</span>
                </div>
                <span style="font-size:.8rem;color:${inStock?'#2563eb':'#dc2626'};">
                    ${inStock ? '● In Stock' : '● Out of Stock'}
                </span>
            </div>
            ${inStock
                ? `<button class="add-btn" onclick="addToCart(${p.id})" style="width:100%;background:#15803d;color:white;border:none;padding:10px;border-radius:8px;margin-top:15px;cursor:pointer;font-weight:bold;transition:.3s;" onmouseover="this.style.background='#14532d'" onmouseout="this.style.background='#15803d'">
                     <i class="fas fa-cart-plus"></i> Add to Cart</button>`
                : `<button disabled style="width:100%;background:#e5e7eb;color:#9ca3af;border:none;padding:10px;border-radius:8px;margin-top:15px;cursor:not-allowed;font-weight:bold;">Out of Stock</button>`
            }
        </div>
    </div>`;
}

function buildPagination(totalPages) {
    const pg = document.getElementById('pagination');
    if (!pg || totalPages <= 1) { if(pg) pg.innerHTML=''; return; }

    let html = '';
    for (let i = 1; i <= totalPages; i++) {
        html += `<button onclick="goToPage(${i})" style="padding:8px 16px;border:1.5px solid ${i===currentPage?'#15803d':'#e5e7eb'};background:${i===currentPage?'#15803d':'white'};color:${i===currentPage?'white':'#374151'};border-radius:8px;cursor:pointer;font-weight:600;transition:.2s;">${i}</button>`;
    }
    pg.innerHTML = html;
}

function goToPage(page) {
    currentPage = page;
    loadProducts();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ---- Cart Logic ----
function addToCart(productId) {
    const product = allProds.find(p => parseInt(p.id) === parseInt(productId));
    if (!product) return;

    const existing = cart.find(item => parseInt(item.id) === parseInt(productId));
    if (existing) {
        existing.qty = (existing.qty || 1) + 1;
    } else {
        cart.push({ ...product, qty: 1 });
    }

    updateCartUI();
    toggleCart(true);

    // Animate button briefly
    const btn = document.querySelector(`[data-id="${productId}"] .add-btn`);
    if (btn) {
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Added!';
        btn.style.background = '#166534';
        setTimeout(() => { btn.innerHTML = original; btn.style.background = '#15803d'; }, 1000);
    }
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartUI();
}

function changeQty(index, delta) {
    cart[index].qty = Math.max(1, (cart[index].qty || 1) + delta);
    updateCartUI();
}

function updateCartUI() {
    const countLabel = document.getElementById('cart-count');
    const totalQty   = cart.reduce((s, i) => s + (i.qty || 1), 0);
    if (countLabel) countLabel.innerText = totalQty;

    const container    = document.getElementById('cart-items-container');
    const totalDisplay = document.getElementById('cart-total');
    if (!container) return;

    if (cart.length === 0) {
        container.innerHTML = '<div style="text-align:center;padding:3rem;color:#9ca3af;"><i class="fas fa-shopping-cart" style="font-size:2.5rem;display:block;margin-bottom:1rem;"></i><p>Your cart is empty.</p></div>';
        if (totalDisplay) totalDisplay.innerText = '₹0';
        return;
    }

    let html  = '';
    let total = 0;
    cart.forEach((item, index) => {
        const price = parseFloat(item.offer_price) || parseFloat(item.price);
        const qty   = item.qty || 1;
        const sub   = price * qty;
        total += sub;
        html += `<div style="display:flex;align-items:center;gap:10px;margin-bottom:15px;border-bottom:1px solid #f3f4f6;padding-bottom:15px;">
            <img src="${(item.image && item.image.startsWith('prod_')) ? 'uploads/' : 'images/'}${item.image}" width="50" height="50" style="object-fit:cover;border-radius:8px;background:#f9fafb;" onerror="this.src='https://placehold.co/50x50/dcfce7/15803d?text=IMG'">
            <div style="flex:1;">
                <h4 style="margin:0;font-size:.85rem;line-height:1.3;">${escHtml(item.name)}</h4>
                <p style="margin:3px 0;color:#666;font-size:.78rem;">${escHtml(item.unit)}</p>
                <div style="display:flex;align-items:center;gap:6px;margin-top:6px;">
                    <button onclick="changeQty(${index},-1)" style="background:#f3f4f6;border:none;width:26px;height:26px;border-radius:6px;cursor:pointer;font-weight:bold;">−</button>
                    <span style="font-weight:bold;min-width:20px;text-align:center;">${qty}</span>
                    <button onclick="changeQty(${index},1)" style="background:#f3f4f6;border:none;width:26px;height:26px;border-radius:6px;cursor:pointer;font-weight:bold;">+</button>
                    <span style="font-size:.85rem;color:#15803d;font-weight:bold;margin-left:5px;">₹${sub.toFixed(0)}</span>
                </div>
            </div>
            <button onclick="removeFromCart(${index})" style="background:none;border:none;color:#dc2626;cursor:pointer;font-size:1.2rem;padding:4px;">×</button>
        </div>`;
    });

    container.innerHTML  = html;
    if (totalDisplay) totalDisplay.innerText = `₹${total.toFixed(0)}`;
}

// ---- Cart Sidebar Toggle ----
function toggleCart(isOpen) {
    const sidebar = document.getElementById('cart-sidebar');
    const overlay = document.getElementById('cart-overlay');
    if (sidebar && overlay) {
        sidebar.classList.toggle('active', isOpen);
        overlay.classList.toggle('active', isOpen);
    }
}

// ---- WhatsApp Checkout ----
function checkoutWhatsApp() {
    if (cart.length === 0) { alert('Please add items to your cart first.'); return; }
    let message = 'Hello IFFDC Maharajpur! I want to order:\n\n';
    let total   = 0;
    cart.forEach((item, i) => {
        const price = parseFloat(item.offer_price) || parseFloat(item.price);
        const qty   = item.qty || 1;
        const sub   = price * qty;
        total += sub;
        message += `${i + 1}. ${item.name} (${item.unit}) × ${qty} = ₹${sub.toFixed(0)}\n`;
    });
    message += `\nTotal Amount: ₹${total.toFixed(0)}`;
    window.open(`https://wa.me/${SHOP_PHONE}?text=${encodeURIComponent(message)}`, '_blank');
}

// ---- Place Order (AJAX) ----
function checkoutOrder() {
    if (!IS_LOGGED_IN) { window.location.href = 'auth/login.php'; return; }
    if (cart.length === 0) { alert('Please add items to your cart first.'); return; }

    const name    = document.getElementById('checkout-name') ? document.getElementById('checkout-name').value.trim() : '';
    const phone   = document.getElementById('checkout-phone') ? document.getElementById('checkout-phone').value.trim() : '';
    const address = document.getElementById('checkout-address') ? document.getElementById('checkout-address').value.trim() : '';
    const pincode = document.getElementById('checkout-pincode') ? document.getElementById('checkout-pincode').value.trim() : '';
    const paymentMethodEl = document.querySelector('input[name="payment_method"]:checked');
    const payment_method = paymentMethodEl ? paymentMethodEl.value : 'cod';

    if (!name) { alert('Please enter your full name.'); return; }
    if (!phone || phone.length !== 10 || isNaN(phone)) { alert('Please enter a valid 10-digit phone number.'); return; }
    if (!address) { alert('Please enter your delivery address.'); return; }
    if (!pincode || pincode.length !== 6 || isNaN(pincode)) { alert('Please enter a valid 6-digit pincode.'); return; }

    const btn = document.querySelector('.checkout-btn');
    if (btn) btn.innerText = 'Processing...';

    fetch('api/place-order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cart, name, phone, address, pincode, payment_method })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (payment_method === 'online') {
                const options = {
                    "key": "rzp_test_YourTestKeyHere", // <-- REPLACE WITH YOUR RAZORPAY KEY
                    "amount": data.amount_paise,
                    "currency": "INR",
                    "name": "IFFDC Maharajpur",
                    "description": "Order #" + data.order_number,
                    "handler": function (response) {
                        // In a real app, verify signature on backend here.
                        // We will call a simple success endpoint.
                        fetch('api/verify-payment.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ order_id: data.order_id, payment_id: response.razorpay_payment_id })
                        }).then(r=>r.json()).then(res=>{
                            cart = []; updateCartUI(); toggleCart(false);
                            alert(`✅ Payment successful! Order #${data.order_number} placed.\nThank you!`);
                            window.location.href = 'customer/orders.php';
                        });
                    },
                    "prefill": { "name": name, "contact": phone },
                    "theme": { "color": "#15803d" }
                };
                const rzp1 = new Razorpay(options);
                rzp1.on('payment.failed', function (response){
                    alert("Payment failed: " + response.error.description);
                });
                rzp1.open();
                if (btn) btn.innerHTML = '<i class="fas fa-check-circle"></i> Place Order';
            } else {
                cart = [];
                updateCartUI();
                toggleCart(false);
                alert(`✅ Order placed successfully! (Cash on Delivery)\nOrder #${data.order_number}\n\nThank you for shopping with IFFDC Maharajpur!`);
                window.location.href = 'customer/orders.php';
            }
        } else {
            alert('Error: ' + (data.message || 'Could not place order.'));
            if (btn) btn.innerHTML = '<i class="fas fa-check-circle"></i> Place Order';
        }
    })
    .catch(() => alert('Network error. Please try again.'));
}

// ---- HTML Escape ----
function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}