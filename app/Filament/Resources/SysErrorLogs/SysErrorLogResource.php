<?php

namespace App\Filament\Resources\SysErrorLogs;

use App\Filament\Resources\SysErrorLogs\Pages\ListSysErrorLogs;
use App\Filament\Resources\SysErrorLogs\Pages\ViewSysErrorLog;
use App\Filament\Resources\SysErrorLogs\Schemas\SysErrorLogInfolist;
use App\Filament\Resources\SysErrorLogs\Tables\SysErrorLogsTable;
use App\Models\ErrorLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SysErrorLogResource extends Resource
{
    protected static ?string $model = ErrorLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bug-ant';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Log Error';

    protected static ?string $modelLabel = 'Log Error';

    protected static ?string $pluralModelLabel = 'Log Error';

    protected static ?string $slug = 'error-logs';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return SysErrorLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SysErrorLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSysErrorLogs::route('/'),
            'view' => ViewSysErrorLog::route('/{record}'),
        ];
    }
}
