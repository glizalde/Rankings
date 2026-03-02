<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosReviewController extends Controller
{
    private function requireAdmin(): User
    {
        $user = User::find(session('pos_user'));
        abort_unless($user, 401);
        abort_unless($user->role === 'admin', 403);
        return $user;
    }

    public function approve($submissionId)
    {
        $user = $this->requireAdmin();

        $submission = DB::table('submissions')->where('id', $submissionId)->first();
        abort_unless($submission, 404);

        // Solo aprobar si ya fue enviada (o en revisión)
        if (!in_array($submission->status, ['submitted', 'in_review'], true)) {
            return back()->with('error', 'Solo se puede aprobar una captura enviada.');
        }

        DB::table('submissions')->where('id', $submissionId)->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $user->id,
            'review_notes' => null,
            'updated_by' => $user->id,
            'updated_at' => now(),
        ]);

        return back()->with('ok', 'Captura aprobada.');
    }

    public function reject(Request $request, $submissionId)
    {
        $user = $this->requireAdmin();

        $data = $request->validate([
            'review_notes' => ['required', 'string', 'min:5'],
        ]);

        $submission = DB::table('submissions')->where('id', $submissionId)->first();
        abort_unless($submission, 404);

        if (!in_array($submission->status, ['submitted', 'in_review'], true)) {
            return back()->with('error', 'Solo se puede rechazar una captura enviada.');
        }

        DB::table('submissions')->where('id', $submissionId)->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $user->id,
            'review_notes' => $data['review_notes'],
            'updated_by' => $user->id,
            'updated_at' => now(),
        ]);

        return back()->with('ok', 'Captura rechazada (se reabre para correcciones).');
    }

    public function reopen(Request $request, $submissionId)
    {
        $user = $this->requireAdmin();

        $submission = DB::table('submissions')->where('id', $submissionId)->first();
        abort_unless($submission, 404);

        // Permitir reabrir desde rejected o submitted si tú quieres
        

        if ($submission->status !== 'rejected') {
            return back()->with('error', 'Solo se puede reabrir una captura rechazada.');
        }

        DB::table('submissions')->where('id', $submissionId)->update([
            'status' => 'draft',
            'updated_by' => $user->id,
            'updated_at' => now(),
        ]);

        return back()->with('ok', 'Captura reabierta a borrador.');
    }
}
