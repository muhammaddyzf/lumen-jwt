<?php
namespace App\Http\Controllers;
use Validator;
use App\User;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;


class UserController extends BaseController 
{
	function show(Request $request)
	{
		$userId = $request->session->id;
		$user = User::find($userId);

		return json_encode($user);
	}
}