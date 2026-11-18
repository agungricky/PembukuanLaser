<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatKlarifikasiPesanDibaca;
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
    /**
     * Daftar percakapan milik CS yang login atau Packing pembuat chat.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->isParticipantRole($user)) {
            return $this->forbidden();
        }

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['menunggu_jawaban', 'dibalas', 'selesai'])],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'belum_dibaca_saja' => ['nullable', 'boolean'],
        ]);

        $query = ChatKlarifikasiPacking::query()
            ->with(['packing:id,name', 'cs:id,name'])
            ->withCount(['pesan as jumlah_belum_dibaca' => function ($q) use ($user) {
                $q->where('pengirim_user_id', '<>', $user->id)
                    ->whereNull('dibaca_at');
            }]);

        if ($user->role === 'cs') {
            $query->where('cs_user_id', $user->id);
        } else {
            $query->where('packing_user_id', $user->id);
        }

        // Total unread dihitung dari semua chat milik peserta (tidak terpengaruh
        // filter pencarian/status/paginasi), cocok untuk badge pada Flutter.
        $jumlahBelumDibacaTotal = PesanChatKlarifikasiPacking::query()
            ->where('pengirim_user_id', '<>', $user->id)
            ->whereNull('dibaca_at')
            ->whereHas('chat', function ($q) use ($user) {
                if ($user->role === 'cs') {
                    $q->where('cs_user_id', $user->id);
                } else {
                    $q->where('packing_user_id', $user->id);
                }
            })->count();

        // Opsional: hanya chat yang memiliki pesan masuk belum dibaca.
        if ($request->boolean('belum_dibaca_saja')) {
            $query->whereHas('pesan', function ($q) use ($user) {
                $q->where('pengirim_user_id', '<>', $user->id)
                    ->whereNull('dibaca_at');
            });
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('no_pesanan', 'like', "%{$search}%")
                    ->orWhere('topik', 'like', "%{$search}%");
            });
        }

        $result = $query->orderByRaw('COALESCE(pesan_terakhir_at, created_at) DESC')
            ->orderByDesc('id')
            ->paginate((int) ($validated['per_page'] ?? 20));

        return response()->json([
            'success' => true,
            'data' => $result,
            'jumlah_belum_dibaca_total' => (int) $jumlahBelumDibacaTotal,
        ]);
    }

    /**
     * Packing memulai percakapan baru untuk pesanan Meta.
     * client_request_id opsional: retry dengan UUID yang sama mengembalikan chat lama.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'packing') {
            return $this->forbidden('Hanya Packing yang dapat membuat chat.');
        }

        $validated = $request->validate([
            'no_pesanan' => ['required', 'string', 'max:50'],
            'id_per_produk' => ['nullable', 'integer', 'min:1'],
            'topik' => ['required', 'string', 'max:255'],
            'pesan' => ['required', 'string', 'min:1', 'max:10000'],
            'client_request_id' => ['sometimes', 'nullable', 'uuid'],
        ]);

        $noPesanan = trim($validated['no_pesanan']);
        $topik = trim($validated['topik']);
        $isiPesan = trim($validated['pesan']);
        $idPerProduk = isset($validated['id_per_produk'])
            ? (int) $validated['id_per_produk'] : null;
        $uuid = $validated['client_request_id'] ?? null;

        if ($noPesanan === '' || $topik === '' || $isiPesan === '') {
            return response()->json([
                'success' => false,
                'message' => 'Nomor pesanan, topik dan pesan tidak boleh kosong.',
            ], 422);
        }

        try {
            // Kunci akun Packing agar dua request simultan dengan UUID sama
            // tidak membuat dua chat (unique index tetap menjadi pengaman DB).
            $result = DB::transaction(function () use (
                $user, $noPesanan, $topik, $isiPesan, $idPerProduk, $uuid
            ) {
                \App\Models\User::query()
                    ->whereKey($user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($uuid !== null) {
                    $existing = ChatKlarifikasiPacking::query()
                        ->where('packing_user_id', $user->id)
                        ->where('client_request_id', $uuid)
                        ->first();

                    if ($existing) {
                        $firstMessage = $existing->pesan()
                            ->reorder('id', 'asc')
                            ->first();

                        $samePayload = $existing->no_pesanan === $noPesanan
                            && (int) ($existing->id_per_produk ?? 0) === (int) ($idPerProduk ?? 0)
                            && $existing->topik === $topik
                            && $firstMessage !== null
                            && (int) $firstMessage->pengirim_user_id === (int) $user->id
                            && $firstMessage->pesan === $isiPesan;

                        if (! $samePayload) {
                            return ['state' => 'conflict'];
                        }

                        return [
                            'state' => 'duplicate',
                            'chat' => $existing,
                            'message' => $firstMessage,
                        ];
                    }
                }

                $pesanan = Pesanan::query()
                    ->where('no_pesanan', $noPesanan)
                    ->first();

                if (! $pesanan || $pesanan->platform !== 'meta') {
                    return ['state' => 'order_not_found'];
                }

                if (! $pesanan->id_user) {
                    return ['state' => 'owner_missing'];
                }

                $cs = \App\Models\User::query()->find($pesanan->id_user);
                if (! $cs || $cs->role !== 'cs') {
                    return ['state' => 'owner_invalid'];
                }

                if ($idPerProduk !== null && ! PesananPerProduk::query()
                    ->where('id_per_produk', $idPerProduk)
                    ->where('no_pesanan', $noPesanan)
                    ->exists()) {
                    return ['state' => 'product_invalid'];
                }

                // TODO: validasi penugasan Packing jika mekanismenya tersedia.
                // Gunakan assignment langsung agar tidak tergantung $fillable model.
                $chat = new ChatKlarifikasiPacking();
                $chat->no_pesanan = $noPesanan;
                $chat->id_per_produk = $idPerProduk;
                $chat->packing_user_id = $user->id;
                $chat->cs_user_id = $cs->id;
                $chat->client_request_id = $uuid;
                $chat->topik = $topik;
                $chat->status = 'menunggu_jawaban';
                $chat->save();

                $message = $chat->pesan()->create([
                    'pengirim_user_id' => $user->id,
                    'pesan' => $isiPesan,
                ]);

                $chat->update(['pesan_terakhir_at' => $message->created_at]);

                return [
                    'state' => 'created',
                    'chat' => $chat,
                    'message' => $message,
                ];
            });
        } catch (Throwable $e) {
            Log::error('Gagal membuat chat klarifikasi', ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat percakapan.',
            ], 500);
        }

        $rejected = [
            'conflict' => [409, 'client_request_id sudah digunakan untuk chat dengan data berbeda.'],
            'order_not_found' => [404, 'Pesanan Meta tidak ditemukan.'],
            'owner_missing' => [422, 'Pesanan belum memiliki CS pemilik.'],
            'owner_invalid' => [422, 'CS pemilik pesanan tidak valid.'],
            'product_invalid' => [422, 'Produk tidak termasuk dalam pesanan tersebut.'],
        ];

        if (isset($rejected[$result['state']])) {
            [$status, $message] = $rejected[$result['state']];
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        $chat = $result['chat'];
        $firstMessage = $result['message'];

        // Retry tidak boleh mengirim event kedua.
        if ($result['state'] === 'created') {
            $this->broadcastSafely($firstMessage);
        }

        // Struktur respons dipertahankan untuk kompatibilitas DDK Mobile.
        return response()->json([
            'success' => true,
            'message' => 'Chat klarifikasi berhasil dibuat.',
            'data' => $chat->fresh(['packing:id,name', 'cs:id,name']),
            'pesan_pertama' => $firstMessage->load('pengirim:id,name'),
        ], 201);
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
            $jumlahDitandai = DB::transaction(function () use ($chat, $userId, $lastMessageId) {
                return $chat->pesan()
                    ->where('id', '<=', $lastMessageId)
                    ->where('pengirim_user_id', '<>', $userId)
                    ->whereNull('dibaca_at')
                    ->update(['dibaca_at' => now()]);
            });
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

        if (! $request->user()
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

        return response()->json([
            'success' => true,
            'message' => 'Klarifikasi selesai.',
            'data' => $chat,
        ]);
    }

    private function isParticipantRole($user): bool
    {
        return $user && in_array($user->role, ['packing', 'cs'], true);
    }

    private function canAccess(Request $request, ChatKlarifikasiPacking $chat): bool
    {
        $user = $request->user();

        if (! $this->isParticipantRole($user)) {
            return false;
        }

        return $user->role === 'packing'
            ? (int) $chat->packing_user_id === (int) $user->id
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
