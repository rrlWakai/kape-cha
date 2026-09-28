@php
    /**
     * Every way the application talks back to the operator.
     *
     * The transaction engine writes its outcome to `commit` and `rollback`
     * rather than `success`/`error`, so those two are read here too — a
     * message the server sends but the page never renders is worse than no
     * message at all.
     *
     * Each entry is: tone, title, body. The tone sets the colour and the
     * glyph, the title and the glyph mean the message still reads correctly
     * in greyscale or with colour ignored entirely.
     */
    $messages = array_filter([
        session('success') ? ['success', 'Success', session('success')] : null,
        session('error') ? ['error', 'Error', session('error')] : null,
        session('warning') ? ['warning', 'Warning', session('warning')] : null,
        session('info') ? ['info', 'Note', session('info')] : null,
        session('commit') ? ['success', 'Processed', session('commit')] : null,
        session('rollback') ? ['error', 'Rolled back', session('rollback')] : null,
    ]);

    $glyphs = ['success' => '✓', 'error' => '!', 'warning' => '!', 'info' => 'i'];

    // A single optional call to action rides along with the newest message.
    $action = session('notice_action');

    // Field names are machine names; the summary has to read like a sentence,
    // so each one is given the label the customer actually saw on the form.
    $fieldLabels = [
        'customer_name' => 'Customer name',
        'service_type' => 'Service type',
        'product_id' => 'Item',
        'quantity' => 'Quantity',
        'product_name' => 'Product name',
        'category' => 'Category',
        'price' => 'Price',
        'description' => 'Description',
        'username' => 'Username',
        'role' => 'Role',
        'password' => 'Password',
        'password_confirmation' => 'Confirm password',
    ];

    $label = fn (string $field) => $fieldLabels[$field]
        ?? \Illuminate\Support\Str::headline(str_replace('_', ' ', $field));

    // Error pages, and any view rendered outside the HTTP stack, never get the
    // shared error bag. Defaulting here keeps this partial safe to include
    // anywhere - a crash on an error page is the worst possible moment for it.
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
@endphp

@if ($messages || $errors->any())
    @php
        $wrap = $wrap ?? 'mx-auto max-w-[90rem] px-5 pt-6 md:px-10';
    @endphp

    <div class="{{ $wrap }} mb-8 space-y-3" role="region" aria-label="Notifications">
        @foreach ($messages as [$tone, $title, $body])
            <div class="kc-msg kc-msg--{{ $tone }}"
                 @if ($tone === 'error' || $tone === 'warning') role="alert" @else role="status" @endif>
                <span class="kc-msg__icon" aria-hidden="true">{{ $glyphs[$tone] }}</span>

                <div class="min-w-0 flex-1">
                    <p class="kc-msg__title">{{ $title }}</p>
                    <p class="kc-msg__body">{{ $body }}</p>

                    @if ($loop->last && $action)
                        <div class="kc-msg__actions">
                            @if (($action['method'] ?? 'get') === 'post')
                                <form method="POST" action="{{ $action['url'] }}">
                                    @csrf
                                    <button type="submit" class="kc-btn kc-btn--sm">{{ $action['label'] }}</button>
                                </form>
                            @else
                                <a href="{{ $action['url'] }}" class="kc-btn kc-btn--sm">{{ $action['label'] }}</a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        {{--
            A failed submission is listed once here and repeated beside each
            control, so a customer can see the whole list at a glance and still
            find the detail where they need to fix it. Each entry links to the
            field: clicking it moves keyboard focus, not just the scrollbar.
        --}}
        @if ($errors->any())
            <div class="kc-msg kc-msg--error" role="alert">
                <span class="kc-msg__icon" aria-hidden="true">!</span>

                <div class="min-w-0 flex-1">
                    <p class="kc-msg__title">Check the form</p>
                    <p class="kc-msg__body">
                        {{-- A plain count check rather than trans_choice(): this
                             is fixed English copy, not a translatable string,
                             and the helper's argument order is easy to get
                             wrong in a way that fails at render time. --}}
                        @if ($errors->count() === 1)
                            There is 1 problem to fix. Select it below to jump to the field.
                        @else
                            There are {{ $errors->count() }} problems to fix. Select one below to jump to the field.
                        @endif
                    </p>

                    <ul class="kc-error-summary">
                        @foreach ($errors->getBags() as $bag)
                            @foreach ($bag->messages() as $field => $fieldMessages)
                                @foreach ((array) $fieldMessages as $message)
                                    <li>
                                        <a class="kc-error-summary__link" href="#{{ $field }}-error">
                                            <span class="kc-error-summary__field">{{ $label($field) }}</span>
                                            <span>{{ $message }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>
@endif
