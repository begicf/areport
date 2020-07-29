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
                [
                    'name' => 'EBA 2.8.1.1',
                    'original_name' => 'FullTaxonomy.2.8.1.1',
                    'path' => 'tax',
                    'folder' => '5ed61a099ab25',
                    'active' => 1,
                ],
                [
                    'name' => 'EBA 2.10 Phase1',
                    'original_name' => 'FullTaxonomy2.10.Phase1',
                    'path' => 'tax',
                    'folder' => '5ee081cb7d85e',
                    'active' => 0
                ]
            ]
        );
    }
}
