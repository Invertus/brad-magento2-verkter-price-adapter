<?php
declare(strict_types=1);

namespace Magenmagic\BradSearchPrice\Test\Unit\Model;

use BradSearch\SearchGraphQl\Model\Data\CalculatedPrice;
use Magenmagic\BradSearchPrice\Model\MagenmagicPriceCalculator;
use Magenmagic\ProductPrices\Helper\Price as MagenmagicPriceHelper;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MagenmagicPriceCalculatorTest extends TestCase
{
    /** @var MagenmagicPriceHelper&MockObject */
    private $helper;

    /** @var ScopeConfigInterface&MockObject */
    private $scopeConfig;

    /** @var StoreManagerInterface&MockObject */
    private $storeManager;

    private MagenmagicPriceCalculator $calculator;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(MagenmagicPriceHelper::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);

        $this->calculator = new MagenmagicPriceCalculator(
            $this->helper,
            $this->scopeConfig,
            $this->storeManager
        );
    }

    public function testCalculateMapsHelperOutputToCalculatedPrice(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $store = $this->createMock(Store::class);

        $this->scopeConfig->expects(self::once())
            ->method('getValue')
            ->with('general/country/default', ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('LT');

        $this->helper->expects(self::once())
            ->method('getCalculatePrice')
            ->with('LT', $product, 1)
            ->willReturn([
                'minimum_price' => [
                    'regular_price'        => ['value' => 10.00, 'currency' => 'EUR'],
                    'final_price'          => ['value' => 8.00,  'currency' => 'EUR'],
                    'final_price_excl_tax' => ['value' => 6.61,  'currency' => 'EUR'],
                ],
                'maximum_price' => [
                    'regular_price'        => ['value' => 20.00, 'currency' => 'EUR'],
                    'final_price'          => ['value' => 18.00, 'currency' => 'EUR'],
                    'final_price_excl_tax' => ['value' => 14.88, 'currency' => 'EUR'],
                ],
            ]);

        $store->method('getCurrentCurrencyCode')->willReturn('EUR');
        $this->storeManager->expects(self::once())
            ->method('getStore')
            ->with(1)
            ->willReturn($store);

        $result = $this->calculator->calculate($product, 1);

        self::assertInstanceOf(CalculatedPrice::class, $result);
        self::assertSame(8.00, $result->getMinimumPrice()->getFinalPrice()->getValue());
        self::assertSame(18.00, $result->getMaximumPrice()->getFinalPrice()->getValue());
        self::assertSame('EUR', $result->getMinimumPrice()->getFinalPrice()->getCurrency());
    }

    public function testCalculateFallsBackToStoreCurrencyWhenHelperOmitsIt(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $store = $this->createMock(Store::class);

        $this->scopeConfig->method('getValue')->willReturn('EE');

        $this->helper->method('getCalculatePrice')->willReturn([
            'minimum_price' => [
                'regular_price' => ['value' => 5.0],
                'final_price'   => ['value' => 5.0],
            ],
            'maximum_price' => [],
        ]);

        $store->method('getCurrentCurrencyCode')->willReturn('EUR');
        $this->storeManager->method('getStore')->willReturn($store);

        $result = $this->calculator->calculate($product, 2);

        self::assertNotNull($result);
        self::assertSame('EUR', $result->getMinimumPrice()->getFinalPrice()->getCurrency());
        self::assertSame(0.0, $result->getMaximumPrice()->getFinalPrice()->getValue());
    }

    public function testCalculateHandlesCompletelyEmptyHelperResponse(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $store = $this->createMock(Store::class);

        $this->scopeConfig->method('getValue')->willReturn('LV');
        $this->helper->method('getCalculatePrice')->willReturn([]);
        $store->method('getCurrentCurrencyCode')->willReturn('EUR');
        $this->storeManager->method('getStore')->willReturn($store);

        $result = $this->calculator->calculate($product, 3);

        self::assertNotNull($result);
        self::assertSame(0.0, $result->getMinimumPrice()->getFinalPrice()->getValue());
        self::assertSame(0.0, $result->getMaximumPrice()->getFinalPrice()->getValue());
    }
}
