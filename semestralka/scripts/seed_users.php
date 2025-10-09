<?php
// scripts/seed_users.php
// Usage: php scripts/seed_users.php [-v]
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Models\Role;

if (php_sapi_name() !== 'cli') {
	echo "Run from CLI: php scripts/seed_users.php\n";
	exit(1);
}

$verbose = in_array('-v', $argv) || in_array('--verbose', $argv);

function h(string $p): string
{
	return password_hash($p, PASSWORD_BCRYPT);
}

$first = ['Flat', 'Round', 'Spinning', 'Wobbly', 'Gravityless', 'Moonbeam', 'Compass', 'Pole', 'Skewed', 'Tilted', 'Sky', 'Horizon', 'Tinfoil', 'Feather', 'Quirky', 'Crispy', 'Bouncy'];
$last  = ['Jones', 'McGee', "O'Flat", 'Vanishing', 'Sprocket', 'Turner', 'Fitz', 'McHollow', 'Droplet', 'Featherstone', 'Noodle', 'Wren', 'Pancake', 'Whimsy', 'Loop'];
$adjs  = ['Professor', 'Dr', 'Sir', 'Madam', 'Captain', 'Agent', 'Mister', 'Ms'];
$pwpool = ['flatIsFun', 'noGravity!', 'pancake42', 'tiltspin', 'gravityno', 'roundnah', 'horiz0n', 'tinfoilHat', 'beacon123', 'coriolis'];

$want = [
	'super'   => ['count' => 1,  'role' => Role::Superadmin->value, 'prefix' => 'super'],
	'admins'  => ['count' => 8,  'role' => Role::Admin->value,      'prefix' => 'admin'],
	'authors' => ['count' => 15, 'role' => Role::Author->value,     'prefix' => 'author'],
	'reviewers' => ['count' => 15, 'role' => Role::Reviewer->value,   'prefix' => 'rev'],
];

$credentials = [];

try {
	$db = new Database();

	// ensure superadmin exists (use predictable credentials)
	$superUser = 'supremus';
	$superPass = 'superfun';
	$db->query("INSERT IGNORE INTO users (username, passwordHash, name, role) VALUES (:u,:ph,:n,:r)")
		->bind(':u', $superUser)
		->bind(':ph', h($superPass))
		->bind(':n', 'Supreme Admin')
		->bind(':r', Role::Superadmin->value)
		->execute();

	$credentials[] = ['username' => $superUser, 'password' => $superPass, 'name' => 'Supreme Admin', 'role' => 'Superadmin'];
	if ($verbose) echo "created/ensured superadmin: $superUser\n";

	// helper to create N users for given role
	$createN = function (int $n, int $role, string $prefix) use ($db, $first, $last, $adjs, $pwpool, &$credentials, $verbose) {
		$created = 0;
		$tries = 0;
		while ($created < $n && $tries < $n * 6) {
			$tries++;
			$fn = $first[array_rand($first)];
			$ln = $last[array_rand($last)];
			$adj = $adjs[array_rand($adjs)];
			// username: prefix_first3last (letters+digits only)
			$raw = $fn . substr($ln, 0, 3);
			$username = strtolower($prefix . '_' . preg_replace('/[^a-z0-9]/i', '', $raw));
			// if username too short, append random digit
			if (strlen($username) < 4) $username .= rand(10, 99);
			$name = "$adj $fn $ln";
			$plain = $pwpool[array_rand($pwpool)] . rand(1, 99);

			try {
				$db->query("INSERT INTO users (username, passwordHash, name, role) VALUES (:username,:ph,:name,:role)")
					->bind(':username', $username)
					->bind(':ph', h($plain))
					->bind(':name', $name)
					->bind(':role', $role)
					->execute();

				$credentials[] = ['username' => $username, 'password' => $plain, 'name' => $name, 'role' => (function ($r) {
					try {
						return \App\Models\Role::from((int)$r)->name;
					} catch (\Throwable $e) {
						return (string)$r;
					}
				})($role)];

				$created++;
				if ($verbose) echo " - created user $username (role=$role)\n";
			} catch (\Throwable $e) {
				// likely duplicate username -> continue trying
				continue;
			}
		}
		return $created;
	};

	// create each group (skip super since already handled)
	$counts = [];
	foreach ($want as $k => $cfg) {
		if ($k === 'super') {
			$counts['super'] = 1;
			continue;
		}
		$counts[$k] = $createN($cfg['count'], $cfg['role'], $cfg['prefix']);
	}

	// write credentials files next to script
	$txtPath = __DIR__ . '/.credentials';
	$jsonPath = __DIR__ . '/.credentials.json';

	$lines = ["username,password,name,role"];
	foreach ($credentials as $c) {
		$lines[] = '"' . addcslashes($c['username'], '"') . '","' . addcslashes($c['password'], '"') . '","' . addcslashes($c['name'], '"') . '","' . addcslashes($c['role'], '"') . '"';
	}
	file_put_contents($txtPath, implode(PHP_EOL, $lines) . PHP_EOL);
	@chmod($txtPath, 0600);

	file_put_contents($jsonPath, json_encode($credentials, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	@chmod($jsonPath, 0600);

	// summary
	echo "Users seeded. Credentials saved to: $txtPath and $jsonPath\n";
	echo "Counts:\n";
	foreach ($counts as $k => $v) {
		echo " - $k: $v\n";
	}

	// show DB totals
	$totals = $db->query("SELECT role, COUNT(*) AS cnt FROM users GROUP BY role")->fetchAll();
	echo "DB totals by role:\n";
	foreach ($totals as $t) {
		$label = (function ($roleVal) {
			try {
				return Role::from((int)$roleVal)->name;
			} catch (\Throwable $e) {
				return (string)$roleVal;
			}
		})($t['role']);
		echo " - {$label}: {$t['cnt']}\n";
	}

	exit(0);
} catch (\Throwable $e) {
	fwrite(STDERR, "Error: " . $e->getMessage() . PHP_EOL);
	exit(1);
}
