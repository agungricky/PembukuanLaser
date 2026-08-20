<div class="sidebar">

    @if (auth()->check() && in_array(auth()->user()->role, ['pegawai', 'manager']))
        <div class="sidebar">

            <div class="menu-section">
                <div class="menu-header" onclick="toggleMenu(this)">
                    <span class="menu-title">
                        <i class="bi bi-box-seam me-2"></i>
                        Pesanan
                    </span>
                    <span class="arrow">
                        <i class="bi bi-chevron-down"></i>
                    </span>
                </div>

                <ul class="submenu {{ request()->routeIs('pesanan.*') ? 'open' : '' }}">
                    <li>
                        <a href="{{ route('pesanan.index') }}" class="{{ Request::is('pesanan') ? 'active' : '' }}">
                            <i class="bi bi-list-ul me-2"></i>
                            Semua Pesanan
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('pesanan.proses') }}" class="{{ Request::is('proses') ? 'active' : '' }}">
                            <i class="bi bi-gear me-2"></i>
                            Pesanan Diproses
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('pesanan.kirim') }}" class="{{ Request::is('kirim') ? 'active' : '' }}">
                            <i class="bi bi-truck me-2"></i>
                            Pesanan Dikirim
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('pesanan.terima') }}" class="{{ Request::is('terima') ? 'active' : '' }}">
                            <i class="bi bi-check2-circle me-2"></i>
                            Pesanan Diterima
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('pesanan.return') }}" class="{{ Request::is('return') ? 'active' : '' }}">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>
                            Batal/Return
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('pesanan.affiliate') }}"
                            class="{{ Request::is('affiliate') ? 'active' : '' }}">
                            <i class="bi bi-star me-2"></i>
                            Pesanan Affiliate
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('pesanan.cek') }}" class="{{ Request::is('cek') ? 'active' : '' }}">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Pesanan Cek
                        </a>
                    </li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-header" onclick="toggleMenu(this)">
                    <span class="menu-title">
                        <i class="bi bi-boxes me-2"></i>
                        Management Stok
                    </span>
                    <span class="arrow">
                        <i class="bi bi-chevron-down"></i>
                    </span>
                </div>

                <ul class="submenu {{ request()->routeIs('stok-produk.*') ? 'open' : '' }}">
                    <li>
                        <a href="{{ route('stok-produk.index') }}"
                            class="{{ Request::is('stok-produk') ? 'active' : '' }}">
                            <i class="bi bi-shop me-2"></i>
                            Stok Produk
                        </a>
                    </li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-header" onclick="toggleMenu(this)">
                    <span class="menu-title">
                        <i class="bi bi-database-fill-gear me-2"></i>
                        Data Master
                    </span>
                    <span class="arrow">
                        <i class="bi bi-chevron-down"></i>
                    </span>
                </div>

                <ul
                    class="submenu {{ request()->routeIs('toko.*') || request()->routeIs('sku.*') || request()->routeIs('iklan.*') || request()->routeIs('kategori.*') ? 'open' : '' }}">
                    <li>
                        <a href="{{ route('toko.index') }}" class="{{ Request::is('toko') ? 'active' : '' }}">
                            <i class="bi bi-shop me-2"></i>
                            Data Toko
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('sku.index') }}"
                            class="d-flex align-items-center justify-content-between {{ Request::is('sku') ? 'active' : '' }}">

                            <span>
                                <i class="bi bi-box-seam me-2"></i>
                                SKU Produk
                            </span>

                            <span class="badge rounded-pill bg-danger">
                                {{ $countProduk ?? 0 }}
                            </span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('kategori.index') }}"
                            class="d-flex align-items-center justify-content-between {{ Request::is('kategori*') ? 'active' : '' }}">

                            <span>
                                <i class="bi bi-tags me-2"></i>
                                Kategori
                            </span>

                            <span class="badge rounded-pill bg-danger">
                                {{ $countKategori ?? 0 }}
                            </span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('iklan.index') }}" class="{{ Request::is('iklan') ? 'active' : '' }}">
                            <i class="bi bi-megaphone me-2"></i>
                            Biaya Iklan
                        </a>
                    </li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-header" onclick="toggleMenu(this)">
                    <span class="menu-title">
                        <i class="bi bi-graph-up-arrow me-2"></i>
                        Detail Laporan
                    </span>
                    <span class="arrow">
                        <i class="bi bi-chevron-down"></i>
                    </span>
                </div>

                <ul
                    class="submenu {{ request()->routeIs('penjualan', 'penjualan.*') || request()->routeIs('pembeli', 'pembeli.*') || request()->routeIs('produk', 'produk.*') ? 'open' : '' }}">
                    <li>
                        <a href="{{ route('penjualan') }}" class="{{ Request::is('penjualan') ? 'active' : '' }}">
                            <i class="bi bi-bar-chart-line me-2"></i>
                            Penjualan
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('pembeli') }}" class="{{ Request::is('pembeli') ? 'active' : '' }}">
                            <i class="bi bi-person me-2"></i>
                            Pembeli
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('produk') }}" class="{{ Request::is('produk') ? 'active' : '' }}">
                            <i class="bi bi-star me-2"></i>
                            Produk Terlaris
                        </a>
                    </li>
                </ul>
            </div>

            @php
                $isManager = auth()->user()->role === 'manager';
                $targetUrl = $isManager ? route('users.index') : route('users.me');
                $isActive = $isManager ? request()->routeIs('users.*') : request()->routeIs('users.me');
            @endphp

            <div class="menu-section">
                <a href="{{ $targetUrl }}"
                    class="menu-header menu-profile d-flex align-items-center justify-content-between {{ $isActive ? 'active' : '' }}">

                    <span class="d-flex align-items-center text-truncate menu-title">
                        <i class="bi bi-person-circle me-2"></i>
                        <span class="text-truncate">
                            {{ $isManager ? 'Users' : 'Profil Saya' }}
                        </span>
                    </span>

                    <span class="role-badge">
                        {{ ucfirst(auth()->user()->role) }}
                    </span>
                </a>
            </div>

        </div>
    @endif


    @if (auth()->check() && auth()->user()->role === 'editor')
        <aside class="d-none d-lg-flex">
            <nav class="sidebar">

                <div>
                    <div class="sidebar-heading border-bottom">
                        # EDITOR
                    </div>

                    <a href="{{ route('editor.index') }}"
                        class="sidebar-link mt-1 {{ request()->routeIs('editor.index') ? 'active' : '' }}">

                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-gauge-high"></i>
                            <span>Dashboard</span>
                        </div>
                    </a>
                </div>

                <div>
                    <div class="sidebar-heading border-bottom mt-3">
                        # PEKERJAAN
                    </div>

                    <a href="{{ route('editor.download.plat') }}" class="sidebar-link mt-1">

                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-file-arrow-down"></i>
                            <span>Download Excel</span>
                        </div>
                    </a>
                </div>

                <div>
                    <div class="sidebar-heading border-bottom mt-3">
                        # PROSES
                    </div>

                    <div class="sidebar-link editor-info">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-pen-ruler"></i>
                            <span>Editor & VBA Corel</span>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="sidebar-heading border-bottom mt-3">
                        # AKUN
                    </div>

                    <div class="sidebar-link editor-info">
                        <div class="d-flex align-items-center gap-3 overflow-hidden">
                            <i class="fa-solid fa-user-pen"></i>
                            <span class="text-truncate">
                                {{ auth()->user()->name }}
                            </span>
                        </div>

                        <span class="badge rounded-pill bg-primary">
                            Editor
                        </span>
                    </div>
                </div>

            </nav>
        </aside>
    @endif


    @if (auth()->check() && auth()->user()->role === 'gudang')
        <aside class="d-none d-lg-flex">
            <nav class="sidebar">

                <div>
                    <div class="sidebar-heading border-bottom">
                        # DASHBOARD
                    </div>

                    <a href="{{ route('gudang.index') }}"
                        class="sidebar-link mt-1 {{ Request::is('gudang') ? 'active' : '' }}">

                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-gauge-high"></i>
                            <span>Dashboard</span>
                        </div>
                    </a>
                </div>

                <div>
                    <div class="sidebar-heading mt-3 border-bottom">
                        # TRANSAKSI
                    </div>

                    <a class="sidebar-link justify-content-between mt-1" data-bs-toggle="collapse"
                        href="#transaksiMenu">

                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-arrow-right-arrow-left"></i>
                            <span>Transaksi Stok</span>
                        </div>

                        <i class="fa-solid fa-chevron-down small"></i>
                    </a>

                    <div class="collapse {{ request()->routeIs('transaksi.*', 'gudang.sampel', 'gudang.retur', 'produk-custom.*') ? 'show' : '' }}"
                        id="transaksiMenu">

                        <a href="{{ route('transaksi.show', ['transaksi' => 'siapkan']) }}"
                            class="sidebar-sublink {{ request()->route('transaksi') === 'siapkan' ? 'active' : '' }}">

                            <i class="fa-solid fa-box"></i>
                            <small>Perlu Disiapkan</small>
                        </a>

                        <a href="{{ route('transaksi.show', ['transaksi' => 'siap']) }}"
                            class="sidebar-sublink {{ request()->route('transaksi') === 'siap' ? 'active' : '' }}">

                            <i class="fa-solid fa-box-open"></i>
                            <small>Siap Diambil</small>
                        </a>

                        <a href="{{ route('transaksi.show', ['transaksi' => 'diambil']) }}"
                            class="sidebar-sublink {{ request()->route('transaksi') === 'diambil' ? 'active' : '' }}">

                            <i class="fa-solid fa-circle-check"></i>
                            <small>Sudah Diambil</small>
                        </a>

                        <a href="{{ route('gudang.retur') }}"
                            class="sidebar-sublink {{ Route::is('gudang.retur') ? 'active' : '' }}">
                            <i class="fa-solid fa-rotate-left"></i>
                            <small>Barang Retur</small>
                        </a>

                        <a href="{{ route('gudang.sampel') }}"
                            class="sidebar-sublink {{ Route::is('gudang.sampel') ? 'active' : '' }}">
                            <i class="fa-solid fa-vial"></i>
                            <small>Barang Sampel</small>
                        </a>

                        <a href="{{ route('produk-custom.index') }}"
                            class="sidebar-sublink {{ Route::is('produk-custom.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-pen-ruler"></i>
                            <small>Produk Custom</small>
                        </a>
                    </div>
                </div>

                <div>
                    <div class="sidebar-heading border-bottom mt-3">
                        # MASTER DATA
                    </div>

                    <a href="{{ route('gudang.produk') }}"
                        class="sidebar-link mt-1 {{ Route::is('gudang.produk') ? 'active' : '' }}">

                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-box"></i>
                            <span>Produk</span>
                        </div>

                        <span class="badge rounded-pill bg-primary">
                            {{ $countProduk ?? 0 }}
                        </span>
                    </a>

                    <a href="{{ route('gudang.kategori') }}"
                        class="sidebar-link {{ Request::is('kategori-produk') ? 'active' : '' }}">

                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-tags"></i>
                            <span>Kategori</span>
                        </div>

                        <span class="badge rounded-pill bg-success">
                            {{ $countKategori ?? 0 }}
                        </span>
                    </a>
                </div>

                <div>
                    <div class="sidebar-heading border-bottom mt-3">
                        # MONITORING
                    </div>

                    <a href="{{ route('gudang.aktivitas') }}"
                        class="sidebar-link {{ Route::is('gudang.aktivitas') ? 'active' : '' }}">

                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <span>Riwayat Aktivitas</span>
                        </div>
                    </a>
                </div>

            </nav>
        </aside>
    @endif

</div>

<style>
    .sidebar-heading {
        padding-left: 0;
        margin-left: 4px;
    }

    .sidebar-link {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        border-radius: 12px;
        color: #334155;
        text-decoration: none;
        transition: .25s;
        margin-bottom: 4px;
    }

    .sidebar-link:hover {
        background: #2563eb;
        color: white;
    }

    .sidebar-link.active {
        background: #2563eb;
        color: white;
    }

    .sidebar-sublink {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 20px 10px 30px;
        color: #64748b;
        text-decoration: none;
        border-radius: 10px;
        margin: 3px 0;
        transition: .2s;
    }

    .sidebar-sublink:hover {
        background: #f8fafc;
        color: #2563eb;
    }

    .sidebar-sublink.active {
        background: #dbeafe;
        color: #2563eb;
        font-weight: 600;
    }

    .editor-info {
        cursor: default;
    }

    .editor-info:hover {
        background: #f8fafc;
        color: #334155;
    }
</style>
