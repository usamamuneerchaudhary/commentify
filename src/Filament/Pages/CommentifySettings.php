<?php

namespace Usamamuneerchaudhary\Commentify\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommentifySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-cog-6-tooth';


    protected static string|null|\UnitEnum $navigationGroup = 'Commentify';

    protected static ?int $navigationSort = 10;

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return 'Commentify Settings';
    }

    public function getView(): string
    {
        return 'commentify::pages.settings';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('General Settings')
                    ->schema([
                        Forms\Components\Select::make('css_framework')
                            ->label('CSS Framework')
                            ->options([
                                'tailwind' => 'Tailwind CSS',
                                'bootstrap' => 'Bootstrap',
                            ])
                            ->default('tailwind')
                            ->required()
                            ->helperText('Choose the CSS framework for comment components'),
                        Forms\Components\TextInput::make('users_route_prefix')
                            ->label('Users Route Prefix')
                            ->default('users')
                            ->required(),
                        Forms\Components\TextInput::make('pagination_count')
                            ->label('Comments Per Page')
                            ->numeric()
                            ->default(10)
                            ->required(),
                        Forms\Components\Toggle::make('comment_nesting')
                            ->label('Enable Comment Nesting')
                            ->default(true),
                        Forms\Components\Toggle::make('read_only')
                            ->label('Read Only Mode')
                            ->helperText('Disable all commenting functionality')
                            ->default(false),
                    ]),
                Section::make('Sorting & Display')
                    ->schema([
                        Forms\Components\Select::make('default_sort')
                            ->label('Default Sort Order')
                            ->options([
                                'newest' => 'Newest First',
                                'oldest' => 'Oldest First',
                                'most_liked' => 'Most Liked',
                                'most_replied' => 'Most Replied',
                            ])
                            ->default('newest')
                            ->required(),
                        Forms\Components\Toggle::make('enable_sorting')
                            ->label('Enable Sorting')
                            ->default(true),
                    ]),
                Section::make('Moderation')
                    ->schema([
                        Forms\Components\Toggle::make('enable_reporting')
                            ->label('Enable Comment Reporting')
                            ->default(true),
                        Forms\Components\TagsInput::make('report_reasons')
                            ->label('Report Reasons')
                            ->placeholder('Add report reason')
                            ->default(['spam', 'inappropriate', 'offensive', 'other']),
                    ]),
                Section::make('Features')
                    ->schema([
                        Forms\Components\Select::make('theme')
                            ->label('Theme Mode')
                            ->options([
                                'light' => 'Light',
                                'dark' => 'Dark',
                                'auto' => 'Auto (System Preference)',
                            ])
                            ->default('auto')
                            ->required(),
                        Forms\Components\Toggle::make('enable_emoji_picker')
                            ->label('Enable Emoji Picker')
                            ->default(true),
                    ]),
                Section::make('Notifications')
                    ->schema([
                        Forms\Components\Toggle::make('enable_notifications')
                            ->label('Enable Notifications')
                            ->default(false),
                        Forms\Components\CheckboxList::make('notification_channels')
                            ->label('Notification Channels')
                            ->options([
                                'database' => 'Database',
                                'mail' => 'Email',
                                'broadcast' => 'Broadcast (WebSocket)',
                            ])
                            ->default(['database'])
                            ->visible(fn ($get) => $get('enable_notifications')),
                    ]),
            ])
            ->statePath('data');
    }

    public function mount(): void
    {
        $this->form->fill(config('commentify', []));
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Save to config file
        $configPath = config_path('commentify.php');

        if (file_exists($configPath)) {
            $config = require $configPath;
            $config = array_merge($config, $data);

            file_put_contents(
                $configPath,
                "<?php\n\nreturn " . var_export($config, true) . ";\n"
            );

            // Clear config cache
            if (function_exists('config')) {
                app()->make('config')->set('commentify', $config);
            }
        }

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}

