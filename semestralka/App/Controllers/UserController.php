<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\User;
use App\Models\Role;

class UserController extends Controller
{
	public function users(): void
	{
		Auth::requireRole([Role::Admin, Role::Superadmin]);

		$users = User::all();
		$this->renderView('admin/users', ['users' => $users]);
	}

	public function update(int $userId): void
	{
		Auth::requireRole([Role::Admin, Role::Superadmin]);
		$current = $_SESSION['user'];
		$target = User::find($userId);

		if (!$target) {
			self::redirect('users');
		}

		// admins cannot admins or supers
		if ($current['role'] === Role::Admin->value && $target->role->value >= Role::Admin->value) {
			self::redirect('users');
		}

		$target->update([
			'role' => (int)$_POST['role'],
			'blocked' => isset($_POST['blocked']) ? 1 : 0
		]);

		self::redirect('users');
	}

	public function delete(int $userId): void
	{
		Auth::requireRole([Role::Admin, Role::Superadmin]);
		$current = $_SESSION['user'];
		$target = User::find($userId);

		if (!$target) {
			self::redirect('users');
		}

		// Same restriction
		if ($current['role'] === Role::Admin->value && $target->role->value >= Role::Admin->value) {
			self::redirect('users');
		}

		$target->delete();

		self::redirect('users');
	}
}
