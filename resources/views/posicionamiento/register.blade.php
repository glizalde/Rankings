<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center" style="min-height:100vh;">
        <div class="card p-4 shadow" style="width:400px;">
            <h4 class="text-center mb-3">Registrar usuario</h4>

            @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $e)
                <div>{{ $e }}</div>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('pos.register.post') }}">
                @csrf

                <input class="form-control mb-2" name="numero_economico" placeholder="Número económico">
                <input class="form-control mb-2" name="cargo" placeholder="Cargo">
                <input class="form-control mb-2" name="correo" placeholder="Correo">
                <input class="form-control mb-2" name="unidad" placeholder="Unidad">

                <input class="form-control mb-2" type="password" name="password" placeholder="Contraseña">
                <input class="form-control mb-2" type="password" name="password_confirmation"
                    placeholder="Confirmar contraseña">

                <button class="btn btn-success w-100">Guardar</button>
            </form>
        </div>
    </div>

</body>

</html>