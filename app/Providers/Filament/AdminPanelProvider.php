<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\MenuItem;
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
use Filament\View\PanelsRenderHook;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()->id('admin')->path('admin')
            ->brandLogo(asset('images/logo_mci.png'))
            ->brandLogoHeight('55px')
            ->favicon(asset('images/head_logo.png'))
            ->colors(['primary' => Color::Amber])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([Pages\Dashboard::class])
            ->widgets([
                PengajuanPerDivisiChart::class,
                PengeluaranHarianChart::class,
                RiwayatPengajuanWidget::class,
            ])
            ->userMenuItems([
                'logout' => MenuItem::make()
                    ->label('Keluar / Log Out (SSO)')
                    ->url('/admin/sso-logout')
                    ->icon('heroicon-o-arrow-left-on-rectangle'),
                MenuItem::make()
                    ->label('Kembali ke SSO Portal')
                    ->url('javascript:returnToSsoPortal()')
                    ->icon('heroicon-o-home'),
            ])
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): string => '
                <script>
                function returnToSsoPortal() {
                    if (window.opener && !window.opener.closed) {
                        try { window.opener.focus(); } catch(e){}
                        window.close();
                    } else {
                        window.location.href = "http://" + (window.location.hostname || "127.0.0.1") + ":3005";
                    }
                }

                (function() {
                    var sessionSsoToken = "' . session('sso_token', '') . '";
                    if (sessionSsoToken) {
                        try { localStorage.setItem("sso_token", sessionSsoToken); } catch(e){}
                    }

                    function checkSsoSession() {
                        var token = localStorage.getItem("sso_token");
                        if (!token) return;

                        var ssoHost = window.location.hostname || "127.0.0.1";
                        fetch("http://" + ssoHost + ":8000/api/sso/validate-token", {
                            headers: {
                                "Authorization": "Bearer " + token,
                                "Accept": "application/json"
                            }
                        })
                        .then(function(res) {
                            if (res.status === 401) {
                                window.location.href = "/admin/sso-logout";
                            }
                        })
                        .catch(function(err){});
                    }

                    setInterval(checkSsoSession, 3000);
                    window.addEventListener("focus", checkSsoSession);
                })();
                </script>
                <a href="javascript:void(0)" onclick="returnToSsoPortal()" style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px; background:linear-gradient(135deg, #00b486 0%, #008767 100%); color:#ffffff; font-weight:700; font-size:12px; border-radius:8px; text-decoration:none; margin-right:12px; box-shadow:0 2px 6px rgba(0,180,134,0.3); transition:all 0.2s;" onmouseover="this.style.opacity=0.9" onmouseout="this.style.opacity=1"><span>🏠</span> Kembali ke SSO Portal Utama</a>
                '
            )
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
            ->authMiddleware([\App\Http\Middleware\SsoAuthenticate::class])
            ->navigationGroups([
                NavigationGroup::make()->label('Master Data'),
                NavigationGroup::make()->label('Pendaftaran User'),
            ]);
    }
}
