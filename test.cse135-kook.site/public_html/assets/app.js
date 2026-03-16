(function () {
  "use strict";

  const qs = (sel) => document.querySelector(sel);

  const money = (n) => `$${n.toFixed(2)}`;

  // --- Product data (static, no backend needed) ---
  const PRODUCTS = [
    { sku: "sofa-01", name: "Cedar Lounge Sofa", category: "seating", material: "oak", price: 1299, inStock: true, tags: ["sofa", "linen", "oak", "seating"] },
    { sku: "chair-01", name: "Harbor Accent Chair", category: "seating", material: "walnut", price: 449, inStock: true, tags: ["chair", "walnut", "seating"] },
    { sku: "bench-01", name: "Ridge Entry Bench", category: "seating", material: "oak", price: 299, inStock: false, tags: ["bench", "oak", "entry", "seating"] },

    { sku: "table-01", name: "Atlas Coffee Table", category: "tables", material: "walnut", price: 399, inStock: true, tags: ["coffee", "table", "walnut", "tables"] },
    { sku: "dining-01", name: "Monarch Dining Table", category: "tables", material: "oak", price: 999, inStock: true, tags: ["dining", "table", "oak", "tables"] },
    { sku: "side-01", name: "Pico Side Table", category: "tables", material: "black", price: 179, inStock: true, tags: ["side", "table", "black", "tables"] },

    { sku: "shelf-01", name: "Loft Bookshelf", category: "storage", material: "oak", price: 549, inStock: true, tags: ["bookshelf", "oak", "storage"] },
    { sku: "dresser-01", name: "Juniper Dresser", category: "storage", material: "walnut", price: 799, inStock: false, tags: ["dresser", "walnut", "storage"] },
    { sku: "cabinet-01", name: "Ashwood Cabinet", category: "storage", material: "black", price: 649, inStock: true, tags: ["cabinet", "black", "storage"] },

    { sku: "desk-01", name: "Keystone Writing Desk", category: "desks", material: "oak", price: 499, inStock: true, tags: ["desk", "oak", "desks"] },
    { sku: "desk-02", name: "Orbit Standing Desk", category: "desks", material: "black", price: 899, inStock: true, tags: ["standing", "desk", "black", "desks"] },
  ];

  // --- Utilities ---
  const bySku = (sku) => PRODUCTS.find((p) => p.sku === sku) || null;
  const param = (k) => new URLSearchParams(window.location.search).get(k);

  // --- Footer year ---
  const year = qs("#year");
  if (year) year.textContent = String(new Date().getFullYear());

  // --- Theme toggle ---
  const root = document.documentElement;
  const themeBtn = qs("#themeToggle");

  const setTheme = (theme) => {
    const light = theme === "light";
    root.classList.toggle("light", light);
    if (themeBtn) themeBtn.setAttribute("aria-pressed", light ? "true" : "false");
    try { localStorage.setItem("ja_theme", theme); } catch (_) {}
  };

  let savedTheme = "dark";
  try {
    const t = localStorage.getItem("ja_theme");
    if (t === "light" || t === "dark") savedTheme = t;
  } catch (_) {}
  setTheme(savedTheme);

  if (themeBtn) {
    themeBtn.addEventListener("click", () => {
      const next = root.classList.contains("light") ? "dark" : "light";
      setTheme(next);
    });
  }

  // --- Cart (localStorage-backed) ---
  // cart format: { [sku]: qty }
  const CART_KEY = "ja_cart_v1";

  const loadCart = () => {
    try {
      const raw = localStorage.getItem(CART_KEY);
      if (!raw) return {};
      const obj = JSON.parse(raw);
      return obj && typeof obj === "object" ? obj : {};
    } catch (_) {
      return {};
    }
  };

  const saveCart = (cart) => {
    try { localStorage.setItem(CART_KEY, JSON.stringify(cart)); } catch (_) {}
    updateCartBadge();
  };

  const cartCount = () => {
    const cart = loadCart();
    let n = 0;
    for (const k of Object.keys(cart)) n += Number(cart[k]) || 0;
    return n;
  };

  const cartTotal = () => {
    const cart = loadCart();
    let total = 0;
    for (const sku of Object.keys(cart)) {
      const qty = Number(cart[sku]) || 0;
      const p = bySku(sku);
      if (p) total += p.price * qty;
    }
    return total;
  };

  const updateCartBadge = () => {
    const badge = qs("#cartCount");
    if (badge) badge.textContent = String(cartCount());
  };

  const addToCart = (sku, qty) => {
    const p = bySku(sku);
    if (!p) return;
    const cart = loadCart();
    const cur = Number(cart[sku]) || 0;
    const next = cur + (Number(qty) || 1);
    cart[sku] = Math.max(1, Math.min(99, next));
    saveCart(cart);
  };

  const setCartQty = (sku, qty) => {
    const cart = loadCart();
    const q = Number(qty) || 0;
    if (q <= 0) delete cart[sku];
    else cart[sku] = Math.min(99, q);
    saveCart(cart);
  };

  const clearCart = () => saveCart({});

  updateCartBadge();

  // --- Cart modal (shared) ---
  const cartModal = qs("#cartModal");
  const openCartBtn = qs("#openCart");
  const closeCartBtn = qs("#closeCart");
  const cartBody = qs("#cartBody");
  const cartTotalEl = qs("#cartTotal");
  const clearCartBtn = qs("#clearCart");

  const renderCart = () => {
    if (!cartBody || !cartTotalEl) return;

    const cart = loadCart();
    const rows = Object.keys(cart);

    cartBody.innerHTML = "";
    if (rows.length === 0) {
      cartBody.innerHTML = `<p class="muted">Your cart is empty. Add something wooden.</p>`;
      cartTotalEl.textContent = money(0);
      return;
    }

    for (const sku of rows) {
      const qty = Number(cart[sku]) || 0;
      const p = bySku(sku);
      if (!p) continue;

      const row = document.createElement("div");
      row.className = "cart-row";
      row.innerHTML = `
        <div>
          <strong>${p.name}</strong><br/>
          <small>${sku} · ${money(p.price)}</small>
        </div>
        <div class="row wrap">
          <button class="btn" type="button" data-cart-dec="${sku}">−</button>
          <input aria-label="Quantity" class="qty" type="number" min="1" max="99" value="${qty}" data-cart-qty="${sku}" />
          <button class="btn" type="button" data-cart-inc="${sku}">+</button>
          <button class="btn" type="button" data-cart-rm="${sku}">Remove</button>
        </div>
      `;
      cartBody.appendChild(row);
    }

    cartTotalEl.textContent = money(cartTotal());
  };

  const openDialog = (dlg) => {
    if (!dlg) return;
    if (typeof dlg.showModal === "function") dlg.showModal();
    else dlg.setAttribute("open", "open");
  };

  const closeDialog = (dlg) => {
    if (!dlg) return;
    if (typeof dlg.close === "function") dlg.close();
    else dlg.removeAttribute("open");
  };

  if (openCartBtn && cartModal) {
    openCartBtn.addEventListener("click", () => {
      renderCart();
      openDialog(cartModal);
    });
  }
  if (closeCartBtn && cartModal) {
    closeCartBtn.addEventListener("click", () => closeDialog(cartModal));
  }
  if (clearCartBtn) {
    clearCartBtn.addEventListener("click", () => {
      clearCart();
      renderCart();
    });
  }

  // cart event delegation
  document.addEventListener("click", (e) => {
    const el = e.target instanceof HTMLElement ? e.target : null;
    if (!el) return;

    const dec = el.getAttribute("data-cart-dec");
    const inc = el.getAttribute("data-cart-inc");
    const rm = el.getAttribute("data-cart-rm");

    if (dec) {
      const cart = loadCart();
      const cur = Number(cart[dec]) || 0;
      setCartQty(dec, cur - 1);
      renderCart();
    }
    if (inc) {
      const cart = loadCart();
      const cur = Number(cart[inc]) || 0;
      setCartQty(inc, cur + 1);
      renderCart();
    }
    if (rm) {
      setCartQty(rm, 0);
      renderCart();
    }

    const add = el.getAttribute("data-add-sku");
    if (add) {
      addToCart(add, 1);
      const status = qs("#statusLine");
      if (status) status.textContent = `Added to cart: ${add}.`;
    }
  });

  document.addEventListener("change", (e) => {
    const el = e.target instanceof HTMLElement ? e.target : null;
    if (!el) return;
    const sku = el.getAttribute("data-cart-qty");
    if (!sku) return;
    const input = el;
    const qty = Number(input.value) || 1;
    setCartQty(sku, qty);
    renderCart();
  });

  // --- Home: promo modal + quick picks + scroll list + newsletter status ---
  const promoModal = qs("#promoModal");
  const openPromo = qs("#openPromo");
  const closePromo = qs("#closePromo");

  if (openPromo && promoModal) openPromo.addEventListener("click", () => openDialog(promoModal));
  if (closePromo && promoModal) closePromo.addEventListener("click", () => closeDialog(promoModal));

  const quickPicks = qs("#quickPicks");
  if (quickPicks) {
    const picks = ["sofa-01", "table-01", "desk-01"].map(bySku).filter(Boolean);
    quickPicks.innerHTML = "";
    for (const p of picks) {
      const card = document.createElement("div");
      card.className = "card product";
      card.innerHTML = `
        <div class="row space wrap">
          <h3>${p.name}</h3>
          <span class="pill">${p.inStock ? "In stock" : "Out of stock"}</span>
        </div>
        <p class="muted">Material: ${p.material} · Category: ${p.category}</p>
        <div class="row space wrap">
          <div class="price">${money(p.price)}</div>
          <div class="row wrap">
            <a class="btn" href="/product.html?sku=${encodeURIComponent(p.sku)}">View</a>
            <button class="btn primary" type="button" data-add-sku="${p.sku}">Add</button>
          </div>
        </div>
      `;
      quickPicks.appendChild(card);
    }
  }

  const scrollList = qs("#scrollList");
  if (scrollList) {
    const frag = document.createDocumentFragment();
    for (let i = 1; i <= 160; i++) {
      const li = document.createElement("li");
      li.textContent = `Gallery item ${i}`;
      frag.appendChild(li);
    }
    scrollList.appendChild(frag);
  }

  const newsletterForm = qs("#newsletterForm");
  const newsletterStatus = qs("#newsletterStatus");
  if (newsletterForm && newsletterStatus) {
    newsletterForm.addEventListener("submit", () => {
      newsletterStatus.textContent = "Submitting… (GET request will reload this page).";
    });
    newsletterForm.addEventListener("reset", () => {
      newsletterStatus.textContent = "Form reset.";
    });

    const params = new URLSearchParams(window.location.search);
    if (params.has("email")) {
      const email = params.get("email") || "";
      newsletterStatus.textContent = `Thanks! You subscribed with "${email}". (No backend needed.)`;
    }
  }

  // --- Catalog page: filters + product grid ---
  const catalogGrid = qs("#catalogGrid");
  const searchInput = qs("#searchInput");
  const categorySelect = qs("#categorySelect");
  const priceRange = qs("#priceRange");
  const priceLabel = qs("#priceLabel");
  const inStockOnly = qs("#inStockOnly");
  const resultCount = qs("#resultCount");
  const resetFilters = qs("#resetFilters");

  const renderCatalog = () => {
    if (!catalogGrid) return;

    const q = (searchInput ? searchInput.value : "").trim().toLowerCase();
    const cat = categorySelect ? categorySelect.value : "all";
    const maxPrice = priceRange ? Number(priceRange.value) : 2000;
    const stockOnly = !!(inStockOnly && inStockOnly.checked);

    let items = PRODUCTS.slice();

    if (cat !== "all") items = items.filter((p) => p.category === cat);
    items = items.filter((p) => p.price <= maxPrice);
    if (stockOnly) items = items.filter((p) => p.inStock);

    if (q) {
      items = items.filter((p) => {
        const hay = `${p.name} ${p.material} ${p.category} ${p.tags.join(" ")}`.toLowerCase();
        return hay.includes(q);
      });
    }

    catalogGrid.innerHTML = "";
    for (const p of items) {
      const card = document.createElement("div");
      card.className = "card product";
      card.innerHTML = `
        <div class="row space wrap">
          <h3>${p.name}</h3>
          <span class="pill">${p.inStock ? "In stock" : "Out of stock"}</span>
        </div>
        <p class="muted">Material: ${p.material} · Category: ${p.category}</p>
        <div class="row space wrap">
          <div class="price">${money(p.price)}</div>
          <div class="row wrap">
            <a class="btn" href="/product.html?sku=${encodeURIComponent(p.sku)}">View</a>
            <button class="btn primary" type="button" data-add-sku="${p.sku}" ${p.inStock ? "" : "disabled"}>Add</button>
          </div>
        </div>
      `;
      catalogGrid.appendChild(card);
    }

    if (resultCount) resultCount.textContent = `Showing ${items.length} item(s).`;
  };

  if (priceRange && priceLabel) {
    const syncPrice = () => { priceLabel.textContent = `$${priceRange.value}`; };
    priceRange.addEventListener("input", () => { syncPrice(); renderCatalog(); });
    syncPrice();
  }

  if (searchInput) searchInput.addEventListener("input", renderCatalog);
  if (categorySelect) categorySelect.addEventListener("change", renderCatalog);
  if (inStockOnly) inStockOnly.addEventListener("change", renderCatalog);

  if (resetFilters) {
    resetFilters.addEventListener("click", () => {
      if (searchInput) searchInput.value = "";
      if (categorySelect) categorySelect.value = "all";
      if (priceRange) priceRange.value = "2000";
      if (inStockOnly) inStockOnly.checked = false;
      if (priceLabel) priceLabel.textContent = "$2000";
      renderCatalog();
    });
  }

  renderCatalog();

  // --- Product page: load by sku param + option interactions ---
  const productName = qs("#productName");
  const productMeta = qs("#productMeta");
  const productPrice = qs("#productPrice");
  const skuField = qs("#skuField");
  const finishSelect = qs("#finishSelect");
  const mediaLabel = qs("#mediaLabel");
  const qtyInput = qs("#qtyInput");
  const addFromProduct = qs("#addToCartFromProduct");
  const productStatus = qs("#productStatus");

  if (productName && productMeta && productPrice && skuField) {
    const sku = param("sku") || "sofa-01";
    const p = bySku(sku) || PRODUCTS[0];

    productName.textContent = p.name;
    productMeta.textContent = `SKU: ${p.sku} · Category: ${p.category} · In stock: ${p.inStock ? "yes" : "no"}`;
    productPrice.textContent = money(p.price);
    skuField.value = p.sku;

    if (mediaLabel) mediaLabel.textContent = `Material sample: ${p.material}`;

    if (finishSelect && mediaLabel) {
      finishSelect.value = p.material;
      finishSelect.addEventListener("change", () => {
        mediaLabel.textContent = `Material sample: ${finishSelect.value}`;
        if (productStatus) productStatus.textContent = `Finish set to ${finishSelect.value}.`;
      });
    }

    if (addFromProduct) {
      addFromProduct.addEventListener("click", () => {
        const qty = qtyInput ? Number(qtyInput.value) || 1 : 1;
        addToCart(p.sku, qty);
        if (productStatus) productStatus.textContent = `Added ${qty} × ${p.sku} to cart.`;
      });
    }

    // If product form submitted via GET, show a message
    if (productStatus) {
      const params = new URLSearchParams(window.location.search);
      if (params.has("finish") || params.has("qty") || params.has("assembly")) {
        productStatus.textContent = "Options saved (GET). No backend required.";
      }
    }
  }

  // Ensure cart badge updates if other tabs changed localStorage
  window.addEventListener("storage", (e) => {
    if (e.key === CART_KEY) updateCartBadge();
  });
})();
