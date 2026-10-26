<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login | WebAdmin</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('laravel-icon.svg') }}">

    <link rel="stylesheet"
          href="{{ asset('css/login.css') }}">
    <link rel="stylesheet"
          href="{{ asset('css/form-icons.css') }}?v=2">
    <link rel="stylesheet"
          href="{{ asset('css/button-system.css') }}?v=1">

    <script src="{{ asset('js/login.js') }}" defer></script>
</head>
<body>

<main class="login-shell">

    <!-- LEFT BLUE PANEL -->
    <section class="brand-panel">

        <div class="brand-content">

            <div class="brand-logo">
                <span class="logo-mark">
                    <svg viewBox="0 0 48 48" fill="none">
                        <path d="M24 3 43 14v20L24 45 5 34V14L24 3Z"
                              stroke="currentColor"
                              stroke-width="4"
                              stroke-linejoin="round"/>
                        <path d="M5 14 24 25 43 14M24 25v20"
                              stroke="currentColor"
                              stroke-width="4"
                              stroke-linejoin="round"/>
                    </svg>
                </span>
                <span>WebAdmin</span>
            </div>

            <div class="brand-description">
                <h1>
                    Inventory &amp; Sales<br>
                    Management System
                </h1>

                <p>
                    Manage inventory, sales, purchases
                    and grow your business.
                </p>
            </div>

        </div>

        <!-- DECORATIVE CUBE ILLUSTRATION -->
        <div class="cube-art" aria-hidden="true">
            <div class="art-glow"></div>

            <div class="art-grid">
                <span></span><span></span>
                <span></span><span></span>
            </div>

            <div class="cube cube-main">
                <span class="cube-top"></span>
                <span class="cube-left"></span>
                <span class="cube-right"></span>
            </div>

            <div class="cube cube-small">
                <span class="cube-top"></span>
                <span class="cube-left"></span>
                <span class="cube-right"></span>
            </div>

            <div class="cube cube-front">
                <span class="cube-top"></span>
                <span class="cube-left"></span>
                <span class="cube-right"></span>
            </div>

            <div class="cube cube-back">
                <span class="cube-top"></span>
                <span class="cube-left"></span>
                <span class="cube-right"></span>
            </div>
        </div>

    </section>

    <!-- RIGHT LOGIN PANEL -->
    <section class="form-panel">

        <div class="login-box">

            <div class="login-heading">

                <div class="login-cube" aria-hidden="true">
                    <svg viewBox="0 0 64 64" fill="none">
                        <path d="M32 4 56 18v28L32 60 8 46V18L32 4Z"
                              fill="#0876F9"/>
                        <path d="m32 4 24 14-24 14L8 18 32 4Z"
                              fill="#58B1FF"/>
                        <path d="M32 32 56 18v28L32 60V32Z"
                              fill="#0061DB"/>
                        <path d="M32 32 8 18v28l24 14V32Z"
                              fill="#1889FF"/>
                        <path d="m32 13 16 9-16 9-16-9 16-9Z"
                              fill="#B7E2FF"/>
                    </svg>
                </div>

                <h2>Welcome Back</h2>
                <p>Sign in to your account</p>

            </div>

            @if(session('error'))
                <div class="login-alert login-alert-error" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            @if(session('status'))
                <div class="login-alert login-alert-status" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <form action="{{ route('login.submit') }}"
                  method="POST"
                  class="login-form">

                @csrf

                <div class="field-group">
                    <label for="email">Email Address</label>

                    <div class="input-wrap">
                        <svg viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.8"
                             aria-hidden="true">
                            <rect x="3" y="5" width="18"
                                  height="14" rx="2"/>
                            <path d="m3 7 9 7 9-7"/>
                        </svg>

                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="admin@example.com"
                            autocomplete="username"
                            required
                            autofocus
                        >
                    </div>

                    @error('email')
                        <p class="field-error" role="alert">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="field-group">
                    <label for="password">Password</label>

                    <div class="input-wrap">
                        <svg viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.8"
                             aria-hidden="true">
                            <rect x="5" y="10" width="14"
                                  height="11" rx="2"/>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                        </svg>

                        <input
                            id="password"
                            type="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            id="togglePassword"
                            aria-label="Show password"
                            aria-pressed="false"
                        >
                            <svg viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="1.8">
                                <path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>

                    @error('password')
                        <p class="field-error" role="alert">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <label class="remember-row">
                    <input type="checkbox"
                           name="remember"
                           value="1"
                           {{ old('remember') ? 'checked' : '' }}>
                    <span>Remember me</span>
                </label>

                <button type="submit" class="sign-in-btn">
                    Sign In
                </button>

            </form>

        </div>

    </section>

</main>

</body>
</html>
