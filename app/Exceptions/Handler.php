<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use     Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param \Throwable $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Throwable $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ApiException) {
            $code = $exception->getCode();
            return response()->json([
                'code' => $code ?? 0,
                'msg'  => getMsg($code) ?? (config('app.debug') ? $exception->getMessage() : '失败'),
                'data' => null
            ]);
        }
        if (Str::lower($request->segment(1)) === 'api') {
            if ($exception instanceof ValidationException) {
                return response()->json([
                    'code' => Response::HTTP_BAD_REQUEST,
                    'msg' => $exception->validator->errors()->first(),
                ], Response::HTTP_BAD_REQUEST);
            } elseif ($exception instanceof AuthenticationException) {
                return response()->json([
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'msg' => $exception->getMessage(),
                ], Response::HTTP_UNAUTHORIZED);
            } elseif ($exception instanceof AuthorizationException) {
                return response()->json([
                    'code' => Response::HTTP_FORBIDDEN,
                    'msg' => $exception->getMessage(),
                ], Response::HTTP_UNAUTHORIZED);
            } elseif ($exception instanceof HttpException) {
                return response()->json([
                    'code' => $exception->getCode(),
                    'msg' => $exception->getMessage(),
                ], $exception->getStatusCode());
            } elseif ($exception instanceof UnauthorizedHttpException) {
                return response()->json([
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'msg' => 'Please login again',
                ], Response::HTTP_OK);
            } elseif ($exception instanceof TokenBlacklistedException) {
                return response()->json([
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'msg' => 'Please login again',
                ], Response::HTTP_OK);
            } elseif ($exception instanceof NotFoundHttpException) {
                return response()->json([
                    'code' => Response::HTTP_NOT_FOUND,
                    'msg' => 'Not Found'
                ], Response::HTTP_NOT_FOUND);
            } elseif ($exception instanceof ModelNotFoundException) {
                return response()->json([
                    'code' => Response::HTTP_NOT_FOUND,
                    'msg' => 'No query results'
                ], Response::HTTP_NOT_FOUND);
            } else {
                return parent::render($request, $exception);
            }
        } else {
            return parent::render($request, $exception);
        }
    }

    protected function convertExceptionToArray(Throwable $e)
    {
        return config('app.debug') ? [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => collect($e->getTrace())->map(function ($trace) {
                return Arr::except($trace, ['args']);
            })->all(),
        ] : [
            'message' => $this->isHttpException($e) ? $e->getMessage() : 'Server Error',
            'code' => $e->getCode(),
        ];
    }
}
