<?php

namespace App\Models;

enum Status: int
{
	case PendingReview = 10;
	case Accepted = 20;
	case Rejected = 30;

	public static function fromFilter(string $filter): ?self
	{
		return match ($filter) {
			'in_review' => self::PendingReview,
			'published' => self::Accepted,
			'rejected' => self::Rejected,
			default => null,
		};
	}

	public function label(): string
	{
		return match ($this) {
			self::PendingReview => 'In Review',
			self::Accepted => 'Published',
			self::Rejected => 'Rejected',
		};
	}
}
