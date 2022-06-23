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

$postfix = strtolower($_GET["postfix"]);

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

function array_find(array $a, callable $fn) {
    foreach ($a as $key => $value) {
        if ($fn($value, $key, $a)) {
            return $value;
        }
    }
    return false;
}

$metadata_url = "/home/andreu/aina/colles.json";
$gravacions_url = "https://commonvoice.mozilla.org/api/v1/ca/clips/leaderboard?cursor=1";
$validacions_url = "https://commonvoice.mozilla.org/api/v1/ca/clips/votes/leaderboard?cursor=1";

// Takes raw data from the request
$metadata_json = file_get_contents($metadata_url);
$gravacions_json = file_get_contents($gravacions_url);
$validacions_json = file_get_contents($validacions_url);

// Converts it into a PHP object
$data = json_decode($gravacions_json);
$data2 = json_decode($validacions_json);

$metadata_all = json_decode($metadata_json);
$metadata = isset($metadata_all->{$postfix}) ? $metadata_all->{$postfix} : $metadata_all->{"null"};

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
    $a_not_validations = !array_key_exists($a->username, $username_votes) || $a->clips < 360;
    $b_not_validations = !array_key_exists($b->username, $username_votes) || $b->clips < 360;

    if ($a_not_validations && $b_not_validations) return $a->clips < $b->clips;
    else if ($a_not_validations) return $a->clips < $b->clips + $username_votes[$b->username];
    else if ($b_not_validations) return $a->clips + $username_votes[$a->username] < $b->clips;
    else return $a->clips + $username_votes[$a->username] < $b->clips + $username_votes[$b->username];
});

$total_clips = array_map(function ($user) use ($username_votes) {
    if (!array_key_exists($user->username, $username_votes)) return $user->clips;
    if ($user->clips < 360) return $user->clips;    // if less than 360 clips, don't count validations
    return $user->clips + $username_votes[$user->username];
}, $mapped);

$usernames = array_map(function ($user) { return $user->username; }, $mapped);
$clips = array_map(function ($user) { return $user->clips; }, $mapped);

$recorded_clips = array_map(function ($user) use ($username_votes) {
    if (!array_key_exists($user->username, $username_votes)) return 0;
    if ($user->clips < 360) return 0;    // if less than 360 clips, don't count validations
    return $username_votes[$user->username];
}, $mapped);
$recorded_combined = array_combine($usernames, $recorded_clips);

$reduced2 = array_reduce($total_clips, function ($sum, $user) {
    return $sum + $user;
});

$milestones = array_map(function ($milestone) {
    return $milestone->{"hores"};
}, $metadata->{"recompenses"});

$avg_sentence_duration = 10/3600; // in hours
$total_hours = $reduced2*$avg_sentence_duration; // in hours
$rounded_hours = round($total_hours, 1); // total hours

$next_milestone = array_find($milestones, function($value) use ($total_hours) {
    return $value >= $total_hours;
});
if (!$next_milestone && end($milestones) + 1 > $total_hours) $next_milestone = end($milestones); // for victory pictures
else if (!$next_milestone) $next_milestone = ceil($total_hours/25)*25; // if there's no milestone, go in 25s
$progress = $total_hours/$next_milestone*100; // in percentage

?>

<script>
    let postfix = "<?php echo $postfix; ?>";
    let usernames = ["<?php echo implode("\",\"", $usernames); ?>"];
    let clips = [<?php echo implode(",", $clips); ?>];
    let validations = [<?php echo implode(",", $recorded_clips); ?>];
    let total = <?php echo $reduced2; ?>;
    let total1 = <?php echo $reduced; ?>;

    let minutes = clips.map(function (number) {
        return Math.round(number*<?php echo $avg_sentence_duration; ?>*60);
    });
    let val_mins = validations.map(function (number) {
        return Math.round(number*<?php echo $avg_sentence_duration; ?>*60);
    });

    let entropy = clips.map(function (clips) {
        let p = clips/total1;
        if (p == 0) return suma;
        return - p * Math.log(p);
    }).reduce((a, b) => a + b, 0);

    // 20% fa el 80%
    let cumsum = 0;
    let cumprobs = clips.map(function (user_clips) {
        cumsum += user_clips/total1;
        return cumsum;
    });

    console.log(postfix);
    console.log(total1);
    console.log(clips);
    console.log(entropy);
    console.log(cumprobs);
</script>

