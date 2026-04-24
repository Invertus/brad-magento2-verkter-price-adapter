<?php
declare(strict_types=1);

namespace Magenmagic\BradSearchPrice\Model;

use BradSearch\SearchGraphQl\Api\Data\CalculatedPriceInterface;
use BradSearch\SearchGraphQl\Api\PriceCalculatorInterface;
use BradSearch\SearchGraphQl\Model\Data\CalculatedPrice;
use BradSearch\SearchGraphQl\Model\Data\Money;
use BradSearch\SearchGraphQl\Model\Data\PriceTuple;
use Magenmagic\ProductPrices\Helper\Price as MagenmagicPriceHelper;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Bridge implementation that reuses Magenmagic\ProductPrices\Helper\Price —
 * the same helper that powers the storefront `productPrice` GraphQL query.
 *
 * This guarantees BradSearch's `calculated_price` matches what customers see.
 */
class MagenmagicPriceCalculator implements PriceCalculatorInterface
{
    private MagenmagicPriceHelper $helper;
    private ScopeConfigInterface $scopeConfig;
    private StoreManagerInterface $storeManager;

    public function __construct(
        MagenmagicPriceHelper $helper,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    public function calculate(ProductInterface $product, int $storeId): ?CalculatedPriceInterface
    {
        $country = (string) $this->scopeConfig->getValue(
            'general/country/default',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $raw = $this->helper->getCalculatePrice($country, $product, $storeId);
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore($storeId);
        $currency = (string) $store->getCurrentCurrencyCode();

        return new CalculatedPrice(
            $this->toTuple($raw['minimum_price'] ?? [], $currency),
            $this->toTuple($raw['maximum_price'] ?? [], $currency)
        );
    }

    /**
     * @param array<string, mixed> $priceList
     */
    private function toTuple(array $priceList, string $currency): PriceTuple
    {
        return new PriceTuple(
            $this->money($priceList['regular_price'] ?? null, $currency),
            $this->money($priceList['final_price'] ?? null, $currency),
            $this->money($priceList['final_price_excl_tax'] ?? null, $currency)
        );
    }

    /**
     * @param array{value?: float|null, currency?: string|null}|null $money
     */
    private function money(?array $money, string $fallbackCurrency): Money
    {
        $value = is_array($money) && isset($money['value']) ? (float) $money['value'] : 0.0;
        $currency = is_array($money) && !empty($money['currency']) ? $money['currency'] : $fallbackCurrency;

        return new Money($value, $currency);
    }
}
