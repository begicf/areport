<?php

use Illuminate\Database\Seeder;

class TaxonomyTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('taxonomies')->insert([
            'name' => 'EBA 2.8.1.1',
            'original_name' => 'FullTaxonomy.2.8.1.1',
            'path' => 'tax',
            'folder' => '5ed61a099ab25',
            'active' => 1,
        ]);
    }
}
