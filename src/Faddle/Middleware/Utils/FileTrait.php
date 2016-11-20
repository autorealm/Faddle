<?php namespace Faddle\Middleware\Utils;

use Psr\Http\Message\RequestInterface;

/**
 * Common methods used by middlewares that read/write files.
 */
trait FileTrait
{
    use BasePathTrait;
    use StorageTrait;

    /**
     * Returns the filename of the response file.
     *
     * @param RequestInterface $request
     *
     * @return string
     */
    private function getFilename(RequestInterface $request)
    {
        $path = $this->getBasePath($request->getUri()->getPath());

        $parts = pathinfo($path);
        $path = isset($parts['dirname']) ? $parts['dirname'] : '';
        $filename = isset($parts['basename']) ? $parts['basename'] : '';

        //if it's a directory, append "/index.html"
        if (empty($parts['extension'])) {
            $filename .= '/index.html';
        }

        return Path::join($this->storage, $path, $filename);
    }
}
