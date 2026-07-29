<?php

namespace Tests\Feature;

use App\Models\DeviceMigration;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DiamondLiveSyncTest extends TestCase
{
    use RefreshDatabase;

    private function diamondUser(): User
    {
        $package = Package::create([
            'name' => 'Diamond-'.fake()->uuid(),
            'price' => 4999,
            'duration_days' => 365,
            'features' => ['backup', 'auto_backup', 'live_sync'],
            'is_active' => true,
        ]);

        return User::factory()->create([
            'package_id' => $package->id,
            'subscription_expires_at' => now()->addYear(),
        ]);
    }

    private function businessChange(string $syncId, int $localId = 1, string $name = 'Diamond Shop'): array
    {
        return [
            'table' => 'businesses',
            'op' => 'upsert',
            'row' => [
                'id' => $localId,
                'sync_id' => $syncId,
                'user_id' => 0,
                'name' => $name,
                'type' => null,
                'category' => 'business',
                'phone' => null,
                'address' => null,
                'currency' => 'BDT',
                'meta' => null,
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ],
        ];
    }

    public function test_active_diamond_plan_is_required(): void
    {
        $user = User::factory()->create(['subscription_expires_at' => now()->addMonth()]);
        Sanctum::actingAs($user);

        $this->postJson('/api/sync/push', [
            'changes' => [$this->businessChange('business-no-plan')],
        ])->assertForbidden();
    }

    public function test_device_local_integer_ids_do_not_collide_between_accounts(): void
    {
        $first = $this->diamondUser();
        Sanctum::actingAs($first);
        $this->postJson('/api/sync/push', [
            'changes' => [$this->businessChange('business-user-one', 1, 'First Shop')],
        ])->assertOk()->assertJsonPath('data.results.0.accepted', true);

        $second = $this->diamondUser();
        Sanctum::actingAs($second);
        $this->postJson('/api/sync/push', [
            'changes' => [$this->businessChange('business-user-two', 1, 'Second Shop')],
        ])->assertOk()->assertJsonPath('data.results.0.accepted', true);

        $rows = DB::table('businesses')->whereIn('sync_id', ['business-user-one', 'business-user-two'])->get();
        $this->assertCount(2, $rows);
        $this->assertNotSame($rows[0]->id, $rows[1]->id);
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $rows->pluck('user_id')->all());
    }

    public function test_foreign_sync_ids_must_belong_to_the_authenticated_user(): void
    {
        $owner = $this->diamondUser();
        Sanctum::actingAs($owner);
        $this->postJson('/api/sync/push', [
            'changes' => [$this->businessChange('owner-business')],
        ])->assertOk();

        $attacker = $this->diamondUser();
        Sanctum::actingAs($attacker);
        $this->postJson('/api/sync/push', [
            'changes' => [[
                'table' => 'parties',
                'op' => 'upsert',
                'row' => [
                    'id' => 1,
                    'sync_id' => 'attacker-party',
                    'business_id' => 1,
                    'business_sync_id' => 'owner-business',
                    'name' => 'Wrong owner',
                    'type' => 'customer',
                ],
            ]],
        ])
            ->assertOk()
            ->assertJsonPath('data.results.0.accepted', false)
            ->assertJsonPath('data.results.0.reason', 'business_sync_id_not_owned');

        $this->assertDatabaseMissing('parties', ['sync_id' => 'attacker-party']);
    }

    public function test_all_media_tables_materialize_base64_to_public_storage(): void
    {
        Storage::fake('public');
        $user = $this->diamondUser();
        Sanctum::actingAs($user);
        $image = 'data:image/png;base64,'.base64_encode('test-image');

        $response = $this->postJson('/api/sync/push', [
            'changes' => [
                $this->businessChange('media-business'),
                [
                    'table' => 'parties',
                    'op' => 'upsert',
                    'row' => [
                        'id' => 1,
                        'sync_id' => 'media-party',
                        'business_id' => 1,
                        'business_sync_id' => 'media-business',
                        'name' => 'Media Party',
                        'type' => 'customer',
                    ],
                ],
                [
                    'table' => 'transactions',
                    'op' => 'upsert',
                    'row' => [
                        'id' => 1,
                        'sync_id' => 'media-transaction',
                        'business_id' => 1,
                        'business_sync_id' => 'media-business',
                        'party_id' => 1,
                        'party_sync_id' => 'media-party',
                        'type' => 'debit',
                        'amount' => 10,
                        'note' => null,
                        'image_path' => $image,
                        'signature_path' => null,
                        'txn_date' => now()->toDateString(),
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertTrue(collect($response->json('data.results'))->every('accepted'));
        $transaction = DB::table('transactions')->where('sync_id', 'media-transaction')->first();
        $this->assertNotNull($transaction);
        $this->assertStringStartsWith('transaction/', $transaction->image_path);
        Storage::disk('public')->assertExists($transaction->image_path);
    }

    public function test_confirmed_user_can_pull_current_cloud_mirror_and_sync_time_is_recorded(): void
    {
        $user = $this->diamondUser();
        DeviceMigration::create([
            'user_id' => $user->id,
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/sync/push', [
            'changes' => [$this->businessChange('recoverable-business')],
        ])->assertOk();

        $this->getJson('/api/sync/pull')
            ->assertOk()
            ->assertJsonPath('data.snapshot.user_id', $user->id)
            ->assertJsonPath('data.snapshot.counts.businesses', 1)
            ->assertJsonPath('data.snapshot.tables.businesses.0.sync_id', 'recoverable-business');

        $this->assertNotNull(DeviceMigration::where('user_id', $user->id)->first()->last_synced_at);
    }

    public function test_landlord_graph_pushes_and_recovers_with_stable_relations(): void
    {
        $user = $this->diamondUser();
        Sanctum::actingAs($user);
        $today = now()->toDateString();

        $changes = [
            $this->businessChange('landlord-business'),
            [
                'table' => 'parties',
                'op' => 'upsert',
                'row' => [
                    'id' => 1,
                    'sync_id' => 'landlord-tenant',
                    'business_id' => 1,
                    'business_sync_id' => 'landlord-business',
                    'name' => 'Tenant',
                    'type' => 'customer',
                ],
            ],
            [
                'table' => 'landlord_properties',
                'op' => 'upsert',
                'row' => [
                    'id' => 1,
                    'sync_id' => 'landlord-property',
                    'business_id' => 1,
                    'business_sync_id' => 'landlord-business',
                    'name' => 'Main Building',
                    'kind' => 'building',
                ],
            ],
            [
                'table' => 'landlord_units',
                'op' => 'upsert',
                'row' => [
                    'id' => 1,
                    'sync_id' => 'landlord-unit',
                    'business_id' => 1,
                    'business_sync_id' => 'landlord-business',
                    'property_id' => 1,
                    'property_sync_id' => 'landlord-property',
                    'tenant_id' => 1,
                    'tenant_sync_id' => 'landlord-tenant',
                    'name' => 'A-1',
                    'monthly_rent' => 12000,
                    'is_active' => true,
                ],
            ],
            [
                'table' => 'landlord_charges',
                'op' => 'upsert',
                'row' => [
                    'id' => 1,
                    'sync_id' => 'landlord-charge',
                    'business_id' => 1,
                    'business_sync_id' => 'landlord-business',
                    'property_id' => 1,
                    'property_sync_id' => 'landlord-property',
                    'unit_id' => 1,
                    'unit_sync_id' => 'landlord-unit',
                    'party_id' => 1,
                    'party_sync_id' => 'landlord-tenant',
                    'period' => now()->format('Y-m'),
                    'category' => 'rent',
                    'amount' => 12000,
                ],
            ],
            [
                'table' => 'landlord_payments',
                'op' => 'upsert',
                'row' => [
                    'id' => 1,
                    'sync_id' => 'landlord-payment',
                    'business_id' => 1,
                    'business_sync_id' => 'landlord-business',
                    'party_id' => 1,
                    'party_sync_id' => 'landlord-tenant',
                    'unit_id' => 1,
                    'unit_sync_id' => 'landlord-unit',
                    'cashbook_entry_id' => null,
                    'amount' => 12000,
                    'paid_at' => $today,
                ],
            ],
            [
                'table' => 'landlord_projects',
                'op' => 'upsert',
                'row' => [
                    'id' => 1,
                    'sync_id' => 'landlord-project',
                    'business_id' => 1,
                    'business_sync_id' => 'landlord-business',
                    'property_id' => 1,
                    'property_sync_id' => 'landlord-property',
                    'name' => 'Roof Repair',
                    'budget' => 50000,
                    'status' => 'active',
                ],
            ],
            [
                'table' => 'landlord_payment_allocations',
                'op' => 'upsert',
                'row' => [
                    'id' => 1,
                    'sync_id' => 'landlord-allocation',
                    'payment_id' => 1,
                    'payment_sync_id' => 'landlord-payment',
                    'charge_id' => 1,
                    'charge_sync_id' => 'landlord-charge',
                    'amount' => 12000,
                ],
            ],
            [
                'table' => 'landlord_expenses',
                'op' => 'upsert',
                'row' => [
                    'id' => 1,
                    'sync_id' => 'landlord-expense',
                    'business_id' => 1,
                    'business_sync_id' => 'landlord-business',
                    'property_id' => 1,
                    'property_sync_id' => 'landlord-property',
                    'unit_id' => null,
                    'project_id' => 1,
                    'project_sync_id' => 'landlord-project',
                    'cashbook_entry_id' => null,
                    'category' => 'repair',
                    'amount' => 10000,
                    'paid_at' => $today,
                ],
            ],
            [
                'table' => 'landlord_recoveries',
                'op' => 'upsert',
                'row' => [
                    'id' => 1,
                    'sync_id' => 'landlord-recovery',
                    'business_id' => 1,
                    'business_sync_id' => 'landlord-business',
                    'project_id' => 1,
                    'project_sync_id' => 'landlord-project',
                    'payment_id' => 1,
                    'payment_sync_id' => 'landlord-payment',
                    'amount' => 1000,
                    'recovered_at' => $today,
                ],
            ],
        ];

        $response = $this->postJson('/api/sync/push', ['changes' => $changes])->assertOk();
        $this->assertTrue(collect($response->json('data.results'))->every('accepted'));

        $this->getJson('/api/sync/pull')
            ->assertOk()
            ->assertJsonPath('data.snapshot.counts.landlord_properties', 1)
            ->assertJsonPath('data.snapshot.counts.landlord_units', 1)
            ->assertJsonPath('data.snapshot.counts.landlord_payment_allocations', 1)
            ->assertJsonPath('data.snapshot.counts.landlord_recoveries', 1);
    }
}
