<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\NumberFormat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** The signed-in user's own settings (the workspace ones are under Workspace settings). */
class SettingsController extends Controller
{
    public function index()
    {
        return view('account.settings', ['style' => NumberFormat::style(), 'styles' => NumberFormat::STYLES]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['number_format' => ['required', Rule::in(array_keys(NumberFormat::STYLES))]]);
        $request->user()->forceFill($data)->save();

        return redirect()->route('account.settings')->with('success', 'Numbers are now written as '.NumberFormat::STYLES[$data['number_format']][2].'.');
    }
}
