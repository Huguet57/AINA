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
$url2 = "https://commonvoice.mozilla.org/api/v1/ca/clips/votes/leaderboard?cursor=1";

// Takes raw data from the request
$json = file_get_contents($url);
$json2 = file_get_contents($url2);

// Converts it into a PHP object
$data = json_decode($json);
$data2 = json_decode($json2);

$queried = array_filter($data, function ($user) use($postfix) {
	return ends_With($user->username, $postfix);
});
$queried2 = array_filter($data2, function ($user) use($postfix) {
	return ends_With($user->username, $postfix);
});

$mapped = array_map("simplify", $queried);
$reduced = array_reduce($mapped, function ($sum, $user) {
    return $sum + $user->clips;
});

$mapped2 = array_map("simplify", $queried2);

$username_votes = [];
foreach ($mapped2 as $key => $value) { 
    $username_votes[$value->username] = $value->clips;
}

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

uasort($mapped, function ($a, $b) use ($username_votes) {
    $a_not_validations = !array_key_exists($a->username, $username_votes) || $a->clips < 300;
    $b_not_validations = !array_key_exists($b->username, $username_votes) || $b->clips < 300;

    if ($a_not_validations && $b_not_validations) return $a->clips < $b->clips;
    else if ($a_not_validations) return $a->clips < $b->clips + $username_votes[$b->username];
    else if ($b_not_validations) return $a->clips + $username_votes[$a->username] < $b->clips;
    else return $a->clips + $username_votes[$a->username] < $b->clips + $username_votes[$b->username];
});

$total_clips = array_map(function ($user) use ($username_votes) {
    if (!array_key_exists($user->username, $username_votes)) return $user->clips;
    if ($user->clips < 300) return $user->clips;    // if less than 300 clips, don't count validations
    return $user->clips + $username_votes[$user->username];
}, $mapped);

$usernames = array_map(function ($user) { return $user->username; }, $mapped);
$clips = array_map(function ($user) { return $user->clips; }, $mapped);

// $combined = array_combine($usernames, $total_clips);
// arsort($combined);

$recorded_clips = array_map(function ($user) use ($username_votes) {
    if (!array_key_exists($user->username, $username_votes)) return 0;
    if ($user->clips < 300) return 0;    // if less than 300 clips, don't count validations
    return $username_votes[$user->username];
}, $mapped);
$recorded_combined = array_combine($usernames, $recorded_clips);

$reduced2 = array_reduce($total_clips, function ($sum, $user) {
    return $sum + $user;
});

$avg_sentence_duration = 10/3600; // in hours
$next_milestone = 50; // in hours
$total_hours = $reduced2*$avg_sentence_duration; // in hours
$progress = $total_hours/$next_milestone*100; // in percentage

?>

