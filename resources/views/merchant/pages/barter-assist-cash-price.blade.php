@extends('merchant.layouts.main')

@section('title', __('Barter Assist · Cash Price, Trade as Acceptable Settlement'))
@section('content-header', __('Barter Assist · Cash Price, Trade as Acceptable Settlement'))

@section('content')
<style>
    section.content h1 {
        color: #020617;
        font-weight: bold;
        font-size: 26px;
    }

    section.content .small {

        font-size: 13px;
        color: #000;
    }

    section.content .card h2 {
        color: #fff;

    }

    section.content .card label {
        color: #fff;

    }

    section.content .card p {
        color: #fff;

    }

    section.content .card {
        background: #020617;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 16px;
        box-shadow: 0 2px 12px rgba(15, 23, 42, 0.8);
        border: 1px solid #1f2937;
    }

    .row {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
    }

    .col {
        flex: 1;
        min-width: 260px;
    }

    label {
        display: block;
        margin-bottom: 6px;
        font-weight: 500;
        font-size: 13px;
    }

    input[type="text"],
    input[type="number"],
    textarea {
        width: 100%;
        padding: 8px 10px;
        border-radius: 8px;
        border: 1px solid #4b5563;
        font: inherit;
        margin-bottom: 10px;
        background: #020617;
        color: #e5e7eb;
    }

    input::placeholder,
    textarea::placeholder {
        color: #6b7280;
    }

    textarea {
        min-height: 60px;
        resize: vertical;
    }

    .btn {
        display: inline-block;
        padding: 9px 14px;
        border-radius: 999px;
        border: none;
        font: inherit;
        cursor: pointer;
        margin-right: 8px;
        margin-top: 6px;
        font-size: 13px;
    }

    .btn-primary {
        background: #ecfeff;
        color: #0f172a;
        font-weight: 600;
    }

    .btn-secondary {
        background: #111827;
        color: #e5e7eb;
        border: 1px solid #4b5563;
    }

    .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-left: 8px;
    }

    .status-none {
        background: #111827;
        color: #9ca3af;
        border: 1px solid #374151;
    }

    .status-pending {
        background: #facc15;
        color: #713f12;
    }

    .status-complete {
        background: #22c55e;
        color: #052e16;
    }

    .ledger-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px;
    }

    .ledger-tile {
        padding: 10px 12px;
        border-radius: 10px;
        background: #020617;
        border: 1px solid #1f2937;
    }

    .ledger-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #9ca3af;
        margin-bottom: 4px;
    }

    .ledger-value {
        font-size: 18px;
        font-weight: 600;
    }

    .pill {
        display: inline-block;
        margin-top: 4px;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 11px;
        color: #e5e7eb;
        background: rgba(15, 118, 110, 0.3);
        border: 1px solid rgba(34, 197, 94, 0.5);
    }

    pre {
        background: #020617;
        color: #a7f3d0;
        padding: 10px;
        border-radius: 8px;
        font-size: 12px;
        overflow-x: auto;
        max-height: 260px;
        border: 1px solid #1f2937;
    }

</style>

