@if (session('success'))
    <div class="alert alert-ok" role="status">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-bad" role="alert">{{ session('error') }}</div>
@endif
