<?php

namespace App\Filament\Resources;

use App\Filament\Forms\PartialDate;
use App\Filament\Resources\PrisonerCaseResource\Pages;
use App\Models\PrisonerCase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PrisonerCaseResource extends Resource
{
    protected static ?string $model = PrisonerCase::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Prisoner Database';

    protected static ?string $modelLabel = 'Case';

    protected static ?string $pluralModelLabel = 'Cases';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Associations')
                    ->schema([
                        Forms\Components\Select::make('prisoner_id')
                            ->relationship('prisoner', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('institution_id')
                            ->relationship('institution', 'name')
                            ->nullable()
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Case Details')
                    ->schema([
                        Forms\Components\Textarea::make('charges')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('indicted')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('convicted')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('plead')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('prosecutor')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('judge')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('sentence'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Key Dates')
                    ->description('Enter as much as is known — a year alone, a month and year, or a full date. Blank parts show blank publicly; internally they default to the 1st.')
                    ->schema([
                        PartialDate::make('arrest_date', 'Arrest date'),
                        PartialDate::make('sentenced_date', 'Sentenced date'),
                        PartialDate::make('incarceration_date', 'Incarceration date'),
                        PartialDate::make('release_date', 'Release date'),
                        PartialDate::make('death_in_custody_date', 'Death in custody date'),
                        PartialDate::make('in_exile_since', 'In exile since'),
                        PartialDate::make('end_of_exile', 'End of exile'),
                    ]),

                Forms\Components\Section::make('Duration')
                    ->schema([
                        Forms\Components\TextInput::make('imprisoned_for_days')
                            ->numeric()
                            ->disabled()
                            ->helperText('Auto-calculated from incarceration and release dates, or from the documented months below'),
                        Forms\Components\TextInput::make('imprisoned_for_months')
                            ->label('Documented months served')
                            ->numeric()
                            ->helperText('Only when a source states the time served in months and the dates cannot support a day-level span. Overrides the calculation above. The public counter converts whole years: 24 months reads "2 Years"; 38 reads "3 Years 2 Months".'),
                        Forms\Components\TextInput::make('in_exile_for_days')
                            ->numeric(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('prisoner.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('institution.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('charges')
                    ->limit(40),
                Tables\Columns\TextColumn::make('arrest_date')
                    ->formatStateUsing(fn ($state, PrisonerCase $record) => $record->formatPartialDate('arrest_date'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('release_date')
                    ->formatStateUsing(fn ($state, PrisonerCase $record) => $record->formatPartialDate('release_date'))
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrisonerCases::route('/'),
            'create' => Pages\CreatePrisonerCase::route('/create'),
            'edit' => Pages\EditPrisonerCase::route('/{record}/edit'),
        ];
    }
}
