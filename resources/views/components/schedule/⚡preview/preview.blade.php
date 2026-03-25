<div x-data="schedulePreview()" x-on:dragover.prevent x-on:drop="handleDrop($event, null, null)">
    <div class="page-header">
        <div>
            <h1 class="page-title">Preview Jadwal</h1>
            <p class="page-subtitle">Lihat, ubah, dan publish jadwal shift pegawai</p>
        </div>
        <div class="header-actions">
            {{-- @if ($hasDrafts)--}}
            {{-- <button wire:click="publishAll" class="btn btn-success" id="btn-publish">--}}
                {{-- <span wire:loading.remove wire:target="publishAll"
                    class="flex items-center justify-between gap-2">--}}
                    {{-- <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" --}}
                        {{-- stroke="currentColor">--}}
                        {{--
                        <path stroke-linecap="round" stroke-linejoin="round" --}} {{--
                            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />--}}
                        {{--
                    </svg>--}}
                    {{-- Publish Semua--}}
                    {{-- </span>--}}
                {{-- <span wire:loading wire:target="publishAll">Publishing...</span>--}}
                {{-- </button>--}}
            {{-- @endif--}}
            <button id="btn-export-png" class="btn btn-secondary" x-on:click="exportPng()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Export PNG
            </button>
        </div>
    </div>

    {{-- Date Range Filter --}}
    <div class="filter-bar">
        <div class="form-row">
            <div class="form-group-inline">
                <label class="form-label" for="preview-from">Dari</label>
                <input id="preview-from" type="date" wire:model.live="date_from" class="form-input form-input-sm"
                    @if($date_from) min="{{ $date_from }}" @endif @if($date_to) max="{{ $date_to }}" @endif />
            </div>
            <div class="form-group-inline">
                <label class="form-label" for="preview-to">Sampai</label>
                <input id="preview-to" type="date" wire:model.live="date_to" class="form-input form-input-sm"
                    @if($date_from) min="{{ $date_from }}" @endif @if($date_to) max="{{ $date_to }}" @endif />
            </div>
        </div>
    </div>

    {{-- Schedule Grid --}}
    <div class="card mb-5">
        <div class="table-wrap" id="schedule-grid">
            <table class="schedule-table" id="schedule-grid-table">
                <thead>
                    <tr>
                        <th class="shift-header-cell">Shift</th>
                        @foreach ($dates as $date)
                            <th class="date-header-cell">
                                <span class="date-day">{{ $date->translatedFormat('D') }}</span>
                                <span class="date-num">{{ $date->format('d') }}</span>
                                <span class="date-month">{{ $date->translatedFormat('M') }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($shifts as $shift)
                        <tr wire:key="shift-row-{{ $shift->id }}">
                            <td class="shift-label-cell">
                                <div class="shift-label">
                                    <span class="shift-name">{{ $shift->name }}</span>
                                    <span
                                        class="shift-time">{{ \Carbon\Carbon::createFromFormat('H:i', $shift->start_time)->format('H:i') }}
                                        –
                                        {{ \Carbon\Carbon::createFromFormat('H:i', $shift->end_time)->format('H:i') }}</span>
                                </div>
                            </td>
                            @foreach ($dates as $date)
                                @php $dateKey = $date->toDateString(); @endphp
                                <td class="schedule-cell" x-on:dragover.prevent="highlightDrop($event)"
                                    x-on:dragleave="unhighlightDrop($event)"
                                    x-on:drop.stop="handleDrop($event, {{ $shift->id }}, '{{ $dateKey }}',{{ $grid[$shift->id][$dateKey]->first()?->id ?? 'null' }})"
                                    data-shift="{{ $shift->id }}" data-date="{{ $dateKey }}">
                                    @foreach ($grid[$shift->id][$dateKey] as $schedule)
                                        @php
                                            $employeeColor = $schedule->employee?->color ?? '#00ADB5';
                                            $hex = ltrim($employeeColor, '#');
                                            if (strlen($hex) !== 6) {
                                                $hex = '00ADB5';
                                                $employeeColor = '#00ADB5';
                                            }
                                            $r = hexdec(substr($hex, 0, 2));
                                            $g = hexdec(substr($hex, 2, 2));
                                            $b = hexdec(substr($hex, 4, 2));
                                            $bgAlpha = $schedule->status === 'published' ? 0.22 : 0.16;
                                            $bg = "rgba({$r}, {$g}, {$b}, {$bgAlpha})";
                                            $border = "rgba({$r}, {$g}, {$b}, 0.28)";
                                        @endphp
                                        <div class="employee-card {{ $schedule->status === 'published' ? 'published' : 'draft' }} {{ $schedule->is_overtime ? 'overtime' : '' }}"
                                            wire:key="schedule-{{ $schedule->id }}" draggable="true"
                                            x-on:dragstart="startDrag($event, {{ $schedule->id }}, '{{ $dateKey }}')"
                                            x-on:dragend="endDrag($event)" x-on:dragover.prevent="highlightDrop($event)"
                                            x-on:dragleave="unhighlightDrop($event)"
                                            x-on:drop.stop="handleDrop($event, {{ $shift->id }}, '{{ $dateKey }}', {{ $schedule->id }})"
                                            id="schedule-card-{{ $schedule->id }}"
                                            style="background: {{ $bg }}; border-color: {{ $border }}; border-left: 6px solid {{ $employeeColor }}; color: var(--text-primary);">
                                            <span class="employee-name">{{ $schedule->employee?->name ?? '—' }}</span>
                                            @if ($schedule->is_overtime)
                                                <span class="overtime-badge">OT</span>
                                            @endif
                                            {{-- @if ($schedule->status === 'published')--}}
                                            {{-- <span class="status-dot published-dot" title="Published"></span>--}}
                                            {{-- @else--}}
                                            {{-- <span class="status-dot draft-dot" title="Draft"></span>--}}
                                            {{-- @endif--}}
                                        </div>
                                    @endforeach
                                </td>
                            @endforeach
                        </tr>
                    @endforeach

                    <tr class="employee-pool-row">
                        <th class="shift-header-cell">Pegawai</th>
                        <td colspan="{{ count($dates) }}" class="employee-name-cell" style="padding: 10px 12px;">
                            <div class="flex justify-between" >
                                @foreach ($employees as $employee)
                                    @php
                                        $employeeColor = $employee->color ?? '#00ADB5';
                                        $hex = ltrim($employeeColor, '#');
                                        if (strlen($hex) !== 6) {
                                            $hex = '00ADB5';
                                            $employeeColor = '#00ADB5';
                                        }
                                        $r = hexdec(substr($hex, 0, 2));
                                        $g = hexdec(substr($hex, 2, 2));
                                        $b = hexdec(substr($hex, 4, 2));
                                        $bg = "rgba({$r}, {$g}, {$b}, 0.12)";
                                        $border = "rgba({$r}, {$g}, {$b}, 0.28)";
                                    @endphp
                                    <div class="employee-card" wire:key="employee-pool-{{ $employee->id }}" draggable="true"
                                        x-on:dragstart="startDragEmployee($event, {{ $employee->id }})"
                                        x-on:dragend="endDrag($event)"
                                        style="background: {{ $bg }}; border-color: {{ $border }}; border-left: 6px solid {{ $employeeColor }}; color: var(--text-primary); min-width: 140px; max-width: 220px; display: inline-flex; align-items: center; justify-content: space-between; padding: 6px 10px;">
                                        <span class="employee-name"
                                            style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $employee->name }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>

                    @if ($shifts->isEmpty())
                        <tr>
                            <td colspan="{{ count($dates) + 1 }}" class="empty-state">
                                Belum ada shift atau jadwal. <a href="{{ route('schedule.generate') }}"
                                    wire:navigate>Generate sekarang →</a>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- Summary Card --}}
    <div class="card">
        <div class="card-body">
            <div class="info-title">Summary Shift Per Pegawai</div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Pegawai</th>
                            <th>Shift Normal</th>
                            <th>Shift Lembur</th>
                            <th>Total Shift</th>
                            <th>Total Jam</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employeeSummaries as $summary)
                            <tr wire:key="employee-summary-{{ $summary['id'] }}">
                                <td class="font-medium">
                                    <span class="badge"
                                        style="background: {{ $summary['color'] ?? '#00ADB5' }}; color: transparent; padding: 0; width: 10px; height: 10px;"></span>
                                    {{ $summary['name'] ?? '—' }}
                                </td>
                                <td>
                                    <span class="badge badge-green">{{ $summary['normal_count'] ?? 0 }} normal</span>
                                </td>
                                <td>
                                    <span class="badge badge-warning">{{ $summary['overtime_count'] ?? 0 }} lembur</span>
                                </td>
                                <td>
                                    <span class="badge badge-blue">{{ $summary['shift_count'] ?? 0 }} shift</span>
                                </td>
                                <td>
                                    <span
                                        class="badge badge-purple">{{ number_format((float) ($summary['total_hours'] ?? 0), 2) }}
                                        jam</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty-state">Belum ada jadwal pada rentang tanggal ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>


