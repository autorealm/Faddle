<?php namespace Faddle\Middleware;

use Faddle\Middleware\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to create basic http authentication.
 */
class BasicAuthentication implements MiddlewareInterface {

    use Utils\AuthenticationTrait;

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
        $authorization = self::parseAuthorizationHeader($request->getHeaderLine('Authorization'));

        if ($authorization && $this->checkUserPassword($authorization['username'], $authorization['password'])) {
            return $next($request, $response);
        }

        return $response
            ->withStatus(401)
            ->withHeader('WWW-Authenticate', 'Basic realm="'.$this->realm.'"');
    }

    /**
     * Validate the user and password.
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    private function checkUserPassword($username, $password)
    {
        if (!isset($this->users[$username]) || $this->users[$username] !== $password) {
            return false;
        }

        return true;
    }

    /**
     * Parses the authorization header for a basic authentication.
     *
     * @param string $header
     *
     * @return false|array
     */
    private static function parseAuthorizationHeader($header)
    {
        if (strpos($header, 'Basic') !== 0) {
            return false;
        }

        $header = explode(':', base64_decode(substr($header, 6)), 2);

        return [
            'username' => $header[0],
            'password' => isset($header[1]) ? $header[1] : null,
        ];
    }
}
