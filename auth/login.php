<?php
$is_invalid = false;
if (!empty($_GET["invalid"]))
{
	$is_invalid = true;
	$notice_errors = array(
		"unregistered" => "Username is not registered.",
		"wrongpass" => "Wrong password.",
		"username" => "Invalid username.",
		"password" => "Invalid password.",
		"banned" => "You are banned.",
	);
	
	$notice_error = "Unknown error.";
	if (in_array($_GET["invalid"], array_keys($notice_errors))) $notice_error = $notice_errors[$_GET["invalid"]];
}

if (isset($_GET["registered"]))
{
	$notice_success = "Successfully registered. Log in.";
}
?>
<html>
<head>
<?php
require __DIR__ . "/../head.php";
?>
</head>
<body>
<?php
require __DIR__ . "/../header.php";
?>
<main class="main">
<?php require __DIR__ . "/../notice.php"; ?>

<?php if ($session_authenticated):
	require __DIR__ . "/log-out-first.php";
else: ?>
<form class="login-form" action="<?php echo action_to_link("auth"); ?>login-handler.php" method="POST">

<h2>Log in</h2>

<label class="input-label">Login name:</label>
<input class="input-textbox" type="text" name="username" pattern="[A-Za-z0-9_\.-]{3,128}" placeholder="username"><br>

<label class="input-label">Password:</label>
<input class="input-textbox" type="password" name="password" pattern=".{8,4000}" placeholder="password"><br>

<input class="input-submit" type="submit" value="Log in">
</form>
<?php endif; ?>
</main>
<?php
require __DIR__ . "/../footer.php";
?>
</body>
</html>