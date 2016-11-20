<?php namespace Faddle\Middleware;

use Faddle\Middleware\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to display temporary 503 maintenance pages.
 */
class Shutdown implements MiddlewareInterface {

    use Utils\HandlerTrait;

    /**
     * Execute the middleware.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param callable          $next
     *
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        $response = $this->executeHandler($request, $response);

        return $response->withStatus(503);
    }
}
