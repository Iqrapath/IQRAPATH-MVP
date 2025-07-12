@component('mail::message')
# {{ $title }}

{!! nl2br(e($body)) !!}

@if(isset($metadata['action_url']) && isset($metadata['action_text']))
@component('mail::button', ['url' => $metadata['action_url']])
{{ $metadata['action_text'] }}
@endcomponent
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent 