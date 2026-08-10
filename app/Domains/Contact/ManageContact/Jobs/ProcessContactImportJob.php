<?php

namespace App\Domains\Contact\ManageContact\Jobs;

use App\Domains\Contact\ManageContact\Services\CreateContact;
use App\Models\ImportError;
use App\Models\ImportJobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessContactImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;
    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(public ImportJobs $importJob) {}

    public function handle(CreateContact $createContact): void
    {
        $this->importJob->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $filePath = storage_path('app/' . $this->importJob->file_path);

        if (! file_exists($filePath)) {
            $this->importJob->update([
                'status' => 'failed',
                'failure_message' => 'Import file not found in storage.',
            ]);
            return;
        }

        try {
            $handle = fopen($filePath, 'r');
            $header = fgetcsv($handle);

            if (! $header) {
                $this->importJob->update([
                    'status' => 'failed',
                    'failure_message' => 'CSV file is empty or invalid.',
                ]);
                fclose($handle);
                return;
            }

            $header = array_map('trim', $header);
            $rowNumber = 0;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($rowNumber === 1 || $rowNumber % 20 === 0) {
                    $currentStatus = ImportJobs::where('id', $this->importJob->id)->value('status');

                    if (in_array($currentStatus, ['cancelling', 'cancelled'])) {
                        fclose($handle);
                        $this->importJob->update([
                            'status'       => 'cancelled',
                            'completed_at' => now(),
                        ]);
                        return; 
                    }
                }

                if ($rowNumber <= ($this->importJob->processed_rows + $this->importJob->failed_rows)) {
                    continue;
                }

                $rowData = array_combine($header, array_pad($row, count($header), null));

                DB::transaction(function () use ($rowData, $createContact) {
                    $contactPayload = [
                        'account_id'  => $this->importJob->account_id,
                        'author_id'   => $this->importJob->user_id,
                        'vault_id'    => $rowData['vault_id'] ?? $this->importJob->vault_id,
                        'first_name'  => $rowData['first_name'] ?? null,
                        'last_name'   => $rowData['last_name'] ?? null,
                        'middle_name' => $rowData['middle_name'] ?? null,
                        'nickname'    => $rowData['nickname'] ?? null,
                        'maiden_name' => $rowData['maiden_name'] ?? null,
                        'prefix'      => $rowData['prefix'] ?? null,
                        'suffix'      => $rowData['suffix'] ?? null,
                        'gender_id'   => ! empty($rowData['gender_id']) ? (int) $rowData['gender_id'] : null,
                        'pronoun_id'  => ! empty($rowData['pronoun_id']) ? (int) $rowData['pronoun_id'] : null,
                        'template_id' => ! empty($rowData['template_id']) ? (int) $rowData['template_id'] : null,
                        'listed'      => true,
                    ];

                    $createContact->execute($contactPayload);
                    $this->importJob->increment('processed_rows');
                });
            }

            fclose($handle);

            $this->importJob->refresh();

            $finalStatus = ($this->importJob->processed_rows === 0 && $this->importJob->failed_rows > 0)
                ? 'failed'
                : 'completed';

            $this->importJob->update([
                'status'       => $finalStatus,
                'completed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            if (isset($rowNumber) && $rowNumber > ($this->importJob->processed_rows + $this->importJob->failed_rows)) {
                ImportError::create([
                    'import_jobs_id' => $this->importJob->id,
                    'row_number'    => $rowNumber,
                    'row_data'      => $rowData ?? [],
                    'error_message' => $exception->getMessage(),
                ]);
                $this->importJob->increment('failed_rows');
            }

            $this->importJob->update([
                'status'          => 'failed',
                'failure_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
