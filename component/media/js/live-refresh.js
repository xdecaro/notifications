(() => {
  'use strict';

  const roots = document.querySelectorAll('[data-xdecaro-live-refresh]');

  if (!roots.length) {
    return;
  }

  const userIsEditing = (root) => {
    const active = document.activeElement;

    if (!active || !root.contains(active)) {
      return false;
    }

    return active.matches('input, select, textarea, [contenteditable="true"]');
  };

  roots.forEach((root) => {
    const currentContent = root.querySelector('[data-xdecaro-live-content]');

    if (!currentContent) {
      return;
    }

    const configured = Number.parseInt(root.dataset.refreshSeconds || '10', 10);
    const refreshSeconds = Number.isFinite(configured)
      ? Math.max(5, Math.min(300, configured))
      : 10;
    let busy = false;

    const refresh = async () => {
      if (document.hidden || busy || userIsEditing(root)) {
        return;
      }

      busy = true;

      try {
        const url = new URL(window.location.href);
        url.searchParams.set('tmpl', 'component');
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

        const html = await response.text();
        const nextDocument = new DOMParser().parseFromString(html, 'text/html');
        const nextRoot = nextDocument.querySelector('[data-xdecaro-live-refresh]');
        const nextContent = nextRoot
          ? nextRoot.querySelector('[data-xdecaro-live-content]')
          : null;

        if (nextContent) {
          currentContent.innerHTML = nextContent.innerHTML;
        }
      } catch (error) {
        // A transient polling failure must not interrupt the administrator UI.
      } finally {
        busy = false;
      }
    };

    window.setInterval(refresh, refreshSeconds * 1000);

    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) {
        refresh();
      }
    });
  });
})();
