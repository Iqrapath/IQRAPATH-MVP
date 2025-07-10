@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'IqraPath')
<img src="{{ asset('assets/images/logo/IqraPath-logo.png') }}" class="logo" alt="IqraPath Logo">
@else
{{ $slot }}
@endif
</a>
</td>
</tr> 