Hello, {{ $recipientName }}!

Subject: {{ $emailSubject }}
----------------------------------------

{{ $emailBody }}

----------------------------------------
Sent by : {{env('APP_NAME')}}  
{{ now()->format('F j, Y \a\t g:i A') }}
