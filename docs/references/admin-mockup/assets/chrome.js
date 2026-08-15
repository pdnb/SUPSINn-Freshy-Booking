(function () {
  const STATE_KEY = "admin-prototype-state";

  const NAV = [
    { id: "overview", href: "admin-overview.html", label: "ภาพรวม" },
    { id: "orders", href: "admin-orders.html", label: "คิวสลิป", badge: "pending" },
    { id: "fulfillment", href: "admin-fulfillment.html", label: "จัดส่ง" },
    { id: "pickup", href: "admin-pickup.html", label: "รับของ" },
    { id: "production", href: "admin-production.html", label: "สรุปยอด" },
    { id: "products", href: "admin-products.html", label: "สินค้า" },
    { id: "rounds", href: "admin-rounds.html", label: "รอบจอง" },
    { id: "settings", href: "admin-settings.html", label: "ตั้งค่า" },
  ];

  function cloneSeed() {
    return JSON.parse(JSON.stringify(window.AdminSeed));
  }

  function loadState() {
    try {
      const raw = sessionStorage.getItem(STATE_KEY);
      if (raw) {
        return JSON.parse(raw);
      }
    } catch (error) {
      // Fall through to seed.
    }

    const seed = cloneSeed();
    sessionStorage.setItem(STATE_KEY, JSON.stringify(seed));

    return seed;
  }

  function saveState(state) {
    sessionStorage.setItem(STATE_KEY, JSON.stringify(state));
  }

  function pendingCount(state) {
    return state.orders.filter((order) => order.status === "pending_review").length;
  }

  function money(value) {
    return Number(value).toLocaleString("th-TH", { minimumFractionDigits: 0 });
  }

  function pill(status) {
    const label = window.AdminLabels.status[status] || status;
    return `<span class="pill pill-${status}">${label}</span>`;
  }

  function channel(code) {
    return window.AdminLabels.fulfillment[code] || code;
  }

  function queryParam(name) {
    return new URLSearchParams(window.location.search).get(name);
  }

  function nowStamp() {
    const date = new Date();
    const pad = (n) => String(n).padStart(2, "0");

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
  }

  function updateOrder(id, patch, timelineText) {
    const state = loadState();
    const order = state.orders.find((row) => row.id === id);

    if (!order) {
      return null;
    }

    Object.assign(order, patch);

    if (timelineText) {
      order.timeline = [{ at: nowStamp(), text: timelineText }, ...(order.timeline || [])];
    }

    saveState(state);

    return order;
  }

  function toast(message) {
    let root = document.querySelector("[data-od-id='toast-root']");

    if (!root) {
      root = document.createElement("div");
      root.className = "toast-root";
      root.dataset.odId = "toast-root";
      document.body.appendChild(root);
    }

    const node = document.createElement("div");
    node.className = "toast";
    node.setAttribute("role", "status");
    node.textContent = message;
    root.appendChild(node);
    window.setTimeout(() => node.remove(), 2800);
  }

  function closeDialog() {
    const root = document.querySelector("[data-od-id='dialog-root']");

    if (root) {
      root.hidden = true;
      root.innerHTML = "";
    }
  }

  function dialog({ title, bodyHtml, primaryLabel, onPrimary, danger }) {
    let root = document.querySelector("[data-od-id='dialog-root']");

    if (!root) {
      root = document.createElement("div");
      root.className = "dialog-root";
      root.dataset.odId = "dialog-root";
      document.body.appendChild(root);
    }

    root.hidden = false;
    root.innerHTML = `
      <div class="dialog-backdrop" data-od-id="dialog-backdrop"></div>
      <div class="dialog-panel" role="dialog" aria-modal="true" aria-labelledby="dialog-title" data-od-id="dialog-panel">
        <h2 id="dialog-title" class="page-title" style="font-size:18px">${title}</h2>
        <div class="panel-body" style="padding:12px 0 0">${bodyHtml}</div>
        <div class="dialog-actions">
          <button type="button" class="btn btn-ghost" data-od-id="dialog-cancel">ยกเลิก</button>
          <button type="button" class="btn ${danger ? "btn-danger" : "btn-primary"}" data-od-id="dialog-primary">${primaryLabel}</button>
        </div>
      </div>
    `;

    const primary = root.querySelector("[data-od-id='dialog-primary']");
    primary.focus();
    root.querySelector("[data-od-id='dialog-cancel']").addEventListener("click", closeDialog);
    root.querySelector("[data-od-id='dialog-backdrop']").addEventListener("click", closeDialog);
    primary.addEventListener("click", () => {
      onPrimary(root);
      closeDialog();
    });
  }

  function mountChrome({ active }) {
    const state = loadState();
    const sidebar = document.querySelector("[data-chrome='sidebar']");
    const topbar = document.querySelector("[data-chrome='topbar']");

    if (sidebar) {
      sidebar.innerHTML = `
        <a class="sidebar-brand" href="admin-overview.html" data-od-id="nav-brand">${state.storeName}</a>
        <nav class="sidebar-nav" aria-label="เมนูแอดมิน" data-od-id="sidebar-nav">
          <ul>
            ${NAV.map((item) => {
              const isActive = item.id === active;
              const badge = item.badge === "pending" ? pendingCount(state) : 0;

              return `
                <li>
                  <a class="sidebar-link${isActive ? " is-active" : ""}" href="${item.href}" data-od-id="nav-${item.id}"${isActive ? ' aria-current="page"' : ""}>
                    <span>${item.label}</span>
                    ${badge > 0 ? `<span class="sidebar-badge" data-od-id="nav-pending-count">${badge > 99 ? "99+" : badge}</span>` : ""}
                  </a>
                </li>
              `;
            }).join("")}
          </ul>
        </nav>
      `;
    }

    if (topbar) {
      topbar.innerHTML = `
        <input class="topbar-search" type="search" placeholder="ค้นหาออเดอร์หรือรหัสนักศึกษา" aria-label="ค้นหาทั้งระบบ" data-od-id="global-search" />
        <div class="topbar-meta">
          <span>ร้านเดียว · มรส.</span>
          <span class="topbar-avatar" aria-hidden="true">จน</span>
        </div>
      `;
    }

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        closeDialog();
      }
    });
  }

  window.AdminChrome = {
    mount: mountChrome,
    state: loadState,
    save: saveState,
    updateOrder,
    pendingCount,
    money,
    pill,
    channel,
    queryParam,
    toast,
    dialog,
    closeDialog,
    nowStamp,
  };
})();
