{{-- Fill the phone screen even when Chrome "Desktop site" reports ~980px. --}}
<script>
(function () {
    try {
        if (window.screen && window.screen.width && window.screen.width <= 920) {
            var meta = document.querySelector('meta[name="viewport"]');
            if (meta) {
                meta.setAttribute('content', 'width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover');
            }
        }
    } catch (e) {}
})();
</script>
<style>
    @media (max-width: 1100px) {
        html, body {
            width: 100% !important;
            max-width: 100% !important;
        }
        body.pay-public-body {
            padding: 0 !important;
            align-items: stretch !important;
            justify-content: flex-start !important;
        }
        .pay-card,
        .waiting-card,
        .payment-card {
            max-width: none !important;
            width: 100% !important;
            min-height: 100dvh;
            border-radius: 0 !important;
            margin: 0 !important;
            box-shadow: none !important;
        }
        .finance-shell,
        .finance-page,
        .receipt-container {
            max-width: 100% !important;
            width: 100% !important;
            padding-left: 12px !important;
            padding-right: 12px !important;
        }
        .receipt-container { padding-top: 12px !important; padding-bottom: 12px !important; }
        table { width: 100% !important; }
        .items-table,
        .receipt-details-table,
        .allocations-table {
            display: block;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .no-print .btn,
        .no-print a.btn {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
        }
    }
</style>
