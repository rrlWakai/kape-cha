@props(['label', 'value', 'note' => null, 'money' => false])

{{-- A back-office figure: label, one numeral, nothing boxed. A row of these
     is divided by its own rules rather than by cards. --}}
<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <p class="kc-label">{{ $label }}</p>
    <p class="kc-figure__value mt-3 tabular">{{ $money ? \App\Support\Money::format($value) : $value }}</p>

    @if ($note)
        <p class="kc-meta mt-2">{{ $note }}</p>
    @endif
</div>
