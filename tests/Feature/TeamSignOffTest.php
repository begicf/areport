<?php

namespace Tests\Feature;

use App\Model\FactAudit;
use App\Model\FactModule;
use App\Model\Taxonomy;
use App\Support\InstanceHooks;
use App\User;
use Begicf\AReportTeams\Mail\SignOffStepMail;
use Begicf\AReportTeams\Models\SignOff;
use Begicf\AReportTeams\Models\TeamMember;
use Begicf\AReportTeams\Support\SignOffFlow;
use Begicf\AReportTeams\Support\Teams;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TeamSignOffTest extends TestCase
{
    use RefreshDatabase;

    private Taxonomy $taxonomy;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(SignOffFlow::class)) {
            $this->markTestSkipped('areport-teams is not installed.');
        }
        Mail::fake();
        $this->taxonomy = Taxonomy::query()->create(['name' => 'EBA', 'original_name' => 'eba', 'path' => 'eba', 'folder' => 'eba']);
    }

    private function user(string $name, bool $verified = true): User
    {
        $user = User::query()->create(['name' => $name, 'email' => strtolower($name).'@bank.example', 'password' => Hash::make('secret')]);
        $user->forceFill(['email_verified_at' => $verified ? now() : null])->save();

        return $user;
    }

    /** A report validated after its last change, without errors. */
    private function report(User $owner): FactModule
    {
        return FactModule::withoutGlobalScopes()->create([
            'user_id' => $owner->id, 'taxonomy_id' => $this->taxonomy->id, 'period' => '2026-09-30',
            'module_name' => 'Own Funds', 'module_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.0/mod/corep_of.xsd', 'groups' => '{}',
            'data_changed_at' => now()->subHour(), 'validated_at' => now()->subMinutes(30), 'validation_summary' => ['failed_errors' => 0, 'failed_warnings' => 0],
        ]);
    }

    /** Alice (admin) starts a team; the others are invited and accept. */
    private function team(array $roles): array
    {
        $admin = $this->user('Alice');
        $this->actingAs($admin)->post('/teams', ['name' => 'Regulatory reporting'])->assertRedirect();
        $users = ['admin' => $admin];
        foreach ($roles as $name => $role) {
            $u = $this->user($name);
            $this->actingAs($admin)->post('/teams/members', ['email' => $u->email, 'role' => $role])->assertSessionHas('success');
            $invitation = Teams::invitationsFor($u)->first();
            $this->actingAs($u)->post('/teams/invitations/'.$invitation->id.'/accept')->assertSessionHas('success');
            $users[$name] = $u;
        }
        Teams::forget();
        SignOffFlow::forget();

        return $users;
    }

    private function act(User $user, FactModule $report, string $action, ?string $comment = null)
    {
        Teams::forget();
        SignOffFlow::forget();

        return $this->actingAs($user)->postJson('/teams/sign-off/'.$report->id.'/'.$action, ['comment' => $comment]);
    }

    public function test_members_see_each_others_reports_only_after_accepting_the_invitation(): void
    {
        $alice = $this->user('Alice');
        $bob = $this->user('Bob');
        $bobsReport = $this->report($bob);
        $this->actingAs($alice)->post('/teams', ['name' => 'Team'])->assertRedirect();

        $this->actingAs($alice)->post('/teams/members', ['email' => 'BOB@bank.example', 'role' => 'preparer'])->assertSessionHas('success');
        Teams::forget();
        $this->assertSame([], array_column($this->actingAs($alice)->postJson('/areport/json')->json('rows'), 'id'), 'an invitation alone shares nothing');

        $invitation = Teams::invitationsFor($bob)->sole();
        $this->actingAs($bob)->post('/teams/invitations/'.$invitation->id.'/accept')->assertSessionHas('success');
        Teams::forget();
        $this->assertSame([$bobsReport->id], array_column($this->actingAs($alice)->postJson('/areport/json')->json('rows'), 'id'));

        // An outsider still sees nothing of the team.
        $carol = $this->user('Carol');
        Teams::forget();
        $this->assertSame([], array_column($this->actingAs($carol)->postJson('/areport/json')->json('rows'), 'id'));
    }

    public function test_an_unverified_address_cannot_take_an_invitation(): void
    {
        $alice = $this->user('Alice');
        $this->actingAs($alice)->post('/teams', ['name' => 'Team']);
        $this->actingAs($alice)->post('/teams/members', ['email' => 'mallory@bank.example', 'role' => 'admin']);
        $mallory = $this->user('Mallory', verified: false);

        $this->assertCount(0, Teams::invitationsFor($mallory));
        $id = \Begicf\AReportTeams\Models\TeamInvitation::query()->value('id');
        $this->actingAs($mallory)->post('/teams/invitations/'.$id.'/accept')->assertNotFound();
        $this->assertNull(Teams::membership($mallory->id));
    }

    public function test_review_return_and_four_eyes_approval_before_filing(): void
    {
        ['admin' => $alice, 'Pat' => $pat, 'Rita' => $rita, 'Aldo' => $aldo] = $this->team(['Pat' => 'preparer', 'Rita' => 'reviewer', 'Aldo' => 'approver']);
        $report = $this->report($pat);

        // Draft: the preparer edits, filing waits for the approval.
        $this->actingAs($pat);
        $this->assertNull(InstanceHooks::lockReason($report, 'edit'));
        $this->postJson('/instance/export-csv', ['instance_id' => $report->id])->assertStatus(423);
        $this->assertContains('locked', array_column($this->postJson('/instance/preflight', ['instance_id' => $report->id])->json('checks'), 'code'));

        // A reviewer neither changes the data nor submits it.
        $this->actingAs($rita)->postJson('/table/save', ['instance_id' => $report->id])->assertStatus(423);
        $this->act($rita, $report, 'submit')->assertStatus(422);

        $this->act($pat, $report, 'submit')->assertOk()->assertJson(['state' => 'in_review']);
        $this->actingAs($pat)->postJson('/table/save', ['instance_id' => $report->id])->assertStatus(423);
        $this->actingAs($pat)->postJson('/instance/settings', ['instance_id' => $report->id])->assertStatus(423);

        // Returned with a reason (required).
        $this->act($rita, $report, 'return')->assertStatus(422);
        $this->act($rita, $report, 'return', 'C 01.00 r010 does not match the ledger')->assertOk()->assertJson(['state' => 'draft']);
        SignOffFlow::forget();
        $this->assertSame('returned', SignOffFlow::badge($report->fresh())['key']);

        // Submitted again; Aldo (approver) approves — not the submitter.
        $this->act($pat, $report, 'submit')->assertOk();
        $this->act($pat, $report, 'approve')->assertStatus(422);
        $this->act($aldo, $report, 'approve')->assertOk()->assertJson(['state' => 'approved']);

        $this->actingAs($pat);
        Teams::forget();
        SignOffFlow::forget();
        $this->assertNull(InstanceHooks::lockReason($report->fresh(), 'export'));
        $this->assertNotContains('locked', array_column($this->postJson('/instance/preflight', ['instance_id' => $report->id])->json('checks'), 'code'));
        $this->postJson('/table/save', ['instance_id' => $report->id])->assertStatus(423);

        // Reopened (reason required): back to draft, filing waits for a new approval.
        $this->act($rita, $report, 'reopen', 'x')->assertStatus(422);
        $this->act($aldo, $report, 'reopen', 'Late adjustment')->assertOk()->assertJson(['state' => 'draft']);
        $this->actingAs($pat)->postJson('/instance/export-csv', ['instance_id' => $report->id])->assertStatus(423);

        $actions = \Begicf\AReportTeams\Models\SignOffEvent::query()->where('fact_module_id', $report->id)->orderBy('id')->pluck('action')->all();
        $this->assertSame(['submit', 'return', 'submit', 'approve', 'reopen'], $actions);
    }

    /** Who got a sign-off e-mail since the last call (sorted names). */
    private function mailedTo(): array
    {
        $names = [];
        foreach (Mail::sent(SignOffStepMail::class) as $mail) {
            foreach ($mail->to as $to) {
                $names[] = $to['name'];
            }
        }
        sort($names);
        Mail::fake();

        return $names;
    }

    public function test_sign_off_steps_are_mailed_to_whom_they_concern(): void
    {
        ['Rita' => $rita, 'Pat' => $pat, 'Aldo' => $aldo, 'admin' => $alice] = $this->team(['Pat' => 'preparer', 'Rita' => 'reviewer', 'Aldo' => 'approver', 'Olga' => 'preparer']);
        $report = $this->report($pat);
        Mail::fake();

        // To review: the reviewers, approvers and admins — not the submitter, not another preparer.
        $this->act($pat, $report, 'submit')->assertOk();
        $this->assertSame(['Aldo', 'Alice', 'Rita'], $this->mailedTo());

        // Returned: the submitter, with the reason.
        $this->act($rita, $report, 'return', 'C 01.00 r010 does not match the ledger')->assertOk();
        Mail::assertSent(SignOffStepMail::class, fn (SignOffStepMail $m) => $m->hasTo($pat->email) && $m->comment === 'C 01.00 r010 does not match the ledger'
            && str_contains($m->render(), 'does not match the ledger') && str_contains($m->url, '/table/open?id='.$report->id));
        $this->assertSame(['Pat'], $this->mailedTo());

        // A member who turned the e-mails off gets none.
        $this->actingAs($rita)->patch('/teams/notifications', [])->assertSessionHas('success');
        $this->act($pat, $report, 'submit')->assertOk();
        $this->assertSame(['Aldo', 'Alice'], $this->mailedTo());

        $this->act($aldo, $report, 'approve')->assertOk();
        $this->assertSame(['Pat'], $this->mailedTo());

        // A comment: whoever took part (Rita is off) and the owner.
        $this->act($alice, $report, 'comment', 'Filed copy archived')->assertOk();
        $this->assertSame(['Aldo', 'Pat'], $this->mailedTo());

        config(['areport-teams.notify_mail' => false]);
        $this->act($aldo, $report, 'reopen', 'Late adjustment')->assertOk();
        $this->assertSame([], $this->mailedTo());
    }

    public function test_whoever_changed_the_data_does_not_approve_it(): void
    {
        ['admin' => $alice, 'Pat' => $pat] = $this->team(['Pat' => 'preparer']);
        $report = $this->report($pat);
        FactAudit::query()->create(['fact_module_id' => $report->id, 'table_path' => 't.xsd', 'cr_code' => 'c010r010', 'new_value' => '5', 'user_id' => $alice->id]);

        $this->act($pat, $report, 'submit')->assertOk();
        $this->act($alice, $report, 'approve')->assertStatus(422)->assertJsonFragment(['message' => 'Four-eyes: you changed this report\'s data, so someone else approves it.']);
        $this->act($alice, $report, 'return', 'Needs a second approver')->assertOk();
    }

    public function test_an_unvalidated_report_cannot_be_submitted(): void
    {
        ['Pat' => $pat] = $this->team(['Pat' => 'preparer']);
        $report = $this->report($pat);
        $report->stamp(['data_changed_at' => now()]);   // changed after the validation

        $response = $this->act($pat, $report, 'submit');
        if (class_exists(\Begicf\AReportDpm\Services\InstanceValidator::class)) {
            $response->assertStatus(422);
            $this->assertSame(0, SignOff::query()->count());
        } else {
            $response->assertOk();   // no validator installed: nothing to require
        }
    }

    public function test_a_report_outside_a_team_is_not_locked(): void
    {
        $solo = $this->user('Solo');
        $report = $this->report($solo);
        $this->actingAs($solo);

        $this->assertNull(InstanceHooks::lockReason($report, 'edit'));
        $this->assertNull(InstanceHooks::lockReason($report, 'export'));
        $this->assertSame([], InstanceHooks::badges($report));
        $this->act($solo, $report, 'submit')->assertStatus(422);
    }

    public function test_the_last_admin_stays_and_roles_change(): void
    {
        ['admin' => $alice, 'Pat' => $pat] = $this->team(['Pat' => 'preparer']);
        $own = TeamMember::query()->where('user_id', $alice->id)->sole();
        $patMember = TeamMember::query()->where('user_id', $pat->id)->sole();

        $this->actingAs($alice)->patch('/teams/members/'.$own->id, ['role' => 'preparer'])->assertSessionHas('warning');
        $this->actingAs($pat)->patch('/teams/members/'.$patMember->id, ['role' => 'admin'])->assertForbidden();
        $this->actingAs($alice)->patch('/teams/members/'.$patMember->id, ['role' => 'approver'])->assertSessionHas('success');
        $this->assertSame('approver', $patMember->fresh()->role);

        $this->actingAs($pat)->delete('/teams/members/'.$patMember->id)->assertRedirect();
        $this->assertNull(TeamMember::query()->find($patMember->id));
    }
}
