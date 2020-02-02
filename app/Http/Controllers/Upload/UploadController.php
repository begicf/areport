<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use App\Model\UploadTaxonomy;
use Illuminate\Http\Request;

/**
 * Class UploadController
 * @category
 * @package App\Http\Controllers\Upload
 * @author Fuad Begic <fuad.begic@gmail.com>
 * Date: 02/02/2020
 * Time: 18:08
 */
class UploadController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @return View
     */
    public function index()
    {

        return view('upload/upload');
    }

    /**
     * @param Request $request
     * @return Http\Response
     */
    public function store(Request $request)
    {
        $path = 'file';

        $_name = $request->file('file')->getClientOriginalName();
        $file = $request->file('file')->store($path, 'public');

        $zip = new \ZipArchive();

        $res = $zip->open(storage_path('app/public/' . $file));

        $name = $path . DIRECTORY_SEPARATOR . substr($_name, 0, strpos($_name, "."));

        if ($res === TRUE):

            $zip->extractTo(storage_path('app/public/' . $name));

            $zip->close();
            // remove zip file
            //  unlink(storage_path('app/public/' . $file));

            UploadTaxonomy::create([
                'file' => $file,
                'original_name' => $name
            ]);

        endif;


        if ($request->wantsJson()) {
            return response([], 204);
        }

        return back();
    }
}
