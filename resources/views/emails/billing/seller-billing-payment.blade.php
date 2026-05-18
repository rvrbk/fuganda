<p>Hello,</p>

<p><strong>{{ $headline }}</strong></p>
<p>{{ $messageBody }}</p>

@if (!empty($payload))
    <ul>
        @foreach ($payload as $label => $value)
            <li><strong>{{ $label }}:</strong> {{ $value }}</li>
        @endforeach
    </ul>
@endif

@if (!empty($actionUrl))
    <p><a href="{{ $actionUrl }}">{{ $actionLabel ?? 'Complete payment' }}</a></p>
@endif

<p>Thank you,<br>Fuganda Team</p>
