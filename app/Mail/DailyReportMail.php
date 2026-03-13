<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyReportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly array $report) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Rapport quotidien J-1 — NutriSport ({$this->report['date']})");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-report',
            with: ['report' => $this->report]
        );
    }
}
