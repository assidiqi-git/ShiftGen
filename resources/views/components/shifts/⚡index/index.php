<?php

use App\Models\Shift;
use Carbon\Carbon;
use Livewire\Component;

new class extends Component {
    public string $name = '';

    public string $start_time = '';

    public string $end_time = '';

    public float $duration_hours = 0;

    public int $sort_order = 0;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public bool $showForm = false;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'duration_hours' => 'required|numeric|min:0.25|max:24',
            'sort_order' => 'required|integer|min:0',
        ];
    }

    public function updated(string $field): void
    {
        if (in_array($field, ['start_time', 'end_time'])) {
            $this->recalcDuration();
        }
    }

    private function recalcDuration(): void
    {
        if ($this->start_time && $this->end_time) {
            try {
                $start = Carbon::createFromFormat('H:i', $this->start_time);
                $end = Carbon::createFromFormat('H:i', $this->end_time);
                if ($end->lt($start)) {
                    $end->addDay(); // overnight shift
                }
                $this->duration_hours = round($start->diffInMinutes($end) / 60, 2);
                // dd(round($start->diffInMinutes($end) / 60, 2));
            } catch (Throwable) {
                $this->duration_hours = 0;
            }
        }
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'start_time', 'end_time', 'duration_hours', 'sort_order', 'editingId']);
        $this->sort_order = Shift::max('sort_order') + 1;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $shift = Shift::findOrFail($id);
        $this->editingId = $id;
        $this->name = $shift->name;
        $this->start_time = $shift->start_time;
        $this->end_time = $shift->end_time;
        $this->duration_hours = $shift->duration_hours;
        $this->sort_order = $shift->sort_order;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();
        $this->validateTotalHours();

        $data = [
            'name' => $this->name,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'duration_hours' => $this->duration_hours,
            'sort_order' => $this->sort_order,
        ];

        if ($this->editingId) {
            Shift::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast-show', message: 'Shift berhasil diperbarui.', type: 'success');
        } else {
            Shift::create($data);
            $this->dispatch('toast-show', message: 'Shift berhasil ditambahkan.', type: 'success');
        }

        $this->reset(['name', 'start_time', 'end_time', 'duration_hours', 'sort_order', 'editingId', 'showForm']);
    }

    private function validateTotalHours(): void
    {
        $query = Shift::query();
        if ($this->editingId) {
            $query->where('id', '!=', $this->editingId);
        }
        $existingTotal = $query->sum('duration_hours');
        $newTotal = $existingTotal + $this->duration_hours;

        if ($newTotal > 24) {
            $this->addError('duration_hours', "Total durasi semua shift akan melebihi 24 jam (saat ini: {$existingTotal} jam + {$this->duration_hours} jam = {$newTotal} jam).");
            $this->halt();
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Shift::findOrFail($this->deletingId)->delete();
            $this->dispatch('toast-show', message: 'Shift berhasil dihapus.', type: 'success');
        }
        $this->deletingId = null;
    }

    public function cancelForm(): void
    {
        $this->reset(['name', 'start_time', 'end_time', 'duration_hours', 'sort_order', 'editingId', 'showForm']);
        $this->resetValidation();
    }

    public function totalHours(): float
    {
        return (float) Shift::sum('duration_hours');
    }

    public function with(): array
    {
        return [
            'shifts' => Shift::orderBy('sort_order')->get(),
            'totalHours' => $this->totalHours(),
        ];
    }
};
