<?php

namespace App\Support;

/**
 * Resolves the catalogue's photography.
 *
 * The products table stores no image column, so a dish's photograph is looked
 * up by name, then by category, then by the house default. Every URL is
 * requested at the size the layout actually needs, so a catalogue thumbnail
 * never downloads a full-bleed plate.
 */
class Photography
{
    public const SOURCE = 'https://images.unsplash.com/';

    /**
     * A photograph entry, resolved by dish name, then by category, then by the
     * house default.
     */
    public static function entry(?string $name = null, ?string $category = null): array
    {
        $photos = config('kapecha.photos');

        if ($name !== null && isset($photos['dishes'][$name])) {
            return $photos['dishes'][$name];
        }

        if ($category !== null && isset($photos['categories'][$category])) {
            return $photos['categories'][$category];
        }

        return $photos['default'];
    }

    /**
     * An absolute, size-appropriate image URL.
     *
     * A height of 0 leaves the crop to the browser's object-fit.
     */
    public static function url(?string $name, int $width = 1200, int $height = 0, ?string $category = null): string
    {
        return static::build(static::entry($name, $category), $width, $height);
    }

    /**
     * An absolute URL for one of the house frames — hero, interior, craft.
     */
    public static function house(string $frame, int $width = 1600, int $height = 0): string
    {
        $photos = config('kapecha.photos');
        $entry = $photos[$frame] ?? $photos['default'];

        return static::build($entry, $width, $height);
    }

    /**
     * Alt text for a photograph, describing the intended subject.
     */
    public static function alt(?string $name, ?string $category = null): string
    {
        return static::entry($name, $category)['alt'];
    }

    /**
     * The printed caption for a house frame.
     */
    public static function caption(string $frame): string
    {
        $photos = config('kapecha.photos');

        return ($photos[$frame] ?? $photos['default'])['caption'] ?? '';
    }

    /**
     * @param  array{id: string, alt: string}  $entry
     */
    private static function build(array $entry, int $width, int $height): string
    {
        $query = [
            'fm' => 'jpg',
            'q' => 74,
            'w' => $width,
            'auto' => 'format',
            'fit' => 'crop',
        ];

        if ($height > 0) {
            $query['h'] = $height;
        }

        return self::SOURCE.$entry['id'].'?'.http_build_query($query);
    }
}
