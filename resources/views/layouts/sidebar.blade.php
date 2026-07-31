<div class="sidebar">
    @if (auth()->check() && auth()->user()->role === 'packing')
    @else
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
                    <span class="menu-title"> <i class="bi bi-database-fill-gear me-2"></i> Data Master </span>
                    <span class="arrow">
                        <i class="bi bi-chevron-down"></i>
                    </span>
                </div>
                <ul class="submenu 
                {{  
                    request()->routeIs('toko.*') ||
                    request()->routeIs('sku.*') ||
                    request()->routeIs('iklan.*') ? 'open' : '' 
                }}">
                
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
                <ul class="submenu {{  
                    request()->routeIs('penjualan', 'penjualan.*') ||
                    request()->routeIs('pembeli', 'pembeli.*') ||
                    request()->routeIs('produk', 'produk.*') ? 'open' : '' 
                }}">
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
</div>
