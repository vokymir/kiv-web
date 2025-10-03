<?php

namespace App\Models;

enum Status: int
{
	case PendingReview = 10;
	case Accepted = 20;
	case Rejected = 30;
	case Reworking = 40;

	public function label(): string
	{
		return match ($this) {
			self::PendingReview => 'Pending Review',
			self::Accepted => 'Accepted',
			self::Rejected => 'Rejected',
			self::Reworking => 'Reworking'
		};
	}
}
