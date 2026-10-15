<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\ManagerEditRequest;
use App\Models\Payment;
use App\Models\Rent;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Support\RoleRequestNotifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ManagerEditRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();
        abort_unless($user && ($user->isAdmin() || $user->isManager()), 403, 'Unauthorized action.');

        ManagerEditRequest::expireStaleApprovals();

        $requests = ManagerEditRequest::with(['requester.role', 'admin', 'requestable'])
            ->latest();

        if ($user->isManager()) {
            $requests->where('user_id', $user->id);
        }

        return view('manager-edit-requests.index', [
            'requests' => $requests->paginate(20),
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        abort_unless($user && $user->isManager(), 403, 'Only managers can submit these requests.');

        $validated = $request->validate([
            'record_type' => ['required', 'in:building,tenant,rent,payment,unit'],
            'record_id' => ['required', 'integer'],
            'request_kind' => ['nullable', 'in:edit,agreement_download,agreement_delete'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $requestKind = $validated['request_kind'] ?? ManagerEditRequest::REQUEST_KIND_EDIT;

        if ($requestKind !== ManagerEditRequest::REQUEST_KIND_EDIT && ! ManagerEditRequest::supportsRequestKinds()) {
            return back()->with('error', 'Agreement download/delete requests will work after the latest request migration is installed.');
        }

        if ($requestKind !== ManagerEditRequest::REQUEST_KIND_EDIT && $validated['record_type'] !== 'rent') {
            return back()->with('error', 'This request type is only available for rental agreements.');
        }

        $requestable = $this->resolveRequestable($validated['record_type'], (int) $validated['record_id']);

        abort_unless($this->managerCanRequestEditFor($user, $requestable), 403, 'You can only request edits for records in your assigned buildings.');

        $activeApproval = ManagerEditRequest::latestForRecord($user->id, $requestable::class, (int) $requestable->getKey(), $requestKind);

        if ($activeApproval && $activeApproval->hasActiveEditWindow()) {
            return back()->with('error', 'This '.$this->requestKindLabel($requestKind).' request is already approved until '.$activeApproval->expires_at->format('d M Y, h:i A').'.');
        }

        $existingPendingRequestQuery = ManagerEditRequest::pending()
            ->where('user_id', $user->id)
            ->where('requestable_type', $requestable::class)
            ->where('requestable_id', $requestable->getKey());

        if (ManagerEditRequest::supportsRequestKinds()) {
            $existingPendingRequestQuery->where('request_kind', $requestKind);
        }

        $existingPendingRequest = $existingPendingRequestQuery->first();

        if ($existingPendingRequest) {
            return back()->with('error', $this->requestKindLabel($requestKind).' already requested for this record.');
        }

        $editRequestPayload = [
            'user_id' => $user->id,
            'requestable_type' => $requestable::class,
            'requestable_id' => $requestable->getKey(),
            'reason' => $validated['reason'],
        ];

        if (ManagerEditRequest::supportsRequestKinds()) {
            $editRequestPayload['request_kind'] = $requestKind;
        }

        $editRequest = ManagerEditRequest::create($editRequestPayload);

        RoleRequestNotifier::notifyAdmins([
            'title' => 'Manager '.$this->requestKindLabel($requestKind).' request',
            'message' => $user->name.' requested admin '.$this->requestKindActionText($requestKind).' for '.$this->describeRequestable($requestable).'.',
            'url' => route('manager-edit-requests.index'),
            'request_type' => 'manager_'.strtolower(str_replace(' ', '_', $this->requestKindLabel($requestKind))).'_request',
            'created_by' => $user->name,
            'subject_id' => $editRequest->id,
        ], $user->id);

        return back()->with('success', $this->requestKindLabel($requestKind).' request submitted to admin.');
    }

    public function handle(Request $request, ManagerEditRequest $managerEditRequest)
    {
        $admin = auth()->user();
        abort_unless($admin && $admin->isAdmin(), 403, 'Unauthorized action.');

        if ($managerEditRequest->status !== 'pending') {
            return back()->with('error', 'This edit request has already been reviewed.');
        }

        $validated = $request->validate([
            'action' => ['required', 'in:approved,completed,rejected'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $resolvedAction = $validated['action'] === 'completed'
            ? 'approved'
            : $validated['action'];

        $expiresAt = $resolvedAction === 'approved'
            ? now()->addHours(ManagerEditRequest::EDIT_WINDOW_HOURS)
            : null;

        $managerEditRequest->update([
            'status' => $resolvedAction,
            'admin_id' => $admin->id,
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        RoleRequestNotifier::notifyUser($managerEditRequest->requester, [
            'title' => 'Manager '.$this->requestKindLabel($managerEditRequest->request_kind).' request '.$resolvedAction,
            'message' => $resolvedAction === 'approved'
                ? 'Admin approved your '.$this->requestKindLabel($managerEditRequest->request_kind).' request for '.$this->describeRequestable($managerEditRequest->requestable).'. You can '.$this->requestKindActionText($managerEditRequest->request_kind).' until '.$expiresAt?->format('d M Y, h:i A').'.'
                : 'Admin reviewed your '.$this->requestKindLabel($managerEditRequest->request_kind).' request for '.$this->describeRequestable($managerEditRequest->requestable).'.',
            'url' => route('manager-edit-requests.index'),
            'request_type' => 'manager_'.strtolower(str_replace(' ', '_', $this->requestKindLabel($managerEditRequest->request_kind))).'_request_'.$resolvedAction,
            'created_by' => $admin->name,
            'subject_id' => $managerEditRequest->id,
        ], $admin->id);

        $successMessage = $resolvedAction === 'approved'
            ? $this->requestKindLabel($managerEditRequest->request_kind).' request approved. Manager can '.$this->requestKindActionText($managerEditRequest->request_kind).' this record until '.$expiresAt?->format('d M Y, h:i A').'.'
            : $this->requestKindLabel($managerEditRequest->request_kind).' request marked '.$resolvedAction.'.';

        return back()->with('success', $successMessage);
    }

    private function resolveRequestable(string $type, int $id): Model
    {
        return match ($type) {
            'building' => Building::findOrFail($id),
            'tenant' => Tenant::with('unit.building')->findOrFail($id),
            'rent' => Rent::with('unit.building', 'tenant')->findOrFail($id),
            'payment' => Payment::with('rent.unit.building', 'rent.tenant')->findOrFail($id),
            'unit' => Unit::with('building')->findOrFail($id),
        };
    }

    private function managerCanRequestEditFor(User $user, Model $requestable): bool
    {
        if ($requestable instanceof Building) {
            return (int) $requestable->manager_id === (int) $user->id;
        }

        if ($requestable instanceof Tenant) {
            return (int) optional(optional($requestable->unit)->building)->manager_id === (int) $user->id;
        }

        if ($requestable instanceof Rent) {
            return (int) optional(optional($requestable->unit)->building)->manager_id === (int) $user->id;
        }

        if ($requestable instanceof Payment) {
            return (int) optional(optional(optional($requestable->rent)->unit)->building)->manager_id === (int) $user->id;
        }

        if ($requestable instanceof Unit) {
            return (int) optional($requestable->building)->manager_id === (int) $user->id;
        }

        return false;
    }

    private function describeRequestable(?Model $requestable): string
    {
        if ($requestable instanceof Building) {
            return 'building '.$requestable->name;
        }

        if ($requestable instanceof Tenant) {
            return 'tenant '.$requestable->full_name;
        }

        if ($requestable instanceof Rent) {
            return 'rental agreement #'.$requestable->id;
        }

        if ($requestable instanceof Payment) {
            return 'payment #'.$requestable->id;
        }

        if ($requestable instanceof Unit) {
            return 'unit '.$requestable->unit_number.' in '.optional($requestable->building)->name;
        }

        return 'record';
    }

    private function requestKindLabel(?string $requestKind): string
    {
        return strtolower(ManagerEditRequest::requestKindLabel($requestKind));
    }

    private function requestKindActionText(?string $requestKind): string
    {
        return match ($requestKind) {
            ManagerEditRequest::REQUEST_KIND_AGREEMENT_DOWNLOAD => 'download the uploaded agreement',
            ManagerEditRequest::REQUEST_KIND_AGREEMENT_DELETE => 'delete the uploaded agreement',
            default => 'edit it',
        };
    }
}
