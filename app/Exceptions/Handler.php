<?php

namespace App\Exceptions;

use Log;
use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function render($request, Exception $e)
    {
        $rendered = parent::render($request, $e);
        $response = [
            'error' => [
                'code' => $rendered->getStatusCode(),
                'message' => [ $e->getMessage() ]
            ]
        ];

      
        if( $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            $response['error']['message'] = [ 'Not Found' ];           
        }

        if ($e instanceof \Illuminate\Validation\ValidationException) {
            $response['error']['message'] = [];
            foreach($e->getResponse()->original as $key => $value) {      
                $response['error']['message'][] = $value[0];
            }
        }

        if($rendered->getStatusCode() === 500) {
            Log::error($e->getTraceAsString());
        }
        return response()->json($response, $rendered->getStatusCode());
    }
}
