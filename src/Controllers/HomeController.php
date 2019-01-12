<?php
declare(strict_types = 1);

namespace Blog\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\PhpRenderer;

/**
 * Home page controller.
 */
class HomeController
{
    /**
     * @var PhpRenderer
     */
    private $renderer;

    /**
     * @var string
     */
    private $postsApi;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * HomeController constructor.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->renderer = $container->get('renderer');
        $this->postsApi = $container->get('settings')['ubuntu']['posts_api'];
        $this->logger = $container->get('logger');
        $this->httpClient = $container->get('http_client');
    }

    /**
     * Handles a home page request.
     * Fetches posts from the remote API and displays them.
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(Request $request, Response $response, array $args)
    {
        $args['error'] = false;

        $posts = $this->fetchPosts();

        if ($posts === null) {
            $args['error'] = true;
        } else {
            $args['cards'] = $this->mapPosts($posts);
        }

        $args['escape'] = function ($in) {
            return htmlspecialchars($in, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
        };

        $args['escapeAttr'] = function ($in) {
            return str_replace('"', '', $in);
        };

        return $this->renderer->render($response, 'home.phtml', $args);
    }

    /**
     * Maps post data to template data.
     *
     * @param array $posts The post data.
     * @return array
     */
    private function mapPosts(array $posts): array
    {
        return array_map(function ($post) {
            return [
                'title' => $post['title']['rendered'] ?? '-',
                'date' => $this->extractDate($post),
                'author' => $post['_embedded']['author'][0]['name'] ?? '-',
                'authorLink' => $post['_embedded']['author'][0]['link'] ?? '#',
                'link' => $post['link'] ?? '#',
                'tag' => $this->extractTag($post),
                'group' => $this->extractGroup($post),
                'image' => '/thumbs/' . \base64_encode($post['_embedded']['wp:featuredmedia'][0]['source_url'] ?? ''),
                'imageAlt' => $post['_embedded']['wp:featuredmedia'][0]['alt_text'] ?? ''
            ];
        }, $posts);
    }

    /**
     * Fetches the posts from the remote API.
     *
     * @return array|null
     */
    private function fetchPosts(): ?array
    {
        try {
            $apiResponse = $this->httpClient->get($this->postsApi, [
                RequestOptions::TIMEOUT => 5,
                'query' => [
                    'per_page' => 3,
                    'page' => 1,
                    '_embed' => 'True'
                ]
            ]);

            if ($apiResponse->getStatusCode() >= 200 && $apiResponse->getStatusCode() < 300) {
                $contents = $apiResponse->getBody()->getContents();
                $data = json_decode($contents, true);

                if ($data === null) {
                    $this->logger->critical('Error decoding posts response', [
                        'response' => $apiResponse->getBody()->getContents(),
                    ]);
                }

                return $data;
            } else {
                $this->logger->critical('Received error response from API', [
                    'status' => $apiResponse->getStatusCode(),
                    'response' => $apiResponse->getBody()->getContents(),
                ]);
                return null;
            }
        } catch (\Throwable $e) {
            $this->logger->critical('Error fetching posts', [
                'err' => $e,
            ]);
            return null;
        }
    }

    /**
     * Extracts the post date from the "date" field.
     *
     * @param array $post The post data
     * @return \DateTimeInterface|null The date or now as fallback
     * @throws \Exception
     */
    private function extractDate(array $post): ?\DateTimeInterface
    {
        try {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $post['date'] ?? '-');
        } catch (\Throwable $e) {
            $date = new \DateTimeImmutable();
        }

        if ($date === false) {
            $date = new \DateTimeImmutable();
        }

        return $date;
    }

    /**
     * Extracts the post group.
     *
     * @param array $post
     * @return string The tag, or "-" as fallback
     */
    private function extractGroup(array $post): string
    {
        return $this->extractTerm($post, 'group');
    }

    /**
     * Extracts the post tag.
     *
     * @param array $post
     * @return string The tag, or "-" as fallback
     */
    private function extractTag(array $post): string
    {
        return $this->extractTerm($post, 'post_tag');
    }

    /**
     * Extracts a post term
     *
     * @param array $post The post data
     * @param string $type The type to extract, e.g. "post_tag"
     * @return string The term, or "-" as fallback
     */
    private function extractTerm(array $post, string $type): string
    {
        if (isset($post['_embedded']['wp:term']) === false || \is_array($post['_embedded']['wp:term']) === false) {
            return '-';
        }

        foreach ($post['_embedded']['wp:term'] as $term) {
            if (isset($term[0]['taxonomy']) === true && $term[0]['taxonomy'] === $type && isset($term[0]['name']) === true) {
                return $term[0]['name'];
            }
        }

        // not found
        return '-';
    }
}
