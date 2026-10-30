@extends('layouts.app')

@section('content')
<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-lg-5">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <div class="text-center mb-4">
                        <span class="badge rounded-pill bg-success-subtle text-success-emphasis px-3 py-2">Workspace Created</span>
                        <h1 class="h3 fw-bold mt-3 mb-2">{{ $newWorkspace['name'] }} is ready</h1>
                        <p class="text-muted mb-0">Save your Account ID now. You will need it every time you sign in.</p>
                    </div>

                    <div class="border rounded-3 p-3 p-lg-4 mb-4 bg-body-tertiary">
                        <div class="small text-uppercase text-muted fw-semibold mb-2">Your Account ID</div>
                        <div class="d-flex flex-column flex-sm-row gap-2">
                            <input type="text" id="created_account_id" class="form-control form-control-lg font-monospace" value="{{ $newWorkspace['account_id'] }}" readonly>
                            <button type="button" class="btn btn-outline-primary flex-shrink-0" onclick="copyCreatedAccountId(this)">Copy</button>
                        </div>
                        <div class="form-text">Store this somewhere safe: a notes app, password manager, or a screenshot.</div>
                    </div>

                    <div class="mb-4">
                        <div class="small text-uppercase text-muted fw-semibold mb-1">Workspace Login</div>
                        <a href="{{ route('companies.login', $newWorkspace['slug']) }}">{{ route('companies.login', $newWorkspace['slug']) }}</a>
                    </div>

                    <div class="alert alert-warning">
                        Sign in with this Account ID and the password you just created. Keep the Account ID safe, because it cannot be recovered from your password alone.
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('companies.login', $newWorkspace['slug']) }}" class="btn btn-primary btn-lg">Continue to Login</a>
                        <a href="{{ url('/') }}" class="btn btn-outline-secondary">Back Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function copyCreatedAccountId(button) {
        const input = document.getElementById('created_account_id');
        if (!input) {
            return;
        }

        const done = function () {
            const original = button.textContent;
            button.textContent = 'Copied';
            setTimeout(function () {
                button.textContent = original;
            }, 1500);
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(input.value).then(done, function () {
                input.select();
                document.execCommand('copy');
                done();
            });
            return;
        }

        input.select();
        document.execCommand('copy');
        done();
    }
</script>
@endpush
