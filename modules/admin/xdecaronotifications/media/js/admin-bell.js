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
    const count = root.querySelector('[data-xdecaro-bell-count]');
    const itemsRoot = root.querySelector('[data-xdecaro-bell-items]');
    const toggle = root.querySelector('.xdecaro-notifications-toggle');
    let busy = false;

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

    const renderItems = (items) => {
      itemsRoot.replaceChildren();

      if (!Array.isArray(items) || items.length === 0) {
        appendText(itemsRoot, 'dropdown-item-text text-body-secondary', root.dataset.emptyLabel || 'No notifications.');
        return;
      }

      items.forEach((item) => {
        const actionUrl = typeof item.action_url === 'string' ? item.action_url : '';
        const wrapper = document.createElement(actionUrl ? 'a' : 'div');
        wrapper.className = actionUrl ? 'dropdown-item py-2' : 'dropdown-item-text py-2';

        if (actionUrl) {
          wrapper.setAttribute('href', actionUrl);
        }

        appendText(wrapper, 'fw-semibold text-wrap', item.title);
        appendText(wrapper, 'small text-body-secondary text-wrap', item.message);

        const metadata = [item.priority_label, item.state_label, item.created_label]
          .filter((value) => typeof value === 'string' && value !== '')
          .join(' · ');

        if (metadata) {
          appendText(wrapper, 'small text-body-secondary mt-1', metadata);
        }

        itemsRoot.appendChild(wrapper);
      });
    };

    const poll = async () => {
      if (document.hidden || busy) {
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

    window.setInterval(poll, POLL_INTERVAL);

    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) {
        poll();
      }
    });
  });
})();
