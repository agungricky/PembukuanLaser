<div class="sidebar">
    @if (auth()->check() && auth()->user()->role === 'packing')
    @endif

    @if ((auth()->check() && auth()->user()->role === 'pegawai') || auth()->user()->role === 'pegawai')
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
                            <i class="bi bi-list-ul me-2"></i> Semua Pesanan
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('pesanan.proses') }}" class="{{ Request::is('proses') ? 'active' : '' }}">
                            <i class="bi bi-gear me-2"></i> Pesanan Diproses
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('pesanan.kirim') }}" class="{{ Request::is('kirim') ? 'active' : '' }}">
                            <i class="bi bi-truck me-2"></i> Pesanan Dikirim
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('pesanan.terima') }}" class="{{ Request::is('terima') ? 'active' : '' }}">
                            <i class="bi bi-check2-circle me-2"></i> Pesanan
                            Diterima
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
                            <i class="bi bi-exclamation-triangle me-2"></i> Pesanan Cek
                        </a>
                    </li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-header" onclick="toggleMenu(this)">
                    <span class="menu-title"> <i class="bi bi-boxes me-2"></i> Management Stok </span>
                    <span class="arrow">
                        <i class="bi bi-chevron-down"></i>
                    </span>
                </div>
                <ul class="submenu 
                {{ request()->routeIs('stok-produk.*') ? 'open' : '' }}">
                    <li>
                        <a href="{{ route('stok-produk.index') }}"
                            class="{{ Request::is('stok-produk') ? 'active' : '' }}">
                            <i class="bi bi-shop me-2"></i> Stok Produk
                        </a>
                    </li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-header" onclick="toggleMenu(this)">
                    <span class="menu-title"> <i class="bi bi-database-fill-gear me-2"></i> Data Master </span>
                    <span class="arrow">
                        <i class="bi bi-chevron-down"></i>
                    </span>
                </div>
                <ul
                    class="submenu 
                {{ request()->routeIs('toko.*') || request()->routeIs('sku.*') || request()->routeIs('iklan.*') ? 'open' : '' }}">

                    <li>
                        <a href="{{ route('toko.index') }}" class="{{ Request::is('toko') ? 'active' : '' }}">
                            <i class="bi bi-shop me-2"></i> Data Toko
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('sku.index') }}" class="{{ Request::is('sku') ? 'active' : '' }}">
                            <i class="bi bi-box-seam me-2"></i> SKU Produk
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('iklan.index') }}" class="{{ Request::is('iklan') ? 'active' : '' }}">
                            <i class="bi bi-megaphone me-2"></i> Biaya Iklan
                        </a>
                    </li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-header" onclick="toggleMenu(this)">
                    <span class="menu-title">
                        <i class="bi bi-graph-up-arrow me-2"></i> Detail Laporan
                    </span>
                    <span class="arrow">
                        <i class="bi bi-chevron-down"></i>
                    </span>
                </div>
                <ul
                    class="submenu {{ request()->routeIs('penjualan', 'penjualan.*') ||
                    request()->routeIs('pembeli', 'pembeli.*') ||
                    request()->routeIs('produk', 'produk.*')
                        ? 'open'
                        : '' }}">
                    <li>
                        <a href="{{ route('penjualan') }}" class="{{ Request::is('penjualan') ? 'active' : '' }}">
                            <i class="bi bi-bar-chart-line me-2"></i> Penjualan
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('pembeli') }}" class="{{ Request::is('pembeli') ? 'active' : '' }}">
                            <i class="bi bi-person me-2"></i> Pembeli
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('produk') }}" class="{{ Request::is('produk') ? 'active' : '' }}">
                            <i class="bi bi-star me-2"></i> Produk Terlaris
                        </a>
                    </li>
                </ul>
            </div>

            @auth
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
                            <span class="text-truncate">{{ $isManager ? 'Users' : 'Profil Saya' }}</span>
                        </span>
                        <span class="role-badge">{{ ucfirst(auth()->user()->role) }}</span>
                    </a>
                </div>
            @endauth
        </div>
    @endif

    @if ($dataLogin->role === 'gudang')
        <aside class="sidebar-gudang mt-3">

            <a href="{{ url('/gudang/dashboard') }}"
                class="sidebar-item {{ request()->is('gudang/dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard Gudang</span>
            </a>

            <a href="{{ url('/kategori-produk') }}"
                class="sidebar-item {{ request()->is('kategori-produk*') ? 'active' : '' }}">
                <i class="bi bi-tag"></i>
                <span>Kategori Produk</span>
            </a>

            <a href="{{ url('/produk') }}" class="sidebar-item {{ request()->is('produk*') ? 'active' : '' }}">
                <i class="bi bi-box"></i>
                <span>Produk</span>
            </a>

            <a href="{{ url('/pesanan') }}" class="sidebar-item {{ request()->is('pesanan*') ? 'active' : '' }}">
                <i class="bi bi-cart3"></i>
                <span>Daftar Pesanan</span>
            </a>

            <a href="{{ url('/stok') }}" class="sidebar-item {{ request()->is('stok') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i>
                <span>Stok</span>
            </a>

            <a href="{{ url('/pengajuan-stok') }}"
                class="sidebar-item {{ request()->is('pengajuan-stok*') ? 'active' : '' }}">
                <i class="bi bi-clipboard-data"></i>
                <span>Pengajuan Stok</span>
            </a>

            <a href="{{ url('/laporan-stok') }}"
                class="sidebar-item {{ request()->is('laporan-stok*') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line"></i>
                <span>Laporan Stok</span>
            </a>

        </aside>
    @endif
</div>

<style>
    .sidebar-gudang {
        width: 220px;
        min-height: 100vh;
        padding: 10px 9px;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        gap: 10px;
        box-sizing: border-box;
        font-family: Inter, Arial, sans-serif;
    }

    .sidebar-item {
        width: 100%;
        min-height: 46px;
        padding: 10px 13px;

        display: flex;
        align-items: center;
        gap: 11px;

        color: #111827;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;

        font-size: 15px;
        font-weight: 500;
        line-height: 1.2;
        text-decoration: none;

        box-sizing: border-box;
        transition:
            background-color 0.2s ease,
            border-color 0.2s ease,
            color 0.2s ease,
            transform 0.2s ease,
            box-shadow 0.2s ease;
    }

    .sidebar-item i {
        width: 17px;
        min-width: 17px;
        font-size: 17px;
        color: #111827;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .sidebar-item span {
        white-space: nowrap;
    }

    /* Efek ketika diarahkan mouse */
    .sidebar-item:hover {
        color: #1d4ed8;
        background: #f8faff;
        border-color: #bfdbfe;
        box-shadow: 0 3px 10px rgba(37, 99, 235, 0.08);
        transform: translateY(-1px);
    }

    .sidebar-item:hover i {
        color: #1d4ed8;
    }

    /* Menu aktif */
    .sidebar-item.active {
        color: #1d4ed8;
        background: #eff6ff;
        border-color: #93b9ff;
        font-weight: 600;
    }

    .sidebar-item.active i {
        color: #2563eb;
    }

    /* Tampilan layar kecil */
    @media (max-width: 768px) {
        .sidebar-gudang {
            width: 78px;
            padding: 10px 8px;
        }

        .sidebar-item {
            justify-content: center;
            padding: 10px;
        }

        .sidebar-item span {
            display: none;
        }

        .sidebar-item i {
            font-size: 19px;
        }
    }
</style>
