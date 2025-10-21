<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\User;
use App\Models\Role;

class UserController extends Controller
{
	// Show all users (Admins and Superadmins only)
	public function users(): void
	{
		Auth::requireRole([Role::Admin, Role::Superadmin]);

		$users = User::all();
		$this->renderView('admin/users', [
			'title' => 'Manage Users',
			'users' => $users
		]);
	}

	// Update a user (Admins+ only)
	public function update(int $userId): void
	{
		Auth::requireRole([Role::Admin, Role::Superadmin]);
		$current = $_SESSION['user'];
		$target = User::find($userId);
		$newRole = (int)$_POST['role'];

		if (!$target) {
			Flash::set('error', 'Cannot edit non-existing user.');
			self::redirect('users');
		}

		// Admins cannot edit admins or supers
		if ($current['role'] === Role::Admin->value && $target->role->value >= Role::Admin->value) {
			Flash::set('warning', 'Cannot edit other admins.');
			self::redirect('users');
		}

		// Only Superadmin can create another Superadmin
		if ($newRole > Role::Admin->value && $current['role'] != Role::Superadmin->value) {
			$newRole = Role::Admin->value;
		}

		$target->update([
			'role' => $newRole,
			'blocked' => isset($_POST['blocked']) ? 1 : 0
		]);

		// if editing self
		if ($target->id == $current['id']) {
			$_SESSION['user']['role'] = $target->role;
			$_SESSION['user']['blocked'] = $target->blocked;
		}

		Flash::set('success', 'User successfully edited!');

		// if edited self
		if ($_SESSION['user']['role'] >= Role::Admin->value) {
			self::redirect('users');
		} else {
			self::redirect('/');
		}
	}

	// Delete a user (Admins+ only)
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
