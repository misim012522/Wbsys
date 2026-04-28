@props(['url'])
<tr>
<td class="header">
@if (trim($slot) === 'Laravel')
<a href="{{ $url }}" style="display: inline-block;">
<img src="https://laravel.com/img/notification-logo-v2.1.png" class="logo" alt="Laravel Logo">
</a>
@else
<div style="display: inline-block; font-weight: bold; font-size: 19px; color: #18181b; text-decoration: none; margin: 15px 0 10px 0;">
{!! $slot !!}
</div>
@endif
</td>
</tr>
