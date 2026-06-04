<?php

declare(strict_types=1);

namespace ETechFlow\PageSpeedOptimizerPremium\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Premium-tier configuration reader.
 *
 * Three feature groups:
 *   - infinite_scroll (the headline feature)
 *   - bulk_sweep (existing-folder image optimization via cron)
 *   - detailed_logging (verbose per-image optimization log)
 *
 * isEnabled() returns false when EITHER the license isn't valid OR the
 * master switch is off.
 */
class Config
{
    public const XML_PATH_ENABLED = 'etechflow_pso_premium/general/enabled';

    // Infinite Scroll
    public const XML_PATH_IS_ENABLED         = 'etechflow_pso_premium/infinite_scroll/enabled';
    public const XML_PATH_IS_ENABLE_CATEGORY = 'etechflow_pso_premium/infinite_scroll/enable_category';
    public const XML_PATH_IS_ENABLE_SEARCH   = 'etechflow_pso_premium/infinite_scroll/enable_search';
    public const XML_PATH_IS_SCROLL_THRESHOLD = 'etechflow_pso_premium/infinite_scroll/scroll_threshold_px';
    public const XML_PATH_IS_MAX_PAGES       = 'etechflow_pso_premium/infinite_scroll/max_pages';
    public const XML_PATH_IS_SHOW_BACKTOP    = 'etechflow_pso_premium/infinite_scroll/show_back_to_top';

    // Bulk-sweep image optimization
    public const XML_PATH_SWEEP_ENABLED      = 'etechflow_pso_premium/bulk_sweep/enabled';
    public const XML_PATH_SWEEP_BATCH        = 'etechflow_pso_premium/bulk_sweep/batch_size';
    public const XML_PATH_SWEEP_PATHS        = 'etechflow_pso_premium/bulk_sweep/scan_paths';

    // Detailed logging
    public const XML_PATH_DETAILED_LOGGING   = 'etechflow_pso_premium/logging/detailed_enabled';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LicenseValidator $licenseValidator
    ) {
    }

    public function isEnabled(): bool
    {
        if (!$this->licenseValidator->isValid()) {
            return false;
        }
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    // ─── Infinite Scroll ───────────────────────────────────────

    public function isInfiniteScrollEnabled(): bool
    {
        if (!$this->isEnabled()) return false;
        // Portal plan can toggle this feature off; HMAC/bundle/dev keep it on.
        if (!$this->licenseValidator->isFeatureEnabled('infinite_scroll')) return false;
        return $this->scopeConfig->isSetFlag(self::XML_PATH_IS_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    public function isInfiniteScrollOnCategory(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_IS_ENABLE_CATEGORY, ScopeInterface::SCOPE_STORE);
    }

    public function isInfiniteScrollOnSearch(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_IS_ENABLE_SEARCH, ScopeInterface::SCOPE_STORE);
    }

    public function getScrollThresholdPx(): int
    {
        $value = (int) $this->scopeConfig->getValue(self::XML_PATH_IS_SCROLL_THRESHOLD, ScopeInterface::SCOPE_STORE);
        return $value > 0 ? $value : 400;
    }

    public function getMaxAutoLoadPages(): int
    {
        $value = (int) $this->scopeConfig->getValue(self::XML_PATH_IS_MAX_PAGES, ScopeInterface::SCOPE_STORE);
        return $value > 0 ? $value : 5;
    }

    public function isBackToTopEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_IS_SHOW_BACKTOP, ScopeInterface::SCOPE_STORE);
    }

    // ─── Bulk-sweep ────────────────────────────────────────────

    public function isBulkSweepEnabled(): bool
    {
        if (!$this->isEnabled()) return false;
        if (!$this->licenseValidator->isFeatureEnabled('bulk_sweep')) return false;
        return $this->scopeConfig->isSetFlag(self::XML_PATH_SWEEP_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    public function getBulkSweepBatchSize(): int
    {
        $value = (int) $this->scopeConfig->getValue(self::XML_PATH_SWEEP_BATCH, ScopeInterface::SCOPE_STORE);
        return $value > 0 ? $value : 500;
    }

    /** @return string[] absolute paths relative to pub/media */
    public function getBulkSweepScanPaths(): array
    {
        $raw = (string) $this->scopeConfig->getValue(self::XML_PATH_SWEEP_PATHS, ScopeInterface::SCOPE_STORE);
        if ($raw === '') {
            return ['catalog/product', 'catalog/category', 'wysiwyg'];
        }
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        return array_values(array_filter(array_map('trim', $lines), fn($s) => $s !== ''));
    }

    // ─── Logging ───────────────────────────────────────────────

    public function isDetailedLoggingEnabled(): bool
    {
        if (!$this->isEnabled()) return false;
        if (!$this->licenseValidator->isFeatureEnabled('detailed_logging')) return false;
        return $this->scopeConfig->isSetFlag(self::XML_PATH_DETAILED_LOGGING, ScopeInterface::SCOPE_STORE);
    }
}
