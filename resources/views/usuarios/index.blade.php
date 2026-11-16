@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
    @php($errors = (new \Illuminate\Support\ViewErrorBag)->put('default', new \Illuminate\Support\MessageBag(session('user_form_errors', []))))
    <p class="text-sm text-slate-600">Crea cuentas y asigna los permisos de acceso al sistema.</p>
    @if (session('success'))
        <div role="status" class="rounded-lg border border-teal-200 bg-teal-50 p-4 text-sm text-teal-800">{{ session('success') }}</div>
    @endif

    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Crear cuenta</h2>
        <p class="mt-1 text-sm text-slate-500">El administrador gestiona todo. El técnico consulta equipos y actualiza el estado de los mantenimientos.</p>
        @if ($errors->any())
            <div role="alert" class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">Revisa los campos indicados.</div>
        @endif
        <form action="{{ route('usuarios.store') }}" method="POST" class="mt-6 grid gap-5 md:grid-cols-2">
            @csrf
            @foreach (['name' => 'Nombre', 'email' => 'Correo electrónico', 'password' => 'Contraseña', 'password_confirmation' => 'Confirmar contraseña'] as $field => $label)
                <div>
                    <label for="{{ $field }}" class="mb-2 block text-sm font-medium">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="{{ str_starts_with($field, 'password') ? 'password' : ($field === 'email' ? 'email' : 'text') }}"
                        @if (! str_starts_with($field, 'password')) value="{{ old($field) }}" maxlength="255" @else minlength="8" maxlength="255" autocomplete="new-password" @endif
                        required @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-200">
                    @error($field)<p id="{{ $field }}-error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            @endforeach
            <div>
                <label for="role" class="mb-2 block text-sm font-medium">Rol</label>
                <select id="role" name="role" required @error('role') aria-invalid="true" aria-describedby="role-error" @enderror
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-200">
                    <option value="tecnico" @selected(old('role', 'tecnico') === 'tecnico')>Técnico</option>
                    <option value="administrador" @selected(old('role') === 'administrador')>Administrador</option>
                </select>
                @error('role')<p id="role-error" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="rounded-lg bg-teal-700 px-5 py-3 text-sm font-medium text-white hover:bg-teal-800">Crear cuenta</button>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <h2 class="border-b border-slate-200 p-6 text-lg font-semibold">Cuentas registradas</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Usuarios del sistema</caption>
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        @foreach (['Nombre', 'Correo electrónico', 'Rol'] as $column)
                            <th scope="col" class="px-6 py-4 font-medium">{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($usuarios as $usuario)
                        <tr>
                            <td class="break-words px-6 py-4">{{ $usuario->name }}</td>
                            <td class="break-words px-6 py-4">{{ $usuario->email }}</td>
                            <td class="px-6 py-4">{{ $usuario->isAdmin() ? 'Administrador' : 'Técnico' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($usuarios->hasPages())
            <div class="border-t border-slate-200 p-6">{{ $usuarios->links() }}</div>
        @endif
    </section>
@endsection
