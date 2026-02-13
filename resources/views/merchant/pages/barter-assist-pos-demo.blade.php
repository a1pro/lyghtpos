@extends('merchant.layouts.main')

@section('content')
<div class="container">
    <div id="barter-assist" data-merchant-id="MERCHANT_001" data-terminal-id="TERMINAL_A1" data-transaction-id="TXN_88421" data-amount-due="125.00" data-currency="USD" data-assist-enabled="true" data-assist-type="barter" data-assist-sources="church,nonprofit,sponsor,community-member" data-settlement-mode="pledge" data-release-condition="governed">
    </div>

    <script>
        (function() {

            var assistScript = document.createElement("script");

            assistScript.src = "https://communyti.org/pox/barter-assist.js";

            assistScript.async = true;

            document.head.appendChild(assistScript);

        })();

    </script>

    <!-- Point of Sale | Barter Assist Example -->

    <div class="pox-checkout">

        <h2>Amount Due: $125.00</h2>

        <div class="payment-options">
            <button type="button">Pay Cash</button>
            <button type="button">Pay Card</button>
            <button type="button" onclick="toggleAssist()">Request Barter Assist</button>
        </div>

        <div id="assist-panel" style="display:none; margin-top:20px; padding:15px; border:1px solid #ccc;">

            <h3>Community Barter Assist</h3>

            <p>

                A church, nonprofit, sponsor, or approved community member may pledge value

                toward this transaction.
            </p>

            <label>

                Assist Source:
                <select>
                    <option>Church</option>
                    <option>Nonprofit</option>
                    <option>Sponsor</option>
                    <option>Community Member</option>
                </select>
            </label>

            <br><br>

            <label>

                Amount to Pledge:
                <input type="number" placeholder="Enter amount" />
            </label>

            <br><br>

            <button type="button">Submit Pledge</button>

            <p style="font-size:12px; margin-top:15px;">

                Settlement Mode: Pledge Token<br>

                Release Condition: Governed Satisfaction
            </p>

        </div>

    </div>

    <script>
        function toggleAssist() {

            var panel = document.getElementById("assist-panel");

            panel.style.display = panel.style.display === "none" ? "block" : "none";

        }

    </script>
</div>
@endsection
