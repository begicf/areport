<?php

namespace App\Http\Controllers\Taxonomy;

use App\Http\Controllers\Controller;
use App\Model\Taxonomy;
use Illuminate\Http\Request;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

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

            return view('taxonomy.managing.managing', ['tax' => $tax]);

        else:

            return redirect('/home')->with('warning', 'Please upload a taxonomy!');

        endif;
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

            return back()->with('success', 'You have successfully actived the taxonomy.');
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

        return back()->with('success', 'You taxonomy is successful deleted.');

    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {

        $_name = pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_FILENAME);
        $file = $request->file('file')->store($this->path, 'public');

        $zip = new ZipArchive();

        $res = $zip->open(storage_path('app/public/' . $file));

        if ($res === TRUE):

            $zip->extractTo(storage_path('app/public/' . $this->path));

            $zip->close();

            // remove zip file
            unlink(storage_path('app/public/' . $file));
            $_newName = uniqid();

            // rename
            rename(storage_path('app/public/' . $this->path . DIRECTORY_SEPARATOR . $_name), storage_path('app/public/' . $this->path . DIRECTORY_SEPARATOR . $_newName));

            Taxonomy::create([
                'path' => $this->path,
                'folder' => $_newName,
                'name' => $request->get('name'),
                'original_name' => $_name
            ]);

            return back()->with('success', 'Upload successful.');

        endif;

        return back()->with('danger', 'An error has occurred!');
    }
}
