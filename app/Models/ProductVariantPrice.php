<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantPrice extends Model
{


    public function productVariantOne(){

        return $this->belongsTo(ProductVariant::class,'product_variant_one');
    }
    public function productVariantTwo(){

        return $this->belongsTo(ProductVariant::class,'product_variant_two');
    }
    public function productVariantThree(){

        return $this->belongsTo(ProductVariant::class,'product_variant_three');
    }

    public function productVariantPriceName()
    {
        $name = '';
        if($this->productVariantOne){
            $name .= $this->productVariantOne->variant;
        }
        if($this->productVariantTwo){
            $name .=' / ';
            $name .= $this->productVariantTwo->variant;
        }
        if($this->productVariantThree){
            $name .=' / ';
            $name .= $this->productVariantThree->variant;
        }
        return $name;
    }

}
