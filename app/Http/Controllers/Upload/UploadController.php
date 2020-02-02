<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use App\Model\UploadTaxonomy;
use Illuminate\Http\Request;


class UploadController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {


        return view('upload/upload');
    }

    public function store(Request $request)
    {
        $_name = $request->file('file')->getClientOriginalName();
        $file = $request->file('file')->store('file', 'public');

        $zip = new \ZipArchive();

        $res = $zip->open(storage_path('app/public/' . $file));

        $name = substr($_name, 0, strpos($_name, "."));

        if ($res === TRUE):

            $zip->extractTo(storage_path('app/public/file/' . $name));

            $zip->close();

        endif;

        UploadTaxonomy::create([
            'file' => $file,
            'original_name' => $name
        ]);

        if ($request->wantsJson()) {
            return response([], 204);
        }

        return back();
    }
}
