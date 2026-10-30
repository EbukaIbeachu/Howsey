{{-- Masked HOWSEY badge: the platform logo is the only visible element; it spins on hover/focus. --}}
<style>
    .howsey-credit {
        position: fixed;
        left: 20px;
        bottom: 20px;
        z-index: 1050;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 0;
        text-decoration: none;
    }

    .howsey-credit img {
        display: block;
        width: 28px;
        height: 28px;
        object-fit: contain;
    }

    @media (min-width: 769px) {
        .howsey-credit img {
            width: 44px;
            height: 44px;
        }
    }

    .howsey-credit:hover img,
    .howsey-credit:focus-visible img {
        animation: howsey-credit-spin 0.7s ease-in-out;
    }

    @keyframes howsey-credit-spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    @media (prefers-reduced-motion: reduce) {
        .howsey-credit:hover img,
        .howsey-credit:focus-visible img {
            animation: none;
        }
    }
</style>

<a class="howsey-credit" href="https://seashell-termite-214529.hostingersite.com/#top"
   target="_blank" rel="noopener noreferrer" aria-label="HOWSEY" title="HOWSEY">
    <img src="{{ url('/howsey.png') }}" alt="HOWSEY">
</a>
