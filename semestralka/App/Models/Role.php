<?php

namespace App\Models;

enum Role: int
{
	case Author = 10;
	case Reviewer = 20;
	case Admin = 30;
	case Superadmin = 50;

	public function label(): string
	{
		return match ($this) {
			self::Author => 'Author',
			self::Reviewer => 'Reviewer',
			self::Admin => 'Admin',
			self::Superadmin => 'Superadmin',
		};
	}
}
