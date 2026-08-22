<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <title>
        QR {{ $part->kode_part }}
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
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #000;
            background: #fff;
        }

        .print-page {
            position: relative;
            width: 100mm;
            height: 150mm;
            margin: 0;
            padding: 0;
            overflow: hidden;
            page-break-after: always;
        }

        .print-page.last-page {
            page-break-after: auto;
        }

        .item {
            position: absolute;
            left: 1mm;
            width: 98mm;
            height: 21mm;
            margin: 0;
            padding: 0;
            overflow: hidden;
            border-bottom: .25mm dashed #777;
        }

        .item-table {
            width: 100%;
            height: 21mm;
            margin: 0;
            padding: 0;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .item-table tr {
            height: 21mm;
        }

        .request-cell {
            width: 45%;
            height: 21mm;
            vertical-align: middle;
            padding: .2mm 1mm;
            overflow: hidden;
        }

        .product-cell {
            width: 35%;
            height: 21mm;
            vertical-align: middle;
            text-align: center;
            padding: .2mm .8mm;
            border-left: .2mm solid #aaa;
            overflow: hidden;
        }

        .qr-cell {
            width: 20%;
            height: 21mm;
            vertical-align: middle;
            text-align: center;
            padding: .1mm;
            border-left: .2mm solid #aaa;
            overflow: hidden;
        }

        .request-main {
            margin: 0;
            padding: 0;
            font-size: 15pt;
            line-height: .95;
            font-weight: 900;
            word-wrap: break-word;
        }

        .request-secondary {
            margin: .4mm 0 0;
            padding: 0;
            font-size: 12pt;
            line-height: .95;
            font-weight: 900;
            word-wrap: break-word;
        }

        .request-date {
            margin: .4mm 0 0;
            padding: 0;
            font-size: 11pt;
            line-height: .95;
            font-weight: 800;
            word-wrap: break-word;
        }

        .random {
            margin: 0;
            padding: 0;
            font-size: 19pt;
            line-height: 1;
            font-weight: 900;
        }

        .unit {
            margin-top: .4mm;
            font-size: 7pt;
            line-height: 1;
            font-weight: 800;
        }

        .product-name {
            margin: 0;
            padding: 0;
            font-size: 8pt;
            line-height: 1;
            font-weight: 900;
            text-align: center;
            word-wrap: break-word;
        }

        .variation {
            margin: .4mm 0 0;
            padding: 0;
            font-size: 7pt;
            line-height: 1;
            font-weight: 800;
            text-align: center;
            word-wrap: break-word;
        }

        .qr-image {
            display: block;
            width: 14mm;
            height: 14mm;
            margin: 0 auto;
            padding: 0;
        }

        .no-pesanan {
            margin-top: .1mm;
            padding: 0;
            font-size: 4.5pt;
            line-height: 1;
            font-weight: 800;
            text-align: center;
            white-space: nowrap;
        }
    </style>
</head>

<body>

    @foreach($pages as $pageRows)

        <div class="print-page {{ $loop->last ? 'last-page' : '' }}">

            @foreach($pageRows as $row)

                @php
                    $top = $loop->index * 21;
                @endphp

                <div class="item"
                    style="top: {{ $top }}mm;">

                    <table class="item-table">

                        <tr>

                            <td class="request-cell">

                                @if($row['status_request'] === 'random')

                                    <div class="random">
                                        RANDOM
                                    </div>

                                @else

                                    @if($row['plat_lengkap'])

                                        <div class="request-main">
                                            {{ $row['plat_lengkap'] }}
                                        </div>

                                    @endif

                                    @if($row['nama'])

                                        <div class="request-secondary">
                                            {{ $row['nama'] }}
                                        </div>

                                    @endif

                                    @if($row['tanggal_bulan_tahun'])

                                        <div class="request-date">
                                            {{ $row['tanggal_bulan_tahun'] }}
                                        </div>

                                    @endif

                                @endif

                                @if($row['jumlah'] > 1)

                                    <div class="unit">
                                        UNIT {{ $row['unit'] }}/{{ $row['jumlah'] }}
                                    </div>

                                @endif

                            </td>

                            <td class="product-cell">

                                <div class="product-name">
                                    {{ $row['nama_produk'] }}
                                </div>

                                @if($row['variasi'])

                                    <div class="variation">
                                        {{ $row['variasi'] }}
                                    </div>

                                @endif

                            </td>

                            <td class="qr-cell">

                                <img
                                    src="{{ $row['qr_code'] }}"
                                    class="qr-image"
                                    alt="QR">

                                <div class="no-pesanan">
                                    {{ $row['no_pesanan'] }}
                                </div>

                            </td>

                        </tr>

                    </table>

                </div>

            @endforeach

        </div>

    @endforeach

</body>

</html>
