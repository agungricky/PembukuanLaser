<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MergeDuplicateChats extends Command
{
    protected $signature = 'chat:merge-duplicates {--execute : Gabungkan chat duplikat setelah backup database}';
    protected $description = 'Audit/gabung chat dengan no_pesanan sama tanpa menghapus pesan';

    public function handle(): int
    {
        if (! DB::getSchemaBuilder()->hasTable('chat_klarifikasi_merge_archives')) {
            $this->error('Jalankan migration tabel chat_klarifikasi_merge_archives terlebih dahulu.');
            return self::FAILURE;
        }

        $numbers = DB::table('chat_klarifikasi_packings')
            ->select('no_pesanan')
            ->groupBy('no_pesanan')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('no_pesanan');

        if ($numbers->isEmpty()) {
            $this->info('Tidak ditemukan chat duplikat.');
            return self::SUCCESS;
        }

        $execute = (bool) $this->option('execute');
        if ($execute && ! $this->confirm('Sudah backup DATABASE? Gabungkan chat duplikat sekarang?', false)) {
            $this->warn('Dibatalkan. Database tidak berubah.');
            return self::SUCCESS;
        }

        foreach ($numbers as $number) {
            try {
                $description = DB::transaction(function () use ($number, $execute): array {
                    $chats = DB::table('chat_klarifikasi_packings')
                        ->where('no_pesanan', $number)
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

                    if ($chats->count() <= 1) {
                        return ['number' => $number, 'notice' => 'sudah tidak duplikat'];
                    }

                    $counts = DB::table('pesan_chat_klarifikasi_packings')
                        ->whereIn('chat_id', $chats->pluck('id')->all())
                        ->select('chat_id')
                        ->selectRaw('COUNT(*) as total')
                        ->groupBy('chat_id')
                        ->pluck('total', 'chat_id');

                    // Pertahankan chat dengan pesan terbanyak, jika sama pilih ID paling kecil.
                    $canonical = $chats->sort(function ($a, $b) use ($counts) {
                        $byCount = ((int) ($counts[$b->id] ?? 0)) <=> ((int) ($counts[$a->id] ?? 0));
                        return $byCount !== 0 ? $byCount : ($a->id <=> $b->id);
                    })->first();
                    $others = $chats->where('id', '!=', $canonical->id)->values();

                    foreach ($others as $other) {
                        if ((int) $other->cs_user_id !== (int) $canonical->cs_user_id ||
                            (int) $other->packing_user_id !== (int) $canonical->packing_user_id) {
                            throw new RuntimeException('Peserta berbeda pada chat #'.$other->id.'; audit manual diperlukan.');
                        }
                    }

                    // Index UNIQUE(chat_id, pengirim_user_id, client_message_id) tidak
                    // boleh bentrok sesudah seluruh pesan disatukan.
                    $duplicates = DB::table('pesan_chat_klarifikasi_packings')
                        ->whereIn('chat_id', $chats->pluck('id')->all())
                        ->whereNotNull('client_message_id')
                        ->select('pengirim_user_id', 'client_message_id')
                        ->groupBy('pengirim_user_id', 'client_message_id')
                        ->havingRaw('COUNT(*) > 1')
                        ->exists();
                    if ($duplicates) {
                        throw new RuntimeException('client_message_id berbenturan, perlu audit manual.');
                    }

                    $moved = 0;
                    foreach ($others as $other) {
                        $count = (int) ($counts[$other->id] ?? 0);
                        $moved += $count;
                        if (! $execute) {
                            continue;
                        }

                        // Simpan salinan metadata dahulu agar chat lama dapat ditelusuri.
                        DB::table('chat_klarifikasi_merge_archives')->insert([
                            'original_chat_id' => $other->id,
                            'canonical_chat_id' => $canonical->id,
                            'no_pesanan' => $number,
                            'original_chat_json' => json_encode($other, JSON_THROW_ON_ERROR),
                            'jumlah_pesan_dipindahkan' => $count,
                            'merged_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        DB::table('pesan_chat_klarifikasi_packings')
                            ->where('chat_id', $other->id)
                            ->update(['chat_id' => $canonical->id]);
                        DB::table('chat_klarifikasi_packings')->where('id', $other->id)->delete();
                    }

                    if ($execute) {
                        $lastMessageAt = DB::table('pesan_chat_klarifikasi_packings')
                            ->where('chat_id', $canonical->id)->max('created_at');
                        DB::table('chat_klarifikasi_packings')
                            ->where('id', $canonical->id)
                            ->update(['pesan_terakhir_at' => $lastMessageAt, 'updated_at' => now()]);
                    }

                    return [
                        'number' => $number,
                        'main' => $canonical->id,
                        'merged_ids' => $others->pluck('id')->implode(', '),
                        'messages' => (int) $counts->sum(),
                        'moved' => $moved,
                    ];
                });

                if (isset($description['notice'])) {
                    $this->line($description['number'].': '.$description['notice']);
                    continue;
                }
                $this->line(sprintf(
                    '%s: utama #%s; gabung [%s]; total %s pesan; dipindah %s%s',
                    $description['number'], $description['main'],
                    $description['merged_ids'], $description['messages'],
                    $description['moved'], $execute ? ' (SELESAI)' : ' (SIMULASI)'
                ));
            } catch (\Throwable $e) {
                $this->error($number.': '.$e->getMessage());
                return self::FAILURE;
            }
        }

        $this->info($execute ? 'Penggabungan selesai.' : 'Audit selesai; tidak ada data yang diubah.');
        return self::SUCCESS;
    }
}
