<?php
declare(strict_types=1);

namespace Magenmagic\ProductPrices\Helper;

use Magento\Catalog\Api\Data\ProductInterface;

/**
 * Minimal stub of the Verkter helper so this package can be unit-tested in isolation.
 *
 * The real class lives in the consumer (verkter/app/code/Magenmagic/ProductPrices).
 * Only the subset of the surface area the bridge calls is mirrored here.
 */
class Price
{
    /**
     * @return array<string, array<string, array{value: float|null, currency?: string|null}>>
     */
    public function getCalculatePrice(string $country, ProductInterface $product, int $storeId): array
    {
        return [];
    }
}
