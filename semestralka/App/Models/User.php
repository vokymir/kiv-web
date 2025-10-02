<?php

namespace App\Models;

class User
{
	public int $id;
	public Role $role;
	public bool $blocked;

	public string $username;
	public string $passwordHash;
}
