<?php

namespace App\Filament\Resources\PrisonerResource\RelationManagers;

use App\Filament\Concerns\HandlesPartialDateForm;
use App\Filament\Forms\PartialDate;
use App\Models\PrisonerCase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CasesRelationManager extends RelationManager
{
    use HandlesPartialDateForm;

    protected static string $relationship = 'cases';

    protected static ?string $recordTitleAttribute = 'charges';

    protected function partialDateFields(): array
    {
        return (new PrisonerCase)->partialDateFields();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('institution_id')
                    ->relationship('institution', 'name')
                    ->nullable()
                    ->searchable()
                    ->preload(),
                Forms\Components\Textarea::make('charges')
                    ->columnSpanFull(),
                PartialDate::make('arrest_date', 'Arrest date'),
                Forms\Components\TextInput::make('indicted')
                    ->maxLength(255),
                Forms\Components\TextInput::make('convicted')
                    ->maxLength(255),
                Forms\Components\TextInput::make('plead')
                    ->maxLength(255),
                PartialDate::make('sentenced_date', 'Sentenced date'),
                PartialDate::make('incarceration_date', 'Incarceration date'),
                PartialDate::make('release_date', 'Release date'),
                PartialDate::make('death_in_custody_date', 'Death in custody date'),
                PartialDate::make('in_exile_since', 'In exile since'),
                PartialDate::make('end_of_exile', 'End of exile'),
                Forms\Components\TextInput::make('prosecutor')
                    ->maxLength(255),
                Forms\Components\TextInput::make('judge')
                    ->maxLength(255),
                Forms\Components\Textarea::make('sentence'),
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('institution.name'),
                Tables\Columns\TextColumn::make('charges')
                    ->limit(40),
                Tables\Columns\TextColumn::make('arrest_date')
                    ->formatStateUsing(fn ($state, PrisonerCase $record) => $record->formatPartialDate('arrest_date')),
                Tables\Columns\TextColumn::make('release_date')
                    ->formatStateUsing(fn ($state, PrisonerCase $record) => $record->formatPartialDate('release_date')),
                Tables\Columns\TextColumn::make('sentence')
                    ->limit(30),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(fn (array $data): array => $this->combinePartialDates($data)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data): array => $this->splitPartialDates($data))
                    ->mutateFormDataUsing(fn (array $data): array => $this->combinePartialDates($data)),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
