<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Generic mailable used for every database-driven e-mail template.
 */
class TemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{data: string, name: string, mime: string}>  $files
     */
    public function __construct(
        public string $subjectLine,
        public string $bodyText,
        public array $files = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.template', with: [
            'subjectLine' => $this->subjectLine,
            'bodyText' => $this->bodyText,
        ]);
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return array_map(
            fn (array $file) => Attachment::fromData(fn () => $file['data'], $file['name'])->withMime($file['mime']),
            $this->files,
        );
    }
}