<div class="container">
    {{-- <h1>Barter Assist · Cash Price, Trade as Acceptable Settlement</h1> --}}
    <p class="small">
        The price is set in <strong>cash (USD)</strong>. Trade dollars (TD) are an
        <strong>acceptable settlement medium pegged 1:1 to the cash price</strong>.
        <br>
        When the shopper is short on cash, a sponsor trade member can step in and:
        <br>· Pay the merchant in trade dollars (TD) equal to the cash price
        <br>· Receive the shopper's available cash
    </p>
    <div class="card">
        <h2>1. Transaction Setup</h2>
        <div class="row">
            <div class="col">
                <label>Item / Service</label>
                <input type="text" id="itemName" value="Kitchen Appliance – Member Special">
            </div>
            <div class="col">
                <label>Price (Cash, USD)</label>
                <input type="number" id="itemPrice" value="300">
            </div>
        </div>
        <div class="row">
            <div class="col">
                <label>Shopper Name (Non-Member or Low Cash)</label>
                <input type="text" id="shopperName" value="Alice Shopper">
            </div>
            <div class="col">
                <label>Merchant Name (Trade Member)</label>
                <input type="text" id="merchantName" value="Bob's Appliances">
            </div>
            <div class="col">
                <label>Sponsor Name (Fellow Trade Member)</label>
                <input type="text" id="sponsorName" value="Carol Sponsor (Trade Member)">
            </div>
        </div>
        <div class="row">
            <div class="col">
                <label>Shopper Cash Available (USD)</label>
                <input type="number" id="shopperCashOnHand" value="120">
            </div>
        </div>
        <p class="small">
            The <strong>price is denominated in cash</strong> ($). In a Barter Assist scenario, the
            system uses
            that same amount as the trade-dollar value (TD) the sponsor pays to the merchant.
        </p>
    </div>
    <div class="card">
        <h2>2. Settlement Modes</h2>
        <p class="small">
            In a real Lyght/Bryght / trade-exchange environment, the system could:
            <br>· Settle in Cash (Shopper → Merchant)
            <br>· Settle in Trade (Trade Member → Merchant)
            <br>· Trigger <strong>Barter Assist</strong> when the shopper is short on cash
        </p>
        <p class="small">
            This demo focuses on the <strong>Barter Assist path</strong>:
            <br>· Merchant is paid in <strong>trade dollars (TD)</strong> equal to the cash price <br>· Shopper
            gives available <strong>cash</strong> to the sponsor
        </p>
        <div class="btn-box">
            <button class="btn btn-primary" id="btnSimulate">Simulate Barter Assist
                Transaction</button>
            <span class="status-badge status-none" id="statusBadge">No Transaction</span>
        </div>
    </div>
    <div class="card">
        <h2>3. Ledgers (Before / After)</h2>
        <p class="small">
            Simplified local ledgers. Cash is in USD. Trade balances are in TD (Trade Dollars, pegged 1:1 to USD
            price).
            <br>Rule: <strong>Price is cash. Trade is an acceptable way to settle that price.</strong>
        </p>
        <div class="ledger-grid">
            <div class="ledger-tile">
                <div class="ledger-label">Shopper Cash On Hand (USD)</div>
                <div class="ledger-value text-white" id="ledgerShopperCash">$0.00</div>
                <div class="pill">Pays cash to Sponsor (not Merchant)</div>
            </div>
            <div class="ledger-tile">
                <div class="ledger-label">Merchant Trade Balance (TD)</div>
                <div class="ledger-value text-white" id="ledgerMerchantTrade">0 TD</div>
                <div class="pill">Receives Trade Dollars = Cash Price</div>
            </div>
            <div class="ledger-tile">
                <div class="ledger-label">Sponsor Trade Balance (TD)</div>
                <div class="ledger-value text-white" id="ledgerSponsorTrade">0 TD</div>
                <div class="pill">Spends Trade to Help Shopper</div>
            </div>
            <div class="ledger-tile">
                <div class="ledger-label">Sponsor Cash On Hand (USD)</div>
                <div class="ledger-value text-white" id="ledgerSponsorCash">$0.00</div>
                <div class="pill">Receives Shopper's Cash</div>
            </div>
        </div>
    </div>
    <div class="card">
        <h2>4. Transaction Record</h2>
        <pre id="transactionRecord">{ No transaction yet }</pre>
    </div>
    <div class="card">
        <h2>5. Governance Log</h2>
        <pre id="governanceLog">[Log will appear here]</pre>
    </div>
</div>
@endsection

