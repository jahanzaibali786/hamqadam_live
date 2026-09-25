<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Gift;
use Illuminate\Database\Seeder;

/**
 * Seeds the 10 gifts whose Lottie animations and PNG thumbnails ship inside
 * the Flutter app bundle. Asset paths are served by the app's own asset
 * system (asset paths from GET /gifts are app-bundle paths), so no file copy
 * is needed here. Prices follow sort_order × 2 and can be changed later from
 * the admin/API without touching Flutter.
 */
class GiftSeeder extends Seeder
{
    /** name => [slug, animation file, thumbnail file] — sort_order is implied by order. */
    private const GIFTS = [
        ['Roses Bouquet', 'roses_bouquet', 'gift_1_roses_bouquet.json', 'Gift1.png'],
        ['Teddy Bear', 'teddy_bear', 'gift_2_teddy_bear.json', 'Gift2.png'],
        ['Chocolate Box', 'chocolate_box', 'gift_3_chocolate_box.json', 'Gift3.png'],
        ['Perfume', 'perfume', 'gift_4_perfume.json', 'Gift4.png'],
        ['Luxury Watch', 'luxury_watch', 'gift_5_luxury_watch.json', 'Gift5.png'],
        ['Heart Balloon', 'heart_balloon', 'gift_6_heart_balloon.json', 'Gift6.png'],
        ['Flower Box', 'flower_box', 'gift_7_flower_box.json', 'Gift7.png'],
        ['Gift Card', 'gift_card', 'gift_8_gift_card.json', 'Gift8.png'],
        ['Scented Candle', 'scented_candle', 'gift_9_scented_candle.json', 'Gift9.png'],
        ['Ring Box', 'ring_box', 'gift_10_ring_box.json', 'Gift10.png'],
    ];

    public function run(): void
    {
        foreach (self::GIFTS as $index => [$name, $slug, $animation, $thumbnail]) {
            $sortOrder = $index + 1;

            Gift::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'thumbnail' => 'assets/gift_thumbnail/' . $thumbnail,
                    'animated_asset' => 'assets/animated_gifts/' . $animation,
                    // coins = sort_order × 2 (initial pricing; DB is the source of truth)
                    'coins' => $sortOrder * 2,
                    'category' => 'general',
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                ]
            );
        }
    }
}
