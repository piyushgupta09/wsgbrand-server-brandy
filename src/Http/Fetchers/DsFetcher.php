<?php

namespace Fpaipl\Brandy\Http\Fetchers;

use Illuminate\Http\Request;
use Fpaipl\Brandy\Http\Fetchers\Fetcher;
use Fpaipl\Brandy\Http\Responses\ApiResponse;
use Fpaipl\Brandy\Http\Resources\DS\SkuResource;
use Fpaipl\Brandy\Http\Resources\ProductResource;
use Fpaipl\Brandy\Http\Resources\DS\ProductSkuResource;

class DsFetcher extends Fetcher
{

    public function __construct()
    {
        // parent::__construct('http://127.0.0.1:8001/api/');
        // parent::__construct('http://192.168.1.9:8001/api/');
        parent::__construct('http://192.168.1.183:8001/api/');
        // parent::__construct('http://192.168.1.184:8001/api/');
    }

    /**
    * Get All Products
    */
    public function allProducts()
    {
        $params = '?'.$this->api_secret();
        $response = $this->makeApiRequest('get', '/api/products', $params);  
        if($response->status == config('api.error')){
            return ApiResponse::error($response->message, $response->statusCode); 
        } else {
            return ApiResponse::success(ProductResource::collection($response->data));
        }
    }

    public function getAllProducts()
    {
        $params = '?'.$this->api_secret();
        $response = $this->makeApiRequest('get', '/api/products', $params);  
        return $response->data;
    }

    public function showProduct(Request $request, $sid)
    {
        $params ='?'.$this->api_secret().'&&check='.$request->check;
        $response = $this->makeApiRequest('get', '/api/products/'.$sid, $params);
        if($response->status == config('api.error')){
            return ApiResponse::error($response->message, $response->statusCode); 
        } else {
            if(isset($response->data->available) && $response->data->available == true){
                return ApiResponse::success($response->data);
            }      
            return ApiResponse::success(new ProductResource($response->data));
        }
    }
    
    public function allProductSkus(){
        $params = '?'.$this->api_secret();
        $response = $this->makeApiRequest('get', '/api/product_skus/', $params);
        if($response->status == config('api.error')){
            return ApiResponse::error($response->message, $response->statusCode); 
        } else {
            return ApiResponse::success(SkuResource::collection($response->data));
        }
    }

    public function showProductSku($sku){
        $params = '?'.$this->api_secret();
        $response = $this->makeApiRequest('get', '/api/product_skus/'.$sku, $params);
        if($response->status == config('api.error')){
            return ApiResponse::error($response->message, $response->statusCode); 
        } else {
            return ApiResponse::success(new ProductSkuResource($response->data));
        }
    }
    
}