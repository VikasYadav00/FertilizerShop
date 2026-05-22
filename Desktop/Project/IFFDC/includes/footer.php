<?php
// ============================================================
// includes/footer.php — Shared Footer
// ============================================================
$shopAddress = getSiteSetting($conn, 'shop_address') ?? 'Maharajpur, UP';
$shopEmail   = getSiteSetting($conn, 'shop_email')   ?? 'info@iffdc.com';
?>
    <!-- Cart Sidebar -->
    <div id="cart-sidebar" class="cart-sidebar">
        <div class="cart-header">
            <h2>Your Cart</h2>
            <button id="close-cart" class="close-btn">&times;</button>
        </div>
        <div id="cart-items-container" class="cart-items-container"></div>
        <div class="cart-footer">
            <div class="total-row">
                <span>Total Amount:</span>
                <span id="cart-total">₹0</span>
            </div>
            <?php if ($isLoggedIn): ?>
                <button class="checkout-btn" onclick="checkoutOrder()">
                    <i class="fas fa-check-circle"></i> Place Order
                </button>
                <button class="checkout-btn" onclick="checkoutWhatsApp()" style="background:#25d366;margin-top:10px;">
                    <i class="fab fa-whatsapp"></i> Order on WhatsApp
                </button>
            <?php else: ?>
                <button class="checkout-btn" onclick="window.location.href='auth/login.php'" style="background:#1d4ed8;">
                    <i class="fas fa-lock"></i> Login to Checkout
                </button>
            <?php endif; ?>
        </div>
    </div>
    <div id="cart-overlay" class="cart-overlay"></div>

    <footer>
        <div style="max-width:1200px;margin:auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:2rem;text-align:left;">
            <div>
                <h3 style="color:white;margin-bottom:0.5rem;">IFFDC Maharajpur</h3>
                <p style="margin:0;font-size:0.85rem;">Krishak Seva Kendra</p>
                <p style="font-size:0.85rem;margin-top:1rem;line-height:1.6;"><?php echo htmlspecialchars($shopAddress); ?></p>
            </div>
            <div>
                <h4 style="color:white;margin-bottom:1rem;">Quick Links</h4>
                <a href="index.php" style="display:block;color:#9ca3af;text-decoration:none;margin-bottom:0.5rem;font-size:0.9rem;">🏠 Home</a>
                <a href="auth/login.php" style="display:block;color:#9ca3af;text-decoration:none;margin-bottom:0.5rem;font-size:0.9rem;">🔐 Login</a>
                <a href="auth/register.php" style="display:block;color:#9ca3af;text-decoration:none;font-size:0.9rem;">📝 Register</a>
            </div>
            <div>
                <h4 style="color:white;margin-bottom:1rem;">Contact Us</h4>
                <p style="font-size:0.85rem;margin:0 0 0.5rem;">📧 <?php echo htmlspecialchars($shopEmail); ?></p>
                <p style="font-size:0.85rem;margin:0;">📞 +91 <?php echo $shopPhone ?? '0000000000'; ?></p>
            </div>
        </div>
        <hr style="border-color:#374151;margin:2rem 0;">
        <p style="text-align:center;margin:0;font-size:0.85rem;">© 2026 IFFDC Maharajpur. Designed by Vikas Yadav.</p>
    </footer>
</body>
</html>
