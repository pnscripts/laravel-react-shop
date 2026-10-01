<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Install PN Shop</title>
    {{-- Self-contained: the installer works before any assets are built or published. --}}
    <style>
        :root { --fg: #171717; --muted: #6b7280; --border: #e5e7eb; --bg: #fafafa; --card: #fff; --accent: #4f46e5; --bad: #b91c1c; --good: #15803d; }
        @media (prefers-color-scheme: dark) { :root { --fg: #f5f5f5; --muted: #a3a3a3; --border: #333; --bg: #0a0a0a; --card: #171717; --accent: #818cf8; --bad: #f87171; --good: #4ade80; } }
        * { box-sizing: border-box; }
        body { margin: 0; font: 15px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; color: var(--fg); background: var(--bg); }
        main { max-width: 640px; margin: 48px auto; padding: 0 16px; }
        h1 { font-size: 24px; margin: 0 0 4px; } h2 { font-size: 17px; margin: 24px 0 8px; }
        .muted { color: var(--muted); } .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 24px; margin-top: 24px; }
        .steps { display: flex; gap: 8px; font-size: 13px; color: var(--muted); margin-top: 8px; } .steps .on { color: var(--fg); font-weight: 600; }
        label { display: block; font-weight: 500; margin: 12px 0 4px; } .row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        input, select { width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 8px; background: var(--card); color: var(--fg); font: inherit; }
        input[type=checkbox] { width: auto; margin-right: 8px; } .check { display: flex; align-items: center; font-weight: 400; }
        button, .button { display: inline-block; margin-top: 20px; padding: 10px 18px; border: 0; border-radius: 8px; background: var(--accent); color: #fff; font: inherit; font-weight: 600; text-decoration: none; cursor: pointer; }
        button[disabled] { opacity: .5; cursor: not-allowed; }
        .error { color: var(--bad); font-size: 14px; margin-top: 4px; } .ok { color: var(--good); } .bad { color: var(--bad); }
        table { width: 100%; border-collapse: collapse; font-size: 14px; } td { padding: 6px 0; border-bottom: 1px solid var(--border); vertical-align: top; }
        td:last-child { text-align: right; white-space: nowrap; }
        @media (max-width: 520px) { .row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<main>
    <h1>Install PN Shop</h1>
    <div class="steps">
        @foreach (['Requirements', 'Database', 'Store and administrator'] as $index => $label)
            <span class="{{ $index === ($step ?? 0) ? 'on' : '' }}">{{ $index + 1 }}. {{ $label }}</span>
        @endforeach
    </div>
    <div class="card">
        @yield('content')
    </div>
</main>
</body>
</html>
