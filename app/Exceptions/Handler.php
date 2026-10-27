<?php

namespace App\Exceptions;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $levels = [
        //
    ];

    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    protected function shouldReturnJson($request, Throwable $e)
    {
        return $request->is('api/*') || parent::shouldReturnJson($request, $e);
    }

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (
            NotFoundHttpException $e,
            $request
        ) {
            $previous = $e->getPrevious();

            if (
                $request->is('api/*') &&
                $previous instanceof ModelNotFoundException &&
                in_array($previous->getModel(), [Product::class, Category::class], true)
            ) {
                return response()->json([
                    'message' => $previous->getModel() === Product::class
                        ? 'Produto não encontrado.'
                        : 'Categoria não encontrada.'
                ], 404);
            }
        });
    }
}
