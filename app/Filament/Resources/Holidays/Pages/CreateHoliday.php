<?php

namespace App\Filament\Resources\Holidays\Pages;

use App\Enums\HolidayScope;
use App\Filament\Resources\Holidays\HolidayResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHoliday extends CreateRecord
{
    protected static string $resource = HolidayResource::class;

    protected function afterCreate(): void
    {
        $this->syncEmployeeHolidays();
    }

    private function syncEmployeeHolidays(): void
    {
        if ($this->record->scope !== HolidayScope::Partial) {
            return;
        }

        $items = $this->data['employeeHolidays'] ?? [];
        $syncData = [];

        foreach ($items as $item) {
            if (! empty($item['employee_id']) && ! empty($item['reason'])) {
                $syncData[$item['employee_id']] = ['reason' => $item['reason']];
            }
        }

        if (! empty($syncData)) {
            $this->record->employees()->sync($syncData);
        }
    }
}
