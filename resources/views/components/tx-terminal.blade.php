@props(['log' => null, 'outcome' => null])

@php
    $lines = (array) ($log ?? []);

    // Read like a logbook: guarantees in green, failures in red, housekeeping
    // notes in grey, and everything else in ink.
    $lineClass = function (string $line): string {
        if (str_contains($line, 'COMMIT;')) {
            return 'kc-trace__line kc-trace__line--commit';
        }

        if (str_contains($line, 'ROLLBACK;') || str_contains($line, '[BLOCKED]')) {
            return 'kc-trace__line kc-trace__line--rollback';
        }

        if (str_starts_with($line, '--')) {
            return 'kc-trace__line kc-trace__line--note';
        }

        return 'kc-trace__line';
    };
@endphp

<div {{ $attributes->merge(['class' => 'border border-line bg-white']) }}>
    <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-1 border-b border-line px-5 py-3">
        <span class="kc-label">Live SQL Trace Log</span>
        <span class="kc-meta">
            {{ config('database.default') }} connection
            @if ($outcome)
                &middot; {{ $outcome }}
            @endif
        </span>
    </div>

    <div class="kc-trace overflow-x-auto px-5 py-4" id="txTerminalLog">
        @forelse ($lines as $line)
            <span class="{{ $lineClass($line) }}">{{ $line }}</span>
        @empty
            <span class="kc-trace__line kc-trace__line--note">&gt; Awaiting Staff Operator action on this ticket&hellip;</span>
            <span class="kc-trace__line">&gt; System ready. Prepared statement: ORDER_PROCESSING_TRANSACTION</span>
            <span class="kc-trace__line">&gt; Locks: SELECT &hellip; FOR UPDATE on orders + products</span>
        @endforelse
    </div>
</div>
