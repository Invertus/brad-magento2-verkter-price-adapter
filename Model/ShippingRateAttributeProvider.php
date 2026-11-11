<?php
declare(strict_types=1);

namespace Magenmagic\BradSearchPrice\Model;

use BradSearch\SearchGraphQl\Api\ProductHashAttributeProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * The weight and length attributes behind Verkter's free-shipping markup.
 *
 * The calculated price adds that markup (Magenmagic\FreeShipping\Helper\Rate), so a weight or
 * length change moves the indexed price. Neither attribute is searchable or filterable, so
 * real-time sync would not see the change without this provider. The config paths and the
 * fallbacks mirror Rate::getProductWeight() and Rate::getProductLength().
 */
class ShippingRateAttributeProvider implements ProductHashAttributeProviderInterface
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

        $codes = [$weight !== '' ? $weight : 'weight'];
        if ($length !== '') {
            $codes[] = $length;
        }

        return $codes;
    }
}
