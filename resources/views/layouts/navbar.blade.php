{{-- ==== TOPBAR (Compact, dropdown pakai DIV) ==== --}}
<style>
  .navbar-compact {
    min-height: 48px;
    padding-top: 4px;
    padding-bottom: 4px;
  }

  .navbar-compact .navbar-brand {
    font-weight: 600;
    font-size: 1rem;
    letter-spacing: .2px;
  }

  .avatar-initial {
    width: 28px;
    height: 28px;
    font-size: .85rem;
  }

  /* pastikan dropdown overlay (tidak menambah tinggi navbar) */
  .navbar.sticky-top {
    z-index: 1050;
  }

  .navbar .dropdown-menu {
    margin-top: .5rem;
    z-index: 1080;
  }
</style>

<nav class="navbar bg-white border-bottom shadow-sm sticky-top navbar-compact">
  <div class="container-fluid px-2 px-sm-3">
    {{-- Brand --}}
    @if (auth()->check() && auth()->user()->role === 'packing' || $dataLogin->role == 'gudang')
      <span class="navbar-brand mb-0 text-primary" title="Packing fokus scan pesanan">
        LASER CUSTOM KEDIRI
      </span>
    @else
      <a href="{{ route('dashboard') }}" class="navbar-brand mb-0 text-primary">
        LASER CUSTOM KEDIRI
      </a>
    @endif

    @auth
      <div class="dropdown ms-auto">
        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 py-0" href="#" id="profileDropdown"
          role="button" data-bs-toggle="dropdown" data-bs-offset="0,8" {{-- beri jarak 8px dari toggle --}}
          aria-expanded="false">
          <span
            class="avatar-initial rounded-circle bg-primary text-white d-inline-flex justify-content-center align-items-center">
            {{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
          </span>
          <span class="fw-semibold d-none d-sm-inline small">
            {{ \Illuminate\Support\Str::limit(auth()->user()->name, 22) }}
          </span>
        </a>

        {{-- DROPDOWN: div-based --}}
        <div class="dropdown-menu dropdown-menu-end shadow-sm py-2" aria-labelledby="profileDropdown"
          style="min-width:220px;">
          <div class="px-3 pb-2 small">
            <div class="text-muted">Masuk sebagai</div>
            <div class="fw-semibold">{{ auth()->user()->name }}</div>
            <span class="badge bg-light text-secondary text-capitalize">{{ auth()->user()->role }}</span>
          </div>

          <div class="dropdown-divider my-1"></div>

          {{-- Menu profil/users --}}
          @if(auth()->user()->role === 'manager')
            <a class="dropdown-item d-flex align-items-center gap-2 small py-2" href="{{ route('users.index') }}">
              <i class="bi bi-people"></i> <span>Users</span>
            </a>
          @elseif(auth()->user()->role !== 'packing')
            <a class="dropdown-item d-flex align-items-center gap-2 small py-2" href="{{ route('users.me') }}">
              <i class="bi bi-person-circle"></i> <span>Profil Saya</span>
            </a>
          @endif

          {{-- Dashboard: hilang untuk packing --}}
          @if(auth()->user()->role !== 'packing')
            <a class="dropdown-item d-flex align-items-center gap-2 small py-2" href="{{ route('dashboard') }}">
              <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
            </a>
          @endif

          <div class="dropdown-divider my-1"></div>

          {{-- Logout semua role --}}
          <form action="{{ route('logout') }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="dropdown-item d-flex align-items-center gap-2 small py-2 text-danger">
              <i class="bi bi-box-arrow-right"></i> <span>Logout</span>
            </button>
          </form>
        </div>
      </div>
    @endauth
  </div>
</nav>