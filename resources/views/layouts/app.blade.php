<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Supplier Sync' }}</title>
    <style>
        :root{--bg:#0f172a;--card:#111827;--text:#e5e7eb;--muted:#9ca3af;--accent:#22d3ee;--green:#10b981;--red:#ef4444}
        body{background:var(--bg);color:var(--text);font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,"Noto Sans",sans-serif;margin:0}
        a{color:var(--accent);text-decoration:none}
        .container{max-width:1000px;margin:0 auto;padding:24px}
        .nav{display:flex;gap:16px;align-items:center;justify-content:space-between;margin-bottom:20px}
        .nav a{padding:8px 12px;border-radius:8px;background:var(--card);border:1px solid #1f2937}
        .card{background:var(--card);border:1px solid #1f2937;border-radius:12px;padding:16px;margin-bottom:16px}
        h1,h2{margin:0 0 12px 0}
        table{width:100%;border-collapse:collapse}
        th,td{padding:8px;border-bottom:1px solid #1f2937}
        th{color:var(--muted);text-align:left}
        .btn{display:inline-block;padding:8px 12px;border-radius:8px;border:1px solid #1f2937;background:#0b1220;color:var(--text)}
        .btn-primary{background:#0ea5e9;border-color:#0ea5e9}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .muted{color:var(--muted)}
        input,select{width:100%;padding:8px;border-radius:8px;border:1px solid #1f2937;background:#0b1220;color:var(--text)}
        .alert{padding:12px;border-radius:8px;margin-bottom:12px}
        .alert-success{background:rgba(16,185,129,.1);border:1px solid var(--green)}
        .alert-error{background:rgba(239,68,68,.1);border:1px solid var(--red)}
        .kpi{display:flex;gap:16px}
        .kpi .item{flex:1;background:linear-gradient(180deg,#0b1220,#0e1726);border:1px solid #1f2937;border-radius:12px;padding:12px}
        .kpi .value{font-size:20px;font-weight:600}
        .kpi .label{color:var(--muted)}
    </style>
    <!-- Optional assets via Vite; removed to avoid manifest error in dev without asset build -->
</head>
<body>
<div class="container">
    <div class="nav">
        <div style="display:flex;gap:12px">
            <a href="{{ route('products.index') }}">Products</a>
            <a href="{{ route('products.sync.form') }}">Supplier Sync</a>
        </div>
        <div class="muted">Laravel Backend Assessment</div>
    </div>
    <div class="card">
        <h1>{{ $title ?? 'Supplier Sync' }}</h1>
    </div>
    {{ $slot ?? '' }}
    @yield('content')
</div>
</body>
</html>