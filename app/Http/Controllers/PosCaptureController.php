<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

use Illuminate\Support\Facades\Storage;


class PosCaptureController extends Controller
{
    //
    public function show($submissionId)
    {
        // Tu sistema actual usa pos_user en sesión
        $user = User::find(session('pos_user'));
        abort_unless($user, 401);

        // Resolver unit_id aunque no esté seteado en users
        $unitId = $user->unit_id ?? DB::table('units')->where('name', $user->unidad)->value('id');

        // Traer submission
        $submission = DB::table('submissions')->where('id', $submissionId)->first();
        abort_unless($submission, 404);

        // Seguridad: solo su unidad (admin central lo abrimos después)
        if ($user->role !== 'admin') {
            $unitId = $user->unit_id ?? DB::table('units')->where('name', $user->unidad)->value('id');
            abort_unless((int)$submission->unit_id === (int)$unitId, 403);
        }

        // Traer variables del catálogo (form dinámico)
        $catalogVars = DB::table('catalog_variable as cv')
            ->join('variables as v', 'v.id', '=', 'cv.variable_id')
            ->where('cv.catalog_id', $submission->catalog_id)
            ->where('cv.visible', 1)
            ->orderByRaw("COALESCE(cv.section,'') ASC")
            ->orderBy('cv.order')
            ->select([
                'cv.variable_id',
                'cv.section',
                'cv.code',
                'cv.order',
                'cv.required_value',
                'cv.required_word',
                'cv.required_links_min',
                'v.key',
                'v.label',
                'v.description',
                'v.data_type',
            ])
            ->get();

        // Valores ya guardados (si existen)
        $values = DB::table('submission_values')
            ->where('submission_id', $submissionId)
            ->get()
            ->keyBy('variable_id');

        $links = DB::table('submission_value_links as l')
            ->join('submission_values as sv', 'sv.id', '=', 'l.submission_value_id')
            ->where('sv.submission_id', $submissionId)
            ->select('l.*', 'sv.variable_id')
            ->get()
            ->groupBy('variable_id');

        // Pendientes por variable según catálogo
        $missing = [];

        foreach ($catalogVars as $v) {
            $saved = $values[$v->variable_id] ?? null;

            // 1) valor requerido
            $hasValue = true;
            if ($v->required_value) {
                if ($v->data_type === 'number') $hasValue = !is_null($saved?->value_number);
                elseif ($v->data_type === 'boolean') $hasValue = !is_null($saved?->value_bool);
                else $hasValue = !empty(trim((string)($saved?->value_text ?? '')));
            }

            // 2) word requerido
            $hasWord = true;
            if ($v->required_word) {
                $hasWord = !empty($saved?->word_path);
            }

            // 3) links mínimos
            $minLinks = (int)($v->required_links_min ?? 0);
            $hasLinks = true;
            if ($minLinks > 0) {
                $hasLinks = (($links[$v->variable_id] ?? collect())->count() >= $minLinks);
            }

            if (!$hasValue || !$hasWord || !$hasLinks) {
                $missing[] = [
                    'variable_id' => $v->variable_id,
                    'section' => $v->section,
                    'code' => $v->code,
                    'label' => $v->label,
                    'needs_value' => !$hasValue,
                    'needs_word' => !$hasWord,
                    'needs_links' => !$hasLinks,
                    'min_links' => $minLinks,
                ];
            }
        }

        $total = $catalogVars->count();
        $missingCount = count($missing);
        $completedCount = $total - $missingCount;
        $progressPct = $total > 0 ? round(($completedCount / $total) * 100) : 0;

        return view('posicionamiento.capture', compact(
            'user',
            'submission',
            'catalogVars',
            'values',
            'links',
            'missing',
            'total',
            'missingCount',
            'completedCount',
            'progressPct'
        ));
    }

