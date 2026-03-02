<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SubmissionsGenerate extends Command
{
    protected $signature = 'submissions:generate
        {--year=2026 : Año}
        {--ranking=1 : ID del ranking}
        {--stage=1 : ID de la etapa}
        {--catalog= : ID del catálogo (opcional; si no, usa el published más reciente)}';

    protected $description = 'Genera submissions (una por unidad) para un año/ranking/etapa.';

    public function handle(): int
    {
        $year = (int)$this->option('year');
        $rankingId = (int)$this->option('ranking');
        $stageId = (int)$this->option('stage');
        $catalogId = $this->option('catalog') ? (int)$this->option('catalog') : null;

        if (!$catalogId) {
            $catalogId = DB::table('catalogs')
                ->where('ranking_id', $rankingId)
                ->where('stage_id', $stageId)
                ->where('status', 'published')
                ->orderByDesc('id')
                ->value('id');
        }

        if (!$catalogId) {
            $this->error("No encontré un catálogo published para ranking={$rankingId} stage={$stageId}.");
            return self::FAILURE;
        }

        $units = DB::table('units')->select('id','name')->orderBy('id')->get();
        if ($units->isEmpty()) {
            $this->error("No hay unidades en la tabla units. Corre el UnitsSeeder.");
            return self::FAILURE;
        }

        $created = 0;
        foreach ($units as $u) {
            $exists = DB::table('submissions')->where([
                'year' => $year,
                'unit_id' => $u->id,
                'ranking_id' => $rankingId,
                'stage_id' => $stageId,
            ])->exists();

            if ($exists) continue;

            DB::table('submissions')->insert([
                'year' => $year,
                'unit_id' => $u->id,
                'ranking_id' => $rankingId,
                'stage_id' => $stageId,
                'catalog_id' => $catalogId,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $created++;
        }

        $this->info("Catálogo usado: {$catalogId}");
        $this->info("Submissions creadas: {$created} (año={$year}, ranking={$rankingId}, etapa={$stageId})");
        return self::SUCCESS;
    }
}