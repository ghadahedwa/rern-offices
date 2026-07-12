<?php

namespace App\Livewire\DocumentPhotocopyingServices;

use App\Models\DocumentPhotocopyingService;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('خدمة تصوير المستندات')]
class Create extends Component
{
    public ?DocumentPhotocopyingService $documentPhotocopyingService = null;

    public string $name = '';

    public function mount(?DocumentPhotocopyingService $documentPhotocopyingService = null): void
    {
        abort_unless(auth()->user()?->can('offices.settings'), 403);

        if ($documentPhotocopyingService?->exists) {
            $this->documentPhotocopyingService = $documentPhotocopyingService;
            $this->name                        = $documentPhotocopyingService->name;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->documentPhotocopyingService?->exists) {
            $this->documentPhotocopyingService->update(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.document_photocopying_updated'));
        } else {
            DocumentPhotocopyingService::create(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.document_photocopying_created'));
        }

        $this->redirect(route('document-photocopying-services.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.document-photocopying-services.create');
    }
}
