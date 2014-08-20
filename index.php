<?php
define("myPath", dirname(__FILE__));define("appVersion", "1.692");$RESOURCES = json_decode("{\"php\\/classes\\/class.tree.php\":[15,4326],\"php\\/classes\\/class.profile.php\":[4341,12827],\"php\\/classes\\/class.posts.php\":[17168,9130],\"php\\/classes\\/class.postfile.php\":[26298,361],\"php\\/classes\\/class.post.php\":[26659,4188],\"php\\/classes\\/class.image.php\":[30847,1982],\"php\\/classes\\/class.history.php\":[32829,1384],\"php\\/classes\\/class.groups.php\":[34213,737],\"php\\/classes\\/class.group.php\":[34950,1405],\"php\\/classes\\/class.files.php\":[36355,930],\"php\\/classes\\/class.feed.php\":[37285,1811],\"php\\/classes\\/class.comment.php\":[39096,150],\"php\\/classes\\/class.SMTP.php\":[39246,25613],\"php\\/classes\\/class.PHPMailer.php\":[64859,74678],\"php\\/classes\\/class.Markdown.php\":[139537,43251],\"templates\\/fullmail.tpl\":[182788,6366],\"templates\\/onepost.tpl\":[189154,9469],\"templates\\/fullpost.tpl\":[198623,3404],\"resources\\/css\\/bootstrap.min.css\":[202027,97399],\"resources\\/css\\/styles.css\":[299426,1464],\"resources\\/css\\/tree.css\":[300890,1310],\"resources\\/images\\/favicon.ico\":[302200,67646],\"resources\\/images\\/ownunity.png\":[369846,5419],\"resources\\/js\\/jquery.js\":[375265,93106],\"resources\\/js\\/bootstrap.min.js\":[468371,27726],\"resources\\/js\\/script.js\":[496097,11395],\"resources\\/js\\/utils.js\":[507492,696],\"resources\\/js\\/json2.js\":[508188,17413],\"resources\\/js\\/jstorage.js\":[525601,17688],\"resources\\/js\\/openpgp.min.js\":[543289,222346],\"resources\\/js\\/crypto.js\":[765635,2995],\"templates\\/crypto_frameset.tpl\":[768630,880],\"resources\\/fonts\\/glyphicons-halflings-regular.eot\":[769510,14079],\"resources\\/fonts\\/glyphicons-halflings-regular.svg\":[783589,63157],\"resources\\/fonts\\/glyphicons-halflings-regular.ttf\":[846746,29512],\"resources\\/fonts\\/glyphicons-halflings-regular.woff\":[876258,16448],\"resources\\/images\\/person.png\":[892706,3005],\"resources\\/images\\/person-big.png\":[895711,4677],\"resources\\/images\\/person-medium.png\":[900388,1784],\"resources\\/images\\/person-small.png\":[902172,602],\"resources\\/images\\/person-mini.png\":[902774,445],\"resources\\/images\\/loader.gif\":[903219,3208],\"templates\\/navbar.tpl\":[906427,4423],\"templates\\/register.tpl\":[910850,2229],\"templates\\/login.tpl\":[913079,1617],\"templates\\/lost.tpl\":[914696,2202],\"templates\\/credentials.tpl\":[916898,2360],\"templates\\/info_de.tpl\":[919258,10],\"templates\\/info_en.tpl\":[919268,10],\"templates\\/homepage.tpl\":[919278,984],\"templates\\/profil.tpl\":[920262,8019],\"templates\\/search.tpl\":[928281,1402],\"templates\\/contacts.tpl\":[929683,830],\"templates\\/groups.tpl\":[930513,1743],\"templates\\/notifications.tpl\":[932256,1186],\"templates\\/crypto.tpl\":[933442,6185],\"templates\\/group.tpl\":[939627,16895],\"templates\\/userprofil.tpl\":[956522,2495],\"templates\\/sharepostemail.tpl\":[959017,3112],\"templates\\/editpost.tpl\":[962129,1211],\"templates\\/full.tpl\":[963340,8795],\"templates\\/overview.tpl\":[972135,23740],\"templates\\/main2.tpl\":[995875,8382]}", true);
function getRes($resname) { global $RESOURCES;$R = $RESOURCES[$resname];
$fp = fopen(dirname(__FILE__)."/resources_ownunity.php", "r");
fseek($fp, $R[0]);
$RES = fread($fp, $R[1]);
$RES = str_replace("ofFILENAME", basename($_SERVER["PHP_SELF"]), $RES);fclose($fp);
return $RES;}if(isset($_GET["RES"]) && $_GET["RES"]!="") {
if(stristr($_GET["RES"],".css")) header("Content-type: text/css");
else if(stristr($_GET["RES"],".jpg")) header("Content-type: image/jpeg");
else if(stristr($_GET["RES"],".png")) header("Content-type: image/png");
else if(stristr($_GET["RES"],".js")) header("Content-type: text/javascript");
$RES = getRes($_GET["RES"]);echo $RES;exit;
}

