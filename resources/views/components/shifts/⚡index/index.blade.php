<div>
    <div class="page-header">
        <div>
            <h1 class="page-title">Shift</h1>
            <p class="page-subtitle">Atur shift kerja harian (total maksimum 24 jam)</p>
        </div>
        <button wire:click="openCreate" class="btn btn-primary" id="btn-add-shift">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Shift
        </button>
    </div>

    {{-- Capacity Bar --}}
    <div class="capacity-card">
        <div class="capacity-header">
            <span>Kapasitas Harian</span>
            <span class="capacity-value {{ $totalHours > 20 ? 'text-danger' : 'text-success' }}">
                {{ number_format($totalHours, 2) }} / 24 jam
            </span>
        </div>
        <div class="capacity-bar-track">
            <div class="capacity-bar-fill {{ $totalHours > 20 ? 'danger' : '' }}"
                style="width: {{ min(($totalHours / 24) * 100, 100) }}%"></div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Form Modal --}}
    @if ($showForm)
        <div class="modal-backdrop" id="shift-form-modal">
            <div class="modal-card">
                <div class="modal-header">
                    <h2>{{ $editingId ? 'Edit Shift' : 'Tambah Shift' }}</h2>
                    <button wire:click="cancelForm" class="btn-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form wire:submit="save" class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="shift-name">Nama Shift</label>
                        <input id="shift-name" type="text" wire:model="name"
                            class="form-input {{ $errors->has('name') ? 'input-error' : '' }}"
                            placeholder="Contoh: Shift Pagi" autofocus />
                        @error('name') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="shift-start">Jam Mulai</label>
                            <input id="shift-start" type="time" wire:model.live="start_time"
                                class="form-input {{ $errors->has('start_time') ? 'input-error' : '' }}" />
                            @error('start_time') <span class="form-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="shift-end">Jam Selesai</label>
                            <input id="shift-end" type="time" wire:model.live="end_time"
                                class="form-input {{ $errors->has('end_time') ? 'input-error' : '' }}" />
                            @error('end_time') <span class="form-error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Durasi Otomatis</label>
                        <div class="duration-display">
                            {{ $duration_hours > 0 ? number_format($duration_hours, 2) . ' jam' : '— (atur jam mulai & selesai)' }}
                        </div>
                        @error('duration_hours') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="shift-order">Urutan (sort order)</label>
                        <input id="shift-order" type="number" wire:model="sort_order" min="0"
                            class="form-input {{ $errors->has('sort_order') ? 'input-error' : '' }}" />
                        @error('sort_order') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="modal-footer">
                        <button type="button" wire:click="cancelForm" class="btn btn-secondary">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btn-save-shift">
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
        <div class="modal-backdrop" id="shift-delete-modal">
            <div class="modal-card modal-card-sm">
                <div class="modal-header">
                    <h2>Konfirmasi Hapus</h2>
                </div>
                <div class="modal-body">
                    <p class="text-secondary">Menghapus shift ini juga akan menghapus semua jadwal yang terkait.</p>
                    <div class="modal-footer">
                        <button wire:click="$set('deletingId', null)" class="btn btn-secondary">Batal</button>
                        <button wire:click="delete" class="btn btn-danger" id="btn-confirm-delete-shift">
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
            <table class="table" id="shifts-table">
                <thead>
                    <tr>
                        <th>Urutan</th>
                        <th>Nama Shift</th>
                        <th>Jam Mulai</th>
                        <th>Jam Selesai</th>
                        <th>Durasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($shifts as $shift)
                        {{-- @dd($shift->start_time) --}}
                        <tr wire:key="shift-{{ $shift->id }}">
                            <td class="text-muted">{{ $shift->sort_order }}</td>
                            <td class="font-medium">{{ $shift->name }}</td>
                            <td>{{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }}</td>
                            <td>{{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }}</td>
                            <td><span class="badge badge-purple">{{ number_format($shift->duration_hours, 2) }} jam</span>
                            </td>
                            <td>
                                <div class="action-group">
                                    <button wire:click="openEdit({{ $shift->id }})"
                                        class="btn btn-secondary btn-sm">Edit</button>
                                    <button wire:click="confirmDelete({{ $shift->id }})"
                                        class="btn btn-danger btn-sm">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                <p>Belum ada shift. Tambahkan shift pertama Anda.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>