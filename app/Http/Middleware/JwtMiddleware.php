<?php
namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpKernel\Exception\HttpException;

use \Firebase\JWT\JWT;

class JwtMiddleware
{
    public function handle($request, Closure $next, $guard = null)
    {
        $jwt = $request->header('jwt');

        if(empty($jwt)) {
            throw new HttpException(401, 'Unauthorized');
        }

        $token  = JWT::decode($jwt, base64_encode(env('JWT_SECRET')), array(env('JWT_ALG')));       
        
        if($token->data->env !== env('APP_ENV')) {
            throw new HttpException(401, 'Unauthorized'); 
        }

        // Now let's put the user in the request class so that you can grab it from there
        $request->merge(['session' => $token->data]);
        return $next($request);
    }
}