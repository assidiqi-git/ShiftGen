<?php

use App\Models\Employee;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $name = '';

    public string $color = '#00ADB5';

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public bool $showForm = false;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'color', 'editingId']);
        $this->color = '#00ADB5';
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $employee = Employee::findOrFail($id);
        $this->editingId = $id;
        $this->name = $employee->name;
        $this->color = $employee->color ?? '#00ADB5';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            Employee::findOrFail($this->editingId)->update([
                'name' => $this->name,
                'color' => $this->color,
            ]);
            session()->flash('success', 'Pegawai berhasil diperbarui.');
        } else {
            Employee::create([
                'name' => $this->name,
                'color' => $this->color,
            ]);
            session()->flash('success', 'Pegawai berhasil ditambahkan.');
        }

        $this->reset(['name', 'color', 'editingId', 'showForm']);
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Employee::findOrFail($this->deletingId)->delete();
            $this->dispatch('toast-show', message: 'Pegawai berhasil dihapus.', type: 'success');
        }
        $this->deletingId = null;
    }

    public function cancelForm(): void
    {
        $this->reset(['name', 'color', 'editingId', 'showForm']);
        $this->resetValidation();
    }

    public function with(): array
    {
        return [
            'employees' => Employee::withCount('schedules')->orderBy('name')->paginate(15),
        ];
    }
};
