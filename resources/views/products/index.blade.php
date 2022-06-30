@extends('layouts.app')

@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Products</h1>
    </div>


    <div class="card">
        <form action="" method="get" class="card-header">
            <div class="form-row justify-content-between">
                <div class="col-md-2">
                    <input type="text" name="title" value="{{isset($filters['title']) && !empty($filters['title'])? $filters['title']:'' }}" placeholder="Product Title" class="form-control">
                </div>
                <div class="col-md-2">
                    {!! Form::select('variant', $variants,[isset($filters['variant']) && !empty($filters['variant'])? $filters['variant']:''], array('class' => 'form-control','placeholder'=>'Select Variant')) !!}

                    {{--<select name="variant" id="" class="form-control">

                    </select>--}}
                </div>

                <div class="col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Price Range</span>
                        </div>
                        <input type="text" name="price_from" value="{{isset($filters['price_from']) && !empty($filters['price_from'])? $filters['price_from']:0 }}" aria-label="First name" placeholder="From" class="form-control">
                        <input type="text" name="price_to" value="{{isset($filters['price_to']) && !empty($filters['price_to'])? $filters['price_to']:'' }}" aria-label="Last name" placeholder="To" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date" value="{{isset($filters['date']) && !empty($filters['date'])? $filters['date']:'' }}" placeholder="Date" class="form-control">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary float-right"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>

        <div class="card-body">
            <div class="table-response">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Variant</th>
                        <th width="150px">Action</th>
                    </tr>
                    </thead>

                    <tbody>
                    @if($products)
                        @foreach($products as $product)
                            <tr>
                                <td>{{$loop->index+1}}</td>
                                <td>{{$product->title}} <br> Created at : {{$product->created_at->format('d-M-Y')}}</td>
                                <td>{!! $product->description !!}</td>
                                <td width="350">
                                    <dl class="row mb-0" style="height: 80px; overflow: hidden" id="variant_{{$product->id}}">
                                        {!! $product->productVariantPriceStr() !!}
                                    </dl>
                                    <button onclick="$('#variant_{{$product->id}}').toggleClass('h-auto')" class="btn btn-sm btn-link">Show more</button>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('product.edit', $product->id) }}" class="btn btn-success">Edit</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                    </tbody>

                </table>

            </div>

        </div>

        <div class="card-footer">
            <div class="row justify-content-between">
                <div class="col-md-6">
                    <p>Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} out of  {{ $products->total() }}</p>
                </div>
                <div class="col-md-3">
                    {{ $products->appends($filters)->links() }}
{{--                    {!! $products->render() !!}--}}
                </div>
            </div>
        </div>
    </div>

@endsection
