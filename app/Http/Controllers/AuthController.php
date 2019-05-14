<?php
namespace App\Http\Controllers;
use Validator;
use App\User;
use App\RefreshToken;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Firebase\JWT\ExpiredException;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Routing\Controller as BaseController;
use App\Libraries\Token;
use \Illuminate\Validation\ValidationException;
use DB;


class AuthController extends BaseController 
{
    public function auth(Request $request)
    {
        $this->validate($request, [
            'email'     => 'required|email',
            'password'  => 'required'
        ]);

        $err = [
            'original' => []
        ];

        $user = User::where('email', $request->input('email'))->first();

        if(empty($user)){
            $err['original'][] = 'login invalid user not found';
        }

        if(count($err['original']) > 0) {
            throw new ValidationException(null, response()->json($err, 422));
        }

        list($jwt, $expire) = Token::jwtEncode($user->id);

        //Create Refresh Token
        $code         = '';
        $isTokenExist = false;

        do{

            $code  = Token::generate(32);
            $count = DB::table('refresh_tokens')->where('token', $code)->count();
            if($count > 0) {
                $isTokenExist = true;
            }

        } while ($isTokenExist);

        $refreshToken = RefreshToken::create([
            'token'  => $code,
            'user_id'=> $user->id,
        ]);


        return response()->json([
            'resp' => [
                'data' =>  [
                    'access_token'  => $jwt,
                    'expire_at'     => $expire,
                    'refresh_token' => $code
                ],
                'message' => [ 'User successfully login' ],
            ]
        ]);
    }


    public function refreshToken(Request $request)
    {
        $rules = [
            'token'  => 'required'
        ];

        $this->validate($request, $rules);

        $refresh_token = DB::table('refresh_tokens')->where([
            ['token', $request->input('token')],
            ['still_active', true]
        ])->first();

        if(!$refresh_token) {
            throw new ValidationException(null, response()->json([
                'original' => [
                    'Token not valid'
                ]
            ], 422));
        }

        $user = DB::table('users')->where([
            ['id', '=', $refresh_token->user_id]
        ])->first();

        if(empty($user)) {
            throw new ValidationException(null, response()->json([
                'original' => [
                    'User not found'
                ]
            ], 422));
        }

        DB::beginTransaction();
        try {
            list($jwt, $expire) = Token::jwtEncode($user->id);

            $code = '';
            $is_token_exist = false;
            do {
                $code = Token::generate(32);
                $count = DB::table('refresh_tokens')->where('token', $code)->count();
                if($count > 0) {
                    $is_token_exist = true;
                }
            } while ($is_token_exist);

            DB::table('refresh_tokens')->where('user_id', $user->id)->update([
                'still_active' => false
            ]);

            DB::table('refresh_tokens')->insert(array(
                'token'      => $code,
                'user_id'    => $user->id,
                'created_at' => date('Y-m-d H:i:s')
            ));

            // DB::table('users')->where('id', $user->id)
            // ->update([
            //     'last_sign_in_at' => date('Y-m-d H:i:s')
            // ]);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        DB::commit();
        return response()->json([
            'resp' => [
                'data' =>  [
                    'access_token' => $jwt,
                    'expire_at'   => $expire,
                    'refresh_token' => $code
                ],
                'message' => [ 'Successfully refresh token' ],
            ]
        ]);
    }
}