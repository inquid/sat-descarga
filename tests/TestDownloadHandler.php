<?php

namespace tests;

use inquid\SatDownload\DownloadHandler;
use JsonException;
use PHPUnit\Framework\TestCase;

/**
 * Tests Xml can be loaded and downloaded.
 */
class TestDownloadHandler extends TestCase
{
    /** @var string Certificado SAT */
    protected string $cer;
    /** @var string Key SAT */
    protected string $key;
    /** @var string Password del key */
    protected string $password;

    /** @var DownloadHandler The service to call */
    protected DownloadHandler $downloadHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cer = 'artifacts/cer.cer';
        $this->key = 'artifacts/key.key';
        $this->password = 'Artisalie15!';

        $this->downloadHandler = new DownloadHandler($this->cer, $this->key, $this->password);
    }

    /**
     * Test I can login into the SAT webservice using the system.
     */
    public function testICanDownloadXmls(): void
    {
        $this->assertTrue($this->downloadHandler->login());
    }

    /**
     *
     * @throws JsonException
     */
    public function testICanDownloadIncomingXmls(): void
    {
        $this->downloadHandler->login();
        $result = $this->downloadHandler->searchForIncomingCfdis(2021, 11);
        $this->assertIsArray($result);
        $this->assertStringEqualsFile(
            '/Users/gogl92/sat-descarga/artifacts/incomingCfdisExample.json',
            json_encode($result, JSON_THROW_ON_ERROR)
        );
    }
}
