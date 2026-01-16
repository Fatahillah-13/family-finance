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

    // bikin URL dari href yang sudah ada (agar base path benar)
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
    monthLabel.textContent = state.month;
}

function setLoading(isLoading) {
    state.loading = isLoading;
    loadingEl.classList.toggle("hidden", !isLoading);
    loadMoreBtn.disabled = isLoading;
    setFabLoading(isLoading);
}

function txCard(tx) {
    const amountColor =
        tx.type === "income"
            ? "text-green-700"
            : tx.type === "expense"
            ? "text-red-700"
            : "text-gray-800";

    const title =
        tx.type === "transfer"
            ? "Transfer"
            : tx.category?.name ?? "Uncategorized";

    const accountText = tx.type === "transfer" ? "" : tx.account?.name ?? "";

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
        renderTransactions(json.data, reset);

        state.hasMore = !!json.meta?.has_more;
        loadMoreBtn.classList.toggle("hidden", !state.hasMore);
    } catch (e) {
        console.error(e);
        alert("Failed to load transactions. Check console/log.");
    } finally {
        setLoading(false);
    }
}

function resetAndLoad() {
    state.page = 1;
    renderMonthLabel();
    loadTransactions({ reset: true });
}

// Tabs
document.querySelectorAll(".tab-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
        state.type = btn.dataset.type;
        document
            .querySelectorAll(".tab-btn")
            .forEach((b) => b.classList.remove("bg-gray-100"));
        btn.classList.add("bg-gray-100");
        updateFabLink();
        resetAndLoad();
    });
});

// Search
document.getElementById("searchBtn")?.addEventListener("click", () => {
    state.q = document.getElementById("q")?.value?.trim() ?? "";
    resetAndLoad();
});

// Load more
loadMoreBtn.addEventListener("click", () => {
    if (!state.hasMore) return;
    state.page += 1;
    loadTransactions({ reset: false });
});

// initial
renderMonthLabel();
document
    .querySelector('.tab-btn[data-type="expense"]')
    ?.classList.add("bg-gray-100");
updateFabLink();
resetAndLoad();
