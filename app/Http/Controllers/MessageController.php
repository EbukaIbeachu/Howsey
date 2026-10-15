<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    /**
     * Inbox + sent list.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $tab = $request->query('tab', 'inbox');

        $inbox = Message::with('sender')
            ->where('recipient_id', $user->id)
            ->latest()
            ->get();

        $sent = Message::with('recipient')
            ->where('sender_id', $user->id)
            ->latest()
            ->get();

        $unreadCount = $inbox->whereNull('read_at')->count();

        return view('messages.index', compact('inbox', 'sent', 'tab', 'unreadCount'));
    }

    /**
     * Compose form.
     */
    public function create(Request $request): View
    {
        $user = $request->user();

        abort_unless($this->canSend($user), 403, 'You are not allowed to send messages.');

        $recipients = $this->allowedRecipients($user);

        return view('messages.create', compact('recipients'));
    }

    /**
     * Reply form (pre-fills the original sender as recipient).
     */
    public function reply(Request $request, Message $message): View
    {
        $user = $request->user();

        abort_unless($this->canReply($user), 403, 'You are not allowed to reply.');
        abort_unless((int) $message->recipient_id === (int) $user->id, 403);

        $sender = $message->sender;
        $recipients = collect([$sender]);

        return view('messages.create', compact('recipients', 'message'));
    }

    /**
     * Tenant acknowledges a message — sends "Acknowledged" back to the sender.
     */
    public function acknowledge(Request $request, Message $message): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->isTenant() || $user->isAccountant(), 403, 'Only tenants and accountants can acknowledge.');
        abort_unless((int) $message->recipient_id === (int) $user->id, 403);

        $sender = $message->sender;

        $reply = Message::create([
            'sender_id' => $user->id,
            'recipient_id' => $sender->id,
            'body' => 'Acknowledged',
        ]);

        $sender->notify(new NewMessageNotification($user->name, 'Acknowledged', $reply->id));

        return redirect()->route('messages.index')
            ->with('success', 'Acknowledgement sent to '.$sender->name.'.');
    }

    /**
     * Send a message.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $isReply = $request->filled('reply_to');

        abort_unless(
            $isReply ? $this->canReply($user) : $this->canSend($user),
            403,
            $isReply ? 'You are not allowed to reply.' : 'You are not allowed to send messages.'
        );

        $validated = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $recipient = User::findOrFail($validated['recipient_id']);

        if ($isReply) {
            // When replying, the recipient must be the original sender.
            $original = Message::findOrFail($request->input('reply_to'));
            abort_unless((int) $original->recipient_id === (int) $user->id, 403);
            abort_unless((int) $original->sender_id === (int) $recipient->id, 403, 'You can only reply to the sender.');
        } else {
            // Ensure the recipient is in the allowed list for this sender.
            $allowedIds = $this->allowedRecipients($user)->pluck('id')->all();
            abort_unless(in_array((int) $recipient->id, $allowedIds, true), 403, 'You cannot message this user.');
        }

        $message = Message::create([
            'sender_id' => $user->id,
            'recipient_id' => $recipient->id,
            'body' => $validated['body'],
        ]);

        $recipient->notify(new NewMessageNotification($user->name, $validated['body'], $message->id));

        return redirect()->route('messages.index', ['tab' => 'sent'])
            ->with('success', 'Message sent to '.$recipient->name.'.');
    }

    /**
     * View a message (and mark as read if recipient).
     */
    public function show(Request $request, Message $message): View
    {
        $user = $request->user();

        abort_unless(
            (int) $message->sender_id === (int) $user->id || (int) $message->recipient_id === (int) $user->id,
            403
        );

        $message->load(['sender', 'recipient']);

        if ((int) $message->recipient_id === (int) $user->id && $message->read_at === null) {
            $message->markAsRead();

            // Also mark the associated notification as read.
            $user->unreadNotifications()
                ->where('data->request_type', 'new_message')
                ->where('data->message_id', $message->id)
                ->get()
                ->markAsRead();
        }

        return view('messages.show', compact('message'));
    }

    /**
     * Only super_admin, admin, and manager can initiate new messages.
     */
    private function canSend(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin() || $user->isManager();
    }

    /**
     * Everyone except tenants can reply to a message they received.
     */
    private function canReply(User $user): bool
    {
        return ! $user->isTenant();
    }

    /**
     * Build the list of users the current user is allowed to message.
     *
     * - super_admin -> admins of any workspace
     * - admin       -> managers, accountants, tenants within the same company
     * - manager     -> tenants of buildings they manage
     */
    private function allowedRecipients(User $user)
    {
        if ($user->isSuperAdmin()) {
            return User::with('company')
                ->whereHas('role', function ($q) {
                    $q->where('name', 'admin');
                })
                ->orderBy('name')
                ->get();
        }

        if ($user->isAdmin()) {
            $companyId = $user->company_id;

            return User::with('role')
                ->where('company_id', $companyId)
                ->whereHas('role', function ($q) {
                    $q->whereIn('name', ['manager', 'accountant', 'tenant']);
                })
                ->orderBy('name')
                ->get();
        }

        if ($user->isManager()) {
            $buildingIds = Building::where('manager_id', $user->id)->pluck('id');

            return User::with(['role', 'tenant.unit.building'])
                ->whereHas('role', function ($q) {
                    $q->where('name', 'tenant');
                })
                ->whereHas('tenant', function ($q) use ($buildingIds) {
                    $q->whereHas('unit', function ($u) use ($buildingIds) {
                        $u->whereIn('building_id', $buildingIds);
                    });
                })
                ->orderBy('name')
                ->get();
        }

        return collect();
    }
}
