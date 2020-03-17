<?php

use Illuminate\Database\Seeder;

class MenuTable extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('menu')->insert([
            'parent_id' => '0',
            'title' => 'Home',
            'url'=>'/',
            'description' => 'Home Page',
            'order'=>1
        ]);
    }
}
