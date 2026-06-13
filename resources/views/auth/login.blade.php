<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Maintenance | 404</title>
    
    <meta name="description" content="Halaman sedang dalam pemeliharaan (Under Maintenance). Kami akan segera kembali.">
    <meta name="theme-color" content="#facc15">
    
    <!-- Google Fonts: Fredoka untuk kesan ceria & Inter untuk teks paragraf -->
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #fffbeb;      /* Light yellow/cream */
            --text-main: #1e293b;     /* Slate 800 */
            --text-muted: #475569;    /* Slate 600 */
            --accent-yellow: #facc15; /* Yellow 400 */
            --accent-orange: #f97316; /* Orange 500 */
            --black: #0f172a;         /* Slate 900 */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Strip Garis Kuning-Hitam (Yellow Line) */
        .warning-strip {
            position: absolute;
            width: 100%;
            height: 28px;
            background: repeating-linear-gradient(
                -45deg,
                var(--accent-yellow),
                var(--accent-yellow) 40px,
                var(--black) 40px,
                var(--black) 80px
            );
            z-index: 10;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            animation: moveStripes 20s linear infinite;
        }

        .strip-top { top: 0; }
        .strip-bottom { bottom: 0; }

        @keyframes moveStripes {
            0% { background-position: 0 0; }
            100% { background-position: 1000px 0; }
        }

        /* Main Container - Bergaya Neobrutalism Ceria */
        .container {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 3.5rem 2rem;
            max-width: 650px;
            width: 90%;
            background: #ffffff;
            border: 4px solid var(--black);
            border-radius: 32px;
            box-shadow: 12px 12px 0px var(--black);
            animation: popIn 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        /* Area Grafik (Gears & Cone) */
        .graphics {
            position: relative;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        /* Gears (Roda Gigi) */
        .gear {
            position: absolute;
            fill: var(--text-muted);
            opacity: 0.15;
            z-index: 0;
        }

        .gear-1 {
            width: 150px;
            left: 20%;
            top: 10%;
            fill: var(--accent-orange);
            opacity: 0.25;
            animation: spin 10s linear infinite;
        }

        .gear-2 {
            width: 100px;
            right: 25%;
            top: 40%;
            animation: spin-reverse 7s linear infinite;
        }

        /* Cone (Kerucut Lalu Lintas) */
        .cone-wrapper {
            position: relative;
            z-index: 2;
            width: 160px;
            animation: bounce 2s ease-in-out infinite;
            transform-origin: bottom center;
        }

        .cone-shadow {
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 110px;
            height: 25px;
            background: rgba(0,0,0,0.15);
            border-radius: 50%;
            z-index: 1;
            animation: shadowPulse 2s ease-in-out infinite;
        }

        /* Typography */
        .error-code {
            font-family: 'Fredoka', sans-serif;
            font-size: 5rem;
            font-weight: 700;
            color: var(--accent-yellow);
            text-shadow: 4px 4px 0px var(--black), -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;
            margin-bottom: 0.5rem;
            line-height: 1;
            letter-spacing: 2px;
            transform: rotate(-3deg);
            display: inline-block;
        }

        h1 {
            font-family: 'Fredoka', sans-serif;
            font-size: 2.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--black);
        }

        p {
            font-size: 1.15rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 2rem;
            font-weight: 400;
        }

        /* Link Highlight */
        .highlight-link {
            display: inline-block;
            color: var(--accent-orange);
            text-decoration: none;
            font-weight: 800;
            font-size: 1.25rem;
            background: #fff7ed;
            padding: 6px 16px;
            border-radius: 12px;
            border: 2px dashed var(--accent-orange);
            margin: 10px 0;
            transition: all 0.3s ease;
        }

        .highlight-link:hover {
            background: var(--accent-orange);
            color: white;
            transform: scale(1.05) rotate(-2deg);
        }

        /* Button Utama */
        .actions {
            display: flex;
            justify-content: center;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 1.2rem 3rem;
            font-size: 1.2rem;
            font-weight: 700;
            font-family: 'Fredoka', sans-serif;
            background: var(--accent-yellow);
            color: var(--black);
            border: 4px solid var(--black);
            border-radius: 99px;
            text-decoration: none;
            box-shadow: 6px 6px 0px var(--black);
            transition: all 0.2s ease;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            transform: translate(-2px, -2px);
            box-shadow: 8px 8px 0px var(--black);
            background: var(--accent-orange);
            color: white;
        }

        .btn-primary:active {
            transform: translate(4px, 4px);
            box-shadow: 2px 2px 0px var(--black);
        }

        /* Keyframe Animations */
        @keyframes spin { 100% { transform: rotate(360deg); } }
        @keyframes spin-reverse { 100% { transform: rotate(-360deg); } }
        @keyframes bounce {
            0%, 100% { transform: translateY(0) scaleY(1); }
            50% { transform: translateY(-25px) scaleY(1.05) rotate(4deg); }
        }
        @keyframes shadowPulse {
            0%, 100% { transform: translateX(-50%) scale(1); opacity: 0.2; }
            50% { transform: translateX(-50%) scale(0.7); opacity: 0.05; }
        }
        @keyframes popIn {
            0% { opacity: 0; transform: scale(0.8) translateY(50px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* Pola Latar Belakang Ceria */
        .bg-pattern {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: radial-gradient(var(--accent-yellow) 3px, transparent 3px);
            background-size: 40px 40px;
            opacity: 0.4;
            z-index: 0;
            animation: slideBg 30s linear infinite;
        }

        @keyframes slideBg {
            0% { background-position: 0 0; }
            100% { background-position: -400px -400px; }
        }

        /* Responsif */
        @media (max-width: 640px) {
            .error-code { font-size: 4rem; }
            h1 { font-size: 1.8rem; }
            p { font-size: 1rem; }
            .container { padding: 2.5rem 1.5rem; box-shadow: 8px 8px 0px var(--black); }
            .btn-primary { width: 100%; padding: 1rem; font-size: 1.1rem; }
            .graphics { height: 160px; }
            .cone-wrapper { width: 120px; }
            .gear-1 { width: 100px; }
            .gear-2 { width: 70px; }
        }
    </style>
</head>
<body>

    <!-- Strip Kuning Garis Atas -->
    <div class="warning-strip strip-top"></div>
    
    <!-- Pattern Latar Belakang -->
    <div class="bg-pattern"></div>

    <div class="container">
        
        <div class="graphics">
            <!-- Gear Besar (Berputar Kanan) -->
            <svg class="gear gear-1" viewBox="0 0 24 24">
                <path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.06-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.73,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.8,11.69,4.8,12s0.02,0.64,0.06,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.43-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.49-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/>
            </svg>

            <!-- Gear Kecil (Berputar Kiri) -->
            <svg class="gear gear-2" viewBox="0 0 24 24">
                <path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.06-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.73,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.8,11.69,4.8,12s0.02,0.64,0.06,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.43-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.49-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/>
            </svg>

            <!-- Bayangan Cone -->
            <div class="cone-shadow"></div>
            
            <!-- Traffic Cone SVG (Animasi Bouncing) -->
            <div class="cone-wrapper">
                <svg viewBox="0 0 512 512" width="100%" height="100%">
                    <path d="M448 416H64c-17.67 0-32 14.33-32 32v32c0 17.67 14.33 32 32 32h384c17.67 0 32-14.33 32-32v-32c0-17.67-14.33-32-32-32z" fill="#1e293b"/>
                    <path d="M356.17 416L297.87 43.14C293.98 23.36 276.55 8 256 8s-37.98 15.36-41.87 35.14L155.83 416h200.34z" fill="#f97316"/>
                    <path d="M269.1 128h-26.2l-15.6 80h57.4l-15.6-80z" fill="#ffffff"/>
                    <path d="M282.2 256H216.7l-18.7 96h116l-18.7-96z" fill="#ffffff"/>
                </svg>
            </div>
        </div>

        <div class="error-code">404</div>
        <h1>Sedang Dalam Perbaikan</h1>
        <p>Kami sedang meningkatkan layanan agar lebih optimal. Silakan kembali beberapa saat lagi.<br><br>
        silahkan beralih kedalam <strong>WEB RESMI</strong><br>
        <a href="https://inventorysdaju.com/login" class="highlight-link">https://inventorysdaju.com/login</a><br><br>
        untuk melanjutkan Pendataan Inventori.</p>

        <div class="actions">
            <a href="https://inventorysdaju.com/login" class="btn-primary">Beralih ke Web Resmi</a>
        </div>
    </div>

    <!-- Strip Kuning Garis Bawah -->
    <div class="warning-strip strip-bottom"></div>

</body>
</html>
