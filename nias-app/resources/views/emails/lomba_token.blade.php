<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Token Login Lomba</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 480px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 32px 28px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .logo { text-align: center; margin-bottom: 24px; }
        .logo span { font-size: 20px; font-weight: 700; color: #003d8f; }
        .token-box { background: #e8f0fb; border: 2px dashed #003d8f; border-radius: 8px; padding: 16px; text-align: center; margin: 20px 0; }
        .token-text { font-size: 28px; font-weight: 700; letter-spacing: 4px; color: #003d8f; font-family: 'Courier New', monospace; }
        .info { color: #666; font-size: 14px; line-height: 1.6; }
        .expire { color: #e65100; font-size: 13px; margin-top: 16px; }
        .footer { margin-top: 24px; font-size: 12px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <span>🏊 POSSI Jawa Timur</span>
        </div>
        <p class="info">Halo,</p>
        <p class="info">Berikut token login untuk akses <strong>Daftar Lomba</strong>:</p>

        <div class="token-box">
            <span class="token-text">{{ $token }}</span>
        </div>

        <p class="info">Masukkan token di atas ke halaman verifikasi. Token hanya berlaku <strong>10 menit</strong>.</p>
        <p class="expire">⚠ Jika kamu tidak meminta token ini, abaikan email ini.</p>

        <div class="footer">
            POSSI Jawa Timur &mdash; Sistem Pendaftaran Lomba Finswimming
        </div>
    </div>
</body>
</html>
