<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Sales Report — {{ $filterLabel }}</title>
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
            color: #333;
            font-size: 11px;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td,
        th {
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .text-gray {
            color: #666;
        }

        .mt-4 {
            margin-top: 16px;
        }

        .mb-2 {
            margin-bottom: 8px;
        }

        .header-title {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #333;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }

        .summary-box {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px 14px;
        }

        .items-table th {
            background-color: #f3f4f6;
            padding: 8px 10px;
            border-bottom: 2px solid #333;
            font-size: 9px;
            text-transform: uppercase;
            text-align: left;
        }

        .items-table td {
            padding: 7px 10px;
            border-bottom: 1px solid #eee;
        }

        .section {
            margin-top: 20px;
        }
    </style>
</head>

<body>
    @php
        $formatAmt = fn ($amount) => number_format((float) $amount, 2, '.', ',');
        $company = auth()->user()->company ?? null;
    @endphp

    {{-- Letterhead --}}
    <table class="mb-2">
        <tr>
            <td style="width: 60%">
                <div class="header-title">Sales Report</div>
                <div class="text-gray">{{ $company->name ?? get_setting('site_name', 'Business') }}</div>
            </td>
            <td style="width: 40%" class="text-right">
                <div class="font-bold">Period: {{ $filterLabel }}</div>
                <div class="text-gray">Generated: {{ now()->format('d M Y, h:i A') }}</div>
            </td>
        </tr>
    </table>

    {{-- Summary --}}
    <table class="section">
        <tr>
            <td style="width: 33%; padding-right: 8px">
                <div class="summary-box">
                    <div class="text-gray" style="font-size: 9px; text-transform: uppercase">Net Sales</div>
                    <div class="font-bold" style="font-size: 16px">₹{{ $formatAmt($salesSummary['net_sales']) }}</div>
                </div>
            </td>
            <td style="width: 33%; padding: 0 4px">
                <div class="summary-box">
                    <div class="text-gray" style="font-size: 9px; text-transform: uppercase">Gross Sales</div>
                    <div class="font-bold" style="font-size: 16px">₹{{ $formatAmt($salesSummary['gross_sales']) }}</div>
                </div>
            </td>
            <td style="width: 33%; padding-left: 8px">
                <div class="summary-box">
                    <div class="text-gray" style="font-size: 9px; text-transform: uppercase">Returns</div>
                    <div class="font-bold" style="font-size: 16px; color: #c00">
                        ₹{{ $formatAmt($salesSummary['returns']) }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Sales by Source --}}
    <div class="section">
        <div class="section-title">Sales By Source</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Source</th>
                    <th class="text-center">Invoices</th>
                    <th class="text-right">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($salesBySource as $source)
                    <tr>
                        <td style="text-transform: uppercase">{{ $source->source }}</td>
                        <td class="text-center">{{ $source->invoice_count }}</td>
                        <td class="text-right font-bold">₹{{ $formatAmt($source->total_revenue) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-gray text-center">No sales data for this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Top Selling Products --}}
    <div class="section">
        <div class="section-title">Top Selling Products</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th class="text-center">Qty Sold</th>
                    <th class="text-right">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($topProducts as $product)
                    <tr>
                        <td>{{ $product->display_name ?? $product->product_name }}</td>
                        <td>{{ $product->sku_code }}</td>
                        <td class="text-center">{{ (float) $product->total_qty_sold }}</td>
                        <td class="text-right font-bold">₹{{ $formatAmt($product->total_revenue) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-gray text-center">No sales data found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Low Selling Products --}}
    <div class="section">
        <div class="section-title">Low Selling Products</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th class="text-center">Qty Sold</th>
                    <th class="text-right">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lowProducts as $product)
                    <tr>
                        <td>{{ $product->display_name ?? $product->product_name }}</td>
                        <td>{{ $product->sku_code }}</td>
                        <td class="text-center">{{ (float) $product->total_qty_sold }}</td>
                        <td class="text-right font-bold">₹{{ $formatAmt($product->total_revenue) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-gray text-center">No sales data found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Top Customers --}}
    <div class="section">
        <div class="section-title">Top Customers</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th class="text-center">Invoices</th>
                    <th class="text-right">Total Spent</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($topCustomers as $customer)
                    <tr>
                        <td>{{ $customer->client_name }}</td>
                        <td>{{ $customer->client_phone }}</td>
                        <td class="text-center">{{ $customer->invoice_count }}</td>
                        <td class="text-right font-bold">₹{{ $formatAmt($customer->total_spent) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-gray text-center">No customer data found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
