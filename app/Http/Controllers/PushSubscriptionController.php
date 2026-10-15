<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Support\WebPushSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function publicKey(Request $request): JsonResponse
    {
        return response()->json([
            'supported' => WebPushSender::configured(),
            'publicKey' => WebPushSender::publicKey(),
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        if (! WebPushSender::configured()) {
            return response()->json([
                'supported' => false,
                'message' => 'Web Push is not configured on this server.',
            ], 503);
        }

        $validated = $request->validate([
            'endpoint' => 'required|string|max:4000',
            'keys.p256dh' => 'required|string|max:1000',
            'keys.auth' => 'required|string|max:1000',
            'contentEncoding' => 'nullable|string|max:50',
        ]);

        $endpoint = trim($validated['endpoint']);
        $endpointHash = hash('sha256', $endpoint);

        $subscription = PushSubscription::updateOrCreate(
            ['endpoint_hash' => $endpointHash],
            [
                'user_id' => $request->user()->id,
                'endpoint' => $endpoint,
                'public_key' => data_get($validated, 'keys.p256dh'),
                'auth_token' => data_get($validated, 'keys.auth'),
                'content_encoding' => $validated['contentEncoding'] ?? null,
                'user_agent' => (string) $request->userAgent(),
                'last_seen_at' => now(),
            ]
        );

        return response()->json([
            'ok' => true,
            'subscription_id' => $subscription->id,
        ]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string|max:4000',
        ]);

        $endpointHash = hash('sha256', trim($validated['endpoint']));

        $request->user()
            ->pushSubscriptions()
            ->where('endpoint_hash', $endpointHash)
            ->delete();

        return response()->json(['ok' => true]);
    }

    public function latest(Request $request): JsonResponse
    {
        $notification = $this->visibleUnreadNotifications($request)
            ->latest()
            ->first();

        $fallbackUrl = $request->user()->isAccountant()
            ? route('accounting.index')
            : route('dashboard');

        if (! $notification) {
            return response()->json([
                'title' => 'HOWSEY Update',
                'message' => 'You have a new update in HOWSEY.',
                'open_url' => $fallbackUrl,
                'tag' => 'howsey-generic-notification',
            ]);
        }

        return response()->json([
            'title' => (string) data_get($notification->data, 'title', 'New notification'),
            'message' => (string) data_get($notification->data, 'message', 'A new item needs your attention.'),
            'open_url' => route('notifications.open', $notification->id),
            'tag' => 'howsey-notification-'.$notification->id,
            'notification_id' => $notification->id,
        ]);
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
