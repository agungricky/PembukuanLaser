<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Click</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f5f6f8;
        }

        .card-stat {
            border: 0;
            border-radius: 12px;
        }

        .icon-box {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .table-card {
            border: 0;
            border-radius: 12px;
        }
    </style>
</head>

<body>
    <div class="container-fluid py-4 px-4">
        <!-- HEADER -->
        <div class="mb-4">
            <h4 class="fw-bold mb-1">
                Dashboard Click
            </h4>
            <small class="text-muted">
                Monitoring jumlah click
            </small>
        </div>
        <!-- STATISTIK -->
        <div class="row g-3 mb-4">
            <!-- TOTAL CLICK -->
            <div class="col-lg-3 col-md-6">
                <div class="card shadow-sm card-stat">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">
                                    Total Click
                                </small>
                                <h2 class="fw-bold mb-0 mt-2">
                                    {{ $totalKlikHariIni }}
                                </h2>
                            </div>
                            <div class="icon-box bg-primary-subtle text-primary">
                                <i class="bi bi-cursor-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- CLICK HARI INI -->
            <div class="col-lg-3 col-md-6">
                <div class="card shadow-sm card-stat">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">
                                    Click Hari Ini
                                </small>
                                <h2 class="fw-bold mb-0 mt-2">
                                    0
                                </h2>
                            </div>
                            <div class="icon-box bg-success-subtle text-success">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- CLICK BULAN INI -->
            <div class="col-lg-3 col-md-6">
                <div class="card shadow-sm card-stat">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">
                                    Click Bulan Ini
                                </small>
                                <h2 class="fw-bold mb-0 mt-2">
                                    0
                                </h2>
                            </div>
                            <div class="icon-box bg-warning-subtle text-warning">
                                <i class="bi bi-bar-chart-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- UNIQUE VISITOR -->
            <div class="col-lg-3 col-md-6">
                <div class="card shadow-sm card-stat">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">
                                    Unique Visitor
                                </small>
                                <h2 class="fw-bold mb-0 mt-2">
                                    0
                                </h2>
                            </div>
                            <div class="icon-box bg-danger-subtle text-danger">
                                <i class="bi bi-people-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- CLICK PER CS -->
        <div class="card shadow-sm table-card mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">
                    Jumlah Click Per CS
                </h6>
                <div class="row g-3">
                    <div class="col-lg col-md-4 col-6">
                        <div class="border rounded p-3 text-center">
                            <small class="text-muted">
                                CS 1
                            </small>
                            <h3 class="fw-bold mb-0 mt-2">
                                0
                            </h3>
                        </div>
                    </div>
                    <div class="col-lg col-md-4 col-6">
                        <div class="border rounded p-3 text-center">
                            <small class="text-muted">
                                CS 2
                            </small>
                            <h3 class="fw-bold mb-0 mt-2">
                                0
                            </h3>
                        </div>
                    </div>
                    <div class="col-lg col-md-4 col-6">
                        <div class="border rounded p-3 text-center">
                            <small class="text-muted">
                                CS 3
                            </small>
                            <h3 class="fw-bold mb-0 mt-2">
                                0
                            </h3>
                        </div>
                    </div>
                    <div class="col-lg col-md-4 col-6">
                        <div class="border rounded p-3 text-center">
                            <small class="text-muted">
                                CS 4
                            </small>
                            <h3 class="fw-bold mb-0 mt-2">
                                0
                            </h3>
                        </div>
                    </div>
                    <div class="col-lg col-md-4 col-6">
                        <div class="border rounded p-3 text-center">
                            <small class="text-muted">
                                CS 5
                            </small>
                            <h3 class="fw-bold mb-0 mt-2">
                                0
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- TABEL -->
        <div class="card shadow-sm table-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">
                        Aktivitas Click
                    </h6>
                    <span class="badge bg-primary">
                        0 Click
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Jumlah Klik</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($riwayat as $item)
                                <tr>
                                    <td>
                                        {{ $loop->iteration }}
                                    </td>

                                    <td>
                                        {{ $item->created_at->format('d M Y') }}
                                    </td>

                                    <td>
                                        <span class="badge bg-primary">
                                            {{ number_format($item->jumlah_click) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        Belum ada riwayat klik
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
