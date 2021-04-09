<?php

namespace App\Controllers;

class Home extends BaseController
{
	public function index()
	{
		return \password_hash('test', PASSWORD_DEFAULT);
	}
}
