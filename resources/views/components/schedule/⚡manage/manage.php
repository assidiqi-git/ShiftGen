<?php

use App\Models\ScheduleSet;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public ?int $deletingId = null;

    public bool $showGenerateModal = false;

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            ScheduleSet::findOrFail($this->deletingId)->delete();
            $this->toast('Jadwal berhasil dihapus.', true);
        }
        $this->deletingId = null;
    }

    private function toast(string $message, bool $success = true): void
    {
        $this->dispatch('toast-show', message: $message, type: $success ? 'success' : 'error');
    }

    public function with(): array
    {
        $sets = ScheduleSet::query()
            ->withCount('schedules')
            ->orderByDesc('created_at')
            ->paginate(15);

        return compact('sets');
    }
};
