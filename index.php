<?
$path = getcwd();
if (isset($_POST["submit"]) && $_POST["submit"] == "submit") {
	$candidates_data = $_POST["candidates"];
	$ballots_data = $_POST["ballots"];
}
$numerator = 5;
if (isset($_POST["num"]) && preg_match("/^[0-9]+$/",$_POST["num"]) == 1)
{
	$numerator = intval($_POST["num"]);
}
$denumerator = 10;
if (isset($_POST["denum"]) && preg_match("/^[0-9]+$/",$_POST["denum"]) == 1)
{
	$denumerator = intval($_POST["denum"]);
	if ($denumerator == 0)
		$denumerator = 10;
}
$selstrict = ' checked="checked"';
$dostrict = "+";
if (intval(count($_POST)) > 2 && !isset($_POST["strict"]))
{
	$selstrict = "";
	$dostrict = "";
}


if (isset($_POST["mode"]) && $_POST["mode"] == "schulze")
{
	$sels = ' checked="checked"';
	$i = 0;
        $candidates = explode("\r\n",$candidates_data);
	foreach ($candidates as $candidate)
	{
		$i++;
		if (preg_match("/^[A-Z0-9 _-]+$/i",$candidate) != 1)
		{
			$result = "FEHLER: Der Name des $i. Kandidaten ist ungültig.\nKandidatennamen dürfen nur aus Buchstaben, Zahlen, Leerzeichen und folgenden weiteren Zeichen bestehen: _ -";
			goto end;
		}
	}
	file_put_contents("$path/files/candidates.txt",$candidates_data);
        $i = 0;
        $ballots = explode("\r\n",$ballots_data);
        foreach ($ballots as $ballot)
        {
                $i++;
                if (preg_match("/^([A-Z0-9 _-]+| |\\/|;|,)*$/i",$ballot) != 1)
                {
                        $result = "FEHLER: Der $i. Stimmzettel enthält ungültige Zeichen.";
                        goto end;
                }
        }
	file_put_contents("$path/files/ballots.txt",$ballots_data);
	$result = str_replace($path,"",shell_exec("lua $path/schulze -c $path/files/candidates.txt -b $path/files/ballots.txt -q $numerator/$denumerator".$dostrict." 2>&1"));
}
elseif (isset($_POST["mode"]) && $_POST["mode"] == "schulze2")
{
	$sels2 = ' checked="checked"';
	$i = 0;
        $candidates = explode("\r\n",$candidates_data);
	foreach ($candidates as $candidate)
	{
		$i++;
		if (preg_match("/^[A-Z0-9 _-]+$/i",$candidate) != 1)
		{
			$result = "FEHLER: Der Name des $i. Kandidaten ist ungültig.\nKandidatennamen dürfen nur aus Buchstaben, Zahlen, Leerzeichen und folgenden weiteren Zeichen bestehen: _ -";
			goto end;
		}
	}
	file_put_contents("$path/files/candidates.txt",$candidates_data);
        $i = 0;
	$pb = array();
        $ballots = explode("\r\n",$ballots_data);
        foreach ($ballots as $ballot)
        {
                $i++;
                if (preg_match("/^[0-9,]+$/i",$ballot) != 1)
                {
                        $result = "FEHLER: Der $i. Stimmzettel enthält ungültige Zeichen.";
                        goto end;
                }
		if (strlen($ballot) > 1)
		{
			$ballots_numbers[] = explode(",",$ballot);
		}
	}
	for ($j = 0; $j < count($ballots_numbers); $j++)
	{
		for ($i = 0; $i < count($ballots_numbers[$j]); $i++)
		{
			if (!is_numeric($ballots_numbers[$j][$i]))
			{
				$ballots_numbers[$j][$i] = PHP_INT_MAX;
			}
			else
			{
				$ballots_numbers[$j][$i] = intval($ballots_numbers[$j][$i]);
			}
		}
	}
	$ballots_generated = "";
	foreach ($ballots_numbers as $ballot_numbers)
	{
		$ballot_names = $candidates;
		if (count($ballot_numbers) != count($ballot_names))
		{
			$result = "FEHLER: Der $i. Stimmzettel enthält nicht die richtige Anzahl von Kandidaten.";
			goto end;
		}
		array_multisort($ballot_numbers, $ballot_names);
		$ballots_generated .= $ballot_names[0];
		for ($i = 1; $i < count($ballot_names); $i++)
		{
			if ($ballot_numbers[$i-1] == $ballot_numbers[$i])
			{
				$ballots_generated .= ",";
			}
			else
			{
				$ballots_generated .= ";";
			}
			$ballots_generated .= $ballot_names[$i];
		}
		$ballots_generated .= "\n";
	}
	$result = "Berechnete Stimmzettel:\n\n";
	$result .= $ballots_generated . "\n";
	file_put_contents("$path/files/ballots.txt",$ballots_generated);
	$result .= str_replace($path,"",shell_exec("lua $path/schulze -c $path/files/candidates.txt -b $path/files/ballots.txt -q $numerator/$denumerator".$dostrict." 2>&1"));
}
elseif (isset($_POST["mode"]))
{
	$sela = ' checked="checked"';
        $i = 0;
        $candidates = explode("\r\n",$candidates_data);
        foreach ($candidates as $candidate)
        {
                $votes[$i]['j'] = 0;
                $votes[$i]['e'] = 0;
                $votes[$i]['n'] = 0;
		$votes[$i]['name'] = $candidate;
		$votes[$i]['perc'] = -1.0;
                $i++;
                if (preg_match("/^[A-Z0-9 _-]+$/i",$candidate) != 1)
                {
                        $result = "FEHLER: Der Name des $i. Kandidaten ist ungültig.\nKandidatennamen dürfen nur aus Buchstaben, Zahlen, Leerzeichen und folgenden weiteren Zeichen bestehen: _ -";
                        goto end;
                }
        }
	$ncandidates = count($candidates);
        $i = 0;
        $ballots = explode("\r\n",$ballots_data);
        foreach ($ballots as $ballot)
        {
                $i++;
                if (preg_match("/^(j|e|n)+$/i",$ballot) != 1)
                {
                        $result = "FEHLER: Der $i. Stimmzettel enthält ungültige Zeichen.";
                        goto end;
                }
		if (strlen($ballot) > $ncandidates)
		{
                        $result = "FEHLER: Der $i. Stimmzettel enthält mehr Stimmen als es Kandidaten gibt.";
                        goto end;
                }
                if (strlen($ballot) < $ncandidates)
                {
                        $result = "FEHLER: Der $i. Stimmzettel enthält weniger Stimmen als es Kandidaten gibt.";
                        goto end;
                }
        }
	$nballots = count($ballots);
        foreach ($ballots as $ballot)
        {
		$letters = str_split(strtolower($ballot));
		$i = 0;
		foreach ($letters as $letter)
		{
			$votes[$i][$letter]++;
			$i++;
		}
        }
        $result = "$ncandidates Kandidat" . ($ncandidates != 1?"en":"") . ". ";
	$result .= "$nballots abgegebene Stimme" . ($nballots != 1?"n":"") . ".\n";
	foreach ($votes as $i => $vote)
	{
		if ($vote['j'] + $vote['n'] > 0)
			$votes[$i]['perc'] = 100.0 * $vote['j'] / ($vote['j'] + $vote['n']);
	}
	function cmp_by_perc($a, $b)
	{
		return ($b['perc'] - $a['perc']) * 1000000.0;
	}
	usort($votes, "cmp_by_perc");
	$i = 1;
	foreach ($votes as $vote)
	{
		$result .= "$i. {$vote['name']} &nbsp; ";
		if ($vote['perc'] < 0)
		{
			$result .= "NaN ";
			$accepted = "undefiniert";
		}
		else if (($vote['perc'] > 100.0 * $numerator / $denumerator) || (strlen($dostrict) == 0 && $vote['perc'] >= 100.0 * $numerator / $denumerator))
		{
                        $result .= round($vote['perc'],3) . "% ";
			$accepted = "akzeptiert";
		}
		else
		{
			$result .= round($vote['perc'],3) . "% ";
			$accepted = "nicht akzeptiert";
		}
		$result .= "({$vote['j']} Ja-Stimme" . ($vote['j'] != 1?"n":"") . ", {$vote['e']} Enthaltung" . ($vote['e'] != 1?"en":"") . ", {$vote['n']} Nein-Stimme" . ($vote['n'] != 1?"n":"") . "), also $accepted\n";
		$i++;
	}
}
else
{
        $sels2 = ' checked="checked"';
}
end:
?>

