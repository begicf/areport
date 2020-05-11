<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Model\Taxonomy;
use DpmXbrl\Library\Data;
use DpmXbrl\Library\Format;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use DpmXbrl\Tax;


class TableController extends Controller
{

    private $_tablePath;
    private $_period;
    private $_taxonomy;


    public function table(Request $request)
    {

        $this->_taxonomy = Taxonomy::all()->where('active', true)->first();
        $this->_period = $request->get('period');

        if ($request->get('table')):
            $table = array_map('json_decode', $request->get('table'));

            $groups = array_column($table, 'group');

            $tables = array_map(function ($arr) {
                return $arr->group = json_encode($arr->table);
            }, $table);

            $_groups = array_combine($groups, $tables);

            $tc = current($table[0]->table);
        else:

            $groups_array = json_decode($request->get('group'), true);


            if ($request->get('tab')):
                $tc = $request->get('tab');
            else:
                $tc = current($groups_array);
            endif;


        endif;

        // $tax = Data::getTax($tc);

        if (file_exists($tc)):

            if (isset($table)):
                return view('table.table', [

                    'taxonomy' => $this->_tablePath,
                    'table' => $table,
                    'groups' => $_groups,
                    'period' => $this->_period,
                    'mod' => $request->get('mod'),
                    'group' => $request->get('mod')
                ]);
            else:

                $tax = Data::getTax($tc);
                $taxOb = new Tax();
                $data = $taxOb->render()->renderHtml($tax);
                $data['groups'] = $this->makeButtonGroup($groups_array, $tc);

                return response($data);

            endif;

        else:
            abort(404);
        endif;
    }

    private function makeButtonGroup($array, $table): ?string
    {
        $buttonGroup = null;

        if (is_array($array) && count($array) > 1):

            $buttonGroup = "<div class='btn-group' role='group' aria-label='Basic example'>";
            foreach ($array as $key => $row):
                $active = ($row == $table) ? 'active' : '';
                $buttonGroup .= "<button type='button' onclick='changeTable(this,\"T\")' value='$row'  class='btn btn-primary $active'>" . Format::getAfterSpecChar($key, '_t', 2) . "</button>";
            endforeach;
            $buttonGroup .= "</div>";
        endif;

        return $buttonGroup;
    }
}
