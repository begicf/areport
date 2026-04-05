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
            $tableLabel = $this->resolveTableDisplayName($tableFile);

            $nodes[] = [
                'parent' => $id,
                'children' => false,
                'data' => $taxonomyPath,
                'id' => $id . '#' . preg_replace('/[^a-zA-Z0-9]+/', '', $tableCode),
                'text' => $tableLabel ?: $tableCode,
                'table_xsd' => $tableFile,
                'type' => 'file',
            ];
        }

        return $nodes;
    }

    private function getJsonFallbackGroups(string $modulePath): array
    {
        $tables = $this->resolveModuleTableMapFromJson($modulePath);

        if (empty($tables)) {
            return [];
        }

        ksort($tables, SORT_NATURAL);

        return [
            'Available groups' => json_encode($tables),
        ];
    }

    private function resolveModuleTableMapFromJson(string $modulePath): array
    {
        if (method_exists(ModuleTree::class, 'getModuleTableMapFromJson')) {
            return ModuleTree::getModuleTableMapFromJson($modulePath);
        }

        $jsonPath = preg_replace('/\.xsd$/', '.json', $modulePath);

        if (!is_string($jsonPath) || !is_file($jsonPath)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($jsonPath), true);

        if (!is_array($decoded)) {
            return [];
        }

        $tables = [];

        foreach (($decoded['documentInfo']['extends'] ?? []) as $extendPath) {
            if (!is_string($extendPath) || substr($extendPath, -5) !== '.json') {
                continue;
            }

            if (strpos($extendPath, 'FilingIndicators.json') !== false || strpos($extendPath, 'FootNotes.json') !== false) {
                continue;
            }

            if (preg_match('/^https?:\/\//i', $extendPath)) {
                continue;
            }

            $localJsonPath = realpath(dirname($jsonPath) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $extendPath));

            if ($localJsonPath === false) {
                continue;
            }

            $localXsdPath = preg_replace('/\.json$/', '.xsd', $localJsonPath);

            if (!is_string($localXsdPath) || !is_file($localXsdPath)) {
                continue;
            }

            $tables[strtoupper(pathinfo($localXsdPath, PATHINFO_FILENAME))] = $localXsdPath;
        }

        return $tables;
    }

    private function resolveTableDisplayName(string $tablePath): string
    {
        if (method_exists(ModuleTree::class, 'getTableDisplayName')) {
            return ModuleTree::getTableDisplayName($tablePath) ?: strtoupper(pathinfo($tablePath, PATHINFO_FILENAME));
        }

        try {
            $taxonomy = Data::getTax($tablePath, Data::getLangSpec('mod'));
        } catch (\Throwable $exception) {
            return strtoupper(pathinfo($tablePath, PATHINFO_FILENAME));
        }

        $languageKey = Data::checkLang($taxonomy);

        if (empty($languageKey) || empty($taxonomy[$languageKey]) || !is_array($taxonomy[$languageKey])) {
            return strtoupper(pathinfo($tablePath, PATHINFO_FILENAME));
        }

        $tableCode = pathinfo($tablePath, PATHINFO_FILENAME);

        foreach ($this->preferredLabelRoles() as $role) {
            foreach ($taxonomy[$languageKey] as $entry) {
                if (($entry['role'] ?? null) !== $role) {
                    continue;
                }

                if (strpos((string) ($entry['href'] ?? ''), $tableCode . '-rend.xml#') === false) {
                    continue;
                }

                $content = $this->normalizeDisplayTitle($entry['@content'] ?? null);

                if (!empty($content) && !in_array($content, ['Rows', 'Columns'], true)) {
                    return strtoupper($tableCode) . ' - ' . $content;
                }
            }
        }

        return strtoupper($tableCode);
    }

    private function preferredLabelRoles(): array
    {
        return [
            'http://www.xbrl.org/2008/role/verboseLabel',
            'http://www.xbrl.org/2003/role/verboseLabel',
            'http://www.xbrl.org/2008/role/label',
            'http://www.xbrl.org/2003/role/label',
        ];
    }

    private function normalizeDisplayTitle($label): ?string
    {
        if (!is_string($label)) {
            return null;
        }

        $label = trim((string) preg_replace('/\s+/', ' ', $label));

        if ($label === '') {
            return null;
        }

        return preg_replace('/^[A-Z]_[0-9]{2}\.[0-9]{2}(?:\.[A-Za-z0-9]+)?\s*:\s*/', '', $label) ?: $label;
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
                'Available groups' => json_encode($group),
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

        $groups = [];

        if (!empty($module['pre']) && !empty($module['elements'])) {
            $parent = ArrayManipulation::searchHref($module['pre'], key($module['elements']));

            if (!empty($parent)) {
                $groups = ModuleTree::getGroupTable($module['pre'], key($parent));
            }
        }

        if (empty($groups)) {
            $fallbackGroups = $this->getJsonFallbackGroups($modulePath);

            if (!empty($fallbackGroups)) {
                return response()->json($fallbackGroups);
            }
        }

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
