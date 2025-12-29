<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=1200, height=630">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 1200px;
            height: 630px;
            overflow: hidden;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #000000;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .glow-1 {
            position: absolute;
            top: 10%;
            left: 50%;
            transform: translateX(-50%);
            width: 800px;
            height: 800px;
            background: rgba(59, 130, 246, 0.15);
            border-radius: 50%;
            filter: blur(100px);
            pointer-events: none;
        }

        .glow-2 {
            position: absolute;
            top: 20%;
            left: 20%;
            width: 600px;
            height: 600px;
            background: rgba(139, 92, 246, 0.1);
            border-radius: 50%;
            filter: blur(100px);
            pointer-events: none;
        }

        .content {
            position: relative;
            z-index: 10;
            text-align: center;
            max-width: 1000px;
            padding: 0 60px;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 40px;
        }

        .logo svg {
            width: 40px;
            height: 40px;
            color: #58a6ff;
        }

        .logo-text {
            font-size: 28px;
            font-weight: 600;
            color: white;
        }

        .title {
            font-size: 80px;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 30px;
            letter-spacing: -0.02em;
        }

        .title-white {
            color: white;
        }

        .title-gradient {
            background: linear-gradient(90deg, #3b82f6, #22d3ee, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .tagline {
            font-size: 28px;
            font-weight: 400;
            color: #9ca3af;
            line-height: 1.5;
            max-width: 800px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="glow-1"></div>
    <div class="glow-2"></div>

    <div class="content">
        <div class="logo">
            <svg fill="currentColor" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="5"/>
                <path d="M12 1v3M12 20v3M4.22 4.22l2.12 2.12M17.66 17.66l2.12 2.12M1 12h3M20 12h3M4.22 19.78l2.12-2.12M17.66 6.34l2.12-2.12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span class="logo-text">Photova</span>
        </div>

        <h1 class="title">
            <span class="title-white">The Ultimate</span>
            <br>
            <span class="title-gradient">Photo Platform</span>
        </h1>

        <p class="tagline">Edit, organize, and enhance your entire photo library with AI-powered tools. Professional-grade. No subscriptions. No limits.</p>
    </div>
</body>
</html>
