@extends('layouts.app')

@section('content')
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">

        <!-- Dashboard Title & Pulse Badge -->
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">
                    Dashboard Gudang
                </h1>
                <p class="text-muted small mb-0">
                    Monitoring stok, mutasi gudang, alokasi channel, dan riwayat aktivitas (Bootstrap 5.3 UI).
                </p>
            </div>
            <div>
                <span
                    class="badge bg-white text-primary border rounded-pill px-3 py-2 fw-bold shadow-xs d-inline-flex align-items-center gap-2">
                    <span class="spinner-grow spinner-grow-sm text-primary" style="width: 6px; height: 6px;"
                        role="status"></span>
                    LIVE PULSE • AGU 2026
                </span>
            </div>
        </div>

        <!-- Info Notice Banner -->
        <div class="alert alert-primary border-primary-subtle rounded-4 shadow-xs d-flex align-items-start gap-3 p-3 mb-4">
            <i class="fa-solid fa-circle-info fs-5 text-primary mt-0.5"></i>
            <div class="small">
                <strong class="text-primary-emphasis">Sistem WMS Murni (Bootstrap 5 + JS Vanilla):</strong> Klik kartu
                status di bawah untuk menyaring tabel produk secara otomatis, atau gunakan tombol <strong
                    class="text-primary-emphasis">Tambah Stok</strong> / <strong
                    class="text-primary-emphasis">Mutasi</strong> untuk melakukan pembaruan stok real-time.
            </div>
        </div>

        <!-- 8 Metric Status Cards Grid -->
        <div class="mb-4">

            <!-- Grid Row 1: General Metrics Cards -->
            <div class="row g-3 mb-3">

                <!-- Card 1: Total Stok Tersedia -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 hover-shadow">
                        <div class="card-body p-1 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">
                                        TOTAL STOK TERSEDIA
                                    </span>
                                    <div class="bg-light text-dark rounded-circle d-flex align-items-center justify-content-center"
                                        style="width: 32px; height: 32px;">
                                        <i class="fa-solid fa-boxes-stacked text-secondary"></i>
                                    </div>
                                </div>
                                <h2 id="cardTotalStock" class="h2 fw-bold text-dark my-1">
                                    18.030
                                </h2>
                                <div class="progress mt-3" style="height: 6px;">
                                    <div id="healthProgressBar" class="progress-bar bg-primary" role="progressbar"
                                        style="width: 85%;"></div>
                                </div>
                            </div>
                            <p class="text-muted small mb-0 mt-3">
                                Kesehatan stok: <strong id="healthPercentText" class="text-dark">85% aman</strong>.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Stok Masuk Hari Ini -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 hover-shadow">
                        <div class="card-body p-1 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">
                                        STOK MASUK HARI INI
                                    </span>
                                    <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center"
                                        style="width: 32px; height: 32px;">
                                        <i class="fa-solid fa-circle-arrow-down"></i>
                                    </div>
                                </div>
                                <h2 id="cardStockIn" class="h2 fw-bold text-dark my-1">
                                    2.450
                                </h2>
                            </div>
                            <p class="text-success fw-semibold small mb-0 mt-3">
                                ↑ Aktivitas barang masuk terdaftar
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Stok Keluar Hari Ini -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 hover-shadow">
                        <div class="card-body p-1 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">
                                        STOK KELUAR HARI INI
                                    </span>
                                    <div class="bg-light text-dark rounded-circle d-flex align-items-center justify-content-center"
                                        style="width: 32px; height: 32px;">
                                        <i class="fa-solid fa-circle-arrow-up text-secondary"></i>
                                    </div>
                                </div>
                                <h2 id="cardStockOut" class="h2 fw-bold text-dark my-1">
                                    1.820
                                </h2>
                            </div>
                            <p class="text-muted small mb-0 mt-3">
                                Total barang keluar berdasarkan mutasi.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Mutasi Hari Ini -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 hover-shadow">
                        <div class="card-body p-1 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">
                                        MUTASI HARI INI
                                    </span>
                                    <div class="bg-light text-dark rounded-circle d-flex align-items-center justify-content-center"
                                        style="width: 32px; height: 32px;">
                                        <i class="fa-solid fa-right-left text-secondary"></i>
                                    </div>
                                </div>
                                <h2 id="cardMutationCount" class="h2 fw-bold text-dark my-1">
                                    34
                                </h2>
                            </div>
                            <p class="text-muted small mb-0 mt-3">
                                Terakhir diproses baru saja.
                            </p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Grid Row 2: Status Category Cards (Clickable Filter Buttons) -->
            <div class="row g-3">

                <!-- Card 5: Stok Aman -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div data-status-card="aman"
                        class="status-card-btn card border-0 bg-success-subtle border-success-subtle rounded-4 h-100 p-3 cursor-pointer hover-shadow">
                        <div class="card-body p-1 d-flex flex-column justify-content-between">
                            <div>
                                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center mb-3"
                                    style="width: 36px; height: 36px;">
                                    <i class="fa-solid fa-circle-check"></i>
                                </div>
                                <p class="text-success-emphasis fw-bold text-uppercase mb-1" style="font-size: 11px;">Stok
                                    Aman</p>
                                <h2 id="cardAmanCount" class="h2 fw-bold text-dark my-1">
                                    107
                                </h2>
                            </div>
                            <p class="text-success fw-bold small mb-0 mt-3 d-flex align-items-center gap-1">
                                Filter Stok Aman <i class="fa-solid fa-chevron-right ms-auto"></i>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card 6: Stok Menipis -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div data-status-card="menipis"
                        class="status-card-btn card border-0 bg-warning-subtle border-warning-subtle rounded-4 h-100 p-3 cursor-pointer hover-shadow">
                        <div class="card-body p-1 d-flex flex-column justify-content-between">
                            <div>
                                <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center mb-3"
                                    style="width: 36px; height: 36px;">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                </div>
                                <p class="text-warning-emphasis fw-bold text-uppercase mb-1" style="font-size: 11px;">Stok
                                    Menipis</p>
                                <h2 id="cardMenipisCount" class="h2 fw-bold text-dark my-1">
                                    743
                                </h2>
                            </div>
                            <p class="text-warning-emphasis fw-bold small mb-0 mt-3 d-flex align-items-center gap-1">
                                Filter Stok Menipis <i class="fa-solid fa-chevron-right ms-auto"></i>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card 7: Stok Kritis -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div data-status-card="kritis"
                        class="status-card-btn card border-0 bg-danger-subtle border-danger-subtle rounded-4 h-100 p-3 cursor-pointer hover-shadow">
                        <div class="card-body p-1 d-flex flex-column justify-content-between">
                            <div>
                                <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center mb-3"
                                    style="width: 36px; height: 36px;">
                                    <i class="fa-solid fa-circle-exclamation"></i>
                                </div>
                                <p class="text-danger-emphasis fw-bold text-uppercase mb-1" style="font-size: 11px;">Stok
                                    Kritis</p>
                                <h2 id="cardKritisCount" class="h2 fw-bold text-dark my-1">
                                    48
                                </h2>
                            </div>
                            <p class="text-danger fw-bold small mb-0 mt-3 d-flex align-items-center gap-1">
                                Filter Stok Kritis <i class="fa-solid fa-chevron-right ms-auto"></i>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card 8: Stok Habis -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div data-status-card="habis"
                        class="status-card-btn card border-0 bg-secondary-subtle border-secondary-subtle rounded-4 h-100 p-3 cursor-pointer hover-shadow">
                        <div class="card-body p-1 d-flex flex-column justify-content-between">
                            <div>
                                <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center mb-3"
                                    style="width: 36px; height: 36px;">
                                    <i class="fa-solid fa-circle-xmark"></i>
                                </div>
                                <p class="text-secondary-emphasis fw-bold text-uppercase mb-1" style="font-size: 11px;">
                                    Stok Habis</p>
                                <h2 id="cardHabisCount" class="h2 fw-bold text-dark my-1">
                                    12
                                </h2>
                            </div>
                            <p class="text-secondary-emphasis fw-bold small mb-0 mt-3 d-flex align-items-center gap-1">
                                Filter Stok Habis <i class="fa-solid fa-chevron-right ms-auto"></i>
                            </p>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- Inventory Stock Table Section -->
        <section id="stockTableSection" class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <!-- Table Header Bar -->
            <div
                class="card-header bg-white border-bottom p-3 p-md-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h2 class="h5 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-boxes-stacked text-primary"></i>
                        Daftar Inventory Stok
                    </h2>
                    <p class="text-muted small mb-0">
                        Rincian stok per marketplace & alokasi rak gudang.
                    </p>
                </div>

                <!-- Controls: Filters & Table Search -->
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <!-- Table Search Input -->
                    <div class="input-group input-group-sm" style="max-width: 240px;">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa-solid fa-magnifying-glass text-muted"></i>
                        </span>
                        <input type="text" id="tableSearchInput" placeholder="Cari SKU / Produk..."
                            class="form-control form-control-sm border-start-0 bg-light" />
                    </div>

                    <!-- Status Select Filter -->
                    <select id="statusSelectFilter" class="form-select form-select-sm" style="width: auto;">
                        <option value="all">Semua Status</option>
                        <option value="aman">Stok Aman</option>
                        <option value="menipis">Stok Menipis</option>
                        <option value="kritis">Stok Kritis</option>
                        <option value="habis">Stok Habis</option>
                    </select>

                    <!-- Marketplace Select Filter -->
                    <select id="marketplaceSelectFilter" class="form-select form-select-sm" style="width: auto;">
                        <option value="all">Semua Channel</option>
                        <option value="Shopee">Shopee</option>
                        <option value="Tokopedia">Tokopedia</option>
                        <option value="Lazada">Lazada</option>
                        <option value="TikTok Shop">TikTok Shop</option>
                    </select>
                </div>
            </div>

            <!-- Table Container -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="py-3 px-4">Informasi Produk</th>
                            <th scope="col" class="py-3 px-4">Status Stok</th>
                            <th scope="col" class="py-3 px-4 text-center">Total Stok</th>
                            <th scope="col" class="py-3 px-4 text-center">Shopee</th>
                            <th scope="col" class="py-3 px-4 text-center">Tokopedia</th>
                            <th scope="col" class="py-3 px-4 text-center">Lazada</th>
                            <th scope="col" class="py-3 px-4 text-center">TikTok Shop</th>
                            <th scope="col" class="py-3 px-4 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="stockTableBody">
                        <!-- Rendered dynamically via JS -->
                    </tbody>
                </table>
            </div>

            <!-- Table Footer Pagination Info -->
            <div
                class="card-footer bg-white border-top p-3 d-flex align-items-center justify-content-between text-muted small">
                <p class="mb-0">Menampilkan <strong id="showingCount" class="text-dark">0</strong> produk terpilih</p>
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-outline-secondary btn-sm" disabled>Sebelumnya</button>
                    <span class="btn btn-primary btn-sm px-3 fw-bold disabled">1</span>
                    <button class="btn btn-outline-secondary btn-sm">Selanjutnya</button>
                </div>
            </div>
        </section>

        <!-- Activity & Mutation History Log -->
        <section id="activityLogSection" class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white border-bottom p-3 p-md-4 d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="h5 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-primary"></i>
                        Riwayat Mutasi & Aktivitas Gudang
                    </h2>
                    <p class="text-muted small mb-0">Log pendaftaran stok masuk, keluar, dan pemindahan antar rak.</p>
                </div>
                <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill">
                    Real-time Log
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="py-3 px-4">Waktu</th>
                            <th scope="col" class="py-3 px-4">Jenis</th>
                            <th scope="col" class="py-3 px-4">SKU & Nama Produk</th>
                            <th scope="col" class="py-3 px-4 text-center">Jumlah</th>
                            <th scope="col" class="py-3 px-4">Asal & Tujuan</th>
                            <th scope="col" class="py-3 px-4">Petugas</th>
                            <th scope="col" class="py-3 px-4">Catatan</th>
                        </tr>
                    </thead>
                    <tbody id="activityLogBody">
                        <!-- Rendered dynamically via JS -->
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    </div>

    <!-- Footer -->
    <footer class="bg-white border-top py-3 text-center text-muted small">
        <div class="container-fluid px-4 d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2">
            <p class="mb-0">© 2026 StockFlow WMS. Pure Bootstrap 5.3 Framework & Modern JavaScript.</p>
            <button id="footerBtnCodeModal" class="btn btn-link text-primary p-0 text-decoration-none fw-semibold small">
                Lihat Kode Pure HTML & CSS / Bootstrap 5
            </button>
        </div>
    </footer>

    <!-- Modal: Tambah Stok (Bootstrap 5 Modal) -->
    <div class="modal fade" id="addStockModal" tabindex="-1" aria-labelledby="addStockModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
                <div class="modal-header bg-light">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary text-white rounded-3 p-2 d-flex align-items-center justify-content-center"
                            style="width: 32px; height: 32px;">
                            <i class="fa-solid fa-plus"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark fs-6" id="addStockModalLabel">Tambah Stok Produk Baru
                            </h5>
                            <p class="text-muted mb-0" style="font-size: 11px;">Daftarkan produk dan stok awal ke WMS</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="addStockForm">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark mb-1">SKU Produk *</label>
                                <input type="text" id="addSku" required placeholder="Contoh: KRM-SM-009"
                                    class="form-control form-control-sm" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark mb-1">Kategori *</label>
                                <input type="text" id="addCategory" required placeholder="Pakaian, Elektronik..."
                                    class="form-control form-control-sm" />
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label fw-bold small text-dark mb-1">Nama Produk *</label>
                            <input type="text" id="addName" required placeholder="Nama lengkap produk..."
                                class="form-control form-control-sm" />
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark mb-1">Lokasi Rak Gudang *</label>
                                <input type="text" id="addLocation" required placeholder="Rak A-01"
                                    class="form-control form-control-sm" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark mb-1">Batas Minimum Stok *</label>
                                <input type="number" id="addMinStock" required value="20" min="1"
                                    class="form-control form-control-sm" />
                            </div>
                        </div>

                        <div class="border-top pt-3 mt-3">
                            <p class="fw-bold text-dark small mb-2">Alokasi Stok Marketplace</p>
                            <div class="row g-2">
                                <div class="col-6 col-sm-3">
                                    <label class="form-label text-muted mb-1" style="font-size: 10px;">Shopee</label>
                                    <input type="number" id="addStockShopee" value="0" min="0"
                                        class="form-control form-control-sm" />
                                </div>
                                <div class="col-6 col-sm-3">
                                    <label class="form-label text-muted mb-1" style="font-size: 10px;">Tokopedia</label>
                                    <input type="number" id="addStockTokopedia" value="0" min="0"
                                        class="form-control form-control-sm" />
                                </div>
                                <div class="col-6 col-sm-3">
                                    <label class="form-label text-muted mb-1" style="font-size: 10px;">Lazada</label>
                                    <input type="number" id="addStockLazada" value="0" min="0"
                                        class="form-control form-control-sm" />
                                </div>
                                <div class="col-6 col-sm-3">
                                    <label class="form-label text-muted mb-1" style="font-size: 10px;">TikTok Shop</label>
                                    <input type="number" id="addStockTiktok" value="0" min="0"
                                        class="form-control form-control-sm" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light border-top p-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold"
                            data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-3 fw-semibold px-4">
                            Simpan Produk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Mutasi Stok (Bootstrap 5 Modal) -->
    <div class="modal fade" id="mutasiModal" tabindex="-1" aria-labelledby="mutasiModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
                <div class="modal-header bg-light">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-warning text-dark rounded-3 p-2 d-flex align-items-center justify-content-center"
                            style="width: 32px; height: 32px;">
                            <i class="fa-solid fa-right-left"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark fs-6" id="mutasiModalLabel">Form Mutasi Stok Gudang
                            </h5>
                            <p class="text-muted mb-0" style="font-size: 11px;">Pindahkan, kurangi, atau tambahkan stok
                                secara langsung</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="mutasiForm">
                    <div class="modal-body p-4">
                        <div>
                            <label class="form-label fw-bold small text-dark mb-1">Pilih Produk *</label>
                            <select id="mutasiProductSelect" required class="form-select form-select-sm">
                                <!-- Rendered via JS -->
                            </select>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark mb-1">Jenis Mutasi *</label>
                                <select id="mutasiTypeSelect" required class="form-select form-select-sm">
                                    <option value="masuk">Stok Masuk (+)</option>
                                    <option value="keluar">Stok Keluar (-)</option>
                                    <option value="mutasi">Transfer / Mutasi Channel</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark mb-1">Jumlah (Qty) *</label>
                                <input type="number" id="mutasiQty" required value="10" min="1"
                                    class="form-control form-control-sm" />
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark mb-1">Asal / Sumber</label>
                                <input type="text" id="mutasiSource" value="Gudang Utama"
                                    class="form-control form-control-sm" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark mb-1">Tujuan / Channel</label>
                                <input type="text" id="mutasiDestination" value="Shopee Fulfillment"
                                    class="form-control form-control-sm" />
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label fw-bold small text-dark mb-1">Petugas Operator</label>
                            <input type="text" id="mutasiOperator" value="Alex Rivera"
                                class="form-control form-control-sm" />
                        </div>

                        <div class="mt-3">
                            <label class="form-label fw-bold small text-dark mb-1">Catatan / Alasan Mutasi</label>
                            <textarea id="mutasiNote" rows="2" placeholder="Contoh: Restock mingguan dari pabrik..."
                                class="form-control form-control-sm"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer bg-light border-top p-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold"
                            data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-3 fw-semibold px-4">
                            Submit Mutasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Pure HTML/CSS Code View Modal (Bootstrap 5 Modal) -->
    <div class="modal fade" id="codeModal" tabindex="-1" aria-labelledby="codeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content bg-dark text-light rounded-4 border-secondary shadow-lg overflow-hidden">
                <div class="modal-header bg-black border-secondary">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-code text-info"></i>
                        <h5 class="modal-title fw-bold text-white fs-6" id="codeModalLabel">Kode Source Murni Pure HTML5 &
                            Bootstrap 5 CSS</h5>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button id="btnCopyCode"
                            class="btn btn-primary btn-sm fw-semibold d-flex align-items-center gap-1.5">
                            <i class="fa-solid fa-copy"></i> Salin Kode
                        </button>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-3 font-monospace small bg-black text-success"
                    style="max-height: 70vh; overflow-y: auto;">
                    <pre id="codeSnippetContent" class="mb-0">Loading Bootstrap 5 source code...</pre>
                </div>
            </div>
        </div>
    </div>
