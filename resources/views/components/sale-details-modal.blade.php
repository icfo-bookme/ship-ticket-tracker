<x-entity-modal id="saleReportDetailModal" title="Sale Details" maxWidth="4xl" :hideFooter="true">
    <div class="space-y-5 p-5">
        <div id="saleReportPaymentHistory"></div>
        <section>
            <h4 class="mb-3 text-xs font-medium uppercase text-gray-500">Verification Timeline</h4>
            <div id="saleReportVerificationTimeline" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3"></div>
        </section>
        <dl id="saleReportDetails" class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2 lg:grid-cols-3"></dl>
    </div>
</x-entity-modal>
