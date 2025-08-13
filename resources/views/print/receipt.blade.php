<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt #{{ $sale->id }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <style>
        @page {
            margin: 0;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                margin: 0;
            }
        }

        html, body {
            width: 58mm; /* Change to 80mm for wider printers */
            margin: 0;
            padding: 10px 6px;
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 11px;
            color: #222;
            background: #fff;
        }

        h1, h2, h3 {
            margin: 0 0 8px;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 1px;
        }

        .header-info {
            text-align: center;
            margin-bottom: 8px;
        }

        .header-info .muted {
            display: block;
            font-size: 10px;
            color: #888;
        }

        .customer-info {
            margin-bottom: 10px;
            font-size: 11px;
        }

        .customer-info strong {
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th, td {
            padding: 2px 0;
            text-align: left;
            vertical-align: top;
        }

        th {
            font-size: 11px;
            font-weight: 600;
            border-bottom: 1px dashed #bbb;
        }

        td.num, th.num {
            text-align: right;
        }

        tr {
            page-break-inside: avoid;
        }

        .totals {
            margin-top: 10px;
            font-size: 12px;
        }

        .totals div {
            margin-bottom: 2px;
        }

        .thankyou {
            text-align: center;
            margin-top: 12px;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .btn {
            border: none;
            background: #0ea5e9;
            color: #fff;
            font-size: 12px;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn:active {
            background: #0369a1;
        }
    </style>
</head>
<body>
<div class="no-print" style="text-align:center;margin-bottom:8px;">
    <button type="button" class="btn" onclick="window.print()">Print Receipt</button>
</div>

<div class="header-info">
    <h3>SALES RECEIPT</h3>
    <span class="muted">Receipt #: {{ $sale->id }}</span>
    <span class="muted">Date: {{ optional($sale->created_at)->format('d-m-Y h:i A') }}</span>
</div>

<div class="customer-info">
    <strong>Customer:</strong>
    @if($sale->customer)
        {{ $sale->customer->name }}<br>
        @if($sale->customer->phone)
            <span class="muted">{{ $sale->customer->phone }}</span><br>
        @endif
        @if($sale->customer->email)
            <span class="muted">{{ $sale->customer->email }}</span>
        @endif
    @else
        <span class="muted">Guest</span>
    @endif
</div>

<table class="receipt">
    <thead>
    <tr class="head-name">
        <th colspan="5">Product Details</th>
    </tr>
    <tr class="head-meta">
        <th>Qty</th>
        <th class="num">Price</th>
        <th class="num">Disc.</th>
        <th class="num">Tax</th>
        <th class="num">Total</th>
    </tr>
    </thead>
    @foreach($sale->products as $p)
        <tr>
            <td colspan="5" style="font-weight:bold;">{{ $p->name }}</td>
        </tr>
        <tr>
            <td>{{ $p->pivot->quantity }}</td>
            <td class="num">{{ $p->pivot->unit_price }}</td>
            <td class="num">{{ $p->pivot->discount }}%</td>
            <td class="num">{{ $p->pivot->tax }}</td>
            <td class="num">{{ $p->pivot->price }}</td>
        </tr>
        @if(!$loop->last)
            <tr>
                <td colspan="5">
                    <hr style="border-top:1px dashed #bbb; margin:4px 0;">
                </td>
            </tr>
        @endif
    @endforeach
</table>

<div class="totals">
    <hr>
    <div><strong>Subtotal:</strong> {{ $sale->subtotal }}</div>
    <div><strong>Total Tax:</strong> {{ $sale->tax }}</div>
    <div><strong>Discount:</strong> {{ $sale->discount }}%</div>
    <div><strong>Total Amount:</strong> {{ $sale->total }}</div>
</div>

<hr>
<div class="thankyou">Thank you for shopping with us!</div>

<script>
    // Target URL to go to after printing (e.g., "create new sale")
    const nextUrl = @json($next ?? '/');
    let printInitiated = false;
    let redirected = false;

    function goNext() {
        if (redirected) return;
        redirected = true;
        location.replace(nextUrl);
    }

    function triggerPrint() {
        if (printInitiated) return;
        printInitiated = true;
        setTimeout(() => {
            try {
                window.print();
            } catch (e) {
            }
        }, 50);
        setTimeout(goNext, 5000);
    }

    window.addEventListener('afterprint', goNext);

    if (window.matchMedia) {
        const mql = window.matchMedia('print');
        if (mql.addListener) {
            mql.addListener((e) => {
                if (!e.matches) goNext();
            });
        }
        if (mql.addEventListener) {
            mql.addEventListener('change', (e) => {
                if (!e.matches) goNext();
            });
        }
    }

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && printInitiated) {
            goNext();
        }
    });
    window.addEventListener('focus', () => {
        if (printInitiated) goNext();
    });

    window.addEventListener('load', triggerPrint);
</script>
</body>
</html>
