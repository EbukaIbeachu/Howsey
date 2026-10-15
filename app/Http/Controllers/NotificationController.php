<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function open(Request $request, string $notification): RedirectResponse
    {
        $record = $this->visibleNotifications($request)->findOrFail($notification);

        if ($record->read_at === null) {
            $record->markAsRead();
        }

        $fallback = $request->user()->isAccountant()
            ? route('accounting.index')
            : route('dashboard');

        return redirect()->to((string) data_get($record->data, 'url', $fallback));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $this->visibleUnreadNotifications($request)->get()->markAsRead();

        return back()->with('success', 'Notifications marked as read.');
    }

    private function visibleNotifications(Request $request)
    {
        $query = $request->user()->notifications();

        if ($request->user()->isSuperAdmin()) {
            $query->where('data->request_type', 'company_application');
        }

        return $query;
    }

    private function visibleUnreadNotifications(Request $request)
    {
        $query = $request->user()->unreadNotifications();

        if ($request->user()->isSuperAdmin()) {
            $query->where('data->request_type', 'company_application');
        }

        return $query;
    }
}
