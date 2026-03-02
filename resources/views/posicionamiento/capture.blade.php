@extends('posicionamiento.layout')

@section('title', 'Captura')

@section('content')

@if(session('ok'))
<div class="alert alert-success">{{ session('ok') }}</div>
@endif



@php
$isReadOnly = !in_array($submission->status, ['draft','rejected']);
@endphp

@if($isReadOnly)
<div class="alert alert-info">
    Esta captura está en <strong>solo lectura</strong> (estado: {{ $submission->status }}).
</div>
@endif

@if($submission->status === 'rejected' && !empty($submission->review_notes))
<div class="alert alert-warning">
    <div class="fw-bold">Observaciones del admin:</div>
    <div class="mt-1">{{ $submission->review_notes }}</div>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
    <div class="fw-bold mb-2">Hay errores:</div>
    <ul class="mb-0">
        @foreach($errors->all() as $e)
        <li>{{ $e }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-1">Captura</h5>
                <div class="text-muted small">
                    Año: <strong>{{ $submission->year }}</strong> · Submission: <strong>#{{ $submission->id }}</strong> · Estado: <strong>{{ $submission->status }}</strong>
                </div>
            </div>
            <a href="{{ route('pos.dashboard') }}" class="btn btn-outline-secondary btn-sm">Volver al dashboard</a>
        </div>
    </div>
</div>

@if(session('error'))
<div class="alert alert-danger">
    <div class="fw-bold">{{ session('error') }}</div>

    @if(session('missing_list'))
    <div class="mt-2">Pendientes:</div>
    <ul class="mb-0">
        @foreach(session('missing_list') as $m)
        <li>{{ $m }}</li>
        @endforeach
    </ul>
    @endif
</div>
@endif

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <div class="fw-semibold">Progreso</div>
                <div class="small text-muted">
                    Completadas: <strong>{{ $completedCount }}</strong> / {{ $total }}
                    · Pendientes: <strong>{{ $missingCount }}</strong>
                </div>
            </div>

            <div style="min-width: 240px;" class="flex-grow-1">
                <div class="progress" style="height: 10px;">

                    <div class="progress-bar" role="progressbar" style="width: {{ $progressPct }}%;"></div>
                </div>
                <div class="small text-muted mt-1">{{ $progressPct }}%</div>
            </div>

            <form method="POST" action="{{ route('pos.capture.submit', ['submission' => $submission->id]) }}">
                @csrf
                <button class="btn btn-success"
                    {{ ($missingCount > 0 || !in_array($submission->status, ['draft','rejected'])) ? 'disabled' : '' }}>
                    Enviar captura
                </button>
            </form>
        </div>

        @if($missingCount > 0)
        <div class="mt-3">
            <div class="fw-semibold mb-2">Pendientes ({{ $missingCount }})</div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Sección</th>
                            <th>Código</th>
                            <th>Variable</th>
                            <th>Falta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($missing as $m)
                        <tr>
                            <td>{{ $m['section'] ?: '—' }}</td>
                            <td>{{ $m['code'] ?: '—' }}</td>
                            <td>{{ $m['label'] }}</td>
                            <td>
                                @if($m['needs_value']) <span class="badge bg-danger">valor</span> @endif
                                @if($m['needs_word']) <span class="badge bg-warning text-dark">word</span> @endif
                                @if($m['needs_links'])
                                <span class="badge bg-info text-dark">links (mín {{ $m['min_links'] }})</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>

@php
$currentSection = null;
@endphp

@foreach($catalogVars as $v)
@if($currentSection !== $v->section)
@php $currentSection = $v->section; @endphp
<div class="mt-4 mb-2">
    <h5 class="mb-0">{{ $currentSection ?: 'Sin sección' }}</h5>
    <hr class="mt-2">
</div>
@endif

@php
$saved = $values[$v->variable_id] ?? null;
$savedValue = null;

if($v->data_type === 'number') $savedValue = $saved?->value_number;
elseif($v->data_type === 'boolean') $savedValue = is_null($saved?->value_bool) ? null : ($saved?->value_bool ? 1 : 0);
else $savedValue = $saved?->value_text;

$varLinks = $links[$v->variable_id] ?? collect();
@endphp

<div class="card shadow-sm mb-3">
    <div class="card-body">

        <div class="d-flex flex-wrap justify-content-between gap-2">
            <div class="pe-2">
                <div class="fw-bold">
                    {{ $v->code }} — {{ $v->label }}
                </div>
                @if(!empty($v->description))
                <div class="text-muted small">{{ $v->description }}</div>
                @endif

                <div class="mt-2 d-flex flex-wrap gap-2">
                    <span class="badge bg-light text-dark">tipo: {{ $v->data_type }}</span>

                    @if($v->required_value)
                    <span class="badge bg-danger">valor requerido</span>
                    @endif

                    @if($v->required_word)
                    <span class="badge bg-warning text-dark">word requerido</span>
                    @endif

                    @if($v->required_links_min > 0)
                    <span class="badge bg-info text-dark">mín links: {{ $v->required_links_min }}</span>
                    @endif
                </div>
            </div>

            <div class="text-end">
                @if($saved)
                <div class="small text-muted">Última edición</div>
                <div class="small">
                    {{ \Carbon\Carbon::parse($saved->updated_at)->format('Y-m-d H:i') }}
                </div>
                @endif
            </div>
        </div>

        <hr>

        {{-- 1) Guardar valor --}}
        <form method="POST" action="{{ route('pos.capture.value.save', ['submission' => $submission->id, 'variable' => $v->variable_id]) }}" class="row g-2 align-items-end">
            @csrf

            <div class="col-12 col-md-8">
                <label class="form-label mb-1">Valor</label>

                @if($v->data_type === 'number')
                <input type="number" step="any" name="value"
                    value="{{ old('value', $savedValue) }}"
                    class="form-control"
                    placeholder="Ingresa un número" {{ $isReadOnly ? 'disabled' : '' }}>
                @elseif($v->data_type === 'boolean')
                <select name="value" class="form-select" {{ $isReadOnly ? 'disabled' : '' }}>
                    <option value="" {{ is_null($savedValue) ? 'selected' : '' }}>— Selecciona —</option>
                    <option value="1" {{ $savedValue === 1 ? 'selected' : '' }}>Sí</option>
                    <option value="0" {{ $savedValue === 0 ? 'selected' : '' }}>No</option>
                </select>
                @else
                <input type="text" name="value"
                    value="{{ old('value', $savedValue) }}"
                    class="form-control"
                    placeholder="Escribe el valor" {{ $isReadOnly ? 'disabled' : '' }}>
                @endif

                <div class="form-text">
                    {{ $v->required_value ? 'Campo obligatorio según catálogo.' : 'Opcional.' }}
                </div>
            </div>

            <div class="col-12 col-md-4 text-md-end">
                <button class="btn btn-primary w-100 w-md-auto" {{ $isReadOnly ? 'disabled' : '' }}>Guardar valor</button>
            </div>
        </form>

        {{-- 2) Word (1 archivo) --}}
        <div class="mt-3">
            <div class="fw-semibold mb-1">Evidencia (Word)</div>

            @if(!empty($saved?->word_path))
            <div class="alert alert-light py-2 d-flex justify-content-between align-items-center">
                <div class="small text-muted text-break">
                    Guardado: {{ $saved->word_path }}
                </div>
            </div>
            @else
            <div class="small text-muted mb-2">No hay archivo cargado.</div>
            @endif

            <form method="POST"
                action="{{ route('pos.capture.word.upload', ['submission' => $submission->id, 'variable' => $v->variable_id]) }}"
                enctype="multipart/form-data"
                class="row g-2 align-items-end">
                @csrf
                <div class="col-12 col-md-8">
                    <input type="file" name="word" class="form-control" accept=".doc,.docx"   {{ $v->required_word ? 'required' : '' }} {{ $isReadOnly ? 'disabled' : '' }} >
                    <div class="form-text">
                        {{ $v->required_word ? 'Obligatorio para enviar.' : 'Opcional.' }} (doc/docx, máx 10MB)
                    </div>
                </div>
                <div class="col-12 col-md-4 text-md-end">
                    <button class="btn btn-outline-primary w-100 w-md-auto" {{ $isReadOnly ? 'disabled' : '' }}>Subir Word</button>
                </div>
            </form>
        </div>

        {{-- 3) Links múltiples --}}
        <div class="mt-4">
            <div class="fw-semibold mb-2">Links</div>

            @if($varLinks->count())
            <ul class="list-group mb-2">
                @foreach($varLinks as $l)
                <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                    <a href="{{ $l->url }}" target="_blank" class="text-break">{{ $l->url }}</a>
                    @if(!$isReadOnly)
                    <form method="POST" action="{{ route('pos.capture.links.delete', ['link' => $l->id]) }}"
                        onsubmit="return confirm('¿Eliminar este link?')">
                        @csrf
                        @method('DELETE')


                        <button class="btn btn-sm btn-outline-danger">Eliminar</button>


                    </form>
                    @endif
                </li>
                @endforeach
            </ul>
            @else
            <div class="small text-muted mb-2">Sin links.</div>
            @endif

            <form method="POST"
                action="{{ route('pos.capture.links.add', ['submission' => $submission->id, 'variable' => $v->variable_id]) }}"
                class="row g-2 align-items-end">
                @csrf
                <div class="col-12 col-md-8">
                    <label class="form-label mb-1">Agregar link</label>
                    <input type="url" name="url" class="form-control" placeholder="https://..." required {{ $isReadOnly ? 'disabled' : '' }}>
                    <div class="form-text">
                        Mínimo requerido: {{ $v->required_links_min }}.
                    </div>
                </div>
                <div class="col-12 col-md-4 text-md-end">
                    <button class="btn btn-outline-success w-100 w-md-auto" {{ $isReadOnly ? 'disabled' : '' }}>Agregar link</button>
                </div>
            </form>
        </div>

    </div>
</div>

@endforeach

@endsection