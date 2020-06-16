<?php

namespace App\Http\Controllers\Areport;

use App\Http\Controllers\Controller;
use App\Model\FactHeader;
use App\Model\FactModule;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {

        return view('areport.home');
    }

    public function json(Request $request)
    {
        $data = FactModule::query();


        if ($request->sort):
            $data->orderBy($request->sort, $request->order);
        endif;

        $data->offset($request->offset)->limit($request->limit);


        $result = $data->get()->toArray();


        return response()->json(['total' => count($result), 'rows' => $result]);

    }
}
