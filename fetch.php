<?php

function ends_With( $haystack, $needle ) {
    $length = strlen( $needle );
    if( !$length ) {
        return true;
    }
    return substr( $haystack, -$length ) === $needle;
}

function endsWith($user) {
    return ends_With($user->username, "Huguet");
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

$queried = array_filter($data, "endsWith");
$mapped = array_map("simplify", $queried);

?>

<pre>
<?php print_r($mapped); ?>
</pre>
