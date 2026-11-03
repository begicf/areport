<?php

namespace Tests\Feature;

use App\Model\Entity;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EntityTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email): User
    {
        return User::query()->create(['name' => $email, 'email' => $email, 'password' => Hash::make('secret')]);
    }

    public function test_entities_are_added_by_lei_and_the_first_is_the_default(): void
    {
        $alice = $this->actingAs($this->user('alice@example.com'));

        $this->post('/entities', ['lei' => '5493001KJTIIGC8Y1R12', 'name' => 'Example Bank', 'country' => 'fr'])->assertSessionHas('success');
        $this->post('/entities', ['lei' => 'HWUPKR0MPOU8FGXBT394', 'name' => 'Apple Inc.'])->assertSessionHas('success');
        $this->post('/entities', ['lei' => '5493001KJTIIGC8Y1R13', 'name' => 'Typo'])->assertSessionHasErrors('lei');

        $first = Entity::query()->where('lei', '5493001KJTIIGC8Y1R12')->sole();
        $this->assertTrue($first->is_default);
        $this->assertSame('FR', $first->country);

        $second = Entity::query()->where('lei', 'HWUPKR0MPOU8FGXBT394')->sole();
        $this->patch('/entities/'.$second->id.'/default')->assertSessionHas('success');
        $this->assertSame($second->id, Entity::current()->id);

        $this->get('/entities')->assertOk()->assertSee('Apple Inc.')->assertSee('5493001KJTIIGC8Y1R12');
        $this->delete('/entities/'.$first->id)->assertSessionHas('success');
        $this->assertSame(1, Entity::query()->count());
    }

    public function test_users_only_see_and_change_their_own_entities(): void
    {
        $bob = $this->user('bob@example.com');
        $bobs = Entity::query()->create(['user_id' => $bob->id, 'lei' => '5493001KJTIIGC8Y1R12', 'name' => 'Bob Bank']);

        $this->actingAs($this->user('alice@example.com'));
        $this->get('/entities')->assertOk()->assertDontSee('Bob Bank');
        $this->delete('/entities/'.$bobs->id)->assertNotFound();
        $this->patch('/entities/'.$bobs->id.'/default')->assertNotFound();
        $this->patch('/entities/'.$bobs->id, ['name' => 'Mine now'])->assertNotFound();
        $this->assertNotNull(Entity::query()->withoutGlobalScopes()->find($bobs->id));
    }

    public function test_the_legal_name_and_country_come_from_gleif(): void
    {
        Http::fake(['api.gleif.org/*' => Http::response(['data' => ['attributes' => [
            'entity' => ['legalName' => ['name' => 'EXAMPLE BANK SA'], 'legalAddress' => ['country' => 'FR']],
            'registration' => ['status' => 'ISSUED'],
        ]]])]);
        $this->actingAs($this->user('alice@example.com'));

        $this->postJson('/entities/lei-lookup', ['lei' => '5493001kjtiigc8y1r12'])
            ->assertOk()->assertJson(['ok' => true, 'lei' => '5493001KJTIIGC8Y1R12', 'name' => 'EXAMPLE BANK SA', 'country' => 'FR', 'status' => 'ISSUED']);
        $this->postJson('/entities/lei-lookup', ['lei' => '5493001KJTIIGC8Y1R13'])->assertStatus(422);
    }

    public function test_an_entity_is_edited_not_overwritten_and_kept_while_reports_use_it(): void
    {
        $alice = $this->user('alice@example.com');
        $this->actingAs($alice);
        $this->post('/entities', ['lei' => '5493001KJTIIGC8Y1R12', 'name' => 'Example Bank', 'country' => 'FR']);
        $entity = Entity::query()->sole();

        // Adding the same LEI again does not rewrite it.
        $this->post('/entities', ['lei' => '5493001kjtiigc8y1r12', 'name' => 'Other name'])->assertSessionHasErrors('lei');
        $this->assertSame(['Example Bank', 'FR'], [$entity->fresh()->name, $entity->fresh()->country]);

        // Edit changes the name and country.
        $this->patch('/entities/'.$entity->id, ['name' => 'Example Bank SA', 'country' => 'be'])->assertSessionHas('success');
        $this->assertSame(['Example Bank SA', 'BE'], [$entity->fresh()->name, $entity->fresh()->country]);
        $this->patch('/entities/'.$entity->id, ['name' => '', 'country' => 'BEL'])->assertSessionHasErrors(['name', 'country'], null, 'edit'.$entity->id);

        // A report uses it: not removed.
        $tax = \App\Model\Taxonomy::query()->create(['name' => 'EBA', 'original_name' => 'eba', 'path' => 'eba', 'folder' => 'eba']);
        \App\Model\FactModule::withoutGlobalScopes()->create(['user_id' => $alice->id, 'taxonomy_id' => $tax->id, 'entity_id' => $entity->id,
            'period' => '2026-12-31', 'module_name' => 'Own Funds', 'module_path' => 'x/mod/corep_of.xsd', 'groups' => '{}']);
        $this->delete('/entities/'.$entity->id)->assertSessionHas('warning');
        $this->assertNotNull($entity->fresh());
    }
}
