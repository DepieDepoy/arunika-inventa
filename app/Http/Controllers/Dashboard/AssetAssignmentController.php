<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssetAssignmentController extends Controller
{
    /**
     * ============================================================
     * ASSIGNMENT HISTORY PAGE
     * ============================================================
     */
    public function index($id)
    {
        $authUser = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Keep encrypted ID for route / redirect
        |--------------------------------------------------------------------------
        */

        $encryptedId = $id;

        $id = decryptId($id);

        /*
        |--------------------------------------------------------------------------
        | Get asset
        |--------------------------------------------------------------------------
        */

        $asset = Asset::query()
            ->where('company_id', $authUser->company_id)
            ->with([
                'category',
                'subCategory',
                'responsibleUser',
                'currentAssignment.user',
            ])
            ->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | Get assignment history
        |--------------------------------------------------------------------------
        */

        $assignments = AssetAssignment::query()
            ->where('asset_id', $asset->id)
            ->with([
                'user',
                'creator',
            ])
            ->orderByDesc('start_at')
            ->orderByDesc('id')
            ->get();

        return view(
            'dashboard.asset.assignment.index',
            compact(
                'asset',
                'assignments',
                'encryptedId'
            )
        );
    }


    /**
     * ============================================================
     * AJAX USER SEARCH
     * ============================================================
     *
     * Digunakan oleh search user pada modal Assign / Transfer.
     *
     * Search berdasarkan:
     * - name
     * - NIK
     * - email
     */
    public function users(Request $request, $id)
    {
        $authUser = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Validate asset belongs to current company
        |--------------------------------------------------------------------------
        |
        | Kita tetap validasi asset ID agar endpoint tidak bisa digunakan
        | sembarangan untuk asset milik company lain.
        |
        */

        $assetId = decryptId($id);

        Asset::query()
            ->where('company_id', $authUser->company_id)
            ->findOrFail($assetId);

        /*
        |--------------------------------------------------------------------------
        | Search keyword
        |--------------------------------------------------------------------------
        */

        $search = trim(
            (string) $request->input('q', '')
        );

        $perPage = 10;

        /*
        |--------------------------------------------------------------------------
        | Query users
        |--------------------------------------------------------------------------
        */

        $query = User::query()
            ->where('company_id', $authUser->company_id)
            ->where('status', 1)
            ->when(
                $search !== '',
                function ($query) use ($search) {

                    $query->where(function ($q) use ($search) {

                        $q->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )

                        ->orWhere(
                            'nik',
                            'like',
                            "%{$search}%"
                        )

                        ->orWhere(
                            'email',
                            'like',
                            "%{$search}%"
                        );

                    });

                }
            )
            ->orderBy('name');

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $users = $query->paginate(
            $perPage,
            [
                'id',
                'name',
                'nik',
                'email',
            ],
            'page',
            $request->input('page', 1)
        );

        /*
        |--------------------------------------------------------------------------
        | Format response for AJAX
        |--------------------------------------------------------------------------
        */

        $results = $users->map(
            function ($user) {

                return [
                    'id' => $user->id,
                    'text' => $user->name,
                    'name' => $user->name,
                    'nik' => $user->nik,
                    'email' => $user->email,
                ];

            }
        );

        return response()->json([
            'results' => $results,

            'pagination' => [
                'more' => $users->hasMorePages(),
            ],
        ]);
    }


    /**
     * ============================================================
     * ASSIGN / TRANSFER ASSET
     * ============================================================
     *
     * Jika asset belum punya user:
     *
     *     assign
     *
     * Jika asset sudah punya user:
     *
     *     transfer
     *
     * Contoh:
     *
     * A mendapatkan laptop
     *
     * A | initial/assign | laptop baru
     *
     * Kemudian A transfer ke B
     *
     * A | initial | laptop baru
     * B | transfer | tukeran dengan user B
     *
     * Assignment A ditutup.
     * History A TIDAK diubah reason/notes-nya.
     */
    public function assign(Request $request, $id)
    {
        $authUser = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Keep encrypted ID
        |--------------------------------------------------------------------------
        */

        $encryptedId = $id;

        $id = decryptId($id);

        /*
        |--------------------------------------------------------------------------
        | Get asset
        |--------------------------------------------------------------------------
        */

        $asset = Asset::query()
            ->where('company_id', $authUser->company_id)
            ->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | Validate request
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],

            'start_at' => [
                'required',
                'date',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Make sure target user belongs to same company
        |--------------------------------------------------------------------------
        */

        $targetUser = User::query()
            ->where('company_id', $authUser->company_id)
            ->where('id', $validated['user_id'])
            ->where('status', 1)
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Prevent assigning asset to the same current user
        |--------------------------------------------------------------------------
        */

        if (
            $asset->responsible_user_id &&
            (int) $asset->responsible_user_id === (int) $targetUser->id
        ) {

            return redirect()
                ->route(
                    'assets.assignment.index',
                    $encryptedId
                )
                ->with(
                    'error',
                    'Asset sudah diberikan kepada user tersebut.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Transaction
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use (
                $asset,
                $targetUser,
                $validated,
                $authUser
            ) {

                /*
                |--------------------------------------------------------------------------
                | Determine action
                |--------------------------------------------------------------------------
                */

                $hasCurrentAssignment =
                    AssetAssignment::query()
                        ->where('asset_id', $asset->id)
                        ->whereNull('end_at')
                        ->exists();

                /*
                |--------------------------------------------------------------------------
                | If currently assigned:
                |
                | Close current assignment only.
                |
                | IMPORTANT:
                | Do NOT modify reason / notes.
                |--------------------------------------------------------------------------
                */

                if ($hasCurrentAssignment) {

                    AssetAssignment::query()
                        ->where('asset_id', $asset->id)
                        ->whereNull('end_at')
                        ->update([
                            'end_at' => $validated['start_at'],
                            'updated_at' => now(),
                        ]);

                    $assignmentType = 'transfer';

                } else {

                    $assignmentType = 'assign';
                }

                /*
                |--------------------------------------------------------------------------
                | Create NEW assignment history row
                |--------------------------------------------------------------------------
                */

                AssetAssignment::create([
                    'asset_id' => $asset->id,

                    'user_id' => $targetUser->id,

                    'start_at' => $validated['start_at'],

                    'end_at' => null,

                    'assignment_type' => $assignmentType,

                    'reason' => $validated['reason'] ?? null,

                    'notes' => $validated['notes'] ?? null,

                    'created_by' => $authUser->id,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Update current asset owner
                |--------------------------------------------------------------------------
                */

                $asset->update([
                    'responsible_user_id' => $targetUser->id,
                ]);
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        if (
            $asset->responsible_user_id
        ) {

            return redirect()
                ->route(
                    'assets.assignment.index',
                    $encryptedId
                )
                ->with(
                    'success',
                    'Asset berhasil diberikan / ditransfer kepada user.'
                );
        }

        return redirect()
            ->route(
                'assets.assignment.index',
                $encryptedId
            )
            ->with(
                'success',
                'Asset berhasil diberikan kepada user.'
            );
    }


    /**
     * ============================================================
     * RETURN ASSET
     * ============================================================
     *
     * Contoh:
     *
     * A menerima laptop
     *
     * A | assign   | laptop baru
     *
     * A transfer ke B
     *
     * A | assign   | laptop baru
     * B | transfer | tukeran dengan user B
     *
     * B return ke kantor
     *
     * A | assign   | laptop baru
     * B | transfer | tukeran dengan user B
     * B | return   | dikembalikan ke kantor
     *
     * Tidak ada history yang ditimpa.
     */
    public function returnAsset(Request $request, $id)
    {
        $authUser = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Keep encrypted ID
        |--------------------------------------------------------------------------
        */

        $encryptedId = $id;

        $id = decryptId($id);

        /*
        |--------------------------------------------------------------------------
        | Get asset
        |--------------------------------------------------------------------------
        */

        $asset = Asset::query()
            ->where('company_id', $authUser->company_id)
            ->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | Validate
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'end_at' => [
                'required',
                'date',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Make sure asset actually has a responsible user
        |--------------------------------------------------------------------------
        */

        if (!$asset->responsible_user_id) {

            return redirect()
                ->route(
                    'assets.assignment.index',
                    $encryptedId
                )
                ->with(
                    'error',
                    'Asset saat ini tidak memiliki user yang bertanggung jawab.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Transaction
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use (
                $asset,
                $validated,
                $authUser
            ) {

                /*
                |--------------------------------------------------------------------------
                | Get active assignment
                |--------------------------------------------------------------------------
                */

                $activeAssignment = AssetAssignment::query()
                    ->where('asset_id', $asset->id)
                    ->whereNull('end_at')
                    ->latest('start_at')
                    ->first();

                /*
                |--------------------------------------------------------------------------
                | Safety check
                |--------------------------------------------------------------------------
                */

                if (!$activeAssignment) {

                    /*
                    |--------------------------------------------------------------------------
                    | Asset says it has responsible user but no active
                    | assignment history.
                    |--------------------------------------------------------------------------
                    */

                    $activeAssignment = AssetAssignment::query()
                        ->where('asset_id', $asset->id)
                        ->where(
                            'user_id',
                            $asset->responsible_user_id
                        )
                        ->latest('start_at')
                        ->first();
                }

                /*
                |--------------------------------------------------------------------------
                | If assignment exists
                |--------------------------------------------------------------------------
                */

                if ($activeAssignment) {

                    /*
                    |--------------------------------------------------------------------------
                    | 1. Close current assignment
                    |--------------------------------------------------------------------------
                    |
                    | ONLY end_at is changed.
                    |
                    | Reason / notes from the original assignment stay intact.
                    |
                    */

                    $activeAssignment->update([
                        'end_at' => $validated['end_at'],
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | 2. Create NEW RETURN history row
                    |--------------------------------------------------------------------------
                    |
                    | User remains the last responsible user.
                    |
                    | This row represents the return event.
                    |
                    */

                    AssetAssignment::create([
                        'asset_id' => $asset->id,

                        'user_id' => $activeAssignment->user_id,

                        'start_at' => $validated['end_at'],

                        'end_at' => null,

                        'assignment_type' => 'return',

                        'reason' => $validated['reason'] ?? null,

                        'notes' => $validated['notes'] ?? null,

                        'created_by' => $authUser->id,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | 3. Asset is now unassigned
                |--------------------------------------------------------------------------
                */

                $asset->update([
                    'responsible_user_id' => null,
                ]);
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route(
                'assets.assignment.index',
                $encryptedId
            )
            ->with(
                'success',
                'Asset berhasil dikembalikan dan sekarang berstatus unassigned.'
            );
    }
}