<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SellerBillingPaymentMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param array<string,mixed> $payload
     */
    public function __construct(
        public string $subjectLine,
        public string $headline,
        public string $messageBody,
        public array $payload = [],
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject($this->subjectLine)
            ->view('emails.billing.seller-billing-payment');
    }
}
