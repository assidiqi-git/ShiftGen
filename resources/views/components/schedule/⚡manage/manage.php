<?php

use App\Models\ScheduleSet;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
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
            session()->flash('success', 'Jadwal berhasil dihapus.');
        }
        $this->deletingId = null;
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
