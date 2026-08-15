/* Meridian Commerce — shared sample data (labelled Sample). Persist mutations in localStorage. */
(function () {
  const STORE_KEY = "meridian-admin-state-v1";

  const seed = {
    store: {
      name: "Meridian Home",
      domain: "meridianhome.example",
      currency: "USD",
      timezone: "America/Los_Angeles",
      email: "ops@meridianhome.example",
    },
    kpis: {
      revenueToday: 4280,
      ordersToday: 37,
      aov: 115.68,
      conversion: 2.4,
      labels: "Sample data · last 24h",
    },
    products: [
      { id: "PRD-1001", title: "Linen Duvet Cover — Cloud", status: "active", price: 148, compareAt: 168, sku: "LN-DV-CLD", inventory: 42, threshold: 12, variants: ["Twin", "Queen", "King"], category: "Bedding" },
      { id: "PRD-1002", title: "Ceramic Pour-Over Set", status: "active", price: 64, compareAt: null, sku: "CR-PO-SET", inventory: 18, threshold: 10, variants: ["Matte White", "Stone Grey"], category: "Kitchen" },
      { id: "PRD-1003", title: "Oak Side Table", status: "active", price: 220, compareAt: null, sku: "WD-ST-OAK", inventory: 7, threshold: 8, variants: ["Natural", "Smoked"], category: "Furniture" },
      { id: "PRD-1004", title: "Wool Throw — Heather", status: "active", price: 96, compareAt: 110, sku: "WL-TH-HTH", inventory: 5, threshold: 10, variants: ["Heather", "Ink"], category: "Textiles" },
      { id: "PRD-1005", title: "Brass Desk Lamp", status: "draft", price: 132, compareAt: null, sku: "MT-LP-BRS", inventory: 0, threshold: 6, variants: ["Antique Brass"], category: "Lighting" },
      { id: "PRD-1006", title: "Stoneware Dinner Plates (4)", status: "active", price: 78, compareAt: null, sku: "ST-PL-4PK", inventory: 31, threshold: 15, variants: ["Sand", "Slate"], category: "Tabletop" },
    ],
    customers: [
      { id: "CUS-501", name: "Amelia Chen", email: "amelia.chen@example.com", orders: 6, spent: 892, segment: "Repeat", lastOrder: "2026-08-14" },
      { id: "CUS-502", name: "Jonah Blake", email: "j.blake@example.com", orders: 1, spent: 220, segment: "New", lastOrder: "2026-08-15" },
      { id: "CUS-503", name: "Priya Nair", email: "priya.nair@example.com", orders: 11, spent: 2140, segment: "VIP", lastOrder: "2026-08-12" },
      { id: "CUS-504", name: "Marcus Lee", email: "marcus.lee@example.com", orders: 3, spent: 410, segment: "Repeat", lastOrder: "2026-08-10" },
      { id: "CUS-505", name: "Sofia Alvarez", email: "sofia.a@example.com", orders: 2, spent: 174, segment: "New", lastOrder: "2026-08-13" },
    ],
    orders: [
      { id: "ORD-88421", customerId: "CUS-502", customer: "Jonah Blake", email: "j.blake@example.com", total: 220, status: "paid", payment: "Visa ···· 4242", created: "2026-08-15T09:14:00", items: [{ productId: "PRD-1003", title: "Oak Side Table", qty: 1, price: 220, sku: "WD-ST-OAK" }], shipping: { name: "Jonah Blake", line1: "418 Grove St", city: "Portland", region: "OR", postal: "97214" }, tracking: "", timeline: [{ at: "2026-08-15T09:14:00", text: "Order placed" }, { at: "2026-08-15T09:15:00", text: "Payment captured" }] },
      { id: "ORD-88418", customerId: "CUS-501", customer: "Amelia Chen", email: "amelia.chen@example.com", total: 244, status: "pending", payment: "Apple Pay", created: "2026-08-15T07:52:00", items: [{ productId: "PRD-1001", title: "Linen Duvet Cover — Cloud", qty: 1, price: 148, sku: "LN-DV-CLD" }, { productId: "PRD-1004", title: "Wool Throw — Heather", qty: 1, price: 96, sku: "WL-TH-HTH" }], shipping: { name: "Amelia Chen", line1: "90 Fillmore Ave", city: "San Francisco", region: "CA", postal: "94117" }, tracking: "", timeline: [{ at: "2026-08-15T07:52:00", text: "Order placed" }, { at: "2026-08-15T07:53:00", text: "Awaiting payment confirmation" }] },
      { id: "ORD-88402", customerId: "CUS-503", customer: "Priya Nair", email: "priya.nair@example.com", total: 142, status: "paid", payment: "Mastercard ···· 5510", created: "2026-08-14T18:20:00", items: [{ productId: "PRD-1002", title: "Ceramic Pour-Over Set", qty: 1, price: 64, sku: "CR-PO-SET" }, { productId: "PRD-1006", title: "Stoneware Dinner Plates (4)", qty: 1, price: 78, sku: "ST-PL-4PK" }], shipping: { name: "Priya Nair", line1: "12 Riverside Dr", city: "Austin", region: "TX", postal: "78701" }, tracking: "", timeline: [{ at: "2026-08-14T18:20:00", text: "Order placed" }, { at: "2026-08-14T18:21:00", text: "Payment captured" }] },
      { id: "ORD-88391", customerId: "CUS-505", customer: "Sofia Alvarez", email: "sofia.a@example.com", total: 148, status: "fulfilled", payment: "Visa ···· 1881", created: "2026-08-14T11:05:00", items: [{ productId: "PRD-1001", title: "Linen Duvet Cover — Cloud", qty: 1, price: 148, sku: "LN-DV-CLD" }], shipping: { name: "Sofia Alvarez", line1: "6 Willow Lane", city: "Denver", region: "CO", postal: "80205" }, tracking: "1Z999AA10123456784", timeline: [{ at: "2026-08-14T11:05:00", text: "Order placed" }, { at: "2026-08-14T11:06:00", text: "Payment captured" }, { at: "2026-08-14T16:40:00", text: "Fulfilled · UPS" }] },
      { id: "ORD-88370", customerId: "CUS-504", customer: "Marcus Lee", email: "marcus.lee@example.com", total: 96, status: "fulfilled", payment: "Shop Pay", created: "2026-08-13T15:33:00", items: [{ productId: "PRD-1004", title: "Wool Throw — Heather", qty: 1, price: 96, sku: "WL-TH-HTH" }], shipping: { name: "Marcus Lee", line1: "221B Market St", city: "Seattle", region: "WA", postal: "98101" }, tracking: "9400111899223344556677", timeline: [{ at: "2026-08-13T15:33:00", text: "Order placed" }, { at: "2026-08-13T19:10:00", text: "Fulfilled · USPS" }] },
      { id: "ORD-88355", customerId: "CUS-501", customer: "Amelia Chen", email: "amelia.chen@example.com", total: 78, status: "cancelled", payment: "Visa ···· 4242", created: "2026-08-12T09:48:00", items: [{ productId: "PRD-1006", title: "Stoneware Dinner Plates (4)", qty: 1, price: 78, sku: "ST-PL-4PK" }], shipping: { name: "Amelia Chen", line1: "90 Fillmore Ave", city: "San Francisco", region: "CA", postal: "94117" }, tracking: "", timeline: [{ at: "2026-08-12T09:48:00", text: "Order placed" }, { at: "2026-08-12T10:12:00", text: "Cancelled by customer" }] },
      { id: "ORD-88340", customerId: "CUS-503", customer: "Priya Nair", email: "priya.nair@example.com", total: 280, status: "paid", payment: "Amex ···· 1007", created: "2026-08-11T20:02:00", items: [{ productId: "PRD-1001", title: "Linen Duvet Cover — Cloud", qty: 1, price: 148, sku: "LN-DV-CLD" }, { productId: "PRD-1002", title: "Ceramic Pour-Over Set", qty: 2, price: 64, sku: "CR-PO-SET" }], shipping: { name: "Priya Nair", line1: "12 Riverside Dr", city: "Austin", region: "TX", postal: "78701" }, tracking: "", timeline: [{ at: "2026-08-11T20:02:00", text: "Order placed" }, { at: "2026-08-11T20:03:00", text: "Payment captured" }] },
      { id: "ORD-88322", customerId: "CUS-504", customer: "Marcus Lee", email: "marcus.lee@example.com", total: 64, status: "pending", payment: "PayPal", created: "2026-08-10T13:27:00", items: [{ productId: "PRD-1002", title: "Ceramic Pour-Over Set", qty: 1, price: 64, sku: "CR-PO-SET" }], shipping: { name: "Marcus Lee", line1: "221B Market St", city: "Seattle", region: "WA", postal: "98101" }, tracking: "", timeline: [{ at: "2026-08-10T13:27:00", text: "Order placed" }] },
    ],
    discounts: [
      { id: "DSC-01", code: "WELCOME10", type: "percent", value: 10, limit: 200, used: 84, status: "active", starts: "2026-07-01", ends: "2026-12-31" },
      { id: "DSC-02", code: "LINEN25", type: "fixed", value: 25, limit: 50, used: 19, status: "active", starts: "2026-08-01", ends: "2026-08-31" },
      { id: "DSC-03", code: "SPRING15", type: "percent", value: 15, limit: 100, used: 100, status: "expired", starts: "2026-03-01", ends: "2026-05-31" },
    ],
    analytics: {
      period: "7d",
      series: {
        "7d": { labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"], revenue: [3100, 4200, 3800, 5100, 4700, 6200, 4280], orders: [28, 34, 31, 41, 38, 52, 37] },
        "30d": { labels: ["W1", "W2", "W3", "W4"], revenue: [18200, 21400, 19850, 23100], orders: [148, 172, 161, 189] },
        "90d": { labels: ["Jun", "Jul", "Aug"], revenue: [61200, 70400, 64800], orders: [510, 580, 540] },
      },
      note: "Sample series for prototype evaluation",
    },
  };

  function clone(v) {
    return JSON.parse(JSON.stringify(v));
  }

  function load() {
    try {
      const raw = localStorage.getItem(STORE_KEY);
      if (!raw) return clone(seed);
      const parsed = JSON.parse(raw);
      return Object.assign(clone(seed), parsed);
    } catch (e) {
      return clone(seed);
    }
  }

  function save(state) {
    localStorage.setItem(STORE_KEY, JSON.stringify(state));
  }

  let state = load();

  function money(n) {
    return new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(n);
  }

  function formatDate(iso) {
    const d = new Date(iso);
    return d.toLocaleString("en-US", { month: "short", day: "numeric", hour: "numeric", minute: "2-digit" });
  }

  function formatDay(iso) {
    const d = new Date(iso);
    return d.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" });
  }

  function lowStockProducts() {
    return state.products.filter((p) => p.inventory <= p.threshold);
  }

  function attentionQueue() {
    const unpaid = state.orders.filter((o) => o.status === "pending");
    const unfulfilled = state.orders.filter((o) => o.status === "paid");
    const low = lowStockProducts();
    return { unpaid, unfulfilled, low };
  }

  function getOrder(id) {
    return state.orders.find((o) => o.id === id);
  }

  function getProduct(id) {
    return state.products.find((p) => p.id === id);
  }

  function fulfillOrder(orderId, tracking) {
    const order = getOrder(orderId);
    if (!order) return null;
    if (order.status !== "paid" && order.status !== "pending") return order;
    order.status = "fulfilled";
    order.tracking = tracking || "";
    order.timeline.push({
      at: new Date().toISOString(),
      text: tracking ? "Fulfilled · tracking " + tracking : "Marked as fulfilled",
    });
    save(state);
    return order;
  }

  function adjustStock(productId, delta, reason) {
    const product = getProduct(productId);
    if (!product) return null;
    product.inventory = Math.max(0, product.inventory + Number(delta));
    product._lastAdjust = { delta: Number(delta), reason: reason || "", at: new Date().toISOString() };
    save(state);
    return product;
  }

  function publishProduct(productId, fields) {
    let product = getProduct(productId);
    if (!product) {
      product = {
        id: productId || ("PRD-" + (1100 + state.products.length)),
        title: "",
        status: "draft",
        price: 0,
        compareAt: null,
        sku: "",
        inventory: 0,
        threshold: 5,
        variants: [],
        category: "General",
      };
      state.products.unshift(product);
    }
    Object.assign(product, fields || {});
    product.status = "active";
    save(state);
    return product;
  }

  function persist() {
    save(state);
  }

  function saveProductDraft(productId, fields) {
    let product = getProduct(productId);
    if (!product) {
      product = {
        id: productId || "PRD-" + (1100 + state.products.length),
        title: "",
        status: "draft",
        price: 0,
        compareAt: null,
        sku: "",
        inventory: 0,
        threshold: 5,
        variants: [],
        category: "General",
      };
      state.products.unshift(product);
    }
    Object.assign(product, fields || {});
    save(state);
    return product;
  }

  function createDiscount(payload) {
    const row = {
      id: "DSC-" + String(state.discounts.length + 1).padStart(2, "0"),
      code: payload.code,
      type: payload.type,
      value: Number(payload.value),
      limit: Number(payload.limit) || 0,
      used: 0,
      status: "active",
      starts: payload.starts || formatDay(new Date().toISOString()),
      ends: payload.ends || "",
    };
    state.discounts.unshift(row);
    save(state);
    return row;
  }

  function reset() {
    state = clone(seed);
    save(state);
    return state;
  }

  window.Meridian = {
    get state() { return state; },
    reload: function () { state = load(); return state; },
    money: money,
    formatDate: formatDate,
    formatDay: formatDay,
    lowStockProducts: lowStockProducts,
    attentionQueue: attentionQueue,
    getOrder: getOrder,
    getProduct: getProduct,
    fulfillOrder: fulfillOrder,
    adjustStock: adjustStock,
    publishProduct: publishProduct,
    saveProductDraft: saveProductDraft,
    createDiscount: createDiscount,
    persist: persist,
    reset: reset,
    STORE_KEY: STORE_KEY,
  };
})();
