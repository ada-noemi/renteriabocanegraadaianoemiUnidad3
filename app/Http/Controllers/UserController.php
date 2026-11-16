<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('usuarios.index', ['usuarios' => User::query()->latest('id')->paginate(10)]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = new User;
        $user->fill(collect($data)->only(['name', 'email', 'password'])->all());
        $user->role = $data['role'];
        $user->save();

        return to_route('usuarios.index')->with('success', 'Cuenta creada correctamente.');
    }
}
