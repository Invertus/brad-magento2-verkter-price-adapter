<?php
declare(strict_types=1);

namespace Magenmagic\BradSearchPrice\Test\Unit\Model;

use Magenmagic\BradSearchPrice\Model\PriceInputAttributeProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PriceInputAttributeProviderTest extends TestCase
{
    /** @var ScopeConfigInterface&MockObject */
    private $scopeConfig;

    private PriceInputAttributeProvider $provider;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->provider = new PriceInputAttributeProvider($this->scopeConfig);
    }

    public function testReturnsPriceTaxClassAndTheConfiguredWeightAndLengthOfTheStore(): void
    {
        $this->scopeConfig->method('getValue')->willReturnMap([
            ['mmfreeshipping/attr_code/weight', ScopeInterface::SCOPE_STORES, 3, 'net_weight'],
            ['mmfreeshipping/attr_code/length', ScopeInterface::SCOPE_STORES, 3, '211ed48136080d335044b25ff97c26'],
        ]);

        $this->assertSame(
            ['price', 'tax_class_id', 'net_weight', '211ed48136080d335044b25ff97c26'],
            $this->provider->getAttributeCodes(3)
        );
    }

    public function testFallsBackToWeightAndNoLengthLikeTheShippingRateHelper(): void
    {
        $this->scopeConfig->method('getValue')->willReturn(null);

        $this->assertSame(['price', 'tax_class_id', 'weight'], $this->provider->getAttributeCodes(3));
    }
}
