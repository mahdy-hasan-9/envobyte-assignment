<?php

namespace App\Domains\Contact\ManageContact\Services;

use App\Domains\Contact\ManageContact\Jobs\ProcessContactImportJob;
use App\Interfaces\ServiceInterface;
use App\Models\ImportJobs;
use App\Services\BaseService;
use Illuminate\Http\UploadedFile;

class ImportContacts extends BaseService implements ServiceInterface
{
    private array $data;
    private string $filePath;
    private int $totalRows = 0;
    private ImportJobs $importJob;

    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id'  => 'required|uuid|exists:users,id',
            'vault_id'   => 'required|uuid|exists:vaults,id',
            'file'       => 'required|file|mimes:csv,txt|max:10240',
        ];
    }

    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
        ];
    }

    public function execute(array $data): ImportJobs
    {
        $this->data = $data;

        $this->validate();
        $this->storeFileInNonPublicLocation();
        $this->calculateTotalRows();
        $this->createImportJobRecord();
        $this->dispatchQueueJob();

        return $this->importJob;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
    }

    private function storeFileInNonPublicLocation(): void
    {
        $file = $this->data['file'];
        $this->filePath = $file->store('imports', 'local');
    }

    private function calculateTotalRows(): void
    {
        $fullPath = storage_path('app/' . $this->filePath);

        if (($handle = fopen($fullPath, 'r')) !== false) {
            fgetcsv($handle); 
            while (fgetcsv($handle) !== false) {
                $this->totalRows++;
            }
            fclose($handle);
        }
    }

    private function createImportJobRecord(): void
    {
        $file = $this->data['file'];
        $this->importJob = ImportJobs::create([
            'account_id' => $this->data['account_id'],
            'user_id'    => $this->data['author_id'],
            'vault_id'   => $this->data['vault_id'],
            'filename'   => $file->getClientOriginalName(),
            'file_path'  => $this->filePath,
            'total_rows' => $this->totalRows,
            'status'     => 'pending',
        ]);
    }

    private function dispatchQueueJob(): void
    {
        ProcessContactImportJob::dispatch($this->importJob);
    }
}