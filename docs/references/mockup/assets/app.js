(function () {
  var CART_KEY = "mrsu-freshy-cart-v1";
  var ORDER_KEY = "mrsu-freshy-order-v1";
  var CHECKOUT_KEY = "mrsu-freshy-checkout-v1";

  function read(key, fallback) {
    try {
      var raw = localStorage.getItem(key);
      return raw ? JSON.parse(raw) : fallback;
    } catch (e) {
      return fallback;
    }
  }

  function write(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
  }

  function getCart() {
    return read(CART_KEY, []);
  }

  function setCart(items) {
    write(CART_KEY, items);
    updateBadges();
  }

  function cartCount() {
    return getCart().reduce(function (n, item) {
      return n + (item.qty || 1);
    }, 0);
  }

  function updateBadges() {
    var n = cartCount();
    document.querySelectorAll("[data-cart-badge]").forEach(function (el) {
      el.textContent = String(n);
      el.hidden = n === 0;
    });
  }

  function toast(msg) {
    var el = document.querySelector("[data-toast]");
    if (!el) {
      el = document.createElement("div");
      el.className = "toast";
      el.setAttribute("data-toast", "");
      el.setAttribute("role", "status");
      el.setAttribute("aria-live", "polite");
      var phone = document.querySelector(".phone") || document.body;
      phone.appendChild(el);
    }
    el.textContent = msg;
    el.classList.add("show");
    clearTimeout(toast._t);
    toast._t = setTimeout(function () {
      el.classList.remove("show");
    }, 1800);
  }

  function addToCart(item) {
    var cart = getCart().filter(function (x) {
      return x.id !== item.id;
    });
    cart.push(item);
    setCart(cart);
    toast("เพิ่มลงตะกร้าแล้ว");
  }

  function removeFromCart(id) {
    setCart(
      getCart().filter(function (x) {
        return x.id !== id;
      })
    );
  }

  function getOrder() {
    return read(ORDER_KEY, null);
  }

  function setOrder(order) {
    write(ORDER_KEY, order);
  }

  function getCheckout() {
    return read(CHECKOUT_KEY, null);
  }

  function setCheckout(data) {
    write(CHECKOUT_KEY, data);
  }

  function moneyLabel() {
    return "รอประกาศราคา";
  }

  function bindSeg(groupSelector) {
    document.querySelectorAll(groupSelector).forEach(function (group) {
      group.addEventListener("click", function (e) {
        var btn = e.target.closest("[data-seg]");
        if (!btn || !group.contains(btn)) return;
        group.querySelectorAll("[data-seg]").forEach(function (b) {
          b.setAttribute("aria-pressed", "false");
        });
        btn.setAttribute("aria-pressed", "true");
        group.dispatchEvent(
          new CustomEvent("segchange", {
            detail: { value: btn.getAttribute("data-seg") },
            bubbles: true,
          })
        );
      });
    });
  }

  function selectedSeg(group) {
    var on = group.querySelector('[aria-pressed="true"]');
    return on ? on.getAttribute("data-seg") : null;
  }

  function icon(name) {
    var paths = {
      home: '<path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>',
      bag: '<path d="M7 8V7a5 5 0 0 1 10 0v1M5.5 8h13l-.8 11.2A2 2 0 0 1 15.7 21H8.3a2 2 0 0 1-2-1.8L5.5 8z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>',
      pin: '<path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z" fill="none" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="11" r="2.2" fill="none" stroke="currentColor" stroke-width="1.7"/>',
      user: '<circle cx="12" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M5.5 19.5a6.5 6.5 0 0 1 13 0" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>',
      cart: '<path d="M4 5h2l1.4 9.2a1.5 1.5 0 0 0 1.5 1.3h8.4a1.5 1.5 0 0 0 1.5-1.2L20 8H8" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="19" r="1.3" fill="currentColor"/><circle cx="17" cy="19" r="1.3" fill="currentColor"/>',
      back: '<path d="M15 5 8 12l7 7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
      search: '<circle cx="11" cy="11" r="6" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="m16 16 4 4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>',
    };
    return (
      '<svg viewBox="0 0 24 24" aria-hidden="true">' +
      (paths[name] || "") +
      "</svg>"
    );
  }

  window.FreshyApp = {
    getCart: getCart,
    setCart: setCart,
    addToCart: addToCart,
    removeFromCart: removeFromCart,
    cartCount: cartCount,
    updateBadges: updateBadges,
    getOrder: getOrder,
    setOrder: setOrder,
    getCheckout: getCheckout,
    setCheckout: setCheckout,
    moneyLabel: moneyLabel,
    toast: toast,
    bindSeg: bindSeg,
    selectedSeg: selectedSeg,
    icon: icon,
  };

  document.addEventListener("DOMContentLoaded", updateBadges);
})();
