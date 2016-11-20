<?php namespace Faddle\Middleware\Utils;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Utilities used by router middlewares.
 */
trait CacheTrait
{
    /**
     * Check whether the response can be cached or not.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @return bool
     */
    private static function isCacheable(RequestInterface $request, ResponseInterface $response)
    {
        if ($request->getMethod() !== 'GET') {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        //Check http headers
        $cache = self::parseCacheControl($response->getHeaderLine('Cache-Control'));

        if (in_array('no-cache', $cache) || in_array('no-store', $cache) || in_array('private', $cache)) {
            return false;
        }

        return true;
    }

    /**
     * Parses and returns the cache-control header values.
     *
     * @param string $header
     *
     * @return array
     */
    private static function parseCacheControl($header)
    {
        if (empty($header)) {
            return [];
        }

        $cache = [];

        foreach (array_map('trim', explode(',', strtolower($header))) as $part) {
            if (strpos($part, '=') === false) {
                $cache[$part] = true;
            } else {
                $part = array_map('trim', explode('=', $part, 2));
                $cache[$part[0]] = $part[1];
            }
        }

        return $cache;
    }
}
