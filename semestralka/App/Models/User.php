<?php

namespace App\Models;

enum Role: int
{
	case Author = 10;
	case Reviewer = 20;
	case Admin = 30;
	case Superadmin = 50;
}

class User
{
	public int $id;
	public Role $role;
	public bool $blocked;

	public string $username;
	public string $passwordHash;
}
