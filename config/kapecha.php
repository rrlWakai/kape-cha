<?php

/*
|--------------------------------------------------------------------------
| KAPE-CHA house configuration
|--------------------------------------------------------------------------
| The single source of truth for menu art direction and the printed facts in
| the masthead. The catalogue carries no image column, so a dish is matched to
| a photograph by name, falls back to its category, and finally to the house
| interior. Every frame degrades to a tonal paper plate, so an unfetchable
| image still art-directs rather than breaks.
|
| The order of 'sections' is the order the categories are printed in.
*/

return [

    /*
    |----------------------------------------------------------------------
    | Menu sections
    |----------------------------------------------------------------------
    | Printed in this order. Anything else the counter adds is appended.
    */

    'sections' => ['Coffee', 'Milk Tea', 'Food', 'Dessert'],

    /*
    |----------------------------------------------------------------------
    | Photographed sections
    |----------------------------------------------------------------------
    | These print as a photographic grid. Everything else prints as a compact
    | text list with the price carried to the margin on a leader.
    */

    'photographed' => ['Coffee', 'Milk Tea'],

    /*
    |----------------------------------------------------------------------
    | Photography
    |----------------------------------------------------------------------
    | Sourced from Unsplash. Each entry pairs a photo id with alt text that
    | describes the intended subject for assistive technology.
    */

    'photos' => [

        // House photography for the masthead and the closing colophon.
        'hero' => [
            'id' => 'photo-1749626588174-09f86a67a5aa',
            'alt' => 'Warm light falling across the long counter of an independent coffee house',
            'caption' => 'The long counter, 7am',
        ],

        'interior' => [
            'id' => 'photo-1721413152547-8ee737a9586c',
            'alt' => 'Timber and brass interior of a small coffee bar',
            'caption' => 'Timber, brass, and a single grinder',
        ],

        'craft' => [
            'id' => 'photo-1757010055723-fccb3bb5c4ce',
            'alt' => 'Espresso machine and printed menu board behind the bar',
            'caption' => 'The board, rewritten every season',
        ],

        // Curated per dish.
        'dishes' => [
            'Iced Spanish Latte' => [
                'id' => 'photo-1461023058943-07fcbe16d735',
                'alt' => 'Iced Spanish latte in a tall glass over ice',
            ],
            'Caramel Macchiato' => [
                'id' => 'photo-1563090308-5a7889e40542',
                'alt' => 'Caramel macchiato served in a clear glass',
            ],
            'Cafe Mocha' => [
                'id' => 'photo-1485808191679-5f86510681a2',
                'alt' => 'Cafe mocha with cocoa and steamed milk',
            ],
            'Iced Americano' => [
                'id' => 'photo-1535092169293-83be7b5434a1',
                'alt' => 'Iced Americano served over ice',
            ],
            'Brown Sugar Milk Tea' => [
                'id' => 'photo-1774979300628-12e379acea7b',
                'alt' => 'Brown sugar milk tea with tapioca pearls',
            ],
            'Wintermelon Milk Tea' => [
                'id' => 'photo-1774979300682-f92e5988d55b',
                'alt' => 'Wintermelon milk tea held over a bed of ice',
            ],
            'Okinawa Milk Tea' => [
                'id' => 'photo-1529474944862-bf4949bd2f1a',
                'alt' => 'Okinawa milk tea in a chilled tumbler',
            ],
            'Matcha Latte' => [
                'id' => 'photo-1775846933630-3c1531299e5a',
                'alt' => 'Iced matcha latte with milk and ice',
            ],
            'Chicken Sandwich' => [
                'id' => 'photo-1528735602780-2552fd46c7af',
                'alt' => 'Grilled chicken sandwich on toasted bread',
            ],
            'Burger' => [
                'id' => 'photo-1568901346375-23c9450c58cd',
                'alt' => 'Beef burger with lettuce and cheddar on a sesame bun',
            ],
            'Chocolate Waffle' => [
                'id' => 'photo-1562376552-0d160a2f238d',
                'alt' => 'Belgian waffle with chocolate and cream',
            ],
            'Chocolate Cake' => [
                'id' => 'photo-1578985545062-69928b1d9587',
                'alt' => 'Slice of dense chocolate cake with ganache',
            ],
        ],

        // Where a dish has no curated photograph.
        'categories' => [
            'Coffee' => [
                'id' => 'photo-1461023058943-07fcbe16d735',
                'alt' => 'Coffee served over ice',
            ],
            'Milk Tea' => [
                'id' => 'photo-1758716139428-117a17ad9a08',
                'alt' => 'Milk tea served cold',
            ],
            'Food' => [
                'id' => 'photo-1528735602780-2552fd46c7af',
                'alt' => 'A sandwich from the KAPE-CHA kitchen',
            ],
            'Dessert' => [
                'id' => 'photo-1578985545062-69928b1d9587',
                'alt' => 'A slice of cake from the KAPE-CHA kitchen',
            ],
        ],

        // Where a dish has no category match either.
        'default' => [
            'id' => 'photo-1757010055723-fccb3bb5c4ce',
            'alt' => 'The espresso bar at KAPE-CHA',
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Composition lines
    |----------------------------------------------------------------------
    | The formula a barista would call out, printed under the dish name.
    | Products added through the admin console fall back to a category
    | reading.
    */

    'notes' => [
        'Iced Spanish Latte' => 'Espresso · Milk · Vanilla',
        'Caramel Macchiato' => 'Espresso · Caramel · Milk Foam',
        'Cafe Mocha' => 'Espresso · Cocoa · Steamed Milk',
        'Iced Americano' => 'Espresso · Cold Water · Nothing Else',
        'Brown Sugar Milk Tea' => 'Black Tea · Brown Sugar · Boba',
        'Wintermelon Milk Tea' => 'Jasmine · Wintermelon · Cream',
        'Okinawa Milk Tea' => 'Oolong · Okinawa Sugar · Boba',
        'Matcha Latte' => 'Ceremonial Matcha · Milk · Oat',
        'Chicken Sandwich' => 'Chicken · Cheddar · Herb Mayo',
        'Burger' => 'Beef · Cheddar · House Sauce',
        'Chocolate Waffle' => 'Belgian Waffle · Dark Chocolate · Vanilla Cream',
        'Chocolate Cake' => 'Chocolate Sponge · Ganache · Cocoa Glaze',
    ],

    'category_notes' => [
        'Coffee' => 'Brewed to order',
        'Milk Tea' => 'Chilled, brewed daily',
        'Food' => 'From the kitchen, until sold out',
        'Dessert' => 'Baked each morning',
    ],

    'default_note' => 'Made fresh at the counter',

    /*
    |----------------------------------------------------------------------
    | The printed facts
    |----------------------------------------------------------------------
    | Rendered in the masthead's business column and in the closing colophon.
    */

    'house' => [
        'name' => 'KAPE-CHA',
        'tagline' => 'Brewed & Boba',
        'address' => '18 Katipunan Ave, Quezon City',
        'contact' => '0917 555 0142',
        'hours' => [
            'Monday to Friday' => '07:00 — 21:00',
            'Saturday to Sunday' => '08:00 — 22:00',
        ],
        'hours_note' => 'Open daily. Last pour thirty minutes before close.',
        'promise' => 'Brewed in small batches, served without hurry.',
        'promise_note' => 'Two origins on rotation, one grinder, and a barista who knows your order by the third visit.',
        'established' => 'Established 2019',
    ],

    /*
    |----------------------------------------------------------------------
    | Order rules
    |----------------------------------------------------------------------
    | The most any one line of a customer order may hold. A counter ticket is
    | read by a person standing at a machine, so a line past this is a
    | wholesale order and belongs on a different form.
    |
    | The quantity inputs are rendered from this same number, so the control can
    | never offer a quantity the server would then refuse.
    */

    'max_per_line' => 99,
];
