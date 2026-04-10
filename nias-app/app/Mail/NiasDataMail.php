<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class NiasDataMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string  $namaclub,
        public string  $emailPelatih,
        public int     $jumlahBaru,
        public int     $jumlahUpdate,
        public string  $keterangan,
        public string  $zipPath,       // path absolut file ZIP sementara
        public string  $zipFilename,   // nama file ZIP untuk attachment
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[NIAS JATIM] Data Pendaftaran - ' . $this->namaclub . ' - ' . $this->emailPelatih,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.nias_data',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->zipPath)
                      ->as($this->zipFilename)
                      ->withMime('application/zip'),
        ];
    }
}
