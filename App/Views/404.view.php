<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 · MPF</title>

    <style>
        :root {
            --bg-1: #0f0c29;
            --bg-2: #302b63;
            --bg-3: #24243e;
            --accent: #8b5cf6;
            --text: #e5e7eb;
            --muted: #9ca3af;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text);
            background: linear-gradient(135deg, var(--bg-1), var(--bg-2), var(--bg-3));
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Background symbol */
        .back-banner {
            position: absolute;
            font-size: 22rem;
            opacity: 0.06;
            animation: float 6s ease-in-out infinite;
            user-select: none;
        }

        @keyframes float {
            0% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0); }
        }

        /* Glass card */
        .card {
            width: min(92%, 420px);
            padding: 2.5rem 2rem;
            text-align: center;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(14px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            z-index: 1;
        }

        h1 {
            margin: 0;
            font-size: 2.5rem;
            letter-spacing: 0.1em;
        }

        h2 {
            margin: 1rem 0 0.2rem;
            font-size: 4rem;
            font-weight: 800;
            color: var(--accent);
        }

        .subtitle {
            font-size: 0.9rem;
            letter-spacing: 0.12em;
            color: var(--muted);
        }

        .author {
            margin: 1.2rem 0;
            font-size: 0.85rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            color: var(--muted);
        }

        p {
            margin: 1.2rem 0 2rem;
            color: var(--muted);
        }

        a.button {
            display: inline-block;
            padding: 0.8rem 1.6rem;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.35);
        }

        a.button:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 35px rgba(139, 92, 246, 0.55);
        }
    </style>
</head>
<body>

    <div class="back-banner">👽</div>

    <div class="card">
        <h1>MPF</h1>
        <div class="subtitle">MINIMALIST PHP FRAMEWORK</div>

        <div class="author">made by · adaomajor</div>

        <h2>404</h2>
        <p>The page you are looking for does not exist.</p>

        <a class="button" href="https://adaomajor.github.io">Learn More</a>
    </div>

</body>
</html>
