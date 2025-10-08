<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\User;
use App\Models\Role;

class UserController extends Controller
{
	// show all users, if current user is Admin or Super
	public function users(): void
	{
		Auth::requireRole([Role::Admin, Role::Superadmin]);

		$users = User::all();
		$this->renderView('admin/users', ['users' => $users]);
	}

	// update one user from POST data, only for admins+
	public function update(int $userId): void
	{
		Auth::requireRole([Role::Admin, Role::Superadmin]);
		$current = $_SESSION['user'];
		$target = User::find($userId);

		if (!$target) {
			Flash::set('error', 'Cannot edit non-existing user.');
			self::redirect('users');
		}

		// admins cannot edit admins or supers
		if ($current['role'] === Role::Admin->value && $target->role->value >= Role::Admin->value) {
			Flash::set('warning', 'Cannot edit other admins.');
			self::redirect('users');
		}

		$target->update([
			'role' => (int)$_POST['role'],
			'blocked' => isset($_POST['blocked']) ? 1 : 0
		]);

		Flash::set('success', 'User successfully edited!');
		self::redirect('users');
	}

	// delete target user, but only if current user is admin+
	public function delete(int $userId): void
	{
		Auth::requireRole([Role::Admin, Role::Superadmin]);
		$current = $_SESSION['user'];
		$target = User::find($userId);

		if (!$target) {
			Flash::set('error', 'Cannot delete non-existing user.');
			self::redirect('users');
		}

		// Same restriction
		if ($current['role'] === Role::Admin->value && $target->role->value >= Role::Admin->value) {
			Flash::set('warning', 'Cannot edit other admins.');
			self::redirect('users');
		}

		$target->delete();

		Flash::set('success', 'User successfully deleted!');
		self::redirect('users');
	}
}
