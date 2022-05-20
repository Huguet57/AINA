<!DOCTYPE html>
<head>
<meta charset="UTF-8">

<?php

$postfix = $_GET["postfix"];

function ends_With( $haystack, $needle ) {
    $length = strlen( $needle );
    if( !$length ) {
        return true;
    }
    $haystack = strtolower($haystack);
    $needle = strtolower($needle);
    return substr( $haystack, -$length ) === $needle;
}

function simplify($user) {
    $x = new stdClass();
    $x->username = $user->username;
    $x->clips = $user->total;
    return $x;
}

$url = "https://commonvoice.mozilla.org/api/v1/ca/clips/leaderboard?cursor=1";

// Takes raw data from the request
$json = file_get_contents($url);

// Converts it into a PHP object
$data = json_decode($json);

$queried = array_filter($data, function ($user) use($postfix) {
	return ends_With($user->username, $postfix);
});
$mapped = array_map("simplify", $queried);
?>

</head>
<body>
<?php echo json_encode($mapped); ?>
</body>
</html>
