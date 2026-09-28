<?php

namespace Tests\Feature;

use App\Models\Diploma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VerificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_employer_can_register_and_login(): void
    {
        $registration = $this->postJson('/api/register', [
            'name' => 'Entreprise Test',
            'email' => 'employer@test.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $registration->assertCreated()->assertJsonPath('user.role', 'employer');
        $this->postJson('/api/login', ['email' => 'employer@test.test', 'password' => 'password'])->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_employer_can_submit_and_admin_can_decide_request(): void
    {
        $employer = User::factory()->create(['role' => 'employer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $diploma = Diploma::create(['number' => 'IAI-TEST-001', 'holder_name' => 'Nadia Test', 'program' => 'Informatique', 'graduation_year' => 2026, 'qr_token' => Str::uuid()->toString()]);

        $request = $this->actingAs($employer, 'sanctum')->postJson('/api/requests', ['holder_name' => 'Nadia Test', 'diploma_number' => $diploma->number, 'graduation_year' => 2026, 'program' => 'Informatique'])->assertCreated();
        $requestId = $request->json('id');

        $this->actingAs($admin, 'sanctum')->patchJson("/api/requests/$requestId/decision", ['status' => 'validated'])->assertOk()->assertJsonPath('status', 'validated');
        $this->getJson('/api/diplomas/qr/'.$diploma->qr_token)->assertOk()->assertJsonPath('verified', true);
    }

    public function test_admin_cannot_validate_a_request_when_diploma_number_is_unknown(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employer = User::factory()->create(['role' => 'employer']);
        $request = \App\Models\VerificationRequest::create([
            'reference' => 'REQ-UNKNOWN-001', 'employer_id' => $employer->id, 'holder_name' => 'Candidat Inconnu',
            'diploma_number' => 'IAI-NOT-FOUND', 'graduation_year' => 2026, 'program' => 'DTS', 'status' => 'pending',
        ]);

        $this->actingAs($admin, 'sanctum')->patchJson('/api/requests/'.$request->id.'/decision', ['status' => 'validated'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'DIPLOMA_NUMBER_NOT_FOUND');
        $this->assertDatabaseHas('verification_requests', ['id' => $request->id, 'status' => 'pending']);
    }
}
