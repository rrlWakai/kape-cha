@props([])

{{-- The transaction's guarantees, printed as a colophon under the ticket.
     Each property is a word and a rule, in the order a database provides
     them. --}}
<dl {{ $attributes->merge(['class' => 'grid gap-x-10 gap-y-5 border-t border-line pt-6 sm:grid-cols-3']) }}>
    <div>
        <dt class="kc-label">Row-Level Mutex</dt>
        <dd class="kc-meta mt-2">SELECT &hellip; FOR UPDATE on the ticket and the case</dd>
    </div>
    <div>
        <dt class="kc-label">Atomic Stock Sync</dt>
        <dd class="kc-meta mt-2">One commit writes the order and the inventory together</dd>
    </div>
    <div>
        <dt class="kc-label">ACID Validated</dt>
        <dd class="kc-meta mt-2">Any failure restores every quantity it touched</dd>
    </div>
</dl>
