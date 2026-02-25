<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Login - Posicionamiento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center" style="min-height:100vh;">
        <div class="card shadow p-4" style="width: 350px;">
            <h4 class="text-center mb-3">Iniciar sesión</h4>

            @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
            @endif

            @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $e)
                <div>{{ $e }}</div>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('pos.login.post') }}">
                @csrf

                <div class="mb-3">
                    <label>Número económico</label>
                    <input type="text" name="numero_economico" class="form-control"
                        maxlength="5" required>
                </div>

                <div class="mb-3">
                    <label>Contraseña</label>
                    <input type="password" name="password" class="form-control" required autofocus>
                </div>

                
                <button class="btn btn-success w-100 fw-bold">
                    Iniciar sesión
                </button>
            </form>
        </div>
    </div>

</body>

</html>