<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_steps_endpoint_returns_correct_structure()
    {
        $response = $this->getJson('/api/v1/auth/register/steps');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_steps',
                    'steps' => [
                        '*' => [
                            'key',
                            'name',
                            'skippable',
                            'fields',
                            'options'
                        ]
                    ]
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals(18, $data['total_steps']);
        $this->assertEquals('step1', $data['steps'][0]['key']);
        $this->assertEquals('Account For', $data['steps'][0]['name']);
    }

    public function test_step1_creates_user_and_returns_token()
    {
        $response = $this->postJson('/api/v1/auth/register/step1', [
            'on_behalf' => 1,
            'gender' => 2,
            'marriage_timeline' => 'within_6_months',
            'willing_to_work_after_marriage' => 'depends_on_mutual_understanding',
            'expects_spouse_to_work' => 'depends_on_mutual_understanding',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token',
                    'token_type',
                    'expires_at',
                    'user',
                    'registration'
                ]
            ]);

        $this->assertDatabaseHas('users', ['user_type' => 'member']);
        $this->assertDatabaseHas('members', ['verification_status' => 'draft']);
    }

    public function test_step3_accepts_controlled_religion_fields()
    {
        $user = User::factory()->create(['user_type' => 'member']);
        $member = Member::factory()->create(['user_id' => $user->id]);
        
        $token = $user->createToken('test-token');
        
        $response = $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/auth/register/step/3', [
                'religion_id' => 1,
                'mother_tongue' => 1,
                'sect_main_id' => 1,
                'school_of_thought_id' => 1,
                'tradition_id' => 1,
            ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('spiritual_backgrounds', [
            'user_id' => $user->id,
            'religion_id' => 1,
            'sect_main_id' => 1,
            'school_of_thought_id' => 1,
            'tradition_id' => 1,
        ]);
    }

    public function test_step8_accepts_controlled_education_fields()
    {
        $user = User::factory()->create(['user_type' => 'member']);
        $member = Member::factory()->create(['user_id' => $user->id]);
        
        $token = $user->createToken('test-token');
        
        $response = $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/auth/register/step/8', [
                'education_level_id' => 6,
                'degree_id' => 1,
                'field_of_study_id' => 2,
                'institution_id' => 45,
                'graduation_year' => 2024,
                'education_status' => 'completed',
                'expected_graduation_year' => null,
            ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('education', [
            'user_id' => $user->id,
            'education_level_id' => 6,
            'degree_id' => 1,
            'field_of_study_id' => 2,
            'institution_id' => 45,
        ]);
    }

    public function test_step10_accepts_controlled_profession_fields()
    {
        $user = User::factory()->create(['user_type' => 'member']);
        $member = Member::factory()->create(['user_id' => $user->id]);
        
        $token = $user->createToken('test-token');
        
        $response = $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/auth/register/step/10', [
                'annual_income' => 1800000,
                'employment_status' => 'private',
                'profession_category_id' => 2,
                'profession_id' => 20,
                'job_title' => 'Software Engineer',
                'organization' => 'Tech Company',
                'years_of_experience' => 1,
            ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('careers', [
            'user_id' => $user->id,
            'profession_category_id' => 2,
            'profession_id' => 20,
        ]);
    }

    public function test_step5_uses_phone_not_mobile_number()
    {
        $user = User::factory()->create(['user_type' => 'member']);
        $member = Member::factory()->create(['user_id' => $user->id]);
        
        $token = $user->createToken('test-token');
        
        $response = $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/auth/register/step/5', [
                'country_code' => '+92',
                'phone' => '3001234567',
                'email' => 'test@example.com',
            ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '+923001234567',
            'email' => 'test@example.com',
        ]);
    }

    public function test_step18_uses_email_verify_field()
    {
        $user = User::factory()->create(['user_type' => 'member']);
        $member = Member::factory()->create(['user_id' => $user->id]);
        
        $token = $user->createToken('test-token');
        
        $response = $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/auth/register/step/18', [
                'email_verify' => 'newemail@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'newemail@example.com',
        ]);
    }

    public function test_registration_status_returns_correct_mandatory_optional_steps()
    {
        $user = User::factory()->create(['user_type' => 'member']);
        $member = Member::factory()->create(['user_id' => $user->id]);
        
        $token = $user->createToken('test-token');
        
        $response = $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/auth/register/status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_steps',
                    'completed_steps',
                    'next_step',
                    'mandatory_steps',
                    'optional_steps',
                    'registration_completed'
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals(18, $data['total_steps']);
        $this->assertContains('step1', $data['mandatory_steps']);
        $this->assertContains('step17', $data['mandatory_steps']);
        $this->assertContains('step18', $data['mandatory_steps']);
        $this->assertContains('step14', $data['optional_steps']);
        $this->assertContains('step15', $data['optional_steps']);
        $this->assertContains('step16', $data['optional_steps']);
    }
}