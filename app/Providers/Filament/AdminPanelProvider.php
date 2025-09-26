<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use App\Filament\Pages\Auth\Login;
use Filament\Support\Colors\Color;
use Filament\Navigation\NavigationGroup;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use App\Filament\Widgets\PengeluaranHarianChart;
use App\Filament\Widgets\RiwayatPengajuanWidget;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Filament\Widgets\PengajuanPerDivisiChart;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()->id('admin')->path('admin')
            ->brandLogo(asset('images/logo_mci.png'))
            ->brandLogoHeight('55px')
            ->favicon(asset('images/head_logo.png'))
            ->login(Login::class)
            ->colors(['primary' => Color::Amber])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([Pages\Dashboard::class])
            ->widgets([
                PengajuanPerDivisiChart::class,
                PengeluaranHarianChart::class,
                RiwayatPengajuanWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class])
            ->navigationGroups([
                NavigationGroup::make()->label('Master Data'),
                NavigationGroup::make()->label('Pendaftaran User'),
            ]);
    }
}
