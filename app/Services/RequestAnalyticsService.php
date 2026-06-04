<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;
use Stevebauman\Location\Facades\Location;

class RequestAnalyticsService
{
    protected $request;
    protected $agent;
    protected $ip;
    protected $userAgent;
    protected $locationData = null;

    public function __construct(?Request $request = null)
    {
        $this->request = $request ?? request();
        $this->agent = new Agent();
        $this->ip = $this->getClientIp();
        $this->userAgent = $this->request->userAgent();
        
        // Set user agent for parsing
        if ($this->userAgent) {
            $this->agent->setUserAgent($this->userAgent);
        }
    }

    /**
     * Get comprehensive request analytics
     */
    public function getAnalytics(): array
    {
        return [
            'ip' => $this->getIpInfo(),
            'device' => $this->getDeviceInfo(),
            'location' => $this->getLocationInfo(),
            'request' => $this->getRequestInfo(),
            'security' => $this->getSecurityInfo(),
            'metadata' => $this->getMetadata(),
        ];
    }

    /**
     * Get IP information
     */
    public function getIpInfo(): array
    {
        return [
            'ip_address' => $this->ip,
            'is_localhost' => $this->isLocalhost(),
            'is_vpn' => $this->isVPN(),
            'is_proxy' => $this->isProxy(),
            'type' => $this->getIpType(),
            'hostname' => $this->getHostname(),
        ];
    }

    /**
     * Get device and browser information
     */
    public function getDeviceInfo(): array
    {
        return [
            'browser' => $this->getBrowser(),
            'os' => $this->getOperatingSystem(),
            'device' => $this->getDevice(),
            'is_mobile' => $this->agent->isMobile(),
            'is_tablet' => $this->agent->isTablet(),
            'is_desktop' => $this->agent->isDesktop(),
            'is_robot' => $this->agent->isRobot(),
            'robot_name' => $this->agent->robot(),
            'languages' => $this->agent->languages(),
            'platform' => $this->agent->platform(),
            'browser_version' => $this->agent->version($this->agent->browser()),
            'platform_version' => $this->agent->version($this->agent->platform()),
        ];
    }

    /**
     * Get location information from IP
     */
    public function getLocationInfo(): array
    {
        if ($this->isLocalhost()) {
            return [
                'country' => 'Local',
                'city' => 'Localhost',
                'region' => 'Internal',
                'latitude' => null,
                'longitude' => null,
                'timezone' => config('app.timezone'),
                'isp' => 'Local Network',
                'accuracy' => 'local',
            ];
        }

        // Try multiple geolocation services with fallback
        $location = $this->getLocationWithFallback();
        
        if ($location) {
            return [
                'country' => $location['country'] ?? null,
                'country_code' => $location['country_code'] ?? null,
                'city' => $location['city'] ?? null,
                'region' => $location['region'] ?? null,
                'latitude' => $location['latitude'] ?? null,
                'longitude' => $location['longitude'] ?? null,
                'timezone' => $location['timezone'] ?? null,
                'isp' => $location['isp'] ?? null,
                'organization' => $location['organization'] ?? null,
                'accuracy' => $location['accuracy'] ?? 'unknown',
                'source' => $location['source'] ?? 'unknown',
            ];
        }

        return [
            'country' => 'Unknown',
            'city' => 'Unknown',
            'region' => 'Unknown',
            'latitude' => null,
            'longitude' => null,
            'timezone' => 'UTC',
            'isp' => 'Unknown',
            'accuracy' => 'unknown',
        ];
    }

    /**
     * Get request information
     */
    public function getRequestInfo(): array
    {
        return [
            'method' => $this->request->method(),
            'url' => $this->request->fullUrl(),
            'path' => $this->request->path(),
            'query_params' => $this->request->query(),
            'headers' => $this->getFilteredHeaders(),
            'content_type' => $this->request->header('Content-Type'),
            'accept_language' => $this->request->header('Accept-Language'),
            'referer' => $this->request->header('Referer'),
            'secure' => $this->request->secure(),
            'ajax' => $this->request->ajax(),
            'pjax' => $this->request->pjax(),
            'wants_json' => $this->request->wantsJson(),
        ];
    }

