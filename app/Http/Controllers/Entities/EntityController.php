<?php

namespace App\Http\Controllers\Entities;

use App\Http\Controllers\Controller;
use App\Model\Entity;
use AReportDpmXBRL\Library\Lei;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/** The reporting entities: added by LEI, with the legal name and country from GLEIF. */
class EntityController extends Controller
{
    public function index()
    {
        return view('entities.index', ['entities' => Entity::choices()]);
    }

    /** Resolve a LEI's legal name and country from the public GLEIF register. */
    public function lookup(Request $request)
    {
        $lei = strtoupper(trim((string) $request->get('lei')));
        if (! preg_match('/^[A-Z0-9]{20}$/', $lei)) {
            return response()->json(['ok' => false, 'error' => 'Enter a valid 20-character LEI.'], 422);
        }
        if (! Lei::isValid($lei)) {
            return response()->json(['ok' => false, 'error' => 'This LEI fails the ISO 17442 check digits — check for a typo.'], 422);
        }

        try {
            $resp = Http::timeout(8)->acceptJson()->get("https://api.gleif.org/api/v1/lei-records/{$lei}");
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => 'Could not reach the GLEIF register.'], 502);
        }

        if ($resp->status() === 404) {
            return response()->json(['ok' => false, 'error' => 'LEI not found in the GLEIF register.'], 404);
        }
        if (! $resp->ok()) {
            return response()->json(['ok' => false, 'error' => 'GLEIF lookup failed.'], 502);
        }

        $name = data_get($resp->json(), 'data.attributes.entity.legalName.name');
        if (! $name) {
            return response()->json(['ok' => false, 'error' => 'No legal name returned for this LEI.'], 422);
        }

        return response()->json([
            'ok' => true,
            'lei' => $lei,
            'name' => $name,
            'country' => data_get($resp->json(), 'data.attributes.entity.legalAddress.country'),
            // ISSUED is current; LAPSED / RETIRED etc. mean the LEI is not maintained.
            'status' => data_get($resp->json(), 'data.attributes.registration.status'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'lei' => ['required', 'string', 'regex:/^[A-Za-z0-9]{20}$/', function ($attribute, $value, $fail) {
                if (! Lei::isValid($value)) {
                    $fail('The LEI fails the ISO 17442 check digits.');
                }
            }],
            'name' => ['required', 'string', 'max:255'],
            'country' => ['nullable', 'regex:/^[A-Za-z]{2}$/'],
        ]);

        $entity = Entity::query()->updateOrCreate(
            ['lei' => strtoupper($data['lei'])],
            ['name' => $data['name'], 'country' => ! empty($data['country']) ? strtoupper($data['country']) : null]
        );
        if (Entity::query()->count() === 1) {
            $entity->makeDefault();
        }

        return back()->with('success', "Entity '{$data['name']}' saved.");
    }

    public function setDefault(int $entity)
    {
        $record = Entity::query()->findOrFail($entity);
        $record->makeDefault();

        return back()->with('success', "'{$record->name}' is now the default entity.");
    }

    public function destroy(int $entity)
    {
        $record = Entity::query()->findOrFail($entity);
        $record->delete();

        return back()->with('success', 'Entity removed.');
    }
}
