<?php

namespace App\Filament\Resources\UserProfiles;

use App\Filament\Resources\UserProfiles\Pages\EditUserProfile;
use App\Filament\Resources\UserProfiles\Pages\ViewUserProfile;
use App\Filament\Resources\UserProfiles\Schemas\UserProfileForm;
use App\Filament\Resources\UserProfiles\Schemas\UserProfileInfolist;
use App\Models\User;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

use function Filament\Support\original_request;

class UserProfileResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Pengguna';

    protected static ?string $navigationLabel = 'Profil Saya';

    protected static ?string $modelLabel = 'Profil';

    protected static ?string $pluralModelLabel = 'Profil';

    protected static ?string $slug = 'profile';

    protected static ?int $navigationSort = 99;

    public static function form(Schema $schema): Schema
    {
        return UserProfileForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserProfileInfolist::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereKey(Auth::id());
    }

    public static function canViewAny(): bool
    {
        return Auth::check();
    }

    public static function canView(Model $record): bool
    {
        return Auth::check() && $record->is(Auth::user());
    }

    public static function canEdit(Model $record): bool
    {
        return static::canView($record);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getNavigationUrl(): string
    {
        return static::getUrl('view', ['record' => Auth::id()]);
    }

    public static function getIndexUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        return static::getUrl('view', ['record' => Auth::id()], $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters);
    }

    public static function getNavigationItems(): array
    {
        if (! Auth::check()) {
            return [];
        }

        return [
            NavigationItem::make(static::getNavigationLabel())
                ->key(static::class)
                ->group(static::getNavigationGroup())
                ->icon(static::getNavigationIcon())
                ->isActiveWhen(fn (): bool => original_request()->routeIs(static::getNavigationItemActiveRoutePattern()))
                ->sort(static::getNavigationSort())
                ->url(static::getNavigationUrl()),
        ];
    }

    public static function getPages(): array
    {
        return [
            'view' => ViewUserProfile::route('/{record}'),
            'edit' => EditUserProfile::route('/{record}/edit'),
        ];
    }
}