@endsection
{{-- 
@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/min/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script>
        $(function () {
            const start = @json($startDate);
            const end = @json($endDate);

            $('#daterange').daterangepicker({
                startDate: start ? moment(start, 'YYYY-MM-DD') : moment(),
                endDate: end ? moment(end, 'YYYY-MM-DD') : moment(),
                locale: {
                    format: 'DD-MM-YYYY',
                    separator: ' s.d ',
                    applyLabel: 'Terapkan',
                    cancelLabel: 'Batal',
                    customRangeLabel: 'Custom',
                    daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                    monthNames: [
                        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                    ],
                    firstDay: 1
                },
                autoUpdateInput: false,
                opens: 'left'
            });

            if (start && end) {
                $('#daterange').val(
                    moment(start, 'YYYY-MM-DD').format('DD-MM-YYYY') +
                    ' s.d ' +
                    moment(end, 'YYYY-MM-DD').format('DD-MM-YYYY')
                );
            }

            $('#daterange').on('apply.daterangepicker', function (ev, picker) {
                $(this).val(picker.startDate.format('DD-MM-YYYY') + ' s.d ' + picker.endDate.format('DD-MM-YYYY'));
                $('#start_date').val(picker.startDate.format('YYYY-MM-DD'));
                $('#end_date').val(picker.endDate.format('YYYY-MM-DD'));
                $('#filterForm').trigger('submit');
            });

            $('#daterange').on('cancel.daterangepicker', function () {
                $(this).val('');
                $('#start_date').val('');
                $('#end_date').val('');
                $('#filterForm').trigger('submit');
            });

            $('#clearRange').on('click', function () {
                $('#daterange').val('');
                $('#start_date').val('');
                $('#end_date').val('');
                $('#filterForm').trigger('submit');
            });

            $('.seg-btn').on('click', function () {
                $('.seg-btn').removeClass('active').attr('aria-selected', 'false');
                $(this).addClass('active').attr('aria-selected', 'true');
                const id = '#table-' + $(this).data('tab');
                $('.table-wrap').attr('hidden', true);
                $(id).removeAttr('hidden');
            });
        });
    </script>
@endpush --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* StockFlow WMS - Professional Bootstrap 5 Custom Theme */

    :root {
        --bs-font-sans-serif: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        --wms-sidebar-width: 270px;
        --wms-header-height: 64px;
    }

    body {
        font-family: var(--bs-font-sans-serif);
        background-color: #f1f5f9;
        color: #0f172a;
        min-height: 100vh;
    }

    /* Cursor pointer helper */
    .cursor-pointer {
        cursor: pointer;
    }

    /* Professional Sidebar Styling */
    .sidebar-wrapper {
        width: var(--wms-sidebar-width);
        background-color: #0f172a;
        /* Slate 900 */
        color: #f8fafc;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        border-right: 1px solid #1e293b;
        position: sticky;
        top: 0;
        height: 100vh;
        z-index: 1020;
    }

    .sidebar-brand {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #1e293b;
    }

    .sidebar-nav {
        padding: 1rem 0.75rem;
        overflow-y: auto;
    }

    .sidebar-heading {
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        padding: 0.75rem 1rem 0.375rem 1rem;
    }

    .sidebar-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.625rem 1rem;
        margin-bottom: 0.25rem;
        color: #94a3b8;
        font-weight: 600;
        font-size: 0.875rem;
        border-radius: 0.5rem;
        text-decoration: none;
        transition: all 0.2s ease-in-out;
    }

    .sidebar-link:hover {
        color: #ffffff;
        background-color: #1e293b;
    }

    .sidebar-link.active {
        color: #ffffff;
        background-color: #2563eb;
        /* Primary Blue */
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    .sidebar-link.active i {
        color: #ffffff !important;
    }

    .sidebar-footer {
        padding: 1rem 1.25rem;
        border-top: 1px solid #1e293b;
        background-color: #090d16;
    }

    /* Offcanvas Sidebar for Mobile */
    .offcanvas-sidebar {
        width: 280px !important;
        background-color: #0f172a;
        color: #f8fafc;
    }

    .offcanvas-sidebar .btn-close {
        filter: invert(1);
    }

    /* Card Enhancements */
    .card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .hover-shadow:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.75rem 1.5rem rgba(15, 23, 42, 0.08) !important;
    }

    /* Status Card Active Indicator */
    .status-card-active {
        outline: 2.5px solid #2563eb !important;
        box-shadow: 0 0.5rem 1.25rem rgba(37, 99, 235, 0.2) !important;
    }

    /* Table Customizations */
    .table th {
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        font-size: 0.6875rem;
        color: #64748b;
        background-color: #f8fafc;
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    ::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 9999px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>
