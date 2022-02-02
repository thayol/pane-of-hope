<html>
<head>
<?php
require _WEBROOT_ . "/app/views/global/head.php";
?>
</head>
<body>
<?php

$context_nav_buttons["Listing"] = "characters";

if ($session_is_admin)
{
	$context_nav_buttons["New"] = "character-new";
}

require _WEBROOT_ . "/app/views/global/header.php";
?>
<main class="main">
<?php
$page = 1; // if not set
$page_count = 1; // default fallback
$page_size = Config::$listing_page_size;

$page_count = ceil(Database::characters()->count() / $page_size);

if (!empty($_GET["page"]) && $_GET["page"] > 0 && $_GET["page"] <= $page_count)
{
	$page = intval($_GET["page"]);
}

$offset = ($page - 1) * $page_size;

$result = Database::query("SELECT characters.id AS id, characters.name AS name, characters.original_name AS original_name, characters.gender AS gender, sources.id AS source_id, sources.title AS source_title FROM characters LEFT OUTER JOIN conn_character_source AS conn ON characters.id=conn.character_id LEFT JOIN sources ON conn.source_id=sources.id ORDER BY characters.id ASC LIMIT {$page_size} OFFSET {$offset};");

$characters = array();
if ($result->num_rows > 0)
{
	while ($character = $result->fetch_assoc())
	{
		if (empty($characters[$character["id"]]))
		{
			$characters[$character["id"]] = $character;
			$characters[$character["id"]]["sources"] = array();
			if (!empty($character["source_id"]) && !empty($character["source_title"]))
			{
				$characters[$character["id"]]["sources"][$character["source_id"]] = $character["source_title"];
				unset($characters[$character["id"]]["source_id"]);
				unset($characters[$character["id"]]["source_title"]);
			}
		}
		else
		{
			if (!empty($character["source_id"]) && !empty($character["source_title"]))
			{
				$characters[$character["id"]]["sources"][$character["source_id"]] = $character["source_title"];
			}
		}
	}
}
?>

<?php if (empty($characters)): ?>
<p>There are no characters in the database. (Or there is a database error.)</p>
<?php else: ?>

<table class="table-wide thead-separator alternating-rows">
	<thead>
		<tr>
			<td>ID</td>
			<td>Name</td>
			<td>Source</td>
			<td>Gender</td>
		</tr>
	</thead>
	<tbody>

<?php
foreach ($characters as $character)
{
	$id = $character["id"];
	$name = $character["name"];
	$og_name = $character["original_name"];
	$gender_raw = $character["gender"];
	$formatted_sources = "";

	foreach ($character["sources"] as $source_id => $source_title)
	{
		if (!empty($formatted_sources)) $formatted_sources .= "<br>";
		$source_url = Routes::get_action_url("source") . "?id={$source_id}";
		$formatted_sources .= "<span><a href=\"{$source_url}\">{$source_title}</a></span>";
	}

	if ($og_name != null)
	{
		$name .= " ($og_name)";
	}

	$gender = '<span class="gender-neutral-icon">?</span>';
	if ($gender_raw == 1) $gender = '<span class="gender-female-icon">F</span>';
	if ($gender_raw == 2) $gender = '<span class="gender-male-icon">M</span>';

	$char_url = Routes::get_action_url("character") . "?id={$id}";

	echo '<tr>';
	echo "<td>{$id}</td>";
	echo "<td><a href=\"{$char_url}\">{$name}</a></td>";
	echo "<td>$formatted_sources</td>";
	echo "<td>{$gender}</td>";
	echo '</tr>';
}
echo "</tbody></table>";

echo "<nav>Page: ";
for ($i = $page - Config::$max_seek_page_numbers; $i <= $page + Config::$max_seek_page_numbers; $i++)
{
	if ($i > 0 && $i <= $page_count)
	{
		$url = Routes::get_action_url($action, "page={$i}");
		$class = "nav-button";
		if ($i == $page)
		{
			$class .= " nav-button-current";
		}

		echo "<a class=\"{$class}\" href=\"{$url}\">{$i}</a> ";
	}
}
echo "</nav>";

endif; ?>

</main>
<?php
require _WEBROOT_ . "/app/views/global/footer.php";
?>
</body>
</html>