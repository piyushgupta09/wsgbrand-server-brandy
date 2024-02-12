<?php

namespace Fpaipl\Brandy\Http\Fetchers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseFetcher;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Exception;
use GuzzleHttp\Client;

class Fetcher extends BaseFetcher
{
    use AuthorizesRequests, ValidatesRequests;

    public $fetcher;

    public function __construct($baseUri){
        // Intiating Http client
        $this->fetcher = new Client([
            'base_uri' => $baseUri,
            // You can set any number of default request options.
            'timeout'  => 5.0,
        ]);
    }

    public function makeApiRequest($method, $endpoint, $params = null, $body = null, $debug = false)
    {
        $cacheKey = 'api_request_'.md5($method.$endpoint.$params.json_encode($body));
        $cacheDuration = config('api.cache.duration', 60); // Default to 60 minutes, or get from config

        // Check if the response is already in the cache
        if ($cachedResponse = Cache::get($cacheKey)) {
            return $cachedResponse;
        }
        
        try {
            // Calling the API for data
            if(strtolower($method) == 'get'){
                $response = $this->fetcher->$method($endpoint . $params);
            } else if(strtolower($method) == 'post'){
                $response = $this->fetcher->$method($endpoint . $params, ['json' => $body] );
            }

            if($debug) {
               return $response;
            }
            // Get the HTTP status code
            $statusCode = $response->getStatusCode();
            // Get the response body as a string
            $body = $response->getBody()->getContents(); 
            $data = json_decode($body);
            // Process the response data based on the status code and body
            if ($statusCode === 200) {
                // Successful response, sent back for processing and handling    
                $data->statusCode = $statusCode;    
                // Cache the response for future requests
                Cache::put($cacheKey, $data, $cacheDuration);      
                return $data;
            } else {
                throw new Exception('Server Error, please try again after some time.');
            }
        } catch (Exception $e) {

            // Handle GuzzleHttp request exceptions
            // For example, log the exception or rethrow it

            $response = [
                'data' => null,
                'status' => config('api.error'),
                'message' => '',
                'statusCode' => 404,
            ];

            if ($e instanceof RequestException && $e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                if ($statusCode === 422) {
                    $responseBody = $e->getResponse()->getBody()->getContents();
                    $errors = json_decode($responseBody);
                    // set statusCode to 422 to show request validation error.
                    $response['statusCode'] =  $statusCode;
                    $response['message'] =  $errors;
                    return (object)$response;
                    // Handle validation errors
                    // The $errors variable now contains the validation errors returned by the server
                    // You can log them, display them to the user, or take appropriate action
                    // var_dump($errors);
                } else {
                    // Handle other HTTP error status codes
                    $response['message'] =  $e->getMessage();
                    return (object)$response;
                }
            } else if($e instanceof ConnectException) {
                // Handle Server connection timeout error
                $response['statusCode'] =  500;
                $response['message'] =  'Connection timeout, please try again.';
                return (object)$response;
            } else {
                // Handle other exceptions
                $response['message'] =  $e->getMessage();
                return (object)$response;
            }

        }
    }

    public function api_secret(){
        return 'api_secret=api123';
    }
}