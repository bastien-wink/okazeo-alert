<?php

namespace App\Tests\Services;

use App\Services\OkkazeoCrawler;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class OkkazeoCrawlerTest extends TestCase
{
    private $httpClient;
    private $logger;
    private $entityManager;
    private $appKernel;
    private $okkazeoCrawler;

    protected function setUp(): void
    {
        $this->httpClient = $this->getMockBuilder(HttpClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->appKernel = $this->getMockBuilder(KernelInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->okkazeoCrawler = new OkkazeoCrawler($this->httpClient, $this->logger, $this->entityManager, $this->appKernel);
    }

    public function testGetCacheContent()
    {
        // TODO: Write test
    }

    // TODO: Add more test methods for each public method in the OkkazeoCrawler class
}