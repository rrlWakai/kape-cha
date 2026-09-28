@props(['href', 'active' => false, 'count' => null])

@php
    $state = $active
        ? 'border-b border-coffee text-ink'
        : 'border-b border-transparent text-muted hover:text-ink';
    $extra = trim(($attributes->get('class') ?? '').' '.$state);
@endphp

{{-- A table-of-contents entry. The current page is marked by a rule in the
     accent, never by a filled pill. --}}
<a href="{{ $href }}"
   {{ $attributes->except('class') }}
   class="kc-nav shrink-0 whitespace-nowrap border-b pb-3 {{ $extra }}"
   @if ($active) aria-current="page" @endif>
    {{ $slot }}
    @if ($count !== null)
        <span class="ml-1.5 text-ink tabular">{{ $count }}</span>
    @endif
</a>
