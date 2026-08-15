/* Swipe + dots carousel for product detail pages */
(function () {
  function initCarousel(root) {
    var track = root.querySelector("[data-carousel-track]");
    var slides = track ? track.children : [];
    var dotsWrap = root.querySelector("[data-carousel-dots]");
    if (!track || !slides.length) return;

    var index = 0;
    var startX = 0;
    var deltaX = 0;
    var dragging = false;

    function go(i) {
      index = (i + slides.length) % slides.length;
      track.style.transform = "translateX(-" + index * 100 + "%)";
      if (dotsWrap) {
        dotsWrap.querySelectorAll("button").forEach(function (dot, di) {
          var on = di === index;
          dot.setAttribute("aria-current", on ? "true" : "false");
          dot.className = on
            ? "h-2 w-2 rounded-full bg-accent"
            : "h-2 w-2 rounded-full bg-white/80 ring-1 ring-black/10";
        });
      }
    }

    if (dotsWrap) {
      dotsWrap.innerHTML = "";
      for (var d = 0; d < slides.length; d++) {
        (function (di) {
          var btn = document.createElement("button");
          btn.type = "button";
          btn.setAttribute("aria-label", "รูปที่ " + (di + 1));
          btn.addEventListener("click", function () {
            go(di);
          });
          dotsWrap.appendChild(btn);
        })(d);
      }
    }

    function onStart(x) {
      dragging = true;
      startX = x;
      deltaX = 0;
    }
    function onMove(x) {
      if (!dragging) return;
      deltaX = x - startX;
    }
    function onEnd() {
      if (!dragging) return;
      dragging = false;
      if (Math.abs(deltaX) > 40) go(index + (deltaX < 0 ? 1 : -1));
    }

    root.addEventListener("touchstart", function (e) { onStart(e.touches[0].clientX); }, { passive: true });
    root.addEventListener("touchmove", function (e) { onMove(e.touches[0].clientX); }, { passive: true });
    root.addEventListener("touchend", onEnd);

    root.addEventListener("pointerdown", function (e) {
      if (e.pointerType === "touch") return;
      root.setPointerCapture(e.pointerId);
      onStart(e.clientX);
    });
    root.addEventListener("pointermove", function (e) {
      if (e.pointerType === "touch") return;
      onMove(e.clientX);
    });
    root.addEventListener("pointerup", function (e) {
      if (e.pointerType === "touch") return;
      onEnd();
    });

    root.style.touchAction = "pan-y";
    go(0);
  }

  document.querySelectorAll("[data-carousel]").forEach(initCarousel);
})();
