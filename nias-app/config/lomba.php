<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fitur Daftar Lomba (sementara nonaktif)
    |--------------------------------------------------------------------------
    |
    | true  → fitur lomba AKTIF (halaman /lomba dan semua route lomba bisa diakses)
    | false → fitur lomba NONAKTIF (tombol di halaman utama dinonaktifkan dan
    |         semua URL /lomba/* dialihkan ke halaman utama)
    |
    | Ubah lewat file .env:  LOMBA_ENABLED=true|false
    | Setelah mengubah .env, jalankan: php artisan config:clear
    |
    */
    'enabled' => env('LOMBA_ENABLED', false),
];
