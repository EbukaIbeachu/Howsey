<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'HOWSEY')</title>
    <meta name="description" content="@yield('description')">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="@yield('canonical')">
    <link rel="icon" type="image/png" href="{{ url('/howsey.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --lg-primary: #7c3aed;
            --lg-primary-hover: #6d28d9;
            --lg-ink: #1e1b2e;
            --lg-muted: #5b5670;
            --lg-line: rgba(30, 27, 46, 0.10);
        }
        *, *::before, *::after { box-sizing: border-box; }
        body.lg-body {
            margin: 0;
            font-family: 'Nunito', sans-serif;
            background-color: #fdfbf7;
            color: var(--lg-ink);
            line-height: 1.7;
            border-top: 5px solid var(--lg-primary);
        }
        .lg-header {
            position: sticky;
            top: 0;
            z-index: 10;
            background: rgba(253, 251, 247, 0.94);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--lg-line);
        }
        .lg-bar {
            max-width: 70rem;
            margin: 0 auto;
            padding: 0.85rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .lg-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            text-decoration: none;
            color: var(--lg-ink);
        }
        .lg-brand img { width: 28px; height: 30px; object-fit: contain; }
        .lg-brand span {
            font-family: 'Playfair Display', Georgia, serif;
            font-weight: 700;
            letter-spacing: 0.04em;
            font-size: 1.15rem;
        }
        .lg-nav { display: flex; flex-wrap: wrap; gap: 1.1rem; font-size: 0.9rem; }
        .lg-nav a { color: var(--lg-muted); text-decoration: none; font-weight: 600; }
        .lg-nav a:hover, .lg-nav a:focus-visible { color: var(--lg-primary); }
        .lg-main { padding: 2.5rem 0 3.5rem; }
        .lg-container { max-width: 52rem; margin: 0 auto; padding: 0 1.25rem; }
        .lg-page-head {
            border-bottom: 1px solid var(--lg-line);
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        .lg-eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.14em;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--lg-primary);
            margin-bottom: 0.6rem;
        }
        .lg-page-head h1 {
            font-family: 'Playfair Display', Georgia, serif;
            font-weight: 700;
            font-size: clamp(1.8rem, 4vw, 2.4rem);
            margin: 0 0 0.9rem;
        }
        .lg-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem 1.25rem;
            margin: 0;
            color: var(--lg-muted);
            font-size: 0.88rem;
        }
        .lg-section { margin-bottom: 2rem; }
        .lg-section h2 {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0 0 0.75rem;
        }
        .lg-section h3 { font-size: 1rem; font-weight: 700; margin: 1.25rem 0 0.5rem; }
        .lg-section p { margin: 0 0 0.85rem; }
        .lg-section ul, .lg-section ol { margin: 0 0 0.95rem; padding-left: 1.3rem; }
        .lg-section li { margin-bottom: 0.35rem; }
        .lg-section a { color: var(--lg-primary); }
        .lg-contact {
            background: #f7f5f1;
            border: 1px solid var(--lg-line);
            border-radius: 0.85rem;
            padding: 1rem 1.15rem;
            margin: 0.5rem 0 1rem;
        }
        .lg-contact p { margin: 0 0 0.3rem; }
        .lg-contact p:last-child { margin-bottom: 0; }
        .lg-contact a { color: var(--lg-primary); font-weight: 700; text-decoration: none; }
        .lg-contact a:hover { text-decoration: underline; }
        .lg-footer { border-top: 1px solid var(--lg-line); background: #fff; padding: 1.4rem 0; }
        .lg-footer-inner {
            max-width: 70rem;
            margin: 0 auto;
            padding: 0 1.25rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.6rem 1.25rem;
            color: var(--lg-muted);
            font-size: 0.86rem;
        }
        .lg-footer-links { display: flex; gap: 1rem; }
        .lg-footer-links a { color: var(--lg-muted); text-decoration: none; }
        .lg-footer-links a:hover, .lg-footer-links a:focus-visible { color: var(--lg-primary); }
        @media (max-width: 575.98px) {
            .lg-bar { padding: 0.7rem 0.9rem; }
            .lg-nav { font-size: 0.84rem; gap: 0.85rem; }
        }
    </style>
</head>
<body class="lg-body">
    <header class="lg-header">
        <div class="lg-bar">
            <a class="lg-brand" href="{{ url('/') }}">
                <img src="{{ url('/howsey.png') }}" alt="HOWSEY" width="28" height="30" decoding="async">
                <span>HOWSEY</span>
            </a>
            <nav class="lg-nav" aria-label="Legal and site navigation">
                <a href="{{ url('/') }}">Home</a>
                <a href="{{ route('legal.privacy') }}">Privacy Policy</a>
                <a href="{{ route('legal.terms') }}">Terms of Service</a>
            </nav>
        </div>
    </header>

    <main class="lg-main">
        <div class="lg-container">
            @yield('content')
        </div>
    </main>

    <footer class="lg-footer">
        <div class="lg-footer-inner">
            <span>&copy; 2026 HOWSEY. All rights reserved.</span>
            <span class="lg-footer-links">
                <a href="{{ route('legal.privacy') }}">Privacy Policy</a>
                <a href="{{ route('legal.terms') }}">Terms of Service</a>
            </span>
        </div>
    </footer>
</body>
</html>
