<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Attach baseline security headers to every outgoing response.
     *
     * The policy is deliberately conservative: script-src and style-src are
     * left unrestricted because the application relies heavily on inline
     * handlers and inline styles. Only the directives that carry no risk of
     * breaking existing UI are enforced.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            // Stop MIME sniffing of user-supplied uploads.
            'X-Content-Type-Options' => 'nosniff',

            // Legacy clickjacking protection.
            'X-Frame-Options' => 'SAMEORIGIN',

            // Do not leak full URLs (and query strings) to third parties.
            'Referrer-Policy' => 'strict-origin-when-cross-origin',

            // The application never needs these browser capabilities.
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',

            // Disable the legacy XSS auditor; it introduced more issues than it solved.
            'X-XSS-Protection' => '0',

            // Restricts framing, plugin/object embedding and <base> hijacking
            // without constraining script-src/style-src.
            'Content-Security-Policy' => "frame-ancestors 'self'; object-src 'none'; base-uri 'self'",

            // Browsers ignore this over plain HTTP, so it is safe to always send.
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        ];

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
