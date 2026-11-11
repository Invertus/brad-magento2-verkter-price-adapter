<?php
declare(strict_types=1);

namespace Magenmagic\BradSearchPrice\Model;

use BradSearch\SearchGraphQl\Api\ProductHashAttributeProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * The attributes Verkter's price helper reads from the default-scope product.
 *
 * Magenmagic\ProductPrices\Helper\Price takes the regular price and the tax class from a copy of
 * the product loaded at store 0, and adds a free-shipping markup computed from its weight and
 * length (Magenmagic\FreeShipping\Helper\Rate). Real-time sync hashes these at default scope, so
 * a change there is sent even when a store view has its own value. The weight and length config
 * paths and fallbacks mirror Rate::getProductWeight() and Rate::getProductLength().
 */
class PriceInputAttributeProvider implements ProductHashAttributeProviderInterface
{
    private const CONFIG_WEIGHT_ATTRIBUTE = 'mmfreeshipping/attr_code/weight';
    private const CONFIG_LENGTH_ATTRIBUTE = 'mmfreeshipping/attr_code/length';

    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return string[]
     */
    public function getAttributeCodes(int $storeId): array
    {
        $weight = (string)$this->scopeConfig->getValue(self::CONFIG_WEIGHT_ATTRIBUTE, ScopeInterface::SCOPE_STORES, $storeId);
        $length = (string)$this->scopeConfig->getValue(self::CONFIG_LENGTH_ATTRIBUTE, ScopeInterface::SCOPE_STORES, $storeId);

        $codes = ['price', 'tax_class_id', $weight !== '' ? $weight : 'weight'];
        if ($length !== '') {
            $codes[] = $length;
        }

        return $codes;
    }
}
