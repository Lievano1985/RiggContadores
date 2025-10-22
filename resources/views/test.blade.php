<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test Livewire</title>
    @livewireStyles
</head>
<body class="p-10">
    <h1 class="text-2xl font-bold mb-4">🚧 Test del componente ClienteContrasena</h1>

    @livewire('clientes.cliente-contrasena', ['clienteId' => 1]) {{-- Usa un ID de cliente real --}}

    @livewireScripts
</body>
</html>