@section('js')
<script>
    (function() {
        // Simple mock ledger state
        const ledger = {
            shopperCash: 0
            , merchantTrade: 0
            , sponsorTrade: 1000, // sponsor starts with trade dollars (TD)
            sponsorCash: 200 // sponsor starts with some cash (USD)
        };
        const statusBadge = document.getElementById('statusBadge');
        const transactionRecord = document.getElementById('transactionRecord');
        const governanceLog = document.getElementById('governanceLog');
        const ledgerShopperCash = document.getElementById('ledgerShopperCash');
        const ledgerMerchantTrade = document.getElementById('ledgerMerchantTrade');
        const ledgerSponsorTrade = document.getElementById('ledgerSponsorTrade');
        const ledgerSponsorCash = document.getElementById('ledgerSponsorCash');

        function formatMoney(amount) {
            return "$" + amount.toFixed(2);
        }

        function renderLedger() {
            ledgerShopperCash.textContent = formatMoney(ledger.shopperCash);
            ledgerMerchantTrade.textContent = ledger.merchantTrade.toFixed(2) + " TD";
            ledgerSponsorTrade.textContent = ledger.sponsorTrade.toFixed(2) + " TD";
            ledgerSponsorCash.textContent = formatMoney(ledger.sponsorCash);
        }

        function log(message) {
            const ts = new Date().toISOString();
            const existing = governanceLog.textContent === "[Log will appear here]" ? "" :
                governanceLog.textContent + "\n";
            governanceLog.textContent = existing + "[" + ts + "] " + message;
        }

        function setStatus(label, cssClass) {
            statusBadge.textContent = label;
            statusBadge.className = "status-badge " + cssClass;
        }
        // Initial ledger view
        renderLedger();
        document.getElementById('btnSimulate').addEventListener('click', function() {
            const itemName = document.getElementById('itemName').value.trim();
            const itemPriceRaw = document.getElementById('itemPrice').value;
            const shopperName = document.getElementById('shopperName').value.trim();
            const merchantName = document.getElementById('merchantName').value.trim();
            const sponsorName = document.getElementById('sponsorName').value.trim();
            const shopperCashOnHandRaw =
                document.getElementById('shopperCashOnHand').value;
            const itemPrice = parseFloat(itemPriceRaw || "0"); // Cash price ($)
            const shopperCashOnHand = parseFloat(shopperCashOnHandRaw || "0"); // Shopper's cash($)
            if (!itemName || !shopperName || !merchantName || !sponsorName) {
                alert("Item, shopper, merchant, and sponsor must be specified.");
                return;
            }
            if (!itemPrice || itemPrice <= 0) {
                alert("Price must be a positive cash amount.");
                return;
            }
            if (shopperCashOnHand <= 0) {
                alert("Shopper cash available must be greater than zero for this scenario.");
                return;
            }
            // Reset ledger to starting demo values on each simulation
            ledger.shopperCash = shopperCashOnHand;
            ledger.merchantTrade = 0;
            ledger.sponsorTrade = 1000;
            ledger.sponsorCash = 200;
            renderLedger();
            // If shopper has enough cash to pay merchant directly, no Barter Assist is needed.
            if (shopperCashOnHand >= itemPrice) {
                alert("In this Barter Assist demo, we assume the shopper cannot pay the full amount in cash.Lower 'Shopper Cash Available' below the price to see the assist path.");
                setStatus("No Barter Assist Needed", "status-none");
                log("No barter assist: shopper had enough cash to pay merchant directly at cash price.");
                return;
            }
            // Barter Assist path:
            // 1. Sponsor pays merchant full price in Trade Dollars (TD), 1 TD = $1 cash price // 2. Shopper gives all available cash to sponsor
            // Merchant ends with full value in TD; sponsor ends with shopper's cash, reduced TD.
            if (ledger.sponsorTrade < itemPrice) {
                alert("In this demo, sponsor does not have enough trade dollars (TD) to cover the cash price.");
                log("Barter assist failed: sponsor lacked sufficient trade dollars (TD) to match cash price.");
                setStatus("Assist Failed", "status-pending");
                return;
            }
            // Sponsor pays merchant in trade dollars (TD) equal to the cash price
            ledger.sponsorTrade -= itemPrice;
            ledger.merchantTrade += itemPrice;
            // Shopper gives all their cash to sponsor
            ledger.sponsorCash += shopperCashOnHand;
            ledger.shopperCash = 0;
            renderLedger();
            const transaction = {
                id: "BA-" + Date.now()
                , type: "BARTER_ASSIST"
                , itemName: itemName
                , priceCashUSD: itemPrice
                , shopper: {
                    name: shopperName
                    , cashContributedUSD: shopperCashOnHand
                }
                , merchant: {
                    name: merchantName
                    , tradeReceivedTD: itemPrice // pegged 1:1 to cash price
                }
                , sponsor: {
                    name: sponsorName
                    , tradeSpentTD: itemPrice
                    , cashReceivedFromShopperUSD: shopperCashOnHand
                    , netPosition: shopperCashOnHand - itemPrice // shopper cash in minus trade value out 
                }
                , createdAt: new Date().toISOString()
                , notes: "Price denominated in cash. Sponsor settles merchant in trade dollars equal to cash price; shopper's cash goes to sponsor."
            };
            transactionRecord.textContent = JSON.stringify(transaction, null, 2);
            setStatus("Barter Assist Completed", "status-complete");
            log("Barter Assist " + transaction.id +
                ": Price $" + itemPrice.toFixed(2) +
                ". Sponsor '" + sponsorName +
                "' paid " + itemPrice.toFixed(2) +
                " TD (trade) to merchant '" + merchantName +
                "', and received $" + shopperCashOnHand.toFixed(2) +
                " cash from shopper '" + shopperName + "'.");
        });
    })();

</script>
@endsection
