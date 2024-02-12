<?php

namespace Fpaipl\Brandy\Http\Fetchers;

use Fpaipl\Brandy\Http\Fetchers\Fetcher;

class StoreFetcher extends Fetcher
{
    public function __construct(){
        parent::__construct('http://192.168.1.133:8003');
    }
}