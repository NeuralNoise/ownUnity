<?php exit();?><?php
class tree {
	
	public $path;
	public $tree = array(); 
	function __construct($group='own') {
		$this->path = checkfordir("trees");
		$this->group = $group;
		if(file_exists($this->path.'/'.me().".tree")) { // $group
			$this->tree = json_decode(file_get_contents($this->path.'/'.me().".tree"), true);
		} else {
			$this->tree = array( 
						"maxid" => 10, 
						"tree" => array(
								array( 
									"id" => 1, 
									"title" => "-1-",
									"count" => 0,
									"sub" => array(
											array( 
												"id" => 2, 
												"title" => "-2-",
												"count" => 0,
												"sub" => array()
												),
											array( 
												"id" => 3, 
												"title" => "-3-",
												"count" => 0,
												"sub" => array()
												),
											)
									),
								)
						);
		}
	}
	
	function getTree($depth=0, $tree=null) {
		if($tree===null) $tree = $this->tree["tree"];
		$html = "<ul style='";
		if($depth==0) $html .= "padding-left:0;'";
		//else $html .= "display:none;";
		$html .= "'>";
		
		for($i=0;$i<count($tree);$i++) {
			$html .= "<li ";
			if($depth>0) $html .= "style='display:none'";
			$html .= ">";
			$hasSub = false;
			if(isset($tree[$i]["sub"]) && $tree[$i]["sub"]!=array()) $hasSub = true;
			$html .= '<span rel="'.$tree[$i]["id"].'">';
			if($hasSub) $html .= '<i class="glyphicon glyphicon-plus-sign"></i>&nbsp;';
			$T = $tree[$i]["title"];
			if($T=="-1-") $T = trans("Alle Beitrֳ₪ge", "all entries");
			if($T=="-2-") $T = trans("nicht zugewiesene Beitrֳ₪ge", "not linked entries");
			if($T=="-3-") $T = trans("meine Beitrֳ₪ge", "my entries");
			$html .= '<a href="#" onclick="clickOnTree('.$tree[$i]["id"].');return false;">'.$T.'</a></span>';
			#if($tree[$i]["id"]>=10) $html .= '<span class="badge" style="float:right;">'.$tree[$i]["count"].'</span>';
			if($hasSub) {
				$html .= $this->getTree($depth+1, $tree[$i]["sub"]);
			}
			$html .= "</li>";
		}
		
		
		$html .= "</ul>";
		return $html;		
	}
	
	function getTreeJson() {
		return json_encode($this->tree["tree"]);
	}
	
	function getTreeOptionArray() {
		$this->optionlist = array();
		$this->getTreeOption($this->tree["tree"]);
		return $this->optionlist;
	}
	function getTreeOption(&$tree, $title="") {
		for($i=0;$i<count($tree);$i++) {
			if($tree[$i]["id"]<10) continue;
			$this->optionlist[] = array("id"=>$tree[$i]["id"], "title" => $title."&raquo;".$tree[$i]["title"]);
			if(isset($tree[$i]["sub"]) && $tree[$i]["sub"]!=array()) {
				$this->getTreeOption($tree[$i]["sub"], $title."&raquo;".$tree[$i]["title"]);
			}
		}
	}
	
	function getTreeTitle($id) {
		$this->getTreeOptionArray();
		for($i=0;$i<count($this->optionlist);$i++) {
			if($this->optionlist[$i]["id"]==$id) {
				return str_replace("&raquo;", "<i class='glyphicon glyphicon-play'></i>", $this->optionlist[$i]["title"]);
			}
		}
	}
	
	
	function addtreeentry($title, $below=0) {
		
		if($below==0) {
			$this->tree["tree"][] = array( 
				"id" => ++$this->tree["maxid"], 
				"title" => htmlspecialchars($title),
				"count" => 0,
				"sub" => array()
				);
		} else {
			$this->addInSubTree($this->tree["tree"], $title, $below);
		}
		
		$this->save();
	}
	
	function addInSubTree(&$tree, $title, $below) {
		for($i=0;$i<count($tree);$i++) {
			if($tree[$i]["id"]==$below) {
				$tree[$i]["sub"][] = array( 
							"id" => ++$this->tree["maxid"], 
							"title" => htmlspecialchars($title),
							"count" => 0,
							"sub" => array()
							);
				return;
			} 
			if(isset($tree[$i]["sub"]) && $tree[$i]["sub"]!=array()) {
				$this->addInSubTree($tree[$i]["sub"], $title, $below);
			}
		}
	}
	
	function increment($id) {
		$res = $this->incdec($this->tree["tree"], $id, 1);
		$this->save();
		return $res;
	}
	function decrement($id) {
		$res = $this->incdec($this->tree["tree"], $id, -1);
		$this->save();
		return $res;
	}
	function incdec(&$tree, $id, $pm) {
		for($i=0;$i<count($tree);$i++) {
			if($tree[$i]["id"]==$id) {
				$tree[$i]["count"] += $pm;
				return $tree[$i];
			}
			if(isset($tree[$i]["sub"]) && $tree[$i]["sub"]!=array()) {
				$res = $this->incdec($tree[$i]["sub"], $id, $pm);
				if($res!=false) return $res;
			}
		}
		return false;
	}
	
	function save() {
		file_put_contents($this->path.'/'.me().".tree", json_encode($this->tree)); // $this->group
	}
	
	
}
?>
<?php
class profile {
	public function __construct() {
		$profildir = "profiles";
		$this->path = checkfordir($profildir);
	}
	
	public function get($user="") {
		$fn = $this->getUserDir($user);
		$data = json_decode(file_get_contents($fn.'/0.user'), true);
		$data["path"] = $fn;
		return $data;
	}
	
	function getUserDir($user="") {
		if($user=="") {
			$fn = getS("userFile");
		} else {
			$f = glob($this->path.'/*_'.$user.'_user');
			$fn = $f[0];
		}
		return $fn;
	}
	
	public function search($query) {
		$q = explode(" ", strtolower($query));
		$f = glob($this->path.'/*_user');
		$treffer = array();
		for($i=0;$i<count($f);$i++) {
			$data = json_decode(file_get_contents($f[$i].'/0.user'), true);
			
			if($data["key"]==me()) continue;
			if(trim($data["keyword"])=="") continue;
			
			$KW = explode(" ", strtolower($data["keyword"]));
			$found = true;
			for($j=0;$j<count($q);$j++) {
				if(!in_array($q[$j], $KW)) {
					$found = false;
					break;
				}
			}
			if($found) {
				$data["img"] = $this->getImageBase64($data, "small");
				$data["status"] = $this->getStatusBetweenMe($data["key"]);
				unset($data["password"]);
				unset($data["pubkey"]);
				$treffer[] = $data;
			}
		}
		return $treffer;
	}
	
	public function getContacts() {
		$ctc = array();
		$d = $this->getContactsKeys();
		for($i=0;$i<count($d);$i++) {
			$user = $d[$i];
			$ctc[] = $this->get($user);
		}
		return $ctc;
	}
	public function getContactsKeys() {

		$path = $this->getUserDir(me());
		$d = glob($path.'/*.connected');
		if($d==false) return array();
		$d2 = array();
		for($i=0;$i<count($d);$i++) {
			$user = substr(basename($d[$i]),0,strrpos(basename($d[$i]),"."));
			$d2[] = $user;
		}
		return $d2;
	}
	
	
	
	public function getStatusBetweenMe($userkey) {
		$path = $this->getUserDir($userkey);
		if(file_exists($path.'/'.me().".connected")) return "connected";
		if(file_exists($path.'/'.me().".inquiry")) return "inquiry";
		$path = $this->getUserDir(me());
		if(file_exists($path.'/'.$userkey.".inquiry")) return "request";
		return false;
	}
	
	public function requestConnection($userkey) {
		if($this->getStatusBetweenMe($userkey)===false) {
			$path = $this->getUserDir($userkey);
			touch($path.'/'.me().".inquiry");
		}
	}
	public function withdrawConnection($userkey) {
		if($this->getStatusBetweenMe($userkey)==="inquiry") {
			$path = $this->getUserDir($userkey);
			unlink($path.'/'.me().".inquiry");
		}
	}
	public function rejectConnection($userkey) {
		if($this->getStatusBetweenMe($userkey)==="request") {
			$path = $this->getUserDir(me());
			unlink($path.'/'.$userkey.".inquiry");
		}
	}
	
	public function splitConnection($userkey) {
		if($this->getStatusBetweenMe($userkey)==="connected") {
			$path = $this->getUserDir($userkey);
			unlink($path.'/'.me().".connected");
			$path = $this->getUserDir(me());
			unlink($path.'/'.$userkey.".connected");
		}
	}
	public function acceptConnection($userkey) {
		if($this->getStatusBetweenMe($userkey)==="request") {
			$path = $this->getUserDir(me());
			unlink($path.'/'.$userkey.".inquiry");
			touch($path.'/'.$userkey.".connected");
			$path = $this->getUserDir($userkey);
			touch($path.'/'.me().".connected");
			
			$P = new posts();
			$ud = $this->get(me());
			$ud2 = $this->get($userkey);
			$data = array("recipients" => array($userkey, me()),
					"text" => $ud["name"]." und ".$ud2["name"]." sind nun verbunden",
					"date" => now(),
					"newformrecipienttype" => "ausgewaehlte",
					"newformcommenttype" => "keine",
					"newformeditable" => "nein"
					
					);
			$P->save($data, "own", me(), "ausgewaehlte", array($userkey, me()));
			
		}
	}
	
	
	public function getInquiriesForMe() {
		$path = $this->getUserDir(me());
		$d = glob($path.'/*.inquiry');
		if($d==false) return array();
		$inq = array();
		for($i=0;$i<count($d);$i++) {
			$user = substr(basename($d[$i]),0,strrpos(basename($d[$i]),"."));
			#vd($user);
			$inq[] = $this->get($user);
		}
		
		return $inq;
	}
	
	public function getAllExceptMe() {
		$res = array();
		$p = glob($this->path.'/*_user');
		for($i=0;$i<count($p);$i++) {
			$data = json_decode(file_get_contents($p[$i].'/0.user'), true);
			if($data["key"]!=me()) $res[] = $data["key"]; 
		}
		return $res;
	}
	
	public function setData($data, $user="") {
		if(!is_Array($data)) return false;
		
		if($user=="") {
			$fn = getS("userFile");
		} else {
			$f = glob($this->path.'/*_'.$user.'_user');
			$fn = $f[0];
		}
		
		$orig = $this->get($user);
		foreach($data as $key => $val) {
			$orig[$key] = $val;
		}
		file_put_contents($fn.'/0.user', json_encode($orig));
	}
	
	public function increment($what, $user="") {
		$data = $this->get($user);
		if(!isset($data["counts"])) $data["counts"] = array();
		$C = $data["counts"];
		$C[$what]++;
		$data = array("counts" => $C);
		$this->setData($data, $user);
	}
	
	public function setProfileImage($tmpName, $name) {
		$this->removeProfileImage();
		
		$ext = strtolower(substr($name,strrpos($name,".")));
		
		$im = new image($tmpName, $ext);
		$im->resize(300, 300);
		$im->save($tmpName.'-big');
		
		$im->resize(150, 150);
		$im->save($tmpName.'-medium');

		$im->resize(50, 50);
		$im->save($tmpName.'-small');

		$im->resize(25, 25);
		$im->save($tmpName.'-mini');
		
		$file = new files();
		$fn = $file->add($tmpName.'-big', getS("user")."_profileimage-big".$ext);
		$fn2 = $file->add($tmpName.'-medium', getS("user")."_profileimage-medium".$ext);
		$fn3 = $file->add($tmpName.'-small', getS("user")."_profileimage-small".$ext);
		$fn4 = $file->add($tmpName.'-mini', getS("user")."_profileimage-mini".$ext);
		
		unlink($tmpName);
		return str_replace("-big".$ext, $ext, $fn);
	}
	public function removeProfileImage() {
		$file = new files();
		$data = $this->get();
		if(isset($data["profileimage"]) && $data["profileimage"]!="") {
			$file->delete($file->addBeforeExt($data["profileimage"],'-big'));
			$file->delete($file->addBeforeExt($data["profileimage"],'-medium'));
			$file->delete($file->addBeforeExt($data["profileimage"],'-small'));
			$file->delete($file->addBeforeExt($data["profileimage"],'-mini'));
		}
	}
	
	public function getImageBase64($data, $size) {
		if(isfilled($data["profileimage"])) {
			
			$file = new files();
			$fn = $file->addBeforeExt($data["profileimage"], '-'.$size);
			if($file->exists($fn)) {
				$ext = strtolower(substr($fn,strrpos($fn,".")));
				if($ext==".jpg") $filetype = "jpeg";
				else if($ext==".png") $filetype = "png";
				$imgbinary = file_get_contents($file->fullPath($fn));
				return 'data:image/' . $filetype . ';base64,' . base64_encode($imgbinary);
			}
		} else {
			$imgbinary = getRes("resources/images/person-".$size.".png");
			return 'data:image/png;base64,' . base64_encode($imgbinary);
			//return FILENAME.'?RES=resources/images/person.png';
		}
	}
	
	public function exists($email) {
		$email = strtolower(trim($email));
		#vd($this->path.'/'.$email.'*.user');
		$user = glob($this->path.'/'.$email.'*_user');
		if($user===false) $user = array();
		#var_dump($user);
		#exit;
		return count($user)>0;
	}
	public function register($data) {
		$fn = $this->path.'/'.$data["loginname"].'_'.$data["pubkey"].'_'.$data["key"].'_user';
		if(!file_exists($fn)) {
			mkdir($fn, 0775, true);
			chmod($fn, 0775);
		}
		file_put_contents($fn.'/0.user', json_encode($data));
		setS("userFile", $fn);
		
		setS("user", $data["key"]);
		setS("userName", $data["name"]);
		setS("pubkey", $data["pubkey"]);
		setCookie(SESSKEY."user", getS("user"), time()+60*60*24*30);
		
	}
	public function login($email, $pass) {
		$email = strtolower(trim($email));
		$f = glob($this->path.'/'.$email.'_*_user');
		#vd($this->path.'/'.$email.'.user');exit;
		if(count($f)!=1) return false;

		$data = json_decode(file_get_contents($f[0].'/0.user'), true);
		if($data["password"] != md5(strtolower(trim($email.$pass)))) return false;
		
		$this->loginUserByFilename($f[0]);
		
		return true;
	}

    public function loginByFeedKey($key) {
        $f = glob($this->path.'/*_user');

        for($i=0;$i<count($f);$i++) {
            $data = json_decode(file_get_contents($f[$i].'/0.user'), true);
            #vd($data);
            if(!isset($data["feedKey"])) continue;
            if($data["feedKey"]==$key) {
                $this->loginUserByFilename($f[$i]);
                return true;
            }
        }
        return false;
    }

	public function loginByCookieValue($userkey) {
		$f = glob($this->path.'/*_'.$userkey.'_user');
		if(count($f)!=1) return false;
		$this->loginUserByFilename($f[0]);
		
		return true;
	}
	
	public function loginByUserIDValue($userid) {
		$f = glob($this->path.'/*_'.$userid.'_*_user');
		if(count($f)!=1) return false;
		$this->loginUserByFilename($f[0]);
		return true;
	}
	
	private function loginUserByFilename($fn) {
		$data = json_decode(file_get_contents($fn.'/0.user'), true);
		
		setS("userFile", $fn);
		setS("user", $data["key"]);
		setS("userName", $data["name"]);
		setS("pubkey", $data["pubkey"]);
		setCookie(SESSKEY."user", getS("user"), time()+60*60*24*30);
	}
	
	public function autoConnectSameDomain() {
		// {{{
		
		$me = $this->get(me());
		#vd($me);
		$mydomain = trim(strtolower(substr($me["email"], strpos($me["email"], "@")+1)));
		#vd($mydomain);
		
		$P = glob($this->path.'/*', GLOB_ONLYDIR);
		for($i=0;$i<count($P);$i++) {
			if(!file_exists($P[$i].'/'.getS("user").'.connected')) {
				$U = readJson($P[$i].'/0.user');
				$domain = trim(strtolower(substr($U["email"], strpos($U["email"], "@")+1)));
				if($domain==$mydomain && $U["key"]!=$me["key"]) {
					#vd($P[$i]);
					#vd($U);
					#vd(array($me["path"].'/'.$U["key"].".connected",
					#$P[$i].'/'.$me["key"].".connected"));
					touch($me["path"].'/'.$U["key"].".connected");
					touch($P[$i].'/'.$me["key"].".connected");
				}
			}
		}
		// }}}
	}

    public function createFeedKey() {
        $data = array("feedKey" => md5(uniqid(rand()).microtime(true)));
        $this->setData($data);
        return $data["feedKey"];
    }
}
?>
<?php
class posts {
	private $path = "";
	public $data = array();
	private $postpath = "";
	public function __construct($postid="") {
		$this->path = checkfordir("posts");
		
		if($postid!="") {
			$this->data = $this->get($postid);
		}
	}
	
	
	public function get($id) {
		$f = glob($this->path.'/*/*_'.$id.'_*_'.me().'_post');
		if($f===false) $f = array();
		if(count($f)==1) {
			$data = json_decode(file_get_contents($f[0].'/0.post'), true);
			$this->postpath = $f[0];
			return $data;
		}
		return false;
	}
	
	public function save($data, $group, $user, $recipienttype="alle", $recipientsSelect=array()) {
		// {{{
		#$id = date("Ym").'/'.microtime(true);
		$mid = mid();
		$datefolder = substr($data["date"],0,4).substr($data["date"],5,2);
		$id = $mid;
		
		$this->lastNewID = $id;
		
		$recipients = array($user);
		if($recipienttype=="alle") {
			$p = new profile();
			$data["recipients"] = $recipients = array_merge($recipients, $p->getContactsKeys());
			#vd($recipients);
		} else if($recipienttype=="ausgewaehlte") {
			$p = new profile();
			$data["recipients"] = $recipients = array_merge($recipients, $recipientsSelect);
		} else {
			$data["recipients"] = array(me());
		}

		if($group!="own") {
			$G = new group("", $group);
			$recipients = $G->data["members"];
		}
		
		for($i=0;$i<count($recipients);$i++) {
			file_put_contents($this->path.'/'.$group."_".$recipients[$i].".ping", $id);
		
			#$postdir = "posts/".$id."_".$group."_!_".$user."_post";
			$postdir = "posts/".$datefolder.'/'.$id.'_'.$mid.'_'.($recipients[$i]==me() ? '!' : '-')."_".$group."_".$recipients[$i]."_post";
			$path = checkfordir($postdir);
			
			if($recipients[$i]==me()) $myPath = $path;
			
			
			$from = new profile();
			$fromData = $from->get(me());
			$H = new history($recipients[$i]);
			$H->add("post", $id, $group, $mid, "Neue Nachricht von : ".$fromData["name"]);
			
			$post = array("data" => $data,
					"id" => $id,
					"user" => $user,
					"group" => $group,
					"created" => now(),
					);
			$postjson = json_encode($post);
			$fn = $path.'/0.post';
			file_put_contents($fn, $postjson);
		}
		
		if($group!="own") {
			$g = new group("", $group);
			$g->increment();
		}
		return $myPath;
		// }}}
	}
	
	public function comment($data, $user) {
		#vd($this->data);exit;
		$id = mid();
		$comment = array("data" => $data,
				"id" => $id,
				"user" => $user,
				"created" => now(),
				);
		$commentjson = json_encode($comment);
		
		$P = new profile();
		$fromData = $P->get(me());
		
		if($this->data["group"]!="own") {
			
			$G = new group("", $this->data["group"]);
			for($i=0;$i<count($G->data["members"]);$i++) {
				$contactsPath = $this->getPostPathForUser($this->data["id"], $G->data["members"][$i]);
				if($contactsPath!=false) {
					$fn = $contactsPath.'/'.$id.'.comment';
					file_put_contents($fn, $commentjson);
					
					$H = new history($G->data["members"][$i]);
					$H->add("comment", $this->data["id"], $this->data["group"], $id, "Neuer Kommentar von ".$fromData["name"]);
					
					$this->touchPost($contactsPath);
				}
			}
			
		} else {
		
			$fn = $this->postpath.'/'.$id.'.comment';
			file_put_contents($fn, $commentjson);
			
			$H = new history(me());
			$H->add("comment", $this->data["id"], $this->data["group"], $id, "Neuer Kommentar von ".$fromData["name"]);
			
			$this->touchPost($this->postpath);
			
			if($this->data["user"]==me()) {
				
				$myContacts = $P->getContactsKeys();
				for($i=0;$i<count($myContacts);$i++) {
					$contactsPath = $this->getPostPathForUser($this->data["id"], $myContacts[$i]);
					if($contactsPath!=false) {
						$fn = $contactsPath.'/'.$id.'.comment';
						file_put_contents($fn, $commentjson);
						
						$H = new history($myContacts[$i]);
						$H->add("comment", $this->data["id"], $this->data["group"], $id, "Neuer Kommentar von ".$fromData["name"]);
						
						$this->touchPost($contactsPath);
					}
				}
			} else if($this->data["data"]["newformcommenttype"]=="bidrektional") {
				$senderPath = $this->getPostPathForUser($this->data["id"], $this->data["user"]);
				$fn = $senderPath.'/'.$id.'.comment';
				file_put_contents($fn, $commentjson);
				
				$H = new history($this->data["user"]);
				$H->add("comment", $this->data["id"], $this->data["group"], $id, "Neuer Kommentar von ".$fromData["name"]);
				
				$this->touchPost($senderPath);

			} else if($this->data["data"]["newformcommenttype"]=="bekannte") {
				
				$myContacts = $P->getContactsKeys();
				for($i=0;$i<count($myContacts);$i++) {
					$contactsPath = $this->getPostPathForUser($this->data["id"], $myContacts[$i]);
					if($contactsPath!=false) {
						$fn = $contactsPath.'/'.$id.'.comment';
						file_put_contents($fn, $commentjson);
						
						$H = new history($myContacts[$i]);
						$H->add("comment", $this->data["id"], $this->data["group"], $id, "Neuer Kommentar von ".$fromData["name"]);
						
						$this->touchPost($contactsPath);
					}
					
				}
			}
		}
		#vd($_REQUEST);
		#vd($this->data);exit;
		
	}
	
	private function touchPost($postpath) {
		
		#vd($postpath);
		$bn = basename($postpath);
		$dn = dirname($postpath);
		$unchange = substr($bn,strpos($bn,"_"));
		
		$newpath = $dn."/".mid().$unchange;
		
		#vd(array($postpath, $newpath));
		
		rename($postpath, $newpath);
		
		// !!!!!!!!!!!!!!!!!
		#return;
		
		$olddate = basename(dirname($newpath));
		$newdate = date("Ym");
		if($olddate==$newdate) return;
		
		checkfordir("posts/".$newdate);
		
		$newpath2 = str_replace("/posts/".$olddate, "/posts/".$newdate, $newpath);
		//vd(array($newpath, $newpath2));
		rename($newpath, $newpath2);
		
	}
	
	public function getPostPathForUser($id, $user) {
		$f = glob($this->path.'/*/*_'.$id.'_*_'.$user.'_post');
		if($f===false) return false;
		if(count($f)==1) {
			return $f[0];
		}
		return false;
	}
	
	public function recentContacts($C, $keys) {
		$dirs = glob($this->path."/*", GLOB_ONLYDIR);
		if(count($dirs)==0) return array();
		$max = 20;
		$data = array();
		//vd($dirs);
		for($b=count($dirs)-1;$b>=0;$b--) {
			$postdir = "posts/".basename($dirs[$b]);
			$path = checkfordir($postdir);
			$globDir = $path.'/*_*_own_'.me().'_post';
			$opt = 0;
			$files = glob($globDir, $opt);
			if($files===false) continue;
			$recent = array();
			for($i=count($files)-1;$i>=0;$i--) {
				$data = json_decode(file_get_contents($files[$i].'/0.post'), true);
				if(in_array($data["user"], $keys)) {
					if(isset($C[$data["user"]])) {
						$C[$data["user"]]["text"] = $data["data"]["text"];
						$C[$data["user"]]["id"] = $data["id"];
						$C[$data["user"]]["fromid"] = $data["user"];
						$C[$data["user"]]["date"] = strtotime($data["data"]["date"]);
						$recent[] = $C[$data["user"]];
						unset($C[$data["user"]]);
						if(count($recent) == count($keys)) return $recent;
					}
				}
			}
			return $recent;
		}			
	}
	
	public function recent($group="own", $since='', $newer=0) {
		// {{{
		$dirs = glob($this->path."/*", GLOB_ONLYDIR);
		if(count($dirs)==0) return array();
			
		$max = 20;
		$data = array();
		
		for($b=count($dirs)-1;$b>=0;$b--) {
			$postdir = "posts/".basename($dirs[$b]);
			$path = checkfordir($postdir);
			$globDir = $path.'/*_'.$group.'_'.me().'_post';
			$opt = 0;
			$files = glob($globDir, $opt);
			if($files===false) continue;
			for($i=count($files)-1;$i>=0;$i--) {
				if($since=="") {
					$bn = basename($files[$i]);
					$ts = substr($bn,0,strpos($bn,"_"));
					#if(filemtime($files[$i])<=$newer) return $data;
					if($ts<=$newer) return $data;
					$P = new post($files[$i]);
					$P->data["ts"] = $ts;
					$data[] = $P;
				} else {
					$id = substr($files[$i], strpos($files[$i], "posts/")+6);
					$idx = explode("_", $id);
					#vd($idx);
					$id = $idx[1];
					//$id = substr($id, 0, strpos($id,"_"));
					if($id>=$since && $since!='*') continue;
					
					if(isfilled($_REQUEST['treeid']) && $_REQUEST['treeid']>1) {
						
						
						if($_REQUEST['treeid']==2) {
							// Nicht zugewiesene
							$T = readJson($files[$i].'/tree.json');
							if($T["id"]>0) continue;
						} else if($_REQUEST['treeid']==3) {
							// meine
							if(!stristr($files[$i], '_'.me().'_post')) continue;
						} else {
							$T = readJson($files[$i].'/tree.json');
							if($T["id"]!=$_REQUEST['treeid']) continue;
						}
					}
					
					$data[] = $id;
				}
				if(count($data)>=$max) break;			
			}
			if(count($data)>=$max) break;
		}
		return $data;
		
		// }}}
	}
	
}
?>
<?php
class postfile {
	public $file;
	public $ext="";
	function __construct($file) {
		$this->file = $file;
		$this->basename = basename($file);
		$this->name = substr(basename($file),5);
		$this->name = substr($this->name, strpos($this->name,"_")+1);
		$this->ext = strtolower(substr($file, strrpos($file, ".")));
		$this->filesize = filesize($file);
	}
}
?>
<?php
class post {
	public $data = array();
	public $path = "";
	public $id;
	public function __construct($path, $id="") {
		if($id!="") {
			$ppath = checkfordir("posts");

			$pp = $ppath.'/*/*_'.$id.'_*_'.me().'_post';

			$f = glob($pp);
			if($f===false) $f = array();
			if(count($f)==1) {
				$path = $f[0];
			}
		}
#echo $path;exit;
		$this->path = $path;
		$this->data = json_decode(file_get_contents($path.'/0.post'), true);
        #echo "<pre>";var_dump($this->data );exit;
		if(file_exists($path.'/tree.json')) {
			$T = json_decode(file_get_contents($path.'/tree.json'), true);
		} else $T = array();

        if(!is_array($this->data["data"]["recipients"])) $this->data["data"]["recipients"] = explode(",", $this->data["data"]["recipients"]);
		$this->data["data"]["recipients"] = array_unique($this->data["data"]["recipients"]);
        #var_dump($this->data["data"]["recipients"]);exit;

		$this->data["data"]["recipientNames"] = array();
		$profil = new profile();
		$ck = $profil->getContactsKeys();
#        echo "<pre>";var_dump($this->data["data"]["recipients"]);
#var_dump($ck);exit;
		$rID = array();
		$rN = array();
		for($i=0;$i<count($this->data["data"]["recipients"]);$i++) {
			if(in_array($this->data["data"]["recipients"][$i], $ck)) {
				$rec = $profil->get($this->data["data"]["recipients"][$i]);
				$rID = $this->data["data"]["recipients"][$i];
				$rN[] = $rec["name"];
			}
		}
		$this->data["data"]["recipients"] = $rID;
		$this->data["data"]["recipientNames"] = $rN;



		$this->id = $this->data["id"];
		$this->tree = $T;
	}

	public function getComments() {
		#$ppath = checkfordir("posts");
		#$C = glob($ppath.'/'.$this->id.'_*_post/*.comment');
		#vd($f);

		$C = glob($this->path.'/*.comment');
		if($C===false) $C = array();
		$comments = array();
		for($i=0;$i<count($C);$i++) {
			$comments[] = new comment($C[$i]);
		}
		return $comments;
	}

	public function getFiles()  {
		$ppath = checkfordir("posts");
		$C = glob($ppath.'/*/*_'.$this->id.'_*_post/file_*');
		#vd($ppath.'/'.$this->id.'_*_post/file_*');
		#$C = glob($this->path.'/file_*');
		if($C===false) $C = array();
		$files = array();
		for($i=0;$i<count($C);$i++) {
			$files[] = new postfile($C[$i]);
		}
		return $files;
	}

	public function getImages() {
		$F = $this->getFiles();
		$img = array();
		for($i=0;$i<count($F);$i++) {
			$ext = $F[$i]->ext;
			if($ext==".jpg" || $ext==".png") $img[] = $F[$i];
		}

		return $img;
	}

	public function update($text) {
		// {{{
		//vd($this->path);exit;
		$ppath = checkfordir("posts");
		$C = glob($ppath.'/*/*_'.$this->id.'_*_post/0.post'); // Hier wirklich zuerst die Ordner auslesen, weil gleich umbenannt wird.

		rename($this->path.'/0.post', $this->path.'/0-'.time().'.old');
		if(!isset($this->data["history"])) $this->data["history"] = array();
		$this->data["history"][] = array("user" => me(), "changed" => now());
		$this->data["data"]["text"] = htmlspecialchars($text);

		for($i=0;$i<count($C);$i++) {
			file_put_contents($C[$i], json_encode($this->data));
		}

		// }}}
	}

	public function deleteMine() {
		$np = str_replace("_".me()."_post", "_DELETED_post", $this->path);
		rename($this->path, $np);
	}

	public function addfile($F) {
		// {{{
		$names = array();
		for($i=0;$i<count($F["name"]);$i++) {
			$names[$i] = 'file_'.time()."_".fixname($F["name"][$i]);
			move_uploaded_file($F["tmp_name"][$i], $this->path.'/'.$names[$i]);
		}
		return $names;
		// }}}
	}
	public function addlocalfile($fn) {
		$fn2 = fixname(basename($fn));
		if(substr($fn2,0,5)!="file_") {
            $imageName = time()."_".$fn2;
			$fn2 = 'file_'.$imageName;
		} else {
            $imageName = substr($fn2,5);
        }
		rename($fn, $this->path.'/'.$fn2);
        return $imageName;
	}

	public function setTreelink($id, $group) {
		// {{{
		$tree = new tree($group);
		if($this->tree!=array()) {
			$tree->decrement($this->tree["id"]);
		}
		$entry = $tree->increment($id);

		$title = $entry["title"];
		$T = array("id" => $id, "group" => $group, "title" => $title); // $tree->getTreeTitle($id)
		file_put_contents($this->path.'/tree.json', json_encode($T));
		return $T;
		// }}}
	}

}
?>
<?php
class image {
	protected $im;
	protected $ext = ".jpg";
	public $w = 0;
	public $h = 0;
	function __construct($fn="", $ext="") {
		if($fn!="") {
			if($ext=="")  $this->ext = strtolower(substr($fn,strrpos($fn,".")));
			else $this->ext = $ext;
			if($this->ext==".jpg") $this->im = imageCreateFromJpeg($fn);
			else if($this->ext==".png") $this->im = imageCreateFromPng($fn);
		}
	}
	
	function resize($w, $h, $upscale=true) {
		$wh1 = $this->scaleFit(imageSx($this->im),imageSy($this->im), $w, $h, $upscale);
		$this->w = $wh1[0];
		$this->h = $wh1[1];
		#vd($wh1);exit;
		$im2 = imageCreateTrueColor($wh1[0], $wh1[1]);
		imageCopyResampled($im2, $this->im, 0,0,0,0,$wh1[0],$wh1[1],imageSx($this->im),imageSy($this->im));
		$this->im = $im2;
	}
	
	public function save($fn) {
		
		if($this->ext==".jpg") imageJpeg($this->im, $fn);
		else if($this->ext==".png") imagePng($this->im, $fn);
		
	}
	
	public function deliver($fn) {
		if($this->ext==".jpg") imageJpeg($this->im);
		else if($this->ext==".png") imagePng($this->im);
	}
	
	public function getBase64() {
		$fn = "files/kmco/".microtime(true).".tmpimage";
		
		if($this->ext==".jpg") imageJpeg($this->im, $fn);
		else if($this->ext==".png") imagePng($this->im, $fn);
		
		if($this->ext==".jpg") $filetype = "jpeg";
		else if($this->ext==".png") $filetype = "png";
		
		$imgbinary = file_get_contents($fn);
		$base64 = 'data:image/' . $filetype . ';base64,' . base64_encode($imgbinary);
		unlink($fn);
		
		return $base64;
	}
	
	
	private function scaleFit($origWidth, $origHeight, $newWidth, $newHeight, $upscale=true) {
		// {{{
		if ( ($upscale && $origWidth != $newWidth) || (!$upscale && $origWidth > $newWidth) ) {
		    $origHeight = $origHeight * ($newWidth / $origWidth);
		    $origWidth = $newWidth;
		}
		if ($origHeight > $newHeight) {
		    $origWidth = $origWidth * ($newHeight / $origHeight);
		    $origHeight = $newHeight;
		}
		return(array(floor($origWidth), floor($origHeight)));
		// }}}
	}
	
}
?>
<?php
class history {
	private $path;
	private $userid;
	public function __construct($userid) {
		$this->path = checkfordir("history");
		$this->userid = $userid;
	}
	
	
	public function add($type, $id, $group="own", $mid=-1, $info="") {
		// {{{
		$path = checkfordir("history/".date("Ymd"));
		if($mid==-1) $mid = mid();
		$data = array("date" => now(),
				"type" => $type,
				"id" => $id,
				"mid" => $mid,
				"info" => $info,
				"userid" => $this->userid,
				"group" => $group
			);
		file_put_contents($path.'/'.$mid."_".$this->userid."_".$group.".json", json_encode($data) );
		
		// }}}
	}
	
	public function newerThan($time, $group="own") {
		// {{{
		//$path = checkfordir("history/".date("Ymd"));
		
		$paths  = array();
		for($i=10;$i>=0;$i--) {
			$path = checkfordir("history/".date("Ymd", time()-60*60*24*$i));
			$paths[] = $path;
		}
		$f = glob("{".implode(",",$paths)."}".'/*'.$this->userid.'_*', GLOB_BRACE);


		//$f = glob($path.'/*'.$this->userid.'_*');
		
		if($f==false || count($f)==0) return false;
		
		for($i=0;$i<count($f);$i++) {
			$n = explode("_", basename($f[$i]));
			if($n[0]>$time) {
				//$n = explode("_", basename($f[count($f)-1]));
				//return $n[0];
				return readJson($f[count($f)-1]);
			}
		}
		return false;
		// }}}
	}
	
}
?>
<?php
class groups {
	
	public $path;
	public function __construct() {
		$groupsdir = "groups";
		$this->path = checkfordir($groupsdir);
	}
	
	public function getAll() {
		$G = glob($this->path.'/*_group');
		if($G===false) $G = array();
		
		$groups = array();
		for($i=0;$i<count($G);$i++) {
			$groups[] = new group($G[$i]);
		}
		return $groups;
	}
	
	public function add($name, $user) {
		$id = microtime(true);
		$path = checkfordir("groups/".$id.'_'.fixname($name).'_group');
		$data = array("id" => $id,
				"name" => htmlspecialchars($name),
				"creator" => $user,
			);
		file_put_contents($path.'/0.group', json_encode($data));
		file_put_contents($path.'/'.$user.'.member', json_encode(array("since" => now())) );
	}
	
}
?>
<?php
class group {
	public $path;
	public $data = array();
	public function __construct($path, $id="") {
		if($id!="") {
			$groupsdir = "groups";
			$allpath = checkfordir($groupsdir);
			
			$G = glob($allpath.'/'.$id.'_*_group');
			if($G===false) die("Wrong Group-ID!");
			$path = $G[0];
			
		}
		$this->path = $path;
		$this->data = json_decode(file_get_contents($this->path.'/0.group'), true);
	
		$mems = glob($path.'/*.member');
		$members = array();
		for($i=0;$i<count($mems);$i++) {
			$m = basename($mems[$i]);
			$members[] = substr($m,0,strrpos($m,"."));
		}
		$this->data["members"] = $members;
	}
	
	public function increment() {
		if(!isset($this->data["posts"])) $this->data["posts"] = 0;
		$this->data["posts"]++;
		$this->save();
	}
	
	public function save() {
		file_put_contents($this->path.'/0.group', json_encode($this->data));
	}
	
	public function addMember($user) {
		if(!file_Exists($this->path.'/'.$user.'.member')) {
			file_put_contents($this->path.'/'.$user.'.member', json_encode(array("since" => now())) );
			
			
			$P = new posts();
			$data = array("date" => date("Y-m-d H:i:s"),
				"text" => htmlspecialchars(getS("userName"). " ist der Gruppe beigetreten."),
				"newformrecipienttype"=> "alle",
				"recipients"=> array(),
				"newformcommenttype"=> "keine",
				"newformeditable"=> "nein",
				);
			$P->save($data, $_REQUEST["group"], me());
			
		}
	}
}
?>
<?php
class files {
	public $path;
	public function __construct() {
		$profildir = "files";
		$this->path = checkfordir($profildir);
	}

	
	public function add($tmpFile, $name) {
		$sub = date("Ym");
		$filepath = checkfordir('files/'.$sub);
		
		$name2 = $name;
		$i=0;
		while(file_exists($filepath.'/'.$name2)) {
			$i++;
			$p = strrpos($name,".");
			$name2 = substr($name,0,$p)."_".$i.substr($name,$p);
		}
		rename($tmpFile, $filepath.'/'.$name2);
		return $sub.'/'.$name2;
	}
	
	public function exists($fn) {
		if(file_exists($this->path.'/'.$fn)) return true;
		return false;
	}
	
	public function delete($fn) {
		if($this->exists($fn)) {
			unlink($this->fullPath($fn));
			return true;
		}
		return false;
	}
	
	public function fullPath($fn) {
		return $this->path.'/'.$fn;
	}
	
	public function addBeforeExt($fn, $add) {
		$p = strrpos($fn,".");
		$fn2 = substr($fn,0,$p).$add.substr($fn,$p);
		return $fn2;
	}
	
}
?>
<?php
class feed {

    public $title = "RSS-Feed";
    public $link = "";
    public $descrpition = "";
    public $nr = 0;

    public $items = array();

    public function __construct() {
        $this->nr = 0;
        $this->items = array();
    }

    public function rss() {
        $rssfeed = '';
        $rssfeed = '<'.'?xml version="1.0" encoding="utf-8"?'.'>';
        $rssfeed .= '<rss version="2.0">';
        $rssfeed .= '<channel>';
        $rssfeed .= '<title>'.$this->title.'</title>';
        $rssfeed .= '<link>'.$this->link.'</link>';
        $rssfeed .= '<description>'.$this->description.'</description>';
        #$rssfeed .= '<language>de</language>';
        #$rssfeed .= '<copyright></copyright>';



        for($i=0;$i<count($this->items);$i++) {
            $line = $this->items[$i];
            $rssfeed .= '<item>';
            $rssfeed .= '<title>' . $line["title"] . '</title>';
            $rssfeed .= '<description>' . $line["description"] . '</description>';
            $rssfeed .= '<link>' . $line["link"] . '</link>';
            $rssfeed .= '<pubDate>' . date("D, d M Y H:i:s O", strtotime($line["date"])) . '</pubDate>';
            $rssfeed .= '</item>';
        }

        $rssfeed .= '</channel>';
        $rssfeed .= '</rss>';
        return $rssfeed;
    }

    public function addItem() {
        $this->items[] = array();
        $this->nr = count($this->items)-1;
    }

    public function setTitle($title) {
        $this->items[$this->nr]["title"] = $title;
    }
    public function setDescription($desc) {
        $this->items[$this->nr]["description"] = $desc;
    }
    public function setLink($link) {
        $this->items[$this->nr]["link"] = $link;
    }
    public function setDate($date) {
        $this->items[$this->nr]["date"] = $date;
    }

}
?><?php
class comment {
	public $data = array();
	public function __construct($fn) {
		$this->data = json_decode(file_get_contents($fn), true);
	}
}
?>
<?php

#
# Markdown Parser Class
#

class Markdown {

	### Version ###

	const  MARKDOWNLIB_VERSION  =  "1.3";

	### Simple Function Interface ###

	public static function defaultTransform($text) {
	#
	# Initialize the parser and return the result of its transform method.
	# This will work fine for derived classes too.
	#
		# Take parser class on which this function was called.
		$parser_class = \get_called_class();

		# try to take parser from the static parser list
		static $parser_list;
		$parser =& $parser_list[$parser_class];

		# create the parser it not already set
		if (!$parser)
			$parser = new $parser_class;

		# Transform text using parser.
		return $parser->transform($text);
	}

	### Configuration Variables ###

	# Change to ">" for HTML output.
	public $empty_element_suffix = " />";
	public $tab_width = 4;
	
	# Change to `true` to disallow markup or entities.
	public $no_markup = false;
	public $no_entities = false;
	
	# Predefined urls and titles for reference links and images.
	public $predef_urls = array();
	public $predef_titles = array();


	### Parser Implementation ###

	# Regex to match balanced [brackets].
	# Needed to insert a maximum bracked depth while converting to PHP.
	protected $nested_brackets_depth = 6;
	protected $nested_brackets_re;
	
	protected $nested_url_parenthesis_depth = 4;
	protected $nested_url_parenthesis_re;

	# Table of hash values for escaped characters:
	protected $escape_chars = '\`*_{}[]()>#+-.!';
	protected $escape_chars_re;


	public function __construct() {
	#
	# Constructor function. Initialize appropriate member variables.
	#
		$this->_initDetab();
		$this->prepareItalicsAndBold();
	
		$this->nested_brackets_re = 
			str_repeat('(?>[^\[\]]+|\[', $this->nested_brackets_depth).
			str_repeat('\])*', $this->nested_brackets_depth);
	
		$this->nested_url_parenthesis_re = 
			str_repeat('(?>[^()\s]+|\(', $this->nested_url_parenthesis_depth).
			str_repeat('(?>\)))*', $this->nested_url_parenthesis_depth);
		
		$this->escape_chars_re = '['.preg_quote($this->escape_chars).']';
		
		# Sort document, block, and span gamut in ascendent priority order.
		asort($this->document_gamut);
		asort($this->block_gamut);
		asort($this->span_gamut);
	}


	# Internal hashes used during transformation.
	protected $urls = array();
	protected $titles = array();
	protected $html_hashes = array();
	
	# Status flag to avoid invalid nesting.
	protected $in_anchor = false;
	
	
	protected function setup() {
	#
	# Called before the transformation process starts to setup parser 
	# states.
	#
		# Clear global hashes.
		$this->urls = $this->predef_urls;
		$this->titles = $this->predef_titles;
		$this->html_hashes = array();
		
		$this->in_anchor = false;
	}
	
	protected function teardown() {
	#
	# Called after the transformation process to clear any variable 
	# which may be taking up memory unnecessarly.
	#
		$this->urls = array();
		$this->titles = array();
		$this->html_hashes = array();
	}


	public function transform($text) {
	#
	# Main function. Performs some preprocessing on the input text
	# and pass it through the document gamut.
	#
		$this->setup();
	
		# Remove UTF-8 BOM and marker character in input, if present.
		$text = preg_replace('{^\xEF\xBB\xBF|\x1A}', '', $text);

		# Standardize line endings:
		#   DOS to Unix and Mac to Unix
		$text = preg_replace('{\r\n?}', "\n", $text);

		# Make sure $text ends with a couple of newlines:
		$text .= "\n\n";

		# Convert all tabs to spaces.
		$text = $this->detab($text);

		# Turn block-level HTML blocks into hash entries
		$text = $this->hashHTMLBlocks($text);

		# Strip any lines consisting only of spaces and tabs.
		# This makes subsequent regexen easier to write, because we can
		# match consecutive blank lines with /\n+/ instead of something
		# contorted like /[ ]*\n+/ .
		$text = preg_replace('/^[ ]+$/m', '', $text);

		# Run document gamut methods.
		foreach ($this->document_gamut as $method => $priority) {
			$text = $this->$method($text);
		}
		
		$this->teardown();

		return $text . "\n";
	}
	
	protected $document_gamut = array(
		# Strip link definitions, store in hashes.
		"stripLinkDefinitions" => 20,
		
		"runBasicBlockGamut"   => 30,
		);


	protected function stripLinkDefinitions($text) {
	#
	# Strips link definitions from text, stores the URLs and titles in
	# hash references.
	#
		$less_than_tab = $this->tab_width - 1;

		# Link defs are in the form: ^[id]: url "optional title"
		$text = preg_replace_callback('{
							^[ ]{0,'.$less_than_tab.'}\[(.+)\][ ]?:	# id = $1
							  [ ]*
							  \n?				# maybe *one* newline
							  [ ]*
							(?:
							  <(.+?)>			# url = $2
							|
							  (\S+?)			# url = $3
							)
							  [ ]*
							  \n?				# maybe one newline
							  [ ]*
							(?:
								(?<=\s)			# lookbehind for whitespace
								["(]
								(.*?)			# title = $4
								[")]
								[ ]*
							)?	# title is optional
							(?:\n+|\Z)
			}xm',
			array(&$this, '_stripLinkDefinitions_callback'),
			$text);
		return $text;
	}
	protected function _stripLinkDefinitions_callback($matches) {
		$link_id = strtolower($matches[1]);
		$url = $matches[2] == '' ? $matches[3] : $matches[2];
		$this->urls[$link_id] = $url;
		$this->titles[$link_id] =& $matches[4];
		return ''; # String that will replace the block
	}


	protected function hashHTMLBlocks($text) {
		if ($this->no_markup)  return $text;

		$less_than_tab = $this->tab_width - 1;

		# Hashify HTML blocks:
		# We only want to do this for block-level HTML tags, such as headers,
		# lists, and tables. That's because we still want to wrap <p>s around
		# "paragraphs" that are wrapped in non-block-level tags, such as anchors,
		# phrase emphasis, and spans. The list of tags we're looking for is
		# hard-coded:
		#
		# *  List "a" is made of tags which can be both inline or block-level.
		#    These will be treated block-level when the start tag is alone on 
		#    its line, otherwise they're not matched here and will be taken as 
		#    inline later.
		# *  List "b" is made of tags which are always block-level;
		#
		$block_tags_a_re = 'ins|del';
		$block_tags_b_re = 'p|div|h[1-6]|blockquote|pre|table|dl|ol|ul|address|'.
						   'script|noscript|form|fieldset|iframe|math|svg|'.
						   'article|section|nav|aside|hgroup|header|footer|'.
						   'figure';

		# Regular expression for the content of a block tag.
		$nested_tags_level = 4;
		$attr = '
			(?>				# optional tag attributes
			  \s			# starts with whitespace
			  (?>
				[^>"/]+		# text outside quotes
			  |
				/+(?!>)		# slash not followed by ">"
			  |
				"[^"]*"		# text inside double quotes (tolerate ">")
			  |
				\'[^\']*\'	# text inside single quotes (tolerate ">")
			  )*
			)?	
			';
		$content =
			str_repeat('
				(?>
				  [^<]+			# content without tag
				|
				  <\2			# nested opening tag
					'.$attr.'	# attributes
					(?>
					  />
					|
					  >', $nested_tags_level).	# end of opening tag
					  '.*?'.					# last level nested tag content
			str_repeat('
					  </\2\s*>	# closing nested tag
					)
				  |				
					<(?!/\2\s*>	# other tags with a different name
				  )
				)*',
				$nested_tags_level);
		$content2 = str_replace('\2', '\3', $content);

		# First, look for nested blocks, e.g.:
		# 	<div>
		# 		<div>
		# 		tags for inner block must be indented.
		# 		</div>
		# 	</div>
		#
		# The outermost tags must start at the left margin for this to match, and
		# the inner nested divs must be indented.
		# We need to do this before the next, more liberal match, because the next
		# match will start at the first `<div>` and stop at the first `</div>`.
		$text = preg_replace_callback('{(?>
			(?>
				(?<=\n\n)		# Starting after a blank line
				|				# or
				\A\n?			# the beginning of the doc
			)
			(						# save in $1

			  # Match from `\n<tag>` to `</tag>\n`, handling nested tags 
			  # in between.
					
						[ ]{0,'.$less_than_tab.'}
						<('.$block_tags_b_re.')# start tag = $2
						'.$attr.'>			# attributes followed by > and \n
						'.$content.'		# content, support nesting
						</\2>				# the matching end tag
						[ ]*				# trailing spaces/tabs
						(?=\n+|\Z)	# followed by a newline or end of document

			| # Special version for tags of group a.

						[ ]{0,'.$less_than_tab.'}
						<('.$block_tags_a_re.')# start tag = $3
						'.$attr.'>[ ]*\n	# attributes followed by >
						'.$content2.'		# content, support nesting
						</\3>				# the matching end tag
						[ ]*				# trailing spaces/tabs
						(?=\n+|\Z)	# followed by a newline or end of document
					
			| # Special case just for <hr />. It was easier to make a special 
			  # case than to make the other regex more complicated.
			
						[ ]{0,'.$less_than_tab.'}
						<(hr)				# start tag = $2
						'.$attr.'			# attributes
						/?>					# the matching end tag
						[ ]*
						(?=\n{2,}|\Z)		# followed by a blank line or end of document
			
			| # Special case for standalone HTML comments:
			
					[ ]{0,'.$less_than_tab.'}
					(?s:
						<!-- .*? -->
					)
					[ ]*
					(?=\n{2,}|\Z)		# followed by a blank line or end of document
			
			| # PHP and ASP-style processor instructions (<? and <%)
			
					[ ]{0,'.$less_than_tab.'}
					(?s:
						<([?%])			# $2
						.*?
						\2>
					)
					[ ]*
					(?=\n{2,}|\Z)		# followed by a blank line or end of document
					
			)
			)}Sxmi',
			array(&$this, '_hashHTMLBlocks_callback'),
			$text);

		return $text;
	}
	protected function _hashHTMLBlocks_callback($matches) {
		$text = $matches[1];
		$key  = $this->hashBlock($text);
		return "\n\n$key\n\n";
	}
	
	
	protected function hashPart($text, $boundary = 'X') {
	#
	# Called whenever a tag must be hashed when a function insert an atomic 
	# element in the text stream. Passing $text to through this function gives
	# a unique text-token which will be reverted back when calling unhash.
	#
	# The $boundary argument specify what character should be used to surround
	# the token. By convension, "B" is used for block elements that needs not
	# to be wrapped into paragraph tags at the end, ":" is used for elements
	# that are word separators and "X" is used in the general case.
	#
		# Swap back any tag hash found in $text so we do not have to `unhash`
		# multiple times at the end.
		$text = $this->unhash($text);
		
		# Then hash the block.
		static $i = 0;
		$key = "$boundary\x1A" . ++$i . $boundary;
		$this->html_hashes[$key] = $text;
		return $key; # String that will replace the tag.
	}


	protected function hashBlock($text) {
	#
	# Shortcut function for hashPart with block-level boundaries.
	#
		return $this->hashPart($text, 'B');
	}


	protected $block_gamut = array(
	#
	# These are all the transformations that form block-level
	# tags like paragraphs, headers, and list items.
	#
		"doHeaders"         => 10,
		"doHorizontalRules" => 20,
		
		"doLists"           => 40,
		"doCodeBlocks"      => 50,
		"doBlockQuotes"     => 60,
		);

	protected function runBlockGamut($text) {
	#
	# Run block gamut tranformations.
	#
		# We need to escape raw HTML in Markdown source before doing anything 
		# else. This need to be done for each block, and not only at the 
		# begining in the Markdown function since hashed blocks can be part of
		# list items and could have been indented. Indented blocks would have 
		# been seen as a code block in a previous pass of hashHTMLBlocks.
		$text = $this->hashHTMLBlocks($text);
		
		return $this->runBasicBlockGamut($text);
	}
	
	protected function runBasicBlockGamut($text) {
	#
	# Run block gamut tranformations, without hashing HTML blocks. This is 
	# useful when HTML blocks are known to be already hashed, like in the first
	# whole-document pass.
	#
		foreach ($this->block_gamut as $method => $priority) {
			$text = $this->$method($text);
		}
		
		# Finally form paragraph and restore hashed blocks.
		$text = $this->formParagraphs($text);

		return $text;
	}
	
	
	protected function doHorizontalRules($text) {
		# Do Horizontal Rules:
		return preg_replace(
			'{
				^[ ]{0,3}	# Leading space
				([-*_])		# $1: First marker
				(?>			# Repeated marker group
					[ ]{0,2}	# Zero, one, or two spaces.
					\1			# Marker character
				){2,}		# Group repeated at least twice
				[ ]*		# Tailing spaces
				$			# End of line.
			}mx',
			"\n".$this->hashBlock("<hr$this->empty_element_suffix")."\n", 
			$text);
	}


	protected $span_gamut = array(
	#
	# These are all the transformations that occur *within* block-level
	# tags like paragraphs, headers, and list items.
	#
		# Process character escapes, code spans, and inline HTML
		# in one shot.
		"parseSpan"           => -30,

		# Process anchor and image tags. Images must come first,
		# because ![foo][f] looks like an anchor.
		"doImages"            =>  10,
		"doAnchors"           =>  20,
		
		# Make links out of things like `<http://example.com/>`
		# Must come after doAnchors, because you can use < and >
		# delimiters in inline links like [this](<url>).
		"doAutoLinks"         =>  30,
		"encodeAmpsAndAngles" =>  40,

		"doItalicsAndBold"    =>  50,
		"doHardBreaks"        =>  60,
		);

	protected function runSpanGamut($text) {
	#
	# Run span gamut tranformations.
	#
		foreach ($this->span_gamut as $method => $priority) {
			$text = $this->$method($text);
		}

		return $text;
	}
	
	
	protected function doHardBreaks($text) {
		# Do hard breaks:
		return preg_replace_callback('/ {2,}\n/', 
			array(&$this, '_doHardBreaks_callback'), $text);
	}
	protected function _doHardBreaks_callback($matches) {
		return $this->hashPart("<br$this->empty_element_suffix\n");
	}


	protected function doAnchors($text) {
	#
	# Turn Markdown link shortcuts into XHTML <a> tags.
	#
		if ($this->in_anchor) return $text;
		$this->in_anchor = true;
		
		#
		# First, handle reference-style links: [link text] [id]
		#
		$text = preg_replace_callback('{
			(					# wrap whole match in $1
			  \[
				('.$this->nested_brackets_re.')	# link text = $2
			  \]

			  [ ]?				# one optional space
			  (?:\n[ ]*)?		# one optional newline followed by spaces

			  \[
				(.*?)		# id = $3
			  \]
			)
			}xs',
			array(&$this, '_doAnchors_reference_callback'), $text);

		#
		# Next, inline-style links: [link text](url "optional title")
		#
		$text = preg_replace_callback('{
			(				# wrap whole match in $1
			  \[
				('.$this->nested_brackets_re.')	# link text = $2
			  \]
			  \(			# literal paren
				[ \n]*
				(?:
					<(.+?)>	# href = $3
				|
					('.$this->nested_url_parenthesis_re.')	# href = $4
				)
				[ \n]*
				(			# $5
				  ([\'"])	# quote char = $6
				  (.*?)		# Title = $7
				  \6		# matching quote
				  [ \n]*	# ignore any spaces/tabs between closing quote and )
				)?			# title is optional
			  \)
			)
			}xs',
			array(&$this, '_doAnchors_inline_callback'), $text);

		#
		# Last, handle reference-style shortcuts: [link text]
		# These must come last in case you've also got [link text][1]
		# or [link text](/foo)
		#
		$text = preg_replace_callback('{
			(					# wrap whole match in $1
			  \[
				([^\[\]]+)		# link text = $2; can\'t contain [ or ]
			  \]
			)
			}xs',
			array(&$this, '_doAnchors_reference_callback'), $text);

		$this->in_anchor = false;
		return $text;
	}
	protected function _doAnchors_reference_callback($matches) {
		$whole_match =  $matches[1];
		$link_text   =  $matches[2];
		$link_id     =& $matches[3];

		if ($link_id == "") {
			# for shortcut links like [this][] or [this].
			$link_id = $link_text;
		}
		
		# lower-case and turn embedded newlines into spaces
		$link_id = strtolower($link_id);
		$link_id = preg_replace('{[ ]?\n}', ' ', $link_id);

		if (isset($this->urls[$link_id])) {
			$url = $this->urls[$link_id];
			$url = $this->encodeAttribute($url);
			
			$result = "<a href=\"$url\"";
			if ( isset( $this->titles[$link_id] ) ) {
				$title = $this->titles[$link_id];
				$title = $this->encodeAttribute($title);
				$result .=  " title=\"$title\"";
			}
		
			$link_text = $this->runSpanGamut($link_text);
			$result .= ">$link_text</a>";
			$result = $this->hashPart($result);
		}
		else {
			$result = $whole_match;
		}
		return $result;
	}
	protected function _doAnchors_inline_callback($matches) {
		$whole_match	=  $matches[1];
		$link_text		=  $this->runSpanGamut($matches[2]);
		$url			=  $matches[3] == '' ? $matches[4] : $matches[3];
		$title			=& $matches[7];

		$url = $this->encodeAttribute($url);

		$result = "<a href=\"$url\"";
		if (isset($title)) {
			$title = $this->encodeAttribute($title);
			$result .=  " title=\"$title\"";
		}
		
		$link_text = $this->runSpanGamut($link_text);
		$result .= ">$link_text</a>";

		return $this->hashPart($result);
	}


	protected function doImages($text) {
	#
	# Turn Markdown image shortcuts into <img> tags.
	#
		#
		# First, handle reference-style labeled images: ![alt text][id]
		#
		$text = preg_replace_callback('{
			(				# wrap whole match in $1
			  !\[
				('.$this->nested_brackets_re.')		# alt text = $2
			  \]

			  [ ]?				# one optional space
			  (?:\n[ ]*)?		# one optional newline followed by spaces

			  \[
				(.*?)		# id = $3
			  \]

			)
			}xs', 
			array(&$this, '_doImages_reference_callback'), $text);

		#
		# Next, handle inline images:  ![alt text](url "optional title")
		# Don't forget: encode * and _
		#
		$text = preg_replace_callback('{
			(				# wrap whole match in $1
			  !\[
				('.$this->nested_brackets_re.')		# alt text = $2
			  \]
			  \s?			# One optional whitespace character
			  \(			# literal paren
				[ \n]*
				(?:
					<(\S*)>	# src url = $3
				|
					('.$this->nested_url_parenthesis_re.')	# src url = $4
				)
				[ \n]*
				(			# $5
				  ([\'"])	# quote char = $6
				  (.*?)		# title = $7
				  \6		# matching quote
				  [ \n]*
				)?			# title is optional
			  \)
			)
			}xs',
			array(&$this, '_doImages_inline_callback'), $text);

		return $text;
	}
	protected function _doImages_reference_callback($matches) {
		$whole_match = $matches[1];
		$alt_text    = $matches[2];
		$link_id     = strtolower($matches[3]);

		if ($link_id == "") {
			$link_id = strtolower($alt_text); # for shortcut links like ![this][].
		}

		$alt_text = $this->encodeAttribute($alt_text);
		if (isset($this->urls[$link_id])) {
			$url = $this->encodeAttribute($this->urls[$link_id]);
			$result = "<img src=\"$url\" alt=\"$alt_text\"";
			if (isset($this->titles[$link_id])) {
				$title = $this->titles[$link_id];
				$title = $this->encodeAttribute($title);
				$result .=  " title=\"$title\"";
			}
			$result .= $this->empty_element_suffix;
			$result = $this->hashPart($result);
		}
		else {
			# If there's no such link ID, leave intact:
			$result = $whole_match;
		}

		return $result;
	}
	protected function _doImages_inline_callback($matches) {
		$whole_match	= $matches[1];
		$alt_text		= $matches[2];
		$url			= $matches[3] == '' ? $matches[4] : $matches[3];
		$title			=& $matches[7];

		$alt_text = $this->encodeAttribute($alt_text);
		$url = $this->encodeAttribute($url);
		$result = "<img src=\"$url\" alt=\"$alt_text\"";
		if (isset($title)) {
			$title = $this->encodeAttribute($title);
			$result .=  " title=\"$title\""; # $title already quoted
		}
		$result .= $this->empty_element_suffix;

		return $this->hashPart($result);
	}


	protected function doHeaders($text) {
		# Setext-style headers:
		#	  Header 1
		#	  ========
		#  
		#	  Header 2
		#	  --------
		#
		$text = preg_replace_callback('{ ^(.+?)[ ]*\n(=+|-+)[ ]*\n+ }mx',
			array(&$this, '_doHeaders_callback_setext'), $text);

		# atx-style headers:
		#	# Header 1
		#	## Header 2
		#	## Header 2 with closing hashes ##
		#	...
		#	###### Header 6
		#
		$text = preg_replace_callback('{
				^(\#{1,6})	# $1 = string of #\'s
				[ ]*
				(.+?)		# $2 = Header text
				[ ]*
				\#*			# optional closing #\'s (not counted)
				\n+
			}xm',
			array(&$this, '_doHeaders_callback_atx'), $text);

		return $text;
	}
	protected function _doHeaders_callback_setext($matches) {
		# Terrible hack to check we haven't found an empty list item.
		if ($matches[2] == '-' && preg_match('{^-(?: |$)}', $matches[1]))
			return $matches[0];
		
		$level = $matches[2]{0} == '=' ? 1 : 2;
		$block = "<h$level>".$this->runSpanGamut($matches[1])."</h$level>";
		return "\n" . $this->hashBlock($block) . "\n\n";
	}
	protected function _doHeaders_callback_atx($matches) {
		$level = strlen($matches[1]);
		$block = "<h$level>".$this->runSpanGamut($matches[2])."</h$level>";
		return "\n" . $this->hashBlock($block) . "\n\n";
	}


	protected function doLists($text) {
	#
	# Form HTML ordered (numbered) and unordered (bulleted) lists.
	#
		$less_than_tab = $this->tab_width - 1;

		# Re-usable patterns to match list item bullets and number markers:
		$marker_ul_re  = '[*+-]';
		$marker_ol_re  = '\d+[\.]';
		$marker_any_re = "(?:$marker_ul_re|$marker_ol_re)";

		$markers_relist = array(
			$marker_ul_re => $marker_ol_re,
			$marker_ol_re => $marker_ul_re,
			);

		foreach ($markers_relist as $marker_re => $other_marker_re) {
			# Re-usable pattern to match any entirel ul or ol list:
			$whole_list_re = '
				(								# $1 = whole list
				  (								# $2
					([ ]{0,'.$less_than_tab.'})	# $3 = number of spaces
					('.$marker_re.')			# $4 = first list item marker
					[ ]+
				  )
				  (?s:.+?)
				  (								# $5
					  \z
					|
					  \n{2,}
					  (?=\S)
					  (?!						# Negative lookahead for another list item marker
						[ ]*
						'.$marker_re.'[ ]+
					  )
					|
					  (?=						# Lookahead for another kind of list
					    \n
						\3						# Must have the same indentation
						'.$other_marker_re.'[ ]+
					  )
				  )
				)
			'; // mx
			
			# We use a different prefix before nested lists than top-level lists.
			# See extended comment in _ProcessListItems().
		
			if ($this->list_level) {
				$text = preg_replace_callback('{
						^
						'.$whole_list_re.'
					}mx',
					array(&$this, '_doLists_callback'), $text);
			}
			else {
				$text = preg_replace_callback('{
						(?:(?<=\n)\n|\A\n?) # Must eat the newline
						'.$whole_list_re.'
					}mx',
					array(&$this, '_doLists_callback'), $text);
			}
		}

		return $text;
	}
	protected function _doLists_callback($matches) {
		# Re-usable patterns to match list item bullets and number markers:
		$marker_ul_re  = '[*+-]';
		$marker_ol_re  = '\d+[\.]';
		$marker_any_re = "(?:$marker_ul_re|$marker_ol_re)";
		
		$list = $matches[1];
		$list_type = preg_match("/$marker_ul_re/", $matches[4]) ? "ul" : "ol";
		
		$marker_any_re = ( $list_type == "ul" ? $marker_ul_re : $marker_ol_re );
		
		$list .= "\n";
		$result = $this->processListItems($list, $marker_any_re);
		
		$result = $this->hashBlock("<$list_type>\n" . $result . "</$list_type>");
		return "\n". $result ."\n\n";
	}

	protected $list_level = 0;

	protected function processListItems($list_str, $marker_any_re) {
	#
	#	Process the contents of a single ordered or unordered list, splitting it
	#	into individual list items.
	#
		# The $this->list_level global keeps track of when we're inside a list.
		# Each time we enter a list, we increment it; when we leave a list,
		# we decrement. If it's zero, we're not in a list anymore.
		#
		# We do this because when we're not inside a list, we want to treat
		# something like this:
		#
		#		I recommend upgrading to version
		#		8. Oops, now this line is treated
		#		as a sub-list.
		#
		# As a single paragraph, despite the fact that the second line starts
		# with a digit-period-space sequence.
		#
		# Whereas when we're inside a list (or sub-list), that line will be
		# treated as the start of a sub-list. What a kludge, huh? This is
		# an aspect of Markdown's syntax that's hard to parse perfectly
		# without resorting to mind-reading. Perhaps the solution is to
		# change the syntax rules such that sub-lists must start with a
		# starting cardinal number; e.g. "1." or "a.".
		
		$this->list_level++;

		# trim trailing blank lines:
		$list_str = preg_replace("/\n{2,}\\z/", "\n", $list_str);

		$list_str = preg_replace_callback('{
			(\n)?							# leading line = $1
			(^[ ]*)							# leading whitespace = $2
			('.$marker_any_re.'				# list marker and space = $3
				(?:[ ]+|(?=\n))	# space only required if item is not empty
			)
			((?s:.*?))						# list item text   = $4
			(?:(\n+(?=\n))|\n)				# tailing blank line = $5
			(?= \n* (\z | \2 ('.$marker_any_re.') (?:[ ]+|(?=\n))))
			}xm',
			array(&$this, '_processListItems_callback'), $list_str);

		$this->list_level--;
		return $list_str;
	}
	protected function _processListItems_callback($matches) {
		$item = $matches[4];
		$leading_line =& $matches[1];
		$leading_space =& $matches[2];
		$marker_space = $matches[3];
		$tailing_blank_line =& $matches[5];

		if ($leading_line || $tailing_blank_line || 
			preg_match('/\n{2,}/', $item))
		{
			# Replace marker with the appropriate whitespace indentation
			$item = $leading_space . str_repeat(' ', strlen($marker_space)) . $item;
			$item = $this->runBlockGamut($this->outdent($item)."\n");
		}
		else {
			# Recursion for sub-lists:
			$item = $this->doLists($this->outdent($item));
			$item = preg_replace('/\n+$/', '', $item);
			$item = $this->runSpanGamut($item);
		}

		return "<li>" . $item . "</li>\n";
	}


	protected function doCodeBlocks($text) {
	#
	#	Process Markdown `<pre><code>` blocks.
	#
		$text = preg_replace_callback('{
				(?:\n\n|\A\n?)
				(	            # $1 = the code block -- one or more lines, starting with a space/tab
				  (?>
					[ ]{'.$this->tab_width.'}  # Lines must start with a tab or a tab-width of spaces
					.*\n+
				  )+
				)
				((?=^[ ]{0,'.$this->tab_width.'}\S)|\Z)	# Lookahead for non-space at line-start, or end of doc
			}xm',
			array(&$this, '_doCodeBlocks_callback'), $text);

		return $text;
	}
	protected function _doCodeBlocks_callback($matches) {
		$codeblock = $matches[1];

		$codeblock = $this->outdent($codeblock);
		#$codeblock = htmlspecialchars($codeblock, ENT_NOQUOTES);

		# trim leading newlines and trailing newlines
		$codeblock = preg_replace('/\A\n+|\n+\z/', '', $codeblock);

		$codeblock = "<pre><code>$codeblock\n</code></pre>";
		return "\n\n".$this->hashBlock($codeblock)."\n\n";
	}


	protected function makeCodeSpan($code) {
	#
	# Create a code span markup for $code. Called from handleSpanToken.
	#
		$code = htmlspecialchars(trim($code), ENT_NOQUOTES);
		return $this->hashPart("<code>$code</code>");
	}


	protected $em_relist = array(
		''  => '(?:(?<!\*)\*(?!\*)|(?<!_)_(?!_))(?=\S|$)(?![\.,:;]\s)',
		'*' => '(?<=\S|^)(?<!\*)\*(?!\*)',
		'_' => '(?<=\S|^)(?<!_)_(?!_)',
		);
	protected $strong_relist = array(
		''   => '(?:(?<!\*)\*\*(?!\*)|(?<!_)__(?!_))(?=\S|$)(?![\.,:;]\s)',
		'**' => '(?<=\S|^)(?<!\*)\*\*(?!\*)',
		'__' => '(?<=\S|^)(?<!_)__(?!_)',
		);
	protected $em_strong_relist = array(
		''    => '(?:(?<!\*)\*\*\*(?!\*)|(?<!_)___(?!_))(?=\S|$)(?![\.,:;]\s)',
		'***' => '(?<=\S|^)(?<!\*)\*\*\*(?!\*)',
		'___' => '(?<=\S|^)(?<!_)___(?!_)',
		);
	protected $em_strong_prepared_relist;
	
	protected function prepareItalicsAndBold() {
	#
	# Prepare regular expressions for searching emphasis tokens in any
	# context.
	#
		foreach ($this->em_relist as $em => $em_re) {
			foreach ($this->strong_relist as $strong => $strong_re) {
				# Construct list of allowed token expressions.
				$token_relist = array();
				if (isset($this->em_strong_relist["$em$strong"])) {
					$token_relist[] = $this->em_strong_relist["$em$strong"];
				}
				$token_relist[] = $em_re;
				$token_relist[] = $strong_re;
				
				# Construct master expression from list.
				$token_re = '{('. implode('|', $token_relist) .')}';
				$this->em_strong_prepared_relist["$em$strong"] = $token_re;
			}
		}
	}
	
	protected function doItalicsAndBold($text) {
		$token_stack = array('');
		$text_stack = array('');
		$em = '';
		$strong = '';
		$tree_char_em = false;
		
		while (1) {
			#
			# Get prepared regular expression for seraching emphasis tokens
			# in current context.
			#
			$token_re = $this->em_strong_prepared_relist["$em$strong"];
			
			#
			# Each loop iteration search for the next emphasis token. 
			# Each token is then passed to handleSpanToken.
			#
			$parts = preg_split($token_re, $text, 2, PREG_SPLIT_DELIM_CAPTURE);
			$text_stack[0] .= $parts[0];
			$token =& $parts[1];
			$text =& $parts[2];
			
			if (empty($token)) {
				# Reached end of text span: empty stack without emitting.
				# any more emphasis.
				while ($token_stack[0]) {
					$text_stack[1] .= array_shift($token_stack);
					$text_stack[0] .= array_shift($text_stack);
				}
				break;
			}
			
			$token_len = strlen($token);
			if ($tree_char_em) {
				# Reached closing marker while inside a three-char emphasis.
				if ($token_len == 3) {
					# Three-char closing marker, close em and strong.
					array_shift($token_stack);
					$span = array_shift($text_stack);
					$span = $this->runSpanGamut($span);
					$span = "<strong><em>$span</em></strong>";
					$text_stack[0] .= $this->hashPart($span);
					$em = '';
					$strong = '';
				} else {
					# Other closing marker: close one em or strong and
					# change current token state to match the other
					$token_stack[0] = str_repeat($token{0}, 3-$token_len);
					$tag = $token_len == 2 ? "strong" : "em";
					$span = $text_stack[0];
					$span = $this->runSpanGamut($span);
					$span = "<$tag>$span</$tag>";
					$text_stack[0] = $this->hashPart($span);
					$$tag = ''; # $$tag stands for $em or $strong
				}
				$tree_char_em = false;
			} else if ($token_len == 3) {
				if ($em) {
					# Reached closing marker for both em and strong.
					# Closing strong marker:
					for ($i = 0; $i < 2; ++$i) {
						$shifted_token = array_shift($token_stack);
						$tag = strlen($shifted_token) == 2 ? "strong" : "em";
						$span = array_shift($text_stack);
						$span = $this->runSpanGamut($span);
						$span = "<$tag>$span</$tag>";
						$text_stack[0] .= $this->hashPart($span);
						$$tag = ''; # $$tag stands for $em or $strong
					}
				} else {
					# Reached opening three-char emphasis marker. Push on token 
					# stack; will be handled by the special condition above.
					$em = $token{0};
					$strong = "$em$em";
					array_unshift($token_stack, $token);
					array_unshift($text_stack, '');
					$tree_char_em = true;
				}
			} else if ($token_len == 2) {
				if ($strong) {
					# Unwind any dangling emphasis marker:
					if (strlen($token_stack[0]) == 1) {
						$text_stack[1] .= array_shift($token_stack);
						$text_stack[0] .= array_shift($text_stack);
					}
					# Closing strong marker:
					array_shift($token_stack);
					$span = array_shift($text_stack);
					$span = $this->runSpanGamut($span);
					$span = "<strong>$span</strong>";
					$text_stack[0] .= $this->hashPart($span);
					$strong = '';
				} else {
					array_unshift($token_stack, $token);
					array_unshift($text_stack, '');
					$strong = $token;
				}
			} else {
				# Here $token_len == 1
				if ($em) {
					if (strlen($token_stack[0]) == 1) {
						# Closing emphasis marker:
						array_shift($token_stack);
						$span = array_shift($text_stack);
						$span = $this->runSpanGamut($span);
						$span = "<em>$span</em>";
						$text_stack[0] .= $this->hashPart($span);
						$em = '';
					} else {
						$text_stack[0] .= $token;
					}
				} else {
					array_unshift($token_stack, $token);
					array_unshift($text_stack, '');
					$em = $token;
				}
			}
		}
		return $text_stack[0];
	}


	protected function doBlockQuotes($text) {
		$text = preg_replace_callback('/
			  (								# Wrap whole match in $1
				(?>
				  ^[ ]*>[ ]?			# ">" at the start of a line
					.+\n					# rest of the first line
				  (.+\n)*					# subsequent consecutive lines
				  \n*						# blanks
				)+
			  )
			/xm',
			array(&$this, '_doBlockQuotes_callback'), $text);

		return $text;
	}
	protected function _doBlockQuotes_callback($matches) {
		$bq = $matches[1];
		# trim one level of quoting - trim whitespace-only lines
		$bq = preg_replace('/^[ ]*>[ ]?|^[ ]+$/m', '', $bq);
		$bq = $this->runBlockGamut($bq);		# recurse

		$bq = preg_replace('/^/m', "  ", $bq);
		# These leading spaces cause problem with <pre> content, 
		# so we need to fix that:
		$bq = preg_replace_callback('{(\s*<pre>.+?</pre>)}sx', 
			array(&$this, '_doBlockQuotes_callback2'), $bq);

		return "\n". $this->hashBlock("<blockquote>\n$bq\n</blockquote>")."\n\n";
	}
	protected function _doBlockQuotes_callback2($matches) {
		$pre = $matches[1];
		$pre = preg_replace('/^  /m', '', $pre);
		return $pre;
	}


	protected function formParagraphs($text) {
	#
	#	Params:
	#		$text - string to process with html <p> tags
	#
		# Strip leading and trailing lines:
		$text = preg_replace('/\A\n+|\n+\z/', '', $text);

		$grafs = preg_split('/\n{2,}/', $text, -1, PREG_SPLIT_NO_EMPTY);

		#
		# Wrap <p> tags and unhashify HTML blocks
		#
		foreach ($grafs as $key => $value) {
			if (!preg_match('/^B\x1A[0-9]+B$/', $value)) {
				# Is a paragraph.
				$value = $this->runSpanGamut($value);
				$value = preg_replace('/^([ ]*)/', "<p>", $value);
				$value .= "</p>";
				$grafs[$key] = $this->unhash($value);
			}
			else {
				# Is a block.
				# Modify elements of @grafs in-place...
				$graf = $value;
				$block = $this->html_hashes[$graf];
				$graf = $block;
//				if (preg_match('{
//					\A
//					(							# $1 = <div> tag
//					  <div  \s+
//					  [^>]*
//					  \b
//					  markdown\s*=\s*  ([\'"])	#	$2 = attr quote char
//					  1
//					  \2
//					  [^>]*
//					  >
//					)
//					(							# $3 = contents
//					.*
//					)
//					(</div>)					# $4 = closing tag
//					\z
//					}xs', $block, $matches))
//				{
//					list(, $div_open, , $div_content, $div_close) = $matches;
//
//					# We can't call Markdown(), because that resets the hash;
//					# that initialization code should be pulled into its own sub, though.
//					$div_content = $this->hashHTMLBlocks($div_content);
//					
//					# Run document gamut methods on the content.
//					foreach ($this->document_gamut as $method => $priority) {
//						$div_content = $this->$method($div_content);
//					}
//
//					$div_open = preg_replace(
//						'{\smarkdown\s*=\s*([\'"]).+?\1}', '', $div_open);
//
//					$graf = $div_open . "\n" . $div_content . "\n" . $div_close;
//				}
				$grafs[$key] = $graf;
			}
		}

		return implode("\n\n", $grafs);
	}


	protected function encodeAttribute($text) {
	#
	# Encode text for a double-quoted HTML attribute. This function
	# is *not* suitable for attributes enclosed in single quotes.
	#
		$text = $this->encodeAmpsAndAngles($text);
		$text = str_replace('"', '&quot;', $text);
		return $text;
	}
	
	
	protected function encodeAmpsAndAngles($text) {
	#
	# Smart processing for ampersands and angle brackets that need to 
	# be encoded. Valid character entities are left alone unless the
	# no-entities mode is set.
	#
		if ($this->no_entities) {
			$text = str_replace('&', '&amp;', $text);
		} else {
			# Ampersand-encoding based entirely on Nat Irons's Amputator
			# MT plugin: <http://bumppo.net/projects/amputator/>
			$text = preg_replace('/&(?!#?[xX]?(?:[0-9a-fA-F]+|\w+);)/', 
								'&amp;', $text);;
		}
		# Encode remaining <'s
		$text = str_replace('<', '&lt;', $text);

		return $text;
	}


	protected function doAutoLinks($text) {
		$text = preg_replace_callback('{<((https?|ftp|dict):[^\'">\s]+)>}i', 
			array(&$this, '_doAutoLinks_url_callback'), $text);

		# Email addresses: <address@domain.foo>
		$text = preg_replace_callback('{
			<
			(?:mailto:)?
			(
				(?:
					[-!#$%&\'*+/=?^_`.{|}~\w\x80-\xFF]+
				|
					".*?"
				)
				\@
				(?:
					[-a-z0-9\x80-\xFF]+(\.[-a-z0-9\x80-\xFF]+)*\.[a-z]+
				|
					\[[\d.a-fA-F:]+\]	# IPv4 & IPv6
				)
			)
			>
			}xi',
			array(&$this, '_doAutoLinks_email_callback'), $text);

		return $text;
	}
	protected function _doAutoLinks_url_callback($matches) {
		$url = $this->encodeAttribute($matches[1]);
		$link = "<a href=\"$url\">$url</a>";
		return $this->hashPart($link);
	}
	protected function _doAutoLinks_email_callback($matches) {
		$address = $matches[1];
		$link = $this->encodeEmailAddress($address);
		return $this->hashPart($link);
	}


	protected function encodeEmailAddress($addr) {
	#
	#	Input: an email address, e.g. "foo@example.com"
	#
	#	Output: the email address as a mailto link, with each character
	#		of the address encoded as either a decimal or hex entity, in
	#		the hopes of foiling most address harvesting spam bots. E.g.:
	#
	#	  <p><a href="&#109;&#x61;&#105;&#x6c;&#116;&#x6f;&#58;&#x66;o&#111;
	#        &#x40;&#101;&#x78;&#97;&#x6d;&#112;&#x6c;&#101;&#46;&#x63;&#111;
	#        &#x6d;">&#x66;o&#111;&#x40;&#101;&#x78;&#97;&#x6d;&#112;&#x6c;
	#        &#101;&#46;&#x63;&#111;&#x6d;</a></p>
	#
	#	Based by a filter by Matthew Wickline, posted to BBEdit-Talk.
	#   With some optimizations by Milian Wolff.
	#
		$addr = "mailto:" . $addr;
		$chars = preg_split('/(?<!^)(?!$)/', $addr);
		$seed = (int)abs(crc32($addr) / strlen($addr)); # Deterministic seed.
		
		foreach ($chars as $key => $char) {
			$ord = ord($char);
			# Ignore non-ascii chars.
			if ($ord < 128) {
				$r = ($seed * (1 + $key)) % 100; # Pseudo-random function.
				# roughly 10% raw, 45% hex, 45% dec
				# '@' *must* be encoded. I insist.
				if ($r > 90 && $char != '@') /* do nothing */;
				else if ($r < 45) $chars[$key] = '&#x'.dechex($ord).';';
				else              $chars[$key] = '&#'.$ord.';';
			}
		}
		
		$addr = implode('', $chars);
		$text = implode('', array_slice($chars, 7)); # text without `mailto:`
		$addr = "<a href=\"$addr\">$text</a>";

		return $addr;
	}


	protected function parseSpan($str) {
	#
	# Take the string $str and parse it into tokens, hashing embeded HTML,
	# escaped characters and handling code spans.
	#
		$output = '';
		
		$span_re = '{
				(
					\\\\'.$this->escape_chars_re.'
				|
					(?<![`\\\\])
					`+						# code span marker
			'.( $this->no_markup ? '' : '
				|
					<!--    .*?     -->		# comment
				|
					<\?.*?\?> | <%.*?%>		# processing instruction
				|
					<[!$]?[-a-zA-Z0-9:_]+	# regular tags
					(?>
						\s
						(?>[^"\'>]+|"[^"]*"|\'[^\']*\')*
					)?
					>
				|
					<[-a-zA-Z0-9:_]+\s*/> # xml-style empty tag
				|
					</[-a-zA-Z0-9:_]+\s*> # closing tag
			').'
				)
				}xs';

		while (1) {
			#
			# Each loop iteration seach for either the next tag, the next 
			# openning code span marker, or the next escaped character. 
			# Each token is then passed to handleSpanToken.
			#
			$parts = preg_split($span_re, $str, 2, PREG_SPLIT_DELIM_CAPTURE);
			
			# Create token from text preceding tag.
			if ($parts[0] != "") {
				$output .= $parts[0];
			}
			
			# Check if we reach the end.
			if (isset($parts[1])) {
				$output .= $this->handleSpanToken($parts[1], $parts[2]);
				$str = $parts[2];
			}
			else {
				break;
			}
		}
		
		return $output;
	}
	
	
	protected function handleSpanToken($token, &$str) {
	#
	# Handle $token provided by parseSpan by determining its nature and 
	# returning the corresponding value that should replace it.
	#
		switch ($token{0}) {
			case "\\":
				return $this->hashPart("&#". ord($token{1}). ";");
			case "`":
				# Search for end marker in remaining text.
				if (preg_match('/^(.*?[^`])'.preg_quote($token).'(?!`)(.*)$/sm', 
					$str, $matches))
				{
					$str = $matches[2];
					$codespan = $this->makeCodeSpan($matches[1]);
					return $this->hashPart($codespan);
				}
				return $token; // return as text since no ending marker found.
			default:
				return $this->hashPart($token);
		}
	}


	protected function outdent($text) {
	#
	# Remove one level of line-leading tabs or spaces
	#
		return preg_replace('/^(\t|[ ]{1,'.$this->tab_width.'})/m', '', $text);
	}


	# String length function for detab. `_initDetab` will create a function to 
	# hanlde UTF-8 if the default function does not exist.
	protected $utf8_strlen = 'mb_strlen';
	
	protected function detab($text) {
	#
	# Replace tabs with the appropriate amount of space.
	#
		# For each line we separate the line in blocks delemited by
		# tab characters. Then we reconstruct every line by adding the 
		# appropriate number of space between each blocks.
		
		$text = preg_replace_callback('/^.*\t.*$/m',
			array(&$this, '_detab_callback'), $text);

		return $text;
	}
	protected function _detab_callback($matches) {
		$line = $matches[0];
		$strlen = $this->utf8_strlen; # strlen function for UTF-8.
		
		# Split in blocks.
		$blocks = explode("\t", $line);
		# Add each blocks to the line.
		$line = $blocks[0];
		unset($blocks[0]); # Do not add first block twice.
		foreach ($blocks as $block) {
			# Calculate amount of space, insert spaces, insert block.
			$amount = $this->tab_width - 
				$strlen($line, 'UTF-8') % $this->tab_width;
			$line .= str_repeat(" ", $amount) . $block;
		}
		return $line;
	}
	protected function _initDetab() {
	#
	# Check for the availability of the function in the `utf8_strlen` property
	# (initially `mb_strlen`). If the function is not available, create a 
	# function that will loosely count the number of UTF-8 characters with a
	# regular expression.
	#
		if (function_exists($this->utf8_strlen)) return;
		$this->utf8_strlen = create_function('$text', 'return preg_match_all(
			"/[\\\\x00-\\\\xBF]|[\\\\xC0-\\\\xFF][\\\\x80-\\\\xBF]*/", 
			$text, $m);');
	}


	protected function unhash($text) {
	#
	# Swap back in all the tags hashed by _HashHTMLBlocks.
	#
		return preg_replace_callback('/(.)\x1A[0-9]+\1/', 
			array(&$this, '_unhash_callback'), $text);
	}
	protected function _unhash_callback($matches) {
		return $this->html_hashes[$matches[0]];
	}

}

?>
	<?php
	$sender = $profil->get($P[$i]->data["user"]);
	$sender["smallimage"] = $profil->getImageBase64($sender, 'small');
	//vd($P[$i]);
	?>
	<div class='post' id='<?= htmlid($P[$i]->data["id"]); ?>' rel='<?= $P[$i]->data["id"]; ?>' style='cursor:pointer;padding: 10px;margin-top:10px;background-color: #ffffff;color:#494949;border: solid 1px #ececec;' rel=''
		xonclick="if($(this).attr('rel')=='') {$(this).attr('rel', 'locked'); $(this).find('.functions').slideDown();} else if($(this).attr('rel')=='*') $(this).attr('rel', ''); ">
	
		<div style="max-height:150px;overflow:hidden;" onclick="expand(this);return false;$(this).closest('.post').find('.functions').slideToggle();" class='outerContent'>
			<img src='<?= $sender["smallimage"];?>' width=50 height=50 style="float:left;border-radius: 10px;margin-right:10px;">
		
			<div style='text-align:right;float:right;margin-left: 10px;' class="treeeditlink">
				<span style='font-size:0.8em;'><?= formatDateHuman($P[$i]->data["data"]["date"]); ?></span>
				&nbsp;
				<a href='#' onclick="if(confirm('<?= trans("Diesen Beitrag lֳ¶schen?\\nEr wird nur in Deiner Anzeige entfernt.\\nAndere Empfֳ₪nger kֳ¶nnen ihr weiterhin sehen." ,"Really delete this post?\\nIt will only disappear your view.\\nOther recipients can still see this post.");?>')) {
					window.location='<?= FILENAME;?>?action=delete&id=<?= $P[$i]->data["id"]; ?>'; 
				} return false;" style="color:gray;"><i class="glyphicon glyphicon-remove-circle"></i></a>
				
				<br>	
				<div style='float:right;font-size:0.8em;max-width:200px;'>sichtbar fֳ¼r:
				<?php
				/*
				for($ri=0;$ri < count($P[$i]->data["data"]["recipientNames"]);$ri++) {
					$P[$i]->data["data"]["recipientNames"][$ri] = "<a href='".FILENAME."?profile=".$P[$i]->data["data"]["recipients"][$ri]."'>".$P[$i]->data["data"]["recipientNames"][$ri]."</a>";
				}
				*/
				echo implode(", ", array_merge(array("mich"), $P[$i]->data["data"]["recipientNames"]));
				?>
				</div>
				<div style='clear:both;'></div>
			</div>

			<div style=''>
				<div class='innerContent'>
					<span style='color: #800000;font-weight: bold;'><?= $sender["name"]; ?></span><br/>
					<?= prepareText($P[$i]->data["data"]["text"], $P[$i]); ?>
					
					<?php
                    /*
					$img = $P[$i]->getImages();
					
					if(count($img)>0) { ?>
						<div style='min-height:100px;text-align:center;' id='img_<?= htmlid($P[$i]->data["id"]); ?>'></div>
						<script>
						$(function() {
							openImagePreview('img_<?= htmlid($P[$i]->data["id"]); ?>', '<?= $P[$i]->data["id"];?>');
						});
						</script>
					<?php }
                    */
                    ?>
					
					
				</div>
			</div>
			
			<div style="clear:both;"></div>
		</div>
		
		<div style='display:none;padding-left:58px;padding-top:5px;' class='functions'>
			
			<div style='float:left;'>
				<!--
				<a href='#' onclick="$(this).closest('.functions').slideUp();$(this).closest('.post').attr('rel', '*');return false;"><?= trans("verstecken", "collapse");?></a>
				-->
				<?php if(getConfigValue("enableTrees", "yes")=="yes") {?>
				<div style='float:left;' class="treeeditlink">
					<a href='#' onclick="editpostlink(this, '<?= $P[$i]->data["id"]; ?>');return false;"><i class="glyphicon glyphicon-list"></i>&nbsp;<?= ($P[$i]->tree["title"]!="" ? $P[$i]->tree["title"] : '<span style="color:silver;">'.trans('keine Zuordnung', 'not in tree').'</span>');?></a>
				</div>
				<?php } ?>
				
				
			</div>
			<div style='float:right;'>
				
				<a href='<?= FILENAME;?>?full=<?= $P[$i]->data["id"]; ?>'><i class='glyphicon glyphicon-fullscreen'></i>&nbsp;<?= trans("vollstֳ₪ndige Ansicht", "full view");?></a>
				<?php if($P[$i]->data["data"]["newformcommenttype"]!='keine' || $P[$i]->data["user"]==me() ) { ?>
				<!--
				&nbsp;&nbsp;&nbsp;
				<a href='#' onclick="openreply(this);$(this).hide();return false;"><i class='glyphicon glyphicon-comment'></i>&nbsp;<?= trans("kommentieren", "add comment");?></a>
				-->
				<?php } ?>
				
				<?php /*
				<!--
				&nbsp;&nbsp;
				<a href='#' onclick="return false;"><i class="glyphicon glyphicon-star-empty"></i>&nbsp;<?= trans("Beobachten", "favorite");?></a>
				-->
				<!-- <i class="glyphicon glyphicon-star"></i> -->
				*/ ?>
			</div>
			<div style='clear:both;'></div>
			
			<?php if($P[$i]->data["data"]["newformcommenttype"]!='keine' || $P[$i]->data["user"]==me() ) { ?>
			<div class='replydiv' rel="<?php
				if($P[$i]->data["user"]==me()) echo "allemeine";
				else echo $P[$i]->data["data"]["newformcommenttype"];
			?>"></div>
			<?php } ?>
			
		</div>
		
	</div>
	<?php if($P[$i]->data["data"]["newformcommenttype"]!='keine' || $P[$i]->data["user"]==me() ) { ?>
	<div style='padding-left:70px;' id='comments<?= htmlid($P[$i]->data["id"]); ?>'>
		<?php
		$comments = $P[$i]->getComments();
		for($j=0;$j<count($comments);$j++) {
			$senderC = $profil->get($comments[$j]->data["user"]);
			$senderC["miniimage"] = $profil->getImageBase64($senderC, 'mini');
		?>
			<div style='margin-bottom:1px;border-left:solid 1px #ececec;border-bottom:solid 1px #ececec;border-right:solid 1px #ececec;padding:10px;font-size:0.8em;background-color:#f6f6f6;'>

				<img src='<?= $senderC["miniimage"];?>'  width=25 height=25 style="float:left;border-radius: 5px;margin-right:10px;">
				
				<div style='float:right'><?= formatDateHuman($comments[$j]->data["data"]["date"]); ?></div>
				<div style='float:left;'>
					<span style='color: #800000;font-weight: bold;'><?= $senderC["name"]; ?></span><br/>
					<?= $comments[$j]->data["data"]["text"];?>
				</div>
				<div style='clear:both;'></div>
			
			</div>
		<?php } ?>
	</div>
	<?php } ?>
	

	
	
<?php
$files = $P->getFiles();
$img = $P->getImages();
#vd($img[0]);
?>
	<div class='post' id='<?= htmlid($P->data["id"]); ?>' rel='<?= $P->data["id"]; ?>' style='padding: 10px;margin-top:10px;background-color: #ffffff;color:#494949;border: solid 1px #ececec;' rel=''>
	
		<div style=''>
			<?= prepareText($P->data["data"]["text"], $P); ?>
			
			<?php if(1==2 && count($img)>0) { ?>
				<div style='text-align:center;' id='img_<?= htmlid($P->data["id"]); ?>'></div>
				<script>
				$(function() {
					openImagePreview('img_<?= htmlid($P->data["id"]); ?>', '<?= $P->data["id"];?>');
				});
				</script>
			<?php } ?>
			
		</div>
		
		<div style="clear:both;"></div>
		
		<div style='display:block;padding-left:58px;padding-top:5px;' class='functions'>
			
			<div style='float:left;'>
				
			</div>
			<div style='float:right;'>
				
				<?php if($P->data["data"]["newformcommenttype"]!='keine' || $P->data["user"]==me()) { ?>
				<a href='#' onclick="openreply(this);$(this).hide();return false;"><i class='glyphicon glyphicon-comment'></i>&nbsp;<?= trans("kommentieren", "add comment");?></a>
				<?php } ?>
				<!--
				&nbsp;&nbsp;
				<a href='#' onclick="return false;"><i class="glyphicon glyphicon-star-empty"></i>&nbsp;<?= trans("Beobachten", "favorite");?></a>
				-->
				<!-- <i class="glyphicon glyphicon-star"></i> -->
			</div>
			<div style='clear:both;'></div>
			
			<div class='replydiv'></div>
			
		</div>
		
	</div>
	<?php
	
	if(count($files)>0) {
		?>
		<br/>
		<div style='padding: 10px;background-color: #800000;color:white;'>
		<?= trans("Dateien / Bilder", "Files / Pictures");?>
		</div>
		<div class="list-group" style="margin-bottom:0;">
		<?php for ($j=0;$j<count($files);$j++) { ?>
				<a href="#" class="list-group-item">
				<span class="badge"><?= round($files[$j]->filesize/1024); ?> KB</span>
				<h4 class="list-group-item-heading"><?= $files[$j]->name;?></h4>
				<!-- <p class="list-group-item-text">...</p> -->
				</a>
		<?php } ?>
		</div>
		<?php
	}
	
	
	$comments = $P->getComments();
	if(count($comments)>0) {
	?>
	
	<br/>
	<div style='padding: 10px;background-color: #800000;color:white;'>
		<?= trans("Kommentare", "Comments");?>
	</div>
	
	<div style=''>
		<?php
		for($j=0;$j<count($comments);$j++) {
			$senderC = $profil->get($comments[$j]->data["user"]);
			$senderC["miniimage"] = $profil->getImageBase64($senderC, 'mini');
		?>
			<div style='margin-bottom:1px;border-left:solid 1px #ececec;border-bottom:solid 1px #ececec;border-right:solid 1px #ececec;padding:10px;font-size:0.8em;background-color:#f6f6f6;'>

				<img src='<?= $senderC["miniimage"];?>'  width=25 height=25 style="float:left;border-radius: 5px;margin-right:10px;">
				
				<div style='float:right'><?= formatDateHuman($comments[$j]->data["data"]["date"]); ?></div>
				<div style='float:left;'>
					<span style='color: #800000;font-weight: bold;'><?= $senderC["name"]; ?></span><br/>
					<?= $comments[$j]->data["data"]["text"];?>
				</div>
				<div style='clear:both;'></div>
			
			</div>
		<?php } ?>
	</div>
	<?php } ?>
/*!
 * Bootstrap v3.0.0
 *
 * Copyright 2013 Twitter, Inc
 * Licensed under the Apache License v2.0
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Designed and built with all the love in the world by @mdo and @fat.
 *//*! normalize.css v2.1.0 | MIT License | git.io/normalize */article,aside,details,figcaption,figure,footer,header,hgroup,main,nav,section,summary{display:block}audio,canvas,video{display:inline-block}audio:not([controls]){display:none;height:0}[hidden]{display:none}html{font-family:sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}body{margin:0}a:focus{outline:thin dotted}a:active,a:hover{outline:0}h1{margin:.67em 0;font-size:2em}abbr[title]{border-bottom:1px dotted}b,strong{font-weight:bold}dfn{font-style:italic}hr{height:0;-moz-box-sizing:content-box;box-sizing:content-box}mark{color:#000;background:#ff0}code,kbd,pre,samp{font-family:monospace,serif;font-size:1em}pre{white-space:pre-wrap}q{quotes:"\201C" "\201D" "\2018" "\2019"}small{font-size:80%}sub,sup{position:relative;font-size:75%;line-height:0;vertical-align:baseline}sup{top:-0.5em}sub{bottom:-0.25em}img{border:0}svg:not(:root){overflow:hidden}figure{margin:0}fieldset{padding:.35em .625em .75em;margin:0 2px;border:1px solid #c0c0c0}legend{padding:0;border:0}button,input,select,textarea{margin:0;font-family:inherit;font-size:100%}button,input{line-height:normal}button,select{text-transform:none}button,html input[type="button"],input[type="reset"],input[type="submit"]{cursor:pointer;-webkit-appearance:button}button[disabled],html input[disabled]{cursor:default}input[type="checkbox"],input[type="radio"]{padding:0;box-sizing:border-box}input[type="search"]{-webkit-box-sizing:content-box;-moz-box-sizing:content-box;box-sizing:content-box;-webkit-appearance:textfield}input[type="search"]::-webkit-search-cancel-button,input[type="search"]::-webkit-search-decoration{-webkit-appearance:none}button::-moz-focus-inner,input::-moz-focus-inner{padding:0;border:0}textarea{overflow:auto;vertical-align:top}table{border-collapse:collapse;border-spacing:0}@media print{*{color:#000!important;text-shadow:none!important;background:transparent!important;box-shadow:none!important}a,a:visited{text-decoration:underline}a[href]:after{content:" (" attr(href) ")"}abbr[title]:after{content:" (" attr(title) ")"}.ir a:after,a[href^="javascript:"]:after,a[href^="#"]:after{content:""}pre,blockquote{border:1px solid #999;page-break-inside:avoid}thead{display:table-header-group}tr,img{page-break-inside:avoid}img{max-width:100%!important}@page{margin:2cm .5cm}p,h2,h3{orphans:3;widows:3}h2,h3{page-break-after:avoid}.navbar{display:none}.table td,.table th{background-color:#fff!important}.btn>.caret,.dropup>.btn>.caret{border-top-color:#000!important}.label{border:1px solid #000}.table{border-collapse:collapse!important}.table-bordered th,.table-bordered td{border:1px solid #ddd!important}}*,*:before,*:after{-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}html{font-size:62.5%;-webkit-tap-highlight-color:rgba(0,0,0,0)}body{font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;font-size:14px;line-height:1.428571429;color:#333;background-color:#fff}input,button,select,textarea{font-family:inherit;font-size:inherit;line-height:inherit}button,input,select[multiple],textarea{background-image:none}a{color:#428bca;text-decoration:none}a:hover,a:focus{color:#2a6496;text-decoration:underline}a:focus{outline:thin dotted #333;outline:5px auto -webkit-focus-ring-color;outline-offset:-2px}img{vertical-align:middle}.img-responsive{display:block;height:auto;max-width:100%}.img-rounded{border-radius:6px}.img-thumbnail{display:inline-block;height:auto;max-width:100%;padding:4px;line-height:1.428571429;background-color:#fff;border:1px solid #ddd;border-radius:4px;-webkit-transition:all .2s ease-in-out;transition:all .2s ease-in-out}.img-circle{border-radius:50%}hr{margin-top:20px;margin-bottom:20px;border:0;border-top:1px solid #eee}.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0 0 0 0);border:0}p{margin:0 0 10px}.lead{margin-bottom:20px;font-size:16.099999999999998px;font-weight:200;line-height:1.4}@media(min-width:768px){.lead{font-size:21px}}small{font-size:85%}cite{font-style:normal}.text-muted{color:#999}.text-primary{color:#428bca}.text-warning{color:#c09853}.text-danger{color:#b94a48}.text-success{color:#468847}.text-info{color:#3a87ad}.text-left{text-align:left}.text-right{text-align:right}.text-center{text-align:center}h1,h2,h3,h4,h5,h6,.h1,.h2,.h3,.h4,.h5,.h6{font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;font-weight:500;line-height:1.1}h1 small,h2 small,h3 small,h4 small,h5 small,h6 small,.h1 small,.h2 small,.h3 small,.h4 small,.h5 small,.h6 small{font-weight:normal;line-height:1;color:#999}h1,h2,h3{margin-top:20px;margin-bottom:10px}h4,h5,h6{margin-top:10px;margin-bottom:10px}h1,.h1{font-size:36px}h2,.h2{font-size:30px}h3,.h3{font-size:24px}h4,.h4{font-size:18px}h5,.h5{font-size:14px}h6,.h6{font-size:12px}h1 small,.h1 small{font-size:24px}h2 small,.h2 small{font-size:18px}h3 small,.h3 small,h4 small,.h4 small{font-size:14px}.page-header{padding-bottom:9px;margin:40px 0 20px;border-bottom:1px solid #eee}ul,ol{margin-top:0;margin-bottom:10px}ul ul,ol ul,ul ol,ol ol{margin-bottom:0}.list-unstyled{padding-left:0;list-style:none}.list-inline{padding-left:0;list-style:none}.list-inline>li{display:inline-block;padding-right:5px;padding-left:5px}dl{margin-bottom:20px}dt,dd{line-height:1.428571429}dt{font-weight:bold}dd{margin-left:0}@media(min-width:768px){.dl-horizontal dt{float:left;width:160px;overflow:hidden;clear:left;text-align:right;text-overflow:ellipsis;white-space:nowrap}.dl-horizontal dd{margin-left:180px}.dl-horizontal dd:before,.dl-horizontal dd:after{display:table;content:" "}.dl-horizontal dd:after{clear:both}.dl-horizontal dd:before,.dl-horizontal dd:after{display:table;content:" "}.dl-horizontal dd:after{clear:both}}abbr[title],abbr[data-original-title]{cursor:help;border-bottom:1px dotted #999}abbr.initialism{font-size:90%;text-transform:uppercase}blockquote{padding:10px 20px;margin:0 0 20px;border-left:5px solid #eee}blockquote p{font-size:17.5px;font-weight:300;line-height:1.25}blockquote p:last-child{margin-bottom:0}blockquote small{display:block;line-height:1.428571429;color:#999}blockquote small:before{content:'\2014 \00A0'}blockquote.pull-right{padding-right:15px;padding-left:0;border-right:5px solid #eee;border-left:0}blockquote.pull-right p,blockquote.pull-right small{text-align:right}blockquote.pull-right small:before{content:''}blockquote.pull-right small:after{content:'\00A0 \2014'}q:before,q:after,blockquote:before,blockquote:after{content:""}address{display:block;margin-bottom:20px;font-style:normal;line-height:1.428571429}code,pre{font-family:Monaco,Menlo,Consolas,"Courier New",monospace}code{padding:2px 4px;font-size:90%;color:#c7254e;white-space:nowrap;background-color:#f9f2f4;border-radius:4px}pre{display:block;padding:9.5px;margin:0 0 10px;font-size:13px;line-height:1.428571429;color:#333;word-break:break-all;word-wrap:break-word;background-color:#f5f5f5;border:1px solid #ccc;border-radius:4px}pre.prettyprint{margin-bottom:20px}pre code{padding:0;font-size:inherit;color:inherit;white-space:pre-wrap;background-color:transparent;border:0}.pre-scrollable{max-height:340px;overflow-y:scroll}.container{padding-right:15px;padding-left:15px;margin-right:auto;margin-left:auto}.container:before,.container:after{display:table;content:" "}.container:after{clear:both}.container:before,.container:after{display:table;content:" "}.container:after{clear:both}.row{margin-right:-15px;margin-left:-15px}.row:before,.row:after{display:table;content:" "}.row:after{clear:both}.row:before,.row:after{display:table;content:" "}.row:after{clear:both}.col-xs-1,.col-xs-2,.col-xs-3,.col-xs-4,.col-xs-5,.col-xs-6,.col-xs-7,.col-xs-8,.col-xs-9,.col-xs-10,.col-xs-11,.col-xs-12,.col-sm-1,.col-sm-2,.col-sm-3,.col-sm-4,.col-sm-5,.col-sm-6,.col-sm-7,.col-sm-8,.col-sm-9,.col-sm-10,.col-sm-11,.col-sm-12,.col-md-1,.col-md-2,.col-md-3,.col-md-4,.col-md-5,.col-md-6,.col-md-7,.col-md-8,.col-md-9,.col-md-10,.col-md-11,.col-md-12,.col-lg-1,.col-lg-2,.col-lg-3,.col-lg-4,.col-lg-5,.col-lg-6,.col-lg-7,.col-lg-8,.col-lg-9,.col-lg-10,.col-lg-11,.col-lg-12{position:relative;min-height:1px;padding-right:15px;padding-left:15px}.col-xs-1,.col-xs-2,.col-xs-3,.col-xs-4,.col-xs-5,.col-xs-6,.col-xs-7,.col-xs-8,.col-xs-9,.col-xs-10,.col-xs-11{float:left}.col-xs-1{width:8.333333333333332%}.col-xs-2{width:16.666666666666664%}.col-xs-3{width:25%}.col-xs-4{width:33.33333333333333%}.col-xs-5{width:41.66666666666667%}.col-xs-6{width:50%}.col-xs-7{width:58.333333333333336%}.col-xs-8{width:66.66666666666666%}.col-xs-9{width:75%}.col-xs-10{width:83.33333333333334%}.col-xs-11{width:91.66666666666666%}.col-xs-12{width:100%}@media(min-width:768px){.container{max-width:750px}.col-sm-1,.col-sm-2,.col-sm-3,.col-sm-4,.col-sm-5,.col-sm-6,.col-sm-7,.col-sm-8,.col-sm-9,.col-sm-10,.col-sm-11{float:left}.col-sm-1{width:8.333333333333332%}.col-sm-2{width:16.666666666666664%}.col-sm-3{width:25%}.col-sm-4{width:33.33333333333333%}.col-sm-5{width:41.66666666666667%}.col-sm-6{width:50%}.col-sm-7{width:58.333333333333336%}.col-sm-8{width:66.66666666666666%}.col-sm-9{width:75%}.col-sm-10{width:83.33333333333334%}.col-sm-11{width:91.66666666666666%}.col-sm-12{width:100%}.col-sm-push-1{left:8.333333333333332%}.col-sm-push-2{left:16.666666666666664%}.col-sm-push-3{left:25%}.col-sm-push-4{left:33.33333333333333%}.col-sm-push-5{left:41.66666666666667%}.col-sm-push-6{left:50%}.col-sm-push-7{left:58.333333333333336%}.col-sm-push-8{left:66.66666666666666%}.col-sm-push-9{left:75%}.col-sm-push-10{left:83.33333333333334%}.col-sm-push-11{left:91.66666666666666%}.col-sm-pull-1{right:8.333333333333332%}.col-sm-pull-2{right:16.666666666666664%}.col-sm-pull-3{right:25%}.col-sm-pull-4{right:33.33333333333333%}.col-sm-pull-5{right:41.66666666666667%}.col-sm-pull-6{right:50%}.col-sm-pull-7{right:58.333333333333336%}.col-sm-pull-8{right:66.66666666666666%}.col-sm-pull-9{right:75%}.col-sm-pull-10{right:83.33333333333334%}.col-sm-pull-11{right:91.66666666666666%}.col-sm-offset-1{margin-left:8.333333333333332%}.col-sm-offset-2{margin-left:16.666666666666664%}.col-sm-offset-3{margin-left:25%}.col-sm-offset-4{margin-left:33.33333333333333%}.col-sm-offset-5{margin-left:41.66666666666667%}.col-sm-offset-6{margin-left:50%}.col-sm-offset-7{margin-left:58.333333333333336%}.col-sm-offset-8{margin-left:66.66666666666666%}.col-sm-offset-9{margin-left:75%}.col-sm-offset-10{margin-left:83.33333333333334%}.col-sm-offset-11{margin-left:91.66666666666666%}}@media(min-width:992px){.container{max-width:970px}.col-md-1,.col-md-2,.col-md-3,.col-md-4,.col-md-5,.col-md-6,.col-md-7,.col-md-8,.col-md-9,.col-md-10,.col-md-11{float:left}.col-md-1{width:8.333333333333332%}.col-md-2{width:16.666666666666664%}.col-md-3{width:25%}.col-md-4{width:33.33333333333333%}.col-md-5{width:41.66666666666667%}.col-md-6{width:50%}.col-md-7{width:58.333333333333336%}.col-md-8{width:66.66666666666666%}.col-md-9{width:75%}.col-md-10{width:83.33333333333334%}.col-md-11{width:91.66666666666666%}.col-md-12{width:100%}.col-md-push-0{left:auto}.col-md-push-1{left:8.333333333333332%}.col-md-push-2{left:16.666666666666664%}.col-md-push-3{left:25%}.col-md-push-4{left:33.33333333333333%}.col-md-push-5{left:41.66666666666667%}.col-md-push-6{left:50%}.col-md-push-7{left:58.333333333333336%}.col-md-push-8{left:66.66666666666666%}.col-md-push-9{left:75%}.col-md-push-10{left:83.33333333333334%}.col-md-push-11{left:91.66666666666666%}.col-md-pull-0{right:auto}.col-md-pull-1{right:8.333333333333332%}.col-md-pull-2{right:16.666666666666664%}.col-md-pull-3{right:25%}.col-md-pull-4{right:33.33333333333333%}.col-md-pull-5{right:41.66666666666667%}.col-md-pull-6{right:50%}.col-md-pull-7{right:58.333333333333336%}.col-md-pull-8{right:66.66666666666666%}.col-md-pull-9{right:75%}.col-md-pull-10{right:83.33333333333334%}.col-md-pull-11{right:91.66666666666666%}.col-md-offset-0{margin-left:0}.col-md-offset-1{margin-left:8.333333333333332%}.col-md-offset-2{margin-left:16.666666666666664%}.col-md-offset-3{margin-left:25%}.col-md-offset-4{margin-left:33.33333333333333%}.col-md-offset-5{margin-left:41.66666666666667%}.col-md-offset-6{margin-left:50%}.col-md-offset-7{margin-left:58.333333333333336%}.col-md-offset-8{margin-left:66.66666666666666%}.col-md-offset-9{margin-left:75%}.col-md-offset-10{margin-left:83.33333333333334%}.col-md-offset-11{margin-left:91.66666666666666%}}@media(min-width:1200px){.container{max-width:1170px}.col-lg-1,.col-lg-2,.col-lg-3,.col-lg-4,.col-lg-5,.col-lg-6,.col-lg-7,.col-lg-8,.col-lg-9,.col-lg-10,.col-lg-11{float:left}.col-lg-1{width:8.333333333333332%}.col-lg-2{width:16.666666666666664%}.col-lg-3{width:25%}.col-lg-4{width:33.33333333333333%}.col-lg-5{width:41.66666666666667%}.col-lg-6{width:50%}.col-lg-7{width:58.333333333333336%}.col-lg-8{width:66.66666666666666%}.col-lg-9{width:75%}.col-lg-10{width:83.33333333333334%}.col-lg-11{width:91.66666666666666%}.col-lg-12{width:100%}.col-lg-push-0{left:auto}.col-lg-push-1{left:8.333333333333332%}.col-lg-push-2{left:16.666666666666664%}.col-lg-push-3{left:25%}.col-lg-push-4{left:33.33333333333333%}.col-lg-push-5{left:41.66666666666667%}.col-lg-push-6{left:50%}.col-lg-push-7{left:58.333333333333336%}.col-lg-push-8{left:66.66666666666666%}.col-lg-push-9{left:75%}.col-lg-push-10{left:83.33333333333334%}.col-lg-push-11{left:91.66666666666666%}.col-lg-pull-0{right:auto}.col-lg-pull-1{right:8.333333333333332%}.col-lg-pull-2{right:16.666666666666664%}.col-lg-pull-3{right:25%}.col-lg-pull-4{right:33.33333333333333%}.col-lg-pull-5{right:41.66666666666667%}.col-lg-pull-6{right:50%}.col-lg-pull-7{right:58.333333333333336%}.col-lg-pull-8{right:66.66666666666666%}.col-lg-pull-9{right:75%}.col-lg-pull-10{right:83.33333333333334%}.col-lg-pull-11{right:91.66666666666666%}.col-lg-offset-0{margin-left:0}.col-lg-offset-1{margin-left:8.333333333333332%}.col-lg-offset-2{margin-left:16.666666666666664%}.col-lg-offset-3{margin-left:25%}.col-lg-offset-4{margin-left:33.33333333333333%}.col-lg-offset-5{margin-left:41.66666666666667%}.col-lg-offset-6{margin-left:50%}.col-lg-offset-7{margin-left:58.333333333333336%}.col-lg-offset-8{margin-left:66.66666666666666%}.col-lg-offset-9{margin-left:75%}.col-lg-offset-10{margin-left:83.33333333333334%}.col-lg-offset-11{margin-left:91.66666666666666%}}table{max-width:100%;background-color:transparent}th{text-align:left}.table{width:100%;margin-bottom:20px}.table thead>tr>th,.table tbody>tr>th,.table tfoot>tr>th,.table thead>tr>td,.table tbody>tr>td,.table tfoot>tr>td{padding:8px;line-height:1.428571429;vertical-align:top;border-top:1px solid #ddd}.table thead>tr>th{vertical-align:bottom;border-bottom:2px solid #ddd}.table caption+thead tr:first-child th,.table colgroup+thead tr:first-child th,.table thead:first-child tr:first-child th,.table caption+thead tr:first-child td,.table colgroup+thead tr:first-child td,.table thead:first-child tr:first-child td{border-top:0}.table tbody+tbody{border-top:2px solid #ddd}.table .table{background-color:#fff}.table-condensed thead>tr>th,.table-condensed tbody>tr>th,.table-condensed tfoot>tr>th,.table-condensed thead>tr>td,.table-condensed tbody>tr>td,.table-condensed tfoot>tr>td{padding:5px}.table-bordered{border:1px solid #ddd}.table-bordered>thead>tr>th,.table-bordered>tbody>tr>th,.table-bordered>tfoot>tr>th,.table-bordered>thead>tr>td,.table-bordered>tbody>tr>td,.table-bordered>tfoot>tr>td{border:1px solid #ddd}.table-bordered>thead>tr>th,.table-bordered>thead>tr>td{border-bottom-width:2px}.table-striped>tbody>tr:nth-child(odd)>td,.table-striped>tbody>tr:nth-child(odd)>th{background-color:#f9f9f9}.table-hover>tbody>tr:hover>td,.table-hover>tbody>tr:hover>th{background-color:#f5f5f5}table col[class*="col-"]{display:table-column;float:none}table td[class*="col-"],table th[class*="col-"]{display:table-cell;float:none}.table>thead>tr>td.active,.table>tbody>tr>td.active,.table>tfoot>tr>td.active,.table>thead>tr>th.active,.table>tbody>tr>th.active,.table>tfoot>tr>th.active,.table>thead>tr.active>td,.table>tbody>tr.active>td,.table>tfoot>tr.active>td,.table>thead>tr.active>th,.table>tbody>tr.active>th,.table>tfoot>tr.active>th{background-color:#f5f5f5}.table>thead>tr>td.success,.table>tbody>tr>td.success,.table>tfoot>tr>td.success,.table>thead>tr>th.success,.table>tbody>tr>th.success,.table>tfoot>tr>th.success,.table>thead>tr.success>td,.table>tbody>tr.success>td,.table>tfoot>tr.success>td,.table>thead>tr.success>th,.table>tbody>tr.success>th,.table>tfoot>tr.success>th{background-color:#dff0d8;border-color:#d6e9c6}.table-hover>tbody>tr>td.success:hover,.table-hover>tbody>tr>th.success:hover,.table-hover>tbody>tr.success:hover>td{background-color:#d0e9c6;border-color:#c9e2b3}.table>thead>tr>td.danger,.table>tbody>tr>td.danger,.table>tfoot>tr>td.danger,.table>thead>tr>th.danger,.table>tbody>tr>th.danger,.table>tfoot>tr>th.danger,.table>thead>tr.danger>td,.table>tbody>tr.danger>td,.table>tfoot>tr.danger>td,.table>thead>tr.danger>th,.table>tbody>tr.danger>th,.table>tfoot>tr.danger>th{background-color:#f2dede;border-color:#eed3d7}.table-hover>tbody>tr>td.danger:hover,.table-hover>tbody>tr>th.danger:hover,.table-hover>tbody>tr.danger:hover>td{background-color:#ebcccc;border-color:#e6c1c7}.table>thead>tr>td.warning,.table>tbody>tr>td.warning,.table>tfoot>tr>td.warning,.table>thead>tr>th.warning,.table>tbody>tr>th.warning,.table>tfoot>tr>th.warning,.table>thead>tr.warning>td,.table>tbody>tr.warning>td,.table>tfoot>tr.warning>td,.table>thead>tr.warning>th,.table>tbody>tr.warning>th,.table>tfoot>tr.warning>th{background-color:#fcf8e3;border-color:#fbeed5}.table-hover>tbody>tr>td.warning:hover,.table-hover>tbody>tr>th.warning:hover,.table-hover>tbody>tr.warning:hover>td{background-color:#faf2cc;border-color:#f8e5be}@media(max-width:768px){.table-responsive{width:100%;margin-bottom:15px;overflow-x:scroll;overflow-y:hidden;border:1px solid #ddd}.table-responsive>.table{margin-bottom:0;background-color:#fff}.table-responsive>.table>thead>tr>th,.table-responsive>.table>tbody>tr>th,.table-responsive>.table>tfoot>tr>th,.table-responsive>.table>thead>tr>td,.table-responsive>.table>tbody>tr>td,.table-responsive>.table>tfoot>tr>td{white-space:nowrap}.table-responsive>.table-bordered{border:0}.table-responsive>.table-bordered>thead>tr>th:first-child,.table-responsive>.table-bordered>tbody>tr>th:first-child,.table-responsive>.table-bordered>tfoot>tr>th:first-child,.table-responsive>.table-bordered>thead>tr>td:first-child,.table-responsive>.table-bordered>tbody>tr>td:first-child,.table-responsive>.table-bordered>tfoot>tr>td:first-child{border-left:0}.table-responsive>.table-bordered>thead>tr>th:last-child,.table-responsive>.table-bordered>tbody>tr>th:last-child,.table-responsive>.table-bordered>tfoot>tr>th:last-child,.table-responsive>.table-bordered>thead>tr>td:last-child,.table-responsive>.table-bordered>tbody>tr>td:last-child,.table-responsive>.table-bordered>tfoot>tr>td:last-child{border-right:0}.table-responsive>.table-bordered>thead>tr:last-child>th,.table-responsive>.table-bordered>tbody>tr:last-child>th,.table-responsive>.table-bordered>tfoot>tr:last-child>th,.table-responsive>.table-bordered>thead>tr:last-child>td,.table-responsive>.table-bordered>tbody>tr:last-child>td,.table-responsive>.table-bordered>tfoot>tr:last-child>td{border-bottom:0}}fieldset{padding:0;margin:0;border:0}legend{display:block;width:100%;padding:0;margin-bottom:20px;font-size:21px;line-height:inherit;color:#333;border:0;border-bottom:1px solid #e5e5e5}label{display:inline-block;margin-bottom:5px;font-weight:bold}input[type="search"]{-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}input[type="radio"],input[type="checkbox"]{margin:4px 0 0;margin-top:1px \9;line-height:normal}input[type="file"]{display:block}select[multiple],select[size]{height:auto}select optgroup{font-family:inherit;font-size:inherit;font-style:inherit}input[type="file"]:focus,input[type="radio"]:focus,input[type="checkbox"]:focus{outline:thin dotted #333;outline:5px auto -webkit-focus-ring-color;outline-offset:-2px}input[type="number"]::-webkit-outer-spin-button,input[type="number"]::-webkit-inner-spin-button{height:auto}.form-control:-moz-placeholder{color:#999}.form-control::-moz-placeholder{color:#999}.form-control:-ms-input-placeholder{color:#999}.form-control::-webkit-input-placeholder{color:#999}.form-control{display:block;width:100%;height:34px;padding:6px 12px;font-size:14px;line-height:1.428571429;color:#555;vertical-align:middle;background-color:#fff;border:1px solid #ccc;border-radius:4px;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);-webkit-transition:border-color ease-in-out .15s,box-shadow ease-in-out .15s;transition:border-color ease-in-out .15s,box-shadow ease-in-out .15s}.form-control:focus{border-color:#66afe9;outline:0;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 8px rgba(102,175,233,0.6);box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 8px rgba(102,175,233,0.6)}.form-control[disabled],.form-control[readonly],fieldset[disabled] .form-control{cursor:not-allowed;background-color:#eee}textarea.form-control{height:auto}.form-group{margin-bottom:15px}.radio,.checkbox{display:block;min-height:20px;padding-left:20px;margin-top:10px;margin-bottom:10px;vertical-align:middle}.radio label,.checkbox label{display:inline;margin-bottom:0;font-weight:normal;cursor:pointer}.radio input[type="radio"],.radio-inline input[type="radio"],.checkbox input[type="checkbox"],.checkbox-inline input[type="checkbox"]{float:left;margin-left:-20px}.radio+.radio,.checkbox+.checkbox{margin-top:-5px}.radio-inline,.checkbox-inline{display:inline-block;padding-left:20px;margin-bottom:0;font-weight:normal;vertical-align:middle;cursor:pointer}.radio-inline+.radio-inline,.checkbox-inline+.checkbox-inline{margin-top:0;margin-left:10px}input[type="radio"][disabled],input[type="checkbox"][disabled],.radio[disabled],.radio-inline[disabled],.checkbox[disabled],.checkbox-inline[disabled],fieldset[disabled] input[type="radio"],fieldset[disabled] input[type="checkbox"],fieldset[disabled] .radio,fieldset[disabled] .radio-inline,fieldset[disabled] .checkbox,fieldset[disabled] .checkbox-inline{cursor:not-allowed}.input-sm{height:30px;padding:5px 10px;font-size:12px;line-height:1.5;border-radius:3px}select.input-sm{height:30px;line-height:30px}textarea.input-sm{height:auto}.input-lg{height:45px;padding:10px 16px;font-size:18px;line-height:1.33;border-radius:6px}select.input-lg{height:45px;line-height:45px}textarea.input-lg{height:auto}.has-warning .help-block,.has-warning .control-label{color:#c09853}.has-warning .form-control{border-color:#c09853;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);box-shadow:inset 0 1px 1px rgba(0,0,0,0.075)}.has-warning .form-control:focus{border-color:#a47e3c;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #dbc59e;box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #dbc59e}.has-warning .input-group-addon{color:#c09853;background-color:#fcf8e3;border-color:#c09853}.has-error .help-block,.has-error .control-label{color:#b94a48}.has-error .form-control{border-color:#b94a48;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);box-shadow:inset 0 1px 1px rgba(0,0,0,0.075)}.has-error .form-control:focus{border-color:#953b39;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #d59392;box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #d59392}.has-error .input-group-addon{color:#b94a48;background-color:#f2dede;border-color:#b94a48}.has-success .help-block,.has-success .control-label{color:#468847}.has-success .form-control{border-color:#468847;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);box-shadow:inset 0 1px 1px rgba(0,0,0,0.075)}.has-success .form-control:focus{border-color:#356635;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #7aba7b;box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #7aba7b}.has-success .input-group-addon{color:#468847;background-color:#dff0d8;border-color:#468847}.form-control-static{padding-top:7px;margin-bottom:0}.help-block{display:block;margin-top:5px;margin-bottom:10px;color:#737373}@media(min-width:768px){.form-inline .form-group{display:inline-block;margin-bottom:0;vertical-align:middle}.form-inline .form-control{display:inline-block}.form-inline .radio,.form-inline .checkbox{display:inline-block;padding-left:0;margin-top:0;margin-bottom:0}.form-inline .radio input[type="radio"],.form-inline .checkbox input[type="checkbox"]{float:none;margin-left:0}}.form-horizontal .control-label,.form-horizontal .radio,.form-horizontal .checkbox,.form-horizontal .radio-inline,.form-horizontal .checkbox-inline{padding-top:7px;margin-top:0;margin-bottom:0}.form-horizontal .form-group{margin-right:-15px;margin-left:-15px}.form-horizontal .form-group:before,.form-horizontal .form-group:after{display:table;content:" "}.form-horizontal .form-group:after{clear:both}.form-horizontal .form-group:before,.form-horizontal .form-group:after{display:table;content:" "}.form-horizontal .form-group:after{clear:both}@media(min-width:768px){.form-horizontal .control-label{text-align:right}}.btn{display:inline-block;padding:6px 12px;margin-bottom:0;font-size:14px;font-weight:normal;line-height:1.428571429;text-align:center;white-space:nowrap;vertical-align:middle;cursor:pointer;border:1px solid transparent;border-radius:4px;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;-o-user-select:none;user-select:none}.btn:focus{outline:thin dotted #333;outline:5px auto -webkit-focus-ring-color;outline-offset:-2px}.btn:hover,.btn:focus{color:#333;text-decoration:none}.btn:active,.btn.active{background-image:none;outline:0;-webkit-box-shadow:inset 0 3px 5px rgba(0,0,0,0.125);box-shadow:inset 0 3px 5px rgba(0,0,0,0.125)}.btn.disabled,.btn[disabled],fieldset[disabled] .btn{pointer-events:none;cursor:not-allowed;opacity:.65;filter:alpha(opacity=65);-webkit-box-shadow:none;box-shadow:none}.btn-default{color:#333;background-color:#fff;border-color:#ccc}.btn-default:hover,.btn-default:focus,.btn-default:active,.btn-default.active,.open .dropdown-toggle.btn-default{color:#333;background-color:#ebebeb;border-color:#adadad}.btn-default:active,.btn-default.active,.open .dropdown-toggle.btn-default{background-image:none}.btn-default.disabled,.btn-default[disabled],fieldset[disabled] .btn-default,.btn-default.disabled:hover,.btn-default[disabled]:hover,fieldset[disabled] .btn-default:hover,.btn-default.disabled:focus,.btn-default[disabled]:focus,fieldset[disabled] .btn-default:focus,.btn-default.disabled:active,.btn-default[disabled]:active,fieldset[disabled] .btn-default:active,.btn-default.disabled.active,.btn-default[disabled].active,fieldset[disabled] .btn-default.active{background-color:#fff;border-color:#ccc}.btn-primary{color:#fff;background-color:#428bca;border-color:#357ebd}.btn-primary:hover,.btn-primary:focus,.btn-primary:active,.btn-primary.active,.open .dropdown-toggle.btn-primary{color:#fff;background-color:#3276b1;border-color:#285e8e}.btn-primary:active,.btn-primary.active,.open .dropdown-toggle.btn-primary{background-image:none}.btn-primary.disabled,.btn-primary[disabled],fieldset[disabled] .btn-primary,.btn-primary.disabled:hover,.btn-primary[disabled]:hover,fieldset[disabled] .btn-primary:hover,.btn-primary.disabled:focus,.btn-primary[disabled]:focus,fieldset[disabled] .btn-primary:focus,.btn-primary.disabled:active,.btn-primary[disabled]:active,fieldset[disabled] .btn-primary:active,.btn-primary.disabled.active,.btn-primary[disabled].active,fieldset[disabled] .btn-primary.active{background-color:#428bca;border-color:#357ebd}.btn-warning{color:#fff;background-color:#f0ad4e;border-color:#eea236}.btn-warning:hover,.btn-warning:focus,.btn-warning:active,.btn-warning.active,.open .dropdown-toggle.btn-warning{color:#fff;background-color:#ed9c28;border-color:#d58512}.btn-warning:active,.btn-warning.active,.open .dropdown-toggle.btn-warning{background-image:none}.btn-warning.disabled,.btn-warning[disabled],fieldset[disabled] .btn-warning,.btn-warning.disabled:hover,.btn-warning[disabled]:hover,fieldset[disabled] .btn-warning:hover,.btn-warning.disabled:focus,.btn-warning[disabled]:focus,fieldset[disabled] .btn-warning:focus,.btn-warning.disabled:active,.btn-warning[disabled]:active,fieldset[disabled] .btn-warning:active,.btn-warning.disabled.active,.btn-warning[disabled].active,fieldset[disabled] .btn-warning.active{background-color:#f0ad4e;border-color:#eea236}.btn-danger{color:#fff;background-color:#d9534f;border-color:#d43f3a}.btn-danger:hover,.btn-danger:focus,.btn-danger:active,.btn-danger.active,.open .dropdown-toggle.btn-danger{color:#fff;background-color:#d2322d;border-color:#ac2925}.btn-danger:active,.btn-danger.active,.open .dropdown-toggle.btn-danger{background-image:none}.btn-danger.disabled,.btn-danger[disabled],fieldset[disabled] .btn-danger,.btn-danger.disabled:hover,.btn-danger[disabled]:hover,fieldset[disabled] .btn-danger:hover,.btn-danger.disabled:focus,.btn-danger[disabled]:focus,fieldset[disabled] .btn-danger:focus,.btn-danger.disabled:active,.btn-danger[disabled]:active,fieldset[disabled] .btn-danger:active,.btn-danger.disabled.active,.btn-danger[disabled].active,fieldset[disabled] .btn-danger.active{background-color:#d9534f;border-color:#d43f3a}.btn-success{color:#fff;background-color:#5cb85c;border-color:#4cae4c}.btn-success:hover,.btn-success:focus,.btn-success:active,.btn-success.active,.open .dropdown-toggle.btn-success{color:#fff;background-color:#47a447;border-color:#398439}.btn-success:active,.btn-success.active,.open .dropdown-toggle.btn-success{background-image:none}.btn-success.disabled,.btn-success[disabled],fieldset[disabled] .btn-success,.btn-success.disabled:hover,.btn-success[disabled]:hover,fieldset[disabled] .btn-success:hover,.btn-success.disabled:focus,.btn-success[disabled]:focus,fieldset[disabled] .btn-success:focus,.btn-success.disabled:active,.btn-success[disabled]:active,fieldset[disabled] .btn-success:active,.btn-success.disabled.active,.btn-success[disabled].active,fieldset[disabled] .btn-success.active{background-color:#5cb85c;border-color:#4cae4c}.btn-info{color:#fff;background-color:#5bc0de;border-color:#46b8da}.btn-info:hover,.btn-info:focus,.btn-info:active,.btn-info.active,.open .dropdown-toggle.btn-info{color:#fff;background-color:#39b3d7;border-color:#269abc}.btn-info:active,.btn-info.active,.open .dropdown-toggle.btn-info{background-image:none}.btn-info.disabled,.btn-info[disabled],fieldset[disabled] .btn-info,.btn-info.disabled:hover,.btn-info[disabled]:hover,fieldset[disabled] .btn-info:hover,.btn-info.disabled:focus,.btn-info[disabled]:focus,fieldset[disabled] .btn-info:focus,.btn-info.disabled:active,.btn-info[disabled]:active,fieldset[disabled] .btn-info:active,.btn-info.disabled.active,.btn-info[disabled].active,fieldset[disabled] .btn-info.active{background-color:#5bc0de;border-color:#46b8da}.btn-link{font-weight:normal;color:#428bca;cursor:pointer;border-radius:0}.btn-link,.btn-link:active,.btn-link[disabled],fieldset[disabled] .btn-link{background-color:transparent;-webkit-box-shadow:none;box-shadow:none}.btn-link,.btn-link:hover,.btn-link:focus,.btn-link:active{border-color:transparent}.btn-link:hover,.btn-link:focus{color:#2a6496;text-decoration:underline;background-color:transparent}.btn-link[disabled]:hover,fieldset[disabled] .btn-link:hover,.btn-link[disabled]:focus,fieldset[disabled] .btn-link:focus{color:#999;text-decoration:none}.btn-lg{padding:10px 16px;font-size:18px;line-height:1.33;border-radius:6px}.btn-sm,.btn-xs{padding:5px 10px;font-size:12px;line-height:1.5;border-radius:3px}.btn-xs{padding:1px 5px}.btn-block{display:block;width:100%;padding-right:0;padding-left:0}.btn-block+.btn-block{margin-top:5px}input[type="submit"].btn-block,input[type="reset"].btn-block,input[type="button"].btn-block{width:100%}.fade{opacity:0;-webkit-transition:opacity .15s linear;transition:opacity .15s linear}.fade.in{opacity:1}.collapse{display:none}.collapse.in{display:block}.collapsing{position:relative;height:0;overflow:hidden;-webkit-transition:height .35s ease;transition:height .35s ease}@font-face{font-family:'Glyphicons Halflings';src:url('?RES=resources/fonts/glyphicons-halflings-regular.eot');src:url('?RES=resources/fonts/glyphicons-halflings-regular.eot?#iefix') format('embedded-opentype'),url('?RES=resources/fonts/glyphicons-halflings-regular.woff') format('woff'),url('?RES=resources/fonts/glyphicons-halflings-regular.ttf') format('truetype'),url('?RES=resources/fonts/glyphicons-halflings-regular.svg#glyphicons-halflingsregular') format('svg')}.glyphicon{position:relative;top:1px;display:inline-block;font-family:'Glyphicons Halflings';-webkit-font-smoothing:antialiased;font-style:normal;font-weight:normal;line-height:1}.glyphicon-asterisk:before{content:"\2a"}.glyphicon-plus:before{content:"\2b"}.glyphicon-euro:before{content:"\20ac"}.glyphicon-minus:before{content:"\2212"}.glyphicon-cloud:before{content:"\2601"}.glyphicon-envelope:before{content:"\2709"}.glyphicon-pencil:before{content:"\270f"}.glyphicon-glass:before{content:"\e001"}.glyphicon-music:before{content:"\e002"}.glyphicon-search:before{content:"\e003"}.glyphicon-heart:before{content:"\e005"}.glyphicon-star:before{content:"\e006"}.glyphicon-star-empty:before{content:"\e007"}.glyphicon-user:before{content:"\e008"}.glyphicon-film:before{content:"\e009"}.glyphicon-th-large:before{content:"\e010"}.glyphicon-th:before{content:"\e011"}.glyphicon-th-list:before{content:"\e012"}.glyphicon-ok:before{content:"\e013"}.glyphicon-remove:before{content:"\e014"}.glyphicon-zoom-in:before{content:"\e015"}.glyphicon-zoom-out:before{content:"\e016"}.glyphicon-off:before{content:"\e017"}.glyphicon-signal:before{content:"\e018"}.glyphicon-cog:before{content:"\e019"}.glyphicon-trash:before{content:"\e020"}.glyphicon-home:before{content:"\e021"}.glyphicon-file:before{content:"\e022"}.glyphicon-time:before{content:"\e023"}.glyphicon-road:before{content:"\e024"}.glyphicon-download-alt:before{content:"\e025"}.glyphicon-download:before{content:"\e026"}.glyphicon-upload:before{content:"\e027"}.glyphicon-inbox:before{content:"\e028"}.glyphicon-play-circle:before{content:"\e029"}.glyphicon-repeat:before{content:"\e030"}.glyphicon-refresh:before{content:"\e031"}.glyphicon-list-alt:before{content:"\e032"}.glyphicon-flag:before{content:"\e034"}.glyphicon-headphones:before{content:"\e035"}.glyphicon-volume-off:before{content:"\e036"}.glyphicon-volume-down:before{content:"\e037"}.glyphicon-volume-up:before{content:"\e038"}.glyphicon-qrcode:before{content:"\e039"}.glyphicon-barcode:before{content:"\e040"}.glyphicon-tag:before{content:"\e041"}.glyphicon-tags:before{content:"\e042"}.glyphicon-book:before{content:"\e043"}.glyphicon-print:before{content:"\e045"}.glyphicon-font:before{content:"\e047"}.glyphicon-bold:before{content:"\e048"}.glyphicon-italic:before{content:"\e049"}.glyphicon-text-height:before{content:"\e050"}.glyphicon-text-width:before{content:"\e051"}.glyphicon-align-left:before{content:"\e052"}.glyphicon-align-center:before{content:"\e053"}.glyphicon-align-right:before{content:"\e054"}.glyphicon-align-justify:before{content:"\e055"}.glyphicon-list:before{content:"\e056"}.glyphicon-indent-left:before{content:"\e057"}.glyphicon-indent-right:before{content:"\e058"}.glyphicon-facetime-video:before{content:"\e059"}.glyphicon-picture:before{content:"\e060"}.glyphicon-map-marker:before{content:"\e062"}.glyphicon-adjust:before{content:"\e063"}.glyphicon-tint:before{content:"\e064"}.glyphicon-edit:before{content:"\e065"}.glyphicon-share:before{content:"\e066"}.glyphicon-check:before{content:"\e067"}.glyphicon-move:before{content:"\e068"}.glyphicon-step-backward:before{content:"\e069"}.glyphicon-fast-backward:before{content:"\e070"}.glyphicon-backward:before{content:"\e071"}.glyphicon-play:before{content:"\e072"}.glyphicon-pause:before{content:"\e073"}.glyphicon-stop:before{content:"\e074"}.glyphicon-forward:before{content:"\e075"}.glyphicon-fast-forward:before{content:"\e076"}.glyphicon-step-forward:before{content:"\e077"}.glyphicon-eject:before{content:"\e078"}.glyphicon-chevron-left:before{content:"\e079"}.glyphicon-chevron-right:before{content:"\e080"}.glyphicon-plus-sign:before{content:"\e081"}.glyphicon-minus-sign:before{content:"\e082"}.glyphicon-remove-sign:before{content:"\e083"}.glyphicon-ok-sign:before{content:"\e084"}.glyphicon-question-sign:before{content:"\e085"}.glyphicon-info-sign:before{content:"\e086"}.glyphicon-screenshot:before{content:"\e087"}.glyphicon-remove-circle:before{content:"\e088"}.glyphicon-ok-circle:before{content:"\e089"}.glyphicon-ban-circle:before{content:"\e090"}.glyphicon-arrow-left:before{content:"\e091"}.glyphicon-arrow-right:before{content:"\e092"}.glyphicon-arrow-up:before{content:"\e093"}.glyphicon-arrow-down:before{content:"\e094"}.glyphicon-share-alt:before{content:"\e095"}.glyphicon-resize-full:before{content:"\e096"}.glyphicon-resize-small:before{content:"\e097"}.glyphicon-exclamation-sign:before{content:"\e101"}.glyphicon-gift:before{content:"\e102"}.glyphicon-leaf:before{content:"\e103"}.glyphicon-eye-open:before{content:"\e105"}.glyphicon-eye-close:before{content:"\e106"}.glyphicon-warning-sign:before{content:"\e107"}.glyphicon-plane:before{content:"\e108"}.glyphicon-random:before{content:"\e110"}.glyphicon-comment:before{content:"\e111"}.glyphicon-magnet:before{content:"\e112"}.glyphicon-chevron-up:before{content:"\e113"}.glyphicon-chevron-down:before{content:"\e114"}.glyphicon-retweet:before{content:"\e115"}.glyphicon-shopping-cart:before{content:"\e116"}.glyphicon-folder-close:before{content:"\e117"}.glyphicon-folder-open:before{content:"\e118"}.glyphicon-resize-vertical:before{content:"\e119"}.glyphicon-resize-horizontal:before{content:"\e120"}.glyphicon-hdd:before{content:"\e121"}.glyphicon-bullhorn:before{content:"\e122"}.glyphicon-certificate:before{content:"\e124"}.glyphicon-thumbs-up:before{content:"\e125"}.glyphicon-thumbs-down:before{content:"\e126"}.glyphicon-hand-right:before{content:"\e127"}.glyphicon-hand-left:before{content:"\e128"}.glyphicon-hand-up:before{content:"\e129"}.glyphicon-hand-down:before{content:"\e130"}.glyphicon-circle-arrow-right:before{content:"\e131"}.glyphicon-circle-arrow-left:before{content:"\e132"}.glyphicon-circle-arrow-up:before{content:"\e133"}.glyphicon-circle-arrow-down:before{content:"\e134"}.glyphicon-globe:before{content:"\e135"}.glyphicon-tasks:before{content:"\e137"}.glyphicon-filter:before{content:"\e138"}.glyphicon-fullscreen:before{content:"\e140"}.glyphicon-dashboard:before{content:"\e141"}.glyphicon-heart-empty:before{content:"\e143"}.glyphicon-link:before{content:"\e144"}.glyphicon-phone:before{content:"\e145"}.glyphicon-usd:before{content:"\e148"}.glyphicon-gbp:before{content:"\e149"}.glyphicon-sort:before{content:"\e150"}.glyphicon-sort-by-alphabet:before{content:"\e151"}.glyphicon-sort-by-alphabet-alt:before{content:"\e152"}.glyphicon-sort-by-order:before{content:"\e153"}.glyphicon-sort-by-order-alt:before{content:"\e154"}.glyphicon-sort-by-attributes:before{content:"\e155"}.glyphicon-sort-by-attributes-alt:before{content:"\e156"}.glyphicon-unchecked:before{content:"\e157"}.glyphicon-expand:before{content:"\e158"}.glyphicon-collapse-down:before{content:"\e159"}.glyphicon-collapse-up:before{content:"\e160"}.glyphicon-log-in:before{content:"\e161"}.glyphicon-flash:before{content:"\e162"}.glyphicon-log-out:before{content:"\e163"}.glyphicon-new-window:before{content:"\e164"}.glyphicon-record:before{content:"\e165"}.glyphicon-save:before{content:"\e166"}.glyphicon-open:before{content:"\e167"}.glyphicon-saved:before{content:"\e168"}.glyphicon-import:before{content:"\e169"}.glyphicon-export:before{content:"\e170"}.glyphicon-send:before{content:"\e171"}.glyphicon-floppy-disk:before{content:"\e172"}.glyphicon-floppy-saved:before{content:"\e173"}.glyphicon-floppy-remove:before{content:"\e174"}.glyphicon-floppy-save:before{content:"\e175"}.glyphicon-floppy-open:before{content:"\e176"}.glyphicon-credit-card:before{content:"\e177"}.glyphicon-transfer:before{content:"\e178"}.glyphicon-cutlery:before{content:"\e179"}.glyphicon-header:before{content:"\e180"}.glyphicon-compressed:before{content:"\e181"}.glyphicon-earphone:before{content:"\e182"}.glyphicon-phone-alt:before{content:"\e183"}.glyphicon-tower:before{content:"\e184"}.glyphicon-stats:before{content:"\e185"}.glyphicon-sd-video:before{content:"\e186"}.glyphicon-hd-video:before{content:"\e187"}.glyphicon-subtitles:before{content:"\e188"}.glyphicon-sound-stereo:before{content:"\e189"}.glyphicon-sound-dolby:before{content:"\e190"}.glyphicon-sound-5-1:before{content:"\e191"}.glyphicon-sound-6-1:before{content:"\e192"}.glyphicon-sound-7-1:before{content:"\e193"}.glyphicon-copyright-mark:before{content:"\e194"}.glyphicon-registration-mark:before{content:"\e195"}.glyphicon-cloud-download:before{content:"\e197"}.glyphicon-cloud-upload:before{content:"\e198"}.glyphicon-tree-conifer:before{content:"\e199"}.glyphicon-tree-deciduous:before{content:"\e200"}.glyphicon-briefcase:before{content:"\1f4bc"}.glyphicon-calendar:before{content:"\1f4c5"}.glyphicon-pushpin:before{content:"\1f4cc"}.glyphicon-paperclip:before{content:"\1f4ce"}.glyphicon-camera:before{content:"\1f4f7"}.glyphicon-lock:before{content:"\1f512"}.glyphicon-bell:before{content:"\1f514"}.glyphicon-bookmark:before{content:"\1f516"}.glyphicon-fire:before{content:"\1f525"}.glyphicon-wrench:before{content:"\1f527"}.caret{display:inline-block;width:0;height:0;margin-left:2px;vertical-align:middle;border-top:4px solid #000;border-right:4px solid transparent;border-bottom:0 dotted;border-left:4px solid transparent;content:""}.dropdown{position:relative}.dropdown-toggle:focus{outline:0}.dropdown-menu{position:absolute;top:100%;left:0;z-index:1000;display:none;float:left;min-width:160px;padding:5px 0;margin:2px 0 0;font-size:14px;list-style:none;background-color:#fff;border:1px solid #ccc;border:1px solid rgba(0,0,0,0.15);border-radius:4px;-webkit-box-shadow:0 6px 12px rgba(0,0,0,0.175);box-shadow:0 6px 12px rgba(0,0,0,0.175);background-clip:padding-box}.dropdown-menu.pull-right{right:0;left:auto}.dropdown-menu .divider{height:1px;margin:9px 0;overflow:hidden;background-color:#e5e5e5}.dropdown-menu>li>a{display:block;padding:3px 20px;clear:both;font-weight:normal;line-height:1.428571429;color:#333;white-space:nowrap}.dropdown-menu>li>a:hover,.dropdown-menu>li>a:focus{color:#fff;text-decoration:none;background-color:#428bca}.dropdown-menu>.active>a,.dropdown-menu>.active>a:hover,.dropdown-menu>.active>a:focus{color:#fff;text-decoration:none;background-color:#428bca;outline:0}.dropdown-menu>.disabled>a,.dropdown-menu>.disabled>a:hover,.dropdown-menu>.disabled>a:focus{color:#999}.dropdown-menu>.disabled>a:hover,.dropdown-menu>.disabled>a:focus{text-decoration:none;cursor:not-allowed;background-color:transparent;background-image:none;filter:progid:DXImageTransform.Microsoft.gradient(enabled=false)}.open>.dropdown-menu{display:block}.open>a{outline:0}.dropdown-header{display:block;padding:3px 20px;font-size:12px;line-height:1.428571429;color:#999}.dropdown-backdrop{position:fixed;top:0;right:0;bottom:0;left:0;z-index:990}.pull-right>.dropdown-menu{right:0;left:auto}.dropup .caret,.navbar-fixed-bottom .dropdown .caret{border-top:0 dotted;border-bottom:4px solid #000;content:""}.dropup .dropdown-menu,.navbar-fixed-bottom .dropdown .dropdown-menu{top:auto;bottom:100%;margin-bottom:1px}@media(min-width:768px){.navbar-right .dropdown-menu{right:0;left:auto}}.btn-default .caret{border-top-color:#333}.btn-primary .caret,.btn-success .caret,.btn-warning .caret,.btn-danger .caret,.btn-info .caret{border-top-color:#fff}.dropup .btn-default .caret{border-bottom-color:#333}.dropup .btn-primary .caret,.dropup .btn-success .caret,.dropup .btn-warning .caret,.dropup .btn-danger .caret,.dropup .btn-info .caret{border-bottom-color:#fff}.btn-group,.btn-group-vertical{position:relative;display:inline-block;vertical-align:middle}.btn-group>.btn,.btn-group-vertical>.btn{position:relative;float:left}.btn-group>.btn:hover,.btn-group-vertical>.btn:hover,.btn-group>.btn:focus,.btn-group-vertical>.btn:focus,.btn-group>.btn:active,.btn-group-vertical>.btn:active,.btn-group>.btn.active,.btn-group-vertical>.btn.active{z-index:2}.btn-group>.btn:focus,.btn-group-vertical>.btn:focus{outline:0}.btn-group .btn+.btn,.btn-group .btn+.btn-group,.btn-group .btn-group+.btn,.btn-group .btn-group+.btn-group{margin-left:-1px}.btn-toolbar:before,.btn-toolbar:after{display:table;content:" "}.btn-toolbar:after{clear:both}.btn-toolbar:before,.btn-toolbar:after{display:table;content:" "}.btn-toolbar:after{clear:both}.btn-toolbar .btn-group{float:left}.btn-toolbar>.btn+.btn,.btn-toolbar>.btn-group+.btn,.btn-toolbar>.btn+.btn-group,.btn-toolbar>.btn-group+.btn-group{margin-left:5px}.btn-group>.btn:not(:first-child):not(:last-child):not(.dropdown-toggle){border-radius:0}.btn-group>.btn:first-child{margin-left:0}.btn-group>.btn:first-child:not(:last-child):not(.dropdown-toggle){border-top-right-radius:0;border-bottom-right-radius:0}.btn-group>.btn:last-child:not(:first-child),.btn-group>.dropdown-toggle:not(:first-child){border-bottom-left-radius:0;border-top-left-radius:0}.btn-group>.btn-group{float:left}.btn-group>.btn-group:not(:first-child):not(:last-child)>.btn{border-radius:0}.btn-group>.btn-group:first-child>.btn:last-child,.btn-group>.btn-group:first-child>.dropdown-toggle{border-top-right-radius:0;border-bottom-right-radius:0}.btn-group>.btn-group:last-child>.btn:first-child{border-bottom-left-radius:0;border-top-left-radius:0}.btn-group .dropdown-toggle:active,.btn-group.open .dropdown-toggle{outline:0}.btn-group-xs>.btn{padding:5px 10px;padding:1px 5px;font-size:12px;line-height:1.5;border-radius:3px}.btn-group-sm>.btn{padding:5px 10px;font-size:12px;line-height:1.5;border-radius:3px}.btn-group-lg>.btn{padding:10px 16px;font-size:18px;line-height:1.33;border-radius:6px}.btn-group>.btn+.dropdown-toggle{padding-right:8px;padding-left:8px}.btn-group>.btn-lg+.dropdown-toggle{padding-right:12px;padding-left:12px}.btn-group.open .dropdown-toggle{-webkit-box-shadow:inset 0 3px 5px rgba(0,0,0,0.125);box-shadow:inset 0 3px 5px rgba(0,0,0,0.125)}.btn .caret{margin-left:0}.btn-lg .caret{border-width:5px 5px 0;border-bottom-width:0}.dropup .btn-lg .caret{border-width:0 5px 5px}.btn-group-vertical>.btn,.btn-group-vertical>.btn-group{display:block;float:none;width:100%;max-width:100%}.btn-group-vertical>.btn-group:before,.btn-group-vertical>.btn-group:after{display:table;content:" "}.btn-group-vertical>.btn-group:after{clear:both}.btn-group-vertical>.btn-group:before,.btn-group-vertical>.btn-group:after{display:table;content:" "}.btn-group-vertical>.btn-group:after{clear:both}.btn-group-vertical>.btn-group>.btn{float:none}.btn-group-vertical>.btn+.btn,.btn-group-vertical>.btn+.btn-group,.btn-group-vertical>.btn-group+.btn,.btn-group-vertical>.btn-group+.btn-group{margin-top:-1px;margin-left:0}.btn-group-vertical>.btn:not(:first-child):not(:last-child){border-radius:0}.btn-group-vertical>.btn:first-child:not(:last-child){border-top-right-radius:4px;border-bottom-right-radius:0;border-bottom-left-radius:0}.btn-group-vertical>.btn:last-child:not(:first-child){border-top-right-radius:0;border-bottom-left-radius:4px;border-top-left-radius:0}.btn-group-vertical>.btn-group:not(:first-child):not(:last-child)>.btn{border-radius:0}.btn-group-vertical>.btn-group:first-child>.btn:last-child,.btn-group-vertical>.btn-group:first-child>.dropdown-toggle{border-bottom-right-radius:0;border-bottom-left-radius:0}.btn-group-vertical>.btn-group:last-child>.btn:first-child{border-top-right-radius:0;border-top-left-radius:0}.btn-group-justified{display:table;width:100%;border-collapse:separate;table-layout:fixed}.btn-group-justified .btn{display:table-cell;float:none;width:1%}[data-toggle="buttons"]>.btn>input[type="radio"],[data-toggle="buttons"]>.btn>input[type="checkbox"]{display:none}.input-group{position:relative;display:table;border-collapse:separate}.input-group.col{float:none;padding-right:0;padding-left:0}.input-group .form-control{width:100%;margin-bottom:0}.input-group-lg>.form-control,.input-group-lg>.input-group-addon,.input-group-lg>.input-group-btn>.btn{height:45px;padding:10px 16px;font-size:18px;line-height:1.33;border-radius:6px}select.input-group-lg>.form-control,select.input-group-lg>.input-group-addon,select.input-group-lg>.input-group-btn>.btn{height:45px;line-height:45px}textarea.input-group-lg>.form-control,textarea.input-group-lg>.input-group-addon,textarea.input-group-lg>.input-group-btn>.btn{height:auto}.input-group-sm>.form-control,.input-group-sm>.input-group-addon,.input-group-sm>.input-group-btn>.btn{height:30px;padding:5px 10px;font-size:12px;line-height:1.5;border-radius:3px}select.input-group-sm>.form-control,select.input-group-sm>.input-group-addon,select.input-group-sm>.input-group-btn>.btn{height:30px;line-height:30px}textarea.input-group-sm>.form-control,textarea.input-group-sm>.input-group-addon,textarea.input-group-sm>.input-group-btn>.btn{height:auto}.input-group-addon,.input-group-btn,.input-group .form-control{display:table-cell}.input-group-addon:not(:first-child):not(:last-child),.input-group-btn:not(:first-child):not(:last-child),.input-group .form-control:not(:first-child):not(:last-child){border-radius:0}.input-group-addon,.input-group-btn{width:1%;white-space:nowrap;vertical-align:middle}.input-group-addon{padding:6px 12px;font-size:14px;font-weight:normal;line-height:1;text-align:center;background-color:#eee;border:1px solid #ccc;border-radius:4px}.input-group-addon.input-sm{padding:5px 10px;font-size:12px;border-radius:3px}.input-group-addon.input-lg{padding:10px 16px;font-size:18px;border-radius:6px}.input-group-addon input[type="radio"],.input-group-addon input[type="checkbox"]{margin-top:0}.input-group .form-control:first-child,.input-group-addon:first-child,.input-group-btn:first-child>.btn,.input-group-btn:first-child>.dropdown-toggle,.input-group-btn:last-child>.btn:not(:last-child):not(.dropdown-toggle){border-top-right-radius:0;border-bottom-right-radius:0}.input-group-addon:first-child{border-right:0}.input-group .form-control:last-child,.input-group-addon:last-child,.input-group-btn:last-child>.btn,.input-group-btn:last-child>.dropdown-toggle,.input-group-btn:first-child>.btn:not(:first-child){border-bottom-left-radius:0;border-top-left-radius:0}.input-group-addon:last-child{border-left:0}.input-group-btn{position:relative;white-space:nowrap}.input-group-btn>.btn{position:relative}.input-group-btn>.btn+.btn{margin-left:-4px}.input-group-btn>.btn:hover,.input-group-btn>.btn:active{z-index:2}.nav{padding-left:0;margin-bottom:0;list-style:none}.nav:before,.nav:after{display:table;content:" "}.nav:after{clear:both}.nav:before,.nav:after{display:table;content:" "}.nav:after{clear:both}.nav>li{position:relative;display:block}.nav>li>a{position:relative;display:block;padding:10px 15px}.nav>li>a:hover,.nav>li>a:focus{text-decoration:none;background-color:#eee}.nav>li.disabled>a{color:#999}.nav>li.disabled>a:hover,.nav>li.disabled>a:focus{color:#999;text-decoration:none;cursor:not-allowed;background-color:transparent}.nav .open>a,.nav .open>a:hover,.nav .open>a:focus{background-color:#eee;border-color:#428bca}.nav .nav-divider{height:1px;margin:9px 0;overflow:hidden;background-color:#e5e5e5}.nav>li>a>img{max-width:none}.nav-tabs{border-bottom:1px solid #ddd}.nav-tabs>li{float:left;margin-bottom:-1px}.nav-tabs>li>a{margin-right:2px;line-height:1.428571429;border:1px solid transparent;border-radius:4px 4px 0 0}.nav-tabs>li>a:hover{border-color:#eee #eee #ddd}.nav-tabs>li.active>a,.nav-tabs>li.active>a:hover,.nav-tabs>li.active>a:focus{color:#555;cursor:default;background-color:#fff;border:1px solid #ddd;border-bottom-color:transparent}.nav-tabs.nav-justified{width:100%;border-bottom:0}.nav-tabs.nav-justified>li{float:none}.nav-tabs.nav-justified>li>a{text-align:center}@media(min-width:768px){.nav-tabs.nav-justified>li{display:table-cell;width:1%}}.nav-tabs.nav-justified>li>a{margin-right:0;border-bottom:1px solid #ddd}.nav-tabs.nav-justified>.active>a{border-bottom-color:#fff}.nav-pills>li{float:left}.nav-pills>li>a{border-radius:5px}.nav-pills>li+li{margin-left:2px}.nav-pills>li.active>a,.nav-pills>li.active>a:hover,.nav-pills>li.active>a:focus{color:#fff;background-color:#428bca}.nav-stacked>li{float:none}.nav-stacked>li+li{margin-top:2px;margin-left:0}.nav-justified{width:100%}.nav-justified>li{float:none}.nav-justified>li>a{text-align:center}@media(min-width:768px){.nav-justified>li{display:table-cell;width:1%}}.nav-tabs-justified{border-bottom:0}.nav-tabs-justified>li>a{margin-right:0;border-bottom:1px solid #ddd}.nav-tabs-justified>.active>a{border-bottom-color:#fff}.tabbable:before,.tabbable:after{display:table;content:" "}.tabbable:after{clear:both}.tabbable:before,.tabbable:after{display:table;content:" "}.tabbable:after{clear:both}.tab-content>.tab-pane,.pill-content>.pill-pane{display:none}.tab-content>.active,.pill-content>.active{display:block}.nav .caret{border-top-color:#428bca;border-bottom-color:#428bca}.nav a:hover .caret{border-top-color:#2a6496;border-bottom-color:#2a6496}.nav-tabs .dropdown-menu{margin-top:-1px;border-top-right-radius:0;border-top-left-radius:0}.navbar{position:relative;z-index:1000;min-height:50px;margin-bottom:20px;border:1px solid transparent}.navbar:before,.navbar:after{display:table;content:" "}.navbar:after{clear:both}.navbar:before,.navbar:after{display:table;content:" "}.navbar:after{clear:both}@media(min-width:768px){.navbar{border-radius:4px}}.navbar-header:before,.navbar-header:after{display:table;content:" "}.navbar-header:after{clear:both}.navbar-header:before,.navbar-header:after{display:table;content:" "}.navbar-header:after{clear:both}@media(min-width:768px){.navbar-header{float:left}}.navbar-collapse{max-height:340px;padding-right:15px;padding-left:15px;overflow-x:visible;border-top:1px solid transparent;box-shadow:inset 0 1px 0 rgba(255,255,255,0.1);-webkit-overflow-scrolling:touch}.navbar-collapse:before,.navbar-collapse:after{display:table;content:" "}.navbar-collapse:after{clear:both}.navbar-collapse:before,.navbar-collapse:after{display:table;content:" "}.navbar-collapse:after{clear:both}.navbar-collapse.in{overflow-y:auto}@media(min-width:768px){.navbar-collapse{width:auto;border-top:0;box-shadow:none}.navbar-collapse.collapse{display:block!important;height:auto!important;padding-bottom:0;overflow:visible!important}.navbar-collapse.in{overflow-y:visible}.navbar-collapse .navbar-nav.navbar-left:first-child{margin-left:-15px}.navbar-collapse .navbar-nav.navbar-right:last-child{margin-right:-15px}.navbar-collapse .navbar-text:last-child{margin-right:0}}.container>.navbar-header,.container>.navbar-collapse{margin-right:-15px;margin-left:-15px}@media(min-width:768px){.container>.navbar-header,.container>.navbar-collapse{margin-right:0;margin-left:0}}.navbar-static-top{border-width:0 0 1px}@media(min-width:768px){.navbar-static-top{border-radius:0}}.navbar-fixed-top,.navbar-fixed-bottom{position:fixed;right:0;left:0;border-width:0 0 1px}@media(min-width:768px){.navbar-fixed-top,.navbar-fixed-bottom{border-radius:0}}.navbar-fixed-top{top:0;z-index:1030}.navbar-fixed-bottom{bottom:0;margin-bottom:0}.navbar-brand{float:left;padding:15px 15px;font-size:18px;line-height:20px}.navbar-brand:hover,.navbar-brand:focus{text-decoration:none}@media(min-width:768px){.navbar>.container .navbar-brand{margin-left:-15px}}.navbar-toggle{position:relative;float:right;padding:9px 10px;margin-top:8px;margin-right:15px;margin-bottom:8px;background-color:transparent;border:1px solid transparent;border-radius:4px}.navbar-toggle .icon-bar{display:block;width:22px;height:2px;border-radius:1px}.navbar-toggle .icon-bar+.icon-bar{margin-top:4px}@media(min-width:768px){.navbar-toggle{display:none}}.navbar-nav{margin:7.5px -15px}.navbar-nav>li>a{padding-top:10px;padding-bottom:10px;line-height:20px}@media(max-width:767px){.navbar-nav .open .dropdown-menu{position:static;float:none;width:auto;margin-top:0;background-color:transparent;border:0;box-shadow:none}.navbar-nav .open .dropdown-menu>li>a,.navbar-nav .open .dropdown-menu .dropdown-header{padding:5px 15px 5px 25px}.navbar-nav .open .dropdown-menu>li>a{line-height:20px}.navbar-nav .open .dropdown-menu>li>a:hover,.navbar-nav .open .dropdown-menu>li>a:focus{background-image:none}}@media(min-width:768px){.navbar-nav{float:left;margin:0}.navbar-nav>li{float:left}.navbar-nav>li>a{padding-top:15px;padding-bottom:15px}}@media(min-width:768px){.navbar-left{float:left!important}.navbar-right{float:right!important}}.navbar-form{padding:10px 15px;margin-top:8px;margin-right:-15px;margin-bottom:8px;margin-left:-15px;border-top:1px solid transparent;border-bottom:1px solid transparent;-webkit-box-shadow:inset 0 1px 0 rgba(255,255,255,0.1),0 1px 0 rgba(255,255,255,0.1);box-shadow:inset 0 1px 0 rgba(255,255,255,0.1),0 1px 0 rgba(255,255,255,0.1)}@media(min-width:768px){.navbar-form .form-group{display:inline-block;margin-bottom:0;vertical-align:middle}.navbar-form .form-control{display:inline-block}.navbar-form .radio,.navbar-form .checkbox{display:inline-block;padding-left:0;margin-top:0;margin-bottom:0}.navbar-form .radio input[type="radio"],.navbar-form .checkbox input[type="checkbox"]{float:none;margin-left:0}}@media(max-width:767px){.navbar-form .form-group{margin-bottom:5px}}@media(min-width:768px){.navbar-form{width:auto;padding-top:0;padding-bottom:0;margin-right:0;margin-left:0;border:0;-webkit-box-shadow:none;box-shadow:none}}.navbar-nav>li>.dropdown-menu{margin-top:0;border-top-right-radius:0;border-top-left-radius:0}.navbar-fixed-bottom .navbar-nav>li>.dropdown-menu{border-bottom-right-radius:0;border-bottom-left-radius:0}.navbar-nav.pull-right>li>.dropdown-menu,.navbar-nav>li>.dropdown-menu.pull-right{right:0;left:auto}.navbar-btn{margin-top:8px;margin-bottom:8px}.navbar-text{float:left;margin-top:15px;margin-bottom:15px}@media(min-width:768px){.navbar-text{margin-right:15px;margin-left:15px}}.navbar-default{background-color:#f8f8f8;border-color:#e7e7e7}.navbar-default .navbar-brand{color:#777}.navbar-default .navbar-brand:hover,.navbar-default .navbar-brand:focus{color:#5e5e5e;background-color:transparent}.navbar-default .navbar-text{color:#777}.navbar-default .navbar-nav>li>a{color:#777}.navbar-default .navbar-nav>li>a:hover,.navbar-default .navbar-nav>li>a:focus{color:#333;background-color:transparent}.navbar-default .navbar-nav>.active>a,.navbar-default .navbar-nav>.active>a:hover,.navbar-default .navbar-nav>.active>a:focus{color:#555;background-color:#e7e7e7}.navbar-default .navbar-nav>.disabled>a,.navbar-default .navbar-nav>.disabled>a:hover,.navbar-default .navbar-nav>.disabled>a:focus{color:#ccc;background-color:transparent}.navbar-default .navbar-toggle{border-color:#ddd}.navbar-default .navbar-toggle:hover,.navbar-default .navbar-toggle:focus{background-color:#ddd}.navbar-default .navbar-toggle .icon-bar{background-color:#ccc}.navbar-default .navbar-collapse,.navbar-default .navbar-form{border-color:#e6e6e6}.navbar-default .navbar-nav>.dropdown>a:hover .caret,.navbar-default .navbar-nav>.dropdown>a:focus .caret{border-top-color:#333;border-bottom-color:#333}.navbar-default .navbar-nav>.open>a,.navbar-default .navbar-nav>.open>a:hover,.navbar-default .navbar-nav>.open>a:focus{color:#555;background-color:#e7e7e7}.navbar-default .navbar-nav>.open>a .caret,.navbar-default .navbar-nav>.open>a:hover .caret,.navbar-default .navbar-nav>.open>a:focus .caret{border-top-color:#555;border-bottom-color:#555}.navbar-default .navbar-nav>.dropdown>a .caret{border-top-color:#777;border-bottom-color:#777}@media(max-width:767px){.navbar-default .navbar-nav .open .dropdown-menu>li>a{color:#777}.navbar-default .navbar-nav .open .dropdown-menu>li>a:hover,.navbar-default .navbar-nav .open .dropdown-menu>li>a:focus{color:#333;background-color:transparent}.navbar-default .navbar-nav .open .dropdown-menu>.active>a,.navbar-default .navbar-nav .open .dropdown-menu>.active>a:hover,.navbar-default .navbar-nav .open .dropdown-menu>.active>a:focus{color:#555;background-color:#e7e7e7}.navbar-default .navbar-nav .open .dropdown-menu>.disabled>a,.navbar-default .navbar-nav .open .dropdown-menu>.disabled>a:hover,.navbar-default .navbar-nav .open .dropdown-menu>.disabled>a:focus{color:#ccc;background-color:transparent}}.navbar-default .navbar-link{color:#777}.navbar-default .navbar-link:hover{color:#333}.navbar-inverse{background-color:#222;border-color:#080808}.navbar-inverse .navbar-brand{color:#999}.navbar-inverse .navbar-brand:hover,.navbar-inverse .navbar-brand:focus{color:#fff;background-color:transparent}.navbar-inverse .navbar-text{color:#999}.navbar-inverse .navbar-nav>li>a{color:#999}.navbar-inverse .navbar-nav>li>a:hover,.navbar-inverse .navbar-nav>li>a:focus{color:#fff;background-color:transparent}.navbar-inverse .navbar-nav>.active>a,.navbar-inverse .navbar-nav>.active>a:hover,.navbar-inverse .navbar-nav>.active>a:focus{color:#fff;background-color:#080808}.navbar-inverse .navbar-nav>.disabled>a,.navbar-inverse .navbar-nav>.disabled>a:hover,.navbar-inverse .navbar-nav>.disabled>a:focus{color:#444;background-color:transparent}.navbar-inverse .navbar-toggle{border-color:#333}.navbar-inverse .navbar-toggle:hover,.navbar-inverse .navbar-toggle:focus{background-color:#333}.navbar-inverse .navbar-toggle .icon-bar{background-color:#fff}.navbar-inverse .navbar-collapse,.navbar-inverse .navbar-form{border-color:#101010}.navbar-inverse .navbar-nav>.open>a,.navbar-inverse .navbar-nav>.open>a:hover,.navbar-inverse .navbar-nav>.open>a:focus{color:#fff;background-color:#080808}.navbar-inverse .navbar-nav>.dropdown>a:hover .caret{border-top-color:#fff;border-bottom-color:#fff}.navbar-inverse .navbar-nav>.dropdown>a .caret{border-top-color:#999;border-bottom-color:#999}.navbar-inverse .navbar-nav>.open>a .caret,.navbar-inverse .navbar-nav>.open>a:hover .caret,.navbar-inverse .navbar-nav>.open>a:focus .caret{border-top-color:#fff;border-bottom-color:#fff}@media(max-width:767px){.navbar-inverse .navbar-nav .open .dropdown-menu>.dropdown-header{border-color:#080808}.navbar-inverse .navbar-nav .open .dropdown-menu>li>a{color:#999}.navbar-inverse .navbar-nav .open .dropdown-menu>li>a:hover,.navbar-inverse .navbar-nav .open .dropdown-menu>li>a:focus{color:#fff;background-color:transparent}.navbar-inverse .navbar-nav .open .dropdown-menu>.active>a,.navbar-inverse .navbar-nav .open .dropdown-menu>.active>a:hover,.navbar-inverse .navbar-nav .open .dropdown-menu>.active>a:focus{color:#fff;background-color:#080808}.navbar-inverse .navbar-nav .open .dropdown-menu>.disabled>a,.navbar-inverse .navbar-nav .open .dropdown-menu>.disabled>a:hover,.navbar-inverse .navbar-nav .open .dropdown-menu>.disabled>a:focus{color:#444;background-color:transparent}}.navbar-inverse .navbar-link{color:#999}.navbar-inverse .navbar-link:hover{color:#fff}.breadcrumb{padding:8px 15px;margin-bottom:20px;list-style:none;background-color:#f5f5f5;border-radius:4px}.breadcrumb>li{display:inline-block}.breadcrumb>li+li:before{padding:0 5px;color:#ccc;content:"/\00a0"}.breadcrumb>.active{color:#999}.pagination{display:inline-block;padding-left:0;margin:20px 0;border-radius:4px}.pagination>li{display:inline}.pagination>li>a,.pagination>li>span{position:relative;float:left;padding:6px 12px;margin-left:-1px;line-height:1.428571429;text-decoration:none;background-color:#fff;border:1px solid #ddd}.pagination>li:first-child>a,.pagination>li:first-child>span{margin-left:0;border-bottom-left-radius:4px;border-top-left-radius:4px}.pagination>li:last-child>a,.pagination>li:last-child>span{border-top-right-radius:4px;border-bottom-right-radius:4px}.pagination>li>a:hover,.pagination>li>span:hover,.pagination>li>a:focus,.pagination>li>span:focus{background-color:#eee}.pagination>.active>a,.pagination>.active>span,.pagination>.active>a:hover,.pagination>.active>span:hover,.pagination>.active>a:focus,.pagination>.active>span:focus{z-index:2;color:#fff;cursor:default;background-color:#428bca;border-color:#428bca}.pagination>.disabled>span,.pagination>.disabled>a,.pagination>.disabled>a:hover,.pagination>.disabled>a:focus{color:#999;cursor:not-allowed;background-color:#fff;border-color:#ddd}.pagination-lg>li>a,.pagination-lg>li>span{padding:10px 16px;font-size:18px}.pagination-lg>li:first-child>a,.pagination-lg>li:first-child>span{border-bottom-left-radius:6px;border-top-left-radius:6px}.pagination-lg>li:last-child>a,.pagination-lg>li:last-child>span{border-top-right-radius:6px;border-bottom-right-radius:6px}.pagination-sm>li>a,.pagination-sm>li>span{padding:5px 10px;font-size:12px}.pagination-sm>li:first-child>a,.pagination-sm>li:first-child>span{border-bottom-left-radius:3px;border-top-left-radius:3px}.pagination-sm>li:last-child>a,.pagination-sm>li:last-child>span{border-top-right-radius:3px;border-bottom-right-radius:3px}.pager{padding-left:0;margin:20px 0;text-align:center;list-style:none}.pager:before,.pager:after{display:table;content:" "}.pager:after{clear:both}.pager:before,.pager:after{display:table;content:" "}.pager:after{clear:both}.pager li{display:inline}.pager li>a,.pager li>span{display:inline-block;padding:5px 14px;background-color:#fff;border:1px solid #ddd;border-radius:15px}.pager li>a:hover,.pager li>a:focus{text-decoration:none;background-color:#eee}.pager .next>a,.pager .next>span{float:right}.pager .previous>a,.pager .previous>span{float:left}.pager .disabled>a,.pager .disabled>a:hover,.pager .disabled>a:focus,.pager .disabled>span{color:#999;cursor:not-allowed;background-color:#fff}.label{display:inline;padding:.2em .6em .3em;font-size:75%;font-weight:bold;line-height:1;color:#fff;text-align:center;white-space:nowrap;vertical-align:baseline;border-radius:.25em}.label[href]:hover,.label[href]:focus{color:#fff;text-decoration:none;cursor:pointer}.label:empty{display:none}.label-default{background-color:#999}.label-default[href]:hover,.label-default[href]:focus{background-color:#808080}.label-primary{background-color:#428bca}.label-primary[href]:hover,.label-primary[href]:focus{background-color:#3071a9}.label-success{background-color:#5cb85c}.label-success[href]:hover,.label-success[href]:focus{background-color:#449d44}.label-info{background-color:#5bc0de}.label-info[href]:hover,.label-info[href]:focus{background-color:#31b0d5}.label-warning{background-color:#f0ad4e}.label-warning[href]:hover,.label-warning[href]:focus{background-color:#ec971f}.label-danger{background-color:#d9534f}.label-danger[href]:hover,.label-danger[href]:focus{background-color:#c9302c}.badge{display:inline-block;min-width:10px;padding:3px 7px;font-size:12px;font-weight:bold;line-height:1;color:#fff;text-align:center;white-space:nowrap;vertical-align:baseline;background-color:#999;border-radius:10px}.badge:empty{display:none}a.badge:hover,a.badge:focus{color:#fff;text-decoration:none;cursor:pointer}.btn .badge{position:relative;top:-1px}a.list-group-item.active>.badge,.nav-pills>.active>a>.badge{color:#428bca;background-color:#fff}.nav-pills>li>a>.badge{margin-left:3px}.jumbotron{padding:30px;margin-bottom:30px;font-size:21px;font-weight:200;line-height:2.1428571435;color:inherit;background-color:#eee}.jumbotron h1{line-height:1;color:inherit}.jumbotron p{line-height:1.4}.container .jumbotron{border-radius:6px}@media screen and (min-width:768px){.jumbotron{padding-top:48px;padding-bottom:48px}.container .jumbotron{padding-right:60px;padding-left:60px}.jumbotron h1{font-size:63px}}.thumbnail{display:inline-block;display:block;height:auto;max-width:100%;padding:4px;line-height:1.428571429;background-color:#fff;border:1px solid #ddd;border-radius:4px;-webkit-transition:all .2s ease-in-out;transition:all .2s ease-in-out}.thumbnail>img{display:block;height:auto;max-width:100%}a.thumbnail:hover,a.thumbnail:focus{border-color:#428bca}.thumbnail>img{margin-right:auto;margin-left:auto}.thumbnail .caption{padding:9px;color:#333}.alert{padding:15px;margin-bottom:20px;border:1px solid transparent;border-radius:4px}.alert h4{margin-top:0;color:inherit}.alert .alert-link{font-weight:bold}.alert>p,.alert>ul{margin-bottom:0}.alert>p+p{margin-top:5px}.alert-dismissable{padding-right:35px}.alert-dismissable .close{position:relative;top:-2px;right:-21px;color:inherit}.alert-success{color:#468847;background-color:#dff0d8;border-color:#d6e9c6}.alert-success hr{border-top-color:#c9e2b3}.alert-success .alert-link{color:#356635}.alert-info{color:#3a87ad;background-color:#d9edf7;border-color:#bce8f1}.alert-info hr{border-top-color:#a6e1ec}.alert-info .alert-link{color:#2d6987}.alert-warning{color:#c09853;background-color:#fcf8e3;border-color:#fbeed5}.alert-warning hr{border-top-color:#f8e5be}.alert-warning .alert-link{color:#a47e3c}.alert-danger{color:#b94a48;background-color:#f2dede;border-color:#eed3d7}.alert-danger hr{border-top-color:#e6c1c7}.alert-danger .alert-link{color:#953b39}@-webkit-keyframes progress-bar-stripes{from{background-position:40px 0}to{background-position:0 0}}@-moz-keyframes progress-bar-stripes{from{background-position:40px 0}to{background-position:0 0}}@-o-keyframes progress-bar-stripes{from{background-position:0 0}to{background-position:40px 0}}@keyframes progress-bar-stripes{from{background-position:40px 0}to{background-position:0 0}}.progress{height:20px;margin-bottom:20px;overflow:hidden;background-color:#f5f5f5;border-radius:4px;-webkit-box-shadow:inset 0 1px 2px rgba(0,0,0,0.1);box-shadow:inset 0 1px 2px rgba(0,0,0,0.1)}.progress-bar{float:left;width:0;height:100%;font-size:12px;color:#fff;text-align:center;background-color:#428bca;-webkit-box-shadow:inset 0 -1px 0 rgba(0,0,0,0.15);box-shadow:inset 0 -1px 0 rgba(0,0,0,0.15);-webkit-transition:width .6s ease;transition:width .6s ease}.progress-striped .progress-bar{background-image:-webkit-gradient(linear,0 100%,100% 0,color-stop(0.25,rgba(255,255,255,0.15)),color-stop(0.25,transparent),color-stop(0.5,transparent),color-stop(0.5,rgba(255,255,255,0.15)),color-stop(0.75,rgba(255,255,255,0.15)),color-stop(0.75,transparent),to(transparent));background-image:-webkit-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-moz-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-size:40px 40px}.progress.active .progress-bar{-webkit-animation:progress-bar-stripes 2s linear infinite;-moz-animation:progress-bar-stripes 2s linear infinite;-ms-animation:progress-bar-stripes 2s linear infinite;-o-animation:progress-bar-stripes 2s linear infinite;animation:progress-bar-stripes 2s linear infinite}.progress-bar-success{background-color:#5cb85c}.progress-striped .progress-bar-success{background-image:-webkit-gradient(linear,0 100%,100% 0,color-stop(0.25,rgba(255,255,255,0.15)),color-stop(0.25,transparent),color-stop(0.5,transparent),color-stop(0.5,rgba(255,255,255,0.15)),color-stop(0.75,rgba(255,255,255,0.15)),color-stop(0.75,transparent),to(transparent));background-image:-webkit-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-moz-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent)}.progress-bar-info{background-color:#5bc0de}.progress-striped .progress-bar-info{background-image:-webkit-gradient(linear,0 100%,100% 0,color-stop(0.25,rgba(255,255,255,0.15)),color-stop(0.25,transparent),color-stop(0.5,transparent),color-stop(0.5,rgba(255,255,255,0.15)),color-stop(0.75,rgba(255,255,255,0.15)),color-stop(0.75,transparent),to(transparent));background-image:-webkit-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-moz-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent)}.progress-bar-warning{background-color:#f0ad4e}.progress-striped .progress-bar-warning{background-image:-webkit-gradient(linear,0 100%,100% 0,color-stop(0.25,rgba(255,255,255,0.15)),color-stop(0.25,transparent),color-stop(0.5,transparent),color-stop(0.5,rgba(255,255,255,0.15)),color-stop(0.75,rgba(255,255,255,0.15)),color-stop(0.75,transparent),to(transparent));background-image:-webkit-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-moz-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent)}.progress-bar-danger{background-color:#d9534f}.progress-striped .progress-bar-danger{background-image:-webkit-gradient(linear,0 100%,100% 0,color-stop(0.25,rgba(255,255,255,0.15)),color-stop(0.25,transparent),color-stop(0.5,transparent),color-stop(0.5,rgba(255,255,255,0.15)),color-stop(0.75,rgba(255,255,255,0.15)),color-stop(0.75,transparent),to(transparent));background-image:-webkit-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-moz-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent)}.media,.media-body{overflow:hidden;zoom:1}.media,.media .media{margin-top:15px}.media:first-child{margin-top:0}.media-object{display:block}.media-heading{margin:0 0 5px}.media>.pull-left{margin-right:10px}.media>.pull-right{margin-left:10px}.media-list{padding-left:0;list-style:none}.list-group{padding-left:0;margin-bottom:20px}.list-group-item{position:relative;display:block;padding:10px 15px;margin-bottom:-1px;background-color:#fff;border:1px solid #ddd}.list-group-item:first-child{border-top-right-radius:4px;border-top-left-radius:4px}.list-group-item:last-child{margin-bottom:0;border-bottom-right-radius:4px;border-bottom-left-radius:4px}.list-group-item>.badge{float:right}.list-group-item>.badge+.badge{margin-right:5px}a.list-group-item{color:#555}a.list-group-item .list-group-item-heading{color:#333}a.list-group-item:hover,a.list-group-item:focus{text-decoration:none;background-color:#f5f5f5}.list-group-item.active,.list-group-item.active:hover,.list-group-item.active:focus{z-index:2;color:#fff;background-color:#428bca;border-color:#428bca}.list-group-item.active .list-group-item-heading,.list-group-item.active:hover .list-group-item-heading,.list-group-item.active:focus .list-group-item-heading{color:inherit}.list-group-item.active .list-group-item-text,.list-group-item.active:hover .list-group-item-text,.list-group-item.active:focus .list-group-item-text{color:#e1edf7}.list-group-item-heading{margin-top:0;margin-bottom:5px}.list-group-item-text{margin-bottom:0;line-height:1.3}.panel{margin-bottom:20px;background-color:#fff;border:1px solid transparent;border-radius:4px;-webkit-box-shadow:0 1px 1px rgba(0,0,0,0.05);box-shadow:0 1px 1px rgba(0,0,0,0.05)}.panel-body{padding:15px}.panel-body:before,.panel-body:after{display:table;content:" "}.panel-body:after{clear:both}.panel-body:before,.panel-body:after{display:table;content:" "}.panel-body:after{clear:both}.panel>.list-group{margin-bottom:0}.panel>.list-group .list-group-item{border-width:1px 0}.panel>.list-group .list-group-item:first-child{border-top-right-radius:0;border-top-left-radius:0}.panel>.list-group .list-group-item:last-child{border-bottom:0}.panel-heading+.list-group .list-group-item:first-child{border-top-width:0}.panel>.table{margin-bottom:0}.panel>.panel-body+.table{border-top:1px solid #ddd}.panel-heading{padding:10px 15px;border-bottom:1px solid transparent;border-top-right-radius:3px;border-top-left-radius:3px}.panel-title{margin-top:0;margin-bottom:0;font-size:16px}.panel-title>a{color:inherit}.panel-footer{padding:10px 15px;background-color:#f5f5f5;border-top:1px solid #ddd;border-bottom-right-radius:3px;border-bottom-left-radius:3px}.panel-group .panel{margin-bottom:0;overflow:hidden;border-radius:4px}.panel-group .panel+.panel{margin-top:5px}.panel-group .panel-heading{border-bottom:0}.panel-group .panel-heading+.panel-collapse .panel-body{border-top:1px solid #ddd}.panel-group .panel-footer{border-top:0}.panel-group .panel-footer+.panel-collapse .panel-body{border-bottom:1px solid #ddd}.panel-default{border-color:#ddd}.panel-default>.panel-heading{color:#333;background-color:#f5f5f5;border-color:#ddd}.panel-default>.panel-heading+.panel-collapse .panel-body{border-top-color:#ddd}.panel-default>.panel-footer+.panel-collapse .panel-body{border-bottom-color:#ddd}.panel-primary{border-color:#428bca}.panel-primary>.panel-heading{color:#fff;background-color:#428bca;border-color:#428bca}.panel-primary>.panel-heading+.panel-collapse .panel-body{border-top-color:#428bca}.panel-primary>.panel-footer+.panel-collapse .panel-body{border-bottom-color:#428bca}.panel-success{border-color:#d6e9c6}.panel-success>.panel-heading{color:#468847;background-color:#dff0d8;border-color:#d6e9c6}.panel-success>.panel-heading+.panel-collapse .panel-body{border-top-color:#d6e9c6}.panel-success>.panel-footer+.panel-collapse .panel-body{border-bottom-color:#d6e9c6}.panel-warning{border-color:#fbeed5}.panel-warning>.panel-heading{color:#c09853;background-color:#fcf8e3;border-color:#fbeed5}.panel-warning>.panel-heading+.panel-collapse .panel-body{border-top-color:#fbeed5}.panel-warning>.panel-footer+.panel-collapse .panel-body{border-bottom-color:#fbeed5}.panel-danger{border-color:#eed3d7}.panel-danger>.panel-heading{color:#b94a48;background-color:#f2dede;border-color:#eed3d7}.panel-danger>.panel-heading+.panel-collapse .panel-body{border-top-color:#eed3d7}.panel-danger>.panel-footer+.panel-collapse .panel-body{border-bottom-color:#eed3d7}.panel-info{border-color:#bce8f1}.panel-info>.panel-heading{color:#3a87ad;background-color:#d9edf7;border-color:#bce8f1}.panel-info>.panel-heading+.panel-collapse .panel-body{border-top-color:#bce8f1}.panel-info>.panel-footer+.panel-collapse .panel-body{border-bottom-color:#bce8f1}.well{min-height:20px;padding:19px;margin-bottom:20px;background-color:#f5f5f5;border:1px solid #e3e3e3;border-radius:4px;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.05);box-shadow:inset 0 1px 1px rgba(0,0,0,0.05)}.well blockquote{border-color:#ddd;border-color:rgba(0,0,0,0.15)}.well-lg{padding:24px;border-radius:6px}.well-sm{padding:9px;border-radius:3px}.close{float:right;font-size:21px;font-weight:bold;line-height:1;color:#000;text-shadow:0 1px 0 #fff;opacity:.2;filter:alpha(opacity=20)}.close:hover,.close:focus{color:#000;text-decoration:none;cursor:pointer;opacity:.5;filter:alpha(opacity=50)}button.close{padding:0;cursor:pointer;background:transparent;border:0;-webkit-appearance:none}.modal-open{overflow:hidden}body.modal-open,.modal-open .navbar-fixed-top,.modal-open .navbar-fixed-bottom{margin-right:15px}.modal{position:fixed;top:0;right:0;bottom:0;left:0;z-index:1040;display:none;overflow:auto;overflow-y:scroll}.modal.fade .modal-dialog{-webkit-transform:translate(0,-25%);-ms-transform:translate(0,-25%);transform:translate(0,-25%);-webkit-transition:-webkit-transform .3s ease-out;-moz-transition:-moz-transform .3s ease-out;-o-transition:-o-transform .3s ease-out;transition:transform .3s ease-out}.modal.in .modal-dialog{-webkit-transform:translate(0,0);-ms-transform:translate(0,0);transform:translate(0,0)}.modal-dialog{z-index:1050;width:auto;padding:10px;margin-right:auto;margin-left:auto}.modal-content{position:relative;background-color:#fff;border:1px solid #999;border:1px solid rgba(0,0,0,0.2);border-radius:6px;outline:0;-webkit-box-shadow:0 3px 9px rgba(0,0,0,0.5);box-shadow:0 3px 9px rgba(0,0,0,0.5);background-clip:padding-box}.modal-backdrop{position:fixed;top:0;right:0;bottom:0;left:0;z-index:1030;background-color:#000}.modal-backdrop.fade{opacity:0;filter:alpha(opacity=0)}.modal-backdrop.in{opacity:.5;filter:alpha(opacity=50)}.modal-header{min-height:16.428571429px;padding:15px;border-bottom:1px solid #e5e5e5}.modal-header .close{margin-top:-2px}.modal-title{margin:0;line-height:1.428571429}.modal-body{position:relative;padding:20px}.modal-footer{padding:19px 20px 20px;margin-top:15px;text-align:right;border-top:1px solid #e5e5e5}.modal-footer:before,.modal-footer:after{display:table;content:" "}.modal-footer:after{clear:both}.modal-footer:before,.modal-footer:after{display:table;content:" "}.modal-footer:after{clear:both}.modal-footer .btn+.btn{margin-bottom:0;margin-left:5px}.modal-footer .btn-group .btn+.btn{margin-left:-1px}.modal-footer .btn-block+.btn-block{margin-left:0}@media screen and (min-width:768px){.modal-dialog{right:auto;left:50%;width:600px;padding-top:30px;padding-bottom:30px}.modal-content{-webkit-box-shadow:0 5px 15px rgba(0,0,0,0.5);box-shadow:0 5px 15px rgba(0,0,0,0.5)}}.tooltip{position:absolute;z-index:1030;display:block;font-size:12px;line-height:1.4;opacity:0;filter:alpha(opacity=0);visibility:visible}.tooltip.in{opacity:.9;filter:alpha(opacity=90)}.tooltip.top{padding:5px 0;margin-top:-3px}.tooltip.right{padding:0 5px;margin-left:3px}.tooltip.bottom{padding:5px 0;margin-top:3px}.tooltip.left{padding:0 5px;margin-left:-3px}.tooltip-inner{max-width:200px;padding:3px 8px;color:#fff;text-align:center;text-decoration:none;background-color:#000;border-radius:4px}.tooltip-arrow{position:absolute;width:0;height:0;border-color:transparent;border-style:solid}.tooltip.top .tooltip-arrow{bottom:0;left:50%;margin-left:-5px;border-top-color:#000;border-width:5px 5px 0}.tooltip.top-left .tooltip-arrow{bottom:0;left:5px;border-top-color:#000;border-width:5px 5px 0}.tooltip.top-right .tooltip-arrow{right:5px;bottom:0;border-top-color:#000;border-width:5px 5px 0}.tooltip.right .tooltip-arrow{top:50%;left:0;margin-top:-5px;border-right-color:#000;border-width:5px 5px 5px 0}.tooltip.left .tooltip-arrow{top:50%;right:0;margin-top:-5px;border-left-color:#000;border-width:5px 0 5px 5px}.tooltip.bottom .tooltip-arrow{top:0;left:50%;margin-left:-5px;border-bottom-color:#000;border-width:0 5px 5px}.tooltip.bottom-left .tooltip-arrow{top:0;left:5px;border-bottom-color:#000;border-width:0 5px 5px}.tooltip.bottom-right .tooltip-arrow{top:0;right:5px;border-bottom-color:#000;border-width:0 5px 5px}.popover{position:absolute;top:0;left:0;z-index:1010;display:none;max-width:276px;padding:1px;text-align:left;white-space:normal;background-color:#fff;border:1px solid #ccc;border:1px solid rgba(0,0,0,0.2);border-radius:6px;-webkit-box-shadow:0 5px 10px rgba(0,0,0,0.2);box-shadow:0 5px 10px rgba(0,0,0,0.2);background-clip:padding-box}.popover.top{margin-top:-10px}.popover.right{margin-left:10px}.popover.bottom{margin-top:10px}.popover.left{margin-left:-10px}.popover-title{padding:8px 14px;margin:0;font-size:14px;font-weight:normal;line-height:18px;background-color:#f7f7f7;border-bottom:1px solid #ebebeb;border-radius:5px 5px 0 0}.popover-content{padding:9px 14px}.popover .arrow,.popover .arrow:after{position:absolute;display:block;width:0;height:0;border-color:transparent;border-style:solid}.popover .arrow{border-width:11px}.popover .arrow:after{border-width:10px;content:""}.popover.top .arrow{bottom:-11px;left:50%;margin-left:-11px;border-top-color:#999;border-top-color:rgba(0,0,0,0.25);border-bottom-width:0}.popover.top .arrow:after{bottom:1px;margin-left:-10px;border-top-color:#fff;border-bottom-width:0;content:" "}.popover.right .arrow{top:50%;left:-11px;margin-top:-11px;border-right-color:#999;border-right-color:rgba(0,0,0,0.25);border-left-width:0}.popover.right .arrow:after{bottom:-10px;left:1px;border-right-color:#fff;border-left-width:0;content:" "}.popover.bottom .arrow{top:-11px;left:50%;margin-left:-11px;border-bottom-color:#999;border-bottom-color:rgba(0,0,0,0.25);border-top-width:0}.popover.bottom .arrow:after{top:1px;margin-left:-10px;border-bottom-color:#fff;border-top-width:0;content:" "}.popover.left .arrow{top:50%;right:-11px;margin-top:-11px;border-left-color:#999;border-left-color:rgba(0,0,0,0.25);border-right-width:0}.popover.left .arrow:after{right:1px;bottom:-10px;border-left-color:#fff;border-right-width:0;content:" "}.carousel{position:relative}.carousel-inner{position:relative;width:100%;overflow:hidden}.carousel-inner>.item{position:relative;display:none;-webkit-transition:.6s ease-in-out left;transition:.6s ease-in-out left}.carousel-inner>.item>img,.carousel-inner>.item>a>img{display:block;height:auto;max-width:100%;line-height:1}.carousel-inner>.active,.carousel-inner>.next,.carousel-inner>.prev{display:block}.carousel-inner>.active{left:0}.carousel-inner>.next,.carousel-inner>.prev{position:absolute;top:0;width:100%}.carousel-inner>.next{left:100%}.carousel-inner>.prev{left:-100%}.carousel-inner>.next.left,.carousel-inner>.prev.right{left:0}.carousel-inner>.active.left{left:-100%}.carousel-inner>.active.right{left:100%}.carousel-control{position:absolute;top:0;bottom:0;left:0;width:15%;font-size:20px;color:#fff;text-align:center;text-shadow:0 1px 2px rgba(0,0,0,0.6);opacity:.5;filter:alpha(opacity=50)}.carousel-control.left{background-image:-webkit-gradient(linear,0 top,100% top,from(rgba(0,0,0,0.5)),to(rgba(0,0,0,0.0001)));background-image:-webkit-linear-gradient(left,color-stop(rgba(0,0,0,0.5) 0),color-stop(rgba(0,0,0,0.0001) 100%));background-image:-moz-linear-gradient(left,rgba(0,0,0,0.5) 0,rgba(0,0,0,0.0001) 100%);background-image:linear-gradient(to right,rgba(0,0,0,0.5) 0,rgba(0,0,0,0.0001) 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#80000000',endColorstr='#00000000',GradientType=1)}.carousel-control.right{right:0;left:auto;background-image:-webkit-gradient(linear,0 top,100% top,from(rgba(0,0,0,0.0001)),to(rgba(0,0,0,0.5)));background-image:-webkit-linear-gradient(left,color-stop(rgba(0,0,0,0.0001) 0),color-stop(rgba(0,0,0,0.5) 100%));background-image:-moz-linear-gradient(left,rgba(0,0,0,0.0001) 0,rgba(0,0,0,0.5) 100%);background-image:linear-gradient(to right,rgba(0,0,0,0.0001) 0,rgba(0,0,0,0.5) 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#00000000',endColorstr='#80000000',GradientType=1)}.carousel-control:hover,.carousel-control:focus{color:#fff;text-decoration:none;opacity:.9;filter:alpha(opacity=90)}.carousel-control .icon-prev,.carousel-control .icon-next,.carousel-control .glyphicon-chevron-left,.carousel-control .glyphicon-chevron-right{position:absolute;top:50%;left:50%;z-index:5;display:inline-block}.carousel-control .icon-prev,.carousel-control .icon-next{width:20px;height:20px;margin-top:-10px;margin-left:-10px;font-family:serif}.carousel-control .icon-prev:before{content:'\2039'}.carousel-control .icon-next:before{content:'\203a'}.carousel-indicators{position:absolute;bottom:10px;left:50%;z-index:15;width:60%;padding-left:0;margin-left:-30%;text-align:center;list-style:none}.carousel-indicators li{display:inline-block;width:10px;height:10px;margin:1px;text-indent:-999px;cursor:pointer;border:1px solid #fff;border-radius:10px}.carousel-indicators .active{width:12px;height:12px;margin:0;background-color:#fff}.carousel-caption{position:absolute;right:15%;bottom:20px;left:15%;z-index:10;padding-top:20px;padding-bottom:20px;color:#fff;text-align:center;text-shadow:0 1px 2px rgba(0,0,0,0.6)}.carousel-caption .btn{text-shadow:none}@media screen and (min-width:768px){.carousel-control .icon-prev,.carousel-control .icon-next{width:30px;height:30px;margin-top:-15px;margin-left:-15px;font-size:30px}.carousel-caption{right:20%;left:20%;padding-bottom:30px}.carousel-indicators{bottom:20px}}.clearfix:before,.clearfix:after{display:table;content:" "}.clearfix:after{clear:both}.pull-right{float:right!important}.pull-left{float:left!important}.hide{display:none!important}.show{display:block!important}.invisible{visibility:hidden}.text-hide{font:0/0 a;color:transparent;text-shadow:none;background-color:transparent;border:0}.affix{position:fixed}@-ms-viewport{width:device-width}@media screen and (max-width:400px){@-ms-viewport{width:320px}}.hidden{display:none!important;visibility:hidden!important}.visible-xs{display:none!important}tr.visible-xs{display:none!important}th.visible-xs,td.visible-xs{display:none!important}@media(max-width:767px){.visible-xs{display:block!important}tr.visible-xs{display:table-row!important}th.visible-xs,td.visible-xs{display:table-cell!important}}@media(min-width:768px) and (max-width:991px){.visible-xs.visible-sm{display:block!important}tr.visible-xs.visible-sm{display:table-row!important}th.visible-xs.visible-sm,td.visible-xs.visible-sm{display:table-cell!important}}@media(min-width:992px) and (max-width:1199px){.visible-xs.visible-md{display:block!important}tr.visible-xs.visible-md{display:table-row!important}th.visible-xs.visible-md,td.visible-xs.visible-md{display:table-cell!important}}@media(min-width:1200px){.visible-xs.visible-lg{display:block!important}tr.visible-xs.visible-lg{display:table-row!important}th.visible-xs.visible-lg,td.visible-xs.visible-lg{display:table-cell!important}}.visible-sm{display:none!important}tr.visible-sm{display:none!important}th.visible-sm,td.visible-sm{display:none!important}@media(max-width:767px){.visible-sm.visible-xs{display:block!important}tr.visible-sm.visible-xs{display:table-row!important}th.visible-sm.visible-xs,td.visible-sm.visible-xs{display:table-cell!important}}@media(min-width:768px) and (max-width:991px){.visible-sm{display:block!important}tr.visible-sm{display:table-row!important}th.visible-sm,td.visible-sm{display:table-cell!important}}@media(min-width:992px) and (max-width:1199px){.visible-sm.visible-md{display:block!important}tr.visible-sm.visible-md{display:table-row!important}th.visible-sm.visible-md,td.visible-sm.visible-md{display:table-cell!important}}@media(min-width:1200px){.visible-sm.visible-lg{display:block!important}tr.visible-sm.visible-lg{display:table-row!important}th.visible-sm.visible-lg,td.visible-sm.visible-lg{display:table-cell!important}}.visible-md{display:none!important}tr.visible-md{display:none!important}th.visible-md,td.visible-md{display:none!important}@media(max-width:767px){.visible-md.visible-xs{display:block!important}tr.visible-md.visible-xs{display:table-row!important}th.visible-md.visible-xs,td.visible-md.visible-xs{display:table-cell!important}}@media(min-width:768px) and (max-width:991px){.visible-md.visible-sm{display:block!important}tr.visible-md.visible-sm{display:table-row!important}th.visible-md.visible-sm,td.visible-md.visible-sm{display:table-cell!important}}@media(min-width:992px) and (max-width:1199px){.visible-md{display:block!important}tr.visible-md{display:table-row!important}th.visible-md,td.visible-md{display:table-cell!important}}@media(min-width:1200px){.visible-md.visible-lg{display:block!important}tr.visible-md.visible-lg{display:table-row!important}th.visible-md.visible-lg,td.visible-md.visible-lg{display:table-cell!important}}.visible-lg{display:none!important}tr.visible-lg{display:none!important}th.visible-lg,td.visible-lg{display:none!important}@media(max-width:767px){.visible-lg.visible-xs{display:block!important}tr.visible-lg.visible-xs{display:table-row!important}th.visible-lg.visible-xs,td.visible-lg.visible-xs{display:table-cell!important}}@media(min-width:768px) and (max-width:991px){.visible-lg.visible-sm{display:block!important}tr.visible-lg.visible-sm{display:table-row!important}th.visible-lg.visible-sm,td.visible-lg.visible-sm{display:table-cell!important}}@media(min-width:992px) and (max-width:1199px){.visible-lg.visible-md{display:block!important}tr.visible-lg.visible-md{display:table-row!important}th.visible-lg.visible-md,td.visible-lg.visible-md{display:table-cell!important}}@media(min-width:1200px){.visible-lg{display:block!important}tr.visible-lg{display:table-row!important}th.visible-lg,td.visible-lg{display:table-cell!important}}.hidden-xs{display:block!important}tr.hidden-xs{display:table-row!important}th.hidden-xs,td.hidden-xs{display:table-cell!important}@media(max-width:767px){.hidden-xs{display:none!important}tr.hidden-xs{display:none!important}th.hidden-xs,td.hidden-xs{display:none!important}}@media(min-width:768px) and (max-width:991px){.hidden-xs.hidden-sm{display:none!important}tr.hidden-xs.hidden-sm{display:none!important}th.hidden-xs.hidden-sm,td.hidden-xs.hidden-sm{display:none!important}}@media(min-width:992px) and (max-width:1199px){.hidden-xs.hidden-md{display:none!important}tr.hidden-xs.hidden-md{display:none!important}th.hidden-xs.hidden-md,td.hidden-xs.hidden-md{display:none!important}}@media(min-width:1200px){.hidden-xs.hidden-lg{display:none!important}tr.hidden-xs.hidden-lg{display:none!important}th.hidden-xs.hidden-lg,td.hidden-xs.hidden-lg{display:none!important}}.hidden-sm{display:block!important}tr.hidden-sm{display:table-row!important}th.hidden-sm,td.hidden-sm{display:table-cell!important}@media(max-width:767px){.hidden-sm.hidden-xs{display:none!important}tr.hidden-sm.hidden-xs{display:none!important}th.hidden-sm.hidden-xs,td.hidden-sm.hidden-xs{display:none!important}}@media(min-width:768px) and (max-width:991px){.hidden-sm{display:none!important}tr.hidden-sm{display:none!important}th.hidden-sm,td.hidden-sm{display:none!important}}@media(min-width:992px) and (max-width:1199px){.hidden-sm.hidden-md{display:none!important}tr.hidden-sm.hidden-md{display:none!important}th.hidden-sm.hidden-md,td.hidden-sm.hidden-md{display:none!important}}@media(min-width:1200px){.hidden-sm.hidden-lg{display:none!important}tr.hidden-sm.hidden-lg{display:none!important}th.hidden-sm.hidden-lg,td.hidden-sm.hidden-lg{display:none!important}}.hidden-md{display:block!important}tr.hidden-md{display:table-row!important}th.hidden-md,td.hidden-md{display:table-cell!important}@media(max-width:767px){.hidden-md.hidden-xs{display:none!important}tr.hidden-md.hidden-xs{display:none!important}th.hidden-md.hidden-xs,td.hidden-md.hidden-xs{display:none!important}}@media(min-width:768px) and (max-width:991px){.hidden-md.hidden-sm{display:none!important}tr.hidden-md.hidden-sm{display:none!important}th.hidden-md.hidden-sm,td.hidden-md.hidden-sm{display:none!important}}@media(min-width:992px) and (max-width:1199px){.hidden-md{display:none!important}tr.hidden-md{display:none!important}th.hidden-md,td.hidden-md{display:none!important}}@media(min-width:1200px){.hidden-md.hidden-lg{display:none!important}tr.hidden-md.hidden-lg{display:none!important}th.hidden-md.hidden-lg,td.hidden-md.hidden-lg{display:none!important}}.hidden-lg{display:block!important}tr.hidden-lg{display:table-row!important}th.hidden-lg,td.hidden-lg{display:table-cell!important}@media(max-width:767px){.hidden-lg.hidden-xs{display:none!important}tr.hidden-lg.hidden-xs{display:none!important}th.hidden-lg.hidden-xs,td.hidden-lg.hidden-xs{display:none!important}}@media(min-width:768px) and (max-width:991px){.hidden-lg.hidden-sm{display:none!important}tr.hidden-lg.hidden-sm{display:none!important}th.hidden-lg.hidden-sm,td.hidden-lg.hidden-sm{display:none!important}}@media(min-width:992px) and (max-width:1199px){.hidden-lg.hidden-md{display:none!important}tr.hidden-lg.hidden-md{display:none!important}th.hidden-lg.hidden-md,td.hidden-lg.hidden-md{display:none!important}}@media(min-width:1200px){.hidden-lg{display:none!important}tr.hidden-lg{display:none!important}th.hidden-lg,td.hidden-lg{display:none!important}}.visible-print{display:none!important}tr.visible-print{display:none!important}th.visible-print,td.visible-print{display:none!important}@media print{.visible-print{display:block!important}tr.visible-print{display:table-row!important}th.visible-print,td.visible-print{display:table-cell!important}.hidden-print{display:none!important}tr.hidden-print{display:none!important}th.hidden-print,td.hidden-print{display:none!important}}body {
	padding-top: 60px;
}

.bs-callout-info {
    background-color: #F0F7FD;
    border-color: #D0E3F0;
}
.bs-callout {
    border-left: 5px solid #EEEEEE;
    margin: 10px 0;
    padding: 15px 30px 15px 15px;
}
.container-full {
  margin: 0 auto;
  width: 100%;
}
body {
	background-color: #f8f5ee;
}
hr {
	margin:0;
	padding:0;
	border: 0;
	border-bottom: solid 1px #ececec;
	clear:both;
}

.navbar-inverse .navbar-nav > .active > a, .navbar-inverse .navbar-nav > .active > a:hover, .navbar-inverse .navbar-nav > .active > a:focus {
    background-color: #800000;
    color: #FFFFFF;
}


@media (max-width: 767px) {

.col-xs-1, .col-xs-2, .col-xs-3, .col-xs-4, .col-xs-5, .col-xs-6, .col-xs-7, .col-xs-8, .col-xs-9, .col-xs-10, .col-xs-11, .col-xs-12, 
.col-sm-1, .col-sm-2, .col-sm-3, .col-sm-4, .col-sm-5, .col-sm-6, .col-sm-7, .col-sm-8, .col-sm-9, .col-sm-10, .col-sm-11, .col-sm-12, 
.col-md-1, .col-md-2, .col-md-3, .col-md-4, .col-md-5, .col-md-6, .col-md-7, .col-md-8, .col-md-9, .col-md-10, .col-md-11, .col-md-12, 
.col-lg-1, .col-lg-2, .col-lg-3, .col-lg-4, .col-lg-5, .col-lg-6, .col-lg-7, .col-lg-8, .col-lg-9, .col-lg-10, .col-lg-11, .col-lg-12 {
	padding-left: 0;
	padding-right: 0;
}

}

.onsmall {
	display:none;
}

@media (max-width: 991px) {
	.onbig {
		display:none;
	}
	.onsmall {
		display:block;
	}
}

.previewimg {
	border: solid 1px #ececec;
	padding: 5px;
	margin: 10px 0 10px 0;
	background-color: #f8f5ee;
    max-width: 100%;
}


.tree {
    min-height:20px;
    padding:19px;
    /*margin-bottom:20px;*/
/*
	background-color:#fbfbfb;
    border:1px solid #999;
    -webkit-border-radius:4px;
    -moz-border-radius:4px;
    border-radius:4px;
    -webkit-box-shadow:inset 0 1px 1px rgba(0, 0, 0, 0.05);
    -moz-box-shadow:inset 0 1px 1px rgba(0, 0, 0, 0.05);
    box-shadow:inset 0 1px 1px rgba(0, 0, 0, 0.05)
*/    
}
.tree li {
    list-style-type:none;
    margin:0;
    padding:10px 5px 0 5px;
    position:relative
}
.tree li::before, .tree li::after {
    content:'';
    left:-20px;
    position:absolute;
    right:auto
}
.tree li::before {
    border-left:1px solid #999;
    bottom:50px;
    height:100%;
    top:0;
    width:1px
}
.tree li::after {
    border-top:1px solid #999;
    height:20px;
    top:25px;
    width:25px
}
.tree li span {
    -moz-border-radius:5px;
    -webkit-border-radius:5px;
    border:1px solid #999;
    border-radius:5px;
    display:inline-block;
    padding:3px 8px;
    text-decoration:none
}
.tree li.parent_li>span {
    cursor:pointer
}
.tree>ul>li::before, .tree>ul>li::after {
    border:0
}
.tree li:last-child::before {
    height:30px
}
/*
.tree li.parent_li>span:hover, .tree li.parent_li>span:hover+ul li span {
    background:#eee;
    border:1px solid #94a0b4;
    color:#000
}
*/
    €€     (    (   €                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     
   
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            
         #   )   +   $      	                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                '   .   4   ;]]]Q      	                                                                                                                                                                                                                                                                                                                                                                                                                                                                               	         !   *   3   :   H†††’חחחצ   *   
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    %   -   6   ?Y¶¶¶ֶלללÿלללÿ   0   
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                (   0   8   ERRRy׳׳׳ךלללÿלללÿלללÿ   0   
                                                                                                                                                                                                                                                                                                                                                                                                                                                               
         "   +   3   <   O®חחחûלללÿלללÿלללÿלללÿ   0   
                                                                                                                                                                                                                                                                                                                                                                                                                                                                   &   .   6   B555gָָָÚלללÿלללÿלללÿלללÿלללÿלללÿ   0   
                                                                                                                                                                                                                                                                                                                                                                                                                                     	             )   2   9   Itttבבבפםםםÿםםםÿםםםÿםםםÿםםםÿםםםÿםםםÿ   0   
                                                                                                                                                                                                                                                                                                                                                                                                     	   	   	   	   	   	   	   
            $   ,   4   >T×××»םםםÿםםםÿםםםÿםםםÿםםםÿםםםÿםםםÿםםםÿםםםÿ   0   
                                                                                                                                                                                                                                                                                                                                                                      	                                                      !   (   0   7   DDDDpׁׁׁגםםםÿםםםÿםםםÿםםםÿםםםÿםםםÿםםםÿםםםÿםםםÿםםםÿ   0   
                                                                                                                                                                                                                                                                                                                                                    	                                  "   #   $   %   &   &   '   &   &   &   '   *   .   4   ;   K˜זזזרםםםÿםםםÿםםםÿםםםÿםםםÿםםםÿםםםÿםםםÿםםםÿםםםÿםםםÿ   0                                                                                                                                                                                                                                                                                                                                           
                     "   %   (   *   ,   -   /   0   1   2   2   3   3   3   3   3   3   3   5   9   A###^···ַמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿ   1                                                                                                                                                                                                                                                                                                                               
                  #   '   *   -   0   2   4   5   6   8   9   9   :   :   ;   ;   ;   ;   ;   ;   ;   ;   >   Ieee„ÞÞÞןמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿ   5                                                                                                                                                                                                                                                                                                                                     %   )   -   0   3   5   8   9   ;   >   A   D   G   J   K   M   N   P   Q   R   R   R   R   P   P   X¡¡¡³כככ‎מממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿ   :         	                                                                                                                                                                                                                                                                                               	               $   )   .   2   5   8   :   >   C   G   KSPPPtyyy•••¦×××÷»»»ָָָֻÙ׃׃׃הÜÜÜםהההפטטטתללל‎מממÿללל‎טטטתהההפÝÝÝןחחחרמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿמממÿ   B   "            	                                                                                                                                                                                                                                                                               
            !   '   ,   1   4   8   <   A   G   NRRRr‡‡‡×××»ֶֶֶײÝÝÝםןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿ   J   .   '   !            
                                                                                                                                                                                                                                                                	            "   )   .   3   7   <   B   I222a“¬¬¬¼ֽֽֽÞיייתןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿ   P   7   3   .   )   "            	                                                                                                                                                                                                                                                             "   )   /   4   9   >   G---`†††–µµµֶÙÙÙךןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿ(((k   G   >   9   4   /   )   "                                                                                                                                                                                                                                                          !   (   /   5   9   B   Lmmm‚×××÷װװװוןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿןןןÿ׃׃׃ז×××÷mmm‚   L   B   9   5   /   (   !                                                                                                                                                                                                                                              &   -   4   9   BQ‡‡‡–¾¾¾־טטטרנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿטטטר¾¾¾־‡‡‡–Q   B   9   4   -   &                                                                                                                                                                                                                         
         "   +   1   8   BUֶֶֶ׳נננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿֶֶֶ׳U   B   8   1   +   "         
                                                                                                                                                                                                              &   .   6   >   Mˆˆˆ–ִִִױנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿִִִױˆˆˆ–   M   >   6   .   &                                                                                                                                                                                                                )   2   :   Hooo„÷÷÷ֹםםםüנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿנננÿםםםü÷÷÷ֹooo„   H   :   2   )                                                                                                                                                                                                     #   ,   5   A777a₪₪₪²ßßßמסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿßßßמ₪₪₪²777a   A   5   ,   #                                                                                                                                                                                	         %   /   7   G}}}‹ֶֶֶױסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿֶֶֶױ}}}‹   G   7   /   %         	                                                                                                                                                               	         &   0   <Q   ®אאאןסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿסססÿאאאן   ®Q   <   0   &         	                                                                                                                                                       	         '   2   ?UUUo¸¸¸ֶעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿ¸¸¸ֶUUUo   ?   2   '         	                                                                                                                                                        '   2   Buuu…ÊÊÊ״עעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿזזזÿÿhhhÿNNNÿNNNÿhhhÿÿזזזÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿÊÊÊ״uuu…   B   2   '                                                                                                                                                         &   2   Dˆˆˆ”ײײײועעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿֿֿֿÿ```ÿVVVÿ[[[ÿ^^^ÿ^^^ÿ[[[ÿVVVÿ```ÿֿֿֿÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿײײײוˆˆˆ”   D   2   &                                                                                                                                                 %   1   D‘‘‘ÝÝÝםעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿÛÛÛÿXXXÿ\\\ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ\\\ÿXXXÿÛÛÛÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿעעעÿÝÝÝם‘‘‘   D   1   %                                                                                                                                          #   0   D••• דדדנףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿsssÿZZZÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿXXXÿ‡‡‡ÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿדדדנ•••    D   0   #                                                                                                                             
          .   B’’’דדדסףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿÜÜÜÿPPPÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿPPPÿÜÜÜÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿדדדס’’’   B   .          
                                                                                                                        +   ?ˆˆˆ”אאאםףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿ¯¯¯ÿTTTÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿTTTÿ¯¯¯ÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿאאאםˆˆˆ”   ?   +                                                                                                                          '   :xxx„׳׳׳וףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿ›››ÿVVVÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿVVVÿ›››ÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿףףףÿ׳׳׳וxxx„   :   '                                                                                                             
      !   3VVVlּּּ״פפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿ›››ÿWWWÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿWWWÿ›››ÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿּּּ״VVVl   3   !      
                                                                                                         ,I÷÷÷ִפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿ°°°ÿUUUÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿUUUÿ°°°ÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿ÷÷÷ִI   ,                                                                                                           &   >¡¡¡§פפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿÝÝÝÿQQQÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿQQQÿÝÝÝÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿ¡¡¡§   >   &                                                                                                       2xxxßßßלפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿ‡‡‡ÿYYYÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿYYYÿ‡‡‡ÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿפפפÿßßßלxxx   2                                                                                                   (LִִִֽץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿטטטÿYYYÿ]]]ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ]]]ÿYYYÿטטטÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿִִִֽL   (                                                                                               7¡ץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿׂׂׂÿaaaÿWWWÿ\\\ÿ___ÿ___ÿ\\\ÿWWWÿaaaÿׂׂׂÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿ¡   7                                                                                     
      )TTTdײײײגץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿטטטÿÿjjjÿOOOÿOOOÿjjjÿÿטטטÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿץץץÿײײײגTTTd   )      
                                                                                 7¬¬¬°צצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿ¬¬¬°   7                                                                                    &^^^gÞÞÞטצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿÞÞÞט^^^g   &                                                                                3×××¬צצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿ×××¬   3                                                                             ;;;T״״״בצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿ׃׃׃ÿ׃׃׃ÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿצצצÿ״״״ב;;;T                                                                             *———–קקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿ¾¾¾ÿSSSÿSSSÿÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿ———–   *                                                                  	      6ַַַÊקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿkkkÿ]]]ÿ___ÿYYYÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿַַַÊ   6      	                                                              ```bיייףקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿPPPÿ```ÿ```ÿQQQÿךךךÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿיייף```b                                                                    &™קקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿװװװÿSSSÿ```ÿ```ÿTTTÿָָָÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿקקקÿ™   &                                                                 .ִִִֵרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿֹֹֹÿTTTÿ```ÿ```ÿWWWÿ¨¨¨ÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿִִִֵ   .                                                              =גגגחרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿÿXXXÿ```ÿ```ÿXXXÿÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿגגגח=                                                              wwwkרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿ”””ÿYYYÿ```ÿ```ÿ\\\ÿuuuÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿwwwk                                                              ’רררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿuuuÿ\\\ÿ```ÿ```ÿ]]]ÿkkkÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿרררÿ’                                                           
   #¸¸¸°שששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿYYYÿ```ÿaaaÿaaaÿaaaÿQQQÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿ¸¸¸°   #   
                                                     
   &ָּּּשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿQQQÿaaaÿaaaÿaaaÿaaaÿSSSÿבבבÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿָּּּ   &   
                                                     
   )ÛÛÛÜשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿÊÊÊÿUUUÿaaaÿaaaÿaaaÿaaaÿUUUÿÊÊÊÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿשששÿÛÛÛÜ   )   
                                                     
   +זזזךתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿְְְÿVVVÿaaaÿaaaÿaaaÿaaaÿYYYÿ   ÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿזזזך   +   
                                                     
   -ססספתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿ   ÿYYYÿaaaÿaaaÿaaaÿaaaÿZZZÿ•••ÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿססספ   -   
                                                     	   .קקקûתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿ‹‹‹ÿ[[[ÿaaaÿaaaÿaaaÿaaaÿ]]]ÿvvvÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿקקקû   .   	                                                     	   -תתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿvvvÿ]]]ÿaaaÿaaaÿaaaÿaaaÿ___ÿdddÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿתתתÿ   -   	                                                        +רררûûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿZZZÿ```ÿaaaÿaaaÿaaaÿaaaÿaaaÿQQQÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿרררû   +                                                           (עעעפûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿמממÿRRRÿaaaÿaaaÿaaaÿaaaÿaaaÿaaaÿTTTÿ׳׳׳ÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿעעעפ   (                                                           %טטטיûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿּּּÿUUUÿaaaÿaaaÿaaaÿaaaÿaaaÿaaaÿVVVÿֱֱֱÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿטטטי   %                                                           ßßßÙûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿµµµÿWWWÿaaaÿaaaÿaaaÿaaaÿaaaÿaaaÿYYYÿ   ÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿßßßÙ                                                              ִׂׂׂüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿ¡¡¡ÿZZZÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿ\\\ÿ‹‹‹ÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿִׂׂׂ                                                               ֲֲֲ©üüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿÿ]]]ÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿ^^^ÿwwwÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿֲֲֲ©                                                                ®®®‡üüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿwwwÿ^^^ÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿaaaÿ[[[ÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿ®®®‡                                                                
‹‹‹\üüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿQQQÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿQQQÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿ‹‹‹\   
                                                              !!!'יייה‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿהההÿSSSÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿVVVÿֽֽֽÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿיייה!!!'                                                                     ׂׂׂ¼‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿֽֽֽÿVVVÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿXXXÿ¶¶¶ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿׂׂׂ¼                                                                        µµµ‰‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ«««ÿYYYÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿZZZÿ¢¢¢ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿµµµ‰                                                                         €€€Kעעענ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ¢¢¢ÿZZZÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿ]]]ÿ‚‚‚ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿ‎‎‎ÿעעענ€€€K                                                                             ײײײְ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿxxxÿ___ÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿ___ÿxxxÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿײײײְ                                                                                 ³³³‚‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿnnnÿ```ÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿRRRÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ³³³‚                                                                                  bbb4זזזÛ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿRRRÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿSSSÿסססÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿזזזÛbbb4                                                                                     ֳֳֳ›‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿÚÚÚÿUUUÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿWWWÿ־־־ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿֳֳֳ›                                                                                         ‰‰‰Hכככג‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿֳֳֳÿWWWÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿYYYÿ···ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿכככג‰‰‰H                                                                                             ֶֶֶ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ¢¢¢ÿZZZÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿZZZÿ¢¢¢ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿ‏‏‏ÿֶֶֶ                                                                                                 CחחחÛÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿƒƒƒÿ^^^ÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿ___ÿyyyÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿחחחÛC                                                                                                     ½½½‹ÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿyyyÿ___ÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿaaaÿeeeÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿ½½½‹                                                                                                         :::#ÙÙÙֱÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿRRRÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿRRRÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÙÙÙֱ:::#                                                                                                             
¦¦¦aמממחÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿRRRÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿSSSÿעעעÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿמממח¦¦¦a   
                                                                                                                 ֱֱֱ‘ÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿזזזÿTTTÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿWWWÿֿֿֿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿֱֱֱ‘                                                                                                                         ###׃׃׃´ÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿֿֿֿÿWWWÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿWWWÿֿֿֿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿ׃׃׃´###                                                                                                                              EאאאֽÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÛÛÛÿUUUÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿWWWÿֿֿֿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿאאאֽE                                                                                                                                     §§§cיייÞÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿRRRÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿTTTÿזזזÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿיייÞ§§§c                                                                                                                                            µµµuנננטÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿeeeÿaaaÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿbbbÿ\\\ÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿנננטµµµu                                                                                                                                                    ÷÷÷עעעםÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿ¢¢¢ÿZZZÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿ]]]ÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿעעעם÷÷÷                                                                                                                                                            ½½½„עעעלÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿעעעÿ\\\ÿbbbÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿbbbÿ]]]ÿזזזÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿעעעל½½½„                                                                                                                                                                    ÷÷÷נננחÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿֿֿֿÿ^^^ÿ___ÿcccÿcccÿcccÿcccÿcccÿcccÿ___ÿUUUÿֳֳֳÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿנננח÷÷÷                                                                                                                                                                            µµµuיייÝÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÛÛÛÿyyyÿTTTÿYYYÿZZZÿZZZÿZZZÿUUUÿyyyÿÛÛÛÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿיייÝµµµu                                                                                                                                                                                    §§§bבבבּÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿזזזÿ¸¸¸ÿ¢¢¢ÿ¢¢¢ÿ¬¬¬ÿÛÛÛÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿבבבּ§§§b                                                                                                                                                                                            EױױױµÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿױױױµE                                                                                                                                                                                                    :::ֵֵֵ•סססכÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿסססכֵֵֵ•:::                                                                                                                                                                                                               ¯¯¯iÞÞÞֹÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÞÞÞֹ¯¯¯i                                                                                                                                                                                                                           rrr1ַַַ›סססיÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿסססיַַַ›rrr1                                                                                                                                                                                                                                       ¥¥¥^׳׳׳¹‎‎‎ûÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿ‎‎‎û׳׳׳¹¥¥¥^                                                                                                                                                                                                                                                      ¸¸¸vÞÞÞָÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÞÞÞָ¸¸¸v                                                                                                                                                                                                                                                                   666!»»»ßßßÊÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿßßßÊ»»»666!                                                                                                                                                                                                                                                                               +++¶¶¶vÚÚÚ¿שששצÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿשששצÚÚÚ¿¶¶¶v+++                                                                                                                                                                                                                                                                                              
   ₪₪₪\ֽֽֽ₪ךךךÝÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿךךךÝֽֽֽ₪₪₪₪\      
                                                                                                                                                                                                                                                                                                               bbb/¶¶¶vױױױ´מממהÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿמממהױױױ´¶¶¶vbbb/                                                                                                                                                                                                                                                                                                                                      
   hhh1°°°q־־־§זזזװûûûרÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿûûûרזזזװ־־־§°°°qhhh1      
                                                                                                                                                                                                                                                                                                                                                       	      G¶¶¶z־־־¥בבבֹסססטÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿסססטבבבֹ־־־¥¶¶¶zG         	                                                                                                                                                                                                                                                                                                                                                                                  
      H¬¬¬lְְְ־־־¥ÙÙÙ»גגגֽךךךÜסססטקקקסûûûר‎‎‎‎ÿÿÿÿ‎‎‎‎ûûûרקקקססססטךךךÜגגגֽÙÙÙ»־־־¥ְְְ¬¬¬lH         
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               ÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿנÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿאÿÿÿÿÿÿÿÿÿÿÿÿÿÿ€?ÿÿÿÿÿÿÿÿÿÿÿÿÿÿ ?ÿÿÿÿÿÿÿÿÿÿÿÿÿü ?ÿÿÿÿÿÿÿÿÿÿÿÿÿר ?ÿÿÿÿÿÿÿÿÿÿÿÿÿנ ?ÿÿÿÿÿÿÿÿÿÿÿÿÿְ ?ÿÿÿÿÿÿÿÿÿÿÿÿÿ€ ?ÿÿÿÿÿÿÿÿÿÿÿÿÿ  ?ÿÿÿÿÿÿÿÿÿÿÿÿü  ?ÿÿÿÿÿÿÿÿÿÿÿÿנ  ?ÿÿÿÿÿÿÿÿÿÿר    ?ÿÿÿÿÿÿÿÿÿÿ     ?ÿÿÿÿÿÿÿÿÿנ     ?ÿÿÿÿÿÿÿÿÿ€     ?ÿÿÿÿÿÿÿÿü      ÿÿÿÿÿÿÿÿנ      ÿÿÿÿÿÿÿÿְ      ÿÿÿÿÿÿÿÿ        ÿÿÿÿÿÿü        ÿÿÿÿÿÿר        ÿÿÿÿÿÿא        ÿÿÿÿÿÿְ        ÿÿÿÿÿÿ          ÿÿÿÿ‏          ?ÿÿÿÿü          ÿÿÿÿר          ÿÿÿÿנ          ÿÿÿÿא          ÿÿÿÿְ          ÿÿÿÿ€           ÿÿÿÿ            ÿÿ‏            ?ÿÿü            ÿÿü            ÿÿר            ÿÿנ            ÿÿנ            ÿÿא            ÿÿא            ÿÿְ            ÿÿְ            ÿÿ€             ÿÿ€             ÿÿ              ÿ              ÿ              ‏              ?‏              ?‏              ?ü              ü              ü              ü              ü              ü              ü              ü              ü              ü              ü              ü              ü              ü              ü              ü              ü              ü              ü              ‏              ?‏              ?‏              ?ÿ              ÿ              ÿ              ÿ€             ÿÿ€             ÿÿְ            ÿÿְ            ÿÿְ            ÿÿא            ÿÿא            ÿÿנ            ÿÿנ            ÿÿר            ÿÿר            ÿÿü            ÿÿ‏            ?ÿÿÿ            ÿÿÿ            ÿÿÿ€           ÿÿÿÿְ          ÿÿÿÿא          ÿÿÿÿנ          ÿÿÿÿר          ÿÿÿÿü          ÿÿÿÿ‏          ?ÿÿÿÿÿ          ÿÿÿÿÿ€         ÿÿÿÿÿÿא        ÿÿÿÿÿÿנ        ÿÿÿÿÿÿü        ÿÿÿÿÿÿÿ        ÿÿÿÿÿÿÿ€       ÿÿÿÿÿÿÿÿא      ÿÿÿÿÿÿÿÿü      ÿÿÿÿÿÿÿÿÿ      ÿÿÿÿÿÿÿÿÿא    ÿÿÿÿÿÿÿÿÿÿü    ÿÿÿÿÿÿÿÿÿÿÿְ  ÿÿÿÿÿÿÿÿÿÿÿÿÿ  ÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿ/*! jQuery v1.10.2 | (c) 2005, 2013 jQuery Foundation, Inc. | jquery.org/license
//@ sourceMappingURL=jquery-1.10.2.min.map
*/
(function(e,t){var n,r,i=typeof t,o=e.location,a=e.document,s=a.documentElement,l=e.jQuery,u=e.$,c={},p=[],f="1.10.2",d=p.concat,h=p.push,g=p.slice,m=p.indexOf,y=c.toString,v=c.hasOwnProperty,b=f.trim,x=function(e,t){return new x.fn.init(e,t,r)},w=/[+-]?(?:\d*\.|)\d+(?:[eE][+-]?\d+|)/.source,T=/\S+/g,C=/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g,N=/^(?:\s*(<[\w\W]+>)[^>]*|#([\w-]*))$/,k=/^<(\w+)\s*\/?>(?:<\/\1>|)$/,E=/^[\],:{}\s]*$/,S=/(?:^|:|,)(?:\s*\[)+/g,A=/\\(?:["\\\/bfnrt]|u[\da-fA-F]{4})/g,j=/"[^"\\\r\n]*"|true|false|null|-?(?:\d+\.|)\d+(?:[eE][+-]?\d+|)/g,D=/^-ms-/,L=/-([\da-z])/gi,H=function(e,t){return t.toUpperCase()},q=function(e){(a.addEventListener||"load"===e.type||"complete"===a.readyState)&&(_(),x.ready())},_=function(){a.addEventListener?(a.removeEventListener("DOMContentLoaded",q,!1),e.removeEventListener("load",q,!1)):(a.detachEvent("onreadystatechange",q),e.detachEvent("onload",q))};x.fn=x.prototype={jquery:f,constructor:x,init:function(e,n,r){var i,o;if(!e)return this;if("string"==typeof e){if(i="<"===e.charAt(0)&&">"===e.charAt(e.length-1)&&e.length>=3?[null,e,null]:N.exec(e),!i||!i[1]&&n)return!n||n.jquery?(n||r).find(e):this.constructor(n).find(e);if(i[1]){if(n=n instanceof x?n[0]:n,x.merge(this,x.parseHTML(i[1],n&&n.nodeType?n.ownerDocument||n:a,!0)),k.test(i[1])&&x.isPlainObject(n))for(i in n)x.isFunction(this[i])?this[i](n[i]):this.attr(i,n[i]);return this}if(o=a.getElementById(i[2]),o&&o.parentNode){if(o.id!==i[2])return r.find(e);this.length=1,this[0]=o}return this.context=a,this.selector=e,this}return e.nodeType?(this.context=this[0]=e,this.length=1,this):x.isFunction(e)?r.ready(e):(e.selector!==t&&(this.selector=e.selector,this.context=e.context),x.makeArray(e,this))},selector:"",length:0,toArray:function(){return g.call(this)},get:function(e){return null==e?this.toArray():0>e?this[this.length+e]:this[e]},pushStack:function(e){var t=x.merge(this.constructor(),e);return t.prevObject=this,t.context=this.context,t},each:function(e,t){return x.each(this,e,t)},ready:function(e){return x.ready.promise().done(e),this},slice:function(){return this.pushStack(g.apply(this,arguments))},first:function(){return this.eq(0)},last:function(){return this.eq(-1)},eq:function(e){var t=this.length,n=+e+(0>e?t:0);return this.pushStack(n>=0&&t>n?[this[n]]:[])},map:function(e){return this.pushStack(x.map(this,function(t,n){return e.call(t,n,t)}))},end:function(){return this.prevObject||this.constructor(null)},push:h,sort:[].sort,splice:[].splice},x.fn.init.prototype=x.fn,x.extend=x.fn.extend=function(){var e,n,r,i,o,a,s=arguments[0]||{},l=1,u=arguments.length,c=!1;for("boolean"==typeof s&&(c=s,s=arguments[1]||{},l=2),"object"==typeof s||x.isFunction(s)||(s={}),u===l&&(s=this,--l);u>l;l++)if(null!=(o=arguments[l]))for(i in o)e=s[i],r=o[i],s!==r&&(c&&r&&(x.isPlainObject(r)||(n=x.isArray(r)))?(n?(n=!1,a=e&&x.isArray(e)?e:[]):a=e&&x.isPlainObject(e)?e:{},s[i]=x.extend(c,a,r)):r!==t&&(s[i]=r));return s},x.extend({expando:"jQuery"+(f+Math.random()).replace(/\D/g,""),noConflict:function(t){return e.$===x&&(e.$=u),t&&e.jQuery===x&&(e.jQuery=l),x},isReady:!1,readyWait:1,holdReady:function(e){e?x.readyWait++:x.ready(!0)},ready:function(e){if(e===!0?!--x.readyWait:!x.isReady){if(!a.body)return setTimeout(x.ready);x.isReady=!0,e!==!0&&--x.readyWait>0||(n.resolveWith(a,[x]),x.fn.trigger&&x(a).trigger("ready").off("ready"))}},isFunction:function(e){return"function"===x.type(e)},isArray:Array.isArray||function(e){return"array"===x.type(e)},isWindow:function(e){return null!=e&&e==e.window},isNumeric:function(e){return!isNaN(parseFloat(e))&&isFinite(e)},type:function(e){return null==e?e+"":"object"==typeof e||"function"==typeof e?c[y.call(e)]||"object":typeof e},isPlainObject:function(e){var n;if(!e||"object"!==x.type(e)||e.nodeType||x.isWindow(e))return!1;try{if(e.constructor&&!v.call(e,"constructor")&&!v.call(e.constructor.prototype,"isPrototypeOf"))return!1}catch(r){return!1}if(x.support.ownLast)for(n in e)return v.call(e,n);for(n in e);return n===t||v.call(e,n)},isEmptyObject:function(e){var t;for(t in e)return!1;return!0},error:function(e){throw Error(e)},parseHTML:function(e,t,n){if(!e||"string"!=typeof e)return null;"boolean"==typeof t&&(n=t,t=!1),t=t||a;var r=k.exec(e),i=!n&&[];return r?[t.createElement(r[1])]:(r=x.buildFragment([e],t,i),i&&x(i).remove(),x.merge([],r.childNodes))},parseJSON:function(n){return e.JSON&&e.JSON.parse?e.JSON.parse(n):null===n?n:"string"==typeof n&&(n=x.trim(n),n&&E.test(n.replace(A,"@").replace(j,"]").replace(S,"")))?Function("return "+n)():(x.error("Invalid JSON: "+n),t)},parseXML:function(n){var r,i;if(!n||"string"!=typeof n)return null;try{e.DOMParser?(i=new DOMParser,r=i.parseFromString(n,"text/xml")):(r=new ActiveXObject("Microsoft.XMLDOM"),r.async="false",r.loadXML(n))}catch(o){r=t}return r&&r.documentElement&&!r.getElementsByTagName("parsererror").length||x.error("Invalid XML: "+n),r},noop:function(){},globalEval:function(t){t&&x.trim(t)&&(e.execScript||function(t){e.eval.call(e,t)})(t)},camelCase:function(e){return e.replace(D,"ms-").replace(L,H)},nodeName:function(e,t){return e.nodeName&&e.nodeName.toLowerCase()===t.toLowerCase()},each:function(e,t,n){var r,i=0,o=e.length,a=M(e);if(n){if(a){for(;o>i;i++)if(r=t.apply(e[i],n),r===!1)break}else for(i in e)if(r=t.apply(e[i],n),r===!1)break}else if(a){for(;o>i;i++)if(r=t.call(e[i],i,e[i]),r===!1)break}else for(i in e)if(r=t.call(e[i],i,e[i]),r===!1)break;return e},trim:b&&!b.call("\ufeff\u00a0")?function(e){return null==e?"":b.call(e)}:function(e){return null==e?"":(e+"").replace(C,"")},makeArray:function(e,t){var n=t||[];return null!=e&&(M(Object(e))?x.merge(n,"string"==typeof e?[e]:e):h.call(n,e)),n},inArray:function(e,t,n){var r;if(t){if(m)return m.call(t,e,n);for(r=t.length,n=n?0>n?Math.max(0,r+n):n:0;r>n;n++)if(n in t&&t[n]===e)return n}return-1},merge:function(e,n){var r=n.length,i=e.length,o=0;if("number"==typeof r)for(;r>o;o++)e[i++]=n[o];else while(n[o]!==t)e[i++]=n[o++];return e.length=i,e},grep:function(e,t,n){var r,i=[],o=0,a=e.length;for(n=!!n;a>o;o++)r=!!t(e[o],o),n!==r&&i.push(e[o]);return i},map:function(e,t,n){var r,i=0,o=e.length,a=M(e),s=[];if(a)for(;o>i;i++)r=t(e[i],i,n),null!=r&&(s[s.length]=r);else for(i in e)r=t(e[i],i,n),null!=r&&(s[s.length]=r);return d.apply([],s)},guid:1,proxy:function(e,n){var r,i,o;return"string"==typeof n&&(o=e[n],n=e,e=o),x.isFunction(e)?(r=g.call(arguments,2),i=function(){return e.apply(n||this,r.concat(g.call(arguments)))},i.guid=e.guid=e.guid||x.guid++,i):t},access:function(e,n,r,i,o,a,s){var l=0,u=e.length,c=null==r;if("object"===x.type(r)){o=!0;for(l in r)x.access(e,n,l,r[l],!0,a,s)}else if(i!==t&&(o=!0,x.isFunction(i)||(s=!0),c&&(s?(n.call(e,i),n=null):(c=n,n=function(e,t,n){return c.call(x(e),n)})),n))for(;u>l;l++)n(e[l],r,s?i:i.call(e[l],l,n(e[l],r)));return o?e:c?n.call(e):u?n(e[0],r):a},now:function(){return(new Date).getTime()},swap:function(e,t,n,r){var i,o,a={};for(o in t)a[o]=e.style[o],e.style[o]=t[o];i=n.apply(e,r||[]);for(o in t)e.style[o]=a[o];return i}}),x.ready.promise=function(t){if(!n)if(n=x.Deferred(),"complete"===a.readyState)setTimeout(x.ready);else if(a.addEventListener)a.addEventListener("DOMContentLoaded",q,!1),e.addEventListener("load",q,!1);else{a.attachEvent("onreadystatechange",q),e.attachEvent("onload",q);var r=!1;try{r=null==e.frameElement&&a.documentElement}catch(i){}r&&r.doScroll&&function o(){if(!x.isReady){try{r.doScroll("left")}catch(e){return setTimeout(o,50)}_(),x.ready()}}()}return n.promise(t)},x.each("Boolean Number String Function Array Date RegExp Object Error".split(" "),function(e,t){c["[object "+t+"]"]=t.toLowerCase()});function M(e){var t=e.length,n=x.type(e);return x.isWindow(e)?!1:1===e.nodeType&&t?!0:"array"===n||"function"!==n&&(0===t||"number"==typeof t&&t>0&&t-1 in e)}r=x(a),function(e,t){var n,r,i,o,a,s,l,u,c,p,f,d,h,g,m,y,v,b="sizzle"+-new Date,w=e.document,T=0,C=0,N=st(),k=st(),E=st(),S=!1,A=function(e,t){return e===t?(S=!0,0):0},j=typeof t,D=1<<31,L={}.hasOwnProperty,H=[],q=H.pop,_=H.push,M=H.push,O=H.slice,F=H.indexOf||function(e){var t=0,n=this.length;for(;n>t;t++)if(this[t]===e)return t;return-1},B="checked|selected|async|autofocus|autoplay|controls|defer|disabled|hidden|ismap|loop|multiple|open|readonly|required|scoped",P="[\\x20\\t\\r\\n\\f]",R="(?:\\\\.|[\\w-]|[^\\x00-\\xa0])+",W=R.replace("w","w#"),$="\\["+P+"*("+R+")"+P+"*(?:([*^$|!~]?=)"+P+"*(?:(['\"])((?:\\\\.|[^\\\\])*?)\\3|("+W+")|)|)"+P+"*\\]",I=":("+R+")(?:\\(((['\"])((?:\\\\.|[^\\\\])*?)\\3|((?:\\\\.|[^\\\\()[\\]]|"+$.replace(3,8)+")*)|.*)\\)|)",z=RegExp("^"+P+"+|((?:^|[^\\\\])(?:\\\\.)*)"+P+"+$","g"),X=RegExp("^"+P+"*,"+P+"*"),U=RegExp("^"+P+"*([>+~]|"+P+")"+P+"*"),V=RegExp(P+"*[+~]"),Y=RegExp("="+P+"*([^\\]'\"]*)"+P+"*\\]","g"),J=RegExp(I),G=RegExp("^"+W+"$"),Q={ID:RegExp("^#("+R+")"),CLASS:RegExp("^\\.("+R+")"),TAG:RegExp("^("+R.replace("w","w*")+")"),ATTR:RegExp("^"+$),PSEUDO:RegExp("^"+I),CHILD:RegExp("^:(only|first|last|nth|nth-last)-(child|of-type)(?:\\("+P+"*(even|odd|(([+-]|)(\\d*)n|)"+P+"*(?:([+-]|)"+P+"*(\\d+)|))"+P+"*\\)|)","i"),bool:RegExp("^(?:"+B+")$","i"),needsContext:RegExp("^"+P+"*[>+~]|:(even|odd|eq|gt|lt|nth|first|last)(?:\\("+P+"*((?:-\\d)?\\d*)"+P+"*\\)|)(?=[^-]|$)","i")},K=/^[^{]+\{\s*\[native \w/,Z=/^(?:#([\w-]+)|(\w+)|\.([\w-]+))$/,et=/^(?:input|select|textarea|button)$/i,tt=/^h\d$/i,nt=/'|\\/g,rt=RegExp("\\\\([\\da-f]{1,6}"+P+"?|("+P+")|.)","ig"),it=function(e,t,n){var r="0x"+t-65536;return r!==r||n?t:0>r?String.fromCharCode(r+65536):String.fromCharCode(55296|r>>10,56320|1023&r)};try{M.apply(H=O.call(w.childNodes),w.childNodes),H[w.childNodes.length].nodeType}catch(ot){M={apply:H.length?function(e,t){_.apply(e,O.call(t))}:function(e,t){var n=e.length,r=0;while(e[n++]=t[r++]);e.length=n-1}}}function at(e,t,n,i){var o,a,s,l,u,c,d,m,y,x;if((t?t.ownerDocument||t:w)!==f&&p(t),t=t||f,n=n||[],!e||"string"!=typeof e)return n;if(1!==(l=t.nodeType)&&9!==l)return[];if(h&&!i){if(o=Z.exec(e))if(s=o[1]){if(9===l){if(a=t.getElementById(s),!a||!a.parentNode)return n;if(a.id===s)return n.push(a),n}else if(t.ownerDocument&&(a=t.ownerDocument.getElementById(s))&&v(t,a)&&a.id===s)return n.push(a),n}else{if(o[2])return M.apply(n,t.getElementsByTagName(e)),n;if((s=o[3])&&r.getElementsByClassName&&t.getElementsByClassName)return M.apply(n,t.getElementsByClassName(s)),n}if(r.qsa&&(!g||!g.test(e))){if(m=d=b,y=t,x=9===l&&e,1===l&&"object"!==t.nodeName.toLowerCase()){c=mt(e),(d=t.getAttribute("id"))?m=d.replace(nt,"\\$&"):t.setAttribute("id",m),m="[id='"+m+"'] ",u=c.length;while(u--)c[u]=m+yt(c[u]);y=V.test(e)&&t.parentNode||t,x=c.join(",")}if(x)try{return M.apply(n,y.querySelectorAll(x)),n}catch(T){}finally{d||t.removeAttribute("id")}}}return kt(e.replace(z,"$1"),t,n,i)}function st(){var e=[];function t(n,r){return e.push(n+=" ")>o.cacheLength&&delete t[e.shift()],t[n]=r}return t}function lt(e){return e[b]=!0,e}function ut(e){var t=f.createElement("div");try{return!!e(t)}catch(n){return!1}finally{t.parentNode&&t.parentNode.removeChild(t),t=null}}function ct(e,t){var n=e.split("|"),r=e.length;while(r--)o.attrHandle[n[r]]=t}function pt(e,t){var n=t&&e,r=n&&1===e.nodeType&&1===t.nodeType&&(~t.sourceIndex||D)-(~e.sourceIndex||D);if(r)return r;if(n)while(n=n.nextSibling)if(n===t)return-1;return e?1:-1}function ft(e){return function(t){var n=t.nodeName.toLowerCase();return"input"===n&&t.type===e}}function dt(e){return function(t){var n=t.nodeName.toLowerCase();return("input"===n||"button"===n)&&t.type===e}}function ht(e){return lt(function(t){return t=+t,lt(function(n,r){var i,o=e([],n.length,t),a=o.length;while(a--)n[i=o[a]]&&(n[i]=!(r[i]=n[i]))})})}s=at.isXML=function(e){var t=e&&(e.ownerDocument||e).documentElement;return t?"HTML"!==t.nodeName:!1},r=at.support={},p=at.setDocument=function(e){var n=e?e.ownerDocument||e:w,i=n.defaultView;return n!==f&&9===n.nodeType&&n.documentElement?(f=n,d=n.documentElement,h=!s(n),i&&i.attachEvent&&i!==i.top&&i.attachEvent("onbeforeunload",function(){p()}),r.attributes=ut(function(e){return e.className="i",!e.getAttribute("className")}),r.getElementsByTagName=ut(function(e){return e.appendChild(n.createComment("")),!e.getElementsByTagName("*").length}),r.getElementsByClassName=ut(function(e){return e.innerHTML="<div class='a'></div><div class='a i'></div>",e.firstChild.className="i",2===e.getElementsByClassName("i").length}),r.getById=ut(function(e){return d.appendChild(e).id=b,!n.getElementsByName||!n.getElementsByName(b).length}),r.getById?(o.find.ID=function(e,t){if(typeof t.getElementById!==j&&h){var n=t.getElementById(e);return n&&n.parentNode?[n]:[]}},o.filter.ID=function(e){var t=e.replace(rt,it);return function(e){return e.getAttribute("id")===t}}):(delete o.find.ID,o.filter.ID=function(e){var t=e.replace(rt,it);return function(e){var n=typeof e.getAttributeNode!==j&&e.getAttributeNode("id");return n&&n.value===t}}),o.find.TAG=r.getElementsByTagName?function(e,n){return typeof n.getElementsByTagName!==j?n.getElementsByTagName(e):t}:function(e,t){var n,r=[],i=0,o=t.getElementsByTagName(e);if("*"===e){while(n=o[i++])1===n.nodeType&&r.push(n);return r}return o},o.find.CLASS=r.getElementsByClassName&&function(e,n){return typeof n.getElementsByClassName!==j&&h?n.getElementsByClassName(e):t},m=[],g=[],(r.qsa=K.test(n.querySelectorAll))&&(ut(function(e){e.innerHTML="<select><option selected=''></option></select>",e.querySelectorAll("[selected]").length||g.push("\\["+P+"*(?:value|"+B+")"),e.querySelectorAll(":checked").length||g.push(":checked")}),ut(function(e){var t=n.createElement("input");t.setAttribute("type","hidden"),e.appendChild(t).setAttribute("t",""),e.querySelectorAll("[t^='']").length&&g.push("[*^$]="+P+"*(?:''|\"\")"),e.querySelectorAll(":enabled").length||g.push(":enabled",":disabled"),e.querySelectorAll("*,:x"),g.push(",.*:")})),(r.matchesSelector=K.test(y=d.webkitMatchesSelector||d.mozMatchesSelector||d.oMatchesSelector||d.msMatchesSelector))&&ut(function(e){r.disconnectedMatch=y.call(e,"div"),y.call(e,"[s!='']:x"),m.push("!=",I)}),g=g.length&&RegExp(g.join("|")),m=m.length&&RegExp(m.join("|")),v=K.test(d.contains)||d.compareDocumentPosition?function(e,t){var n=9===e.nodeType?e.documentElement:e,r=t&&t.parentNode;return e===r||!(!r||1!==r.nodeType||!(n.contains?n.contains(r):e.compareDocumentPosition&&16&e.compareDocumentPosition(r)))}:function(e,t){if(t)while(t=t.parentNode)if(t===e)return!0;return!1},A=d.compareDocumentPosition?function(e,t){if(e===t)return S=!0,0;var i=t.compareDocumentPosition&&e.compareDocumentPosition&&e.compareDocumentPosition(t);return i?1&i||!r.sortDetached&&t.compareDocumentPosition(e)===i?e===n||v(w,e)?-1:t===n||v(w,t)?1:c?F.call(c,e)-F.call(c,t):0:4&i?-1:1:e.compareDocumentPosition?-1:1}:function(e,t){var r,i=0,o=e.parentNode,a=t.parentNode,s=[e],l=[t];if(e===t)return S=!0,0;if(!o||!a)return e===n?-1:t===n?1:o?-1:a?1:c?F.call(c,e)-F.call(c,t):0;if(o===a)return pt(e,t);r=e;while(r=r.parentNode)s.unshift(r);r=t;while(r=r.parentNode)l.unshift(r);while(s[i]===l[i])i++;return i?pt(s[i],l[i]):s[i]===w?-1:l[i]===w?1:0},n):f},at.matches=function(e,t){return at(e,null,null,t)},at.matchesSelector=function(e,t){if((e.ownerDocument||e)!==f&&p(e),t=t.replace(Y,"='$1']"),!(!r.matchesSelector||!h||m&&m.test(t)||g&&g.test(t)))try{var n=y.call(e,t);if(n||r.disconnectedMatch||e.document&&11!==e.document.nodeType)return n}catch(i){}return at(t,f,null,[e]).length>0},at.contains=function(e,t){return(e.ownerDocument||e)!==f&&p(e),v(e,t)},at.attr=function(e,n){(e.ownerDocument||e)!==f&&p(e);var i=o.attrHandle[n.toLowerCase()],a=i&&L.call(o.attrHandle,n.toLowerCase())?i(e,n,!h):t;return a===t?r.attributes||!h?e.getAttribute(n):(a=e.getAttributeNode(n))&&a.specified?a.value:null:a},at.error=function(e){throw Error("Syntax error, unrecognized expression: "+e)},at.uniqueSort=function(e){var t,n=[],i=0,o=0;if(S=!r.detectDuplicates,c=!r.sortStable&&e.slice(0),e.sort(A),S){while(t=e[o++])t===e[o]&&(i=n.push(o));while(i--)e.splice(n[i],1)}return e},a=at.getText=function(e){var t,n="",r=0,i=e.nodeType;if(i){if(1===i||9===i||11===i){if("string"==typeof e.textContent)return e.textContent;for(e=e.firstChild;e;e=e.nextSibling)n+=a(e)}else if(3===i||4===i)return e.nodeValue}else for(;t=e[r];r++)n+=a(t);return n},o=at.selectors={cacheLength:50,createPseudo:lt,match:Q,attrHandle:{},find:{},relative:{">":{dir:"parentNode",first:!0}," ":{dir:"parentNode"},"+":{dir:"previousSibling",first:!0},"~":{dir:"previousSibling"}},preFilter:{ATTR:function(e){return e[1]=e[1].replace(rt,it),e[3]=(e[4]||e[5]||"").replace(rt,it),"~="===e[2]&&(e[3]=" "+e[3]+" "),e.slice(0,4)},CHILD:function(e){return e[1]=e[1].toLowerCase(),"nth"===e[1].slice(0,3)?(e[3]||at.error(e[0]),e[4]=+(e[4]?e[5]+(e[6]||1):2*("even"===e[3]||"odd"===e[3])),e[5]=+(e[7]+e[8]||"odd"===e[3])):e[3]&&at.error(e[0]),e},PSEUDO:function(e){var n,r=!e[5]&&e[2];return Q.CHILD.test(e[0])?null:(e[3]&&e[4]!==t?e[2]=e[4]:r&&J.test(r)&&(n=mt(r,!0))&&(n=r.indexOf(")",r.length-n)-r.length)&&(e[0]=e[0].slice(0,n),e[2]=r.slice(0,n)),e.slice(0,3))}},filter:{TAG:function(e){var t=e.replace(rt,it).toLowerCase();return"*"===e?function(){return!0}:function(e){return e.nodeName&&e.nodeName.toLowerCase()===t}},CLASS:function(e){var t=N[e+" "];return t||(t=RegExp("(^|"+P+")"+e+"("+P+"|$)"))&&N(e,function(e){return t.test("string"==typeof e.className&&e.className||typeof e.getAttribute!==j&&e.getAttribute("class")||"")})},ATTR:function(e,t,n){return function(r){var i=at.attr(r,e);return null==i?"!="===t:t?(i+="","="===t?i===n:"!="===t?i!==n:"^="===t?n&&0===i.indexOf(n):"*="===t?n&&i.indexOf(n)>-1:"$="===t?n&&i.slice(-n.length)===n:"~="===t?(" "+i+" ").indexOf(n)>-1:"|="===t?i===n||i.slice(0,n.length+1)===n+"-":!1):!0}},CHILD:function(e,t,n,r,i){var o="nth"!==e.slice(0,3),a="last"!==e.slice(-4),s="of-type"===t;return 1===r&&0===i?function(e){return!!e.parentNode}:function(t,n,l){var u,c,p,f,d,h,g=o!==a?"nextSibling":"previousSibling",m=t.parentNode,y=s&&t.nodeName.toLowerCase(),v=!l&&!s;if(m){if(o){while(g){p=t;while(p=p[g])if(s?p.nodeName.toLowerCase()===y:1===p.nodeType)return!1;h=g="only"===e&&!h&&"nextSibling"}return!0}if(h=[a?m.firstChild:m.lastChild],a&&v){c=m[b]||(m[b]={}),u=c[e]||[],d=u[0]===T&&u[1],f=u[0]===T&&u[2],p=d&&m.childNodes[d];while(p=++d&&p&&p[g]||(f=d=0)||h.pop())if(1===p.nodeType&&++f&&p===t){c[e]=[T,d,f];break}}else if(v&&(u=(t[b]||(t[b]={}))[e])&&u[0]===T)f=u[1];else while(p=++d&&p&&p[g]||(f=d=0)||h.pop())if((s?p.nodeName.toLowerCase()===y:1===p.nodeType)&&++f&&(v&&((p[b]||(p[b]={}))[e]=[T,f]),p===t))break;return f-=i,f===r||0===f%r&&f/r>=0}}},PSEUDO:function(e,t){var n,r=o.pseudos[e]||o.setFilters[e.toLowerCase()]||at.error("unsupported pseudo: "+e);return r[b]?r(t):r.length>1?(n=[e,e,"",t],o.setFilters.hasOwnProperty(e.toLowerCase())?lt(function(e,n){var i,o=r(e,t),a=o.length;while(a--)i=F.call(e,o[a]),e[i]=!(n[i]=o[a])}):function(e){return r(e,0,n)}):r}},pseudos:{not:lt(function(e){var t=[],n=[],r=l(e.replace(z,"$1"));return r[b]?lt(function(e,t,n,i){var o,a=r(e,null,i,[]),s=e.length;while(s--)(o=a[s])&&(e[s]=!(t[s]=o))}):function(e,i,o){return t[0]=e,r(t,null,o,n),!n.pop()}}),has:lt(function(e){return function(t){return at(e,t).length>0}}),contains:lt(function(e){return function(t){return(t.textContent||t.innerText||a(t)).indexOf(e)>-1}}),lang:lt(function(e){return G.test(e||"")||at.error("unsupported lang: "+e),e=e.replace(rt,it).toLowerCase(),function(t){var n;do if(n=h?t.lang:t.getAttribute("xml:lang")||t.getAttribute("lang"))return n=n.toLowerCase(),n===e||0===n.indexOf(e+"-");while((t=t.parentNode)&&1===t.nodeType);return!1}}),target:function(t){var n=e.location&&e.location.hash;return n&&n.slice(1)===t.id},root:function(e){return e===d},focus:function(e){return e===f.activeElement&&(!f.hasFocus||f.hasFocus())&&!!(e.type||e.href||~e.tabIndex)},enabled:function(e){return e.disabled===!1},disabled:function(e){return e.disabled===!0},checked:function(e){var t=e.nodeName.toLowerCase();return"input"===t&&!!e.checked||"option"===t&&!!e.selected},selected:function(e){return e.parentNode&&e.parentNode.selectedIndex,e.selected===!0},empty:function(e){for(e=e.firstChild;e;e=e.nextSibling)if(e.nodeName>"@"||3===e.nodeType||4===e.nodeType)return!1;return!0},parent:function(e){return!o.pseudos.empty(e)},header:function(e){return tt.test(e.nodeName)},input:function(e){return et.test(e.nodeName)},button:function(e){var t=e.nodeName.toLowerCase();return"input"===t&&"button"===e.type||"button"===t},text:function(e){var t;return"input"===e.nodeName.toLowerCase()&&"text"===e.type&&(null==(t=e.getAttribute("type"))||t.toLowerCase()===e.type)},first:ht(function(){return[0]}),last:ht(function(e,t){return[t-1]}),eq:ht(function(e,t,n){return[0>n?n+t:n]}),even:ht(function(e,t){var n=0;for(;t>n;n+=2)e.push(n);return e}),odd:ht(function(e,t){var n=1;for(;t>n;n+=2)e.push(n);return e}),lt:ht(function(e,t,n){var r=0>n?n+t:n;for(;--r>=0;)e.push(r);return e}),gt:ht(function(e,t,n){var r=0>n?n+t:n;for(;t>++r;)e.push(r);return e})}},o.pseudos.nth=o.pseudos.eq;for(n in{radio:!0,checkbox:!0,file:!0,password:!0,image:!0})o.pseudos[n]=ft(n);for(n in{submit:!0,reset:!0})o.pseudos[n]=dt(n);function gt(){}gt.prototype=o.filters=o.pseudos,o.setFilters=new gt;function mt(e,t){var n,r,i,a,s,l,u,c=k[e+" "];if(c)return t?0:c.slice(0);s=e,l=[],u=o.preFilter;while(s){(!n||(r=X.exec(s)))&&(r&&(s=s.slice(r[0].length)||s),l.push(i=[])),n=!1,(r=U.exec(s))&&(n=r.shift(),i.push({value:n,type:r[0].replace(z," ")}),s=s.slice(n.length));for(a in o.filter)!(r=Q[a].exec(s))||u[a]&&!(r=u[a](r))||(n=r.shift(),i.push({value:n,type:a,matches:r}),s=s.slice(n.length));if(!n)break}return t?s.length:s?at.error(e):k(e,l).slice(0)}function yt(e){var t=0,n=e.length,r="";for(;n>t;t++)r+=e[t].value;return r}function vt(e,t,n){var r=t.dir,o=n&&"parentNode"===r,a=C++;return t.first?function(t,n,i){while(t=t[r])if(1===t.nodeType||o)return e(t,n,i)}:function(t,n,s){var l,u,c,p=T+" "+a;if(s){while(t=t[r])if((1===t.nodeType||o)&&e(t,n,s))return!0}else while(t=t[r])if(1===t.nodeType||o)if(c=t[b]||(t[b]={}),(u=c[r])&&u[0]===p){if((l=u[1])===!0||l===i)return l===!0}else if(u=c[r]=[p],u[1]=e(t,n,s)||i,u[1]===!0)return!0}}function bt(e){return e.length>1?function(t,n,r){var i=e.length;while(i--)if(!e[i](t,n,r))return!1;return!0}:e[0]}function xt(e,t,n,r,i){var o,a=[],s=0,l=e.length,u=null!=t;for(;l>s;s++)(o=e[s])&&(!n||n(o,r,i))&&(a.push(o),u&&t.push(s));return a}function wt(e,t,n,r,i,o){return r&&!r[b]&&(r=wt(r)),i&&!i[b]&&(i=wt(i,o)),lt(function(o,a,s,l){var u,c,p,f=[],d=[],h=a.length,g=o||Nt(t||"*",s.nodeType?[s]:s,[]),m=!e||!o&&t?g:xt(g,f,e,s,l),y=n?i||(o?e:h||r)?[]:a:m;if(n&&n(m,y,s,l),r){u=xt(y,d),r(u,[],s,l),c=u.length;while(c--)(p=u[c])&&(y[d[c]]=!(m[d[c]]=p))}if(o){if(i||e){if(i){u=[],c=y.length;while(c--)(p=y[c])&&u.push(m[c]=p);i(null,y=[],u,l)}c=y.length;while(c--)(p=y[c])&&(u=i?F.call(o,p):f[c])>-1&&(o[u]=!(a[u]=p))}}else y=xt(y===a?y.splice(h,y.length):y),i?i(null,a,y,l):M.apply(a,y)})}function Tt(e){var t,n,r,i=e.length,a=o.relative[e[0].type],s=a||o.relative[" "],l=a?1:0,c=vt(function(e){return e===t},s,!0),p=vt(function(e){return F.call(t,e)>-1},s,!0),f=[function(e,n,r){return!a&&(r||n!==u)||((t=n).nodeType?c(e,n,r):p(e,n,r))}];for(;i>l;l++)if(n=o.relative[e[l].type])f=[vt(bt(f),n)];else{if(n=o.filter[e[l].type].apply(null,e[l].matches),n[b]){for(r=++l;i>r;r++)if(o.relative[e[r].type])break;return wt(l>1&&bt(f),l>1&&yt(e.slice(0,l-1).concat({value:" "===e[l-2].type?"*":""})).replace(z,"$1"),n,r>l&&Tt(e.slice(l,r)),i>r&&Tt(e=e.slice(r)),i>r&&yt(e))}f.push(n)}return bt(f)}function Ct(e,t){var n=0,r=t.length>0,a=e.length>0,s=function(s,l,c,p,d){var h,g,m,y=[],v=0,b="0",x=s&&[],w=null!=d,C=u,N=s||a&&o.find.TAG("*",d&&l.parentNode||l),k=T+=null==C?1:Math.random()||.1;for(w&&(u=l!==f&&l,i=n);null!=(h=N[b]);b++){if(a&&h){g=0;while(m=e[g++])if(m(h,l,c)){p.push(h);break}w&&(T=k,i=++n)}r&&((h=!m&&h)&&v--,s&&x.push(h))}if(v+=b,r&&b!==v){g=0;while(m=t[g++])m(x,y,l,c);if(s){if(v>0)while(b--)x[b]||y[b]||(y[b]=q.call(p));y=xt(y)}M.apply(p,y),w&&!s&&y.length>0&&v+t.length>1&&at.uniqueSort(p)}return w&&(T=k,u=C),x};return r?lt(s):s}l=at.compile=function(e,t){var n,r=[],i=[],o=E[e+" "];if(!o){t||(t=mt(e)),n=t.length;while(n--)o=Tt(t[n]),o[b]?r.push(o):i.push(o);o=E(e,Ct(i,r))}return o};function Nt(e,t,n){var r=0,i=t.length;for(;i>r;r++)at(e,t[r],n);return n}function kt(e,t,n,i){var a,s,u,c,p,f=mt(e);if(!i&&1===f.length){if(s=f[0]=f[0].slice(0),s.length>2&&"ID"===(u=s[0]).type&&r.getById&&9===t.nodeType&&h&&o.relative[s[1].type]){if(t=(o.find.ID(u.matches[0].replace(rt,it),t)||[])[0],!t)return n;e=e.slice(s.shift().value.length)}a=Q.needsContext.test(e)?0:s.length;while(a--){if(u=s[a],o.relative[c=u.type])break;if((p=o.find[c])&&(i=p(u.matches[0].replace(rt,it),V.test(s[0].type)&&t.parentNode||t))){if(s.splice(a,1),e=i.length&&yt(s),!e)return M.apply(n,i),n;break}}}return l(e,f)(i,t,!h,n,V.test(e)),n}r.sortStable=b.split("").sort(A).join("")===b,r.detectDuplicates=S,p(),r.sortDetached=ut(function(e){return 1&e.compareDocumentPosition(f.createElement("div"))}),ut(function(e){return e.innerHTML="<a href='#'></a>","#"===e.firstChild.getAttribute("href")})||ct("type|href|height|width",function(e,n,r){return r?t:e.getAttribute(n,"type"===n.toLowerCase()?1:2)}),r.attributes&&ut(function(e){return e.innerHTML="<input/>",e.firstChild.setAttribute("value",""),""===e.firstChild.getAttribute("value")})||ct("value",function(e,n,r){return r||"input"!==e.nodeName.toLowerCase()?t:e.defaultValue}),ut(function(e){return null==e.getAttribute("disabled")})||ct(B,function(e,n,r){var i;return r?t:(i=e.getAttributeNode(n))&&i.specified?i.value:e[n]===!0?n.toLowerCase():null}),x.find=at,x.expr=at.selectors,x.expr[":"]=x.expr.pseudos,x.unique=at.uniqueSort,x.text=at.getText,x.isXMLDoc=at.isXML,x.contains=at.contains}(e);var O={};function F(e){var t=O[e]={};return x.each(e.match(T)||[],function(e,n){t[n]=!0}),t}x.Callbacks=function(e){e="string"==typeof e?O[e]||F(e):x.extend({},e);var n,r,i,o,a,s,l=[],u=!e.once&&[],c=function(t){for(r=e.memory&&t,i=!0,a=s||0,s=0,o=l.length,n=!0;l&&o>a;a++)if(l[a].apply(t[0],t[1])===!1&&e.stopOnFalse){r=!1;break}n=!1,l&&(u?u.length&&c(u.shift()):r?l=[]:p.disable())},p={add:function(){if(l){var t=l.length;(function i(t){x.each(t,function(t,n){var r=x.type(n);"function"===r?e.unique&&p.has(n)||l.push(n):n&&n.length&&"string"!==r&&i(n)})})(arguments),n?o=l.length:r&&(s=t,c(r))}return this},remove:function(){return l&&x.each(arguments,function(e,t){var r;while((r=x.inArray(t,l,r))>-1)l.splice(r,1),n&&(o>=r&&o--,a>=r&&a--)}),this},has:function(e){return e?x.inArray(e,l)>-1:!(!l||!l.length)},empty:function(){return l=[],o=0,this},disable:function(){return l=u=r=t,this},disabled:function(){return!l},lock:function(){return u=t,r||p.disable(),this},locked:function(){return!u},fireWith:function(e,t){return!l||i&&!u||(t=t||[],t=[e,t.slice?t.slice():t],n?u.push(t):c(t)),this},fire:function(){return p.fireWith(this,arguments),this},fired:function(){return!!i}};return p},x.extend({Deferred:function(e){var t=[["resolve","done",x.Callbacks("once memory"),"resolved"],["reject","fail",x.Callbacks("once memory"),"rejected"],["notify","progress",x.Callbacks("memory")]],n="pending",r={state:function(){return n},always:function(){return i.done(arguments).fail(arguments),this},then:function(){var e=arguments;return x.Deferred(function(n){x.each(t,function(t,o){var a=o[0],s=x.isFunction(e[t])&&e[t];i[o[1]](function(){var e=s&&s.apply(this,arguments);e&&x.isFunction(e.promise)?e.promise().done(n.resolve).fail(n.reject).progress(n.notify):n[a+"With"](this===r?n.promise():this,s?[e]:arguments)})}),e=null}).promise()},promise:function(e){return null!=e?x.extend(e,r):r}},i={};return r.pipe=r.then,x.each(t,function(e,o){var a=o[2],s=o[3];r[o[1]]=a.add,s&&a.add(function(){n=s},t[1^e][2].disable,t[2][2].lock),i[o[0]]=function(){return i[o[0]+"With"](this===i?r:this,arguments),this},i[o[0]+"With"]=a.fireWith}),r.promise(i),e&&e.call(i,i),i},when:function(e){var t=0,n=g.call(arguments),r=n.length,i=1!==r||e&&x.isFunction(e.promise)?r:0,o=1===i?e:x.Deferred(),a=function(e,t,n){return function(r){t[e]=this,n[e]=arguments.length>1?g.call(arguments):r,n===s?o.notifyWith(t,n):--i||o.resolveWith(t,n)}},s,l,u;if(r>1)for(s=Array(r),l=Array(r),u=Array(r);r>t;t++)n[t]&&x.isFunction(n[t].promise)?n[t].promise().done(a(t,u,n)).fail(o.reject).progress(a(t,l,s)):--i;return i||o.resolveWith(u,n),o.promise()}}),x.support=function(t){var n,r,o,s,l,u,c,p,f,d=a.createElement("div");if(d.setAttribute("className","t"),d.innerHTML="  <link/><table></table><a href='/a'>a</a><input type='checkbox'/>",n=d.getElementsByTagName("*")||[],r=d.getElementsByTagName("a")[0],!r||!r.style||!n.length)return t;s=a.createElement("select"),u=s.appendChild(a.createElement("option")),o=d.getElementsByTagName("input")[0],r.style.cssText="top:1px;float:left;opacity:.5",t.getSetAttribute="t"!==d.className,t.leadingWhitespace=3===d.firstChild.nodeType,t.tbody=!d.getElementsByTagName("tbody").length,t.htmlSerialize=!!d.getElementsByTagName("link").length,t.style=/top/.test(r.getAttribute("style")),t.hrefNormalized="/a"===r.getAttribute("href"),t.opacity=/^0.5/.test(r.style.opacity),t.cssFloat=!!r.style.cssFloat,t.checkOn=!!o.value,t.optSelected=u.selected,t.enctype=!!a.createElement("form").enctype,t.html5Clone="<:nav></:nav>"!==a.createElement("nav").cloneNode(!0).outerHTML,t.inlineBlockNeedsLayout=!1,t.shrinkWrapBlocks=!1,t.pixelPosition=!1,t.deleteExpando=!0,t.noCloneEvent=!0,t.reliableMarginRight=!0,t.boxSizingReliable=!0,o.checked=!0,t.noCloneChecked=o.cloneNode(!0).checked,s.disabled=!0,t.optDisabled=!u.disabled;try{delete d.test}catch(h){t.deleteExpando=!1}o=a.createElement("input"),o.setAttribute("value",""),t.input=""===o.getAttribute("value"),o.value="t",o.setAttribute("type","radio"),t.radioValue="t"===o.value,o.setAttribute("checked","t"),o.setAttribute("name","t"),l=a.createDocumentFragment(),l.appendChild(o),t.appendChecked=o.checked,t.checkClone=l.cloneNode(!0).cloneNode(!0).lastChild.checked,d.attachEvent&&(d.attachEvent("onclick",function(){t.noCloneEvent=!1}),d.cloneNode(!0).click());for(f in{submit:!0,change:!0,focusin:!0})d.setAttribute(c="on"+f,"t"),t[f+"Bubbles"]=c in e||d.attributes[c].expando===!1;d.style.backgroundClip="content-box",d.cloneNode(!0).style.backgroundClip="",t.clearCloneStyle="content-box"===d.style.backgroundClip;for(f in x(t))break;return t.ownLast="0"!==f,x(function(){var n,r,o,s="padding:0;margin:0;border:0;display:block;box-sizing:content-box;-moz-box-sizing:content-box;-webkit-box-sizing:content-box;",l=a.getElementsByTagName("body")[0];l&&(n=a.createElement("div"),n.style.cssText="border:0;width:0;height:0;position:absolute;top:0;left:-9999px;margin-top:1px",l.appendChild(n).appendChild(d),d.innerHTML="<table><tr><td></td><td>t</td></tr></table>",o=d.getElementsByTagName("td"),o[0].style.cssText="padding:0;margin:0;border:0;display:none",p=0===o[0].offsetHeight,o[0].style.display="",o[1].style.display="none",t.reliableHiddenOffsets=p&&0===o[0].offsetHeight,d.innerHTML="",d.style.cssText="box-sizing:border-box;-moz-box-sizing:border-box;-webkit-box-sizing:border-box;padding:1px;border:1px;display:block;width:4px;margin-top:1%;position:absolute;top:1%;",x.swap(l,null!=l.style.zoom?{zoom:1}:{},function(){t.boxSizing=4===d.offsetWidth}),e.getComputedStyle&&(t.pixelPosition="1%"!==(e.getComputedStyle(d,null)||{}).top,t.boxSizingReliable="4px"===(e.getComputedStyle(d,null)||{width:"4px"}).width,r=d.appendChild(a.createElement("div")),r.style.cssText=d.style.cssText=s,r.style.marginRight=r.style.width="0",d.style.width="1px",t.reliableMarginRight=!parseFloat((e.getComputedStyle(r,null)||{}).marginRight)),typeof d.style.zoom!==i&&(d.innerHTML="",d.style.cssText=s+"width:1px;padding:1px;display:inline;zoom:1",t.inlineBlockNeedsLayout=3===d.offsetWidth,d.style.display="block",d.innerHTML="<div></div>",d.firstChild.style.width="5px",t.shrinkWrapBlocks=3!==d.offsetWidth,t.inlineBlockNeedsLayout&&(l.style.zoom=1)),l.removeChild(n),n=d=o=r=null)}),n=s=l=u=r=o=null,t
}({});var B=/(?:\{[\s\S]*\}|\[[\s\S]*\])$/,P=/([A-Z])/g;function R(e,n,r,i){if(x.acceptData(e)){var o,a,s=x.expando,l=e.nodeType,u=l?x.cache:e,c=l?e[s]:e[s]&&s;if(c&&u[c]&&(i||u[c].data)||r!==t||"string"!=typeof n)return c||(c=l?e[s]=p.pop()||x.guid++:s),u[c]||(u[c]=l?{}:{toJSON:x.noop}),("object"==typeof n||"function"==typeof n)&&(i?u[c]=x.extend(u[c],n):u[c].data=x.extend(u[c].data,n)),a=u[c],i||(a.data||(a.data={}),a=a.data),r!==t&&(a[x.camelCase(n)]=r),"string"==typeof n?(o=a[n],null==o&&(o=a[x.camelCase(n)])):o=a,o}}function W(e,t,n){if(x.acceptData(e)){var r,i,o=e.nodeType,a=o?x.cache:e,s=o?e[x.expando]:x.expando;if(a[s]){if(t&&(r=n?a[s]:a[s].data)){x.isArray(t)?t=t.concat(x.map(t,x.camelCase)):t in r?t=[t]:(t=x.camelCase(t),t=t in r?[t]:t.split(" ")),i=t.length;while(i--)delete r[t[i]];if(n?!I(r):!x.isEmptyObject(r))return}(n||(delete a[s].data,I(a[s])))&&(o?x.cleanData([e],!0):x.support.deleteExpando||a!=a.window?delete a[s]:a[s]=null)}}}x.extend({cache:{},noData:{applet:!0,embed:!0,object:"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"},hasData:function(e){return e=e.nodeType?x.cache[e[x.expando]]:e[x.expando],!!e&&!I(e)},data:function(e,t,n){return R(e,t,n)},removeData:function(e,t){return W(e,t)},_data:function(e,t,n){return R(e,t,n,!0)},_removeData:function(e,t){return W(e,t,!0)},acceptData:function(e){if(e.nodeType&&1!==e.nodeType&&9!==e.nodeType)return!1;var t=e.nodeName&&x.noData[e.nodeName.toLowerCase()];return!t||t!==!0&&e.getAttribute("classid")===t}}),x.fn.extend({data:function(e,n){var r,i,o=null,a=0,s=this[0];if(e===t){if(this.length&&(o=x.data(s),1===s.nodeType&&!x._data(s,"parsedAttrs"))){for(r=s.attributes;r.length>a;a++)i=r[a].name,0===i.indexOf("data-")&&(i=x.camelCase(i.slice(5)),$(s,i,o[i]));x._data(s,"parsedAttrs",!0)}return o}return"object"==typeof e?this.each(function(){x.data(this,e)}):arguments.length>1?this.each(function(){x.data(this,e,n)}):s?$(s,e,x.data(s,e)):null},removeData:function(e){return this.each(function(){x.removeData(this,e)})}});function $(e,n,r){if(r===t&&1===e.nodeType){var i="data-"+n.replace(P,"-$1").toLowerCase();if(r=e.getAttribute(i),"string"==typeof r){try{r="true"===r?!0:"false"===r?!1:"null"===r?null:+r+""===r?+r:B.test(r)?x.parseJSON(r):r}catch(o){}x.data(e,n,r)}else r=t}return r}function I(e){var t;for(t in e)if(("data"!==t||!x.isEmptyObject(e[t]))&&"toJSON"!==t)return!1;return!0}x.extend({queue:function(e,n,r){var i;return e?(n=(n||"fx")+"queue",i=x._data(e,n),r&&(!i||x.isArray(r)?i=x._data(e,n,x.makeArray(r)):i.push(r)),i||[]):t},dequeue:function(e,t){t=t||"fx";var n=x.queue(e,t),r=n.length,i=n.shift(),o=x._queueHooks(e,t),a=function(){x.dequeue(e,t)};"inprogress"===i&&(i=n.shift(),r--),i&&("fx"===t&&n.unshift("inprogress"),delete o.stop,i.call(e,a,o)),!r&&o&&o.empty.fire()},_queueHooks:function(e,t){var n=t+"queueHooks";return x._data(e,n)||x._data(e,n,{empty:x.Callbacks("once memory").add(function(){x._removeData(e,t+"queue"),x._removeData(e,n)})})}}),x.fn.extend({queue:function(e,n){var r=2;return"string"!=typeof e&&(n=e,e="fx",r--),r>arguments.length?x.queue(this[0],e):n===t?this:this.each(function(){var t=x.queue(this,e,n);x._queueHooks(this,e),"fx"===e&&"inprogress"!==t[0]&&x.dequeue(this,e)})},dequeue:function(e){return this.each(function(){x.dequeue(this,e)})},delay:function(e,t){return e=x.fx?x.fx.speeds[e]||e:e,t=t||"fx",this.queue(t,function(t,n){var r=setTimeout(t,e);n.stop=function(){clearTimeout(r)}})},clearQueue:function(e){return this.queue(e||"fx",[])},promise:function(e,n){var r,i=1,o=x.Deferred(),a=this,s=this.length,l=function(){--i||o.resolveWith(a,[a])};"string"!=typeof e&&(n=e,e=t),e=e||"fx";while(s--)r=x._data(a[s],e+"queueHooks"),r&&r.empty&&(i++,r.empty.add(l));return l(),o.promise(n)}});var z,X,U=/[\t\r\n\f]/g,V=/\r/g,Y=/^(?:input|select|textarea|button|object)$/i,J=/^(?:a|area)$/i,G=/^(?:checked|selected)$/i,Q=x.support.getSetAttribute,K=x.support.input;x.fn.extend({attr:function(e,t){return x.access(this,x.attr,e,t,arguments.length>1)},removeAttr:function(e){return this.each(function(){x.removeAttr(this,e)})},prop:function(e,t){return x.access(this,x.prop,e,t,arguments.length>1)},removeProp:function(e){return e=x.propFix[e]||e,this.each(function(){try{this[e]=t,delete this[e]}catch(n){}})},addClass:function(e){var t,n,r,i,o,a=0,s=this.length,l="string"==typeof e&&e;if(x.isFunction(e))return this.each(function(t){x(this).addClass(e.call(this,t,this.className))});if(l)for(t=(e||"").match(T)||[];s>a;a++)if(n=this[a],r=1===n.nodeType&&(n.className?(" "+n.className+" ").replace(U," "):" ")){o=0;while(i=t[o++])0>r.indexOf(" "+i+" ")&&(r+=i+" ");n.className=x.trim(r)}return this},removeClass:function(e){var t,n,r,i,o,a=0,s=this.length,l=0===arguments.length||"string"==typeof e&&e;if(x.isFunction(e))return this.each(function(t){x(this).removeClass(e.call(this,t,this.className))});if(l)for(t=(e||"").match(T)||[];s>a;a++)if(n=this[a],r=1===n.nodeType&&(n.className?(" "+n.className+" ").replace(U," "):"")){o=0;while(i=t[o++])while(r.indexOf(" "+i+" ")>=0)r=r.replace(" "+i+" "," ");n.className=e?x.trim(r):""}return this},toggleClass:function(e,t){var n=typeof e;return"boolean"==typeof t&&"string"===n?t?this.addClass(e):this.removeClass(e):x.isFunction(e)?this.each(function(n){x(this).toggleClass(e.call(this,n,this.className,t),t)}):this.each(function(){if("string"===n){var t,r=0,o=x(this),a=e.match(T)||[];while(t=a[r++])o.hasClass(t)?o.removeClass(t):o.addClass(t)}else(n===i||"boolean"===n)&&(this.className&&x._data(this,"__className__",this.className),this.className=this.className||e===!1?"":x._data(this,"__className__")||"")})},hasClass:function(e){var t=" "+e+" ",n=0,r=this.length;for(;r>n;n++)if(1===this[n].nodeType&&(" "+this[n].className+" ").replace(U," ").indexOf(t)>=0)return!0;return!1},val:function(e){var n,r,i,o=this[0];{if(arguments.length)return i=x.isFunction(e),this.each(function(n){var o;1===this.nodeType&&(o=i?e.call(this,n,x(this).val()):e,null==o?o="":"number"==typeof o?o+="":x.isArray(o)&&(o=x.map(o,function(e){return null==e?"":e+""})),r=x.valHooks[this.type]||x.valHooks[this.nodeName.toLowerCase()],r&&"set"in r&&r.set(this,o,"value")!==t||(this.value=o))});if(o)return r=x.valHooks[o.type]||x.valHooks[o.nodeName.toLowerCase()],r&&"get"in r&&(n=r.get(o,"value"))!==t?n:(n=o.value,"string"==typeof n?n.replace(V,""):null==n?"":n)}}}),x.extend({valHooks:{option:{get:function(e){var t=x.find.attr(e,"value");return null!=t?t:e.text}},select:{get:function(e){var t,n,r=e.options,i=e.selectedIndex,o="select-one"===e.type||0>i,a=o?null:[],s=o?i+1:r.length,l=0>i?s:o?i:0;for(;s>l;l++)if(n=r[l],!(!n.selected&&l!==i||(x.support.optDisabled?n.disabled:null!==n.getAttribute("disabled"))||n.parentNode.disabled&&x.nodeName(n.parentNode,"optgroup"))){if(t=x(n).val(),o)return t;a.push(t)}return a},set:function(e,t){var n,r,i=e.options,o=x.makeArray(t),a=i.length;while(a--)r=i[a],(r.selected=x.inArray(x(r).val(),o)>=0)&&(n=!0);return n||(e.selectedIndex=-1),o}}},attr:function(e,n,r){var o,a,s=e.nodeType;if(e&&3!==s&&8!==s&&2!==s)return typeof e.getAttribute===i?x.prop(e,n,r):(1===s&&x.isXMLDoc(e)||(n=n.toLowerCase(),o=x.attrHooks[n]||(x.expr.match.bool.test(n)?X:z)),r===t?o&&"get"in o&&null!==(a=o.get(e,n))?a:(a=x.find.attr(e,n),null==a?t:a):null!==r?o&&"set"in o&&(a=o.set(e,r,n))!==t?a:(e.setAttribute(n,r+""),r):(x.removeAttr(e,n),t))},removeAttr:function(e,t){var n,r,i=0,o=t&&t.match(T);if(o&&1===e.nodeType)while(n=o[i++])r=x.propFix[n]||n,x.expr.match.bool.test(n)?K&&Q||!G.test(n)?e[r]=!1:e[x.camelCase("default-"+n)]=e[r]=!1:x.attr(e,n,""),e.removeAttribute(Q?n:r)},attrHooks:{type:{set:function(e,t){if(!x.support.radioValue&&"radio"===t&&x.nodeName(e,"input")){var n=e.value;return e.setAttribute("type",t),n&&(e.value=n),t}}}},propFix:{"for":"htmlFor","class":"className"},prop:function(e,n,r){var i,o,a,s=e.nodeType;if(e&&3!==s&&8!==s&&2!==s)return a=1!==s||!x.isXMLDoc(e),a&&(n=x.propFix[n]||n,o=x.propHooks[n]),r!==t?o&&"set"in o&&(i=o.set(e,r,n))!==t?i:e[n]=r:o&&"get"in o&&null!==(i=o.get(e,n))?i:e[n]},propHooks:{tabIndex:{get:function(e){var t=x.find.attr(e,"tabindex");return t?parseInt(t,10):Y.test(e.nodeName)||J.test(e.nodeName)&&e.href?0:-1}}}}),X={set:function(e,t,n){return t===!1?x.removeAttr(e,n):K&&Q||!G.test(n)?e.setAttribute(!Q&&x.propFix[n]||n,n):e[x.camelCase("default-"+n)]=e[n]=!0,n}},x.each(x.expr.match.bool.source.match(/\w+/g),function(e,n){var r=x.expr.attrHandle[n]||x.find.attr;x.expr.attrHandle[n]=K&&Q||!G.test(n)?function(e,n,i){var o=x.expr.attrHandle[n],a=i?t:(x.expr.attrHandle[n]=t)!=r(e,n,i)?n.toLowerCase():null;return x.expr.attrHandle[n]=o,a}:function(e,n,r){return r?t:e[x.camelCase("default-"+n)]?n.toLowerCase():null}}),K&&Q||(x.attrHooks.value={set:function(e,n,r){return x.nodeName(e,"input")?(e.defaultValue=n,t):z&&z.set(e,n,r)}}),Q||(z={set:function(e,n,r){var i=e.getAttributeNode(r);return i||e.setAttributeNode(i=e.ownerDocument.createAttribute(r)),i.value=n+="","value"===r||n===e.getAttribute(r)?n:t}},x.expr.attrHandle.id=x.expr.attrHandle.name=x.expr.attrHandle.coords=function(e,n,r){var i;return r?t:(i=e.getAttributeNode(n))&&""!==i.value?i.value:null},x.valHooks.button={get:function(e,n){var r=e.getAttributeNode(n);return r&&r.specified?r.value:t},set:z.set},x.attrHooks.contenteditable={set:function(e,t,n){z.set(e,""===t?!1:t,n)}},x.each(["width","height"],function(e,n){x.attrHooks[n]={set:function(e,r){return""===r?(e.setAttribute(n,"auto"),r):t}}})),x.support.hrefNormalized||x.each(["href","src"],function(e,t){x.propHooks[t]={get:function(e){return e.getAttribute(t,4)}}}),x.support.style||(x.attrHooks.style={get:function(e){return e.style.cssText||t},set:function(e,t){return e.style.cssText=t+""}}),x.support.optSelected||(x.propHooks.selected={get:function(e){var t=e.parentNode;return t&&(t.selectedIndex,t.parentNode&&t.parentNode.selectedIndex),null}}),x.each(["tabIndex","readOnly","maxLength","cellSpacing","cellPadding","rowSpan","colSpan","useMap","frameBorder","contentEditable"],function(){x.propFix[this.toLowerCase()]=this}),x.support.enctype||(x.propFix.enctype="encoding"),x.each(["radio","checkbox"],function(){x.valHooks[this]={set:function(e,n){return x.isArray(n)?e.checked=x.inArray(x(e).val(),n)>=0:t}},x.support.checkOn||(x.valHooks[this].get=function(e){return null===e.getAttribute("value")?"on":e.value})});var Z=/^(?:input|select|textarea)$/i,et=/^key/,tt=/^(?:mouse|contextmenu)|click/,nt=/^(?:focusinfocus|focusoutblur)$/,rt=/^([^.]*)(?:\.(.+)|)$/;function it(){return!0}function ot(){return!1}function at(){try{return a.activeElement}catch(e){}}x.event={global:{},add:function(e,n,r,o,a){var s,l,u,c,p,f,d,h,g,m,y,v=x._data(e);if(v){r.handler&&(c=r,r=c.handler,a=c.selector),r.guid||(r.guid=x.guid++),(l=v.events)||(l=v.events={}),(f=v.handle)||(f=v.handle=function(e){return typeof x===i||e&&x.event.triggered===e.type?t:x.event.dispatch.apply(f.elem,arguments)},f.elem=e),n=(n||"").match(T)||[""],u=n.length;while(u--)s=rt.exec(n[u])||[],g=y=s[1],m=(s[2]||"").split(".").sort(),g&&(p=x.event.special[g]||{},g=(a?p.delegateType:p.bindType)||g,p=x.event.special[g]||{},d=x.extend({type:g,origType:y,data:o,handler:r,guid:r.guid,selector:a,needsContext:a&&x.expr.match.needsContext.test(a),namespace:m.join(".")},c),(h=l[g])||(h=l[g]=[],h.delegateCount=0,p.setup&&p.setup.call(e,o,m,f)!==!1||(e.addEventListener?e.addEventListener(g,f,!1):e.attachEvent&&e.attachEvent("on"+g,f))),p.add&&(p.add.call(e,d),d.handler.guid||(d.handler.guid=r.guid)),a?h.splice(h.delegateCount++,0,d):h.push(d),x.event.global[g]=!0);e=null}},remove:function(e,t,n,r,i){var o,a,s,l,u,c,p,f,d,h,g,m=x.hasData(e)&&x._data(e);if(m&&(c=m.events)){t=(t||"").match(T)||[""],u=t.length;while(u--)if(s=rt.exec(t[u])||[],d=g=s[1],h=(s[2]||"").split(".").sort(),d){p=x.event.special[d]||{},d=(r?p.delegateType:p.bindType)||d,f=c[d]||[],s=s[2]&&RegExp("(^|\\.)"+h.join("\\.(?:.*\\.|)")+"(\\.|$)"),l=o=f.length;while(o--)a=f[o],!i&&g!==a.origType||n&&n.guid!==a.guid||s&&!s.test(a.namespace)||r&&r!==a.selector&&("**"!==r||!a.selector)||(f.splice(o,1),a.selector&&f.delegateCount--,p.remove&&p.remove.call(e,a));l&&!f.length&&(p.teardown&&p.teardown.call(e,h,m.handle)!==!1||x.removeEvent(e,d,m.handle),delete c[d])}else for(d in c)x.event.remove(e,d+t[u],n,r,!0);x.isEmptyObject(c)&&(delete m.handle,x._removeData(e,"events"))}},trigger:function(n,r,i,o){var s,l,u,c,p,f,d,h=[i||a],g=v.call(n,"type")?n.type:n,m=v.call(n,"namespace")?n.namespace.split("."):[];if(u=f=i=i||a,3!==i.nodeType&&8!==i.nodeType&&!nt.test(g+x.event.triggered)&&(g.indexOf(".")>=0&&(m=g.split("."),g=m.shift(),m.sort()),l=0>g.indexOf(":")&&"on"+g,n=n[x.expando]?n:new x.Event(g,"object"==typeof n&&n),n.isTrigger=o?2:3,n.namespace=m.join("."),n.namespace_re=n.namespace?RegExp("(^|\\.)"+m.join("\\.(?:.*\\.|)")+"(\\.|$)"):null,n.result=t,n.target||(n.target=i),r=null==r?[n]:x.makeArray(r,[n]),p=x.event.special[g]||{},o||!p.trigger||p.trigger.apply(i,r)!==!1)){if(!o&&!p.noBubble&&!x.isWindow(i)){for(c=p.delegateType||g,nt.test(c+g)||(u=u.parentNode);u;u=u.parentNode)h.push(u),f=u;f===(i.ownerDocument||a)&&h.push(f.defaultView||f.parentWindow||e)}d=0;while((u=h[d++])&&!n.isPropagationStopped())n.type=d>1?c:p.bindType||g,s=(x._data(u,"events")||{})[n.type]&&x._data(u,"handle"),s&&s.apply(u,r),s=l&&u[l],s&&x.acceptData(u)&&s.apply&&s.apply(u,r)===!1&&n.preventDefault();if(n.type=g,!o&&!n.isDefaultPrevented()&&(!p._default||p._default.apply(h.pop(),r)===!1)&&x.acceptData(i)&&l&&i[g]&&!x.isWindow(i)){f=i[l],f&&(i[l]=null),x.event.triggered=g;try{i[g]()}catch(y){}x.event.triggered=t,f&&(i[l]=f)}return n.result}},dispatch:function(e){e=x.event.fix(e);var n,r,i,o,a,s=[],l=g.call(arguments),u=(x._data(this,"events")||{})[e.type]||[],c=x.event.special[e.type]||{};if(l[0]=e,e.delegateTarget=this,!c.preDispatch||c.preDispatch.call(this,e)!==!1){s=x.event.handlers.call(this,e,u),n=0;while((o=s[n++])&&!e.isPropagationStopped()){e.currentTarget=o.elem,a=0;while((i=o.handlers[a++])&&!e.isImmediatePropagationStopped())(!e.namespace_re||e.namespace_re.test(i.namespace))&&(e.handleObj=i,e.data=i.data,r=((x.event.special[i.origType]||{}).handle||i.handler).apply(o.elem,l),r!==t&&(e.result=r)===!1&&(e.preventDefault(),e.stopPropagation()))}return c.postDispatch&&c.postDispatch.call(this,e),e.result}},handlers:function(e,n){var r,i,o,a,s=[],l=n.delegateCount,u=e.target;if(l&&u.nodeType&&(!e.button||"click"!==e.type))for(;u!=this;u=u.parentNode||this)if(1===u.nodeType&&(u.disabled!==!0||"click"!==e.type)){for(o=[],a=0;l>a;a++)i=n[a],r=i.selector+" ",o[r]===t&&(o[r]=i.needsContext?x(r,this).index(u)>=0:x.find(r,this,null,[u]).length),o[r]&&o.push(i);o.length&&s.push({elem:u,handlers:o})}return n.length>l&&s.push({elem:this,handlers:n.slice(l)}),s},fix:function(e){if(e[x.expando])return e;var t,n,r,i=e.type,o=e,s=this.fixHooks[i];s||(this.fixHooks[i]=s=tt.test(i)?this.mouseHooks:et.test(i)?this.keyHooks:{}),r=s.props?this.props.concat(s.props):this.props,e=new x.Event(o),t=r.length;while(t--)n=r[t],e[n]=o[n];return e.target||(e.target=o.srcElement||a),3===e.target.nodeType&&(e.target=e.target.parentNode),e.metaKey=!!e.metaKey,s.filter?s.filter(e,o):e},props:"altKey bubbles cancelable ctrlKey currentTarget eventPhase metaKey relatedTarget shiftKey target timeStamp view which".split(" "),fixHooks:{},keyHooks:{props:"char charCode key keyCode".split(" "),filter:function(e,t){return null==e.which&&(e.which=null!=t.charCode?t.charCode:t.keyCode),e}},mouseHooks:{props:"button buttons clientX clientY fromElement offsetX offsetY pageX pageY screenX screenY toElement".split(" "),filter:function(e,n){var r,i,o,s=n.button,l=n.fromElement;return null==e.pageX&&null!=n.clientX&&(i=e.target.ownerDocument||a,o=i.documentElement,r=i.body,e.pageX=n.clientX+(o&&o.scrollLeft||r&&r.scrollLeft||0)-(o&&o.clientLeft||r&&r.clientLeft||0),e.pageY=n.clientY+(o&&o.scrollTop||r&&r.scrollTop||0)-(o&&o.clientTop||r&&r.clientTop||0)),!e.relatedTarget&&l&&(e.relatedTarget=l===e.target?n.toElement:l),e.which||s===t||(e.which=1&s?1:2&s?3:4&s?2:0),e}},special:{load:{noBubble:!0},focus:{trigger:function(){if(this!==at()&&this.focus)try{return this.focus(),!1}catch(e){}},delegateType:"focusin"},blur:{trigger:function(){return this===at()&&this.blur?(this.blur(),!1):t},delegateType:"focusout"},click:{trigger:function(){return x.nodeName(this,"input")&&"checkbox"===this.type&&this.click?(this.click(),!1):t},_default:function(e){return x.nodeName(e.target,"a")}},beforeunload:{postDispatch:function(e){e.result!==t&&(e.originalEvent.returnValue=e.result)}}},simulate:function(e,t,n,r){var i=x.extend(new x.Event,n,{type:e,isSimulated:!0,originalEvent:{}});r?x.event.trigger(i,null,t):x.event.dispatch.call(t,i),i.isDefaultPrevented()&&n.preventDefault()}},x.removeEvent=a.removeEventListener?function(e,t,n){e.removeEventListener&&e.removeEventListener(t,n,!1)}:function(e,t,n){var r="on"+t;e.detachEvent&&(typeof e[r]===i&&(e[r]=null),e.detachEvent(r,n))},x.Event=function(e,n){return this instanceof x.Event?(e&&e.type?(this.originalEvent=e,this.type=e.type,this.isDefaultPrevented=e.defaultPrevented||e.returnValue===!1||e.getPreventDefault&&e.getPreventDefault()?it:ot):this.type=e,n&&x.extend(this,n),this.timeStamp=e&&e.timeStamp||x.now(),this[x.expando]=!0,t):new x.Event(e,n)},x.Event.prototype={isDefaultPrevented:ot,isPropagationStopped:ot,isImmediatePropagationStopped:ot,preventDefault:function(){var e=this.originalEvent;this.isDefaultPrevented=it,e&&(e.preventDefault?e.preventDefault():e.returnValue=!1)},stopPropagation:function(){var e=this.originalEvent;this.isPropagationStopped=it,e&&(e.stopPropagation&&e.stopPropagation(),e.cancelBubble=!0)},stopImmediatePropagation:function(){this.isImmediatePropagationStopped=it,this.stopPropagation()}},x.each({mouseenter:"mouseover",mouseleave:"mouseout"},function(e,t){x.event.special[e]={delegateType:t,bindType:t,handle:function(e){var n,r=this,i=e.relatedTarget,o=e.handleObj;return(!i||i!==r&&!x.contains(r,i))&&(e.type=o.origType,n=o.handler.apply(this,arguments),e.type=t),n}}}),x.support.submitBubbles||(x.event.special.submit={setup:function(){return x.nodeName(this,"form")?!1:(x.event.add(this,"click._submit keypress._submit",function(e){var n=e.target,r=x.nodeName(n,"input")||x.nodeName(n,"button")?n.form:t;r&&!x._data(r,"submitBubbles")&&(x.event.add(r,"submit._submit",function(e){e._submit_bubble=!0}),x._data(r,"submitBubbles",!0))}),t)},postDispatch:function(e){e._submit_bubble&&(delete e._submit_bubble,this.parentNode&&!e.isTrigger&&x.event.simulate("submit",this.parentNode,e,!0))},teardown:function(){return x.nodeName(this,"form")?!1:(x.event.remove(this,"._submit"),t)}}),x.support.changeBubbles||(x.event.special.change={setup:function(){return Z.test(this.nodeName)?(("checkbox"===this.type||"radio"===this.type)&&(x.event.add(this,"propertychange._change",function(e){"checked"===e.originalEvent.propertyName&&(this._just_changed=!0)}),x.event.add(this,"click._change",function(e){this._just_changed&&!e.isTrigger&&(this._just_changed=!1),x.event.simulate("change",this,e,!0)})),!1):(x.event.add(this,"beforeactivate._change",function(e){var t=e.target;Z.test(t.nodeName)&&!x._data(t,"changeBubbles")&&(x.event.add(t,"change._change",function(e){!this.parentNode||e.isSimulated||e.isTrigger||x.event.simulate("change",this.parentNode,e,!0)}),x._data(t,"changeBubbles",!0))}),t)},handle:function(e){var n=e.target;return this!==n||e.isSimulated||e.isTrigger||"radio"!==n.type&&"checkbox"!==n.type?e.handleObj.handler.apply(this,arguments):t},teardown:function(){return x.event.remove(this,"._change"),!Z.test(this.nodeName)}}),x.support.focusinBubbles||x.each({focus:"focusin",blur:"focusout"},function(e,t){var n=0,r=function(e){x.event.simulate(t,e.target,x.event.fix(e),!0)};x.event.special[t]={setup:function(){0===n++&&a.addEventListener(e,r,!0)},teardown:function(){0===--n&&a.removeEventListener(e,r,!0)}}}),x.fn.extend({on:function(e,n,r,i,o){var a,s;if("object"==typeof e){"string"!=typeof n&&(r=r||n,n=t);for(a in e)this.on(a,n,r,e[a],o);return this}if(null==r&&null==i?(i=n,r=n=t):null==i&&("string"==typeof n?(i=r,r=t):(i=r,r=n,n=t)),i===!1)i=ot;else if(!i)return this;return 1===o&&(s=i,i=function(e){return x().off(e),s.apply(this,arguments)},i.guid=s.guid||(s.guid=x.guid++)),this.each(function(){x.event.add(this,e,i,r,n)})},one:function(e,t,n,r){return this.on(e,t,n,r,1)},off:function(e,n,r){var i,o;if(e&&e.preventDefault&&e.handleObj)return i=e.handleObj,x(e.delegateTarget).off(i.namespace?i.origType+"."+i.namespace:i.origType,i.selector,i.handler),this;if("object"==typeof e){for(o in e)this.off(o,n,e[o]);return this}return(n===!1||"function"==typeof n)&&(r=n,n=t),r===!1&&(r=ot),this.each(function(){x.event.remove(this,e,r,n)})},trigger:function(e,t){return this.each(function(){x.event.trigger(e,t,this)})},triggerHandler:function(e,n){var r=this[0];return r?x.event.trigger(e,n,r,!0):t}});var st=/^.[^:#\[\.,]*$/,lt=/^(?:parents|prev(?:Until|All))/,ut=x.expr.match.needsContext,ct={children:!0,contents:!0,next:!0,prev:!0};x.fn.extend({find:function(e){var t,n=[],r=this,i=r.length;if("string"!=typeof e)return this.pushStack(x(e).filter(function(){for(t=0;i>t;t++)if(x.contains(r[t],this))return!0}));for(t=0;i>t;t++)x.find(e,r[t],n);return n=this.pushStack(i>1?x.unique(n):n),n.selector=this.selector?this.selector+" "+e:e,n},has:function(e){var t,n=x(e,this),r=n.length;return this.filter(function(){for(t=0;r>t;t++)if(x.contains(this,n[t]))return!0})},not:function(e){return this.pushStack(ft(this,e||[],!0))},filter:function(e){return this.pushStack(ft(this,e||[],!1))},is:function(e){return!!ft(this,"string"==typeof e&&ut.test(e)?x(e):e||[],!1).length},closest:function(e,t){var n,r=0,i=this.length,o=[],a=ut.test(e)||"string"!=typeof e?x(e,t||this.context):0;for(;i>r;r++)for(n=this[r];n&&n!==t;n=n.parentNode)if(11>n.nodeType&&(a?a.index(n)>-1:1===n.nodeType&&x.find.matchesSelector(n,e))){n=o.push(n);break}return this.pushStack(o.length>1?x.unique(o):o)},index:function(e){return e?"string"==typeof e?x.inArray(this[0],x(e)):x.inArray(e.jquery?e[0]:e,this):this[0]&&this[0].parentNode?this.first().prevAll().length:-1},add:function(e,t){var n="string"==typeof e?x(e,t):x.makeArray(e&&e.nodeType?[e]:e),r=x.merge(this.get(),n);return this.pushStack(x.unique(r))},addBack:function(e){return this.add(null==e?this.prevObject:this.prevObject.filter(e))}});function pt(e,t){do e=e[t];while(e&&1!==e.nodeType);return e}x.each({parent:function(e){var t=e.parentNode;return t&&11!==t.nodeType?t:null},parents:function(e){return x.dir(e,"parentNode")},parentsUntil:function(e,t,n){return x.dir(e,"parentNode",n)},next:function(e){return pt(e,"nextSibling")},prev:function(e){return pt(e,"previousSibling")},nextAll:function(e){return x.dir(e,"nextSibling")},prevAll:function(e){return x.dir(e,"previousSibling")},nextUntil:function(e,t,n){return x.dir(e,"nextSibling",n)},prevUntil:function(e,t,n){return x.dir(e,"previousSibling",n)},siblings:function(e){return x.sibling((e.parentNode||{}).firstChild,e)},children:function(e){return x.sibling(e.firstChild)},contents:function(e){return x.nodeName(e,"iframe")?e.contentDocument||e.contentWindow.document:x.merge([],e.childNodes)}},function(e,t){x.fn[e]=function(n,r){var i=x.map(this,t,n);return"Until"!==e.slice(-5)&&(r=n),r&&"string"==typeof r&&(i=x.filter(r,i)),this.length>1&&(ct[e]||(i=x.unique(i)),lt.test(e)&&(i=i.reverse())),this.pushStack(i)}}),x.extend({filter:function(e,t,n){var r=t[0];return n&&(e=":not("+e+")"),1===t.length&&1===r.nodeType?x.find.matchesSelector(r,e)?[r]:[]:x.find.matches(e,x.grep(t,function(e){return 1===e.nodeType}))},dir:function(e,n,r){var i=[],o=e[n];while(o&&9!==o.nodeType&&(r===t||1!==o.nodeType||!x(o).is(r)))1===o.nodeType&&i.push(o),o=o[n];return i},sibling:function(e,t){var n=[];for(;e;e=e.nextSibling)1===e.nodeType&&e!==t&&n.push(e);return n}});function ft(e,t,n){if(x.isFunction(t))return x.grep(e,function(e,r){return!!t.call(e,r,e)!==n});if(t.nodeType)return x.grep(e,function(e){return e===t!==n});if("string"==typeof t){if(st.test(t))return x.filter(t,e,n);t=x.filter(t,e)}return x.grep(e,function(e){return x.inArray(e,t)>=0!==n})}function dt(e){var t=ht.split("|"),n=e.createDocumentFragment();if(n.createElement)while(t.length)n.createElement(t.pop());return n}var ht="abbr|article|aside|audio|bdi|canvas|data|datalist|details|figcaption|figure|footer|header|hgroup|mark|meter|nav|output|progress|section|summary|time|video",gt=/ jQuery\d+="(?:null|\d+)"/g,mt=RegExp("<(?:"+ht+")[\\s/>]","i"),yt=/^\s+/,vt=/<(?!area|br|col|embed|hr|img|input|link|meta|param)(([\w:]+)[^>]*)\/>/gi,bt=/<([\w:]+)/,xt=/<tbody/i,wt=/<|&#?\w+;/,Tt=/<(?:script|style|link)/i,Ct=/^(?:checkbox|radio)$/i,Nt=/checked\s*(?:[^=]|=\s*.checked.)/i,kt=/^$|\/(?:java|ecma)script/i,Et=/^true\/(.*)/,St=/^\s*<!(?:\[CDATA\[|--)|(?:\]\]|--)>\s*$/g,At={option:[1,"<select multiple='multiple'>","</select>"],legend:[1,"<fieldset>","</fieldset>"],area:[1,"<map>","</map>"],param:[1,"<object>","</object>"],thead:[1,"<table>","</table>"],tr:[2,"<table><tbody>","</tbody></table>"],col:[2,"<table><tbody></tbody><colgroup>","</colgroup></table>"],td:[3,"<table><tbody><tr>","</tr></tbody></table>"],_default:x.support.htmlSerialize?[0,"",""]:[1,"X<div>","</div>"]},jt=dt(a),Dt=jt.appendChild(a.createElement("div"));At.optgroup=At.option,At.tbody=At.tfoot=At.colgroup=At.caption=At.thead,At.th=At.td,x.fn.extend({text:function(e){return x.access(this,function(e){return e===t?x.text(this):this.empty().append((this[0]&&this[0].ownerDocument||a).createTextNode(e))},null,e,arguments.length)},append:function(){return this.domManip(arguments,function(e){if(1===this.nodeType||11===this.nodeType||9===this.nodeType){var t=Lt(this,e);t.appendChild(e)}})},prepend:function(){return this.domManip(arguments,function(e){if(1===this.nodeType||11===this.nodeType||9===this.nodeType){var t=Lt(this,e);t.insertBefore(e,t.firstChild)}})},before:function(){return this.domManip(arguments,function(e){this.parentNode&&this.parentNode.insertBefore(e,this)})},after:function(){return this.domManip(arguments,function(e){this.parentNode&&this.parentNode.insertBefore(e,this.nextSibling)})},remove:function(e,t){var n,r=e?x.filter(e,this):this,i=0;for(;null!=(n=r[i]);i++)t||1!==n.nodeType||x.cleanData(Ft(n)),n.parentNode&&(t&&x.contains(n.ownerDocument,n)&&_t(Ft(n,"script")),n.parentNode.removeChild(n));return this},empty:function(){var e,t=0;for(;null!=(e=this[t]);t++){1===e.nodeType&&x.cleanData(Ft(e,!1));while(e.firstChild)e.removeChild(e.firstChild);e.options&&x.nodeName(e,"select")&&(e.options.length=0)}return this},clone:function(e,t){return e=null==e?!1:e,t=null==t?e:t,this.map(function(){return x.clone(this,e,t)})},html:function(e){return x.access(this,function(e){var n=this[0]||{},r=0,i=this.length;if(e===t)return 1===n.nodeType?n.innerHTML.replace(gt,""):t;if(!("string"!=typeof e||Tt.test(e)||!x.support.htmlSerialize&&mt.test(e)||!x.support.leadingWhitespace&&yt.test(e)||At[(bt.exec(e)||["",""])[1].toLowerCase()])){e=e.replace(vt,"<$1></$2>");try{for(;i>r;r++)n=this[r]||{},1===n.nodeType&&(x.cleanData(Ft(n,!1)),n.innerHTML=e);n=0}catch(o){}}n&&this.empty().append(e)},null,e,arguments.length)},replaceWith:function(){var e=x.map(this,function(e){return[e.nextSibling,e.parentNode]}),t=0;return this.domManip(arguments,function(n){var r=e[t++],i=e[t++];i&&(r&&r.parentNode!==i&&(r=this.nextSibling),x(this).remove(),i.insertBefore(n,r))},!0),t?this:this.remove()},detach:function(e){return this.remove(e,!0)},domManip:function(e,t,n){e=d.apply([],e);var r,i,o,a,s,l,u=0,c=this.length,p=this,f=c-1,h=e[0],g=x.isFunction(h);if(g||!(1>=c||"string"!=typeof h||x.support.checkClone)&&Nt.test(h))return this.each(function(r){var i=p.eq(r);g&&(e[0]=h.call(this,r,i.html())),i.domManip(e,t,n)});if(c&&(l=x.buildFragment(e,this[0].ownerDocument,!1,!n&&this),r=l.firstChild,1===l.childNodes.length&&(l=r),r)){for(a=x.map(Ft(l,"script"),Ht),o=a.length;c>u;u++)i=l,u!==f&&(i=x.clone(i,!0,!0),o&&x.merge(a,Ft(i,"script"))),t.call(this[u],i,u);if(o)for(s=a[a.length-1].ownerDocument,x.map(a,qt),u=0;o>u;u++)i=a[u],kt.test(i.type||"")&&!x._data(i,"globalEval")&&x.contains(s,i)&&(i.src?x._evalUrl(i.src):x.globalEval((i.text||i.textContent||i.innerHTML||"").replace(St,"")));l=r=null}return this}});function Lt(e,t){return x.nodeName(e,"table")&&x.nodeName(1===t.nodeType?t:t.firstChild,"tr")?e.getElementsByTagName("tbody")[0]||e.appendChild(e.ownerDocument.createElement("tbody")):e}function Ht(e){return e.type=(null!==x.find.attr(e,"type"))+"/"+e.type,e}function qt(e){var t=Et.exec(e.type);return t?e.type=t[1]:e.removeAttribute("type"),e}function _t(e,t){var n,r=0;for(;null!=(n=e[r]);r++)x._data(n,"globalEval",!t||x._data(t[r],"globalEval"))}function Mt(e,t){if(1===t.nodeType&&x.hasData(e)){var n,r,i,o=x._data(e),a=x._data(t,o),s=o.events;if(s){delete a.handle,a.events={};for(n in s)for(r=0,i=s[n].length;i>r;r++)x.event.add(t,n,s[n][r])}a.data&&(a.data=x.extend({},a.data))}}function Ot(e,t){var n,r,i;if(1===t.nodeType){if(n=t.nodeName.toLowerCase(),!x.support.noCloneEvent&&t[x.expando]){i=x._data(t);for(r in i.events)x.removeEvent(t,r,i.handle);t.removeAttribute(x.expando)}"script"===n&&t.text!==e.text?(Ht(t).text=e.text,qt(t)):"object"===n?(t.parentNode&&(t.outerHTML=e.outerHTML),x.support.html5Clone&&e.innerHTML&&!x.trim(t.innerHTML)&&(t.innerHTML=e.innerHTML)):"input"===n&&Ct.test(e.type)?(t.defaultChecked=t.checked=e.checked,t.value!==e.value&&(t.value=e.value)):"option"===n?t.defaultSelected=t.selected=e.defaultSelected:("input"===n||"textarea"===n)&&(t.defaultValue=e.defaultValue)}}x.each({appendTo:"append",prependTo:"prepend",insertBefore:"before",insertAfter:"after",replaceAll:"replaceWith"},function(e,t){x.fn[e]=function(e){var n,r=0,i=[],o=x(e),a=o.length-1;for(;a>=r;r++)n=r===a?this:this.clone(!0),x(o[r])[t](n),h.apply(i,n.get());return this.pushStack(i)}});function Ft(e,n){var r,o,a=0,s=typeof e.getElementsByTagName!==i?e.getElementsByTagName(n||"*"):typeof e.querySelectorAll!==i?e.querySelectorAll(n||"*"):t;if(!s)for(s=[],r=e.childNodes||e;null!=(o=r[a]);a++)!n||x.nodeName(o,n)?s.push(o):x.merge(s,Ft(o,n));return n===t||n&&x.nodeName(e,n)?x.merge([e],s):s}function Bt(e){Ct.test(e.type)&&(e.defaultChecked=e.checked)}x.extend({clone:function(e,t,n){var r,i,o,a,s,l=x.contains(e.ownerDocument,e);if(x.support.html5Clone||x.isXMLDoc(e)||!mt.test("<"+e.nodeName+">")?o=e.cloneNode(!0):(Dt.innerHTML=e.outerHTML,Dt.removeChild(o=Dt.firstChild)),!(x.support.noCloneEvent&&x.support.noCloneChecked||1!==e.nodeType&&11!==e.nodeType||x.isXMLDoc(e)))for(r=Ft(o),s=Ft(e),a=0;null!=(i=s[a]);++a)r[a]&&Ot(i,r[a]);if(t)if(n)for(s=s||Ft(e),r=r||Ft(o),a=0;null!=(i=s[a]);a++)Mt(i,r[a]);else Mt(e,o);return r=Ft(o,"script"),r.length>0&&_t(r,!l&&Ft(e,"script")),r=s=i=null,o},buildFragment:function(e,t,n,r){var i,o,a,s,l,u,c,p=e.length,f=dt(t),d=[],h=0;for(;p>h;h++)if(o=e[h],o||0===o)if("object"===x.type(o))x.merge(d,o.nodeType?[o]:o);else if(wt.test(o)){s=s||f.appendChild(t.createElement("div")),l=(bt.exec(o)||["",""])[1].toLowerCase(),c=At[l]||At._default,s.innerHTML=c[1]+o.replace(vt,"<$1></$2>")+c[2],i=c[0];while(i--)s=s.lastChild;if(!x.support.leadingWhitespace&&yt.test(o)&&d.push(t.createTextNode(yt.exec(o)[0])),!x.support.tbody){o="table"!==l||xt.test(o)?"<table>"!==c[1]||xt.test(o)?0:s:s.firstChild,i=o&&o.childNodes.length;while(i--)x.nodeName(u=o.childNodes[i],"tbody")&&!u.childNodes.length&&o.removeChild(u)}x.merge(d,s.childNodes),s.textContent="";while(s.firstChild)s.removeChild(s.firstChild);s=f.lastChild}else d.push(t.createTextNode(o));s&&f.removeChild(s),x.support.appendChecked||x.grep(Ft(d,"input"),Bt),h=0;while(o=d[h++])if((!r||-1===x.inArray(o,r))&&(a=x.contains(o.ownerDocument,o),s=Ft(f.appendChild(o),"script"),a&&_t(s),n)){i=0;while(o=s[i++])kt.test(o.type||"")&&n.push(o)}return s=null,f},cleanData:function(e,t){var n,r,o,a,s=0,l=x.expando,u=x.cache,c=x.support.deleteExpando,f=x.event.special;for(;null!=(n=e[s]);s++)if((t||x.acceptData(n))&&(o=n[l],a=o&&u[o])){if(a.events)for(r in a.events)f[r]?x.event.remove(n,r):x.removeEvent(n,r,a.handle);
u[o]&&(delete u[o],c?delete n[l]:typeof n.removeAttribute!==i?n.removeAttribute(l):n[l]=null,p.push(o))}},_evalUrl:function(e){return x.ajax({url:e,type:"GET",dataType:"script",async:!1,global:!1,"throws":!0})}}),x.fn.extend({wrapAll:function(e){if(x.isFunction(e))return this.each(function(t){x(this).wrapAll(e.call(this,t))});if(this[0]){var t=x(e,this[0].ownerDocument).eq(0).clone(!0);this[0].parentNode&&t.insertBefore(this[0]),t.map(function(){var e=this;while(e.firstChild&&1===e.firstChild.nodeType)e=e.firstChild;return e}).append(this)}return this},wrapInner:function(e){return x.isFunction(e)?this.each(function(t){x(this).wrapInner(e.call(this,t))}):this.each(function(){var t=x(this),n=t.contents();n.length?n.wrapAll(e):t.append(e)})},wrap:function(e){var t=x.isFunction(e);return this.each(function(n){x(this).wrapAll(t?e.call(this,n):e)})},unwrap:function(){return this.parent().each(function(){x.nodeName(this,"body")||x(this).replaceWith(this.childNodes)}).end()}});var Pt,Rt,Wt,$t=/alpha\([^)]*\)/i,It=/opacity\s*=\s*([^)]*)/,zt=/^(top|right|bottom|left)$/,Xt=/^(none|table(?!-c[ea]).+)/,Ut=/^margin/,Vt=RegExp("^("+w+")(.*)$","i"),Yt=RegExp("^("+w+")(?!px)[a-z%]+$","i"),Jt=RegExp("^([+-])=("+w+")","i"),Gt={BODY:"block"},Qt={position:"absolute",visibility:"hidden",display:"block"},Kt={letterSpacing:0,fontWeight:400},Zt=["Top","Right","Bottom","Left"],en=["Webkit","O","Moz","ms"];function tn(e,t){if(t in e)return t;var n=t.charAt(0).toUpperCase()+t.slice(1),r=t,i=en.length;while(i--)if(t=en[i]+n,t in e)return t;return r}function nn(e,t){return e=t||e,"none"===x.css(e,"display")||!x.contains(e.ownerDocument,e)}function rn(e,t){var n,r,i,o=[],a=0,s=e.length;for(;s>a;a++)r=e[a],r.style&&(o[a]=x._data(r,"olddisplay"),n=r.style.display,t?(o[a]||"none"!==n||(r.style.display=""),""===r.style.display&&nn(r)&&(o[a]=x._data(r,"olddisplay",ln(r.nodeName)))):o[a]||(i=nn(r),(n&&"none"!==n||!i)&&x._data(r,"olddisplay",i?n:x.css(r,"display"))));for(a=0;s>a;a++)r=e[a],r.style&&(t&&"none"!==r.style.display&&""!==r.style.display||(r.style.display=t?o[a]||"":"none"));return e}x.fn.extend({css:function(e,n){return x.access(this,function(e,n,r){var i,o,a={},s=0;if(x.isArray(n)){for(o=Rt(e),i=n.length;i>s;s++)a[n[s]]=x.css(e,n[s],!1,o);return a}return r!==t?x.style(e,n,r):x.css(e,n)},e,n,arguments.length>1)},show:function(){return rn(this,!0)},hide:function(){return rn(this)},toggle:function(e){return"boolean"==typeof e?e?this.show():this.hide():this.each(function(){nn(this)?x(this).show():x(this).hide()})}}),x.extend({cssHooks:{opacity:{get:function(e,t){if(t){var n=Wt(e,"opacity");return""===n?"1":n}}}},cssNumber:{columnCount:!0,fillOpacity:!0,fontWeight:!0,lineHeight:!0,opacity:!0,order:!0,orphans:!0,widows:!0,zIndex:!0,zoom:!0},cssProps:{"float":x.support.cssFloat?"cssFloat":"styleFloat"},style:function(e,n,r,i){if(e&&3!==e.nodeType&&8!==e.nodeType&&e.style){var o,a,s,l=x.camelCase(n),u=e.style;if(n=x.cssProps[l]||(x.cssProps[l]=tn(u,l)),s=x.cssHooks[n]||x.cssHooks[l],r===t)return s&&"get"in s&&(o=s.get(e,!1,i))!==t?o:u[n];if(a=typeof r,"string"===a&&(o=Jt.exec(r))&&(r=(o[1]+1)*o[2]+parseFloat(x.css(e,n)),a="number"),!(null==r||"number"===a&&isNaN(r)||("number"!==a||x.cssNumber[l]||(r+="px"),x.support.clearCloneStyle||""!==r||0!==n.indexOf("background")||(u[n]="inherit"),s&&"set"in s&&(r=s.set(e,r,i))===t)))try{u[n]=r}catch(c){}}},css:function(e,n,r,i){var o,a,s,l=x.camelCase(n);return n=x.cssProps[l]||(x.cssProps[l]=tn(e.style,l)),s=x.cssHooks[n]||x.cssHooks[l],s&&"get"in s&&(a=s.get(e,!0,r)),a===t&&(a=Wt(e,n,i)),"normal"===a&&n in Kt&&(a=Kt[n]),""===r||r?(o=parseFloat(a),r===!0||x.isNumeric(o)?o||0:a):a}}),e.getComputedStyle?(Rt=function(t){return e.getComputedStyle(t,null)},Wt=function(e,n,r){var i,o,a,s=r||Rt(e),l=s?s.getPropertyValue(n)||s[n]:t,u=e.style;return s&&(""!==l||x.contains(e.ownerDocument,e)||(l=x.style(e,n)),Yt.test(l)&&Ut.test(n)&&(i=u.width,o=u.minWidth,a=u.maxWidth,u.minWidth=u.maxWidth=u.width=l,l=s.width,u.width=i,u.minWidth=o,u.maxWidth=a)),l}):a.documentElement.currentStyle&&(Rt=function(e){return e.currentStyle},Wt=function(e,n,r){var i,o,a,s=r||Rt(e),l=s?s[n]:t,u=e.style;return null==l&&u&&u[n]&&(l=u[n]),Yt.test(l)&&!zt.test(n)&&(i=u.left,o=e.runtimeStyle,a=o&&o.left,a&&(o.left=e.currentStyle.left),u.left="fontSize"===n?"1em":l,l=u.pixelLeft+"px",u.left=i,a&&(o.left=a)),""===l?"auto":l});function on(e,t,n){var r=Vt.exec(t);return r?Math.max(0,r[1]-(n||0))+(r[2]||"px"):t}function an(e,t,n,r,i){var o=n===(r?"border":"content")?4:"width"===t?1:0,a=0;for(;4>o;o+=2)"margin"===n&&(a+=x.css(e,n+Zt[o],!0,i)),r?("content"===n&&(a-=x.css(e,"padding"+Zt[o],!0,i)),"margin"!==n&&(a-=x.css(e,"border"+Zt[o]+"Width",!0,i))):(a+=x.css(e,"padding"+Zt[o],!0,i),"padding"!==n&&(a+=x.css(e,"border"+Zt[o]+"Width",!0,i)));return a}function sn(e,t,n){var r=!0,i="width"===t?e.offsetWidth:e.offsetHeight,o=Rt(e),a=x.support.boxSizing&&"border-box"===x.css(e,"boxSizing",!1,o);if(0>=i||null==i){if(i=Wt(e,t,o),(0>i||null==i)&&(i=e.style[t]),Yt.test(i))return i;r=a&&(x.support.boxSizingReliable||i===e.style[t]),i=parseFloat(i)||0}return i+an(e,t,n||(a?"border":"content"),r,o)+"px"}function ln(e){var t=a,n=Gt[e];return n||(n=un(e,t),"none"!==n&&n||(Pt=(Pt||x("<iframe frameborder='0' width='0' height='0'/>").css("cssText","display:block !important")).appendTo(t.documentElement),t=(Pt[0].contentWindow||Pt[0].contentDocument).document,t.write("<!doctype html><html><body>"),t.close(),n=un(e,t),Pt.detach()),Gt[e]=n),n}function un(e,t){var n=x(t.createElement(e)).appendTo(t.body),r=x.css(n[0],"display");return n.remove(),r}x.each(["height","width"],function(e,n){x.cssHooks[n]={get:function(e,r,i){return r?0===e.offsetWidth&&Xt.test(x.css(e,"display"))?x.swap(e,Qt,function(){return sn(e,n,i)}):sn(e,n,i):t},set:function(e,t,r){var i=r&&Rt(e);return on(e,t,r?an(e,n,r,x.support.boxSizing&&"border-box"===x.css(e,"boxSizing",!1,i),i):0)}}}),x.support.opacity||(x.cssHooks.opacity={get:function(e,t){return It.test((t&&e.currentStyle?e.currentStyle.filter:e.style.filter)||"")?.01*parseFloat(RegExp.$1)+"":t?"1":""},set:function(e,t){var n=e.style,r=e.currentStyle,i=x.isNumeric(t)?"alpha(opacity="+100*t+")":"",o=r&&r.filter||n.filter||"";n.zoom=1,(t>=1||""===t)&&""===x.trim(o.replace($t,""))&&n.removeAttribute&&(n.removeAttribute("filter"),""===t||r&&!r.filter)||(n.filter=$t.test(o)?o.replace($t,i):o+" "+i)}}),x(function(){x.support.reliableMarginRight||(x.cssHooks.marginRight={get:function(e,n){return n?x.swap(e,{display:"inline-block"},Wt,[e,"marginRight"]):t}}),!x.support.pixelPosition&&x.fn.position&&x.each(["top","left"],function(e,n){x.cssHooks[n]={get:function(e,r){return r?(r=Wt(e,n),Yt.test(r)?x(e).position()[n]+"px":r):t}}})}),x.expr&&x.expr.filters&&(x.expr.filters.hidden=function(e){return 0>=e.offsetWidth&&0>=e.offsetHeight||!x.support.reliableHiddenOffsets&&"none"===(e.style&&e.style.display||x.css(e,"display"))},x.expr.filters.visible=function(e){return!x.expr.filters.hidden(e)}),x.each({margin:"",padding:"",border:"Width"},function(e,t){x.cssHooks[e+t]={expand:function(n){var r=0,i={},o="string"==typeof n?n.split(" "):[n];for(;4>r;r++)i[e+Zt[r]+t]=o[r]||o[r-2]||o[0];return i}},Ut.test(e)||(x.cssHooks[e+t].set=on)});var cn=/%20/g,pn=/\[\]$/,fn=/\r?\n/g,dn=/^(?:submit|button|image|reset|file)$/i,hn=/^(?:input|select|textarea|keygen)/i;x.fn.extend({serialize:function(){return x.param(this.serializeArray())},serializeArray:function(){return this.map(function(){var e=x.prop(this,"elements");return e?x.makeArray(e):this}).filter(function(){var e=this.type;return this.name&&!x(this).is(":disabled")&&hn.test(this.nodeName)&&!dn.test(e)&&(this.checked||!Ct.test(e))}).map(function(e,t){var n=x(this).val();return null==n?null:x.isArray(n)?x.map(n,function(e){return{name:t.name,value:e.replace(fn,"\r\n")}}):{name:t.name,value:n.replace(fn,"\r\n")}}).get()}}),x.param=function(e,n){var r,i=[],o=function(e,t){t=x.isFunction(t)?t():null==t?"":t,i[i.length]=encodeURIComponent(e)+"="+encodeURIComponent(t)};if(n===t&&(n=x.ajaxSettings&&x.ajaxSettings.traditional),x.isArray(e)||e.jquery&&!x.isPlainObject(e))x.each(e,function(){o(this.name,this.value)});else for(r in e)gn(r,e[r],n,o);return i.join("&").replace(cn,"+")};function gn(e,t,n,r){var i;if(x.isArray(t))x.each(t,function(t,i){n||pn.test(e)?r(e,i):gn(e+"["+("object"==typeof i?t:"")+"]",i,n,r)});else if(n||"object"!==x.type(t))r(e,t);else for(i in t)gn(e+"["+i+"]",t[i],n,r)}x.each("blur focus focusin focusout load resize scroll unload click dblclick mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave change select submit keydown keypress keyup error contextmenu".split(" "),function(e,t){x.fn[t]=function(e,n){return arguments.length>0?this.on(t,null,e,n):this.trigger(t)}}),x.fn.extend({hover:function(e,t){return this.mouseenter(e).mouseleave(t||e)},bind:function(e,t,n){return this.on(e,null,t,n)},unbind:function(e,t){return this.off(e,null,t)},delegate:function(e,t,n,r){return this.on(t,e,n,r)},undelegate:function(e,t,n){return 1===arguments.length?this.off(e,"**"):this.off(t,e||"**",n)}});var mn,yn,vn=x.now(),bn=/\?/,xn=/#.*$/,wn=/([?&])_=[^&]*/,Tn=/^(.*?):[ \t]*([^\r\n]*)\r?$/gm,Cn=/^(?:about|app|app-storage|.+-extension|file|res|widget):$/,Nn=/^(?:GET|HEAD)$/,kn=/^\/\//,En=/^([\w.+-]+:)(?:\/\/([^\/?#:]*)(?::(\d+)|)|)/,Sn=x.fn.load,An={},jn={},Dn="*/".concat("*");try{yn=o.href}catch(Ln){yn=a.createElement("a"),yn.href="",yn=yn.href}mn=En.exec(yn.toLowerCase())||[];function Hn(e){return function(t,n){"string"!=typeof t&&(n=t,t="*");var r,i=0,o=t.toLowerCase().match(T)||[];if(x.isFunction(n))while(r=o[i++])"+"===r[0]?(r=r.slice(1)||"*",(e[r]=e[r]||[]).unshift(n)):(e[r]=e[r]||[]).push(n)}}function qn(e,n,r,i){var o={},a=e===jn;function s(l){var u;return o[l]=!0,x.each(e[l]||[],function(e,l){var c=l(n,r,i);return"string"!=typeof c||a||o[c]?a?!(u=c):t:(n.dataTypes.unshift(c),s(c),!1)}),u}return s(n.dataTypes[0])||!o["*"]&&s("*")}function _n(e,n){var r,i,o=x.ajaxSettings.flatOptions||{};for(i in n)n[i]!==t&&((o[i]?e:r||(r={}))[i]=n[i]);return r&&x.extend(!0,e,r),e}x.fn.load=function(e,n,r){if("string"!=typeof e&&Sn)return Sn.apply(this,arguments);var i,o,a,s=this,l=e.indexOf(" ");return l>=0&&(i=e.slice(l,e.length),e=e.slice(0,l)),x.isFunction(n)?(r=n,n=t):n&&"object"==typeof n&&(a="POST"),s.length>0&&x.ajax({url:e,type:a,dataType:"html",data:n}).done(function(e){o=arguments,s.html(i?x("<div>").append(x.parseHTML(e)).find(i):e)}).complete(r&&function(e,t){s.each(r,o||[e.responseText,t,e])}),this},x.each(["ajaxStart","ajaxStop","ajaxComplete","ajaxError","ajaxSuccess","ajaxSend"],function(e,t){x.fn[t]=function(e){return this.on(t,e)}}),x.extend({active:0,lastModified:{},etag:{},ajaxSettings:{url:yn,type:"GET",isLocal:Cn.test(mn[1]),global:!0,processData:!0,async:!0,contentType:"application/x-www-form-urlencoded; charset=UTF-8",accepts:{"*":Dn,text:"text/plain",html:"text/html",xml:"application/xml, text/xml",json:"application/json, text/javascript"},contents:{xml:/xml/,html:/html/,json:/json/},responseFields:{xml:"responseXML",text:"responseText",json:"responseJSON"},converters:{"* text":String,"text html":!0,"text json":x.parseJSON,"text xml":x.parseXML},flatOptions:{url:!0,context:!0}},ajaxSetup:function(e,t){return t?_n(_n(e,x.ajaxSettings),t):_n(x.ajaxSettings,e)},ajaxPrefilter:Hn(An),ajaxTransport:Hn(jn),ajax:function(e,n){"object"==typeof e&&(n=e,e=t),n=n||{};var r,i,o,a,s,l,u,c,p=x.ajaxSetup({},n),f=p.context||p,d=p.context&&(f.nodeType||f.jquery)?x(f):x.event,h=x.Deferred(),g=x.Callbacks("once memory"),m=p.statusCode||{},y={},v={},b=0,w="canceled",C={readyState:0,getResponseHeader:function(e){var t;if(2===b){if(!c){c={};while(t=Tn.exec(a))c[t[1].toLowerCase()]=t[2]}t=c[e.toLowerCase()]}return null==t?null:t},getAllResponseHeaders:function(){return 2===b?a:null},setRequestHeader:function(e,t){var n=e.toLowerCase();return b||(e=v[n]=v[n]||e,y[e]=t),this},overrideMimeType:function(e){return b||(p.mimeType=e),this},statusCode:function(e){var t;if(e)if(2>b)for(t in e)m[t]=[m[t],e[t]];else C.always(e[C.status]);return this},abort:function(e){var t=e||w;return u&&u.abort(t),k(0,t),this}};if(h.promise(C).complete=g.add,C.success=C.done,C.error=C.fail,p.url=((e||p.url||yn)+"").replace(xn,"").replace(kn,mn[1]+"//"),p.type=n.method||n.type||p.method||p.type,p.dataTypes=x.trim(p.dataType||"*").toLowerCase().match(T)||[""],null==p.crossDomain&&(r=En.exec(p.url.toLowerCase()),p.crossDomain=!(!r||r[1]===mn[1]&&r[2]===mn[2]&&(r[3]||("http:"===r[1]?"80":"443"))===(mn[3]||("http:"===mn[1]?"80":"443")))),p.data&&p.processData&&"string"!=typeof p.data&&(p.data=x.param(p.data,p.traditional)),qn(An,p,n,C),2===b)return C;l=p.global,l&&0===x.active++&&x.event.trigger("ajaxStart"),p.type=p.type.toUpperCase(),p.hasContent=!Nn.test(p.type),o=p.url,p.hasContent||(p.data&&(o=p.url+=(bn.test(o)?"&":"?")+p.data,delete p.data),p.cache===!1&&(p.url=wn.test(o)?o.replace(wn,"$1_="+vn++):o+(bn.test(o)?"&":"?")+"_="+vn++)),p.ifModified&&(x.lastModified[o]&&C.setRequestHeader("If-Modified-Since",x.lastModified[o]),x.etag[o]&&C.setRequestHeader("If-None-Match",x.etag[o])),(p.data&&p.hasContent&&p.contentType!==!1||n.contentType)&&C.setRequestHeader("Content-Type",p.contentType),C.setRequestHeader("Accept",p.dataTypes[0]&&p.accepts[p.dataTypes[0]]?p.accepts[p.dataTypes[0]]+("*"!==p.dataTypes[0]?", "+Dn+"; q=0.01":""):p.accepts["*"]);for(i in p.headers)C.setRequestHeader(i,p.headers[i]);if(p.beforeSend&&(p.beforeSend.call(f,C,p)===!1||2===b))return C.abort();w="abort";for(i in{success:1,error:1,complete:1})C[i](p[i]);if(u=qn(jn,p,n,C)){C.readyState=1,l&&d.trigger("ajaxSend",[C,p]),p.async&&p.timeout>0&&(s=setTimeout(function(){C.abort("timeout")},p.timeout));try{b=1,u.send(y,k)}catch(N){if(!(2>b))throw N;k(-1,N)}}else k(-1,"No Transport");function k(e,n,r,i){var c,y,v,w,T,N=n;2!==b&&(b=2,s&&clearTimeout(s),u=t,a=i||"",C.readyState=e>0?4:0,c=e>=200&&300>e||304===e,r&&(w=Mn(p,C,r)),w=On(p,w,C,c),c?(p.ifModified&&(T=C.getResponseHeader("Last-Modified"),T&&(x.lastModified[o]=T),T=C.getResponseHeader("etag"),T&&(x.etag[o]=T)),204===e||"HEAD"===p.type?N="nocontent":304===e?N="notmodified":(N=w.state,y=w.data,v=w.error,c=!v)):(v=N,(e||!N)&&(N="error",0>e&&(e=0))),C.status=e,C.statusText=(n||N)+"",c?h.resolveWith(f,[y,N,C]):h.rejectWith(f,[C,N,v]),C.statusCode(m),m=t,l&&d.trigger(c?"ajaxSuccess":"ajaxError",[C,p,c?y:v]),g.fireWith(f,[C,N]),l&&(d.trigger("ajaxComplete",[C,p]),--x.active||x.event.trigger("ajaxStop")))}return C},getJSON:function(e,t,n){return x.get(e,t,n,"json")},getScript:function(e,n){return x.get(e,t,n,"script")}}),x.each(["get","post"],function(e,n){x[n]=function(e,r,i,o){return x.isFunction(r)&&(o=o||i,i=r,r=t),x.ajax({url:e,type:n,dataType:o,data:r,success:i})}});function Mn(e,n,r){var i,o,a,s,l=e.contents,u=e.dataTypes;while("*"===u[0])u.shift(),o===t&&(o=e.mimeType||n.getResponseHeader("Content-Type"));if(o)for(s in l)if(l[s]&&l[s].test(o)){u.unshift(s);break}if(u[0]in r)a=u[0];else{for(s in r){if(!u[0]||e.converters[s+" "+u[0]]){a=s;break}i||(i=s)}a=a||i}return a?(a!==u[0]&&u.unshift(a),r[a]):t}function On(e,t,n,r){var i,o,a,s,l,u={},c=e.dataTypes.slice();if(c[1])for(a in e.converters)u[a.toLowerCase()]=e.converters[a];o=c.shift();while(o)if(e.responseFields[o]&&(n[e.responseFields[o]]=t),!l&&r&&e.dataFilter&&(t=e.dataFilter(t,e.dataType)),l=o,o=c.shift())if("*"===o)o=l;else if("*"!==l&&l!==o){if(a=u[l+" "+o]||u["* "+o],!a)for(i in u)if(s=i.split(" "),s[1]===o&&(a=u[l+" "+s[0]]||u["* "+s[0]])){a===!0?a=u[i]:u[i]!==!0&&(o=s[0],c.unshift(s[1]));break}if(a!==!0)if(a&&e["throws"])t=a(t);else try{t=a(t)}catch(p){return{state:"parsererror",error:a?p:"No conversion from "+l+" to "+o}}}return{state:"success",data:t}}x.ajaxSetup({accepts:{script:"text/javascript, application/javascript, application/ecmascript, application/x-ecmascript"},contents:{script:/(?:java|ecma)script/},converters:{"text script":function(e){return x.globalEval(e),e}}}),x.ajaxPrefilter("script",function(e){e.cache===t&&(e.cache=!1),e.crossDomain&&(e.type="GET",e.global=!1)}),x.ajaxTransport("script",function(e){if(e.crossDomain){var n,r=a.head||x("head")[0]||a.documentElement;return{send:function(t,i){n=a.createElement("script"),n.async=!0,e.scriptCharset&&(n.charset=e.scriptCharset),n.src=e.url,n.onload=n.onreadystatechange=function(e,t){(t||!n.readyState||/loaded|complete/.test(n.readyState))&&(n.onload=n.onreadystatechange=null,n.parentNode&&n.parentNode.removeChild(n),n=null,t||i(200,"success"))},r.insertBefore(n,r.firstChild)},abort:function(){n&&n.onload(t,!0)}}}});var Fn=[],Bn=/(=)\?(?=&|$)|\?\?/;x.ajaxSetup({jsonp:"callback",jsonpCallback:function(){var e=Fn.pop()||x.expando+"_"+vn++;return this[e]=!0,e}}),x.ajaxPrefilter("json jsonp",function(n,r,i){var o,a,s,l=n.jsonp!==!1&&(Bn.test(n.url)?"url":"string"==typeof n.data&&!(n.contentType||"").indexOf("application/x-www-form-urlencoded")&&Bn.test(n.data)&&"data");return l||"jsonp"===n.dataTypes[0]?(o=n.jsonpCallback=x.isFunction(n.jsonpCallback)?n.jsonpCallback():n.jsonpCallback,l?n[l]=n[l].replace(Bn,"$1"+o):n.jsonp!==!1&&(n.url+=(bn.test(n.url)?"&":"?")+n.jsonp+"="+o),n.converters["script json"]=function(){return s||x.error(o+" was not called"),s[0]},n.dataTypes[0]="json",a=e[o],e[o]=function(){s=arguments},i.always(function(){e[o]=a,n[o]&&(n.jsonpCallback=r.jsonpCallback,Fn.push(o)),s&&x.isFunction(a)&&a(s[0]),s=a=t}),"script"):t});var Pn,Rn,Wn=0,$n=e.ActiveXObject&&function(){var e;for(e in Pn)Pn[e](t,!0)};function In(){try{return new e.XMLHttpRequest}catch(t){}}function zn(){try{return new e.ActiveXObject("Microsoft.XMLHTTP")}catch(t){}}x.ajaxSettings.xhr=e.ActiveXObject?function(){return!this.isLocal&&In()||zn()}:In,Rn=x.ajaxSettings.xhr(),x.support.cors=!!Rn&&"withCredentials"in Rn,Rn=x.support.ajax=!!Rn,Rn&&x.ajaxTransport(function(n){if(!n.crossDomain||x.support.cors){var r;return{send:function(i,o){var a,s,l=n.xhr();if(n.username?l.open(n.type,n.url,n.async,n.username,n.password):l.open(n.type,n.url,n.async),n.xhrFields)for(s in n.xhrFields)l[s]=n.xhrFields[s];n.mimeType&&l.overrideMimeType&&l.overrideMimeType(n.mimeType),n.crossDomain||i["X-Requested-With"]||(i["X-Requested-With"]="XMLHttpRequest");try{for(s in i)l.setRequestHeader(s,i[s])}catch(u){}l.send(n.hasContent&&n.data||null),r=function(e,i){var s,u,c,p;try{if(r&&(i||4===l.readyState))if(r=t,a&&(l.onreadystatechange=x.noop,$n&&delete Pn[a]),i)4!==l.readyState&&l.abort();else{p={},s=l.status,u=l.getAllResponseHeaders(),"string"==typeof l.responseText&&(p.text=l.responseText);try{c=l.statusText}catch(f){c=""}s||!n.isLocal||n.crossDomain?1223===s&&(s=204):s=p.text?200:404}}catch(d){i||o(-1,d)}p&&o(s,c,p,u)},n.async?4===l.readyState?setTimeout(r):(a=++Wn,$n&&(Pn||(Pn={},x(e).unload($n)),Pn[a]=r),l.onreadystatechange=r):r()},abort:function(){r&&r(t,!0)}}}});var Xn,Un,Vn=/^(?:toggle|show|hide)$/,Yn=RegExp("^(?:([+-])=|)("+w+")([a-z%]*)$","i"),Jn=/queueHooks$/,Gn=[nr],Qn={"*":[function(e,t){var n=this.createTween(e,t),r=n.cur(),i=Yn.exec(t),o=i&&i[3]||(x.cssNumber[e]?"":"px"),a=(x.cssNumber[e]||"px"!==o&&+r)&&Yn.exec(x.css(n.elem,e)),s=1,l=20;if(a&&a[3]!==o){o=o||a[3],i=i||[],a=+r||1;do s=s||".5",a/=s,x.style(n.elem,e,a+o);while(s!==(s=n.cur()/r)&&1!==s&&--l)}return i&&(a=n.start=+a||+r||0,n.unit=o,n.end=i[1]?a+(i[1]+1)*i[2]:+i[2]),n}]};function Kn(){return setTimeout(function(){Xn=t}),Xn=x.now()}function Zn(e,t,n){var r,i=(Qn[t]||[]).concat(Qn["*"]),o=0,a=i.length;for(;a>o;o++)if(r=i[o].call(n,t,e))return r}function er(e,t,n){var r,i,o=0,a=Gn.length,s=x.Deferred().always(function(){delete l.elem}),l=function(){if(i)return!1;var t=Xn||Kn(),n=Math.max(0,u.startTime+u.duration-t),r=n/u.duration||0,o=1-r,a=0,l=u.tweens.length;for(;l>a;a++)u.tweens[a].run(o);return s.notifyWith(e,[u,o,n]),1>o&&l?n:(s.resolveWith(e,[u]),!1)},u=s.promise({elem:e,props:x.extend({},t),opts:x.extend(!0,{specialEasing:{}},n),originalProperties:t,originalOptions:n,startTime:Xn||Kn(),duration:n.duration,tweens:[],createTween:function(t,n){var r=x.Tween(e,u.opts,t,n,u.opts.specialEasing[t]||u.opts.easing);return u.tweens.push(r),r},stop:function(t){var n=0,r=t?u.tweens.length:0;if(i)return this;for(i=!0;r>n;n++)u.tweens[n].run(1);return t?s.resolveWith(e,[u,t]):s.rejectWith(e,[u,t]),this}}),c=u.props;for(tr(c,u.opts.specialEasing);a>o;o++)if(r=Gn[o].call(u,e,c,u.opts))return r;return x.map(c,Zn,u),x.isFunction(u.opts.start)&&u.opts.start.call(e,u),x.fx.timer(x.extend(l,{elem:e,anim:u,queue:u.opts.queue})),u.progress(u.opts.progress).done(u.opts.done,u.opts.complete).fail(u.opts.fail).always(u.opts.always)}function tr(e,t){var n,r,i,o,a;for(n in e)if(r=x.camelCase(n),i=t[r],o=e[n],x.isArray(o)&&(i=o[1],o=e[n]=o[0]),n!==r&&(e[r]=o,delete e[n]),a=x.cssHooks[r],a&&"expand"in a){o=a.expand(o),delete e[r];for(n in o)n in e||(e[n]=o[n],t[n]=i)}else t[r]=i}x.Animation=x.extend(er,{tweener:function(e,t){x.isFunction(e)?(t=e,e=["*"]):e=e.split(" ");var n,r=0,i=e.length;for(;i>r;r++)n=e[r],Qn[n]=Qn[n]||[],Qn[n].unshift(t)},prefilter:function(e,t){t?Gn.unshift(e):Gn.push(e)}});function nr(e,t,n){var r,i,o,a,s,l,u=this,c={},p=e.style,f=e.nodeType&&nn(e),d=x._data(e,"fxshow");n.queue||(s=x._queueHooks(e,"fx"),null==s.unqueued&&(s.unqueued=0,l=s.empty.fire,s.empty.fire=function(){s.unqueued||l()}),s.unqueued++,u.always(function(){u.always(function(){s.unqueued--,x.queue(e,"fx").length||s.empty.fire()})})),1===e.nodeType&&("height"in t||"width"in t)&&(n.overflow=[p.overflow,p.overflowX,p.overflowY],"inline"===x.css(e,"display")&&"none"===x.css(e,"float")&&(x.support.inlineBlockNeedsLayout&&"inline"!==ln(e.nodeName)?p.zoom=1:p.display="inline-block")),n.overflow&&(p.overflow="hidden",x.support.shrinkWrapBlocks||u.always(function(){p.overflow=n.overflow[0],p.overflowX=n.overflow[1],p.overflowY=n.overflow[2]}));for(r in t)if(i=t[r],Vn.exec(i)){if(delete t[r],o=o||"toggle"===i,i===(f?"hide":"show"))continue;c[r]=d&&d[r]||x.style(e,r)}if(!x.isEmptyObject(c)){d?"hidden"in d&&(f=d.hidden):d=x._data(e,"fxshow",{}),o&&(d.hidden=!f),f?x(e).show():u.done(function(){x(e).hide()}),u.done(function(){var t;x._removeData(e,"fxshow");for(t in c)x.style(e,t,c[t])});for(r in c)a=Zn(f?d[r]:0,r,u),r in d||(d[r]=a.start,f&&(a.end=a.start,a.start="width"===r||"height"===r?1:0))}}function rr(e,t,n,r,i){return new rr.prototype.init(e,t,n,r,i)}x.Tween=rr,rr.prototype={constructor:rr,init:function(e,t,n,r,i,o){this.elem=e,this.prop=n,this.easing=i||"swing",this.options=t,this.start=this.now=this.cur(),this.end=r,this.unit=o||(x.cssNumber[n]?"":"px")},cur:function(){var e=rr.propHooks[this.prop];return e&&e.get?e.get(this):rr.propHooks._default.get(this)},run:function(e){var t,n=rr.propHooks[this.prop];return this.pos=t=this.options.duration?x.easing[this.easing](e,this.options.duration*e,0,1,this.options.duration):e,this.now=(this.end-this.start)*t+this.start,this.options.step&&this.options.step.call(this.elem,this.now,this),n&&n.set?n.set(this):rr.propHooks._default.set(this),this}},rr.prototype.init.prototype=rr.prototype,rr.propHooks={_default:{get:function(e){var t;return null==e.elem[e.prop]||e.elem.style&&null!=e.elem.style[e.prop]?(t=x.css(e.elem,e.prop,""),t&&"auto"!==t?t:0):e.elem[e.prop]},set:function(e){x.fx.step[e.prop]?x.fx.step[e.prop](e):e.elem.style&&(null!=e.elem.style[x.cssProps[e.prop]]||x.cssHooks[e.prop])?x.style(e.elem,e.prop,e.now+e.unit):e.elem[e.prop]=e.now}}},rr.propHooks.scrollTop=rr.propHooks.scrollLeft={set:function(e){e.elem.nodeType&&e.elem.parentNode&&(e.elem[e.prop]=e.now)}},x.each(["toggle","show","hide"],function(e,t){var n=x.fn[t];x.fn[t]=function(e,r,i){return null==e||"boolean"==typeof e?n.apply(this,arguments):this.animate(ir(t,!0),e,r,i)}}),x.fn.extend({fadeTo:function(e,t,n,r){return this.filter(nn).css("opacity",0).show().end().animate({opacity:t},e,n,r)},animate:function(e,t,n,r){var i=x.isEmptyObject(e),o=x.speed(t,n,r),a=function(){var t=er(this,x.extend({},e),o);(i||x._data(this,"finish"))&&t.stop(!0)};return a.finish=a,i||o.queue===!1?this.each(a):this.queue(o.queue,a)},stop:function(e,n,r){var i=function(e){var t=e.stop;delete e.stop,t(r)};return"string"!=typeof e&&(r=n,n=e,e=t),n&&e!==!1&&this.queue(e||"fx",[]),this.each(function(){var t=!0,n=null!=e&&e+"queueHooks",o=x.timers,a=x._data(this);if(n)a[n]&&a[n].stop&&i(a[n]);else for(n in a)a[n]&&a[n].stop&&Jn.test(n)&&i(a[n]);for(n=o.length;n--;)o[n].elem!==this||null!=e&&o[n].queue!==e||(o[n].anim.stop(r),t=!1,o.splice(n,1));(t||!r)&&x.dequeue(this,e)})},finish:function(e){return e!==!1&&(e=e||"fx"),this.each(function(){var t,n=x._data(this),r=n[e+"queue"],i=n[e+"queueHooks"],o=x.timers,a=r?r.length:0;for(n.finish=!0,x.queue(this,e,[]),i&&i.stop&&i.stop.call(this,!0),t=o.length;t--;)o[t].elem===this&&o[t].queue===e&&(o[t].anim.stop(!0),o.splice(t,1));for(t=0;a>t;t++)r[t]&&r[t].finish&&r[t].finish.call(this);delete n.finish})}});function ir(e,t){var n,r={height:e},i=0;for(t=t?1:0;4>i;i+=2-t)n=Zt[i],r["margin"+n]=r["padding"+n]=e;return t&&(r.opacity=r.width=e),r}x.each({slideDown:ir("show"),slideUp:ir("hide"),slideToggle:ir("toggle"),fadeIn:{opacity:"show"},fadeOut:{opacity:"hide"},fadeToggle:{opacity:"toggle"}},function(e,t){x.fn[e]=function(e,n,r){return this.animate(t,e,n,r)}}),x.speed=function(e,t,n){var r=e&&"object"==typeof e?x.extend({},e):{complete:n||!n&&t||x.isFunction(e)&&e,duration:e,easing:n&&t||t&&!x.isFunction(t)&&t};return r.duration=x.fx.off?0:"number"==typeof r.duration?r.duration:r.duration in x.fx.speeds?x.fx.speeds[r.duration]:x.fx.speeds._default,(null==r.queue||r.queue===!0)&&(r.queue="fx"),r.old=r.complete,r.complete=function(){x.isFunction(r.old)&&r.old.call(this),r.queue&&x.dequeue(this,r.queue)},r},x.easing={linear:function(e){return e},swing:function(e){return.5-Math.cos(e*Math.PI)/2}},x.timers=[],x.fx=rr.prototype.init,x.fx.tick=function(){var e,n=x.timers,r=0;for(Xn=x.now();n.length>r;r++)e=n[r],e()||n[r]!==e||n.splice(r--,1);n.length||x.fx.stop(),Xn=t},x.fx.timer=function(e){e()&&x.timers.push(e)&&x.fx.start()},x.fx.interval=13,x.fx.start=function(){Un||(Un=setInterval(x.fx.tick,x.fx.interval))},x.fx.stop=function(){clearInterval(Un),Un=null},x.fx.speeds={slow:600,fast:200,_default:400},x.fx.step={},x.expr&&x.expr.filters&&(x.expr.filters.animated=function(e){return x.grep(x.timers,function(t){return e===t.elem}).length}),x.fn.offset=function(e){if(arguments.length)return e===t?this:this.each(function(t){x.offset.setOffset(this,e,t)});var n,r,o={top:0,left:0},a=this[0],s=a&&a.ownerDocument;if(s)return n=s.documentElement,x.contains(n,a)?(typeof a.getBoundingClientRect!==i&&(o=a.getBoundingClientRect()),r=or(s),{top:o.top+(r.pageYOffset||n.scrollTop)-(n.clientTop||0),left:o.left+(r.pageXOffset||n.scrollLeft)-(n.clientLeft||0)}):o},x.offset={setOffset:function(e,t,n){var r=x.css(e,"position");"static"===r&&(e.style.position="relative");var i=x(e),o=i.offset(),a=x.css(e,"top"),s=x.css(e,"left"),l=("absolute"===r||"fixed"===r)&&x.inArray("auto",[a,s])>-1,u={},c={},p,f;l?(c=i.position(),p=c.top,f=c.left):(p=parseFloat(a)||0,f=parseFloat(s)||0),x.isFunction(t)&&(t=t.call(e,n,o)),null!=t.top&&(u.top=t.top-o.top+p),null!=t.left&&(u.left=t.left-o.left+f),"using"in t?t.using.call(e,u):i.css(u)}},x.fn.extend({position:function(){if(this[0]){var e,t,n={top:0,left:0},r=this[0];return"fixed"===x.css(r,"position")?t=r.getBoundingClientRect():(e=this.offsetParent(),t=this.offset(),x.nodeName(e[0],"html")||(n=e.offset()),n.top+=x.css(e[0],"borderTopWidth",!0),n.left+=x.css(e[0],"borderLeftWidth",!0)),{top:t.top-n.top-x.css(r,"marginTop",!0),left:t.left-n.left-x.css(r,"marginLeft",!0)}}},offsetParent:function(){return this.map(function(){var e=this.offsetParent||s;while(e&&!x.nodeName(e,"html")&&"static"===x.css(e,"position"))e=e.offsetParent;return e||s})}}),x.each({scrollLeft:"pageXOffset",scrollTop:"pageYOffset"},function(e,n){var r=/Y/.test(n);x.fn[e]=function(i){return x.access(this,function(e,i,o){var a=or(e);return o===t?a?n in a?a[n]:a.document.documentElement[i]:e[i]:(a?a.scrollTo(r?x(a).scrollLeft():o,r?o:x(a).scrollTop()):e[i]=o,t)},e,i,arguments.length,null)}});function or(e){return x.isWindow(e)?e:9===e.nodeType?e.defaultView||e.parentWindow:!1}x.each({Height:"height",Width:"width"},function(e,n){x.each({padding:"inner"+e,content:n,"":"outer"+e},function(r,i){x.fn[i]=function(i,o){var a=arguments.length&&(r||"boolean"!=typeof i),s=r||(i===!0||o===!0?"margin":"border");return x.access(this,function(n,r,i){var o;return x.isWindow(n)?n.document.documentElement["client"+e]:9===n.nodeType?(o=n.documentElement,Math.max(n.body["scroll"+e],o["scroll"+e],n.body["offset"+e],o["offset"+e],o["client"+e])):i===t?x.css(n,r,s):x.style(n,r,i,s)},n,a?i:t,a,null)}})}),x.fn.size=function(){return this.length},x.fn.andSelf=x.fn.addBack,"object"==typeof module&&module&&"object"==typeof module.exports?module.exports=x:(e.jQuery=e.$=x,"function"==typeof define&&define.amd&&define("jquery",[],function(){return x}))})(window);/**
* bootstrap.js v3.0.0 by @fat and @mdo
* Copyright 2013 Twitter Inc.
* http://www.apache.org/licenses/LICENSE-2.0
*/
if(!jQuery)throw new Error("Bootstrap requires jQuery");+function(a){"use strict";function b(){var a=document.createElement("bootstrap"),b={WebkitTransition:"webkitTransitionEnd",MozTransition:"transitionend",OTransition:"oTransitionEnd otransitionend",transition:"transitionend"};for(var c in b)if(void 0!==a.style[c])return{end:b[c]}}a.fn.emulateTransitionEnd=function(b){var c=!1,d=this;a(this).one(a.support.transition.end,function(){c=!0});var e=function(){c||a(d).trigger(a.support.transition.end)};return setTimeout(e,b),this},a(function(){a.support.transition=b()})}(window.jQuery),+function(a){"use strict";var b='[data-dismiss="alert"]',c=function(c){a(c).on("click",b,this.close)};c.prototype.close=function(b){function c(){f.trigger("closed.bs.alert").remove()}var d=a(this),e=d.attr("data-target");e||(e=d.attr("href"),e=e&&e.replace(/.*(?=#[^\s]*$)/,""));var f=a(e);b&&b.preventDefault(),f.length||(f=d.hasClass("alert")?d:d.parent()),f.trigger(b=a.Event("close.bs.alert")),b.isDefaultPrevented()||(f.removeClass("in"),a.support.transition&&f.hasClass("fade")?f.one(a.support.transition.end,c).emulateTransitionEnd(150):c())};var d=a.fn.alert;a.fn.alert=function(b){return this.each(function(){var d=a(this),e=d.data("bs.alert");e||d.data("bs.alert",e=new c(this)),"string"==typeof b&&e[b].call(d)})},a.fn.alert.Constructor=c,a.fn.alert.noConflict=function(){return a.fn.alert=d,this},a(document).on("click.bs.alert.data-api",b,c.prototype.close)}(window.jQuery),+function(a){"use strict";var b=function(c,d){this.$element=a(c),this.options=a.extend({},b.DEFAULTS,d)};b.DEFAULTS={loadingText:"loading..."},b.prototype.setState=function(a){var b="disabled",c=this.$element,d=c.is("input")?"val":"html",e=c.data();a+="Text",e.resetText||c.data("resetText",c[d]()),c[d](e[a]||this.options[a]),setTimeout(function(){"loadingText"==a?c.addClass(b).attr(b,b):c.removeClass(b).removeAttr(b)},0)},b.prototype.toggle=function(){var a=this.$element.closest('[data-toggle="buttons"]');if(a.length){var b=this.$element.find("input").prop("checked",!this.$element.hasClass("active")).trigger("change");"radio"===b.prop("type")&&a.find(".active").removeClass("active")}this.$element.toggleClass("active")};var c=a.fn.button;a.fn.button=function(c){return this.each(function(){var d=a(this),e=d.data("bs.button"),f="object"==typeof c&&c;e||d.data("bs.button",e=new b(this,f)),"toggle"==c?e.toggle():c&&e.setState(c)})},a.fn.button.Constructor=b,a.fn.button.noConflict=function(){return a.fn.button=c,this},a(document).on("click.bs.button.data-api","[data-toggle^=button]",function(b){var c=a(b.target);c.hasClass("btn")||(c=c.closest(".btn")),c.button("toggle"),b.preventDefault()})}(window.jQuery),+function(a){"use strict";var b=function(b,c){this.$element=a(b),this.$indicators=this.$element.find(".carousel-indicators"),this.options=c,this.paused=this.sliding=this.interval=this.$active=this.$items=null,"hover"==this.options.pause&&this.$element.on("mouseenter",a.proxy(this.pause,this)).on("mouseleave",a.proxy(this.cycle,this))};b.DEFAULTS={interval:5e3,pause:"hover",wrap:!0},b.prototype.cycle=function(b){return b||(this.paused=!1),this.interval&&clearInterval(this.interval),this.options.interval&&!this.paused&&(this.interval=setInterval(a.proxy(this.next,this),this.options.interval)),this},b.prototype.getActiveIndex=function(){return this.$active=this.$element.find(".item.active"),this.$items=this.$active.parent().children(),this.$items.index(this.$active)},b.prototype.to=function(b){var c=this,d=this.getActiveIndex();return b>this.$items.length-1||0>b?void 0:this.sliding?this.$element.one("slid",function(){c.to(b)}):d==b?this.pause().cycle():this.slide(b>d?"next":"prev",a(this.$items[b]))},b.prototype.pause=function(b){return b||(this.paused=!0),this.$element.find(".next, .prev").length&&a.support.transition.end&&(this.$element.trigger(a.support.transition.end),this.cycle(!0)),this.interval=clearInterval(this.interval),this},b.prototype.next=function(){return this.sliding?void 0:this.slide("next")},b.prototype.prev=function(){return this.sliding?void 0:this.slide("prev")},b.prototype.slide=function(b,c){var d=this.$element.find(".item.active"),e=c||d[b](),f=this.interval,g="next"==b?"left":"right",h="next"==b?"first":"last",i=this;if(!e.length){if(!this.options.wrap)return;e=this.$element.find(".item")[h]()}this.sliding=!0,f&&this.pause();var j=a.Event("slide.bs.carousel",{relatedTarget:e[0],direction:g});if(!e.hasClass("active")){if(this.$indicators.length&&(this.$indicators.find(".active").removeClass("active"),this.$element.one("slid",function(){var b=a(i.$indicators.children()[i.getActiveIndex()]);b&&b.addClass("active")})),a.support.transition&&this.$element.hasClass("slide")){if(this.$element.trigger(j),j.isDefaultPrevented())return;e.addClass(b),e[0].offsetWidth,d.addClass(g),e.addClass(g),d.one(a.support.transition.end,function(){e.removeClass([b,g].join(" ")).addClass("active"),d.removeClass(["active",g].join(" ")),i.sliding=!1,setTimeout(function(){i.$element.trigger("slid")},0)}).emulateTransitionEnd(600)}else{if(this.$element.trigger(j),j.isDefaultPrevented())return;d.removeClass("active"),e.addClass("active"),this.sliding=!1,this.$element.trigger("slid")}return f&&this.cycle(),this}};var c=a.fn.carousel;a.fn.carousel=function(c){return this.each(function(){var d=a(this),e=d.data("bs.carousel"),f=a.extend({},b.DEFAULTS,d.data(),"object"==typeof c&&c),g="string"==typeof c?c:f.slide;e||d.data("bs.carousel",e=new b(this,f)),"number"==typeof c?e.to(c):g?e[g]():f.interval&&e.pause().cycle()})},a.fn.carousel.Constructor=b,a.fn.carousel.noConflict=function(){return a.fn.carousel=c,this},a(document).on("click.bs.carousel.data-api","[data-slide], [data-slide-to]",function(b){var c,d=a(this),e=a(d.attr("data-target")||(c=d.attr("href"))&&c.replace(/.*(?=#[^\s]+$)/,"")),f=a.extend({},e.data(),d.data()),g=d.attr("data-slide-to");g&&(f.interval=!1),e.carousel(f),(g=d.attr("data-slide-to"))&&e.data("bs.carousel").to(g),b.preventDefault()}),a(window).on("load",function(){a('[data-ride="carousel"]').each(function(){var b=a(this);b.carousel(b.data())})})}(window.jQuery),+function(a){"use strict";var b=function(c,d){this.$element=a(c),this.options=a.extend({},b.DEFAULTS,d),this.transitioning=null,this.options.parent&&(this.$parent=a(this.options.parent)),this.options.toggle&&this.toggle()};b.DEFAULTS={toggle:!0},b.prototype.dimension=function(){var a=this.$element.hasClass("width");return a?"width":"height"},b.prototype.show=function(){if(!this.transitioning&&!this.$element.hasClass("in")){var b=a.Event("show.bs.collapse");if(this.$element.trigger(b),!b.isDefaultPrevented()){var c=this.$parent&&this.$parent.find("> .panel > .in");if(c&&c.length){var d=c.data("bs.collapse");if(d&&d.transitioning)return;c.collapse("hide"),d||c.data("bs.collapse",null)}var e=this.dimension();this.$element.removeClass("collapse").addClass("collapsing")[e](0),this.transitioning=1;var f=function(){this.$element.removeClass("collapsing").addClass("in")[e]("auto"),this.transitioning=0,this.$element.trigger("shown.bs.collapse")};if(!a.support.transition)return f.call(this);var g=a.camelCase(["scroll",e].join("-"));this.$element.one(a.support.transition.end,a.proxy(f,this)).emulateTransitionEnd(350)[e](this.$element[0][g])}}},b.prototype.hide=function(){if(!this.transitioning&&this.$element.hasClass("in")){var b=a.Event("hide.bs.collapse");if(this.$element.trigger(b),!b.isDefaultPrevented()){var c=this.dimension();this.$element[c](this.$element[c]())[0].offsetHeight,this.$element.addClass("collapsing").removeClass("collapse").removeClass("in"),this.transitioning=1;var d=function(){this.transitioning=0,this.$element.trigger("hidden.bs.collapse").removeClass("collapsing").addClass("collapse")};return a.support.transition?(this.$element[c](0).one(a.support.transition.end,a.proxy(d,this)).emulateTransitionEnd(350),void 0):d.call(this)}}},b.prototype.toggle=function(){this[this.$element.hasClass("in")?"hide":"show"]()};var c=a.fn.collapse;a.fn.collapse=function(c){return this.each(function(){var d=a(this),e=d.data("bs.collapse"),f=a.extend({},b.DEFAULTS,d.data(),"object"==typeof c&&c);e||d.data("bs.collapse",e=new b(this,f)),"string"==typeof c&&e[c]()})},a.fn.collapse.Constructor=b,a.fn.collapse.noConflict=function(){return a.fn.collapse=c,this},a(document).on("click.bs.collapse.data-api","[data-toggle=collapse]",function(b){var c,d=a(this),e=d.attr("data-target")||b.preventDefault()||(c=d.attr("href"))&&c.replace(/.*(?=#[^\s]+$)/,""),f=a(e),g=f.data("bs.collapse"),h=g?"toggle":d.data(),i=d.attr("data-parent"),j=i&&a(i);g&&g.transitioning||(j&&j.find('[data-toggle=collapse][data-parent="'+i+'"]').not(d).addClass("collapsed"),d[f.hasClass("in")?"addClass":"removeClass"]("collapsed")),f.collapse(h)})}(window.jQuery),+function(a){"use strict";function b(){a(d).remove(),a(e).each(function(b){var d=c(a(this));d.hasClass("open")&&(d.trigger(b=a.Event("hide.bs.dropdown")),b.isDefaultPrevented()||d.removeClass("open").trigger("hidden.bs.dropdown"))})}function c(b){var c=b.attr("data-target");c||(c=b.attr("href"),c=c&&/#/.test(c)&&c.replace(/.*(?=#[^\s]*$)/,""));var d=c&&a(c);return d&&d.length?d:b.parent()}var d=".dropdown-backdrop",e="[data-toggle=dropdown]",f=function(b){a(b).on("click.bs.dropdown",this.toggle)};f.prototype.toggle=function(d){var e=a(this);if(!e.is(".disabled, :disabled")){var f=c(e),g=f.hasClass("open");if(b(),!g){if("ontouchstart"in document.documentElement&&!f.closest(".navbar-nav").length&&a('<div class="dropdown-backdrop"/>').insertAfter(a(this)).on("click",b),f.trigger(d=a.Event("show.bs.dropdown")),d.isDefaultPrevented())return;f.toggleClass("open").trigger("shown.bs.dropdown"),e.focus()}return!1}},f.prototype.keydown=function(b){if(/(38|40|27)/.test(b.keyCode)){var d=a(this);if(b.preventDefault(),b.stopPropagation(),!d.is(".disabled, :disabled")){var f=c(d),g=f.hasClass("open");if(!g||g&&27==b.keyCode)return 27==b.which&&f.find(e).focus(),d.click();var h=a("[role=menu] li:not(.divider):visible a",f);if(h.length){var i=h.index(h.filter(":focus"));38==b.keyCode&&i>0&&i--,40==b.keyCode&&i<h.length-1&&i++,~i||(i=0),h.eq(i).focus()}}}};var g=a.fn.dropdown;a.fn.dropdown=function(b){return this.each(function(){var c=a(this),d=c.data("dropdown");d||c.data("dropdown",d=new f(this)),"string"==typeof b&&d[b].call(c)})},a.fn.dropdown.Constructor=f,a.fn.dropdown.noConflict=function(){return a.fn.dropdown=g,this},a(document).on("click.bs.dropdown.data-api",b).on("click.bs.dropdown.data-api",".dropdown form",function(a){a.stopPropagation()}).on("click.bs.dropdown.data-api",e,f.prototype.toggle).on("keydown.bs.dropdown.data-api",e+", [role=menu]",f.prototype.keydown)}(window.jQuery),+function(a){"use strict";var b=function(b,c){this.options=c,this.$element=a(b),this.$backdrop=this.isShown=null,this.options.remote&&this.$element.load(this.options.remote)};b.DEFAULTS={backdrop:!0,keyboard:!0,show:!0},b.prototype.toggle=function(a){return this[this.isShown?"hide":"show"](a)},b.prototype.show=function(b){var c=this,d=a.Event("show.bs.modal",{relatedTarget:b});this.$element.trigger(d),this.isShown||d.isDefaultPrevented()||(this.isShown=!0,this.escape(),this.$element.on("click.dismiss.modal",'[data-dismiss="modal"]',a.proxy(this.hide,this)),this.backdrop(function(){var d=a.support.transition&&c.$element.hasClass("fade");c.$element.parent().length||c.$element.appendTo(document.body),c.$element.show(),d&&c.$element[0].offsetWidth,c.$element.addClass("in").attr("aria-hidden",!1),c.enforceFocus();var e=a.Event("shown.bs.modal",{relatedTarget:b});d?c.$element.find(".modal-dialog").one(a.support.transition.end,function(){c.$element.focus().trigger(e)}).emulateTransitionEnd(300):c.$element.focus().trigger(e)}))},b.prototype.hide=function(b){b&&b.preventDefault(),b=a.Event("hide.bs.modal"),this.$element.trigger(b),this.isShown&&!b.isDefaultPrevented()&&(this.isShown=!1,this.escape(),a(document).off("focusin.bs.modal"),this.$element.removeClass("in").attr("aria-hidden",!0).off("click.dismiss.modal"),a.support.transition&&this.$element.hasClass("fade")?this.$element.one(a.support.transition.end,a.proxy(this.hideModal,this)).emulateTransitionEnd(300):this.hideModal())},b.prototype.enforceFocus=function(){a(document).off("focusin.bs.modal").on("focusin.bs.modal",a.proxy(function(a){this.$element[0]===a.target||this.$element.has(a.target).length||this.$element.focus()},this))},b.prototype.escape=function(){this.isShown&&this.options.keyboard?this.$element.on("keyup.dismiss.bs.modal",a.proxy(function(a){27==a.which&&this.hide()},this)):this.isShown||this.$element.off("keyup.dismiss.bs.modal")},b.prototype.hideModal=function(){var a=this;this.$element.hide(),this.backdrop(function(){a.removeBackdrop(),a.$element.trigger("hidden.bs.modal")})},b.prototype.removeBackdrop=function(){this.$backdrop&&this.$backdrop.remove(),this.$backdrop=null},b.prototype.backdrop=function(b){var c=this.$element.hasClass("fade")?"fade":"";if(this.isShown&&this.options.backdrop){var d=a.support.transition&&c;if(this.$backdrop=a('<div class="modal-backdrop '+c+'" />').appendTo(document.body),this.$element.on("click.dismiss.modal",a.proxy(function(a){a.target===a.currentTarget&&("static"==this.options.backdrop?this.$element[0].focus.call(this.$element[0]):this.hide.call(this))},this)),d&&this.$backdrop[0].offsetWidth,this.$backdrop.addClass("in"),!b)return;d?this.$backdrop.one(a.support.transition.end,b).emulateTransitionEnd(150):b()}else!this.isShown&&this.$backdrop?(this.$backdrop.removeClass("in"),a.support.transition&&this.$element.hasClass("fade")?this.$backdrop.one(a.support.transition.end,b).emulateTransitionEnd(150):b()):b&&b()};var c=a.fn.modal;a.fn.modal=function(c,d){return this.each(function(){var e=a(this),f=e.data("bs.modal"),g=a.extend({},b.DEFAULTS,e.data(),"object"==typeof c&&c);f||e.data("bs.modal",f=new b(this,g)),"string"==typeof c?f[c](d):g.show&&f.show(d)})},a.fn.modal.Constructor=b,a.fn.modal.noConflict=function(){return a.fn.modal=c,this},a(document).on("click.bs.modal.data-api",'[data-toggle="modal"]',function(b){var c=a(this),d=c.attr("href"),e=a(c.attr("data-target")||d&&d.replace(/.*(?=#[^\s]+$)/,"")),f=e.data("modal")?"toggle":a.extend({remote:!/#/.test(d)&&d},e.data(),c.data());b.preventDefault(),e.modal(f,this).one("hide",function(){c.is(":visible")&&c.focus()})}),a(document).on("show.bs.modal",".modal",function(){a(document.body).addClass("modal-open")}).on("hidden.bs.modal",".modal",function(){a(document.body).removeClass("modal-open")})}(window.jQuery),+function(a){"use strict";var b=function(a,b){this.type=this.options=this.enabled=this.timeout=this.hoverState=this.$element=null,this.init("tooltip",a,b)};b.DEFAULTS={animation:!0,placement:"top",selector:!1,template:'<div class="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>',trigger:"hover focus",title:"",delay:0,html:!1,container:!1},b.prototype.init=function(b,c,d){this.enabled=!0,this.type=b,this.$element=a(c),this.options=this.getOptions(d);for(var e=this.options.trigger.split(" "),f=e.length;f--;){var g=e[f];if("click"==g)this.$element.on("click."+this.type,this.options.selector,a.proxy(this.toggle,this));else if("manual"!=g){var h="hover"==g?"mouseenter":"focus",i="hover"==g?"mouseleave":"blur";this.$element.on(h+"."+this.type,this.options.selector,a.proxy(this.enter,this)),this.$element.on(i+"."+this.type,this.options.selector,a.proxy(this.leave,this))}}this.options.selector?this._options=a.extend({},this.options,{trigger:"manual",selector:""}):this.fixTitle()},b.prototype.getDefaults=function(){return b.DEFAULTS},b.prototype.getOptions=function(b){return b=a.extend({},this.getDefaults(),this.$element.data(),b),b.delay&&"number"==typeof b.delay&&(b.delay={show:b.delay,hide:b.delay}),b},b.prototype.getDelegateOptions=function(){var b={},c=this.getDefaults();return this._options&&a.each(this._options,function(a,d){c[a]!=d&&(b[a]=d)}),b},b.prototype.enter=function(b){var c=b instanceof this.constructor?b:a(b.currentTarget)[this.type](this.getDelegateOptions()).data("bs."+this.type);return clearTimeout(c.timeout),c.hoverState="in",c.options.delay&&c.options.delay.show?(c.timeout=setTimeout(function(){"in"==c.hoverState&&c.show()},c.options.delay.show),void 0):c.show()},b.prototype.leave=function(b){var c=b instanceof this.constructor?b:a(b.currentTarget)[this.type](this.getDelegateOptions()).data("bs."+this.type);return clearTimeout(c.timeout),c.hoverState="out",c.options.delay&&c.options.delay.hide?(c.timeout=setTimeout(function(){"out"==c.hoverState&&c.hide()},c.options.delay.hide),void 0):c.hide()},b.prototype.show=function(){var b=a.Event("show.bs."+this.type);if(this.hasContent()&&this.enabled){if(this.$element.trigger(b),b.isDefaultPrevented())return;var c=this.tip();this.setContent(),this.options.animation&&c.addClass("fade");var d="function"==typeof this.options.placement?this.options.placement.call(this,c[0],this.$element[0]):this.options.placement,e=/\s?auto?\s?/i,f=e.test(d);f&&(d=d.replace(e,"")||"top"),c.detach().css({top:0,left:0,display:"block"}).addClass(d),this.options.container?c.appendTo(this.options.container):c.insertAfter(this.$element);var g=this.getPosition(),h=c[0].offsetWidth,i=c[0].offsetHeight;if(f){var j=this.$element.parent(),k=d,l=document.documentElement.scrollTop||document.body.scrollTop,m="body"==this.options.container?window.innerWidth:j.outerWidth(),n="body"==this.options.container?window.innerHeight:j.outerHeight(),o="body"==this.options.container?0:j.offset().left;d="bottom"==d&&g.top+g.height+i-l>n?"top":"top"==d&&g.top-l-i<0?"bottom":"right"==d&&g.right+h>m?"left":"left"==d&&g.left-h<o?"right":d,c.removeClass(k).addClass(d)}var p=this.getCalculatedOffset(d,g,h,i);this.applyPlacement(p,d),this.$element.trigger("shown.bs."+this.type)}},b.prototype.applyPlacement=function(a,b){var c,d=this.tip(),e=d[0].offsetWidth,f=d[0].offsetHeight,g=parseInt(d.css("margin-top"),10),h=parseInt(d.css("margin-left"),10);isNaN(g)&&(g=0),isNaN(h)&&(h=0),a.top=a.top+g,a.left=a.left+h,d.offset(a).addClass("in");var i=d[0].offsetWidth,j=d[0].offsetHeight;if("top"==b&&j!=f&&(c=!0,a.top=a.top+f-j),/bottom|top/.test(b)){var k=0;a.left<0&&(k=-2*a.left,a.left=0,d.offset(a),i=d[0].offsetWidth,j=d[0].offsetHeight),this.replaceArrow(k-e+i,i,"left")}else this.replaceArrow(j-f,j,"top");c&&d.offset(a)},b.prototype.replaceArrow=function(a,b,c){this.arrow().css(c,a?50*(1-a/b)+"%":"")},b.prototype.setContent=function(){var a=this.tip(),b=this.getTitle();a.find(".tooltip-inner")[this.options.html?"html":"text"](b),a.removeClass("fade in top bottom left right")},b.prototype.hide=function(){function b(){"in"!=c.hoverState&&d.detach()}var c=this,d=this.tip(),e=a.Event("hide.bs."+this.type);return this.$element.trigger(e),e.isDefaultPrevented()?void 0:(d.removeClass("in"),a.support.transition&&this.$tip.hasClass("fade")?d.one(a.support.transition.end,b).emulateTransitionEnd(150):b(),this.$element.trigger("hidden.bs."+this.type),this)},b.prototype.fixTitle=function(){var a=this.$element;(a.attr("title")||"string"!=typeof a.attr("data-original-title"))&&a.attr("data-original-title",a.attr("title")||"").attr("title","")},b.prototype.hasContent=function(){return this.getTitle()},b.prototype.getPosition=function(){var b=this.$element[0];return a.extend({},"function"==typeof b.getBoundingClientRect?b.getBoundingClientRect():{width:b.offsetWidth,height:b.offsetHeight},this.$element.offset())},b.prototype.getCalculatedOffset=function(a,b,c,d){return"bottom"==a?{top:b.top+b.height,left:b.left+b.width/2-c/2}:"top"==a?{top:b.top-d,left:b.left+b.width/2-c/2}:"left"==a?{top:b.top+b.height/2-d/2,left:b.left-c}:{top:b.top+b.height/2-d/2,left:b.left+b.width}},b.prototype.getTitle=function(){var a,b=this.$element,c=this.options;return a=b.attr("data-original-title")||("function"==typeof c.title?c.title.call(b[0]):c.title)},b.prototype.tip=function(){return this.$tip=this.$tip||a(this.options.template)},b.prototype.arrow=function(){return this.$arrow=this.$arrow||this.tip().find(".tooltip-arrow")},b.prototype.validate=function(){this.$element[0].parentNode||(this.hide(),this.$element=null,this.options=null)},b.prototype.enable=function(){this.enabled=!0},b.prototype.disable=function(){this.enabled=!1},b.prototype.toggleEnabled=function(){this.enabled=!this.enabled},b.prototype.toggle=function(b){var c=b?a(b.currentTarget)[this.type](this.getDelegateOptions()).data("bs."+this.type):this;c.tip().hasClass("in")?c.leave(c):c.enter(c)},b.prototype.destroy=function(){this.hide().$element.off("."+this.type).removeData("bs."+this.type)};var c=a.fn.tooltip;a.fn.tooltip=function(c){return this.each(function(){var d=a(this),e=d.data("bs.tooltip"),f="object"==typeof c&&c;e||d.data("bs.tooltip",e=new b(this,f)),"string"==typeof c&&e[c]()})},a.fn.tooltip.Constructor=b,a.fn.tooltip.noConflict=function(){return a.fn.tooltip=c,this}}(window.jQuery),+function(a){"use strict";var b=function(a,b){this.init("popover",a,b)};if(!a.fn.tooltip)throw new Error("Popover requires tooltip.js");b.DEFAULTS=a.extend({},a.fn.tooltip.Constructor.DEFAULTS,{placement:"right",trigger:"click",content:"",template:'<div class="popover"><div class="arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div></div>'}),b.prototype=a.extend({},a.fn.tooltip.Constructor.prototype),b.prototype.constructor=b,b.prototype.getDefaults=function(){return b.DEFAULTS},b.prototype.setContent=function(){var a=this.tip(),b=this.getTitle(),c=this.getContent();a.find(".popover-title")[this.options.html?"html":"text"](b),a.find(".popover-content")[this.options.html?"html":"text"](c),a.removeClass("fade top bottom left right in"),a.find(".popover-title").html()||a.find(".popover-title").hide()},b.prototype.hasContent=function(){return this.getTitle()||this.getContent()},b.prototype.getContent=function(){var a=this.$element,b=this.options;return a.attr("data-content")||("function"==typeof b.content?b.content.call(a[0]):b.content)},b.prototype.arrow=function(){return this.$arrow=this.$arrow||this.tip().find(".arrow")},b.prototype.tip=function(){return this.$tip||(this.$tip=a(this.options.template)),this.$tip};var c=a.fn.popover;a.fn.popover=function(c){return this.each(function(){var d=a(this),e=d.data("bs.popover"),f="object"==typeof c&&c;e||d.data("bs.popover",e=new b(this,f)),"string"==typeof c&&e[c]()})},a.fn.popover.Constructor=b,a.fn.popover.noConflict=function(){return a.fn.popover=c,this}}(window.jQuery),+function(a){"use strict";function b(c,d){var e,f=a.proxy(this.process,this);this.$element=a(c).is("body")?a(window):a(c),this.$body=a("body"),this.$scrollElement=this.$element.on("scroll.bs.scroll-spy.data-api",f),this.options=a.extend({},b.DEFAULTS,d),this.selector=(this.options.target||(e=a(c).attr("href"))&&e.replace(/.*(?=#[^\s]+$)/,"")||"")+" .nav li > a",this.offsets=a([]),this.targets=a([]),this.activeTarget=null,this.refresh(),this.process()}b.DEFAULTS={offset:10},b.prototype.refresh=function(){var b=this.$element[0]==window?"offset":"position";this.offsets=a([]),this.targets=a([]);var c=this;this.$body.find(this.selector).map(function(){var d=a(this),e=d.data("target")||d.attr("href"),f=/^#\w/.test(e)&&a(e);return f&&f.length&&[[f[b]().top+(!a.isWindow(c.$scrollElement.get(0))&&c.$scrollElement.scrollTop()),e]]||null}).sort(function(a,b){return a[0]-b[0]}).each(function(){c.offsets.push(this[0]),c.targets.push(this[1])})},b.prototype.process=function(){var a,b=this.$scrollElement.scrollTop()+this.options.offset,c=this.$scrollElement[0].scrollHeight||this.$body[0].scrollHeight,d=c-this.$scrollElement.height(),e=this.offsets,f=this.targets,g=this.activeTarget;if(b>=d)return g!=(a=f.last()[0])&&this.activate(a);for(a=e.length;a--;)g!=f[a]&&b>=e[a]&&(!e[a+1]||b<=e[a+1])&&this.activate(f[a])},b.prototype.activate=function(b){this.activeTarget=b,a(this.selector).parents(".active").removeClass("active");var c=this.selector+'[data-target="'+b+'"],'+this.selector+'[href="'+b+'"]',d=a(c).parents("li").addClass("active");d.parent(".dropdown-menu").length&&(d=d.closest("li.dropdown").addClass("active")),d.trigger("activate")};var c=a.fn.scrollspy;a.fn.scrollspy=function(c){return this.each(function(){var d=a(this),e=d.data("bs.scrollspy"),f="object"==typeof c&&c;e||d.data("bs.scrollspy",e=new b(this,f)),"string"==typeof c&&e[c]()})},a.fn.scrollspy.Constructor=b,a.fn.scrollspy.noConflict=function(){return a.fn.scrollspy=c,this},a(window).on("load",function(){a('[data-spy="scroll"]').each(function(){var b=a(this);b.scrollspy(b.data())})})}(window.jQuery),+function(a){"use strict";var b=function(b){this.element=a(b)};b.prototype.show=function(){var b=this.element,c=b.closest("ul:not(.dropdown-menu)"),d=b.attr("data-target");if(d||(d=b.attr("href"),d=d&&d.replace(/.*(?=#[^\s]*$)/,"")),!b.parent("li").hasClass("active")){var e=c.find(".active:last a")[0],f=a.Event("show.bs.tab",{relatedTarget:e});if(b.trigger(f),!f.isDefaultPrevented()){var g=a(d);this.activate(b.parent("li"),c),this.activate(g,g.parent(),function(){b.trigger({type:"shown.bs.tab",relatedTarget:e})})}}},b.prototype.activate=function(b,c,d){function e(){f.removeClass("active").find("> .dropdown-menu > .active").removeClass("active"),b.addClass("active"),g?(b[0].offsetWidth,b.addClass("in")):b.removeClass("fade"),b.parent(".dropdown-menu")&&b.closest("li.dropdown").addClass("active"),d&&d()}var f=c.find("> .active"),g=d&&a.support.transition&&f.hasClass("fade");g?f.one(a.support.transition.end,e).emulateTransitionEnd(150):e(),f.removeClass("in")};var c=a.fn.tab;a.fn.tab=function(c){return this.each(function(){var d=a(this),e=d.data("bs.tab");e||d.data("bs.tab",e=new b(this)),"string"==typeof c&&e[c]()})},a.fn.tab.Constructor=b,a.fn.tab.noConflict=function(){return a.fn.tab=c,this},a(document).on("click.bs.tab.data-api",'[data-toggle="tab"], [data-toggle="pill"]',function(b){b.preventDefault(),a(this).tab("show")})}(window.jQuery),+function(a){"use strict";var b=function(c,d){this.options=a.extend({},b.DEFAULTS,d),this.$window=a(window).on("scroll.bs.affix.data-api",a.proxy(this.checkPosition,this)).on("click.bs.affix.data-api",a.proxy(this.checkPositionWithEventLoop,this)),this.$element=a(c),this.affixed=this.unpin=null,this.checkPosition()};b.RESET="affix affix-top affix-bottom",b.DEFAULTS={offset:0},b.prototype.checkPositionWithEventLoop=function(){setTimeout(a.proxy(this.checkPosition,this),1)},b.prototype.checkPosition=function(){if(this.$element.is(":visible")){var c=a(document).height(),d=this.$window.scrollTop(),e=this.$element.offset(),f=this.options.offset,g=f.top,h=f.bottom;"object"!=typeof f&&(h=g=f),"function"==typeof g&&(g=f.top()),"function"==typeof h&&(h=f.bottom());var i=null!=this.unpin&&d+this.unpin<=e.top?!1:null!=h&&e.top+this.$element.height()>=c-h?"bottom":null!=g&&g>=d?"top":!1;this.affixed!==i&&(this.unpin&&this.$element.css("top",""),this.affixed=i,this.unpin="bottom"==i?e.top-d:null,this.$element.removeClass(b.RESET).addClass("affix"+(i?"-"+i:"")),"bottom"==i&&this.$element.offset({top:document.body.offsetHeight-h-this.$element.height()}))}};var c=a.fn.affix;a.fn.affix=function(c){return this.each(function(){var d=a(this),e=d.data("bs.affix"),f="object"==typeof c&&c;e||d.data("bs.affix",e=new b(this,f)),"string"==typeof c&&e[c]()})},a.fn.affix.Constructor=b,a.fn.affix.noConflict=function(){return a.fn.affix=c,this},a(window).on("load",function(){a('[data-spy="affix"]').each(function(){var b=a(this),c=b.data();c.offset=c.offset||{},c.offsetBottom&&(c.offset.bottom=c.offsetBottom),c.offsetTop&&(c.offset.top=c.offsetTop),b.affix(c)})})}(window.jQuery);function addEntry(text) {
	$.ajax({
			"url": filename,
			"type": "post",
			"data": {"action": "add", "text": text},
			"dataType": "json",
			"success": function() {
			}
	});
	var html = "";
	html += "<div class='bs-callout bs-callout-info'>";
	html += text;
	html += "</div>";
	$('#list').prepend(html);
}


var editAfterSend = 0;
function sendnewpost(obj) {
	var data = {
		"action": "newpost",
		"type": "ajax",
		"newposttext": $(obj).find('.newposttext').val(),
		"newformrecipienttype": $(obj).find('.newformrecipienttype').val(),
		"newformcommenttype": $(obj).find('.newformcommenttype').val(),
		"newformeditable": $(obj).find('.newformeditable').val(), 
		"group":  $(obj).find('.group').val()
	};
	
	
	if(data.newformrecipienttype=="ausgewaehlte") {
		var recipients = [];
		$('.recipientcheckbox:checked').each(function() {
			recipients.push($(this).val());	
		});
		data.recipients = recipients; 
	}
//	console.log(data);
	
	$(obj).find('.newposttext').val("");
	$.ajax({
		"url": filename,
		"type": "post",
		"data": data,
		"dataType": "json",
		"success": function(data) {
			//console.log(html);
			//$('#postlist').prepend(html);
			//pollCounter=pollInterval;
			if(editAfterSend==1) {
				window.location = filename+'?full='+data.newid+"&edit=1";
			} else {
				pollCounter=pollInterval;
			}
			editAfterSend = 0;
		}
	});
}

function sendaddpost(obj) {
	var data = {
		"action": "appendpost",
		"type": "ajax",
		"addposttext": $(obj).find('.addposttext').val(),
		"id":  $(obj).find('.id').val()
	};
	$(obj).find('.addposttext').val("");
	$.ajax({
		"url": filename,
		"type": "post",
		"data": data,
		"dataType": "json",
		"success": function(data) {
			updatePostDiv(data.id)
		}
	});
	
}

function sendaddline(postid, line) {
    var data = {
        "action": "appendpost",
        "type": "ajax",
        "addposttext": line,
        "id":  postid
    };
    $.ajax({
        "url": filename,
        "type": "post",
        "data": data,
        "dataType": "json",
        "success": function(data) {
            updatePostDiv(data.id)
        }
    });

}

function updatePostDiv(id) {
	$.ajax({
			"url": filename,
			"type": "get",
			"data": {"action": "getpost", "id": id},
			"dataType": "html",
			"success": function(html) {
				$('#postlist').html(html);
			}
	});
}

var minPollInterval = 5;
var maxPollInterval = 30;
var pollInterval = minPollInterval;
var pollCounter = 0;

function pollnews() {
	
	if(typeof(whichView)=="undefined") return;
	if(whichView.indexOf("detail")!=-1) return;
	
	pollCounter++;
	if(pollCounter>=pollInterval) {
		pollCounter=-60;
		$.ajax({
				"url": filename,
				"type": "get",
				"data": {"action": "pollnews", "whichview": whichView, "lastchange": lastChange},
				"dataType": "json",
				"success": function(data) {
					pollCounter=0;
					if(data.result==1) {
						lastChange = data.lastChange;
						
						for(var i=0;i<data.ids.length;i++) {
							$('#'+htmlid(data.ids[i])).remove();
							$('#comments'+htmlid(data.ids[i])).remove();
						}
						
						$('#postlist').prepend(data.html);
					}
				}
		});
	}
}
$(function() { 
	document.addEventListener("visibilitychange", function() {
			if(document.hidden || document.visibilityState=="hidden") pollInterval = maxPollInterval;
			else pollInterval = minPollInterval;
	}, false);		
	setInterval(function() {pollnews();}, 1000);

	initTree();
	
});

function initTree() {
	if(typeof(optionTREE)=="undefined") return;
	
    $('.tree li:has(ul)').addClass('parent_li').find(' > span').attr('title', 'Collapse this branch');
    $('.tree li.parent_li > span').on('click', function (e) {
        var children = $(this).parent('li.parent_li').find(' > ul > li');
        if (children.is(":visible")) {
            children.slideUp();
            $(this).attr('title', 'Expand this branch').find(' > i').addClass('glyphicon-plus-sign').removeClass('glyphicon-minus-sign');
        } else {
            children.slideDown();
            $(this).attr('title', 'Collapse this branch').find(' > i').addClass('glyphicon-minus-sign').removeClass('glyphicon-plus-sign');
        }
        e.stopPropagation();
    });
    
    $('.tree li.parent_li > span > a').on('click', function (e) {
    		    e.stopPropagation();
    });
	
    
    /*
    if(optionTREE.length==0) $('.treeeditlink').hide();
    else $('.treeeditlink').show();
    */
}
function edittree() {
	$('#grouptree').prepend("<div class='obersteebene'><input type='radio' name='belowhere' value=0  checked> auf oberster Ebene</div>");
	$('.tree').find("span").each(function() {
			if(parseInt($(this).attr("rel"))>=10) {
				$(this).append("&nbsp;<input type=radio value='"+$(this).attr("rel")+"' name=belowhere>");
			}
	});
	$('.edittreelink').hide();
	$('.savetreelink').show();
	$('#edittreeform').slideDown(function() {
			$('#edittreenewfolder').focus();
	});
}
function savetree() {
	$('.tree').find("span").find("input[type=radio]").remove();
	$('.obersteebene').remove();
	$('.edittreelink').show();
	$('.savetreelink').hide();
	$('#edittreeform').slideUp();
}

function addTreeEntry(obj) {
	
	var below = $('.tree').find("input[type=radio]:checked").val();
	if(typeof(below)=="undefined") below = 0;
	$.ajax({
			"url": filename,
			"type": "post",
			"data": {"action": "addtreeentry", 
				 "title": $('#edittreenewfolder').val(),
				 "below": below,
				 "group": whichView
			},
			"dataType": "json",
			"success": function(data) {
				if(data.result==1) {
					$('#grouptree').html(data.html);
					optionTREE = data.options;
					savetree();
					initTree();
				}
			}
	});
	$('#edittreenewfolder').val("");
}

function clickOnTree(id) {
	$('#postlist').html("");
	recentPostID = "*";
	getOlderPosts({"treeid": id});
	
}

var lastLObj;
function savepostlink(obj, id) {
	var lid = $(obj).find('select').val();
	
	lastLObj = obj;
	
	$.ajax({
			"url": filename,
			"type": "post",
			"data": {"action": "setposttreelink", 
				 "group": whichView, 
				 "id": id, 
				 "tree": lid
			},
			"dataType": "json",
			"success": function(data){
				//console.log(data);
				html = '<a href="#" onclick="editpostlink(this);return false;"><i class="glyphicon glyphicon-list"></i></a>&nbsp;'+data.title;
				$(lastLObj).closest("div").html(html);

			}
	});
	
}

function editpostlink(obj, id) {
	var html = "";
	
	html += "<form onsubmit=\"savepostlink(this, '"+id+"');return false;\">";
	html += "<table><tr><td>";
	html += "<select class='form-control' style='float:left;'>";
	html += "<option value=''></option>";
	for(var i=0;i<optionTREE.length;i++) {
		html += "<option value='"+optionTREE[i].id+"'>"+optionTREE[i].title+"</option>";
	}
	html += "</select>";
	html += "</td><td>";
	html += "<button value='' style='float:left;' class='form-control'>Ok</button>";
	html += "</td></tr></table>";
	html += "</form>";
	
	
	$(obj).closest(".post").attr("id");
	$(obj).closest("div").html(html);
}

function getOlderPosts(extra) {
	var postdata = {
			"action": "getolderposts", 
			"group": whichView, 
			"recentid": recentPostID 
			};
	if(typeof(extra)!="undefined") $.extend( postdata, extra );
	$.ajax({
			"url": filename,
			"type": "post",
			"data": postdata,
			"dataType": "json",
			"success": function(data){
				//console.log(data);
				if(data.result!=1) return;
				
				recentPostID = data.lastid; 
				
				for(var i=0;i<data.posts.length;i++) {
					
					$('#postlist').append("<div id='olderpost"+htmlid(data.posts[i])+"'><img src='"+filename+"?RES=resources/images/loader.gif'></div>");
					
					$.ajax({
							"url": filename,
							"type": "get",
							"data": {"action": "getpost", "id": data.posts[i], "view": "short"},
							"dataType": "html",
							"success": $.proxy(function(html) {
										$('#'+this.id).replaceWith(html);
									}, {"id": "olderpost"+htmlid(data.posts[i])})
					});
					
					
				}
				

			}
	});
}

function openImagePreview(divid, postid) {
	var divwidth = Math.floor($('#'+divid).width()*0.9);
	$('#'+divid).html("<img src='"+filename+"?action=imgpreview&id="+postid+"&width="+divwidth+"' class='previewimg'  >");
}

function openImageShow(divid, postid, imgnr) {
    var divwidth = Math.floor($('#'+divid).width()*0.9);
    var maxheight = Math.floor($(window).height()*0.8);
    $('#'+divid).html("<img src='"+filename+"?action=imgshow&id="+postid+"&imgnr="+imgnr+"&width="+divwidth+"' style='max-height:"+maxheight+"px;' class='previewimg'  >");
}


function htmlid(id) {
	id = id.replace("/","_").replace(".","_");
	return id;
}


var progressNr=0;
function uploadFile(obj, id, postfieldid, postid, callback) {
    if(typeof(postid)=="undefined") postid = -1;
    progressNr++;
    progress = '<progress id="progressBar' + progressNr + '" value="0" max="100" style="width:300px;"></progress>';
    $(obj).closest("div").append(progress);

    var formdata = new FormData();
    for(var i=0;i<obj.files.length;i++) {
        formdata.append("fileappend[]", obj.files[i] );
    }

    var ajax = new XMLHttpRequest();
    progressData = {
        "id": 'progressBar' + progressNr,
        "textid": id,
        "postfieldid": postfieldid,
        "callback": callback,
        /*"name": file.name,*/
        "progress": function(event) {
            $('#' + this.textid).val("");
            var percent = (event.loaded / event.total) * 100;
            $('#' + this.id).val(Math.round(percent));
        },
        "done": function(event) {

            var T = event.target.responseText;

            $('#'+postfieldid).val( $('#'+postfieldid).val() + "\n" + T); // this.name
            $('#' + this.id).remove();
            if(typeof(this.callback)!="undefined") this.callback();
        }

    };
    ajax.upload.addEventListener("progress", $.proxy(progressData.progress, progressData), false);

    ajax.addEventListener("load", $.proxy(progressData.done, progressData), false);
    ajax.addEventListener("error", function(event) { }, false);
    ajax.addEventListener("abort", function(event) { }, false);
    ajax.open("POST", filename+'?action=addfile&view=json&id='+postid);
    ajax.send(formdata);

}ÿ6  ¡5                    LP                      ‰˜                  ( G L Y P H I C O N S   H a l f l i n g s    R e g u l a r   x V e r s i o n   1 . 0 0 1 ; P S   0 0 1 . 0 0 1 ; h o t c o n v   1 . 0 . 7 0 ; m a k e o t f . l i b 2 . 5 . 5 8 3 2 9   8 G L Y P H I C O N S   H a l f l i n g s   R e g u l a r     BSGP                 w 5s 5y -ֶֽיֽׂױ(tÛKûֱDגֲ'PמMמ
‡ Bֳj¶פübeJ2ccֵַLF1+†WEeuJפױe•~m•©%‰חַW¾Ù*ײIzIֲ€בsL„	x

זצ4Ûxׁ—PSח-Uu׳Tם²˜E ױF×“?ֽ¬	װ¯ׂ4ÛÊ¨gו•q Ùeׂ$צ{ם-+1u«„±©{ב¿Sףנ"!EÜB&/˜†הL¼³צE״ְKמׂ7fם¦ׂ®‎|צ'נ=‏ÿ›j¼װp¡ÿE÷A_B@Ü‏*·?~ךכד9ֲ&„Iט„Ùvטנ«@e‎—rג’>Mo<†LXיבּ%ײ׳üa>ֻ’ ‘¦jp?Pת;ÿ_‎א®«iֶ¶ַ <מ³}LbXֹUe1ְL	€!ׁ;„צ®¦¨	Dו^%‡ 	PP‚”A‚@‘üµ5ד©ףˆKa€l׃´ִ§a!£ו‡וע|Zh¬®FFIע0”EüF"ִ‘ „”¦'Mױ¸¹9°@…)1A£₪׃ִָֿ¶רׁ€‡‘ד<ֲֽי־,ג@¬כA†	pA‡¨Cֿ²ּ\‘.Û mָ₪Ix”±›sS
"ֲrצr}‹nNֱדנױ~lQo¦`>t™Üףי°…¶ֳ׳¶¶װימׂ5׃צ‎€X`
ֲ­g9K×Þ עgצ]בs ½zT0%j‡kTֻדײgּ™0"µYVg‘€@¬°)Ü>aFc !t z־ ’א¿=Vֱ²k¨גװ@op^vÜ?שJְ׀=fא¬ם0[7üß@¿›}tל³\ח¹ֹס}ֵּv\|f{צy†?=י1"f˜‏ש´x‡´ֿHxx‏iפ8‹k™PYְ8צ~ֳזה§ח7@ז(:3vלפ°¬¨XַX+(כ₪µ±ü¢Fjײח19„’'(m¨}÷Ê÷e•Ý׃C¦§ֵ’£=‡&Êתףe
y²…E׀ֹB ƒ(™2²אפE?xv5₪ki{P"ׂIG kXLP״ֵ€Eנ>Gװ[ֱ|q!cז+o0÷
a‡‹~§\®´;ס!ך!ך+¦¦uX1ˆH£"×₪—¶¶₪ה¦ U94%‚ׁ`³§÷)ן§y1‚*ÝÙױײ/Û:>\gַ@¥<H‘´HÜ¶W¨ײ~}©ִף:Þדhf«¢צlZ׳4‘Y¯°ב־–ֵה?zUאכש«ױAUq:א>xמסµZהד.]¼“A₪ֱ·ׂױK¶ע/
«{[8ֶ]©b‎.q~˜צ2—DÜoR”zK²ÊoGl׀ל₪{E‎¸¡„9ן›	׳E¨ZRֲ}3QO•ד׀•6ְֱC–).¹R„ִת%Oy¼ף¡¿r
ָ#Q½V?l ֻGÛIנעVÊ	T.Rׂנטt†Þ‹ׁןִmÝ¯צmb־v ‰vtOn#<uן9ַÞp3:¯³ß)״% ®Tz´ץמכc‡‚BWc©‡£†³
cFvEZ׃OהIkֿÊMûY’uj%[Ü(0i˜‘>)»aQy}ֶvb2ו‎'*ƒb†G½ט£´~	—5עs«'ü¡ף·יֳ¥¶vx8)#7Jּזÿח†@Iֶ ˜`S(E ֻ–Bט«!$<ק sm±¡¶{ׁ÷ ֱמז׳û!`xכ¢"ZaB3”‎¼x´´Wֹ[׳ƒ˜²רjֶƒhנ%ptKְ[³dµ“±C$UC@\)ײA~qֻ…ֳֹׁ־/ז@znז2e?9¬Ú‚Nּ״¸Q²91¯Baןƒל•a״0ז#¹Ú˜.3ֳאֳ—‚״‏ÿ•½¿²+z%µװ†¾ֳֶEÚ”s¬AםU’|hא±@£פ״¥s5S8*v0Dט׳גף®UרYmדןI½€ןsגדו:׀Dt¸ִXrµ/ל©0[LNû	ûנI»רגִ$a‹¸pp™’†MNÊ¢7›ֹtDÞI'alˆY"4₪X	מr¼[†{_£MW½ X1Ú<×”c®פ)L|»–S ˆQ™’ֽ–˜V ‰ָF£זFֻ‏tu¼ֿ4dr-Mv«M÷u₪Sת¢8•91c˜˜ױ
ֳ’`ּAIÿ#\¯*p•XÚ׳¼×)–¢§4%>׃ִחbm]„5ˆÞ5N=G­€’ #ûr¨—¿96#R–©ÿ
Yi¹£+f 'F58@יx‏4ם ‎K€‡×.§µ- 2¯¼:‎Q	4<y
Êƒ9i´װָV3:Ýף<7>Ü¥5ֲ2ÿXC°7`M-jָ
 >;~׀L‎ֲ5n”H­HF4צKß¹÷Y‰p¢ÿrׂwsד4ˆÝF¦Ûה•Fר:מ0׃¢L[f|„װ†©ג־Jְ¢¦«ײ :h©$¨I¢ry(*‚u&~Kÿe“`ַ±§73)„Wה$ %״¶צ ¢ְpג@Cץ\₪r|Sָ"Ù÷E"¥«ּ0iּrOÿDh¢md#$4Eש¥@J©¦"ׁקָזTֱ(פ“L™9Ù´ׂNְCxM¼‚₪א—÷}D¸-£BIב„rx¬ֽ)\)׳gU•J…iûֵuבOֿ1T>¸|ז>J‡ױDט–bs -׃@´E@Þ]”8נו¹ִײֲאBנ´¼…‹Ú–‎ָ®X…_3בU
קdD„ַ“_± ˜¬‘Ù”t£Ù ׳Kü4n,ְD²־־ש"6°¾@…כ‡‚– ­Fֻm­`Y’Ê6ן'3ֲ¨––2lבpM±`<­hWג"­’P־@ּע¡¢	(J(ü^3´°Oכy¿Lq½ֳט‘](D”₪R¾rc¯{e ‚¢־\3P!}"טשַ¼Vt„)w¸™®4?­u~•Dֱנ‰U2]›0׀OVFס6-§ֶM§)¨’װeYנl.HײִRֿˆ_ A†שJ½Ê»YÝו’“oIFהׂZt:¿Ù¬‹÷ֲ·ת¹‡‏˜h"Bג¥םְֻך&ם¶ü"ÿnyף˜‹‰A׀-ל מx#ױ~6–ױ·ÞֽhX©¹ˆ¥{9Aי"Ü5io¬rjֽ{¬H8ח)¥ÜIZ^׀¼ֲִ³@.@‚־“F.ְב—RעKAD dûפ Q„,ֲƒ†€a[q‚g@ק	¸D_¥…#E u€)טR1•וB]F
±uִVˆ²טׁG{§יר”Ü°‚x׀]־µ(@m¢l¦­f‚£Üמ¬P­:ˆHרµפ s¬,i”û¥ם“Q>2¿"d[(ֹֳ סü/uׂ¼bS²Lֻ³‎.‰"•:®A”U@p¥Ûײ½RüE•”ִ{ֱBטH¢™ֶ¥ע!Iµױa¨-w"ְ)w% גלב™™¯×ױrw«¿ֽ³¼rְ6§ס!©1£Y§א ֻ˜LT’pxבבֳrפ8]ֶ>ׂˆֶS<hפyjפCב€£•]„ֶK ֵ€ִ¬&\vכC`b£#a¦‘ֿ[]סIמ÷״F\kאµ¥X]©¯מ `אe״MXםdTֶ2ֻױf®ֻױ}…›™“^nֻNƒֳ$ˆ·@׀Ù×ָzֵװR:׃q"SÛׁY‡0J°/…üױ©Y3½ִ™ְֵ®Ù#¡qו׃Y׀sק¶ר¶|HJÜ¢„ַ”0ֽÙ‚ְvֳ8׃4„ֳ‘¦םQvלy©—Pׁ¡Cµ;פ־{x8`3GּÚfNהתןƒ[V¹ß¾P¢	׀UÊ«]]ÚJO’‰6°Úr÷_t~oֽÞ>"#^”m²ך₪¾לןB\ַץi‰&U־=Q˜1ִ,? t&r,@lי5AÛׁJ´­6\²–¢w״זx7I™\`nֹsKpÊ´‎Ý€׳¼מ2L× ב'„M3ˆ‡–ֻKi[v&V‚tF’THט
rxרְTUYׂEe¯}×±״$Wׁ=מX/¥}%₪KֳDן©*ך¥:דD =K×_¬ְה†ד`הDֲ#ף[צ¹JÛQ‚l£$	˜׃zװ s/¾ְ«Úq’ְJ÷„הI+¹Xסֿ,˜$	°g־f`זֳ%²Qü6ײ"@tµN"9()y.B™ Lֿל*ˆ6Üo^4ם4Y‚‰e-ׂ«‹—z_÷|¾•~מnM7כ¾Ú¼Tg
§`מ־‹dֻkbrד״ָקצֳ¬¹f”vִר/$}%)ּ-ִ—¼R
א&H|&‚X’$	;c+s-A«׀ˆ>>;_»?-§1hG*ָז@כ Urָ³אֻ73ד6h)»¥ףשי— O¶‹Û .ֹM2׀>j j=Bֲ¦ץ1²Mט XB`Lגׁ°>xיל¬d™´מbN	·XֻK³‹n,א½«W{ם“™’ך;ױß´×p[j)^עמ`z‰ֲWIײ®•ֻרp¸hה4?ֽ¿¹²eS¬Úƒְ¬4+v{ְ1¢ˆX’PBXÿ ךq±־EsG–…‡p?g±ב¶i{׃˜‎M€tרֹü’ך›–™–₪­cןץ8;$<¬\3QÊÚְZָldv©fÜ†"†»c«ֵ¾›¹‏·\"ZÚ…T)Uנ•ZlפקUA'hÿE£,װֿ.A=ָֽtת¹‚FÙDRlßsפ†)ָ™ש`/";ֹ.Ü׃2ז]b—P¡cBבroם*[©ם	 ‚ֹBƒ K|e¸€40 לM
ד2Bf1ü®ט1yֲ‏׳ֿי½ו©R<"°F×I›'„V×´חP±‹נNÞֹ£ פ„
PF8`ֲj"jmנֱß˜ gxDüA	‚ÚHÞu*W‡X_Ûזםˆ9qÚ[µ·LiÞuN§9v@pkvmR׃ֳsSÝְ(‎¢@״µÛ™סˆר“bqLֲ$· ֵֻ¨!™בg¯XY₪d‘· ?ָ©ָֱD|'¼²‰Cֿ#ru¢*(n„oץ5₪´³˜ְ "xÙc!B¼טiÙב2¼טל«³’¸טת¸±D\C£§Eֱ=»—„z®״J׀וkאו)בÙgY״]”e×ƒ0›#˜ׁ`†P"ך{ ½ר—”ֶך׀Q]bÚPb»jB!»Zlv;‘"ח¦¥N…‚[",ְ0yװ÷ד#u-׳גלGמדָק!אˆÿׂ.דװ¸n¦bצלזFX	ל&‰tµ!2נ]6H·GH<½ hCƒ¼K4N­ֵ"‹ׂ7Þ	ß|2ש~™’·פל'h¹•µA$ÞVTvˆנ´Y˜¾ “
=<¥Iֵ##Ü!L$דעהְ[%ְת'Rÿ¸שF¶!.ֵע¼| —m9('…%װ
t P+‚l₪w8D­1›‏צgצ WÞ׃Y1°¶lbד	קֱÊ(¡ה˜5Vzk®†ע1m’ֱ¼¥ײ» -—²½…I’–ˆ¢Ú6“	A9xע–?;gHֹ¬¼{) ³G†)°ײ «ךNp”ָ€³
O9ֲ•S¿…%<×E)¹<OדLÙBLxֻAֱ ¡01}ֲ¬h -=״neC™#0c†÷k°ֳק- –צb§!:f©T3,(m׀¡¸qחי=״ƒ‏c‚‏ ץ״‚ױ1’*TilOֹ)`¡h{ûM]Nנ%ˆ
הß~*&ךW״$˜=78‡Mֶ´Úd
4tZ ›…3ױ”א3jִ‘¯ױan*ש¿Pֻ6½וֲ¢2J$ׂ¢(LP@cתֿR #@$ˆױׂh7Nzpq@ב–;acחבH[טwhֽv"c’ßװק¾3s³+ײ’ֻ` ט6S±v˜ ~	Ttנr‘ֵHS1eROÜג(X µ’`]¢eC› 4x˜h ¦@םֵװE¹r@דx—JXמײY%½47„d@FX‚Q³¡ץ(¨Ù“{A<µ!^!F‹y´ ¢¬%=ֻ!…ûbׁטk8₪†„ÝPזyWrrqKyֻ»E\›¼״«¶£ױ¸מT»B¢טh״	CwIצ! |Dj7\ײ״i±UÞ*ֱJ§ƒwR
ֶGÞ:’R™M•׃bלHקֱn|wiְ«Ú ®³*ץ€g*~\ˆ™ƒG‘”חSvY’TÚj¨ה„Þ@VהqT‹fׁ*ןֻER TûûP¾וnt8׀,׀־¬ֱ×eׁA—ִ2bP„§“¥°ֲ-’ֱלU00הf4ON8	ָ0'Cכ#’ְף'"Wv;ßוÊ לL
ְ]C0Yיׁ>¶Ly|wנx.ה׃¸3#"Vן°ûÊµKIֶZ £aךױh³­[P‎ָ‚=ּ˜Aaאתױ% [(P±'R°Dׂ8cßPMn+jc¥©ּ›­3shל[¦t·ֵ8(C׀3<#`eˆh²up#‰¶5ךAaֿ…ֽ)& DO‘״ÛגײֳÙ/Þ˜Z•)דג=) ½סD'n_₪ß¼…BGÛףY³\lZˆרק÷@„0!צ׃•!CכƒDFiי`("״½5Ýב‏™ֹ9ZGG4?‘€Wד‚”M†¥'iָ?9½תאl*ד‘FV–H}₪‚Pש׳JM¯־—[|״bdML(Lװ•ה(ֹג׀˜_©\.טי2	‹ףB¥ר“±ÜI?w רTwׁ‚¶Ú2g¼`¢ א: נֻ ּkQ‚Ù}$¡U±‎מבz¨×ֳ>’/H
P@f}ֽק‘2(AˆלÊ
ßÙ%†Êך‘“,q3)ׁ־ֹ°₪¬jh׳נס־hw´Vµvק—VQ€Þש\!` tX5גש“^¢B/¦')“0ףt3ƒ~ƒ½¬…זש$ 	˜°ˆ•4טֿwV	2©(ד5ƒ[•ֵֶַRֲu
ֵ¿˜¢[s~-b| P״Q{ץ?ֻ÷JTא1€[Ê5kֲֵ> ZNִנw&|תr´>„dˆ;ֵ¨8†’^V ½†אח0ֳ÷Z"d&[,גnY"}|)ּֳn6˜ׂגד(4S³„ֻÊטu|[ֲ‚דL6ס¯פtÝcDD‹‡K ר¶i¢
Kכt™2*
¶2₪וָeZָ ֽ—7ש4	Q׀M²ױiq±¡HTy‰Y־ָ(9T·ףüךנa-kpx±d[Nׂy״~O”qָ^lqQ0Z•ףך¬d¢גa-ֵ1ֿ sע ַ#€ּû†Ý#ּ¨gה|]°ֳ6“®$dַװ?~tM€ק ט₪÷¥™¬ךÜµן6cֶ³5e†±˜ן¹´XUב8GEיֽך§vז?chיV©K'ֲֳC‹$ü־§S׀Aמÿ`¥nֽÿJwZ˜©'¨־'ztOד״G„lִˆ¨ֿ[DִֹQ›+ִq
בBֹ
9ֳ”Ú<?l\ו¹ $יssb'¶¶¬<©ֳW¦1üֲה0DLÿ6נ+Akׁט=אפײm‎_Gװסv˜5p«,Ùs‹nכ”צ.´רך QaRצPD+<3קlMˆ5_Bװ†AÛװNµײp«וH
ִ £ֻ<„. ßא€€ד‹ת|[P	Zתmו£X%-6¥tך־g @@ûדz&·˜•mLƒֻ3¼¹<Q@ֽ·n´H­ֹ¾q¯÷´ֳ4„״זלQֵ;&:‡’
±=ךbU`…[וMJo°»,©״ @Iײ|'eְ¿M³^Hֱcfאי™$הPÞd lַ§C&‏Ú¦wµ2w¦puעlװ^n¼ֶ£°ֵ p„ׂT¢mוֻ°ÿˆֹּ\ß€Ýhb=rA.usˆ4®שנOָi€;ךP­ûm9J״•0²םךIֽ}¸ u)ַ„בrT:Bר!˜	3׃(
¨*…lשU/8
=‰>'t]ץüד׳]״i¨׃ulz‡$´²|טHֹn3÷0v?ֵyU‡t{ִי)ֲ¶U½0ׂ‎~O[}Þ "§קה) ¥ Ù“¾'jß‎kב£MCX”<-‚ֱ³×ט₪/p¾ץת’Gט?lֱ
ˆ9 4µFָׁ~÷´‎u×uֲh+ן+¥cH¨µאטכ…™Qƒ&מtײ¢”xטqבt+ _Q$T^״‡¡Ù`u“O¿׀d³Qp]הÞֹÜה Ù!Hטˆ°ßֵ¶”SJTˆֽJֽy€ֵg׳xh!¨8Gb*Ê<¦עT™h}„ױ׳"ײN4bx#$¡	|װֽ‘±a×¬Pזl׃I‏÷:W`ˆLוU„MK¡¾½‘±טbTs(S”׳·ְˆÚקpר~¬U1‏p׃.7¡Ê
Nת׀זÙ¨א€D¿Þ›Êh­AXa‡„ם
@+־†´-'„ם€ײ6O]¿TU<1 ךהq­׃ֿwך$µ­גט˜ֵ=ׂ¼L¾€ הץ3טfi¹+˜פ§Mחpֳ6•ֵ“6דָמ‏{L†₪‹ק¿’E’;0‘ְ~pNüֲ lZ§$¿²Nר™…׀¶•¹„bְק…iß)יyײכ^M™\§ַD¸’₪ü
dײ¼·ֹPeֲ#6 ׁAX4׳ּ†[ְ;´Ek™7דl-ַÙbG€°V‚ײ0טת׃דץrÊ)*r÷“׃ן:†Gw6˜U˜I>jÞנ¨…#q’–RהפH{יUך³!BR"¯£û 3x"3‘ךÞ—m0>=‡&ֲֿח—B@˜J´¾
¢‘k³§ּ×ts0p~	TÝah¹Bßאא!•s[1y־÷ 	ײVJ3!z„‡´	TP¸LI:üֲ‹— וqע‚s/׃C½¦OÊü-נ©“ֶ!¼ץ…÷€„Lƒ21¹שy‡״״‚¥F×ה₪£['׀ײ'ם€V‏ֱDz•ûd¼ֿ׃†™³Êp²״ֶ.÷»Ek¡¢Ê₪I$©„™ז| e‡פֳ„\א«T©•'ּבH>;Y
ˆ¹:+UCג7כמÊׂcÜu”@Ê¸׳.Êשx‘~÷Jן+Iְ"FH־ױ8ףֳNחבבkך„׀ "‡up›¢Moz¿Gה°¦r{;‹©Dzז³רUw‹RpRr`ׁץ{l¼¾¹s>ֻ²$@)‘….x-ֿtֽטכa"אNא³^²א~&@ב§§ןƒ<¨K—ֻכ”ֲÚֽ,ךן“Rנּ˜זPÚ–¡–®י¯װ¬k
ֲִָ9רדPS4₪ֽ³b₪nְךZq«˜dֶ#-±C	 פxz»W6>Þ/¶ז‏-ָQףטNפסׁk׀sˆÿwl^‹ׂ׀נ¯ֹFVיז£Y‚™‎¯¯“ף'C?ש ‡xBoÜ‰נQ
M־?¯~ÿûUjֹa³S׃¯¾‎׀s5ְ	ֹ{Y¹2ײaFÜˆ:J±s»ׂ´@Wמi[ת'Oüם׳vWÞBז6ן}{I5־סּ‘ük§r±(˜4¬u-ֳ0₪7HסX…	%$¬Ù‡Ad§?[×•h\<ƒnƒ—_Ê©XױVׁ3/‹2sE°ײu9¦’ב‏ג‹>ֶ^Oפז5ך¨QkFסֲ&%Y0c[£¥²kO‚÷™ß&©ִcאfֹhi‹1…‹×¹3_וֱ“3û}wajiA$t‹…ֵ¶„Û€Úzפ'$‹0×,Nÿ״ßRגHJ(‹%ƒBםÜ¼ֲv5׀|ּ±Xx,‹דLו@H‹^׳יֵG#ן¨ßל®Ùq;”_§uJ×•gד*ÿWֽבÚ
‚§f#®;0//Z׃צ‎¬U¶~ֿn6Ü+Ùָ$zv}.י0.=פ
­&ֶpג׃ûזױב¿¼|“°f&sÜ‡bÛׁ9ש.¹Aאֹ=¼`ü„ִD‚ ײב¿ü₪`* ˜yR”¨תpץפ₪.2ץט¬…?ְט0t÷צ¼hZ3ֻ·e5ֲ,’i•זÞ¾0´Qe®”÷;׳סü,b·ƒטףך‡ ²ׁ‚׀‚ˆ)Q@¿.ײ‡ß@ֱ(Úz.°1E½>•ֽפµ×ֶ J|W[+U"ִ"Aף*†¥wמ]ySלAH>ױ>ָ®ֻ©ׁ F,ק#ג/ל¦ֱw†
–ûk~Y¡ְHע»l¾eu¬H x}+p8'[»…¼U‘ÙÜ¥ִָV’¯#‰ָ6€“0(־”r£תÊXS¢6YB‘ֲֻm%MץÙ׃ƒYן67†נxױ-nh'*ח,RÝ*³¸ס¹	;´|°6%UCr—c±ֹ2ּג*y”E¡ַ+ץ‰xk„ *Z…ֶ^0N—AÞoפע’*ZY¦5uטג¾C?0 02־לם¢׀P™ן₪Gƒ‡X2z6P3ֲּװ´€1ÝSûÜ`ד¸|&|׃€iE׃נֻןסA‰׀)¥×תkֹײ0NÞ ˆףץj׃DיÜ‰ ®@מֱןל‘זך}†~w§ֹUל,rq t°ֲ₪S>^0$rI)V¿rqֳ÷י‎y÷ֿÿ7©¶םEÝתnU׃wiנ‚!u%b”…ו÷”™^›®IגOֻ  ר™iּÿַֿ‘×›cI›דׁ¾d…ˆCק¸ü÷N];"Tכף‹B’)ּ8$₪Vֱ ¦Fk?Ý±¡2HO¼>Úג׀¿	¦׀‏‏c&ָ—א%פZ˜:קhÚ
ה׃>®¿p%־שH™$zd›׳fn(="‰®ףטזû¶׀^ַ׳׃‰ֶYׂ+D<û*;§{£+Zylˆ—‘B0Ûא‘ָ!¶ֳ	Þ₪ףOd׃"R•¸ֵקg‹aך™H›—™עIׂ¶igָש…u­*Ùx׀xכ˜ֽ\’¬€ֽj&>™ֽc‏5ײד€¸¹ֽ”?Mlׂn£,ד³…zב8חÙjpZ{c,˜
S9<ּ,	׀¬S־‡ÿ¦Þדׂ”ßgP37B„xW_״\¹M˜לױb›״ֻ
©ד:-¦ֽ!—¼׃ ]1הD¦Eװ¦‎ז<ז`ט®»Fv׃חקץL
y|עeק2&}»ָ׳Ú›‚פףזRÚֿ’ֳ{I´~±|ך¬1Nײֶ״5ß®Nhגײ(f1Hl₪ץV8@Eֽ)+€eeR
‚עסh˜Z	י[£±VוPgRÞÜfU³ןi°‏E(4"Þc“l¸"EךBÜתהגְ`»2¼û|ֳ†@‹#¿*qSנ&›
”@¹7±[|ת„<₪	“¨j-H›»׀t*/ֱ®Iֱ‹פÚ6q½‡8Z`†Úb[“ˆÊY'P™HרW‡'4Dכ”´5¬F„cÝPהֶj¢׳₪ֶ׃d‏‹"ֵ©RXrgI…7ֿ€ֵr—טû7ֿ”0„Fָ₪"ַp2h‰» ADy«p°Pד”
–\'­Tֱ„&”†״sv9.+.…¯LÝpi’ß₪vHbָ©‏©EרTבהfe£י–¹—DYÿûװ ׃+ע…־°ֶHÛ¹
¦צ¥#K4xB±2‡(Dµ?¨} iS|’fVWxתמֱ©„’‎±üeü‹ץ›תןJך־•p+g°״²• ֱx¹CבAאe†×xT¼ÙHq¥gg׀»†^# ֽiַovֽWֵנ­Gמֿkקט™
¸2ֱ׀F¾Ù¨)Ü1£?”²t‹‡¬© 
ֳֹ׀pַבt©?׀״¯ב"ׁIV~W¶g¥7b€t*
ּ¯²לCˆR€r	›DÙ~ֽ*¨נGP´%Fo1™(H‏”%Y‹”ZֱˆױHּ^X V¡r™(זdˆפGףֿß; 4$ֱt*†ִe¡bמ<L›N.£²¨ב±ט¸C E׀×Ê‚ָְ~]˜3ףµJƒ¨o\TXoפזכב@< PÞƒHDֱ¸?ְ?ץ±?¹ †Pn/–A½‚Ý)("!¢JP •y÷\ Ú6U|I)ף´x”ב{pNKת†ך
OÜא¨ױtט,•ֶa#‎7›6B‡_dµ‚מֹ'Rr;¢]Ê‰לHײQ4?M±Úצ±±X?~ֱהß"ְRױ¨₪<םה+ֻ™¾7
uxSֲַָץוQy{C0„*j»ֹ"ק‏Ý¼µ‚ֵ¶Qi1ּF0·בױeR«Xִ ֹ‚ֻQB Jָ<ת¶e_ֱM	Eֱס?O·aÊנ[>£¥­m\E‘l¬&‚ם{J£בÞAכֹ‹ךj°.µ#§<2j}ׁׂ•J:B₪ZK?7¥6ב‹DDֱ&ְB_a/›#§ßQµ‡אM`החµN×`נPצoְ¦€$_€q—V¿9¡K1¢ =ֱ9)~7>¸¢GoסTXÜÙת
Tc¨*†נִט!°’a”¾?”X¯XüC O÷4¸"„!ׂjו¾i–צÊױ¡E;„3כ ßXM	a±Hװ]	ִ½ֽµE®ז¹”LפD¿&Ú"^·}‡«)כa*ד+›בתָVt@ֿjg9aי±ֶÊױC“}ת»%X*+Aµ&­א>זUÛֵRƒ)¢cֽ|M+™-–¡#@<b׳ß&-כ–¨CAmנט4µ$ye2J[6־¨‘Q“k„®¯פ*פּּְסüU†₪ב¡¾}ִ'Uz´ִײ±xו*	ְW'–˜סp(˜Jׁzר£dX$ ָ¢Q»&0nּÝEYs›(n­ש„‰*¾%©נְ”‰Lכ= >ס\‰­‎˜+׃”W l1÷×װ‘5S™¸־¡³:ָ“/dd2@#FUת2ֹ÷"ָ״47]ף83‹‹‡dl׀ְ״X„f¦24½Úgü(%…±ֳ[
}5א"ַ¸5·ָ'uׁKה־	R*#ףxןÛ™X#G!™t‚bH°¬¦w¼ֻ—vהׁ°¬¹˜t״׃B™Tױסt®ם«>§ƒּF³]p^2ÿאü װep@Xּw9aֱn›Xץµ)@Fְ₪@׃ …h>J&mA״¹!הu=üלז}TQWֹל3AׂM@¿Cב״ְײ\ׁה"µְ—F¦ג±ה˜d+ר ˜›OקQׂ‡B®®—לq&בךױ¼­W„ֻש«:5sz%)	y¿R#‹”‘˜|Rֶ¾r%³fr”_ל׳yEמ-־רp&ׁ_•7D„±־4£×w@ֹ"Ý°0ױUלYbÛ!ׁ(‎epQם~"רV
vQ»ß·‏q\yב6ָGYR₪_
ֶUׁ›˜†עָÝ¸±ׁoP״cZ&‰
¢4ֶRf{`סגPhך;N¾ םH(Tֶפ´ֶ7^ˆ¥־•Z«גnß[ף־µƒֱ²x.“¹_3ü¸O‚¦¯Dצ₪ׁE­F"DÞ׳(„33÷ײtuאב’¯ut `Rhהֱa°Z¼(DJ·$lְ0±¿A	קH2"ׂ^H"םy1Êֱתe¼@-וooהֶj־›˜‹]ֻ~!†H§”־l]S3ֶײׂ3VמYX£‰„Cg~ָ AGI6ךü¾«׳¾3BüZc÷דתִ‰«‰׃י#Lv-C„P,Vע@÷‡+װ˜ֳ¡QL»s
ׁ$¼E¦ֿ–a,Aג#R†€ֶ”fWִd‘Rך60נ€C­ג—	Ùi	¯dfט¥ֲױT:H‡«ֿ'ד	g"P´4ױ I9ÙקU^—@±%2E.®„¶h·¦Eן:§hא |‡
מָױ°צü!O:ֵ:fֵֶֻµ¿‚”hs¸Ú>2הE<qI־’סF¦w
^hÙcHd¨‹±HEל[Mשt)ֳZ9ÛDT=½L¡cN­XJנDֻ´קÙהי4÷#~%4z₪Kן ‎‏Þ׳o)Ý‰ ש€Û©0]ִP-…×+שp„Ü2g<üß§†},`₪nIֲ‡ÿָ{±_ֹ'F“µםc5 8ּ©ן€®®ֲח@Ü²|ײש\N4T–£‘X¹‹©ףה%†cעkx6D,fXUh\
w§₪9ִR5`h׀ס2‚Ü¶ֹÝb¢׃v¹”Oƒ†ָ3¶Q=ֳװ›ֳ
Q>°µ‰}—×Êיvn׃rQהVטאlÛײ“F¡בeצJ™ֱ‘KÚנ(dÜl־ׂ‏†u0גהן@Uײ^¿װפ_~¿כB¡rֳִ.‚™*h׃E{ תXI—ה¬דÜלx9>ו‰gסcÊװװ׳ÿ}ִגד~ˆ>nN’ּ¥Zהקdןdְa¬נ+3Dˆ6wp; µa®O‚¸ E¦~*²Mֽ×‚\·©‰ßגֱ›÷GÙG]>lyNףְa’D³JI%B,Gׁ,ֻûחKֿÿֽ¸3ִt¡ף2—µ4½l׳G@וÜכ{¢£•†®ׂk™¡ש מ d?ׂב$.×CDÿ¥0‘₪¹ ²”7d:[­ûװמƒ>}¼ֿ)_ט’¾3ֻ’]ü	­[נˆ׃7 טל#;ֻ>רPOֿחf„cמזל&¨aulץh=¯wװ!רX™ֵ“@w׀/גzך]„Yawֱ[×t׀fר——$×µEטֵ\׀.”ֳ-װvWֽ!‹ֿ ‚‘^ÜJûרƒ26פUü®ֳinÿ״ֽhׁע£‹%ְֵט+W;’טיn"1]„Cת?`ײÙ”,i(ק†…־ּ-±׳א¬+G†ײ…1°Lװײע;אû3Qֽ¿s'9־@¿ֲׁ­‚N>ÿ]a׃¡ˆ&˜6	תG2ü›`QIB×N₪ ףR	wץcׂ4R²ץ>¨ÊMûv÷.¥u…'3ÝֶÊ‰:ˆo"ב’Zg?×%ליhÙ‘³ּֿ~ָHg¥´‎Ù¬$%ְ״&]קׂ'gFOaחֽƒq4nx<»>"Jֳr ֿ¢u¯G^װֿ}ײ„‘÷„ףAמךֵבת5c‡ B²ָZ‰X‚b«µ'ÚnQi0“3†anL'ףuֵe[י<Hm%./¸1K0כmט¹[Ú©û3ט–;=\@י:xh €fֲM>1•Q/u°0G‎ף¡ab-_jו!7ַ·²2Gngב­‏u"ֹ#ף׀GRcם©‘ƒ+µ+¼I„2§;£W®ש˜]n)«Dֲַ&שב‘3=צ!״¶›7ˆyָ™>שß”ֹ:ÞW/-±Q6qW,WEnק	VL¼_N‰|YkÙƒZּz ƒTשn,P<8xטß9­¦AûII±ְ‹-b(<¬iעַ.I¡; £ַזm Iˆ^¦µ׀/0 ֹס€„}Û‚{P€E7.ֹפ3{ֿH«נ[^`<Jְ/S·ִֶÿRֻ!²F®R…°;‎8ֽש0wO‹ךU.ß gז#T§ּ<ֹ­µ¢±זo ‹½@_¾–Eבÿ´§N”…G¹L—₪}.8‏vCu‡MטL²©a׀3׃¢’חI8ׁÝHZסuÝיֵחׂױז@1F»״פH&L“$¬	&׃אÜ" 0׀ח3R)û(־¢³]¼p	*×t:ׁ<¡´¢­1&ְw'»‚Sאף‎Lױ}07M¦¶pךÛ rָִ;^ֳ°טg_ף- hֵû×ּ6—­+‏[™¯.†‡ÝÛS!ׂ»K\u°ÜEQ`
bW8%»lע	B÷x¦ב8$;K{kײBבc"״ׁ`gr:;)eש«M©ז°Þאב¢%W÷‡ם׀Sv$j“ZÊ?9 d•´€¨$-m2דp›+”צִ›˜>Qu5qÞ|ה2 ץֲ?–\ש**!ˆ‡ ÛS¢%YiQC¿ßU|&gyבV₪Ê\a[uˆ¢׀©5‏­WÝ–יע‚ר₪ƒ«¥@ְֲ’ׂן=¥ט¾=ƒt9(Zו*<¦U¨§D¢±j)’\j±­CÜץB/וts|U…vה—zUL׳EB®"חמz`{8)¡¡Zֿq(׀E¸¨"uE »eE’ּּ[÷fְ p)€K׃L¡R‘װֱM²A’+lGÛ”$Ú%ױ¬¦¥5ײל,¶‹₪Zµ@*Yß§לÞֽ¦û‹P›fִֽ1F©t2€Immצ¥טך~£׀Mר¦w`xJƒ# ´Ýש ErH2tםFי.» ƒVPEl‚*O׀ֹ×;ֻai<ְ½(*–"RFצ'לִ~¡UO*W¦epÚ†6K|ְ׀ v2ֱ{³־.6סjNq¯ן	Ü:Iֿדֿyg½רLל.₪ך„ü¿בך:—\ק¼ ,÷®Ù`0x–÷ב`)S ¡³Ê@zwQEֹnק^/!׀?קםֶ…ׂQ±er
¨ן¢Ùû ‎¼*;ם‘ה©&¥"TÊHִֿ¥ױhP פCQִ4ON=ֵֹחְzcר"~tÞE&X!u%r””¨EֲװAי02t„s“F)6E8ֱס¡ףc=†יֶj0 א£j6°א£׳#´X:…HhD‘)ֵ¦!א’גע V0ÞC@7 פ¡נ€ Sƒc4 ֲ#[@ˆac|ּ'€°@m‚„07Pp6€ ף€`A׃X-גÿ°
0m¸c(Eוˆµ: ÿ*¨½wגנ8Xu€כTוAÊ₪ © <~ E18v€nז?$™X(\n^‚	˜­3 Zp¦€<\4D˜u	†e¬ ג¥רq(ה8mn)6ƒp›Ps0  ²א… « ױ€+ R (˜ I( ‚P ה`'€; s ׃0”T9|T9|L9dL9TD9>@8ּ Y$ֶ†‘ה@``¢0@0@V,@"(@$C|H† ‘‘ƒס‹סc
ס@b€) c‰ ְְ
´
¬¡פ1+אb°ִAƒ'A‚ַ`ן€¶ְם@:€yךÙ˜־`€nהµˆ~ X€`גcˆn X I`pf׀Ý°v@MY ֿdp	Uְװרש’6/¢2GA
ךD/qb‡¡¶†j„m‹Lֶ=׃L]L¥;/R@סq@ßֶט¦5C1¦ּd†bÙ‡‡f˜gv״¼ה§ ,90Üְ¯ך/¨~}חO¾oûז·ז§#;ע#¯Úsגƒgר ¶ײa]¨‚{0Ù€גִײ 4/xKְP^rנ€t´ױ c«Hך@³Rb%װ”א=#ÙH@yB©נֿ€ׂl`.™Tָ
%ְE. ´¸עא}—״¨…@ױ*Di²ֹ˜Ê$ֽ1w©ֳyvגÝ³V†8¨ּ07ˆ™F¦Hִ.µ¼ ֶ8±ֱJם@  `  ¡'ג‚`\LT‏נÙApBs)r…!ױ
ג(
ׂi‚`<?xml version="1.0" standalone="no"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd" >
<svg xmlns="http://www.w3.org/2000/svg">
<metadata></metadata>
<defs>
<font id="glyphicons_halflingsregular" horiz-adv-x="1200" >
<font-face units-per-em="1200" ascent="960" descent="-240" />
<missing-glyph horiz-adv-x="500" />
<glyph />
<glyph />
<glyph unicode=" " />
<glyph unicode="*" d="M1100 500h-259l183 -183l-141 -141l-183 183v-259h-200v259l-183 -183l-141 141l183 183h-259v200h259l-183 183l141 141l183 -183v259h200v-259l183 183l141 -141l-183 -183h259v-200z" />
<glyph unicode="+" d="M1100 400h-400v-400h-300v400h-400v300h400v400h300v-400h400v-300z" />
<glyph unicode="&#xa0;" />
<glyph unicode="&#x2000;" horiz-adv-x="652" />
<glyph unicode="&#x2001;" horiz-adv-x="1304" />
<glyph unicode="&#x2002;" horiz-adv-x="652" />
<glyph unicode="&#x2003;" horiz-adv-x="1304" />
<glyph unicode="&#x2004;" horiz-adv-x="434" />
<glyph unicode="&#x2005;" horiz-adv-x="326" />
<glyph unicode="&#x2006;" horiz-adv-x="217" />
<glyph unicode="&#x2007;" horiz-adv-x="217" />
<glyph unicode="&#x2008;" horiz-adv-x="163" />
<glyph unicode="&#x2009;" horiz-adv-x="260" />
<glyph unicode="&#x200a;" horiz-adv-x="72" />
<glyph unicode="&#x202f;" horiz-adv-x="260" />
<glyph unicode="&#x205f;" horiz-adv-x="326" />
<glyph unicode="&#x20ac;" d="M800 500h-300q9 -74 33 -132t52.5 -91t62 -54.5t59 -29t46.5 -7.5q29 0 66 13t75 37t63.5 67.5t25.5 96.5h174q-31 -172 -128 -278q-107 -117 -274 -117q-205 0 -324 158q-36 46 -69 131.5t-45 205.5h-217l100 100h113q0 47 5 100h-218l100 100h135q37 167 112 257 q117 141 297 141q242 0 354 -189q60 -103 66 -209h-181q0 55 -25.5 99t-63.5 68t-75 36.5t-67 12.5q-24 0 -52.5 -10t-62.5 -32t-65.5 -67t-50.5 -107h379l-100 -100h-300q-6 -46 -6 -100h406z" />
<glyph unicode="&#x2212;" d="M1100 700h-900v-300h900v300z" />
<glyph unicode="&#x2601;" d="M178 300h750q120 0 205 86t85 208q0 120 -85 206.5t-205 86.5q-46 0 -90 -14q-44 97 -134.5 156.5t-200.5 59.5q-152 0 -260 -107.5t-108 -260.5q0 -25 2 -37q-66 -14 -108.5 -67.5t-42.5 -122.5q0 -80 56.5 -137t135.5 -57z" />
<glyph unicode="&#x2709;" d="M1200 1100h-1200l600 -603zM300 600l-300 -300v600zM1200 900v-600l-300 300zM800 500l400 -400h-1200l400 400l200 -200z" />
<glyph unicode="&#x270f;" d="M1101 889l99 92q13 13 13 32.5t-13 33.5l-153 153q-15 13 -33 13t-33 -13l-94 -97zM401 189l614 614l-214 214l-614 -614zM-13 -13l333 112l-223 223z" />
<glyph unicode="&#xe000;" horiz-adv-x="500" d="M0 0z" />
<glyph unicode="&#xe001;" d="M700 100h300v-100h-800v100h300v550l-500 550h1200l-500 -550v-550z" />
<glyph unicode="&#xe002;" d="M1000 934v-521q-64 16 -138 -7q-79 -26 -122.5 -83t-25.5 -111q17 -55 85.5 -75.5t147.5 4.5q70 23 111.5 63.5t41.5 95.5v881q0 10 -7 15.5t-17 2.5l-752 -193q-10 -3 -17 -12.5t-7 -19.5v-689q-64 17 -138 -7q-79 -25 -122.5 -82t-25.5 -112t86 -75.5t147 5.5 q65 21 109 69t44 90v606z" />
<glyph unicode="&#xe003;" d="M913 432l300 -300q7 -8 7 -18t-7 -18l-109 -109q-8 -7 -18 -7t-18 7l-300 300q-119 -78 -261 -78q-200 0 -342 142t-142 342t142 342t342 142t342 -142t142 -342q0 -142 -78 -261zM176 693q0 -136 97 -233t234 -97t233.5 96.5t96.5 233.5t-96.5 233.5t-233.5 96.5 t-234 -97t-97 -233z" />
<glyph unicode="&#xe005;" d="M649 949q48 69 109.5 105t121.5 38t118.5 -20.5t102.5 -64t71 -100.5t27 -123q0 -57 -33.5 -117.5t-94 -124.5t-126.5 -127.5t-150 -152.5t-146 -174q-62 85 -145.5 174t-149.5 152.5t-126.5 127.5t-94 124.5t-33.5 117.5q0 64 28 123t73 100.5t104.5 64t119 20.5 t120 -38.5t104.5 -104.5z" />
<glyph unicode="&#xe006;" d="M791 522l145 -449l-384 275l-382 -275l146 447l-388 280h479l146 400h2l146 -400h472zM168 71l2 1z" />
<glyph unicode="&#xe007;" d="M791 522l145 -449l-384 275l-382 -275l146 447l-388 280h479l146 400h2l146 -400h472zM747 331l-74 229l193 140h-235l-77 211l-78 -211h-239l196 -142l-73 -226l192 140zM168 71l2 1z" />
<glyph unicode="&#xe008;" d="M1200 143v-143h-1200v143l400 257v100q-37 0 -68.5 74.5t-31.5 125.5v200q0 124 88 212t212 88t212 -88t88 -212v-200q0 -51 -31.5 -125.5t-68.5 -74.5v-100z" />
<glyph unicode="&#xe009;" d="M1200 1100v-1100h-1200v1100h1200zM200 1000h-100v-100h100v100zM900 1000h-600v-400h600v400zM1100 1000h-100v-100h100v100zM200 800h-100v-100h100v100zM1100 800h-100v-100h100v100zM200 600h-100v-100h100v100zM1100 600h-100v-100h100v100zM900 500h-600v-400h600 v400zM200 400h-100v-100h100v100zM1100 400h-100v-100h100v100zM200 200h-100v-100h100v100zM1100 200h-100v-100h100v100z" />
<glyph unicode="&#xe010;" d="M500 1050v-400q0 -21 -14.5 -35.5t-35.5 -14.5h-400q-21 0 -35.5 14.5t-14.5 35.5v400q0 21 14.5 35.5t35.5 14.5h400q21 0 35.5 -14.5t14.5 -35.5zM1100 1050v-400q0 -21 -14.5 -35.5t-35.5 -14.5h-400q-21 0 -35.5 14.5t-14.5 35.5v400q0 21 14.5 35.5t35.5 14.5h400 q21 0 35.5 -14.5t14.5 -35.5zM500 450v-400q0 -21 -14.5 -35.5t-35.5 -14.5h-400q-21 0 -35.5 14.5t-14.5 35.5v400q0 21 14.5 35.5t35.5 14.5h400q21 0 35.5 -14.5t14.5 -35.5zM1100 450v-400q0 -21 -14.5 -35.5t-35.5 -14.5h-400q-21 0 -35.5 14.5t-14.5 35.5v400 q0 21 14.5 35.5t35.5 14.5h400q21 0 35.5 -14.5t14.5 -35.5z" />
<glyph unicode="&#xe011;" d="M300 1050v-200q0 -21 -14.5 -35.5t-35.5 -14.5h-200q-21 0 -35.5 14.5t-14.5 35.5v200q0 21 14.5 35.5t35.5 14.5h200q21 0 35.5 -14.5t14.5 -35.5zM700 1050v-200q0 -21 -14.5 -35.5t-35.5 -14.5h-200q-21 0 -35.5 14.5t-14.5 35.5v200q0 21 14.5 35.5t35.5 14.5h200 q21 0 35.5 -14.5t14.5 -35.5zM1100 1050v-200q0 -21 -14.5 -35.5t-35.5 -14.5h-200q-21 0 -35.5 14.5t-14.5 35.5v200q0 21 14.5 35.5t35.5 14.5h200q21 0 35.5 -14.5t14.5 -35.5zM300 650v-200q0 -21 -14.5 -35.5t-35.5 -14.5h-200q-21 0 -35.5 14.5t-14.5 35.5v200 q0 21 14.5 35.5t35.5 14.5h200q21 0 35.5 -14.5t14.5 -35.5zM700 650v-200q0 -21 -14.5 -35.5t-35.5 -14.5h-200q-21 0 -35.5 14.5t-14.5 35.5v200q0 21 14.5 35.5t35.5 14.5h200q21 0 35.5 -14.5t14.5 -35.5zM1100 650v-200q0 -21 -14.5 -35.5t-35.5 -14.5h-200 q-21 0 -35.5 14.5t-14.5 35.5v200q0 21 14.5 35.5t35.5 14.5h200q21 0 35.5 -14.5t14.5 -35.5zM300 250v-200q0 -21 -14.5 -35.5t-35.5 -14.5h-200q-21 0 -35.5 14.5t-14.5 35.5v200q0 21 14.5 35.5t35.5 14.5h200q21 0 35.5 -14.5t14.5 -35.5zM700 250v-200 q0 -21 -14.5 -35.5t-35.5 -14.5h-200q-21 0 -35.5 14.5t-14.5 35.5v200q0 21 14.5 35.5t35.5 14.5h200q21 0 35.5 -14.5t14.5 -35.5zM1100 250v-200q0 -21 -14.5 -35.5t-35.5 -14.5h-200q-21 0 -35.5 14.5t-14.5 35.5v200q0 21 14.5 35.5t35.5 14.5h200q21 0 35.5 -14.5 t14.5 -35.5z" />
<glyph unicode="&#xe012;" d="M300 1050v-200q0 -21 -14.5 -35.5t-35.5 -14.5h-200q-21 0 -35.5 14.5t-14.5 35.5v200q0 21 14.5 35.5t35.5 14.5h200q21 0 35.5 -14.5t14.5 -35.5zM1200 1050v-200q0 -21 -14.5 -35.5t-35.5 -14.5h-700q-21 0 -35.5 14.5t-14.5 35.5v200q0 21 14.5 35.5t35.5 14.5h700 q21 0 35.5 -14.5t14.5 -35.5zM300 450v200q0 21 -14.5 35.5t-35.5 14.5h-200q-21 0 -35.5 -14.5t-14.5 -35.5v-200q0 -21 14.5 -35.5t35.5 -14.5h200q21 0 35.5 14.5t14.5 35.5zM1200 650v-200q0 -21 -14.5 -35.5t-35.5 -14.5h-700q-21 0 -35.5 14.5t-14.5 35.5v200 q0 21 14.5 35.5t35.5 14.5h700q21 0 35.5 -14.5t14.5 -35.5zM300 250v-200q0 -21 -14.5 -35.5t-35.5 -14.5h-200q-21 0 -35.5 14.5t-14.5 35.5v200q0 21 14.5 35.5t35.5 14.5h200q21 0 35.5 -14.5t14.5 -35.5zM1200 250v-200q0 -21 -14.5 -35.5t-35.5 -14.5h-700 q-21 0 -35.5 14.5t-14.5 35.5v200q0 21 14.5 35.5t35.5 14.5h700q21 0 35.5 -14.5t14.5 -35.5z" />
<glyph unicode="&#xe013;" d="M448 34l818 820l-212 212l-607 -607l-206 207l-212 -212z" />
<glyph unicode="&#xe014;" d="M882 106l-282 282l-282 -282l-212 212l282 282l-282 282l212 212l282 -282l282 282l212 -212l-282 -282l282 -282z" />
<glyph unicode="&#xe015;" d="M913 432l300 -300q7 -8 7 -18t-7 -18l-109 -109q-8 -7 -18 -7t-18 7l-300 300q-119 -78 -261 -78q-200 0 -342 142t-142 342t142 342t342 142t342 -142t142 -342q0 -142 -78 -261zM507 363q137 0 233.5 96.5t96.5 233.5t-96.5 233.5t-233.5 96.5t-234 -97t-97 -233 t97 -233t234 -97zM600 800h100v-200h-100v-100h-200v100h-100v200h100v100h200v-100z" />
<glyph unicode="&#xe016;" d="M913 432l300 -299q7 -7 7 -18t-7 -18l-109 -109q-8 -8 -18 -8t-18 8l-300 299q-120 -77 -261 -77q-200 0 -342 142t-142 342t142 342t342 142t342 -142t142 -342q0 -141 -78 -262zM176 694q0 -136 97 -233t234 -97t233.5 97t96.5 233t-96.5 233t-233.5 97t-234 -97 t-97 -233zM300 801v-200h400v200h-400z" />
<glyph unicode="&#xe017;" d="M700 750v400q0 21 -14.5 35.5t-35.5 14.5h-100q-21 0 -35.5 -14.5t-14.5 -35.5v-400q0 -21 14.5 -35.5t35.5 -14.5h100q21 0 35.5 14.5t14.5 35.5zM800 975v166q167 -62 272 -210t105 -331q0 -118 -45.5 -224.5t-123 -184t-184 -123t-224.5 -45.5t-224.5 45.5t-184 123 t-123 184t-45.5 224.5q0 183 105 331t272 210v-166q-103 -55 -165 -155t-62 -220q0 -177 125 -302t302 -125t302 125t125 302q0 120 -62 220t-165 155z" />
<glyph unicode="&#xe018;" d="M1200 1h-200v1200h200v-1200zM900 1h-200v800h200v-800zM600 1h-200v500h200v-500zM300 301h-200v-300h200v300z" />
<glyph unicode="&#xe019;" d="M488 183l38 -151q40 -5 74 -5q27 0 74 5l38 151l6 2q46 13 93 39l5 3l134 -81q56 44 104 105l-80 134l3 5q24 44 39 93l1 6l152 38q5 40 5 74q0 28 -5 73l-152 38l-1 6q-16 51 -39 93l-3 5l80 134q-44 58 -104 105l-134 -81l-5 3q-45 25 -93 39l-6 1l-38 152q-40 5 -74 5 q-27 0 -74 -5l-38 -152l-5 -1q-50 -14 -94 -39l-5 -3l-133 81q-59 -47 -105 -105l80 -134l-3 -5q-25 -47 -38 -93l-2 -6l-151 -38q-6 -48 -6 -73q0 -33 6 -74l151 -38l2 -6q14 -49 38 -93l3 -5l-80 -134q45 -59 105 -105l133 81l5 -3q45 -26 94 -39zM600 815q89 0 152 -63 t63 -151q0 -89 -63 -152t-152 -63t-152 63t-63 152q0 88 63 151t152 63z" />
<glyph unicode="&#xe020;" d="M900 1100h275q10 0 17.5 -7.5t7.5 -17.5v-50q0 -11 -7 -18t-18 -7h-1050q-11 0 -18 7t-7 18v50q0 10 7.5 17.5t17.5 7.5h275v100q0 41 29.5 70.5t70.5 29.5h300q41 0 70.5 -29.5t29.5 -70.5v-100zM800 1100v100h-300v-100h300zM200 900h900v-800q0 -41 -29.5 -71 t-70.5 -30h-700q-41 0 -70.5 30t-29.5 71v800zM300 100h100v700h-100v-700zM500 100h100v700h-100v-700zM700 100h100v700h-100v-700zM900 100h100v700h-100v-700z" />
<glyph unicode="&#xe021;" d="M1301 601h-200v-600h-300v400h-300v-400h-300v600h-200l656 644z" />
<glyph unicode="&#xe022;" d="M600 700h400v-675q0 -11 -7 -18t-18 -7h-850q-11 0 -18 7t-7 18v1150q0 11 7 18t18 7h475v-500zM1000 800h-300v300z" />
<glyph unicode="&#xe023;" d="M600 1196q162 0 299 -80t217 -217t80 -299t-80 -299t-217 -217t-299 -80t-299 80t-217 217t-80 299t80 299t217 217t299 80zM600 1014q-171 0 -292.5 -121.5t-121.5 -292.5t121.5 -292.5t292.5 -121.5t292.5 121.5t121.5 292.5t-121.5 292.5t-292.5 121.5zM600 600h200 v-100h-300v400h100v-300z" />
<glyph unicode="&#xe024;" d="M721 400h-242l-40 -400h-539l431 1200h209l-21 -300h162l-20 300h208l431 -1200h-538zM712 500l-27 300h-170l-27 -300h224z" />
<glyph unicode="&#xe025;" d="M1100 400v-400h-1100v400h490l-290 300h200v500h300v-500h200l-290 -300h490zM988 300h-175v-100h175v100z" />
<glyph unicode="&#xe026;" d="M600 1199q122 0 233 -47.5t191 -127.5t127.5 -191t47.5 -233t-47.5 -233t-127.5 -191t-191 -127.5t-233 -47.5t-233 47.5t-191 127.5t-127.5 191t-47.5 233t47.5 233t127.5 191t191 127.5t233 47.5zM600 1012q-170 0 -291 -121t-121 -291t121 -291t291 -121t291 121 t121 291t-121 291t-291 121zM700 600h150l-250 -300l-250 300h150v300h200v-300z" />
<glyph unicode="&#xe027;" d="M600 1196q162 0 299 -80t217 -217t80 -299t-80 -299t-217 -217t-299 -80t-299 80t-217 217t-80 299t80 299t217 217t299 80zM600 1014q-171 0 -292.5 -121.5t-121.5 -292.5t121.5 -292.5t292.5 -121.5t292.5 121.5t121.5 292.5t-121.5 292.5t-292.5 121.5zM850 600h-150 v-300h-200v300h-150l250 300z" />
<glyph unicode="&#xe028;" d="M0 500l200 700h800q199 -700 200 -700v-475q0 -11 -7 -18t-18 -7h-1150q-11 0 -18 7t-7 18v475zM903 1000h-606l-97 -500h200l50 -200h300l50 200h200z" />
<glyph unicode="&#xe029;" d="M600 1196q162 0 299 -80t217 -217t80 -299t-80 -299t-217 -217t-299 -80t-299 80t-217 217t-80 299t80 299t217 217t299 80zM600 1014q-171 0 -292.5 -121.5t-121.5 -292.5q0 -172 121.5 -293t292.5 -121t292.5 121t121.5 293q0 171 -121.5 292.5t-292.5 121.5zM797 598 l-297 -201v401z" />
<glyph unicode="&#xe030;" d="M1177 600h-150q0 -177 -125 -302t-302 -125t-302 125t-125 302t125 302t302 125q136 0 246 -81l-146 -146h400v400l-145 -145q-157 122 -355 122q-118 0 -224.5 -45.5t-184 -123t-123 -184t-45.5 -224.5t45.5 -224.5t123 -184t184 -123t224.5 -45.5t224.5 45.5t184 123 t123 184t45.5 224.5z" />
<glyph unicode="&#xe031;" d="M700 800l147 147q-112 80 -247 80q-177 0 -302 -125t-125 -302h-150q0 118 45.5 224.5t123 184t184 123t224.5 45.5q198 0 355 -122l145 145v-400h-400zM500 400l-147 -147q112 -80 247 -80q177 0 302 125t125 302h150q0 -118 -45.5 -224.5t-123 -184t-184 -123 t-224.5 -45.5q-198 0 -355 122l-145 -145v400h400z" />
<glyph unicode="&#xe032;" d="M100 1200v-1200h1100v1200h-1100zM1100 100h-900v900h900v-900zM400 800h-100v100h100v-100zM1000 800h-500v100h500v-100zM400 600h-100v100h100v-100zM1000 600h-500v100h500v-100zM400 400h-100v100h100v-100zM1000 400h-500v100h500v-100zM400 200h-100v100h100v-100 zM1000 300h-500v-100h500v100z" />
<glyph unicode="&#xe034;" d="M200 0h-100v1100h100v-1100zM1100 600v500q-40 -81 -101.5 -115.5t-127.5 -29.5t-138 25t-139.5 40t-125.5 25t-103 -29.5t-65 -115.5v-500q60 60 127.5 84t127.5 17.5t122 -23t119 -30t110 -11t103 42t91 120.5z" />
<glyph unicode="&#xe035;" d="M1200 275v300q0 116 -49.5 227t-131 192.5t-192.5 131t-227 49.5t-227 -49.5t-192.5 -131t-131 -192.5t-49.5 -227v-300q0 -11 7 -18t18 -7h50q11 0 18 7t7 18v300q0 127 70.5 231.5t184.5 161.5t245 57t245 -57t184.5 -161.5t70.5 -231.5v-300q0 -11 7 -18t18 -7h50 q11 0 18 7t7 18zM400 480v-460q0 -8 -6 -14t-14 -6h-160q-8 0 -14 6t-6 14v460q0 8 6 14t14 6h160q8 0 14 -6t6 -14zM1000 480v-460q0 -8 -6 -14t-14 -6h-160q-8 0 -14 6t-6 14v460q0 8 6 14t14 6h160q8 0 14 -6t6 -14z" />
<glyph unicode="&#xe036;" d="M0 800v-400h300l300 -200v800l-300 -200h-300zM971 600l141 -141l-71 -71l-141 141l-141 -141l-71 71l141 141l-141 141l71 71l141 -141l141 141l71 -71z" />
<glyph unicode="&#xe037;" d="M0 800v-400h300l300 -200v800l-300 -200h-300zM700 857l69 53q111 -135 111 -310q0 -169 -106 -302l-67 54q86 110 86 248q0 146 -93 257z" />
<glyph unicode="&#xe038;" d="M974 186l6 8q142 178 142 405q0 230 -144 408l-6 8l-83 -64l7 -8q123 -151 123 -344q0 -189 -119 -339l-7 -8zM300 801l300 200v-800l-300 200h-300v400h300zM702 858l69 53q111 -135 111 -310q0 -170 -106 -303l-67 55q86 110 86 248q0 145 -93 257z" />
<glyph unicode="&#xe039;" d="M100 700h400v100h100v100h-100v300h-500v-600h100v100zM1200 700v500h-600v-200h100v-300h200v-300h300v200h-200v100h200zM100 1100h300v-300h-300v300zM800 800v300h300v-300h-300zM200 900h100v100h-100v-100zM900 1000h100v-100h-100v100zM300 600h-100v-100h-200 v-500h500v500h-200v100zM900 200v-100h-200v100h-100v100h100v200h-200v100h300v-300h200v-100h-100zM400 400v-300h-300v300h300zM300 200h-100v100h100v-100zM1100 300h100v-100h-100v100zM600 100h100v-100h-100v100zM1200 100v-100h-300v100h300z" />
<glyph unicode="&#xe040;" d="M100 1200h-100v-1000h100v1000zM300 200h-100v1000h100v-1000zM700 200h-200v1000h200v-1000zM900 200h-100v1000h100v-1000zM1200 1200v-1000h-200v1000h200zM400 100v-100h-300v100h300zM500 91h100v-91h-100v91zM700 91h100v-91h-100v91zM1100 91v-91h-200v91h200z " />
<glyph unicode="&#xe041;" d="M1200 500l-500 -500l-699 700v475q0 10 7.5 17.5t17.5 7.5h474zM320 882q29 29 29 71t-29 71q-30 30 -71.5 30t-71.5 -30q-29 -29 -29 -71t29 -71q30 -30 71.5 -30t71.5 30z" />
<glyph unicode="&#xe042;" d="M1201 500l-500 -500l-699 700v475q0 11 7 18t18 7h474zM1501 500l-500 -500l-50 50l450 450l-700 700h100zM320 882q30 29 30 71t-30 71q-29 30 -71 30t-71 -30q-30 -29 -30 -71t30 -71q29 -30 71 -30t71 30z" />
<glyph unicode="&#xe043;" d="M1200 1200v-1000l-100 -100v1000h-750l-100 -100h750v-1000h-900v1025l175 175h925z" />
<glyph unicode="&#xe045;" d="M947 829l-94 346q-2 11 -10 18t-18 7h-450q-10 0 -18 -7t-10 -18l-94 -346l40 -124h592zM1200 800v-700h-200v200h-800v-200h-200v700h200l100 -200h600l100 200h200zM881 176l38 -152q2 -10 -3.5 -17t-15.5 -7h-600q-10 0 -15.5 7t-3.5 17l38 152q2 10 11.5 17t19.5 7 h500q10 0 19.5 -7t11.5 -17z" />
<glyph unicode="&#xe047;" d="M1200 0v66q-34 1 -74 43q-18 19 -33 42t-21 37l-6 13l-385 998h-93l-399 -1006q-24 -48 -52 -75q-12 -12 -33 -25t-36 -20l-15 -7v-66h365v66q-41 0 -72 11t-49 38t1 71l92 234h391l82 -222q16 -45 -5.5 -88.5t-74.5 -43.5v-66h417zM416 521l178 457l46 -140l116 -317 h-340z" />
<glyph unicode="&#xe048;" d="M100 1199h471q120 0 213 -88t93 -228q0 -55 -11.5 -101.5t-28 -74t-33.5 -47.5t-28 -28l-12 -7q8 -3 21.5 -9t48 -31.5t60.5 -58t47.5 -91.5t21.5 -129q0 -84 -59 -156.5t-142 -111t-162 -38.5h-500v89q41 7 70.5 32.5t29.5 65.5v827q0 28 -1 39.5t-5.5 26t-15.5 21 t-29 14t-49 14.5v70zM400 1079v-379h139q76 0 130 61.5t54 138.5q0 82 -84 130.5t-239 48.5zM400 200h161q89 0 153 48.5t64 132.5q0 90 -62.5 154.5t-156.5 64.5h-159v-400z" />
<glyph unicode="&#xe049;" d="M877 1200l2 -57q-33 -8 -62 -25.5t-46 -37t-29.5 -38t-17.5 -30.5l-5 -12l-128 -825q-10 -52 14 -82t95 -36v-57h-500v57q77 7 134.5 40.5t65.5 80.5l173 849q10 56 -10 74t-91 37q-6 1 -10.5 2.5t-9.5 2.5v57h425z" />
<glyph unicode="&#xe050;" d="M1150 1200h150v-300h-50q0 29 -8 48.5t-18.5 30t-33.5 15t-39.5 5.5t-50.5 1h-200v-850l100 -50v-100h-400v100l100 50v850h-200q-34 0 -50.5 -1t-40 -5.5t-33.5 -15t-18.5 -30t-8.5 -48.5h-49v300h150h700zM100 1000v-800h75l-125 -167l-125 167h75v800h-75l125 167 l125 -167h-75z" />
<glyph unicode="&#xe051;" d="M950 1201h150v-300h-50q0 29 -8 48.5t-18 30t-33.5 15t-40 5.5t-50.5 1h-200v-650l100 -50v-100h-400v100l100 50v650h-200q-34 0 -50.5 -1t-39.5 -5.5t-33.5 -15t-18.5 -30t-8 -48.5h-50v300h150h700zM200 101h800v75l167 -125l-167 -125v75h-800v-75l-167 125l167 125 v-75z" />
<glyph unicode="&#xe052;" d="M700 950v100q0 21 -14.5 35.5t-35.5 14.5h-600q-21 0 -35.5 -14.5t-14.5 -35.5v-100q0 -20 14.5 -35t35.5 -15h600q21 0 35.5 15t14.5 35zM1100 650v100q0 21 -14.5 35.5t-35.5 14.5h-1000q-21 0 -35.5 -14.5t-14.5 -35.5v-100q0 -20 14.5 -35t35.5 -15h1000 q21 0 35.5 15t14.5 35zM900 350v100q0 21 -14.5 35.5t-35.5 14.5h-800q-21 0 -35.5 -14.5t-14.5 -35.5v-100q0 -20 14.5 -35t35.5 -15h800q21 0 35.5 15t14.5 35zM1200 50v100q0 21 -14.5 35.5t-35.5 14.5h-1100q-21 0 -35.5 -14.5t-14.5 -35.5v-100q0 -20 14.5 -35 t35.5 -15h1100q21 0 35.5 15t14.5 35z" />
<glyph unicode="&#xe053;" d="M1000 950v100q0 21 -14.5 35.5t-35.5 14.5h-700q-21 0 -35.5 -14.5t-14.5 -35.5v-100q0 -20 14.5 -35t35.5 -15h700q21 0 35.5 15t14.5 35zM1200 650v100q0 21 -14.5 35.5t-35.5 14.5h-1100q-21 0 -35.5 -14.5t-14.5 -35.5v-100q0 -20 14.5 -35t35.5 -15h1100 q21 0 35.5 15t14.5 35zM1000 350v100q0 21 -14.5 35.5t-35.5 14.5h-700q-21 0 -35.5 -14.5t-14.5 -35.5v-100q0 -20 14.5 -35t35.5 -15h700q21 0 35.5 15t14.5 35zM1200 50v100q0 21 -14.5 35.5t-35.5 14.5h-1100q-21 0 -35.5 -14.5t-14.5 -35.5v-100q0 -20 14.5 -35 t35.5 -15h1100q21 0 35.5 15t14.5 35z" />
<glyph unicode="&#xe054;" d="M500 950v100q0 21 14.5 35.5t35.5 14.5h600q21 0 35.5 -14.5t14.5 -35.5v-100q0 -20 -14.5 -35t-35.5 -15h-600q-21 0 -35.5 15t-14.5 35zM100 650v100q0 21 14.5 35.5t35.5 14.5h1000q21 0 35.5 -14.5t14.5 -35.5v-100q0 -20 -14.5 -35t-35.5 -15h-1000q-21 0 -35.5 15 t-14.5 35zM300 350v100q0 21 14.5 35.5t35.5 14.5h800q21 0 35.5 -14.5t14.5 -35.5v-100q0 -20 -14.5 -35t-35.5 -15h-800q-21 0 -35.5 15t-14.5 35zM0 50v100q0 21 14.5 35.5t35.5 14.5h1100q21 0 35.5 -14.5t14.5 -35.5v-100q0 -20 -14.5 -35t-35.5 -15h-1100 q-21 0 -35.5 15t-14.5 35z" />
<glyph unicode="&#xe055;" d="M0 950v100q0 21 14.5 35.5t35.5 14.5h1100q21 0 35.5 -14.5t14.5 -35.5v-100q0 -20 -14.5 -35t-35.5 -15h-1100q-21 0 -35.5 15t-14.5 35zM0 650v100q0 21 14.5 35.5t35.5 14.5h1100q21 0 35.5 -14.5t14.5 -35.5v-100q0 -20 -14.5 -35t-35.5 -15h-1100q-21 0 -35.5 15 t-14.5 35zM0 350v100q0 21 14.5 35.5t35.5 14.5h1100q21 0 35.5 -14.5t14.5 -35.5v-100q0 -20 -14.5 -35t-35.5 -15h-1100q-21 0 -35.5 15t-14.5 35zM0 50v100q0 21 14.5 35.5t35.5 14.5h1100q21 0 35.5 -14.5t14.5 -35.5v-100q0 -20 -14.5 -35t-35.5 -15h-1100 q-21 0 -35.5 15t-14.5 35z" />
<glyph unicode="&#xe056;" d="M0 950v100q0 21 14.5 35.5t35.5 14.5h100q21 0 35.5 -14.5t14.5 -35.5v-100q0 -20 -14.5 -35t-35.5 -15h-100q-21 0 -35.5 15t-14.5 35zM300 950v100q0 21 14.5 35.5t35.5 14.5h800q21 0 35.5 -14.5t14.5 -35.5v-100q0 -20 -14.5 -35t-35.5 -15h-800q-21 0 -35.5 15 t-14.5 35zM0 650v100q0 21 14.5 35.5t35.5 14.5h100q21 0 35.5 -14.5t14.5 -35.5v-100q0 -20 -14.5 -35t-35.5 -15h-100q-21 0 -35.5 15t-14.5 35zM300 650v100q0 21 14.5 35.5t35.5 14.5h800q21 0 35.5 -14.5t14.5 -35.5v-100q0 -20 -14.5 -35t-35.5 -15h-800 q-21 0 -35.5 15t-14.5 35zM0 350v100q0 21 14.5 35.5t35.5 14.5h100q21 0 35.5 -14.5t14.5 -35.5v-100q0 -20 -14.5 -35t-35.5 -15h-100q-21 0 -35.5 15t-14.5 35zM300 350v100q0 21 14.5 35.5t35.5 14.5h800q21 0 35.5 -14.5t14.5 -35.5v-100q0 -20 -14.5 -35t-35.5 -15 h-800q-21 0 -35.5 15t-14.5 35zM0 50v100q0 21 14.5 35.5t35.5 14.5h100q21 0 35.5 -14.5t14.5 -35.5v-100q0 -20 -14.5 -35t-35.5 -15h-100q-21 0 -35.5 15t-14.5 35zM300 50v100q0 21 14.5 35.5t35.5 14.5h800q21 0 35.5 -14.5t14.5 -35.5v-100q0 -20 -14.5 -35t-35.5 -15 h-800q-21 0 -35.5 15t-14.5 35z" />
<glyph unicode="&#xe057;" d="M400 1100h-100v-1100h100v1100zM700 950v100q0 21 -15 35.5t-35 14.5h-100q-21 0 -35.5 -14.5t-14.5 -35.5v-100q0 -20 14.5 -35t35.5 -15h100q20 0 35 15t15 35zM1100 650v100q0 21 -15 35.5t-35 14.5h-500q-21 0 -35.5 -14.5t-14.5 -35.5v-100q0 -20 14.5 -35t35.5 -15 h500q20 0 35 15t15 35zM100 425v75h-201v100h201v75l166 -125zM900 350v100q0 21 -15 35.5t-35 14.5h-300q-21 0 -35.5 -14.5t-14.5 -35.5v-100q0 -20 14.5 -35t35.5 -15h300q20 0 35 15t15 35zM1200 50v100q0 21 -15 35.5t-35 14.5h-600q-21 0 -35.5 -14.5t-14.5 -35.5 v-100q0 -20 14.5 -35t35.5 -15h600q20 0 35 15t15 35z" />
<glyph unicode="&#xe058;" d="M201 950v100q0 21 -15 35.5t-35 14.5h-100q-21 0 -35.5 -14.5t-14.5 -35.5v-100q0 -20 14.5 -35t35.5 -15h100q20 0 35 15t15 35zM801 1100h100v-1100h-100v1100zM601 650v100q0 21 -15 35.5t-35 14.5h-500q-21 0 -35.5 -14.5t-14.5 -35.5v-100q0 -20 14.5 -35t35.5 -15 h500q20 0 35 15t15 35zM1101 425v75h200v100h-200v75l-167 -125zM401 350v100q0 21 -15 35.5t-35 14.5h-300q-21 0 -35.5 -14.5t-14.5 -35.5v-100q0 -20 14.5 -35t35.5 -15h300q20 0 35 15t15 35zM701 50v100q0 21 -15 35.5t-35 14.5h-600q-21 0 -35.5 -14.5t-14.5 -35.5 v-100q0 -20 14.5 -35t35.5 -15h600q20 0 35 15t15 35z" />
<glyph unicode="&#xe059;" d="M900 925v-650q0 -31 -22 -53t-53 -22h-750q-31 0 -53 22t-22 53v650q0 31 22 53t53 22h750q31 0 53 -22t22 -53zM1200 300l-300 300l300 300v-600z" />
<glyph unicode="&#xe060;" d="M1200 1056v-1012q0 -18 -12.5 -31t-31.5 -13h-1112q-18 0 -31 13t-13 31v1012q0 18 13 31t31 13h1112q19 0 31.5 -13t12.5 -31zM1100 1000h-1000v-737l247 182l298 -131l-74 156l293 318l236 -288v500zM476 750q0 -56 -39 -95t-95 -39t-95 39t-39 95t39 95t95 39t95 -39 t39 -95z" />
<glyph unicode="&#xe062;" d="M600 1213q123 0 227 -63t164.5 -169.5t60.5 -229.5t-73 -272q-73 -114 -166.5 -237t-150.5 -189l-57 -66q-10 9 -27 26t-66.5 70.5t-96 109t-104 135.5t-100.5 155q-63 139 -63 262q0 124 60.5 231.5t165 172t226.5 64.5zM599 514q107 0 182.5 75.5t75.5 182.5t-75.5 182 t-182.5 75t-182 -75.5t-75 -181.5q0 -107 75.5 -182.5t181.5 -75.5z" />
<glyph unicode="&#xe063;" d="M600 1199q122 0 233 -47.5t191 -127.5t127.5 -191t47.5 -233t-47.5 -233t-127.5 -191t-191 -127.5t-233 -47.5t-233 47.5t-191 127.5t-127.5 191t-47.5 233t47.5 233t127.5 191t191 127.5t233 47.5zM600 173v854q-176 0 -301.5 -125t-125.5 -302t125.5 -302t301.5 -125z " />
<glyph unicode="&#xe064;" d="M554 1295q21 -71 57.5 -142.5t76 -130.5t83 -118.5t82 -117t70 -116t50 -125.5t18.5 -136q0 -89 -39 -165.5t-102 -126.5t-140 -79.5t-156 -33.5q-114 6 -211.5 53t-161.5 138.5t-64 210.5q0 94 34 186t88.5 172.5t112 159t115 177t87.5 194.5zM455 296q-7 6 -18 17 t-34 48t-33 77q-15 73 -14 143.5t10 122.5l9 51q-92 -110 -119.5 -185t-12.5 -156q14 -82 59.5 -136t136.5 -80z" />
<glyph unicode="&#xe065;" d="M1108 902l113 113l-21 85l-92 28l-113 -113zM1100 625v-225q0 -165 -117.5 -282.5t-282.5 -117.5h-300q-165 0 -282.5 117.5t-117.5 282.5v300q0 165 117.5 282.5t282.5 117.5q366 -6 397 -14l-186 -186h-311q-41 0 -70.5 -29.5t-29.5 -70.5v-500q0 -41 29.5 -70.5 t70.5 -29.5h500q41 0 70.5 29.5t29.5 70.5v125zM436 341l161 50l412 412l-114 113l-405 -405z" />
<glyph unicode="&#xe066;" d="M1100 453v-53q0 -165 -117.5 -282.5t-282.5 -117.5h-300q-165 0 -282.5 117.5t-117.5 282.5v300q0 165 117.5 282.5t282.5 117.5h261l2 -80q-133 -32 -218 -120h-145q-41 0 -70.5 -29.5t-29.5 -70.5v-500q0 -41 29.5 -70.5t70.5 -29.5h500q41 0 70.5 29.5t29.5 70.5z M813 431l360 324l-359 318v-216q-7 0 -19 -1t-48 -8t-69.5 -18.5t-76.5 -37t-76.5 -59t-62 -88t-39.5 -121.5q30 38 81.5 64t103 35.5t99 14t77.5 3.5l29 -1v-209z" />
<glyph unicode="&#xe067;" d="M1100 569v-169q0 -165 -117.5 -282.5t-282.5 -117.5h-300q-165 0 -282.5 117.5t-117.5 282.5v300q0 165 117.5 282.5t282.5 117.5h300q60 0 127 -23l-178 -177h-349q-41 0 -70.5 -29.5t-29.5 -70.5v-500q0 -41 29.5 -70.5t70.5 -29.5h500q41 0 70.5 29.5t29.5 70.5v69z M625 348l566 567l-136 137l-430 -431l-147 147l-136 -136z" />
<glyph unicode="&#xe068;" d="M900 303v198h-200v-200h195l-295 -300l-300 300h200v200h-200v-198l-300 300l300 296v-198h200v200h-200l300 300l295 -300h-195v-200h200v198l300 -296z" />
<glyph unicode="&#xe069;" d="M900 0l-500 488v-438q0 -21 -14.5 -35.5t-35.5 -14.5h-100q-21 0 -35.5 14.5t-14.5 35.5v1000q0 21 14.5 35.5t35.5 14.5h100q21 0 35.5 -14.5t14.5 -35.5v-437l500 487v-1100z" />
<glyph unicode="&#xe070;" d="M1200 0l-500 488v-488l-500 488v-438q0 -21 -14.5 -35.5t-35.5 -14.5h-100q-21 0 -35.5 14.5t-14.5 35.5v1000q0 21 14.5 35.5t35.5 14.5h100q21 0 35.5 -14.5t14.5 -35.5v-437l500 487v-487l500 487v-1100z" />
<glyph unicode="&#xe071;" d="M1200 0l-500 488v-488l-564 550l564 550v-487l500 487v-1100z" />
<glyph unicode="&#xe072;" d="M1100 550l-900 550v-1100z" />
<glyph unicode="&#xe073;" d="M500 150v800q0 21 -14.5 35.5t-35.5 14.5h-200q-21 0 -35.5 -14.5t-14.5 -35.5v-800q0 -21 14.5 -35.5t35.5 -14.5h200q21 0 35.5 14.5t14.5 35.5zM900 150v800q0 21 -14.5 35.5t-35.5 14.5h-200q-21 0 -35.5 -14.5t-14.5 -35.5v-800q0 -21 14.5 -35.5t35.5 -14.5h200 q21 0 35.5 14.5t14.5 35.5z" />
<glyph unicode="&#xe074;" d="M1100 150v800q0 21 -14.5 35.5t-35.5 14.5h-800q-21 0 -35.5 -14.5t-14.5 -35.5v-800q0 -20 14.5 -35t35.5 -15h800q21 0 35.5 15t14.5 35z" />
<glyph unicode="&#xe075;" d="M500 0v488l-500 -488v1100l500 -487v487l564 -550z" />
<glyph unicode="&#xe076;" d="M1050 1100h100q21 0 35.5 -14.5t14.5 -35.5v-1000q0 -21 -14.5 -35.5t-35.5 -14.5h-100q-21 0 -35.5 14.5t-14.5 35.5v438l-500 -488v488l-500 -488v1100l500 -487v487l500 -487v437q0 21 14.5 35.5t35.5 14.5z" />
<glyph unicode="&#xe077;" d="M850 1100h100q21 0 35.5 -14.5t14.5 -35.5v-1000q0 -21 -14.5 -35.5t-35.5 -14.5h-100q-21 0 -35.5 14.5t-14.5 35.5v438l-500 -488v1100l500 -487v437q0 21 14.5 35.5t35.5 14.5z" />
<glyph unicode="&#xe078;" d="M650 1064l-550 -564h1100zM1200 350v-100q0 -21 -14.5 -35.5t-35.5 -14.5h-1000q-21 0 -35.5 14.5t-14.5 35.5v100q0 21 14.5 35.5t35.5 14.5h1000q21 0 35.5 -14.5t14.5 -35.5z" />
<glyph unicode="&#xe079;" d="M777 7l240 240l-353 353l353 353l-240 240l-592 -594z" />
<glyph unicode="&#xe080;" d="M513 -46l-241 240l353 353l-353 353l241 240l572 -571l21 -22l-1 -1v-1z" />
<glyph unicode="&#xe081;" d="M600 1197q162 0 299.5 -80t217.5 -217.5t80 -299.5t-80 -299.5t-217.5 -217.5t-299.5 -80t-299.5 80t-217.5 217.5t-80 299.5t80 299.5t217.5 217.5t299.5 80zM500 900v-200h-200v-200h200v-200h200v200h200v200h-200v200h-200z" />
<glyph unicode="&#xe082;" d="M600 1197q162 0 299.5 -80t217.5 -217.5t80 -299.5t-80 -299.5t-217.5 -217.5t-299.5 -80t-299.5 80t-217.5 217.5t-80 299.5t80 299.5t217.5 217.5t299.5 80zM300 700v-200h600v200h-600z" />
<glyph unicode="&#xe083;" d="M600 1197q162 0 299.5 -80t217.5 -217.5t80 -299.5t-80 -299.5t-217.5 -217.5t-299.5 -80t-299.5 80t-217.5 217.5t-80 299.5t80 299.5t217.5 217.5t299.5 80zM247 741l141 -141l-142 -141l213 -213l141 142l141 -142l213 213l-142 141l142 141l-213 212l-141 -141 l-141 142z" />
<glyph unicode="&#xe084;" d="M600 1197q162 0 299.5 -80t217.5 -217.5t80 -299.5t-80 -299.5t-217.5 -217.5t-299.5 -80t-299.5 80t-217.5 217.5t-80 299.5t80 299.5t217.5 217.5t299.5 80zM546 623l-102 102l-174 -174l276 -277l411 411l-175 174z" />
<glyph unicode="&#xe085;" d="M600 1197q162 0 299.5 -80t217.5 -217.5t80 -299.5t-80 -299.5t-217.5 -217.5t-299.5 -80t-299.5 80t-217.5 217.5t-80 299.5t80 299.5t217.5 217.5t299.5 80zM500 500h200q5 3 14 8t31.5 25.5t39.5 45.5t31 69t14 94q0 51 -17.5 89t-42 58t-58.5 32t-58.5 15t-51.5 3 q-105 0 -172 -56t-67 -183h144q4 0 11.5 -1t11 -1t6.5 3t3 9t1 11t3.5 8.5t3.5 6t5.5 4t6.5 2.5t9 1.5t9 0.5h11.5h12.5q19 0 30 -10t11 -26q0 -22 -4 -28t-27 -22q-5 -1 -12.5 -3t-27 -13.5t-34 -27t-26.5 -46t-11 -68.5zM500 400v-100h200v100h-200z" />
<glyph unicode="&#xe086;" d="M600 1197q162 0 299.5 -80t217.5 -217.5t80 -299.5t-80 -299.5t-217.5 -217.5t-299.5 -80t-299.5 80t-217.5 217.5t-80 299.5t80 299.5t217.5 217.5t299.5 80zM500 900v-100h200v100h-200zM400 700v-100h100v-200h-100v-100h400v100h-100v300h-300z" />
<glyph unicode="&#xe087;" d="M1200 700v-200h-203q-25 -102 -116.5 -186t-180.5 -117v-197h-200v197q-140 27 -208 102.5t-98 200.5h-194v200h194q15 60 36 104.5t55.5 86t88 69t126.5 40.5v200h200v-200q54 -20 113 -60t112.5 -105.5t71.5 -134.5h203zM700 500v-206q149 48 201 206h-201v200h200 q-25 74 -76 127.5t-124 76.5v-204h-200v203q-75 -24 -130 -77.5t-79 -125.5h209v-200h-210q24 -73 79.5 -127.5t130.5 -78.5v206h200z" />
<glyph unicode="&#xe088;" d="M600 1196q162 0 299 -80t217 -217t80 -299t-80 -299t-217 -217t-299 -80t-299 80t-217 217t-80 299t80 299t217 217t299 80zM600 1014q-171 0 -292.5 -121.5t-121.5 -292.5t121.5 -292.5t292.5 -121.5t292.5 121.5t121.5 292.5t-121.5 292.5t-292.5 121.5zM844 735 l-135 -135l135 -135l-109 -109l-135 135l-135 -135l-109 109l135 135l-135 135l109 109l135 -135l135 135z" />
<glyph unicode="&#xe089;" d="M600 1196q162 0 299 -80t217 -217t80 -299t-80 -299t-217 -217t-299 -80t-299 80t-217 217t-80 299t80 299t217 217t299 80zM600 1014q-171 0 -292.5 -121.5t-121.5 -292.5t121.5 -292.5t292.5 -121.5t292.5 121.5t121.5 292.5t-121.5 292.5t-292.5 121.5zM896 654 l-346 -345l-228 228l141 141l87 -87l204 205z" />
<glyph unicode="&#xe090;" d="M600 1196q162 0 299 -80t217 -217t80 -299t-80 -299t-217 -217t-299 -80t-299 80t-217 217t-80 299t80 299t217 217t299 80zM248 385l568 567q-100 62 -216 62q-171 0 -292.5 -121.5t-121.5 -292.5q0 -115 62 -215zM955 809l-564 -564q97 -59 209 -59q171 0 292.5 121.5 t121.5 292.5q0 112 -59 209z" />
<glyph unicode="&#xe091;" d="M1200 400h-600v-301l-600 448l600 453v-300h600v-300z" />
<glyph unicode="&#xe092;" d="M600 400h-600v300h600v300l600 -453l-600 -448v301z" />
<glyph unicode="&#xe093;" d="M1098 600h-298v-600h-300v600h-296l450 600z" />
<glyph unicode="&#xe094;" d="M998 600l-449 -600l-445 600h296v600h300v-600h298z" />
<glyph unicode="&#xe095;" d="M600 199v301q-95 -2 -183 -20t-170 -52t-147 -92.5t-100 -135.5q6 132 41 238.5t103.5 193t184 138t271.5 59.5v271l600 -453z" />
<glyph unicode="&#xe096;" d="M1200 1200h-400l129 -129l-294 -294l142 -142l294 294l129 -129v400zM565 423l-294 -294l129 -129h-400v400l129 -129l294 294z" />
<glyph unicode="&#xe097;" d="M871 730l129 -130h-400v400l129 -129l295 295l142 -141zM200 600h400v-400l-129 130l-295 -295l-142 141l295 295z" />
<glyph unicode="&#xe101;" d="M600 1177q118 0 224.5 -45.5t184 -123t123 -184t45.5 -224.5t-45.5 -224.5t-123 -184t-184 -123t-224.5 -45.5t-224.5 45.5t-184 123t-123 184t-45.5 224.5t45.5 224.5t123 184t184 123t224.5 45.5zM686 549l58 302q4 20 -8 34.5t-33 14.5h-207q-20 0 -32 -14.5t-8 -34.5 l58 -302q4 -20 21.5 -34.5t37.5 -14.5h54q20 0 37.5 14.5t21.5 34.5zM700 400h-200v-100h200v100z" />
<glyph unicode="&#xe102;" d="M1200 900h-111v6t-1 15t-3 18l-34 172q-11 39 -41.5 63t-69.5 24q-32 0 -61 -17l-239 -144q-22 -13 -40 -35q-19 24 -40 36l-238 144q-33 18 -62 18q-39 0 -69.5 -23t-40.5 -61l-35 -177q-2 -8 -3 -18t-1 -15v-6h-111v-100h100v-200h400v300h200v-300h400v200h100v100z M731 900l202 197q5 -12 12 -32.5t23 -64t25 -72t7 -28.5h-269zM481 900h-281q-3 0 14 48t35 96l18 47zM100 0h400v400h-400v-400zM700 400h400v-400h-400v400z" />
<glyph unicode="&#xe103;" d="M0 121l216 193q-9 53 -13 83t-5.5 94t9 113t38.5 114t74 124q47 60 99.5 102.5t103 68t127.5 48t145.5 37.5t184.5 43.5t220 58.5q0 -189 -22 -343t-59 -258t-89 -181.5t-108.5 -120t-122 -68t-125.5 -30t-121.5 -1.5t-107.5 12.5t-87.5 17t-56.5 7.5l-99 -55l-201 -202 v143zM692 611q70 38 118.5 69.5t102 79t99 111.5t86.5 148q22 50 24 60t-6 19q-7 5 -17 5t-26.5 -14.5t-33.5 -39.5q-35 -51 -113.5 -108.5t-139.5 -89.5l-61 -32q-369 -197 -458 -401q-48 -111 -28.5 -117.5t86.5 76.5q55 66 367 234z" />
<glyph unicode="&#xe105;" d="M1261 600l-26 -40q-6 -10 -20 -30t-49 -63.5t-74.5 -85.5t-97 -90t-116.5 -83.5t-132.5 -59t-145.5 -23.5t-145.5 23.5t-132.5 59t-116.5 83.5t-97 90t-74.5 85.5t-49 63.5t-20 30l-26 40l26 40q6 10 20 30t49 63.5t74.5 85.5t97 90t116.5 83.5t132.5 59t145.5 23.5 t145.5 -23.5t132.5 -59t116.5 -83.5t97 -90t74.5 -85.5t49 -63.5t20 -30zM600 240q64 0 123.5 20t100.5 45.5t85.5 71.5t66.5 75.5t58 81.5t47 66q-1 1 -28.5 37.5t-42 55t-43.5 53t-57.5 63.5t-58.5 54q49 -74 49 -163q0 -124 -88 -212t-212 -88t-212 88t-88 212 q0 85 46 158q-102 -87 -226 -258q7 -10 40.5 -58t56 -78.5t68 -77.5t87.5 -75t103 -49.5t125 -21.5zM484 762l-107 -106q49 -124 154 -191l105 105q-37 24 -75 72t-57 84z" />
<glyph unicode="&#xe106;" d="M906 1200l-314 -1200h-148l37 143q-82 21 -165 71.5t-140 102t-109.5 112t-72 88.5t-29.5 43l-26 40l26 40q6 10 20 30t49 63.5t74.5 85.5t97 90t116.5 83.5t132.5 59t145.5 23.5q61 0 121 -17l37 142h148zM1261 600l-26 -40q-7 -12 -25.5 -38t-63.5 -79.5t-95.5 -102.5 t-124 -100t-146.5 -79l38 145q22 15 44.5 34t46 44t40.5 44t41 50.5t33.5 43.5t33 44t24.5 34q-97 127 -140 175l39 146q67 -54 131.5 -125.5t87.5 -103.5t36 -52zM513 264l37 141q-107 18 -178.5 101.5t-71.5 193.5q0 85 46 158q-102 -87 -226 -258q210 -282 393 -336z M484 762l-107 -106q49 -124 154 -191l47 47l23 87q-30 28 -59 69t-44 68z" />
<glyph unicode="&#xe107;" d="M-47 0h1294q37 0 50.5 35.5t-7.5 67.5l-642 1056q-20 33 -48 36t-48 -29l-642 -1066q-21 -32 -7.5 -66t50.5 -34zM700 200v100h-200v-100h-345l445 723l445 -723h-345zM700 700h-200v-100l100 -300l100 300v100z" />
<glyph unicode="&#xe108;" d="M800 711l363 -325q15 -14 26 -38.5t11 -44.5v-41q0 -20 -12 -26.5t-29 5.5l-359 249v-263q100 -91 100 -113v-64q0 -21 -13 -29t-32 1l-94 78h-222l-94 -78q-19 -9 -32 -1t-13 29v64q0 22 100 113v263l-359 -249q-17 -12 -29 -5.5t-12 26.5v41q0 20 11 44.5t26 38.5 l363 325v339q0 62 44 106t106 44t106 -44t44 -106v-339z" />
<glyph unicode="&#xe110;" d="M941 800l-600 -600h-341v200h259l600 600h241v198l300 -295l-300 -300v197h-159zM381 678l141 142l-181 180h-341v-200h259zM1100 598l300 -295l-300 -300v197h-241l-181 181l141 142l122 -123h159v198z" />
<glyph unicode="&#xe111;" d="M100 1100h1000q41 0 70.5 -29.5t29.5 -70.5v-600q0 -41 -29.5 -70.5t-70.5 -29.5h-596l-304 -300v300h-100q-41 0 -70.5 29.5t-29.5 70.5v600q0 41 29.5 70.5t70.5 29.5z" />
<glyph unicode="&#xe112;" d="M400 900h-300v300h300v-300zM1100 900h-300v300h300v-300zM1100 800v-200q0 -42 -3 -83t-15 -104t-31.5 -116t-58 -109.5t-89 -96.5t-129 -65.5t-174.5 -25.5t-174.5 25.5t-129 65.5t-89 96.5t-58 109.5t-31.5 116t-15 104t-3 83v200h300v-250q0 -113 6 -145 q17 -92 102 -117q39 -11 92 -11q37 0 66.5 5.5t50 15.5t36 24t24 31.5t14 37.5t7 42t2.5 45t0 47v25v250h300z" />
<glyph unicode="&#xe113;" d="M902 184l226 227l-578 579l-580 -579l227 -227l352 353z" />
<glyph unicode="&#xe114;" d="M650 218l578 579l-226 227l-353 -353l-352 353l-227 -227z" />
<glyph unicode="&#xe115;" d="M1198 400v600h-796l215 -200h381v-400h-198l299 -283l299 283h-200zM-198 700l299 283l300 -283h-203v-400h385l215 -200h-800v600h-196z" />
<glyph unicode="&#xe116;" d="M1050 1200h94q20 0 35 -14.5t15 -35.5t-15 -35.5t-35 -14.5h-54l-201 -961q-2 -4 -6 -10.5t-19 -17.5t-33 -11h-31v-50q0 -20 -14.5 -35t-35.5 -15t-35.5 15t-14.5 35v50h-300v-50q0 -20 -14.5 -35t-35.5 -15t-35.5 15t-14.5 35v50h-50q-21 0 -35.5 15t-14.5 35 q0 21 14.5 35.5t35.5 14.5h535l48 200h-633q-32 0 -54.5 21t-27.5 43l-100 475q-5 24 10 42q14 19 39 19h896l38 162q5 17 18.5 27.5t30.5 10.5z" />
<glyph unicode="&#xe117;" d="M1200 1000v-100h-1200v100h200q0 41 29.5 70.5t70.5 29.5h300q41 0 70.5 -29.5t29.5 -70.5h500zM0 800h1200v-800h-1200v800z" />
<glyph unicode="&#xe118;" d="M201 800l-200 -400v600h200q0 41 29.5 70.5t70.5 29.5h300q41 0 70.5 -29.5t29.5 -70.5h500v-200h-1000zM1501 700l-300 -700h-1200l300 700h1200z" />
<glyph unicode="&#xe119;" d="M302 300h198v600h-198l298 300l298 -300h-198v-600h198l-298 -300z" />
<glyph unicode="&#xe120;" d="M900 303v197h-600v-197l-300 297l300 298v-198h600v198l300 -298z" />
<glyph unicode="&#xe121;" d="M31 400l172 739q5 22 23 41.5t38 19.5h672q19 0 37.5 -22.5t23.5 -45.5l172 -732h-1138zM100 300h1000q41 0 70.5 -29.5t29.5 -70.5v-100q0 -41 -29.5 -70.5t-70.5 -29.5h-1000q-41 0 -70.5 29.5t-29.5 70.5v100q0 41 29.5 70.5t70.5 29.5zM900 200h-100v-100h100v100z M1100 200h-100v-100h100v100z" />
<glyph unicode="&#xe122;" d="M1100 200v850q0 21 14.5 35.5t35.5 14.5q20 0 35 -14.5t15 -35.5v-850q0 -20 -15 -35t-35 -15q-21 0 -35.5 15t-14.5 35zM325 800l675 250v-850l-675 200h-38l47 -276q2 -12 -3 -17.5t-11 -6t-21 -0.5h-8h-83q-20 0 -34.5 14t-18.5 35q-56 337 -56 351v250v5 q0 13 0.5 18.5t2.5 13t8 10.5t15 3h200zM-101 600v50q0 24 25 49t50 38l25 13v-250l-11 5.5t-24 14t-30 21.5t-24 27.5t-11 31.5z" />
<glyph unicode="&#xe124;" d="M445 1180l-45 -233l-224 78l78 -225l-233 -44l179 -156l-179 -155l233 -45l-78 -224l224 78l45 -233l155 179l155 -179l45 233l224 -78l-78 224l234 45l-180 155l180 156l-234 44l78 225l-224 -78l-45 233l-155 -180z" />
<glyph unicode="&#xe125;" d="M700 1200h-50q-27 0 -51 -20t-38 -48l-96 -198l-145 -196q-20 -26 -20 -63v-400q0 -75 100 -75h61q123 -100 139 -100h250q46 0 83 57l238 344q29 31 29 74v100q0 44 -30.5 84.5t-69.5 40.5h-328q28 118 28 125v150q0 44 -30.5 84.5t-69.5 40.5zM700 925l-50 -225h450 v-125l-250 -375h-214l-136 100h-100v375l150 212l100 213h50v-175zM0 800v-600h200v600h-200z" />
<glyph unicode="&#xe126;" d="M700 0h-50q-27 0 -51 20t-38 48l-96 198l-145 196q-20 26 -20 63v400q0 75 100 75h61q123 100 139 100h250q46 0 83 -57l238 -344q29 -31 29 -74v-100q0 -44 -30.5 -84.5t-69.5 -40.5h-328q28 -118 28 -125v-150q0 -44 -30.5 -84.5t-69.5 -40.5zM200 400h-200v600h200 v-600zM700 275l-50 225h450v125l-250 375h-214l-136 -100h-100v-375l150 -212l100 -213h50v175z" />
<glyph unicode="&#xe127;" d="M364 873l362 230q14 6 25 6q17 0 29 -12l109 -112q14 -14 14 -34q0 -18 -11 -32l-85 -121h302q85 0 138.5 -38t53.5 -110t-54.5 -111t-138.5 -39h-107l-130 -339q-7 -22 -20.5 -41.5t-28.5 -19.5h-341q-7 0 -90 81t-83 94v525q0 17 14 35.5t28 28.5zM408 792v-503 l100 -89h293l131 339q6 21 19.5 41t28.5 20h203q16 0 25 15t9 36q0 20 -9 34.5t-25 14.5h-457h-6.5h-7.5t-6.5 0.5t-6 1t-5 1.5t-5.5 2.5t-4 4t-4 5.5q-5 12 -5 20q0 14 10 27l147 183l-86 83zM208 200h-200v600h200v-600z" />
<glyph unicode="&#xe128;" d="M475 1104l365 -230q7 -4 16.5 -10.5t26 -26t16.5 -36.5v-526q0 -13 -85.5 -93.5t-93.5 -80.5h-342q-15 0 -28.5 20t-19.5 41l-131 339h-106q-84 0 -139 39t-55 111t54 110t139 37h302l-85 121q-11 16 -11 32q0 21 14 34l109 113q13 12 29 12q11 0 25 -6zM370 946 l145 -184q10 -11 10 -26q0 -11 -5 -20q-1 -3 -3.5 -5.5l-4 -4t-5 -2.5t-5.5 -1.5t-6.5 -1t-6.5 -0.5h-7.5h-6.5h-476v-100h222q15 0 28.5 -20.5t19.5 -40.5l131 -339h293l106 89v502l-342 237zM1199 201h-200v600h200v-600z" />
<glyph unicode="&#xe129;" d="M1100 473v342q0 15 -20 28.5t-41 19.5l-339 131v106q0 84 -39 139t-111 55t-110 -53.5t-38 -138.5v-302l-121 84q-15 12 -33.5 11.5t-32.5 -13.5l-112 -110q-22 -22 -6 -53l230 -363q4 -6 10.5 -15.5t26 -25t36.5 -15.5h525q13 0 94 83t81 90zM911 400h-503l-236 339 l83 86l183 -146q22 -18 47 -5q3 1 5.5 3.5l4 4t2.5 5t1.5 5.5t1 6.5t0.5 6v7.5v7v456q0 22 25 31t50 -0.5t25 -30.5v-202q0 -16 20 -29.5t41 -19.5l339 -130v-294zM1000 200v-200h-600v200h600z" />
<glyph unicode="&#xe130;" d="M305 1104v200h600v-200h-600zM605 310l339 131q20 6 40.5 19.5t20.5 28.5v342q0 7 -81 90t-94 83h-525q-17 0 -35.5 -14t-28.5 -28l-10 -15l-230 -362q-15 -31 7 -53l112 -110q13 -13 32 -13.5t34 10.5l121 85l-1 -302q0 -84 38.5 -138t110.5 -54t111 55t39 139v106z M905 804v-294l-340 -130q-20 -6 -40 -20t-20 -29v-202q0 -22 -25 -31t-50 0t-25 31v456v14.5t-1.5 11.5t-5 12t-9.5 7q-24 13 -46 -5l-184 -146l-83 86l237 339h503z" />
<glyph unicode="&#xe131;" d="M603 1195q162 0 299.5 -80t217.5 -218t80 -300t-80 -299.5t-217.5 -217.5t-299.5 -80t-300 80t-218 217.5t-80 299.5q0 122 47.5 232.5t127.5 190.5t190.5 127.5t232.5 47.5zM598 701h-298v-201h300l-2 -194l402 294l-402 298v-197z" />
<glyph unicode="&#xe132;" d="M597 1195q122 0 232.5 -47.5t190.5 -127.5t127.5 -190.5t47.5 -232.5q0 -162 -80 -299.5t-218 -217.5t-300 -80t-299.5 80t-217.5 217.5t-80 299.5q0 122 47.5 232.5t127.5 190.5t190.5 127.5t231.5 47.5zM200 600l400 -294v194h302v201h-300v197z" />
<glyph unicode="&#xe133;" d="M603 1195q121 0 231.5 -47.5t190.5 -127.5t127.5 -190.5t47.5 -232.5q0 -162 -80 -299.5t-217.5 -217.5t-299.5 -80t-300 80t-218 217.5t-80 299.5q0 122 47.5 232.5t127.5 190.5t190.5 127.5t232.5 47.5zM300 600h200v-300h200v300h200l-300 400z" />
<glyph unicode="&#xe134;" d="M603 1195q121 0 231.5 -47.5t190.5 -127.5t127.5 -190.5t47.5 -232.5q0 -162 -80 -299.5t-217.5 -217.5t-299.5 -80t-300 80t-218 217.5t-80 299.5q0 122 47.5 232.5t127.5 190.5t190.5 127.5t232.5 47.5zM500 900v-300h-200l300 -400l300 400h-200v300h-200z" />
<glyph unicode="&#xe135;" d="M603 1195q121 0 231.5 -47.5t190.5 -127.5t127.5 -190.5t47.5 -232.5q0 -162 -80 -299.5t-217.5 -217.5t-299.5 -80t-300 80t-218 217.5t-80 299.5q0 122 47.5 232.5t127.5 190.5t190.5 127.5t232.5 47.5zM627 1101q-15 -12 -36.5 -21t-34.5 -12t-44 -8t-39 -6 q-15 -3 -45.5 0.5t-45.5 -2.5q-21 -7 -52 -26.5t-34 -34.5q-3 -11 6.5 -22.5t8.5 -18.5q-3 -34 -27.5 -90.5t-29.5 -79.5q-8 -33 5.5 -92.5t7.5 -87.5q0 -9 17 -44t16 -60q12 0 23 -5.5t23 -15t20 -13.5q24 -12 108 -42q22 -8 53 -31.5t59.5 -38.5t57.5 -11q8 -18 -15 -55 t-20 -57q42 -71 87 -80q0 -6 -3 -15.5t-3.5 -14.5t4.5 -17q102 -2 221 112q30 29 47 47t34.5 49t20.5 62q-14 9 -37 9.5t-36 7.5q-14 7 -49 15t-52 19q-9 0 -39.5 -0.5t-46.5 -1.5t-39 -6.5t-39 -16.5q-50 -35 -66 -12q-4 2 -3.5 25.5t0.5 25.5q-6 13 -26.5 17t-24.5 7 q2 22 -2 41t-16.5 28t-38.5 -20q-23 -25 -42 4q-19 28 -8 58q6 16 22 22q6 -1 26 -1.5t33.5 -4t19.5 -13.5q12 -19 32 -37.5t34 -27.5l14 -8q0 3 9.5 39.5t5.5 57.5q-4 23 14.5 44.5t22.5 31.5q5 14 10 35t8.5 31t15.5 22.5t34 21.5q-6 18 10 37q8 0 23.5 -1.5t24.5 -1.5 t20.5 4.5t20.5 15.5q-10 23 -30.5 42.5t-38 30t-49 26.5t-43.5 23q11 41 1 44q31 -13 58.5 -14.5t39.5 3.5l11 4q6 36 -17 53.5t-64 28.5t-56 23q-19 -3 -37 0zM613 994q0 -18 8 -42.5t16.5 -44t9.5 -23.5q-9 2 -31 5t-36 5t-32 8t-30 14q3 12 16 30t16 25q10 -10 18.5 -10 t14 6t14.5 14.5t16 12.5z" />
<glyph unicode="&#xe137;" horiz-adv-x="1220" d="M100 1196h1000q41 0 70.5 -29.5t29.5 -70.5v-100q0 -41 -29.5 -70.5t-70.5 -29.5h-1000q-41 0 -70.5 29.5t-29.5 70.5v100q0 41 29.5 70.5t70.5 29.5zM1100 1096h-200v-100h200v100zM100 796h1000q41 0 70.5 -29.5t29.5 -70.5v-100q0 -41 -29.5 -70.5t-70.5 -29.5h-1000 q-41 0 -70.5 29.5t-29.5 70.5v100q0 41 29.5 70.5t70.5 29.5zM1100 696h-500v-100h500v100zM100 396h1000q41 0 70.5 -29.5t29.5 -70.5v-100q0 -41 -29.5 -70.5t-70.5 -29.5h-1000q-41 0 -70.5 29.5t-29.5 70.5v100q0 41 29.5 70.5t70.5 29.5zM1100 296h-300v-100h300v100z " />
<glyph unicode="&#xe138;" d="M1100 1200v-100h-1000v100h1000zM150 1000h900l-350 -500v-300l-200 -200v500z" />
<glyph unicode="&#xe140;" d="M329 729l142 142l-200 200l129 129h-400v-400l129 129zM1200 1200v-400l-129 129l-200 -200l-142 142l200 200l-129 129h400zM271 129l129 -129h-400v400l129 -129l200 200l142 -142zM1071 271l129 129v-400h-400l129 129l-200 200l142 142z" />
<glyph unicode="&#xe141;" d="M596 1192q162 0 299 -80t217 -217t80 -299t-80 -299t-217 -217t-299 -80t-299 80t-217 217t-80 299t80 299t217 217t299 80zM596 1010q-171 0 -292.5 -121.5t-121.5 -292.5q0 -172 121.5 -293t292.5 -121t292.5 121t121.5 293q0 171 -121.5 292.5t-292.5 121.5zM455 905 q22 0 38 -16t16 -39t-16 -39t-38 -16q-23 0 -39 16.5t-16 38.5t16 38.5t39 16.5zM708 821l1 1q-9 14 -9 28q0 22 16 38.5t39 16.5q22 0 38 -16t16 -39t-16 -39t-38 -16q-14 0 -29 10l-55 -145q17 -22 17 -51q0 -36 -25.5 -61.5t-61.5 -25.5t-61.5 25.5t-25.5 61.5 q0 32 20.5 56.5t51.5 29.5zM855 709q23 0 38.5 -15.5t15.5 -38.5t-16 -39t-38 -16q-23 0 -39 16t-16 39q0 22 16 38t39 16zM345 709q23 0 39 -16t16 -38q0 -23 -16 -39t-39 -16q-22 0 -38 16t-16 39t15.5 38.5t38.5 15.5z" />
<glyph unicode="&#xe143;" d="M649 54l-16 22q-90 125 -293 323q-71 70 -104.5 105.5t-77 89.5t-61 99t-17.5 91q0 131 98.5 229.5t230.5 98.5q143 0 241 -129q103 129 246 129q129 0 226 -98.5t97 -229.5q0 -46 -17.5 -91t-61 -99t-77 -89.5t-104.5 -105.5q-203 -198 -293 -323zM844 524l12 12 q64 62 97.5 97t64.5 79t31 72q0 71 -48 119t-105 48q-74 0 -132 -82l-118 -171l-114 174q-51 79 -123 79q-60 0 -109.5 -49t-49.5 -118q0 -27 30.5 -70t61.5 -75.5t95 -94.5l22 -22q93 -90 190 -201q82 92 195 203z" />
<glyph unicode="&#xe144;" d="M476 406l19 -17l105 105l-212 212l389 389l247 -247l-95 -96l18 -18q46 -46 77 -99l29 29q35 35 62.5 88t27.5 96q0 93 -66 159l-141 141q-66 66 -159 66q-95 0 -159 -66l-283 -283q-66 -64 -66 -159q0 -93 66 -159zM123 193l141 -141q66 -66 159 -66q95 0 159 66 l283 283q66 66 66 159t-66 159l-141 141q-12 12 -19 17l-105 -105l212 -212l-389 -389l-247 248l95 95l-18 18q-46 45 -75 101l-55 -55q-66 -66 -66 -159q0 -94 66 -160z" />
<glyph unicode="&#xe145;" d="M200 100v953q0 21 30 46t81 48t129 38t163 15t162 -15t127 -38t79 -48t29 -46v-953q0 -41 -29.5 -70.5t-70.5 -29.5h-600q-41 0 -70.5 29.5t-29.5 70.5zM900 1000h-600v-700h600v700zM600 46q43 0 73.5 30.5t30.5 73.5t-30.5 73.5t-73.5 30.5t-73.5 -30.5t-30.5 -73.5 t30.5 -73.5t73.5 -30.5z" />
<glyph unicode="&#xe148;" d="M700 1029v-307l64 -14q34 -7 64 -16.5t70 -31.5t67.5 -52t47.5 -80.5t20 -112.5q0 -139 -89 -224t-244 -96v-77h-100v78q-152 17 -237 104q-40 40 -52.5 93.5t-15.5 139.5h139q5 -77 48.5 -126.5t117.5 -64.5v335l-27 7q-46 14 -79 26.5t-72 36t-62.5 52t-40 72.5 t-16.5 99q0 92 44 159.5t109 101t144 40.5v78h100v-79q38 -4 72.5 -13.5t75.5 -31.5t71 -53.5t51.5 -84t24.5 -118.5h-159q-8 72 -35 109.5t-101 50.5zM600 755v274q-61 -8 -97.5 -37.5t-36.5 -102.5q0 -29 8 -51t16.5 -34t29.5 -22.5t31 -13.5t38 -10q7 -2 11 -3zM700 548 v-311q170 18 170 151q0 64 -44 99.5t-126 60.5z" />
<glyph unicode="&#xe149;" d="M866 300l50 -147q-41 -25 -80.5 -36.5t-59 -13t-61.5 -1.5q-23 0 -128 33t-155 29q-39 -4 -82 -17t-66 -25l-24 -11l-55 145l16.5 11t15.5 10t13.5 9.5t14.5 12t14.5 14t17.5 18.5q48 55 54 126.5t-30 142.5h-221v100h166q-24 49 -44 104q-10 26 -14.5 55.5t-3 72.5 t25 90t68.5 87q97 88 263 88q129 0 230 -89t101 -208h-153q0 52 -34 89.5t-74 51.5t-76 14q-37 0 -79 -14.5t-62 -35.5q-41 -44 -41 -101q0 -11 2.5 -24.5t5.5 -24t9.5 -26.5t10.5 -25t14 -27.5t14 -25.5t15.5 -27t13.5 -24h242v-100h-197q8 -50 -2.5 -115t-31.5 -94 q-41 -59 -99 -113q35 11 84 18t70 7q32 1 102 -16t104 -17q76 0 136 30z" />
<glyph unicode="&#xe150;" d="M300 0l298 300h-198v900h-200v-900h-198zM900 1200l298 -300h-198v-900h-200v900h-198z" />
<glyph unicode="&#xe151;" d="M400 300h198l-298 -300l-298 300h198v900h200v-900zM1000 1200v-500h-100v100h-100v-100h-100v500h300zM901 1100h-100v-200h100v200zM700 500h300v-200h-99v-100h-100v100h99v100h-200v100zM800 100h200v-100h-300v200h100v-100z" />
<glyph unicode="&#xe152;" d="M400 300h198l-298 -300l-298 300h198v900h200v-900zM1000 1200v-200h-99v-100h-100v100h99v100h-200v100h300zM800 800h200v-100h-300v200h100v-100zM700 500h300v-500h-100v100h-100v-100h-100v500zM801 200h100v200h-100v-200z" />
<glyph unicode="&#xe153;" d="M300 0l298 300h-198v900h-200v-900h-198zM900 1100h-100v100h200v-500h-100v400zM1100 500v-500h-100v100h-200v400h300zM1001 400h-100v-200h100v200z" />
<glyph unicode="&#xe154;" d="M300 0l298 300h-198v900h-200v-900h-198zM1100 1200v-500h-100v100h-200v400h300zM1001 1100h-100v-200h100v200zM900 400h-100v100h200v-500h-100v400z" />
<glyph unicode="&#xe155;" d="M300 0l298 300h-198v900h-200v-900h-198zM900 1000h-200v200h200v-200zM1000 700h-300v200h300v-200zM1100 400h-400v200h400v-200zM1200 100h-500v200h500v-200z" />
<glyph unicode="&#xe156;" d="M300 0l298 300h-198v900h-200v-900h-198zM1200 1000h-500v200h500v-200zM1100 700h-400v200h400v-200zM1000 400h-300v200h300v-200zM900 100h-200v200h200v-200z" />
<glyph unicode="&#xe157;" d="M400 1100h300q162 0 281 -118.5t119 -281.5v-300q0 -165 -118.5 -282.5t-281.5 -117.5h-300q-165 0 -282.5 117.5t-117.5 282.5v300q0 165 117.5 282.5t282.5 117.5zM800 900h-500q-41 0 -70.5 -29.5t-29.5 -70.5v-500q0 -41 29.5 -70.5t70.5 -29.5h500q41 0 70.5 29.5 t29.5 70.5v500q0 41 -29.5 70.5t-70.5 29.5z" />
<glyph unicode="&#xe158;" d="M700 0h-300q-163 0 -281.5 117.5t-118.5 282.5v300q0 163 119 281.5t281 118.5h300q165 0 282.5 -117.5t117.5 -282.5v-300q0 -165 -117.5 -282.5t-282.5 -117.5zM800 900h-500q-41 0 -70.5 -29.5t-29.5 -70.5v-500q0 -41 29.5 -70.5t70.5 -29.5h500q41 0 70.5 29.5 t29.5 70.5v500q0 41 -29.5 70.5t-70.5 29.5zM400 800v-500l333 250z" />
<glyph unicode="&#xe159;" d="M0 400v300q0 163 117.5 281.5t282.5 118.5h300q163 0 281.5 -119t118.5 -281v-300q0 -165 -117.5 -282.5t-282.5 -117.5h-300q-165 0 -282.5 117.5t-117.5 282.5zM900 300v500q0 41 -29.5 70.5t-70.5 29.5h-500q-41 0 -70.5 -29.5t-29.5 -70.5v-500q0 -41 29.5 -70.5 t70.5 -29.5h500q41 0 70.5 29.5t29.5 70.5zM800 700h-500l250 -333z" />
<glyph unicode="&#xe160;" d="M1100 700v-300q0 -162 -118.5 -281t-281.5 -119h-300q-165 0 -282.5 118.5t-117.5 281.5v300q0 165 117.5 282.5t282.5 117.5h300q165 0 282.5 -117.5t117.5 -282.5zM900 300v500q0 41 -29.5 70.5t-70.5 29.5h-500q-41 0 -70.5 -29.5t-29.5 -70.5v-500q0 -41 29.5 -70.5 t70.5 -29.5h500q41 0 70.5 29.5t29.5 70.5zM550 733l-250 -333h500z" />
<glyph unicode="&#xe161;" d="M500 1100h400q165 0 282.5 -117.5t117.5 -282.5v-300q0 -165 -117.5 -282.5t-282.5 -117.5h-400v200h500q41 0 70.5 29.5t29.5 70.5v500q0 41 -29.5 70.5t-70.5 29.5h-500v200zM700 550l-400 -350v200h-300v300h300v200z" />
<glyph unicode="&#xe162;" d="M403 2l9 -1q13 0 26 16l538 630q15 19 6 36q-8 18 -32 16h-300q1 4 78 219.5t79 227.5q2 17 -6 27l-8 8h-9q-16 0 -25 -15q-4 -5 -98.5 -111.5t-228 -257t-209.5 -238.5q-17 -19 -7 -40q10 -19 32 -19h302q-155 -438 -160 -458q-5 -21 4 -32z" />
<glyph unicode="&#xe163;" d="M800 200h-500q-41 0 -70.5 29.5t-29.5 70.5v500q0 41 29.5 70.5t70.5 29.5h500v185q-14 4 -114 7.5t-193 5.5l-93 2q-165 0 -282.5 -117.5t-117.5 -282.5v-300q0 -165 117.5 -282.5t282.5 -117.5h300q47 0 100 15v185zM900 200v200h-300v300h300v200l400 -350z" />
<glyph unicode="&#xe164;" d="M1200 700l-149 149l-342 -353l-213 213l353 342l-149 149h500v-500zM1022 571l-122 -123v-148q0 -41 -29.5 -70.5t-70.5 -29.5h-500q-41 0 -70.5 29.5t-29.5 70.5v500q0 41 29.5 70.5t70.5 29.5h156l118 122l-74 78h-100q-165 0 -282.5 -117.5t-117.5 -282.5v-300 q0 -165 117.5 -282.5t282.5 -117.5h300q163 0 281.5 117.5t118.5 282.5v98z" />
<glyph unicode="&#xe165;" d="M600 1196q162 0 299 -80t217 -217t80 -299t-80 -299t-217 -217t-299 -80t-299 80t-217 217t-80 299t80 299t217 217t299 80zM600 1014q-171 0 -292.5 -121.5t-121.5 -292.5t121.5 -292.5t292.5 -121.5t292.5 121.5t121.5 292.5t-121.5 292.5t-292.5 121.5zM600 794 q80 0 137 -57t57 -137t-57 -137t-137 -57t-137 57t-57 137t57 137t137 57z" />
<glyph unicode="&#xe166;" d="M700 800v400h-300v-400h-300l445 -500l450 500h-295zM25 300h1048q11 0 19 -7.5t8 -17.5v-275h-1100v275q0 11 7 18t18 7zM1000 200h-100v-50h100v50z" />
<glyph unicode="&#xe167;" d="M400 700v-300h300v300h295l-445 500l-450 -500h300zM25 300h1048q11 0 19 -7.5t8 -17.5v-275h-1100v275q0 11 7 18t18 7zM1000 200h-100v-50h100v50z" />
<glyph unicode="&#xe168;" d="M405 400l596 596l-154 155l-442 -442l-150 151l-155 -155zM25 300h1048q11 0 19 -7.5t8 -17.5v-275h-1100v275q0 11 7 18t18 7zM1000 200h-100v-50h100v50z" />
<glyph unicode="&#xe169;" d="M409 1103l-97 97l-212 -212l97 -98zM650 861l-149 149l-212 -212l149 -149l-238 -248h700v699zM25 300h1048q11 0 19 -7.5t8 -17.5v-275h-1100v275q0 11 7 18t18 7zM1000 200h-100v-50h100v50z" />
<glyph unicode="&#xe170;" d="M539 950l-149 -149l212 -212l149 148l248 -237v700h-699zM297 709l-97 -97l212 -212l98 97zM25 300h1048q11 0 19 -7.5t8 -17.5v-275h-1100v275q0 11 7 18t18 7zM1000 200h-100v-50h100v50z" />
<glyph unicode="&#xe171;" d="M1200 1199v-1079l-475 272l-310 -393v416h-392zM1166 1148l-672 -712v-226z" />
<glyph unicode="&#xe172;" d="M1100 1000v-850q0 -21 -15 -35.5t-35 -14.5h-150v400h-700v-400h-150q-21 0 -35.5 14.5t-14.5 35.5v1000q0 20 14.5 35t35.5 15h250v-300h500v300h100zM700 1200h-100v-200h100v200z" />
<glyph unicode="&#xe173;" d="M578 500h-378v-400h-150q-21 0 -35.5 14.5t-14.5 35.5v1000q0 20 14.5 35t35.5 15h250v-300h500v300h100l200 -200v-218l-276 -275l-120 120zM700 1200h-100v-200h100v200zM1300 538l-475 -476l-244 244l123 123l120 -120l353 352z" />
<glyph unicode="&#xe174;" d="M529 500h-329v-400h-150q-21 0 -35.5 14.5t-14.5 35.5v1000q0 20 14.5 35t35.5 15h250v-300h500v300h100l200 -200v-269l-103 -103l-170 170zM700 1200h-100v-200h100v200zM1167 6l-170 170l-170 -170l-127 127l170 170l-170 170l127 127l170 -170l170 170l127 -128 l-170 -169l170 -170z" />
<glyph unicode="&#xe175;" d="M700 500h-500v-400h-150q-21 0 -35.5 14.5t-14.5 35.5v1000q0 20 14.5 35t35.5 15h250v-300h500v300h100l200 -200v-300h-400v-200zM700 1000h-100v200h100v-200zM1000 600h-200v-300h-200l300 -300l300 300h-200v300z" />
<glyph unicode="&#xe176;" d="M602 500h-402v-400h-150q-21 0 -35.5 14.5t-14.5 35.5v1000q0 20 14.5 35t35.5 15h250v-300h500v300h100l200 -200v-402l-200 200zM700 1000h-100v200h100v-200zM1000 300h200l-300 300l-300 -300h200v-300h200v300z" />
<glyph unicode="&#xe177;" d="M1200 900v150q0 21 -14.5 35.5t-35.5 14.5h-1100q-21 0 -35.5 -14.5t-14.5 -35.5v-150h1200zM0 800v-550q0 -21 14.5 -35.5t35.5 -14.5h1100q21 0 35.5 14.5t14.5 35.5v550h-1200zM100 500h400v-200h-400v200z" />
<glyph unicode="&#xe178;" d="M500 1000h400v198l300 -298l-300 -298v198h-400v200zM100 800v200h100v-200h-100zM400 800h-100v200h100v-200zM700 300h-400v-198l-300 298l300 298v-198h400v-200zM800 500h100v-200h-100v200zM1000 500v-200h100v200h-100z" />
<glyph unicode="&#xe179;" d="M1200 50v1106q0 31 -18 40.5t-44 -7.5l-276 -117q-25 -16 -43.5 -50.5t-18.5 -65.5v-359q0 -29 10.5 -55.5t25 -43t29 -28.5t25.5 -18l10 -5v-397q0 -21 14.5 -35.5t35.5 -14.5h200q21 0 35.5 14.5t14.5 35.5zM550 1200l50 -100v-400l-100 -203v-447q0 -21 -14.5 -35.5 t-35.5 -14.5h-200q-21 0 -35.5 14.5t-14.5 35.5v447l-100 203v400l50 100l50 -100v-300h100v300l50 100l50 -100v-300h100v300z" />
<glyph unicode="&#xe180;" d="M1100 106v888q0 22 25 34.5t50 13.5l25 2v56h-400v-56q75 0 87.5 -6t12.5 -44v-394h-500v394q0 38 12.5 44t87.5 6v56h-400v-56q4 0 11 -0.5t24 -3t30 -7t24 -15t11 -24.5v-888q0 -22 -25 -34.5t-50 -13.5l-25 -2v-56h400v56q-75 0 -87.5 6t-12.5 44v394h500v-394 q0 -38 -12.5 -44t-87.5 -6v-56h400v56q-4 0 -11 0.5t-24 3t-30 7t-24 15t-11 24.5z" />
<glyph unicode="&#xe181;" d="M675 1000l-100 100h-375l-100 -100h400l200 -200v-98l295 98h105v200h-425zM500 300v500q0 41 -29.5 70.5t-70.5 29.5h-300q-41 0 -70.5 -29.5t-29.5 -70.5v-500q0 -41 29.5 -70.5t70.5 -29.5h300q41 0 70.5 29.5t29.5 70.5zM100 800h300v-200h-300v200zM700 565l400 133 v-163l-400 -133v163zM100 500h300v-200h-300v200zM805 300l295 98v-298h-425l-100 -100h-375l-100 100h400l200 200h105z" />
<glyph unicode="&#xe182;" d="M179 1169l-162 -162q-1 -11 -0.5 -32.5t16 -90t46.5 -140t104 -177.5t175 -208q103 -103 207.5 -176t180 -103.5t137 -47t92.5 -16.5l31 1l163 162q16 17 13 40.5t-22 37.5l-192 136q-19 14 -45 12t-42 -19l-119 -118q-143 103 -267 227q-126 126 -227 268l118 118 q17 17 20 41.5t-11 44.5l-139 194q-14 19 -36.5 22t-40.5 -14z" />
<glyph unicode="&#xe183;" d="M1200 712v200q-6 8 -19 20.5t-63 45t-112 57t-171 45t-235 20.5q-92 0 -175 -10.5t-141.5 -27t-108.5 -36.5t-81.5 -40t-53.5 -36.5t-31 -27.5l-9 -10v-200q0 -21 14.5 -33.5t34.5 -8.5l202 33q20 4 34.5 21t14.5 38v146q141 24 300 24t300 -24v-146q0 -21 14.5 -38 t34.5 -21l202 -33q20 -4 34.5 8.5t14.5 33.5zM800 650l365 -303q14 -14 24.5 -39.5t10.5 -45.5v-212q0 -21 -15 -35.5t-35 -14.5h-1100q-21 0 -35.5 14.5t-14.5 35.5v212q0 20 10.5 45.5t24.5 39.5l365 303v50q0 4 1 10.5t12 22.5t30 28.5t60 23t97 10.5t97 -10t60 -23.5 t30 -27.5t12 -24l1 -10v-50z" />
<glyph unicode="&#xe184;" d="M175 200h950l-125 150v250l100 100v400h-100v-200h-100v200h-200v-200h-100v200h-200v-200h-100v200h-100v-400l100 -100v-250zM1200 100v-100h-1100v100h1100z" />
<glyph unicode="&#xe185;" d="M600 1100h100q41 0 70.5 -29.5t29.5 -70.5v-1000h-300v1000q0 41 29.5 70.5t70.5 29.5zM1000 800h100q41 0 70.5 -29.5t29.5 -70.5v-700h-300v700q0 41 29.5 70.5t70.5 29.5zM400 0v400q0 41 -29.5 70.5t-70.5 29.5h-100q-41 0 -70.5 -29.5t-29.5 -70.5v-400h300z" />
<glyph unicode="&#xe186;" d="M1200 800v-500q0 -124 -88 -212t-212 -88h-700q-124 0 -212 88t-88 212v500q0 124 88 212t212 88h700q124 0 212 -88t88 -212zM1000 900h-900v-700h900v700zM200 800v-300h200v-100h-200v-100h300v300h-200v100h200v100h-300zM800 800h-200v-500h200v100h100v300h-100 v100zM800 700v-300h-100v300h100z" />
<glyph unicode="&#xe187;" d="M1200 800v-500q0 -124 -88 -212t-212 -88h-700q-124 0 -212 88t-88 212v500q0 124 88 212t212 88h700q124 0 212 -88t88 -212zM1000 900h-900v-700h900v700zM400 600h-100v200h-100v-500h100v200h100v-200h100v500h-100v-200zM800 800h-200v-500h200v100h100v300h-100 v100zM800 700v-300h-100v300h100z" />
<glyph unicode="&#xe188;" d="M1200 800v-500q0 -124 -88 -212t-212 -88h-700q-124 0 -212 88t-88 212v500q0 124 88 212t212 88h700q124 0 212 -88t88 -212zM1000 900h-900v-700h900v700zM200 800v-500h300v100h-200v300h200v100h-300zM600 800v-500h300v100h-200v300h200v100h-300z" />
<glyph unicode="&#xe189;" d="M1200 800v-500q0 -124 -88 -212t-212 -88h-700q-124 0 -212 88t-88 212v500q0 124 88 212t212 88h700q124 0 212 -88t88 -212zM1000 900h-900v-700h900v700zM500 700l-300 -150l300 -150v300zM600 400l300 150l-300 150v-300z" />
<glyph unicode="&#xe190;" d="M1200 800v-500q0 -124 -88 -212t-212 -88h-700q-124 0 -212 88t-88 212v500q0 124 88 212t212 88h700q124 0 212 -88t88 -212zM1000 900h-900v-700h900v700zM900 800v-500h-700v500h700zM300 400h130q41 0 68 42t27 107t-28.5 108t-66.5 43h-130v-300zM800 700h-130 q-38 0 -66.5 -43t-28.5 -108t27 -107t68 -42h130v300z" />
<glyph unicode="&#xe191;" d="M1200 800v-500q0 -124 -88 -212t-212 -88h-700q-124 0 -212 88t-88 212v500q0 124 88 212t212 88h700q124 0 212 -88t88 -212zM1000 900h-900v-700h900v700zM200 800v-300h200v-100h-200v-100h300v300h-200v100h200v100h-300zM800 300h100v500h-200v-100h100v-400z M601 300h100v100h-100v-100z" />
<glyph unicode="&#xe192;" d="M1200 800v-500q0 -124 -88 -212t-212 -88h-700q-124 0 -212 88t-88 212v500q0 124 88 212t212 88h700q124 0 212 -88t88 -212zM1000 900h-900v-700h900v700zM300 700v100h-100v-500h300v400h-200zM800 300h100v500h-200v-100h100v-400zM401 400h-100v200h100v-200z M601 300h100v100h-100v-100z" />
<glyph unicode="&#xe193;" d="M200 1100h700q124 0 212 -88t88 -212v-500q0 -124 -88 -212t-212 -88h-700q-124 0 -212 88t-88 212v500q0 124 88 212t212 88zM1000 900h-900v-700h900v700zM400 700h-200v100h300v-300h-99v-100h-100v100h99v200zM800 700h-100v100h200v-500h-100v400zM201 400h100v-100 h-100v100zM701 300h-100v100h100v-100z" />
<glyph unicode="&#xe194;" d="M600 1196q162 0 299 -80t217 -217t80 -299t-80 -299t-217 -217t-299 -80t-299 80t-217 217t-80 299t80 299t217 217t299 80zM600 1014q-171 0 -292.5 -121.5t-121.5 -292.5t121.5 -292.5t292.5 -121.5t292.5 121.5t121.5 292.5t-121.5 292.5t-292.5 121.5zM800 700h-300 v-200h300v-100h-300l-100 100v200l100 100h300v-100z" />
<glyph unicode="&#xe195;" d="M596 1196q162 0 299 -80t217 -217t80 -299t-80 -299t-217 -217t-299 -80t-299 80t-217 217t-80 299t80 299t217 217t299 80zM596 1014q-171 0 -292.5 -121.5t-121.5 -292.5t121.5 -292.5t292.5 -121.5t292.5 121.5t121.5 292.5t-121.5 292.5t-292.5 121.5zM800 700v-100 h-100v100h-200v-100h200v-100h-200v-100h-100v400h300zM800 400h-100v100h100v-100z" />
<glyph unicode="&#xe197;" d="M800 300h128q120 0 205 86t85 208q0 120 -85 206.5t-205 86.5q-46 0 -90 -14q-44 97 -134.5 156.5t-200.5 59.5q-152 0 -260 -107.5t-108 -260.5q0 -25 2 -37q-66 -14 -108.5 -67.5t-42.5 -122.5q0 -80 56.5 -137t135.5 -57h222v300h400v-300zM700 200h200l-300 -300 l-300 300h200v300h200v-300z" />
<glyph unicode="&#xe198;" d="M600 714l403 -403q94 26 154.5 104t60.5 178q0 121 -85 207.5t-205 86.5q-46 0 -90 -14q-44 97 -134.5 156.5t-200.5 59.5q-152 0 -260 -107.5t-108 -260.5q0 -25 2 -37q-66 -14 -108.5 -67.5t-42.5 -122.5q0 -80 56.5 -137t135.5 -57h8zM700 -100h-200v300h-200l300 300 l300 -300h-200v-300z" />
<glyph unicode="&#xe199;" d="M700 200h400l-270 300h170l-270 300h170l-300 333l-300 -333h170l-270 -300h170l-270 -300h400v-155l-75 -45h350l-75 45v155z" />
<glyph unicode="&#xe200;" d="M700 45v306q46 -30 100 -30q74 0 126.5 52.5t52.5 126.5q0 24 -9 55q50 32 79.5 83t29.5 112q0 90 -61.5 155.5t-150.5 71.5q-26 89 -99.5 145.5t-167.5 56.5q-116 0 -197.5 -81.5t-81.5 -197.5q0 -4 1 -12t1 -11q-14 2 -23 2q-74 0 -126.5 -52.5t-52.5 -126.5 q0 -53 28.5 -97t75.5 -65q-4 -16 -4 -38q0 -74 52.5 -126.5t126.5 -52.5q56 0 100 30v-306l-75 -45h350z" />
<glyph unicode="&#x1f4bc;" d="M800 1000h300q41 0 70.5 -29.5t29.5 -70.5v-400h-500v100h-200v-100h-500v400q0 41 29.5 70.5t70.5 29.5h300v100q0 41 29.5 70.5t70.5 29.5h200q41 0 70.5 -29.5t29.5 -70.5v-100zM500 1000h200v100h-200v-100zM1200 400v-200q0 -41 -29.5 -70.5t-70.5 -29.5h-1000 q-41 0 -70.5 29.5t-29.5 70.5v200h1200z" />
<glyph unicode="&#x1f4c5;" d="M1100 900v150q0 21 -14.5 35.5t-35.5 14.5h-150v100h-100v-100h-500v100h-100v-100h-150q-21 0 -35.5 -14.5t-14.5 -35.5v-150h1100zM0 800v-750q0 -20 14.5 -35t35.5 -15h1000q21 0 35.5 15t14.5 35v750h-1100zM100 600h100v-100h-100v100zM300 600h100v-100h-100v100z M500 600h100v-100h-100v100zM700 600h100v-100h-100v100zM900 600h100v-100h-100v100zM100 400h100v-100h-100v100zM300 400h100v-100h-100v100zM500 400h100v-100h-100v100zM700 400h100v-100h-100v100zM900 400h100v-100h-100v100zM100 200h100v-100h-100v100zM300 200 h100v-100h-100v100zM500 200h100v-100h-100v100zM700 200h100v-100h-100v100zM900 200h100v-100h-100v100z" />
<glyph unicode="&#x1f4cc;" d="M902 1185l283 -282q15 -15 15 -36t-15 -35q-14 -15 -35 -15t-35 15l-36 35l-279 -267v-300l-212 210l-208 -207l-380 -303l303 380l207 208l-210 212h300l267 279l-35 36q-15 14 -15 35t15 35q14 15 35 15t35 -15z" />
<glyph unicode="&#x1f4ce;" d="M518 119l69 -60l517 511q67 67 95 157t11 183q-16 87 -67 154t-130 103q-69 33 -152 33q-107 0 -197 -55q-40 -24 -111 -95l-512 -512q-68 -68 -81 -163t35 -173q35 -57 94 -89t129 -32q63 0 119 28q33 16 65 40.5t52.5 45.5t59.5 64q40 44 57 61l394 394q35 35 47 84 t-3 96q-27 87 -117 104q-20 2 -29 2q-46 0 -79.5 -17t-67.5 -51l-388 -396l-7 -7l69 -67l377 373q20 22 39 38q23 23 50 23q38 0 53 -36q16 -39 -20 -75l-547 -547q-52 -52 -125 -52q-55 0 -100 33t-54 96q-5 35 2.5 66t31.5 63t42 50t56 54q24 21 44 41l348 348 q52 52 82.5 79.5t84 54t107.5 26.5q25 0 48 -4q95 -17 154 -94.5t51 -175.5q-7 -101 -98 -192l-252 -249l-253 -256z" />
<glyph unicode="&#x1f4f7;" d="M1200 200v600q0 41 -29.5 70.5t-70.5 29.5h-150q-4 8 -11.5 21.5t-33 48t-53 61t-69 48t-83.5 21.5h-200q-41 0 -82 -20.5t-70 -50t-52 -59t-34 -50.5l-12 -20h-150q-41 0 -70.5 -29.5t-29.5 -70.5v-600q0 -41 29.5 -70.5t70.5 -29.5h1000q41 0 70.5 29.5t29.5 70.5z M1000 700h-100v100h100v-100zM844 500q0 -100 -72 -172t-172 -72t-172 72t-72 172t72 172t172 72t172 -72t72 -172zM706 500q0 44 -31 75t-75 31t-75 -31t-31 -75t31 -75t75 -31t75 31t31 75z" />
<glyph unicode="&#x1f512;" d="M900 800h100q41 0 70.5 -29.5t29.5 -70.5v-600q0 -41 -29.5 -70.5t-70.5 -29.5h-900q-41 0 -70.5 29.5t-29.5 70.5v600q0 41 29.5 70.5t70.5 29.5h100v200q0 82 59 141t141 59h300q82 0 141 -59t59 -141v-200zM400 800h300v150q0 21 -14.5 35.5t-35.5 14.5h-200 q-21 0 -35.5 -14.5t-14.5 -35.5v-150z" />
<glyph unicode="&#x1f514;" d="M1062 400h17q20 0 33.5 -14.5t13.5 -35.5q0 -20 -13 -40t-31 -27q-22 -9 -63 -23t-167.5 -37t-251.5 -23t-245.5 20.5t-178.5 41.5l-58 20q-18 7 -31 27.5t-13 40.5q0 21 13.5 35.5t33.5 14.5h17l118 173l63 327q15 77 76 140t144 83l-18 32q-6 19 3 32t29 13h94 q20 0 29 -10.5t3 -29.5l-18 -37q83 -19 144 -82.5t76 -140.5l63 -327zM600 104q-54 0 -103 6q12 -49 40 -79.5t63 -30.5t63 30.5t39 79.5q-48 -6 -102 -6z" />
<glyph unicode="&#x1f516;" d="M200 0l450 444l450 -443v1150q0 20 -14.5 35t-35.5 15h-800q-21 0 -35.5 -15t-14.5 -35v-1151z" />
<glyph unicode="&#x1f525;" d="M400 755q2 -12 8 -41.5t8 -43t6 -39.5t3.5 -39.5t-1 -33.5t-6 -31.5t-13.5 -24t-21 -20.5t-31 -12q-38 -10 -67 13t-40.5 61.5t-15 81.5t10.5 75q-52 -46 -83.5 -101t-39 -107t-7.5 -85t5 -63q9 -56 44 -119.5t105 -108.5q31 -21 64 -16t62 23.5t57 49.5t48 61.5t35 60.5 q32 66 39 184.5t-13 157.5q79 -80 122 -164t26 -184q-5 -33 -20.5 -69.5t-37.5 -80.5q-10 -19 -14.5 -29t-12 -26t-9 -23.5t-3 -19t2.5 -15.5t11 -9.5t19.5 -5t30.5 2.5t42 8q57 20 91 34t87.5 44.5t87 64t65.5 88.5t47 122q38 172 -44.5 341.5t-246.5 278.5q22 -44 43 -129 q39 -159 -32 -154q-15 2 -33 9q-79 33 -120.5 100t-44 175.5t48.5 257.5q-13 -8 -34 -23.5t-72.5 -66.5t-88.5 -105.5t-60 -138t-8 -166.5z" />
<glyph unicode="&#x1f527;" d="M948 778l251 126q13 -175 -151 -267q-123 -70 -253 -23l-596 -596q-15 -16 -36.5 -16t-36.5 16l-111 110q-15 15 -15 36.5t15 37.5l600 599q-33 101 6 201.5t135 154.5q164 92 306 -9l-259 -138z" />
</font>
</defs></svg>      €  pFFTMh+¶   ü   GDEF       OS/2ilד  8   `cmap©/Vנ  ˜  .cvt  (ר  ָ   gaspÿÿ   ּ   glyfעֶ  װ  [Xhead 8=נ  b,   6hhea
®x  bd   $hmtx p  bˆ  זlocaֹמא@  ep  ¸maxp. ֻ  g(    nameװ–™ü  gH  |postײcQw  jִ  ywebfK)Qׁ  s@          ּ=¢ֿ    ֽק–    ֽצû§               Ú          ׀   Z׀  ₪ 2¸                          UKWN @  ÿÿxÿפ ”                          ,   
       (     ,  
   p   X @     +   
 / _ ¬"&'	'אא	אא)א2א9אCאEאIאYא`אiאyא‰א—בבבב"ב)ב5ב8בAבEבIבYבiבyב‰ב•ב™ג ÿÿ     *     / _ ¬"&'	'א אאא א0א4א@אEאGאPא`אbאpא€אבבבב ב$ב0ב7ב@בCבHבPב`בpב€בב—ג ÿÿÿדÿÚÿfאßדß´ßhÞÚÙÙ	      ÿ‏‎קסנךהÞutmgf`_XWUOIC=76׀                                                                                                     5              *   +                     
      /   /      _   _      ¬   ¬     "  "     &  &     '	  '	     '  '     א   א     א  א	     א  א   "  א   א)   ,  א0  א2   6  א4  א9   9  א@  אC   ?  אE  אE   C  אG  אI   D  אP  אY   G  א`  א`   Q  אb  אi   R  אp  אy   Z  א€  א‰   d  א  א—   n  ב  ב   v  ב  ב   y  ב  ב   }  ב   ב"   ‡  ב$  ב)     ב0  ב5     ב7  ב8   –  ב@  בA   ˜  בC  בE     בH  בI     בP  בY     ב`  בi   ©  בp  בy   ³  ב€  ב‰   ½  ב  ב•   ַ  ב—  ב™   ֽ  ג   ג    ׀ פ¼ פ¼   ׁ פֵ פֵ   ׂ פּ פּ   ׃ פ־ פ־   װ פק פק   ױ ץ ץ   ײ ץ ץ   ׳ ץ ץ   ״ ץ% ץ%   Ù ץ' ץ'   Ú                                                                                                                                                                                                                                                             (ר   ÿÿ   (  h    .± /<² ם2±Ü<² ם2 ± /<² ם2²ü<² ם23!%3#(@‏טננ üא(׀  d dLL   !'#'7!5!'737!L‏‎··ָ··‏‎··ָ··פ··‏‎··ָ··‏‎··        LL   !!!!!!L‏p‏װ‏p,‏p,‏p  d ® 7  !32>53#"'.'#7347#7367632#4.#"!!! ‏װ	09C3JL3®ak§ֽw$BÙdqÚd‡%Ku´עp<µ3LJ9D?{d‏װ–פJtB+0W5¬ju.«xd/5d§Z½gj7X0,Z>d.6  ָL¼   !!Lü|„¼‏װ ÿע,ֲA   !2654&#".#"²מx××x.,,µn˜״BUq,¬zx­aw׳™kEPr       d°L      !%!!°ûPX‏װ‏װ°‏װdûPָL‎¥g‏װX‎¨,d‏pָ  ÿףÿף½½ 	    764/&"	'%'Mc™$^‏fײ‎ָMßy\'™aünfײ‎‏`pß                1      °° 	  %!!5!!¼,üא,‏°‏ddd&&‎Ú    L¯    &7>54&&7>5ט@JOW‰OFS
‎
@JOW$ˆOAX¦‎קr67)Q7q
ֱ
‎Orn)`*^  ÿלִ™    	"'#"      6& ‘,m‏װwָ‏הüֲׁֱֱ‏מ°‏װm,N‏הָ‏נֱֲֱ  d X¯D   >.54>‰0{xuX6Cy„¨>>§„yC8ZwwyµEH-Sv@9y€²UU²€y9@vS-I   ÿ¸ G•° 
   	!3!7‘‏€‏‚’‏|ß’’״ü
‏?‏ם¿‏p‎' ÿ¸ G•° 
    	!3!'7#'#77‘‏€‏‚’‏|ß’’״‏VJֱכMNןִIְ‏€
‏?‏ם¿‏p‏+ו׃׃ג‏o     °°   %!55"&=462#°ûP%?°ר°?%d•3ָ|°°|ָ3•d     °L         # ' + / 3  !#3%!!#3#3%#3#3%#3#!!#3%#3#3%#3°ûPָdd¼‎¨Xָddü|dd„ddü|dd„ddָ‎¨X‎Ddd„ddü|dd„ddLû´Lddd‏pddddddddd‏p,ddddddd     LL   / ?  #!"&5463!2#!"&5463!2#!"&5463!2#!"&5463!2פ‏pX‏p‎¨‏pX‏p‏p‏p‎“‏p‏p   	    LL   / ? O _ o    +"&=46;2+"&=46;2+"&=46;2+"&=46;2+"&=46;2+"&=46;2+"&=46;2+"&=46;2+"&=46;2,ָָָָָָüאָָָָָָüאָָָָָָָָָָָָ‏[ָָָָָָ‏[ָָָָָָ      °L   / ? O _  +"&=46;2#!"&=463!254&+";26%#!"&=463!2+"&=46;2#!"&=463!2,ָָ„‎D¼ü|ָָ„‎D¼ü|ָָ„‎D¼ָָָָ‎“ָָÝָָ‏[ָָָָ      "ע*   %''ְ2װ‎¡־װ"4װ‎¡ֿװ     j jFF   %	'	7		r‏ז‏זװ‏זװװ‏זj‏זװװ‏זװ‏ז‏ז   ÿלִ™   '  	"'#"     6& %3##5#5353‘,m‏װwָ‏ה‎“ֱֱ‏מֲ¨ddָddָ°‏װm,N‏הָ¼ֱֱֲ‏נףָddָd   ÿלִ     	"'#"      6& !5‘,m‏װxָ‏הüֲׁֱֱ‏מF°‏ױm+M‏הָ‏נֲֲֲßָָ   ™°  +  4&+";2675".547 654&¼ddd§ׂ[›ױלױ›[ׂ§g|תbת|מ‏pצ¦>‏״·vױ›[[›ױv·(>¦7ָx±תת±xָ    d °±      %#3#3#3#3°ָָ‏װָָ‏װָָ‏װָָ°ûP üאפָ‏װ     –– G Q  %32?6?67'76?654/&/7&''&/&#"'72"&54ט&("/&./†80P˜˜P,<†-0&("/&2,…;.P——P-<…-1²~~²~·——Q,=†,1&("-&3*†:/Q˜˜Q/:†/.&0!)&1,†;.Qv~XY~~YX   dÿÿ°   ' + / 3 7  !2#!"&=463!5463!25!!#!"&5;#3#3#3#„
ûז
;),);d‏װ‏װ„;)‎D);dddָddָddָddL
22
d);;)dddָüא)<<)¼‎D¼‎D¼‎D¼     Ý 
  #!!!#ָ‏װ‏װ‏װָY‎¨‏pX„     d  ט°    !#!"&5463!!Xü®Û‏װ¼‎]~‏p,   ¬¬        $  63!3¶D  ‏מ‏¼‏מ  _‏×ףףVף‏bָ‏װd¬ ‏מ‏¼‏מ  Dף‏×ףףV«d ÿ  °    #!333!#ׁע(‎ו¯ׁ¢׀¯‎ז2×‏p°‏װ,ûPפ,‏װ      L°    !!3!3#3Lû´ך‏Þָ,ָ‏Þz¯¯‏p,פ‏‏װdd     ¯¯      2".4>  6333ÞפÞ __ ÞפÞ __ ‏¬עעTע‏ָ–תת–ָ¯_ ÞפÞ __ ÞפÞ \ע‏¬עעT×‏װ,,    ¬¬        $  6###¶D  ‏מ‏¼‏מ  _‏×ףףVף₪–ָ–ת¬ ‏מ‏¼‏מ  Dף‏×ףףV«‏װ,,      °°    !3#!"&5!3!73ָ ַû‚‡‎¢aָ2,2ָפ¼‎D‏%ֿ‏ָָ      ¬¬        $  654¶D  ‏מ‏¼‏מ  _‏×ףףVףÙ‏׳¬ ‏מ‏¼‏מ  Dף«¬עע¬«­ֹ‘   ™°   # &632!&#"2>™–ת‏תת±ˆn’‘ֶvױ›[[›ױלױ›[X±תתbתQ’‘z[›ױלױ›[[›ױ    ™°  !  7&#"#4>32732653#"'¼“p‡±ת–[›ױvֶ‘‎¨“p‡±ת–[›ױvֶ‘ “Pת±vױ›[z‘‏p‏p“Pת±vױ›[z‘   
 d  °°         # '  !!!#53!5!#53!5!#53!5!#53)!dLdü|„‎DddX‏פ‎¨ddX‏פ‎¨ddX‏פ‎¨ddX‏פ°ûP°û´„ָddd‏װddd‏װddd‏װdd  d  LL    3#3.>>ָdd„({„‡tZ<‡x|rjdL‏פQE
((
EQ‏<0!O       °— ! 1 A  4.";2654> ;26%+"&546;2+"&546;2°c£ÞטÞ£c2הה2üא  X  ,tÞ£cc£Þt‏װ,ׁrrׁ‏װ״‏4ּ‏4ּ      ָXט    !''7'77,,‏װGGGG ‏pָ ָָGGGG       ָpט    !%7'654,,‏װEojCV ‏pָ ָ95‡¯©…6n’     ÷bק     %7654/%%!%7'654־S{w‎¯,‏װ‏װ¾EojCV÷²דז²@—ֱ½–%ָüאָ95‡¯×…7n‘       °° 	    ! % - ; ? C G K O  !535#!3%!33!5#5!)!%35#!3###!##5#535#5!3%!#53!3#3#%!5ddd‏dL‎¨dָ,ָü|,‏װ¼,ü|dd¼dd‎¨dָפָXָddָ,ָ‎¨‏װָdd dd‏ddX‏װ¼dd,‎¨dפָ‏װ‏װָd‏װ,‏װddd‏װd‏פ‏װdddָd‏װdָ‏װ,ָddddddd  	    °°         #  #;#3#3#3!#!53#73#%#5dddָddָָָdd,ָ‎¨‏װddָddָ°üטüטüטüטû´dd	[[[[[[       °°    	463!64'&"2°‏‎E
Ú´SSפ‏¼Û
‏ֲTT    Ý°     	463!	'	364'&"2±‏‎EÚט‏2ֲ‎Dd‏TTפ‏¼Û‎D‏2ֲ¼‏ֲTT  d  °° 
  !!!7°d‎dמü|¯°üdטdü¯        °°   '  .#!"!%#5!#3!7#!"&?>3!2³^
‏>
^(P;ָüאָָdXdw&
‎¨
&
פ
=Z‏¦|_‎Dָָ¼ָָ‎˜

˜
     5  °¯  "  !5&'./#!5".?!#°"(‏]‏q*m)>$\‡R+5‏‘².tB*.זü0BB6,ךÞ-WB	ֹ‏ֳ    d  ֳ¯   ( 1  !2#!5>54.'32654&32654&+d׳x÷!"E4+v¦O‏);	$,‹Ll¨›¡Y€}^¯°7]7(3AvFT‘MY3(;2‏…{MRaü‘aTZ    ָ  o°   !5>76&'.'5m!:"€
0G‏Ms­
(G	°9#'%üַ4<99C/Q8$9  ÿµ  °  %  3#4.+!57#"#33'3#7~–2.!"ָd‏pdָ"!/1–‏¢K}}KK}}°‏װ'	ü®2dd2R	',ָüא§§ §§    !ÿ¶±  %  3#4.+!57#"#3!55!'7¶–2/!"ָd‏pdָ"!.2–2 §§üא§§±‏װ'	‎v2dd2	',û´K}}KK}}       °L   / ?  54&#!"3!2654&#!"3!2654&#!"3!2654&#!"3!26¼‎¨Xüטָüא ,û´L¶dd‏טdd‏טdd‏טdd      °L   / ?  54&#!"3!2654&#!"3!2654&#!"3!2654&#!"3!26ט‎D¼ָû´Lָ‎D¼ָû´L¶dd‏טdd‏טdd‏טdd        °L   / ?  5463!2#!"&5463!2#!"&5463!2#!"&5463!2#!"&פX‎¨‏pטüָ üא‏װLû´¶dd‏טdd‏טdd‏טdd      °L   / ?  5463!2#!"&5463!2#!"&5463!2#!"&5463!2#!"&Lû´Lû´Lû´Lû´¶dd‏טdd‏טdd‏טdd     °L   / ? O _ o   546;2+"&%5463!2#!"&546;2+"&%5463!2#!"&546;2+"&%5463!2#!"&546;2+"&%5463!2#!"&dd, üא‏װdd, üא‏װdd, üא‏װdd, üא¶dddd‏טdddd‏טdddd‏טdddd   ÿ›  °L   # * : J  #354&+";2654&#!"3!265#53554&#!"3!2654&#!"3!26dd,dd‏פüֹֹ¦z‏װ,,‎¨XLû´¶dd‏טddֽKdK}ָdd‏טdd      L   # * : J  54&+";26%3#54&#!"3!26535#554&#!"3!2654&#!"3!26ֹddXddָ‏פפָָ§‎כ‏װ,,‎¨X¶dd×û´ddֽKdK}ָdd‏טdd     ָ°ט    #!"&5463!2	„,‎,,מ,,‏װ,‎v,,,,‎p,,        °L     #!"&5463!2!7' "&462°û¨Xdüק*J%ל‎NpNNp üפJ‎¶ƒ>‏א2pNNpN   ”ÿף½    2.'&54>264&"X{׀yII»99
"c]s+?yׁyk——ײ–—½~ױצ•rצBB	"ko K‹{|׳‎E—ײ–—jk—   ¯¯     2".4>"ÞפÞ __ ÞפÞ __ X°ûû¯_ ÞפÞ __ ÞפÞ ü]Vת‏ת    u ß  %  .54>.'&6?*IOWM?%N~Orֳ€DmssE.
		\7[[Gvwsu†EY™d;^·w^¸¡ֵüyJ(I43n–QRl      ֵh  ! &  7/#!"&5463!"3!26=7'Tq\qiכ¥‏װ¥ככ¥n÷‏ֹ);;)פ);‏0¡r‏k†qUq‏zב¥ככ¥,¥כ÷;)‏);;)}T2q‏k       •L  .  #!"&5463!#"3!265'	">Lכ¥‏װ¥ככ¥…U‘);;)פ);Wh‏™HCVC9gg_ֵ5¥ככ¥,¥כP X;)‏);;)ƒD>״3CmC&4	        §L  #  #!"&5463!2!"3!26=''Lכ¥‏װ¥ככ¥,<C²‏£);;)פ);‏ם6ˆ‏R“ˆ9©¥ככ¥,¥כ±;)‏);;)E7‰‏Q“ˆ    °±   5#3	35#	35#	#35„ֳָ‏Ù‏װָָ‏װ,ָָ,'ֳָ,/ֶָ‏װ,ֶָ,(ֶָ,‏װֶָ‏״    ָ  „L   !+"&546;2„‏ddפט‏Jט‏Kח     °L   !+"&546;2°‏‏ddפפט‏ט‏Jט‏Kח‏ח  ˆ  °L   !	°‏‎ּ4פט‏&&‏ח   ָ  LL   	Lü|&&û´   ָ d„ט    %4&+";26%4&+";26פָָָָ– üא üא  ָ dLט   %4&#!"3!26Lüא – üא     (L   !פ‏פ4ט‏L‏ח‎Ú        °L   32+"&546dd‏‏פפLü¶‏ט‏L‏ח‏µ  ,  טL   32+"&546Rdd‏פLü¶‏L‏µ   d ָ°(    	!#!"&=463!2‎ÚLüט(‎ּ–dd   ¹ ש©   %7	'	נ‏aנ‎°נaaנ‎®  ÿׂRt   '	7סa‏ס<.נaaנ‎ֵ    ­­       $#33535#5¶D  ‏ם‏¼‏ם  Qָָָָָ­ ‏ם‏¼‏ם  D‰ָָָָָָ    ­­       $!5¶D  ‏ם‏¼‏ם  ‰X­ ‏ם‏¼‏ם  D‏¯ָָ   ­­       $77'7''¶D  ‏ם‏¼‏ם  Tױױױ­ ‏ם‏¼‏ם  D‏״ױױװ     ­­       $'	'¶D  ‏ם‏¼‏ם  f®›¯­ ‏ם‏¼‏ם  D‏bf®‏כ›®      ­­  6 :     $3>54.#"32264>:3235¶D  ‏ם‏¼‏ם  Qָ-"#1D1i†	'ָ­ ‏ם‏¼‏ם  D‎ח
)2X23L(p
=dd    ­­        $353#!5#¶D  ‏ם‏¼‏ם  Qָ‏װddd­ ‏ם‏¼‏ם  D‰ddָdָdd,        °°  1  ##5.'#53>75367#53.'#53#5°ֻ·Yָˆֲֲ*EkIָ6vk‏׳•4ָֹfIָKnׁׂoK¼ָf¨!ֵֵ—}ָ<YS7ָָPƒEָ־0ָJkֻּkHָIm־   ¬¬        $  6''7'77¶D  ‏מ‏¼‏מ  _‏×ףףVף×‡‡m‡‡m‡‡m‡‡¬ ‏מ‏¼‏מ  Dף‏×ףףV$‡‡m‡‡m‡‡m‡‡   ¬¬        $  6'77¶D  ‏מ‏¼‏מ  _‏×ףףVףv‏¦הWּ¬ ‏מ‏¼‏מ  Dף‏×ףףVu‏§הWֽ   ¬¬        $&#"	32654¶D  ‏מ‏¼‏מ  T8dt«ף‎ּap«ף¬ ‏מ‏¼‏מ  D‎u7>ף«sD‎ּ;ף«p      c°ט   !	!°‎¨‎¨XX‏׃ְֵ‏װ       c°ט   !!	X‎¨XX‎¨,,‏;‏@     ּ  J°   !!!J‏ײ‏װ‏״ֲX‎¨XX   h  ז°   	!!ז‏?‏C(,X‎¨XX‎¨    ַ°L   %>7X_°₪‚#F‰חXַ-$DuM„ױ­g‏;      °°    !	!°‏p‏Ú&‎…‏Ú‏p&°‏Ú&‏‡‏Ú&    " #    !!''g‏p'ü:‏Ù'Ú‚'‏W‏p‚‏Ù'   ™™   #   2".4>6&+";26#3גלױ›[[›ױלױ›[[›¡:ֿ:#6#ָָ™[›ױלױ›[[›ױלױ›‎ח.‏ׂd     °° & - 3 7 ;  #<&/.#"&/&#"#3!3!53%7#)"6?!)!°o"=' ןמ!'=
#odָd‏+Ê ‎ש‏ח"§‏pX‏p„
¬'0.&±
dָ,‏װָdֵ)W9`0/û½‏p      ÿך¯°  2  57.>7>7.#>7>'&"76״	8./ie‰טh,Jhqƒx{\Scֹ´Fak[)!#==‏Y0'C7yֱ5<€b‚;<U3-9½‏ּ׀›U3	7Êy&?_€T2	3s  ֵּoSB   ÿֳ }ם3 ! ? G  "./7>2 2>7.'"&5477.'םFOsv““vsOFFOsv““vsOF‎E€wRY,H7:91°ר°.f|C-[TFk1ii%LX(
(WT`G//G`TW(
((
(WT`G//G`TW(
‏p(3\;hI%E:JY|°°|UIW«
`=^8ßj|Ci`$ ÿֳ  ם°  . 8 A  	#7./7>3277>7&'77.547?.'‏ֶ”%R¦ri'
FOsv“H=<%ק%Ze“I&-/"0/a+'C.‎.%k.f|ׂk1i/:°ûPegy8((
(WT`G/‎¨(4kbf‘&2&?@0’6@‏ˆ§nUIW«‏ז¼j|C/WR   ÿ  «     #!26'.%5#!	#/%‎~8‎~ָ‏§½½‏§ָddG  ! ûײ Dָdd׃‎-פd‏װ,   d °° )  	'%/#&=47&=467462 k‏™d^Þ^d‏™kX|Xַ‏»1)ש‏ש[@	NN	@[ש)1ES>XX>    x® 
    	!5!35	57'!!	5#'73­‎¨‏«Xס,‏װ‎1µ‏«I,‏װסµz ‎¨ָXֶ‏Ù‏װֵz´ָÊ‏Ù‏װֵµ{     °L   !2#!#"&546dט);;)‎¬‏׀d);;L;)‎¨);‏װ,;)X);    d  L°   -  !!!!".=!32>'4=‏װ,¼‏װ,'MeְeM',U'5%;)„,‏װ,‏pָ*R~jqP33Pqj~R*ָתq \#(,.ת  ÿג ¸hÞ   %7	†ג‎¾‎¼ד`¸דC‎½דa   F Úּ    %'	Bג‏‏ דÚCד‏aד  ÿ: dvט    !!#	#!!®üה׳}ֶ++שִ+,ֻ׳üאXָ‏p‏ו,‏ו‏pָX       ×° 2  32++"&=!"&=#"&5463!7!"&'&763!7>^6ֹ*‏װ*20‎‡ -d€&°*ü?2222ָ*Û¢       °L    !53463!2!!°ûPָ;),);‎D°ûPטdd);;)ָüא    ÝL    3463!2!!ָָֹ;),);פ,‏װûP, ‏pX);;)ָd‎D¼ .  ‚° 	  3#	#3.ֶֶ**ֶֶ‏ײ,X,‏װ‎¨‏װ   /°‚ 	  5!	!5„‎¨‏װ,X,/ֵֵ)*ֶֶ‏ײ     °° 	   !  >3!2!2#!"&=46#37#3¬$ %¬û׃ט);;)ü);;Iddָddד'-‎$d;)d);;)d);dddd   ÿ› d°L  ' 3  %4632#"&%%##"+"&'=454>3546?.Lüש£‎]&/
S8	ג2"ָRü®lתü®ָ‏לQתָ22ת!       '777'7'7'7'½-אNי³³יNא-››-אNך´´ךNא-›יNב,›-אNי³³יNא-›,בNי´    d°°  * .  #";;276=4&#!6=4&!#'#?33¼20`‘d={ת.%מ='‏¸='2ֲתײˆd–d2‎Dָ°(ִֶ%‏pKd9X+d,Qv–,Q‏םב}‏‰dwװױ‏װ‎¨X     °L  " .  !#"&/&54;6;2#!#3'!5##3¼20`‘d={ת.%מ='‏¸=‎וָָפ2ֲתײˆd–d2(ִֶ%Kd9‏¨+d,Qv–,QX‎+ב}wd‏‰װױ     dU  = A  %632!2+#!"&5467!>;2654&#!*.'&54?'#3ljmU.UkmTk‚‏«¦:d%ƒֻ‏7	
“V‎וָָiזpyLN‏­'¢%
H‏	YS(·SüִX   ÿ› e¯V  8 <  #!"&'#"&46!'&54?632*#!32!7%#3Ûm!«‏×ƒjTnlU.Um[‘
	‏$Þƒ%j‏×זָָPז'‎ע¡(SNLyq₪¸	d)‏­YצםüִX   a  L  6 :  4&'%54&"'&3!26!77><546!5L(‏­NLy%pז'¢½‏	לS·22(S‎¨ÙVƒjTnkU‏ׂTn‏•¦BSV’	ָÊ‚‏Ú‏װָָ   
ם   6  5!%>54&#!"?265&5<.'&'!1X‏װS)¢‎ף%

זp&yMN,‏¬(22¸SםקPָָüזƒV¦‏–nU‏ׂTlnTX‏Ú‚Êָ’VS     °«    2 $54>!!	[¢  ‏ם‏¼‏ל _ Ýu‏ײ,’‏n« ‏ל‏¼‏ם  ¢zÝ _‏ֲֹ&*       ««    2 $54>	5!5!5UzÝ _ ‏ל‏¼‏ם _ Ý‏ל.‏װ«_ Ýz¢‏ם  ¢zÝ _‎­‏Úֲֵֹ    °«    2 $54>333[yÝ _ ‏ם‏¼‏ל _ Ýµָָָ‏װ«_ Ýz¢‏ם  ¢zÝ _‎­‏װ,     °«    2 $54>#	#[yÝ _ ‏ם‏¼‏ל _ Ýָ,,ָ«_ Ýz¢‏ם  ¢zÝ _‏Ù‏װ‏p,      °«  ˆ ™  2 $54>&277>7.'.'"'&65.'6.'&76746'&67>7&72267.'6'?6.'.'>72>[yÝ _ ‏ם‏¼‏ל _ Ý’+>=>1"T>9.*-fw"#.F	= .2)((%
	
)#?
7.R 	,$
«_ Ýz¢‏ם  ¢zÝ _^
'"q!w	F/JG	
r$>	#/&
%	I+
*		' )$#h1'$
       `°¬   # ' 7 ;  !2#!"&=46#3!2#!"&=46!!!2#!"&=46!!dט);;)ü);;ָָüט);;)ü);;‏פüט);;)ü);;‏װ,¬;)d);;)d);ddָ;)d);;)d);ddָ;)d);;)d);dd    d  L°  	  !5!Lü2„‏¢ָ°ddָ‏‏װָפ     °°      7'7!7''7'!77!7'7Iָ‏p/ָָ‎ן‏pָX‏pָÙָ‏p‏pָָûׁָ:‏pָ      ¨¨    8 B L     $  654%2#"&467&54632#"'"&546762#"&54$2#"&4²D  ‏מ‏¼‏מ  _‏×ףףVף‎ױ    	   73H3)צ.  ‏".   ¨ ‏מ‏¼‏מ  Dף«¬עע¬« . !,!T! . 
‘$33$ 1.      .   P 6ֳX  3  %'&'.546326327>54&#"'&#"6‰ZֻGCW#ֵ„bgֲ#WCGֻZ²@C>`9J:vr3H<c=>@]aR6}ֶFGlZ.ƒֵֵƒ.ZlGFֶ}ְ>FXG`R«®ObEVA>Zo\    9ÿעw¾  2  7'7>54/&#"32764/&''7'&'Üiװ…ק_.#7BB]_@‏וBBװB]_@BBiװ‏{ק_.7B–iװ…ק`.5#j+]BBB‏ו@_]B‏BBB÷Biװ‏{ר_-87B]^     ָ  ט°     74>2#!"&!!264&"ָ<f×d:;)‎¨);¼‎¨X‏©V==V=d¹2..2üG);;­‎D‏=V==V  ' 	ב / ; A  #5&'.'3'.54>753#.654&¼@"<P7(²›d˜U(‹WJ.BN/!X‚Od&ER<+6®=I*h×X‏ֽ,<e>‹×MNW(kVMcO/9X7\‡CNO,?iBHK‏û;I,׀‏ֹ…@G  d f”­ H  #"&'>7>'#53&'.>7632#4.#"3#>3632b2)O'*ׂ2'V7
	0$Ý¦
	/-a¦Ê™DP$%T)עֵ):#b "L,“B‘
7Gd17;V^(X²w4K,9 %(d2‚;6"       ®°    !###	###,*ֶֶָ‚*ֶֶָ,„ü|„‏װü|„      ט°     "  3	33!#5###3!##535#3!53ֶ‏ײ‏ײֶָXdddֹddֹ,cdcָdָ‏װd,‏װ,„‏ddפdָ‏pָddd‏װdָ    ט°     "  3	33!##535#53!53!#5##735#ֶ‏ײ‏ײֶָXcdcָdָ‏װdd,dddedd,‏װ,„ָdddd‏pdָ‏p‏ddָָ     L°      !####53##5##3,*ֶֶָ‚dָdָdָֹdd,„ü| d‏ָ‏ddָ    L°      !####5##3#53#,*ֶֶָJdָֹddedָd,„ü|„‏ddָ‏d‏       °°  
     !####53!5!!5!!5!,*ֶֶָ‚ָָd‏װ,d‏pd‏פ,„ü|¼ָ‏ָ‏ָ‏ָ       °°  
     !###!5!!5!!5!#53,*ֶֶָ®‏פd‏pd‏װ,dָָ,„ü|¼ָ‏ָ‏ָ‏ָ        LL    !2#!"&546!"3!2654&,¢מם£‏װ¥ככ5‏);;)פ);;Lם£‏װ¥ככ¥,¥כָ;)‏);;)פ);        LL   "  )"&5463!2!"3!2654&%¼‏װ£םמ¢,¥ככA‏);;)פ);;‏GMכ¥,£םכ¥‏װ¥כ„;)‏);;)פ);d‏ת     LL   "  463!2#!"&%4&#!"3!26!כ¥,£םכ¥‏װ¥כ„;)‏);;)פ);d‏ת,£םמ¢‏װ¥ככAפ);;)‏);;¹‏³     LL   "  #!"&5463!24&#!"3!26!Lם£‏װ¥ככ¥,¥כָ;)‏);;)פ);‏¢תפ¼‏װ¢מם£,¥ככ‎ֻפ);;)‏);;Ú‏³      L    !2#!5!2654&#!5!!5פ¥ככ¥‏pפ);;)‏ָ‏p‏װ,Lכ¥‏װ¥כָ;)פ);‏¢‏¢ָ,ָ   ״ ײ   %276'&!676/#" 3!“		‏װ		½‏ץL

.›	v¯
ױ‏׃X‏J        L    %!"&5463!5./"3!2?5!!5 ‏);;)פָ]]¥ככ¥,/5d‏װ,ָ;)פ);¹כ¥‏װ¥כ¹ָ,ָ‏¢      °°  $  '''!#!"&546;7'#"3!26=°•‏×ױa•פ²z;)‏);;)vJd¥ככ¥,£ם¼•‏ױV•‎‹{”);;)פ);zNכ¥‏װ¥ככ¥b     ¬¬        $  6$2"&4¶D  ‏מ‏¼‏מ  _‏×ףףVף‏ rr r¬ ‏מ‏¼‏מ  Dף‏×ףףVr rr         L°     !!	!2!46#3¼‏װ‏װ½ֲü6û´Údd ‏p‏פ‏
‏םd2      L°     !!	!2!46#3,'‏C‏>Kû´Údd¼‏װ,פ‏‏p
‏םd2      L     	''!2!46#3•T‏F–›Kû´ÚddT›‏F—›‏k
‏םd2     L°  
    ''!!2!46#3™aװaֵ•װ•מ¼ü•û´ÚddOaװb•װ•ר»üא
‏םd2      L°  
    7!7!2!46#3•װ•ר‎Eaװb‏û´Údd¶•װ”ם¼‏aװaֵ
‏םd2    ÿÿ°¯    	!	°‏%‏Ê‏xw‎`¯ûֹ‏w Ý‎8ג    dL°    +!#"&546;!3+3L–‎D–תפdָddטü®‏pט‏װ,ָ      >°     !#"&546;!3'#3	'7B‏†–תפdָ‏לxddX‏%פ{xaפ‏pט‏װ,ָÚ‏םx=ָ‏2‏$פ{x`    °   #  !#"&546;!3'#3''7'77‏·–תפdָg×dd׃××××××××פ‏pט‏װ,ָ‏ףg×’ָü××××××€©×        °°     !#"&546;!3!#53##	#¼‏–תפdָ‏pdd,ָָ,,ָפ‏pט‏װ,ָ‏װ,ָ‎¨‏װ‏װ,      °°     !#"&546;!3/#533	33Z‏n–תפdָָָdd,ָ‏װ‏װָָפ‏pט‏װ,ָ‏nָÊָü|,‏װ‏װ     ָ°L 	    54&#!"3!265!!°û´Lû´‏p„––d‎Ú&‏װָ       f°®  
      !5	5)533#53!	!;#735פ,‏װ‏p‏pdָdd,‏p‏װ,dddָdטֶ‏ײ‏ײֶָָָ‎Dֶ**ֶָָָָ  d  °°  /  %4&;26+"&5'7373°$‏ל%
ָ‎v2dָd22d22d2RuE‏™5!‏s“d‏pֻ‏A¿ֻdd‏װ,dd‏װ,     d  °L 3  %46?5!2!4635!2!5"&5!#!5".L2‏pK‏K‏p"2KפK"jx88&‏v&88	üˆ88&‏v&88	       LL 	   ! % .  '!!%354&#!"3!26!!%%!)%!!'!7£d‏‰dָ'i‎¨;)‏װ);;),);‏p,‏װX‏p‎¨,‏װֱ'‏Wd‏‰dָטddָbbָ‎Dפ);;)‏);;ָ#…£…bָb‏ײddָ    !  3?6&/&&'&'7>/.³¢>’fgׁ—{£ְ4w|~ev‹-‘¢+‰װfg’=!¢/ˆvg|~v1ֲ     °L " @  5.#"?>=6 6#!"&=46754>2°d|Ú~\¦ud?,		Ê>Ê‏pmû´m&RpR&ָָA1)!((!
ָ!"’’"!)‏ׁ3װװ3/2 

     d  °L    7!'57##5##5##5#!5¯¶}dddָdָddd„û´ָ–תdָָָָָָ‏pdתתdd    d  °L 	    32!4632!464&+"Xd);‏װ;¹d);‏װ;‎ׁ;)d);L;)üט);‏װ;)‎D¼);üא);;)‏p    ÿ  °L    ' +  #!"&5463!2!!3#!#535!#353#1#°°|‎D|°°|¼|°ָü|„üאָָ,ָָ,ָָddd ‏|°°|פ|°°‎DX‏װdd,dd‏d,‏װ,  ÿ  °L    ' +  #!"&5463!2!!#5#3533#!#353#1#°°|‎D|°°|¼|°ָü|„‎¨ddddddָָddd ‏|°°|פ|°°‎Dָ‏ָָפ‏d,‏װ,   ÿ  °L    #  #!"&5463!2!!!5#353!5#35°°|‎D|°°|¼|°ָü|„üא,ָָd,ָָ ‏|°°|פ|°°‎DX‏d,d‏d,d ÿ  °L      #!"&5463!2!!3-°°|‎D|°°|¼|°ָü|„‏‏װ,d,‏װ ‏|°°|פ|°°‎Dפ––––   ÿ  °L     '  #!"&5463!2!!!3264&+!#";°°|‎D|°°|¼|°ָü|„d‎Dd‚)69&‚פ‚&96)‚ ‏|°°|פ|°°‎DX‏פ‏pT‚VV‚T    ÿ  °L    % )  #!"&5463!2!!3#!#5353#335#°°|‎D|°°|¼|°ָü|„üאָָ,ָָ,dָdַdd ‏|°°|פ|°°‎DX‏װdd,dd‏פd‏pd    ÿ  °L     # '  #!"&5463!2!!5#!3#3#5335#°°|‎D|°°|¼|°ָü|„‎Dd,,dָd‏qddָdd ‏|°°|פ|°°‎Dפd‏‏pפd‏װָ‏װd   ÿ  °L    # ' +  !2#!"&546!!#5!##53%#53#%3#!#53ָ¼|°°|‎D|°°ü|„‎¨ָ,cdcdָd‎©ddפddL°|‏|°°|פ|°ָ‎Dפd‏װddָd‏ddd     ¬¬        $  6!!!'57!¶D  ‏מ‏¼‏מ  _‏×ףףVףײ‏װ,‏װdd,¬ ‏מ‏¼‏מ  Dף‏×ףףVGָddָd     ¨¬     $     $  6#5#3##!#53²D  ‏מ‏¼‏מ  _‏×ףףVףׂdָָָd,ddd¬ ‏מ‏¼‏מ  Dף‏×ףףVGddddd‏pd    ÿעÿֲA  !  32654&#".#";!3	33 €x××x.,,µn˜״BUqOÞdָ‏װ‏װָָ,¬zx­aw׳™kEPr,‏p‏װ,, ÿעÿֲA    	>54&#".#";##	#X“^y×x.,,µn˜״BUqOָָ,,ָÊ‏mdy­aw׳™kEPr‏p,,‏װ   d  Lm   %!33	33!!'¼‏ע×‏ע×‏װ‏װ×‏ע×‏עK^Kָ,,M‏³‏װ‏װ›--     y  7› )  %32654'>54&'.#"&#"327!¼.6Ji	2;{Y“^t£	Ji9/iJ8,K^-2iJ f=ZƒYq£tiJ5XJi‏־-      d°°   %  !2!5#!463!546;235##!"&= ,);‏ָ‏;),;)ָ);‏װָָ¼;)ü);ט;)‏pdd);d);;)dd‎Dָ);;)ָ      L°    # ' + / 3 7 ; ? C G K O S W  54&+5#!5##"3!2653#73#73#73#73#3#73#73#73#73#3#73#73#73#73#L–d‏d–טüddָddָddָddָddüאddָddָddָddָddüאddָddָddָddָdd„–dddd–d‎מָddddddddddddddddddddddddddddd       °°   	"/'	7'!'&4762†*$‏יװ׀‏„/ֿׂ,#*¡‏ז*#‏ץ‏װֿׂ‏ׁ|׀װ$*    ÿ״ ;÷° O  %>'.'&#"327>767>'&'&#"67632#"&'&>767>32EC8fOESkZ(G‏ D0#vF?8!@)'(#Z	.C"‏|Ey&$‎Ý4I7Z	0$&\4=k6_v[ü‎w<ÿC´]W†$!7G‏ D¾N9@1*+,#b/W""‏tCu$'$‎Ý4B?#>@$$\475›be[שÿ      d°L  ! ) 1  %4&+.+"#"3!26#53"&4624&"2°;)–37S*ָ)R:.–);;)ט);ָddָָ>X>>XָX);E5+);;;)‎¨);;dָָָ‏אX>>X>       L°  #  32#!"&546;5463!2!54&+"„d);;)ü|);;)dvR,Rv‏,ָ ;)‎¨);;)X);ָRvvRָ–    J  f° ' /  32"&/.546;7>7'&6;2 "'267&R‎תס::v?zS^Sz?‏Þl18F8(	.))­GM~ %M‏¹‏+1==1   ָ  L± 
  3	4&#!"ָֲֲüא¼‏E~    o Dח H  .7>7>'>7>76''&'.7&:4?	FFB:8( OV
	$9DkC@&¥₪'GOS3*gJ.ף;4(
.MV .nhB8-
%>=Bם'P¨d!I, =CnC¬Sm,U!†Ù•fm§    ¯…   7'"/&47&676´û₪{‚‎¬+oX!N`₪‏‎
~¯\F/‎¬n+Weֹ6\e       A(˜‘_<ץ °    ֽצû§    ֽצû§ÿ:ÿÝ             ”ÿפ  ÿ:‏׃Ý                ˜¸ (°  °  °  ° d°  °          ²  F   Ù   Ù   £     H    F  ° d° ָ°ÿע°  °ÿףפ  °  ° ° ° d°ÿ¸°ÿ¸°  °  °  °  °  ° ° j° ° ° ° d° ° d° ° d° °ÿ°  ° ° °  ° ° ° ° d° d°  °  °  °  °  °  ° ° ° d°  ° 5° d° ָ°ÿµ° !°  °  °  °  °  °ÿ›° °  °  ° ”° ° u°  °  °  °  ° ָ°  ° ˆ° ָ° ָ° ָ°  °  °,° d° ¹°° ° ° ° ° ° °  ° ° ° °  °  ° ּ° h°  °  ° "° °  °  °ÿֳ°ÿֳ°ÿ° d°  °  ° d°ÿג° F°ÿ:° °  ° °.°  °  °ÿ›° °  °  ° °ÿ›° a° ° °  ° ° ° ִ  ° d     P 9 ָ' d                  ״                               d d      d dÿÿÿÿÿÿÿÿ   ÿעÿע d y      ÿ״     J ָ o       * * * * V p p p p p p p p p p p p p p p ְ ־ פBJb׀תNnֶ&גfzא^€תPjְה<rּת0z¢	 	&	F	|	ר
8
^
’
¬
נ.x¦אvׂ.†2.f˜ְü>†ְל8N\¨¾ך0D\ˆ¬Þ
b”ÜPˆ´ָÜר>xײ&פ$f¼ (P¸Úע
B¼L®
d¾לDrX²ÊxֶN®  4 l ₪ ּ צ!$!R!†!¾!צ"0"^"˜"ּ##@#j#”#¼#ל$$6$`$–$װ%%<%f%%ט&2&„&¼''D'x'¼( (:(l(®(ע)6)|)¶)ר*.*d**ָ++€+²,2,|,²,‏--‚-¬    Û              @ .        ÷           	   j   	  ( |  	   ₪  	  L ²  	  8 ‏  	  x6  	  6®  	  ה  	 	 ת  	  $  	  $4  	  $X  	 ָ |  	 ֹ 0’www.glyphicons.com C o p y r i g h t   ©   2 0 1 3   b y   J a n   K o v a r i k .   A l l   r i g h t s   r e s e r v e d . G L Y P H I C O N S   H a l f l i n g s R e g u l a r 1 . 0 0 1 ; U K W N ; G L Y P H I C O N S H a l f l i n g s - R e g u l a r G L Y P H I C O N S   H a l f l i n g s   R e g u l a r V e r s i o n   1 . 0 0 1 ; P S   0 0 1 . 0 0 1 ; h o t c o n v   1 . 0 . 7 0 ; m a k e o t f . l i b 2 . 5 . 5 8 3 2 9 G L Y P H I C O N S H a l f l i n g s - R e g u l a r J a n   K o v a r i k J a n   K o v a r i k w w w . g l y p h i c o n s . c o m w w w . g l y p h i c o n s . c o m w w w . g l y p h i c o n s . c o m W e b f o n t   1 . 0 M o n   J u l     1   0 5 : 2 6 : 0 0   2 0 1 3       ÿµ 2                     Û     	
 ן !"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~€‚ƒ„…†‡ˆ‰‹‘’“”•–—˜™› ¡¢£₪¥¦§¨©×«¬­®¯°±²³´µ¶·¸¹÷»¼½¾¿ְֱֲֳִֵֶַָֹÊֻּֽ־ֿ׀ׁׂ׃װױײ׳glyph1glyph2uni00A0uni2000uni2001uni2002uni2003uni2004uni2005uni2006uni2007uni2008uni2009uni200Auni202Funi205FEurouni2601uni2709uni270FuniE000uniE001uniE002uniE003uniE005uniE006uniE007uniE008uniE009uniE010uniE011uniE012uniE013uniE014uniE015uniE016uniE017uniE018uniE019uniE020uniE021uniE022uniE023uniE024uniE025uniE026uniE027uniE028uniE029uniE030uniE031uniE032uniE034uniE035uniE036uniE037uniE038uniE039uniE040uniE041uniE042uniE043uniE045uniE047uniE048uniE049uniE050uniE051uniE052uniE053uniE054uniE055uniE056uniE057uniE058uniE059uniE060uniE062uniE063uniE064uniE065uniE066uniE067uniE068uniE069uniE070uniE071uniE072uniE073uniE074uniE075uniE076uniE077uniE078uniE079uniE080uniE081uniE082uniE083uniE084uniE085uniE086uniE087uniE088uniE089uniE090uniE091uniE092uniE093uniE094uniE095uniE096uniE097uniE101uniE102uniE103uniE105uniE106uniE107uniE108uniE110uniE111uniE112uniE113uniE114uniE115uniE116uniE117uniE118uniE119uniE120uniE121uniE122uniE124uniE125uniE126uniE127uniE128uniE129uniE130uniE131uniE132uniE133uniE134uniE135uniE137uniE138uniE140uniE141uniE143uniE144uniE145uniE148uniE149uniE150uniE151uniE152uniE153uniE154uniE155uniE156uniE157uniE158uniE159uniE160uniE161uniE162uniE163uniE164uniE165uniE166uniE167uniE168uniE169uniE170uniE171uniE172uniE173uniE174uniE175uniE176uniE177uniE178uniE179uniE180uniE181uniE182uniE183uniE184uniE185uniE186uniE187uniE188uniE189uniE190uniE191uniE192uniE193uniE194uniE195uniE197uniE198uniE199uniE200u1F4BCu1F4C5u1F4CCu1F4CEu1F4F7u1F512u1F514u1F516u1F525u1F527    QׁK(  wOFF     @@     sH                       FFTM  X      h+¶GDEF  t        OS/2  ”   F   `ilדcmap  Ü  ~  .©/Vנcvt   \       (רgasp  `      ÿÿ glyf  h  3ִ  [Xעֶhead  8,   4   6 8=נhhea  8`      $
®xhmtx  8€    ז ploca  9”  ©  ¸ֹמא@maxp  ;@        . ֻname  ;`  „  |װ–™üpost  <ה  R  yײcQwwebf  @8      K)Qׁ       ּ=¢ֿ    ֽק–    ֽצû§xÚc`d``אb	`b`ֲ[@ּז1  ¨  xÚc`fidְְÊְֲֳּt!
B3.a0bÚה¥€	‰ךמַאְ נÿ?kֵÿ/2¬Sx€ֲHJAֽ  xÚֽ“?LSQֵֿ£-PB₪©€t׀{׃ 0 0K*N¢‰\hˆA4jֱׂ„ֽ`X1YM״tװׁ¸¨ס¿ֲ ßNN’h˜´ûn]˜\L|ֹ¯¿ףnתמ;}ן+€€9Jך€א&Dp5׳#•ץh0ֻףqהxM,:±bכl—´«יd[‰g$"qII»פָ הeX
2!E™‘’ֻּ’F´VSײvם׃ׂa-ט„־hIחuQ—·P.ףÜ{v†ִ$!V÷¥WrÜyDֶ¸ף”ּÊ,h 1M¨ױVםײ¬ז4¯£:¦Eױ9]׀%·sy»¼^–ֽםַֽ›7"כMkûײג6e[lׂײÛj3eףֻü4»fַ|3_ֽ³a®+ז’¹`¦MׁLqsײ1'Mֱהֽ1“5‎‡^û§ץOzקQu«rײ>{_₪׃½(²R¡÷ע¾/װ׀]$יIצ׃«4י₪M@[אA3‰{p€nננ­C(qקOׁqß9H'׳¦YBÚ‎LI7יaמ§{ֹ 3‘ֹ3§‡=א¹ף	zL0»µ¢§ט)2ֳ|זLJ‰ש=Gז™ֿ׃d‰ש2 üMך:_£ÙYk™y²³÷־׳ivVvַ÷•¸‏7iצWקל¹fֹ ףm‎uˆשֽÞךתß¥G‰כfu‎׀ל®®ףCױu~D³³÷־OiwEזח4»כ2ףK`pÚ¾?	Þ׀ֿB€·פ‹א‎*xOן† üH† יֶאÝ|¦‡ kt&Xחÿ³ץgװƒ*7˜{¦5נcיhÿץQ½w¡סo®ת•‎H   (ר   ÿÿ xÚµ|	`Tױ¹נ=w™›Y2û›m&¹™ּLֳf23@H‚aûQpDֱ€@qm ‹`¥״
„HQ»`_¥…^װ¶j£Uת´¶¶”VסµÊk+d.ÿק;“L´‎ß{¹Û9ק;ßש־w־·ֻ°L%ֳ[¸2†cD&עa×Gyז|פ9ƒנ#r,\2ֿqרXְַGEיq”אף˜Cqb%©ûכ_¹²־*Ù׳ֲ₪˜ßִ712ְVd1הנ;ִPR‰+¡₪לˆ9’²¨נMZתֵM/×נ׃ׂ„ֻ\\ט‎„ײcאֱ…kF¸®ÿ «Eכ׀ZH	ֳ¯-{¥µP\‏«üa&‰ןררX´ַ¢ֱ_?«Iֲ!‘Lִ¢²Gצ׳DX¹•Q1H
W¦u“¼b‡K,ÝקףU–wS+„3©‡ן<צiֻֳף±¦	¾‚a£\dmJכ0הֲ„;Úֿ‰Zrח7"W§×ד©}s7½tף’dףאBwxn}*’ üTֶׂ7±ַ‏´O|Sח:n#{\כ`ָוOI˜?ֱ_ֵxֶ¥Dס ¿\¹ArֻV"¹=GÙOV8°:?¿üכן°³Vנ¡»V?k]°ך׳;-ײ¥c¦®₪dƒ_oÚ±ְה1ש״	 בJזÛ/Me›|»ײ‘"m—¦’6•oJûf­ƒmNן'בׂUeמעg—?ד_ג_b ַ’‰j,·#	.ג'Ý˜¿׃Va›§±‹ÞN?¡’I¿וײ\—²מּֿ_׀¹>װז·ü¶k\³ÿ†dַ¹oרL@‘€o€"{„;? aֽZR©dƒי3XßÊה_aÊאֶ­Dצ‘d=‰;j‚¢ t¢ֿ2¸s#'L™דt<2eל«)m7Yס¾גב)W5sֿ₪ÿa—‹V&’U…ע´$·ֲdז^ז\¦פGAבÊוUEׂüל<  |שc‏§üNֵֶ3‘xÜ6b(א) G#¹»§,a–q[IX—ו¹ף´U›Uם₪X³₪ףִשעֻÚ'h…ֻD‘„'כeÚװִֽ£‎ץִֻP…עo3„ֽBßךY+¹­בד5pC״G¯]}gsbװûככקm\3jטÜU«ײpֿ¹4£uה°5קÞstײ¬£קÜ»f״ָײƒרס@Zצעק˜qüv ¯‰± T—‘¸ˆג¹’הה­Ú½Ú†ַ´uה·±‘w:],kׂˆK;O‏ƒא₪J‡נ@77עg.”Pׂ‎²˜ל6{ֲֻMü_?ÿcםÞ> µüך¯~µשק_ױn#9|א> 6 סע`©ID=^ג6ר%הYׂhhÿg{C`ֻ–-„₪¶ַװuםםכװ״צע8}¿‰®mfֶpd±Rֶֿ„˜L5C~Q²_`מ¹א,אu־ÙֿI¯g״®
üw<½m†‹־u©·±û¬גףפטש¦Kַר&`[E©ל?¸	g.יױ׳7אXֽ4 v׀O¥<ˆ)Q׀כ‏Kֻ/x%­Eעz%ׂ&yÙזÜ»פ‏+—סEÝ׳¹ֿ½יmW( װ}נֲÜְÜֶÜֳlHnֳ@¢_-L¿ה‏ÿ¼~״+©ˆ=H[ÿ׳üןװב‹²W‎´kÿ'ֵcטגף\÷קKqה¼§¼גÊ0—<Pפ.ÿ²קs(ָmפJיׁxֳ¬ûקKת¥Qz[׳ו{_L]x™r‏S~ H8& ’($’sׁפ7ׁQ־ױְש—z½%ּ~,?ץˆ ˆ,#I‰l$ÜJם¬v¶ƒׁ)ך׀־’¢%=o;°
ְָ2 װ¿א©0״1ֹ/שד‏x,»‚DHo׃ֲ	²ײר»‚d8Rב„צ׳ֿ°N×'(¥_,DXCsÛ_½©¯DwםÚ[¢ךה‘“÷ÞD$J'ˆ‎ִoUe #ף;am¶ֱJ
כ”־K’Cח¥dÜדAֵ–G״x3)Q]¥צ¸WJב¨ֱ!µןֽkw½ץס[»®}sßֽכ.’…׳±ה¬,¦^{חֵV(‡j­/’Êתg’ךךח.^|nµJuC€ןכZß™€ß#™ ?¾]Uµמֱ¥ץTrמQ2EL1¿ƒßֱc¦ֱû²'m`DN$Cֹ\ֶk×Ù`5ICb(—‏rk°‘A”E\JמRײSJD‎ׁץְ4¸†V–W‘jבֱ¡ƒ§r6עuAר:±ֻÜװנˆ…Aƒz!¨Lו
ףÙַ†ַYk7u׀ˆ„ACJ®_t‎‹ֲדl~7-\ק ‚ן•
b„‡WOד
­:dk!7­zרƒBuִ\¨TCֲֲƒµ‘i\Q>Û÷¾שץכ¯if€V©ֻ—שvֱ:…·KR‎8#Y»fy\ך’W$Aֹ
—Zגנ»bÝ?n#q™lVף¥³f«ֽD\µU$\U›Be’ÛX[•7:W׃?¾ֹf­VsװdKUױײVaaחU#FTHÝ‎£s‏ÿט×®סûב‘אaLÙ6צ׀NAy`›ÙSח€7­ƒjױ¹BnÙfׂf5wFָoאױhןz¼ד׳›­(’£x†חס‡€¯\¨{1e¼׃־עe¬Ýֳֹֶ%ִ$xם28קלׁ>ׁkלÙֳÞ רל32û3m!א˜גu•'ײפ|¦—%t±Ý#¸WÊ¿ִֿ)$ֱֶ¢˜ב†}ד׃Êפֹ‘7₪½מ׳ֹ‘פÙhסbל+*י$ךֿPˆ;°ֿ÷לֲֽA2´ֿbµT>ָ_´קUVµקֹ]GtYI4`	ƒ>­A׳<dֶO¢>Vִ5ץ₪»׃f #ן_xֿ7לֹXV;פי§dז§÷דגֵ*₪«O׳}×@¬¼½ט[װ?}E?~׳	ü­׀״•י›m‹םׂ#- «2—גeY‚(\LV’1•+ûÞ`¶r¥ק.P£$UֹdD-€¬ֱ‎R³¨¸לפְ]b
תאYךDֱqEּßױ~ÝÞF‡>{l…‘ָ®¯%ָnCY׀×¨8ִ ¨n–,¿sַEם° >¼ü1ׂ¶ץֹS™ֵ׳T¶VJXS§aֹ]™‡p¢k7CaÛ­· ”~0( $״ƒ°&ג1y°‘E{|[ֻCֿ]Ükנ©'·¦קק¸וÊ¶M½ר6{׳V׀ß[÷ן€»˜µ©םû׃ֱp(¬*€lְ־#‎«ˆ₪ְ‘B»•j°R-הBzÿ]ב2¿cÜF•לÜƒ¾>P=•ƒסcb(/Vב¢£ר״z¢¢Ê^¹vcÛCwּuxhץ÷•K <m©²ׂ4ff18_)¦P~׀שכq זז**#,QVֲֲSOÊ|${pט.HT³ךuֿ·ßרפûחÞתFX"ֽײM †?l¢—XעצהYטmמ¾ֻ+|װ‡׳מ׀:ט²¾ח•+ß¸‡Þ½£ױ`Eעs®}n¨|3wt&X+‰"8ע‰,‚°ש"§¬Om·)ûַ•i-0_T5ח“…ֵµPX¶,!(†`>טHÛ˜Û–…1,‏׀‘ן<X‏טc:ְ/ה‏–›$H¢פ%¢ l„`₪uֳד~a״Üf˜!®]%¦ h@ףGרע~xצhÞָ¼ַ_~iG ב¢’6lךְֹוn…vעs|•ƒ
`1µ =]`"ט׀KeN,אP$Xפ
(q @×ץ|ֵ‎
hv`ת©LÊ¨x‏8¼ ¨]a~´_Shרi`%אT\;׳Qג¢ccÚ“©-¸ R³k₪קƒˆn,£/"ww`5†T‡ֱb”`HgpdSz­גEjײִּ״h‎ֿGֶוwװfµ'¸A§0שגˆ’צֱ׳9iSU<יb·פ‡|{gw.קwיX*e¼–‏׃y„dÚׁ×7²T&s‰P°\4¸eO;י1 F9זץתfרנ€B
¨פ“M;ביץשנ = Þ£°עQFg Q>Fra>‡0aVב־i–(9כˆV×ƒC¼Ù
‹=³ַsÛ!]kך ׁ¡ˆ¨„$שצTÚ™b?י\wûג־¥:xr„ֹYPֻ¸ׂ.B@גDNVא`”<ל¡’¨װTµ¸ן־cם&­ÞdgחU²SI-2¯
Zdֽ¦פ~“™².¹`rqud.Nvם™u7₪G«*{\U׃m_7Y­&<טפS<€</g%EH5!~b­ִ†£[!DA4$ג’]|{y¥׃g0hק\¯­((±°Ùָ²×ת
׳uה¡יצqmכָׁd€+bֽח־v:[D±±ׁ“ÿו‎AsY#9‎Uם'”¨‡‎„תו*ׁ±חBUִרx8=גװIבB‚#F‎›~HRה׳« +וfK^ס˜­ֿLׁ,Uµ¬ׁSQJֲ_i÷uÿ®o^sןÝףג´5y}²2Oה,±«Zַ־Ü:י1Vֹױ–ָÞ|›ױX;iת‚־­f־½ך“טְ¼ֶ‎i²•³־א‘%ִ$יJ uBqn«//„{Mƒַi–I·ח=k×g0
|û°<(`±t‏¬f„a״0qT57mh…‹pdתױֿטrֵ°–0 %ש¡k‰ֱ' µ9tט×‚²₪ּ¯ßµK¥fjKJ-W×Ý!;´½ן¾{"üPַף†$#Ûy8JE¹י¬Q
yqÙר`ß>®lß>:¾Êור-`}a›—Q‡%¹ Mwuw›ֹÝEpfÞf÷Ûdֽ´yיX©/¹§¿A_r7ת€‹½ן¹/)G«0½תußIgÞqח$¯
7׀›2(
{¥Kַנo’¼Ü÷)יצש´s_rש/בםת¼û”ֻץa¨¹¸©W,שק°¾Þװ0ֻZk½ן]_R~A₪´Nu­E§´NuU§´Nuא³ÿÿ˜Þ^xü»ק¹ָü+׳ÿֲy‎תׁ׀IסFֽס@M-׀}ÿ/ֻ»}&8zֿÿ^IµÜ‎Qטÿח!װpyW—Þzֶ f83Zף±Þ>ָÞףQ •,|ֹע
	w·JÚ|nֽB}׃Ü¾־‚׃§aן‚GפQ*@ולzדצ¡uÜ?§ü|bjגÝך&¦uA¢ü’׃/Wo¿*zHתפץDסֻתu÷»K  ₪װÜ^צ§מKtו^ופטְ¥cל£ÿR§t{ txדל0²ÜֶpiÚY—²”ך}˜{2ÝJ<
ַt‹nuwכ@MװPÚ¨QI!D\zּj ת÷.םw9שfW!(Sÿ &ְַי¶ֹ-“'·נe,ה.ְqB÷פ…ûw“zםֲֵֹˆû׳.ֶ—נ/•]!QqS%'ֲ‡‚q0ֱ\ׁDMc§¶yםכkֶÿב°a¦ע¯¿}`ֳ7ײ,}üס·w<־¿´‏­¿o_שקֶFcשׂÛצLüÊÚu¿¾/=‹–,}ך¯Y‰װֻCBו»§—c„4·_÷װֻׂy=7ֽv*¿ןdxמ·‚סPשם3 ‚$¢Û´ד"l(˜h 4)³₪סSזLjL^ÿִ”•?¹wפ²Ûo#z"&k4^—´Y¯½V°Ûׂ÷ךצ;sֽ־T-o˜קג×yßûז“';׳<*ַo×‰-ß1mת­EÙ±9ֹß«´ֲׁ †–KÊ ^Ý£‹¬XG„$	~ז
י÷‹?ת¶ײסם>ת6Y^תם4תנָ…×Zmנ7ֹמ•ÚRמֱ³JVhw‎V	û#CÞj«P«­÷{f”ל^¡-e²~™םְ%LDWts‡l‎Ý­ַA£—Ê­‚Xֿ•¾©«y}`ײײlףsָ-ÚN±אךQ³G3ûn¾ש—gq˜Zײֵב~2װ¿ֳZו״¨e£‚5.#!L>û /ZC=ס‰צ¦/ה 1ך¨צt)־³‰‡µיÛf‡}'‹ü\1›|D›¶םaדװ}״˜+קK10]אÝ…“#qֲmT¢½«û°A‎»„~‚ַךSת“Sp]yJֵÙ¨ׂÞיׂs7B_ְÊRH/ƒה ˜%G\ ח´	Yֻ+iֹ÷ג¯4`פֵA׀\כ	aפ…§R˜g`(LcDת5¶F£ת´×Þך§a1®בְװ,½tַJeRÜF÷NֱÊטט}תN‘¹Ýֲ/WziÛ`§Sר÷?gפש¦ÜW÷`א×׃¬2׃?$ƒ yI.@ÿ w|צ³˜פ%1$h–ְ$ף¿&ֱe)‹tFךziףT=&/PBg ֲןyֹ ¨ƒץ>צ}בײ$¸יÝ0ûְ£°(פ“,א+בÊ‰# פD=ל£י3ÝoדPף•י׳vd¡#.ּק‘ûÿֹ%i~ˆס¯ÚSdֱ_׃םג_ֹ¼88Û/¿ֹOחן@-Lְˆ‘E‰%#´§‏ֶH$Sש₪g++ַ?ֻ?KםכXA‚I׃%,uְ÷צלׁ־kַµף{צi*‏דם~גֲ׀ַjO¸¶p]˜׀Þ#lsXÚ»"~.Y”“r2”‰¡ÞנfnÚüײ¦Momֶc_ְןd‹:6mfzגךטÙ,MHךÞ½¿gׁaג&»פ½pׁaם#²כ°.·»ב&@§י	9VֱִִֵַֿX$j½02™Û Zy׳«¾DƒÊKüCF)^ü`o¬ְy+ןג\<Y°…¼₪ן¨₪ÿִ™×¢ֽׁXSe¡וײ`1‰ַ·Ýc±^.­{4•ך‰¯­w¬F „א<¡ִ‎~PCWט}Y}Zװַױ-׀SDS”$¿•ˆ’?)dסX=—ַ$+R"	קנÊ ´’;־·¿Zרג5ךWצ8a0fיx5ׁ÷װ©‎z{ֽiµpׁxuגע‚7Þ,¸m"{\]´_9y²רס»ױ׳ּHז«×{ך‎cװ_‏†:a©üÚ«K¯Vַ/+רE¯רIiÿס[#ק¡ˆ=´,ûwֵXJEN¥-6&’/lµU{ז›ז¼vֵןװצAשֿ{µUÜk&¹p	Xװפnm&;4uֱַֿ8’~mAֱֻֿת4–¾“Mײvנv2:‎Zםg[2kעTÿ5׀ָ!ˆ…oOןַj¿"?&'µ¦÷X3ו°&F$©ֳ»V©׳}רgנO¶®Bר	ÚÛZ‡צ9‘}כÝ}V¯gd±"wVk׀F‘JמNÛ‏•ש(¬hFI4Cû·6רcש׃n¶שgd[1ת־Ißzצז<b׃jsש״ ח ÊDֶװYqָ(ˆ[מ׃־l&ֱûׂhgמƒ;הÛ3´‡אiƒGt>”3~~3¿9‡¾/CַB¸ֲßL_mמץµw7“{f¾Ú₪ֽׁZ6hןn"!ּ] 1®T†û{k׃®ב²ֻGP„c’ש‎ַÝס­kw‘oח₪_÷רב¼ßpû°´ןלtֱ)‎'q{½nםM8Þ—B)”יD£Þµh‰ ֱj‚‰•6bבשÊz·›Ê…z‏c₪ףW‘r׀ָ9ֵQֵ"gÜֶ²וז÷P™ק¿<  >Q¼¡:“eoCg8¬*ÚְWk™“M®‎‰+w:קיqj<r‰ֹw(4״ׁ–ן)°´9#ֱחעÜ&’ׂױ¸װIKױaףW_z‰&²ךk׀ו¿€ם‏ֵ(ֳִ“¶$כ…₪›µעײo#˜ „ְֶ¡v/<ִֹ;ֶ<N©^|׃»%<ב–ק¯^{ÝÛ§Ùcc,½¶Êֳח‰nֵב²®NÛrֽא׀¨ה—ד#מ]¸¡vִ¬״ ß0ßKÚk¯ן3\¶ה+ל`ֳק־÷‚·0v{YÙֹ׳nֻׁˆrדעO˜»שף|,f׀U´Ê#B5 iˆת™‰ײףI0ֶ²)Iƒטד]˜9&¾ָP2vÊם­ÛÚ¶µÞ>el‰¡¨קƒפ˜{WM¿&|µ™$½ֳ‡ש†´ÿ³=²h8j׀µ3ַ.²xq ֵֹ6W*חּ?®÷zÜü™s*1Z׳ד^k©]W{‹•^8בּ5~־A׃ü÷yCֹo—¬µ¸`~ֳC_טK;״ef(Fץ@הig0N
דE¸NCIXש	פצָ‰j f
qj§¾˜‏ּÊֵ!SW_®® ÿ(soÚ6>טT]>״UM÷/’–n.½שt§Zצ·a s‹t׃ֽk†Ú÷zג״Þ_i©Y÷pQֹV[0l(ֹÞ3ר±ִ–‘nםבM־}ֻ±?ÚYr:S=§dz1›o€^והֲ:ךDִzl°‰ֱ ƒC1²~©˜Tײ@±”^ןךN¯—9»×ם#/‘—´} 
ַ•סe¡לׂÛe£1‎סWיAהB
†ֶH$:¯×R>‚×b(א ¬jג¨4&='’¨†‚־ְ•‘¥6OCÛ™*rֽ{«(¥םtxldiף÷fצgÚ­CÜUמ|ֻחÚח׳zFJvדהֹF»4ׂs-?·הCׁ+Cfװ77׳S›–Vף‡c§ס ‚!הXÜHגB2₪H W~K1מÙפ~ם ב״ז¿בlKÙפ<Þrדסמoֿo÷כ)®,½_e›O¡Ýuע®ַֽװWטױף›׳זטם־LLƒת5ˆֳ¯ÛF)מyיCÚכ˜Þֲ7ֱץ~0	PµUlsUm66Bף507H—	T.€‎/ְ₪©#ה–=˜Toױװ=ָ‡‰ב$l(M÷יֹ?y׃₪Pn³Bס@mU©ֳÚ,,װ+£µ¨¦¯_²bj,6uֵ’ץ׃¨W”]Wh6»Kü•בH#ן"4{שקּקר[¸ק³ת¹L¸‏Qתר‡d‏ק>dG¥_ת,ְסֻב_ד™Ln‘ˆל£lדןµ§´=aG}ˆ*‎‡־₪„V‰y4קֶ¡€dנy?½אw‏ב׳המSd רשOב~•Üקכ־HÛ¬j-ÚI1ֽbם€;JClhּ :Eu[-־Ê$t¡T¢g₪=%I0OB\0‰—ץ„/‡Þִָi6ֿח,ץ@חח o”÷ָהֱי‡Ê	)ֱf-המ
ֵ|;”u6נש(¾ױ>·הUxָo
®½)GlÊהÚ€fֻהI	˜ֱÙֱz
Tz4ֽ7;—¢iNjחzüSxÞµ`ז¼‹sd_U$(ה´yUOל¹4•„1T\ R4+0׀=?#>FךJ%‘S§ָ 2אװ)םmװ(cןַ}P«פ”´~\Aח‚‚^»¥5Iץֹ“₪J·ֻoנץָ8S‰NÊ.¡Wf%בWzH¨p³{\ב׀¥_י ³ ÜרLl˜‎׀$§+RµUzׂM
£yG—weצ&ה3!ֹּ ַ%3­Êƒקק»<Vb4bC,4‹Ùן1¼HMP+ֹÛש9ûtתת`5k²״n†Ûֻ%¼ֵרûha!o-/ע¨Ütj«†ךל­;«Úַ3)‘ָ4כE–ֻ7Ûj´ ‘%&ַ0¬k»שÝװFC`»ֱLHÊ¨X‡’ a“—}0שֿß‎מ'0h׳.¸‏ֻ±cֱk~ק'ÿ.¼{WNסמנן&ÿש]«R”ֶ>f ץ~¡ֱֶ¢‹¡– ֻ"יJ@?ƒ~…8Q‘8ָןh 1Ú{<Z<״:«;U·ײ~1רִ[ׂ¾WR’ß~8µ#MÛ½²הװO‹Zִֻװ0ׂ\:0ײ*מO׃־ÿמnם‘YױסעLµn_-ֶ‰(_ֽגzVK׀Û#»2ֳNO”בBJף‹~G)‰]	פU•\טFJַ‡´>Ú‏n|p‏§‏מn²*¥=ׂס]ף`‘רY€Oֽ©	 K”	¢d—’ץֿ%e‡¬¸ך©¢H}טXÝיÛ ך(גz+Yb5;,ֻ¬זY$2kי²™K7ˆֵ6ם רL‰uxמ7Û^µ;v-Iּ¼‘yLÛfc¸ֵg–«Ûi]׃װ6Y{ײÚ›ֿז;¦«5ד5d†Tי“Ü^BXNא,yײügt‏”.^ָ׳7¾6ת0”}|¬”ס‡ְ‚ײ$dEַ:G׃\D«0 z…¦µלR’ ֵ‏7d™טRזilֵ†û—ּ\~+צֲl]–o7_»ױ$6עf¢U¼5H`‰v€=Úנװ³|~ָ‘‏4ÿ›•™1¹­‰¬±Û¥ü–o}ֿl394§I×Ê­={שûyqY€²I°‚IםּמגװwמA3F­EW˜¨pIÙJטה'9<²PˆßT)iֿB“klk‹ַN1aףףק¾₪?ס¢§Po<Eְּ^z—ּ־פnי,םֽ™’¿ÜS×m7¸Ji$3f?זּ׳»ֹYQQÛ~ֵ^l״ A¿(ֽ]4qח…¸*¬י:Gת פÝa¿פ׀ÝÝ`ג¬TI‏ÚZ׀FB.…ֱ2ֳ]µ7?‎±י¬ֽ׃’´­!“Ú&ֳ’zָ]r¢¡ְף½חֹ?ר©×Úy¹‡ּF^²i;J=ֻף‰»pכע™ םּC‰ם׃י!×Y´חqש־ַf“Ý‰…sמ cֵX‰f-[\Ý\ ®ˆ`O^»·ֻ_ססצ¼w']ÜֹcÚr‏אםד¬ַbן]ןם¹As>A‚d@—:°םtוגֶ¡.א fggב[Y( \û¬ײˆײֱ„›»צז€N?«9qתd־‎ֱו0G*H¯¡p»~^E׳u¿P1G³ש¸.]ֶץכB'xXםu5=¿•®%Ý°fvצ.ƒץlְb`.enץP¿|lׂc€³ÛJֽY´TאwBֱת Dֳu2f€”‚P„’$˜bֱ$:ָ’.”!חAֽAt`x¦›'Zß—VÙױחÙךlR=ַ¹7Dָ+6”‹§ ¯`¦§~˜7/ֲd ֲ¢U¾r?gה[ַ:ue{װ־Cֿזֹ.—Ù`7T,•V«‹ח¼`<{^©S6×ü³©4i1"׃m®2»ƒֳ%®ˆ39ּ־¾gqLט$†lfY,_!+«Jcֽ.Þגֻ«צס־	מq` ,לJoE})ט‡¢ÝLX³Ù/³ױl~ח	:½…ÞÝ@ˆhrְ*/ֹMײמB£׃ֵֽrH*«¸|Nא+üw‹sHˆg¼%ֿR!,טג₪{]זֳXÊִ\C÷ןךG+י£© ׀ֿ³T¶ֲ;`:פx€.עÞ€iשC¹zzֵ›l>zF׳7ךת¢¬`ßװYו6j{ARֳk˜ˆׂ2GÿBß½^ֵVuחQoKR’“ס›Uפ·נױק©p•‏/¼Q7³ֽ™ף»z1±ׁ[ץ¾Ko GÝ<\/ֿמÚֿן§שּ¥ «™¦+e5PצRׁ%$©ֶ­ß‡ִ¬QH&2Uג5װ-µֹׁ+8׃oyÊÊ<rYqc™7V[ׂ»:VUתקH)iו‘2,בק_!cתׁ²H™Vf«ױ£ְµi«ַ[‹U”¬¥ת»´‰ֵT&ֱÿ„oֽ5ֽP0בh7d×rbנױ ÷`¦׃ֽ¶ t#־k`±ֿ}uÜ¨9‏“·,¼yֻ}'üsF{uמQָֻQץף‡MÞ÷2vץˆכךGz®_0=ב¹ûװ״q·־Üע¾ûNÞ™{כ¸±§מ&?¶װm.7תֱֳS)}U½gמm׳Q>vשS~ÿ#ְ­G´ק­ײP§Xכ`rh&	Ýte,g(!ÿיZÜAרַ־H©׳lÜװx‎#µ?66v׀+RÜ״¸ֹ".מ׀ײbo²‘לp׀ז;#q¯ְֹכב•Fם#o¸¾Qû\ֲ?€W|ו78M6^?O¨™=א/ֳ$°’F»ץ÷—Y·»ױ‹vx"5\·ƒץm“Úwf׳ױֽ®Kqß—¢/•:ַ¡¹נlz´V‡E „'¨5¿c×a׃¼jטx§™z1XJ!÷–P</û#„sאv#PIvט[F–˜¬<÷+ץץY•…ÜW„9#“«•זSRֱ1׃GהÊK®owp—ˆ·hזםחVׁqS‎W˜'Mvּ©\:{ׂ62E´VkN^ק׀(qעÞnXÜxץDםqזױק†$ƒָ¾®v>0rץ‰,ג¾ֶ?ֻ\MsY˜װ/×׀Mחַ°€:e=ÝÙדט3ָ1ֹןkל®6·0Z5%4@~3-$’ֹaq[*Þ{¦ְm2rױƒ<sß+;GO­ּtW	†ü<wּ~z2O’×†ûJe_-o"בm…2׳ָףEףVÞjָ7;Lֹ-דRC’Eµ³חU6]U3±ִ+…‡™‹¥2gְZֹ§¢¼µ	³•”כ‏G׀*רֳYß±Bטצ£~A<א”zÛ@O`µnִ|}­z״¿@Ûq2%˜J0״M׃ד˜C^N¡[וbq¿+†v5i‹T{û”ש/©׃נGֲ7¦nT1¯9•ֹ>Ùˆ™ׁה<jֹdû«=Ú³3=û5Û^¶‰¸Þ—A 	rÚכj&E·טÞ”ךjOױwמbƒ״4ֶyJ›&Úתחי¢napרel‚צ°‹P4¼₪F°”Xe)ֽ2 ƒQj/xצ\x]8
9@aÝ0»ˆ¯ֳ‚“†©‏:M2rֵDG¡'–.׀	³¿.,U•zרפ]ֻ4 M¡³ַU-‏})l€ַe׀Nדֳ.›ְִ†‏`³=צ׃]jRN&‘™Gmµ¾½זe°q=דs2jש¦ּS¡f³1°¬;_)ÛנKי־Uֲ¹^-	 תד>ÿֹ^רׁU9miדָ$lחיףz†n„כ.J^לÛ^!יn{׳;ן€S\_»״׀N^Eס׀kןkßםÛ^ן|,®W{„S¾€dÚÞ‹¸?Jכ@b׃‏§_ֽmע4©א¦¹E¹cֿ׀׀¯¸ְq(qא¬6lר!§jZ™5Û,טK פ£oז†poףOׂ%b((*	g2Q+),µ÷{L‘.“mֶü|¶ָfּ+  O°l^ׁnה_ׂ‏»ֹa2yHd— Ybg[]'ֻ“#‡)ֿ&¼¥‎×Ù%¹´	nֹÜֵ‹´?´ױ.ךֵ1­¾‹t™>qeYzYױכ¯׳ֹXOיVO¦CßחE–ױ»hû>Ý ··g®÷aµֲ‰99Iµ תsS’רצםÚ·ָ‚םהֲׁ»²#µ»uBJoקיףלסםÚSo‘ÙÛ׃_Yûµל0Þ59›ֲ´a¾8ÞםV ‚‡JÝw>:ק¬\¹gו£»עJ,ֿsת:ם־ל\4²”O‡®ֹ#“ֹ^"':|Ù~י˜•;ƒ›bט'#p»‹ֵfׂ־—Ù^&ps.Pi£´ת‰90uÿ:ÝÙׂּ{טÞ&Ü¹Bף¿zÜ־־|B»cW.Dֶׂ־Ü¥}|—¶4 ß…#G¿ Q€c,ÊüDüÚI°;t, '·wlÿ„=Þ¹=6?eAַB/ü“‎aח¹רע94ֲַָ´נz’·!₪ַp L+־%נÛ;¾\£I€ְֹ\נפ(—י¾üC¹€ִß®´W´ױü×פ|‏ָ¥׃ִ®­"{״קׂCOy:Eq’טמ•„;§²ü;­Ü­ךsןHÞ®Ú‹(״•s‡1uKOUwûhÚ[†ךיÜַה¥q”נd‰„8VJÊ„mװל«}¼§{×ײ®&`װs¡ׁ־¯fכT-×U`ס|Ú–A°fÚעק׃–C₪m‘®Ýs¢ּ:´»[¼שְ=ְ¿:pאל_¯Fµֿn>@S;}Ùע{¿s ח~"”¬‎ץ	ַ­[¨‘ֱ˜YrתÙB·jי־’Þvd6qיq¼¡jװ2×;W[ÞÝNfKÝ”ײ§וך+™‎n˜=ֱיyֶ°)5»wb°]–€q־·ח&ץ_:¦G־i“;Rי34„ז²¾E ףpFׁקץA0 XEְ>ˆ!זzü£–€ֲ‡ֹJp!@o*tTַkׁ—g÷׃;—ׁU5=Z¯׀D1¶kO˜¬!¨iָםcKI6כ¢+ץ.הָ|û
|{E‘צqa ׀7›h""n·¡©h”‏ס׃K];םcJµÞ¸R`´Û¡6¿פ¥Wµ«h²Þ¼Jק¢ך”‰ֲ2]{ױ²q”DRv¶ַנÚַ¡ײ·~^tw<ÝֶסMֲׁB­eb¡f)¨µ׀ˆ	<!mֹ…ֲ‰₪>Yֲ­צvטPKPk%-C‡ֱ(Y;־>&j­ÙַLײ¿׀Dַ:»׃#!ETd) c׀[ן@OM@TE¸Ý.₪$Ù§S²hSIhqz?ˆ·*ֱֲ ´׀]”4«¿S׳/ֳÊ>'Sֳoךֲ…0~9Ú	כU‎<‎ְBu¡צ6ÝלÿÿF»\¢¬Gk 	Lbֱֲ›“D¾»—ׂתַÝüֶדkKK¶\?vױ״WmY·‏¦V‡ֱתכ ~כ^ףְG¶t,÷ש±:…לuT[¶עײ›׳­ßÜךג>ב2הִֻ™‘ָ‎Þ_nוmרÜ
XGe2\—OBָ„׃³N«$ֱהנ(2ßn(J­;³‏÷gמL5„F¯û·w©ßD/€0ֻ¬’ֿH²’e¬'8½ez׀C«×y…W©’”ÊJֵlR¥B^בֻ¥ַ

“Êב²°J{ֳs׃מ˜•TGשü²Bֹ]\b2u}cגO׳ע₪ֲÝfµ£פC–`«#Üw§2©t)nד¥cך‹תצQפ	·₪.^ּ|{ ֿ¨ן‎ױףV‘is®÷²zֱִ€:טµת9‎u·a¨]קױֱ³*–Vױbt™;ֽ¡נ{™=	˜7ׂ¿^ׂg_‚B4g׳װ¸גwִ׀װײßÞ¾.=³F״דכÚU´³pƒ¦¾¼×´“ ףa9¹ ַ@¢9kC¢Aײgך¿’2†k—ֳÿ%xאצwü‡װםx_0@Ý¦†ןG1•j‎RCָׁׁû¥ם?ת¡ 6©ק?ױ«MOVט׃f>‰"‎´„~R€ƒ}ט׃ַ;א_q–)…ûm…־rֵ׀Q5( NדÊÝO›©פטװ†×ִ°אraCpX¢jCD™7sֳלÙfצַkּש2^ֳx>˜צ±¸ÿ9¦׃ֿ¾˜ױ¨i¢r±…Ùo/פ‹	Ýמ®ד@]‎דִ@]w˜¯ ב‎¾ד€א'F0A(}ננf¾ֲ–“A“µח)׳+t9‰¨“¿ ₪ְ•(d׀בvכL¯Rz:RיןPG(™םכ÷Ru|:טxK&ח ‡ד½B+ָ!	W6ו–־ÛÙק¸‚µ3.ף­&װֹy~¦ֽ2´כתm•® פ“QרI:׀X®ה;“×\װ;uוֶ3KB†״ֻ^ÞM¿)YD¿‎כק«’¨ךq÷†ַ•ÝÛכ“SÞ§^%]ֹכף±ֹ.‎)׃V‏e×zׂo[Yµ™l›·¦gSy,›Q[ÙW´eE»Skz4¦µט•ָך=Mü2ּ×	(DWP	j{ =°ַ ְ?@›?­†ּ›H“IÚw±`׳ A÷µ`%ש]8_=·8D‘ePקHzXְ#ד!M:D…=ILXlײ®½¦hÛ¼;f­ֶ	‹‡Uף‹'CSƒHװ·xBAiÙ¢÷¹ק®Yספ¼cOˆ7Kvֿ„ֵ>םƒ2z¾"gvtכ¦fט92_d¢{otVv}‘LaT„׀e†:Qm•"¿ ­¸s( ‘×j3\JViNCקט¶¢£׳׳“=¾‡1ƒ™£כיq£¢הן¡¯g?‘ûûWa
ֿ;²;I:r>…Yüא‹ן©U€¼N-'ז±¨©/תַה~k´@ÿvh(¯&g3’dH!¡`M2ו$ֵ6›u€µBûsַכÚFR‎ֻ7mּ~|ָS;k`µÙüÚko‏R{ƒ¬{½ƒָPßFשיע;L-ÿhc
ת©``ל'‚Aװקצױ³IPל0y—FD°ײIז£´"~Êg×אWz|Dִײ0†Fu״M3cיÜÊq3÷h°¿ulֳPedU¨ׂAץ״ח÷‘QוÚ:qYדvֵֹA›½"‎^ֽרה\£`\,p“כjך–&
וZףִk;׃«Fֻ£]?חֱ
%Y€@4y״ָûֳraץ[^®Ý!"wzלrEֱ56רכGVTH,ח»Þtםח—{|{uÿ*Ü?¡ןֿנ ֽבח rÜXט 6dצƒ:iפ?	R[µƒ·ֵ’3¨U׃‡Gּ–ָ÷¨חֱ ןnSÛÚTC}s}}³‰ryֵcג¡‚הײN לֳB	uÚ´°R=“û½¬B×ףִzÊ Úל†yIP2Ûy%Phx?­׃Ixz+9‎+ˆ\Y¦-א©Nom®RV¦mM€¶ְ2…yUi7ֻ°ו4י־ט₪«ר-Hח¶פ}Ùֵ”‡|ר%=>טp9=׃׃ÿ6|¸³¨״םhm°Ý5ֳip÷ח¹ב8ד.[ƒצ‏­C,Cַ5דז6w¥h,‰TIn±Ê-y%ד&­w—¹$Wְuֿ$םûÚ@ֳ÷÷!Ýûכֳןְr™µֲTr‚׀w¸‘דÚ~=־%:Wnc,üh‏O4וד¬¼hˆ°I+כֶם©=ק8׃©ׁ>kױףֹעָ³BM´±>³½ָ.‹ײ›`;¶´qר׀J[™ױ=e¶C(ץ˜6ֻ³V½tװH[נÛßעH¡q6ח”B,ְֽ"לg–ÚגגJ¹׀־›"“f—E–ßׂטzֿ ֹ¨¯ף5MÝRֶ‡]מ2›g&¼ֱ]R7jש¨Cdֶ²נ¬§VT|w{^י¢eûטְעGר¨¯0י!‚« L}„9(Ü±KשßZ»!}ָ6׀v›ֶֽ6+“חk³–זLכ\7¶:}ָn_nhgחÜt:qÝM2ּÿם¯üxÚc`d```dpdׂ˜11ßז+ƒ<ֻ ֳÙo¿—ֳטÿVÿח°Þe• r9˜@¢ †ח°xÚc`d``עÿ”רoץן2כ] 
˜ ₪ט0 xÚcÜֱ ֱ²	§ְ״L=¬q»100Ü„גֵ@>צ€׀ 9¨‏,‏‚˜ףÿ3ד¸Ù|@,Qף£״ֳ²@U+׀ֳ ¥¡4׀9P=>˜ֽ‚¦/‡=0}LHjL‘ü°H+b׳ק6T/Ll
”_E‎	(Ýe@bF¨;l ֽֳüֶ‚הW>ִH|%¨ßan=ֵףֲׁ€´¶ׂBˆ0aװCף«(’>¨X"װֿ¬PqV8>1,N0×yL(n€I$qT6K ֲת?‚t‏ÿפÿP®¢ü?ִD/†ש t© xÚc``׀‚ֲ0†¼נ ֳ9†/RN^I³/0‏b’aעcÊc:ֶ¬ֶüˆ%¥eֻVײ8ײײ_llYl=l״°³±Û°±ֿb?ֳ‏‹ֳ€£c'§§gח.®8®I\k¸>pכqWp/ד~ְ#ֵSֶs‰W·ֿˆo?¿ÿ‏üלÚ¼ה´פל\!¸Oנ•‹PP‡׀¡{ֲ\ֲIֲS„ןˆH‰ˆtˆּÙ"rBהָQQ;ׁ
ׁkbjb=b_ִUִ׃ִg‰ן‘`‘׀˜%±Cג–ִ'I.I'ֹ	’{₪X₪|₪ײIsI§Hן“~##!ד"S$!»Iצ”“\…Ü1yy?שu
b
&
9
K־(|STQRlSÜ§רMֹ@)Ni†ׂeeו,ו)Ê{”ß¨ָ¨˜©$¨LS¹¢Ê¡j£¦:Gץ…‘Z‹Úuuץ
ץ=V9כ4>iiײhn׃ü¡¥§•¢ױ¥uB›I»A{“‘N־&÷b÷M÷k c±yi       Û              @ .    xÚ­’ֽNֲ@…O‰F$¬»pבֶ¦  ָÊ¸סh]
B©Jl¥’ר>ƒ7.\תת>…ד™aD‚,‘ff¾¹=קּ[ ּבִ/־hׁ׳Kמת¬a	·uּג^q»xRE'p£eO"£=*BB{S#¿+ֶ¢>¯x†¼×8N>Uü‚„‏Uֳ+,‎.C׃v{†SץÚ¾YץZ״„‡z¸‚0נְ‘‚…$VH¾5Pִ9Ú\w¨ן’…¾	“‘¸|!_מj\k\»/¨ÜגֽֿPBÛ<ץ û8¢®@/u‡‏6s©·qֽˆ8%ֹLKײ’ַ1O/3/?ײכ§׃עˆ׳o+0FעNה=|¾קd†k*I‏מ;Ú 2@Uך»ƒkףhׁµIO¡©3*N®°ד&ׂrdÙקr¼ור/5>Êַd¶ֻ¯ÜaÝ×ÚgTPכß4eVYaו"z²§zZ”ץ2.ת™ֶ:{ב,z:ר?~mˆ{xÚmװױ’U Eב^IH°ww‡Þÿׂ'CממNI,¸»»»[pww¹א!x ׃kמט›]]ױg3U_Mk\kפףֲֿVׁת¿ֿ_­דßßÜֲc"“X%X’¥XֹLa–e9–o‎ֽ
¬ָJ¬ּ*¬Êj¬־¬ֹZ¬ֽ:¬ֻz¬ֿlָFlּ&lÊfl־lֹVlֽ6´	J*j÷פ״–ם״״‘״™©Lcˆ]ט3ּ®לֶמלֱלֵÞלֳ¾לַ‏ְֱִֲ¡ֶבֱ‘ֵׁֳ±ַסְ‰ִֹֲtf0“S9Y־lז0—3˜ַ™ֵgsח2ף8¸‹Xְֵ\ֲ¥\ֶו\ֱ•\ֵױ\ֳµ\ַץÜְÜִֽÜֲ­ÜֶםÜֱÜֵÝÜֳ½Üַ‎<ְƒ<ֳִ<ֲ£<ֶד<ֱ“<ֵ׃<ֳ³<ַף¼ְ‹¼ִֻ¼ֲ«¼ֶכ,הÞה-ÞזÞו=Þח>ה#>ז>ו3>ח¾ה+¾ז¾ו;¾ח~ה'~ז~ו7~ח‏8}ײü¹32˜bָׂל™םצװצ¢-Úם±[¸·t+·v·כצÜ©ƒ-†[Oטּ›3ת¥צ’ֶ7םׁץ}DßGפ}DßGפ½¼ןו}/ן{yßֻûם״‰״‰”®½״‹½״‹½ֲ^a¯°W״+לצ
{…½ֲ^a¯c¯c¯c¯c§c§c§c§c§c§´SÚ)ם”¾«´Sz¾פ|ישÊף•ח+ֿW¯|Oe§ע=•½Ê^e¯¶WÛ©םװvj;µÚNm§¶׃״i|Wc¯±׳״kל5צ{½ֶ^׳^׳^׳^׳^׳^׳^׳^׳^׳^׳^ֿ^ֿ^ֿ^ֿ^ֿ^ֿ^ֿ^o׀‹¾£ןט;ת¾׃û‎א‏ט:÷®£כט:÷®£כd¬3ר;¢כט:÷£חט9z£חט9z׃ס]÷®£חט9:£ß”׃qÊ±ß{¯~£ßט7ת~£ßט7ת~£ßט7µ=GַׁqtGַׁqtGַׁqtGַׁqtGַׁqtGַׁqtGַׁqtGַׁqtGַףÛ³׃t‏ûo?q$ֳו´¡ׁ×ד·‏ט7‹¦J1˜r0ץטױ`
-|   QׁK(  ‰PNG

   IHDR         \r¨f   bKGD ÿ ÿ ÿ ½§“  rIDATxםÝ[]Uַסן´¥z£®ו"PD#₪µ!@I”bHװ!ֶ O(ֶDÞ|!Fװנ€’o1Q0^bD–‹P.l…‚@­¥´´׃‹םר°†₪"“¡3kםÿÚk?ֹÿ±ין¬ÙÿÿÙûuצI’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’4•‘ט *b1°x°r¢–Nװ¢I‏Ý`;נ
°רחD=	¬vM­־9 Ú°8xp.pשÿ¶ְ‎ְ}ְ=ְc™ÿIoׁ{€oס z¸8§לK•°¸x¸¦¬ָ¶¼״«—ךdא&אUג}×Ú|8©ָJHrנ}`ס}¸µo"ûסÙWEjÜ\א:ׂ»it#ֿ´¶‘.ָ÷BR£VOß¸¹כq`Uֶu’2\ß¬¥jlג5תץ³tˆyְˆo׀®ך6ׂe4xַk‰oÊ®כ>ׂמDi°ÞNÚjÝQµ´kQִמג«¥™Xi0–ׂז'‎׃­.…₪זwßtµױZ`בײUך…›ˆo¶Zכ‡3XW©z$¾ֹj¯+§½÷Rֵ־$ÝP#÷ֱj¯1Ü1¨ֶאuÿבװZ`ײ´VZ×׀g‰o×¾ױ¦µׂRe– /ßP}«m¸S°8O³Êû2~ַ=K¯F‡h¿Ê*כׂ}ףF£ƒפװ^אtאֵט ­ע ¬/bףִֿ<אKׁ!Zז@9‹ח€£¢ƒפÜׂ=wDi‘g ו|›?‡ֵ₪µT€r> !®e!^”±Xכ›ֻ8י1gכ£ƒ´ֶ3€2>‚ֽ׃pytˆ9 ÊרPt€}8:@‹|—Êoר70?:Hcצ’;¶+:HK<ָo6	ף€5ׁ!Zד ָֿƒ´wGh ¿³¢4ּµּֽw¸-חִט ­q הח/ÿÊסIֳ™9 ע;2:@ֳ\Ûּ שם׀°‎ׁZד ָogt€†¹ 3@~›£4lSt€ײ8 עף -ַב™ ¿—¢4ּµּֽןRוxv•™ ?ׂr®™9 עף -ַµּֽi9]eז‎ Ê״…?	־ם י'ֱ¢ƒ´ִ3€2<ָo+6v€2 ש¹¦8 ÊנZ5?׳´ @¾[וחא (ֳkש9 
p ”בֱkZ€ ¯Wףs א (cKt€m׀"@דׁה€ e¼-:@ƒVFh‘ ¢4טֲט -ע· שֽ!}xttֶלֶ¢ƒ´ִ3€ü־ֳז/apQtˆײ8 עף -חׁZד ָןלט {Wt€ײ8 עףׁ`ו 5€üÜP{2s ה·#:@ֳ\Ûּ ש¹g½c‘™ ¿uׁצht€ײ8 עs ”ףxt€ײ¸0¿Eְ6ׂ@ו3Nתְ_Zfה@~;û£C4טl‏ל eü):@ƒמ׀"@׀ ‡j~Pֶ|ׂח ף¢ƒ4b?°ty¥<(c7p_tˆ†¬ֵז/ֲP־ֿ£4הׁZו%@9ַ/א©qא4אÙט -עא,g3נ׳טx›¿@Y7Fh€kX— eֽ6'חט«­ְ)xְb<(kנµט=v=6¿zn6נ0יֳ,כ­׳₪3(©קÞ$¾©תTחMk¥¥JÝN|Sץ¥~?ֽ5ײaע3€מ|/:@Ü`(ü ;£ְֻ₪ûhr{HwVvכo<ט־אÞט=נ 6g Ýz0:@<`H Ýz(:@¸Fr tכבט =נ·ט Cג‡€ÝÛ‚OּN`	iֿ„:א@ק¼mרהײaףwÊ׀=/&חÚtּ׀½G¢Tּ׀1@קמP±{¢ֶx85:De6+¢Cg 1מP¡¿D"@OuÿC1€— 1^ִץÝ8י’ט¹אƒד@ֽ¸וץPaף‡p ִשMt€ü::€װµ׃נ6a¯׳;g¸–R/ÝC|ףE—?ÿה%@¬[£Tְ5׀`ֽ%}‎.U›נך¡<ˆµרntˆ@7{£CH‘$=ü2תÝ¸כz–t£Tiנ® ¾!»®gY9©·ß”]ױע,™װeְִ7gיz~גµJzƒsIֿˆnׂRµX“mµ₪]C|£–×«3®“װ₪ׂ]ƒ¢›5w‎*ח"I-;‹´G ÷isױ^אפ¬+$5מִַ7n®תYזµQ&מ¬׳O£dה ¨”w₪©׳2ׂS„Zׂ+Hû‏U™®Vm#‎P¨ן¶aףWֻP·Eְָ[}UּP·W¢d°=:€&ח ¨ÛXt€צD׀ה uÛ ƒ‎ׁ49@Ýֶ£d׀ֲkh– n-4O¯¡Y€÷µ׀<-¼†f9 TÚֱט  n-¼{¶נו ¨[ֽ׃ֲkh– nGDָ`nt M־P·…ׁ2hב54ֻP·ׁ2hב54ֻP·§…׳׀,@ÝזGְָK€yCzֽ^¥ÿפ`‏(¨Jװכtתßü s€³£Cטֽ9 ךױׂ³ףZz-Rq€ִßֽ7W½,ֿ¹@R«F€[ˆoÚÜץKÚ״״$3
ÜL|³–×ßַf[-©#ְוְzג›´tm®ֲ-ֲKHֽנ(סÙu=\2דU”zdנiא₪ןָ£±†z¸/װ¨Ùְְ₪Sאט†«µv“‰צQÚ״©›\ÜFz:Ntsץ­צ w־–:ּµ—Bּ.n6ßD­װk₪~ֿT™9רNßezfא/b8רנ<סM1װÚM:3¸¿VTײ~#ס¿ץ¿µtvיRLÊbp5p/י׳ׁ÷5u­®N{“¿§4¥#H8ש]}ÿכAׂf«EHSXE:ֵßDükו­1ׂחדptˆE₪wˆ‰?H­nך)אכ¸y׀V“vזשױÝpkנאƒhFסÝÞzףÚ@ת=ֲװ“€oאמ<kךz•פ9׀©¨ק־#}נ³—רֻךW ם8¼ץ־%ְִDVu7iƒ‘ßTliרcִ0V›µפׂTÙ״רV·ץ4ix“׃`—k‰? ¬aײßOבo:ק^אג ֻ'm,÷wי_ˆÿ£[ײכüת°ˆÙְ5¸kֿ×¿v“¶¢,V $‏kY‡S+ׁ\l'‏iY׃©Ýְ•ט°ֽnְpXmװ·yT¨ֶM‹ÛIקׁ—Z±–´›pKtCױ6 FßחJxˆtÛרׁA^7+:ְÜֽ¯vCתqZ5}W׃¦«€¯D‡
;״E÷ֹl¸Z.–‘מה÷,:ˆװ׳€3IקUֻ©ָח°ש5ֿG‡€zְ¥ׁ₪Uqּ׳r	נ2i¿4cTנ ׃ְ(iַTY₪.-^‰Pֳ%ְ‰״ü¦ׁj 'G‚ †pRt )ˆ €†ֻK אטט Rוׁj ‹£HAֲY^ֳ _)Hר±ן ג,א ג„û )Nר±_ֳ ?’‚,ˆPֳ ¨עf‰Rֲ‎€OZױP…û5 °×¡
?צ Rנcß ֵ	?צ Rנc¿†‏Aˆ$| H’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’₪©‎‎Cִס<a    IEND®B`‚‰PNG

   IHDR  ,  ,   y}u   sRGB ®־י   bKGD ÿ ÿ ÿ ½§“   	pHYs        tIMEÞ(	ßÜc  ֵIDATxÚםÝ[]ױ}ַסן\לסmןזbL1ז.M!iו@…Z!Q/jTU¢‰Zׁצ¥o½¼נRUךKװ*×"¥PU ₪M” 1ֱPs5ƒNJls±g|{ֶsיֳֽ™3ד3ַח²ק9ßtהq!ןÿÞû7ÿµ־Þk$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$ƒK 
ץ+eְRא| X>=@70^ז†€׃י3€~אpÊְֻR5–₪`Z–‚i°¸X¬NבµX,J¡5›S)¨N ַSH >צû€ֳ)ִַֿׂ<2°4ױ¼>}ְ:א÷פÙ\‚«ןן ;ׂgo
÷c©S“¥6=קi¨w#נ»ְן W]e‏ÿדu÷ffûן₪ {ר°=ujce†’Z״ְ#ְ־4LJ1>ֳg,µüם¿;Bּ{H׳#יß.©ֵu_‏x˜+›!Dֶ›ü™יß5 לp”אP­gp?pp=p9ס­^½‡{ץ6{€·€§ַ“jKֵ×/ ›SX]S&v”û7¿üx״jpIֵs9נ׳ְ«4f.×Cֶ©ַ±רֻtל’
 ר<נֳ×J‚כְֽ©’r×רסf&׀›1Q¿/ױ¢ַֻBÊְ¿ß¢X§Z|‡ֶ=ר*©7ÿE<ÞךCְ¹¥!גץ^&Rף}xֹ}¶cPֽײmOֻoצr‘ח.א9»×»­gSְKj°ֿ[ל×זÜmm!¾A”װ — /VU‡ײ6`=>8-ױÝ2גU”׃†Uױ¡5”:­ױ^Nֵׂe	
e)נmא«L^0ֿN¡rי÷_ֲÿe\םT×¹>אֲִׂk34<<H¼s)©†6+Vµ­ק€{¼¼×v6¥׀ÊûR0E–ך¸’X"zp׀ׂה[§%(„ˆy+ױַWRe‡¥.cb-(»«ÚtY¥N«‹XÙב ±«₪*=ףVֿzÜ_גvX×Þ ,ֿt×}§UׂC¼,‎¦e‘זf>סא¨ÝUֳ÷¬QגR׳׀Ê)'Ýף©›xIקÚt¸צzׁ÷¬סTכ«‰—Ê»-‹¥Êפם‹-Eֳ-Nµן³Re¿םo ¶gw¸ײֿ`:vµvX:‹וְ}™y·doR­{ׂ9XnI,=°מץ·{׃»Ü{,Kg·ֹכ\×’ׂÙ2°4ƒ5ִ‚¥›ַב`s†…¥א÷¸׀’X*ןגt“(nKחD–Ê¸¸ֹב`n†…7YK3wX+-Cn,ײZK׃-ײ¥Kֿ©9²ץ_¬²$–¦‎mֿ®׳‰wKS\¹1\ף×¹²ץ¿(XÊ8״ֵEש²,XÊXgrg‰חְֵׂt½¸B@-פ¼X®W€ץ¼ְָ*ˆ3י£|!V"•¥!א´eָבפ‘¥ִײיÊ—cKוo£–!wX–¦;BKש;/–%‡„	e`ש7¹CB‡„2°
ף›Ü!¡–¬ֲtX–%«Ng†„®‡ױ\ÙתץI~¸w¾H÷´LsuLי°ֶ,‰–¦;i	rûKD–Ê,C®־‡¯KXֱ) ß2הF:'2°d`"°-ƒ%ֻKV–sXש1€sX–,;,X	Uûְֻ2°d`X2°n_ֹ“cיְָ’roßי4°4£>`…eָ5ְ"ֻ``©¼•ְU–!7®NחD–Ê¸¸ֳ2הֶםיְָRW—Z†ÜXlִו~,Msi‚€ר5[¶‏׳ WXK“]lH?wר[½©²ץß ¬·$–&[\`ry^VYK“,±¹³$Xr.÷,C.ֿ‹ק‰¥)־ ֳ–!w†׃¹‘¥A\Ýׂף"« אJyװk”Xזc`תÙח°+[ÿ‎ְ'–ְִּׂ¥|—O-ƒ¥ֹ û,Cמלץ¼X*o±׃°O÷7W©‏ְֳ¯p«zKe}ל°¹סנ‘e0°TÞ§ְצּßxo¼lֽßִש+K³ײ[S†&jם–¥™Þµ»j×ל/‰·qSK³:ל´MמY
K³ aג›);­ֶ©צֿבמ–־×xׂs׃פûג)|UÊְׂYs'¥ֹ^'Þ§Tכֿˆ	w7Q5°T£ְ³Ll“מ°°qֱֳ׃ְִ\¢,U״e}Xֱֱ.«±ÝױׁT{׳ְ’זרֻה%&Vֻüל§¶lm_זyשו“Kעז{ˆ2Ü ,µ׃jH‡ץ!נְk–C»א1»¬†} zÙÙa©:£ִִûא"»¬÷u²ְכְ?/<+ַף$Ê·'‰IG«÷I5‏oֻa`יÜ_³¿’י|ּ¡6U©¯[­«¥Úx״’יל´j׃Y•ךר±”,ױְ![©§¸P¥תרְ2װÜ>ֳÊְRםץK¨¶vבk8–ךX»-Cֽםֶed,ױÜ נ~זן~£U½lםÞ7°,ױÞ	א™¿ûMaץ²µû€‰—ּe`©†>!ײ·»×M—5„»mX×›׃ְ{Vֽכ=&ײ“¥:–;»1ְֻR‎k·Vֽ:¬]©¦2°T'‰׳t²7זT%oƒ–ְִR}{Hק›ֲ¹+ױl״לֳְֻRץ{€KQµ1`/pְRX×ÿזÜQח\†ƒƒִ2ָײ־ְRכ7שםVץN¥ְµ–ךk„״³׀ֹגך+:¬6°װ€aֽ^ג5ׂzה×L©V¯ה8$4°װ ¯[×ד7'‡Sםd`©~J¼[¨Ê;SRֽµ–כyא—S†:×l8ר¼ו0°װX#ִSן+ן® ÛסVKM±xֻ2Tl»ְֹRƒ½ lsXXסpp+1ק'KM²‰-ְÜduתP°Tw2ב.KMע2נD¦“°׃ÜY•ךסנ’%‘ןbוRG1–ש¹]?Ù¼j₪‚כ²-ב8±VײÝיv׀ÞOֱg‎נ01Ù>ה¥"וֳ*אQbֽ¬vמ´²ַ<|?ױFR־\Al½n`ֵg_×‰₪ס¿N¼~ׂמץp׃­w«µזn> –>Úk.«t¬‡ןßֱ5¯₪Ü»”ר¿††¥c.ס2°ֳR1IֶF&&Û¡ֻך V‎gאU/©8÷¿£‎ז°צװ·.to]#ִ“Ýo´ׁ1oO–,׀.אֵפs;¼g¸•״[–
טWִ’*­lך־{=ם­=ֿ¡ײ5F<­;י=®‎ִ«8²ֳRA¶ֹqףtX*¶aאPחֱt¬2°T`#ְ‰68־“¸1×¥ֲ¥=–U&זלd`©ְֶh‰טְֻRkקe`ֹ›Ùc”¥ßָד§,ֹK–d‡%ֻמֳc”%ofQ–<ֿ•*םַ(/düF^׀ַ¹ —ü6°װױÛַÙk`X2°,XrHh`ְֹ’%K…µ ¸°ף|`¡§ÛְR±­h£ף¼m0°TX«€MיחV}u%{\׳בץ–
kנ[יחם>²ַץ%אO»¥גי~#}Úֵץְ@§¿u/jµ¦?¾	¬LC¦VÛ)ד:b¹ה—½₪bx״ִֹü־XזחV‎dqנ¸Q°”k·ß#¶¨m£°*Z‡€קxY´¿.¾eִ<ױְ-ְֽ@ֿ”aR;ֹף8ננ:ננs`¯—¥ֶZ\	¬®NAץכL“oדף[מ״wÛׂ¿vןy)X×_'µ¸ר"נÛְחÊÜ¨׳³׳cננ4נ>נ)נמm`©jף‰׳jz€ֻ€ßמ&5ך4₪jײy½™ֲכ§©ûN§,U`aך¢ממJױUֱֽ§ךƒ‹װi=<™÷¯ֿpיeK3שJ]װiר׳›:¬־9Üt×>°ֶ¡װ]‏7u_ֿß¼Êְjk›RHÝ\N<ה¹Xyְ€jl€•»S—uר˜´ÿ9נ’%3°ÚA'ס­Þִk$›ˆoת~עoRשי¾ צ¿ &ךß&—״‚MVKט® V¸
¸¸‰x,ֱ€*nקu x-…ײ־4dü?א %3°f>±ױN_&^Ê]mHµdק5l%N‎Yךִ'-™•Wףˆ	עE)₪¾l&›2 Ú'ְß4>Bל0>2°r¡;…׃ן§?/˜e(a`µv`eֿû@
­'Þk<nֹ¬fײמא~ג©ף‹ֳִֵ³]ִjÝנ*w‡€ְ‘^O©:\4°גZגa־Ûִ<ױ
Jt_ַ‰‡Sקצֿ[ˆU&d`ױּj&VB¸‘X;|m…©®r׳ִaא]ג±ˆmי³ַrXױZB<ּy%ס’סִ”j`»‰'כw¦א:d©¬³י$6_A¼ֻwס$ת|ƒJu®rקאkִ<׳ÿ ֿ{9d4°&י&^8^|ר“75 װט־k רOא»©;ֻßxןמÝü1±rח<ג5™KM¬ׂ’ֿcִüײק“צV÷רֳVˆ­¡zת)gֱp†xbנאQ`—ױ¾ü1~¾.£üW¹ûפ נננCb®ְֻj1‹ˆGמHץy&o¶i@©h׳אג‎ֵ-ִ7G¬גױb»צ{‰9×NƒJ-6d|…˜ßzXת¦ßְ*–ִ·|w‚×cJ«mP©†Ùכxנoִ<׳Gִ7‹Ê¹eִc	ן0ף&›~ü´ך²דִÞ‹FששYוָfbף€a&v>6°ü´[`ֿl½<@ש=6ְֽׁ7S`­aע“י־S©]ח·Fˆ5י· ÿ<[פלjÿ_ E¬ °2‎oדm0O'ֽײ€§{¡פֵ׃5ְ*bIח3Vד]<|‹X'}‏”ß2†•Ú<¼J¿¸»‰ץÚ®M¿װû‰ק¬X@<KץנחLl‰5nHI׃B«#so,nKײbםשB½X´ְZH¬נש0±±ֳLcwIו»­`cך¶Û–f½ש"VנםV—ב<•TM·Uת¿†x z1±׳HQB (!]1ֳֹ4· כ!צÊ¼ר±U;“ֲךֲ)C@ח¬₪ך‡ˆִF¿k‰¹אח¬s׃|ר[b;קlXI×Mh-Jק׳.bכÜ®pקְ÷ ר{b‡ֳJ×_h-!זµ~F|ƒ˜Ky~d±fױ½S
,©צ¡±¿ז­ִ£Cײ­›Vד^[RÝÝִֿפ‹5«RÚÛ]Iץן²JnMק5Gחֿ[Ijµִ³Yײ-dת¦’ךְֿ×ֲ’פgiIץ“½ֿ־'§S0y¬>b¿@I‘}{i^»¬¼ײyִRַS)©‏–Xױ–₪ֶ²ֳ£ֵ–d`%°תR—%©ס/EXךֹkֱ₪6׀KN)Êk`ֽ7°₪¦Yְה§¬
k׳d`eHh`I–CBI³ך5°JvX–₪כ1°ז¦›XqTRs†\.׳ְך₪˜»RK­ ְֻ2°₪¢טּk6ה5°+©y:¬ײרwIם4,4ל°₪ֲ‎GÙaIV«ֳ"¯ֹ{p×מכ)bגן”׳װp½ְ׃–¡2®ß.y/J’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’₪שuÚ<Y״µנ    IEND®B`‚‰PNG

   IHDR   –   –   <qג   sRGB ®־י   bKGD ÿ ÿ ÿ ½§“   	pHYs        tIMEÞ8hּ  xIDATxÚםÛ‹ױUַ?חxm4ׁׂPׂµ‚’׀ׂ©  ‚‚z»H]ˆ"ת₪z(ƒ
‚ט!*
ׂ4÷¨A”F׃J˜ּ|H¼3הefN¿‎c~חgע\ײZחûֽ9־ל‎ÛככZkןß¾€B!„B!„B!„B!„B!„ט,Jך‚×”9ְ, +•"gR9	פ#ך2	«V?פ·k€ְֵ|אR »ֶןG¿‎ְvאSא PQ—v6K€g“0*…2<זחסÊ״Û<“‏¶ט0Vח’FR©Tש¯ק;חR«ױÝסY |Xנ2ץˆחÿ–‘‚G{?ױ-r_J¸'ך•.TXשח™װˆUB^+K±ÞWeÿL6·׀Kױכ½6¥¶	§ln£˜־'²-2O^ksר«',¾%3שb­AOUֻs­—¹|°ר‡‰Mr¶«@ּfO×xֻ«l•Ùls30d0¯:_¾5Ü*ףÙוKb×U¶ֹ|6Ybt8‘Qb˜׳ו@ֲz¢נÝ׃r Rgטצ8ףTױ<׳^`†ּi‡+כ!†‡QBבÊפYֱח×״£«No°לp£ֳÜ×V®µJֲ²ֳ¢@a½Wֲ²ֳװ@ֲך’°ל׀HX'$,;üHX$,;”°$,y¬סשKֲ²ֳ¡פיyr%’ק"¬ֱפaכ”„e‡3Aע/pZֲ²ֳiאX€ח8&aIXֽ _ֲ²'¬~	Kֲjfעכ‎J–ז ׳xכׂ³HXFX\†ÿy¬Ùְ
	ֻw‡yÛן’°ל׀(Wל‘°ל0HX–~+ה)s¬ג³HX״ָcם°ל׀Gצ¾°הװkו»‹†_%,;vבû׳.אˆ„e‡!אû ׃;³S¬ְÿNטUQם.cd³׳Þ«B¶lVC”ƒ	כE‡	|¾₪ c—nF¯yףt6ײ‰HÞ**כסwיƒ2›}fûנsi_ÊCQ(¬Sְ7Nרנ-p\ֲעֱחÚת…‚;Ê±–F4@װ;¡§¦h‎x£א"²ק
…~*ּY#o׃d¯£°üכ;ֳ^9o׃־װV	ֻ;x¬¯•ûc£קךXLÜ‡׀הnÙkx4¸/rַ—ƒk£בüj£‏ßûep{Wק ׳ָ<¾ש;“¥y>–Ybx-kֲZ&³ִ`ƒqוuo9b±ַ€ַÚ-3ִ£§‰|¾Jt¡ּ“‡Ûט­מWקַe6נG½V^ֿ~‚¨V/וײqFW=´‚|ַ׀ִֿ÷HJֲ×B­]סP"ָ‎8ײרmC‎V|־vHV‹iC	KD³„ױÞC«˜Ü†:§HXס™Ù!uJX-f†„%a5ƒ…-ּ{ע:זKXסֳאrZwn‏Jחz(#Fy„צ­nxJÝנ4Ù®דv­ַ× /`ÿ<	Q½ְ£d/­,M>˜D¾MP»bp°5y¨גYV־ֽÞ<L—ÙלN!¬6ױ0¢ױmצֵv}•D6Wזl/’gÚFvײxF³~jr±­ƒdW< \!3·†iְd›Pÿ¦ת	ִo¦û‎x
יק’׀&ּ*אאC¯װ¨Py
x¸Er¸נÝc…]­׀ׁ	eלףN#ֻ«%“תCÝJא²{q<‡·f‰k¬ÛÜ₪PY{ףq²4דu₪Jם¾שxRsc‹€חֹNמ„¼©ש״Ù…O=(¨%ְ»c¦	ה¡דֱען)¥¸×5x“l׳ִװ09¼\QPeא9y§¶חa/ט%רÝix¬Êֶtֵ‰4בך–.אu|¼·כ₪?ןÿ·“\1ל:	ֹ¶ÛM“־oֶעÜYְ/)Y¬4±qaהֻ³₪‘cCֿ—hֶdÚ{•+qֽ%{©o5JׂÝN¬ÞfÙc­+x)y*ûם´־²°׀ח
‰&…U.‘Ü&סsָצ˜ײֵ©q
‏ֲ!ֹv3,
kzj>™I׳t5RXSׁ6&ֿL§ַ-5ZX׃d·L£/¨%,a^X“eק˜j­µ„ױ1L’]$¬f«KvqOֳl״ָֹּn²“כ־ֹ>.™ü@vv„B!„B!„B!„B!„B!„ה?V-¥sב°¯    IEND®B`‚‰PNG

   IHDR   2   2   ?ˆ±   sRGB ®־י   bKGD ÿ ÿ ÿ ½§“   	pHYs        tIMEÞ²D$}  ÚIDAThÞם™½JQ…¿ץ'‚´A´S“B«X"H´°DD|GנLamcai%Qװ &"¢¢–‘$»N …‰ûsw3	{`Ý¹³חÜÙ{ןּ.ִˆ#F/ֱ
)זעְ´\־אp4OL
¸×Bפ/«OJ«ˆ !dםBקך2Fז…˜דׁך23 ]d¢]f£כHֻ{oûָˆ-c׃&f3(2ְˆֿ׀’±ֻ„,Êj׳…<ˆס¬Aָk¿ש2דSƒQ1’„הִX׳ ₪d ֶ­!w-E¢W8ְ7p£בdO Nצ²ִPÝ µײ¶ֶ×I׀עאo™ג`²ך|סHÊÞL–א¦PˆhLטXj.׳‰-¾KZÛÝ‚‡VקXûW§ֿ£ֵ¼wבS	£ß6÷Z/Iעי*e¶ÿÛµְ°Vû²>ÜמZeא@סI`U¶Q'  kְT”2ְ)¿¥Ûֽ¶—¢±ױ>€3 ש$°!}ƒׂ~ִ•ה™c¦DlÊû¦€N‚*ְv׀ט0ׂnםH8yֶV„p›¡?b.ִzּoM˜ץ#dVבY5דGָ„B!דםnut%‹KֻOK¸&F1ת?ע_h…B}    IEND®B`‚‰PNG

   IHDR         ִי…c   sRGB ®־י   bKGD ÿ ÿ ÿ ½§“   	pHYs        tIMEÞ	¬2ׁ%  =IDATHַµ–ֱJ1†¿¬ׂ*jׁ“"ךֽƒxPh¯½׃—ס=תÚ“HAפT/ִ“¥«Uיצ2%v²Ù&‏ֲ־lזc&›ֹֲU€uאß$װ0¦ְּc±'Qָ™ּ¹ףÜt€ךר‚d%¶1ßˆ¿Ùק  ƒָ0°¬/1I ה#r"s®ר­‎4ע¸ס1›B -#ןy¿2וl¬Ýִdp«dcw)ÚJS9םv´Rץ¯¾ׂ»ת!‹³@ָ‎‚÷א!%d¥¢½²־R®gÙ³¥´\ ¯Jkwoְ%°r,־€k¹^‹}קIׁקפ$־Jpß:לתü °¼—”£*ְ־# ‘{ְ¦׃fּ’{י®ß vW÷I¯P7ְpּÿi0מ”Ef#‹P    IEND®B`‚GIF89a    ף  ÿÿÿ   ֶֶֶ„„„¶¶¶666VVV״״״ההה¼¼¼         !‏Created with ajaxload.info !ש 
   !ÿNETSCAPE2.0   ,         חָIia¥ךֽחbK…$F£RA”T²,¥2Sג*05//ֹm¢p!z“ֱּ0;$ֵ0C.I*!üHC(A@oƒ!39T5÷\ׁ8)¨‡ `ֱמ´²dwxG=Y
gƒwHb†vA=’0	V\\ˆ;	₪¥;¥×›H¨¢«¬³˜0¶µt%‘Hs‰‹rY<H.ֵ‰·–	¾’b¿ZbַOEg:˜GY].ְ=ÚAßOQs†ז ך\bֳh.9ל=sgץcףeג״*ײ†f 7D  !ש 
  ,         ךָIiY₪ךֽ§YF5Fװ¢Rֳ”TbG÷J…»ְל¬L×dבנ&…Ymxט”ֳ \@˜€ךֱ °1ü&Rƒ±’H
41Q´Û|V%zv#j0‡
lGg{0~‰<<	„[¢[‡h‘x¥¡G©
y©¢©§¯‡£¶–[0—÷¼G´‘¨¦P†zַhֹ¾¡ִ˜kzֲi—ֹy ׁÊh|zױh’GÝ„גVֵ¢¯י ם¹כ\hח[¯ן ַ₪ˆץ&•+‘ W7·8אָ! !ש 
  ,         מָI)1₪ךֽח1G5d]…(•¡Rַ²”T2”jL„{ֳ׃< [׀5אM¾א
0׀)…
 L¸₪I†נm…ֽE¨ַ`´p¥U
^f%‚^±ָהu;zz}0„X	
‰S0ewyk<%	O££‰“	‘¦z₪×{’­¬×|©×₪¶£¢%¹»½F¯i1ֲ”0ˆ‰€‡¦ֻ¼Y´›ֳ8ִx¿’	zֹ@‰‰׳<Ý« ג ֱַÞדט ¯¾ל8סחY<¯ךֹ¥8ף‡§\‡P$µ»¥!’ֱ  !ש 
  ,         חָI©¢ךֽgEU– ױ R‡a”TBÙ₪ב¾p>'¶•₪eץ$ˆ™"ˆ\‡#E1Cn€ִƒֹ~׳ױ J,Ü,Aa•×Uw^4I%PÝÞuQ33{0i1T…Ggwy}%ˆ%'R ¡	…=¡×¦§«£¥§3¶G–%¹”p÷½0¦
™³JRo…5ָ†0Iִ¦mykˆֳxֽTˆ_}ָ( …׳^גגyKsµלי>i_¨%ױמnת=ÙגÚÊq״4eֽ-Mֲ₪D  !ש 
  ,         מָI)*¨ךֽ')E¥d]•¦ֳPR	A”:!­ûzr’‚“bw“%6€"G₪(d$["‡’רJ±ְFh­aQP`p%ֲ†/BFP\cUג?T׀tW/pG&OtDa_sylD'M˜™q	tc˜¥¡¢™¦b ¢2±D“M:‘µ¹¹d¡ˆ%ְ¢4%s)ְ»‘uƒƒE3¸£ YU€‹ tÚ–זז‘ֶD$זJiMם<אYא;״°€˜d<“ O‚tXע<q'+B  !ש 
  ,         ףָIiR©ךֽ§"J% –¡¦EQZ×®ׂןLd’ק-Y®¦
ץh‚–kטQ¡|€„5בu¾4YרIƒ« NbW‡€u‘‡5›
מֱr‚צ	¬%yb>^%o/rvl9'L’“–—;†‡9—›“ ™…£”×9€%‹i9³³¨ C¹"†BB¹µDs־^Xf}$P	׳{LÞ?P Ê”›O4 ׳׀ְE׃ףו’›Vכ$¸זÊd J׀#)…pVְֲ$ !ש 
  ,         כָIiR©ךֽ§"J…d]• ¡RֲZN‰*P*«ב;ױ$P{*”N‚ְם\E׀ע!1UO2ÝD	™_r6Iצb
¡₪וװִH—8	B—;	²¬"'×ZÛÚt½’b€K#C'Kˆ‰w}?ˆ”‘’K•iz6 :x‚KAC¨¨&}9®tz\\¶¨×D5;x¹¨±Qd(ײ 	ֻ±KWײÊMBאֻˆI´יÚˆM=סֻ₪sרג¸½8Daƒ¡J`@LG !ש 
  ,         ןָIiR©ךֽ§"J…d]• ¡RֲZN‰*P*«ב;ױ$P{*”N‚ְם\E׀ע!1UO2ÝD	™_r6Iצb
¡₪וװִH—8	B—;	²¬"'×ZÛÚt½’b€K#C'Kˆ‰Gziz6ˆ8}z‰’”~›%X„K9ƒ:¢¨0}₪%	¨tz\B±₪lcL¢bQַׁ 	ֵˆ־ׁֵַ‰־ֲ Ýֵ³K־Þט¸ֵˆהלחׂיֵx־(ָ›PאX,ָיײ‚|/"  !ש 
  ,         נָIiR©ךֽ§"J…d]• ¡RֲZN‰*P*«ב;ױ$P{*”N‚ְם\E׀ע!1UO2ÝD	™_r6Iצb
¡₪וװִH—8	B—;	²¬"'×ZÛÚt½’b€K#C'Kˆ‰Gziz6ˆ8}z‰’”~›%…:„A/C}¦¦†u\¯³h}b¥¦Dֲֳ]=¦¨¨ײ 	ָV)׃ײ
Ú׃׀גָ9C׃דכDזKט ץם¼K¦…¢u	·ַ*00SÊtD  !ש 
 	 ,         כָIiR©ךֽ§"J…d]• ¡RֲZN‰*P*«ב;ױ$P{*”N‚ְם\E׀ע!1UO2ÝD	™_r6Iצb
¡₪וװִH—8	B—;	²¬"'×ZÛÚt½’b€K#C'Kˆ‰Gz‘‰z5
‘—“”˜C…:	„A/C}××†u\³·Eh}b©×6 ֵ[=×¥¸¥׳ Wx&)װ׳ׂI9ˆװ¬ ב@oCװךT?KÞֶי״dÛןע]רֱB7¡ְ ‚ 6׀«D !ש 
 
 ,         טָIiR©ךֽ§"J…d]• ¡RֲZN‰*P*«ב;ױ$P{*”N‚ְם\E׀ע!1UO2ÝD	™_r6Iצֶ€װִH—03×³”hױ¸ƒ›aְ—j U{CIkmbK#‡cK‘’€8	„{a’•8™n›•˜™“¥‡‚V:ˆ/q:M
¯¯Cu€~ ·¸‰Eh³k®¯6 	½ [_¯±ƒ6P</UÙYHF’ב9?Mֲ%
בGֻּיCבkף v¨Ùס>.]ת6©נ!‡)Vˆ  !ש 
  ,         נָIiR©ךֽ§"J…d]U¡RֲZN	ֳ”JְjרN2sK6
±›d‹I€ָ)
L״H°W¡G6	ÊKX¦ƒל ±’.6¢d°¨~z“hÙֲuur/6 X5ƒI;_†tO#E	{O››ˆ9V¢£9£¨4¡©±›—;V–C/
€¹6»ֳ˜~*½'ֳMo¸÷»€n־ַbXֲ:~]+V*ֽmוK_אOׁrK ס³N@. ך›ׁdש ~־q׀¦הD¢Bײ‹5D  ;         <div class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          
          
          <a class="navbar-brand" href="<?= FILENAME; ?>"><?= getConfigValue("title", "ownUnity");?></a>
          

        </div>
        <div class="navbar-collapse collapse">
          <ul class="nav navbar-nav">
          	<?php if(getS("user")!="") { ?>
          	   <li <?= ($_REQUEST["group"]=="" && $_REQUEST["view"]=="" ? 'class="active"' : '');?>><a href="<?= FILENAME;?>"><?= trans("ֳbersicht", "Home");?></a></li>
          	   
          	   <?php if(getConfigValue("enableGroups", "no")=="yes") {?>
		   <li <?= ($_REQUEST["view"]=="groups" ? 'class="active"' : '');?>><a href="<?= FILENAME;?>?view=groups"><?= trans("Gruppen", "Groups");?></a></li>
		   <?php } ?>
		   <li <?= ($_REQUEST["view"]=="search" ? 'class="active"' : '');?>><a href="<?= FILENAME;?>?view=search"><?= trans("Suchen", "Search");?></a></li>
		   
		   <?php if(getS("lastGroupID")!="") { ?>
		   	<li <?= (getS("lastGroupID")==$_REQUEST["group"] ? 'class="active"' : '');?>><a href="<?= FILENAME;?>?group=<?= getS("lastGroupID");?>"><?= getS("lastGroupName");?></a></li>
		   <?php } ?>
		   
                <?php } ?>
          </ul>
          <ul class="nav navbar-nav navbar-right">
            
            <?php if(getS("user")=="") { ?>
            	<?php if(getConfigValue("enableRegister", "yes")=="yes") {?>
            	<li><a href="<?= FILENAME;?>?do=register"><?= trans("Registrieren", "register");?></a></li>
            	<?php } ?>
            	<li><a href="<?= FILENAME;?>?do=login"><?= trans("Anmelden", "login");?></a></li>
            <?php } else { ?>
            		
            
            	<?php $profil = new profile();
            	      $inq = $profil->getInquiriesForMe();
            	      
            		if($inq!=array()) { ?>
            		<li class="dropdown">
            			<a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class='glyphicon glyphicon-info-sign'></i> <b class="caret"></b></a>
            			<ul class="dropdown-menu">
            				<?php for($i=0;$i<count($inq);$i++) { ?>
            					<li>
            						<a href='<?= FILENAME;?>?profile=<?= $inq[$i]["key"];?>'><span style='font-size:0.7em;'><?= trans("Verbindungsanfrage", "connectionrequest");?><br/></span><?= $inq[$i]["name"];?></a>
            					</li>
            				<?php } ?>
            			</ul>
            		</li>
            	<?php } ?>
            
            	   <li style="height: 30px;">
            	   	<a href='<?= FILENAME;?>?view=profil' style="padding-top:13px;padding-right:0;"><img src='<?= FILENAME;?>?action=getProfileImage&size=mini' width=25 height=25 style="border-radius: 5px;border: solid 1px gray;" /></a>
            	   </li>
            
		    <li class="dropdown">
		      <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?= $profilData["name"];?> <b class="caret"></b></a>
		      <ul class="dropdown-menu">
			 <li><a href="<?= FILENAME;?>?view=profil"><?= trans("Profil", "Profile");?></a></li>
			 <li><a href="<?= FILENAME;?>?view=contacts"><?= trans("Kontakte", "Contacts");?></a></li>
                  <li><a href="<?= FILENAME;?>?view=notifications"><?= trans("Mitteilungen", "Notifications");?></a></li>
			 <?php if($_COOKIE[SESSKEY."language"]=="de") { ?>
			 	<li><a href="<?= FILENAME;?>?lang=en">english</a></li>
			 <?php } else { ?>
			 	<li><a href="<?= FILENAME;?>?lang=de">deutsch</a></li>
			 <?php } ?>
			 <li class="divider"></li>
			<li><a href="<?= FILENAME;?>?do=logout"><?= trans("Abmelden", "logoff");?></a></li>
		      </ul>
		    </li>
            <?php } ?>
            
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </div>
<style>
body {
  padding-top: 80px;
  padding-bottom: 40px;
  background-color: #eee;
}

.form-signin {
  max-width: 430px;
  padding: 15px;
  margin: 0 auto;
}
.form-signin .form-signin-heading,
.form-signin .checkbox {
  margin-bottom: 10px;
}
.form-signin .checkbox {
  font-weight: normal;
}
.form-signin .form-control {
  position: relative;
  font-size: 16px;
  height: auto;
  padding: 10px;
  -webkit-box-sizing: border-box;
     -moz-box-sizing: border-box;
          box-sizing: border-box;
}
.form-signin .form-control:focus {
  z-index: 2;
}
.form-signin input[type="text"] {
  margin-bottom: -1px;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
}
.form-signin input[name="name"] {
  margin-bottom: 10px;
  border-top-left-radius: 0;
  border-top-right-radius: 0;
}
.form-signin input[name="password"] {
  margin-bottom: -1px;
  border-radius: 0;
  border-radius: 0;
}
.form-signin input[name="password2"] {
  margin-bottom: -1px;
  border-radius: 0;
  border-radius: 0;
}
</style>

<?php if(isset($REG)) { ?>
	
	<div class="form-signin">
		<?= trans("Vielen Dank!<br/>Sie sind nun registriert und eingeloggt.", "Thanks!<br/>You are now registered and logged in.");?>
		<br/><br/>
		<a href='<?= FILENAME;?>?view=profil'><?= trans("weiter...", "next...");?></a>
	</div>
	

<?php } else { ?>
	<form class="form-signin" method="post">
	
		<?php
		if(isset($ERR)) { echo "<div>".$ERR."</div>"; }
		?>
		
		<input type="hidden" name="send" value="do" />
		<h2 class="form-signin-heading"><?= trans("Bitte registrieren Sie sich", "Please register");?></h2>
		
		<input type="text" name="email" class="form-control" placeholder="<?= trans("E-Mail-adresse", "email address");?>" autofocus>
		<input type="password" name="password" class="form-control" placeholder="<?= trans("Password", "password");?>">
		<input type="password" name="password2" class="form-control" placeholder="<?= trans("Password wiederholung", "repeat password");?>">
		<input type="text" name="name" class="form-control" placeholder="<?= trans("Anzeigename", "display name");?>" autofocus>
		
		<button class="btn btn-lg btn-primary btn-block" type="submit"><?= trans("Registrieren", "register");?></button>
	</form>
<?php } ?>      
<style>
body {
  padding-top: 80px;
  padding-bottom: 40px;
  background-color: #eee;
}

.form-signin {
  max-width: 430px;
  padding: 15px;
  margin: 0 auto;
}
.form-signin .form-signin-heading,
.form-signin .checkbox {
  margin-bottom: 10px;
}
.form-signin .checkbox {
  font-weight: normal;
}
.form-signin .form-control {
  position: relative;
  font-size: 16px;
  height: auto;
  padding: 10px;
  -webkit-box-sizing: border-box;
     -moz-box-sizing: border-box;
          box-sizing: border-box;
}
.form-signin .form-control:focus {
  z-index: 2;
}
.form-signin input[type="text"] {
  margin-bottom: -1px;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
}
.form-signin input[name="password"] {
  border-radius: 0;
  border-radius: 0;
}

</style>


<form class="form-signin" method="post">

	<?php
	if(isset($ERR)) { echo "<div>".$ERR."</div>"; }
	?>
	
	<input type="hidden" name="send" value="do" />
	<h2 class="form-signin-heading"><?= trans("Bitte melden Sie sich an", "Please login");?></h2>
	
	<input type="text" name="email" class="form-control" placeholder="<?= trans("E-Mail-adresse", "email address");?>" autofocus>
	<input type="password" name="password" class="form-control" placeholder="<?= trans("Password", "password");?>">
	
	<button class="btn btn-lg btn-primary btn-block" type="submit"><?= trans("Anmelden", "login");?></button>
</form>

<p>

</p>
<p>

</p>
<div style='float:right;'>
	<a href='<?= FILENAME;?>?lang=de'>[de]</a>
	<a href='<?= FILENAME;?>?lang=en'>[en]</a>
</div>
<h1><?= getConfigValue("pagetitle", "My own hosted community");?></h1>

<?php if($_COOKIE[SESSKEY."language"]=="de") { ?>
    <?php
				if(!is_array($tempfn)) $tempfn = array();
				$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php"; 
				file_put_contents($fn, getRes("templates/info_de.tpl")); 
				include $fn;
				unlink(array_pop($tempfn));
				?>
<?php } else { ?>
    <?php
				if(!is_array($tempfn)) $tempfn = array();
				$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php"; 
				file_put_contents($fn, getRes("templates/info_en.tpl")); 
				include $fn;
				unlink(array_pop($tempfn));
				?>
<?php } ?>


<script>
    setTimeout(function() {
        $.ajax({
            "url": "http://www.databay.de/kmco/log.php?version=<?= appVersion;?>"
        });
    }, 1000);
</script><h1><?= trans("Profil", "Profile");?></h1>

<?php
$profil = new profile();
$data = $profil->get(me());
?>

<form class="form-horizontal" role="form" method="post" enctype="multipart/form-data">
	
  <input type="hidden" name="action" value="saveprofil" />
	
  <div class="form-group">
    <label for="inputNick" class="col-sm-2 control-label"><?= trans("Name", "Nickname");?></label>
    <div class="col-sm-5">
      <input type="text" class="form-control" id="inputNick" name="nickname" value="<?= htmlspecialchars($data["name"]);?>" placeholder="<?= trans("Dein Name", "Your name");?>">
    </div>
    <div class="col-sm-5">
        <?= trans("Dein Name erscheint bei allen Posts die Du machst. Auֳerdem wird er angezeigt, wenn ein Nutzer nach Deinen Suchbegriffen sucht und Deinen Eintrag findet. Wֳ₪hle also einen Namen, der Deinen Freunden, Kollegen und sonstigen Kontakten etwas sagt.", "Your name appears in all the posts you make. He will also appear when a user searches for your keywords and place your entry. So choose a name that your friends, colleagues and other contacts says something.");?>
    </div>
  </div>

  <div class="form-group">
    <label for="inputProf" class="col-sm-2 control-label"><?= trans("Spezialgebiet", "Profession");?></label>
    <div class="col-sm-5">
      <input type="text" class="form-control" id="inputProf" name="profession" placeholder="<?= trans("Dein Spezialgebiet / Dein Hobby", "Your profession / your hobby");?>" value="<?= htmlspecialchars($data["profession"]);?>">
    </div>
      <div class="col-sm-5">
          <?= trans("Dein Spezialgebiet wird zusammen mit Deinem Namen oder Deinem Bild angezeigt. Es sollte etwas ֳ¼ber Dich aussagen, von dem Du mֳ¶chtest, dass Andere Dich so sehen.", "Your profession will be displayed along with your name or your image. It should say something about you, from which you want that others see you as."); ?>
      </div>
  </div>

  <div class="form-group">
    <label for="inputKeyword" class="col-sm-2 control-label"><?= trans("Suchbegriff", "Keyword");?></label>
    <div class="col-sm-5">
      <input type="text" class="form-control" id="inputKeyword" name="keyword" placeholder="<?= trans("Suchbegriffe unter dem Du gefunden werden willst (Leerzeichen separiert)", "Keywords to find your profile (space seperated)");?>" value="<?= htmlspecialchars($data["keyword"]);?>">
    </div>
      <div class="col-sm-5">
          <?= trans("<b>Dies ist eines der wichtigsten Profil-Felder.</b> Trage hier mit Leerzeichen getrennt die Begriffe ein, unter denen Du gefunden werden willst. Das kann Dein Name und auch Deine E-Mailadresse sein. Es kann aber auch ein Phantasie-Begriff sein, den Du nur Deinen besten Freunden nennst. Die anderen Felder, wie Name, Spezialgebiet und E-Mailadresse werden nicht fֳ¼r die Personensuche verwendet.", "<b>This is one of the most important custom profile fields.</b> Insert here with a space separated the terms under which you want to be found. This can be your name and your email address. However, it can also be a fancy term that you call only your best friends. The other fields, such as name, specialty and e-mail address will not be used for people search."); ?>
      </div>
  </div>
  

  <div class="form-group">
    <label for="inputEmail" class="col-sm-2 control-label"><?= trans("E-Mail", "Email");?></label>
    <div class="col-sm-5">
      <input type="email" class="form-control" id="inputEmail" name="email" placeholder="<?= trans("Deine E-Mailadresse", "Your emailaddress");?>" value="<?= htmlspecialchars($data["email"]);?>">
    </div>
      <div class="col-sm-5">
          <?= trans("Trage hier Deine E-Mailadresse ein, wenn Du die Passwort-Vergessen-Funktion nutzen mֳ¶chtest. Du kannst auch Benachrichtigungen einstellen, die Dir per E-Mail signalisieren, dass eine neue Nachricht eingegangen ist. Diese Feld kannst Du aber auch leer lassen.", "Please write your e-mail address if you would like to use the forgotten password feature. You can also set alerts that signal you by e-mail that a new message has arrived. You can also leave this field empty."); ?>
      </div>
  </div>
  

  <div class="form-group">
    <label for="inputPic" class="col-sm-2 control-label"><?= trans("Bild", "Picture");?></label>
    <div class="col-sm-5">
      <input type="file" class="xform-control" id="inputPic" name="inputPic">
      <?php if(isfilled($data["profileimage"])) { ?>
      	<br/>
      	<input type="checkbox" value="1" name="removePic" /> <?= trans("Bild entfernen", "remove pic");?>
      <?php } ?>

    </div>
    <div class="col-sm-5">
    	<?php if(isfilled($data["profileimage"])) { ?>
    		<img src='<?= FILENAME;?>?action=getProfileImage&size=medium' />
    	<?php } ?>
    
    </div>
  </div>
    
  
<div class="form-group">
    <div class="col-sm-offset-2 col-sm-10">
      <button type="submit" class="btn btn-default"><?= trans("speichern", "save changes");?></button>
    </div>
  </div>  
</form>
<h1 style='float:left;'><?= trans("Suchen", "Search");?></h1>

<?php
/*
$profil = new profile();
$profil->autoConnectSameDomain();
*/
?>

<hr />

<div style='padding:10px;' id="newgroupform">

<form role="form" method="post">
	<div class="form-group">
		<label for="searchquery"><?= trans("Suchbegriff", "Search for");?></label>
		<input type="text" class="form-control" id="searchquery" name="searchquery" value="<?= $_REQUEST["searchquery"];?>" placeholder="<?= trans("", "");?>">
	</div>
	
	<button type="submit" class="btn btn-default"><?= trans("suchen", "search");?></button>
</form>
<br/><br/><br/>
</div>

<?php

if(isfilled($_REQUEST["searchquery"])) {

	$P = new profile();
	$U = $P->search($_REQUEST["searchquery"]);
#vd($U);
	?>
	
	<ul class="list-group">
	
	<?php for($i=0;$i<count($U);$i++) { ?>
	
	  <a href="<?= FILENAME;?>?profile=<?= $U[$i]["key"];?>" class="list-group-item">
		
	  	<div style="float:left;margin:0 5px 5px 0;min-width:75px;">	
	  	<img src='<?= $U[$i]["img"]?>' />
	  	</div>
	  
	    <span class="badge"><?= (int)$U[$i]["counts"]["posts"]; ?> / <?= (int)$U[$i]["counts"]["comments"]; ?> / <?= (int)$U[$i]["counts"]["replies"]; ?></span>
	    <h4 class="list-group-item-heading"><?= $U[$i]["name"]?></h4>
	    <p class="list-group-item-text">
		<?= $U[$i]["profession"]?>
		<div style='clear:both;'></div>
	    
	    </p>
	  </a>
	
		
	<?php } ?>
	</ul>

<?php } ?>
<h1 style=''><?= trans("Kontakte", "Contacts");?></h1>

<?php
$P = new profile();
$U = $P->getContacts();

//vd($C);
?>


<ul class="list-group">
	
	<?php for($i=0;$i<count($U);$i++) { ?>
	
	  <a href="<?= FILENAME;?>?profile=<?= $U[$i]["key"];?>" class="list-group-item">
	  
	  <?php 
	  
	  $img = $P->getImageBase64($U[$i], 'small');
	  ?>
	  	<div style="float:left;margin:0 5px 5px 0;min-width:75px;">
	  		<img src='<?= $img;?>'   />
	  	</div>
	  
	    <span class="badge"><?= (int)$U[$i]["counts"]["posts"]; ?> / <?= (int)$U[$i]["counts"]["comments"]; ?> / <?= (int)$U[$i]["counts"]["replies"]; ?></span>
	    <h4 class="list-group-item-heading"><?= $U[$i]["name"]?></h4>
	    <p class="list-group-item-text">
		<?= $U[$i]["profession"]?>
		<div style='clear:both;'></div>
	    
	    </p>
	  </a>
	
		
	<?php } ?>
	</ul>
<h1 style='float:left;'><?= trans("Gruppen", "Groups");?></h1>

<div style='float:right;margin-top:20px;'>
<a href="#" onclick="$('#newgroupform').slideDown();return false;"><i class="glyphicon glyphicon-plus-sign" style="font-size:25px;"></i><span style="font-size:25px;">&nbsp;<?= trans("Neue Gruppe anlegen","create new group");?></span></a>
</div>

<hr />

<div style='display:none;padding:10px;' id="newgroupform">

<form role="form" method="post">
	<input type="hidden" name="action" value="newgroup" />

	<div class="form-group">
		<label for="groupname"><?= trans("Name der Gruppe", "Group-name");?></label>
		<input type="text" class="form-control" id="groupname" name="groupname" placeholder="<?= trans("Bitte einen Gruppen-Namen angeben", "Please enter a group-name");?>">
	</div>
	
	<button type="submit" class="btn btn-default"><?= trans("Gruppe anlegen", "Create group");?></button>
</form>
<br/><br/><br/>
</div>

<?php
$groups = new groups();
$G = $groups->getAll();
$profil = new profile();
?>

<ul class="list-group">

<?php for($i=0;$i<count($G);$i++) { ?>

  <a href="<?= FILENAME;?>?group=<?= $G[$i]->data["id"];?>" class="list-group-item">
  
    <span class="badge"><?= (int)$G[$i]->data["posts"]; ?></span>
    <h4 class="list-group-item-heading"><?= $G[$i]->data["name"]?></h4>
    <p class="list-group-item-text">
    	
    	<?php 
    	for($j=0;$j<count($G[$i]->data["members"]);$j++) { 
		$sender = $profil->get($G[$i]->data["members"][$j]);
		$img = $profil->getImageBase64($sender, 'small');
		?>
		<img src='<?= $img;?>' style="margin:0 5px 5px 0;border-radius:5px;float:right;" title='<?= $sender["name"]; ?>' />
		<?php
	}
    	?>
    	<div style='clear:both;'></div>
    
    </p>
  </a>

	
<?php } ?>
</ul>

<h1><?= trans("Mitteilungen", "Notifications");?></h1>


<div class="panel panel-primary">

    <div class="panel-heading">RSS-Feed</div>

    <div class="panel-body">

        <?php
        if($_GET["create"]==1) {
            $profilData["feedKey"] = $profil->createFeedKey();
            #vd($profileData);
        }
        ?>

        <?php if(!isset($profilData["feedKey"]) || $profilData["feedKey"]=="") { ?>

            <a href="<?= FILENAME;?>?view=notifications&create=1"><?= trans("Feed-Link erzeugen", "create feed-link");?></a>

        <?php } else { ?>


            RSS 2.0: <a href="<?= "http://".$_SERVER['HTTP_HOST'].$_SERVER["SCRIPT_NAME"];?>?feed=<?= $profilData["feedKey"];?>"><?= "http://".$_SERVER['HTTP_HOST'].$_SERVER["SCRIPT_NAME"];?>?feed=<?= $profilData["feedKey"];?></a>

            <br><br>
            <a href="<?= FILENAME;?>?view=notifications&create=1" onclick="return confirm('<?= trans('macht bisherige Feed-Links ungֳ¼ltig!', 'revokes older feed-links');?>');"><?= trans("neuen Feed-Link erzeugen", "create new feed-link");?></a>

        <?php } ?>

        <?php
        #vd($_SERVER);
        #vd($profilData);
        ?>


    </div>

</div>
<script>
var whichView = "<?= $_REQUEST["group"];?>";
</script>
<?php
$group = new group("", $_REQUEST["group"]);
$profil = new profile();
#vd($group);
setS("lastGroupID", $_REQUEST["group"]);
setS("lastGroupName", $group->data["name"]);
?>


<div class="row">
	<div class="col-md-4">
	
		<div style="background-color: #fbf9f6;border: solid 1px #ececec;padding: 10px;">
		<div style='padding: 10px;background-color: #800000;color:white;'>
			<h3 style="margin:0;"><?= trans("Gruppe", "Group");?>:</h3>
			<h2 style="margin:0;"><?= $group->data["name"];?></h2>
		</div>
		</div>

		

	
		<div style="background-color: white;border: solid 1px #ececec;">
			<div style="padding: 10px;">
				<?php 
				$meMember = false;
				for($j=0;$j<count($group->data["members"]);$j++) {
					if($group->data["members"][$j]==me()) $meMember = true;
					$sender = $profil->get($group->data["members"][$j]);
					$img = $profil->getImageBase64($sender, 'small');
					?>
					<img src='<?= $img;?>' style="margin:0 5px 5px 0;border-radius:5px;float:left;" title='<?= $sender["name"]; ?>' />
					<?php
				}
				?>
				<div style='clear:both;'></div>
				<?php if($meMember==false) { ?>
					<a href='<?= FILENAME;?>?group=<?= $_REQUEST["group"];?>&action=becomemember' class='btn btn-default'><?= trans("Gruppe beitreten", "become member");?></a>
				<?php } ?>
			</div>
			<hr/>
			
			<hr/>
			<div style='padding: 10px;'>
				<form method="post" onsubmit="sendnewpost(this);return false;">
				<input type="hidden" name="action" value="newpost" />
				<input type="hidden" class="group" value="<?= $_REQUEST["group"];?>" />
				<textarea id="newposttext" name="newposttext" class="form-control newposttext" placeholder="<?= trans("Neuer Eintrag in dieser Gruppe", "new post into this group");?>"></textarea>
				<button class="btn btn-success btn-xs"><?= trans("absenden", "send post");?></button>
				</form>
			</div>
			
			
			
			<?php if(getConfigValue("enableTrees", "yes")=="yes") {?>
			<hr />
			<div style='float:right;padding:5px 5px 0 0;font-size:0.8em;' class="edittreelink">
				<a href='#' onclick="edittree();return false;"><i class='glyphicon glyphicon-pencil'></i>&nbsp;<?= trans("bearbeiten", "edit");?></a>
			</div>
			<div style='float:right;padding:5px 5px 0 0;font-size:0.8em;display:none;' class="savetreelink">
				<a href='#' onclick="savetree();return false;"><i class='glyphicon glyphicon-floppy-disk'></i>&nbsp;<?= trans("speichern", "save");?></a>
			</div>
			<div class="tree" id="grouptree">	
			<?php
			$tree = new tree($_REQUEST["group"]);
			echo $tree->getTree();
			?>
			</div>
			<script>
			var optionTREE = <?= json_encode($tree->getTreeOptionArray()); ?>;
			</script>
			
			
			<div id="edittreeform" style="padding:10px;display:none;">
				<form onsubmit="addTreeEntry(this);return false;">
				<input type="text" class="form-control" id="edittreenewfolder" placeholder="<?= trans('neuer Bereich', 'new folder');?>"/>
				</form>
			</div>
			<?php } ?>
			
			
			
			
		</div>
		
	</div>
	<div class="col-md-8">
		<div style="background-color: #fbf9f6;border: solid 1px #ececec;">
			<div style="padding: 10px;">
			
				<div style='padding: 10px;background-color: #800000;color:white;'>
					<?= trans("Eintrֳ₪ge", "Posts");?>
				</div>
			
				<?php
				$posts = new posts();
				$P = $posts->recent($_REQUEST["group"]);
				?>
				
<?php				
$profil = new profile();
?>
<?php if(isset($P[0])) { ?>
<script>
lastChange = <?= $P[0]->data["ts"]; ?>;
</script>
<?php } ?>
<div id='postlist'>

<?php for($i=0;$i<count($P);$i++) {?>
	
	
	<?php
	$sender = $profil->get($P[$i]->data["user"]);
	$sender["smallimage"] = $profil->getImageBase64($sender, 'small');
	//vd($P[$i]);
	?>
	<div class='post' id='<?= htmlid($P[$i]->data["id"]); ?>' rel='<?= $P[$i]->data["id"]; ?>' style='cursor:pointer;padding: 10px;margin-top:10px;background-color: #ffffff;color:#494949;border: solid 1px #ececec;' rel=''
		xonclick="if($(this).attr('rel')=='') {$(this).attr('rel', 'locked'); $(this).find('.functions').slideDown();} else if($(this).attr('rel')=='*') $(this).attr('rel', ''); ">
	
		<div style="max-height:150px;overflow:hidden;" onclick="expand(this);return false;$(this).closest('.post').find('.functions').slideToggle();" class='outerContent'>
			<img src='<?= $sender["smallimage"];?>' width=50 height=50 style="float:left;border-radius: 10px;margin-right:10px;">
		
			<div style='text-align:right;float:right;margin-left: 10px;' class="treeeditlink">
				<span style='font-size:0.8em;'><?= formatDateHuman($P[$i]->data["data"]["date"]); ?></span>
				&nbsp;
				<a href='#' onclick="if(confirm('<?= trans("Diesen Beitrag lֳ¶schen?\\nEr wird nur in Deiner Anzeige entfernt.\\nAndere Empfֳ₪nger kֳ¶nnen ihr weiterhin sehen." ,"Really delete this post?\\nIt will only disappear your view.\\nOther recipients can still see this post.");?>')) {
					window.location='<?= FILENAME;?>?action=delete&id=<?= $P[$i]->data["id"]; ?>'; 
				} return false;" style="color:gray;"><i class="glyphicon glyphicon-remove-circle"></i></a>
				
				<br>	
				<div style='float:right;font-size:0.8em;max-width:200px;'>sichtbar fֳ¼r:
				<?php
				/*
				for($ri=0;$ri < count($P[$i]->data["data"]["recipientNames"]);$ri++) {
					$P[$i]->data["data"]["recipientNames"][$ri] = "<a href='".FILENAME."?profile=".$P[$i]->data["data"]["recipients"][$ri]."'>".$P[$i]->data["data"]["recipientNames"][$ri]."</a>";
				}
				*/
				echo implode(", ", array_merge(array("mich"), $P[$i]->data["data"]["recipientNames"]));
				?>
				</div>
				<div style='clear:both;'></div>
			</div>

			<div style=''>
				<div class='innerContent'>
					<span style='color: #800000;font-weight: bold;'><?= $sender["name"]; ?></span><br/>
					<?= prepareText($P[$i]->data["data"]["text"], $P[$i]); ?>
					
					<?php
                    /*
					$img = $P[$i]->getImages();
					
					if(count($img)>0) { ?>
						<div style='min-height:100px;text-align:center;' id='img_<?= htmlid($P[$i]->data["id"]); ?>'></div>
						<script>
						$(function() {
							openImagePreview('img_<?= htmlid($P[$i]->data["id"]); ?>', '<?= $P[$i]->data["id"];?>');
						});
						</script>
					<?php }
                    */
                    ?>
					
					
				</div>
			</div>
			
			<div style="clear:both;"></div>
		</div>
		
		<div style='display:none;padding-left:58px;padding-top:5px;' class='functions'>
			
			<div style='float:left;'>
				<!--
				<a href='#' onclick="$(this).closest('.functions').slideUp();$(this).closest('.post').attr('rel', '*');return false;"><?= trans("verstecken", "collapse");?></a>
				-->
				<?php if(getConfigValue("enableTrees", "yes")=="yes") {?>
				<div style='float:left;' class="treeeditlink">
					<a href='#' onclick="editpostlink(this, '<?= $P[$i]->data["id"]; ?>');return false;"><i class="glyphicon glyphicon-list"></i>&nbsp;<?= ($P[$i]->tree["title"]!="" ? $P[$i]->tree["title"] : '<span style="color:silver;">'.trans('keine Zuordnung', 'not in tree').'</span>');?></a>
				</div>
				<?php } ?>
				
				
			</div>
			<div style='float:right;'>
				
				<a href='<?= FILENAME;?>?full=<?= $P[$i]->data["id"]; ?>'><i class='glyphicon glyphicon-fullscreen'></i>&nbsp;<?= trans("vollstֳ₪ndige Ansicht", "full view");?></a>
				<?php if($P[$i]->data["data"]["newformcommenttype"]!='keine' || $P[$i]->data["user"]==me() ) { ?>
				<!--
				&nbsp;&nbsp;&nbsp;
				<a href='#' onclick="openreply(this);$(this).hide();return false;"><i class='glyphicon glyphicon-comment'></i>&nbsp;<?= trans("kommentieren", "add comment");?></a>
				-->
				<?php } ?>
				
				<?php /*
				<!--
				&nbsp;&nbsp;
				<a href='#' onclick="return false;"><i class="glyphicon glyphicon-star-empty"></i>&nbsp;<?= trans("Beobachten", "favorite");?></a>
				-->
				<!-- <i class="glyphicon glyphicon-star"></i> -->
				*/ ?>
			</div>
			<div style='clear:both;'></div>
			
			<?php if($P[$i]->data["data"]["newformcommenttype"]!='keine' || $P[$i]->data["user"]==me() ) { ?>
			<div class='replydiv' rel="<?php
				if($P[$i]->data["user"]==me()) echo "allemeine";
				else echo $P[$i]->data["data"]["newformcommenttype"];
			?>"></div>
			<?php } ?>
			
		</div>
		
	</div>
	<?php if($P[$i]->data["data"]["newformcommenttype"]!='keine' || $P[$i]->data["user"]==me() ) { ?>
	<div style='padding-left:70px;' id='comments<?= htmlid($P[$i]->data["id"]); ?>'>
		<?php
		$comments = $P[$i]->getComments();
		for($j=0;$j<count($comments);$j++) {
			$senderC = $profil->get($comments[$j]->data["user"]);
			$senderC["miniimage"] = $profil->getImageBase64($senderC, 'mini');
		?>
			<div style='margin-bottom:1px;border-left:solid 1px #ececec;border-bottom:solid 1px #ececec;border-right:solid 1px #ececec;padding:10px;font-size:0.8em;background-color:#f6f6f6;'>

				<img src='<?= $senderC["miniimage"];?>'  width=25 height=25 style="float:left;border-radius: 5px;margin-right:10px;">
				
				<div style='float:right'><?= formatDateHuman($comments[$j]->data["data"]["date"]); ?></div>
				<div style='float:left;'>
					<span style='color: #800000;font-weight: bold;'><?= $senderC["name"]; ?></span><br/>
					<?= $comments[$j]->data["data"]["text"];?>
				</div>
				<div style='clear:both;'></div>
			
			</div>
		<?php } ?>
	</div>
	<?php } ?>
	

	
	



<?php } ?>
</div>

<script>


$(function() {
    setTimeout(function() {
	$('.outerContent').each(function() {
		var diff = $(this).find('.innerContent').height()-$(this).height();
		if(diff<30) {
			$(this).css("max-height", "");
		} else {
			var id = $(this).closest('.post').attr("rel");
			var alles = '<div style="border-top:solid 1px gray;text-align:center;" class="alles">';
			alles += '<div style="margin-top:2px;border-top:solid 1px gray;"></div>';
			alles += '<a href="#" onclick="expand(this);return false;"><i class="glyphicon glyphicon-resize-vertical"></i><?= trans("alles anzeigen", "expand");?></a>';
			alles += '&nbsp;&nbsp;&nbsp;'; 
			alles += '<a href="<?= FILENAME;?>?full='+id+'"><i class="glyphicon glyphicon-fullscreen"></i><?= trans("vollstֳ₪ndige Ansicht", "full view");?></a>';
			alles += '</div>';
			$(this).after(alles);
		}
	});
    }, 1000)
});

var lastObj;
var expandBig = true;
function expand(obj) {
	lastObj = obj;
	var istH = $(obj).closest(".post").find(".outerContent").height();
	var sollH = $(obj).closest(".post").find(".outerContent").find(".innerContent").height();
	
	$(obj).closest('.post').find('.functions').slideToggle();


	openreply(obj);
		
	//console.log([istH, sollH]);
	if(sollH<istH) return;

	
	if(istH<sollH) {
		$(obj).attr("rel", istH);
		expandBig = true;
		$(obj).closest(".post").find(".alles").slideUp(function() { 
			//$(this).remove();
			
		});
		
	} else {
		sollH = $(obj).attr("rel");
		expandBig = false;
		$(obj).closest(".post").find(".outerContent").css("max-height", istH);
		$(obj).closest(".post").find(".alles").slideDown(function() { 
			//$(this).remove();
			 
		});
		
	}
	
	
	 $(obj).closest(".post").find(".outerContent").animate({
	 "max-height": sollH
	 }, 1000, function() {
	 	//$(lastObj).closest(".post").find(".outerContent").css("max-height", "");
	 	if(expandBig) $(this).css("max-height", "");
	 	
	});	
	
	
}

var lastObj = "";
function openreply(obj) {
	lastObj = obj;	
	var html = $('#replyform').html();
	html = html.replace("#ID#", $(obj).closest(".post").attr("rel"));
	//$(obj).closest(".functions").find(".replydiv").html( html );
	//setTimeout(function() { $(lastObj).closest(".functions").find('.commenttext').focus(); }, 100);
	var rel = $(obj).closest(".post").find(".replydiv").attr("rel");
	if(rel=="allemeine") rel = "<?= trans('alle ursprֳ¼nglichen Empfֳ₪nger dieser Nachricht', 'all recipients of the post');?>";
	else if(rel=="bekannte") rel = "<?= trans('alle Empfֳ₪nger dieses Posts, die auch mit mir verbunden sind', 'all reipients of this post, who are connected with me');?>";
	else if(rel=="bidrektional") rel = "<?= trans('nur der Autor des Posts', 'only the author');?>";
	html = "<span style='font-size:0.8em;'><b><?= trans('Wer sieht diesen Kommentar:', 'Who sees this comment:');?></b> "+rel + "</span><br>"+html;
	$(obj).closest(".post").find(".replydiv").html( html );
	setTimeout(function() { $(lastObj).closest(".post").find('.commenttext').focus(); }, 100);
}
</script>

<script type="text/plain" id="replyform">

<div style='padding-top:10px;'>
	<form method="post">
		<input type="hidden" name="action" value="newcomment" />
		<input type="hidden" name="id" value="#ID#" />
		<input type="text" class="form-control commenttext" name="replytext"  placeholder="<?= trans("einen Kommentar auf diesen Beitrag hinterlassen", "comment on this post");?>" />
	</form>
</div>

</script>

<script>
var recentPostID = "<?= $P[count($P)-1]->data["id"];?>";
</script>
<center>
<button onclick="getOlderPosts();return false;" class="btn btn-default"><i class='glyphicon glyphicon-download'></i> <?= trans("ֳ₪ltere Beitrֳ₪ge laden", "load older posts");?> <i class='glyphicon glyphicon-download'></i></button>
</center>



				
				
			</div>
		</div>
	</div>
</div>


<?php
$P = new profile();
$U = $P->get($_REQUEST["profile"]);
#vd($U);
?>

<img src='<?= $P->getImageBase64($U, "medium");?>' style="float:right;margin:0 0 10px 10px;">
<h1><?= $U["name"]; ?></h1>
<h2><?= $U["profession"]; ?></h2>

<div class="row">
  	<div class="col-md-6">
	  
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h3 class="panel-title"><?= trans("Nachrichten", "Posts");?></h3>
			</div>
			<div class="panel-body">
				<?= (int)$U["counts"]["posts"];?>
			</div>
		</div>
		<div class="panel panel-success">
			<div class="panel-heading">
				<h3 class="panel-title"><?= trans("Kommentare", "Comments");?></h3>
			</div>
			<div class="panel-body">
				<?= (int)$U["counts"]["comments"];?>
			</div>
		</div>
		<div class="panel panel-info">
			<div class="panel-heading">
				<h3 class="panel-title"><?= trans("Antworten", "Replies");?></h3>
			</div>
			<div class="panel-body">
				<?= (int)$U["counts"]["replies"];?>
			</div>
		</div>

	</div>
	<div class="col-md-6">
		<br/><br/><br/>
		<?php
		$status = $P->getStatusBetweenMe($_REQUEST["profile"]);
		if($status===false) { ?>
			<?= trans("keine Verbindung", "no connection"); ?><br>
			<br/>
			<a href='<?= FILENAME;?>?profile=<?= $_REQUEST["profile"];?>&action=requestConnection' class="btn btn-default"><?= trans("Verbindung anfragen", "request connection");?></a>
			
		<?php } else if($status=="inquiry") { ?>
				
			<?= trans("Verbindung angefragt", "connection requested"); ?><br>
			<br/>
			<a href='<?= FILENAME;?>?profile=<?= $_REQUEST["profile"];?>&action=withdrawConnection' class="btn btn-default"><?= trans("Verbindungsanfrage zurֳ¼ckziehen", "withdraw request");?></a>
		
		<?php } else if($status=="request") { ?>
				
			<?= trans("Verbindungsanfrage liegt vor", "requested for connection"); ?><br>
			<br/>
			<a href='<?= FILENAME;?>?profile=<?= $_REQUEST["profile"];?>&action=acceptConnection' class="btn btn-success"><?= trans("Verbindungsanfrage bestֳ₪tigen", "accept request");?></a>
			<a href='<?= FILENAME;?>?profile=<?= $_REQUEST["profile"];?>&action=rejectConnection' class="btn btn-danger"><?= trans("Verbindungsanfrage ablehnen", "reject request");?></a>
		
		<?php } else if($status=="connected") { ?>
		
			<?= trans("Verbunden", "connected"); ?><br>
			<br/>
			<a href='<?= FILENAME;?>?profile=<?= $_REQUEST["profile"];?>&action=splitConnection' class="btn btn-default"><?= trans("Verbindung trennen", "split connection");?></a>
		
		<?php } ?>
	</div>
</div>
<script>
var whichView = "detail/<?= $_GET["full"];?>";
</script>
<?php
$P = new post("", $_GET["full"]);
$sender = $profil->get($P->data["user"]);
$sender["smallimage"] = $profil->getImageBase64($sender, 'small');
?>
<div class="row">
	<div class="col-md-4">
	
		<div style="background-color: #fbf9f6;border: solid 1px #ececec;padding: 10px;">
		<div style='padding: 10px;background-color: #800000;color:white;'>
			<?= trans("Erstellt von", "Created by");?>
		</div>
		</div>


	
		<div style="background-color: white;border: solid 1px #ececec;">
			<div style="padding: 10px;">
				<img src='<?= $sender["smallimage"];?>' width=50 height=50 style="float:left;border-radius: 10px;margin-right:10px;">
				<div style="float:left;padding-left: 10px;padding-top: 5px;">
					<div style="font-weight: bold;"><?= $sender["name"]; ?></div>
					<div style="font-size:12px;min-height:12px;"><?= $sender["profession"];?></div>
				</div>
				<div style='clear:both;'></div>
				<div style='margin:10px 0 10px 0;'><hr/></div>
				<?= formatDateHuman($P->data["data"]["date"]); ?>
			</div>
		</div>
		
		<br/>
		
		<?php if($P->data["data"]["newformeditable"]!="nein" || $P->data["user"]==me()) { ?>
		<div style="background-color: #fbf9f6;border: solid 1px #ececec;padding: 10px;">
		<div style='padding: 10px;background-color: #800000;color:white;'>
			<?= trans("Anhֳ₪ngen", "Append");?>
		</div>
		</div>
		
		<div style="background-color: white;border: solid 1px #ececec;">
			
			<?php if(!isfilled($_GET["edit"])) { ?>
			<div style='padding: 10px;'>
				<form method="post" id="detailAddPostForm" onsubmit="sendaddpost(this);return false;">
					<input type="hidden" name="id" class='id' value="<?= $_GET["full"];?>" />
					<input type="hidden" name="action" value="addpost" />
					<textarea id="addposttext" name="addposttext" class="form-control addposttext" placeholder="<?= trans("Zusֳ₪tzlicher Text", "additional text");?>"></textarea>
					
					<button class="btn btn-success btn-xs"><?= trans("anfֳ¼gen", "append");?></button>
				</form>
			</div>
			<hr/>
			<?php } ?>
			<div style='padding: 10px;'>
			
				<div style='display:none;'>
					<a href='#' onclick="$('#fileuploaddiv').slideDown();$(this).closest('div').slideUp();return false;"><i class='glyphicon glyphicon-file'></i>&nbsp;<?= trans("Dateien/Bilder anhֳ₪ngen", "append files/pictures");?></a>
					<!--
					&nbsp;&nbsp;
					<a href='<?= FILENAME;?>?full=<?= $P->data["id"]; ?>'><i class='glyphicon glyphicon-picture'></i>&nbsp;<?= trans("Bilder anhֳ₪ngen", "append pictures");?></a>
					-->
				</div>
				<div style='display:block;' id="fileuploaddiv">

                    <b><?= trans("Datei/Bild", "Append file");?>:</b>
                    <input type="hidden" id="appendFileToDetail" value="">
                    <div style="margin-bottom:5px;">
                        <input type='file' multiple=true onchange="uploadFile(this, 'postfile', 'appendFileToDetail', '<?= $P->data["id"]; ?>', sendUploadedFiles);this.value='';">
                    </div>
                    <script>
                        function sendUploadedFiles() {
                            <?php if(isset($_GET["edit"])) { ?>
                                $('#textcontent').val( $('#textcontent').val() + "\n"+ $('#appendFileToDetail').val() );
                            <?php }  else { ?>
                                sendaddline(<?= $_GET["full"];?>, $('#appendFileToDetail').val());
                            <?php } ?>
                        }
                    </script>

                    <?php /*
                    <form method="post" enctype="multipart/form-data">
						<input type="hidden" name="id" value="<?= $_GET["full"];?>" />
						<input type="hidden" name="action" value="addfile" />
						<div class="form-group">
							<label for="exampleInputFile"><?= trans("Datei/Bild", "File/Picture");?></label>
							<input type="file" name="fileappend[]" multiple="multiple">
							<p class="help-block"><?= trans("", "");?></p>
							<button class="btn btn-success btn-xs"><?= trans("ֳ¼bertragen", "transmit");?></button>
						</div>
					</form>
                    */ ?>
				</div>
			</div>


			
		</div>
		<?php } ?>
		
	</div>
	<div class="col-md-8">
		<div style="background-color: #fbf9f6;border: solid 1px #ececec;">
			<div style="padding: 10px;">
			
				<div style='padding: 10px;background-color: #800000;color:white;'>
					<?php if($P->data["data"]["newformeditable"]!="nein" || $P->data["user"]==me()) { ?>
					<div style='float:right;'>
						<?php if(isset($_GET["edit"]) && $_GET["edit"]==1) { ?>
						<?php } else { ?>
							<a href='<?= FILENAME;?>?full=<?= $_GET["full"];?>&edit=1' style="color:white;"><i class="glyphicon glyphicon-pencil"></i>&nbsp;<?= trans("bearbeiten", "edit");?></a>
						<?php } ?>
					</div>
					<?php } ?>
					<?= trans("Eintrag", "Post");?>
				</div>
				
				

				<?php				
				$profil = new profile();
				?>
				<?php if(isfilled($_GET["edit"])) { ?>
					
<div class='post' id='<?= $P->data["id"]; ?>' style='padding: 10px;margin-top:10px;background-color: #ffffff;color:#494949;border: solid 1px #ececec;' rel=''>
	<form method="post"  action="<?= FILENAME;?>?full=<?= $P->data["id"]; ?>">
	<input type="hidden" name="action" value="updatepost" />
	<input type="hidden" name="id" value="<?= $P->data["id"]; ?>" />
	
	<textarea class="form-control" name="textcontent" id="textcontent" style="height:500px;"><?= $P->data["data"]["text"]; ?></textarea>
	<div style='float:left;'>
		<button class="btn btn-success"><?= trans("ֳ„nderungen speichern", "save changes");?></button>
	</div>
	<div style='float:right;'>
		<a href='#' onclick="window.location='<?= FILENAME;?>?full=<?= $P->data["id"]; ?>';return false;" class="btn btn-danger"><?= trans("ֳ„nderungen verwerfen", "cancel");?></a>
	</div>
	<div style='clear:both;'></div>
	
	<center>
	<?= trans("<a href='http://de.wikipedia.org/wiki/Markdown' target='_blank'>Markdown-Syntax</a>", "<a href='http://en.wikipedia.org/wiki/Markdown' target='_blank'>Markdown-Syntax</a>");?>
	</center>
	
	</form>
</div>



				<?php } else { ?>
				<div id='postlist'>
					
<?php
$files = $P->getFiles();
$img = $P->getImages();
#vd($img[0]);
?>
	<div class='post' id='<?= htmlid($P->data["id"]); ?>' rel='<?= $P->data["id"]; ?>' style='padding: 10px;margin-top:10px;background-color: #ffffff;color:#494949;border: solid 1px #ececec;' rel=''>
	
		<div style=''>
			<?= prepareText($P->data["data"]["text"], $P); ?>
			
			<?php if(1==2 && count($img)>0) { ?>
				<div style='text-align:center;' id='img_<?= htmlid($P->data["id"]); ?>'></div>
				<script>
				$(function() {
					openImagePreview('img_<?= htmlid($P->data["id"]); ?>', '<?= $P->data["id"];?>');
				});
				</script>
			<?php } ?>
			
		</div>
		
		<div style="clear:both;"></div>
		
		<div style='display:block;padding-left:58px;padding-top:5px;' class='functions'>
			
			<div style='float:left;'>
				
			</div>
			<div style='float:right;'>
				
				<?php if($P->data["data"]["newformcommenttype"]!='keine' || $P->data["user"]==me()) { ?>
				<a href='#' onclick="openreply(this);$(this).hide();return false;"><i class='glyphicon glyphicon-comment'></i>&nbsp;<?= trans("kommentieren", "add comment");?></a>
				<?php } ?>
				<!--
				&nbsp;&nbsp;
				<a href='#' onclick="return false;"><i class="glyphicon glyphicon-star-empty"></i>&nbsp;<?= trans("Beobachten", "favorite");?></a>
				-->
				<!-- <i class="glyphicon glyphicon-star"></i> -->
			</div>
			<div style='clear:both;'></div>
			
			<div class='replydiv'></div>
			
		</div>
		
	</div>
	<?php
	
	if(count($files)>0) {
		?>
		<br/>
		<div style='padding: 10px;background-color: #800000;color:white;'>
		<?= trans("Dateien / Bilder", "Files / Pictures");?>
		</div>
		<div class="list-group" style="margin-bottom:0;">
		<?php for ($j=0;$j<count($files);$j++) { ?>
				<a href="#" class="list-group-item">
				<span class="badge"><?= round($files[$j]->filesize/1024); ?> KB</span>
				<h4 class="list-group-item-heading"><?= $files[$j]->name;?></h4>
				<!-- <p class="list-group-item-text">...</p> -->
				</a>
		<?php } ?>
		</div>
		<?php
	}
	
	
	$comments = $P->getComments();
	if(count($comments)>0) {
	?>
	
	<br/>
	<div style='padding: 10px;background-color: #800000;color:white;'>
		<?= trans("Kommentare", "Comments");?>
	</div>
	
	<div style=''>
		<?php
		for($j=0;$j<count($comments);$j++) {
			$senderC = $profil->get($comments[$j]->data["user"]);
			$senderC["miniimage"] = $profil->getImageBase64($senderC, 'mini');
		?>
			<div style='margin-bottom:1px;border-left:solid 1px #ececec;border-bottom:solid 1px #ececec;border-right:solid 1px #ececec;padding:10px;font-size:0.8em;background-color:#f6f6f6;'>

				<img src='<?= $senderC["miniimage"];?>'  width=25 height=25 style="float:left;border-radius: 5px;margin-right:10px;">
				
				<div style='float:right'><?= formatDateHuman($comments[$j]->data["data"]["date"]); ?></div>
				<div style='float:left;'>
					<span style='color: #800000;font-weight: bold;'><?= $senderC["name"]; ?></span><br/>
					<?= $comments[$j]->data["data"]["text"];?>
				</div>
				<div style='clear:both;'></div>
			
			</div>
		<?php } ?>
	</div>
	<?php } ?>



				</div>
				<?php } ?>
				
				<script>
				var lastObj = "";
				function openreply(obj) {
					lastObj = obj;	
					var html = $('#replyform').html();
					html = html.replace("#ID#", $(obj).closest(".post").attr("rel"));
					$(obj).closest(".functions").find(".replydiv").html( html );
					setTimeout(function() { $(lastObj).closest(".functions").find('.commenttext').focus(); }, 100);
				}
				</script>
				
				<script type="text/plain" id="replyform">
				
				<div style='padding-top:10px;'>
					<form method="post">
						<input type="hidden" name="action" value="newcomment" />
						<input type="hidden" name="id" value="#ID#" />
						<input type="text" class="form-control commenttext" name="replytext"  placeholder="<?= trans("einen Kommentar auf diesen Beitrag hinterlassen", "comment on this post");?>" />
					</form>
				</div>
				
				</script>				
				
			</div>
		</div>
	</div>
</div>


<script>
var whichView = "own";
</script>
<div class="row">
	<div class="col-md-4">
	
		<script>
		$(document).scroll(function() {
			//console.log();
			
			$('#leftDiv').css("top", $(window).scrollTop());
			
		});
		</script>
	
		<div style='position:relative;' id="leftDiv">
	
		<div style="background-color: white;border: solid 1px #ececec;">
			<div style="padding: 10px;">
			
				<div style='float:right;' class="onsmall">
					<a href='#' onclick="$('.moreinfo').slideDown();$(this).hide();return false;"><i class="glyphicon glyphicon-info-sign" style="font-size:2em;"></i></a>
				</div>
			
				<img src='<?= FILENAME;?>?action=getProfileImage&size=small' width=50 height=50 style="float:left;border-radius: 10px;" />
				<div style="float:left;padding-left: 10px;padding-top: 5px;">
					<div style="font-weight: bold;"><?= $profilData["name"];?></div>
					<div style="font-size:12px;min-height:12px;"><?= $profilData["profession"];?></div>
				</div>
				<div style='clear:both;'></div>
			</div>
			
			<hr/>
			
			<div class='onbig moreinfo'>
				
				<div style='padding: 10px;float:left;width: 30%;border-right:solid 1px #ececec;'>
					<b><?= (int)$profilData["counts"]["posts"];?></b><br/>
					<?= trans("Eintrֳ₪ge", "Posts");?>
				</div>
				<div style='padding: 10px;float:left;width: 30%;border-right:solid 1px #ececec;'>
					<b><?= (int)$profilData["counts"]["comments"];?></b><br/>
					<?= trans("Kommentar", "Comments");?>
				</div>
				<div style='padding: 10px;float:left;'>
					<b><?= (int)$profilData["counts"]["replies"];?></b><br/>
					<?= trans("Antworten", "Replies");?>
				
				</div>
				<hr/>
			</div>
				<div style='padding: 10px;'>
					<form method="post" onsubmit="sendnewpost(this);return false;">
						<input type="hidden" name="action" value="newpost" />
						<textarea id="newposttext" name="newposttext" onfocus="$('#empfaenger').slideDown();" class="form-control newposttext" placeholder="<?= trans("Neuer Eintrag", "new post");?>"></textarea>


						
						<div style="display:none;padding: 5px;" id="empfaenger">

                            <b><?= trans("Datei ֳ¼bertragen", "Append file");?>:</b>
                            <div style="margin-bottom:5px;">
                            <input type='file' multiple=true onchange="uploadFile(this, 'postfile', 'newposttext', -1);">
                            </div>

							<?php
							$P = new profile();
							$C = $P->getContacts();
							?>
							<?php if(count($C)>0) { ?>
							<div>
							<b><?= trans("Sichtbar fֳ¼r", "Visible for");?>:</b><br/>
							<select class="form-control newformrecipienttype" onchange="recipSelect(this);">	
								<option value='alle'><?= trans("alle meine ".count($C)." Kontakte", "all my ".count($C)." contacts");?></option>
								<option value='ausgewaehlte' selected><?= trans("ausgewֳ₪hlte Kontakte", "selected contacts");?></option>
								<option value='mich'><?= trans("mich", "just me");?></option>
							</select>
							</div>
							<?php } ?>
							
							<div style="margin-top:5px;display:block;" id='kontaktauswahl'>
							
							<?php for($i=0;$i<count($C);$i++) { ?>
								<div style='float:left;border: solid 1px silver;padding:3px; margin:0 3px 3px 0;border-radius:5px;min-width:100px;text-align:center;font-size:8pt;' onclick="$(this).find('input[type=checkbox]').click();">
									<label style="margin:0;">
										<input type='checkbox' class='recipientcheckbox' value='<?= $C[$i]["key"];?>' style='vertical-align: bottom;position: relative;top: -1px;'><?= $C[$i]["name"];?>
									</label>
								</div>
							<?php } ?>
							<div style='clear:both;'></div>
							</div>							
							
							<?php if(count($C)>0) { ?>
							<div id='commenttypeselect'>
							<b><?= trans("Kommentare", "Comments");?>:</b><br/>
							<select class="form-control newformcommenttype">	
								<option value='bekannte'><?= trans("Unter verbundenen Kontakten", "connected contacts");?></option>
								<option value='bidrektional'><?= trans("Bi-Direktional", "bidirectional");?></option>
								<option value='keine'><?= trans("keine erlauben", "not allowed");?></option>
							</select>
							</div>
							
							<div id="edittypeselect">
							<b><?= trans("Empfֳ₪nger dֳ¼rfen bearbeiten", "Editable");?>:</b><br/>
							<select class="form-control newformeditable">	
								<option value='nein'><?= trans("nein", "no");?></option>
                                <option value='ja'><?= trans("ja", "yes");?></option>
							</select>
							</div>
							<?php } ?>
							
						</div>
						
						
						<button class="btn btn-success btn-xs" onclick="if(!testMsgSend()) {return false;} editAfterSend=0;return true;"><?= trans("absenden", "send post");?></button>
						
						<div style='float:right;'>
						<button class="btn btn-default btn-xs" onclick="if(!testMsgSend()) {return false;}editAfterSend=1;return true;"><?= trans("absenden und weiter bearbeiten", "send post and edit");?></button>
						</div>
						
						
					</form>
				</div>


						<script>
						
						function testMsgSend() {
							if($('#newposttext').val()=="") {
								alert("keine Nachricht geschrieben!");
								$('#newposttext').focus();
								return false;
							}
							if($('.newformrecipienttype').val()=="ausgewaehlte") {
								if($('.recipientcheckbox:checked').length<=0) {
									alert("keine Empfֳ₪nger markiert!");
									return false;
								}
							}
							return true;
						}
						
						function recipSelect(obj) {
							if(obj.value=='ausgewaehlte') {
								$('#kontaktauswahl').slideDown(); 
								$('#commenttypeselect').slideDown();
                                $('#edittypeselect').slideDown();
							} else {
								$('#kontaktauswahl').slideUp();
								if(obj.value=='mich') {
                                    $('#edittypeselect').slideUp();
									$('#commenttypeselect').slideUp();
								} else {
                                    $('#edittypeselect').slideDown();
									$('#commenttypeselect').slideDown();
								}
							}
							
						}
						
						$(function() {
							$('.recipientcheckbox').bind("click", function(e) {
								
								$(this).closest('div').css("background-color", (this.checked ? "#800000" : '#ffffff') );
								$(this).closest('label').css("color", (this.checked ? "#ffffff" : '#000000') );
								e.stopPropagation();
							});
						});
						</script>
						
				
			<?php if(getConfigValue("enableTrees", "no")=="yes") {?>
			<div class='onbig moreinfo'>	
	
				<hr />
				<div style='float:right;padding:5px 5px 0 0;font-size:0.8em;' class="edittreelink">
					<a href='#' onclick="edittree();return false;"><i class='glyphicon glyphicon-pencil'></i>&nbsp;<?= trans("Baum bearbeiten", "edit tree");?></a>
				</div>
				<div style='float:right;padding:5px 5px 0 0;font-size:0.8em;display:none;' class="savetreelink">
					<a href='#' onclick="savetree();return false;"><i class='glyphicon glyphicon-floppy-disk'></i>&nbsp;<?= trans("Baum speichern", "save tree");?></a>
				</div>
				<div class="tree" id="grouptree">	
				<?php
				$tree = new tree("own");
				echo $tree->getTree();
				?>
				</div>
				<script>
				var optionTREE = <?= json_encode($tree->getTreeOptionArray()); ?>;
				</script>
				
				
				<div id="edittreeform" style="padding:10px;display:none;">
					<form onsubmit="addTreeEntry(this);return false;">
					<input type="text" class="form-control" id="edittreenewfolder" placeholder="<?= trans('neuer Bereich', 'new folder');?>"/>
					</form>
				</div>
			</div>
			<?php } ?>
		
			
			
			
		</div>
		
		</div> <!-- absolute -->
		
	</div>
	<div class="col-md-8">
		<div style="background-color: #fbf9f6;border: solid 1px #ececec;">
			<div style="padding: 10px;">
			
				<div style='padding: 10px;background-color: #800000;color:white;'>
					<?= trans("Eintrֳ₪ge", "Posts");?>
				</div>
				
				<?php
				$posts = new posts();
				$P = $posts->recent();
				?>
				
<?php				
$profil = new profile();
?>
<?php if(isset($P[0])) { ?>
<script>
lastChange = <?= $P[0]->data["ts"]; ?>;
</script>
<?php } ?>
<div id='postlist'>

<?php for($i=0;$i<count($P);$i++) {?>
	
	
	<?php
	$sender = $profil->get($P[$i]->data["user"]);
	$sender["smallimage"] = $profil->getImageBase64($sender, 'small');
	//vd($P[$i]);
	?>
	<div class='post' id='<?= htmlid($P[$i]->data["id"]); ?>' rel='<?= $P[$i]->data["id"]; ?>' style='cursor:pointer;padding: 10px;margin-top:10px;background-color: #ffffff;color:#494949;border: solid 1px #ececec;' rel=''
		xonclick="if($(this).attr('rel')=='') {$(this).attr('rel', 'locked'); $(this).find('.functions').slideDown();} else if($(this).attr('rel')=='*') $(this).attr('rel', ''); ">
	
		<div style="max-height:150px;overflow:hidden;" onclick="expand(this);return false;$(this).closest('.post').find('.functions').slideToggle();" class='outerContent'>
			<img src='<?= $sender["smallimage"];?>' width=50 height=50 style="float:left;border-radius: 10px;margin-right:10px;">
		
			<div style='text-align:right;float:right;margin-left: 10px;' class="treeeditlink">
				<span style='font-size:0.8em;'><?= formatDateHuman($P[$i]->data["data"]["date"]); ?></span>
				&nbsp;
				<a href='#' onclick="if(confirm('<?= trans("Diesen Beitrag lֳ¶schen?\\nEr wird nur in Deiner Anzeige entfernt.\\nAndere Empfֳ₪nger kֳ¶nnen ihr weiterhin sehen." ,"Really delete this post?\\nIt will only disappear your view.\\nOther recipients can still see this post.");?>')) {
					window.location='<?= FILENAME;?>?action=delete&id=<?= $P[$i]->data["id"]; ?>'; 
				} return false;" style="color:gray;"><i class="glyphicon glyphicon-remove-circle"></i></a>
				
				<br>	
				<div style='float:right;font-size:0.8em;max-width:200px;'>sichtbar fֳ¼r:
				<?php
				/*
				for($ri=0;$ri < count($P[$i]->data["data"]["recipientNames"]);$ri++) {
					$P[$i]->data["data"]["recipientNames"][$ri] = "<a href='".FILENAME."?profile=".$P[$i]->data["data"]["recipients"][$ri]."'>".$P[$i]->data["data"]["recipientNames"][$ri]."</a>";
				}
				*/
				echo implode(", ", array_merge(array("mich"), $P[$i]->data["data"]["recipientNames"]));
				?>
				</div>
				<div style='clear:both;'></div>
			</div>

			<div style=''>
				<div class='innerContent'>
					<span style='color: #800000;font-weight: bold;'><?= $sender["name"]; ?></span><br/>
					<?= prepareText($P[$i]->data["data"]["text"], $P[$i]); ?>
					
					<?php
                    /*
					$img = $P[$i]->getImages();
					
					if(count($img)>0) { ?>
						<div style='min-height:100px;text-align:center;' id='img_<?= htmlid($P[$i]->data["id"]); ?>'></div>
						<script>
						$(function() {
							openImagePreview('img_<?= htmlid($P[$i]->data["id"]); ?>', '<?= $P[$i]->data["id"];?>');
						});
						</script>
					<?php }
                    */
                    ?>
					
					
				</div>
			</div>
			
			<div style="clear:both;"></div>
		</div>
		
		<div style='display:none;padding-left:58px;padding-top:5px;' class='functions'>
			
			<div style='float:left;'>
				<!--
				<a href='#' onclick="$(this).closest('.functions').slideUp();$(this).closest('.post').attr('rel', '*');return false;"><?= trans("verstecken", "collapse");?></a>
				-->
				<?php if(getConfigValue("enableTrees", "yes")=="yes") {?>
				<div style='float:left;' class="treeeditlink">
					<a href='#' onclick="editpostlink(this, '<?= $P[$i]->data["id"]; ?>');return false;"><i class="glyphicon glyphicon-list"></i>&nbsp;<?= ($P[$i]->tree["title"]!="" ? $P[$i]->tree["title"] : '<span style="color:silver;">'.trans('keine Zuordnung', 'not in tree').'</span>');?></a>
				</div>
				<?php } ?>
				
				
			</div>
			<div style='float:right;'>
				
				<a href='<?= FILENAME;?>?full=<?= $P[$i]->data["id"]; ?>'><i class='glyphicon glyphicon-fullscreen'></i>&nbsp;<?= trans("vollstֳ₪ndige Ansicht", "full view");?></a>
				<?php if($P[$i]->data["data"]["newformcommenttype"]!='keine' || $P[$i]->data["user"]==me() ) { ?>
				<!--
				&nbsp;&nbsp;&nbsp;
				<a href='#' onclick="openreply(this);$(this).hide();return false;"><i class='glyphicon glyphicon-comment'></i>&nbsp;<?= trans("kommentieren", "add comment");?></a>
				-->
				<?php } ?>
				
				<?php /*
				<!--
				&nbsp;&nbsp;
				<a href='#' onclick="return false;"><i class="glyphicon glyphicon-star-empty"></i>&nbsp;<?= trans("Beobachten", "favorite");?></a>
				-->
				<!-- <i class="glyphicon glyphicon-star"></i> -->
				*/ ?>
			</div>
			<div style='clear:both;'></div>
			
			<?php if($P[$i]->data["data"]["newformcommenttype"]!='keine' || $P[$i]->data["user"]==me() ) { ?>
			<div class='replydiv' rel="<?php
				if($P[$i]->data["user"]==me()) echo "allemeine";
				else echo $P[$i]->data["data"]["newformcommenttype"];
			?>"></div>
			<?php } ?>
			
		</div>
		
	</div>
	<?php if($P[$i]->data["data"]["newformcommenttype"]!='keine' || $P[$i]->data["user"]==me() ) { ?>
	<div style='padding-left:70px;' id='comments<?= htmlid($P[$i]->data["id"]); ?>'>
		<?php
		$comments = $P[$i]->getComments();
		for($j=0;$j<count($comments);$j++) {
			$senderC = $profil->get($comments[$j]->data["user"]);
			$senderC["miniimage"] = $profil->getImageBase64($senderC, 'mini');
		?>
			<div style='margin-bottom:1px;border-left:solid 1px #ececec;border-bottom:solid 1px #ececec;border-right:solid 1px #ececec;padding:10px;font-size:0.8em;background-color:#f6f6f6;'>

				<img src='<?= $senderC["miniimage"];?>'  width=25 height=25 style="float:left;border-radius: 5px;margin-right:10px;">
				
				<div style='float:right'><?= formatDateHuman($comments[$j]->data["data"]["date"]); ?></div>
				<div style='float:left;'>
					<span style='color: #800000;font-weight: bold;'><?= $senderC["name"]; ?></span><br/>
					<?= $comments[$j]->data["data"]["text"];?>
				</div>
				<div style='clear:both;'></div>
			
			</div>
		<?php } ?>
	</div>
	<?php } ?>
	

	
	



<?php } ?>
</div>

<script>


$(function() {
    setTimeout(function() {
	$('.outerContent').each(function() {
		var diff = $(this).find('.innerContent').height()-$(this).height();
		if(diff<30) {
			$(this).css("max-height", "");
		} else {
			var id = $(this).closest('.post').attr("rel");
			var alles = '<div style="border-top:solid 1px gray;text-align:center;" class="alles">';
			alles += '<div style="margin-top:2px;border-top:solid 1px gray;"></div>';
			alles += '<a href="#" onclick="expand(this);return false;"><i class="glyphicon glyphicon-resize-vertical"></i><?= trans("alles anzeigen", "expand");?></a>';
			alles += '&nbsp;&nbsp;&nbsp;'; 
			alles += '<a href="<?= FILENAME;?>?full='+id+'"><i class="glyphicon glyphicon-fullscreen"></i><?= trans("vollstֳ₪ndige Ansicht", "full view");?></a>';
			alles += '</div>';
			$(this).after(alles);
		}
	});
    }, 1000)
});

var lastObj;
var expandBig = true;
function expand(obj) {
	lastObj = obj;
	var istH = $(obj).closest(".post").find(".outerContent").height();
	var sollH = $(obj).closest(".post").find(".outerContent").find(".innerContent").height();
	
	$(obj).closest('.post').find('.functions').slideToggle();


	openreply(obj);
		
	//console.log([istH, sollH]);
	if(sollH<istH) return;

	
	if(istH<sollH) {
		$(obj).attr("rel", istH);
		expandBig = true;
		$(obj).closest(".post").find(".alles").slideUp(function() { 
			//$(this).remove();
			
		});
		
	} else {
		sollH = $(obj).attr("rel");
		expandBig = false;
		$(obj).closest(".post").find(".outerContent").css("max-height", istH);
		$(obj).closest(".post").find(".alles").slideDown(function() { 
			//$(this).remove();
			 
		});
		
	}
	
	
	 $(obj).closest(".post").find(".outerContent").animate({
	 "max-height": sollH
	 }, 1000, function() {
	 	//$(lastObj).closest(".post").find(".outerContent").css("max-height", "");
	 	if(expandBig) $(this).css("max-height", "");
	 	
	});	
	
	
}

var lastObj = "";
function openreply(obj) {
	lastObj = obj;	
	var html = $('#replyform').html();
	html = html.replace("#ID#", $(obj).closest(".post").attr("rel"));
	//$(obj).closest(".functions").find(".replydiv").html( html );
	//setTimeout(function() { $(lastObj).closest(".functions").find('.commenttext').focus(); }, 100);
	var rel = $(obj).closest(".post").find(".replydiv").attr("rel");
	if(rel=="allemeine") rel = "<?= trans('alle ursprֳ¼nglichen Empfֳ₪nger dieser Nachricht', 'all recipients of the post');?>";
	else if(rel=="bekannte") rel = "<?= trans('alle Empfֳ₪nger dieses Posts, die auch mit mir verbunden sind', 'all reipients of this post, who are connected with me');?>";
	else if(rel=="bidrektional") rel = "<?= trans('nur der Autor des Posts', 'only the author');?>";
	html = "<span style='font-size:0.8em;'><b><?= trans('Wer sieht diesen Kommentar:', 'Who sees this comment:');?></b> "+rel + "</span><br>"+html;
	$(obj).closest(".post").find(".replydiv").html( html );
	setTimeout(function() { $(lastObj).closest(".post").find('.commenttext').focus(); }, 100);
}
</script>

<script type="text/plain" id="replyform">

<div style='padding-top:10px;'>
	<form method="post">
		<input type="hidden" name="action" value="newcomment" />
		<input type="hidden" name="id" value="#ID#" />
		<input type="text" class="form-control commenttext" name="replytext"  placeholder="<?= trans("einen Kommentar auf diesen Beitrag hinterlassen", "comment on this post");?>" />
	</form>
</div>

</script>

<script>
var recentPostID = "<?= $P[count($P)-1]->data["id"];?>";
</script>
<center>
<button onclick="getOlderPosts();return false;" class="btn btn-default"><i class='glyphicon glyphicon-download'></i> <?= trans("ֳ₪ltere Beitrֳ₪ge laden", "load older posts");?> <i class='glyphicon glyphicon-download'></i></button>
</center>



				
				
				
			</div>
		</div>
	</div>
</div>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="easy to install private community system">
<meta name="author" content="Aresch Yavari">
<meta name="version" content="1.20140528094835">
<title><?= getConfigValue("pagetitle", "My own hosted community");?></title>
<link rel="stylesheet" href="<?= FILENAME;?>?RES=resources/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= FILENAME;?>?RES=resources/css/styles.css">
<link rel="stylesheet" href="<?= FILENAME;?>?RES=resources/css/tree.css">
<LINK REL="SHORTCUT ICON" HREF="<?= FILENAME;?>?RES=resources/images/favicon.ico">

















<script src="<?= FILENAME;?>?RES=resources/js/jquery.js"></script>
<script>
var filename = "<?= FILENAME;?>";
var lastChange = <?= time()*1000;?>;
</script>

<style>

</style>

</head>
<body>

<?php
				if(!is_array($tempfn)) $tempfn = array();
				$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php"; 
				file_put_contents($fn, getRes("templates/navbar.tpl")); 
				include $fn;
				unlink(array_pop($tempfn));
				?>


<div class="container">
        		
        		<?php if(isset($_GET["do"]) && $_GET["do"]!="") { ?>
        		
        			<?php if($_GET["do"]=="register") { ?>
        				<?php
				if(!is_array($tempfn)) $tempfn = array();
				$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php"; 
				file_put_contents($fn, getRes("templates/register.tpl")); 
				include $fn;
				unlink(array_pop($tempfn));
				?>
        			<?php } ?>
        		
        			<?php if($_GET["do"]=="login") { ?>
        				<?php
				if(!is_array($tempfn)) $tempfn = array();
				$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php"; 
				file_put_contents($fn, getRes("templates/login.tpl")); 
				include $fn;
				unlink(array_pop($tempfn));
				?>
        			<?php } ?>
        		
        		<?php } else { ?>
        			<?php if(me()=="") { ?>
        				<?php
				if(!is_array($tempfn)) $tempfn = array();
				$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php"; 
				file_put_contents($fn, getRes("templates/homepage.tpl")); 
				include $fn;
				unlink(array_pop($tempfn));
				?>
        			<?php } else { ?>
        				<?php if(isset($_GET["view"]) && $_GET["view"]=="profil") { ?>
        					<?php
				if(!is_array($tempfn)) $tempfn = array();
				$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php"; 
				file_put_contents($fn, getRes("templates/profil.tpl")); 
				include $fn;
				unlink(array_pop($tempfn));
				?>
        				<?php } else if(isset($_GET["view"]) && $_GET["view"]=="search") { ?>
        					<?php
				if(!is_array($tempfn)) $tempfn = array();
				$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php"; 
				file_put_contents($fn, getRes("templates/search.tpl")); 
				include $fn;
				unlink(array_pop($tempfn));
				?>
        				<?php } else if(isset($_GET["view"]) && $_GET["view"]=="contacts") { ?>
        					<?php
				if(!is_array($tempfn)) $tempfn = array();
				$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php"; 
				file_put_contents($fn, getRes("templates/contacts.tpl")); 
				include $fn;
				unlink(array_pop($tempfn));
				?>
        				<?php } else if(isset($_GET["view"]) && $_GET["view"]=="groups") { ?>
        					<?php
				if(!is_array($tempfn)) $tempfn = array();
				$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php"; 
				file_put_contents($fn, getRes("templates/groups.tpl")); 
				include $fn;
				unlink(array_pop($tempfn));
				?>
                        <?php } else if(isset($_GET["view"]) && $_GET["view"]=="notifications") { ?>
                            <?php
				if(!is_array($tempfn)) $tempfn = array();
				$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php"; 
				file_put_contents($fn, getRes("templates/notifications.tpl")); 
				include $fn;
				unlink(array_pop($tempfn));
				?>
        				<?php } else if(isset($_GET["group"]) && $_GET["group"]!="") { ?>
        					<?php
				if(!is_array($tempfn)) $tempfn = array();
				$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php"; 
				file_put_contents($fn, getRes("templates/group.tpl")); 
				include $fn;
				unlink(array_pop($tempfn));
				?>
        				<?php } else if(isfilled($_GET["profile"])) { ?>
        					<?php
				if(!is_array($tempfn)) $tempfn = array();
				$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php"; 
				file_put_contents($fn, getRes("templates/userprofil.tpl")); 
				include $fn;
				unlink(array_pop($tempfn));
				?>
        				<?php } else if(isfilled($_GET["full"])) { ?>
        					<?php
				if(!is_array($tempfn)) $tempfn = array();
				$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php"; 
				file_put_contents($fn, getRes("templates/full.tpl")); 
				include $fn;
				unlink(array_pop($tempfn));
				?>
        				<?php } else { ?>
						<?php
				if(!is_array($tempfn)) $tempfn = array();
				$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php"; 
				file_put_contents($fn, getRes("templates/overview.tpl")); 
				include $fn;
				unlink(array_pop($tempfn));
				?>
					<?php } ?>
				<?php } ?>
			<?php } ?>

        		<center>
        		<hr size=1 color=silver />
        		<div style='color:gray;font-size:8pt;'>
        		</div>
        		</center>
</div>

<script src="<?= FILENAME;?>?RES=resources/js/bootstrap.min.js"></script>
<script src="<?= FILENAME;?>?RES=resources/js/script.js"></script>
</body>
</html>
