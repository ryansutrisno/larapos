<?php

use App\User;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'Ryan Sutrisno',
            'email' => 'ryan.sutrisno@gmail.com',
            'password' => bcrypt('secret'),
            'status' => true
        ]);
    }
}
