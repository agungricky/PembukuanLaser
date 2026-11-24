@extends('layouts.app')

@section('content')
    <div class="bg-white p-3 p-md-4 rounded shadow-sm w-100">

        {{-- Header --}}
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary fw-semibold px-2 py-1">👤</span>
                <h5 class="mb-0">Data User</h5>
                <span class="text-muted small">• {{ number_format($users->count(), 0, ',', '.') }} pengguna</span>
            </div>

            <button class="btn btn-success btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal"
                data-bs-target="#tambahDataModal">
                <i class="bi bi-plus-circle"></i><span>Tambah User</span>
            </button>
        </div>

        {{-- Flash --}}
        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center py-2" id="alertSuccess">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        {{-- Tabel --}}
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle" id="userTable" style="width:100%">
                <thead class="table-light">
                    <tr class="text-center">
                        <th style="width:64px">No</th>
                        <th style="min-width:220px" class="text-start">Nama</th>
                        <th style="min-width:220px" class="text-start">Email</th>
                        <th style="min-width:120px">Role</th>
                        <th style="min-width:160px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $index => $user)
                        @php
                            switch ($user->role) {
                                case 'pegawai':
                                    $roleClass = 'danger';
                                    break;
                                case 'manager':
                                    $roleClass = 'info';
                                    break;
                                case 'packing':
                                    $roleClass = 'warning';
                                    break;
                                default:
                                    $roleClass = 'secondary';
                            }
                            $initials = strtoupper(mb_substr($user->name, 0, 1));
                        @endphp
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td class="text-start">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-circle">{{ $initials }}</div>
                                    <div class="fw-semibold">{{ $user->name }}</div>
                                </div>
                            </td>
                            <td class="text-start">
                                <a href="mailto:{{ $user->email }}" class="text-decoration-none">{{ $user->email }}</a>
                            </td>
                            <td class="text-center">
                                <span
                                    class="badge rounded-pill bg-{{ $roleClass }} text-white text-capitalize">{{ $user->role }}</span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#editDataModal" data-id="{{ $user->id }}"
                                        data-name="{{ $user->name }}" data-email="{{ $user->email }}"
                                        data-role="{{ $user->role }}">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Yakin hapus user {{ $user->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Modal Tambah ===== --}}
    <div class="modal fade" id="tambahDataModal" tabindex="-1" aria-labelledby="tambahDataLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="tambahDataLabel">Tambah User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('users.store') }}" method="POST">
                    @csrf

                    <div class="modal-body px-3 py-3">

                        {{-- Nama dan Email --}}
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold mb-1">
                                    Nama Lengkap
                                </label>

                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <i class="bi bi-person text-muted"></i>
                                    </span>

                                    <input type="text" name="name" class="form-control" placeholder="Nama lengkap"
                                        required>
                                </div>
                            </div>

                            <div class="col-6">
                                <label class="form-label fw-semibold mb-1">
                                    Email
                                </label>

                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <i class="bi bi-envelope text-muted"></i>
                                    </span>

                                    <input type="email" name="email" class="form-control" placeholder="nama@email.com"
                                        required>
                                </div>
                            </div>
                        </div>

                        {{-- Hak Akses --}}
                        <div class="bg-light border rounded-3 p-3 mb-3">

                            <div class="fw-semibold text-secondary small mb-2">
                                <i class="bi bi-shield-check me-1"></i>
                                Hak Akses
                            </div>

                            <div class="row g-2">

                                {{-- Role --}}
                                <div class="col-4">
                                    <label class="form-label small fw-semibold mb-1">
                                        Role
                                    </label>

                                    <select name="role" class="form-select" required>
                                        <option value="pegawai">Pegawai</option>
                                        <option value="manager">Manager</option>
                                        <option value="packing">Packing</option>
                                        <option value="gudang">Gudang</option>
                                        <option value="editor">Editor</option>
                                        <option value="cs">Customer Service</option>
                                    </select>
                                </div>

                                {{-- Leader --}}
                                <div class="col-3">
                                    <label class="form-label small fw-semibold mb-1">
                                        Leader
                                    </label>

                                    <select name="leader" class="form-select" required>
                                        <option value="0" selected>Tidak</option>
                                        <option value="1">Ya</option>
                                    </select>
                                </div>

                                {{-- Platform --}}
                                <div class="col-5">
                                    <label class="form-label small fw-semibold mb-1">
                                        Platform
                                    </label>

                                    <select name="platform" class="form-select" required>
                                        <option value="marketplace">Marketplace</option>
                                        <option value="meta">Meta</option>
                                    </select>
                                </div>

                            </div>
                        </div>

                        {{-- Password --}}
                        <div>
                            <label class="form-label fw-semibold mb-1">
                                Password
                            </label>

                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="bi bi-lock text-muted"></i>
                                </span>

                                <input type="password" name="password" id="password" class="form-control"
                                    placeholder="Masukkan password minimal 6 karakter" minlength="6" required>

                                <button class="input-group-text bg-white" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                    </div>

                    {{-- Footer --}}
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">
                            Batal
                        </button>

                        <button type="submit" class="btn btn-success px-3">
                            <i class="bi bi-check-lg me-1"></i>
                            Simpan Pengguna
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ===== Modal Edit ===== --}}
    <div class="modal fade" id="editDataModal" tabindex="-1" aria-labelledby="editDataLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editDataLabel">Edit User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="editForm" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="modal-body px-3 py-3">
                        <input type="hidden" name="id" id="editId">

                        {{-- Nama dan Email --}}
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold mb-1">
                                    Nama Lengkap
                                </label>

                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <i class="bi bi-person text-muted"></i>
                                    </span>

                                    <input type="text" name="name" id="editName" class="form-control"
                                        placeholder="Nama lengkap" required>
                                </div>
                            </div>

                            <div class="col-6">
                                <label class="form-label fw-semibold mb-1">
                                    Email
                                </label>

                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <i class="bi bi-envelope text-muted"></i>
                                    </span>

                                    <input type="email" name="email" id="editEmail" class="form-control"
                                        placeholder="nama@email.com" required>
                                </div>
                            </div>
                        </div>

                        {{-- Hak Akses --}}
                        <div class="bg-light border rounded-3 p-3 mb-3">

                            <div class="fw-semibold text-secondary small mb-2">
                                <i class="bi bi-shield-check me-1"></i>
                                Hak Akses
                            </div>

                            <div class="row g-2">

                                {{-- Role --}}
                                <div class="col-4">
                                    <label class="form-label small fw-semibold mb-1">
                                        Role
                                    </label>

                                    <select name="role" id="editRole" class="form-select" required>
                                        <option value="pegawai">Pegawai</option>
                                        <option value="manager">Manager</option>
                                        <option value="packing">Packing</option>
                                        <option value="gudang">Gudang</option>
                                        <option value="editor">Editor</option>
                                        <option value="cs">Customer Service</option>
                                    </select>
                                </div>

                                {{-- Leader --}}
                                <div class="col-3">
                                    <label class="form-label small fw-semibold mb-1">
                                        Leader
                                    </label>

                                    <select name="leader" id="editLeader" class="form-select" required>
                                        <option value="0">Tidak</option>
                                        <option value="1">Ya</option>
                                    </select>
                                </div>

                                {{-- Platform --}}
                                <div class="col-5">
                                    <label class="form-label small fw-semibold mb-1">
                                        Platform
                                    </label>

                                    <select name="platform" id="editPlatform" class="form-select" required>
                                        <option value="marketplace">Marketplace</option>
                                        <option value="meta">Meta</option>
                                    </select>
                                </div>

                            </div>
                        </div>

                        {{-- Password --}}
                        <div>
                            <label class="form-label fw-semibold mb-1">
                                Password
                                <span class="text-muted fw-normal">(Opsional)</span>
                            </label>

                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="bi bi-lock text-muted"></i>
                                </span>

                                <input type="password" name="password" id="editPassword" class="form-control"
                                    placeholder="Kosongkan jika tidak diubah" minlength="6">

                                <button class="input-group-text bg-white" type="button" id="toggleEditPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">
                            Batal
                        </button>

                        <button type="submit" class="btn btn-primary px-3">
                            <i class="bi bi-check-lg me-1"></i>
                            Perbarui Pengguna
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
    <style>
        #userTable thead th {
            font-size: .85rem;
            letter-spacing: .2px;
        }

        #userTable tbody td {
            vertical-align: middle;
        }

        .avatar-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eef2ff;
            color: #3b82f6;
            font-weight: 700;
            font-size: .9rem;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        // Datatable
        $(function() {
            $('#userTable').DataTable({
                paging: true,
                pageLength: 10,
                lengthChange: false,
                searching: true,
                ordering: true,
                order: [
                    [1, 'asc']
                ], // urut nama
                columnDefs: [{
                    orderable: false,
                    targets: [4]
                }],
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
                }
            });
            setTimeout(() => document.getElementById('alertSuccess')?.remove(), 3000);
        });

        // Populate edit modal
        const editModal = document.getElementById('editDataModal');
        console.log(editModal);
        editModal.addEventListener('show.bs.modal', function(event) {
            const btn = event.relatedTarget;
            const id = btn.getAttribute('data-id');
            const name = btn.getAttribute('data-name');
            const email = btn.getAttribute('data-email');
            const role = btn.getAttribute('data-role');
            const leader = btn.getAttribute('data-leader');
            const platform = btn.getAttribute('data-platform');

            document.getElementById('editForm').action = `/users/${id}`;
            document.getElementById('editId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editEmail').value = email;
            document.getElementById('editRole').value = role;
            document.getElementById('editLeader').value = leader ?? '0';
            document.getElementById('editPlatform').value = platform ?? 'marketplace';
            document.getElementById('editPassword').value = '';
        });

        // Toggle password
        function bindToggle(btnId, inputId) {
            const btn = document.getElementById(btnId);
            const inp = document.getElementById(inputId);
            btn.addEventListener('click', () => {
                const type = inp.type === 'password' ? 'text' : 'password';
                inp.type = type;
                btn.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' :
                    '<i class="bi bi-eye-slash"></i>';
            });
        }
        bindToggle('togglePassword', 'password');
        bindToggle('toggleEditPassword', 'editPassword');
    </script>
@endpush
