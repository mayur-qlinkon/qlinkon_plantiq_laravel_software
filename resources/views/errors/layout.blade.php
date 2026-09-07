@php
    // ──────────────────────────────────────────────
    //  CONTACT DETAILS — edit only here
    // ──────────────────────────────────────────────
    $contact = [
        'company'  => 'Qlinkon Tech',
        'email'    => 'hi@qlinkon.com',
        'phone'    => '+91 9925180106',
        'website'  => 'qlinkon.com',
        'website_url' => 'https://qlinkon.com',
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Error') — {{ $contact['company'] }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="https://fav.farm/⚫">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f7f7f8;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            color: #1a1a1a;
        }

        .card {
            background: #ffffff;
            border-radius: 1.25rem;
            border: 1px solid #e8e8e8;
            box-shadow: 0 2px 24px rgba(0,0,0,0.06);
            padding: 3rem 2.5rem;
            max-width: 520px;
            width: 100%;
            text-align: center;
        }

        .error-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #f3f3f3;
            border: 1.5px solid #e0e0e0;
            color: #555;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 0.3rem 0.85rem;
            border-radius: 999px;
            margin-bottom: 1.5rem;
        }

        .error-badge svg {
            width: 10px; height: 10px;
            stroke: currentColor; fill: none;
            stroke-width: 2.5;
            stroke-linecap: round; stroke-linejoin: round;
        }

        .error-code {
            font-size: clamp(5rem, 20vw, 7.5rem);
            font-weight: 800;
            line-height: 1;
            color: #111111;
            margin-bottom: 0.75rem;
            letter-spacing: -0.04em;
        }

        .error-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: #111111;
            margin-bottom: 0.6rem;
        }

        .error-desc {
            font-size: 0.875rem;
            color: #777;
            line-height: 1.75;
            margin-bottom: 2rem;
        }

        .actions {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 2.5rem;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            background: #111111;
            color: #ffffff;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.6rem 1.4rem;
            border-radius: 0.6rem;
            text-decoration: none;
            transition: background 150ms ease, transform 80ms ease;
        }

        .btn-primary:hover  { background: #2a2a2a; }
        .btn-primary:active { transform: scale(0.97); }

        .btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            background: #f3f3f3;
            color: #444;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.6rem 1.4rem;
            border-radius: 0.6rem;
            text-decoration: none;
            transition: background 150ms ease;
        }

        .btn-ghost:hover { background: #e8e8e8; }

        .btn-primary svg, .btn-ghost svg {
            width: 14px; height: 14px; flex-shrink: 0;
            stroke: currentColor; fill: none;
            stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round;
        }

        .divider {
            height: 1px;
            background: #f0f0f0;
            margin-bottom: 1.75rem;
        }

        .contact-label {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #aaa;
            margin-bottom: 1rem;
        }

        .contact-grid {
            display: flex;
            gap: 0.6rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .contact-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            background: #fafafa;
            border: 1px solid #e8e8e8;
            border-radius: 0.5rem;
            padding: 0.45rem 0.9rem;
            font-size: 0.78rem;
            font-weight: 500;
            color: #444;
            text-decoration: none;
            transition: border-color 150ms ease, background 150ms ease, color 150ms ease;
        }

        .contact-chip:hover {
            border-color: #aaa;
            background: #f3f3f3;
            color: #111;
        }

        .contact-chip svg {
            width: 13px; height: 13px; flex-shrink: 0;
            stroke: currentColor; fill: none;
            stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
        }

        .brand-footer {
            margin-top: 2rem;
            font-size: 0.72rem;
            color: #c0c0c0;
        }

        .brand-footer strong {
            color: #888;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <div class="card">

        <div class="error-badge">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            @yield('badge', 'Error')
        </div>

        <div class="error-code">@yield('code', '?')</div>
        <h1 class="error-title">@yield('title', 'Something went wrong')</h1>
        <p class="error-desc">@yield('description', 'An unexpected error occurred. Please try again or contact support.')</p>

        <div class="actions">
            @yield('actions')
        </div>

        <div class="divider"></div>

        <p class="contact-label">Need help? Reach us at</p>
        <div class="contact-grid">
            <a href="mailto:{{ $contact['email'] }}" class="contact-chip">
                <svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                {{ $contact['email'] }}
            </a>
            <a href="tel:{{ preg_replace('/\s+/', '', $contact['phone']) }}" class="contact-chip">
                <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.15 12 19.79 19.79 0 0 1 1.08 3.4 2 2 0 0 1 3.05 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                {{ $contact['phone'] }}
            </a>
            <a href="{{ $contact['website_url'] }}" target="_blank" class="contact-chip">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                {{ $contact['website'] }}
            </a>
        </div>

    </div>

    <p class="brand-footer">&copy; {{ date('Y') }} <strong>{{ $contact['company'] }}</strong> — All rights reserved.</p>

</body>
</html>
