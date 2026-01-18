<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentExpiryReminder extends Mailable
{
    use Queueable, SerializesModels;

    public $reminder;
    public $description;
    public $link;

    public function __construct($reminder, $description, $link)
    {
        $this->reminder = $reminder;
        $this->description = $description;
        $this->link = $link;
    }

    public function build()
    {
        return $this->subject($this->reminder->title)
                    ->view('email.document_expiry_reminder')
                    ->with([
                        'notification_description' => $this->description,
                        'notification_link' => $this->link
                    ]);
    }
    
}
