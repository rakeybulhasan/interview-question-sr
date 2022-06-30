<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';
    public $timestamps = true;
    protected $dates = ['created_at', 'updated_at'];
    protected $fillable = [
        'title', 'sku', 'description'
    ];

    public function productImages()
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'id');
    }

    public function productVariant()
    {
        return $this->belongsToMany(Variant::class, 'product_variants','product_id', 'variant_id')->withPivot('id','variant');
    }

    public function productVariantPrice()
    {
        return $this->hasMany(ProductVariantPrice::class, 'product_id', 'id');
    }

    public function productVariantPriceStr()
    {
        $str = '';
        if ($this->productVariantPrice->count()) {
            $productVariantPrice = $this->productVariantPrice;
            foreach ($productVariantPrice as $item) {
                $variantOne = $item->productVariantOne?$item->productVariantOne->variant.' /':'';
                $variantTwo = $item->productVariantTwo?$item->productVariantTwo->variant.' /':'';
                $variantThree = $item->productVariantThree?$item->productVariantThree->variant:'';
                $str .='<dt class="col-sm-3 pb-0">
                                            '.$variantOne.' '.$variantTwo.' '.$variantThree.'
                                        </dt>
                                        <dd class="col-sm-9">
                                            <dl class="row mb-0">
                                                <dt class="col-sm-4 pb-0">Price : ' . number_format($item->price,2).'</dt>
                                                <dd class="col-sm-8 pb-0">InStock : ' . number_format($item->stock,2).'</dd>
                                            </dl>
                                        </dd>';
            }
        }
        return $str;
    }
}
