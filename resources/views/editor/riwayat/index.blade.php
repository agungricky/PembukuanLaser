@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h4 class="fw-bold mb-1">
            Riwayat Part
        </h4>

        <div class="text-muted small">
            Part Produksi yang sudah selesai diproses Editor
        </div>
    </div>

    @include('editor.partials.alert')

    <div class="card border-0 shadow-sm">

        <div class="table-responsive">

            <table class="table align-middle mb-0">

                <thead class="table-light">
                    <tr>
                        <th>Part</th>
                        <th>Tanggal Part</th>
                        <th>Item</th>
                        <th>Locked</th>
                        <th>Menunggu</th>
                        <th>Download</th>
                        <th>Upload</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($parts as $part)

                        <tr>

                            <td>
                                <div class="fw-bold">
                                    {{ $part->kode_part }}
                                </div>

                                <span class="badge bg-success">
                                    SELESAI
                                </span>
                            </td>

                            <td>
                                {{ $part->tanggal_part->format('d/m/Y') }}
                            </td>

                            <td>
                                {{ $part->jumlah_item }}
                            </td>

                            <td class="text-success fw-bold">
                                {{ $part->locked_count }}
                            </td>

                            <td class="text-danger fw-bold">
                                {{ $part->skipped_count }}
                            </td>

                            <td>
                                @if($part->downloaded_at)
                                    <div>
                                        {{ $part->downloaded_at->format('d/m/Y') }}
                                    </div>

                                    <div class="small text-muted">
                                        {{ $part->downloaded_at->format('H:i') }}
                                    </div>
                                @else
                                    -
                                @endif
                            </td>

                            <td>
                                @if($part->uploaded_at)
                                    <div>
                                        {{ $part->uploaded_at->format('d/m/Y') }}
                                    </div>

                                    <div class="small text-muted">
                                        {{ $part->uploaded_at->format('H:i') }}
                                    </div>
                                @else
                                    -
                                @endif
                            </td>

                            <td class="text-end">
                                <a href="{{ route('editor.part.show', $part) }}"
                                    class="btn btn-sm btn-outline-primary">
                                    Detail
                                </a>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="8"
                                class="text-center py-5 text-muted">
                                Belum ada riwayat Part.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    <div class="mt-3">
        {{ $parts->links() }}
    </div>

</div>

@endsection
