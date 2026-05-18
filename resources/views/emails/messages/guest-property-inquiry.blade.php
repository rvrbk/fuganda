<p>Hello {{ $sellerName ?: 'Seller' }},</p>

<p>You received a guest inquiry for <strong>{{ $property->title ?? 'your property' }}</strong>.</p>

<p><strong>Guest email:</strong> {{ $guestEmail }}</p>
<p><strong>Subject:</strong> {{ $subjectLine }}</p>
<p><strong>Message:</strong></p>
<p>{{ $bodyText }}</p>

<p>Reply directly to this email to contact the guest.</p>
