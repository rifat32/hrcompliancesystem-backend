<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class UserLetterMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $pdf;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($pdf)
    {
        $this->pdf = $pdf;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        $subject = "Letter from " . (auth()->user()->business?(auth()->user()->business->name . " HRM"):env("APP_NAME"));

        return $this
        ->subject($subject)
        ->view('email.user_letter')
        ->attachData($this->pdf->output(), 'letter.pdf', [
        'mime' => 'application/pdf',
        ]);

    }
}
