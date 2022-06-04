<!DOCTYPE html>
<head>

<title>Projecte AINA</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

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
                    <div class="progress-bar progress-bar-animated progress-bar-striped bg-success" role="progressbar" style="height: 50px; width: <?php echo $reduced*10/3600/10*100 - 100; ?>%" aria-valuenow="<?php echo $reduced*10/3600/10*100 - 100; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    <div id="hours"></div>
                </div>
            </div>
            <div class="milestone">
                <div class="hores">20h</div>
                <div class="frases">7200</div>
            </div>
        </div>

        <center><a style="color: #198754;" href="https://commonvoice.mozilla.org/ca"><h2>Ves a donar la teva veu</h2></a></center>
        <br />

        <h2>Objectius i recompenses</h2>    
        <div class="milestones-container" style="display: flex; flex-direction: row; flex-wrap: wrap; justify-content: center; align-items: center;">
            <div class="milestone-desc assolit noactiu" style="border: solid 2px orange; color:orange;">
                <del>
                    <h4>10 hores gravades: Objectiu inicial</h4>    
                    <p>Amb 50 persones gravant 70 frases ja ho tenim. Mode fàcil.</p>
                </del>
                <div>Assolit al 03/06/2022</div>
            </div>
            <div class="milestone-desc">
                <h4>20 hores gravades: Biblioteca 24h</h4>    
                <img src="./bibliotecanocturna.png"/>
                <p>Transformem la 3A actual en una biblioteca nocturna a la setmana de finals. Amics també poden venir.</p>
            </div>
            <div class="milestone-desc noactiu">
                <h4>50 hores gravades: WIFI</h4>    
                <img src="./wifi.jpeg"/>
                <p>Amb 50 persones gravant 300 frases, podríem tenir Wi-Fi a la 3A. Cuidado.</p>
            </div>
            <div class="milestone-desc noactiu">
                <h4>100 hores gravades: Piscina</h4>    
                <img src="./piscina.jpg"/>
                <p>Amb 100 persones gravant 300 frases, tindríem una recompensa llegendària: piscina a la nova 3A.<br /><br /> La imatge no és cap montatge. Ja ha passat abans.</p>
            </div>
        </div>

        <h2>Aportació de cada membre</h2>
        <div class="chart-container" style="width:90%; height: 600px;">
            <canvas id="histogram"></canvas>
        </div>
    </div>

    <script>
        // Histogram
        const ctx = document.getElementById('histogram').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'horizontalBar',
            data: {
                labels: usernames,
                datasets: [{
                    label: 'Minuts posats per cada membre',
                    data: minutes,
                    backgroundColor: '#198754',
                }]
            },
            options: {
                scales: {
                    xAxes: [{
                        position: "top",
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                },
                maintainAspectRatio: false,
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