(() => {
  'use strict';

  const POLL_INTERVAL = 10000;
  const roots = document.querySelectorAll('[data-xdecaro-notifications-bell]');

  if (!roots.length) {
    return;
  }

  const appendText = (parent, className, text) => {
    const element = document.createElement('div');
    element.className = className;
    element.textContent = String(text || '');
    parent.appendChild(element);
  };

  roots.forEach((root) => {
    const pollUrl = root.dataset.pollUrl || '';
    const archiveUrl = root.dataset.archiveUrl || '';
    const tokenName = root.dataset.tokenName || '';
    const clearConfirm = root.dataset.clearConfirm || '';
    const count = root.querySelector('[data-xdecaro-bell-count]');
    const itemsRoot = root.querySelector('[data-xdecaro-bell-items]');
    const toggle = root.querySelector('.xdecaro-notifications-toggle');
    const clearButton = root.querySelector('[data-xdecaro-bell-clear]');
    let busy = false;
    let clearing = false;
    let hasNotifications = Boolean(itemsRoot?.querySelector('.xdecaro-notification-row'));

    if (!pollUrl || !count || !itemsRoot) {
      return;
    }

    const renderCount = (value) => {
      const unread = Math.max(0, Number.parseInt(value, 10) || 0);
      count.textContent = String(unread);
      count.hidden = unread < 1;

      if (toggle) {
        const label = root.dataset.bellLabel || 'Notifications';
        toggle.setAttribute('aria-label', `${label}: ${unread}`);
      }
    };

    const syncClearButton = () => {
      if (clearButton) {
        clearButton.disabled = clearing || !hasNotifications;
      }
    };

    const renderItems = (items) => {
      itemsRoot.replaceChildren();
      hasNotifications = Array.isArray(items) && items.length > 0;
      syncClearButton();

      if (!hasNotifications) {
        appendText(itemsRoot, 'dropdown-item-text text-white opacity-75', root.dataset.emptyLabel || 'No notifications.');
        return;
      }

      items.forEach((item) => {
        const actionUrl = typeof item.action_url === 'string' ? item.action_url : '';
        const wrapper = document.createElement(actionUrl ? 'a' : 'div');
        wrapper.className = actionUrl
          ? 'dropdown-item py-2 xdecaro-notification-row'
          : 'dropdown-item-text py-2 xdecaro-notification-row';

        if (actionUrl) {
          wrapper.setAttribute('href', actionUrl);
        }

        appendText(wrapper, 'fw-semibold text-wrap xdecaro-notification-title', item.title);

        const content = document.createElement('div');
        content.className = 'xdecaro-notification-content';
        wrapper.appendChild(content);

        appendText(content, 'small text-white opacity-75 text-wrap xdecaro-notification-message', item.message);

        const metadata = [item.priority_label, item.state_label, item.created_label]
          .filter((value) => typeof value === 'string' && value !== '')
          .join(' · ');

        if (metadata) {
          appendText(content, 'small text-white opacity-75 text-wrap xdecaro-notification-meta', metadata);
        }

        itemsRoot.appendChild(wrapper);
      });
    };

    const poll = async () => {
      if (document.hidden || busy || clearing) {
        return;
      }

      busy = true;

      try {
        const url = new URL(pollUrl, window.location.href);
        url.searchParams.set('_live', String(Date.now()));

        const response = await fetch(url.toString(), {
          credentials: 'same-origin',
          cache: 'no-store',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
          },
        });

        if (!response.ok) {
          return;
        }

        const payload = await response.json();

        if (!payload || payload.success !== true || !payload.data) {
          return;
        }

        renderCount(payload.data.unread);
        renderItems(payload.data.items);
      } catch (error) {
        // A transient polling failure must never interrupt the administrator chrome.
      } finally {
        busy = false;
      }
    };

    if (clearButton && archiveUrl && tokenName) {
      clearButton.addEventListener('click', async () => {
        if (clearing || !hasNotifications) {
          return;
        }

        if (clearConfirm && !window.confirm(clearConfirm)) {
          return;
        }

        clearing = true;
        syncClearButton();

        try {
          const body = new URLSearchParams();
          body.set(tokenName, '1');

          const response = await fetch(archiveUrl, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: body.toString(),
          });

          if (!response.ok) {
            return;
          }

          const payload = await response.json();

          if (!payload || payload.success !== true || !payload.data) {
            return;
          }

          renderCount(payload.data.unread);
          renderItems(payload.data.items);
        } catch (error) {
          // Keep the existing bell contents if the archive request fails.
        } finally {
          clearing = false;
          syncClearButton();
        }
      });
    }

    syncClearButton();
    window.setInterval(poll, POLL_INTERVAL);

    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) {
        poll();
      }
    });
  });
})();
