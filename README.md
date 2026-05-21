# ETechFlow Page Speed Optimizer Premium

Adds Premium-tier features on top of the [ETechFlow Page Speed Optimizer Pro](https://github.com/etechflow/module-page-speed-optimizer) module. Together they form the full Amasty Page Speed Optimizer Premium ($599) alternative at $499.

## What this module adds

| Feature | Status |
|---|---|
| **Infinite Scroll** for category + search-result pages | ✅ v1.0 |
| **Auto-optimize EXISTING images in folders** via cron | ✅ v1.0 |
| **Detailed image-optimization logging** | ✅ v1.0 |
| **AJAX Shopping Cart** popup | 🛠 v1.1 |
| **Per-device JS merging** | 🛠 v1.1 |

## Install

```bash
composer require etechflow/module-page-speed-optimizer-premium:^1.0
bin/magento module:enable ETechFlow_PageSpeedOptimizerPremium
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

Requires the Pro module (`etechflow/module-page-speed-optimizer ^2.3`) — Composer pulls it automatically if not installed.

## Verify

```bash
bin/magento etechflow:pso-premium:verify
```

## Compatibility

- Magento Open Source / Adobe Commerce 2.4.4 – 2.4.8
- PHP 8.1 – 8.4
- Hyvä Theme + Hyvä Checkout
- Mage-OS

## Pricing

- **PSO Pro** alone — $179 (full Amasty Pro $199 alternative)
- **PSO Premium** (this module + Pro) — $499 (full Amasty Premium $599 alternative)

## Support

info@etechflow.com
