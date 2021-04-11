<?php

namespace App\Controllers;

class Home extends BaseController
{
	public function index()
	{
		return \password_hash('p455w0rd', PASSWORD_DEFAULT);
	}
}
