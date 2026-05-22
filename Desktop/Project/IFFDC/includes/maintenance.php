<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Under Maintenance | IFFDC Maharajpur</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #14532d 0%, #15803d 50%, #166534 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .maintenance-card {
            text-align: center;
            padding: 3rem 2rem;
            max-width: 500px;
            width: 90%;
        }
        .icon-wrap {
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            animation: pulse 2s ease-in-out infinite;
        }
        .icon-wrap i { font-size: 2.5rem; }
        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255,255,255,0.3); }
            50% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(255,255,255,0); }
        }
        h1 { font-size: 2rem; margin-bottom: 1rem; }
        p { font-size: 1.1rem; line-height: 1.7; opacity: 0.9; margin-bottom: 2rem; }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            background: rgba(255,255,255,0.2);
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: 0.3s;
            backdrop-filter: blur(5px);
        }
        .back-link:hover { background: rgba(255,255,255,0.35); }
        .shop-name { font-size: 0.9rem; opacity: 0.7; margin-top: 2rem; }
    </style>
</head>
<body>
    <div class="maintenance-card">
        <div class="icon-wrap">
            <i class="fas fa-tools"></i>
        </div>
        <h1>Under Maintenance</h1>
        <p><?php echo htmlspecialchars(getSiteSetting($conn, 'maintenance_message') ?? 'Website is temporarily unavailable right now. Please visit again later.'); ?></p>
        <a href="tel:+91<?php echo getSiteSetting($conn, 'shop_phone') ?? '0000000000'; ?>" class="back-link">
            <i class="fas fa-phone"></i> Contact Us
        </a>
        <p class="shop-name">IFFDC Maharajpur — Krishak Seva Kendra</p>
    </div>
</body>
</html>
