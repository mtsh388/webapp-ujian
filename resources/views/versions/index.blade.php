<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pilih Versi Aplikasi - {{ config('app.name', 'Webapp Ujian') }}</title>
    <style>
        :root { color-scheme: light; --ink: #172033; --muted: #657089; --line: #e3e8f0; --blue: #1769e0; --navy: #112344; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: var(--ink); background: radial-gradient(circle at top left, #e8f1ff 0, transparent 36%), #f5f7fb; }
        .shell { width: min(100% - 32px, 960px); margin: 0 auto; padding: 64px 0 36px; }
        .eyebrow { display: inline-flex; gap: 8px; align-items: center; padding: 8px 12px; border: 1px solid #d6e4ff; border-radius: 999px; background: #fff; color: #2457a7; font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        h1 { margin: 20px 0 12px; font-size: clamp(30px, 5vw, 46px); line-height: 1.12; letter-spacing: -.04em; }
        .intro { max-width: 650px; margin: 0; color: var(--muted); font-size: 16px; line-height: 1.7; }
        .versions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; margin-top: 36px; }
        .card { display: flex; flex-direction: column; min-height: 280px; padding: 26px; border: 1px solid var(--line); border-radius: 22px; background: #fff; box-shadow: 0 16px 42px rgba(26, 47, 83, .06); }
        .icon { display: grid; place-items: center; width: 48px; height: 48px; border-radius: 14px; background: #edf4ff; color: var(--blue); font-size: 24px; }
        .tag { margin-top: 22px; color: #58708f; font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        h2 { margin: 8px 0 10px; font-size: 23px; letter-spacing: -.025em; }
        .description { flex: 1; margin: 0 0 24px; color: var(--muted); line-height: 1.65; font-size: 14px; }
        .button { display: inline-flex; justify-content: center; align-items: center; min-height: 46px; padding: 12px 18px; border-radius: 12px; background: var(--blue); color: #fff; font-weight: 700; text-decoration: none; transition: transform .15s ease, background .15s ease; }
        .button:hover { background: #0d55bd; transform: translateY(-1px); }
        .button.secondary { color: var(--navy); background: #eef2f8; }
        .button.secondary:hover { background: #e2e9f3; }
        .note { margin-top: 22px; color: var(--muted); font-size: 12px; line-height: 1.6; }
        footer { margin-top: 44px; padding-top: 18px; border-top: 1px solid var(--line); color: #788399; font-size: 12px; }
        @media (max-width: 640px) { .shell { padding-top: 36px; } .versions { grid-template-columns: 1fr; } .card { min-height: 250px; } }
    </style>
</head>
<body>
    <main class="shell">
        <span class="eyebrow">Portal Demo Bimbel</span>
        <h1>Pilih versi aplikasi</h1>
        <p class="intro">Pilih lingkungan aplikasi yang ingin Anda gunakan. Versi lama tetap tersedia, sementara versi terbaru membawa pembaruan fitur dan pengelolaan keuangan.</p>

        <section class="versions" aria-label="Pilihan versi aplikasi">
            <article class="card">
                <div class="icon" aria-hidden="true">◷</div>
                <div class="tag">Versi 1 · Legacy</div>
                <h2>Aplikasi lama</h2>
                <p class="description">Gunakan versi yang sudah tersedia saat ini, tanpa modul keuangan yang baru.</p>
                <a class="button secondary" href="{{ config('app.legacy_app_url', '/v1') }}">Buka Versi 1 <span aria-hidden="true">&nbsp;→</span></a>
            </article>

            <article class="card">
                <div class="icon" aria-hidden="true">↗</div>
                <div class="tag">Versi 2 · Terbaru</div>
                <h2>Aplikasi terbaru</h2>
                <p class="description">Gunakan versi terbaru dari branch main, termasuk pemasukan, tagihan, pembayaran cicilan, dan master data keuangan.</p>
                <a class="button" href="{{ url('/' . trim(config('app.route_prefix', 'v2'), '/')) }}">Buka Versi 2 <span aria-hidden="true">&nbsp;→</span></a>
            </article>
        </section>

        <p class="note">Catatan: alamat versi lama harus diarahkan ke aplikasi legacy melalui konfigurasi web server. Kedua versi dapat menggunakan database yang sama hanya setelah kompatibilitas migrasi database diperiksa.</p>
        <footer>{{ config('app.name', 'Webapp Ujian') }} · Pemilihan versi aplikasi</footer>
    </main>
</body>
</html>
