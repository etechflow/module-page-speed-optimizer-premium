/**
 * ETechFlow Page Speed Optimizer Premium — Infinite Scroll
 *
 * Vanilla JS (no jQuery dependency — Hyvä-compatible). Uses IntersectionObserver
 * to detect when the bottom of the product grid enters the viewport, then
 * fetches the next paginated page via AJAX and appends new product items.
 *
 * After maxPages auto-loads, switches to a manual "Load More" button so
 * we don't infinite-loop on huge catalogs.
 *
 * Settings injected via window.etechflowPsoPremiumInfiniteScrollSettings:
 *   thresholdPx, maxPages, showBackToTop, containerSelector,
 *   paginationSelector, productItemSelector
 */
(function () {
    'use strict';

    var settings = window.etechflowPsoPremiumInfiniteScrollSettings || {};
    if (!settings.containerSelector) {
        return;
    }

    var container = document.querySelector(settings.containerSelector);
    var pagination = document.querySelector(settings.paginationSelector);
    if (!container || !pagination) {
        // Theme doesn't have the expected DOM — silently bail.
        return;
    }

    var nextPageUrl = findNextPageUrl(pagination);
    if (!nextPageUrl) {
        // No next page — single-page catalog, nothing to do.
        return;
    }

    var loadedAutoPages = 0;
    var isFetching = false;
    var sentinel = createSentinel(container);
    var manualButton = null;

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting || isFetching) return;
            if (loadedAutoPages >= settings.maxPages) {
                observer.disconnect();
                showManualButton();
                return;
            }
            loadNextPage();
        });
    }, {
        rootMargin: (settings.thresholdPx || 400) + 'px'
    });
    observer.observe(sentinel);

    if (settings.showBackToTop) {
        installBackToTopButton();
    }

    function loadNextPage() {
        if (!nextPageUrl || isFetching) return;
        isFetching = true;
        showLoadingIndicator();

        fetch(nextPageUrl, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.text(); })
        .then(function (html) {
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');
            var newItems = doc.querySelectorAll(
                settings.containerSelector + ' > ' + settings.productItemSelector
            );
            // Append each new item to our container.
            newItems.forEach(function (item) {
                container.appendChild(item.cloneNode(true));
            });
            // Update next-page URL from the freshly-loaded pagination.
            var newPagination = doc.querySelector(settings.paginationSelector);
            nextPageUrl = newPagination ? findNextPageUrl(newPagination) : null;
            if (!nextPageUrl) {
                observer.disconnect();
                if (manualButton) manualButton.remove();
            }
            loadedAutoPages++;
            hideLoadingIndicator();
            isFetching = false;
            // Magento may need to re-initialise per-product widgets — fire
            // a custom event so themes can listen.
            document.dispatchEvent(new CustomEvent('etechflow-pso-premium:items-appended', {
                detail: { count: newItems.length }
            }));
        })
        .catch(function (err) {
            hideLoadingIndicator();
            isFetching = false;
        });
    }

    function findNextPageUrl(paginationNode) {
        // Match Magento's default `.next` link OR `[rel="next"]`.
        var link = paginationNode.querySelector('a.next, a[rel="next"]');
        return link ? link.href : null;
    }

    function createSentinel(parent) {
        var s = document.createElement('div');
        s.id = 'etechflow-pso-premium-is-sentinel';
        s.setAttribute('aria-hidden', 'true');
        s.style.cssText = 'height:1px;width:100%;';
        parent.parentNode.insertBefore(s, parent.nextSibling);
        return s;
    }

    function showLoadingIndicator() {
        var el = document.getElementById('etechflow-pso-premium-is-loading');
        if (!el) {
            el = document.createElement('div');
            el.id = 'etechflow-pso-premium-is-loading';
            el.className = 'etechflow-pso-premium-is-loading';
            el.innerHTML = '<div class="spinner"></div><span>Loading more products…</span>';
            sentinel.parentNode.insertBefore(el, sentinel);
        }
        el.style.display = 'flex';
    }

    function hideLoadingIndicator() {
        var el = document.getElementById('etechflow-pso-premium-is-loading');
        if (el) el.style.display = 'none';
    }

    function showManualButton() {
        if (manualButton || !nextPageUrl) return;
        manualButton = document.createElement('button');
        manualButton.id = 'etechflow-pso-premium-is-loadmore';
        manualButton.type = 'button';
        manualButton.className = 'etechflow-pso-premium-is-loadmore';
        manualButton.textContent = 'Load more products';
        manualButton.addEventListener('click', function () {
            loadedAutoPages = 0;
            observer.observe(sentinel);
            manualButton.remove();
            manualButton = null;
            loadNextPage();
        });
        sentinel.parentNode.insertBefore(manualButton, sentinel);
    }

    function installBackToTopButton() {
        var btn = document.createElement('button');
        btn.id = 'etechflow-pso-premium-is-backtop';
        btn.type = 'button';
        btn.className = 'etechflow-pso-premium-is-backtop';
        btn.setAttribute('aria-label', 'Back to top');
        btn.innerHTML = '&uarr;';
        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        document.body.appendChild(btn);
        // Show after the user has scrolled past 600px
        window.addEventListener('scroll', function () {
            btn.style.display = window.scrollY > 600 ? 'block' : 'none';
        }, { passive: true });
    }
})();
