@extends('posicionamiento.layout')

@section('title', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h5 class="card-title">Bienvenido</h5>
                <p><strong>Nombre:</strong> {{ $user->name }}</p>
                <p><strong>Correo:</strong> {{ $user->correo }}</p>
                <p><strong>Unidad:</strong> {{ $user->unidad }}</p>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h5 class="card-title">Acciones</h5>



                <form method="POST" action="{{ route('pos.logout') }}">
                    @csrf
                    <button class="btn btn-danger">Salir</button>
                </form>



            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Mis capturas ({{ $year }})</h5>

                @if(isset($submissions) && $submissions->count())
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                @if($user->role === 'admin')
                                <th>Unidad</th>
                                @endif
                                @if($user->role === 'admin')
                                <th>Revisión</th>
                                @endif
                                <th>Ranking</th>
                                <th>Etapa</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($submissions as $s)
                            <tr>
                                @if($user->role === 'admin')
                                <td>{{ $s->unit_name }}</td>
                                @endif
                                <td>{{ $s->ranking_name }}</td>
                                <td>{{ $s->stage_name }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $s->status }}</span>
                                </td>
                                @if($user->role === 'admin')
                                <td>
                                    @if(in_array($s->status, ['submitted','in_review']))
                                    <div class="d-flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('pos.review.approve', ['submission' => $s->id]) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-success">Aprobar</button>
                                        </form>

                                        <!-- Botón para abrir modal de rechazo -->
                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $s->id }}">
                                            Rechazar
                                        </button>
                                    </div>

                                    <!-- Modal Rechazo -->
                                    <div class="modal fade" id="rejectModal{{ $s->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <form method="POST" action="{{ route('pos.review.reject', ['submission' => $s->id]) }}" class="modal-content">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Rechazar captura</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <label class="form-label">Motivo / Observaciones</label>
                                                    <textarea name="review_notes" class="form-control" rows="4" required minlength="5"
                                                        placeholder="Describe qué falta o qué debe corregirse..."></textarea>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button class="btn btn-danger">Rechazar</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    @elseif($s->status === 'rejected')
                                    <div class="small text-muted mb-1">Rechazada</div>
                                    <form method="POST" action="{{ route('pos.review.reopen', ['submission' => $s->id]) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-warning">Reabrir</button>
                                    </form>
                                    @elseif($s->status === 'approved')
                                    <span class="badge bg-success">Aprobada</span>
                                    @else
                                    <span class="badge bg-secondary">{{ $s->status }}</span>
                                    @endif
                                </td>
                                @endif
                                <td class="text-end">
                                    <a class="btn btn-primary btn-sm"
                                        href="{{ route('pos.capture', ['submission' => $s->id]) }}">
                                        Continuar
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="mb-0">Aún no tienes capturas asignadas para {{ $year }}.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection