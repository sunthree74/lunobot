<?php

use Illuminate\Database\Seeder;
use App\Command;

class CommandsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Command::create([
            'command' => '@welcome',
            'description' => 'Welcome message for new user in group',
            'message' => 'Hi @fname@, welcome to telegram group for Luno Malaysia',
            'user_id' => '1',
            'links' => '[]',
            'link_title' => '[]'
        ]);
    }
}
