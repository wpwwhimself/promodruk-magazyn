<?php

namespace App\DataIntegrators;

use App\Models\ProductSynchronization;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class MidoceanHandler extends ApiHandler
{
    private const URL = "https://api.midocean.com/gateway/";
    private const SUPPLIER_NAME = "Midocean";
    public function getPrefix(): string { return "MO"; }

    public function authenticate(): void
    {
        // no auth required here
    }

    public function downloadAndStoreAllProductData(string $start_from = null): void
    {
        ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["last_sync_started_at" => Carbon::now()]);

        $counter = 0;
        $total = 0;

        $products = $this->getProductInfo()
            ->filter(fn ($p) => Str::startsWith($p["master_code"], $this->getPrefix()));
        $stocks = $this->getStockInfo()
            ->filter(fn ($s) => Str::startsWith($s["sku"], $this->getPrefix()));

        try
        {
            $total = $products->count();

            foreach ($products as $product) {
                if ($start_from != null && $start_from > $product["master_id"]) {
                    echo "- skipping product $product[master_id] : $product[master_code]\n";
                    $counter++;
                    continue;
                }

                foreach ($product["variants"] as $variant) {
                    echo "- downloading product " . $variant["sku"] . "\n";
                    ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => $product["master_id"]]);

                    $this->saveProduct(
                        $variant["sku"],
                        $product["product_name"],
                        $product["long_description"],
                        $product["master_code"],
                        collect($variant["digital_assets"])->sortBy("url")->map(fn ($el) => $el["url_highress"])->toArray(),
                        implode(" ", [$product["category_code"], $product["product_class"]])
                    );

                    $stock = $stocks->firstWhere("sku", $variant["sku"]);

                    $this->saveStock(
                        $variant["sku"],
                        $stock["qty"],
                        $stock["first_arrival_qty"],
                        Carbon::parse($stock["first_arrival_date"])
                    );
                }

                ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["progress" => (++$counter / $total) * 100]);
            }

            ProductSynchronization::where("supplier_name", self::SUPPLIER_NAME)->update(["current_external_id" => null]);
        }
        catch (\Exception $e)
        {
            echo($e->getMessage());
        }
    }

    private function getStockInfo(): Collection
    {
        return Http::acceptJson()
            ->withHeader("x-Gateway-APIKey", env("MIDOCEAN_API_KEY"))
            ->get(self::URL . "stock/2.0", [])
            ->collect("stock");
    }

    private function getProductInfo(): Collection
    {
        return Http::acceptJson()
            ->withHeader("x-Gateway-APIKey", env("MIDOCEAN_API_KEY"))
            ->get(self::URL . "products/2.0", [
                "language" => "pl",
            ])
            ->collect();
    }
}
