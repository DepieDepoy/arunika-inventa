<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Siklus hidup aset:
 *  - asset_disposals            : penghapusan aset (rusak/dimusnahkan, hilang, dijual, dihibahkan, dikembalikan ke vendor)
 *  - asset_location_histories   : riwayat pindah lokasi
 *  - assets.disposed_at         : tanggal aset dihapuskan (status = 2)
 *  - permission asset.move & asset.dispose (diberikan ke role yang sudah punya asset.edit)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->date('disposed_at')->nullable()->after('status');
            $table->index(['company_id', 'status']);
        });

        Schema::create('asset_disposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();

            // scrapped | lost | sold | donated | returned_vendor | other
            $table->string('disposal_type', 30);
            $table->date('disposal_date');

            // Nilai saat penghapusan (snapshot, tidak berubah walau data aset diubah).
            $table->decimal('purchase_price', 18, 2)->nullable();
            $table->decimal('book_value', 18, 2)->nullable();
            $table->decimal('proceeds', 18, 2)->default(0);
            $table->decimal('gain_loss', 18, 2)->nullable();

            $table->foreignId('holder_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('previous_location')->nullable();
            $table->unsignedTinyInteger('previous_status')->default(1);

            $table->text('notes')->nullable();
            $table->string('document_path')->nullable();
            $table->string('document_name')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancel_reason', 500)->nullable();

            $table->timestamps();

            $table->index(['company_id', 'disposal_date']);
            $table->index(['asset_id', 'cancelled_at']);
        });

        Schema::create('asset_location_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('from_location')->nullable();
            $table->string('to_location');
            $table->date('moved_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['asset_id', 'moved_at']);
        });

        $now = now();

        $permissions = [
            'asset.move' => ['Move Asset Location', 'move', 'Memindahkan lokasi aset & melihat riwayat lokasi'],
            'asset.dispose' => ['Dispose Asset', 'dispose', 'Menghapuskan aset (rusak, hilang, dijual, dihibahkan) & membatalkannya'],
        ];

        foreach ($permissions as $code => [$name, $action, $description]) {
            DB::table('permissions')->updateOrInsert(
                ['permission_code' => $code],
                [
                    'permission_name' => $name,
                    'module' => 'asset',
                    'action' => $action,
                    'description' => $description,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // Role yang selama ini boleh mengubah aset otomatis boleh memindahkan lokasi
        // & menghapuskan aset, agar tidak ada yang kehilangan kemampuan setelah update.
        $editId = DB::table('permissions')->where('permission_code', 'asset.edit')->value('id');
        $newIds = DB::table('permissions')->whereIn('permission_code', array_keys($permissions))->pluck('id');

        if ($editId) {
            $roleIds = DB::table('role_permissions')->where('permission_id', $editId)->pluck('role_id')->unique();

            foreach ($roleIds as $roleId) {
                $existing = DB::table('role_permissions')->where('role_id', $roleId)->pluck('permission_id')->all();

                $rows = $newIds->reject(fn ($id) => in_array($id, $existing))
                    ->map(fn ($id) => ['role_id' => $roleId, 'permission_id' => $id, 'created_at' => $now, 'updated_at' => $now])
                    ->values()->all();

                if ($rows) {
                    DB::table('role_permissions')->insert($rows);
                }
            }
        }

        // Teknisi bawaan juga boleh memindahkan lokasi (mis. saat servis/relokasi).
        $moveId = DB::table('permissions')->where('permission_code', 'asset.move')->value('id');
        $technicianRoles = DB::table('roles')->where('role_code', 'teknisi')->whereNull('deleted_at')->pluck('id');

        foreach ($technicianRoles as $roleId) {
            $exists = DB::table('role_permissions')->where('role_id', $roleId)->where('permission_id', $moveId)->exists();

            if (!$exists) {
                DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $moveId, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('permission_code', ['asset.move', 'asset.dispose'])->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        Schema::dropIfExists('asset_location_histories');
        Schema::dropIfExists('asset_disposals');

        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'status']);
            $table->dropColumn('disposed_at');
        });
    }
};
