<?php

use App\Http\Middleware\ResolvePharmacy;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use App\Http\Middleware\SetAppLocale;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('sanctum:prune-expired --hours=24')->daily();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->appendToGroup('api', [
            SetAppLocale::class,
        ]);
        $middleware->alias([
            'resolve.pharmacy' => ResolvePharmacy::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Routing\Exceptions\InvalidSignatureException $e, Request $request) {
            $timestamp = $request->query('t', time());
            if ($request->query('platform') === 'web') {
                return redirect(
                    env('FRONTEND_WEB_VERIFIED_URL')
                        . '?status=invalid_link&t='
                        . $timestamp
                );
            }
            return redirect(
                env('FRONTEND_APP_VERIFIED_URL')
                    . '?status=invalid_link&t='
                    . $timestamp
            );
        });
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                $previous = $e->getPrevious();
                if ($previous instanceof ModelNotFoundException) {
                    $fullModelName = $previous->getModel();
                    $modelName = class_basename($fullModelName);
                    $customMessages = [
                        'Product' => 'Product not found. Please check the product ID and try again.',
                    ];
                    $message = $customMessages[$modelName] ?? "The requested {$modelName} does not exist.";
                    return response()->json([
                        'status'  => 'error',
                        'message' => $message
                    ], 404);
                }
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Endpoint or resource not found.'
                ], 404);
            }
        });
        $exceptions->render(function (\InvalidArgumentException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }
        });
    })->create();
