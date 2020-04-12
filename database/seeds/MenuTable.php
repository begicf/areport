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
            [
            'parent_id' => '0',
            'title' => 'Home',
            'url'=>'/home',
            'description' => 'Home Page',
            'order'=>1
            ],
            [
                'parent_id' => '0',
                'title' => 'Modules',
                'url'=>'/modules',
                'description' => 'Modules',
                'order'=>2
            ],
            [
                'parent_id' => '0',
                'title' => 'Taxonomy',
                'url'=>'/',
                'description' => 'Taxonomy Page',
                'order'=>3
            ],
            [
                'parent_id' => '3',
                'title' => 'Managing',
                'url'=>'/taxonomy/managing',
                'description' => 'Taxonomy Page',
                'order'=>1
            ],
            [
                'parent_id' => '3',
                'title' => 'Upload',
                'url'=>'/taxonomy/upload',
                'description' => 'Taxonomy Page',
                'order'=>2
            ]

        ]);
    }
}
