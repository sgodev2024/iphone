<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>In tem barcode</title>

    <style>
        @page {
            size: 50mm 30mm;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #eeeeee;
            font-family: Arial, sans-serif;
        }

        .print-toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            padding: 12px;
            text-align: center;
            background: #ffffff;
            border-bottom: 1px solid #dddddd;
        }

        .print-button {
            padding: 8px 18px;
            border: 0;
            border-radius: 4px;
            background: #212529;
            color: #ffffff;
            cursor: pointer;
        }

        .labels-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 15px;
        }

        .barcode-label {
            width: 50mm;
            height: 30mm;
            padding: 1.5mm 2mm;
            overflow: hidden;
            background: #ffffff;
            page-break-after: always;
            break-after: page;
        }

        .store-name {
            font-size: 8px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
        }

        .product-name {
            margin-top: 1px;
            font-size: 7px;
            font-weight: bold;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .label-type {
            margin-top: 1px;
            font-size: 5.5px;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
        }

        .barcode-image {
            height: 10mm;
            margin-top: 1px;
            text-align: center;
            overflow: hidden;
        }

        .barcode-image svg {
            width: 100%;
            height: 100%;
        }

        .barcode-code {
            margin-top: 1px;
            font-size: 7px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 0;
        }

        .label-footer {
            display: flex;
            justify-content: space-between;
            gap: 2px;
            margin-top: 1px;
            font-size: 6px;
            white-space: nowrap;
        }

        <style>@page {
            size: 80mm 50mm;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #eeeeee;
            font-family: Arial, sans-serif;
        }

        .print-toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            padding: 15px;
            text-align: center;
            background: #ffffff;
            border-bottom: 1px solid #dddddd;
        }

        .print-button {
            padding: 10px 25px;
            border: 0;
            border-radius: 5px;
            background: #212529;
            color: #ffffff;
            font-size: 15px;
            cursor: pointer;
        }

        .labels-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
            padding: 25px;
        }

        .barcode-label {
            width: 80mm;
            height: 50mm;
            padding: 4mm;
            overflow: hidden;
            background: #ffffff;
            page-break-after: always;
            break-after: page;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .product-name {
            margin-bottom: 3mm;
            font-size: 13px;
            line-height: 1.3;
            font-weight: bold;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .barcode-image {
            width: 100%;
            height: 23mm;
            text-align: center;
            overflow: hidden;
        }

        .barcode-image svg {
            display: block;
            width: 100%;
            height: 100%;
        }

        .barcode-code {
            margin-top: 2mm;
            font-size: 11px;
            line-height: 1.2;
            font-weight: bold;
            text-align: center;
            letter-spacing: 0.5px;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .no-print {
                display: none !important;
            }

            .labels-wrapper {
                display: block;
                padding: 0;
            }

            .barcode-label {
                width: 100mm;
                height: 60mm;
                margin: 0;
            }
        }
    </style>
</head>

<body>
    <div class="print-toolbar no-print">
        <button type="button" class="print-button" onclick="window.print()">
            In tem
        </button>
    </div>

    <main class="labels-wrapper">
        @foreach ($labels as $label)
            <section class="barcode-label">
                {{-- <div class="store-name">
                    {{ config('app.name') }}
                </div> --}}

                <div class="product-name">
                    {{ $label['product_name'] }}
                </div>

                {{-- <div class="label-type">
                    {{ $label['type_label'] }}
                </div> --}}

                <div class="barcode-image">
                    {!! $label['barcode_svg'] !!}
                </div>

                <div class="barcode-code">
                    {{ $label['barcode'] }}
                </div>
            </section>
        @endforeach
    </main>
</body>

</html>
