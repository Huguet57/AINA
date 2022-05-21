<!DOCTYPE html>
<head>
<meta charset="UTF-8">

<!-- CSS only -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
<!-- JavaScript Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-pprn3073KE6tl6bjs2QrFaJGz5/SUsLqktiwsUTF55Jfv3qYSDhgCecCxMW52nD2" crossorigin="anonymous"></script>

<script src='https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.js'></script>

<link href="./style.css" rel="stylesheet" />

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

    let minutes = clips.map(function (number) {
        return Math.round(number*10/60);
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
    <div class="full-container">
        <h1>Arreplegats de la Zona Universitària (@AZU)</h1>
        <h2>Total gravat: <strong id="total"></strong> frases</h2>
        <br />

        <div class="progress-container" style="display:flex;">
            <div style="width: 90%;">
                <div class="progress">
                    <div class="progress-bar progress-bar-animated progress-bar-striped bg-success" role="progressbar" style="height: 50px; width: <?php echo $reduced*10/3600/10*100; ?>%" aria-valuenow="<?php echo $reduced*10/3600/10*100; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    <div id="hours"></div>
                </div>
            </div>
            <div class="milestone">
                <div class="hores">10h</div>
                <div class="frases">3600</div>
            </div>
        </div>
    </div>

    <div class="chart-container" style="margin-left: 25px; height:500px; width:90%">
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
                    data: minutes,
                    backgroundColor: 'darkgreen',
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
        h2.innerHTML = total;
        let hours = document.getElementById("hours");
        hours.innerHTML = String(Math.round(total*10/60/60*10)/10) + "h";
    </script>
</body>
</html>