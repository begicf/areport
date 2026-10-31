<?php

namespace Tests\Feature;

use App\Model\Taxonomy;
use App\Model\TaxonomyPackage as TaxonomyPackageModel;
use App\Support\TaxonomyPackage;
use App\User;
use AReportDpmXBRL\Library\MetricTypes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use ZipArchive;

class TaxonomyPackageTest extends TestCase
{
    use RefreshDatabase;

    private string $folder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->folder = '_pkgtest_'.uniqid();
        mkdir(storage_path('app/public/tax/'.$this->folder.'/www.eba.europa.eu'), 0775, true);
        $this->actingAs(User::query()->create(['name' => 'Alice', 'email' => 'alice@example.com', 'password' => Hash::make('secret'), 'email_verified_at' => now()]));
        putenv('UPLOAD=1');
    }

    protected function tearDown(): void
    {
        TaxonomyPackage::removeDir(storage_path('app/public/tax/'.$this->folder));
        parent::tearDown();
    }

    /** A minimal owner package: META-INF + a module and a metric dictionary under www.example.org. */
    private function packageZip(string $top = 'ex_taxonomy'): string
    {
        $zipFile = tempnam(sys_get_temp_dir(), 'pkg').'.zip';
        $zip = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString($top.'/META-INF/taxonomyPackage.xml', '<?xml version="1.0"?><tp:taxonomyPackage xmlns:tp="http://xbrl.org/2016/taxonomy-package">'
            .'<tp:identifier>http://www.example.org/eu/fr/xbrl/crr/1.1</tp:identifier><tp:name>EX taxonomy</tp:name><tp:version>1.1</tp:version><tp:publisher>Example</tp:publisher>'
            .'<tp:entryPoints><tp:entryPoint><tp:name>EX1</tp:name><tp:entryPointDocument href="http://www.example.org/eu/fr/xbrl/crr/fws/ex/1.1/mod/ex1.xsd"/></tp:entryPoint></tp:entryPoints></tp:taxonomyPackage>');
        $zip->addFromString($top.'/META-INF/catalog.xml', '<catalog xmlns="urn:oasis:names:tc:entity:xmlns:xml:catalog"/>');
        $zip->addFromString($top.'/www.example.org/eu/fr/xbrl/crr/fws/ex/1.1/mod/ex1.xsd', '<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema"/>');
        $zip->addFromString($top.'/www.example.org/eu/fr/xbrl/crr/dict/met/met.xsd', '<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">'
            .'<xs:element name="ex_m1" type="xbrli:integerItemType"/><xs:element name="ex_m2" type="xbrli:monetaryItemType"/></xs:schema>');
        $zip->close();

        return $zipFile;
    }

    public function test_an_extension_package_is_merged_into_the_taxonomy(): void
    {
        $taxonomy = Taxonomy::query()->create(['name' => 'EBA 4.2', 'original_name' => 'eba42', 'path' => 'tax', 'folder' => $this->folder]);
        $file = new UploadedFile($this->packageZip(), 'ex_taxonomy_1.1.zip', 'application/zip', null, true);

        $this->post('/taxonomy/'.$taxonomy->id.'/package', ['file' => $file])
            ->assertRedirect()
            ->assertSessionHas('success');

        $root = storage_path('app/public/tax/'.$this->folder);
        $this->assertFileExists($root.'/www.example.org/eu/fr/xbrl/crr/fws/ex/1.1/mod/ex1.xsd');
        $this->assertDirectoryDoesNotExist($root.'/META-INF');
        $this->assertDirectoryExists($root.'/www.eba.europa.eu');   // EBA's files stay

        $pkg = TaxonomyPackageModel::query()->sole();
        $this->assertSame(['EX taxonomy', '1.1', 2], [$pkg->name, $pkg->version, $pkg->files]);
        $this->assertSame('http://www.example.org/eu/fr/xbrl/crr/fws/ex/1.1/mod/ex1.xsd', $pkg->entry_points[0]['href']);

        // The owner's metric types resolve from its own dictionary (unit / decimals in xBRL-XML).
        $this->assertSame('integer', MetricTypes::kind($this->folder, 'ex_met:ex_m1'));
        $this->assertSame('monetary', MetricTypes::kind($this->folder, 'ex_met:ex_m2'));
    }

    public function test_an_archive_without_a_package_is_refused(): void
    {
        $taxonomy = Taxonomy::query()->create(['name' => 'EBA 4.2', 'original_name' => 'eba42', 'path' => 'tax', 'folder' => $this->folder]);
        $zipFile = tempnam(sys_get_temp_dir(), 'pkg').'.zip';
        $zip = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('readme.txt', 'no package here');
        $zip->close();

        $this->post('/taxonomy/'.$taxonomy->id.'/package', ['file' => new UploadedFile($zipFile, 'x.zip', 'application/zip', null, true)])
            ->assertSessionHas('danger');
        $this->assertSame(0, TaxonomyPackageModel::query()->count());
    }

    public function test_package_paths_cannot_escape_the_extraction_folder(): void
    {
        $zipFile = tempnam(sys_get_temp_dir(), 'pkg').'.zip';
        $zip = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('../evil.txt', 'x');
        $zip->close();

        $this->expectException(\RuntimeException::class);
        TaxonomyPackage::extract($zipFile, sys_get_temp_dir().'/pkgx_'.uniqid());
    }

    public function test_uploading_a_package_whose_folder_differs_from_the_zip_name(): void
    {
        $file = new UploadedFile($this->packageZip('some_other_folder'), 'EX_v1.zip', 'application/zip', null, true);

        $this->post('/taxonomy/upload', ['file' => $file, 'name' => 'EX'])->assertRedirect('/taxonomy/managing');

        $taxonomy = Taxonomy::query()->where('name', 'EX')->sole();
        $root = storage_path('app/public/tax/'.$taxonomy->folder);
        $this->assertFileExists($root.'/www.example.org/eu/fr/xbrl/crr/fws/ex/1.1/mod/ex1.xsd');
        TaxonomyPackage::removeDir($root);
    }
}
