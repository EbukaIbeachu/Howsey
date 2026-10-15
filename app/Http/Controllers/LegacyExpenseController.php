<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

/**
 * @deprecated This controller is part of the legacy expense mechanism and has been disabled.
 * All expense management should now be done through the new Accounting Module.
 */
class LegacyExpenseController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            Log::warning('Unauthorized access attempt to legacy expense module by user: '.auth()->id());
            abort(403, 'The legacy expense module has been deprecated and disabled. Please use the new Accounting Module.');
        });
    }

    public function index()
    {
        abort(403);
    }

    public function create()
    {
        abort(403);
    }

    public function store()
    {
        abort(403);
    }

    public function show()
    {
        abort(403);
    }

    public function edit()
    {
        abort(403);
    }

    public function update()
    {
        abort(403);
    }

    public function destroy()
    {
        abort(403);
    }
}
