<?php
namespace Database\Seeders;

use App\Models\Administrateur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Administrateur::create([
            'nom'               => 'Admin',
            'prenom'            => 'SIMMo',
            'email'             => 'admin@simmo.cm',
            'mot_de_passe_hash' => Hash::make('idriss1234'),
            'role'              => 'super_admin',
            'is_active'         => true,
        ]);
    }
}