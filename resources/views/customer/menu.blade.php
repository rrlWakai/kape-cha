@php
    use App\Support\Photography;

    $house = config('kapecha.house');

    // Drinks are photographed; food and dessert print as compact text lists,
    // the way a real menu gives space to the things that need it.
    $photographed = config('kapecha.photographed');

    // The order the board is printed in, not the order the database returns.
    $rank = array_flip(config('kapecha.sections'));
    $sections = $products->groupBy('category')
        ->sortBy(fn ($items, $category) => $rank[$category] ?? count($rank));
@endphp

<x-guest-layout>
{{-- The cover: the house on the left, the practical facts on the right. --}}
<section class="border-b border-line">
    <div class="mx-auto max-w-[90rem] px-5 md:px-10">
        <div class="grid gap-10 py-12 md:grid-cols-12 md:gap-8 md:py-20">
            <div class="md:col-span-6">
                <p class="kc-wordmark kc-wordmark--large">KAPE-CHA</p>
                <p class="kc-meta mt-5">{{ $house['tagline'] }}</p>
                <p class="kc-prose mt-10">{{ $house['promise'] }}</p>
            </div>

            <div class="md:col-span-4 md:col-start-9">
                <p class="kc-label">Business Info</p>

                <dl class="mt-6 space-y-5">
                    <div>
                        <dt class="kc-label">Opening Hours</dt>
                        <dd class="mt-2 space-y-1">
                            @foreach ($house['hours'] as $days => $time)
                                <span class="flex justify-between gap-4 text-[0.8125rem]">
                                    <span class="text-muted">{{ $days }}</span>
                                    <span class="tabular">{{ $time }}</span>
                                </span>
                            @endforeach
                            <span class="mt-1 block kc-meta">{{ $house['hours_note'] }}</span>
                        </dd>
                    </div>

                    <div>
                        <dt class="kc-label">Location</dt>
                        <dd class="mt-2 text-[0.8125rem]">{{ $house['address'] }}</dd>
                    </div>

                    <div>
                        <dt class="kc-label">Contact</dt>
                        <dd class="mt-2 text-[0.8125rem] tabular">{{ $house['contact'] }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</section>

<div class="mx-auto max-w-[90rem] px-5 md:px-10">
    {{-- The index, printed across the head of the menu. --}}
    <nav class="flex flex-wrap items-baseline gap-x-7 gap-y-2 border-b border-line py-5" aria-label="Menu sections">
        @foreach ($sections as $category => $items)
            <a href="#{{ str($category)->slug() }}" class="kc-nav">{{ str($category)->lower() }}</a>
        @endforeach
    </nav>

    @forelse ($sections as $category => $items)
        <section id="{{ str($category)->slug() }}" class="scroll-mt-6 border-b border-line py-14 md:py-20">
            <div class="flex items-baseline justify-between gap-4">
                <h2 class="kc-section">{{ str($category)->lower() }}</h2>
                <p class="kc-meta shrink-0">
                    {{ $items->count() }} {{ $items->count() === 1 ? 'item' : 'items' }}
                </p>
            </div>

            @if (in_array($category, $photographed, true))
                {{-- Photographed catalogue: four across, image first, then type. --}}
                <div class="mt-10 grid grid-cols-1 gap-x-8 gap-y-14 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($items as $product)
                        <article id="{{ $product->slug() }}"
                                 class="kc-dish scroll-mt-6 @if (! $product->isAvailable()) kc-dish--sold @endif">
                            <div class="kc-photo kc-photo--zoom aspect-[4/5]">
                                <img src="{{ $product->photo(700, 875) }}"
                                     alt="{{ $product->photoAlt() }}"
                                     width="700" height="875"
                                     loading="lazy" decoding="async">
                            </div>

                            <div class="mt-5 flex flex-1 flex-col">
                                <h3 class="kc-dish__name">{{ $product->product_name }}</h3>
                                <p class="kc-dish__note mt-1.5">{{ $product->notes() }}</p>

                                <p class="kc-dish__price mt-3">@money($product->price)</p>
                                <p class="kc-dish__body mt-2">{{ $product->description }}</p>

                                <div class="mt-5 pt-1">
                                    @include('customer.partials.add-to-order', ['product' => $product])
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                {{-- Compact list: the price is carried to the margin on a
                     dotted leader, the way a printed menu sets it. --}}
                <div class="mt-8 max-w-[44rem]">
                    @foreach ($items as $product)
                        <article id="{{ $product->slug() }}"
                                 class="scroll-mt-6 border-b border-line-soft py-7 first:border-t first:pt-0 @if (! $product->isAvailable()) kc-dish--sold @endif">
                            <div class="kc-line">
                                <h3 class="kc-line__name">{{ $product->product_name }}</h3>
                                <span class="kc-leader" aria-hidden="true"></span>
                                <span class="kc-line__price">@money($product->price)</span>
                            </div>

                            <p class="kc-dish__body mt-2">{{ $product->description }}</p>
                            <p class="kc-dish__note mt-1.5">{{ $product->notes() }}</p>

                            <div class="mt-4">
                                @include('customer.partials.add-to-order', ['product' => $product])
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @empty
        <section class="py-24 text-center">
            <h2 class="kc-section">{{ str($activeCategory ?? 'menu')->lower() }}</h2>
            <p class="kc-prose mx-auto mt-6">Nothing on the board for this section today.</p>
            <a href="{{ route('menu') }}" class="kc-btn kc-btn--quiet mt-8">See the whole menu</a>
        </section>
    @endforelse
</div>

<div class="mx-auto max-w-[90rem] px-5 md:px-10">
    {{-- Closing plate: the room, the promise, and the colophon. --}}
    <section class="grid gap-10 py-16 md:grid-cols-12 md:gap-8 md:py-24">
        <figure class="md:col-span-7">
            <div class="kc-photo aspect-[3/2]">
                <img src="{{ Photography::house('craft', 1200, 800) }}"
                     alt="{{ config('kapecha.photos.craft.alt') }}"
                     width="1200" height="800"
                     loading="lazy" decoding="async">
            </div>
            <figcaption class="kc-meta mt-3">{{ Photography::caption('craft') }}</figcaption>
        </figure>

        <div class="flex flex-col justify-center md:col-span-4 md:col-start-9">
            <p class="kc-label">A Note from the Counter</p>
            <p class="kc-subsection mt-5">{{ $house['promise'] }}</p>
            <p class="kc-prose mt-5">{{ $house['promise_note'] }}</p>
            <p class="kc-meta mt-8">{{ $house['established'] }} &middot; {{ $house['address'] }}</p>
        </div>
    </section>
</div>
</x-guest-layout>
