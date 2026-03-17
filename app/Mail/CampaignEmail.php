<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $htmlContent;
    public string $plainContent;
    public string $recipientName;
    public string $emailSubject;

    public function __construct(
        string $subject,
        string $htmlContent,
        string $plainContent = '',
        string $recipientName = 'Subscriber'
    ) {
        $this->emailSubject  = $subject;
        $this->htmlContent   = $htmlContent;
        $this->plainContent  = $plainContent;
        $this->recipientName = $recipientName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->emailSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign-html',
            text: 'emails.campaign-text',
        );
    }
}
