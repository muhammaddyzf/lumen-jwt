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
		dd($request->auth->id);
	}
}