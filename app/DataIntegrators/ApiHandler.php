<?php

namespace App\DataIntegrators;

use App\Models\MainAttribute;
use App\Models\Product;
use App\Models\ProductSynchronization;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

abstract class ApiHandler
{
    private const URL = self::URL;
    private const SUPPLIER_NAME = self::SUPPLIER_NAME;
    private const PRIMARY_KEY = self::PRIMARY_KEY;
    private const SKU_KEY = self::SKU_KEY;

    abstract public function getPrefix(): string | array;

    abstract public function authenticate(): void;
    abstract public function downloadAndStoreAllProductData(ProductSynchronization $sync): void;

    public function saveProduct(
        string $id,
        string $name,
        ?string $description,
        string $product_family_id,
        ?float $price,
        array $image_urls,
        array $thumbnail_urls,
        string $original_sku,
        array $tabs = null,
        string $original_category = null,
        string $original_color_name = null,
        bool $downloadPhotos = false,
        string $source = null,
    ) {
        $product = Product::updateOrCreate(
            ["id" => $id],
            array_merge(
                compact(
                    "id",
                    "name",
                    "description",
                    "product_family_id",
                    "original_sku",
                    "original_color_name",
                    "original_category",
                    "price",
                    "tabs",
                    "source",
                ),
                [
                    "image_urls" => !$downloadPhotos ? $image_urls : null,
                    "thumbnail_urls" => !$downloadPhotos ? $thumbnail_urls : null,
                ]
            )
        );

        if ($downloadPhotos) {
            foreach ([
                "images" => $image_urls,
                "thumbnails" => $thumbnail_urls,
            ] as $type => $urls) {
                Storage::deleteDirectory("public/products/$product->id/$type");

                foreach ($urls as $url) {
                    if (empty($url)) continue;
                    try {
                        $contents = file_get_contents($url);
                        $filename = basename($url);
                        Storage::put("public/products/$product->id/$type/$filename", $contents, [
                            "visibility" => "public",
                            "directory_visibility" => "public",
                        ]);
                    } catch (\Exception $e) {
                        Log::error("> -- Error: " . $e->getMessage());
                        continue;
                    }
                }
            }
        }

        if (!MainAttribute::where("name", "like", "%$original_color_name%")->exists()) {
            MainAttribute::create([
                "name" => $original_color_name,
                "color" => ""
            ]);
        }
    }

    public function saveStock(
        string $id,
        int $current_stock,
        int $future_delivery_amount = null,
        Carbon $future_delivery_date = null,
    ) {
        Stock::updateOrCreate(
            ["id" => $id],
            compact(
                "id",
                "current_stock",
                "future_delivery_amount",
                "future_delivery_date",
            )
        );
    }
}
