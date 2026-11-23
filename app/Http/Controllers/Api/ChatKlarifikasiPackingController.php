<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatKlarifikasiPesanDibaca;
use App\Events\ChatKlarifikasiInboxBerubah;
use App\Events\ChatKlarifikasiPesanDikirim;
use App\Http\Controllers\Controller;
use App\Models\ChatKlarifikasiPacking;
use App\Models\PesanChatKlarifikasiPacking;
use App\Models\Pesanan;
use App\Models\PesananPerProduk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class ChatKlarifikasiPackingController extends Controller
{
    /** Kotak masuk untuk peserta CS Meta dan Packing Meta. */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $this->isParticipantRole($user)) return $this->forbidden();

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['menunggu_jawaban', 'dibalas', 'selesai'])],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'belum_dibaca_saja' => ['nullable', 'boolean'],
        ]);

        $scope = function ($q) use ($user) {
            if ($user->role === 'cs') {
                $q->where('cs_user_id', $user->id);
            } else {
                // Packing Meta melihat chat miliknya serta antrean yang belum ditangani.
                $q->where(function ($inner) use ($user) {
                    $inner->where('packing_user_id', $user->id)
                        ->orWhereNull('packing_user_id');
                });
            }
            $q->whereHas('pesanan', fn ($order) => $order->where('platform', 'meta'));
        };

        $query = ChatKlarifikasiPacking::query()
            ->with(['packing:id,name', 'cs:id,name'])
            ->withCount(['pesan as jumlah_belum_dibaca' => function ($q) use ($user) {
                $q->where('pengirim_user_id', '<>', $user->id)->whereNull('dibaca_at');
            }]);
        $scope($query);

        $jumlahBelumDibacaTotal = PesanChatKlarifikasiPacking::query()
            ->where('pengirim_user_id', '<>', $user->id)
            ->whereNull('dibaca_at')
            ->whereHas('chat', $scope)
            ->count();

        if ($request->boolean('belum_dibaca_saja')) {
            $query->whereHas('pesan', fn ($q) => $q
                ->where('pengirim_user_id', '<>', $user->id)->whereNull('dibaca_at'));
        }
        if (! empty($validated['status'])) $query->where('status', $validated['status']);
        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('no_pesanan', 'like', "%{$search}%")
                    ->orWhere('topik', 'like', "%{$search}%");
            });
        }

        $result = $query->orderByRaw('COALESCE(pesan_terakhir_at, created_at) DESC')
            ->orderByDesc('id')->paginate((int) ($validated['per_page'] ?? 20));

        return response()->json([
            'success' => true, 'data' => $result,
            'jumlah_belum_dibaca_total' => (int) $jumlahBelumDibacaTotal,
        ]);
    }

    /** Cari percakapan secara tepat dari nomor pesanan (tanpa membuatnya). */
    public function findByOrder(Request $request, string $noPesanan): JsonResponse
    {
        $user = $request->user();
        if (! $this->isParticipantRole($user)) return $this->forbidden();

        $order = Pesanan::query()->where('no_pesanan', $noPesanan)
            ->where('platform', 'meta')->first();
        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Pesanan Meta tidak ditemukan.'], 404);
        }
        if ($user->role === 'cs' && (int) $order->id_user !== (int) $user->id) {
            return $this->forbidden('Pesanan bukan milik CS yang login.');
        }

        $chat = ChatKlarifikasiPacking::query()->where('no_pesanan', $noPesanan)
            ->with(['packing:id,name', 'cs:id,name'])->first();
        if ($chat && ! $this->canAccess($request, $chat)) return $this->forbidden();

        // HTTP 200 juga saat belum ada supaya Flutter dapat membuka form dengan aman.
        return response()->json(['success' => true, 'exists' => $chat !== null, 'data' => $chat]);
    }

    /** CS atau Packing Meta membuat satu percakapan per nomor pesanan. */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $this->isParticipantRole($user)) return $this->forbidden();

        $validated = $request->validate([
            'no_pesanan' => ['required', 'string', 'max:50'],
            'id_per_produk' => ['nullable', 'integer', 'min:1'],
            'topik' => ['sometimes', 'nullable', 'string', 'max:255'],
            'pesan' => ['required', 'string', 'min:1', 'max:10000'],
            'client_request_id' => ['sometimes', 'nullable', 'uuid'],
        ]);
        $noPesanan = trim($validated['no_pesanan']);
        $topik = trim($validated['topik'] ?? '') ?: 'Klarifikasi Pesanan';
        $isiPesan = trim($validated['pesan']);
        $idPerProduk = isset($validated['id_per_produk']) ? (int) $validated['id_per_produk'] : null;
        $uuid = $validated['client_request_id'] ?? null;
        if ($noPesanan === '' || $isiPesan === '') {
            return response()->json(['success' => false, 'message' => 'Nomor pesanan dan pesan wajib diisi.'], 422);
        }

        try {
            $result = DB::transaction(function () use ($user, $noPesanan, $topik, $isiPesan, $idPerProduk, $uuid) {
                // Lock baris pesanan: request simultan untuk nomor yang sama diserialkan.
                $order = Pesanan::query()->where('no_pesanan', $noPesanan)
                    ->lockForUpdate()->first();
                if (! $order || $order->platform !== 'meta') return ['state' => 'order_not_found'];
                if (! $order->id_user) return ['state' => 'owner_missing'];
                $cs = \App\Models\User::query()->find($order->id_user);
                if (! $cs || $cs->role !== 'cs' || $cs->platform !== 'meta') {
                    return ['state' => 'owner_invalid'];
                }
                if ($user->role === 'cs' && (int) $order->id_user !== (int) $user->id) {
                    return ['state' => 'forbidden'];
                }

                // Jika chat sudah ada, cukup buka kembali. Pesan pertama tidak dikirim ulang.
                $existing = ChatKlarifikasiPacking::query()
                    ->where('no_pesanan', $noPesanan)->first();
                if ($existing) {
                    if ($user->role === 'packing' && $existing->packing_user_id !== null
                        && (int) $existing->packing_user_id !== (int) $user->id) {
                        return ['state' => 'forbidden'];
                    }
                    return ['state' => 'existing', 'chat' => $existing];
                }

                if ($idPerProduk !== null && ! PesananPerProduk::query()
                    ->where('id_per_produk', $idPerProduk)
                    ->where('no_pesanan', $noPesanan)->exists()) {
                    return ['state' => 'product_invalid'];
                }

                // Pertahankan kontrak UUID idempoten untuk Packing versi lama.
                if ($uuid !== null && $user->role === 'packing'
                    && ChatKlarifikasiPacking::query()
                        ->where('packing_user_id', $user->id)
                        ->where('client_request_id', $uuid)->exists()) {
                    return ['state' => 'request_conflict'];
                }

                $chat = new ChatKlarifikasiPacking();
                $chat->no_pesanan = $noPesanan;
                $chat->id_per_produk = $idPerProduk;
                $chat->packing_user_id = $user->role === 'packing' ? $user->id : null;
                $chat->cs_user_id = $cs->id;
                $chat->client_request_id = $uuid;
                $chat->topik = $topik;
                $chat->status = $user->role === 'cs' ? 'dibalas' : 'menunggu_jawaban';
                $chat->save();

                $message = $chat->pesan()->create([
                    'pengirim_user_id' => $user->id, 'pesan' => $isiPesan,
                ]);
                $chat->update(['pesan_terakhir_at' => $message->created_at]);
                return ['state' => 'created', 'chat' => $chat, 'message' => $message];
            });
        } catch (Throwable $e) {
            Log::error('Gagal membuat chat klarifikasi Meta', ['exception' => $e]);
            return response()->json([
                'success' => false, 'message' => 'Gagal membuat percakapan.',
            ], 500);
        }

        $rejected = [
            'order_not_found' => [404, 'Pesanan Meta tidak ditemukan.'],
            'owner_missing' => [422, 'Pesanan belum memiliki CS pemilik.'],
            'owner_invalid' => [422, 'CS pemilik bukan akun CS Meta.'],
            'product_invalid' => [422, 'Produk tidak termasuk pesanan.'],
            'forbidden' => [403, 'Tidak mempunyai akses ke pesanan atau chat ini.'],
            'request_conflict' => [409, 'client_request_id sudah digunakan untuk pesanan lain.'],
        ];
        if (isset($rejected[$result['state']])) {
            [$status, $message] = $rejected[$result['state']];
            return response()->json(['success' => false, 'message' => $message], $status);
        }

        if ($result['state'] === 'created') {
            $this->broadcastSafely($result['message']);
            $this->broadcastInboxSafely($result['chat']);
        }
        $chat = $result['chat']->fresh(['packing:id,name', 'cs:id,name']);
        return response()->json([
            'success' => true,
            'message' => $result['state'] === 'existing'
                ? 'Percakapan sudah tersedia.' : 'Chat klarifikasi berhasil dibuat.',
            'data' => $chat,
            'pesan_pertama' => $result['message'] ?? null,
        ], $result['state'] === 'created' ? 201 : 200);
    }

    /**
     * Detail chat beserta maksimal 50 pesan terakhir.
     * Query before_id dapat digunakan untuk mengambil pesan yang lebih lama.
     */
    public function show(Request $request, int $chatId): JsonResponse
    {
        $chat = ChatKlarifikasiPacking::with(['packing:id,name', 'cs:id,name'])->find($chatId);

        if (! $chat) {
            return response()->json(['success' => false, 'message' => 'Chat tidak ditemukan.'], 404);
        }

        if (! $this->canAccess($request, $chat)) {
            return $this->forbidden();
        }

        $validated = $request->validate([
            'before_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $messagesQuery = $chat->pesan()->with('pengirim:id,name');

        if (isset($validated['before_id'])) {
            $messagesQuery->where('id', '<', (int) $validated['before_id']);
        }

        $items = $messagesQuery->reorder('id', 'desc')->limit(51)->get();
        $hasMore = $items->count() > 50;
        $messages = $items->take(50)->reverse()->values();

        return response()->json([
            'success' => true,
            'data' => [
                'chat' => $chat,
                'pesan' => $messages,
                'has_more' => $hasMore,
                'before_id_berikutnya' => $hasMore ? $messages->first()?->id : null,
            ],
        ]);
    }

    /**
     * CS atau Packing yang menjadi peserta dapat mengirim pesan.
     */
    public function send(Request $request, int $chatId): JsonResponse
    {
        $chat = ChatKlarifikasiPacking::find($chatId);

        if (! $chat) {
            return response()->json(['success' => false, 'message' => 'Chat tidak ditemukan.'], 404);
        }

        if (! $this->canAccess($request, $chat)) {
            return $this->forbidden();
        }

        $validated = $request->validate([
            'pesan' => ['required', 'string', 'min:1', 'max:10000'],
            // Opsional supaya Flutter versi lama tetap bisa mengirim pesan.
            'client_message_id' => ['sometimes', 'nullable', 'uuid'],
        ]);

        $isiPesan = trim($validated['pesan']);
        if ($isiPesan === '') {
            return response()->json([
                'success' => false,
                'message' => 'Pesan tidak boleh kosong.',
            ], 422);
        }

        $userId = (int) $request->user()->id;
        $uuid = $validated['client_message_id'] ?? null;

        try {
            // Lock satu baris chat menyerialkan request kirim dan tutup chat.
            // Unique index di DB menjadi lapisan keamanan tambahan.
            $result = DB::transaction(function () use ($chatId, $userId, $isiPesan, $uuid, $request) {
                $lockedChat = ChatKlarifikasiPacking::query()
                    ->whereKey($chatId)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Retry ID yang sama: kembalikan pesan sebelumnya, bukan buat pesan baru.
                // Dicek SEBELUM status selesai agar retry tetap aman jika chat sudah ditutup.
                if ($uuid !== null) {
                    $existing = PesanChatKlarifikasiPacking::query()
                        ->where('chat_id', $chatId)
                        ->where('pengirim_user_id', $userId)
                        ->where('client_message_id', $uuid)
                        ->first();

                    if ($existing) {
                        if ($existing->pesan !== $isiPesan) {
                            return ['state' => 'conflict'];
                        }

                        return ['state' => 'duplicate', 'message' => $existing];
                    }
                }

                if ($lockedChat->status === 'selesai') {
                    return ['state' => 'closed'];
                }
                if ($request->user()->role === 'packing') {
                    if ($lockedChat->packing_user_id !== null
                        && (int) $lockedChat->packing_user_id !== $userId) {
                        return ['state' => 'forbidden'];
                    }
                    if ($lockedChat->packing_user_id === null) {
                        $lockedChat->packing_user_id = $userId;
                        $lockedChat->save();
                    }
                }

                // Assignment langsung tidak membutuhkan perubahan $fillable model.
                $message = $lockedChat->pesan()->make([
                    'pengirim_user_id' => $userId,
                    'pesan' => $isiPesan,
                ]);
                $message->client_message_id = $uuid;
                $message->save();

                $lockedChat->update([
                    'status' => $request->user()->role === 'cs' ? 'dibalas' : 'menunggu_jawaban',
                    'pesan_terakhir_at' => $message->created_at,
                ]);

                return ['state' => 'created', 'message' => $message];
            });
        } catch (Throwable $e) {
            Log::error('Gagal mengirim pesan klarifikasi', [
                'chat_id' => $chatId,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan pesan.',
            ], 500);
        }

        if ($result['state'] === 'conflict') {
            return response()->json([
                'success' => false,
                'message' => 'client_message_id sudah digunakan untuk pesan berbeda.',
            ], 409);
        }

        if ($result['state'] === 'forbidden') {
            return $this->forbidden('Chat sudah ditangani Packing lain.');
        }
        if ($result['state'] === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'Chat sudah selesai dan tidak menerima pesan baru.',
            ], 409);
        }

        $message = $result['message'];

        // Pesan lama tidak boleh di-broadcast ulang saat request retry.
        if ($result['state'] === 'created') {
            $this->broadcastSafely($message);
            $this->broadcastInboxSafely($message->chat);
        }

        // Struktur respons lama dipertahankan untuk kompatibilitas Flutter.
        return response()->json([
            'success' => true,
            'message' => 'Pesan terkirim.',
            'data' => $message->load('pengirim:id,name'),
        ], 201);
    }

    /**
     * Tandai pesan dari lawan bicara yang sudah tampil sebagai dibaca.
     * last_message_id wajib agar pesan lebih baru tidak ikut ditandai.
     */
    public function markRead(Request $request, int $chatId): JsonResponse
    {
        $chat = ChatKlarifikasiPacking::find($chatId);

        if (! $chat) {
            return response()->json([
                'success' => false,
                'message' => 'Chat tidak ditemukan.',
            ], 404);
        }

        if (! $this->canAccess($request, $chat)) {
            return $this->forbidden();
        }

        $validated = $request->validate([
            'last_message_id' => ['required', 'integer', 'min:1'],
        ]);

        $userId = (int) $request->user()->id;
        $lastMessageId = (int) $validated['last_message_id'];

        // Cegah ID dari percakapan lain / ID yang tidak ada.
        $exists = $chat->pesan()
            ->whereKey($lastMessageId)
            ->exists();

        if (! $exists) {
            return response()->json([
                'success' => false,
                'message' => 'ID pesan terakhir tidak ditemukan pada chat ini.',
            ], 422);
        }

        try {
            $jumlahDitandai = DB::transaction(function () use ($chat, $userId, $lastMessageId, $request) {
                $locked = ChatKlarifikasiPacking::whereKey($chat->id)->lockForUpdate()->firstOrFail();
                if ($request->user()->role === 'packing') {
                    if ($locked->packing_user_id !== null && (int) $locked->packing_user_id !== $userId) {
                        return null;
                    }
                    if ($locked->packing_user_id === null) {
                        $locked->packing_user_id = $userId;
                        $locked->save();
                    }
                }
                return $locked->pesan()
                    ->where('id', '<=', $lastMessageId)
                    ->where('pengirim_user_id', '<>', $userId)
                    ->whereNull('dibaca_at')
                    ->update(['dibaca_at' => now()]);
            });
            if ($jumlahDitandai === null) {
                return $this->forbidden('Chat sudah ditangani Packing lain.');
            }
        } catch (Throwable $e) {
            Log::error('Gagal memperbarui status baca chat', [
                'chat_id' => $chatId,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status baca pesan.',
            ], 500);
        }

        $jumlahBelumDibaca = $chat->pesan()
            ->where('pengirim_user_id', '<>', $userId)
            ->whereNull('dibaca_at')
            ->count();

        // Transaksi sudah commit. Siarkan hanya jika ada pesan yang baru ditandai.
        // Gangguan Reverb tidak membatalkan perubahan status di database.
        if ($jumlahDitandai > 0) {
            try {
                event(new ChatKlarifikasiPesanDibaca(
                    (int) $chat->id,
                    $userId,
                    $lastMessageId,
                    (int) $jumlahDitandai,
                ));
            } catch (Throwable $e) {
                Log::warning('Status baca tersimpan tetapi event Reverb gagal', [
                    'chat_id' => $chatId,
                    'last_message_id' => $lastMessageId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Perbarui inbox pihak lain dan hapus antrean dari Packing lain setelah klaim.
        $this->broadcastInboxSafely($chat->fresh());

        return response()->json([
            'success' => true,
            'message' => 'Status baca pesan diperbarui.',
            'data' => [
                'chat_id' => (int) $chat->id,
                'last_message_id' => $lastMessageId,
                'jumlah_ditandai' => $jumlahDitandai,
                'jumlah_belum_dibaca' => $jumlahBelumDibaca,
            ],
        ]);
    }

    /**
     * Hanya Packing pembuat chat dapat menutup klarifikasi.
     */
    public function finish(Request $request, int $chatId): JsonResponse
    {
        $chat = ChatKlarifikasiPacking::find($chatId);

        if (! $chat) {
            return response()->json(['success' => false, 'message' => 'Chat tidak ditemukan.'], 404);
        }

        if (! $this->isParticipantRole($request->user())
            || $request->user()->role !== 'packing'
            || (int) $chat->packing_user_id !== (int) $request->user()->id) {
            return $this->forbidden('Hanya Packing pembuat chat yang dapat menyelesaikannya.');
        }

        // Kunci baris chat agar penutupan tidak balapan dengan send().
        try {
            $chat = DB::transaction(function () use ($chatId, $request) {
                $lockedChat = ChatKlarifikasiPacking::query()
                    ->whereKey($chatId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $lockedChat->packing_user_id !== (int) $request->user()->id) {
                    return null;
                }

                if ($lockedChat->status !== 'selesai') {
                    $lockedChat->update(['status' => 'selesai']);
                }

                return $lockedChat->fresh();
            });
        } catch (Throwable $e) {
            Log::error('Gagal menyelesaikan chat klarifikasi', [
                'chat_id' => $chatId,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyelesaikan klarifikasi.',
            ], 500);
        }

        if ($chat === null) {
            return $this->forbidden('Hanya Packing pembuat chat yang dapat menyelesaikannya.');
        }

        $this->broadcastInboxSafely($chat);

        return response()->json([
            'success' => true,
            'message' => 'Klarifikasi selesai.',
            'data' => $chat,
        ]);
    }

    private function isParticipantRole($user): bool
    {
        return $user && $user->platform === 'meta'
            && in_array($user->role, ['packing', 'cs'], true);
    }

    private function canAccess(Request $request, ChatKlarifikasiPacking $chat): bool
    {
        $user = $request->user();

        if (! $this->isParticipantRole($user)) {
            return false;
        }

        if ($chat->pesanan?->platform !== 'meta') return false;
        return $user->role === 'packing'
            ? ($chat->packing_user_id === null || (int) $chat->packing_user_id === (int) $user->id)
            : (int) $chat->cs_user_id === (int) $user->id;
    }

    private function forbidden(string $message = 'Tidak dapat mengakses chat ini.'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }

    /**
     * Respons API tetap berhasil ketika broadcaster sementara tidak tersedia.
     * Pesan sudah tersimpan; klien dapat mengambilnya ulang melalui GET detail.
     */
    /**
     * Beri sinyal inbox berubah, tanpa membocorkan isi pesan lewat event.
     * Setiap Packing Meta menerima sinyal agar antrean yang sudah diklaim
     * Packing lain segera hilang dari inbox mereka. Akses data tetap
     * dikendalikan oleh endpoint index Laravel.
     */
    private function broadcastInboxSafely(ChatKlarifikasiPacking $chat): void
    {
        try {
            $packingIds = \App\Models\User::query()
                ->where('role', 'packing')
                ->where('platform', 'meta')
                ->pluck('id')->map(fn ($id) => (int) $id)->all();
            $recipients = array_values(array_unique(array_merge(
                [(int) $chat->cs_user_id], $packingIds
            )));
            event(new ChatKlarifikasiInboxBerubah((int) $chat->id, $recipients));
        } catch (Throwable $e) {
            Log::warning('Chat tersimpan tetapi sinyal inbox Reverb gagal', [
                'chat_id' => $chat->id, 'error' => $e->getMessage(),
            ]);
        }
    }

    private function broadcastSafely(PesanChatKlarifikasiPacking $message): void
    {
        try {
            event(new ChatKlarifikasiPesanDikirim($message));
        } catch (Throwable $e) {
            Log::warning('Pesan tersimpan tetapi event Reverb gagal', [
                'chat_id' => $message->chat_id,
                'pesan_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
