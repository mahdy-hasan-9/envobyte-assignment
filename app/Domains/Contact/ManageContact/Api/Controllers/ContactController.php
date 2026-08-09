<?php

namespace App\Domains\Contact\ManageContact\Api\Controllers;


use App\Domains\Contact\ManageContact\Services\ImportContacts;
use App\Http\Controllers\Controller;
use App\Models\ImportJobs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ContactController extends Controller
{
    public function import(Request $request)
    {

        DB::table('import_jobs')->delete();
        DB::table('import_errors')->delete();
        DB::table('contacts')->where('pronoun_id', '!=', null)->delete();


        $validated = $request->validate([
            'vault_id' => ['required', 'string', 'exists:vaults,id'],
            'contacts' => ['required', 'file', 'mimes:csv', 'max:10240'],
        ]);

        Gate::authorize('vault-editor', $validated['vault_id']);
        $request->validate([
            'contacts' => 'required|file|mimes:csv|max:10240',
        ]);
        $data = [
            'account_id' => $request->user()->account_id,
            'author_id'  => $request->user()->id,
            'vault_id'   => $validated['vault_id'],
            'file'       => $request->file('contacts'),
        ];
        $importJob = (new ImportContacts)->execute($data);
        return response()->json([
            'data' => [
                'id'             => $importJob->id,
                'filename'       => $importJob->filename,
                'total_rows'     => $importJob->total_rows,
                'processed_rows' => $importJob->processed_rows ?? 0,
                'failed_rows'    => $importJob->failed_rows ?? 0,
                'status'         => $importJob->status,
                'created_at'     => $importJob->created_at->toISOString(),
            ],
        ], 201);
    }


    public function show(string $importJobId)
    {

        $importJob = ImportJobs::query()
            ->where('account_id', Auth::user()->account_id)
            ->where('id', $importJobId)
            ->firstOrFail();

        Gate::authorize('vault-editor', $importJob->vault_id);


        return response()->json([
            'data' => [
                'id'             => $importJob->id,
                'filename'       => $importJob->filename,
                'total_rows'     => (int) $importJob->total_rows,
                'processed_rows' => (int) ($importJob->processed_rows ?? 0),
                'failed_rows'    => (int) ($importJob->failed_rows ?? 0),
                'status'         => $importJob->status,
                'progress_pct'   => $importJob->progress_pct, // Model Accessor
                'started_at'     => $importJob->started_at?->toISOString(),
                'completed_at'   => $importJob->completed_at?->toISOString(),
            ],
        ], 200);
    }
}
