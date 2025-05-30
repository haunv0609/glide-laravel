<?php

namespace League\Glide\Responses;

use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaravelResponseFactory implements ResponseFactoryInterface
{
    /**
     * Request object to check "is not modified".
     * @var Request|null
     */
    protected $request;

    /**
     * Create SymfonyResponseFactory instance.
     * @param Request|null $request Request object to check "is not modified".
     */
    public function __construct(?Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * Create the response.
     * @param  FilesystemOperator $cache The cache file system.
     * @param  string              $path  The cached file path.
     * @return StreamedResponse    The response object.
     */
    public function create(FilesystemOperator $cache, $path)
    {
        $stream = $cache->readStream($path);

        $response = new StreamedResponse();
        $response->headers->set('Content-Type', $cache->mimeType($path));
        $response->headers->set('Content-Length', $cache->fileSize($path));
        $response->setPublic();
        $response->setMaxAge(31536000);
        $response->setExpires(date_create()->modify('+1 years'));

        if ($this->request) {
            $response->setLastModified(date_create()->setTimestamp($cache->lastModified($path)));
            $response->isNotModified($this->request);
        }

        $response->setCallback(function () use ($stream) {
            if (ftell($stream) !== 0) {
                rewind($stream);
            }
            fpassthru($stream);
            fclose($stream);
        });

        return $response;
    }
}