<!DOCTYPE html>
<html lang="de">
	<head>
		<meta charset="utf-8">
		<title>schulze-online</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="description" content="Online-Interface zum Auswerten von Stimmzetteln bei Wahlen und Abstimmungen nach Schulze.">
		<meta name="author" content="Piratenpartei Österreichs">

		<!-- Le styles -->
		<link href="css/bootstrap.css" rel="stylesheet">
		<style type="text/css">
			body {
				background-color: #4c2582;
				padding-top: 60px;
				padding-bottom: 40px;
			}
			footer {
				color: white;
			}
		</style>
		<link href="css/bootstrap-responsive.css" rel="stylesheet">

		<!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
		<!--[if lt IE 9]>
		<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
		<![endif]-->
	</head>

	<body>
		<div class="container">
			<div class="row">
				<div class="span12">
					<div class="well">
						<h1>schulze-online</h1>
						<p>Online-Auszählungstool für Abstimmungen und Wahlen nach Schulze.</p>
						<form action="index.php" method="POST">
                                                        <h4>Modus</h4>
							<p>
								<input type="radio" name="mode" value="acceptance" <? echo $sela; ?> onclick="javascript:document.getElementById('schulze').style.display = 'none';document.getElementById('schulze2').style.display = 'none';document.getElementById('acceptance').style.display = 'block'"> Akzeptanz &nbsp;
								<input type="radio" name="mode" value="schulze" <? echo $sels; ?> onclick="javascript:document.getElementById('acceptance').style.display = 'none';document.getElementById('schulze2').style.display = 'none';document.getElementById('schulze').style.display = 'block'"> SchulzeAlt &nbsp;
								<input type="radio" name="mode" value="schulze2" <? echo $sels2; ?> onclick="javascript:document.getElementById('acceptance').style.display = 'none';document.getElementById('schulze').style.display = 'none';document.getElementById('schulze2').style.display = 'block'"> SchulzeNeu

							&nbsp; &nbsp; &middot; &nbsp; &nbsp;
								Akzeptanzhürde: <input type="text" name="num" size="5" value="<? echo $numerator; ?>"> / <input type="text" name="denum" size="5" value="<? echo $denumerator; ?>"> &nbsp; &middot; &nbsp; <input type="checkbox" name="strict" <? echo $selstrict; ?>> strenge Mehrheit
							</p>
							<h4>Kandidaten</h4>
							<p>Jeder Kandidat muss in eine eigene Zeile geschrieben werden.</p>
							<p><textarea name="candidates" style="width:25%;" rows="6"><?echo $candidates_data;?></textarea></p>
							<h4>Stimmzettel</h4>
							<p id="schulze" <? if (strlen($sels) == 0) { echo 'style="display:none;"'; } ?>>Jeder Stimmzettel wird in einer eigenen Zeile eingetragen. Ganz links steht der höchst-gereihte Kandidat. Sind mehrere Kandidaten gleich-gereiht werden diese mit <code>,</code> voneinander getrennt. Sind Kandidaten nicht gleich-gereiht so wird mit <code>;</code> getrennt. Falls Akzeptanz im gleichen Wahlgang durchgeführt wird, so sind die Blöcke <i>Ja</i>, <i>Enthaltung</i>, <i>Nein</i> je durch ein <code>/</code> getrennt.<br />
							Erlaubte Zeichen: <code>Kandidaten</code>, <code>;</code>, <code>,</code> und <code>/</code></p>
							<p id="schulze2" <? if (strlen($sels2) == 0) { echo 'style="display:none;"'; } ?>>Jeder Stimmzettel wird in einer eigenen Zeile eingetragen. Entsprechend der Reihenfolge der Kandidaten wird von links nach rechts für jeden Kandidaten, Komma-getrennt die Zahl vom Stimmzettel übertragen.<br />
							Erlaubte Zeichen: <code>Ziffern</code> und <code>,</code></p>
							<p id="acceptance" <? if (strlen($sela) == 0) { echo 'style="display:none;"'; } ?>>Jeder Stimmzettel wird in einer eigenen Zeile eingetragen. Entsprechend der Reihenfolge der Kandidaten wird von links nach rechts für jeden Kandidaten eingetragen <code>j</code> für <i>Ja</i>, <code>e</code> für <i>Enthaltung</i> oder <code>n</code> für <i>Nein</i>.<br />
                                                        Erlaubte Zeichen: <code>j</code>, <code>e</code> und <code>n</code></p>
							<p><textarea name="ballots" style="width:90%;" rows="18"><?echo $ballots_data;?></textarea></p>
							<input name="submit" type="hidden" value="submit"></input>
							<button type="submit" class="btn btn-primary">Auswerten</button>
						</form>
						<h4>Ergebnis</h4>
						<p><pre><? echo $result; ?></pre></p>
					</div><!--/well-->
				</div><!--/span12-->
			</div><!--/row-->

			<footer>
				<p><a href="index.php.txt">Source Code PHP</a> &middot; <a href="schulze.txt">schulze.lua</a> &middot; <a href="schulze.LICENSE">LICENCE for schulze.lua</a>
				</p>
			</footer>
		</div><!--/.fluid-container-->

		<!-- Le javascript
		================================================== -->
		<!-- Placed at the end of the document so the pages load faster -->
		<script src="js/bootstrap.min.js"></script>
	</body>
</html>

