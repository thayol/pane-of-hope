<?php

if (isset($_GET["invalid"]))
{
	$notice_error = "Could not add character.";
}

$context_nav_buttons["Listing"] = "characters";

if ($session_is_admin)
{
	$context_nav_buttons["New"] = "character-new";
	if ($action == "character-edit") $context_nav_buttons["Edit"] = "character-edit";
}

$id = "";
$name = "";
$og_name = "";
$gender = 0;

if ($action == "character-edit")
{
	$character_found = false;
	if (!empty($_GET["id"]))
	{
		$id = intval($_GET["id"]);
		if ($id > 0)
		{
			$db = db_connect();
			$result = $db->query("SELECT * FROM characters WHERE id={$id} ORDER BY id ASC;");
			if ($result->num_rows == 1)
			{
				$character_found = true;
				$character_temp = $result->fetch_assoc();
				
				$id = $character_temp["id"];
				$name = htmlspecialchars_decode($character_temp["name"], $htmlspecialchars_flags);
				$og_name = htmlspecialchars_decode($character_temp["original_name"], $htmlspecialchars_flags);
				$gender = $character_temp["gender"];
			}
		}
	}
}

if ($action == "character-edit")
{
	$form_action = action_to_link("characters") . "character-edit-handler.php";
}
else
{
	$form_action = action_to_link("characters") . "character-new-handler.php";
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

<?php if ($session_is_admin): ?>
<form class="login-form" action="<?php echo $form_action; ?>" method="POST">

<h2>New Character</h2>

<label class="input-label">Name:</label>
<input class="input-textbox" type="text" name="name" value="<?php echo htmlspecialchars($name, ENT_COMPAT); ?>" placeholder="English Name" required><br>

<label class="input-label">Original Name:</label>
<input class="input-textbox" type="text" name="original_name" value="<?php echo htmlspecialchars($og_name, ENT_COMPAT); ?>" placeholder="Original Name"><br>

<label class="input-label">Gender:</label>
<select class="input-select" name="gender">
	<option value="0" <?php if ($gender == 0) echo "selected"; ?>>N/A</option>
	<option value="1" <?php if ($gender == 1) echo "selected"; ?>>Female</option>
	<option value="2" <?php if ($gender == 2) echo "selected"; ?>>Male</option>
</select><br>

<input type="hidden" name="id" value="<?php echo $id; ?>">

<input class="input-submit" type="submit" value="Create">
</form>
<?php else: ?>
<p>Unauthorized.</p>
<?php endif; ?>

</main>
<?php
require __DIR__ . "/../footer.php";
?>
</body>
</html>