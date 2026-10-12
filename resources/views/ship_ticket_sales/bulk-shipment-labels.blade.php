<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shipment Labels</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 24px auto; max-width: 800px; padding: 0 20px; color: #111827; font-family: Arial, sans-serif; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .toolbar button { padding: 9px 16px; border: 0; border-radius: 4px; background: #075985; color: white; font-weight: 600; cursor: pointer; }
        .entry { width: min(100%, 620px); margin: 0 auto; padding: 24px 18px; border: 1px dashed #6b7280; text-align: center; break-inside: avoid; page-break-inside: avoid; }
        .entry + .entry { margin-top: 16px; }
        .entry p { margin: 5px 0; overflow-wrap: anywhere; font-size: 22px; font-weight: 700; line-height: 1.35; }
        .entry .shipment-id { font-size: 26px; }
        @media print {
            @page { margin: 12mm; }
            body { max-width: none; margin: 0; padding: 0; }
            .toolbar { display: none; }
            .entry { width: 100%; margin: 0 auto; padding: 18px 14px; }
            .entry + .entry { margin-top: 10px; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <strong>Shipment Labels ({{ $sales->count() }})</strong>
        <button type="button" onclick="window.print()">Print</button>
    </div>

    @foreach ($sales as $sale)
        <section class="entry">
            <p>Name: {{ $sale->customer_name }}</p>
            <p class="shipment-id">Shipment ID: {{ $sale->shipment->shipment_id }}</p>
        </section>
    @endforeach

    <script>
        window.addEventListener('load', () => window.print(), { once: true });
    </script>
</body>
</html>
