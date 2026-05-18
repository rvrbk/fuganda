<?php

namespace App\Mail;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GuestPropertyInquiryMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Property $property,
        public string $sellerName,
        public string $guestEmail,
        public string $subjectLine,
        public string $body,
    ) {
    }

    public function build(): self
    {
        return $this
            ->replyTo($this->guestEmail)
            ->subject('Guest inquiry: '.$this->subjectLine)
            ->view('emails.messages.guest-property-inquiry', [
                'property' => $this->property,
                'sellerName' => $this->sellerName,
                'guestEmail' => $this->guestEmail,
                'subjectLine' => $this->subjectLine,
                'bodyText' => $this->body,
            ]);
    }
}
