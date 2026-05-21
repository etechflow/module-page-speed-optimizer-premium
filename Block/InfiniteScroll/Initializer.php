<?php

declare(strict_types=1);

namespace ETechFlow\PageSpeedOptimizerPremium\Block\InfiniteScroll;

use ETechFlow\PageSpeedOptimizerPremium\Model\Config;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Request\Http as HttpRequest;

/**
 * Renders the JS bootstrap that wires up Infinite Scroll on category /
 * search pages. The actual scroll logic is in
 * view/frontend/web/js/infinite-scroll.js.
 *
 * This block decides whether to render the bootstrap based on:
 *   - License + master toggle + infinite_scroll/enabled
 *   - Per-page-type toggle (category vs search)
 *   - Current full-action-name (catalog_category_view |
 *     catalogsearch_result_index)
 *
 * If any check fails, the template emits nothing — zero JS footprint.
 */
class Initializer extends Template
{
    public function __construct(
        Context $context,
        private readonly Config $config,
        private readonly HttpRequest $request,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function shouldRender(): bool
    {
        if (!$this->config->isInfiniteScrollEnabled()) {
            return false;
        }
        $action = (string) $this->request->getFullActionName();
        if ($action === 'catalog_category_view') {
            return $this->config->isInfiniteScrollOnCategory();
        }
        if (in_array($action, ['catalogsearch_result_index', 'catalogsearch_advanced_result'], true)) {
            return $this->config->isInfiniteScrollOnSearch();
        }
        return false;
    }

    /**
     * Serialised settings handed to the frontend JS.
     */
    public function getSettingsJson(): string
    {
        return (string) json_encode([
            'thresholdPx'   => $this->config->getScrollThresholdPx(),
            'maxPages'      => $this->config->getMaxAutoLoadPages(),
            'showBackToTop' => $this->config->isBackToTopEnabled(),
            // CSS selectors — kept theme-agnostic. Most M2 themes use these
            // exact classes; Hyvä also emits .products-grid as a container.
            'containerSelector' => '.products.wrapper .products-grid > .product-items',
            'paginationSelector' => '.pages',
            'productItemSelector' => '.product-item',
        ], JSON_UNESCAPED_SLASHES);
    }
}
