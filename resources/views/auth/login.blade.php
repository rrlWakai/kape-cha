<x-app-layout title="Staff Login — KAPE-CHA">
    <div class="flex min-h-screen flex-col">
        <header class="border-b border-line">
            <div class="mx-auto flex max-w-[90rem] items-center justify-between px-5 py-5 md:px-10 md:py-6">
                <a href="{{ route('home') }}" class="kc-wordmark">KAPE-CHA</a>
                <a href="{{ route('menu') }}" class="kc-nav">Back to the menu</a>
            </div>
        </header>

        <main class="flex flex-1 items-center justify-center px-5 py-16 md:py-24">
            <div class="w-full max-w-[24rem]">
                <div class="text-center">
                    <h1 class="kc-section">Secure Login</h1>
                    <p class="kc-prose mx-auto mt-5">
                        Staff and management access to the Kape-Cha counter.
                    </p>
                </div>

                <form method="POST" action="{{ route('login.attempt') }}" class="mt-12 space-y-5">
                    @csrf

                    <div>
                        <label for="username" class="kc-label">
                            Username<span class="kc-required" aria-hidden="true">*</span>
                            <span class="sr-only">(required)</span>
                        </label>
                        <input id="username" name="username" type="text" required autofocus
                               autocomplete="username"
                               value="{{ old('username') }}"
                               class="kc-field mt-2"
                               placeholder="Your username"
                               @if ($errors->has('username')) aria-invalid="true" aria-describedby="username-error" @endif>
                        <x-field-error for="username" focus="username" />
                    </div>

                    <div>
                        <label for="password" class="kc-label">
                            Password<span class="kc-required" aria-hidden="true">*</span>
                            <span class="sr-only">(required)</span>
                        </label>
                        {{-- Deliberately carries no value attribute. A failed attempt
                             empties the field so the password is not left on screen. --}}
                        <input id="password" name="password" type="password" required
                               autocomplete="current-password"
                               class="kc-field mt-2"
                               placeholder="Your password"
                               @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
                        <x-field-error for="password" focus="password" />
                    </div>

                    <button type="submit" class="kc-btn kc-btn--primary kc-btn--block kc-btn--lg mt-2"
                            data-loading-label="Signing in">
                        Login
                    </button>
                </form>

                <p class="kc-meta mt-10 text-center">
                    Customers do not need an account. <a href="{{ route('menu') }}" class="kc-link">Order at the counter</a>.
                </p>
            </div>
        </main>
    </div>
</x-app-layout>
