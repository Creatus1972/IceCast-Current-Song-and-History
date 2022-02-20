<?php
// Kiszolgáló adatok -> mindenki a sajátját adja meg
$servername = "localhost";
$username = "root";
$password = "";
// Kapcsolat létrehozása
$conn = mysqli_connect($servername, $username, $password);
// Kapcsolat ellenőrzése
if (!$conn) {
  die("A kapcsolat nem jött létre: " . mysqli_connect_error());
}
// Adatbázis lérehozása
$sql = "CREATE DATABASE icecast";
if (mysqli_query($conn, $sql)) {
  echo "<h4 id='message' style='width: auto; text-align: center; color: green'>Az adatbázis sikeresen létrehozva</h4>";
} else {
    // Ha az adatbázis létezik a(z) icecast_history tábla létrehozása
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "icecast";
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    if (!$conn) {
      die("Connection failed: " . mysqli_connect_error());
    }
    $sql = "CREATE TABLE icecast_history (hid INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(2000) NOT NULL, addedtime TIME)";
    if (mysqli_query($conn, $sql)) {
        echo "Az icecast_history adatbázis-tábla létrehozva";
    }
    // echo "<h4 id='message' style='width: auto; text-align: center; color: red;'>Hiba az adatbázis létrehozásakor: AZ ADATBÁZIS MÁR LÉTEZIK!</h4>";
}
mysqli_close($conn);
// Csatlakozás az adatbázishoz
define("DOMAIN", "localhost");
define("DB_USER", "root");
define("DB_PASSWORD", "");
define("DB_NAME", "icecast");
$dbc = mysqli_connect(DOMAIN, DB_USER, DB_PASSWORD, DB_NAME);
$sql = "set names utf8";
mysqli_query($dbc, $sql);
// Adminisztrátor lekérdezés -> csak saját rádióhoz használható
function get_icecast_info($server_ip, $server_port, $admin_user, $admin_password) {
    $index = @file_get_contents("http://".$admin_user.":".$admin_password."@".$server_ip.":".$server_port."/admin/stats.xml");
    if($index) {
        $xml = new DOMDocument(); if(!$xml->loadXML($index)) return false; $arr = array(); $listItem = $xml->getElementsByTagName("source");
        foreach($listItem as $element) {
            if($element->childNodes->length) {
                foreach($element->childNodes as $i){ $arr[$element->getAttribute("mount")][$i->nodeName] = $i->nodeValue; }
            }
        }
        return $arr;
    } return false;
}
$arr = get_icecast_info("IP or HOST", "PORT", "ADMIN NAME", "ADMIN PASSWORD");
// Publikus lekérdezés -> bárki használhatja, mivel nem kér adminisztrátori adatokat
// object => http://185.43.207.41:8000/status-json.xsl
$file = "http://IP or HOSTNAME:PORT/status-json.xsl";
// Objektum átalakítása tömbbé
$array = json_decode(@file_get_contents($file), TRUE);
// Rádió(k) neve
$servername = $array["icestats"]["source"][0]["server_name"] . "<br>";
// Például: echo $servername;
// Egyetlen állomás adatainak lekérdezése
$onedata = $array["icestats"]["source"][0]; // A 0 a legelső rádióállomás
// print_r($onedata);
// Összes állomás adatainak lekérdezése
$alldata = $array["icestats"]["source"];
// print_r($alldata);
// Minden adata lekérdezése
$fulldata = $array["icestats"];
// print_r($fulldata);
$datumido = date("H:i:s");
$songTitle = $array["icestats"]["source"][0]["title"]; // Aktuális zeneszám
$sql = "select count(*) as db from icecast_history where title = '$songTitle'"; // Ellenörzi, hogy létezik - e az éppen aktuális zeneszám az adatbázisban
$tabla = mysqli_query($dbc, $sql);
list($db) = mysqli_fetch_row($tabla);
if ($db < 1) {
    // Aktuális zeneszám mentése -> 10 másodperces ellenőrzési ciklussal. Ha már létezik figyelmen kívül hagyja.
    $sql = "insert into icecast_history (title, addedtime) values ('$songTitle', '$datumido')";
    mysqli_query($dbc, $sql);
}
// Lejátszási előzmények lekérdezése
$sql = "SELECT title, addedtime FROM icecast_history ORDER BY hid DESC LIMIT 1, 10"; // Azért 1, mert az aktuális zeneszám nem szerepel a lejátszási előzményekben
$result = mysqli_query($dbc, $sql);
?>
<!-- Megjelenítés -->
<div id="refresh">
    <pre>
        <?php
        // Rádióállomás neve
        // print_r($arr["/ebm"]["title"]."<br>");
        echo $array["icestats"]["source"][2]["server_name"];
        ?>
    </pre>
    <pre>
        <?php
        // Aktuális zeneszám
        echo $array["icestats"]["source"][2]["title"] . "<br><br>";
        ?>
    </pre>
    <!-- Konténer formázása -->
    <div style="min-height: 500px; width: auto; overflow: auto; padding: 0 60px; font-size: 14px;">
        <?php
        // Lejátszási előzmények megjelenítése a beállított értékig
        while(list($title, $addedtime) = mysqli_fetch_row($result)) {
            echo "<span><hr><strong>" . $addedtime . "</strong>" . " | " . $title .  "</span><br>";
        }
        // Adatbázis-tábla ürítése az utolsó 21 zeneszámig. Azért 21, mert húszat jelenít meg, de az aktuális zeneszám kivételével
        $sql = "DELETE FROM icecast_history WHERE hid < (SELECT * FROM (SELECT (MAX(hid)-20) FROM icecast_history) AS a)"; // A műveletet az UPDATE - el hajtja végre, vagyis 10 másodpercenként!
        mysqli_query($dbc, $sql);
        ?>
    </div>
</div>
