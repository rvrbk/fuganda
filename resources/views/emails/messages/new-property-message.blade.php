<p>Hello {{ $messageModel->receiver->name ?? 'there' }},</p>

<p>You have received a new message about <strong>{{ $messageModel->property->title ?? 'a property' }}</strong>.</p>

<p><strong>From:</strong> {{ $messageModel->sender->name ?? 'A user' }}</p>
<p><strong>Message:</strong></p>
<p>{{ $messageModel->body }}</p>

<p>You can view and respond from your inbox in Fuganda.</p>
