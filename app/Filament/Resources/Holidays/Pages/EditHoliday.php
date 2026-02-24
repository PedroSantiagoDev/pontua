<?php

namespace App\Filament\Resources\Holidays\Pages;

use App\Enums\HolidayScope;
use App\Filament\Resources\Holidays\HolidayResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHoliday extends EditRecord
{
    protected static string $resource = HolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['employeeHolidays'] = $this->record->employees
            ->map(fn ($employee) => [
                'employee_id' => $employee->id,
                'reason' => $employee->pivot->reason,
            ])
            ->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        $this->syncEmployeeHolidays();
    }

    private function syncEmployeeHolidays(): void
    {
        if ($this->record->scope !== HolidayScope::Partial) {
            $this->record->employees()->detach();

            return;
        }

        $items = $this->data['employeeHolidays'] ?? [];
        $syncData = [];

        foreach ($items as $item) {
            if (! empty($item['employee_id']) && ! empty($item['reason'])) {
                $syncData[$item['employee_id']] = ['reason' => $item['reason']];
            }
        }

        $this->record->employees()->sync($syncData);
    }
}
