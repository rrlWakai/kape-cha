{{--
    The add-to-order control, shared by the photograph grid and the compact
    list.

    A disclosure rather than an overlay: no scrim, no second page, and it
    works with JavaScript disabled because it is a native <details> wrapping a
    real form post. The quantity stays a real <input> inside a −/+ stepper so
    the submitted value is always correct and always editable by hand.

    Two things are kept honest here:
      * the upper bound is what can still be *added*, not what is on the
        shelf, so the control never offers a number the server will refuse;
      * a validation message appears only on the dish that was submitted,
        rather than on every dish on the page.
--}}
@php
    $available = $product->isAvailable();
    $justAdded = (int) session('added_product_id', 0) === $product->product_id;
    $attemptedHere = (int) old('product_id', 0) === $product->product_id;
    $remaining = $remaining ?? [];
    // Bounded by what can still be *added* and by the per-line house limit, so
    // the control never offers a number the server will refuse.
    $max = min(
        (int) ($remaining[$product->product_id] ?? $product->quantity),
        (int) config('kapecha.max_per_line'),
    );
    $value = $attemptedHere ? max(1, (int) old('quantity', 1)) : 1;
    $value = min($value, max(1, $max));
@endphp

@if ($available)
    @if ($justAdded)
        {{-- Confirm in place, so the customer sees which dish changed. --}}
        <div class="kc-msg kc-msg--success" role="status">
            <span class="kc-msg__icon" aria-hidden="true">&check;</span>
            <div class="min-w-0 flex-1">
                <p class="kc-msg__title">Added</p>
                <p class="kc-msg__body">{{ $product->product_name }} is in your order.</p>
                <div class="kc-msg__actions">
                    <a href="{{ route('cart.index') }}" class="kc-btn kc-btn--sm">View order</a>
                </div>
            </div>
        </div>
    @elseif ($max < 1)
        {{-- Reached the limit, so the control says why rather than hiding. --}}
        <p class="kc-meta">
            <span class="font-medium text-ink">All {{ $product->quantity }} in your order.</span>
            <a href="{{ route('cart.index') }}" class="kc-action">Change the amount in your order</a>
            if you need a different number.
        </p>
    @else
        <details class="kc-disclosure">
            <summary class="kc-action">Add to order</summary>

            <form method="POST" action="{{ route('cart.add') }}" class="mt-4 space-y-3">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->product_id }}">

                <div>
                    <span class="kc-label" id="qty-label-{{ $product->product_id }}">Quantity</span>

                    <div class="kc-stepper mt-2" data-stepper>
                        <button type="button" class="kc-stepper__btn"
                                data-step="-1"
                                aria-label="Decrease quantity of {{ $product->product_name }}">&minus;</button>

                        <input id="qty-{{ $product->product_id }}"
                               name="quantity" type="number"
                               class="kc-stepper__input tabular"
                               value="{{ $value }}" min="1" max="{{ $max }}"
                               aria-labelledby="qty-label-{{ $product->product_id }}"
                               @if ($attemptedHere) aria-invalid="true" aria-describedby="qty-error-{{ $product->product_id }}" @endif>

                        <button type="button" class="kc-stepper__btn"
                                data-step="1"
                                aria-label="Increase quantity of {{ $product->product_name }}">+</button>
                    </div>

                    <p class="kc-hint">
                        {{ $max === 1 ? 'One more can be added.' : "Up to {$max} more can be added." }}
                    </p>

                    @if ($attemptedHere)
                        <x-field-error for="quantity"
                                       focus="qty-{{ $product->product_id }}"
                                       id="qty-error-{{ $product->product_id }}" />
                    @endif
                </div>

                <button type="submit" class="kc-btn kc-btn--primary kc-btn--block"
                        data-loading-label="Adding to order">
                    Add to order
                </button>
            </form>
        </details>
    @endif
@else
    {{-- Sold out says so plainly, and says why there is nothing to press. --}}
    <p class="kc-meta">
        <span class="font-medium text-ink">Sold out today.</span>
        None left in the case, so this cannot be added to an order.
    </p>
@endif
