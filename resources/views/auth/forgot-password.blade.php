<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - {{ config('app.name') }}</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body class="auth-body d-flex align-items-center justify-content-center">
    <div class="auth-card card shadow-lg">
        <div class="card-body p-5">
            <h5 class="mb-3">Lupa Password</h5>
            <p class="text-muted small">Masukkan email Anda, kami akan mengirimkan link untuk reset password.</p>

            @if ($errors->any())
                <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
            @endif
            @if (session('status'))
                <div class="alert alert-success py-2 small">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary w-100">Kirim Link Reset</button>
                <a href="{{ route('login') }}" class="btn btn-link w-100 mt-2">Kembali ke Login</a>
            </form>
        </div>
    </div>
</body>
</html>
