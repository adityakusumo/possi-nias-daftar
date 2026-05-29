<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class FormA3Mail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $namaKontingen,
        public string $excelPath,
        public string $excelFilename,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[FORM A3] Data Lomba - ' . $this->namaKontingen,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>Yth. Panitia POSSI Jawa Timur,</p>
<p>Berikut terlampir data FORM A3 lomba finswimming dari kontingen <strong>' . e($this->namaKontingen) . '</strong>.</p>
<p>File Excel ini siap untuk diolah lebih lanjut.</p>
<p>Terima kasih.</p>',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->excelPath)
                      ->as($this->excelFilename)
                      ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }
}
