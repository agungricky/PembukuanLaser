<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <title>
        Barcode {{ $part->kode_part }}
    </title>

    <style>
        @page {
            size: 100mm 150mm;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            color: #000;
            background: #fff;
        }

        .print-page {
            width: 100mm;
            height: 150mm;
            padding: 4mm;
            page-break-after: always;
            overflow: hidden;
        }

        .print-page:last-child {
            page-break-after: auto;
        }

        .page-header {
            height: 8mm;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: .3mm solid #000;
            margin-bottom: 2mm;
        }

        .part-code {
            font-size: 9pt;
            font-weight: bold;
        }

        .page-number {
            font-size: 7pt;
        }

        .barcode-item {
            height: 25.8mm;
            border-bottom: .25mm dashed #888;
            padding: 1.5mm 0;
            display: flex;
            gap: 2mm;
            align-items: center;
            overflow: hidden;
        }

        .barcode-item:last-child {
            border-bottom: none;
        }

        .barcode-left {
            width: 49%;
            text-align: center;
            overflow: hidden;
        }

        .barcode-left svg {
            width: 100%;
            height: 12mm;
            display: block;
        }

        .no-pesanan {
            font-size: 6.5pt;
            font-weight: bold;
            margin-top: .6mm;
            white-space: nowrap;
        }

        .barcode-right {
            width: 51%;
            min-width: 0;
            font-size: 7pt;
            line-height: 1.25;
        }

        .sku {
            display: inline-block;
            font-size: 7pt;
            font-weight: bold;
            border: .2mm solid #000;
            padding: .4mm 1mm;
            margin-bottom: 1mm;
        }

        .status-random {
            display: inline-block;
            margin-left: 1mm;
            font-size: 6pt;
            font-weight: bold;
        }

        .request {
            font-size: 8pt;
            font-weight: bold;
            line-height: 1.2;
            word-break: break-word;
        }

        .request-detail {
            font-size: 7pt;
            margin-top: .7mm;
            word-break: break-word;
        }

        .unit {
            font-size: 6pt;
            margin-top: .8mm;
        }

        .screen-toolbar {
            padding: 12px;
            background: #f4f6f8;
            display: flex;
            gap: 8px;
        }

        .screen-toolbar button,
        .screen-toolbar a {
            border: 0;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            font-family: Arial, sans-serif;
            font-size: 13px;
        }

        .btn-print {
            background: #0d6efd;
            color: #fff;
        }

        .btn-back {
            background: #e9ecef;
            color: #111;
        }

        @media print {
            .screen-toolbar {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="screen-toolbar">

        <button type="button"
            class="btn-print"
            onclick="window.print()">

            Cetak Barcode
        </button>

        <a href="{{ route('editor.part.show', $part) }}"
            class="btn-back">

            Kembali
        </a>

    </div>

    @foreach($pages as $pageIndex => $rows)

        <div class="print-page">

            <div class="page-header">

                <div class="part-code">
                    {{ $part->kode_part }}
                </div>

                <div class="page-number">
                    {{ $pageIndex + 1 }}/{{ $pages->count() }}
                </div>

            </div>

            @foreach($rows as $row)

                <div class="barcode-item">

                    <div class="barcode-left">

                        {!! $row['barcode'] !!}

                        <div class="no-pesanan">
                            {{ $row['no_pesanan'] }}
                        </div>

                    </div>

                    <div class="barcode-right">

                        <div>

                            <span class="sku">
                                {{ $row['sku'] }}
                            </span>

                            @if($row['status_request'] === 'random')

                                <span class="status-random">
                                    RANDOM
                                </span>

                            @endif

                        </div>

                        @if($row['plat_lengkap'])

                            <div class="request">
                                {{ $row['plat_lengkap'] }}
                            </div>

                        @endif

                        @if($row['nama'])

                            <div class="request-detail">
                                Nama:
                                <strong>
                                    {{ $row['nama'] }}
                                </strong>
                            </div>

                        @endif

                        @if($row['tanggal_bulan_tahun'])

                            <div class="request-detail">
                                Tanggal:
                                <strong>
                                    {{ $row['tanggal_bulan_tahun'] }}
                                </strong>
                            </div>

                        @endif

                        @if($row['jumlah'] > 1)

                            <div class="unit">
                                Unit
                                {{ $row['unit'] }}
                                /
                                {{ $row['jumlah'] }}
                            </div>

                        @endif

                    </div>

                </div>

            @endforeach

        </div>

    @endforeach

</body>

</html>