</head>
<body>
    <div class="full-container">
        <h1><?php echo $metadata->{"nom"}; ?> (<?php echo $metadata->{"abrev"}; ?>)</h1>
        <h2>Total gravat: <strong id="total"><?php echo $reduced2; ?></strong> frases</h2>
        <br />

        <div class="progress-container" style="display:flex;">
            <div style="width: 90%;">
                <div class="progress">
                    <div class="progress-bar progress-bar-animated progress-bar-striped" role="progressbar" style="height: 50px; width: <?php echo $progress; ?>%; background-color: <?php echo $metadata->{'color'}; ?>;" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    <div id="hours"><?php echo $rounded_hours; ?>h</div>
                </div>
            </div>
            
            <div class="milestone">
                <?php if ($next_milestone): ?>
                    <div class="hores"><?php echo $next_milestone; ?>h</div>
                    <div class="frases"><?php echo $next_milestone/$avg_sentence_duration; ?></div>
                <?php endif; ?>
            </div>
        </div>

        <center><a style="color: <?php echo $metadata->{'color'}; ?>;" href="https://commonvoice.mozilla.org/ca"><h2>Ves a donar la teva veu</h2></a></center>
        <br />

        <?php
            if (count($metadata->{"sortejos"}) > 0) echo "<h2>Sortejos</h2>";
        
            $sortejos_div = array_map(function ($sorteig) use ($mapped, $avg_sentence_duration) {
                $div = '<div class="sorteig" id="sorteig_'.$sorteig->{"objectiu-minuts"}.'" style="display:none;">';
                $div .= "<h4>" . $sorteig->{"titol"} . "</h4>";
                $div .= "<br />";
                
                // Imatge
                if ($sorteig->{"img"} != "") $div .= '<center><img src="./'.$sorteig->{"img"}.'" style="width: 80%;" /></center>';
                $div .= "<br />";
                
                // Descripció
                $div .= "<p>".$sorteig->{"descripcio"}."</p>";
                
                // Participants
                $clip_milestone = $sorteig->{"objectiu-minuts"}/60/$avg_sentence_duration;
                $chosen_users = array_filter($mapped, function ($user) use ($clip_milestone) { return $user->clips >= $clip_milestone; });
                $chosen_divs = array_map(function ($user) { return '<div class="participant">'.$user->username.'</div>'; }, $chosen_users);

                if (count($chosen_users) > 0) {
                    $div .= '<h5>Llistat de participants ('. count($chosen_users).')</h5>
                        <div class="llistat" style="display: flex; flex-wrap: wrap; justify-content: space-evenly; font-size: 11px; flex-basis: auto;">
                            '. implode("\n", $chosen_divs).'
                        </div>';
                } else {
                    $div .= '<h5>Encara no hi ha participants.</h5>';
                }
                
                // Countdown
                $div .= '<br /><div><h4 id="countdown_'.$sorteig->{"objectiu-minuts"}.'"></h4></div></div>';

                return $div;
            }, $metadata->{"sortejos"});

            echo implode("\n", $sortejos_div);
        ?>

        <?php
            if (count($metadata->{"recompenses"}) > 0) echo "<h2>Objectius i recompenses</h2>";
        ?>

        <div class="milestones-container" style="display: flex; flex-direction: row; flex-wrap: wrap; justify-content: center; align-items: center;">
            <?php
                $recompenses_divs = array_map(function ($recompensa) use ($total_hours) {
                    $div = '';

                    // Mirar si està 'assolit'
                    $assolit = $total_hours > $recompensa->{"hores"};
                    if (!$assolit) $div .= '<div class="milestone-desc actiu">';
                    else $div .= '<div class="milestone-desc assolit noactiu"><del>';
                    
                    $div .= '<h4>' . $recompensa->{"hores"} . ' hores gravades: ' . $recompensa->{"titol"} . '</h4>';
                    
                    // Mirar si hi ha foto
                    if ($recompensa->{"img"} != "") $div .= '<img src="./' . $recompensa->{"img"} . '"/>';
                    
                    $div .= '<p>' . $recompensa->{"descripcio"} . '</p>';

                    if ($assolit) $div .= '</del><pre style="opacity:1;">Assolit.</pre>';
                    $div .= '</div>';

                    return $div;
                }, $metadata->{"recompenses"});

                echo implode("\n", $recompenses_divs);
            ?>
        </div>

        <h2>Aportació de cada membre</h2>
        <div class="chart-container" style="width:90%; height: 600px;">
            <canvas id="histogram"></canvas>
        </div>
    </div>

    <script>
        // Només deixar actiu la recompensa immediata
        let actius = document.getElementsByClassName("actiu");
        for (let i = 1; i < actius.length; ++i) {
            let div = actius[i];
            div.classList.remove("actiu");
            div.classList.add("noactiu");
        }

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
                        backgroundColor: '<?php echo $metadata->{'color'}; ?>',
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
        <?php
            $countdowns_scripts = array_map(function ($sorteig) {
                $minutes = $sorteig->{"objectiu-minuts"};
                
                $script = '// Set the date we are counting down to
                var ini_countDownDate_'.$minutes.' = new Date("'.$sorteig->{"data-inici"}.'").getTime();
                var countDownDate_'.$minutes.' = new Date("'.$sorteig->{"data-fi"}.'").getTime();';

                $script .= '
                // Update the count down every 1 second
                var x = setInterval(function() {
                    // Get today\'s date and time
                    var now = new Date().getTime();
                    var from = ini_countDownDate_'.$minutes.';
                    var to = countDownDate_'.$minutes.';
                    var id_countdown = "countdown_'.$minutes.'";
                    var id_sorteig = "sorteig_'.$minutes.'";

                    // Find the distance between now and the count down date
                    var distance = to - now;
                        
                    // Time calculations for days, hours, minutes and seconds
                    var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                        
                    if (now - from > 0) {
                        document.getElementById(id_countdown).innerHTML = "Queden <strong>" + days + "</strong> dies <strong>" + hours + "</strong> hores <strong>" + minutes + "</strong> minuts <strong>" + seconds + "</strong> segons";

                        // If the count down is over, write some text 
                        if (distance < 0) {
                            clearInterval(x);
                            document.getElementById(id_countdown).innerHTML = "Ja està.";
                        }
                    } else {
                        // Afegeix no actiu
                        document.getElementById(id_sorteig).classList.add("noactiu");

                        // Format date
                        let options = { weekday: "long", year: "numeric", month: "long", day: "numeric", hour: "numeric", minute: "numeric" };
                        let data = (new Date(from)).toLocaleDateString("ca-ES", options);
                        document.getElementById(id_sorteig).innerHTML = "[<strong>Nou sorteig</strong>] Començarà " + data + ".";
                    }

                    document.getElementById(id_sorteig).style.display = "block";
                }, 1000);
                ';

                return $script;
            }, $metadata->{"sortejos"});

            echo implode("\n", $countdowns_scripts);
        ?>
    </script>
</body>
</html>