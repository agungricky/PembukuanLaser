<?php

namespace App\Services\ImportResi;

use App\Models\Pesanan;

class DeadlineUpdater
{
    public function update(int $idToko, array $pages): void
    {
        $updates = collect($pages)
            ->filter(fn ($page) => ! empty($page['batas_kirim_at']))
            ->keyBy('no_pesanan');

        foreach ($updates->chunk(500) as $batch) {
            $model = new Pesanan;
            $connection = $model->getConnection();
            $grammar = $connection->getQueryGrammar();
            $orderColumn = $grammar->wrap('no_pesanan');
            $assignments = [];
            $bindings = [];

            foreach (['batas_kirim_at', 'batas_kirim_source', 'batas_kirim_raw'] as $column) {
                $cases = [];
                foreach ($batch as $page) {
                    $cases[] = 'WHEN ? THEN ?';
                    $bindings[] = (string) $page['no_pesanan'];
                    $bindings[] = $page[$column] ?? null;
                }
                $wrapped = $grammar->wrap($column);
                $assignments[] = $wrapped.' = CASE '.$orderColumn.' '.implode(' ', $cases).' ELSE '.$wrapped.' END';
            }

            $bindings[] = $idToko;
            foreach ($batch as $page) {
                $bindings[] = (string) $page['no_pesanan'];
            }
            $connection->update(
                'UPDATE '.$grammar->wrapTable($model->getTable()).' SET '.implode(', ', $assignments)
                .' WHERE '.$grammar->wrap('id_toko').' = ? AND '.$orderColumn
                .' IN ('.implode(', ', array_fill(0, $batch->count(), '?')).')',
                $bindings
            );
        }
    }
}
