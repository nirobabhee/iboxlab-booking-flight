(function ($) {
  'use strict';

  const routeMap = Array.isArray(window.routes)
    ? window.routes.reduce((acc, item) => Object.assign(acc, item), {})
    : {};

  const normalize = (value) => (value || '').toString().toLowerCase().trim();

  const escapeHtml = (value) =>
    (value || '').toString()
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');

  const valuesFromParams = (params) => {
    if (!params) return [];
    if (Array.isArray(params)) return params;
    return Object.values(params);
  };

  const resolveUrl = (routeName, params) => {
    if (!routeName || !routeMap[routeName]) return null;

    let url = routeMap[routeName];
    valuesFromParams(params).forEach((param) => {
      url = url.replace(/\{[^}]+\??\}/, param);
    });

    return url.replace(/\/+$/, '');
  };

  const buildParentLabel = (item, fallback) => {
    return item.parent || item.header || item.group || fallback || 'Admin';
  };

  const friendlyParent = (parent) => {
    const value = normalize(parent);
    if (!value || value === 'sidebar' || value === 'settings' || value === 'system settings') {
      return '';
    }

    return parent;
  };

  const rawItems = [];
  const seen = new Set();

  const pushItem = (item, options = {}) => {
    const url = resolveUrl(item.route_name, item.params);
    const title = item.title || '';

    if (!url || !title) return;

    const parent = buildParentLabel(item, options.parent);
    const subtitle = item.subtitle || options.subtitle || '';
    const group = options.group || 'Admin';
    const keywords = Array.isArray(item.keyword) ? item.keyword : [];
    const searchText = normalize([title, subtitle, parent, group, keywords.join(' ')].join(' '));
    const key = `${url}::${normalize(title)}`;

    if (seen.has(key)) return;
    seen.add(key);

    const icon = item.icon || options.icon || '';

    rawItems.push({
      title,
      subtitle,
      parent,
      group,
      keywords,
      icon,
      routeName: item.route_name,
      params: item.params || null,
      url,
      searchText,
    });
  };

  Object.values(window.settings || {}).forEach((item) => {
    pushItem(item, { parent: 'System Settings', group: 'Settings' });
  });

  Object.values(window.sidenav || {}).forEach((item) => {
    if (item.route_name) {
      pushItem(item, { parent: item.header || 'Sidebar', group: 'Sidebar' });
    }

    if (Array.isArray(item.submenu)) {
      item.submenu.forEach((subItem) => {
        pushItem(subItem, { parent: item.title || 'Sidebar', group: 'Sidebar', icon: item.icon || '' });
      });
    }
  });

  const scoreItem = (item, query) => {
    if (!query) return 1;

    const title = normalize(item.title);
    const parent = normalize(item.parent);
    const subtitle = normalize(item.subtitle);

    if (title === query) return 120;
    if (title.startsWith(query)) return 100;
    if (parent.startsWith(query)) return 70;
    if (subtitle.startsWith(query)) return 60;
    if (title.includes(query)) return 50;
    if (parent.includes(query)) return 40;
    if (subtitle.includes(query)) return 30;
    if (item.searchText.includes(query)) return 20;

    return 0;
  };

  const searchItems = (query, options = {}) => {
    const normalizedQuery = normalize(query);
    const limit = options.limit || 12;

    return rawItems
      .map((item) => ({ ...item, score: scoreItem(item, normalizedQuery) }))
      .filter((item) => item.score > 0)
      .sort((a, b) => b.score - a.score || a.title.localeCompare(b.title))
      .slice(0, limit);
  };

  const resultItemMarkup = (item) => `
    <li>
      <a class="search-list-link" href="${escapeHtml(item.url)}">
        <span class="search-item-icon"><i class="${escapeHtml(item.icon || 'las la-link')}"></i></span>
        <span class="search-item-text">
          <span class="search-title">${escapeHtml(item.title)}</span>
          ${friendlyParent(item.parent) ? `<span class="search-subtitle">${escapeHtml(friendlyParent(item.parent))}</span>` : ''}
        </span>
        <span class="search-item-arrow"><i class="las la-angle-right"></i></span>
      </a>
    </li>
  `;

  const renderResults = ($target, items, options = {}) => {
    $target.html('');

    if (!items.length) {
      $target.html(typeof window.getEmptyMessage === 'function' ? window.getEmptyMessage() : '');
      return;
    }

    $target.html(items.map(resultItemMarkup).join(''));
  };

  const createSpotlight = () => {
    const $modal = $('#adminSpotlight');
    const $input = $('#adminSpotlightInput');
    const $resultsWrap = $modal.find('.admin-spotlight__results');
    const $results = $('#adminSpotlightResults');
    const $launcher = $('#searchInput');
    let activeIndex = -1;
    let closeTimer = null;

    const spotlightItems = () => $results.find('li').not('.search-list-group');

    const paintActive = (scroll = true) => {
      const $items = spotlightItems();
      $items.removeClass('active');

      if (activeIndex < 0 || !$items.eq(activeIndex).length) return;

      const $active = $items.eq(activeIndex).addClass('active');

      if (!scroll) return;

      var itemCount = $items.length;

      if (activeIndex === 0) {
        $resultsWrap.scrollTop(0);
        return;
      }

      if (activeIndex === itemCount - 1) {
        $resultsWrap.scrollTop($resultsWrap[0].scrollHeight);
        return;
      }

      const activeTop = $active.position().top;
      const activeBottom = activeTop + $active.outerHeight();
      const visibleHeight = $resultsWrap.innerHeight();
      const currentScroll = $resultsWrap.scrollTop();

      if (activeTop < 0) {
        $resultsWrap.scrollTop(currentScroll + activeTop - 8);
      } else if (activeBottom > visibleHeight) {
        $resultsWrap.scrollTop(currentScroll + (activeBottom - visibleHeight) + 8);
      }
    };

    const updateResults = (query) => {
      const matches = searchItems(query, { limit: 14 });
      renderResults($results, matches, { grouped: true });
      activeIndex = matches.length ? 0 : -1;
      paintActive();
    };

    const open = (query = '') => {
      if (closeTimer) {
        window.clearTimeout(closeTimer);
        closeTimer = null;
      }

      $modal.removeClass('is-closing').addClass('show').attr('aria-hidden', 'false');
      $('body').addClass('overflow-hidden');
      $input.val(query);
      $resultsWrap.scrollTop(0);
      updateResults(query);
      window.requestAnimationFrame(() => {
        $input.trigger('focus');
      });
      window.setTimeout(() => {
        $input.trigger('focus');
      }, 40);
    };

    const close = () => {
      $modal.addClass('is-closing');
      $('body').removeClass('overflow-hidden');
      activeIndex = -1;
      $launcher.val('');

      closeTimer = window.setTimeout(() => {
        $modal.removeClass('show is-closing').attr('aria-hidden', 'true');
        closeTimer = null;
      }, 220);
    };

    $launcher.on('focus click', function (event) {
      event.preventDefault();
      open($(this).val());
    });

    $launcher.on('keydown', function (event) {
      if (event.key === 'Tab') return;
      event.preventDefault();
      open($(this).val());
    });

    $(document).on('keydown', function (event) {
      const shortcutPressed = (event.ctrlKey || event.metaKey) && normalize(event.key) === 'k';

      if (shortcutPressed) {
        event.preventDefault();
        open();
        return;
      }

      if (event.key === 'Escape' && $modal.hasClass('show')) {
        event.preventDefault();
        close();
      }
    });

    $modal.on('keydown', function (event) {
      if (!$modal.hasClass('show')) return;

      const itemCount = spotlightItems().length;

      if ((event.key === 'ArrowDown' || event.key === 'ArrowUp') && itemCount) {
        event.preventDefault();
        $input.trigger('focus');

        if (event.key === 'ArrowDown') {
          activeIndex = (activeIndex + 1 + itemCount) % itemCount;
        } else {
          activeIndex = (activeIndex - 1 + itemCount) % itemCount;
        }

        paintActive();
      }

      if (event.key === 'Enter' && itemCount && activeIndex > -1) {
        event.preventDefault();
        const href = spotlightItems().eq(activeIndex).find('a').attr('href');
        if (href) window.location.href = href;
      }
    });

    $modal.on('click', function (event) {
      if (event.target === this) {
        close();
      }
    });

    $input.on('input', function () {
      updateResults($(this).val());
    });

    $results.on('mouseenter', 'li', function () {
      const $items = spotlightItems();
      activeIndex = $items.index(this);
      paintActive(false);
    });

    $results.on('click', 'a', function () {
      close();
    });

    return { open, close };
  };

  window.AdminSpotlight = {
    items: rawItems,
    search: searchItems,
    renderResults,
    resolveUrl,
  };

  $(function () {
    if ($('#adminSpotlight').length && $('#searchInput').length) {
      window.AdminSpotlight.modal = createSpotlight();
    }
  });
})(jQuery);