function getINI($fn, $key, $default, $comment="", $autocreate=true) {
global $appINI;
if(!is_array($key)) $key = explode("/", $key);
if(!isset($appINI[$fn])) {
$appINI[$fn] = readINI(dataDir.'/'.$fn);
}
if(isset($appINI[$fn][$key[0]])) {
if(isset($appINI[$fn][$key[0]][$key[1]])) {
return $appINI[$fn][$key[0]][$key[1]];
}}
if($autocreate) {
$appINI[$fn][$key[0]][$key[1]] = $default;
$appINI[$fn][$key[0]][$key[1].'_comment'] = trim($comment);
writeINI(dataDir.'/'.$fn, $appINI[$fn]);
}
return $default;
}
function readINI($fn) {
$data = file($fn, FILE_IGNORE_NEW_LINES |  FILE_SKIP_EMPTY_LINES );
$ini = array();
$lastkey = "";
for($i=1;$i<count($data);$i++) {
$data[$i] = trim($data[$i]);
$comment = "";
if(stristr($data[$i],"//")) {
$comment = substr($data[$i], strpos($data[$i],"//")+2);
$data[$i] = substr($data[$i], 0, strpos($data[$i],"//"));
}
$data[$i] = trim($data[$i]);
if(substr($data[$i],0,1)=="[" && substr($data[$i],-1)=="]") {
$lastkey = substr($data[$i],1,-1);
} else {
$key = substr($data[$i], 0, strpos($data[$i], "="));
$val = substr($data[$i], strpos($data[$i], "=")+1);
$ini[$lastkey][$key] = $val;
$ini[$lastkey][$key."_comment"] = $comment;
}}
return $ini;
}
function writeINI($fn, $data) {
$ini = "<?php exit; ?>\n";
foreach($data as $key => $val) {
$ini .= "\n[".$key."]\n";
foreach($val as $key2 => $val2) {
if(stristr($key2,"_comment")) continue;
$ini .= trim($key2)."=".trim($val2).($data[$key][$key2."_comment"]!="" ? " //".$data[$key][$key2."_comment"] : "")."\n";
}}
file_put_contents($fn, $ini);
chmod($fn, 0664);
}
function readJson($fn, $default=array()) {
if(!file_Exists($fn)) return $default;
$J = file_Get_contents($fn);
if($J!="") {
return json_decode($J, true);
}
return $default;
}
function writeJson($fn, $data) {
file_put_contents($fn, json_encode($data));
chmod($fn, 0664);
}
function readContent($fn, $default="") {
if(file_exists($fn) && is_readable($fn)) {
return file_get_contents($fn);
} else {
return $default;
}}
function purifyFilename($fn) {
$fn = strtolower($fn);
$fn = str_replace(" ", "_", $fn);
$fn = str_replace("..", "_", $fn);
$fn = str_replace("/", "_", $fn);
$fn = str_replace("&", "_", $fn);
$fn = str_replace("<", "_", $fn);
$fn = str_replace(">", "_", $fn);
$fn = str_replace("'", "_", $fn);
$fn = str_replace('"', "_", $fn);
return $fn;
}
function setS($name, $val) {
$_SESSION[SESSKEY][$name] = $val;
}
function getS($name) {
if(!isset($_SESSION[SESSKEY])) return "";
return $_SESSION[SESSKEY][$name];
}
function trans($de,$en) {
if(isset($_COOKIE[SESSKEY."language"]) && $_COOKIE[SESSKEY."language"]=="de") return $de;
return $en;
}
function me() {
return getS("user");
}
function now() {
return date("Y-m-d H:i:s");
}
function checkfordir($dir) {
if(!file_exists(dirname(__FILE__).'/files/'.SESSKEY.'/'.$dir)) {
mkdir(dirname(__FILE__).'/files/'.SESSKEY.'/'.$dir, 0775, true);
chmod(dirname(__FILE__).'/files/'.SESSKEY.'/'.$dir, 0775);
}
return dirname(__FILE__).'/files/'.SESSKEY.'/'.$dir;
}
function isfilled($x) {
if(isset($x) && strlen($x)>0) return true;
return false;
}
function formatDate($D) {
if(!isset($D) || $D=='' || substr($D,0,10)=='0000-00-00') return '';
$d = explode("-", substr($D,0,10));
$dx = $d[2].'.'.$d[1].'.'.$d[0];
return $dx;
}
function formatDateTime($D) {
$t = substr($D,11);
$d = explode("-", substr($D,0,10));
$dx = $d[2].'.'.$d[1].'.'.$d[0]." ".$t;
return $dx;
}
function formatTime($D) {
if($D=='') return '';
$t = substr($D,0,5);
if($t=='00:00') return '';
return $t;
}
function getWochentag($date, $short=true) {
$T = strtotime($date);
$W = date("w", $T);
return formatWochentag($W, $short);
}
function formatWochentag($nr, $short=true) {
if($nr>6) $nr -= 7;
if(defined('DateLanguage') && DateLanguage=='en') {
$_DAYS_short = array('Su','Mo','Tu','We','Th','Fr','Sa');
$_DAYS_long = array('Sunday', 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
} else {
$_DAYS_short = array('So','Mo','Di','Mi','Do','Fr','Sa');
$_DAYS_long = array('Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag');
}
if($short==true) return $_DAYS_short[$nr];
return $_DAYS_long[$nr];
}
function getMonthName($m) {
$m = (int)$m;
while($m<1) $m+=12;
while($m>12) $m-=12;
$MN["de"] = array("Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember");
$MN["en"] = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
if(defined('DateLanguage') && DateLanguage=='en') {
$M = $MN["en"];
} else {
$M = $MN["de"];
}
return $M[(int)$m-1];
}
define('_TIME', time());
define('_DATE0', '0000-00-00 00:00:00');
define('_DATE_today', date('Y-m-d'));
define('_DATE_yesterday', date('Y-m-d', _TIME-60*60*24));
define('_DATE_beforeyesterday', date('Y-m-d', _TIME-60*60*24*2));
define('_DATE_7days', _TIME-60*60*24*7);
define('_DATE_4days', _TIME-60*60*24*4);
define('_DATE_14days', _TIME-60*60*24*14);
if(defined('DateLanguage') && DateLanguage=='en') {
$_DAYS_short = array('Su','Mo','Tu','We','Th','Fr','Sa');
$_DAYS_long = array('Sunday', 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
} else {
$_DAYS_short = array('So','Mo','Di','Mi','Do','Fr','Sa');
$_DAYS_long = array('Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag');
}
function formatDateHuman($D) {
global $_DAYS_short, $_DAYS_long;
if($D==_DATE0)  return '-';
if(defined('DateLanguage') && DateLanguage=='en') {
$_DAYS_short = array('Su','Mo','Tu','We','Th','Fr','Sa');
$_DAYS_long = array('Sunday', 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
} else {
$_DAYS_short = array('So','Mo','Di','Mi','Do','Fr','Sa');
$_DAYS_long = array('Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag');
}
$t = substr($D,11,5);
$day = substr($D,0,10);
$d = explode("-", $day);
$stt = strtotime($D);
$tag = 'vor wenigen Sekunden ';
/*if($stt>_TIME) {
$tag = 'IN DER ZUKUNFT! ';
} else */if($stt>=_TIME-60) {
$tag = 'vor weniger als einer Minute ';
} else if($stt>_TIME-60*60*6) {
$diffMin = round((_TIME-$stt)/60);
if($diffMin>=60) {
$h = floor($diffMin/60);
if($h==1) {
$m = $diffMin - $h*60;
$tag = ' vor einer Stunde ';
if($m!=0) $tag .= 'und '.$m.' Minuten';
} else $tag = ' vor '.$h.' Stunden ';
} else {
if($diffMin==1) $tag = ' vor einer Minute ';
else $tag = ' vor '.$diffMin.' Minuten ';
}} else if($day==_DATE_today) $tag = 'Heute '.' um '.$t.' Uhr';
else if($day==_DATE_yesterday) $tag = 'Gestern '.' um '.$t.' Uhr';
else if($day==_DATE_beforeyesterday) $tag = 'Vorgestern '.' um '.$t.' Uhr';
else if($stt>_DATE_4days && $stt<=_DATE_7days) $tag = ' am '.$_DAYS_long[date('w',$stt)].' um '.$t.' Uhr';
else if($stt>_DATE_7days) $tag = ' am letzten '.$_DAYS_long[date('w',$stt)].' um '.$t.' Uhr';
else if($stt>_DATE_14days) $tag = ' am '.$d[2].'.'.$d[1].'. ('.$_DAYS_short[date('w',$stt)].') um '.$t.' Uhr';
else $tag = ' am '.$d[2].'.'.$d[1].'.'.$d[0].' ('.$_DAYS_short[date('w',$stt)].')';
return($tag);
}

function __autoload($classname) {
$fn = dirname(__FILE__)."/files/ownunity/cache/tmp_class_".$classname.".php";
if(file_exists($fn) && filemtime($fn)>=filemtime(dirname(__FILE__)."/resources_ownunity.php") && basename($_SERVER["PHP_SELF"])!="onefile.php") {
include_once $fn;
} else {
$filename = "php/classes/class.". $classname .".php";
$classCode = getRes($filename);
file_put_contents($fn, trim($classCode));
include_once $fn;
}}

function sendMail($to, $subject, $body, $from, $fromname="", $header="") {
$mail = new PHPMailer();  // create a new object
$mail->IsSMTP(); // enable SMTP
if($header!="") $mail->AddCustomHeader($header);
//$mail->SMTPDebug = true;
$mail->SMTPAuth = getMailConfigValue("SMTPAuth", 0);  // authentication enabled
$mail->SMTPSecure = getMailConfigValue("SMTPSecure", ""); // secure transfer enabled REQUIRED for GMail
$mail->Host = getMailConfigValue("SMTPserverhost", 'localhost');
$mail->Port = getMailConfigValue("SMTPserverport", '25');
$mail->CharSet = "utf-8";
$mail->Username = getMailConfigValue("SMTPusername", "");
$mail->Password = getMailConfigValue("SMTPpassword", "");
$mail->SetFrom($from, $fromname);
$mail->Subject = $subject;
$mail->Body = $body;
$mail->Sender = $from;
$mail->IsHTML();
$mail->AddAddress($to);
//vd($mail);
return $mail->Send();
}
function prepareText($text, $obj=null) {
if(stristr($text,'-BEGIN PGP MESSAGE-')) return "<div class='pgptext'>".$text."</div>";
$app = preg_match_all("/\[app:(.*?)\]/", $text, $matches);
if($app>0) {
$apps = $matches[1];
for($i=0;$i<count($apps);$i++) {
$html = "<iframe src='files/kmco/apps/".$apps[$i]."/' width='100%' height='400' style='border: solid 1px silver;'></iframe>";
$text = preg_replace('/\[app:'.$apps[$i].'\]/', $html, $text, 1);
}}
if($obj!=null) {
$img = $obj->getImages();
$files = $obj->getFiles();
if(count($img)>0) {
for($i=0;$i<count($img);$i++) {
$text = str_replace("[img:".substr($img[$i]->basename,5)."]", '/img:'.$i.'/', $text);
}}
if(count($files)>0) {
for($i=0;$i<count($files);$i++) {
$text = str_replace("[file:".substr($files[$i]->basename,5)."]", '/file:'.$i.'/', $text);
}}
}
$text = Markdown::defaultTransform($text);
if(count($img)>0) {
for($i=0;$i<count($img);$i++) {
if($_REQUEST["action"]=="shareemail") {
$im = new image($img[$i]->file);
$im->resize(600, 20000, false);
$base64 = $im->getBase64();
$imgcode = '<img src="'.$base64.'" style="max-width:600px;">';
} else {
$imgcode = "
<div style='text-align:center;' id='img_".htmlid($obj->data["id"])."_".$i."'>".$img[$i]->basename."</div>
<script>
$(function() {
openImageShow('img_". htmlid($obj->data["id"])."_".$i."', '". $obj->data["id"] ."', ".$i.");
});
</script>
";
}
$text = str_replace("/img:".$i."/", $imgcode, $text);
}}
if(count($files)>0) {
for($i=0;$i<count($files);$i++) {
$filecode = "download: ".$files[$i]->name;
$text = str_replace("/file:".$i."/", $filecode, $text);
}}
/*
$anz = preg_match_all("/\[img:(.*?)\]/", $text, $files );
if($anz>0) {
$path = checkfordir("files/cache");
for($i=0;$i<count($files[0]);$i++) {
}}
*/
return $text;
}
function make_links_clickable($text){
return preg_replace('!(((f|ht)tp(s)?://)[-a-zA-Zа-яА-Я()0-9@:%_+.~#?&;//=]+)!i', '<a href="$1">$1</a>', $text);
}
function fixname($n) {
$replaceChar = "_";
$n = strtolower($n);
$n = str_replace(" ",$replaceChar,$n);
$n = str_replace('�','ae',$n);
$n = str_replace('�','oe',$n);
$n = str_replace('�','ue',$n);
$n = str_replace('�','ss',$n);
$n = str_replace(utf8_encode('�'),'ae',$n);
$n = str_replace(utf8_encode('�'),'oe',$n);
$n = str_replace(utf8_encode('�'),'ue',$n);
$n = str_replace(utf8_encode('�'),'ss',$n);
$n = str_replace('&',$replaceChar,$n);
$n = str_replace(',',$replaceChar,$n);
//$n = str_replace('-',$replaceChar,$n);
$n = str_replace('/',$replaceChar,$n);
$n = str_replace('?',$replaceChar,$n);
$n = str_replace('#',$replaceChar,$n);
$n = str_replace(';',$replaceChar,$n);
$n = str_replace(':',$replaceChar,$n);
$n = preg_replace('/[^a-z0-9_\-.]/i', $replaceChar, $n);
while (stristr($n,$replaceChar.$replaceChar)) {
$n = str_replace($replaceChar.$replaceChar,$replaceChar,$n);
}
return($n);
}
function htmlid($id) {
$id = str_replace("/", "_", $id);
$id = str_replace(".", "_", $id);
return $id;
}
/*
function readJson($fn) {
if(!file_exists($fn)) return array();
return json_decode(file_get_contents($fn), true);
}
*/
function getExt($fn) {
$ext = strtolower(substr($fn, strrpos($fn,".")));
return $ext;
}
function mid() {
$M = number_format(microtime(true),4,"","");
return $M;
}

error_reporting(0);ini_set("display_errors", "off");
ob_start();
if(!file_exists(dirname(__FILE__).'/files')) {
mkdir(dirname(__FILE__).'/files', 0775);
chmod(dirname(__FILE__).'/files', 0775);
}
if(!file_exists(dirname(__FILE__).'/files')) {
die("Bitte legen Sie einen Unterordner <b>files</b> an und geben ihm Schreibrechte.<br><br>Create a subfolder <b>files</b> and give write permissions to it.");
}
if(!is_writable(dirname(__FILE__).'/files')) {
die("Ordner <b>files</b> muss Schreibrechte haben.<br><br>Subfolder <b>files</b> must be writable for php!");
}
session_start();
if(!defined('FILENAME')) define("FILENAME", basename(__FILE__));
define("SESSKEY", "ownunity");
define("dataDir", dirname(__FILE__).'/files/'.SESSKEY);
if(!file_exists(dataDir)) {
mkdir(dataDir, 0775);
chmod(dataDir, 0775);
}
if(!file_exists(dataDir."/cache")) {
mkdir(dataDir."/cache", 0775);
chmod(dataDir."/cache", 0775);
}
define("configIniFile", "config.ini.php");
if(!file_exists(dataDir.'/'.configIniFile)) {
$data = array();
$data["community"]["enableGroups"] = "no";
$data["community"]["title"] = "My Community";
$data["community"]["pagetitle"] = "My own hosted community";
$data["community"]["absender"] = "info@".str_replace("www.", "", $_SERVER["HTTP_HOST"]);
$data["mail"]["SMTPserverhost"] = "localhost";
$data["mail"]["SMTPserverport"] = "25";
$data["mail"]["SMTPusername"] = "";
$data["mail"]["SMTPpassword"] = "";
$data["mail"]["SMTPAuth"] = 0;
$data["mail"]["SMTPSecure"] = "";
writeINI(dataDir.'/'.configIniFile, $data);
}
/*
$cfgFN = dataDir.'/.config';
if(file_exists($cfgFN)) {
$GLOBALCONFIG = json_decode(file_get_contents($cfgFN), true);
} else {
$GLOBALCONFIG = array(
"hostid" => md5(microtime(true).rand()),
"created" => now(),
"title" => "My-Community",
"pagetitle" => "my community"
);
file_put_contents($cfgFN, json_encode($GLOBALCONFIG));
}
*/
function getMailConfigValue($name, $default='') {
return getINI(configIniFile, array("mail", $name), $default);
}
function getConfigValue($name, $default='') {
return getINI(configIniFile, array("community", $name), $default);
/*
global $GLOBALCONFIG, $cfgFN;
if(!isset($GLOBALCONFIG[$name])) {
$GLOBALCONFIG[$name] = $default;
file_put_contents($cfgFN, json_encode($GLOBALCONFIG));
}
return $GLOBALCONFIG[$name];
*/
}
function setConfigValue($name, $value) {
$data = readINI(dataDir.'/'.configIniFile);
$data["community"][$name] = $value;
writeINI(dataDir.'/'.configIniFile, $data);
/*
global $GLOBALCONFIG, $cfgFN;
$GLOBALCONFIG[$name] = $value;
file_put_contents($cfgFN, json_encode($GLOBALCONFIG));
*/
}
if($_COOKIE[SESSKEY."language"] == "") {
$_COOKIE[SESSKEY."language"] = "en";
setCookie(SESSKEY."language", "en", time()+60*60*24*365);
}
if(isset($_GET["lang"])) {
$_COOKIE[SESSKEY."language"] =  $_GET["lang"];
setCookie(SESSKEY."language", $_GET["lang"], time()+60*60*24*365);
}
$USERS = array();
define('userFile', dirname(__FILE__).'/files/'.SESSKEY.'_users.json');
if(getS("user")=="") {
if(isset($_COOKIE[SESSKEY."user"]) && $_COOKIE[SESSKEY."user"]!="") {
// User Anhand des Cookies anmelden
$profil = new profile();
$profil->loginByCookieValue($_COOKIE[SESSKEY."user"]);
}
if(isset($_REQUEST["userid"]) && $_REQUEST["userid"]!="") {
$profil = new profile();
$profil->loginByUserIDValue($_REQUEST["userid"]);
}}

if(isset($_REQUEST["do"]) && $_REQUEST["do"]!="") {
if(isset($_REQUEST["send"]) && $_REQUEST["send"]=="do") {
if($_GET["do"]=="register") {
$profil = new profile();
$_POST['email'] = strtolower(trim($_POST['email']));
if(!$profil->exists($_POST['email'])) {
$key = md5(uniqid(microtime(true)).rand());
$pubkey = md5(uniqid(microtime(true)).rand());
$data = array("loginname" => strtolower(trim($_POST['email'])),
"email" => strtolower(trim($_POST['email'])),
"password" => md5(strtolower(trim($_POST['email'])).$_POST['password']),
"name" => $_POST["name"],
"registered" => time(),
"key" => $key,
"pubkey" => $pubkey,
);
$profil->register($data);
if(getConfigValue("autoConnectSameDomain", "false")=="true") {
$profil->autoConnectSameDomain();
}
$P = new posts();
$data = array("date" => date("Y-m-d H:i:s"),
"text" => htmlspecialchars($_POST["name"]. " ist der Community beigetreten."),
"newformrecipienttype"=> "alle",
"recipients"=> array(),
"newformcommenttype"=> "keine",
"newformeditable"=> "nein",
);
$P->save($data, "own", me(), "alle");
$REG = true;
} else {
$ERR = trans("E-Mailadresse existiert schon!", "email-address already exists");
}}
if($_GET["do"]=="login") {
$profil = new profile();
if(!$profil->exists($_POST['email'])) {
$ERR = trans("Login nicht korrekt", "login not correct");
} else {
if($profil->login($_POST['email'], $_POST['password'])) {
header("location: ".FILENAME);
exit;
} else {
$ERR = trans("Login nicht korrekt", "login not correct");
}}
}
if($_GET["do"]=="lost") {
$profil = new profile();
if($profil->createNewPassword($_POST['recover'])) {
$LOST = true;
} else {
$ERR = trans("Konnte das Passwort nicht neu erstellen", "error creating a new password");
}}
if($_GET["do"]=="cred") {
$profil = new profile();
if($_POST['newpassword']!="" && $_POST['newpassword']==$_POST['newpassword2']) {
if($profil->setNewPassword($_POST['oldpassword'], $_POST['newpassword'])) {
$CRED = true;
} else {
$ERR = trans("Konnte das Passwort nicht setzen", "error setting new password");
}} else {
$ERR = trans("Konnte das Passwort nicht setzen", "error setting new password");
}}
if($_REQUEST["do"]=="ajaxlogin") {
$res = array("result" => 0);
$profil = new profile();
if(!$profil->exists(strtolower(trim($_REQUEST['loginname'])))) {
$ERR = "Login nicht korrekt";
} else {
if($profil->login(strtolower(trim($_REQUEST['loginname'])), strtolower(trim($_REQUEST['password'])))) {
$res = array("result" => 1,
"userid" =>  getS("pubkey"),
"hostid" => $GLOBALCONFIG['hostid'],
"communityname" => getConfigValue("title", "My Community"),
);
} else {
$res = array("result" => 0);
}}
echo json_encode($res);
exit;
}
if($_REQUEST["do"]=="ajaxregister") {
$profil = new profile();
if($profil->exists(strtolower(trim($_REQUEST['loginname'])))) {
$res = array("result" => 0);
} else {
$key = md5(uniqid(microtime(true)).rand());
$pubkey = md5(uniqid(microtime(true)).rand());
$data = array("loginname" => strtolower(trim($_POST['loginname'])),
"email" => strtolower(trim($_POST['loginname'])),
"password" => md5(strtolower(trim($_POST['loginname'])).$_POST['password']),
"name" => $_POST["nickname"],
"registered" => time(),
"key" => $key,
"keyword" => $_POST["nickname"],
"pubkey" => $pubkey,
);
$profil->register($data);
$res = array("result" => 1, "userid" => $pubkey, "hostid" => $GLOBALCONFIG['hostid']);
}
echo json_encode($res);
exit;
}}
if($_GET["do"]=="logout") {
setS("user", "");
setS("pubkey", "");
setCookie(SESSKEY."user", "", time());
}}
if(isset($_REQUEST["feed"]) && $_REQUEST["feed"]!="") {
$save["userFile"] = getS("userFile");
$save["user"] = getS("user");
$save["userName"] = getS("userName");
$save["pubkey"] = getS("pubkey");
$profil = new profile();
$res = $profil->loginByFeedKey($_REQUEST["feed"]);
if($res==false) {sleep(5);die("wrong key");}
$rss = new feed();
$rss->title = getConfigValue("title", "ownUnity");
$rss->link = "http://".$_SERVER['HTTP_HOST'].$_SERVER["SCRIPT_NAME"];
$posts = new posts();
$P = $posts->recent();
for($i=0;$i<count($P);$i++) {
$sender = $profil->get($P[$i]->data["user"]);
$nr = $rss->addItem();
$title = explode("\n", trim($P[$i]->data['data']["text"]));
$title = $title[0];
$title = str_replace("#", "", $title);
if(strlen($title)>80) $title = substr($title,0,80)."...";
$title = $sender["name"].": ".$title;
$rss->setTitle($title);
$rss->setDate($P[$i]->data["data"]["date"]);
$rss->setLink($rss->link.'?full='.$P[$i]->data['id']);
}
header("content-type: application/rss+xml");
echo $rss->rss();
setS("userFile", $save["userFile"]);
setS("user", $save["user"]);
setS("userName", $save["userName"]);
setS("pubkey", $save["pubkey"]);
exit;
}
if(isset($_REQUEST["action"]) && $_REQUEST["action"]!="") {
if(me()=="") die("not logged in!");
if($_REQUEST["action"]=="shareemail") {
if(getConfigValue("communitysender", "")!="") {
$profil = new profile();
$profilData = $profil->get();
ob_start();

if(!is_array($tempfn)) $tempfn = array();
$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php";
file_put_contents($fn, getRes("templates/fullmail.tpl"));
include $fn;
unlink(array_pop($tempfn));

$C = ob_get_clean();
//echo $C;exit;
for($i=0;$i<count($_POST["sharerecipient"]);$i++) {
sendMail($_POST["sharerecipient"][$i], "[".getConfigValue("title", "ownUnity")."] - ".$_POST["sharesubject"], $C, getConfigValue("communitysender", ""), $profilData["email"], "Content-Type:text/html;charset=utf-8");
}
if($_REQUEST["view"]!="json") {
header("location: ".FILENAME.'?full='.$_GET["full"]);
exit;
}}
}
if($_REQUEST["action"]=="isnew") {
$path = checkfordir("posts");
$fn = $path.'/'.$_REQUEST["whichview"].'_'.me().'.ping';
$res = array("result" => 0);
if(file_Exists($fn)) {
if(substr($_REQUEST["lastchange"],0,1)=="L") $_REQUEST["lastchange"] = substr($_REQUEST["lastchange"],1);
if(substr($_REQUEST["lastchange"],0,1)=="L") $_REQUEST["lastchange"] = substr($_REQUEST["lastchange"],1);
$pingid = file_get_contents($fn);
if($_REQUEST["lastchange"]<$pingid) {
$H = new history(me());
if($last = $H->newerThan($_REQUEST["lastchange"]) ) {
if($last["from"]!=me()) {
$res = array("result" => 1,
//"lastChange" => filemtime($fn),
"lastChange" => "L".$last["mid"],
"info" => $last["info"]
);
} else {
$res = array("result" => 0);
}}
}
/*
if(file_exists($fn) && filemtime($fn)>$_REQUEST["lastchange"]) {
$res = array("result" => 1, "lastChange" => filemtime($fn), "info" => "Neue ---");
}
*/
}
echo json_encode($res);
exit;
}
if($_REQUEST["action"]=="pollnews") {
$path = checkfordir("posts");
$H = new history(me());
$fn = $path.'/'.$_REQUEST["whichview"].'_'.me().'.ping';
$res = array("result" => 0);
if(substr($_REQUEST["lastchange"],0,1)=="L") $_REQUEST["lastchange"] = substr($_REQUEST["lastchange"],1);
if(substr($_REQUEST["lastchange"],0,1)=="L") $_REQUEST["lastchange"] = substr($_REQUEST["lastchange"],1);
//if(file_exists($fn) && filemtime($fn)>$_REQUEST["lastchange"]) {
if($last = $H->newerThan($_REQUEST["lastchange"], $_REQUEST["whichview"])) {
$res = array("result" => 1, "lastChange" => "L".$last["mid"]);
$profil = new profile();
$posts = new posts();
$P = $posts->recent($_REQUEST["whichview"], '', $_REQUEST["lastchange"]);
if($_REQUEST["view"]=="json") {
$list = array();
for($i=0;$i<count($P);$i++) {
$list[] = $P[$i]->data["id"];
}
$res["posts"] = $list;
echo json_encode($res);
exit;
}
ob_start();
$ids = array();
for($i=0;$i<count($P);$i++) { $ids[] = $P[$i]->data["id"]; 
if(!is_array($tempfn)) $tempfn = array();
$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php";
file_put_contents($fn, getRes("templates/onepost.tpl"));
include $fn;
unlink(array_pop($tempfn));
 }
$html = ob_get_clean();
$res["html"] = $html;
$res["ids"] = $ids;
}
echo json_encode($res);
exit;
}
if($_REQUEST["action"]=="checknotify") {
$res = array("result" => 0, "privateid" => getS("user"));
$profil = new profile();
$inq = $profil->getInquiriesForMe();
if($inq!=array()) {
$inq2 = array();
for($i=0;$i<count($inq);$i++) {
$inq2[] = array(
"name" => $inq[$i]["name"],
"key" => $inq[$i]["key"],
"profession" => $inq[$i]["profession"],
);
}
$res = array("result" => 1, "inq" => $inq2);
}
echo json_encode($res);
exit;
}
if($_REQUEST["action"]=="delete") {
$P = new post("", $_REQUEST["id"]);
$P->deleteMine();
}
if($_REQUEST["action"]=="getcomments") {
$profil = new profile();
if($_REQUEST["view"]=="json") {
$P = new post("", $_REQUEST["id"]);
$comments = $P->getComments();
$C = array();
for($j=0;$j<count($comments);$j++) {
$senderC = $profil->get($comments[$j]->data["user"]);
$C[$j]["id"] = $comments[$j]->data["id"];
$C[$j]["miniimage"] = $profil->getImageBase64($senderC, 'mini');
$C[$j]["fromname"] = $senderC["name"];
$C[$j]["fromid"] = $comments[$j]->data["user"];
$C[$j]["text"] = $comments[$j]->data["data"]["text"];
$C[$j]["date"] = $comments[$j]->data["data"]["date"];
$C[$j]["time"] = strtotime($comments[$j]->data["data"]["date"]);
}
$res = array("result" => 1,
"comments" => $C
);
echo json_encode($res);
exit;
}}
if($_REQUEST["action"]=="getpost") {
$path = checkfordir("posts");
$profil = new profile();
if($_REQUEST["view"]=="json") {
$P = $post = new post("", $_REQUEST["id"]);
$sender = $profil->get($P->data["user"]);
$comments = $P->getComments();
$C = array();
for($j=0;$j<count($comments);$j++) {
$senderC = $profil->get($comments[$j]->data["user"]);
$C[$j]["id"] = $comments[$j]->data["id"];
$C[$j]["miniimage"] = $profil->getImageBase64($senderC, 'mini');
$C[$j]["fromname"] = $senderC["name"];
$C[$j]["fromid"] = $comments[$j]->data["user"];
$C[$j]["text"] = $comments[$j]->data["data"]["text"];
$C[$j]["date"] = $comments[$j]->data["data"]["date"];
$C[$j]["time"] = strtotime($comments[$j]->data["data"]["date"]);
}
$recipients = $P->data["data"]["recipients"];
if(is_array($recipients)) $recipients = implode(",",$recipients);
$res = array("result" => 1,
"text" => Markdown::defaultTransform($P->data["data"]["text"]),
"name" => $sender["name"],
"fromid" => $sender["key"],
"date" => date("d.m.Y H:i", strtotime($P->data["data"]["date"])),
"time" => strtotime($P->data["data"]["date"]),
"id" => $_REQUEST["id"],
"to" => ",".$recipients.",",
"img" => $profil->getImageBase64($sender, 'small'),
"comments" => $C
);
$img = $P->getImages();
if(count($img)>0) {
$im = new image($img[0]->file);
$im->resize(300, 300, false);
$base64 = $im->getBase64();
$res["text"] .= "<br/><center><img src='".$base64."' style='max-width:100%;'></center>";
}
echo json_encode($res);
exit;
} else if($_REQUEST["view"]=="short") {
$P = array(new post("", $_REQUEST["id"]));
$i=0;

if(!is_array($tempfn)) $tempfn = array();
$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php";
file_put_contents($fn, getRes("templates/onepost.tpl"));
include $fn;
unlink(array_pop($tempfn));

} else {
$P = new post("", $_REQUEST["id"]);

if(!is_array($tempfn)) $tempfn = array();
$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php";
file_put_contents($fn, getRes("templates/fullpost.tpl"));
include $fn;
unlink(array_pop($tempfn));

}
exit;
}
if($_REQUEST["action"]=="imgpreview") {
$P = $post = new post("", $_REQUEST["id"]);
$img = $P->getImages();
$im = new image($img[0]->file);
$im->resize($_GET["width"], 800, false);
$im->deliver();
exit;
}
if($_REQUEST["action"]=="imgshow") {
$P = $post = new post("", $_REQUEST["id"]);
$img = $P->getImages();
$im = new image($img[(int)$_REQUEST["imgnr"]]->file);
$im->resize($_GET["width"], 800, false);
$im->deliver();
exit;
}
if($_REQUEST["action"]=="requestConnection") {
$P = new profile();
$P->requestConnection($_REQUEST["profile"]);
}
if($_REQUEST["action"]=="withdrawConnection") {
$P = new profile();
$P->withdrawConnection($_REQUEST["profile"]);
}
if($_REQUEST["action"]=="splitConnection") {
$P = new profile();
$P->splitConnection($_REQUEST["profile"]);
}
if($_REQUEST["action"]=="rejectConnection") {
$P = new profile();
$P->rejectConnection($_REQUEST["profile"]);
}
if($_REQUEST["action"]=="acceptConnection") {
$P = new profile();
$P->acceptConnection($_REQUEST["profile"]);
}
if($_REQUEST["action"]=="search") {
$P = new profile();
$T = $P->search($_REQUEST["query"]);
$res = array("result"=>1, "hits" => $T);
echo json_encode($res);
exit;
}
if($_REQUEST["action"]=="loadPubKeys") {
$P = new profile();
$C = $P->getContacts();
$keys = array();
$mine = $P->get();
$keys["mine"] = array();
for($j=0;$j<count($mine["pubkeys"]);$j++) {
$keys["mine"][] = $mine["pubkeys"][$j];
}
for($i=0;$i<count($C);$i++) {
$keys[$C[$i]["key"]] = array();
for($j=0;$j<count($C[$i]["pubkeys"]);$j++) {
$keys[$C[$i]["key"]][] = $C[$i]["pubkeys"][$j];
}}
$res = array("result"=>1, "pubkeys" => $keys);
echo json_encode($res);
exit;
}
if($_REQUEST["action"]=="setmypubkey") {
$P = new profile();
$P->removePubKey($_REQUEST["clientID"]);
$P->addPubKey($_REQUEST["clientID"], $_REQUEST["pubkey"]);
$res = array("result"=>1);
echo json_encode($res);
exit;
}
if($_REQUEST["action"]=="updatepost") {
$P = new post("", $_REQUEST["id"]);
$P->update($_REQUEST["textcontent"]);
}
if($_REQUEST["action"]=="addfile") {
if($_REQUEST["id"]==-1) {
$path = checkfordir("files/cache");
$F = $_FILES["fileappend"];
$res = "";
for($i=0;$i<count($F["name"]);$i++) {
$fn = time()."_".fixname($F["name"][$i]);
move_uploaded_file($F["tmp_name"][$i], $path.'/file_'.$fn);
$ext = getExt($fn);
if($ext==".jpg" || $ext==".png" || $ext==".gif") {
$res .= "[img:".$fn."]\n";
} else {
$res .= "[file:".$fn."]\n";
}}
echo $res;
} else {
$P = new post("", $_REQUEST["id"]);
$names = $P->addfile($_FILES["fileappend"]);
for($i=0;$i<count($names);$i++) {
$ext = getExt($names[$i]);
if($ext==".jpg" || $ext==".png" || $ext==".gif") {
$res .= "[img:".substr($names[$i],5)."]\n";
} else {
$res .= "[file:".substr($names[$i],5)."]\n";
}}
echo $res;
}}
if($_REQUEST["action"]=="appendpost") {
$P = new post("", $_REQUEST["id"]);
$P->update($P->data["data"]["text"]."\n\n".$_REQUEST["addposttext"]);
if($_REQUEST["type"]=="ajax") {
$res = array("id" => $_REQUEST["id"]);
echo json_encode($res);
exit;
}}
if($_REQUEST["action"]=="newpost") {
if(isset($_POST["newposttext"]["mine"])) {
$text = htmlspecialchars($_POST["newposttext"]["mine"]);
}
$posts = new posts();
$data = array("date" => date("Y-m-d H:i:s"),
"text" => $_POST["newposttext"],
"newformrecipienttype"=> $_REQUEST["newformrecipienttype"],
"recipients"=> $_REQUEST["recipients"],
"newformcommenttype"=> $_REQUEST["newformcommenttype"],
"newformeditable"=> $_REQUEST["newformeditable"],
);
if(isfilled($_REQUEST["group"])) {
$group = $_REQUEST["group"];
} else {
$group = "own";
}
$postpath = $posts->save($data, $group, me(), $_REQUEST["newformrecipienttype"], $_REQUEST["recipients"]);
if(isset($_REQUEST["img"])) {
// Bild das aus der App mitgesendet wird als Base64
$fn = "files/kmco/".microtime(true).".jpg";
file_put_contents($fn, base64_decode($_REQUEST["img"]));
$P = new post($postpath);
$imageName = $P->addlocalfile($fn);
$P = new post($postpath);
$P->update($P->data["data"]["text"]."\n[img:".$imageName."]");
}
$anz = preg_match_all("/\[img:(.*?)\]/", $text, $files );
if($anz>0) {
$path = checkfordir("files/cache");
for($i=0;$i<count($files[0]);$i++) {
$fn = $path.'/file_'.$files[1][$i];
if(file_Exists($fn)) {
$P = new post($postpath);
$P->addlocalfile($fn);
}}
}
$anz = preg_match_all("/\[file:(.*?)\]/", $text, $files );
if($anz>0) {
$path = checkfordir("files/cache");
for($i=0;$i<count($files[0]);$i++) {
$fn = $path.'/file_'.$files[1][$i];
if(file_Exists($fn)) {
$P = new post($postpath);
$P->addlocalfile($fn);
}}
}
/*
BildTest!
[img:1400596360_49ec8df8bd799fe3ab2e4bbf9f1c4587.jpg]
*/
$profil = new profile();
$profil->increment("posts");
if($_REQUEST["type"]=="ajax") {
$data = array("result" => 1, "newid" => $posts->lastNewID);
echo json_encode($data);
exit;
}}
if($_REQUEST["action"]=="newcomment") {
$post = new posts($_POST["id"]);
if($post->data===false) die("wrong post id!");
if(trim($_POST["replytext"])!="") {
$data = array("date" => date("Y-m-d H:i:s"),
"text" => htmlspecialchars($_POST["replytext"]),
);
$post->comment($data, me());
$profil = new profile();
$profil->increment("comments");
$profil->increment("replies", $post->data["user"]);
}
if($_REQUEST["view"]=="json") exit;
}
if($_REQUEST["action"]=="loadprofil") {
$P = new profile();
$data = $P->get($_REQUEST["userkey"]);
unset($data["password"]);
unset($data["pubkey"]);
if($_REQUEST["view"]=="json") {
$data["img"] = $P->getImageBase64($data, "medium");
$data["status"] = $P->getStatusBetweenMe($data["key"]);
$res = array("result"=>1, "data" => $data);
echo json_encode($res);
exit;
}}
if($_REQUEST["action"]=="saveprofil") {
$profil = new profile();
$data = array(
"name" => $_POST["nickname"],
"profession" => $_POST["profession"],
"keyword" => $_POST["keyword"],
"email" => $_POST["email"],
);
if(isset($_POST["removePic"]) && $_POST["removePic"]==1) {
$profil->removeProfileImage();
$data["profileimage"] = "";
}
if(isset($_FILES['inputPic']) && isset($_FILES['inputPic']["name"]) && substr($_FILES['inputPic']["type"],0,6)=="image/") {
$ext = strtolower(substr($_FILES['inputPic']["name"],strrpos($_FILES['inputPic']["name"],".")));
if($ext==".png" || $ext==".jpg") {
$tmpName = dataDir.'/'.microtime(true);
move_uploaded_file($_FILES['inputPic']["tmp_name"], $tmpName);
$data["profileimage"] = $profil->setProfileImage($tmpName, $_FILES['inputPic']["name"]);
}}
if(isset($_REQUEST["profilbild"])) {
$tmpName = "files/kmco/".microtime(true).".jpg";
file_put_contents($tmpName, base64_decode($_REQUEST["profilbild"]));
$data["profileimage"] = $profil->setProfileImage($tmpName, "profilbild.jpg");
}
$profil->setData($data);
if($_REQUEST["view"]=="json") exit;
}
if($_REQUEST["action"]=="getProfileImage") {
$profil = new profile();
$data = $profil->get();
if(isfilled($data["profileimage"])) {
if($_REQUEST["size"]=="" || !in_Array($_REQUEST["size"], array("mini", "small", "medium", "big"))) $_REQUEST["size"] = "medium";
$file = new files();
$fn = $file->addBeforeExt($data["profileimage"], '-'.$_REQUEST["size"]);
if($file->exists($fn)) {
header("content-type:image/jpeg");
readfile($file->fullPath($fn));
exit;
}} else {
header("content-type:image/png");
echo getRes('resources/images/person.png');
}
exit;
}
if($_REQUEST["action"]=="newgroup") {
$groups = new groups();
$groups->add($_POST["groupname"], me());
}
if($_REQUEST["action"]=="addtreeentry") {
$tree = new tree($_REQUEST["group"]);
$tree->addtreeentry($_REQUEST["title"], (int)$_REQUEST["below"]);
$res = array("result"=>1, "html" => $tree->getTree(), "options" => $tree->getTreeOptionArray());
echo json_encode($res);
exit;
}
if($_REQUEST["action"]=="becomemember") {
$group = new group("", $_REQUEST["group"]);
$group->addMember(me());
}
if($_REQUEST["action"]=="setposttreelink") {
$tree = new tree($_REQUEST["group"]);
$post = new post("", $_REQUEST["id"]);
$res = $post->setTreelink($_REQUEST["tree"], $_REQUEST["group"]);
echo json_encode($res);
exit;
}
if($_REQUEST["action"]=="getolderposts") {
$res = array("result"=>0);
$posts = new posts();
$res["posts"] = $posts->recent($_REQUEST["group"], $_REQUEST["recentid"]);
if(count($res["posts"])>0 && $res["posts"]!=array()) {
$res["result"] = 1;
$res["lastid"] = $res["posts"][count($res["posts"])-1];
}
echo json_encode($res);
exit;
}
if($_REQUEST["action"]=="getcontacts") {
$P = new profile();
$C = $P->getContacts();
$C2 = array();
for($i=0;$i<count($C);$i++) {
$C2[] = array("key" => $C[$i]["key"], "name" => $C[$i]["name"]);
}
echo json_encode(array("result" => 1, "contacts" => $C2));
exit;
}
if($_REQUEST["action"]=="getrecentcontacts") {
$P = new profile();
$C = $P->getContacts();
$C2 = array();
$keys = array();
for($i=0;$i<count($C);$i++) {
$C2[$C[$i]["key"]] = array("key" => $C[$i]["key"], "name" => $C[$i]["name"]);
$keys[] = $C[$i]["key"];
}
$posts = new posts();
$C3 = $posts->recentContacts($C2, $keys);
echo json_encode(array("result" => 1, "contacts" => $C3));
exit;
}
if($_REQUEST["view"]=="json") exit;
}
if(me()!="") {
$profil = new profile();
$profilData = $profil->get();
if(!isset($profilData["profession"])) {
if(!isset($_GET["view"]) || $_GET["view"]!="profil") {
header("location: ".FILENAME."?view=profil");
exit;
}}
}

if(!is_array($tempfn)) $tempfn = array();
$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php";
file_put_contents($fn, getRes("templates/main2.tpl"));
include $fn;
unlink(array_pop($tempfn));
?>
