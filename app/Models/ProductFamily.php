<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductFamily extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = "string";

    protected $fillable = [
        "id",
        "original_sku",
        "name",
        "description",
        "source",
        "original_category",
        "image_urls",
        "thumbnail_urls",
        "tabs",
    ];

    protected $appends = [
        "images",
        "thumbnails",
    ];

    protected $casts = [
        "image_urls" => "json",
        "thumbnail_urls" => "json",
        "tabs" => "json",
    ];

    public const CUSTOM_PRODUCT_GIVEAWAY = "@@";

    public function getImagesAttribute()
    {
        return collect($this->image_urls)
            ->sort(fn ($a, $b) => Str::beforeLast($a, ".") <=> Str::beforeLast($b, "."))
            ->merge(
                collect(Storage::allFiles("public/products/$this->id/images"))
                    ->map(fn ($path) => env("APP_URL") . Storage::url($path))
            );
    }
    public function getThumbnailsAttribute()
    {
        return collect($this->thumbnail_urls)
            ->sort(fn ($a, $b) => Str::beforeLast($a, ".") <=> Str::beforeLast($b, "."))
            ->merge(
                collect(Storage::allFiles("public/products/$this->id/thumbnails"))
                    ->map(fn ($path) => env("APP_URL") . Storage::url($path))
            );
    }
    public function getAnyThumbnailAttribute()
    {
        return $this->thumbnails?->first()
            ?? ($this->products?->count()
                ? $this->products->random()->thumbnails?->first()
                : null
            );
    }
    public function getIsCustomAttribute()
    {
        return Str::startsWith($this->id, self::CUSTOM_PRODUCT_GIVEAWAY);
    }
    public function getSupplierAttribute()
    {
        return $this->is_custom
            ? CustomSupplier::find(Str::after($this->source, self::CUSTOM_PRODUCT_GIVEAWAY))
            : ProductSynchronization::where("supplier_name", $this->source)->first();
    }
    public function getPrefixedIdAttribute()
    {
        return $this->is_custom
            ? Str::replace(self::CUSTOM_PRODUCT_GIVEAWAY, $this->supplier->prefix, $this->id)
            : $this->id;
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    #region helpers
    public static function newCustomProductId(): string
    {
        do {
            $random_number = Str::of(rand(0, 999999))->padLeft(6, "0");
            $random_number = implode(".", str_split($random_number, 3));
            $id = self::CUSTOM_PRODUCT_GIVEAWAY . $random_number;
        } while (ProductFamily::where("id", $id)->exists());

        return $id;
    }

    public static function getByPrefixedId(string $prefixed_id): ProductFamily
    {

        return ProductFamily::findOrFail(self::CUSTOM_PRODUCT_GIVEAWAY . substr($prefixed_id, -7));
    }
    #endregion
}
