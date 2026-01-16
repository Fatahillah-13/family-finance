const state = {
    type: "expense",
    month: new Date().toISOString().slice(0, 7), // YYYY-MM
    q: "",
    page: 1,
    hasMore: false,
    loading: false,
};

const txList = document.getElementById("txList");
const loadMoreBtn = document.getElementById("loadMoreBtn");
const loadingEl = document.getElementById("loading");
const monthLabel = document.getElementById("activeMonthLabel");
const fabAddTx = document.getElementById("fabAddTx");

function addMonths(yyyyMm, delta) {
    const [y, m] = yyyyMm.split("-").map(Number); // m: 1..12
    const d = new Date(y, m - 1, 1);
    d.setMonth(d.getMonth() + delta);

    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, "0");
    return `${year}-${month}`;
}

function setFabLoading(isLoading) {
    if (!fabAddTx) return;

    if (isLoading) {
        fabAddTx.classList.add("opacity-50", "pointer-events-none");
        fabAddTx.setAttribute("aria-disabled", "true");
        fabAddTx.setAttribute("tabindex", "-1");
    } else {
        fabAddTx.classList.remove("opacity-50", "pointer-events-none");
        fabAddTx.removeAttribute("aria-disabled");
        fabAddTx.removeAttribute("tabindex");
    }
}

function updateFabLink() {
    if (!fabAddTx) return;

    const url = new URL(fabAddTx.getAttribute("href"), window.location.origin);
    url.searchParams.set("type", state.type);

    fabAddTx.setAttribute(
        "href",
        url.pathname + "?" + url.searchParams.toString()
    );
}

function formatIDR(amount) {
    return new Intl.NumberFormat("id-ID").format(amount);
}

function renderMonthLabel() {
    const [y, m] = state.month.split("-").map(Number);
    const d = new Date(y, m - 1, 1);
    if (monthLabel) {
        monthLabel.textContent = d.toLocaleString("id-ID", {
            month: "short",
            year: "numeric",
        });
    }
}

function highlightActiveTab() {
    document
        .querySelectorAll(".tab-btn")
        .forEach((b) => b.classList.remove("bg-gray-100"));
    document
        .querySelector(`.tab-btn[data-type="${state.type}"]`)
        ?.classList.add("bg-gray-100");
}

function setLoading(isLoading) {
    state.loading = isLoading;
    loadingEl?.classList.toggle("hidden", !isLoading);
    if (loadMoreBtn) loadMoreBtn.disabled = isLoading;
    setFabLoading(isLoading);
}

function emptyStateCard() {
    return `
    <div class="bg-white shadow-sm rounded-lg p-6 text-center">
      <div class="text-gray-900 font-semibold">Belum ada transaksi</div>
      <div class="mt-1 text-sm text-gray-600">
        Tidak ada transaksi untuk periode ini. Silakan tambah transaksi baru.
      </div>
      <div class="mt-4">
        <a href="${fabAddTx?.getAttribute("href") ?? "#"}"
           class="inline-flex items-center px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">
          + Tambah transaksi
        </a>
      </div>
    </div>
  `;
}

function txCard(tx) {
    const amountColor =
        tx.type === "income"
            ? "text-green-700"
            : tx.type === "expense"
            ? "text-red-700"
            : "text-gray-700";

    const title =
        tx.type === "transfer"
            ? `${tx.from_account?.name ?? "From"} → ${
                  tx.to_account?.name ?? "To"
              }`
            : tx.category?.name ?? "Uncategorized";

    const accountText =
        tx.type === "transfer" ? "Transfer" : tx.account?.name ?? "";

    return `
    <div class="bg-white shadow-sm rounded-lg p-4">
      <div class="flex justify-between gap-3">
        <div class="min-w-0">
          <div class="font-semibold truncate">${title}</div>
          <div class="text-sm text-gray-600 truncate">${
              tx.description ?? ""
          }</div>
        </div>
        <div class="font-semibold ${amountColor} whitespace-nowrap">
          ${
              tx.type === "income" ? "+" : tx.type === "expense" ? "-" : ""
          }Rp ${formatIDR(tx.amount)}
        </div>
      </div>
      <div class="mt-2 text-xs text-gray-500 flex justify-between">
        <div>${tx.occurred_at ?? ""}</div>
        <div class="truncate">${accountText}</div>
      </div>
    </div>
  `;
}

function renderTransactions(items, reset = false) {
    if (reset) txList.innerHTML = "";
    txList.insertAdjacentHTML("beforeend", items.map(txCard).join(""));
}

async function loadTransactions({ reset = false } = {}) {
    if (state.loading) return;

    setLoading(true);

    const params = new URLSearchParams({
        type: state.type,
        month: state.month,
        q: state.q,
        page: String(state.page),
    });

    const url = `${window.TRANSACTIONS_DATA_URL}?${params.toString()}`;

    try {
        const res = await fetch(url, {
            headers: { Accept: "application/json" },
            credentials: "same-origin",
        });

        if (!res.ok) {
            const text = await res.text();
            throw new Error(text);
        }

        const json = await res.json();

        if (reset) {
            txList.innerHTML = "";

            if (!json.data || json.data.length === 0) {
                txList.insertAdjacentHTML("beforeend", emptyStateCard());
                state.hasMore = false;
                loadMoreBtn?.classList.add("hidden");
                return;
            }
        }

        renderTransactions(json.data, false);

        state.hasMore = !!json.meta?.has_more;
        loadMoreBtn?.classList.toggle("hidden", !state.hasMore);
    } catch (e) {
        console.error(e);
        alert("Failed to load transactions. Check console/log.");
    } finally {
        setLoading(false);
    }
}

function initStateFromUrl() {
    const url = new URL(window.location.href);

    const type = url.searchParams.get("type");
    const month = url.searchParams.get("month");
    const q = url.searchParams.get("q");

    if (["expense", "income", "transfer"].includes(type)) {
        state.type = type;
    }
    if (month && /^\d{4}-\d{2}$/.test(month)) {
        state.month = month;
    }
    if (typeof q === "string") {
        state.q = q;
        const qInput = document.getElementById("q");
        if (qInput) qInput.value = q;
    }
}

function syncUrl() {
    const url = new URL(window.location.href);

    url.searchParams.set("type", state.type);
    url.searchParams.set("month", state.month);

    const qTrim = (state.q ?? "").trim();
    if (qTrim) url.searchParams.set("q", qTrim);
    else url.searchParams.delete("q");

    window.history.replaceState({}, "", url);
}

function resetAndLoad() {
    state.page = 1;
    syncUrl();
    renderMonthLabel();
    highlightActiveTab();
    updateFabLink();
    loadTransactions({ reset: true });
}

// Month switcher
document.getElementById("prevMonthBtn")?.addEventListener("click", () => {
    if (state.loading) return;
    state.month = addMonths(state.month, -1);
    resetAndLoad();
});

document.getElementById("nextMonthBtn")?.addEventListener("click", () => {
    if (state.loading) return;
    state.month = addMonths(state.month, 1);
    resetAndLoad();
});

// Tabs
document.querySelectorAll(".tab-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
        state.type = btn.dataset.type;
        resetAndLoad();
    });
});

// Search
document.getElementById("searchBtn")?.addEventListener("click", () => {
    state.q = document.getElementById("q")?.value?.trim() ?? "";
    resetAndLoad();
});

// Load more
loadMoreBtn?.addEventListener("click", () => {
    if (!state.hasMore) return;
    state.page += 1;
    loadTransactions({ reset: false });
});

// initial
initStateFromUrl();
resetAndLoad();
