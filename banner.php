<?php

error_reporting(E_ALL);
ini_set("error_reporting", E_ALL);

if (!isset($_GET["ip"])) {
	echo("ip query parameter not set, aborting");
	die();
}

$serverIp = $_GET["ip"];
$serverPort = 11775;

if (isset($_GET["port"])) {
	$serverPort = $_GET["port"];
}

function saveServerCache($filepath, $info) {
	$file = fopen($filepath, "w");
	fwrite($file, $info);
	fclose($file);
}

function getServerInfo($ip, $port) {
	$cwd = getcwd();
	$filepath = "${cwd}/cache/${ip}_${port}.json";

	// if cached info doesn't exist, or is older than a minute
	// try fetching new info
	if (!file_exists($filepath) or time() - filemtime($filepath) > 1 * 3600) {
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, "http://${ip}:${port}/");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);

		$data = curl_exec($curl);

		if (curl_errno($curl) == CURLE_OPERATION_TIMEDOUT) {
			return null;
		}

		saveServerCache($filepath, $data);

		return json_decode($data, true);
	}
	// otherwise, return cached
	else {
		return json_decode(file_get_contents($filepath), true);
	}

	return null;
}

$server = getServerInfo($serverIp, $serverPort);

// image stuff 200x113

// create image container
$container = imagecreatetruecolor(600, 120);

// allocate colours
$bgColour = imagecolorallocate($container, 61, 67, 70);
$textColour = imagecolorallocate($container, 255, 255, 255);
$whiteBgTransparent = imagecolorallocatealpha($container, 255, 255, 255, 80);

// fonts
$cwd = getcwd();
$titleFont = "${cwd}/fonts/OpenSans-ExtraBoldItalic.ttf";
$titleFontSize = 16;
$contentFont = "${cwd}/fonts/OpenSans-Bold.ttf";
$contentFontSize = 12;

// fill container with background colour
imagefill($container, 0, 0, $bgColour);

header("Content-Type: image/png");

// if the server is offline, print 'server offline' and die
if ($server == null) {
	imagettftext($container, $titleFontSize, 0, 24, 32, $textColour, $titleFont, "Server offline");
	$data = imagepng($container);
	echo($data);
	die();
}

// map image
imagecopyresampled($container, imagecreatefromjpeg("${cwd}/images/${server["mapFile"]}.jpg"), 6, 4, 0, 0, 200, 113, 350, 197);
imagefilledrectangle($container, 6, 80, 200 + 5, 113 + 3, $whiteBgTransparent);

// server name
imagettftext($container, $titleFontSize, 0, 215, 32, $textColour, $titleFont, "(${server["numPlayers"]}/${server["maxPlayers"]}) ${server["name"]}");

// server ip and port
imagettftext($container, $contentFontSize, 0, 215, 50, $textColour, $contentFont, "${serverIp}:${serverPort}");

// map name
$bound = imagettfbbox($titleFontSize, 0, $contentFont, $server["map"]);
imagettftext($container, $titleFontSize, 0, (600 - ($bound[2] - $bound[0]) / 2) - 492, 106, $bgColour, $contentFont, $server["map"]);

// create the png and display it
$data = imagepng($container);
echo($data);
imagedestroy($container); // clean up

?>