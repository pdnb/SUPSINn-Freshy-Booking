/* Meridian Commerce — shared chrome helpers */
(function () {
  const ICONS = {
    overview: '<svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
    orders: '<svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6 7h12l-1 12H7L6 7z"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/></svg>',
    products: '<svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 8l8-4 8 4v8l-8 4-8-4V8z"/><path d="M12 12v8M4 8l8 4 8-4"/></svg>',
    customers: '<svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="9" cy="8" r="3"/><path d="M3 19a6 6 0 0 1 12 0"/><circle cx="17" cy="9" r="2.5"/><path d="M21 19a4.5 4.5 0 0 0-6-4"/></svg>',
    inventory: '<svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 7h16v12H4z"/><path d="M8 7V5h8v2M8 12h8"/></svg>',
    discounts: '<svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 12l8-8h6v6l-8 8-6-6z"/><circle cx="15.5" cy="8.5" r="1.2"/></svg>',
    analytics: '<svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 19V5M4 19h16"/><path d="M8 15v-4M12 15V8M16 15v-6"/></svg>',
    settings: '<svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="3"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M5.6 18.4L7 17M17 7l1.4-1.4"/></svg>',
  };

  const NAV = [
    { id: "overview", href: "admin-overview.html", label: "Overview", icon: "overview" },
    { id: "orders", href: "admin-orders.html", label: "Orders", icon: "orders" },
    { id: "products", href: "admin-products.html", label: "Products", icon: "products" },
    { id: "customers", href: "admin-customers.html", label: "Customers", icon: "customers" },
    { id: "inventory", href: "admin-inventory.html", label: "Inventory", icon: "inventory" },
    { id: "discounts", href: "admin-discounts.html", label: "Discounts", icon: "discounts" },
    { id: "analytics", href: "admin-analytics.html", label: "Analytics", icon: "analytics" },
    { id: "settings", href: "admin-settings.html", label: "Settings", icon: "settings" },
  ];

  function statusPill(status) {
    const map = {
      pending: "pill-pending",
      paid: "pill-paid",
      fulfilled: "pill-fulfilled",
      cancelled: "pill-cancelled",
      draft: "pill-draft",
      active: "pill-active",
      expired: "pill-expired",
      low: "pill-low",
    };
    const cls = map[status] || "pill-neutral";
    return '<span class="pill ' + cls + '">' + status + "</span>";
  }

  function ensureChrome() {
    const page = document.body.getAttribute("data-page") || "";
    const sidebar = document.querySelector("[data-od-id='sidebar']");
    if (sidebar && !sidebar.dataset.ready) {
      sidebar.dataset.ready = "1";
      sidebar.innerHTML =
        '<div class="sidebar-brand" data-od-id="brand">' +
        '<div class="sidebar-mark">MC</div>' +
        "<div><strong>Meridian</strong><span>Commerce Admin</span></div>" +
        "</div>" +
        '<div class="nav-group"><div class="nav-label">Store</div>' +
        NAV.map(function (item) {
          const active = page === item.id || (page === "order-detail" && item.id === "orders") || (page === "product-edit" && item.id === "products");
          return (
            '<a class="nav-link' + (active ? " is-active" : "") + '" href="' + item.href + '" data-od-id="nav-' + item.id + '">' +
            ICONS[item.icon] + "<span>" + item.label + "</span></a>"
          );
        }).join("") +
        "</div>" +
        '<div class="sidebar-foot"><a href="index.html" class="btn btn-ghost btn-sm" data-od-id="nav-launcher">Module map</a></div>';
    }

    const topbar = document.querySelector("[data-od-id='topbar']");
    if (topbar && !topbar.dataset.ready) {
      topbar.dataset.ready = "1";
      const store = (window.Meridian && Meridian.state.store.name) || "Meridian Home";
      topbar.innerHTML =
        '<label class="topbar-search" data-od-id="global-search">' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>' +
        '<input type="search" placeholder="Search orders, products, customers" aria-label="Global search" />' +
        "</label>" +
        '<div class="topbar-actions">' +
        '<button type="button" class="store-chip" data-od-id="store-switcher" title="Store switcher stub">' + store + ' ▾</button>' +
        '<button type="button" class="icon-btn" data-od-id="notifications" aria-label="Notifications">' +
        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6 9a6 6 0 0 1 12 0c0 7 3 7 3 7H3s3 0 3-7"/><path d="M10 19a2 2 0 0 0 4 0"/></svg>' +
        "</button>" +
        '<div class="avatar" aria-label="Operator">OP</div>' +
        "</div>";
    }

    if (!document.querySelector(".toast-host")) {
      const host = document.createElement("div");
      host.className = "toast-host";
      host.setAttribute("data-od-id", "toast-host");
      document.body.appendChild(host);
    }
  }

  function toast(message) {
    const host = document.querySelector(".toast-host");
    if (!host) return;
    const el = document.createElement("div");
    el.className = "toast";
    el.textContent = message;
    host.appendChild(el);
    setTimeout(function () {
      el.remove();
    }, 2800);
  }

  function openOverlay(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add("is-open");
  }
  function closeOverlay(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove("is-open");
  }

  function wireDismiss() {
    document.addEventListener("keydown", function (e) {
      if (e.key !== "Escape") return;
      document.querySelectorAll(".overlay.is-open, .drawer.is-open, .dialog.is-open").forEach(function (node) {
        node.classList.remove("is-open");
      });
    });
    document.querySelectorAll("[data-close]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        const target = btn.getAttribute("data-close");
        if (target) {
          closeOverlay(target);
          if (target.indexOf("dialog") >= 0 || target.indexOf("drawer") >= 0) {
            closeOverlay("overlay");
          }
        } else {
          closeOverlay("overlay");
          document.querySelectorAll(".drawer.is-open, .dialog.is-open").forEach(function (n) {
            n.classList.remove("is-open");
          });
        }
      });
    });
  }

  function qs(name) {
    return new URLSearchParams(location.search).get(name);
  }

  window.AdminUI = {
    NAV: NAV,
    statusPill: statusPill,
    ensureChrome: ensureChrome,
    toast: toast,
    openOverlay: openOverlay,
    closeOverlay: closeOverlay,
    wireDismiss: wireDismiss,
    qs: qs,
  };

  document.addEventListener("DOMContentLoaded", function () {
    if (window.Meridian) Meridian.reload();
    ensureChrome();
    wireDismiss();
  });
})();
