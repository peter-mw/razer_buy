<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use App\Jobs\ProcessBuyJob;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class CreateProductToBuy extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Create Order');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            Actions\Action::make('createAndExecute')
                ->label('Create and Execute')
                ->action('createAndExecute')
                ->color('success'),
        ];
    }

    public function createAndExecute(): void
    {
        $record = $this->form->getState();
        
        // Create the record
        $this->record = $this->handleRecordCreation($record);
        
        // Dispatch the job
        ProcessBuyJob::dispatch($this->record->id, $this->record->quantity);
        
        // Show notification
        Notification::make()
            ->title('Order created and processing started')
            ->body('The purchase order has been created and is being processed in the background')
            ->success()
            ->send();
        
        // Redirect to list
        $this->redirect($this->getResource()::getUrl('index'));
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        
        // Redirect to the edit page where the process button is available
        $this->redirect($this->getResource()::getUrl('edit', ['record' => $record]));
    }
}