    public function saveValue(Request $request, $submissionId, $variableId)
    {
        $user = User::find(session('pos_user'));
        abort_unless($user, 401);

        $submission = DB::table('submissions')->where('id', $submissionId)->first();
        abort_unless($submission, 404);

        $this->ensureEditable($submission);

        // permiso: admin o misma unidad
        if ($user->role !== 'admin') {
            $unitId = $user->unit_id ?? DB::table('units')->where('name', $user->unidad)->value('id');
            abort_unless((int)$submission->unit_id === (int)$unitId, 403);
        }

        // tipo de dato
        $var = DB::table('variables')->where('id', $variableId)->first();
        abort_unless($var, 404);

        // valida según tipo
        $rules = match ($var->data_type) {
            'number' => ['value' => ['nullable', 'numeric']],
            'boolean' => ['value' => ['nullable', 'in:0,1']],
            default => ['value' => ['nullable', 'string']],
        };
        $data = $request->validate($rules);

        // prepara columnas
        $value_number = null;
        $value_text = null;
        $value_bool = null;
        if ($var->data_type === 'number') $value_number = $data['value'] ?? null;
        elseif ($var->data_type === 'boolean') $value_bool = isset($data['value']) ? (bool)$data['value'] : null;
        else $value_text = $data['value'] ?? null;

        DB::table('submission_values')->updateOrInsert(
            ['submission_id' => $submissionId, 'variable_id' => $variableId],
            [
                'value_number' => $value_number,
                'value_text' => $value_text,
                'value_bool' => $value_bool,
                'created_by' => DB::raw('COALESCE(created_by, ' . ((int)$user->id) . ')'),
                'updated_by' => $user->id,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return back()->with('ok', 'Guardado.');
    }

    public function uploadWord(Request $request, $submissionId, $variableId)
    {
        $user = User::find(session('pos_user'));
        abort_unless($user, 401);

        $submission = DB::table('submissions')->where('id', $submissionId)->first();
        abort_unless($submission, 404);

        $this->ensureEditable($submission);

        if ($user->role !== 'admin') {
            $unitId = $user->unit_id ?? DB::table('units')->where('name', $user->unidad)->value('id');
            abort_unless((int)$submission->unit_id === (int)$unitId, 403);
        }

        $request->validate([
            'word' => ['required', 'file', 'mimes:doc,docx', 'max:10240'], // 10MB
        ]);

        // asegura que exista submission_value
        DB::table('submission_values')->updateOrInsert(
            ['submission_id' => $submissionId, 'variable_id' => $variableId],
            [
                'created_by' => DB::raw('COALESCE(created_by, ' . ((int)$user->id) . ')'),
                'updated_by' => $user->id,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $sv = DB::table('submission_values')
            ->where('submission_id', $submissionId)
            ->where('variable_id', $variableId)
            ->first();

        $path = $request->file('word')->store("posicionamiento/{$submission->year}/submission_{$submissionId}/variable_{$variableId}");

        DB::table('submission_values')->where('id', $sv->id)->update([
            'word_path' => $path,
            'updated_by' => $user->id,
            'updated_at' => now(),
        ]);

        return back()->with('ok', 'Word subido.');
    }

    public function addLink(Request $request, $submissionId, $variableId)
    {
        $user = User::find(session('pos_user'));
        abort_unless($user, 401);

        $submission = DB::table('submissions')->where('id', $submissionId)->first();
        abort_unless($submission, 404);

        $this->ensureEditable($submission);

        if ($user->role !== 'admin') {
            $unitId = $user->unit_id ?? DB::table('units')->where('name', $user->unidad)->value('id');
            abort_unless((int)$submission->unit_id === (int)$unitId, 403);
        }

        $data = $request->validate([
            'url' => ['required', 'url'],
        ]);

        // asegura submission_value
        DB::table('submission_values')->updateOrInsert(
            ['submission_id' => $submissionId, 'variable_id' => $variableId],
            [
                'created_by' => DB::raw('COALESCE(created_by, ' . ((int)$user->id) . ')'),
                'updated_by' => $user->id,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $sv = DB::table('submission_values')
            ->where('submission_id', $submissionId)
            ->where('variable_id', $variableId)
            ->first();

        DB::table('submission_value_links')->insert([
            'submission_value_id' => $sv->id,
            'url' => $data['url'],
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('ok', 'Link agregado.');
    }

    public function deleteLink($linkId)
    {
        $user = User::find(session('pos_user'));
        abort_unless($user, 401);

        $link = DB::table('submission_value_links')->where('id', $linkId)->first();
        abort_unless($link, 404);

        // validar que pertenezca a una submission accesible
        $sv = DB::table('submission_values')->where('id', $link->submission_value_id)->first();
        $submission = DB::table('submissions')->where('id', $sv->submission_id)->first();


        $this->ensureEditable($submission);
        if ($user->role !== 'admin') {
            $unitId = $user->unit_id ?? DB::table('units')->where('name', $user->unidad)->value('id');
            abort_unless((int)$submission->unit_id === (int)$unitId, 403);
        }

        DB::table('submission_value_links')->where('id', $linkId)->delete();
        return back()->with('ok', 'Link eliminado.');
    }

    public function submit(Request $request, $submissionId)
    {
        $user = User::find(session('pos_user'));
        abort_unless($user, 401);

        $submission = DB::table('submissions')->where('id', $submissionId)->first();
        abort_unless($submission, 404);



        // Permisos: admin o misma unidad
        if ($user->role !== 'admin') {
            $unitId = $user->unit_id ?? DB::table('units')->where('name', $user->unidad)->value('id');
            abort_unless((int)$submission->unit_id === (int)$unitId, 403);
        }

        // Solo permitir enviar si está en draft o rejected (tú decides)
        if (!in_array($submission->status, ['draft', 'rejected'], true)) {
            return back()->with('error', 'Esta captura no se puede enviar en su estado actual.');
        }

        // Recalcular pendientes (misma lógica que show)
        $catalogVars = DB::table('catalog_variable as cv')
            ->join('variables as v', 'v.id', '=', 'cv.variable_id')
            ->where('cv.catalog_id', $submission->catalog_id)
            ->where('cv.visible', 1)
            ->orderByRaw("COALESCE(cv.section,'') ASC")
            ->orderBy('cv.order')
            ->select([
                'cv.variable_id',
                'cv.section',
                'cv.code',
                'cv.required_value',
                'cv.required_word',
                'cv.required_links_min',
                'v.label',
                'v.data_type',
            ])
            ->get();

        $values = DB::table('submission_values')
            ->where('submission_id', $submissionId)
            ->get()
            ->keyBy('variable_id');

        $links = DB::table('submission_value_links as l')
            ->join('submission_values as sv', 'sv.id', '=', 'l.submission_value_id')
            ->where('sv.submission_id', $submissionId)
            ->select('l.id', 'sv.variable_id')
            ->get()
            ->groupBy('variable_id');

        $missing = [];

        foreach ($catalogVars as $v) {
            $saved = $values[$v->variable_id] ?? null;

            $hasValue = true;
            if ($v->required_value) {
                if ($v->data_type === 'number') $hasValue = !is_null($saved?->value_number);
                elseif ($v->data_type === 'boolean') $hasValue = !is_null($saved?->value_bool);
                else $hasValue = !empty(trim((string)($saved?->value_text ?? '')));
            }

            $hasWord = true;
            if ($v->required_word) {
                $hasWord = !empty($saved?->word_path);
            }

            $minLinks = (int)($v->required_links_min ?? 0);
            $hasLinks = true;
            if ($minLinks > 0) {
                $hasLinks = (($links[$v->variable_id] ?? collect())->count() >= $minLinks);
            }

            if (!$hasValue || !$hasWord || !$hasLinks) {
                $missing[] = "{$v->code} {$v->label}";
            }
        }

        if (count($missing) > 0) {
            return back()->with('error', 'No se puede enviar: faltan campos requeridos.')
                ->with('missing_list', $missing);
        }

        DB::table('submissions')->where('id', $submissionId)->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'updated_by' => $user->id,
            'updated_at' => now(),
        ]);

        return redirect()->route('pos.capture', ['submission' => $submissionId])
            ->with('ok', '¡Captura enviada! Quedó en revisión.');
    }

    private function ensureEditable($submission): void
    {
        if (!in_array($submission->status, ['draft', 'rejected'], true)) {
            abort(403, 'Esta captura está en modo solo lectura.');
        }
    }
}
