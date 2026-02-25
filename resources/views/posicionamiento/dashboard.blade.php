@extends('posicionamiento.layout')

@section('title', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h5 class="card-title">Bienvenido</h5>
                <p><strong>Número económico:</strong> {{ $user->numero_economico }}</p>
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
                    

                    <form method="POST" action="{{ route('pos.logout') }}">
                        @csrf
                        <button class="btn btn-danger">Salir</button>
                    </form>

                </form>

            </div>
        </div>
    </div>
</div>
@endsection