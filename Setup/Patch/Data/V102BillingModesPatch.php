<?php

declare(strict_types=1);

namespace ETechFlow\PageSpeedOptimizerPremium\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * No-op release marker for v1.0.2 — portal-driven billing mode.
 *
 * The in-admin gate now fetches its purchasable plans from the portal's
 * /license/plans endpoint, so the licensing admin can sell this module on a
 * one-time (lifetime) basis or the existing weekly/monthly/yearly recurring
 * basis. No data migration — keeps the always-a-patch discipline.
 */
class V102BillingModesPatch implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): self
    {
        return $this;
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [V101LicensingPatch::class];
    }
}
