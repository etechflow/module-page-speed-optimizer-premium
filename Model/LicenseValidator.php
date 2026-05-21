<?php

declare(strict_types=1);

namespace ETechFlow\PageSpeedOptimizerPremium\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Premium-tier license validator. Same shape as the Pro one but with its
 * own MODULE_ID and secret. Bundle key shared across the suite.
 *
 * A Pro license does NOT activate Premium features. A Premium license
 * activates everything (Pro is required as a composer dep but Premium's
 * unique features need this Premium key). Bundle key activates both.
 */
class LicenseValidator
{
    public const XML_PATH_LICENSE_KEY            = 'etechflow_pso_premium/license/license_key';
    public const XML_PATH_PRODUCTION_ENVIRONMENT = 'etechflow_pso_premium/license/production_environment';
    public const XML_PATH_BUNDLE_LICENSE_KEY     = 'etechflow_bundle/license/license_key';

    private const MODULE_ID  = 'page-speed-optimizer-premium';
    private const BUNDLE_ID  = 'etechflow-bundle';

    private const SECRET_FRAGMENTS = [
        'eTF-PSO-PREM-2026',
        'r4M2-cV7n',
        'Y8jQ-tH9w',
        'L3pF-zN5k',
    ];

    private const BUNDLE_SECRET_FRAGMENTS = [
        'eTF-BUNDLE-2026',
        'k2D9-mP4x',
        'L8nR-vH2j',
        'X7tY-zW5q',
    ];

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function isValid(): bool
    {
        $host = $this->getCurrentHost();
        if ($host === '') {
            return false;
        }
        if (!$this->isProductionEnvironment()) {
            return true;
        }
        if ($this->isDevelopmentHost($host)) {
            return true;
        }
        $configuredKey = $this->getConfiguredKey();
        if ($configuredKey !== '' && hash_equals($this->computeKey($host), $configuredKey)) {
            return true;
        }
        $bundleKey = $this->getConfiguredBundleKey();
        if ($bundleKey !== '' && hash_equals($this->computeBundleKey($host), $bundleKey)) {
            return true;
        }
        return false;
    }

    public function computeKey(string $host): string
    {
        $payload = $this->canonicalize($host) . ':' . self::MODULE_ID;
        $secret  = implode('', self::SECRET_FRAGMENTS);
        $raw     = hash_hmac('sha256', $payload, $secret, true);
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    public function computeBundleKey(string $host): string
    {
        $payload = $this->canonicalize($host) . ':' . self::BUNDLE_ID;
        $secret  = implode('', self::BUNDLE_SECRET_FRAGMENTS);
        $raw     = hash_hmac('sha256', $payload, $secret, true);
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function canonicalize(string $host): string
    {
        $host = strtolower(trim($host));
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }
        return $host;
    }

    public function getConfiguredKey(): string
    {
        return trim((string) $this->scopeConfig->getValue(self::XML_PATH_LICENSE_KEY, ScopeInterface::SCOPE_STORE));
    }

    public function getConfiguredBundleKey(): string
    {
        return trim((string) $this->scopeConfig->getValue(self::XML_PATH_BUNDLE_LICENSE_KEY, ScopeInterface::SCOPE_STORE));
    }

    public function isProductionEnvironment(): bool
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_PRODUCTION_ENVIRONMENT, ScopeInterface::SCOPE_STORE);
        if ($value === null || $value === '') {
            return true;
        }
        return (bool) $value;
    }

    public function getCurrentHost(): string
    {
        try {
            $url = $this->storeManager->getStore()->getBaseUrl();
            $host = parse_url($url, PHP_URL_HOST);
            return is_string($host) ? strtolower($host) : '';
        } catch (\Exception $e) {
            return '';
        }
    }

    public function isDevHost(?string $host = null): bool
    {
        $check = $host !== null ? $this->canonicalize($host) : $this->canonicalize($this->getCurrentHost());
        return $this->isDevelopmentHost($check);
    }

    private function isDevelopmentHost(string $host): bool
    {
        if ($host === 'localhost' || str_starts_with($host, '127.')) return true;
        if (str_starts_with($host, '10.') || str_starts_with($host, '192.168.')) return true;
        if (preg_match('/^172\.(1[6-9]|2[0-9]|3[01])\./', $host)) return true;
        foreach (['.test', '.local', '.localhost', '.dev', '.example', '.invalid'] as $s) {
            if (str_ends_with($host, $s)) return true;
        }
        foreach (['staging.', 'stage.', 'dev.', 'qa.', 'uat.', 'test.', 'preview.', 'sandbox.'] as $p) {
            if (str_starts_with($host, $p)) return true;
        }
        if (preg_match('/-(staging|stage|dev|qa|uat|test|preview|sandbox)\./', $host)) return true;
        foreach (['.magento.cloud', '.magentocloud.com', '.cloud.magento', '.ngrok.io', '.ngrok-free.app', '.loca.lt', '.serveo.net'] as $s) {
            if (str_ends_with($host, $s)) return true;
        }
        return false;
    }
}
