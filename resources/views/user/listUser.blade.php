@extends('layouts.app')

@section('content')

@push('styles')
<style>
  .profile-hero{
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
    border-radius: 18px;
    color:#fff;
    padding:24px;
    position: relative;
    overflow: hidden;
  }
  .profile-hero::after{
    content:""; position:absolute; right:-40px; top:-40px; width:180px; height:180px;
    background: radial-gradient(ellipse at center, rgba(255,255,255,.18), transparent 55%);
    border-radius:50%;
  }
  .avatar{
    width:72px; height:72px; border-radius:16px; background:rgba(255,255,255,.2);
    display:grid; place-items:center; font-weight:800; font-size:28px; letter-spacing:.5px;
    backdrop-filter: blur(2px);
  }
  .badge-role{ letter-spacing:.4px; }
  .card-soft{ border:1px solid #edf1f5; border-radius:16px; box-shadow:0 4px 18px rgba(2,8,20,.04); }
  .kv-row{ display:flex; justify-content:space-between; align-items:center; gap:12px; padding:12px 0; border-bottom:1px dashed #eef2f6; }
  .kv-row:last-child{ border-bottom:0; }
  .kv-label{ color:#6b7280; font-weight:600; white-space:nowrap; }
  .kv-value{ font-weight:700; color:#111827; text-align:right; }
</style>
@endpush

<div class="container-fluid py-3">

  {{-- Header / Hero --}}
  <div class="profile-hero mb-4">
    <div class="d-flex align-items-center gap-3">
      <div class="avatar">
        {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
      </div>
      <div class="flex-grow-1">
        <div class="d-flex flex-wrap align-items-center gap-2">
          <h1 class="h4 mb-0">{{ $user->name }}</h1>
          <span class="badge {{ $user->role === 'admin' ? 'text-bg-light' : 'text-bg-dark' }} badge-role text-uppercase">
            {{ $user->role }}
          </span>
        </div>
        <div class="small opacity-75 mt-1">
          ID #{{ $user->id }} •
          Dibuat: {{ \Carbon\Carbon::parse($user->created_at)->format('d M Y, H:i') }}
        </div>
      </div>
      <div class="text-end">
        @if(!$user->deleted_at)
          <span class="badge text-bg-success"><i class="bi bi-circle-fill me-1"></i>Aktif</span>
        @else
          <span class="badge text-bg-danger"><i class="bi bi-slash-circle me-1"></i>Nonaktif</span>
        @endif
      </div>
    </div>
  </div>

  {{-- Body --}}
  <div class="row g-4">
    {{-- Kolom kiri: Detail Akun --}}
    <div class="col-12 col-xl-7">
      <div class="card card-soft h-100">
        <div class="card-header bg-white fw-semibold">Detail Akun</div>
        <div class="card-body">
          <div class="kv-row">
            <div class="kv-label">Nama</div>
            <div class="kv-value">{{ $user->name }}</div>
          </div>
          <div class="kv-row">
            <div class="kv-label">Email</div>
            <div class="kv-value">
              {{ $user->email }}
              {{-- TAMPILKAN badge hanya jika VERIFIED --}}
              @if($user->email_verified_at)
                <span class="badge text-bg-success ms-2">
                  <i class="bi bi-patch-check"></i> Verified
                </span>
              @endif
            </div>
          </div>
          <div class="kv-row">
            <div class="kv-label">Role</div>
            <div class="kv-value text-uppercase">{{ $user->role }}</div>
          </div>
          <div class="kv-row">
            <div class="kv-label">Terakhir Diperbarui</div>
            <div class="kv-value">{{ \Carbon\Carbon::parse($user->updated_at)->diffForHumans() }}</div>
          </div>
        </div>
      </div>
    </div>

    {{-- Kolom kanan: Status & Keamanan --}}
    <div class="col-12 col-xl-5">
      <div class="card card-soft h-100">
        <div class="card-header bg-white fw-semibold">Status & Keamanan</div>
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="text-muted">Status Akun</div>
            <div>
              @if(!$user->deleted_at)
                <span class="badge text-bg-success"><i class="bi bi-check2-circle me-1"></i>Aktif</span>
              @else
                <span class="badge text-bg-danger"><i class="bi bi-x-circle me-1"></i>Nonaktif</span>
              @endif
            </div>
          </div>

          {{-- HANYA tampilkan baris verifikasi bila verified; jika tidak, sembunyikan total --}}
          @if($user->email_verified_at)
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="text-muted">Verifikasi Email</div>
              <div>
                <span class="badge text-bg-success">
                  <i class="bi bi-patch-check me-1"></i>
                  {{ \Carbon\Carbon::parse($user->email_verified_at)->format('d M Y') }}
                </span>
              </div>
            </div>
          @endif

          <div class="d-flex align-items-center justify-content-between">
            <div class="text-muted">Remember Token</div>
            <div class="fw-semibold">{{ $user->remember_token ? 'Ya' : '—' }}</div>
          </div>

          <hr>
          <div class="small text-muted">
            Halaman ini bersifat <strong>read-only</strong>. Hubungi Manager untuk perubahan data atau pemulihan akun.
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection