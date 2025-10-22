@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="https://riggcontadores.com/img/logo.png" alt="RIGG CONTADORES" width="200">

@else
{{ $slot }}
@endif
</a>
</td>
</tr>
