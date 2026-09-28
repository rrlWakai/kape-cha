@php
    $val = fn (string $key, $fallback = null) => old($key, $fallback);
@endphp

{{--
    Shared by the create and edit screens.

    Everything the operator typed is preserved on a failed save: only the
    fields they actually left alone fall back to what is on record, so a
    rejected price never costs them a carefully written description.
--}}
<div class="space-y-6">
    <div>
        <label for="product_name" class="kc-label">
            Product Name<span class="kc-required" aria-hidden="true">*</span>
            <span class="sr-only">(required)</span>
        </label>
        <input id="product_name" name="product_name" type="text" required
               maxlength="100"
               value="{{ $val('product_name', $product->product_name ?? null) }}"
               class="kc-field mt-2"
               placeholder="Iced Spanish Latte"
               @if ($errors->has('product_name')) aria-invalid="true" aria-describedby="product_name-error" @endif>
        <x-field-error for="product_name" focus="product_name" />
    </div>

    <div class="grid gap-6 sm:grid-cols-3">
        <div>
            <label for="category" class="kc-label">
                Category<span class="kc-required" aria-hidden="true">*</span>
                <span class="sr-only">(required)</span>
            </label>
            <select id="category" name="category" required
                    class="kc-field kc-field--select mt-2"
                    @if ($errors->has('category')) aria-invalid="true" aria-describedby="category-error" @endif>
                <option value="">Select category</option>
                @foreach (config('kapecha.sections') as $section)
                    <option value="{{ $section }}"
                        @selected($val('category', $product->category ?? null) === $section)>
                        {{ $section }}
                    </option>
                @endforeach
            </select>
            <x-field-error for="category" focus="category" />
        </div>

        <div>
            <label for="price" class="kc-label">
                Price<span class="kc-required" aria-hidden="true">*</span>
                <span class="sr-only">(required)</span>
            </label>
            {{-- min is 0, not 0.01: a free taster is a legitimate thing to
                 enter, and the server accepts it. --}}
            <input id="price" name="price" type="number" required
                   step="0.01" min="0"
                   value="{{ $val('price', $product->price ?? null) }}"
                   class="kc-field mt-2 tabular"
                   placeholder="0.00"
                   @if ($errors->has('price')) aria-invalid="true" aria-describedby="price-error" @endif>
            <x-field-error for="price" focus="price" />
        </div>

        <div>
            <label for="quantity" class="kc-label">
                Quantity<span class="kc-required" aria-hidden="true">*</span>
                <span class="sr-only">(required)</span>
            </label>
            <input id="quantity" name="quantity" type="number" required
                   step="1" min="0"
                   value="{{ $val('quantity', $product->quantity ?? 0) }}"
                   class="kc-field mt-2 tabular"
                   placeholder="0"
                   @if ($errors->has('quantity')) aria-invalid="true" aria-describedby="quantity-error" @endif>
            <x-field-error for="quantity" focus="quantity" />
        </div>
    </div>

    <div>
        <label for="description" class="kc-label">Description</label>
        <textarea id="description" name="description" rows="4" maxlength="500"
                  class="kc-field mt-2 resize-y"
                  placeholder="One line a customer would want to read on the board."
                  @if ($errors->has('description')) aria-invalid="true" aria-describedby="description-error" @endif>{{ $val('description', $product->description ?? null) }}</textarea>
        <x-field-error for="description" focus="description" />
    </div>
</div>
