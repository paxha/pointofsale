<?php

namespace App\Filament\Resources\Procurements\Pages;

use App\Enums\ProcurementStatus;
use App\Filament\Resources\Procurements\ProcurementResource;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CloseProcurement extends Page implements HasSchemas
{
    use InteractsWithRecord, InteractsWithSchemas;

    protected static string $resource = ProcurementResource::class;

    public array $procurementProducts = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->procurementProducts = $this->record->procurementProducts()->with('product')->get()->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'received_quantity' => $item->requested_quantity,
                'received_unit_price' => $item->requested_unit_price,
                'received_tax_percentage' => $item->requested_tax_percentage,
                'received_tax_amount' => $item->requested_tax_amount,
                'received_supplier_percentage' => $item->requested_supplier_percentage,
                'received_supplier_price' => $item->requested_supplier_price,
            ];
        })->toArray();
    }

    protected function getActions(): array
    {
        return [
            Action::make('cancel')
                ->label('Cancel')
                ->color('gray')
                ->url(ProcurementResource::getUrl('index')),
            Action::make('close')
                ->label('Mark as Closed')
                ->color('success')
                ->icon(HeroIcon::OutlinedCheck)
                ->action('save'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Repeater::make('procurementProducts')
                    ->hiddenLabel()
                    ->schema([
                        Forms\Components\Hidden::make('product_id'),
                        Forms\Components\TextInput::make('product_name')
                            ->label('Product')
                            ->columnSpan(2)
                            ->disabled(),
                        Forms\Components\TextInput::make('received_quantity')
                            ->label('Qty')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('received_unit_price')
                            ->label('Unit Price')
                            ->prefix('PKR')
                            ->numeric()
                            ->required()
                            ->live(debounce: 1000)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $percentage = (float)$get('received_supplier_percentage');
                                $price = (float)$get('received_supplier_price');
                                if ($percentage > 0) {
                                    $newPrice = round($state * $percentage / 100, 2);
                                    if ($newPrice !== $price) {
                                        $set('received_supplier_price', $newPrice);
                                    }
                                } elseif ($price > 0 && $state > 0) {
                                    $newPercentage = round($price / $state * 100, 2);
                                    if ($newPercentage !== $percentage) {
                                        $set('received_supplier_percentage', $newPercentage);
                                    }
                                }
                                $taxPercentage = (float)$get('received_tax_percentage');
                                $taxAmount = round($state * $taxPercentage / 100, 2);
                                $set('received_tax_amount', $taxAmount);
                                $taxAmountField = (float)$get('received_tax_amount');
                                if ($taxAmountField > 0 && $state > 0) {
                                    $newTaxPercentage = round($taxAmountField / $state * 100, 2);
                                    if ($newTaxPercentage !== $taxPercentage) {
                                        $set('received_tax_percentage', $newTaxPercentage);
                                    }
                                }
                            }),
                        Forms\Components\TextInput::make('received_tax_percentage')
                            ->label('Tax %')
                            ->prefix('%')
                            ->numeric()
                            ->live(debounce: 1000)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $unitPrice = (float)$get('received_unit_price');
                                $taxAmount = round($unitPrice * $state / 100, 2);
                                $set('received_tax_amount', $taxAmount);
                            }),
                        Forms\Components\TextInput::make('received_tax_amount')
                            ->label('Tax Amt')
                            ->prefix('PKR')
                            ->numeric()
                            ->live(debounce: 1000)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $unitPrice = (float)$get('received_unit_price');
                                $taxPercentage = (float)$get('received_tax_percentage');
                                if ($unitPrice > 0) {
                                    $newTaxPercentage = round($state / $unitPrice * 100, 2);
                                    if ($newTaxPercentage !== $taxPercentage) {
                                        $set('received_tax_percentage', $newTaxPercentage);
                                    }
                                } else {
                                    if ($taxPercentage !== 0) {
                                        $set('received_tax_percentage', 0);
                                    }
                                }
                            }),
                        Forms\Components\TextInput::make('received_supplier_percentage')
                            ->label('Supp %')
                            ->prefix('%')
                            ->numeric()
                            ->live(debounce: 1000)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $unitPrice = (float)$get('received_unit_price');
                                $price = (float)$get('received_supplier_price');
                                if ($unitPrice > 0) {
                                    $newPrice = round($unitPrice - ($unitPrice * $state / 100), 2);
                                    if ($newPrice !== $price) {
                                        $set('received_supplier_price', $newPrice);
                                    }
                                } else {
                                    if ($price !== 0) {
                                        $set('received_supplier_price', 0);
                                    }
                                }
                            }),
                        Forms\Components\TextInput::make('received_supplier_price')
                            ->label('Supp Price')
                            ->prefix('PKR')
                            ->numeric()
                            ->live(debounce: 1000)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $unitPrice = (float)$get('received_unit_price');
                                $percentage = (float)$get('received_supplier_percentage');
                                if ($unitPrice > 0) {
                                    $newPercentage = round((($unitPrice - $state) / $unitPrice) * 100, 2);
                                    if ($newPercentage !== $percentage) {
                                        $set('received_supplier_percentage', $newPercentage);
                                    }
                                } else {
                                    if ($percentage !== 0) {
                                        $set('received_supplier_percentage', 0);
                                    }
                                }
                            }),
                    ])
                    ->columns(4)
                    ->addable(false)
                    ->reorderable(false)
                    ->deletable(false),
            ]);
    }

    public function save(): void
    {
        foreach ($this->procurementProducts as $item) {
            $pp = $this->record->procurementProducts()->where('product_id', $item['product_id'])->first();
            if ($pp) {
                $pp->received_quantity = $item['received_quantity'] ?? 0;
                $pp->received_unit_price = $item['received_unit_price'] ?? 0;
                $pp->received_tax_percentage = $item['received_tax_percentage'] ?? 0;
                $pp->received_tax_amount = $item['received_tax_amount'] ?? 0;
                $pp->received_supplier_percentage = $item['received_supplier_percentage'] ?? 0;
                $pp->received_supplier_price = $item['received_supplier_price'] ?? 0;
                $pp->save();
            }
        }
        $this->record->status = ProcurementStatus::Closed;
        $this->record->save();
        $this->notify('success', 'Procurement closed and details saved.');
        $this->redirect(ProcurementResource::getUrl('index'));
    }
}
