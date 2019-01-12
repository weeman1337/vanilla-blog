<?php
declare(strict_types = 1);

namespace Tests\Functional;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Slim\App;

/**
 * This class provides tests for the home page in case of API errors.
 */
class HomeAPIErrorTest extends BaseTestCase
{
    /**
     * Tests that an API error display the info message.
     */
    public function testApiRequestFails()
    {
        $response = $this->runApp('GET', '/');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('We apologize that we are having some trouble loading the content', (string) $response->getBody());
    }

    /**
     * Sets up the http client service.
     * The client returns a failed response.
     *
     * @param App $app
     */
    protected function setupDependencies(App $app): void
    {
        parent::setupDependencies($app);

        $clientMock = $this->getMockBuilder(Client::class)
            ->setMethods(['get'])
            ->getMock();

        $responseMock = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $responseMock->method('getStatusCode')
            ->willReturn(500);

        $clientMock->method('get')
            ->willReturn($responseMock);

        $container = $app->getContainer();
        $container['http_client'] = $clientMock;
    }
}
