<x-guest-layout>
<div class="mx-auto max-w-[90rem] px-5 md:px-10 @if ($items->isNotEmpty()) kc-has-sticky-cta @endif">
    <div class="grid gap-12 py-12 md:py-20 lg:grid-cols-12 lg:gap-16">
        {{--
            Step one: the order itself, fully editable. Quantities are changed
            in place with a stepper, and removing a line asks first and offers
            an undo.
        --}}
        <div class="lg:col-span-7" id="your-order">
            <div class="flex items-baseline justify-between gap-4 border-b border-ink pb-5">
                <h1 class="kc-section">Your Order</h1>
                <p class="kc-meta tabular">
                    {{ $cartCount }} {{ $cartCount === 1 ? 'item' : 'items' }}
                </p>
            </div>

            @forelse ($items as $item)
                @php
                    $product = $item['product'];
                    // What this control may actually offer: the smaller of what
                    // is on the shelf and the per-line house limit, so it can
                    // never present a number the server would refuse.
                    $lineMax = min($product->quantity, (int) config('kapecha.max_per_line'));
                    $atMax = $item['quantity'] >= $lineMax;
                    // A rejected update is reported on its own line, not on
                    // every line in the order.
                    $attemptedHere = (int) old('product_id', 0) === $product->product_id;
                @endphp

                <div class="border-b border-line-soft py-7">
                    <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-3">
                        <div class="min-w-0">
                            <h2 class="kc-line__name">
                                <a href="{{ route('menu') }}#{{ $product->slug() }}" class="kc-link">{{ $product->product_name }}</a>
                            </h2>
                            <p class="kc-meta mt-1.5 tabular">@money($product->price) each</p>
                        </div>

                        <p class="kc-line__price shrink-0 tabular">@money($item['subtotal'])</p>
                    </div>

                    <p class="kc-dish__note mt-2">{{ $product->notes() }}</p>

                    {{-- Stepper plus an explicit submit: the buttons submit on
                         click when scripting is available, and the Update
                         button is the fallback when it is not. --}}
                    <form method="POST" action="{{ route('cart.update') }}"
                          class="mt-5 flex flex-wrap items-end gap-5"
                          data-stepper-form>
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->product_id }}">

                        <div>
                            <span class="kc-label" id="line-label-{{ $product->product_id }}">Quantity</span>

                            <div class="kc-stepper mt-2" data-stepper>
                                <button type="button" class="kc-stepper__btn"
                                        data-step="-1"
                                        data-autosubmit
                                        aria-label="Decrease quantity of {{ $product->product_name }}">&minus;</button>

                            <input id="line-{{ $product->product_id }}"
                                   name="quantity" type="number"
                                   class="kc-stepper__input tabular"
                                   value="{{ $attemptedHere ? old('quantity', $item['quantity']) : $item['quantity'] }}"
                                   min="1" max="{{ $lineMax }}"
                                   aria-labelledby="line-label-{{ $product->product_id }}"
                                   @if ($attemptedHere) aria-invalid="true" aria-describedby="quantity-error-{{ $product->product_id }}" @endif>


                                <button type="button" class="kc-stepper__btn"
                                        data-step="1"
                                        data-autosubmit
                                        @disabled($atMax)
                                        aria-label="Increase quantity of {{ $product->product_name }}">+</button>
                            </div>
                        </div>

                        <button type="submit" class="kc-btn kc-btn--sm"
                                data-loading-label="Updating">
                            Update
                        </button>

                        @if ($attemptedHere)
                            <x-field-error for="quantity"
                                           focus="line-{{ $product->product_id }}"
                                           id="quantity-error-{{ $product->product_id }}" />
                        @endif
                    </form>

                    <p class="kc-meta mt-2">
                        Subtotal: <span class="tabular">@money($item['subtotal'])</span>
                        @if ($atMax)
                            &middot; <span class="text-pending">This is all we have in stock.</span>
                        @endif
                    </p>

                    {{-- Removing is destructive, so it is styled as such and
                         confirmed in a dialog that can also be undone. --}}
                    <div class="mt-5">
                        <button type="button" class="kc-btn kc-btn--sm kc-btn--danger"
                                data-confirm-open="confirm-remove-{{ $product->product_id }}">
                            Remove
                        </button>
                    </div>

                    <dialog class="kc-dialog" id="confirm-remove-{{ $product->product_id }}"
                            aria-labelledby="confirm-remove-title-{{ $product->product_id }}">
                        <form method="POST" action="{{ route('cart.remove') }}">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->product_id }}">

                            <div class="kc-dialog__body">
                                <h2 class="kc-dialog__title" id="confirm-remove-title-{{ $product->product_id }}">
                                    Remove this item?
                                </h2>
                                <p class="kc-dialog__note">
                                    {{ $product->product_name }} &times; {{ $item['quantity'] }}
                                    will be removed from your order.
                                </p>
                            </div>

                            <div class="kc-dialog__actions">
                                <button type="submit" class="kc-btn kc-btn--danger">
                                    Remove item
                                </button>
                                <button type="button" class="kc-btn" data-confirm-cancel>Keep item</button>
                            </div>
                        </form>
                    </dialog>
                </div>
            @empty
                {{-- An empty order is a state, not a broken form. The heading
                     says it, the body says what to do, and there is no checkout
                     control that would submit nothing. --}}
                <div class="py-16 text-center">
                    <p class="kc-label">Your order is empty</p>
                    <p class="kc-subsection mt-6">Your Order Is Empty</p>
                    <p class="kc-prose mx-auto mt-4">
                        You haven&rsquo;t added anything to your order yet.
                    </p>
                    <a href="{{ route('menu') }}" class="kc-btn kc-btn--primary kc-btn--lg mt-8">
                        Browse Menu
                    </a>
                </div>
            @endforelse

            {{-- A dish that was taken off the menu while it sat in the order
                 cannot be rendered as a line, because there is no longer a
                 product to describe. It is listed here with a way to drop it,
                 so the order is never completed with a line the customer
                 cannot see. --}}
            @foreach ($unavailable->where('missing', true) as $gone)
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border border-line p-4">
                    <p class="text-[0.9375rem]">
                        {{ $gone['name'] }} is no longer on the menu.
                    </p>

                    <form method="POST" action="{{ route('cart.remove') }}">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $gone['product_id'] }}">

                        <button type="submit" class="kc-btn kc-btn--sm">Remove</button>
                    </form>
                </div>
            @endforeach

            @if ($items->isNotEmpty())
                <div class="mt-10 flex flex-col gap-5 border-t border-ink pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('menu') }}" class="kc-btn kc-btn--ghost">Continue shopping</a>

                    <dl class="w-full space-y-3 sm:max-w-xs">
                        <div class="flex items-baseline justify-between gap-4">
                            <dt class="kc-label">Subtotal</dt>
                            <dd class="text-[0.9375rem] tabular">@money($total)</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-t border-ink pt-3">
                            <dt class="kc-label">Total</dt>
                            <dd class="kc-total tabular">@money($total)</dd>
                        </div>
                    </dl>
                </div>
            @endif
        </div>

        {{-- Step two: review and confirm. Nothing is written to the database
             until this button is pressed, so editing above can never produce
             a duplicate order. --}}
        <div class="lg:col-span-4 lg:col-start-9">
            <div class="kc-sheet p-7 md:p-8">
                <p class="kc-label">Review &amp; Confirm</p>

                <p class="kc-meta mt-3">
                    Check your items, then place the order. You can still edit anything above.
                </p>

                <form method="POST" action="{{ route('cart.checkout') }}" id="checkout-form" class="mt-6 space-y-5">
                    @csrf

                    {{-- A line whose stock moved while the order waited. The
                         numbers are shown side by side so the correction is
                         obvious without doing arithmetic. A product that has
                         been taken off the menu entirely gets its own wording,
                         because "available: 0" would be a lie about a dish
                         that no longer exists. --}}
                    @if ($unavailable->isNotEmpty())
                        <div class="kc-msg kc-msg--error" role="alert">
                            <span class="kc-msg__icon" aria-hidden="true">!</span>
                            <div class="min-w-0 flex-1">
                                <p class="kc-msg__title">Not enough stock available.</p>
                                <p class="kc-msg__body">
                                    Some items in your order are no longer available in the requested
                                    quantity. Please update the quantity before continuing.
                                </p>

                                @foreach ($unavailable as $line)
                                    <p class="kc-stock-line">
                                        <span class="kc-stock-line__name">{{ $line['name'] }}</span>
                                        @if ($line['missing'])
                                            <span class="kc-stock-line__numbers">No longer on the menu</span>
                                        @else
                                            <span class="kc-stock-line__numbers tabular">
                                                Requested: {{ $line['requested'] }}
                                                &middot; Available: {{ $line['available'] }}
                                            </span>
                                        @endif
                                    </p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div>
                        <label for="customer_name" class="kc-label">
                            Customer Name<span class="kc-required" aria-hidden="true">*</span>
                            <span class="sr-only">(required)</span>
                        </label>
                        <input id="customer_name" name="customer_name" type="text" maxlength="100"
                               required
                               autocomplete="name"
                               value="{{ old('customer_name') }}"
                               class="kc-field mt-2"
                               placeholder="Name for the ticket"
                               @disabled($items->isEmpty() || ! $unavailable->isEmpty())
                               @if ($errors->has('customer_name')) aria-invalid="true" aria-describedby="customer_name-error" @endif>
                        <x-field-error for="customer_name" focus="customer_name" />
                    </div>

                    <fieldset class="kc-fieldset" @disabled($items->isEmpty() || ! $unavailable->isEmpty())>
                        <legend class="kc-label">
                            Service Type<span class="kc-required" aria-hidden="true">*</span>
                            <span class="sr-only">(required)</span>
                        </legend>

                        {{-- A select rather than two radios, because it can hold
                             an explicit empty choice: a customer who has not
                             decided yet sees "Select service type" instead of
                             silently defaulting to Takeout and placing the
                             order at the wrong counter. --}}
                        <div class="mt-2">
                            <label for="service_type" class="sr-only">Service type</label>
                            <select id="service_type" name="service_type" required
                                    class="kc-field kc-field--select @if ($errors->has('service_type')) aria-invalid="true" aria-describedby="service_type-error" @endif">
                                <option value="">Select service type</option>
                                @foreach (['Takeout', 'Dine-in'] as $option)
                                    <option value="{{ $option }}"
                                        @selected(old('service_type') === $option)>
                                        {{ $option }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <x-field-error for="service_type" focus="service_type" />
                    </fieldset>

                    {{-- An order that cannot be fulfilled has no primary
                         action at all: a disabled button that silently does
                         nothing is worse than an obvious absence. --}}
                    @if ($unavailable->isEmpty())
                        <button type="submit" class="kc-btn kc-btn--primary kc-btn--block kc-btn--lg"
                                data-loading-label="Placing order"
                                @disabled($items->isEmpty())>
                            Confirm order
                        </button>

                        <p class="kc-meta text-center">Pay at the counter when you collect.</p>
                    @else
                        <a href="#your-order" class="kc-btn kc-btn--block kc-btn--lg">
                            Fix the quantities above
                        </a>
                    @endif
                </form>

                @if ($items->isNotEmpty())
                    <div class="mt-6 border-t border-line pt-5">
                        <a href="#your-order" class="kc-btn kc-btn--block">Edit order</a>
                        <p class="kc-hint text-center">
                            Change quantities or remove items without losing this order.
                        </p>
                    </div>
                @else
                    <p class="kc-hint mt-6 border-t border-line pt-5 text-center">
                        Add something to the order first.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- On a phone the primary action would otherwise sit below a long list.
     The button submits the real checkout form through the `form` attribute,
     so whatever the customer already typed is validated and sent rather than
     a second, half-filled copy of it. --}}
@if ($items->isNotEmpty() && $unavailable->isEmpty())
    <div class="kc-sticky-cta" role="region" aria-label="Order total and checkout">
        <div>
            <p class="kc-label">Total</p>
            <p class="tabular text-[1.0625rem] font-medium">@money($total)</p>
        </div>

        <button type="submit" form="checkout-form" class="kc-btn kc-btn--primary"
                data-loading-label="Placing order">
            Checkout
        </button>
    </div>
@endif
</x-guest-layout>
