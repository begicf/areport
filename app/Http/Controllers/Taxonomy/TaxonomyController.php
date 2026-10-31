<?php

namespace App\Http\Controllers\Taxonomy;

use App\Http\Controllers\Controller;
use App\Model\Taxonomy;
use App\Model\TaxonomyPackage as TaxonomyPackageModel;
use App\Support\TaxonomyPackage;
use Illuminate\Http\Request;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class UploadController
 * @category
 * @package App\Http\Controllers\Upload
 * @author Fuad Begic <fuad.begic@gmail.com>
 * Date: 02/02/2020
 * Time: 18:08
 */
class TaxonomyController extends Controller
{

    private $path = 'tax';

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @return View
     */
    public function index()
    {
        return view('taxonomy.upload.upload');
    }

    public function managing()
    {
        $tax = Taxonomy::all();

        if (!$tax->isEmpty()):

            $frameworks = $tax->mapWithKeys(fn ($t) => [$t->id => $this->frameworksIn((string) $t->folder)]);
            $tax->load('packages');

            return view('taxonomy.managing.managing', ['tax' => $tax, 'frameworks' => $frameworks]);

        else:

            return redirect('/home')->with('warning', 'Please upload a taxonomy!');

        endif;
    }

    /**
     * Frameworks a taxonomy folder contains, by owner host, read from its folders:
     * ['COREP' => ['3.4', '4.0', '4.2'], …, 'SPN · fba.ba' => ['1.0']] (EBA's without a host tag).
     */
    private function frameworksIn(string $folder): array
    {
        $result = [];
        foreach (glob(storage_path('app/public/tax/'.$folder.'/*/eu/fr/xbrl/*/fws/*'), GLOB_ONLYDIR) ?: [] as $dir) {
            $host = basename(dirname($dir, 5));
            $versions = array_map('basename', glob($dir.'/*', GLOB_ONLYDIR) ?: []);
            natsort($versions);
            $key = strtoupper(basename($dir)).($host === 'www.eba.europa.eu' ? '' : ' · '.preg_replace('/^www\./', '', $host));
            $result[$key] = array_values(array_unique(array_merge($result[$key] ?? [], $versions)));
        }
        ksort($result);

        return $result;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function active(Request $request): \Illuminate\Http\RedirectResponse
    {
        $id = $request->get('tax_active');

        if ($id != null):

            Taxonomy::query()->update(['active' => false]);
            Taxonomy::where('id', $id)->update(['active' => true]);

            return back()->with('success', 'The taxonomy has been activated successfully.');
        else:

            return back()->with('warning', 'Please select a taxonomy!');

        endif;

    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete(Request $request): \Illuminate\Http\RedirectResponse
    {
        $id = $request->get('id');

        $_tax = Taxonomy::find($id);

        try {
            $dir = storage_path('app/public/' . $_tax->path . DIRECTORY_SEPARATOR . $_tax->folder);

            $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator($it,
                RecursiveIteratorIterator::CHILD_FIRST);

            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            rmdir($dir);

            Taxonomy::destroy($id);

        } catch (\Exception $exception) {

            return back()->with('danger', 'An error has occurred!');
        }

        return back()->with('success', 'The taxonomy was deleted successfully.');

    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['file' => ['required', 'file'], 'name' => ['nullable', 'string', 'max:255']]);
        $_name = pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_FILENAME);
        $tmp = storage_path('app/tax-upload/'.uniqid());
        $_newName = uniqid();
        $target = storage_path('app/public/'.$this->path.DIRECTORY_SEPARATOR.$_newName);

        try {
            TaxonomyPackage::extract($request->file('file')->getRealPath(), $tmp);
            // The package folder is the one holding META-INF/taxonomyPackage.xml, whatever the
            // zip is called; a zip without one is taken as before (its single top folder).
            $roots = TaxonomyPackage::roots($tmp);
            $tops = glob($tmp.'/*', GLOB_ONLYDIR) ?: [];
            if (count($roots) === 1) {
                rename($roots[0], $target);
            } elseif (count($roots) > 1) {
                @mkdir($target, 0775, true);
                foreach ($roots as $root) {
                    TaxonomyPackage::merge($root, $target);
                }
            } elseif (count($tops) === 1) {
                rename($tops[0], $target);
            } else {
                throw new \RuntimeException('No taxonomy package found in the archive (META-INF/taxonomyPackage.xml).');
            }
        } catch (\RuntimeException $e) {
            TaxonomyPackage::removeDir($tmp);

            return back()->with('danger', $e->getMessage());
        }
        TaxonomyPackage::removeDir($tmp);

        Taxonomy::create([
            'path' => $this->path,
            'folder' => $_newName,
            'name' => $request->get('name') ?: $_name,
            'original_name' => $_name,
        ]);

        return redirect('/taxonomy/managing')->with('success', 'Upload successful.');
    }

    /**
     * Add an extension package (e.g. a national regulator's taxonomy built on EBA's)
     * to an uploaded taxonomy: its host folders are merged into the taxonomy folder,
     * so its modules appear next to EBA's and its official URLs resolve offline.
     */
    public function addPackage(Request $request, Taxonomy $taxonomy): \Illuminate\Http\RedirectResponse
    {
        abort_unless(env('UPLOAD'), 403, 'Uploads are disabled.');
        $request->validate(['file' => ['required', 'file']]);
        $tmp = storage_path('app/tax-upload/'.uniqid());
        $target = storage_path('app/public/'.$taxonomy->path.DIRECTORY_SEPARATOR.$taxonomy->folder);
        if (! is_dir($target)) {
            return back()->with('danger', 'The taxonomy folder is missing.');
        }

        try {
            TaxonomyPackage::extract($request->file('file')->getRealPath(), $tmp);
            $roots = TaxonomyPackage::roots($tmp);
            if (! $roots) {
                throw new \RuntimeException('No taxonomy package found in the archive (META-INF/taxonomyPackage.xml).');
            }
            $added = [];
            foreach ($roots as $root) {
                $info = TaxonomyPackage::info($root);
                $files = TaxonomyPackage::merge($root, $target);
                TaxonomyPackageModel::query()->create($info + [
                    'taxonomy_id' => $taxonomy->id,
                    'files' => $files,
                    'original_name' => $request->file('file')->getClientOriginalName(),
                    'user_id' => auth()->id(),
                ]);
                $added[] = trim(($info['name'] ?: basename($root)).' '.($info['version'] ?? ''));
            }
        } catch (\RuntimeException $e) {
            TaxonomyPackage::removeDir($tmp);

            return back()->with('danger', $e->getMessage());
        }
        TaxonomyPackage::removeDir($tmp);

        return back()->with('success', 'Added to '.$taxonomy->name.': '.implode(', ', $added).'.');
    }
}
