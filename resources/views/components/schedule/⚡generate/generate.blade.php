<div>
    <div class="page-header">
        <div>
            <h1 class="page-title">Generate Jadwal</h1>
            <p class="page-subtitle">Buat jadwal shift otomatis berdasarkan aturan bisnis</p>
        </div>
    </div>

    @if ($success)
        <div class="alert alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            Berhasil membuat <strong>{{ $generated }} slot jadwal</strong> (draft). 
            <a href="{{ route('schedule.preview') }}" wire:navigate class="alert-link">Lihat Preview →</a>
        </div>
    @endif

    @if ($errorMsg)
        <div class="alert alert-danger">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            {{ $errorMsg }}
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form wire:submit="generate" id="generate-form">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="date-from">Tanggal Mulai</label>
                        <input id="date-from" type="date" wire:model="date_from"
                               class="form-input {{ $errors->has('date_from') ? 'input-error' : '' }}" />
                        @error('date_from') <span class="form-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="date-to">Tanggal Selesai</label>
                        <input id="date-to" type="date" wire:model="date_to"
                               class="form-input {{ $errors->has('date_to') ? 'input-error' : '' }}" />
                        @error('date_to') <span class="form-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="info-box">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    <div>
                        <p class="info-title">Aturan Generate Jadwal</p>
                        <ul class="info-list">
                            <li>Setiap shift harian mendapat tepat 1 pegawai</li>
                            <li>Overtime otomatis jika pegawai &lt; shift (memilih pegawai jam kerja terkecil)</li>
                            <li>Pegawai tidak boleh ditugaskan di shift berturutan (harus ada jeda ≥ 1 shift)</li>
                            <li>Jadwal yang ada pada rentang tanggal ini akan <strong>ditimpa</strong></li>
                            <li>Maksimum rentang: 31 hari</li>
                        </ul>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg" id="btn-generate">
                        <span wire:loading.remove wire:target="generate">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" />
                            </svg>
                            Generate Jadwal
                        </span>
                        <span wire:loading wire:target="generate">Sedang membuat jadwal...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>