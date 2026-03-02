<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CatalogImport extends Command
{
    protected $signature = 'catalog:import
        {--ranking=1 : ID del ranking}
        {--stage=1 : ID de la etapa}
        {--year= : Año (opcional, si quieres versionar catálogo)}
        {--file= : Ruta al archivo .xlsx (obligatorio)}
        {--publish : Publicar el catálogo al finalizar}';

    protected $description = 'Importa un Excel de variables y crea/actualiza un catálogo (ranking+etapa).';

    public function handle(): int
    {
        $file = $this->option('file');
        if (!$file || !is_file($file)) {
            $this->error("Archivo no válido. Usa --file=/ruta/al.xlsx");
            return self::FAILURE;
        }

        $rankingId = (int)$this->option('ranking');
        $stageId   = (int)$this->option('stage');
        $year      = $this->option('year') ? (int)$this->option('year') : null;

        // 1) Crea/obtén catálogo
        $catalogName = "Ranking {$rankingId} - Etapa {$stageId}" . ($year ? " - {$year}" : "");
        $catalogId = DB::table('catalogs')->where([
            'ranking_id' => $rankingId,
            'stage_id' => $stageId,
            'year' => $year,
        ])->value('id');

        if (!$catalogId) {
            $catalogId = DB::table('catalogs')->insertGetId([
                'ranking_id' => $rankingId,
                'stage_id' => $stageId,
                'year' => $year,
                'status' => 'draft',
                'name' => $catalogName,
                'created_by' => auth()->id() ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2) Lee Excel
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        // Esperamos columnas:
        // A: code / encabezado sección
        // B: descripción
        // C: tipo de dato
        // D: Archivo Word
        // E: link (enlace)
        // F: Variable abreviada
        $currentSection = null;
        $order = 1;
        $imported = 0;
        $skipped = 0;

        // omite encabezado
        foreach ($rows as $i => $r) {
            if ($i === 1) continue;

            $colA = trim((string)($r['A'] ?? ''));
            $colB = trim((string)($r['B'] ?? ''));
            $colC = trim((string)($r['C'] ?? ''));
            $colD = trim((string)($r['D'] ?? ''));
            $colE = trim((string)($r['E'] ?? ''));
            $key  = trim((string)($r['F'] ?? ''));

            // Detecta encabezados de sección tipo: "2) Energy and Climate Change (EC)"
            if ($colA !== '' && preg_match('/^\d\)\s+/', $colA)) {
                $currentSection = $colA;
                continue;
            }

            if ($key === '') {
                continue;
            }

            // Normaliza tipo
            $type = $this->normalizeType($colC);

            // Si no hay descripción/tipo, no rompas, pero marca como inactiva o text
            $label = Str::of($colB)->limit(120, '')->toString();
            if ($label === '') $label = $key;

            $requiresWord = $this->isYes($colD);
            $minLinks = $this->isYes($colE) ? 1 : 0;

            DB::beginTransaction();
            try {
                // upsert variable
                $variableId = DB::table('variables')->where('key', $key)->value('id');
                if (!$variableId) {
                    $variableId = DB::table('variables')->insertGetId([
                        'key' => $key,
                        'label' => $label,
                        'description' => $colB ?: null,
                        'data_type' => $type,
                        'active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('variables')->where('id', $variableId)->update([
                        'label' => $label,
                        'description' => $colB ?: null,
                        'data_type' => $type,
                        'updated_at' => now(),
                    ]);
                }

                // upsert pivot (catalog_variable)
                DB::table('catalog_variable')->updateOrInsert(
                    ['catalog_id' => $catalogId, 'variable_id' => $variableId],
                    [
                        'section' => $currentSection,
                        'code' => $colA ?: null,
                        'order' => $order++,
                        'required_value' => true,
                        'required_word' => $requiresWord,
                        'required_links_min' => $minLinks,
                        'visible' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                DB::commit();
                $imported++;
            } catch (\Throwable $e) {
                DB::rollBack();
                $skipped++;
                $this->warn("Fila {$i} ({$key}) omitida: " . $e->getMessage());
            }
        }

        if ($this->option('publish')) {
            DB::table('catalogs')->where('id', $catalogId)->update([
                'status' => 'published',
                'updated_at' => now(),
            ]);
        }

        $this->info("Catálogo ID: {$catalogId}");
        $this->info("Importadas: {$imported} | Omitidas: {$skipped}");
        return self::SUCCESS;
    }

    private function normalizeType(string $raw): string
    {
        $s = Str::lower(trim($raw));
        if ($s === '') return 'text';

        if (str_contains($s, 'num')) return 'number';
        if (str_contains($s, 'texto')) return 'text';
        if (str_contains($s, 'si/no') || str_contains($s, 'yes/no')) return 'boolean';

        return 'text';
    }

    private function isYes(string $raw): bool
    {
        $s = Str::lower(trim($raw));
        return in_array($s, ['si','sí','yes','y','1','true'], true);
    }
}