</div>
@script
<script>
    Alpine.data('schedulePreview', () => ({
        draggingId: null,
        sourceDate: null,
        draggingEmployeeId: null,

        startDrag(event, scheduleId, date) {
            this.draggingId = scheduleId;
            this.sourceDate = date;
            event.target.classList.add('dragging');
            event.dataTransfer.effectAllowed = 'move';
        },

        startDragEmployee(event, employeeId) {
            this.draggingEmployeeId = employeeId;
            event.target.classList.add('dragging');
            event.dataTransfer.effectAllowed = 'copy';
        },

        endDrag(event) {
            event.target.classList.remove('dragging');
        },

        highlightDrop(event) {
            event.currentTarget.classList.add('drop-target');
        },

        unhighlightDrop(event) {
            event.currentTarget.classList.remove('drop-target');
        },

        handleDrop(event, shiftId, date, targetScheduleId = null) {
            event.currentTarget?.classList?.remove('drop-target');
            if (!shiftId || !date) return;

            if (this.draggingEmployeeId) {
                const empId = this.draggingEmployeeId;
                this.draggingEmployeeId = null;
                this.draggingId = null;
                this.sourceDate = null;
                if (targetScheduleId) {
                    this.$wire.replaceScheduleEmployee(targetScheduleId, empId);
                }
                return;
            }

            if (!this.draggingId) return;

            const draggedId = this.draggingId;

            // Reset state setelah drop
            this.draggingId = null;
            this.sourceDate = null;

            if (targetScheduleId && targetScheduleId !== draggedId) {
                this.$wire.swapSchedule(draggedId, targetScheduleId);
            } else {
                this.$wire.updateSchedule(draggedId, shiftId, date);
            }
        },

        exportPng() {
            if (typeof html2canvas !== 'undefined') {
                const table = document.getElementById('schedule-grid-table');
                if (!table) {
                    alert('Tabel jadwal tidak ditemukan.');
                    return;
                }

                const waitFonts = document.fonts?.ready ?? Promise.resolve();
                waitFonts.then(() => {
                    html2canvas(table, {
                        scale: 2,
                        useCORS: true,
                        onclone: (doc) => {
                            const wrap = doc.getElementById('schedule-grid');
                            if (wrap) {
                                wrap.style.overflow = 'visible';
                                wrap.style.maxWidth = 'none';
                            }

                            doc.querySelectorAll('.shift-header-cell, .shift-label-cell').forEach((el) => {
                                el.style.position = 'static';
                                el.style.left = 'auto';
                            });

                            doc.querySelectorAll('.employee-name').forEach((el) => {
                                el.style.whiteSpace = 'normal';
                                el.style.overflow = 'visible';
                                el.style.textOverflow = 'clip';
                                el.style.wordBreak = 'break-word';
                                el.style.overflowWrap = 'anywhere';
                                el.style.marginBottom = '8px';
                                el.style.marginTop = '0px';

                            });

                            doc.querySelectorAll('.employee-card').forEach((el) => {
                                el.style.alignItems = 'center';
                            });
                        },
                    }).then((canvas) => {
                        const link = document.createElement('a');
                        link.download = 'jadwal-shift ' + Date.now() + '.png';
                        link.href = canvas.toDataURL('image/png');
                        link.click();
                    }).catch(() => {
                        alert('Gagal export PNG. Coba refresh halaman lalu ulangi.');
                    });
                });
            } else {
                alert('Library html2canvas belum dimuat. Pastikan npm run build sudah dijalankan.');
            }
        },
    }));
</script>
@endscript
