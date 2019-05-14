<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RefreshToken extends Model
{
	protected $table = "refresh_tokens";

	protected $fillable = ['token', 'user_id', 'still_active'];

}