@php
    $account = $account ?? null;
    $uid = $uid ?? 'new';
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <x-ui.form.input
            name="label"
            label="Account Label (optional)"
            :value="$account->label ?? ''"
            placeholder="e.g. Main Collection Account"
        />
    </div>
    <div class="col-md-6">
        <x-ui.form.input name="bank_name" label="Bank Name" :value="$account->bank_name ?? ''" required />
    </div>
    <div class="col-md-6">
        <x-ui.form.input name="account_name" label="Account Name" :value="$account->account_name ?? ''" required />
    </div>
    <div class="col-md-6">
        <x-ui.form.input name="account_number" label="Account Number" :value="$account->account_number ?? ''" required />
    </div>
    <div class="col-md-6">
        <x-ui.form.input name="branch" label="Branch (optional)" :value="$account->branch ?? ''" />
    </div>
    <div class="col-md-6">
        <x-ui.form.input
            name="sort_order"
            type="number"
            label="Display Order"
            :value="$account->sort_order ?? 0"
            min="0"
            max="9999"
        />
    </div>
    <div class="col-12">
        <x-ui.form.textarea
            name="instructions"
            label="Note For Tenants (optional)"
            :value="$account->instructions ?? ''"
            :rows="2"
            placeholder="e.g. Use your unit number as the transfer narration."
        />
    </div>
    <div class="col-md-6">
        <input type="hidden" name="is_active" value="0">
        <x-ui.form.checkbox
            name="is_active"
            id="account-active-{{ $uid }}"
            label="Active (visible to tenants)"
            :checked="$account->is_active ?? true"
        />
    </div>
    <div class="col-md-6">
        <input type="hidden" name="is_primary" value="0">
        <x-ui.form.checkbox
            name="is_primary"
            id="account-primary-{{ $uid }}"
            label="Primary account (shown first)"
            :checked="$account->is_primary ?? false"
        />
    </div>
</div>
