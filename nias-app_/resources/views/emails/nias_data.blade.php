<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body        { font-family: Arial, sans-serif; font-size: 14px; color: #333; }
        .container  { max-width: 600px; margin: 0 auto; padding: 24px; }
        .header     { background: #0d6efd; color: #fff; padding: 16px 24px; border-radius: 6px 6px 0 0; }
        .header h2  { margin: 0; font-size: 18px; }
        .body       { border: 1px solid #dee2e6; border-top: none; padding: 24px; border-radius: 0 0 6px 6px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .info-table td { padding: 6px 8px; vertical-align: top; }
        .info-table td:first-child { font-weight: bold; width: 160px; color: #555; }
        .badge      { display: inline-block; padding: 2px 10px; border-radius: 12px;
                      font-size: 12px; font-weight: bold; }
        .badge-baru   { background: #fff3cd; color: #856404; }
        .badge-update { background: #cff4fc; color: #055160; }
        .keterangan { background: #f8f9fa; border-left: 4px solid #0d6efd;
                      padding: 12px 16px; border-radius: 0 4px 4px 0;
                      margin: 16px 0; font-style: italic; color: #444; }
        .footer     { margin-top: 24px; font-size: 12px; color: #888; border-top: 1px solid #eee; padding-top: 12px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>📋 Data Pendaftaran NIAS Jawa Timur</h2>
    </div>
    <div class="body">
        <p>Data pendaftaran NIAS telah dikirimkan oleh pelatih berikut:</p>

        <table class="info-table">
            <tr>
                <td>Club</td>
                <td>: <strong>{{ $namaclub }}</strong></td>
            </tr>
            <tr>
                <td>Email Pelatih</td>
                <td>: {{ $emailPelatih }}</td>
            </tr>
            <tr>
                <td>Tanggal Kirim</td>
                <td>: {{ now()->format('d F Y, H:i') }} WIB</td>
            </tr>
            <tr>
                <td>Jumlah Data</td>
                <td>:
                    <span class="badge badge-baru">Baru: {{ $jumlahBaru }}</span>
                    &nbsp;
                    <span class="badge badge-update">Update: {{ $jumlahUpdate }}</span>
                </td>
            </tr>
        </table>

        @if(trim($keterangan))
        <p><strong>Keterangan dari pelatih:</strong></p>
        <div class="keterangan">{{ $keterangan }}</div>
        @endif

        <p>File ZIP terlampir berisi:</p>
        <ul>
            <li>CSV data atlet daftar baru</li>
            <li>CSV data atlet update/perpanjang</li>
            <li>Dokumen pendukung (KK, Foto, SK Mutasi, dll)</li>
        </ul>

        <div class="footer">
            Email ini dikirim otomatis oleh sistem NIAS POSSI Jawa Timur.<br>
            Mohon tidak membalas email ini.
        </div>
    </div>
</div>
</body>
</html>