<script>
    let postfix = "<?php echo $postfix; ?>";
    let usernames = ["<?php echo implode("\",\"", $usernames); ?>"];
    let clips = [<?php echo implode(",", $clips); ?>];
    let validations = [<?php echo implode(",", $recorded_clips); ?>];
    let total = <?php echo $reduced2; ?>;

    let minutes = clips.map(function (number) {
        return Math.round(number*<?php echo $avg_sentence_duration; ?>*60);
    });
    let val_mins = validations.map(function (number) {
        return Math.round(number*<?php echo $avg_sentence_duration; ?>*60);
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
    console.log(clips);
    console.log(entropy);
    console.log(cumprobs);
</script>

</head>
<body>
    <div class="full-container">
        <h1>Arreplegats de la Zona Universitària (@AZU)</h1>
        <h2>Total gravat: <strong id="total"><?php echo $reduced2; ?></strong> frases</h2>
        <br />

        <div class="progress-container" style="display:flex;">
            <div style="width: 90%;">
                <div class="progress">
                    <div class="progress-bar progress-bar-animated progress-bar-striped bg-success" role="progressbar" style="height: 50px; width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    <div id="hours"><?php echo round($reduced2*$avg_sentence_duration, 1); ?>h</div>
                </div>
            </div>
            <div class="milestone">
                <div class="hores">50h</div>
                <div class="frases">18000</div>
            </div>
        </div>

        <center><a style="color: #198754;" href="https://commonvoice.mozilla.org/ca"><h2>Ves a donar la teva veu</h2></a></center>
        <br />

        <h2>Sortejos</h2>
        <div class="sorteig">
            <h4>Col·lecció gots del Primavera Sound 2001-2019</h4>
            <br />
            <center><img src="./gots.jpeg" style="width: 80%;" /></center>
            <br />
            <p>Totes les persones <strong>amb més de 60 minuts</strong> participaran en un sorteig d'una col·lecció oficial dels gots 2001-2019 del Primavera Sound.</p>
            <?php
                $chosen_users = array_filter($mapped, function ($user) { return $user->clips >= 300; });
                $chosen_divs = array_map(function ($user) { return '<div class="participant">'.$user->username.'</div>'; }, $chosen_users);
            ?>
            <h5>Llistat de participants (<?php echo count($chosen_users); ?>)</h5>
            <div class="llistat" style="display: flex; flex-wrap: wrap; justify-content: space-evenly; font-size: 11px; flex-basis: auto;">
                <?php echo implode("\n", $chosen_divs); ?>
            </div>
            <br />
            <div><h4 id="countdown"></h4></div>
        </div>

        <h2>Objectius i recompenses</h2>    
        <div class="milestones-container" style="display: flex; flex-direction: row; flex-wrap: wrap; justify-content: center; align-items: center;">
            <div class="milestone-desc assolit noactiu">
                <del style="color:orange;">
                    <h4>10 hores gravades: Objectiu inicial</h4>    
                    <p>Amb 50 persones gravant 70 frases ja ho tenim. Mode fàcil.</p>
                </del>
                <pre>Assolit al 03/06/2022</pre>
            </div>
            <div class="milestone-desc assolit noactiu">
                <del style="color:orange;">
                    <h4>20 hores gravades: Biblioteca 24h</h4>    
                    <img src="./bibliotecanocturna.png"/>
                    <p>Transformem la 3A actual en una biblioteca nocturna a la setmana de finals. Amics també poden venir.</p>
                </del>
                <pre>Assolit al 06/06/2022</pre>
            </div>
            <div class="milestone-desc">
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
        // let mes60 = minutes.filter(mins => mins >= 60);
        // let menys60 = minutes.filter(mins => mins < 60);

        // Histogram
        const ctx = document.getElementById('histogram').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'horizontalBar',
            data: {
                labels: usernames,
                datasets: [
                    {
                        label: 'Minuts gravats',
                        data: minutes,
                        backgroundColor: '#198754',
                    },
                    {
                        label: 'Mins validats (amb més de 60 minuts gravats)',
                        data: val_mins,
                        backgroundColor: '#ffd700',
                    }
                ]
            },
            options: {
                scales: {
                    xAxes: [{
                        position: "top",
                        stacked: true,
                        ticks: {
                            beginAtZero: false
                        }
                    }],
                    yAxes: [{
                        stacked: true
                    }]
                },
                maintainAspectRatio: false,
            }
        });
    </script>

    <!-- COUNTDOWN -->
    <script>
    // Set the date we're counting down to
    var countDownDate = new Date("Jun 26, 2022 15:00:00").getTime();

    // Update the count down every 1 second
    var x = setInterval(function() {

    // Get today's date and time
    var now = new Date().getTime();
        
    // Find the distance between now and the count down date
    var distance = countDownDate - now;
        
    // Time calculations for days, hours, minutes and seconds
    var days = Math.floor(distance / (1000 * 60 * 60 * 24));
    var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    var seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
    // Output the result in an element with id="demo"
    document.getElementById("countdown").innerHTML = "Queden <strong>" + days + "</strong> dies <strong>" + hours + "</strong> hores <strong>" + minutes + "</strong> minuts <strong>" + seconds + "</strong> segons";
        
    // If the count down is over, write some text 
    if (distance < 0) {
        clearInterval(x);
        document.getElementById("countdown").innerHTML = "Ja està.";
    }
    }, 1000);
    </script>
</body>
</html>