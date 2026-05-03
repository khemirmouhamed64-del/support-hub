<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', __('auth.sign_in')) - {{ __('app.brand_name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #343a40 0%, #495057 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
        }
        .auth-brand {
            text-align: center;
            margin-bottom: 30px;
        }
        .auth-brand i { font-size: 2.5rem; color: #3498db; }
        .auth-brand h4 { margin-top: 10px; font-weight: 700; }
        .auth-brand small { color: #6c757d; }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-brand">
            <i class="fas fa-headset"></i>
            <h4>{{ __('app.brand_name') }}</h4>
            <small>{{ __('app.brand_subtitle') }}</small>
        </div>
        @yield('content')
    </div>
</body>
</html>
