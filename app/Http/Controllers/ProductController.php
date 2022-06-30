<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\Input;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $filter = $request->all();

        if (!empty($filter)) {
            $products = Product::with('productVariantPrice')->where('title', '!=', 'null')
            ->where(function($products)use ($filter){
                if(isset($filter['title'])&& !empty($filter['title'])){
                    $products->where('title','like','%'.$filter['title'].'%');
                }
                if(isset($filter['date'])&& !empty($filter['date'])){
                    $products->whereBetween('created_at',[$filter['date'].' 00:00:00', $filter['date'].' 23:59:59']);
                }

            })->whereHas('productVariantPrice', function($stockModel) use ($filter) {
                    if(isset($filter['price_from'])&& !empty($filter['price_from'])&& isset($filter['price_to'])&& !empty($filter['price_to'])){
                        $stockModel->where('price', '>=', $filter['price_from']);
                        $stockModel->where('price', '<=', $filter['price_to']);
                    }
                    if(isset($filter['variant'])&& !empty($filter['variant'])){
                        $productVariant = ProductVariant::where('variant','=',$filter['variant'])->pluck('id');

                        $stockModel->where(function($productVariantModel)use ($productVariant){
                         $productVariantModel->whereIn('product_variant_one',$productVariant);
                         $productVariantModel->orWhereIn('product_variant_two',$productVariant);
                         $productVariantModel->orWhereIn('product_variant_three',$productVariant);
                        });
                    }


                })
                ->orderBy('id','DESC')->paginate(10);

        } else {
            $products = Product::orderBy('id','DESC')->paginate(10);
        }
        $variantData = DB::table('variants')
            ->leftJoin('product_variants', 'variants.id', '=', 'product_variants.variant_id')
            ->select('variants.id', 'variants.title', 'product_variants.id as pvId', 'product_variants.variant as variantName')
            ->get();
        $variants=[];
        foreach ($variantData as $item){
            $variants[$item->title][$item->variantName]=$item->variantName;
        }
        return view('products.index',['products'=>$products, 'variants'=>$variants, 'filters'=>$filter]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $product = new Product([
            'title' => $request->input('title'),
            'sku' => $request->input('sku'),
            'description' => $request->input('description')
        ]);
        $product->save();

        $product_images = $request->input('product_image');

        if(sizeof($product_images)>0){
            $product->productImages()->delete();
            foreach ($product_images as $item){
                $productImage= new ProductImage;
                $productImage->file_path=asset('uploads/'.$item);
                $productImage->created_at=new \DateTime();
                $product->productImages()->save($productImage);
            }
        }
        $product_variants = $request->input('product_variant')?$request->input('product_variant'):[];

        if(sizeof($product_variants)>0){
            foreach ($product_variants as $product_variant){
                $optionId = $product_variant['option'];
                $tags = $product_variant['tags'];
                if(sizeof($tags)>0){
                    foreach ($tags as $tag) {
                        $variantObj= ProductVariant::where('variant', $tag)->where('variant_id', $optionId)->where('product_id',$product->id)->first();
                        if(!$variantObj){
                            $productVariant= new ProductVariant();
                            $productVariant->variant=$tag;
                            $productVariant->variant_id=$optionId;
                            $productVariant->product_id=$product->id;
                            $productVariant->save();
                        }
                    }
                }
            }
        }

        $product_variant_prices = $request->input('product_variant_prices')?$request->input('product_variant_prices'):[];

        if(sizeof($product_variant_prices)>0){
            foreach ($product_variant_prices as $item){

                $variantOptions = $item['title'];
                $variantOptionExplode = explode('/', $variantOptions);

                $variantOne=isset($variantOptionExplode[0])&&$variantOptionExplode[0]!=''?$variantOptionExplode[0]:null;
                $variantTwo=isset($variantOptionExplode[1])&&$variantOptionExplode[1]!=''?$variantOptionExplode[1]:null;
                $variantThree=isset($variantOptionExplode[2])&&$variantOptionExplode[2]!=''?$variantOptionExplode[2]:null;
                $variantOneObj=null;
                if($variantOne){
                    $variantOneObj= ProductVariant::where('variant', $variantOne)->where('product_id',$product->id)->first();
                }
                $variantTwoObj=null;
                if($variantTwo){
                    $variantTwoObj= ProductVariant::where('variant', $variantTwo)->where('product_id',$product->id)->first();
                }
                $variantThreeObj=null;
                if($variantThree){
                    $variantThreeObj= ProductVariant::where('variant', $variantThree)->where('product_id',$product->id)->first();
                }

                $productVariantPrice= new ProductVariantPrice();

                $productVariantPrice->product_variant_one=$variantOneObj?$variantOneObj->id:null;
                $productVariantPrice->product_variant_two=$variantTwoObj?$variantTwoObj->id:null;
                $productVariantPrice->product_variant_three=$variantThreeObj?$variantThreeObj->id:null;
                $productVariantPrice->price=$item['price'];
                $productVariantPrice->stock=$item['stock'];
                $productVariantPrice->created_at=new \DateTime();
                $product->productVariantPrice()->save($productVariantPrice);
            }
        }

        return response()->json(['status'=>200, 'message'=>'Product created.']);
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($product)
    {
        $product = Product::with(['productVariantPrice','productImages','productVariant'])->find($product);
        $arrayVariants=[];

        if(isset($product['productVariantPrice']) && sizeof($product['productVariantPrice'])>0){

            foreach ($product['productVariantPrice'] as $key=>$product_detail){
                $product['productVariantPrice'][$key]['title']=$product_detail->productVariantPriceName();


            }
        }
        return response()->json($product);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $variants = Variant::all();
        return view('products.edit', compact('variants'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        $data = [
            'title' => $request->input('title'),
            'sku' => $request->input('sku'),
            'description' => $request->input('description')
        ];

        $product->update($data);

        $product_variants = $request->input('product_variant')?$request->input('product_variant'):[];

        if(sizeof($product_variants)>0){
            foreach ($product_variants as $product_variant){
                $optionId = $product_variant['option'];
                $tags = $product_variant['tags'];
                if(sizeof($tags)>0){
                    foreach ($tags as $tag) {
                        $variantObj= ProductVariant::where('variant', $tag)->where('variant_id', $optionId)->where('product_id',$product->id)->first();
                        if(!$variantObj){
                            $productVariant= new ProductVariant();
                            $productVariant->variant=$tag;
                            $productVariant->variant_id=$optionId;
                            $productVariant->product_id=$product->id;
                            $productVariant->save();
                        }
                    }
                }
            }
        }

        $product_variant_prices = $request->input('product_variant_prices')?$request->input('product_variant_prices'):[];

        if(sizeof($product_variant_prices)>0){
            $product->productVariantPrice()->delete();
            foreach ($product_variant_prices as $item){

                $variantOptions = $item['title'];
                $variantOptionExplode = explode('/', $variantOptions);

                $variantOne=isset($variantOptionExplode[0])&&$variantOptionExplode[0]!=''?$variantOptionExplode[0]:null;
                $variantTwo=isset($variantOptionExplode[1])&&$variantOptionExplode[1]!=''?$variantOptionExplode[1]:null;
                $variantThree=isset($variantOptionExplode[2])&&$variantOptionExplode[2]!=''?$variantOptionExplode[2]:null;
                $variantOneObj=null;
                if($variantOne){
                    $variantOneObj= ProductVariant::where('variant', $variantOne)->where('product_id',$product->id)->first();
                }
                $variantTwoObj=null;
                if($variantTwo){
                    $variantTwoObj= ProductVariant::where('variant', $variantTwo)->where('product_id',$product->id)->first();
                }
                $variantThreeObj=null;
                if($variantThree){
                    $variantThreeObj= ProductVariant::where('variant', $variantThree)->where('product_id',$product->id)->first();
                }
                $productVariantPrice= new ProductVariantPrice();
                $productVariantPrice->product_variant_one=$variantOneObj?$variantOneObj->id:null;
                $productVariantPrice->product_variant_two=$variantTwoObj?$variantTwoObj->id:null;
                $productVariantPrice->product_variant_three=$variantThreeObj?$variantThreeObj->id:null;
                $productVariantPrice->price=$item['price'];
                $productVariantPrice->stock=$item['stock'];
                $productVariantPrice->created_at=new \DateTime();
                $product->productVariantPrice()->save($productVariantPrice);
            }
        }

        $product_images = $request->input('product_image');

        if(sizeof($product_images)>0){
            $product->productImages()->delete();
            foreach ($product_images as $item){
                $productImage= new ProductImage;
                $productImage->file_path=asset('uploads/'.$item);
                $productImage->created_at=new \DateTime();
                $product->productImages()->save($productImage);
            }
        }
        return response()->json(array('status'=>200, 'message'=>'Product Updated'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
