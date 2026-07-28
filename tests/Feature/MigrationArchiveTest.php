<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MigrationArchiveTest extends TestCase
{
    use RefreshDatabase;

    private function seedLedger(User $user): void
    {
        $business = $user->businesses()->create(['name' => 'Test Shop']);
        $party = $business->parties()->create(['name' => 'Karim', 'type' => 'customer']);
        $party->transactions()->create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'type' => 'debit',
            'amount' => 500,
            'txn_date' => now()->toDateString(),
        ]);
    }

    public function test_confirm_archives_and_wipes_server_ledger(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->seedLedger($user);
        Sanctum::actingAs($user);

        $export = $this->postJson('/api/migration/export')->assertOk()->json('data');

        $this->postJson('/api/migration/confirm', [
            'migration_id' => $export['migration_id'],
            'checksum' => $export['checksum'],
        ])->assertOk()->assertJsonPath('data.state', 'done');

        $this->assertSame(0, $user->businesses()->count());
        $this->assertNotNull($user->fresh()->migrated_at);

        $migration = \App\Models\DeviceMigration::where('user_id', $user->id)->first();
        $this->assertSame('confirmed', $migration->status);
        $this->assertNotNull($migration->archive_path);
        $this->assertNotNull($migration->archived_at);
        Storage::disk('local')->assertExists($migration->archive_path);

        $archived = json_decode(Storage::disk('local')->get($migration->archive_path), true);
        $this->assertCount(1, $archived['businesses']);
        $this->assertCount(1, $archived['businesses'][0]['parties']);
        $this->assertCount(1, $archived['businesses'][0]['parties'][0]['transactions']);
    }

    public function test_confirm_is_idempotent_and_does_not_rewipe(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->seedLedger($user);
        Sanctum::actingAs($user);

        $export = $this->postJson('/api/migration/export')->assertOk()->json('data');
        $confirmPayload = ['migration_id' => $export['migration_id'], 'checksum' => $export['checksum']];

        $this->postJson('/api/migration/confirm', $confirmPayload)->assertOk();
        $firstArchivedAt = \App\Models\DeviceMigration::where('user_id', $user->id)->first()->archived_at;

        // A retried confirm call (e.g. a dropped response the app retries) must not error
        // or attempt a second archive/wipe against the now-empty ledger.
        $this->postJson('/api/migration/confirm', $confirmPayload)->assertOk();
        $secondArchivedAt = \App\Models\DeviceMigration::where('user_id', $user->id)->first()->archived_at;

        $this->assertTrue($firstArchivedAt->equalTo($secondArchivedAt));
    }

    public function test_restore_archive_requires_active_yearly_backup_feature(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->seedLedger($user);
        Sanctum::actingAs($user);

        $export = $this->postJson('/api/migration/export')->assertOk()->json('data');
        $this->postJson('/api/migration/confirm', [
            'migration_id' => $export['migration_id'],
            'checksum' => $export['checksum'],
        ])->assertOk();

        // No package at all → forbidden.
        $this->postJson('/api/migration/restore-archive')->assertStatus(403);

        // Monthly-style package (no 'backup' feature) → still forbidden.
        // Re-acting-as a freshly-fetched user each time mirrors production, where
        // every request resolves the user (and its relations) fresh from the DB —
        // unlike this test's shared in-memory $user, which would otherwise cache
        // a stale `package` relation across these simulated requests.
        $monthly = Package::create([
            'name' => 'Monthly-Test', 'price' => 500, 'duration_days' => 30,
            'features' => ['products'],
        ]);
        $user->update(['package_id' => $monthly->id, 'subscription_expires_at' => now()->addDays(30)]);
        Sanctum::actingAs($user->fresh());
        $this->postJson('/api/migration/restore-archive')->assertStatus(403);

        // Yearly-style package but EXPIRED → still forbidden.
        $yearly = Package::create([
            'name' => 'Yearly-Test', 'price' => 5000, 'duration_days' => 365,
            'features' => ['products', 'backup', 'auto_backup'],
        ]);
        $user->update(['package_id' => $yearly->id, 'subscription_expires_at' => now()->subDay()]);
        Sanctum::actingAs($user->fresh());
        $this->postJson('/api/migration/restore-archive')->assertStatus(403);

        // Active Yearly → restores successfully.
        $user->update(['subscription_expires_at' => now()->addYear()]);
        Sanctum::actingAs($user->fresh());
        $this->postJson('/api/migration/restore-archive')->assertOk();

        $this->assertSame(1, $user->businesses()->count());
        $this->assertSame('Karim', $user->businesses()->first()->parties()->first()->name);
    }

    public function test_restore_archive_rearms_migration_for_a_new_device(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->seedLedger($user);
        Sanctum::actingAs($user);

        $export = $this->postJson('/api/migration/export')->assertOk()->json('data');
        $this->postJson('/api/migration/confirm', [
            'migration_id' => $export['migration_id'],
            'checksum' => $export['checksum'],
        ])->assertOk();

        $yearly = Package::create([
            'name' => 'Yearly-Test', 'price' => 5000, 'duration_days' => 365,
            'features' => ['products', 'backup', 'auto_backup'],
        ]);
        $user->update(['package_id' => $yearly->id, 'subscription_expires_at' => now()->addYear()]);
        Sanctum::actingAs($user->fresh());
        $this->postJson('/api/migration/restore-archive')->assertOk();

        // A brand-new device (no local `_meta.migrated_user` marker) now checks in —
        // it must see 'required', not the stale 'done' from before the restore.
        $this->getJson('/api/migration/status')->assertOk()->assertJsonPath('data.state', 'required');

        // That new device runs the normal export→confirm dance...
        $secondExport = $this->postJson('/api/migration/export')->assertOk()->json('data');
        $this->postJson('/api/migration/confirm', [
            'migration_id' => $secondExport['migration_id'],
            'checksum' => $secondExport['checksum'],
        ])->assertOk();

        // ...and the server re-archives + re-wipes exactly as on first migration.
        $this->assertSame(0, $user->businesses()->count());
        $migration = \App\Models\DeviceMigration::where('user_id', $user->id)->first();
        $this->assertSame('confirmed', $migration->status);
        $this->assertNotNull($migration->archive_path);
        Storage::disk('local')->assertExists($migration->archive_path);
        $this->getJson('/api/migration/status')->assertOk()->assertJsonPath('data.state', 'done');
    }
}
