{{--
    The message shown directly beneath a field.

    $errors->get() returns an array, because a single field can fail for more
    than one reason at once — a quantity that is both not a whole number and
    over the stock limit. Rendering the first line only would leave the
    customer guessing about the rest, so every line is printed.

    $focus is the id of the control the message belongs to. The error summary
    at the top of the page links to these, and uses this id to move keyboard
    focus to the offending field rather than merely scrolling past it.

    $id is needed where one error key appears more than once on a page — a
    quantity field on every line of the cart, for instance.
--}}
@props(['for', 'focus' => null, 'id' => null])

@if ($errors->has($for))
    <p class="kc-error"
       id="{{ $id ?? $for.'-error' }}"
       @if ($focus) data-focus="{{ $focus }}" @endif
       role="alert">
        <span class="kc-error__body">
            @foreach ((array) $errors->get($for) as $line)
                <span>{{ $line }}</span>
            @endforeach
        </span>
    </p>
@endif
