<?php
declare(strict_types = 1);

namespace Tests\Functional;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\StreamInterface;
use Slim\App;

/**
 * This class tests the home view for a valid API response.
 */
class HomeTest extends BaseTestCase
{
    /**
     * Tests that an API response is displayed.
     */
    public function testHome()
    {
        $response = $this->runApp('GET', '/');
        $this->assertEquals(200, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertContains('Snap up your development &#8211; Tools for making the snap trek easier', $body);
        $this->assertContains('Apellix engineers safer work environments with Ubuntu powered aerial robotics', $body);
        $this->assertContains('Ubuntu Server development summary &#8211; 08 January 2019', $body);
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
            ->willReturn(200);

        $bodyMock = $this->getMockBuilder(StreamInterface::class)
            ->getMock();

        $jsonData = file_get_contents(__DIR__ . '/../data/api-response.json');

        $bodyMock->method('getContents')
            ->willReturn($jsonData);

        $responseMock->method('getBody')
            ->willReturn($bodyMock);

        $clientMock->method('get')
            ->willReturn($responseMock);

        $container = $app->getContainer();
        $container['http_client'] = $clientMock;
    }
}
