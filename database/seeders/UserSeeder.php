<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if(!User::where('email', 'user@email.net')->first()){
            User::create([
                'name'=>'user',
                'email'=>'user@email.net',
                'password'=> Hash::make('senha', ['rounds'=>12]),
            ]);
        }
    }
}