    /**
     * Get security information
     */
    public function getSecurityInfo(): array
    {
        return [
            'is_secure' => $this->request->secure(),
            'is_ajax' => $this->request->ajax(),
            'has_valid_signature' => $this->request->hasValidSignature(),
            'rate_limit_key' => $this->getRateLimitKey(),
            'session_id' => session()->getId(),
            'csrf_token' => csrf_token(),
        ];
    }

    /**
     * Get metadata
     */
    public function getMetadata(): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
            'app_version' => config('app.version', '1.0.0'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'server_addr' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
            'request_time' => $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true),
            'memory_usage' => memory_get_usage(true),
        ];
    }

    /**
     * Get location with multiple fallback services
     */
    private function getLocationWithFallback(): ?array
    {
        $cacheKey = 'ip_location_' . md5($this->ip);
        
        return Cache::remember($cacheKey, 86400, function () { // Cache for 24 hours
            // Try ipinfo.io first (requires token)
            $location = $this->getLocationFromIpInfo();
            if ($location) {
                $location['source'] = 'ipinfo.io';
                $location['accuracy'] = 'high';
                return $location;
            }

            // Try free-ipapi.com
            $location = $this->getLocationFromFreeIpApi();
            if ($location) {
                $location['source'] = 'free-ipapi.com';
                $location['accuracy'] = 'medium';
                return $location;
            }

            // Try ip-api.com
            $location = $this->getLocationFromIpApi();
            if ($location) {
                $location['source'] = 'ip-api.com';
                $location['accuracy'] = 'medium';
                return $location;
            }

            return null;
        });
    }

    /**
     * Get location from ipinfo.io (most accurate, requires token)
     */
    private function getLocationFromIpInfo(): ?array
    {
        $token = config('services.ipinfo.token');
        if (!$token) {
            return null;
        }

        try {
            $response = Http::timeout(2)
                ->withHeaders(['Accept' => 'application/json'])
                ->get("https://ipinfo.io/{$this->ip}?token={$token}");

            if ($response->successful()) {
                $data = $response->json();
                
                // Parse location from "loc" field (format: "lat,lon")
                $loc = explode(',', $data['loc'] ?? '');
                
                return [
                    'country' => $data['country'] ?? null,
                    'country_code' => $data['country'] ?? null,
                    'city' => $data['city'] ?? null,
                    'region' => $data['region'] ?? null,
                    'latitude' => $loc[0] ?? null,
                    'longitude' => $loc[1] ?? null,
                    'timezone' => $data['timezone'] ?? null,
                    'isp' => $data['org'] ?? null,
                    'organization' => $data['org'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            Log::debug('ipinfo.io geolocation failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Get location from free-ipapi.com
     */
    private function getLocationFromFreeIpApi(): ?array
    {
        try {
            $response = Http::timeout(3)
                ->get("https://freeipapi.com/api/json/{$this->ip}");

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'country' => $data['countryName'] ?? null,
                    'country_code' => $data['countryCode'] ?? null,
                    'city' => $data['cityName'] ?? null,
                    'region' => $data['regionName'] ?? null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'timezone' => $data['timeZone'] ?? null,
                    'isp' => $data['isp'] ?? null,
                    'organization' => null,
                ];
            }
        } catch (\Exception $e) {
            Log::debug('free-ipapi.com geolocation failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Get location from ip-api.com
     */
    private function getLocationFromIpApi(): ?array
    {
        try {
            $response = Http::timeout(3)
                ->get("http://ip-api.com/json/{$this->ip}?fields=status,message,country,countryCode,region,regionName,city,zip,lat,lon,timezone,isp,org,as,query");

            if ($response->successful() && $response->json('status') === 'success') {
                $data = $response->json();
                
                return [
                    'country' => $data['country'] ?? null,
                    'country_code' => $data['countryCode'] ?? null,
                    'city' => $data['city'] ?? null,
                    'region' => $data['regionName'] ?? null,
                    'latitude' => $data['lat'] ?? null,
                    'longitude' => $data['lon'] ?? null,
                    'timezone' => $data['timezone'] ?? null,
                    'isp' => $data['isp'] ?? null,
                    'organization' => $data['org'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            Log::debug('ip-api.com geolocation failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Get client IP address
     */
    private function getClientIp(): string
    {
        $ip = $this->request->ip();
        
        // Check for forwarded IPs
        if ($this->request->header('X-Forwarded-For')) {
            $ips = explode(',', $this->request->header('X-Forwarded-For'));
            $ip = trim($ips[0]);
        }
        
        if ($this->request->header('CF-Connecting-IP')) {
            $ip = $this->request->header('CF-Connecting-IP');
        }
        
        return $ip;
    }

    /**
     * Check if IP is localhost
     */
    private function isLocalhost(): bool
    {
        return in_array($this->ip, ['127.0.0.1', '::1', 'localhost']) || 
               filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false;
    }

    /**
     * Check if IP is VPN (basic check)
     */
    private function isVPN(): bool
    {
        // Simple check for common VPN/datacenter IP ranges
        $vpnRanges = [
            '104.16.0.0/12',  // Cloudflare
            '172.64.0.0/13',  // Cloudflare
            '192.64.0.0/11',  // Some VPN providers
        ];
        
        foreach ($vpnRanges as $range) {
            if ($this->ipInRange($this->ip, $range)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if IP is proxy
     */
    private function isProxy(): bool
    {
        $proxyHeaders = [
            'HTTP_VIA',
            'HTTP_X_FORWARDED_FOR', 
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_CLIENT_IP',
        ];
        
        foreach ($proxyHeaders as $header) {
            if ($this->request->server($header)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get IP type (IPv4/IPv6)
     */
    private function getIpType(): string
    {
        return filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 'IPv6' : 'IPv4';
    }

    /**
     * Get hostname from IP
     */
    private function getHostname(): ?string
    {
        if ($this->isLocalhost()) {
            return 'localhost';
        }
        
        $hostname = @gethostbyaddr($this->ip);
        return $hostname !== $this->ip ? $hostname : null;
    }

    /**
     * Get browser name
     */
    private function getBrowser(): ?string
    {
        return $this->agent->browser();
    }

    /**
     * Get operating system
     */
    private function getOperatingSystem(): ?string
    {
        return $this->agent->platform();
    }

    /**
     * Get device type
     */
    private function getDevice(): string
    {
        if ($this->agent->isMobile()) {
            return 'Mobile';
        }
        
        if ($this->agent->isTablet()) {
            return 'Tablet';
        }
        
        if ($this->agent->isDesktop()) {
            return 'Desktop';
        }
        
        if ($this->agent->isRobot()) {
            return 'Robot (' . $this->agent->robot() . ')';
        }
        
        return 'Unknown';
    }

    /**
     * Get filtered headers (remove sensitive info)
     */
    private function getFilteredHeaders(): array
    {
        $headers = $this->request->headers->all();
        
        // Remove sensitive headers
        $sensitive = [
            'authorization',
            'cookie',
            'php-auth-pw',
            'php-auth-user',
            'x-csrf-token',
            'x-xsrf-token',
        ];
        
        foreach ($sensitive as $key) {
            unset($headers[$key]);
        }
        
        return $headers;
    }

    /**
     * Get rate limit key
     */
    private function getRateLimitKey(): string
    {
        return sha1($this->ip . $this->request->userAgent() . $this->request->path());
    }

    /**
     * Check if IP is in range (CIDR notation)
     */
    private function ipInRange($ip, $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        
        list($subnet, $bits) = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        
        if ($ip === false || $subnet === false) {
            return false;
        }
        
        $mask = -1 << (32 - $bits);
        return ($ip & $mask) === ($subnet & $mask);
    }

    /**
     * Get formatted location string
     */
    public function getLocationString(): string
    {
        $location = $this->getLocationInfo();
        
        $parts = [];
        if ($location['city']) $parts[] = $location['city'];
        if ($location['region']) $parts[] = $location['region'];
        if ($location['country']) $parts[] = $location['country'];
        
        return implode(', ', $parts) ?: 'Unknown Location';
    }

    /**
     * Get simplified device string
     */
    public function getDeviceString(): string
    {
        $device = $this->getDeviceInfo();
        
        $parts = [];
        if ($device['browser']) $parts[] = $device['browser'];
        if ($device['os']) $parts[] = "on {$device['os']}";
        if ($device['device']) $parts[] = "({$device['device']})";
        
        return implode(' ', $parts) ?: 'Unknown Device';
    }

    /**
     * Get analytics summary (for logs/notifications)
     */
    public function getSummary(): array
    {
        return [
            'ip' => $this->ip,
            'location' => $this->getLocationString(),
            'device' => $this->getDeviceString(),
            'user_agent' => $this->userAgent,
            'url' => $this->request->fullUrl(),
            'method' => $this->request->method(),
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}