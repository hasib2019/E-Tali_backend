<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email verified — Tali Khata</title>
    <style>
        :root { color-scheme: light dark; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7f6; color: #1f2937;
        }
        .card {
            background: #fff; border-radius: 20px; padding: 40px 32px; max-width: 360px; width: calc(100% - 32px);
            box-shadow: 0 10px 40px rgba(0,0,0,.08); text-align: center;
        }
        .check {
            width: 72px; height: 72px; border-radius: 50%; background: #0E9F6E; color: #fff;
            display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 38px;
        }
        h1 { font-size: 22px; margin: 0 0 8px; }
        p { color: #6b7280; font-size: 15px; margin: 0 0 24px; line-height: 1.5; }
        a.btn {
            display: inline-block; background: #0E9F6E; color: #fff; text-decoration: none;
            padding: 14px 28px; border-radius: 12px; font-weight: 700; font-size: 15px;
        }
        @media (prefers-color-scheme: dark) {
            body { background: #0b1220; color: #e5e7eb; }
            .card { background: #111827; box-shadow: 0 10px 40px rgba(0,0,0,.4); }
            p { color: #9ca3af; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="check">&check;</div>
        <h1>Email verified</h1>
        <p>Your email address has been verified. You can now return to the Tali Khata app and continue.</p>
        <a class="btn" href="{{ $appUrl }}">Open Tali Khata</a>
    </div>
</body>
</html>
