<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array getAnalytics()
 * @method static array getIpInfo()
 * @method static array getDeviceInfo()
 * @method static array getLocationInfo()
 * @method static array getRequestInfo()
 * @method static array getSecurityInfo()
 * @method static array getMetadata()
 * @method static string getLocationString()
 * @method static string getDeviceString()
 * @method static array getSummary()
 * 
 * @see \App\Services\RequestAnalyticsService
 */
class RequestAnalytics extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'request-analytics';
    }
}