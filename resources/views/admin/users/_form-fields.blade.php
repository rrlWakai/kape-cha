@php
    $val = fn (string $key, $fallback = null) => old($key, $fallback);
@endphp

{{--
    Shared by the create and edit account screens.

    The password fields are never pre-filled — there is nothing useful to
    show and something dangerous to leak. On edit, leaving both boxes blank
    means "keep the current password", which is said on the page so the
    behaviour is not a surprise.
--}}
<div class="space-y-6">
    <div>
        <label for="username" class="kc-label">
            Username<span class="kc-required" aria-hidden="true">*</span>
            <span class="sr-only">(required)</span>
        </label>
        <input id="username" name="username" type="text" required
               maxlength="50"
               autocomplete="off"
               value="{{ $val('username', $user->username ?? null) }}"
               class="kc-field mt-2"
               placeholder="barista.sarah"
               @if ($errors->has('username')) aria-invalid="true" aria-describedby="username-error" @endif>
        <x-field-error for="username" focus="username" />
        <p class="kc-hint">Letters, numbers, dots, hyphens and underscores.</p>
    </div>

    <div>
        <label for="role" class="kc-label">
            Role<span class="kc-required" aria-hidden="true">*</span>
            <span class="sr-only">(required)</span>
        </label>
        <select id="role" name="role" required
                class="kc-field kc-field--select mt-2"
                @if ($errors->has('role')) aria-invalid="true" aria-describedby="role-error" @endif>
            <option value="">Select role</option>
            @foreach (['Admin', 'Staff'] as $option)
                <option value="{{ $option }}"
                    @selected($val('role', $user->role ?? 'Staff') === $option)>
                    {{ $option }}
                </option>
            @endforeach
        </select>
        <x-field-error for="role" focus="role" />
        <p class="kc-hint">Admin manages products and accounts. Staff works the order queue.</p>
    </div>

    <fieldset class="border-t border-line pt-6">
        <legend class="kc-label">
            {{ $user ? 'New password' : 'Password' }}<span class="kc-required" aria-hidden="true">*</span>
            <span class="sr-only">(required)</span>
        </legend>

        <div class="mt-4 grid gap-6 sm:grid-cols-2">
            <div>
                <label for="password" class="kc-label">Password</label>
                <input id="password" name="password" type="password"
                       @required(! $user)
                       autocomplete="new-password"
                       class="kc-field mt-2"
                       placeholder="At least 8 characters"
                       @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
                <x-field-error for="password" focus="password" />
            </div>

            <div>
                <label for="password_confirmation" class="kc-label">Confirm Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                       @required(! $user)
                       autocomplete="new-password"
                       class="kc-field mt-2"
                       placeholder="Type it again"
                       @if ($errors->has('password_confirmation')) aria-invalid="true" aria-describedby="password_confirmation-error" @endif>
                <x-field-error for="password_confirmation" focus="password_confirmation" />
            </div>
        </div>

        @if ($user)
            <p class="kc-hint mt-3">Leave both boxes empty to keep the current password.</p>
        @endif
    </fieldset>
</div>
