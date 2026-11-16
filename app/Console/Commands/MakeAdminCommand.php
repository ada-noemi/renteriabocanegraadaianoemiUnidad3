<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdminCommand extends Command
{
    protected $signature = 'users:make-admin {email : Correo de un usuario existente}';

    protected $description = 'Asigna el rol de administrador a un usuario existente';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No existe un usuario con ese correo.');

            return self::FAILURE;
        }

        $user->forceFill(['role' => User::ROLE_ADMIN])->save();
        $this->info('El usuario ahora es administrador.');

        return self::SUCCESS;
    }
}
