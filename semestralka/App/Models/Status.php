<?php

namespace App\Models;

enum Status: int
{
	case InitialDraft = 0;
	case PendingReview = 10;
	case Accepted = 20;
	case Rejected = 30;
	case Reworking = 40;
}
