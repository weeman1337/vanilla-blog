<?php
declare(strict_types = 1);

namespace Blog\Controllers;

use Gumlet\ImageResize;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * This controller handles thumbnail requests.
 */
class ThumbnailController
{
    public const TARGET_IMG_WIDTH = 500;
    public const TARGET_IMG_HEIGHT = 281;

    /**
     * @var CacheInterface
     */
    private $imageCache;

    /**
     * ThumbnailController constructor.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->imageCache = $container->get('thumb_cache');
    }

    /**
     * Expects a base64 encoded URL as "url" argument.
     * Fetches this image and crop/scale it to the class const values.
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response A response containing the copped/scaled image
     */
    public function __invoke(Request $request, Response $response, array $args)
    {
        $url = base64_decode($args['url']);

        if ($url === false) {
            return $this->makeFallbackResponse($response);
        }

        if ($this->checkUrlAllowed($url) === false) {
            return $this->makeFallbackResponse($response);
        }

        try {
            $image = $this->processImage($url);
        } catch (\Throwable $e) {
            return $this->makeFallbackResponse($response);
        }

        $response->write($image->getImageAsString());
        $mime = image_type_to_mime_type($image->source_type);
        return $response->withHeader('Content-Type', $mime);
    }

    private function processImage(string $url): ImageResize
    {
        $key = md5($url);

        if ($this->imageCache->has($key) === false) {
            $temp = $this->fetchImage($url);
            $image = new ImageResize($temp);
            $image->crop(static::TARGET_IMG_WIDTH, static::TARGET_IMG_HEIGHT, true);
            $this->imageCache->set($key, base64_encode($image->getImageAsString()));
        } else {
            $image = ImageResize::createFromString(base64_decode($this->imageCache->get($key)));
        }

        return $image;
    }

    /**
     * Checks whether the URL is allowed to prevent abuse as image proxy.
     *
     * @param string $url
     * @return bool
     */
    private function checkUrlAllowed(string $url): bool
    {
        return strpos($url, 'https://admin.insights.ubuntu.com') === 0;
    }

    /**
     * Fetches an image from the remote url to a local temp file.
     *
     * @param string $url The url
     * @return string The temp file path
     */
    private function fetchImage(string $url): string
    {
        $temp = tempnam(sys_get_temp_dir(), 'blog_thumb');
        copy($url, $temp);
        return $temp;
    }

    /**
     * Returns a response containing a generic fallback image.
     *
     * @param Response $response
     * @return Response
     */
    private function makeFallbackResponse(Response $response): Response
    {
        $fallbackImage = file_get_contents(__DIR__ . '/../img/fallback.png');
        $response->write($fallbackImage);
        return $response->withHeader('Content-Type', 'image/png');
    }
}
