<?php

use App\Config\Config;
use App\Models\Role;

?>

<h2>Manage Users</h2>

<table class="table table-striped align-middle">
	<thead>
		<tr>
			<th>ID</th>
			<th>Name</th>
			<th>Role</th>
			<th>Blocked</th>
			<th>Actions</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($users as $user): ?>
			<tr>
				<form method="post" action="<?= Config::BASE_URL ?>users/<?= $user->id ?>/update">
					<td><?= $user->id ?></td>
					<td><?= htmlspecialchars($user->name) ?></td>
					<td>
						<select name="role" class="form-select form-select-sm"
							<?= ($user->role->value >= Role::Admin->value && $_SESSION['user']['role'] < Role::Superadmin->value) ? 'disabled' : '' ?>>
							<?php foreach (Role::cases() as $role): ?>
								<option value="<?= $role->value ?>" <?= $role === $user->role ? 'selected' : '' ?>>
									<?= $role->label() ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
					<td>
						<input type="checkbox" name="blocked" <?= $user->blocked ? 'checked' : '' ?>
							<?= ($user->role->value >= Role::Admin->value && $_SESSION['user']['role'] < Role::Superadmin->value) ? 'disabled' : '' ?>>
					</td>
					<td>
						<?php if (($_SESSION['user']['role'] >= Role::Admin->value && $user->role->value < Role::Admin->value) || $_SESSION['user']['role'] == Role::Superadmin->value): ?>
							<button type="submit" class="btn btn-sm btn-primary">Save</button>
							<button type="submit"
								formaction="<?= Config::BASE_URL ?>users/<?= $user->id ?>/delete"
								class="btn btn-sm btn-danger"
								onclick="return confirm('Delete this user?')">Delete</button>
						<?php endif; ?>
					</td>
				</form>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
