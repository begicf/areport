<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Model\FactModule;
use App\Model\Taxonomy;
use AReportDpmXBRL\Config\Config;
use AReportDpmXBRL\Library\ArrayManipulation;
use AReportDpmXBRL\Library\Data;
use AReportDpmXBRL\Library\Format;
use AReportDpmXBRL\ModuleTree;
use Illuminate\Http\Request;


class ModulesController extends Controller
{
    private $_taxonomy;

    public function __construct()
    {
        $this->_taxonomy = Taxonomy::query()->where('active', true)->first();
    }

    public function index()
    {
        if (empty($this->_taxonomy)) {

            return redirect('/home')->with('warning', 'Please activate a taxonomy first.');

        }

        return view('modules.modules');
    }

    private function activeTaxonomyRoot(): string
    {
        return rtrim(Config::publicDir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->_taxonomy->folder;
    }

    private function pathInActiveTaxonomy(string $path): bool
    {
        $realPath = realpath($path);
        $realRoot = realpath($this->activeTaxonomyRoot());

        if ($realPath === false || $realRoot === false) {
            return false;
        }

        return str_starts_with($realPath, $realRoot . DIRECTORY_SEPARATOR) || $realPath === $realRoot;
    }

    private function hasDirectTableTaxonomy(string $path): bool
    {
        $tabSchema = $path . DIRECTORY_SEPARATOR . 'tab' . DIRECTORY_SEPARATOR . 'tab.xsd';
        $moduleSchemas = glob($path . DIRECTORY_SEPARATOR . 'mod' . DIRECTORY_SEPARATOR . '*.xsd') ?: [];

        return is_file($tabSchema) && empty($moduleSchemas);
    }

    private function discoverVersionedModules(string $frameworkPath, string $parentId): array
    {
        $directories = glob(rtrim($frameworkPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];
        $frameworkName = strtoupper(basename(rtrim($frameworkPath, DIRECTORY_SEPARATOR)));
        $nodes = [];

        foreach ($directories as $directory) {
            $moduleSchemas = glob($directory . DIRECTORY_SEPARATOR . 'mod' . DIRECTORY_SEPARATOR . '*.xsd') ?: [];
            $hasDirectTables = $this->hasDirectTableTaxonomy($directory);

            if (empty($moduleSchemas) && !$hasDirectTables) {
                continue;
            }

            $version = basename($directory);

            $nodes[] = [
                'parent' => $parentId,
                'children' => true,
                'data' => $directory,
                'id' => preg_replace('/[^a-zA-Z0-9]+/', '', $frameworkName . $version),
                'text' => $frameworkName . ' / ' . $version,
                'type' => 'tax',
                'creationDate' => $version,
                'entry_mode' => $hasDirectTables ? 'direct_tables' : 'module',
            ];
        }

        usort($nodes, function ($left, $right) {
            return strnatcasecmp($right['creationDate'], $left['creationDate']);
        });

        return $nodes;
    }

    private function makeDirectModuleNode(string $id, string $taxonomyPath): array
    {
        $frameworkName = strtoupper(basename(dirname(rtrim($taxonomyPath, DIRECTORY_SEPARATOR))));
        $version = basename(rtrim($taxonomyPath, DIRECTORY_SEPARATOR));

        return [
            'parent' => $id,
            'children' => true,
            'data' => $taxonomyPath,
            'id' => preg_replace('/[^a-zA-Z0-9#]+/', '', $id . '#direct'),
            'ext' => 'tab',
            'text' => $frameworkName,
            'mod' => $taxonomyPath,
            'type' => 'mod',
            'entry_mode' => 'direct_tables',
            'version' => $version,
        ];
    }

    private function getDirectTableNodes(string $id, string $taxonomyPath): array
    {
        $tableFiles = glob($taxonomyPath . DIRECTORY_SEPARATOR . 'tab' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*.xsd') ?: [];
        $nodes = [];

        sort($tableFiles, SORT_NATURAL);

        foreach ($tableFiles as $tableFile) {
            $tableCode = strtoupper(basename(dirname($tableFile)));

            $nodes[] = [
                'parent' => $id,
                'children' => false,
                'data' => $taxonomyPath,
                'id' => $id . '#' . preg_replace('/[^a-zA-Z0-9]+/', '', $tableCode),
                'text' => $tableCode,
                'table_xsd' => $tableFile,
                'type' => 'file',
            ];
        }

        return $nodes;
    }

    public function group(Request $request)
    {
        if (empty($this->_taxonomy)) {
            return response()->json([], 409);
        }

        $modulePath = (string) $request->get('module');

        if ($modulePath !== '' && (is_file($modulePath) || is_dir($modulePath)) && !$this->pathInActiveTaxonomy($modulePath)) {
            abort(404);
        }

        if (is_file($modulePath)):
            $module = Data::getTax($modulePath);
            $dir = dirname($modulePath);
        elseif (is_dir($modulePath) && $this->pathInActiveTaxonomy($modulePath) && $this->hasDirectTableTaxonomy($modulePath)):
            $tables = glob($modulePath . DIRECTORY_SEPARATOR . 'tab' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*.xsd') ?: [];
            $group = [];

            sort($tables, SORT_NATURAL);

            foreach ($tables as $tableFile) {
                $group[strtoupper(basename(dirname($tableFile)))] = $tableFile;
            }

            return response()->json([
                'All tables' => json_encode($group),
            ]);
        else:
            $tax = FactModule::query()
                ->where('module_path', '=', $modulePath)
                ->where('taxonomy_id', '=', $this->_taxonomy->id)
                ->with('taxonomy')
                ->firstOrFail();
            $path = rtrim(Config::publicDir(), DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR
                . $tax->taxonomy->folder
                . DIRECTORY_SEPARATOR
                . $modulePath;

            $module = Data::getTax($path);
            $dir = dirname($path);
        endif;


        $parent = ArrayManipulation::searchHref($module['pre'], key($module['elements']));

        $groups = ModuleTree::getGroupTable($module['pre'], key($parent));

        $_g = [];

        foreach ($groups as $key => $group) {
            $tmp = [];

            foreach ($group as $row) {

                $k = Format::getAfterSpecChar($row['href'], '#');
                $tmp[$k] =
                    $dir . DIRECTORY_SEPARATOR . (explode("-rend", $row['href']))[0] . '.xsd';
            }

            $_g[$key] = json_encode($tmp);

        }

        return response()->json($_g);

    }

    public function json(Request $request)
    {
        if (empty($this->_taxonomy)) {
            return response()->json([]);
        }

        $moduleTree = new ModuleTree($this->activeTaxonomyRoot());
        $id = (string) $request->get('id');
        $ext = (string) $request->get('ext');
        $path = (string) $request->get('path');

        if ($path !== '' && !$this->pathInActiveTaxonomy($path)) {
            return response()->json([]);
        }

        if ($ext === 'tax' && $path !== '' && $this->pathInActiveTaxonomy($path)) {
            $versionedNodes = $this->discoverVersionedModules($path, $id);

            if (!empty($versionedNodes)) {
                return response()->json($versionedNodes);
            }
        }

        if ($ext === 'mod' && $path !== '' && $this->pathInActiveTaxonomy($path) && $this->hasDirectTableTaxonomy($path)) {
            return response()->json([
                $this->makeDirectModuleNode($id, $path),
            ]);
        }

        if ($ext === 'tab' && $path !== '' && $this->pathInActiveTaxonomy($path) && $this->hasDirectTableTaxonomy($path)) {
            return response()->json($this->getDirectTableNodes($id, $path));
        }

        return response()->json($moduleTree->module($id, $ext, $path, $request->get('mod')));


    }
}
