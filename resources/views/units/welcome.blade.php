<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'HOWSEY') }}</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #fdfbf7; /* Off-white */
            border-top: 5px solid #7c3aed; /* Purple top edge */
        }
        .hero-section {
            background-color: #7c3aed; /* Fallback purple */
            background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('{{ asset("assets/images/hero-bg.jpg") }}');
            background-size: cover;
            background-position: center;
            height: calc(100vh - 5px); /* Adjust for border */
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }
        .hero-content {
            max-width: 800px;
            padding: 20px;
        }
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 30px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
        .btn-custom {
            background-color: #7c3aed;
            border-color: #7c3aed;
            color: white;
            padding: 12px 30px;
            font-size: 1.1rem;
            border-radius: 50px;
            transition: all 0.3s;
            text-decoration: none;
        }
        .btn-custom:hover {
            background-color: #6d28d9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.4);
            color: white;
        }
        .btn-mobile-small {
            padding: 6px 16px;
            font-size: 0.9rem;
        }
        .navbar-custom {
            background-color: transparent;
            position: absolute;
            top: 0;
            width: 100%;
            z-index: 10;
            padding: 20px 0;
        }
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: bold;
            color: white !important;
        }
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 600;
            margin-left: 15px;
        }
        .nav-link:hover {
            color: #7c3aed !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="bi bi-building"></i> HOWSEY</a>
            
            <div class="d-flex align-items-center order-lg-2">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-custom btn-mobile-small ms-2 d-lg-none">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="nav-link d-lg-none me-3">Log in</a>
                    @endauth
                @endif
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>

            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    @if (Route::has('login'))
                        @auth
                            <li class="nav-item d-none d-lg-block">
                                <a href="{{ url('/dashboard') }}" class="btn btn-custom ms-3">Dashboard</a>
                            </li>
                        @else
                            <li class="nav-item d-none d-lg-block">
                                <a href="{{ route('login') }}" class="nav-link">Log in</a>
                            </li>
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a href="{{ route('register') }}" class="btn btn-custom ms-3">Get Started</a>
                                </li>
                            @endif
                        @endauth
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    <div class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Modern Property Management Simplified</h1>
            <p class="hero-subtitle">Streamline your rental operations, manage tenants efficiently, and track payments effortlessly with HOWSEY.</p>
            @if (Route::has('register'))
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="{{ route('register') }}" class="btn btn-custom btn-lg">Get Started</a>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn btn-custom btn-lg">Login to Dashboard</a>
            @endif
        </div>
    </div>

    <!-- Cache Buster: {{ time() }} -->

    <footer class="text-center py-3 bg-white border-top">
        <div class="container">
            <p class="mb-0 text-muted small">
                produced by Noyb Fundamentals 2026 &copy;
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
