<?php

namespace App\DataIntegrators;

use App\Models\ProductSynchronization;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AsgardHandler extends ApiHandler
{
    private const URL = "https://developers.bluecollection.eu/";
    private const SUPPLIER_NAME = "Asgard";
    public function getPrefix(): string { return "AS"; }

    public function authenticate(): void
    {
        function testRequest($url)
        {
            return Http::acceptJson()
                ->withToken(session("asgard_token"))
                ->get($url . "api/categories/1");
        }
        if (empty(session("asgard_token")))
            $this->prepareToken();

        $res = testRequest(self::URL);

        if ($res->unauthorized()) {
            $this->refreshToken();
            $res = testRequest(self::URL);
        }
        if ($res->unauthorized()) {
            $this->prepareToken();
        }
    }

    public function downloadAndStoreAllProductData(ProductSynchronization $sync): void
    {
        ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["last_sync_started_at" => Carbon::now()]);

        if ($sync->product_import_enabled) {
            $categories = Http::acceptJson()
                ->withToken(session("asgard_token"))
                ->get(self::URL . "api/categories")
                ->collect("results")
                ->mapWithKeys(fn ($el) => [$el["id"] => $el["pl"]]);
            $subcategories = Http::acceptJson()
                ->withToken(session("asgard_token"))
                ->get(self::URL . "api/subcategories")
                ->collect("results")
                ->mapWithKeys(fn ($el) => [$el["id"] => $el["pl"]]);
        }

        $is_last_page = false;
        $page = 1;
        $counter = 0;
        $total = 0;

        try
        {
            while (!$is_last_page) {
                $res = $this->getData($page++);
                $total = $res["count"];

                foreach ($res["results"] as $product) {
                    if ($sync->current_external_id != null && $sync->current_external_id > $product["id"]) {
                        $counter++;
                        continue;
                    }

                    Log::debug("-- downloading product $product[id]: " . $product["index"]);
                    ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => $product["id"]]);

                    if ($sync->product_import_enabled)
                    $this->saveProduct(
                        $this->getPrefix() . $product["index"],
                        collect($product["names"])->firstWhere("language", "pl")["title"],
                        collect($product["descriptions"])->firstWhere("language", "pl")["text"],
                        $this->getPrefix() . Str::beforeLast($product["index"], "-"),
                        collect($product["prices"])->first()["pln"],
                        collect($product["image"])->sortBy("url")->pluck("url")->toArray(),
                        collect($product["image"])->sortBy("url")->pluck("url")->map(function ($url) {
                            $code = Str::afterLast($url, "/");
                            $path = "https://bluecollection.gifts/media/catalog/product/$code[0]/$code[1]/$code";

                            // test if the file really is there
                            $definitely_empty_img = file_get_contents("https://bluecollection.gifts/media/catalog/product/aaa");
                            if ($definitely_empty_img == file_get_contents($path)) {
                                $path .= ".jpg";
                            }
                            if ($definitely_empty_img == file_get_contents($path)) {
                                $path = null;
                            }

                            return $path;
                        })->toArray(),
                        $product["index"],
                        $this->processTabs($product),
                        implode(" > ", [$categories[$product["category"]], $subcategories[$product["subcategory"]]]),
                        collect($product["additional"])->firstWhere("item", "color_product")["value"]
                    );

                    [$fd_amount, $fd_date] = $this->processFutureDelivery($product["future_delivery"]);

                    if ($sync->stock_import_enabled)
                    $this->saveStock(
                        $this->getPrefix() . $product["index"],
                        $product["quantity"],
                        $fd_amount,
                        $fd_date
                    );

                    ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["progress" => (++$counter / $total) * 100]);
                }

                $is_last_page = $res["next"] == null;
            }

            ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => null, "product_import_enabled" => false]);
        }
        catch (\Exception $e)
        {
            Log::error("-- Error in " . self::SUPPLIER_NAME . ": " . $e->getMessage(), ["exception" => $e]);
        }
    }

    private function prepareToken()
    {
        $res = Http::acceptJson()
            ->post(self::URL . "api/token/", [
                "username" => env("ASGARD_API_LOGIN"),
                "password" => env("ASGARD_API_HASH_PASSWORD"),
            ]);
        session([
            "asgard_token" => $res->json("access"),
            "asgard_refresh_token" => $res->json("refresh"),
        ]);
    }

    private function refreshToken()
    {
        $res = Http::acceptJson()
            ->withToken(session("asgard_token"))
            ->post(self::URL . "api/token/refresh", [
                "refresh" => session("asgard_refresh_token"),
            ]);
        session("asgard_token", $res->json("access"));
    }

    private function getData(int $page): Collection
    {
        $this->refreshToken();
        return Http::acceptJson()
            ->withToken(session("asgard_token"))
            ->get(self::URL . "api/products-index", [
                "page" => $page,
            ])
            ->collect();
    }

    private function processFutureDelivery(array $future_delivery) {
        if (count($future_delivery) == 0)
            return [null, null];

        // wybierz najbliższą dostawę
        $future_delivery = collect($future_delivery)
            ->sortBy("date")
            ->first();

        return [$future_delivery["quantity"], Carbon::parse($future_delivery["date"])];
    }

    private function processTabs(array $product) {
        $all_fields = collect($product["additional"]);

        //! specification
        /**
         * fields to be extracted for specification
         * "item" field => label
         */
        $specification_fields = [
            "guarantee" => "Gwarancja w miesiącach",
            "pantone_color" => "Kolor Pantone produktu",
            "dimensions" => "Wymiary produktu",
            "ean_code" => "EAN",
            "custom_code" => "Kod celny",
            "color_product" => "Kolor",
            "material_pl" => "Materiał",
            "pen_nib_thickness" => "Grubość linii pisania (mm)",
            "pen_refill_type" => "Typ wkładu",
            "country_origin" => "Kraj pochodzenia",
            "ink_colour" => "Kolor wkładu",
            "soft_touch" => "Powierzchnia SOFT TOUCH",
            "length_of_writing" => "Długość pisania (metry)",
        ];
        $specification = [];
        foreach ($specification_fields as $item => $label) {
            $specification[$label] = $all_fields->firstWhere("item", $item)["value"];
        }

        //! packaging
        /**
         * fields to be extracted for specification
         * "item" field => label
         */
        $packaging_fields = [
            "unit_package" => "Opakowanie produktu",
            "unit_weight" => "Waga jednostkowa brutto (kg)",
            "package_size" => "Wymiary opakowania jednostkowego",
            "qty_package" => "Ilość sztuk w kartonie",
            "package_dimension" => "Wymiary kartonu (cm)",
            "package_weight" => "Waga kartonu (kg)",
        ];
        $packaging = [];
        foreach ($packaging_fields as $item => $label) {
            $packaging[$label] = $all_fields->firstWhere("item", $item)["value"];
        }

        //! markings
        $markings = collect($product["marking_data"])
            ->first();
        if ($markings != null) $markings = collect($markings["marking_place"])
            ->mapWithKeys(fn ($mp) => ["Znakowanie: ".$mp["name_pl"] => $mp["marking_option"][0]["marking_area_img"]])
            ->toArray();

        /**
         * each tab is an array of name and content cells
         * every content item has:
         * - heading (optional)
         * - type: table / text / tiles
         * - content: array (key => value) / string / array (label => link)
         */
        return [
            [
                "name" => "Specyfikacja",
                "cells" => [["type" => "table", "content" => array_filter($specification ?? [])]]
            ],
            [
                "name" => "Pakowanie",
                "cells" => [["type" => "table", "content" => array_filter($packaging ?? [])]],
            ],
            [
                "name" => "Obszary znakowania",
                "cells" => [["type" => "tiles", "content" => array_filter($markings ?? [])]],
            ],
        ];
    }
}
