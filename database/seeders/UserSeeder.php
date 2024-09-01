<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if(!User::where('email', 'cesar@celke.com.br')->first()){
            User::create([
                'name' => 'Cesar',
                'email' => 'cesar@celke.com.br',
                'password' => Hash::make('123456a', ['rounds' => 12]),
            ]);
        }
        if(!User::where('email', 'kelly@celke.com.br')->first()){
            User::create([
                'name' => 'Kelly',
                'email' => 'kelly@celke.com.br',
                'password' => Hash::make('123456a', ['rounds' => 12]),
            ]);
        }
        if(!User::where('email', 'jessica@celke.com.br')->first()){
            User::create([
                'name' => 'Jessica',
                'email' => 'jessica@celke.com.br',
                'password' => Hash::make('123456a', ['rounds' => 12]),
            ]);
        }
        if(!User::where('email', 'gabrielly@celke.com.br')->first()){
            User::create([
                'name' => 'Gabrielly',
                'email' => 'gabrielly@celke.com.br',
                'password' => Hash::make('123456a', ['rounds' => 12]),
            ]);
        }
    }
}
