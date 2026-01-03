<button onclick="printAllTickets()"
 class="px-4 py-2 bg-red-600 text-white rounded flex items-center gap-2">
    <i class="fa-solid fa-print"></i>
    Print All Tickets
</button>
<script>
async function printAllTickets() {
    const res = await fetch('/print-all-ids');
    const ids = await res.json();

    let index = 0;
    let iframe = null;

    function printNext() {
        if (index >= ids.length) {
            console.log("✅ All tickets printed");
            return;
        }

        iframe = document.createElement("iframe");
        iframe.style.display = "none";
        iframe.src = `/print-pdf/${ids[index]}`;

        document.body.appendChild(iframe);

        iframe.onload = () => {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        };

        iframe.contentWindow.onafterprint = () => {
            iframe.remove();
            index++;
            printNext(); // 🔥 next only after finish
        };
    }

    printNext();
}
</script>
