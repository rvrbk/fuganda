<?php

namespace App\Mail;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewPropertyMessageMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Message $messageModel)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('New message about a property on Verbeek.ug Real Estates')
            ->view('emails.messages.new-property-message', [
                'messageModel' => $this->messageModel,
            ]);
    }
}
