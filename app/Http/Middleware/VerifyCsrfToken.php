<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * The chatbot endpoint is a public, read-only JSON responder used from
     * pages that may not always carry the csrf-token meta tag. Every other
     * state-changing route must present a valid token.
     *
     * @var array<int, string>
     */
    protected $except = [
        'bot/message',
    ];
}
