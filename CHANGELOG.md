# Changelog — ETechFlow Page Speed Optimizer Premium

All notable changes to this module. Adheres to [Semantic Versioning](https://semver.org/).

---

## [1.0.0] — 2026-05-21 — Initial Premium tier release

First commercial release of the **Premium** add-on module. Pairs with PSO Pro to form a complete Amasty Page Speed Optimizer Premium ($599) alternative at $499.

### Added

**Infinite Scroll for category + search pages**
- Vanilla-JS IntersectionObserver-based "load more on scroll" behaviour.
- AJAX controller renders next paginated product page server-side and returns just the new products + updated pagination meta.
- Loading indicator + "Back to top" button after N pages scrolled.
- Admin config: enable per page type (Category / Search Results), scroll threshold (px before bottom), products per fetch.
- Hyvä-compatible — no jQuery dependency.

**Bulk-sweep image optimization via cron**
- `SweepFoldersCommand` CLI + cron job walks the configured media dirs and enqueues any UN-optimized images into PSO Pro's existing `etechflow_pso_view_queue`. Pro's `QueueProcessor` cron drains the queue normally.
- Different from Pro's "auto-on-upload" observer: that only catches new uploads. This catches ALL existing folder content.
- Useful for stores migrating from another optimizer or for retroactive optimization after enabling PSO.

**Detailed image-optimization logging**
- Adds per-step logging to the optimization pipeline. Each cron run produces a detailed log file at `var/log/etechflow_pso_premium_optimization_YYYY-MM-DD.log`.
- Captures: source path, engines used, bytes-before/after, savings %, duration ms, status.
- Audit trail for agencies + ops teams managing multi-store catalogs.

### Premium tier feature parity with Amasty Premium ($599)

| Amasty Premium feature | PSO Premium v1.0 |
|---|---|
| PSO Pro included | ✅ (via composer dependency) |
| Back Forward Cache | ✅ (in Pro) |
| **Infinite Scroll** | **✅ NEW** |
| AJAX Shopping Cart | 🛠 v1.1 |
| **Image optimization by Cron (bulk-sweep)** | **✅ NEW** |
| JS merging for particular devices | 🛠 v1.1 |
| **Auto-optimize EXISTING images in folders** | **✅ NEW** |
| **Detailed optimization logging** | **✅ NEW** |

**4 of 6 Premium-only features in v1.0. AJAX Cart + per-device JS merging in v1.1.**

### Hardening (the v1.7.0 lesson)

- **`Setup/Patch/Data/V100ReleaseMarker.php`** — no-op release marker
  patch. Establishes the always-a-patch discipline previously adopted in
  NDE v1.7.1, BED v1.2.2, and ISP v2.0.0. Every release ships at least
  one patch so `setup:upgrade` always has something to register in
  `patch_list` — surfacing FS / permissions / DI errors during the patch
  phase (which retries cleanly) instead of at the end of the upgrade
  (which doesn't). Inaugural patch for v1.0.0.

### Setup

```bash
composer require etechflow/module-page-speed-optimizer-premium:^1.0
bin/magento module:enable ETechFlow_PageSpeedOptimizerPremium
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

### Enable Infinite Scroll

```bash
bin/magento config:set etechflow_pso_premium/infinite_scroll/enabled 1
bin/magento config:set etechflow_pso_premium/infinite_scroll/enable_category 1
bin/magento cache:flush
```

### Compatibility

- Magento Open Source / Adobe Commerce 2.4.4 – 2.4.8
- PHP 8.1 – 8.4
- Hyvä Theme + Hyvä Checkout
- Mage-OS
