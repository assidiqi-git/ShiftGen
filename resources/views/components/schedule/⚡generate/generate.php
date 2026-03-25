<?php

use App\Exceptions\ScheduleConflictException;
use App\Models\ScheduleSet;
use App\Models\Shift;
use App\Services\ScheduleGeneratorService;
use Carbon\Carbon;
use Livewire\Component;

new class extends Component {
    public string $name = '';

    public string $date_from = '';

    public string $date_to = '';

    public bool $success = false;

    public ?string $errorMsg = null;

    public int $generated = 0;

    public ?int $created_set_id = null;

    public function mount(): void
    {
        $this->date_from = now()->startOfWeek()->toDateString();
        $this->date_to = now()->endOfWeek()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'name' => 'nullable|string|max:120',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ];
    }

    public function generate(): void
    {
        $this->validate();

        $this->success = false;
        $this->errorMsg = null;

        $from = Carbon::parse($this->date_from)->startOfDay();
        $to = Carbon::parse($this->date_to)->endOfDay();

        $days = $from->diffInDays($to) + 1;
        if ($days > 31) {
            $this->addError('date_to', 'Rentang tanggal maksimum adalah 31 hari.');

            return;
        }

        try {
            $shiftCount = Shift::count();

            $name = trim($this->name);
            if ($name === '') {
                $name = 'Jadwal ' . $from->toDateString() . ' – ' . $to->toDateString();
            }

            $set = ScheduleSet::create([
                'name' => $name,
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'status' => 'draft',
            ]);
            app(ScheduleGeneratorService::class)->generate($from, $to, $set->id);
            $this->generated = $days * $shiftCount;
            $this->success = true;
            $this->created_set_id = $set->id;
        } catch (ScheduleConflictException $e) {
            $this->errorMsg = $e->getMessage();
        }
    }

    public function with(): array
    {
        return [];
    }
};
