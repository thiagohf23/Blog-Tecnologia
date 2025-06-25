<?php

namespace App\Filament\Resources;

use DateTime;
use Filament\Forms;
use App\Models\Post;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Checkbox;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Resources\PostResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PostResource\RelationManagers;
use Filament\Tables\Columns\CheckboxColumn;
use Filament\Tables\Columns\ImageColumn;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Main content')->schema(
                    [
                    TextInput::make('title')
                    ->live()
                    ->required()->minLength(1)->maxLength(150)
                    ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                        if ($operation === 'edit') {
                            return;
                        }

                        $set('slug', Str::slug($state));
                    }),
                    TextInput::make('slug')->required()->minLength(1)->unique(ignoreRecord: true)->maxLength(150),
                    RichEditor::make('body')->required()->fileAttachmentsDirectory('posts/images')->columnSpanFull(),

                    ]
                )->columns(2),

                Section::make('Meta')->schema(
                    [
                        FileUpload::make('image')
                            ->image()
                            ->directory('posts/images')
                            ->nullable()
                            ->columnSpanFull(),

                        DateTimePicker::make('published_at')
                            ->label('Published At')
                            ->columnSpanFull()
                            ->nullable(),

                        Checkbox::make('featured')
                            ->default(false)
                            ->columnSpanFull(),

                        Select::make('user_id')
                            ->relationship('author', 'name')
                            ->required()
                            ->searchable()
                            ->columnSpanFull(),

                        Select::make('categories')
                            ->relationship('categories', 'title')
                            ->multiple()
                            ->required()
                            ->searchable()
                            ->columnSpanFull(),
                    ]
                ),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image'),
                TextColumn::make('title')->sortable()->searchable(),
                TextColumn::make('slug')->sortable()->searchable(),
                TextColumn::make('author.name')->sortable()->searchable(),
                TextColumn::make('published_at')->dateTime('d-M-Y')->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true),
                CheckboxColumn::make('featured')->sortable()->searchable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
