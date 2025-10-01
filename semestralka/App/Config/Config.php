<?php

namespace App\Config;

class Config
{
	public const DB_HOST = 'localhost';
	public const DB_USER = 'root';
	public const DB_PASS = '';
	public const DB_NAME = 'conference_db';
	public const DB_PORT = '3306';
	public const BASE_URL = 'http://localhost/kiv-web/public/';

	public const VIEW_DATA = [
		"sitename" => "Epic Conference",
		"defaultTitle" => "welcome",
	];
}
