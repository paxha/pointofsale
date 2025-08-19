<?php

namespace App\Filament\Pages;

use App\Enums\StoreStatus;
use App\Models\Store;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class RegisterStore extends RegisterTenant
{


    public static function getLabel(): string
    {
        return 'Create store';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->model(Store::class)
            ->statePath('data')
            ->components([
                Section::make('Store details')
                    ->components([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('address')
                            ->label('Address')
                            ->maxLength(255),
                    ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeRegister(array $data): array
    {
        // Ensure a unique slug is generated from the name.
        $data['slug'] = $this->generateUniqueSlug((string) ($data['name'] ?? ''));

        // Let the database default the status; ensure it's a valid enum string if set here.
        if (! isset($data['status'])) {
            $data['status'] = StoreStatus::default()->value;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(array $data): \Illuminate\Database\Eloquent\Model
    {
        /** @var Store $store */
        $store = Store::create($data);

        if (auth()->check()) {
            $store->users()->syncWithoutDetaching([auth()->id()]);
        }

        return $store;
    }

    protected function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: Str::random(8);
        $slug = $base;
        $suffix = 2;

        while (Store::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
