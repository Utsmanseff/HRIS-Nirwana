<?php

use App\Http\Middleware\EnsureKaryawanClaimed;
use App\Http\Middleware\PastikanAkunAktif;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'claimed' => EnsureKaryawanClaimed::class,
            'aktif' => PastikanAkunAktif::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // QR verifikasi dipindai orang luar: signature rusak harus dapat halaman ramah,
        // bukan halaman 403 bawaan berbahasa Inggris.
        $exceptions->render(function (\Illuminate\Routing\Exceptions\InvalidSignatureException $e, Request $request) {
            $view = match ($request->route()?->getName()) {
                'verifikasi.sanksi' => 'verifikasi.sanksi',
                'verifikasi.cuti' => 'verifikasi.cuti',
                default => null,
            };

            return $view ? response()->view($view, ['invalid' => true], 403) : null;
        });
    })->create();
