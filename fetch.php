<!DOCTYPE html>
<head>
<meta charset="UTF-8">

<script src='https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.js'></script>

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
$reduced = array_reduce($mapped, function ($sum, $user) {
    return $sum + $user->clips;
});

?>

<script>
    let postfix = "<?php echo $postfix; ?>";
    let json = <?php echo json_encode($mapped); ?>;
    let total = <?php echo $reduced; ?>;

    let usernames = Object.values(json).map(function (user) {
        return user.username;
    });

    let clips = Object.values(json).map(function (user) {
        return user.clips;
    });

    let entropy = clips.map(function (clips) {
        let p = clips/total;
        if (p == 0) return suma;
        return - p * Math.log(p);
    }).reduce((a, b) => a + b, 0);

    // 20% fa el 80%
    let cumsum = 0;
    let cumprobs = clips.map(function (user_clips) {
        cumsum += user_clips/total;
        return cumsum;
    });

    console.log(postfix);
    console.log(total);
    console.log(json);
    console.log(clips);
    console.log(entropy);
    console.log(cumprobs);
</script>

</head>
<body>
    <h2>Total: <span id="total"></span> frases</h2>

    <div class="chart-container" style="position: relative; height:500px; width:800px">
        <canvas id="histogram"></canvas>
    </div>

    <script>
        // Histogram
        const ctx = document.getElementById('histogram').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: usernames,
                datasets: [{
                    label: 'Minuts posats per cada membre',
                    data: clips,
                    backgroundColor: 'green',
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                        beginAtZero: true
                        }
                    }]
                }
            }
        });

        // Totals
        let h2 = document.getElementById("total");
        h2.outerHTML = total;
    </script>
</body>
</html>