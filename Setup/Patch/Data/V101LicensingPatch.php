<?php

declare(strict_types=1);

namespace ETechFlow\PageSpeedOptimizerPremium\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * No-op release marker for v1.0.1 — portal licensing.
 *
 * v1.0.1 upgrades the Premium LicenseValidator to the hybrid+strict model
 * (SP-XXXX portal subscription keys with domain + server-IP binding, alongside
 * the existing per-module HMAC and shared bundle keys) and adds the in-admin
 * License & Plans gate + Stripe checkout. No data migration — keeps the
 * always-a-patch discipline (see V100ReleaseMarker).
 */
class V101LicensingPatch implements DataPatchInterface
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
        return [V100ReleaseMarker::class];
    }
}
