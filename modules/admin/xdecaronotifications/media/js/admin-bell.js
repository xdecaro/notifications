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
    const readUrl = root.dataset.readUrl || '';
    const readAllUrl = root.dataset.readAllUrl || '';
    const tokenName = root.dataset.tokenName || '';
    const count = root.querySelector('[data-xdecaro-bell-count]');
    const itemsRoot = root.querySelector('[data-xdecaro-bell-items]');
    const toggle = root.querySelector('.xdecaro-notifications-toggle');
    const readAllButton = root.querySelector('[data-xdecaro-bell-read-all]');
    const markingIds = new Set();
    let busy = false;
    let markingAll = false;
    let unreadCount = Math.max(0, Number.parseInt(count?.textContent || '0', 10) || 0);

    if (!pollUrl || !count || !itemsRoot) {
      return;
    }

    const syncReadAllButton = () => {
      if (readAllButton) {
        readAllButton.disabled = markingAll || unreadCount < 1;
      }
    };

    const renderCount = (value) => {
      const unread = Math.max(0, Number.parseInt(value, 10) || 0);
      unreadCount = unread;
      count.textContent = String(unread);
      count.hidden = unread < 1;
      syncReadAllButton();

      if (toggle) {
        const label = root.dataset.bellLabel || 'Notifications';
        toggle.setAttribute('aria-label', `${label}: ${unread}`);
      }
    };

    const renderItems = (items) => {
      itemsRoot.replaceChildren();

      if (!Array.isArray(items) || items.length < 1) {
        appendText(itemsRoot, 'dropdown-item-text text-white opacity-75', root.dataset.emptyLabel || 'No notifications.');
        return;
      }

      items.forEach((item) => {
        const notificationId = Math.max(0, Number.parseInt(item.id, 10) || 0);
        const state = typeof item.state === 'string' ? item.state : 'unread';
        const isUnread = state === 'unread';
        const actionUrl = typeof item.action_url === 'string' ? item.action_url : '';
        const entry = document.createElement('div');
        entry.className = `xdecaro-notification-entry${isUnread ? ' xdecaro-notification-entry-unread' : ''}`;
        entry.dataset.notificationId = String(notificationId);
        entry.dataset.notificationState = state;

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

        entry.appendChild(wrapper);

        if (isUnread && notificationId > 0) {
          const button = document.createElement('button');
          const label = root.dataset.markReadLabel || 'Mark as read';
          button.className = 'xdecaro-notification-read';
          button.type = 'button';
          button.dataset.xdecaroBellRead = '';
          button.dataset.notificationId = String(notificationId);
          button.title = label;
          button.setAttribute('aria-label', label);
          button.disabled = markingIds.has(notificationId);

          const icon = document.createElement('span');
          icon.className = 'icon-check icon-fw';
          icon.setAttribute('aria-hidden', 'true');
          button.appendChild(icon);
          entry.appendChild(button);
        }

        itemsRoot.appendChild(entry);
      });
    };

    const postMutation = async (url, values = {}) => {
      if (!url || !tokenName) {
        return null;
      }

      const body = new URLSearchParams();
      body.set(tokenName, '1');

      Object.entries(values).forEach(([key, value]) => {
        body.set(key, String(value));
      });

      const response = await fetch(url, {
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
        return null;
      }

      const payload = await response.json();

      if (!payload || payload.success !== true || !payload.data) {
        return null;
      }

      return payload.data;
    };

    const poll = async () => {
      if (document.hidden || busy || markingAll || markingIds.size > 0) {
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

    if (readUrl && tokenName) {
      itemsRoot.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-xdecaro-bell-read]');

        if (!button || !itemsRoot.contains(button)) {
          return;
        }

        event.preventDefault();
        event.stopPropagation();

        const notificationId = Math.max(0, Number.parseInt(button.dataset.notificationId || '0', 10) || 0);

        if (notificationId < 1 || markingIds.has(notificationId)) {
          return;
        }

        markingIds.add(notificationId);
        button.disabled = true;

        try {
          const data = await postMutation(readUrl, { notification_id: notificationId });

          if (!data) {
            return;
          }

          renderCount(data.unread);
          renderItems(data.items);
        } catch (error) {
          // Preserve the current bell state if a mark-read request fails.
        } finally {
          markingIds.delete(notificationId);
        }
      });
    }

    if (readAllButton && readAllUrl && tokenName) {
      readAllButton.addEventListener('click', async () => {
        if (markingAll || unreadCount < 1) {
          return;
        }

        markingAll = true;
        syncReadAllButton();

        try {
          const data = await postMutation(readAllUrl);

          if (!data) {
            return;
          }

          renderCount(data.unread);
          renderItems(data.items);
        } catch (error) {
          // Preserve the current bell state if a mark-all-read request fails.
        } finally {
          markingAll = false;
          syncReadAllButton();
        }
      });
    }

    syncReadAllButton();
    window.setInterval(poll, POLL_INTERVAL);

    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) {
        poll();
      }
    });
  });
})();
