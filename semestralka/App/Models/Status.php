<?php

namespace App\Models;

enum Status: int
{
	case PendingReview = 10;
	case Accepted = 20;
	case Rejected = 30;
}
