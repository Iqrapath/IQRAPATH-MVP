@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'IqraQuest')
<img src="{{ asset('assets/images/logo/IqraQuest-logo.png') }}" class="logo" alt="IqraQuest Logo">
@else
{{ $slot }}
@endif
</a>
</td>
</tr> 