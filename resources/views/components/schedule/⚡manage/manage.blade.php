<div>
    <div class="page-header">
        <div>
            <h1 class="page-title">Manajemen Jadwal</h1>
            <p class="page-subtitle">Kelola kumpulan jadwal (draft/published) dan akses cepat ke preview</p>
        </div>
        <button wire:click="$set('showGenerateModal', true)" class="btn btn-primary" id="btn-new-schedule-set">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Buat Jadwal Baru
        </button>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($deletingId)
        <div class="modal-backdrop">
            <div class="modal-card modal-card-sm">
                <div class="modal-header">
                    <h2>Konfirmasi Hapus</h2>
                </div>
                <div class="modal-body">
                    <p class="text-secondary">Hapus jadwal ini beserta semua slotnya?</p>
                    <div class="modal-footer">
                        <button wire:click="$set('deletingId', null)" class="btn btn-secondary">Batal</button>
                        <button wire:click="delete" class="btn btn-danger" id="btn-confirm-delete-set">
                            <span wire:loading.remove wire:target="delete">Hapus</span>
                            <span wire:loading wire:target="delete">Menghapus...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($showGenerateModal)
        <div class="modal-backdrop">
            <div class="modal-card modal-card-xl">
                <div class="modal-header">
                    <h2>Generate Jadwal</h2>
                    <button class="btn btn-secondary" wire:click="$set('showGenerateModal', false)">Tutup</button>
                </div>
                <div class="modal-body">
                    <livewire:schedule.generate />
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="table-wrap">
            <table class="table" id="schedule-sets-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Jadwal</th>
                        <th>Rentang Tanggal</th>
                        <th>Status</th>
                        <th>Total Slot</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sets as $set)
                        <tr wire:key="set-{{ $set->id }}">
                            <td class="text-muted">{{ $set->id }}</td>
                            <td class="font-medium">{{ $set->name }}</td>
                            <td>
                                <span class="badge">{{ \Carbon\Carbon::parse($set->date_from)->format('d M Y') }}</span>
                                —
                                <span class="badge">{{ \Carbon\Carbon::parse($set->date_to)->format('d M Y') }}</span>
                            </td>
                            <td>
                                @if ($set->status === 'published')
                                    <span class="badge badge-green">Published</span>
                                @else
                                    <span class="badge badge-warning">Draft</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-blue">{{ $set->schedules_count }} slot</span>
                            </td>
                            <td class="text-muted">{{ $set->created_at?->diffForHumans() }}</td>
                            <td>
                                <div class="action-group">
                                    <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('schedule.preview', now()->addMinutes(15), ['set' => $set->id]) }}"
                                        wire:navigate class="btn btn-secondary btn-sm">Preview</a>
                                    <button wire:click="confirmDelete({{ $set->id }})"
                                        class="btn btn-danger btn-sm">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-state">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                </svg>
                                <p>Belum ada jadwal. Klik “Buat Jadwal Baru” untuk mulai.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($sets->hasPages())
            <div class="pagination-wrap">
                {{ $sets->links() }}
            </div>
        @endif
    </div>
</div>