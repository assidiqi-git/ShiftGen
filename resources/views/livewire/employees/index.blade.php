<?php

use App\Models\Employee;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $name = '';
    public ?int $editingId = null;
    public ?int $deletingId = null;
    public bool $showForm = false;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'editingId']);
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $employee = Employee::findOrFail($id);
        $this->editingId = $id;
        $this->name = $employee->name;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            Employee::findOrFail($this->editingId)->update(['name' => $this->name]);
            session()->flash('success', 'Pegawai berhasil diperbarui.');
        } else {
            Employee::create(['name' => $this->name]);
            session()->flash('success', 'Pegawai berhasil ditambahkan.');
        }

        $this->reset(['name', 'editingId', 'showForm']);
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
            session()->flash('success', 'Pegawai berhasil dihapus.');
        }
        $this->deletingId = null;
    }

    public function cancelForm(): void
    {
        $this->reset(['name', 'editingId', 'showForm']);
        $this->resetValidation();
    }
}
?>

<div>
    <div class="page-header">
        <div>
            <h1 class="page-title">Pegawai</h1>
            <p class="page-subtitle">Kelola data pegawai yang akan dijadwalkan</p>
        </div>
        <button wire:click="openCreate" class="btn btn-primary" id="btn-add-employee">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Pegawai
        </button>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Form Modal --}}
    @if ($showForm)
        <div class="modal-backdrop" id="employee-form-modal">
            <div class="modal-card" x-data x-trap="true">
                <div class="modal-header">
                    <h2>{{ $editingId ? 'Edit Pegawai' : 'Tambah Pegawai' }}</h2>
                    <button wire:click="cancelForm" class="btn-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form wire:submit="save" class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="employee-name">Nama Pegawai</label>
                        <input
                            id="employee-name"
                            type="text"
                            wire:model="name"
                            class="form-input {{ $errors->has('name') ? 'input-error' : '' }}"
                            placeholder="Contoh: Budi Santoso"
                            autofocus
                        />
                        @error('name') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="modal-footer">
                        <button type="button" wire:click="cancelForm" class="btn btn-secondary">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btn-save-employee">
                            <span wire:loading.remove wire:target="save">Simpan</span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Delete Confirm Modal --}}
    @if ($deletingId)
        <div class="modal-backdrop" id="delete-confirm-modal">
            <div class="modal-card modal-card-sm">
                <div class="modal-header">
                    <h2>Konfirmasi Hapus</h2>
                </div>
                <div class="modal-body">
                    <p class="text-secondary">Apakah Anda yakin ingin menghapus pegawai ini? Semua jadwal terkait juga akan dihapus.</p>
                    <div class="modal-footer">
                        <button wire:click="$set('deletingId', null)" class="btn btn-secondary">Batal</button>
                        <button wire:click="delete" class="btn btn-danger" id="btn-confirm-delete">
                            <span wire:loading.remove wire:target="delete">Hapus</span>
                            <span wire:loading wire:target="delete">Menghapus...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Table --}}
    <div class="card">
        <div class="table-wrap">
            <table class="table" id="employees-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>Total Jadwal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->employees as $employee)
                        <tr wire:key="employee-{{ $employee->id }}">
                            <td class="text-muted">{{ $employee->getKey() }}</td>
                            <td class="font-medium">{{ $employee->name }}</td>
                            <td>
                                <span class="badge badge-blue">{{ $employee->schedules_count }} jadwal</span>
                            </td>
                            <td>
                                <div class="action-group">
                                    <button wire:click="openEdit({{ $employee->id }})" class="btn btn-secondary btn-sm">Edit</button>
                                    <button wire:click="confirmDelete({{ $employee->id }})" class="btn btn-danger btn-sm">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-state">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                </svg>
                                <p>Belum ada pegawai. Tambahkan pegawai pertama Anda.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($this->employees->hasPages())
            <div class="pagination-wrap">
                {{ $this->employees->links() }}
            </div>
        @endif
    </div>
</div>

@computed
protected function employees()
{
    return Employee::withCount('schedules')->orderBy('name')->paginate(15);
}
