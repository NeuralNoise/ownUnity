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
			if($T=="-1-") $T = trans("Alle BeitrÃ¤ge", "all entries");
			if($T=="-2-") $T = trans("nicht zugewiesene BeitrÃ¤ge", "not linked entries");
			if($T=="-3-") $T = trans("meine BeitrÃ¤ge", "my entries");
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

        $data["pubkeys"] = array();
        $pk = glob($fn.'/*.pubkey');
        if($pk!="") {
            for($i=0;$i<count($pk);$i++) {
                $data["pubkeys"][] = file_get_contents($pk[$i]);
            }
        }

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

    public function setNewPassword($old, $new) {
        $fn = getS("userFile").'/0.user';
        $data = json_decode(file_get_contents($fn), true);
        if( md5(strtolower($data["loginname"].trim($old))) != $data["password"] ) return false;

        $data["password"] = md5(strtolower($data["loginname"].trim($new)));
        file_put_contents($fn, json_encode($data));

        return true;
    }

    public function createNewPassword($recover) {

        if(trim($recover)=="") return false;

        $loginname = strtolower(trim($recover));
        $user = glob($this->path.'/'.$loginname.'*_user');
        if($user!="" && $user!=array()) {
            $fn = $user[0].'/0.user';
            $data = json_decode(file_get_contents($fn), true);
            if($data["email"]=="" || stristr($data["email"],"@")==false) return false;
            $pw = substr(md5(rand()), 0,5);
            $this->saveNewPWandSendEmail($fn, $pw);
            return true;
        } else {
            $all = glob($this->path.'/*_user');
            if($all!="" && count($all)>0) {
                for($i=0;$i<count($all);$i++) {
                    $fn = $all[$i].'/0.user';
                    $data = json_decode(file_get_contents($fn), true);
                    if($data["email"]==trim(strtolower($recover))) {
                        $pw = substr(md5(rand()), 0, 5);
                        $this->saveNewPWandSendEmail($fn, $pw);
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function saveNewPWandSendEmail($fn, $pw) {
#vd($pw);
        $data = json_decode(file_get_contents($fn), true);
        if($data["email"]=="") return false;

        $data["password"] = md5(strtolower($data["loginname"].$pw));
#vd($data["password"]);
        file_put_contents($fn, json_encode($data));

        $msg = trans("neues Kennwort: ", "new password: ").$pw;
        mail($data["email"], "[".getConfigValue("title", "ownUnity")."] ".trans('Neues Kennwort', 'New password'), $msg,  "FROM:".getConfigValue("absender", "info@".str_replace("www.", "", $_SERVER["HTTP_HOST"])) );

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
        #vd($data["password"]);
        #vd(md5(strtolower(trim($email.$pass))));
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

    public function removePubKey($clientID) {
        if($clientID=="") return;
        $me = $this->get(me());
        if(file_exists($me["path"].'/'.$clientID.'.pubkey')) {
            unlink($me["path"].'/'.$clientID.'.pubkey');
        }
    }

    public function addPubKey($clientID, $key) {
        $me = $this->get(me());
        file_put_contents($me["path"].'/'.$clientID.'.pubkey', $key);
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
            if($recipients[$i]=="mine") continue;
			file_put_contents($this->path.'/'.$group."_".$recipients[$i].".ping", $id);
		
			#$postdir = "posts/".$id."_".$group."_!_".$user."_post";
			$postdir = "posts/".$datefolder.'/'.$id.'_'.$mid.'_'.($recipients[$i]==me() ? '!' : '-')."_".$group."_".$recipients[$i]."_post";
			$path = checkfordir($postdir);
			
			if($recipients[$i]==me()) $myPath = $path;
			
			
			$from = new profile();
			$fromData = $from->get(me());
			$H = new history($recipients[$i]);
			$H->add("post", $id, $group, $mid, "Neue Nachricht von : ".$fromData["name"]);

            $dataUser = $data;
            if(is_array($dataUser["text"])) {
                $R = $recipients[$i];
                if($R==me()) $R = "mine";
                $dataUser["text"] = $dataUser["text"][$R];
            } else {
                $dataUser["text"] = htmlspecialchars($dataUser["text"]);
            }

			$post = array("data" => $dataUser,
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
        $ck[] = me();
#        echo "<pre>";var_dump($this->data["data"]["recipients"]);
#var_dump($ck);exit;
		$rID = array();
		$rN = array();
		for($i=0;$i<count($this->data["data"]["recipients"]);$i++) {
			if(in_array($this->data["data"]["recipients"][$i], $ck)) {
				$rec = $profil->get($this->data["data"]["recipients"][$i]);
				$rID[] = $this->data["data"]["recipients"][$i];
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
		$text = $parser->transform($text);

        $text = Markdown::make_links_clickable($text);
        $text = str_replace("ootp", "http", $text);

        return $text;
	}

    public static function make_links_clickable($text){
        return preg_replace('!(((f|ht)tp(s)?://)[-a-zA-ZĞ°-ÑĞ-Ğ¯()0-9@:%_+.~#?&;//=]+)!i', '<a href="$1" target=_blank>$1</a>', $text);
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
        //"doAutoLinks"         =>  30,
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

            $url = str_replace("http", "ootp", $url);

			$result = "<a href=\"$url\"";
			if ( isset( $this->titles[$link_id] ) ) {
				$title = $this->titles[$link_id];
				$title = $this->encodeAttribute($title);
				$result .=  " title=\"$title\"";
			}
		
			$link_text = $this->runSpanGamut($link_text);
			$result .= " target=_blank>$link_text</a>";
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

        $url = str_replace("http", "ootp", $url);

		$result = "<a href=\"$url\"";
		if (isset($title)) {
			$title = $this->encodeAttribute($title);
			$result .=  " title=\"$title\"";
		}
		
		$link_text = $this->runSpanGamut($link_text);
		$result .= " target=_blank>$link_text</a>";

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
    <div class="postAround">
	<div class='post' id='<?= htmlid($P[$i]->data["id"]); ?>' rel='<?= $P[$i]->data["id"]; ?>' style='xcursor:pointer;padding: 10px 10px 10px 10px;margin-top:10px;background-color: #ffffff;color:#494949;border: solid 1px #ececec;'
		xonclick="if($(this).attr('rel')=='') {$(this).attr('rel', 'locked'); $(this).find('.functions').slideDown();} else if($(this).attr('rel')=='*') $(this).attr('rel', ''); ">
	
		<div style="max-height:150px;overflow:hidden;" xonclick="expand(this);return false;$(this).closest('.post').find('.functions').slideToggle();" class='outerContent'>

            <table width="100%" cellspacing="0" cellpadding="0"><tr><td valign="top" width="50">

			    <img src='<?= $sender["smallimage"];?>' width=50 height=50 style="float:left;border-radius: 10px;margin-right:10px;">

            </td><td valign="top">

                <div style='text-align:right;float:right;margin-left: 10px;' class="treeeditlink">
                    <span style='font-size:0.8em;'><?= formatDateHuman($P[$i]->data["data"]["date"]); ?></span>
                    &nbsp;
                    <a href='#' onclick="if(confirm('<?= trans("Diesen Beitrag lÃ¶schen?\\nEr wird nur in Deiner Anzeige entfernt.\\nAndere EmpfÃ¤nger kÃ¶nnen ihr weiterhin sehen." ,"Really delete this post?\\nIt will only disappear your view.\\nOther recipients can still see this post.");?>')) {
                        window.location='<?= FILENAME;?>?action=delete&id=<?= $P[$i]->data["id"]; ?>';
                    } return false;" style="color:gray;"><i class="glyphicon glyphicon-remove-circle"></i></a>

                    <br>
                    <div style='float:right;font-size:0.8em;max-width:200px;'>sichtbar fÃ¼r:
                    <?php
                    /*
                    for($ri=0;$ri < count($P[$i]->data["data"]["recipientNames"]);$ri++) {
                        $P[$i]->data["data"]["recipientNames"][$ri] = "<a href='".FILENAME."?profile=".$P[$i]->data["data"]["recipients"][$ri]."'>".$P[$i]->data["data"]["recipientNames"][$ri]."</a>";
                    }
                    */
                    echo implode(", ", array_merge(array("mich"), $P[$i]->data["data"]["recipientNames"]));
                    ?><br>
                        <a href='<?= FILENAME;?>?full=<?= $P[$i]->data["id"]; ?>'><i class='glyphicon glyphicon-fullscreen'></i>&nbsp;<?= trans("vollstÃ¤ndige Ansicht", "full view");?></a>
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
            </td></tr></table>

        </div>
        <div style="height:0px;position:relative;top:-6px;width:48px;text-align:center;">
            <a href="#" onclick="expand(this);$(this).find('.updowns').toggle();return false;">
                <?php /*
                <div class='updowns' style="color:gray;"><i class="glyphicon glyphicon-chevron-down"></i><i class="glyphicon glyphicon-chevron-down"></i><i class="glyphicon glyphicon-chevron-down"></i></div>
                <div class='updowns' style="color:gray;display:none;"><i class="glyphicon glyphicon-chevron-up"></i><i class="glyphicon glyphicon-chevron-up"></i><i class="glyphicon glyphicon-chevron-up"></i></div>
                */ ?>
            </a>
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
				
				<a href='<?= FILENAME;?>?full=<?= $P[$i]->data["id"]; ?>'><i class='glyphicon glyphicon-fullscreen'></i>&nbsp;<?= trans("vollstÃ¤ndige Ansicht", "full view");?></a>
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

                <table width="100%" cellspacing="0" cellpadding="0"><tr><td valign="top" width="25">
                    <img src='<?= $senderC["miniimage"];?>'  width=25 height=25 style="float:left;border-radius: 5px;margin-right:10px;">
                </td><td valign="top">
                    <div style='float:right;'><?= formatDateHuman($comments[$j]->data["data"]["date"]); ?></div>
                    <div style='float:left;'>
                        <span style='color: #800000;font-weight: bold;'><?= $senderC["name"]; ?></span>
                    </div>
                            <div style='clear:both;'></div>
                    <div style='float:left;'>
                        <?= prepareText($comments[$j]->data["data"]["text"]);?>
                    </div>
                    <div style='clear:both;'></div>

                 </td></tr></table>
			</div>
		<?php } ?>


        <div>
            <form method="post">
                <input type="hidden" name="action" value="newcomment" />
                <input type="hidden" name="id" value="<?= $P[$i]->data["id"]; ?>" />
                <div class="input-group">
                    <input type="text" class="form-control commenttext" name="replytext"  placeholder="<?= trans("einen Kommentar auf diesen Beitrag hinterlassen", "comment on this post");?>" />
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-default"><?= trans("Ok", "ok")?></button>
                    </span>
                </div>
            </form>
        </div>

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
				    <!-- <a href='#' onclick="openreply(this);$(this).hide();return false;"><i class='glyphicon glyphicon-comment'></i>&nbsp;<?= trans("kommentieren", "add comment");?></a> -->
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

                <table width="100%" cellspacing="0" cellpadding="0"><tr><td valign="top" width="25">
                    <img src='<?= $senderC["miniimage"];?>'  width=25 height=25 style="float:left;border-radius: 5px;margin-right:10px;">
                </td><td valign="top">
                    <div style='float:right'><?= formatDateHuman($comments[$j]->data["data"]["date"]); ?></div>
                    <div style='float:left;'>
                        <span style='color: #800000;font-weight: bold;'><?= $senderC["name"]; ?></span><br/>
                        <?= prepareText($comments[$j]->data["data"]["text"]);?>
                    </div>
                    <div style='clear:both;'></div>
                </td></tr></table>
			</div>
		<?php } ?>

    </div>
	<?php } ?>

<?php if($P->data["data"]["newformcommenttype"]!='keine' || $P->data["user"]==me()) { ?>
    <div>
        <form method="post">
            <input type="hidden" name="action" value="newcomment" />
            <input type="hidden" name="id" value="<?= $P->data["id"]; ?>" />
            <div class="input-group">
            <input type="text" class="form-control commenttext" name="replytext"  placeholder="<?= trans("einen Kommentar auf diesen Beitrag hinterlassen", "comment on this post");?>" />
            <span class="input-group-btn">
                    <button type="button" class="btn btn-default"><?= trans("Ok", "ok")?></button>
            </span>

            </div>
        </form>
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
         #   )   +   $      	                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                '   .   4   ;]]]Q      	                                                                                                                                                                                                                                                                                                                                                                                                                                                                               	         !   *   3   :   H†††’çççö   *   
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    %   -   6   ?Y¶¶¶Æìììÿìììÿ   0   
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                (   0   8   ERRRy×××êìììÿìììÿìììÿ   0   
                                                                                                                                                                                                                                                                                                                                                                                                                                                               
         "   +   3   <   O®çççûìììÿìììÿìììÿìììÿ   0   
                                                                                                                                                                                                                                                                                                                                                                                                                                                                   &   .   6   B555gÈÈÈÚìììÿìììÿìììÿìììÿìììÿìììÿ   0   
                                                                                                                                                                                                                                                                                                                                                                                                                                     	             )   2   9   Itttáááôíííÿíííÿíííÿíííÿíííÿíííÿíííÿ   0   
                                                                                                                                                                                                                                                                                                                                                                                                     	   	   	   	   	   	   	   
            $   ,   4   >Tªªª»íííÿíííÿíííÿíííÿíííÿíííÿíííÿíííÿíííÿ   0   
                                                                                                                                                                                                                                                                                                                                                                      	                                                      !   (   0   7   DDDDpÑÑÑâíííÿíííÿíííÿíííÿíííÿíííÿíííÿíííÿíííÿíííÿ   0   
                                                                                                                                                                                                                                                                                                                                                    	                                  "   #   $   %   &   &   '   &   &   &   '   *   .   4   ;   K˜æææøíííÿíííÿíííÿíííÿíííÿíííÿíííÿíííÿíííÿíííÿíííÿ   0                                                                                                                                                                                                                                                                                                                                           
                     "   %   (   *   ,   -   /   0   1   2   2   3   3   3   3   3   3   3   5   9   A###^···Çîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿ   1                                                                                                                                                                                                                                                                                                                               
                  #   '   *   -   0   2   4   5   6   8   9   9   :   :   ;   ;   ;   ;   ;   ;   ;   ;   >   Ieee„ŞŞŞïîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿ   5                                                                                                                                                                                                                                                                                                                                     %   )   -   0   3   5   8   9   ;   >   A   D   G   J   K   M   N   P   Q   R   R   R   R   P   P   X¡¡¡³ëëëıîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿ   :         	                                                                                                                                                                                                                                                                                               	               $   )   .   2   5   8   :   >   C   G   KSPPPtyyy•••¦ªªªº»»»ËÈÈÈÙÓÓÓäÜÜÜíäääôèèèúìììıîîîÿìììıèèèúäääôİİİïçççøîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿîîîÿ   B   "            	                                                                                                                                                                                                                                                                               
            !   '   ,   1   4   8   <   A   G   NRRRr‡‡‡šªªª»ÆÆÆÖİİİíïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿ   J   .   '   !            
                                                                                                                                                                                                                                                                	            "   )   .   3   7   <   B   I222a“¬¬¬¼ÍÍÍŞéééúïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿ   P   7   3   .   )   "            	                                                                                                                                                                                                                                                             "   )   /   4   9   >   G---`†††–µµµÆÙÙÙêïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿ(((k   G   >   9   4   /   )   "                                                                                                                                                                                                                                                          !   (   /   5   9   B   Lmmm‚ªªªºÔÔÔåïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿïïïÿÓÓÓæªªªºmmm‚   L   B   9   5   /   (   !                                                                                                                                                                                                                                              &   -   4   9   BQ‡‡‡–¾¾¾Îèèèøğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿèèèø¾¾¾Î‡‡‡–Q   B   9   4   -   &                                                                                                                                                                                                                         
         "   +   1   8   BUÆÆÆ×ğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿÆÆÆ×U   B   8   1   +   "         
                                                                                                                                                                                                              &   .   6   >   Mˆˆˆ–ÄÄÄÕğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿÄÄÄÕˆˆˆ–   M   >   6   .   &                                                                                                                                                                                                                )   2   :   Hooo„ºººÉíííüğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿğğğÿíííüºººÉooo„   H   :   2   )                                                                                                                                                                                                     #   ,   5   A777a¤¤¤²ßßßîñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿßßßî¤¤¤²777a   A   5   ,   #                                                                                                                                                                                	         %   /   7   G}}}‹ÆÆÆÕñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿÆÆÆÕ}}}‹   G   7   /   %         	                                                                                                                                                               	         &   0   <Q   ®àààïñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿñññÿàààï   ®Q   <   0   &         	                                                                                                                                                       	         '   2   ?UUUo¸¸¸Æòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿ¸¸¸ÆUUUo   ?   2   '         	                                                                                                                                                        '   2   Buuu…ÊÊÊØòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿæææÿšššÿhhhÿNNNÿNNNÿhhhÿšššÿæææÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿÊÊÊØuuu…   B   2   '                                                                                                                                                         &   2   Dˆˆˆ”ÖÖÖåòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿÏÏÏÿ```ÿVVVÿ[[[ÿ^^^ÿ^^^ÿ[[[ÿVVVÿ```ÿÏÏÏÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿÖÖÖåˆˆˆ”   D   2   &                                                                                                                                                 %   1   D‘‘‘İİİíòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿÛÛÛÿXXXÿ\\\ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ\\\ÿXXXÿÛÛÛÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿòòòÿİİİí‘‘‘   D   1   %                                                                                                                                          #   0   D••• ãããğóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿsssÿZZZÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿXXXÿ‡‡‡ÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿãããğ•••    D   0   #                                                                                                                             
          .   B’’’ãããñóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿÜÜÜÿPPPÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿPPPÿÜÜÜÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿãããñ’’’   B   .          
                                                                                                                        +   ?ˆˆˆ”àààíóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿ¯¯¯ÿTTTÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿTTTÿ¯¯¯ÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿàààíˆˆˆ”   ?   +                                                                                                                          '   :xxx„×××åóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿ›››ÿVVVÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿ^^^ÿVVVÿ›››ÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿóóóÿ×××åxxx„   :   '                                                                                                             
      !   3VVVlÌÌÌØôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿ›››ÿWWWÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿWWWÿ›››ÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿÌÌÌØVVVl   3   !      
                                                                                                         ,IºººÄôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿ°°°ÿUUUÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿUUUÿ°°°ÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿºººÄI   ,                                                                                                           &   >¡¡¡§ôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿİİİÿQQQÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿQQQÿİİİÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿ¡¡¡§   >   &                                                                                                       2xxxßßßìôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿ‡‡‡ÿYYYÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿYYYÿ‡‡‡ÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿôôôÿßßßìxxx   2                                                                                                   (LÄÄÄÍõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿèèèÿYYYÿ]]]ÿ___ÿ___ÿ___ÿ___ÿ___ÿ___ÿ]]]ÿYYYÿèèèÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿÄÄÄÍL   (                                                                                               7¡õõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿÒÒÒÿaaaÿWWWÿ\\\ÿ___ÿ___ÿ\\\ÿWWWÿaaaÿÒÒÒÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿ¡   7                                                                                     
      )TTTdÖÖÖâõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿèèèÿœœœÿjjjÿOOOÿOOOÿjjjÿœœœÿèèèÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿõõõÿÖÖÖâTTTd   )      
                                                                                 7¬¬¬°öööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿ¬¬¬°   7                                                                                    &^^^gŞŞŞèöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿŞŞŞè^^^g   &                                                                                3ªªª¬öööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿªªª¬   3                                                                             ;;;TØØØáöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿÓÓÓÿÓÓÓÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿöööÿØØØá;;;T                                                                             *———–÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ¾¾¾ÿSSSÿSSSÿÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ———–   *                                                                  	      6ÇÇÇÊ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿkkkÿ]]]ÿ___ÿYYYÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿÇÇÇÊ   6      	                                                              ```béééó÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿPPPÿ```ÿ```ÿQQQÿêêêÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿéééó```b                                                                    &™÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿÔÔÔÿSSSÿ```ÿ```ÿTTTÿÈÈÈÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ÷÷÷ÿ™   &                                                                 .ÄÄÄÅøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿÉÉÉÿTTTÿ```ÿ```ÿWWWÿ¨¨¨ÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿÄÄÄÅ   .                                                              =âââçøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿÿXXXÿ```ÿ```ÿXXXÿÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿâââç=                                                              wwwkøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿ”””ÿYYYÿ```ÿ```ÿ\\\ÿuuuÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿwwwk                                                              ’øøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿuuuÿ\\\ÿ```ÿ```ÿ]]]ÿkkkÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿøøøÿ’                                                           
   #¸¸¸°ùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿYYYÿ```ÿaaaÿaaaÿaaaÿQQQÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿ¸¸¸°   #   
                                                     
   &ÌÌÌÈùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿQQQÿaaaÿaaaÿaaaÿaaaÿSSSÿáááÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿÌÌÌÈ   &   
                                                     
   )ÛÛÛÜùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿÊÊÊÿUUUÿaaaÿaaaÿaaaÿaaaÿUUUÿÊÊÊÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿùùùÿÛÛÛÜ   )   
                                                     
   +æææêúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿÀÀÀÿVVVÿaaaÿaaaÿaaaÿaaaÿYYYÿ   ÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿæææê   +   
                                                     
   -ñññôúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿ   ÿYYYÿaaaÿaaaÿaaaÿaaaÿZZZÿ•••ÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿñññô   -   
                                                     	   .÷÷÷ûúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿ‹‹‹ÿ[[[ÿaaaÿaaaÿaaaÿaaaÿ]]]ÿvvvÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿ÷÷÷û   .   	                                                     	   -úúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿvvvÿ]]]ÿaaaÿaaaÿaaaÿaaaÿ___ÿdddÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿúúúÿ   -   	                                                        +øøøûûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿZZZÿ```ÿaaaÿaaaÿaaaÿaaaÿaaaÿQQQÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿøøøû   +                                                           (òòòôûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿîîîÿRRRÿaaaÿaaaÿaaaÿaaaÿaaaÿaaaÿTTTÿ×××ÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿòòòô   (                                                           %èèèéûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿÌÌÌÿUUUÿaaaÿaaaÿaaaÿaaaÿaaaÿaaaÿVVVÿÁÁÁÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿèèèé   %                                                           ßßßÙûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿµµµÿWWWÿaaaÿaaaÿaaaÿaaaÿaaaÿaaaÿYYYÿ   ÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿûûûÿßßßÙ                                                              ÒÒÒÄüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿ¡¡¡ÿZZZÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿ\\\ÿ‹‹‹ÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿÒÒÒÄ                                                               ÂÂÂ©üüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿÿ]]]ÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿ^^^ÿwwwÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿÂÂÂ©                                                                ®®®‡üüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿwwwÿ^^^ÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿaaaÿ[[[ÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿ®®®‡                                                                
‹‹‹\üüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿQQQÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿQQQÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿüüüÿ‹‹‹\   
                                                              !!!'éééäıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿäääÿSSSÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿVVVÿÍÍÍÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿéééä!!!'                                                                     ÒÒÒ¼ıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿÍÍÍÿVVVÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿXXXÿ¶¶¶ÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿÒÒÒ¼                                                                        µµµ‰ıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿ«««ÿYYYÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿZZZÿ¢¢¢ÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿµµµ‰                                                                         €€€Kòòòğıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿ¢¢¢ÿZZZÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿbbbÿ]]]ÿ‚‚‚ÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿıııÿòòòğ€€€K                                                                             ÖÖÖÀşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿxxxÿ___ÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿ___ÿxxxÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿÖÖÖÀ                                                                                 ³³³‚şşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿnnnÿ```ÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿRRRÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿ³³³‚                                                                                  bbb4æææÛşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿRRRÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿSSSÿñññÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿæææÛbbb4                                                                                     ÃÃÃ›şşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿÚÚÚÿUUUÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿWWWÿÎÎÎÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿÃÃÃ›                                                                                         ‰‰‰HëëëâşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿÃÃÃÿWWWÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿYYYÿ···ÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿëëëâ‰‰‰H                                                                                             ÆÆÆşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿ¢¢¢ÿZZZÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿZZZÿ¢¢¢ÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿşşşÿÆÆÆ                                                                                                 CçççÛÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿƒƒƒÿ^^^ÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿ___ÿyyyÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿçççÛC                                                                                                     ½½½‹ÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿyyyÿ___ÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿaaaÿeeeÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿ½½½‹                                                                                                         :::#ÙÙÙÁÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿRRRÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿRRRÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÙÙÙÁ:::#                                                                                                             
¦¦¦aîîîçÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿRRRÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿSSSÿòòòÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿîîîç¦¦¦a   
                                                                                                                 ÁÁÁ‘ÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿæææÿTTTÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿWWWÿÏÏÏÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÁÁÁ‘                                                                                                                         ###ÓÓÓ´ÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÏÏÏÿWWWÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿWWWÿÏÏÏÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÓÓÓ´###                                                                                                                              ŒŒŒEàààÍÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÛÛÛÿUUUÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿWWWÿÏÏÏÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿàààÍŒŒŒE                                                                                                                                     §§§céééŞÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿRRRÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿTTTÿæææÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿéééŞ§§§c                                                                                                                                            µµµuğğğèÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿeeeÿaaaÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿbbbÿ\\\ÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿğğğèµµµu                                                                                                                                                    ºººòòòíÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿ¢¢¢ÿZZZÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿ]]]ÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿòòòíººº                                                                                                                                                            ½½½„òòòìÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿòòòÿ\\\ÿbbbÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿcccÿbbbÿ]]]ÿæææÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿòòòì½½½„                                                                                                                                                                    ºººğğğçÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÏÏÏÿ^^^ÿ___ÿcccÿcccÿcccÿcccÿcccÿcccÿ___ÿUUUÿÃÃÃÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿğğğçººº                                                                                                                                                                            µµµuéééİÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÛÛÛÿyyyÿTTTÿYYYÿZZZÿZZZÿZZZÿUUUÿyyyÿÛÛÛÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿéééİµµµu                                                                                                                                                                                    §§§báááÌÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿæææÿ¸¸¸ÿ¢¢¢ÿ¢¢¢ÿ¬¬¬ÿÛÛÛÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿáááÌ§§§b                                                                                                                                                                                            EÕÕÕµÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÕÕÕµE                                                                                                                                                                                                    :::ÅÅÅ•ñññëÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿñññëÅÅÅ•:::                                                                                                                                                                                                               ¯¯¯iŞŞŞÉÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿŞŞŞÉ¯¯¯i                                                                                                                                                                                                                           rrr1ÇÇÇ›ñññéÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿñññéÇÇÇ›rrr1                                                                                                                                                                                                                                       ¥¥¥^×××¹ıııûÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿıııû×××¹¥¥¥^                                                                                                                                                                                                                                                      ¸¸¸vŞŞŞÈÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿŞŞŞÈ¸¸¸v                                                                                                                                                                                                                                                                   666!»»»ßßßÊÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿßßßÊ»»»666!                                                                                                                                                                                                                                                                               +++¶¶¶vÚÚÚ¿ùùùöÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿùùùöÚÚÚ¿¶¶¶v+++                                                                                                                                                                                                                                                                                              
   ¤¤¤\ÍÍÍ¤êêêİÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿêêêİÍÍÍ¤¤¤¤\      
                                                                                                                                                                                                                                                                                                               bbb/¶¶¶vÕÕÕ´îîîäÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿîîîäÕÕÕ´¶¶¶vbbb/                                                                                                                                                                                                                                                                                                                                      
   hhh1°°°qÎÎÎ§æææÔûûûøÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿûûûøæææÔÎÎÎ§°°°qhhh1      
                                                                                                                                                                                                                                                                                                                                                       	      ŒŒŒG¶¶¶zÎÎÎ¥áááÉñññèÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿñññèáááÉÎÎÎ¥¶¶¶zŒŒŒG         	                                                                                                                                                                                                                                                                                                                                                                                  
      ŠŠŠH¬¬¬lÀÀÀŠÎÎÎ¥ÙÙÙ»âââÍêêêÜñññè÷÷÷ñûûûøııııÿÿÿÿııııûûûø÷÷÷ññññèêêêÜâââÍÙÙÙ»ÎÎÎ¥ÀÀÀŠ¬¬¬lŠŠŠH         
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               ÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿğÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿàÿÿÿÿÿÿÿÿÿÿÿÿÿÿ€?ÿÿÿÿÿÿÿÿÿÿÿÿÿÿ ?ÿÿÿÿÿÿÿÿÿÿÿÿÿü ?ÿÿÿÿÿÿÿÿÿÿÿÿÿø ?ÿÿÿÿÿÿÿÿÿÿÿÿÿğ ?ÿÿÿÿÿÿÿÿÿÿÿÿÿÀ ?ÿÿÿÿÿÿÿÿÿÿÿÿÿ€ ?ÿÿÿÿÿÿÿÿÿÿÿÿÿ  ?ÿÿÿÿÿÿÿÿÿÿÿÿü  ?ÿÿÿÿÿÿÿÿÿÿÿÿğ  ?ÿÿÿÿÿÿÿÿÿÿø    ?ÿÿÿÿÿÿÿÿÿÿ     ?ÿÿÿÿÿÿÿÿÿğ     ?ÿÿÿÿÿÿÿÿÿ€     ?ÿÿÿÿÿÿÿÿü      ÿÿÿÿÿÿÿÿğ      ÿÿÿÿÿÿÿÿÀ      ÿÿÿÿÿÿÿÿ        ÿÿÿÿÿÿü        ÿÿÿÿÿÿø        ÿÿÿÿÿÿà        ÿÿÿÿÿÿÀ        ÿÿÿÿÿÿ          ÿÿÿÿş          ?ÿÿÿÿü          ÿÿÿÿø          ÿÿÿÿğ          ÿÿÿÿà          ÿÿÿÿÀ          ÿÿÿÿ€           ÿÿÿÿ            ÿÿş            ?ÿÿü            ÿÿü            ÿÿø            ÿÿğ            ÿÿğ            ÿÿà            ÿÿà            ÿÿÀ            ÿÿÀ            ÿÿ€             ÿÿ€             ÿÿ              ÿ              ÿ              ş              ?ş              ?ş              ?ü              ü              ü              ü              ü              ü              ü              ü              ü              ü              ü              ü              ü              ü              ü              ü              ü              ü              ü              ş              ?ş              ?ş              ?ÿ              ÿ              ÿ              ÿ€             ÿÿ€             ÿÿÀ            ÿÿÀ            ÿÿÀ            ÿÿà            ÿÿà            ÿÿğ            ÿÿğ            ÿÿø            ÿÿø            ÿÿü            ÿÿş            ?ÿÿÿ            ÿÿÿ            ÿÿÿ€           ÿÿÿÿÀ          ÿÿÿÿà          ÿÿÿÿğ          ÿÿÿÿø          ÿÿÿÿü          ÿÿÿÿş          ?ÿÿÿÿÿ          ÿÿÿÿÿ€         ÿÿÿÿÿÿà        ÿÿÿÿÿÿğ        ÿÿÿÿÿÿü        ÿÿÿÿÿÿÿ        ÿÿÿÿÿÿÿ€       ÿÿÿÿÿÿÿÿà      ÿÿÿÿÿÿÿÿü      ÿÿÿÿÿÿÿÿÿ      ÿÿÿÿÿÿÿÿÿà    ÿÿÿÿÿÿÿÿÿÿü    ÿÿÿÿÿÿÿÿÿÿÿÀ  ÿÿÿÿÿÿÿÿÿÿÿÿÿ  ÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿ‰PNG

   IHDR   €   €   Ã>aË   sRGB ®Îé   bKGD ÿ ÿ ÿ ½§“   	pHYs     šœ   tIMEŞ	"«®Áº  «IDATxÚí]ypWşÔ’e–/Y–Œ!KXØå`c”	’™!ÇlQI¦R$l°C6W¥vB2•2)²a—d'ì€‹À$dcœc0d²NÌa3À_0Á|ÈäC¶,u÷şá4=²-ù€VÓ_Õ+TF¥êßû¾÷;Ş{ı B„"Dˆ!B„"Dˆ!B„áC"Úh±sBÇ	 )Ód¬Ï§I8öÓL£8dš—õ™Š0$!şì>²Ã|mÒ¤Iª7ŞxÃšššj‰‹‹K‰ŒŒLT«ÕñJ¥2V¡PD…‡‡GÈår¥L&'"Œ ‚„ÇëõºúÜnwO¿³¯¯ïjooo{WW×åæ¦¦¦së×¯¯»råŠ€‡ÕH–€DLğóú—P®[·.Ójµf†iZ­ÖmŒŒŒÔOÔtuuµ]¿~ıbggç¹ÖÖÖÚººº¿¯^½úÿ ôàBÀ8’. X¾|¹qáÂ…§¤¤ØC†N§³Èd²ğ;õp^¯×íp8Îµ¶¶nnn®,--ıñ‹/¾¸ Ÿ¯ÅÀgÌHW.]ºÔøôÓO/0%$$Ü“È×‡¾víÚå–––S/^,ÿöÛo÷\d¼ƒ‡É)DŒğ<€p Û¶m{4--marrò,N—jñÕáp4ıúë¯GêëëKŸ{î¹ z ¸!¶²‹›WO›6-¥¤¤äÍÆÆÆ¿¹İn- ¸İnWccãßJJJŞœ6mZ
 5c¯D¤!Şf³™ÊÊÊştáÂ…JZÀ¸páBeYYÙŸl6›‰%„»  }iié›MMMGé»MMMGKKKß gú¸›Ü}€ØíÛ·ÿ±¾¾~?I’ú.I’úúúıÛ·oÿ#€X¦_$Bõê•+WN?räÈ_Ng-‚v:mGùëÊ•+§3aâ¨—ˆ-**Êmjj:&Òî7,+**Êe¼L(Ş@ Ün·+**>w:‘êa½£¢¢âs»İndÊaIÈ»üõë×Ï¯­­İ+Ò8jkk÷®_¿~şD‡É“QXXø´İnÏKIIÉæ“2[ZZP]]ööv €J¥Bff&,o±¹¹ùï‡ş¯%K–|ËL"Q¡" )€È½{÷¾4cÆŒåñññ¼™Å;wî


pöìY„……A"¹Ù‹K—.åÚÛÛ›?şÅ¢E‹6èbÖx- )€¨¼n³Ùş%**ÊÀòO:…O?ı‰2™l¨Å¸\.¼öÚkxğÁyñÜN§³µ²²ò¿}ôÑÿ àOH&‚üC‡­ÌÉÉY®Ñh´|!¿»»yyyËå·Œz ik×®EBB_¿³ªªê‹¹sçşûxŠ`<“@ä^ÏÉÉÉåù °{÷nP5"ù n|§°°7Ï¯Ñh´999¹x@äxqGŒ#ùš½{÷¾d³Ù^Öh4±|+G~úé'„‡¾m ,,'Oä•&Öf³½¼wïŞ— hÆƒ¿ñ€€²°°ğé3fäFEEéÁCtvv‚ 7W"‘ ¿¿---¼²#**J?cÆŒÜÂÂÂ§(ÇÆÇC òuëÖÙgÍš•oâ#ùçÎƒTü¢›T*E[[ïì‰7Íš5+oİºuvî”ºcšÍæø¼n4³ø:ÕÓÓÔèÆ¬¼n6›ã1†%ebŒâ‰*((X¾ˆÏ¥×ëA’$„†ôôôE«D–ËÑ
@@±cÇßeddüï• Š
~ÍëõÂl6óÚ¶ŒŒŒ?ìØ±ãwÜS ¹] ìv{Bvvö1114MC£ÑğúcbbÙÙÙ/Øíö„ÑğIŒrôG|øá‡Ë§NúP¨yÏ=÷Àëõü}Š¢ ÕjCÂ¶©S§>ôá‡.¬ äkÖ¬™aµZJ£Y¥R¦é 2öY­Öß¯Y³fF°U1ŠÑ¯?ş³z½ŞBÓ4B¥Æ <€ÇãAZZZÈØ§×ë-óçÏƒËÇ’‰€|íÚµ¶)S¦Ìµxn2™‚ªhš†N§)§L™2oíÚµ¶`¼ äèWÙíö§´Zmr¨	@¯×U	P½^R6jµÚd»İş U ^ È–.]j6›Í‡bFo±X‚ò ¡PúƒÙl~xéÒ¥fî)W(/^ü[ƒÁ`Aˆ")))`„B	èƒÁ²xñâß2óã& )€³ÙüBV^¯S§NY;"Àq sÈò÷ßÿ¼¦ÕimmEccã»| Iƒ³gÏI;•JeLxxø‘üñŒ°q$ ““óZ­
e T*ò $IbòäÉ!k§Z­ÊÉÉyl+T Êäääl„8RSSÊBmÈ¾”ã! Âd2Eëõú{C] ®
†b	èÇÖ{M&SôHŒ~y^^^V«M¥™?Í`04àñx™™Ò¶jµÚÄ¼¼¼fRH2 7›ÍÓ L:uØ)aš¦¡R©a+Ã›|,€  ‹‹KŠ F*I’DRR’ lex“Ç31‚û— EGG'
E “'O6BèÃ›ÿx(æŒ4](¦ÑhtÁ,¥ò|T€$É!½ I’Ğét‚½F‡(	T*•ÑBñ #-
‘$	“É$[ŞFŞ8ŠU¥R©…"€‘æ(ŠB||¼Pòöid’Ñz ©\.Š 4
Å°!€O¯ˆoÃGH@!J ®
¶´´üÃš€o Pl%B†›'£z@B’¤‚N§ó›P…ØØXÁØÉğ6¦©`	 z``À}7€$IFÁØÉğF%	 ª··×%$5@Ó4ÔjÁä»`x£8|•øĞ¥ÕjuBé˜¡f½^/¬V«`r€ŞŞŞ.Œp®P 9 ét:;„ä233ı®a†·Qoñ²£££ƒ¿R¢(ÂyÓáäğT  x®\¹Ò,¤2Ğ_)H’$&Mš!ÙyåÊ•fŒpQE !ÀsæÌ™3Bó F£ñ–DPHËÀ>0¼yÆ’Ğ 6oŞ\İŞŞ.¨0ÀM½^/ÒÒÒc_{{{ëæÍ›«1xo=PÎæææ&!	`Ú´i·$‚Bó ÍÍÍMNŒp=lòi&‰èoll¬ÉÎÎ¶¥ƒÔjõ-À·X(9@ccco.öNÃ@< 	 ¯¼¼üXWWWP`6›oÉH’Djª06>uuuõ”——Ãàmeä˜C €-[¶Ô455Õ)$&&ŞØ¢P(BòU0hjjªß²eKÿ©Ñ
€}—î €S§N’ zê)¸\.ôôô`Ş¼y‚±‹á©‡#€QÍø@pmÛ¶í‡9sæ,JIIIBGÍœ9óÆ f³Yñ¿¹¹¹yÛ¶m? p1¼Úpó€şŠŠŠÕÕÕUBòf³9$_
ÕÕÕU8	à˜æ|` @÷={ÊZ[[Û ‚whmmmÛ³gO€nÜ¼·xÜ<€€këÖ­§Nœ8qTìnşáÄ‰G·nİzŠqÿŞ@<€,Àß¦˜ ĞµsçÎ=V«5Ëh4&‡bGuwwcÏ=8qâ._¾|£"ÈÊÊÂã?’ÕÀÅ‹İ¹sçŞ*2Àğ5â{pàÛUJ  êêê\YYYºôôôÌPë¨óçÏãÏş3Îœ9¯×•J…Bşş~œ={ååå¸ç{BnkXiiiq~~ş^ WĞÕõÁ2ì»äYrüøñÖÙ³gß›À—+5ùï½÷€Áû ØH™L’$Q^^9sæu¿ÀÄÉ“'Oäåå}ŞİİİÂ”ığºúÑ€  íîî&ããã‰´´´tUˆL¢oÜ¸mmmÃ"‘H@’$ZZZ`³Ùxo“Ãáè,,,Ü¾oß¾ãŒûwağŠúG I 7ÀàcO~~ş¡òòò²P ¿­­•••ËG>BO.—£²²’—wpQ^^^–ŸŸˆù}ºşÑ
€º \]±bÅÖÃ‡Wğ½£NŸ>=âÙ@·dÇ2NŸ>Ík›>\±bÅŠ­œ¸ï©ô­ ¸^ @owwwû¦M›v644ğzÓˆÃáêÒ‚ àp8xkOCCÃ™M›6íìîînĞËğÔèMàO@’ÚÚZgll¬Çd2Y"##yYCÕÔÔàüùó{ßA‘ééé¼³åÒ¥KW
wlÜ¸±ƒWÈõ2îß¬ FsZ¸ÏxÕ¹ 8óóóËŠ‹‹‹:;;¯òQ qqqAËÇs:;;¯åçç—1ä»XYPä‡`ÏĞ¼˜šš*7™Lf…BÁ«J­V£´´4àÒ®¯¯Ï?ÿ<"""xcÃõë×»Š‹‹¿[µjÕ7LÜïá€ºİ 9B öíÛ×d±XäF£Ñ¤P(xs¨dDDª««ÑÕÕ5b.000€ììl<òÈ#|"ßùı÷ßï~õÕWw èdÈïeeşA“?^ 9€,))97yòdibb¢Q¥R)ùÒ‰iiiøá‡ ‘H†¼A”¢(P…+Vğæ51‡Ãqµ¸¸¸ˆE~/‹ü€kş‰
şBYZZz~Ò¤IŞ„„„$¾$†°Z­¨ªªB__$Éo@’$U«V!9™Ë—.]ºRTT´kõêÕß2nŸK¾Ãlø¸ ¸"8xğà•JÕ¥×ëããx’QÅÆÆböìÙğz½¸zõ*úûû‡x ¹¹¹¼y;¨¡¡áÌöíÛ?øàƒ ×8ä÷[ó5ZÇkÔK1x ‘ƒ×–øšæ™g¹ïÅ_üg»İ>"ä9²yóæ¯wíÚu
ƒëû½¬6ê¬"= í§ UWW×¹ÿşSÉÉÉr½^ŸÀ§äo¸víÚõ}ûöíËÍÍıKUUÕ9ù®ñ$"r€¡D@»\®¾İ»w×j4šëQQQÑñññ:‘î[Q[[[÷Í7ß|½råÊ].—ËÁîâ¸ıq#¢’@önb®¼åååkjjê¢££	N¯T*•w;ñW¯^½vğàÁƒ|ğAÁ;ªX<.?1ÜÈŸ(°ç(V»ñ¦ÑåË—ß}÷]5€6•J¥0‰DBÜmÄ“$IVUUÿê«¯¾~ë­·şçòåËWX®Şå'ÛWòÇ3	ê·	n;ó%‡J¦)X-*??ÿŸ|ğÁß¤§§[ïòkjjê~şùçÿ}ûí·÷1#¾ŸÕú8.ÌÙş {rÈW!„s„ gş¦Š‹‹Ó¾ûî»gÎœi·Z­÷
•øººº†£Gşè£J;:::qsÇ ‡x7'ŞÓEn“|ŞÀGºÏ„3-€2***êí·ßÿı÷Ï´Z­V¾­)ŒıııîººººcÇÍÏÏ/s:NÜ\½s3­Ÿåî¹ëúôD’s»pc;C¶œ|"3"QP½úê«¶Ù³gÏ´Z­Ó'…ñ—.]j©¯¯¯ùé§ŸnØ°¡’í}¸¹©†M¾o=ß7ê©‰$şNÀŸ7`-‚0VSfdd$,Y²Ä–™™yŸÅb±„J	9kÖ¬çX£İ×¸äø‰õôí"äNàÆsÂ9!!ŒûÿYYY†'x"+###Íd2¥&%%%Éåò0>
 &&f1€†XÇå»ıOİ.âï´ ØŞÀ—$Ê89‚œãØß¹!ˆ'Ÿ|Ò’““s¯Ùl6%%%%êõz},O6õÇÄÄ,p‰Eö 'Æ{9I}'H¸Óà
AÊ"]î'$ÈXßc{ ™B¡P,\¸Ğ”–––˜””¤×étq1111&R­V«•J¥R¡P„Ëd2¹L&“APEy½^Òëõ¸İnJ¥R†ÃK±±±sü‚›köVŒ¿£ÄóI C	Mn˜p… å$šÜæû?ö¿\û}+šŠ/¿üòÙÇ{ì1©TJŒA  8[÷ëñ‚x>
€ûL«IY! Œ;ê9D‚ÙÇ¥K´[
@½lÙ²ŞyçW¢£££G) €FÜ<ªÅ'0šoAÂI¥AÈXŸ‡Áñ0#Ùîó>
³ÙœüÙgŸåŞÿı3G!€, gà»ú¢ ‚ƒ„C´Ô@†û7¹{¥œ„4zÍš5O,[¶ì…`r­V;# ˆ1p ñÃy	‡|ß…fŞ¼yéŸ|òÉÊ¤¤¤ä Ğˆ›ûöDL° 0ÉÁä >à› b/^©"##ã7mÚôòÜ¹sç	A RÜ½ì‰ßg’ÓüıÍßwnü®ÛívíÚµë$EQéÓ§gÈ‡yÛôã?ŞˆÁ]¼¼ŒÿBñ Y…Hqëâ•Šå"rrr¦lØ°a•Åb™âï‡âââxï‘ï!=‰ïMh7SÆõ`pï…gUUUíÌ™3ÿµ¸¸¸Èãñ„äÅZ¢ †û„´>†ø–z ´½øâ‹yå•WşÍÁç×‰EŒ9,øÂ@,€$ S d °øÁ`XRRRr¸££ƒîèè ÜÇ„©Ø…Âf‰@4   +€l v ?úè£ÿliiq1çµ De>?ğ·á•à>tèPC{{ûşêêê_póÈV±
˜7ğMÉXU‚oŸ#Á$N&Op#€»îd"—£öÀ­9|§©ú.lö"ÀÃE„v8 9ƒ‡éWŠùL‹¸;ÊEö;¾òšäsüs€‰«$~Ä!
à.¿|A„"Dˆ!B„"x€ÿ0©!{¸”    IEND®B`‚/*! jQuery v1.10.2 | (c) 2005, 2013 jQuery Foundation, Inc. | jquery.org/license
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
var sendData = [];
function sendnewpost(obj) {
    sendData = [];
    var postText = $(obj).find('.newposttext').val();
    $(obj).find('.newposttext').val("");

	var data = {
		"action": "newpost",
		"type": "ajax",
		"newposttext": postText,
		"newformrecipienttype": $(obj).find('.newformrecipienttype').val(),
		"newformcommenttype": $(obj).find('.newformcommenttype').val(),
		"newformeditable": $(obj).find('.newformeditable').val(), 
		"group":  $(obj).find('.group').val(),
        "recipients": []
	};



    if(data.newformrecipienttype=="ausgewaehlte") {
        var recipients = [];
		$('.recipientcheckbox:checked').each(function() {
			recipients.push($(this).val());
		});
		data.recipients = recipients; 
	}

    //if(window.location != window.parent.location) {
        // Im Frame
//        sendPostAjax(data);
//    } else {
        sendData = data;
        ownUnityCrypto.loadPubKeys(function() {

            var cryptedText = {};

            var R = sendData.recipients;
            R.push("mine");

            for(var i=0;i<R.length;i++) {
                var id = R[i];
                if(ownUnityCrypto.pubkeys[id] && typeof(ownUnityCrypto.pubkeys[id])!="undefined" && ownUnityCrypto.pubkeys[id]!="") {
                    var key = ownUnityCrypto.pubkeys[id];
                    var publicKey = openpgp.key.readArmored(key[0]);
                    cryptedText[id] = openpgp.encryptMessage(publicKey.keys, sendData.newposttext);
                } else {
                    cryptedText[id] = sendData.newposttext;
                }
            }

            sendData.newposttext = cryptedText;

            sendPostAjax(sendData);
        });


  //  }


}

function sendPostAjax(data) {
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
                    ownUnityCrypto.decryptMessages();
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

    ownUnityCrypto.decryptMessages();


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

}


function readStoreJson(key, defaultResult) {
    var v = readStore(key);
    if(typeof(v)=="string" && v!='') return JSON.parse(v);
    else return defaultResult;
}
function readStoreInt(key) {
    var v = readStore(key);
    if(typeof(v)=="number") return v*1;
    if(typeof(v)=="string" && v!='') return parseInt(v);
    else return 0;
}
function readStore(key) {
    var val = jQuery.jStorage.get(key)
    if(typeof(val) == 'undefined' || val===null) val = "";
    return val;
}
function writeStoreJson(keyinput, valinput) {
    writeStore(keyinput, JSON.stringify(valinput));
    return;
}
function writeStore(keyinput, valinput) {
    jQuery.jStorage.set(keyinput, valinput);
    return;
}
/*
    http://www.JSON.org/json2.js
    2011-02-23

    Public Domain.

    NO WARRANTY EXPRESSED OR IMPLIED. USE AT YOUR OWN RISK.

    See http://www.JSON.org/js.html


    This code should be minified before deployment.
    See http://javascript.crockford.com/jsmin.html

    USE YOUR OWN COPY. IT IS EXTREMELY UNWISE TO LOAD CODE FROM SERVERS YOU DO
    NOT CONTROL.


    This file creates a global JSON object containing two methods: stringify
    and parse.

        JSON.stringify(value, replacer, space)
            value       any JavaScript value, usually an object or array.

            replacer    an optional parameter that determines how object
                        values are stringified for objects. It can be a
                        function or an array of strings.

            space       an optional parameter that specifies the indentation
                        of nested structures. If it is omitted, the text will
                        be packed without extra whitespace. If it is a number,
                        it will specify the number of spaces to indent at each
                        level. If it is a string (such as '\t' or '&nbsp;'),
                        it contains the characters used to indent at each level.

            This method produces a JSON text from a JavaScript value.

            When an object value is found, if the object contains a toJSON
            method, its toJSON method will be called and the result will be
            stringified. A toJSON method does not serialize: it returns the
            value represented by the name/value pair that should be serialized,
            or undefined if nothing should be serialized. The toJSON method
            will be passed the key associated with the value, and this will be
            bound to the value

            For example, this would serialize Dates as ISO strings.

                Date.prototype.toJSON = function (key) {
                    function f(n) {
                        // Format integers to have at least two digits.
                        return n < 10 ? '0' + n : n;
                    }

                    return this.getUTCFullYear()   + '-' +
                         f(this.getUTCMonth() + 1) + '-' +
                         f(this.getUTCDate())      + 'T' +
                         f(this.getUTCHours())     + ':' +
                         f(this.getUTCMinutes())   + ':' +
                         f(this.getUTCSeconds())   + 'Z';
                };

            You can provide an optional replacer method. It will be passed the
            key and value of each member, with this bound to the containing
            object. The value that is returned from your method will be
            serialized. If your method returns undefined, then the member will
            be excluded from the serialization.

            If the replacer parameter is an array of strings, then it will be
            used to select the members to be serialized. It filters the results
            such that only members with keys listed in the replacer array are
            stringified.

            Values that do not have JSON representations, such as undefined or
            functions, will not be serialized. Such values in objects will be
            dropped; in arrays they will be replaced with null. You can use
            a replacer function to replace those with JSON values.
            JSON.stringify(undefined) returns undefined.

            The optional space parameter produces a stringification of the
            value that is filled with line breaks and indentation to make it
            easier to read.

            If the space parameter is a non-empty string, then that string will
            be used for indentation. If the space parameter is a number, then
            the indentation will be that many spaces.

            Example:

            text = JSON.stringify(['e', {pluribus: 'unum'}]);
            // text is '["e",{"pluribus":"unum"}]'


            text = JSON.stringify(['e', {pluribus: 'unum'}], null, '\t');
            // text is '[\n\t"e",\n\t{\n\t\t"pluribus": "unum"\n\t}\n]'

            text = JSON.stringify([new Date()], function (key, value) {
                return this[key] instanceof Date ?
                    'Date(' + this[key] + ')' : value;
            });
            // text is '["Date(---current time---)"]'


        JSON.parse(text, reviver)
            This method parses a JSON text to produce an object or array.
            It can throw a SyntaxError exception.

            The optional reviver parameter is a function that can filter and
            transform the results. It receives each of the keys and values,
            and its return value is used instead of the original value.
            If it returns what it received, then the structure is not modified.
            If it returns undefined then the member is deleted.

            Example:

            // Parse the text. Values that look like ISO date strings will
            // be converted to Date objects.

            myData = JSON.parse(text, function (key, value) {
                var a;
                if (typeof value === 'string') {
                    a =
/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2}(?:\.\d*)?)Z$/.exec(value);
                    if (a) {
                        return new Date(Date.UTC(+a[1], +a[2] - 1, +a[3], +a[4],
                            +a[5], +a[6]));
                    }
                }
                return value;
            });

            myData = JSON.parse('["Date(09/09/2001)"]', function (key, value) {
                var d;
                if (typeof value === 'string' &&
                        value.slice(0, 5) === 'Date(' &&
                        value.slice(-1) === ')') {
                    d = new Date(value.slice(5, -1));
                    if (d) {
                        return d;
                    }
                }
                return value;
            });


    This is a reference implementation. You are free to copy, modify, or
    redistribute.
*/

/*jslint evil: true, strict: false, regexp: false */

/*members "", "\b", "\t", "\n", "\f", "\r", "\"", JSON, "\\", apply,
    call, charCodeAt, getUTCDate, getUTCFullYear, getUTCHours,
    getUTCMinutes, getUTCMonth, getUTCSeconds, hasOwnProperty, join,
    lastIndex, length, parse, prototype, push, replace, slice, stringify,
    test, toJSON, toString, valueOf
*/


// Create a JSON object only if one does not already exist. We create the
// methods in a closure to avoid creating global variables.

var JSON;
if (!JSON) {
    JSON = {};
}

(function () {
    "use strict";

    function f(n) {
        // Format integers to have at least two digits.
        return n < 10 ? '0' + n : n;
    }

    if (typeof Date.prototype.toJSON !== 'function') {

        Date.prototype.toJSON = function (key) {

            return isFinite(this.valueOf()) ?
                this.getUTCFullYear()     + '-' +
                f(this.getUTCMonth() + 1) + '-' +
                f(this.getUTCDate())      + 'T' +
                f(this.getUTCHours())     + ':' +
                f(this.getUTCMinutes())   + ':' +
                f(this.getUTCSeconds())   + 'Z' : null;
        };

        String.prototype.toJSON      =
            Number.prototype.toJSON  =
            Boolean.prototype.toJSON = function (key) {
                return this.valueOf();
            };
    }

    var cx = /[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,
        escapable = /[\\\"\x00-\x1f\x7f-\x9f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,
        gap,
        indent,
        meta = {    // table of character substitutions
            '\b': '\\b',
            '\t': '\\t',
            '\n': '\\n',
            '\f': '\\f',
            '\r': '\\r',
            '"' : '\\"',
            '\\': '\\\\'
        },
        rep;


    function quote(string) {

// If the string contains no control characters, no quote characters, and no
// backslash characters, then we can safely slap some quotes around it.
// Otherwise we must also replace the offending characters with safe escape
// sequences.

        escapable.lastIndex = 0;
        return escapable.test(string) ? '"' + string.replace(escapable, function (a) {
            var c = meta[a];
            return typeof c === 'string' ? c :
                '\\u' + ('0000' + a.charCodeAt(0).toString(16)).slice(-4);
        }) + '"' : '"' + string + '"';
    }


    function str(key, holder) {

// Produce a string from holder[key].

        var i,          // The loop counter.
            k,          // The member key.
            v,          // The member value.
            length,
            mind = gap,
            partial,
            value = holder[key];

// If the value has a toJSON method, call it to obtain a replacement value.

        if (value && typeof value === 'object' &&
                typeof value.toJSON === 'function') {
            value = value.toJSON(key);
        }

// If we were called with a replacer function, then call the replacer to
// obtain a replacement value.

        if (typeof rep === 'function') {
            value = rep.call(holder, key, value);
        }

// What happens next depends on the value's type.

        switch (typeof value) {
        case 'string':
            return quote(value);

        case 'number':

// JSON numbers must be finite. Encode non-finite numbers as null.

            return isFinite(value) ? String(value) : 'null';

        case 'boolean':
        case 'null':

// If the value is a boolean or null, convert it to a string. Note:
// typeof null does not produce 'null'. The case is included here in
// the remote chance that this gets fixed someday.

            return String(value);

// If the type is 'object', we might be dealing with an object or an array or
// null.

        case 'object':

// Due to a specification blunder in ECMAScript, typeof null is 'object',
// so watch out for that case.

            if (!value) {
                return 'null';
            }

// Make an array to hold the partial results of stringifying this object value.

            gap += indent;
            partial = [];

// Is the value an array?

            if (Object.prototype.toString.apply(value) === '[object Array]') {

// The value is an array. Stringify every element. Use null as a placeholder
// for non-JSON values.

                length = value.length;
                for (i = 0; i < length; i += 1) {
                    partial[i] = str(i, value) || 'null';
                }

// Join all of the elements together, separated with commas, and wrap them in
// brackets.

                v = partial.length === 0 ? '[]' : gap ?
                    '[\n' + gap + partial.join(',\n' + gap) + '\n' + mind + ']' :
                    '[' + partial.join(',') + ']';
                gap = mind;
                return v;
            }

// If the replacer is an array, use it to select the members to be stringified.

            if (rep && typeof rep === 'object') {
                length = rep.length;
                for (i = 0; i < length; i += 1) {
                    if (typeof rep[i] === 'string') {
                        k = rep[i];
                        v = str(k, value);
                        if (v) {
                            partial.push(quote(k) + (gap ? ': ' : ':') + v);
                        }
                    }
                }
            } else {

// Otherwise, iterate through all of the keys in the object.

                for (k in value) {
                    if (Object.prototype.hasOwnProperty.call(value, k)) {
                        v = str(k, value);
                        if (v) {
                            partial.push(quote(k) + (gap ? ': ' : ':') + v);
                        }
                    }
                }
            }

// Join all of the member texts together, separated with commas,
// and wrap them in braces.

            v = partial.length === 0 ? '{}' : gap ?
                '{\n' + gap + partial.join(',\n' + gap) + '\n' + mind + '}' :
                '{' + partial.join(',') + '}';
            gap = mind;
            return v;
        }
    }

// If the JSON object does not yet have a stringify method, give it one.

    if (typeof JSON.stringify !== 'function') {
        JSON.stringify = function (value, replacer, space) {

// The stringify method takes a value and an optional replacer, and an optional
// space parameter, and returns a JSON text. The replacer can be a function
// that can replace values, or an array of strings that will select the keys.
// A default replacer method can be provided. Use of the space parameter can
// produce text that is more easily readable.

            var i;
            gap = '';
            indent = '';

// If the space parameter is a number, make an indent string containing that
// many spaces.

            if (typeof space === 'number') {
                for (i = 0; i < space; i += 1) {
                    indent += ' ';
                }

// If the space parameter is a string, it will be used as the indent string.

            } else if (typeof space === 'string') {
                indent = space;
            }

// If there is a replacer, it must be a function or an array.
// Otherwise, throw an error.

            rep = replacer;
            if (replacer && typeof replacer !== 'function' &&
                    (typeof replacer !== 'object' ||
                    typeof replacer.length !== 'number')) {
                throw new Error('JSON.stringify');
            }

// Make a fake root object containing our value under the key of ''.
// Return the result of stringifying the value.

            return str('', {'': value});
        };
    }


// If the JSON object does not yet have a parse method, give it one.

    if (typeof JSON.parse !== 'function') {
        JSON.parse = function (text, reviver) {

// The parse method takes a text and an optional reviver function, and returns
// a JavaScript value if the text is a valid JSON text.

            var j;

            function walk(holder, key) {

// The walk method is used to recursively walk the resulting structure so
// that modifications can be made.

                var k, v, value = holder[key];
                if (value && typeof value === 'object') {
                    for (k in value) {
                        if (Object.prototype.hasOwnProperty.call(value, k)) {
                            v = walk(value, k);
                            if (v !== undefined) {
                                value[k] = v;
                            } else {
                                delete value[k];
                            }
                        }
                    }
                }
                return reviver.call(holder, key, value);
            }


// Parsing happens in four stages. In the first stage, we replace certain
// Unicode characters with escape sequences. JavaScript handles many characters
// incorrectly, either silently deleting them, or treating them as line endings.

            text = String(text);
            cx.lastIndex = 0;
            if (cx.test(text)) {
                text = text.replace(cx, function (a) {
                    return '\\u' +
                        ('0000' + a.charCodeAt(0).toString(16)).slice(-4);
                });
            }

// In the second stage, we run the text against regular expressions that look
// for non-JSON patterns. We are especially concerned with '()' and 'new'
// because they can cause invocation, and '=' because it can cause mutation.
// But just to be safe, we want to reject all unexpected forms.

// We split the second stage into 4 regexp operations in order to work around
// crippling inefficiencies in IE's and Safari's regexp engines. First we
// replace the JSON backslash pairs with '@' (a non-JSON character). Second, we
// replace all simple value tokens with ']' characters. Third, we delete all
// open brackets that follow a colon or comma or that begin the text. Finally,
// we look to see that the remaining characters are only whitespace or ']' or
// ',' or ':' or '{' or '}'. If that is so, then the text is safe for eval.

            if (/^[\],:{}\s]*$/
                    .test(text.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g, '@')
                        .replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']')
                        .replace(/(?:^|:|,)(?:\s*\[)+/g, ''))) {

// In the third stage we use the eval function to compile the text into a
// JavaScript structure. The '{' operator is subject to a syntactic ambiguity
// in JavaScript: it can begin a block or an object literal. We wrap the text
// in parens to eliminate the ambiguity.

                j = eval('(' + text + ')');

// In the optional fourth stage, we recursively walk the new structure, passing
// each name/value pair to a reviver function for possible transformation.

                return typeof reviver === 'function' ?
                    walk({'': j}, '') : j;
            }

// If the text is not JSON parseable, then a SyntaxError is thrown.

            throw new SyntaxError('JSON.parse');
        };
    }
}());
/*
 * ----------------------------- JSTORAGE -------------------------------------
 * Simple local storage wrapper to save data on the browser side, supporting
 * all major browsers - IE6+, Firefox2+, Safari4+, Chrome4+ and Opera 10.5+
 *
 * Copyright (c) 2010 Andris Reinman, andris.reinman@gmail.com
 * Project homepage: www.jstorage.info
 *
 * Licensed under MIT-style license:
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

/**
 * $.jStorage
 *
 * USAGE:
 *
 * jStorage requires Prototype, MooTools or jQuery! If jQuery is used, then
 * jQuery-JSON (http://code.google.com/p/jquery-json/) is also needed.
 * (jQuery-JSON needs to be loaded BEFORE jStorage!)
 *
 * Methods:
 *
 * -set(key, value)
 * $.jStorage.set(key, value) -> saves a value
 *
 * -get(key[, default])
 * value = $.jStorage.get(key [, default]) ->
 *    retrieves value if key exists, or default if it doesn't
 *
 * -deleteKey(key)
 * $.jStorage.deleteKey(key) -> removes a key from the storage
 *
 * -flush()
 * $.jStorage.flush() -> clears the cache
 *
 * -storageObj()
 * $.jStorage.storageObj() -> returns a read-ony copy of the actual storage
 *
 * -storageSize()
 * $.jStorage.storageSize() -> returns the size of the storage in bytes
 *
 * -index()
 * $.jStorage.index() -> returns the used keys as an array
 *
 * -storageAvailable()
 * $.jStorage.storageAvailable() -> returns true if storage is available
 *
 * -reInit()
 * $.jStorage.reInit() -> reloads the data from browser storage
 *
 * <value> can be any JSON-able value, including objects and arrays.
 *
 **/

(function($){
    if(!$ || !($.toJSON || Object.toJSON || window.JSON)){
        throw new Error("jQuery, MooTools or Prototype needs to be loaded before jStorage!");
    }

    var
        /* This is the object, that holds the cached values */
        _storage = {},

        /* Actual browser storage (localStorage or globalStorage['domain']) */
        _storage_service = {jStorage:"{}"},

        /* DOM element for older IE versions, holds userData behavior */
        _storage_elm = null,

        /* How much space does the storage take */
        _storage_size = 0,

        /* function to encode objects to JSON strings */
        json_encode = $.toJSON || Object.toJSON || (window.JSON && (JSON.encode || JSON.stringify)),

        /* function to decode objects from JSON strings */
        json_decode = $.evalJSON || (window.JSON && (JSON.decode || JSON.parse)) || function(str){
            return String(str).evalJSON();
        },

        /* which backend is currently used */
        _backend = false,

        /* Next check for TTL */
        _ttl_timeout,

        /**
         * XML encoding and decoding as XML nodes can't be JSON'ized
         * XML nodes are encoded and decoded if the node is the value to be saved
         * but not if it's as a property of another object
         * Eg. -
         *   $.jStorage.set("key", xmlNode);        // IS OK
         *   $.jStorage.set("key", {xml: xmlNode}); // NOT OK
         */
        _XMLService = {

            /**
             * Validates a XML node to be XML
             * based on jQuery.isXML function
             */
            isXML: function(elm){
                var documentElement = (elm ? elm.ownerDocument || elm : 0).documentElement;
                return documentElement ? documentElement.nodeName !== "HTML" : false;
            },

            /**
             * Encodes a XML node to string
             * based on http://www.mercurytide.co.uk/news/article/issues-when-working-ajax/
             */
            encode: function(xmlNode) {
                if(!this.isXML(xmlNode)){
                    return false;
                }
                try{ // Mozilla, Webkit, Opera
                    return new XMLSerializer().serializeToString(xmlNode);
                }catch(E1) {
                    try {  // IE
                        return xmlNode.xml;
                    }catch(E2){}
                }
                return false;
            },

            /**
             * Decodes a XML node from string
             * loosely based on http://outwestmedia.com/jquery-plugins/xmldom/
             */
            decode: function(xmlString){
                var dom_parser = ("DOMParser" in window && (new DOMParser()).parseFromString) ||
                        (window.ActiveXObject && function(_xmlString) {
                    var xml_doc = new ActiveXObject('Microsoft.XMLDOM');
                    xml_doc.async = 'false';
                    xml_doc.loadXML(_xmlString);
                    return xml_doc;
                }),
                resultXML;
                if(!dom_parser){
                    return false;
                }
                resultXML = dom_parser.call("DOMParser" in window && (new DOMParser()) || window, xmlString, 'text/xml');
                return this.isXML(resultXML)?resultXML:false;
            }
        };

    ////////////////////////// PRIVATE METHODS ////////////////////////

    /**
     * Initialization function. Detects if the browser supports DOM Storage
     * or userData behavior and behaves accordingly.
     * @returns undefined
     */
    function _init(){
        /* Check if browser supports localStorage */
        var localStorageReallyWorks = false;
        if("localStorage" in window){
            try {
                window.localStorage.setItem('_tmptest', 'tmpval');
                localStorageReallyWorks = true;
                window.localStorage.removeItem('_tmptest');
            } catch(BogusQuotaExceededErrorOnIos5) {
                // Thanks be to iOS5 Private Browsing mode which throws
                // QUOTA_EXCEEDED_ERRROR DOM Exception 22.
            }
        }
        if(localStorageReallyWorks){
            try {
                if(window.localStorage) {
                    _storage_service = window.localStorage;
                    _backend = "localStorage";
                }
            } catch(E3) {/* Firefox fails when touching localStorage and cookies are disabled */}
        }
        /* Check if browser supports globalStorage */
        else if("globalStorage" in window){
            try {
                if(window.globalStorage) {
                    _storage_service = window.globalStorage[window.location.hostname];
                    _backend = "globalStorage";
                }
            } catch(E4) {/* Firefox fails when touching localStorage and cookies are disabled */}
        }
        /* Check if browser supports userData behavior */
        else {
            _storage_elm = document.createElement('link');
            if(_storage_elm.addBehavior){

                /* Use a DOM element to act as userData storage */
                _storage_elm.style.behavior = 'url(#default#userData)';

                /* userData element needs to be inserted into the DOM! */
                document.getElementsByTagName('head')[0].appendChild(_storage_elm);

                _storage_elm.load("jStorage");
                var data = "{}";
                try{
                    data = _storage_elm.getAttribute("jStorage");
                }catch(E5){}
                _storage_service.jStorage = data;
                _backend = "userDataBehavior";
            }else{
                _storage_elm = null;
                return;
            }
        }

        _load_storage();

        // remove dead keys
        _handleTTL();
    }

    /**
     * Loads the data from the storage based on the supported mechanism
     * @returns undefined
     */
    function _load_storage(){
        /* if jStorage string is retrieved, then decode it */
        if(_storage_service.jStorage){
            try{
                _storage = json_decode(String(_storage_service.jStorage));
            }catch(E6){_storage_service.jStorage = "{}";}
        }else{
            _storage_service.jStorage = "{}";
        }
        _storage_size = _storage_service.jStorage?String(_storage_service.jStorage).length:0;
    }

    /**
     * This functions provides the "save" mechanism to store the jStorage object
     * @returns undefined
     */
    function _save(){
        try{
            _storage_service.jStorage = json_encode(_storage);
            // If userData is used as the storage engine, additional
            if(_storage_elm) {
                _storage_elm.setAttribute("jStorage",_storage_service.jStorage);
                _storage_elm.save("jStorage");
            }
            _storage_size = _storage_service.jStorage?String(_storage_service.jStorage).length:0;
        }catch(E7){/* probably cache is full, nothing is saved this way*/}
    }

    /**
     * Function checks if a key is set and is string or numberic
     */
    function _checkKey(key){
        if(!key || (typeof key != "string" && typeof key != "number")){
            throw new TypeError('Key name must be string or numeric');
        }
        if(key == "__jstorage_meta"){
            throw new TypeError('Reserved key name');
        }
        return true;
    }

    /**
     * Removes expired keys
     */
    function _handleTTL(){
        var curtime, i, TTL, nextExpire = Infinity, changed = false;

        clearTimeout(_ttl_timeout);

        if(!_storage.__jstorage_meta || typeof _storage.__jstorage_meta.TTL != "object"){
            // nothing to do here
            return;
        }

        curtime = +new Date();
        TTL = _storage.__jstorage_meta.TTL;
        for(i in TTL){
            if(TTL.hasOwnProperty(i)){
                if(TTL[i] <= curtime){
                    delete TTL[i];
                    delete _storage[i];
                    changed = true;
                }else if(TTL[i] < nextExpire){
                    nextExpire = TTL[i];
                }
            }
        }

        // set next check
        if(nextExpire != Infinity){
            _ttl_timeout = setTimeout(_handleTTL, nextExpire - curtime);
        }

        // save changes
        if(changed){
            _save();
        }
    }

    ////////////////////////// PUBLIC INTERFACE /////////////////////////

    $.jStorage = {
        /* Version number */
        version: "0.1.6.1",

        /**
         * Sets a key's value.
         *
         * @param {String} key - Key to set. If this value is not set or not
         *              a string an exception is raised.
         * @param value - Value to set. This can be any value that is JSON
         *              compatible (Numbers, Strings, Objects etc.).
         * @returns the used value
         */
        set: function(key, value){
            _checkKey(key);
            if(_XMLService.isXML(value)){
                value = {_is_xml:true,xml:_XMLService.encode(value)};
            }else if(typeof value == "function"){
                value = null; // functions can't be saved!
            }else if(value && typeof value == "object"){
                // clone the object before saving to _storage tree
                value = json_decode(json_encode(value));
            }
            _storage[key] = value;
            _save();
            return value;
        },

        /**
         * Looks up a key in cache
         *
         * @param {String} key - Key to look up.
         * @param {mixed} def - Default value to return, if key didn't exist.
         * @returns the key value, default value or <null>
         */
        get: function(key, def){
            _checkKey(key);
            if(key in _storage){
                if(_storage[key] && typeof _storage[key] == "object" &&
                        _storage[key]._is_xml &&
                            _storage[key]._is_xml){
                    return _XMLService.decode(_storage[key].xml);
                }else{
                    return _storage[key];
                }
            }
            return typeof(def) == 'undefined' ? null : def;
        },

        /**
         * Deletes a key from cache.
         *
         * @param {String} key - Key to delete.
         * @returns true if key existed or false if it didn't
         */
        deleteKey: function(key){
            _checkKey(key);
            if(key in _storage){
                delete _storage[key];
                // remove from TTL list
                if(_storage.__jstorage_meta &&
                  typeof _storage.__jstorage_meta.TTL == "object" &&
                  key in _storage.__jstorage_meta.TTL){
                    delete _storage.__jstorage_meta.TTL[key];
                }
                _save();
                return true;
            }
            return false;
        },

        /**
         * Sets a TTL for a key, or remove it if ttl value is 0 or below
         *
         * @param {String} key - key to set the TTL for
         * @param {Number} ttl - TTL timeout in milliseconds
         * @returns true if key existed or false if it didn't
         */
        setTTL: function(key, ttl){
            var curtime = +new Date();
            _checkKey(key);
            ttl = Number(ttl) || 0;
            if(key in _storage){

                if(!_storage.__jstorage_meta){
                    _storage.__jstorage_meta = {};
                }
                if(!_storage.__jstorage_meta.TTL){
                    _storage.__jstorage_meta.TTL = {};
                }

                // Set TTL value for the key
                if(ttl>0){
                    _storage.__jstorage_meta.TTL[key] = curtime + ttl;
                }else{
                    delete _storage.__jstorage_meta.TTL[key];
                }

                _save();

                _handleTTL();
                return true;
            }
            return false;
        },

        /**
         * Deletes everything in cache.
         *
         * @return true
         */
        flush: function(){
            _storage = {};
            _save();
            return true;
        },

        /**
         * Returns a read-only copy of _storage
         *
         * @returns Object
        */
        storageObj: function(){
            function F() {}
            F.prototype = _storage;
            return new F();
        },

        /**
         * Returns an index of all used keys as an array
         * ['key1', 'key2',..'keyN']
         *
         * @returns Array
        */
        index: function(){
            var index = [], i;
            for(i in _storage){
                if(_storage.hasOwnProperty(i) && i != "__jstorage_meta"){
                    index.push(i);
                }
            }
            return index;
        },

        /**
         * How much space in bytes does the storage take?
         *
         * @returns Number
         */
        storageSize: function(){
            return _storage_size;
        },

        /**
         * Which backend is currently in use?
         *
         * @returns String
         */
        currentBackend: function(){
            return _backend;
        },

        /**
         * Test if storage is available
         *
         * @returns Boolean
         */
        storageAvailable: function(){
            return !!_backend;
        },

        /**
         * Reloads the data from browser storage
         *
         * @returns undefined
         */
        reInit: function(){
            var new_storage_elm, data;
            if(_storage_elm && _storage_elm.addBehavior){
                new_storage_elm = document.createElement('link');

                _storage_elm.parentNode.replaceChild(new_storage_elm, _storage_elm);
                _storage_elm = new_storage_elm;

                /* Use a DOM element to act as userData storage */
                _storage_elm.style.behavior = 'url(#default#userData)';

                /* userData element needs to be inserted into the DOM! */
                document.getElementsByTagName('head')[0].appendChild(_storage_elm);

                _storage_elm.load("jStorage");
                data = "{}";
                try{
                    data = _storage_elm.getAttribute("jStorage");
                }catch(E5){}
                _storage_service.jStorage = data;
                _backend = "userDataBehavior";
            }

            _load_storage();
        }
    };

    // Initialize jStorage
    _init();

})(window.jQuery || window.$);
/*! OpenPGPjs.org  this is LGPL licensed code, see LICENSE/our website for more information.- v0.6.0 - 2014-05-09 */!function(a){"object"==typeof exports?module.exports=a():"function"==typeof define&&define.amd?define(a):"undefined"!=typeof window?window.openpgp=a():"undefined"!=typeof global?global.openpgp=a():"undefined"!=typeof self&&(self.openpgp=a())}(function(){return function a(b,c,d){function e(g,h){if(!c[g]){if(!b[g]){var i="function"==typeof require&&require;if(!h&&i)return i(g,!0);if(f)return f(g,!0);throw new Error("Cannot find module '"+g+"'")}var j=c[g]={exports:{}};b[g][0].call(j.exports,function(a){var c=b[g][1][a];return e(c?c:a)},j,j.exports,a,b,c,d)}return c[g].exports}for(var f="function"==typeof require&&require,g=0;g<d.length;g++)e(d[g]);return e}({1:[function(a,b,c){function d(a,b){return this instanceof d?(this.text=a.replace(/\r/g,"").replace(/[\t ]+\n/g,"\n").replace(/\n/g,"\r\n"),void(this.packets=b||new h.List)):new d(a,b)}function e(a){var b=j.decode(a);if(b.type!==i.armor.signed)throw new Error("No cleartext signed message.");var c=new h.List;c.read(b.data),f(b.headers,c);var e=new d(b.text,c);return e}function f(a,b){for(var c=function(a){for(var c=0;c<b.length;c++)if(b[c].tag===i.packet.signature&&!a.some(function(a){return b[c].hashAlgorithm===a}))return!1;return!0},d=null,e=[],f=0;f<a.length;f++){if(d=a[f].match(/Hash: (.+)/),!d)throw new Error('Only "Hash" header allowed in cleartext signed message');d=d[1].replace(/\s/g,""),d=d.split(","),d=d.map(function(a){a=a.toLowerCase();try{return i.write(i.hash,a)}catch(b){throw new Error("Unknown hash algorithm in armor header: "+a)}}),e=e.concat(d)}if(!e.length&&!c([i.hash.md5]))throw new Error('If no "Hash" header in cleartext signed message, then only MD5 signatures allowed');if(!c(e))throw new Error("Hash algorithm mismatch in armor header and signature")}var g=a("./config"),h=a("./packet"),i=a("./enums.js"),j=a("./encoding/armor.js");d.prototype.getSigningKeyIds=function(){var a=[],b=this.packets.filterByTag(i.packet.signature);return b.forEach(function(b){a.push(b.issuerKeyId)}),a},d.prototype.sign=function(a){var b=new h.List,c=new h.Literal;c.setText(this.text);for(var d=0;d<a.length;d++){var e=new h.Signature;e.signatureType=i.signature.text,e.hashAlgorithm=g.prefer_hash_algorithm;var f=a[d].getSigningKeyPacket();if(e.publicKeyAlgorithm=f.algorithm,!f.isDecrypted)throw new Error("Private key is not decrypted.");e.sign(f,c),b.push(e)}this.packets=b},d.prototype.verify=function(a){var b=[],c=this.packets.filterByTag(i.packet.signature),d=new h.Literal;return d.setText(this.text),a.forEach(function(a){for(var e=0;e<c.length;e++){var f=a.getPublicKeyPacket([c[e].issuerKeyId]);if(f){var g={};g.keyid=c[e].issuerKeyId,g.valid=c[e].verify(f,d),b.push(g);break}}}),b},d.prototype.getText=function(){return this.text.replace(/\r\n/g,"\n")},d.prototype.armor=function(){var a={hash:i.read(i.hash,g.prefer_hash_algorithm).toUpperCase(),text:this.text,data:this.packets.write()};return j.encode(i.armor.signed,a)},c.CleartextMessage=d,c.readArmored=e},{"./config":4,"./encoding/armor.js":28,"./enums.js":30,"./packet":40}],2:[function(a,b){JXG={exists:function(a){return function(b){return!(b===a||null===b)}}()},JXG.decompress=function(a){return unescape(new JXG.Util.Unzip(JXG.Util.Base64.decodeAsArray(a)).unzip()[0][0])},JXG.Util={},JXG.Util.Unzip=function(a){function b(){return J+=8,H<G.length?G[H++]:-1}function c(){I=1}function d(){var a;return J++,a=1&I,I>>=1,0===I&&(I=b(),a=1&I,I=I>>1|128),a}function e(a){for(var b=0,c=a;c--;)b=b<<1|d();return a&&(b=A[b]>>8-a),b}function f(){y=0}function g(a){r++,x[y++]=a,t.push(String.fromCharCode(a)),32768==y&&(y=0)}function h(){this.b0=0,this.b1=0,this.jump=null,this.jumppos=-1}function i(){for(;;){if(S[R]>=U)return-1;if(T[S[R]]==R)return S[R]++;S[R]++}}function j(){var a,b=Q[P];if(u&&document.write("<br>len:"+R+" treepos:"+P),17==R)return-1;if(P++,R++,a=i(),u&&document.write("<br>IsPat "+a),a>=0)b.b0=a,u&&document.write("<br>b0 "+b.b0);else if(b.b0=32768,u&&document.write("<br>b0 "+b.b0),j())return-1;if(a=i(),a>=0)b.b1=a,u&&document.write("<br>b1 "+b.b1),b.jump=null;else if(b.b1=32768,u&&document.write("<br>b1 "+b.b1),b.jump=Q[P],b.jumppos=P,j())return-1;return R--,0}function k(a,b,c,d){var e;for(u&&document.write("currentTree "+a+" numval "+b+" lengths "+c+" show "+d),Q=a,P=0,T=c,U=b,e=0;17>e;e++)S[e]=0;if(R=0,j())return u&&alert("invalid huffman tree\n"),-1;if(u){document.write("<br>Tree: "+Q.length);for(var f=0;32>f;f++)document.write("Places["+f+"].b0="+Q[f].b0+"<br>"),document.write("Places["+f+"].b1="+Q[f].b1+"<br>")}return 0}function l(a){for(var b,c,e,f=0,g=a[f];;)if(e=d(),u&&document.write("b="+e),e){if(!(32768&g.b1))return u&&document.write("ret1"),g.b1;for(g=g.jump,b=a.length,c=0;b>c;c++)if(a[c]===g){f=c;break}}else{if(!(32768&g.b0))return u&&document.write("ret2"),g.b0;f++,g=a[f]}}function m(){var a,i,j,m,n,o,p;do{switch(a=d(),j=e(2)){case 0:u&&alert("Stored\n");break;case 1:u&&alert("Fixed Huffman codes\n");break;case 2:u&&alert("Dynamic Huffman codes\n");break;case 3:u&&alert("Reserved block type!!\n");break;default:u&&alert("Unexpected value %d!\n",j)}if(0===j){var q,r;for(c(),q=b(),q|=b()<<8,r=b(),r|=b()<<8,65535&(q^~r)&&document.write("BlockLen checksum mismatch\n");q--;)i=b(),g(i)}else if(1==j)for(;;)if(n=A[e(7)]>>1,n>23?(n=n<<1|d(),n>199?(n-=128,n=n<<1|d()):(n-=48,n>143&&(n+=136))):n+=256,256>n)g(n);else{if(256==n)break;for(n-=257,o=e(C[n])+B[n],n=A[e(5)]>>3,E[n]>8?(p=e(8),p|=e(E[n]-8)<<8):p=e(E[n]),p+=D[n],n=0;o>n;n++)i=x[y-p&32767],g(i)}else if(2==j){var s,t,v,w,z=new Array(320);for(t=257+e(5),v=1+e(5),w=4+e(4),n=0;19>n;n++)z[n]=0;for(n=0;w>n;n++)z[F[n]]=e(3);for(o=O.length,m=0;o>m;m++)O[m]=new h;if(k(O,19,z,0))return f(),1;if(u){document.write("<br>distanceTree");for(var G=0;G<O.length;G++)document.write("<br>"+O[G].b0+" "+O[G].b1+" "+O[G].jump+" "+O[G].jumppos)}s=t+v,m=0;var H=-1;for(u&&document.write("<br>n="+s+" bits: "+J+"<br>");s>m;)if(H++,n=l(O),u&&document.write("<br>"+H+" i:"+m+" decode: "+n+"    bits "+J+"<br>"),16>n)z[m++]=n;else if(16==n){var I;if(n=3+e(2),m+n>s)return f(),1;for(I=m?z[m-1]:0;n--;)z[m++]=I}else{if(n=17==n?3+e(3):11+e(7),m+n>s)return f(),1;for(;n--;)z[m++]=0}for(o=N.length,m=0;o>m;m++)N[m]=new h;if(k(N,t,z,0))return f(),1;for(o=N.length,m=0;o>m;m++)O[m]=new h;var K=[];for(m=t;m<z.length;m++)K[m-t]=z[m];if(k(O,v,K,0))return f(),1;u&&document.write("<br>literalTree");a:for(;;)if(n=l(N),n>=256){if(n-=256,0===n)break;for(n--,o=e(C[n])+B[n],n=l(O),E[n]>8?(p=e(8),p|=e(E[n]-8)<<8):p=e(E[n]),p+=D[n];o--;){if(0>y-p)break a;i=x[y-p&32767],g(i)}}else g(n)}}while(!a);return f(),c(),0}function n(){u&&alert("NEXTFILE"),t=[];var a=[];if(z=!1,a[0]=b(),a[1]=b(),u&&alert("type: "+a[0]+" "+a[1]),a[0]==parseInt("78",16)&&a[1]==parseInt("da",16)&&(u&&alert("GEONExT-GZIP"),m(),u&&alert(t.join("")),w[v]=new Array(2),w[v][0]=t.join(""),w[v][1]="geonext.gxt",v++),a[0]==parseInt("78",16)&&a[1]==parseInt("9c",16)&&(u&&alert("ZLIB"),m(),u&&alert(t.join("")),w[v]=new Array(2),w[v][0]=t.join(""),w[v][1]="ZLIB",v++),a[0]==parseInt("1f",16)&&a[1]==parseInt("8b",16)&&(u&&alert("GZIP"),o(),u&&alert(t.join("")),w[v]=new Array(2),w[v][0]=t.join(""),w[v][1]="file",v++),a[0]==parseInt("50",16)&&a[1]==parseInt("4b",16)&&(z=!0,a[2]=b(),a[3]=b(),a[2]==parseInt("3",16)&&a[3]==parseInt("4",16))){a[0]=b(),a[1]=b(),u&&alert("ZIP-Version: "+a[1]+" "+a[0]/10+"."+a[0]%10),p=b(),p|=b()<<8,u&&alert("gpflags: "+p);var c=b();c|=b()<<8,u&&alert("method: "+c),b(),b(),b(),b();var d=b();d|=b()<<8,d|=b()<<16,d|=b()<<24;var e=b();e|=b()<<8,e|=b()<<16,e|=b()<<24;var f=b();f|=b()<<8,f|=b()<<16,f|=b()<<24,u&&alert("local CRC: "+d+"\nlocal Size: "+f+"\nlocal CompSize: "+e);var g=b();g|=b()<<8;var h=b();h|=b()<<8,u&&alert("filelen "+g),j=0,L=[];for(var i;g--;)i=b(),"/"==i|":"==i?j=0:K-1>j&&(L[j++]=String.fromCharCode(i));u&&alert("nameBuf: "+L),s||(s=L);for(var j=0;h>j;)i=b(),j++;q=4294967295,r=0,0===f&&"/"==fileOut.charAt(s.length-1)&&u&&alert("skipdir"),8==c&&(m(),u&&alert(t.join("")),w[v]=new Array(2),w[v][0]=t.join(""),w[v][1]=L.join(""),v++),o()}}function o(){var a,c,d,e,f,g,h=[];if(8&p&&(h[0]=b(),h[1]=b(),h[2]=b(),h[3]=b(),h[0]==parseInt("50",16)&&h[1]==parseInt("4b",16)&&h[2]==parseInt("07",16)&&h[3]==parseInt("08",16)?(a=b(),a|=b()<<8,a|=b()<<16,a|=b()<<24):a=h[0]|h[1]<<8|h[2]<<16|h[3]<<24,c=b(),c|=b()<<8,c|=b()<<16,c|=b()<<24,d=b(),d|=b()<<8,d|=b()<<16,d|=b()<<24,u&&alert("CRC:")),z&&n(),h[0]=b(),8!=h[0])return u&&alert("Unknown compression method!"),0;if(p=b(),u&&p&~parseInt("1f",16)&&alert("Unknown flags set!"),b(),b(),b(),b(),b(),e=b(),4&p)for(h[0]=b(),h[2]=b(),R=h[0]+256*h[1],u&&alert("Extra field size: "+R),f=0;R>f;f++)b();if(8&p){for(f=0,L=[];g=b();)("7"==g||":"==g)&&(f=0),K-1>f&&(L[f++]=g);u&&alert("original file name: "+L)}if(16&p)for(;g=b(););2&p&&(b(),b()),m(),a=b(),a|=b()<<8,a|=b()<<16,a|=b()<<24,d=b(),d|=b()<<8,d|=b()<<16,d|=b()<<24,z&&n()}var p,q,r,s,t=[],u=!1,v=0,w=[],x=new Array(32768),y=0,z=!1,A=[0,128,64,192,32,160,96,224,16,144,80,208,48,176,112,240,8,136,72,200,40,168,104,232,24,152,88,216,56,184,120,248,4,132,68,196,36,164,100,228,20,148,84,212,52,180,116,244,12,140,76,204,44,172,108,236,28,156,92,220,60,188,124,252,2,130,66,194,34,162,98,226,18,146,82,210,50,178,114,242,10,138,74,202,42,170,106,234,26,154,90,218,58,186,122,250,6,134,70,198,38,166,102,230,22,150,86,214,54,182,118,246,14,142,78,206,46,174,110,238,30,158,94,222,62,190,126,254,1,129,65,193,33,161,97,225,17,145,81,209,49,177,113,241,9,137,73,201,41,169,105,233,25,153,89,217,57,185,121,249,5,133,69,197,37,165,101,229,21,149,85,213,53,181,117,245,13,141,77,205,45,173,109,237,29,157,93,221,61,189,125,253,3,131,67,195,35,163,99,227,19,147,83,211,51,179,115,243,11,139,75,203,43,171,107,235,27,155,91,219,59,187,123,251,7,135,71,199,39,167,103,231,23,151,87,215,55,183,119,247,15,143,79,207,47,175,111,239,31,159,95,223,63,191,127,255],B=[3,4,5,6,7,8,9,10,11,13,15,17,19,23,27,31,35,43,51,59,67,83,99,115,131,163,195,227,258,0,0],C=[0,0,0,0,0,0,0,0,1,1,1,1,2,2,2,2,3,3,3,3,4,4,4,4,5,5,5,5,0,99,99],D=[1,2,3,4,5,7,9,13,17,25,33,49,65,97,129,193,257,385,513,769,1025,1537,2049,3073,4097,6145,8193,12289,16385,24577],E=[0,0,0,0,1,1,2,2,3,3,4,4,5,5,6,6,7,7,8,8,9,9,10,10,11,11,12,12,13,13],F=[16,17,18,0,8,7,9,6,10,5,11,4,12,3,13,2,14,1,15],G=a,H=0,I=1,J=0,K=256,L=[],M=288,N=new Array(M),O=new Array(32),P=0,Q=null,R=(new Array(64),new Array(64),0),S=new Array(17);S[0]=0;var T,U;JXG.Util.Unzip.prototype.unzipFile=function(a){var b;for(this.unzip(),b=0;b<w.length;b++)if(w[b][1]==a)return w[b][0]},JXG.Util.Unzip.prototype.deflate=function(){t=[];return z=!1,m(),u&&alert(t.join("")),w[v]=new Array(2),w[v][0]=t.join(""),w[v][1]="DEFLATE",v++,w},JXG.Util.Unzip.prototype.unzip=function(){return u&&alert(G),n(),w}},JXG.Util.Base64={_keyStr:"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",encode:function(a){var b,c,d,e,f,g,h,i=[],j=0;for(a=JXG.Util.Base64._utf8_encode(a);j<a.length;)b=a.charCodeAt(j++),c=a.charCodeAt(j++),d=a.charCodeAt(j++),e=b>>2,f=(3&b)<<4|c>>4,g=(15&c)<<2|d>>6,h=63&d,isNaN(c)?g=h=64:isNaN(d)&&(h=64),i.push([this._keyStr.charAt(e),this._keyStr.charAt(f),this._keyStr.charAt(g),this._keyStr.charAt(h)].join(""));return i.join("")},decode:function(a,b){var c,d,e,f,g,h,i,j=[],k=0;for(a=a.replace(/[^A-Za-z0-9\+\/\=]/g,"");k<a.length;)f=this._keyStr.indexOf(a.charAt(k++)),g=this._keyStr.indexOf(a.charAt(k++)),h=this._keyStr.indexOf(a.charAt(k++)),i=this._keyStr.indexOf(a.charAt(k++)),c=f<<2|g>>4,d=(15&g)<<4|h>>2,e=(3&h)<<6|i,j.push(String.fromCharCode(c)),64!=h&&j.push(String.fromCharCode(d)),64!=i&&j.push(String.fromCharCode(e));return j=j.join(""),b&&(j=JXG.Util.Base64._utf8_decode(j)),j},_utf8_encode:function(a){a=a.replace(/\r\n/g,"\n");for(var b="",c=0;c<a.length;c++){var d=a.charCodeAt(c);128>d?b+=String.fromCharCode(d):d>127&&2048>d?(b+=String.fromCharCode(d>>6|192),b+=String.fromCharCode(63&d|128)):(b+=String.fromCharCode(d>>12|224),b+=String.fromCharCode(d>>6&63|128),b+=String.fromCharCode(63&d|128))}return b},_utf8_decode:function(a){for(var b=[],c=0,d=0,e=0,f=0;c<a.length;)d=a.charCodeAt(c),128>d?(b.push(String.fromCharCode(d)),c++):d>191&&224>d?(e=a.charCodeAt(c+1),b.push(String.fromCharCode((31&d)<<6|63&e)),c+=2):(e=a.charCodeAt(c+1),f=a.charCodeAt(c+2),b.push(String.fromCharCode((15&d)<<12|(63&e)<<6|63&f)),c+=3);return b.join("")},_destrip:function(a,b){var c,d,e=[],f=[];for(null===b&&(b=76),a.replace(/ /g,""),c=a.length/b,d=0;c>d;d++)e[d]=a.substr(d*b,b);for(c!=a.length/b&&(e[e.length]=a.substr(c*b,a.length-c*b)),d=0;d<e.length;d++)f.push(e[d]);return f.join("\n")},decodeAsArray:function(a){var b,c=this.decode(a),d=[];for(b=0;b<c.length;b++)d[b]=c.charCodeAt(b);return d},decodeGEONExT:function(a){return decodeAsArray(destrip(a),!1)}},JXG.Util.asciiCharCodeAt=function(a,b){var c=a.charCodeAt(b);if(c>255)switch(c){case 8364:c=128;break;case 8218:c=130;break;case 402:c=131;break;case 8222:c=132;break;case 8230:c=133;break;case 8224:c=134;break;case 8225:c=135;break;case 710:c=136;break;case 8240:c=137;break;case 352:c=138;break;case 8249:c=139;break;case 338:c=140;break;case 381:c=142;break;case 8216:c=145;break;case 8217:c=146;break;case 8220:c=147;break;case 8221:c=148;break;case 8226:c=149;break;case 8211:c=150;break;case 8212:c=151;break;case 732:c=152;break;case 8482:c=153;break;case 353:c=154;break;case 8250:c=155;break;case 339:c=156;break;case 382:c=158;break;case 376:c=159}return c},JXG.Util.utf8Decode=function(a){var b,c=[],d=0,e=0,f=0;if(!JXG.exists(a))return"";for(;d<a.length;)e=a.charCodeAt(d),128>e?(c.push(String.fromCharCode(e)),d++):e>191&&224>e?(f=a.charCodeAt(d+1),c.push(String.fromCharCode((31&e)<<6|63&f)),d+=2):(f=a.charCodeAt(d+1),b=a.charCodeAt(d+2),c.push(String.fromCharCode((15&e)<<12|(63&f)<<6|63&b)),d+=3);return c.join("")},JXG.Util.genUUID=function(){for(var a,b="0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz".split(""),c=new Array(36),d=0,e=0;36>e;e++)8==e||13==e||18==e||23==e?c[e]="-":14==e?c[e]="4":(2>=d&&(d=33554432+16777216*Math.random()|0),a=15&d,d>>=4,c[e]=b[19==e?3&a|8:a]);return c.join("")},b.exports=JXG},{}],3:[function(a,b){var c=a("../enums.js");b.exports={prefer_hash_algorithm:c.hash.sha256,encryption_cipher:c.symmetric.aes256,compression:c.compression.zip,integrity_protect:!0,rsa_blinding:!0,show_version:!0,show_comment:!0,versionstring:"OpenPGP.js v0.6.0",commentstring:"http://openpgpjs.org",keyserver:"keyserver.linux.it",node_store:"./openpgp.store",debug:!1}},{"../enums.js":30}],4:[function(a,b){b.exports=a("./config.js")},{"./config.js":3}],5:[function(a,b){"use strict";var c=a("../util.js"),d=a("./cipher");b.exports={encrypt:function(a,b,e,f,g){b=new d[b](f);var h=b.blockSize,i=new Uint8Array(h),j=new Uint8Array(h);a=a+a.charAt(h-2)+a.charAt(h-1);var k,l,m,n=new Uint8Array(e.length+2+2*h),o=g?0:2;for(k=0;h>k;k++)i[k]=0;for(j=b.encrypt(i),k=0;h>k;k++)n[k]=j[k]^a.charCodeAt(k);for(i.set(n.subarray(0,h)),j=b.encrypt(i),n[h]=j[0]^a.charCodeAt(h),n[h+1]=j[1]^a.charCodeAt(h+1),i.set(g?n.subarray(2,h+2):n.subarray(0,h)),j=b.encrypt(i),k=0;h>k;k++)n[h+2+k]=j[k+o]^e.charCodeAt(k);for(l=h;l<e.length+o;l+=h)for(m=l+2-o,i.set(n.subarray(m,m+h)),j=b.encrypt(i),k=0;h>k;k++)n[h+m+k]=j[k]^e.charCodeAt(l+k-o);return n=n.subarray(0,e.length+2+h),c.Uint8Array2str(n)},mdc:function(a,b,e){a=new d[a](b);var f,g=a.blockSize,h=new Uint8Array(g),i=new Uint8Array(g);for(f=0;g>f;f++)h[f]=0;for(h=a.encrypt(h),f=0;g>f;f++)i[f]=e.charCodeAt(f),h[f]^=i[f];return i=a.encrypt(i),c.bin2str(h)+String.fromCharCode(i[0]^e.charCodeAt(g))+String.fromCharCode(i[1]^e.charCodeAt(g+1))},decrypt:function(a,b,c,e){a=new d[a](b);var f,g=a.blockSize,h=new Uint8Array(g),i=new Uint8Array(g),j="",k="";for(f=0;g>f;f++)h[f]=0;for(h=a.encrypt(h),f=0;g>f;f++)i[f]=c.charCodeAt(f),h[f]^=i[f];if(i=a.encrypt(i),h[g-2]!=(i[0]^c.charCodeAt(g))||h[g-1]!=(i[1]^c.charCodeAt(g+1)))throw new Error("CFB decrypt: invalid key");if(e){for(f=0;g>f;f++)h[f]=c.charCodeAt(f+2);for(j=g+2;j<c.length;j+=g)for(i=a.encrypt(h),f=0;g>f&&f+j<c.length;f++)h[f]=c.charCodeAt(j+f),k+=String.fromCharCode(i[f]^h[f])}else{for(f=0;g>f;f++)h[f]=c.charCodeAt(f);for(j=g;j<c.length;j+=g)for(i=a.encrypt(h),f=0;g>f&&f+j<c.length;f++)h[f]=c.charCodeAt(j+f),k+=String.fromCharCode(i[f]^h[f])}return j=e?0:2,k=k.substring(j,c.length-g-2+j)},normalEncrypt:function(a,b,e,f){a=new d[a](b);var g=a.blockSize,h="",i="",j=0,k="",l="";for(i=f.substring(0,g);e.length>g*j;){var m=a.encrypt(c.str2bin(i));h=e.substring(j*g,j*g+g);for(var n=0;n<h.length;n++)l+=String.fromCharCode(h.charCodeAt(n)^m[n]);i=l,l="",k+=i,j++}return k},normalDecrypt:function(a,b,e,f){a=new d[a](b);var g,h=a.blockSize,i="",j=0,k="",l=0;if(null===f)for(g=0;h>g;g++)i+=String.fromCharCode(0);else i=f.substring(0,h);for(;e.length>h*j;){var m=a.encrypt(c.str2bin(i));for(i=e.substring(j*h+l,j*h+h+l),g=0;g<i.length;g++)k+=String.fromCharCode(i.charCodeAt(g)^m[g]);j++}return k}}},{"../util.js":61,"./cipher":10}],6:[function(a,b){"use strict";function c(a){return 255&a}function d(a){return a>>8&255}function e(a){return a>>16&255}function f(a){return a>>24&255}function g(a,b,c,e){return d(o[255&a])|d(o[b>>8&255])<<8|d(o[c>>16&255])<<16|d(o[e>>>24])<<24}function h(a){var b,c,d=a.length,e=new Array(d/4);if(a&&!(d%4)){for(b=0,c=0;d>c;c+=4)e[b++]=a[c]|a[c+1]<<8|a[c+2]<<16|a[c+3]<<24;return e}}function i(a){var b,g=0,h=a.length,i=new Array(4*h);for(b=0;h>b;b++)i[g++]=c(a[b]),i[g++]=d(a[b]),i[g++]=e(a[b]),i[g++]=f(a[b]);return i}function j(a){var b,g,h,i,j,k,l=new Array(t+1),o=a.length,p=new Array(s),q=new Array(s),r=0;if(16==o)k=10,b=4;else if(24==o)k=12,b=6;else{if(32!=o)throw new Error("Invalid key-length for AES key:"+o);k=14,b=8}for(g=0;t+1>g;g++)l[g]=new Uint32Array(4);for(g=0,h=0;o>h;h++,g+=4)p[h]=a.charCodeAt(g)|a.charCodeAt(g+1)<<8|a.charCodeAt(g+2)<<16|a.charCodeAt(g+3)<<24;for(h=b-1;h>=0;h--)q[h]=p[h];for(i=0,j=0,h=0;b>h&&k+1>i;){for(;b>h&&4>j;h++,j++)l[i][j]=q[h];4==j&&(i++,j=0)}for(;k+1>i;){var u=q[b-1];if(q[0]^=n[d(u)]|n[e(u)]<<8|n[f(u)]<<16|n[c(u)]<<24,q[0]^=m[r++],8!=b)for(h=1;b>h;h++)q[h]^=q[h-1];else{for(h=1;b/2>h;h++)q[h]^=q[h-1];for(u=q[b/2-1],q[b/2]^=n[c(u)]|n[d(u)]<<8|n[e(u)]<<16|n[f(u)]<<24,h=b/2+1;b>h;h++)q[h]^=q[h-1]}for(h=0;b>h&&k+1>i;){for(;b>h&&4>j;h++,j++)l[i][j]=q[h];4==j&&(i++,j=0)}}return{rounds:k,rk:l}}function k(a,b,c){var d,e,f;for(f=h(a),e=b.rounds,d=0;e-1>d;d++)c[0]=f[0]^b.rk[d][0],c[1]=f[1]^b.rk[d][1],c[2]=f[2]^b.rk[d][2],c[3]=f[3]^b.rk[d][3],f[0]=o[255&c[0]]^p[c[1]>>8&255]^q[c[2]>>16&255]^r[c[3]>>>24],f[1]=o[255&c[1]]^p[c[2]>>8&255]^q[c[3]>>16&255]^r[c[0]>>>24],f[2]=o[255&c[2]]^p[c[3]>>8&255]^q[c[0]>>16&255]^r[c[1]>>>24],f[3]=o[255&c[3]]^p[c[0]>>8&255]^q[c[1]>>16&255]^r[c[2]>>>24];return d=e-1,c[0]=f[0]^b.rk[d][0],c[1]=f[1]^b.rk[d][1],c[2]=f[2]^b.rk[d][2],c[3]=f[3]^b.rk[d][3],f[0]=g(c[0],c[1],c[2],c[3])^b.rk[e][0],f[1]=g(c[1],c[2],c[3],c[0])^b.rk[e][1],f[2]=g(c[2],c[3],c[0],c[1])^b.rk[e][2],f[3]=g(c[3],c[0],c[1],c[2])^b.rk[e][3],i(f)}function l(a){var b=function(a){this.key=j(a),this._temp=new Uint32Array(this.blockSize/4),this.encrypt=function(a){return k(a,this.key,this._temp)}};return b.blockSize=b.prototype.blockSize=16,b.keySize=b.prototype.keySize=a/8,b}var m=(a("../../util.js"),new Uint8Array([1,2,4,8,16,32,64,128,27,54,108,216,171,77,154,47,94,188,99,198,151,53,106,212,179,125,250,239,197,145])),n=new Uint8Array([99,124,119,123,242,107,111,197,48,1,103,43,254,215,171,118,202,130,201,125,250,89,71,240,173,212,162,175,156,164,114,192,183,253,147,38,54,63,247,204,52,165,229,241,113,216,49,21,4,199,35,195,24,150,5,154,7,18,128,226,235,39,178,117,9,131,44,26,27,110,90,160,82,59,214,179,41,227,47,132,83,209,0,237,32,252,177,91,106,203,190,57,74,76,88,207,208,239,170,251,67,77,51,133,69,249,2,127,80,60,159,168,81,163,64,143,146,157,56,245,188,182,218,33,16,255,243,210,205,12,19,236,95,151,68,23,196,167,126,61,100,93,25,115,96,129,79,220,34,42,144,136,70,238,184,20,222,94,11,219,224,50,58,10,73,6,36,92,194,211,172,98,145,149,228,121,231,200,55,109,141,213,78,169,108,86,244,234,101,122,174,8,186,120,37,46,28,166,180,198,232,221,116,31,75,189,139,138,112,62,181,102,72,3,246,14,97,53,87,185,134,193,29,158,225,248,152,17,105,217,142,148,155,30,135,233,206,85,40,223,140,161,137,13,191,230,66,104,65,153,45,15,176,84,187,22]),o=new Uint32Array([2774754246,2222750968,2574743534,2373680118,234025727,3177933782,2976870366,1422247313,1345335392,50397442,2842126286,2099981142,436141799,1658312629,3870010189,2591454956,1170918031,2642575903,1086966153,2273148410,368769775,3948501426,3376891790,200339707,3970805057,1742001331,4255294047,3937382213,3214711843,4154762323,2524082916,1539358875,3266819957,486407649,2928907069,1780885068,1513502316,1094664062,49805301,1338821763,1546925160,4104496465,887481809,150073849,2473685474,1943591083,1395732834,1058346282,201589768,1388824469,1696801606,1589887901,672667696,2711000631,251987210,3046808111,151455502,907153956,2608889883,1038279391,652995533,1764173646,3451040383,2675275242,453576978,2659418909,1949051992,773462580,756751158,2993581788,3998898868,4221608027,4132590244,1295727478,1641469623,3467883389,2066295122,1055122397,1898917726,2542044179,4115878822,1758581177,0,753790401,1612718144,536673507,3367088505,3982187446,3194645204,1187761037,3653156455,1262041458,3729410708,3561770136,3898103984,1255133061,1808847035,720367557,3853167183,385612781,3309519750,3612167578,1429418854,2491778321,3477423498,284817897,100794884,2172616702,4031795360,1144798328,3131023141,3819481163,4082192802,4272137053,3225436288,2324664069,2912064063,3164445985,1211644016,83228145,3753688163,3249976951,1977277103,1663115586,806359072,452984805,250868733,1842533055,1288555905,336333848,890442534,804056259,3781124030,2727843637,3427026056,957814574,1472513171,4071073621,2189328124,1195195770,2892260552,3881655738,723065138,2507371494,2690670784,2558624025,3511635870,2145180835,1713513028,2116692564,2878378043,2206763019,3393603212,703524551,3552098411,1007948840,2044649127,3797835452,487262998,1994120109,1004593371,1446130276,1312438900,503974420,3679013266,168166924,1814307912,3831258296,1573044895,1859376061,4021070915,2791465668,2828112185,2761266481,937747667,2339994098,854058965,1137232011,1496790894,3077402074,2358086913,1691735473,3528347292,3769215305,3027004632,4199962284,133494003,636152527,2942657994,2390391540,3920539207,403179536,3585784431,2289596656,1864705354,1915629148,605822008,4054230615,3350508659,1371981463,602466507,2094914977,2624877800,555687742,3712699286,3703422305,2257292045,2240449039,2423288032,1111375484,3300242801,2858837708,3628615824,84083462,32962295,302911004,2741068226,1597322602,4183250862,3501832553,2441512471,1489093017,656219450,3114180135,954327513,335083755,3013122091,856756514,3144247762,1893325225,2307821063,2811532339,3063651117,572399164,2458355477,552200649,1238290055,4283782570,2015897680,2061492133,2408352771,4171342169,2156497161,386731290,3669999461,837215959,3326231172,3093850320,3275833730,2962856233,1999449434,286199582,3417354363,4233385128,3602627437,974525996]),p=new Uint32Array([1667483301,2088564868,2004348569,2071721613,4076011277,1802229437,1869602481,3318059348,808476752,16843267,1734856361,724260477,4278118169,3621238114,2880130534,1987505306,3402272581,2189565853,3385428288,2105408135,4210749205,1499050731,1195871945,4042324747,2913812972,3570709351,2728550397,2947499498,2627478463,2762232823,1920132246,3233848155,3082253762,4261273884,2475900334,640044138,909536346,1061125697,4160222466,3435955023,875849820,2779075060,3857043764,4059166984,1903288979,3638078323,825320019,353708607,67373068,3351745874,589514341,3284376926,404238376,2526427041,84216335,2593796021,117902857,303178806,2155879323,3806519101,3958099238,656887401,2998042573,1970662047,151589403,2206408094,741103732,437924910,454768173,1852759218,1515893998,2694863867,1381147894,993752653,3604395873,3014884814,690573947,3823361342,791633521,2223248279,1397991157,3520182632,0,3991781676,538984544,4244431647,2981198280,1532737261,1785386174,3419114822,3200149465,960066123,1246401758,1280088276,1482207464,3486483786,3503340395,4025468202,2863288293,4227591446,1128498885,1296931543,859006549,2240090516,1162185423,4193904912,33686534,2139094657,1347461360,1010595908,2678007226,2829601763,1364304627,2745392638,1077969088,2408514954,2459058093,2644320700,943222856,4126535940,3166462943,3065411521,3671764853,555827811,269492272,4294960410,4092853518,3537026925,3452797260,202119188,320022069,3974939439,1600110305,2543269282,1145342156,387395129,3301217111,2812761586,2122251394,1027439175,1684326572,1566423783,421081643,1936975509,1616953504,2172721560,1330618065,3705447295,572671078,707417214,2425371563,2290617219,1179028682,4008625961,3099093971,336865340,3739133817,1583267042,185275933,3688607094,3772832571,842163286,976909390,168432670,1229558491,101059594,606357612,1549580516,3267534685,3553869166,2896970735,1650640038,2442213800,2509582756,3840201527,2038035083,3890730290,3368586051,926379609,1835915959,2374828428,3587551588,1313774802,2846444e3,1819072692,1448520954,4109693703,3941256997,1701169839,2054878350,2930657257,134746136,3132780501,2021191816,623200879,774790258,471611428,2795919345,3031724999,3334903633,3907570467,3722289532,1953818780,522141217,1263245021,3183305180,2341145990,2324303749,1886445712,1044282434,3048567236,1718013098,1212715224,50529797,4143380225,235805714,1633796771,892693087,1465364217,3115936208,2256934801,3250690392,488454695,2661164985,3789674808,4177062675,2560109491,286335539,1768542907,3654920560,2391672713,2492740519,2610638262,505297954,2273777042,3924412704,3469641545,1431677695,673730680,3755976058,2357986191,2711706104,2307459456,218962455,3216991706,3873888049,1111655622,1751699640,1094812355,2576951728,757946999,252648977,2964356043,1414834428,3149622742,370551866]),q=new Uint32Array([1673962851,2096661628,2012125559,2079755643,4076801522,1809235307,1876865391,3314635973,811618352,16909057,1741597031,727088427,4276558334,3618988759,2874009259,1995217526,3398387146,2183110018,3381215433,2113570685,4209972730,1504897881,1200539975,4042984432,2906778797,3568527316,2724199842,2940594863,2619588508,2756966308,1927583346,3231407040,3077948087,4259388669,2470293139,642542118,913070646,1065238847,4160029431,3431157708,879254580,2773611685,3855693029,4059629809,1910674289,3635114968,828527409,355090197,67636228,3348452039,591815971,3281870531,405809176,2520228246,84545285,2586817946,118360327,304363026,2149292928,3806281186,3956090603,659450151,2994720178,1978310517,152181513,2199756419,743994412,439627290,456535323,1859957358,1521806938,2690382752,1386542674,997608763,3602342358,3011366579,693271337,3822927587,794718511,2215876484,1403450707,3518589137,0,3988860141,541089824,4242743292,2977548465,1538714971,1792327274,3415033547,3194476990,963791673,1251270218,1285084236,1487988824,3481619151,3501943760,4022676207,2857362858,4226619131,1132905795,1301993293,862344499,2232521861,1166724933,4192801017,33818114,2147385727,1352724560,1014514748,2670049951,2823545768,1369633617,2740846243,1082179648,2399505039,2453646738,2636233885,946882616,4126213365,3160661948,3061301686,3668932058,557998881,270544912,4293204735,4093447923,3535760850,3447803085,202904588,321271059,3972214764,1606345055,2536874647,1149815876,388905239,3297990596,2807427751,2130477694,1031423805,1690872932,1572530013,422718233,1944491379,1623236704,2165938305,1335808335,3701702620,574907938,710180394,2419829648,2282455944,1183631942,4006029806,3094074296,338181140,3735517662,1589437022,185998603,3685578459,3772464096,845436466,980700730,169090570,1234361161,101452294,608726052,1555620956,3265224130,3552407251,2890133420,1657054818,2436475025,2503058581,3839047652,2045938553,3889509095,3364570056,929978679,1843050349,2365688973,3585172693,1318900302,2840191145,1826141292,1454176854,4109567988,3939444202,1707781989,2062847610,2923948462,135272456,3127891386,2029029496,625635109,777810478,473441308,2790781350,3027486644,3331805638,3905627112,3718347997,1961401460,524165407,1268178251,3177307325,2332919435,2316273034,1893765232,1048330814,3044132021,1724688998,1217452104,50726147,4143383030,236720654,1640145761,896163637,1471084887,3110719673,2249691526,3248052417,490350365,2653403550,3789109473,4176155640,2553000856,287453969,1775418217,3651760345,2382858638,2486413204,2603464347,507257374,2266337927,3922272489,3464972750,1437269845,676362280,3752164063,2349043596,2707028129,2299101321,219813645,3211123391,3872862694,1115997762,1758509160,1099088705,2569646233,760903469,253628687,2960903088,1420360788,3144537787,371997206]),r=new Uint32Array([3332727651,4169432188,4003034999,4136467323,4279104242,3602738027,3736170351,2438251973,1615867952,33751297,3467208551,1451043627,3877240574,3043153879,1306962859,3969545846,2403715786,530416258,2302724553,4203183485,4011195130,3001768281,2395555655,4211863792,1106029997,3009926356,1610457762,1173008303,599760028,1408738468,3835064946,2606481600,1975695287,3776773629,1034851219,1282024998,1817851446,2118205247,4110612471,2203045068,1750873140,1374987685,3509904869,4178113009,3801313649,2876496088,1649619249,708777237,135005188,2505230279,1181033251,2640233411,807933976,933336726,168756485,800430746,235472647,607523346,463175808,3745374946,3441880043,1315514151,2144187058,3936318837,303761673,496927619,1484008492,875436570,908925723,3702681198,3035519578,1543217312,2767606354,1984772923,3076642518,2110698419,1383803177,3711886307,1584475951,328696964,2801095507,3110654417,0,3240947181,1080041504,3810524412,2043195825,3069008731,3569248874,2370227147,1742323390,1917532473,2497595978,2564049996,2968016984,2236272591,3144405200,3307925487,1340451498,3977706491,2261074755,2597801293,1716859699,294946181,2328839493,3910203897,67502594,4269899647,2700103760,2017737788,632987551,1273211048,2733855057,1576969123,2160083008,92966799,1068339858,566009245,1883781176,4043634165,1675607228,2009183926,2943736538,1113792801,540020752,3843751935,4245615603,3211645650,2169294285,403966988,641012499,3274697964,3202441055,899848087,2295088196,775493399,2472002756,1441965991,4236410494,2051489085,3366741092,3135724893,841685273,3868554099,3231735904,429425025,2664517455,2743065820,1147544098,1417554474,1001099408,193169544,2362066502,3341414126,1809037496,675025940,2809781982,3168951902,371002123,2910247899,3678134496,1683370546,1951283770,337512970,2463844681,201983494,1215046692,3101973596,2673722050,3178157011,1139780780,3299238498,967348625,832869781,3543655652,4069226873,3576883175,2336475336,1851340599,3669454189,25988493,2976175573,2631028302,1239460265,3635702892,2902087254,4077384948,3475368682,3400492389,4102978170,1206496942,270010376,1876277946,4035475576,1248797989,1550986798,941890588,1475454630,1942467764,2538718918,3408128232,2709315037,3902567540,1042358047,2531085131,1641856445,226921355,260409994,3767562352,2084716094,1908716981,3433719398,2430093384,100991747,4144101110,470945294,3265487201,1784624437,2935576407,1775286713,395413126,2572730817,975641885,666476190,3644383713,3943954680,733190296,573772049,3535497577,2842745305,126455438,866620564,766942107,1008868894,361924487,3374377449,2269761230,2868860245,1350051880,2776293343,59739276,1509466529,159418761,437718285,1708834751,3610371814,2227585602,3501746280,2193834305,699439513,1517759789,504434447,2076946608,2835108948,1842789307,742004246]),s=8,t=14;b.exports={};var u=[128,192,256];for(var v in u)b.exports[u[v]]=l(u[v])},{"../../util.js":61}],7:[function(a,b){function c(){}function d(a){this.bf=new c,this.bf.init(e.str2bin(a)),this.encrypt=function(a){return this.bf.encrypt_block(a)}}c.prototype.BLOCKSIZE=8,c.prototype.SBOXES=[[3509652390,2564797868,805139163,3491422135,3101798381,1780907670,3128725573,4046225305,614570311,3012652279,134345442,2240740374,1667834072,1901547113,2757295779,4103290238,227898511,1921955416,1904987480,2182433518,2069144605,3260701109,2620446009,720527379,3318853667,677414384,3393288472,3101374703,2390351024,1614419982,1822297739,2954791486,3608508353,3174124327,2024746970,1432378464,3864339955,2857741204,1464375394,1676153920,1439316330,715854006,3033291828,289532110,2706671279,2087905683,3018724369,1668267050,732546397,1947742710,3462151702,2609353502,2950085171,1814351708,2050118529,680887927,999245976,1800124847,3300911131,1713906067,1641548236,4213287313,1216130144,1575780402,4018429277,3917837745,3693486850,3949271944,596196993,3549867205,258830323,2213823033,772490370,2760122372,1774776394,2652871518,566650946,4142492826,1728879713,2882767088,1783734482,3629395816,2517608232,2874225571,1861159788,326777828,3124490320,2130389656,2716951837,967770486,1724537150,2185432712,2364442137,1164943284,2105845187,998989502,3765401048,2244026483,1075463327,1455516326,1322494562,910128902,469688178,1117454909,936433444,3490320968,3675253459,1240580251,122909385,2157517691,634681816,4142456567,3825094682,3061402683,2540495037,79693498,3249098678,1084186820,1583128258,426386531,1761308591,1047286709,322548459,995290223,1845252383,2603652396,3431023940,2942221577,3202600964,3727903485,1712269319,422464435,3234572375,1170764815,3523960633,3117677531,1434042557,442511882,3600875718,1076654713,1738483198,4213154764,2393238008,3677496056,1014306527,4251020053,793779912,2902807211,842905082,4246964064,1395751752,1040244610,2656851899,3396308128,445077038,3742853595,3577915638,679411651,2892444358,2354009459,1767581616,3150600392,3791627101,3102740896,284835224,4246832056,1258075500,768725851,2589189241,3069724005,3532540348,1274779536,3789419226,2764799539,1660621633,3471099624,4011903706,913787905,3497959166,737222580,2514213453,2928710040,3937242737,1804850592,3499020752,2949064160,2386320175,2390070455,2415321851,4061277028,2290661394,2416832540,1336762016,1754252060,3520065937,3014181293,791618072,3188594551,3933548030,2332172193,3852520463,3043980520,413987798,3465142937,3030929376,4245938359,2093235073,3534596313,375366246,2157278981,2479649556,555357303,3870105701,2008414854,3344188149,4221384143,3956125452,2067696032,3594591187,2921233993,2428461,544322398,577241275,1471733935,610547355,4027169054,1432588573,1507829418,2025931657,3646575487,545086370,48609733,2200306550,1653985193,298326376,1316178497,3007786442,2064951626,458293330,2589141269,3591329599,3164325604,727753846,2179363840,146436021,1461446943,4069977195,705550613,3059967265,3887724982,4281599278,3313849956,1404054877,2845806497,146425753,1854211946],[1266315497,3048417604,3681880366,3289982499,290971e4,1235738493,2632868024,2414719590,3970600049,1771706367,1449415276,3266420449,422970021,1963543593,2690192192,3826793022,1062508698,1531092325,1804592342,2583117782,2714934279,4024971509,1294809318,4028980673,1289560198,2221992742,1669523910,35572830,157838143,1052438473,1016535060,1802137761,1753167236,1386275462,3080475397,2857371447,1040679964,2145300060,2390574316,1461121720,2956646967,4031777805,4028374788,33600511,2920084762,1018524850,629373528,3691585981,3515945977,2091462646,2486323059,586499841,988145025,935516892,3367335476,2599673255,2839830854,265290510,3972581182,2759138881,3795373465,1005194799,847297441,406762289,1314163512,1332590856,1866599683,4127851711,750260880,613907577,1450815602,3165620655,3734664991,3650291728,3012275730,3704569646,1427272223,778793252,1343938022,2676280711,2052605720,1946737175,3164576444,3914038668,3967478842,3682934266,1661551462,3294938066,4011595847,840292616,3712170807,616741398,312560963,711312465,1351876610,322626781,1910503582,271666773,2175563734,1594956187,70604529,3617834859,1007753275,1495573769,4069517037,2549218298,2663038764,504708206,2263041392,3941167025,2249088522,1514023603,1998579484,1312622330,694541497,2582060303,2151582166,1382467621,776784248,2618340202,3323268794,2497899128,2784771155,503983604,4076293799,907881277,423175695,432175456,1378068232,4145222326,3954048622,3938656102,3820766613,2793130115,2977904593,26017576,3274890735,3194772133,1700274565,1756076034,4006520079,3677328699,720338349,1533947780,354530856,688349552,3973924725,1637815568,332179504,3949051286,53804574,2852348879,3044236432,1282449977,3583942155,3416972820,4006381244,1617046695,2628476075,3002303598,1686838959,431878346,2686675385,1700445008,1080580658,1009431731,832498133,3223435511,2605976345,2271191193,2516031870,1648197032,4164389018,2548247927,300782431,375919233,238389289,3353747414,2531188641,2019080857,1475708069,455242339,2609103871,448939670,3451063019,1395535956,2413381860,1841049896,1491858159,885456874,4264095073,4001119347,1565136089,3898914787,1108368660,540939232,1173283510,2745871338,3681308437,4207628240,3343053890,4016749493,1699691293,1103962373,3625875870,2256883143,3830138730,1031889488,3479347698,1535977030,4236805024,3251091107,2132092099,1774941330,1199868427,1452454533,157007616,2904115357,342012276,595725824,1480756522,206960106,497939518,591360097,863170706,2375253569,3596610801,1814182875,2094937945,3421402208,1082520231,3463918190,2785509508,435703966,3908032597,1641649973,2842273706,3305899714,1510255612,2148256476,2655287854,3276092548,4258621189,236887753,3681803219,274041037,1734335097,3815195456,3317970021,1899903192,1026095262,4050517792,356393447,2410691914,3873677099,3682840055],[3913112168,2491498743,4132185628,2489919796,1091903735,1979897079,3170134830,3567386728,3557303409,857797738,1136121015,1342202287,507115054,2535736646,337727348,3213592640,1301675037,2528481711,1895095763,1721773893,3216771564,62756741,2142006736,835421444,2531993523,1442658625,3659876326,2882144922,676362277,1392781812,170690266,3921047035,1759253602,3611846912,1745797284,664899054,1329594018,3901205900,3045908486,2062866102,2865634940,3543621612,3464012697,1080764994,553557557,3656615353,3996768171,991055499,499776247,1265440854,648242737,3940784050,980351604,3713745714,1749149687,3396870395,4211799374,3640570775,1161844396,3125318951,1431517754,545492359,4268468663,3499529547,1437099964,2702547544,3433638243,2581715763,2787789398,1060185593,1593081372,2418618748,4260947970,69676912,2159744348,86519011,2512459080,3838209314,1220612927,3339683548,133810670,1090789135,1078426020,1569222167,845107691,3583754449,4072456591,1091646820,628848692,1613405280,3757631651,526609435,236106946,48312990,2942717905,3402727701,1797494240,859738849,992217954,4005476642,2243076622,3870952857,3732016268,765654824,3490871365,2511836413,1685915746,3888969200,1414112111,2273134842,3281911079,4080962846,172450625,2569994100,980381355,4109958455,2819808352,2716589560,2568741196,3681446669,3329971472,1835478071,660984891,3704678404,4045999559,3422617507,3040415634,1762651403,1719377915,3470491036,2693910283,3642056355,3138596744,1364962596,2073328063,1983633131,926494387,3423689081,2150032023,4096667949,1749200295,3328846651,309677260,2016342300,1779581495,3079819751,111262694,1274766160,443224088,298511866,1025883608,3806446537,1145181785,168956806,3641502830,3584813610,1689216846,3666258015,3200248200,1692713982,2646376535,4042768518,1618508792,1610833997,3523052358,4130873264,2001055236,3610705100,2202168115,4028541809,2961195399,1006657119,2006996926,3186142756,1430667929,3210227297,1314452623,4074634658,4101304120,2273951170,1399257539,3367210612,3027628629,1190975929,2062231137,2333990788,2221543033,2438960610,1181637006,548689776,2362791313,3372408396,3104550113,3145860560,296247880,1970579870,3078560182,3769228297,1714227617,3291629107,3898220290,166772364,1251581989,493813264,448347421,195405023,2709975567,677966185,3703036547,1463355134,2715995803,1338867538,1343315457,2802222074,2684532164,233230375,2599980071,2000651841,3277868038,1638401717,4028070440,3237316320,6314154,819756386,300326615,590932579,1405279636,3267499572,3150704214,2428286686,3959192993,3461946742,1862657033,1266418056,963775037,2089974820,2263052895,1917689273,448879540,3550394620,3981727096,150775221,3627908307,1303187396,508620638,2975983352,2726630617,1817252668,1876281319,1457606340,908771278,3720792119,3617206836,2455994898,1729034894,1080033504],[976866871,3556439503,2881648439,1522871579,1555064734,1336096578,3548522304,2579274686,3574697629,3205460757,3593280638,3338716283,3079412587,564236357,2993598910,1781952180,1464380207,3163844217,3332601554,1699332808,1393555694,1183702653,3581086237,1288719814,691649499,2847557200,2895455976,3193889540,2717570544,1781354906,1676643554,2592534050,3230253752,1126444790,2770207658,2633158820,2210423226,2615765581,2414155088,3127139286,673620729,2805611233,1269405062,4015350505,3341807571,4149409754,1057255273,2012875353,2162469141,2276492801,2601117357,993977747,3918593370,2654263191,753973209,36408145,2530585658,25011837,3520020182,2088578344,530523599,2918365339,1524020338,1518925132,3760827505,3759777254,1202760957,3985898139,3906192525,674977740,4174734889,2031300136,2019492241,3983892565,4153806404,3822280332,352677332,2297720250,60907813,90501309,3286998549,1016092578,2535922412,2839152426,457141659,509813237,4120667899,652014361,1966332200,2975202805,55981186,2327461051,676427537,3255491064,2882294119,3433927263,1307055953,942726286,933058658,2468411793,3933900994,4215176142,1361170020,2001714738,2830558078,3274259782,1222529897,1679025792,2729314320,3714953764,1770335741,151462246,3013232138,1682292957,1483529935,471910574,1539241949,458788160,3436315007,1807016891,3718408830,978976581,1043663428,3165965781,1927990952,4200891579,2372276910,3208408903,3533431907,1412390302,2931980059,4132332400,1947078029,3881505623,4168226417,2941484381,1077988104,1320477388,886195818,18198404,3786409e3,2509781533,112762804,3463356488,1866414978,891333506,18488651,661792760,1628790961,3885187036,3141171499,876946877,2693282273,1372485963,791857591,2686433993,3759982718,3167212022,3472953795,2716379847,445679433,3561995674,3504004811,3574258232,54117162,3331405415,2381918588,3769707343,4154350007,1140177722,4074052095,668550556,3214352940,367459370,261225585,2610173221,4209349473,3468074219,3265815641,314222801,3066103646,3808782860,282218597,3406013506,3773591054,379116347,1285071038,846784868,2669647154,3771962079,3550491691,2305946142,453669953,1268987020,3317592352,3279303384,3744833421,2610507566,3859509063,266596637,3847019092,517658769,3462560207,3443424879,370717030,4247526661,2224018117,4143653529,4112773975,2788324899,2477274417,1456262402,2901442914,1517677493,1846949527,2295493580,3734397586,2176403920,1280348187,1908823572,3871786941,846861322,1172426758,3287448474,3383383037,1655181056,3139813346,901632758,1897031941,2986607138,3066810236,3447102507,1393639104,373351379,950779232,625454576,3124240540,4148612726,2007998917,544563296,2244738638,2330496472,2058025392,1291430526,424198748,50039436,29584100,3605783033,2429876329,2791104160,1057563949,3255363231,3075367218,3463963227,1469046755,985887462]],c.prototype.PARRAY=[608135816,2242054355,320440878,57701188,2752067618,698298832,137296536,3964562569,1160258022,953160567,3193202383,887688300,3232508343,3380367581,1065670069,3041331479,2450970073,2306472731],c.prototype.NN=16,c.prototype._clean=function(a){if(0>a){var b=2147483647&a;
a=b+2147483648}return a},c.prototype._F=function(a){var b,c,d,e,f;return e=255&a,a>>>=8,d=255&a,a>>>=8,c=255&a,a>>>=8,b=255&a,f=this.sboxes[0][b]+this.sboxes[1][c],f^=this.sboxes[2][d],f+=this.sboxes[3][e]},c.prototype._encrypt_block=function(a){var b,c=a[0],d=a[1];for(b=0;b<this.NN;++b){c^=this.parray[b],d=this._F(c)^d;var e=c;c=d,d=e}c^=this.parray[this.NN+0],d^=this.parray[this.NN+1],a[0]=this._clean(d),a[1]=this._clean(c)},c.prototype.encrypt_block=function(a){var b,c=[0,0],d=this.BLOCKSIZE/2;for(b=0;b<this.BLOCKSIZE/2;++b)c[0]=c[0]<<8|255&a[b+0],c[1]=c[1]<<8|255&a[b+d];this._encrypt_block(c);var e=[];for(b=0;b<this.BLOCKSIZE/2;++b)e[b+0]=c[0]>>>24-8*b&255,e[b+d]=c[1]>>>24-8*b&255;return e},c.prototype._decrypt_block=function(a){var b,c=a[0],d=a[1];for(b=this.NN+1;b>1;--b){c^=this.parray[b],d=this._F(c)^d;var e=c;c=d,d=e}c^=this.parray[1],d^=this.parray[0],a[0]=this._clean(d),a[1]=this._clean(c)},c.prototype.init=function(a){var b,c=0;for(this.parray=[],b=0;b<this.NN+2;++b){var d,e=0;for(d=0;4>d;++d)e=e<<8|255&a[c],++c>=a.length&&(c=0);this.parray[b]=this.PARRAY[b]^e}for(this.sboxes=[],b=0;4>b;++b)for(this.sboxes[b]=[],c=0;256>c;++c)this.sboxes[b][c]=this.SBOXES[b][c];var f=[0,0];for(b=0;b<this.NN+2;b+=2)this._encrypt_block(f),this.parray[b+0]=f[0],this.parray[b+1]=f[1];for(b=0;4>b;++b)for(c=0;256>c;c+=2)this._encrypt_block(f),this.sboxes[b][c+0]=f[0],this.sboxes[b][c+1]=f[1]};var e=a("../../util.js");b.exports=d,b.exports.keySize=d.prototype.keySize=16,b.exports.blockSize=d.prototype.blockSize=16},{"../../util.js":61}],8:[function(a,b){function c(){function a(a,b,c){var d=b+a,e=d<<c|d>>>32-c;return(f[0][e>>>24]^f[1][e>>>16&255])-f[2][e>>>8&255]+f[3][255&e]}function b(a,b,c){var d=b^a,e=d<<c|d>>>32-c;return f[0][e>>>24]-f[1][e>>>16&255]+f[2][e>>>8&255]^f[3][255&e]}function c(a,b,c){var d=b-a,e=d<<c|d>>>32-c;return(f[0][e>>>24]+f[1][e>>>16&255]^f[2][e>>>8&255])-f[3][255&e]}this.BlockSize=8,this.KeySize=16,this.setKey=function(a){if(this.masking=new Array(16),this.rotate=new Array(16),this.reset(),a.length!=this.KeySize)throw new Error("CAST-128: keys must be 16 bytes");return this.keySchedule(a),!0},this.reset=function(){for(var a=0;16>a;a++)this.masking[a]=0,this.rotate[a]=0},this.getBlockSize=function(){return BlockSize},this.encrypt=function(d){for(var e=new Array(d.length),f=0;f<d.length;f+=8){var g,h=d[f]<<24|d[f+1]<<16|d[f+2]<<8|d[f+3],i=d[f+4]<<24|d[f+5]<<16|d[f+6]<<8|d[f+7];g=i,i=h^a(i,this.masking[0],this.rotate[0]),h=g,g=i,i=h^b(i,this.masking[1],this.rotate[1]),h=g,g=i,i=h^c(i,this.masking[2],this.rotate[2]),h=g,g=i,i=h^a(i,this.masking[3],this.rotate[3]),h=g,g=i,i=h^b(i,this.masking[4],this.rotate[4]),h=g,g=i,i=h^c(i,this.masking[5],this.rotate[5]),h=g,g=i,i=h^a(i,this.masking[6],this.rotate[6]),h=g,g=i,i=h^b(i,this.masking[7],this.rotate[7]),h=g,g=i,i=h^c(i,this.masking[8],this.rotate[8]),h=g,g=i,i=h^a(i,this.masking[9],this.rotate[9]),h=g,g=i,i=h^b(i,this.masking[10],this.rotate[10]),h=g,g=i,i=h^c(i,this.masking[11],this.rotate[11]),h=g,g=i,i=h^a(i,this.masking[12],this.rotate[12]),h=g,g=i,i=h^b(i,this.masking[13],this.rotate[13]),h=g,g=i,i=h^c(i,this.masking[14],this.rotate[14]),h=g,g=i,i=h^a(i,this.masking[15],this.rotate[15]),h=g,e[f]=i>>>24&255,e[f+1]=i>>>16&255,e[f+2]=i>>>8&255,e[f+3]=255&i,e[f+4]=h>>>24&255,e[f+5]=h>>>16&255,e[f+6]=h>>>8&255,e[f+7]=255&h}return e},this.decrypt=function(d){for(var e=new Array(d.length),f=0;f<d.length;f+=8){var g,h=d[f]<<24|d[f+1]<<16|d[f+2]<<8|d[f+3],i=d[f+4]<<24|d[f+5]<<16|d[f+6]<<8|d[f+7];g=i,i=h^a(i,this.masking[15],this.rotate[15]),h=g,g=i,i=h^c(i,this.masking[14],this.rotate[14]),h=g,g=i,i=h^b(i,this.masking[13],this.rotate[13]),h=g,g=i,i=h^a(i,this.masking[12],this.rotate[12]),h=g,g=i,i=h^c(i,this.masking[11],this.rotate[11]),h=g,g=i,i=h^b(i,this.masking[10],this.rotate[10]),h=g,g=i,i=h^a(i,this.masking[9],this.rotate[9]),h=g,g=i,i=h^c(i,this.masking[8],this.rotate[8]),h=g,g=i,i=h^b(i,this.masking[7],this.rotate[7]),h=g,g=i,i=h^a(i,this.masking[6],this.rotate[6]),h=g,g=i,i=h^c(i,this.masking[5],this.rotate[5]),h=g,g=i,i=h^b(i,this.masking[4],this.rotate[4]),h=g,g=i,i=h^a(i,this.masking[3],this.rotate[3]),h=g,g=i,i=h^c(i,this.masking[2],this.rotate[2]),h=g,g=i,i=h^b(i,this.masking[1],this.rotate[1]),h=g,g=i,i=h^a(i,this.masking[0],this.rotate[0]),h=g,e[f]=i>>>24&255,e[f+1]=i>>>16&255,e[f+2]=i>>>8&255,e[f+3]=255&i,e[f+4]=h>>>24&255,e[f+5]=h>>16&255,e[f+6]=h>>8&255,e[f+7]=255&h}return e};var d=new Array(4);d[0]=new Array(4),d[0][0]=new Array(4,0,13,15,12,14,8),d[0][1]=new Array(5,2,16,18,17,19,10),d[0][2]=new Array(6,3,23,22,21,20,9),d[0][3]=new Array(7,1,26,25,27,24,11),d[1]=new Array(4),d[1][0]=new Array(0,6,21,23,20,22,16),d[1][1]=new Array(1,4,0,2,1,3,18),d[1][2]=new Array(2,5,7,6,5,4,17),d[1][3]=new Array(3,7,10,9,11,8,19),d[2]=new Array(4),d[2][0]=new Array(4,0,13,15,12,14,8),d[2][1]=new Array(5,2,16,18,17,19,10),d[2][2]=new Array(6,3,23,22,21,20,9),d[2][3]=new Array(7,1,26,25,27,24,11),d[3]=new Array(4),d[3][0]=new Array(0,6,21,23,20,22,16),d[3][1]=new Array(1,4,0,2,1,3,18),d[3][2]=new Array(2,5,7,6,5,4,17),d[3][3]=new Array(3,7,10,9,11,8,19);var e=new Array(4);e[0]=new Array(4),e[0][0]=new Array(24,25,23,22,18),e[0][1]=new Array(26,27,21,20,22),e[0][2]=new Array(28,29,19,18,25),e[0][3]=new Array(30,31,17,16,28),e[1]=new Array(4),e[1][0]=new Array(3,2,12,13,8),e[1][1]=new Array(1,0,14,15,13),e[1][2]=new Array(7,6,8,9,3),e[1][3]=new Array(5,4,10,11,7),e[2]=new Array(4),e[2][0]=new Array(19,18,28,29,25),e[2][1]=new Array(17,16,30,31,28),e[2][2]=new Array(23,22,24,25,18),e[2][3]=new Array(21,20,26,27,22),e[3]=new Array(4),e[3][0]=new Array(8,9,7,6,3),e[3][1]=new Array(10,11,5,4,7),e[3][2]=new Array(12,13,3,2,8),e[3][3]=new Array(14,15,1,0,13),this.keySchedule=function(a){var b,c,g=new Array(8),h=new Array(32);for(b=0;4>b;b++)c=4*b,g[b]=a[c]<<24|a[c+1]<<16|a[c+2]<<8|a[c+3];for(var i,j=[6,7,4,5],k=0,l=0;2>l;l++)for(var m=0;4>m;m++){for(c=0;4>c;c++){var n=d[m][c];i=g[n[1]],i^=f[4][g[n[2]>>>2]>>>24-8*(3&n[2])&255],i^=f[5][g[n[3]>>>2]>>>24-8*(3&n[3])&255],i^=f[6][g[n[4]>>>2]>>>24-8*(3&n[4])&255],i^=f[7][g[n[5]>>>2]>>>24-8*(3&n[5])&255],i^=f[j[c]][g[n[6]>>>2]>>>24-8*(3&n[6])&255],g[n[0]]=i}for(c=0;4>c;c++){var o=e[m][c];i=f[4][g[o[0]>>>2]>>>24-8*(3&o[0])&255],i^=f[5][g[o[1]>>>2]>>>24-8*(3&o[1])&255],i^=f[6][g[o[2]>>>2]>>>24-8*(3&o[2])&255],i^=f[7][g[o[3]>>>2]>>>24-8*(3&o[3])&255],i^=f[4+c][g[o[4]>>>2]>>>24-8*(3&o[4])&255],h[k]=i,k++}}for(b=0;16>b;b++)this.masking[b]=h[b],this.rotate[b]=31&h[16+b]};var f=new Array(8);f[0]=new Array(821772500,2678128395,1810681135,1059425402,505495343,2617265619,1610868032,3483355465,3218386727,2294005173,3791863952,2563806837,1852023008,365126098,3269944861,584384398,677919599,3229601881,4280515016,2002735330,1136869587,3744433750,2289869850,2731719981,2714362070,879511577,1639411079,575934255,717107937,2857637483,576097850,2731753936,1725645e3,2810460463,5111599,767152862,2543075244,1251459544,1383482551,3052681127,3089939183,3612463449,1878520045,1510570527,2189125840,2431448366,582008916,3163445557,1265446783,1354458274,3529918736,3202711853,3073581712,3912963487,3029263377,1275016285,4249207360,2905708351,3304509486,1442611557,3585198765,2712415662,2731849581,3248163920,2283946226,208555832,2766454743,1331405426,1447828783,3315356441,3108627284,2957404670,2981538698,3339933917,1669711173,286233437,1465092821,1782121619,3862771680,710211251,980974943,1651941557,430374111,2051154026,704238805,4128970897,3144820574,2857402727,948965521,3333752299,2227686284,718756367,2269778983,2731643755,718440111,2857816721,3616097120,1113355533,2478022182,410092745,1811985197,1944238868,2696854588,1415722873,1682284203,1060277122,1998114690,1503841958,82706478,2315155686,1068173648,845149890,2167947013,1768146376,1993038550,3566826697,3390574031,940016341,3355073782,2328040721,904371731,1205506512,4094660742,2816623006,825647681,85914773,2857843460,1249926541,1417871568,3287612,3211054559,3126306446,1975924523,1353700161,2814456437,2438597621,1800716203,722146342,2873936343,1151126914,4160483941,2877670899,458611604,2866078500,3483680063,770352098,2652916994,3367839148,3940505011,3585973912,3809620402,718646636,2504206814,2914927912,3631288169,2857486607,2860018678,575749918,2857478043,718488780,2069512688,3548183469,453416197,1106044049,3032691430,52586708,3378514636,3459808877,3211506028,1785789304,218356169,3571399134,3759170522,1194783844,1523787992,3007827094,1975193539,2555452411,1341901877,3045838698,3776907964,3217423946,2802510864,2889438986,1057244207,1636348243,3761863214,1462225785,2632663439,481089165,718503062,24497053,3332243209,3344655856,3655024856,3960371065,1195698900,2971415156,3710176158,2115785917,4027663609,3525578417,2524296189,2745972565,3564906415,1372086093,1452307862,2780501478,1476592880,3389271281,18495466,2378148571,901398090,891748256,3279637769,3157290713,2560960102,1447622437,4284372637,216884176,2086908623,1879786977,3588903153,2242455666,2938092967,3559082096,2810645491,758861177,1121993112,215018983,642190776,4169236812,1196255959,2081185372,3508738393,941322904,4124243163,2877523539,1848581667,2205260958,3180453958,2589345134,3694731276,550028657,2519456284,3789985535,2973870856,2093648313,443148163,46942275,2734146937,1117713533,1115362972,1523183689,3717140224,1551984063),f[1]=new Array(522195092,4010518363,1776537470,960447360,4267822970,4005896314,1435016340,1929119313,2913464185,1310552629,3579470798,3724818106,2579771631,1594623892,417127293,2715217907,2696228731,1508390405,3994398868,3925858569,3695444102,4019471449,3129199795,3770928635,3520741761,990456497,4187484609,2783367035,21106139,3840405339,631373633,3783325702,532942976,396095098,3548038825,4267192484,2564721535,2011709262,2039648873,620404603,3776170075,2898526339,3612357925,4159332703,1645490516,223693667,1567101217,3362177881,1029951347,3470931136,3570957959,1550265121,119497089,972513919,907948164,3840628539,1613718692,3594177948,465323573,2659255085,654439692,2575596212,2699288441,3127702412,277098644,624404830,4100943870,2717858591,546110314,2403699828,3655377447,1321679412,4236791657,1045293279,4010672264,895050893,2319792268,494945126,1914543101,2777056443,3894764339,2219737618,311263384,4275257268,3458730721,669096869,3584475730,3835122877,3319158237,3949359204,2005142349,2713102337,2228954793,3769984788,569394103,3855636576,1425027204,108000370,2736431443,3671869269,3043122623,1750473702,2211081108,762237499,3972989403,2798899386,3061857628,2943854345,867476300,964413654,1591880597,1594774276,2179821409,552026980,3026064248,3726140315,2283577634,3110545105,2152310760,582474363,1582640421,1383256631,2043843868,3322775884,1217180674,463797851,2763038571,480777679,2718707717,2289164131,3118346187,214354409,200212307,3810608407,3025414197,2674075964,3997296425,1847405948,1342460550,510035443,4080271814,815934613,833030224,1620250387,1945732119,2703661145,3966000196,1388869545,3456054182,2687178561,2092620194,562037615,1356438536,3409922145,3261847397,1688467115,2150901366,631725691,3840332284,549916902,3455104640,394546491,837744717,2114462948,751520235,2221554606,2415360136,3999097078,2063029875,803036379,2702586305,821456707,3019566164,360699898,4018502092,3511869016,3677355358,2402471449,812317050,49299192,2570164949,3259169295,2816732080,3331213574,3101303564,2156015656,3705598920,3546263921,143268808,3200304480,1638124008,3165189453,3341807610,578956953,2193977524,3638120073,2333881532,807278310,658237817,2969561766,1641658566,11683945,3086995007,148645947,1138423386,4158756760,1981396783,2401016740,3699783584,380097457,2680394679,2803068651,3334260286,441530178,4016580796,1375954390,761952171,891809099,2183123478,157052462,3683840763,1592404427,341349109,2438483839,1417898363,644327628,2233032776,2353769706,2201510100,220455161,1815641738,182899273,2995019788,3627381533,3702638151,2890684138,1052606899,588164016,1681439879,4038439418,2405343923,4229449282,167996282,1336969661,1688053129,2739224926,1543734051,1046297529,1138201970,2121126012,115334942,1819067631,1902159161,1941945968,2206692869,1159982321),f[2]=new Array(2381300288,637164959,3952098751,3893414151,1197506559,916448331,2350892612,2932787856,3199334847,4009478890,3905886544,1373570990,2450425862,4037870920,3778841987,2456817877,286293407,124026297,3001279700,1028597854,3115296800,4208886496,2691114635,2188540206,1430237888,1218109995,3572471700,308166588,570424558,2187009021,2455094765,307733056,1310360322,3135275007,1384269543,2388071438,863238079,2359263624,2801553128,3380786597,2831162807,1470087780,1728663345,4072488799,1090516929,532123132,2389430977,1132193179,2578464191,3051079243,1670234342,1434557849,2711078940,1241591150,3314043432,3435360113,3091448339,1812415473,2198440252,267246943,796911696,3619716990,38830015,1526438404,2806502096,374413614,2943401790,1489179520,1603809326,1920779204,168801282,260042626,2358705581,1563175598,2397674057,1356499128,2217211040,514611088,2037363785,2186468373,4022173083,2792511869,2913485016,1173701892,4200428547,3896427269,1334932762,2455136706,602925377,2835607854,1613172210,41346230,2499634548,2457437618,2188827595,41386358,4172255629,1313404830,2405527007,3801973774,2217704835,873260488,2528884354,2478092616,4012915883,2555359016,2006953883,2463913485,575479328,2218240648,2099895446,660001756,2341502190,3038761536,3888151779,3848713377,3286851934,1022894237,1620365795,3449594689,1551255054,15374395,3570825345,4249311020,4151111129,3181912732,310226346,1133119310,530038928,136043402,2476768958,3107506709,2544909567,1036173560,2367337196,1681395281,1758231547,3641649032,306774401,1575354324,3716085866,1990386196,3114533736,2455606671,1262092282,3124342505,2768229131,4210529083,1833535011,423410938,660763973,2187129978,1639812e3,3508421329,3467445492,310289298,272797111,2188552562,2456863912,310240523,677093832,1013118031,901835429,3892695601,1116285435,3036471170,1337354835,243122523,520626091,277223598,4244441197,4194248841,1766575121,594173102,316590669,742362309,3536858622,4176435350,3838792410,2501204839,1229605004,3115755532,1552908988,2312334149,979407927,3959474601,1148277331,176638793,3614686272,2083809052,40992502,1340822838,2731552767,3535757508,3560899520,1354035053,122129617,7215240,2732932949,3118912700,2718203926,2539075635,3609230695,3725561661,1928887091,2882293555,1988674909,2063640240,2491088897,1459647954,4189817080,2302804382,1113892351,2237858528,1927010603,4002880361,1856122846,1594404395,2944033133,3855189863,3474975698,1643104450,4054590833,3431086530,1730235576,2984608721,3084664418,2131803598,4178205752,267404349,1617849798,1616132681,1462223176,736725533,2327058232,551665188,2945899023,1749386277,2575514597,1611482493,674206544,2201269090,3642560800,728599968,1680547377,2620414464,1388111496,453204106,4156223445,1094905244,2754698257,2201108165,3757000246,2704524545,3922940700,3996465027),f[3]=new Array(2645754912,532081118,2814278639,3530793624,1246723035,1689095255,2236679235,4194438865,2116582143,3859789411,157234593,2045505824,4245003587,1687664561,4083425123,605965023,672431967,1336064205,3376611392,214114848,4258466608,3232053071,489488601,605322005,3998028058,264917351,1912574028,756637694,436560991,202637054,135989450,85393697,2152923392,3896401662,2895836408,2145855233,3535335007,115294817,3147733898,1922296357,3464822751,4117858305,1037454084,2725193275,2127856640,1417604070,1148013728,1827919605,642362335,2929772533,909348033,1346338451,3547799649,297154785,1917849091,4161712827,2883604526,3968694238,1469521537,3780077382,3375584256,1763717519,136166297,4290970789,1295325189,2134727907,2798151366,1566297257,3672928234,2677174161,2672173615,965822077,2780786062,289653839,1133871874,3491843819,35685304,1068898316,418943774,672553190,642281022,2346158704,1954014401,3037126780,4079815205,2030668546,3840588673,672283427,1776201016,359975446,3750173538,555499703,2769985273,1324923,69110472,152125443,3176785106,3822147285,1340634837,798073664,1434183902,15393959,216384236,1303690150,3881221631,3711134124,3960975413,106373927,2578434224,1455997841,1801814300,1578393881,1854262133,3188178946,3258078583,2302670060,1539295533,3505142565,3078625975,2372746020,549938159,3278284284,2620926080,181285381,2865321098,3970029511,68876850,488006234,1728155692,2608167508,836007927,2435231793,919367643,3339422534,3655756360,1457871481,40520939,1380155135,797931188,234455205,2255801827,3990488299,397000196,739833055,3077865373,2871719860,4022553888,772369276,390177364,3853951029,557662966,740064294,1640166671,1699928825,3535942136,622006121,3625353122,68743880,1742502,219489963,1664179233,1577743084,1236991741,410585305,2366487942,823226535,1050371084,3426619607,3586839478,212779912,4147118561,1819446015,1911218849,530248558,3486241071,3252585495,2886188651,3410272728,2342195030,20547779,2982490058,3032363469,3631753222,312714466,1870521650,1493008054,3491686656,615382978,4103671749,2534517445,1932181,2196105170,278426614,6369430,3274544417,2913018367,697336853,2143000447,2946413531,701099306,1558357093,2805003052,3500818408,2321334417,3567135975,216290473,3591032198,23009561,1996984579,3735042806,2024298078,3739440863,569400510,2339758983,3016033873,3097871343,3639523026,3844324983,3256173865,795471839,2951117563,4101031090,4091603803,3603732598,971261452,534414648,428311343,3389027175,2844869880,694888862,1227866773,2456207019,3043454569,2614353370,3749578031,3676663836,459166190,4132644070,1794958188,51825668,2252611902,3084671440,2036672799,3436641603,1099053433,2469121526,3059204941,1323291266,2061838604,1018778475,2233344254,2553501054,334295216,3556750194,1065731521,183467730),f[4]=new Array(2127105028,745436345,2601412319,2788391185,3093987327,500390133,1155374404,389092991,150729210,3891597772,3523549952,1935325696,716645080,946045387,2901812282,1774124410,3869435775,4039581901,3293136918,3438657920,948246080,363898952,3867875531,1286266623,1598556673,68334250,630723836,1104211938,1312863373,613332731,2377784574,1101634306,441780740,3129959883,1917973735,2510624549,3238456535,2544211978,3308894634,1299840618,4076074851,1756332096,3977027158,297047435,3790297736,2265573040,3621810518,1311375015,1667687725,47300608,3299642885,2474112369,201668394,1468347890,576830978,3594690761,3742605952,1958042578,1747032512,3558991340,1408974056,3366841779,682131401,1033214337,1545599232,4265137049,206503691,103024618,2855227313,1337551222,2428998917,2963842932,4015366655,3852247746,2796956967,3865723491,3747938335,247794022,3755824572,702416469,2434691994,397379957,851939612,2314769512,218229120,1380406772,62274761,214451378,3170103466,2276210409,3845813286,28563499,446592073,1693330814,3453727194,29968656,3093872512,220656637,2470637031,77972100,1667708854,1358280214,4064765667,2395616961,325977563,4277240721,4220025399,3605526484,3355147721,811859167,3069544926,3962126810,652502677,3075892249,4132761541,3498924215,1217549313,3250244479,3858715919,3053989961,1538642152,2279026266,2875879137,574252750,3324769229,2651358713,1758150215,141295887,2719868960,3515574750,4093007735,4194485238,1082055363,3417560400,395511885,2966884026,179534037,3646028556,3738688086,1092926436,2496269142,257381841,3772900718,1636087230,1477059743,2499234752,3811018894,2675660129,3285975680,90732309,1684827095,1150307763,1723134115,3237045386,1769919919,1240018934,815675215,750138730,2239792499,1234303040,1995484674,138143821,675421338,1145607174,1936608440,3238603024,2345230278,2105974004,323969391,779555213,3004902369,2861610098,1017501463,2098600890,2628620304,2940611490,2682542546,1171473753,3656571411,3687208071,4091869518,393037935,159126506,1662887367,1147106178,391545844,3452332695,1891500680,3016609650,1851642611,546529401,1167818917,3194020571,2848076033,3953471836,575554290,475796850,4134673196,450035699,2351251534,844027695,1080539133,86184846,1554234488,3692025454,1972511363,2018339607,1491841390,1141460869,1061690759,4244549243,2008416118,2351104703,2868147542,1598468138,722020353,1027143159,212344630,1387219594,1725294528,3745187956,2500153616,458938280,4129215917,1828119673,544571780,3503225445,2297937496,1241802790,267843827,2694610800,1397140384,1558801448,3782667683,1806446719,929573330,2234912681,400817706,616011623,4121520928,3603768725,1761550015,1968522284,4053731006,4192232858,4005120285,872482584,3140537016,3894607381,2287405443,1963876937,3663887957,1584857e3,2975024454,1833426440,4025083860),f[5]=new Array(4143615901,749497569,1285769319,3795025788,2514159847,23610292,3974978748,844452780,3214870880,3751928557,2213566365,1676510905,448177848,3730751033,4086298418,2307502392,871450977,3222878141,4110862042,3831651966,2735270553,1310974780,2043402188,1218528103,2736035353,4274605013,2702448458,3936360550,2693061421,162023535,2827510090,687910808,23484817,3784910947,3371371616,779677500,3503626546,3473927188,4157212626,3500679282,4248902014,2466621104,3899384794,1958663117,925738300,1283408968,3669349440,1840910019,137959847,2679828185,1239142320,1315376211,1547541505,1690155329,739140458,3128809933,3933172616,3876308834,905091803,1548541325,4040461708,3095483362,144808038,451078856,676114313,2861728291,2469707347,993665471,373509091,2599041286,4025009006,4170239449,2149739950,3275793571,3749616649,2794760199,1534877388,572371878,2590613551,1753320020,3467782511,1405125690,4270405205,633333386,3026356924,3475123903,632057672,2846462855,1404951397,3882875879,3915906424,195638627,2385783745,3902872553,1233155085,3355999740,2380578713,2702246304,2144565621,3663341248,3894384975,2502479241,4248018925,3094885567,1594115437,572884632,3385116731,767645374,1331858858,1475698373,3793881790,3532746431,1321687957,619889600,1121017241,3440213920,2070816767,2833025776,1933951238,4095615791,890643334,3874130214,859025556,360630002,925594799,1764062180,3920222280,4078305929,979562269,2810700344,4087740022,1949714515,546639971,1165388173,3069891591,1495988560,922170659,1291546247,2107952832,1813327274,3406010024,3306028637,4241950635,153207855,2313154747,1608695416,1150242611,1967526857,721801357,1220138373,3691287617,3356069787,2112743302,3281662835,1111556101,1778980689,250857638,2298507990,673216130,2846488510,3207751581,3562756981,3008625920,3417367384,2198807050,529510932,3547516680,3426503187,2364944742,102533054,2294910856,1617093527,1204784762,3066581635,1019391227,1069574518,1317995090,1691889997,3661132003,510022745,3238594800,1362108837,1817929911,2184153760,805817662,1953603311,3699844737,120799444,2118332377,207536705,2282301548,4120041617,145305846,2508124933,3086745533,3261524335,1877257368,2977164480,3160454186,2503252186,4221677074,759945014,254147243,2767453419,3801518371,629083197,2471014217,907280572,3900796746,940896768,2751021123,2625262786,3161476951,3661752313,3260732218,1425318020,2977912069,1496677566,3988592072,2140652971,3126511541,3069632175,977771578,1392695845,1698528874,1411812681,1369733098,1343739227,3620887944,1142123638,67414216,3102056737,3088749194,1626167401,2546293654,3941374235,697522451,33404913,143560186,2595682037,994885535,1247667115,3859094837,2699155541,3547024625,4114935275,2968073508,3199963069,2732024527,1237921620,951448369,1898488916,1211705605,2790989240,2233243581,3598044975),f[6]=new Array(2246066201,858518887,1714274303,3485882003,713916271,2879113490,3730835617,539548191,36158695,1298409750,419087104,1358007170,749914897,2989680476,1261868530,2995193822,2690628854,3443622377,3780124940,3796824509,2976433025,4259637129,1551479e3,512490819,1296650241,951993153,2436689437,2460458047,144139966,3136204276,310820559,3068840729,643875328,1969602020,1680088954,2185813161,3283332454,672358534,198762408,896343282,276269502,3014846926,84060815,197145886,376173866,3943890818,3813173521,3545068822,1316698879,1598252827,2633424951,1233235075,859989710,2358460855,3503838400,3409603720,1203513385,1193654839,2792018475,2060853022,207403770,1144516871,3068631394,1121114134,177607304,3785736302,326409831,1929119770,2983279095,4183308101,3474579288,3200513878,3228482096,119610148,1170376745,3378393471,3163473169,951863017,3337026068,3135789130,2907618374,1183797387,2015970143,4045674555,2182986399,2952138740,3928772205,384012900,2454997643,10178499,2879818989,2596892536,111523738,2995089006,451689641,3196290696,235406569,1441906262,3890558523,3013735005,4158569349,1644036924,376726067,1006849064,3664579700,2041234796,1021632941,1374734338,2566452058,371631263,4007144233,490221539,206551450,3140638584,1053219195,1853335209,3412429660,3562156231,735133835,1623211703,3104214392,2738312436,4096837757,3366392578,3110964274,3956598718,3196820781,2038037254,3877786376,2339753847,300912036,3766732888,2372630639,1516443558,4200396704,1574567987,4069441456,4122592016,2699739776,146372218,2748961456,2043888151,35287437,2596680554,655490400,1132482787,110692520,1031794116,2188192751,1324057718,1217253157,919197030,686247489,3261139658,1028237775,3135486431,3059715558,2460921700,986174950,2661811465,4062904701,2752986992,3709736643,367056889,1353824391,731860949,1650113154,1778481506,784341916,357075625,3608602432,1074092588,2480052770,3811426202,92751289,877911070,3600361838,1231880047,480201094,3756190983,3094495953,434011822,87971354,363687820,1717726236,1901380172,3926403882,2481662265,400339184,1490350766,2661455099,1389319756,2558787174,784598401,1983468483,30828846,3550527752,2716276238,3841122214,1765724805,1955612312,1277890269,1333098070,1564029816,2704417615,1026694237,3287671188,1260819201,3349086767,1016692350,1582273796,1073413053,1995943182,694588404,1025494639,3323872702,3551898420,4146854327,453260480,1316140391,1435673405,3038941953,3486689407,1622062951,403978347,817677117,950059133,4246079218,3278066075,1486738320,1417279718,481875527,2549965225,3933690356,760697757,1452955855,3897451437,1177426808,1702951038,4085348628,2447005172,1084371187,3516436277,3068336338,1073369276,1027665953,3284188590,1230553676,1368340146,2226246512,267243139,2274220762,4070734279,2497715176,2423353163,2504755875),f[7]=new Array(3793104909,3151888380,2817252029,895778965,2005530807,3871412763,237245952,86829237,296341424,3851759377,3974600970,2475086196,709006108,1994621201,2972577594,937287164,3734691505,168608556,3189338153,2225080640,3139713551,3033610191,3025041904,77524477,185966941,1208824168,2344345178,1721625922,3354191921,1066374631,1927223579,1971335949,2483503697,1551748602,2881383779,2856329572,3003241482,48746954,1398218158,2050065058,313056748,4255789917,393167848,1912293076,940740642,3465845460,3091687853,2522601570,2197016661,1727764327,364383054,492521376,1291706479,3264136376,1474851438,1685747964,2575719748,1619776915,1814040067,970743798,1561002147,2925768690,2123093554,1880132620,3151188041,697884420,2550985770,2607674513,2659114323,110200136,1489731079,997519150,1378877361,3527870668,478029773,2766872923,1022481122,431258168,1112503832,897933369,2635587303,669726182,3383752315,918222264,163866573,3246985393,3776823163,114105080,1903216136,761148244,3571337562,1690750982,3166750252,1037045171,1888456500,2010454850,642736655,616092351,365016990,1185228132,4174898510,1043824992,2023083429,2241598885,3863320456,3279669087,3674716684,108438443,2132974366,830746235,606445527,4173263986,2204105912,1844756978,2532684181,4245352700,2969441100,3796921661,1335562986,4061524517,2720232303,2679424040,634407289,885462008,3294724487,3933892248,2094100220,339117932,4048830727,3202280980,1458155303,2689246273,1022871705,2464987878,3714515309,353796843,2822958815,4256850100,4052777845,551748367,618185374,3778635579,4020649912,1904685140,3069366075,2670879810,3407193292,2954511620,4058283405,2219449317,3135758300,1120655984,3447565834,1474845562,3577699062,550456716,3466908712,2043752612,881257467,869518812,2005220179,938474677,3305539448,3850417126,1315485940,3318264702,226533026,965733244,321539988,1136104718,804158748,573969341,3708209826,937399083,3290727049,2901666755,1461057207,4013193437,4066861423,3242773476,2421326174,1581322155,3028952165,786071460,3900391652,3918438532,1485433313,4023619836,3708277595,3678951060,953673138,1467089153,1930354364,1533292819,2492563023,1346121658,1685000834,1965281866,3765933717,4190206607,2052792609,3515332758,690371149,3125873887,2180283551,2903598061,3933952357,436236910,289419410,14314871,1242357089,2904507907,1616633776,2666382180,585885352,3471299210,2699507360,1432659641,277164553,3354103607,770115018,2303809295,3741942315,3177781868,2853364978,2269453327,3774259834,987383833,1290892879,225909803,1741533526,890078084,1496906255,1111072499,916028167,243534141,1252605537,2204162171,531204876,290011180,3916834213,102027703,237315147,209093447,1486785922,220223953,2758195998,4175039106,82940208,3127791296,2569425252,518464269,1353887104,3941492737,2377294467,3935040926)}function d(a){this.cast5=new c,this.cast5.setKey(e.str2bin(a)),this.encrypt=function(a){return this.cast5.encrypt(a)}}var e=a("../../util.js");b.exports=d,b.exports.blockSize=d.prototype.blockSize=8,b.exports.keySize=d.prototype.keySize=16},{"../../util.js":61}],9:[function(a,b){function c(a,b,c,d,g,h){var i,j,k,l,m,n,o,p,q,r,s,t,u,v,w=new Array(16843776,0,65536,16843780,16842756,66564,4,65536,1024,16843776,16843780,1024,16778244,16842756,16777216,4,1028,16778240,16778240,66560,66560,16842752,16842752,16778244,65540,16777220,16777220,65540,0,1028,66564,16777216,65536,16843780,4,16842752,16843776,16777216,16777216,1024,16842756,65536,66560,16777220,1024,4,16778244,66564,16843780,65540,16842752,16778244,16777220,1028,66564,16843776,1028,16778240,16778240,0,65540,66560,0,16842756),x=new Array(-2146402272,-2147450880,32768,1081376,1048576,32,-2146435040,-2147450848,-2147483616,-2146402272,-2146402304,-2147483648,-2147450880,1048576,32,-2146435040,1081344,1048608,-2147450848,0,-2147483648,32768,1081376,-2146435072,1048608,-2147483616,0,1081344,32800,-2146402304,-2146435072,32800,0,1081376,-2146435040,1048576,-2147450848,-2146435072,-2146402304,32768,-2146435072,-2147450880,32,-2146402272,1081376,32,32768,-2147483648,32800,-2146402304,1048576,-2147483616,1048608,-2147450848,-2147483616,1048608,1081344,0,-2147450880,32800,-2147483648,-2146435040,-2146402272,1081344),y=new Array(520,134349312,0,134348808,134218240,0,131592,134218240,131080,134217736,134217736,131072,134349320,131080,134348800,520,134217728,8,134349312,512,131584,134348800,134348808,131592,134218248,131584,131072,134218248,8,134349320,512,134217728,134349312,134217728,131080,520,131072,134349312,134218240,0,512,131080,134349320,134218240,134217736,512,0,134348808,134218248,131072,134217728,134349320,8,131592,131584,134217736,134348800,134218248,520,134348800,131592,8,134348808,131584),z=new Array(8396801,8321,8321,128,8396928,8388737,8388609,8193,0,8396800,8396800,8396929,129,0,8388736,8388609,1,8192,8388608,8396801,128,8388608,8193,8320,8388737,1,8320,8388736,8192,8396928,8396929,129,8388736,8388609,8396800,8396929,129,0,0,8396800,8320,8388736,8388737,1,8396801,8321,8321,128,8396929,129,1,8192,8388609,8193,8396928,8388737,8193,8320,8388608,8396801,128,8388608,8192,8396928),A=new Array(256,34078976,34078720,1107296512,524288,256,1073741824,34078720,1074266368,524288,33554688,1074266368,1107296512,1107820544,524544,1073741824,33554432,1074266112,1074266112,0,1073742080,1107820800,1107820800,33554688,1107820544,1073742080,0,1107296256,34078976,33554432,1107296256,524544,524288,1107296512,256,33554432,1073741824,34078720,1107296512,1074266368,33554688,1073741824,1107820544,34078976,1074266368,256,33554432,1107820544,1107820800,524544,1107296256,1107820800,34078720,0,1074266112,1107296256,524544,33554688,1073742080,524288,0,1074266112,34078976,1073742080),B=new Array(536870928,541065216,16384,541081616,541065216,16,541081616,4194304,536887296,4210704,4194304,536870928,4194320,536887296,536870912,16400,0,4194320,536887312,16384,4210688,536887312,16,541065232,541065232,0,4210704,541081600,16400,4210688,541081600,536870912,536887296,16,541065232,4210688,541081616,4194304,16400,536870928,4194304,536887296,536870912,16400,536870928,541081616,4210688,541065216,4210704,541081600,0,541065232,16,16384,541065216,4210704,16384,4194320,536887312,0,541081600,536870912,4194320,536887312),C=new Array(2097152,69206018,67110914,0,2048,67110914,2099202,69208064,69208066,2097152,0,67108866,2,67108864,69206018,2050,67110912,2099202,2097154,67110912,67108866,69206016,69208064,2097154,69206016,2048,2050,69208066,2099200,2,67108864,2099200,67108864,2099200,2097152,67110914,67110914,69206018,69206018,2,2097154,67108864,67110912,2097152,69208064,2050,2099202,69208064,2050,67108866,69208066,69206016,2099200,0,2,69208066,0,2099202,69206016,2048,67108866,67110912,2048,2097154),D=new Array(268439616,4096,262144,268701760,268435456,268439616,64,268435456,262208,268697600,268701760,266240,268701696,266304,4096,64,268697600,268435520,268439552,4160,266240,262208,268697664,268701696,4160,0,0,268697664,268435520,268439552,266304,262144,266304,262144,268701696,4096,64,268697664,4096,266304,268439552,64,268435520,268697600,268697664,268435456,262144,268439616,0,268701760,262208,268435520,268697600,268439552,268439616,0,268701760,266240,266240,4160,4160,262208,268435456,268701696),E=0,F=b.length,G=0,H=32==a.length?3:9;
for(p=3==H?c?new Array(0,32,2):new Array(30,-2,-2):c?new Array(0,32,2,62,30,-2,64,96,2):new Array(94,62,-2,32,64,2,30,-2,-2),c&&(b=e(b,h),F=b.length),result="",tempresult="",1==d&&(q=g.charCodeAt(E++)<<24|g.charCodeAt(E++)<<16|g.charCodeAt(E++)<<8|g.charCodeAt(E++),s=g.charCodeAt(E++)<<24|g.charCodeAt(E++)<<16|g.charCodeAt(E++)<<8|g.charCodeAt(E++),E=0);F>E;){for(n=b.charCodeAt(E++)<<24|b.charCodeAt(E++)<<16|b.charCodeAt(E++)<<8|b.charCodeAt(E++),o=b.charCodeAt(E++)<<24|b.charCodeAt(E++)<<16|b.charCodeAt(E++)<<8|b.charCodeAt(E++),1==d&&(c?(n^=q,o^=s):(r=q,t=s,q=n,s=o)),k=252645135&(n>>>4^o),o^=k,n^=k<<4,k=65535&(n>>>16^o),o^=k,n^=k<<16,k=858993459&(o>>>2^n),n^=k,o^=k<<2,k=16711935&(o>>>8^n),n^=k,o^=k<<8,k=1431655765&(n>>>1^o),o^=k,n^=k<<1,n=n<<1|n>>>31,o=o<<1|o>>>31,j=0;H>j;j+=3){for(u=p[j+1],v=p[j+2],i=p[j];i!=u;i+=v)l=o^a[i],m=(o>>>4|o<<28)^a[i+1],k=n,n=o,o=k^(x[l>>>24&63]|z[l>>>16&63]|B[l>>>8&63]|D[63&l]|w[m>>>24&63]|y[m>>>16&63]|A[m>>>8&63]|C[63&m]);k=n,n=o,o=k}n=n>>>1|n<<31,o=o>>>1|o<<31,k=1431655765&(n>>>1^o),o^=k,n^=k<<1,k=16711935&(o>>>8^n),n^=k,o^=k<<8,k=858993459&(o>>>2^n),n^=k,o^=k<<2,k=65535&(n>>>16^o),o^=k,n^=k<<16,k=252645135&(n>>>4^o),o^=k,n^=k<<4,1==d&&(c?(q=n,s=o):(n^=r,o^=t)),tempresult+=String.fromCharCode(n>>>24,n>>>16&255,n>>>8&255,255&n,o>>>24,o>>>16&255,o>>>8&255,255&o),G+=8,512==G&&(result+=tempresult,tempresult="",G=0)}return result+=tempresult,c||(result=f(result,h)),result}function d(a){pc2bytes0=new Array(0,4,536870912,536870916,65536,65540,536936448,536936452,512,516,536871424,536871428,66048,66052,536936960,536936964),pc2bytes1=new Array(0,1,1048576,1048577,67108864,67108865,68157440,68157441,256,257,1048832,1048833,67109120,67109121,68157696,68157697),pc2bytes2=new Array(0,8,2048,2056,16777216,16777224,16779264,16779272,0,8,2048,2056,16777216,16777224,16779264,16779272),pc2bytes3=new Array(0,2097152,134217728,136314880,8192,2105344,134225920,136323072,131072,2228224,134348800,136445952,139264,2236416,134356992,136454144),pc2bytes4=new Array(0,262144,16,262160,0,262144,16,262160,4096,266240,4112,266256,4096,266240,4112,266256),pc2bytes5=new Array(0,1024,32,1056,0,1024,32,1056,33554432,33555456,33554464,33555488,33554432,33555456,33554464,33555488),pc2bytes6=new Array(0,268435456,524288,268959744,2,268435458,524290,268959746,0,268435456,524288,268959744,2,268435458,524290,268959746),pc2bytes7=new Array(0,65536,2048,67584,536870912,536936448,536872960,536938496,131072,196608,133120,198656,537001984,537067520,537004032,537069568),pc2bytes8=new Array(0,262144,0,262144,2,262146,2,262146,33554432,33816576,33554432,33816576,33554434,33816578,33554434,33816578),pc2bytes9=new Array(0,268435456,8,268435464,0,268435456,8,268435464,1024,268436480,1032,268436488,1024,268436480,1032,268436488),pc2bytes10=new Array(0,32,0,32,1048576,1048608,1048576,1048608,8192,8224,8192,8224,1056768,1056800,1056768,1056800),pc2bytes11=new Array(0,16777216,512,16777728,2097152,18874368,2097664,18874880,67108864,83886080,67109376,83886592,69206016,85983232,69206528,85983744),pc2bytes12=new Array(0,4096,134217728,134221824,524288,528384,134742016,134746112,16,4112,134217744,134221840,524304,528400,134742032,134746128),pc2bytes13=new Array(0,4,256,260,0,4,256,260,1,5,257,261,1,5,257,261);for(var b,c,d,e=a.length>8?3:1,f=new Array(32*e),g=new Array(0,0,1,1,1,1,1,1,0,1,1,1,1,1,1,0),h=0,j=0,k=0;e>k;k++)for(left=a.charCodeAt(h++)<<24|a.charCodeAt(h++)<<16|a.charCodeAt(h++)<<8|a.charCodeAt(h++),right=a.charCodeAt(h++)<<24|a.charCodeAt(h++)<<16|a.charCodeAt(h++)<<8|a.charCodeAt(h++),d=252645135&(left>>>4^right),right^=d,left^=d<<4,d=65535&(right>>>-16^left),left^=d,right^=d<<-16,d=858993459&(left>>>2^right),right^=d,left^=d<<2,d=65535&(right>>>-16^left),left^=d,right^=d<<-16,d=1431655765&(left>>>1^right),right^=d,left^=d<<1,d=16711935&(right>>>8^left),left^=d,right^=d<<8,d=1431655765&(left>>>1^right),right^=d,left^=d<<1,d=left<<8|right>>>20&240,left=right<<24|right<<8&16711680|right>>>8&65280|right>>>24&240,right=d,i=0;i<g.length;i++)g[i]?(left=left<<2|left>>>26,right=right<<2|right>>>26):(left=left<<1|left>>>27,right=right<<1|right>>>27),left&=-15,right&=-15,b=pc2bytes0[left>>>28]|pc2bytes1[left>>>24&15]|pc2bytes2[left>>>20&15]|pc2bytes3[left>>>16&15]|pc2bytes4[left>>>12&15]|pc2bytes5[left>>>8&15]|pc2bytes6[left>>>4&15],c=pc2bytes7[right>>>28]|pc2bytes8[right>>>24&15]|pc2bytes9[right>>>20&15]|pc2bytes10[right>>>16&15]|pc2bytes11[right>>>12&15]|pc2bytes12[right>>>8&15]|pc2bytes13[right>>>4&15],d=65535&(c>>>16^b),f[j++]=b^d,f[j++]=c^d<<16;return f}function e(a,b){var c=8-a.length%8;return 2==b&&8>c?a+="        ".substr(0,c):1==b?a+=String.fromCharCode(c,c,c,c,c,c,c,c).substr(0,c):!b&&8>c&&(a+="\x00\x00\x00\x00\x00\x00\x00\x00".substr(0,c)),a}function f(a,b){if(2==b)a=a.replace(/ *$/g,"");else if(1==b){var c=a.charCodeAt(a.length-1);a=a.substr(0,a.length-c)}else b||(a=a.replace(/\0*$/g,""));return a}function g(a){this.key=[];for(var b=0;3>b;b++)this.key.push(a.substr(8*b,8));this.encrypt=function(a){return j.str2bin(c(d(this.key[2]),c(d(this.key[1]),c(d(this.key[0]),j.bin2str(a),!0,0,null,null),!1,0,null,null),!0,0,null,null))}}function h(a){this.key=a,this.encrypt=function(a,b){var e=d(this.key);return j.str2bin(c(e,j.bin2str(a),!0,0,null,b))},this.decrypt=function(a,b){var e=d(this.key);return j.str2bin(c(e,j.bin2str(a),!1,0,null,b))}}var j=a("../../util.js");g.keySize=g.prototype.keySize=24,g.blockSize=g.prototype.blockSize=8,b.exports={des:g,originalDes:h}},{"../../util.js":61}],10:[function(a,b){var c=a("./des.js");b.exports={des:c.originalDes,tripledes:c.des,cast5:a("./cast5.js"),twofish:a("./twofish.js"),blowfish:a("./blowfish.js"),idea:function(){throw new Error("IDEA symmetric-key algorithm not implemented")}};var d=a("./aes.js");for(var e in d)b.exports["aes"+e]=d[e]},{"./aes.js":6,"./blowfish.js":7,"./cast5.js":8,"./des.js":9,"./twofish.js":11}],11:[function(a,b){function c(a,b){return(a<<b|a>>>32-b)&j}function d(a,b){return a[b]|a[b+1]<<8|a[b+2]<<16|a[b+3]<<24}function e(a,b,c){a.splice(b,4,255&c,c>>>8&255,c>>>16&255,c>>>24&255)}function f(a,b){return a>>>8*b&255}function g(){function a(a){function b(a){return a^a>>2^[0,90,180,238][3&a]}function e(a){return a^a>>1^a>>2^[0,238,180,90][3&a]}function g(a,b){var c,d,e;for(c=0;8>c;c++)d=b>>>24,b=b<<8&j|a>>>24,a=a<<8&j,e=d<<1,128&d&&(e^=333),b^=d^e<<16,e^=d>>>1,1&d&&(e^=166),b^=e<<24|e<<8;return b}function h(a,b){var c,d,e,f;return c=b>>4,d=15&b,e=A[a][c^d],f=B[a][E[d]^F[c]],D[a][E[f]^F[e]]<<4|C[a][e^f]}function i(a,b){var c=f(a,0),d=f(a,1),e=f(a,2),g=f(a,3);switch(q){case 4:c=G[1][c]^f(b[3],0),d=G[0][d]^f(b[3],1),e=G[0][e]^f(b[3],2),g=G[1][g]^f(b[3],3);case 3:c=G[1][c]^f(b[2],0),d=G[1][d]^f(b[2],1),e=G[0][e]^f(b[2],2),g=G[0][g]^f(b[2],3);case 2:c=G[0][G[0][c]^f(b[1],0)]^f(b[0],0),d=G[0][G[1][d]^f(b[1],1)]^f(b[0],1),e=G[1][G[0][e]^f(b[1],2)]^f(b[0],2),g=G[1][G[1][g]^f(b[1],3)]^f(b[0],3)}return H[0][c]^H[1][d]^H[2][e]^H[3][g]}o=a;var k,l,m,n,p,q,r,u,v,w=[],x=[],y=[],z=[],A=[[8,1,7,13,6,15,3,2,0,11,5,9,14,12,10,4],[2,8,11,13,15,7,6,14,3,1,9,4,0,10,12,5]],B=[[14,12,11,8,1,2,3,5,15,4,10,6,7,0,9,13],[1,14,2,11,4,12,3,7,6,13,10,5,15,9,0,8]],C=[[11,10,5,14,6,13,9,0,12,8,15,3,2,4,7,1],[4,12,7,5,1,6,9,10,0,14,13,8,2,11,3,15]],D=[[13,7,15,4,1,2,6,14,9,11,3,0,8,5,12,10],[11,9,5,1,12,3,13,14,6,4,7,15,2,0,8,10]],E=[0,8,1,9,2,10,3,11,4,12,5,13,6,14,7,15],F=[0,9,2,11,4,13,6,15,8,1,10,3,12,5,14,7],G=[[],[]],H=[[],[],[],[]];for(o=o.slice(0,32),k=o.length;16!=k&&24!=k&&32!=k;)o[k++]=0;for(k=0;k<o.length;k+=4)y[k>>2]=d(o,k);for(k=0;256>k;k++)G[0][k]=h(0,k),G[1][k]=h(1,k);for(k=0;256>k;k++)r=G[1][k],u=b(r),v=e(r),H[0][k]=r+(u<<8)+(v<<16)+(v<<24),H[2][k]=u+(v<<8)+(r<<16)+(v<<24),r=G[0][k],u=b(r),v=e(r),H[1][k]=v+(v<<8)+(u<<16)+(r<<24),H[3][k]=u+(r<<8)+(v<<16)+(u<<24);for(q=y.length/2,k=0;q>k;k++)l=y[k+k],w[k]=l,m=y[k+k+1],x[k]=m,z[q-k-1]=g(l,m);for(k=0;40>k;k+=2)l=16843009*k,m=l+16843009,l=i(l,w),m=c(i(m,x),8),s[k]=l+m&j,s[k+1]=c(l+2*m,9);for(k=0;256>k;k++)switch(l=m=n=p=k,q){case 4:l=G[1][l]^f(z[3],0),m=G[0][m]^f(z[3],1),n=G[0][n]^f(z[3],2),p=G[1][p]^f(z[3],3);case 3:l=G[1][l]^f(z[2],0),m=G[1][m]^f(z[2],1),n=G[0][n]^f(z[2],2),p=G[0][p]^f(z[2],3);case 2:t[0][k]=H[0][G[0][G[0][l]^f(z[1],0)]^f(z[0],0)],t[1][k]=H[1][G[0][G[1][m]^f(z[1],1)]^f(z[0],1)],t[2][k]=H[2][G[1][G[0][n]^f(z[1],2)]^f(z[0],2)],t[3][k]=H[3][G[1][G[1][p]^f(z[1],3)]^f(z[0],3)]}}function b(a){return t[0][f(a,0)]^t[1][f(a,1)]^t[2][f(a,2)]^t[3][f(a,3)]}function g(a){return t[0][f(a,3)]^t[1][f(a,0)]^t[2][f(a,1)]^t[3][f(a,2)]}function h(a,d){var e=b(d[0]),f=g(d[1]);d[2]=c(d[2]^e+f+s[4*a+8]&j,31),d[3]=c(d[3],1)^e+2*f+s[4*a+9]&j,e=b(d[2]),f=g(d[3]),d[0]=c(d[0]^e+f+s[4*a+10]&j,31),d[1]=c(d[1],1)^e+2*f+s[4*a+11]&j}function i(a,d){var e=b(d[0]),f=g(d[1]);d[2]=c(d[2],1)^e+f+s[4*a+10]&j,d[3]=c(d[3]^e+2*f+s[4*a+11]&j,31),e=b(d[2]),f=g(d[3]),d[0]=c(d[0],1)^e+f+s[4*a+8]&j,d[1]=c(d[1]^e+2*f+s[4*a+9]&j,31)}function k(){s=[],t=[[],[],[],[]]}function l(a,b){p=a,q=b;for(var c=[d(p,q)^s[0],d(p,q+4)^s[1],d(p,q+8)^s[2],d(p,q+12)^s[3]],f=0;8>f;f++)h(f,c);return e(p,q,c[2]^s[4]),e(p,q+4,c[3]^s[5]),e(p,q+8,c[0]^s[6]),e(p,q+12,c[1]^s[7]),q+=16,p}function m(a,b){p=a,q=b;for(var c=[d(p,q)^s[4],d(p,q+4)^s[5],d(p,q+8)^s[6],d(p,q+12)^s[7]],f=7;f>=0;f--)i(f,c);e(p,q,c[2]^s[0]),e(p,q+4,c[3]^s[1]),e(p,q+8,c[0]^s[2]),e(p,q+12,c[1]^s[3]),q+=16}function n(){return p}var o=null,p=null,q=-1,r=null;r="twofish";var s=[],t=[[],[],[],[]];return{name:"twofish",blocksize:16,open:a,close:k,encrypt:l,decrypt:m,finalize:n}}function h(a){this.tf=g(),this.tf.open(k.str2bin(a),0),this.encrypt=function(a){return this.tf.encrypt(i(a),0)}}function i(a){for(var b=[],c=0;c<a.length;c++)b[c]=a[c];return b}var j=4294967295,k=a("../../util.js");b.exports=h,b.exports.keySize=h.prototype.keySize=32,b.exports.blockSize=h.prototype.blockSize=16},{"../../util.js":61}],12:[function(a,b){var c=a("./random.js"),d=a("./cipher"),e=a("./public_key"),f=a("../type/mpi.js");b.exports={publicKeyEncrypt:function(a,b,c){var d=function(){var d;switch(a){case"rsa_encrypt":case"rsa_encrypt_sign":var f=new e.rsa,g=b[0].toBigInteger(),h=b[1].toBigInteger();return d=c.toBigInteger(),[f.encrypt(d,h,g)];case"elgamal":var i=new e.elgamal,j=b[0].toBigInteger(),k=b[1].toBigInteger(),l=b[2].toBigInteger();return d=c.toBigInteger(),i.encrypt(d,k,j,l);default:return[]}}();return d.map(function(a){var b=new f;return b.fromBigInteger(a),b})},publicKeyDecrypt:function(a,b,c){var d,g=function(){switch(a){case"rsa_encrypt_sign":case"rsa_encrypt":var f=new e.rsa,g=b[0].toBigInteger(),h=b[1].toBigInteger(),i=b[2].toBigInteger();d=b[3].toBigInteger();var j=b[4].toBigInteger(),k=b[5].toBigInteger(),l=c[0].toBigInteger();return f.decrypt(l,g,h,i,d,j,k);case"elgamal":var m=new e.elgamal,n=b[3].toBigInteger(),o=c[0].toBigInteger(),p=c[1].toBigInteger();return d=b[0].toBigInteger(),m.decrypt(o,p,d,n);default:return null}}(),h=new f;return h.fromBigInteger(g),h},getPrivateMpiCount:function(a){switch(a){case"rsa_encrypt":case"rsa_encrypt_sign":case"rsa_sign":return 4;case"elgamal":return 1;case"dsa":return 1;default:throw new Error("Unknown algorithm")}},getPublicMpiCount:function(a){switch(a){case"rsa_encrypt":case"rsa_encrypt_sign":case"rsa_sign":return 2;case"elgamal":return 3;case"dsa":return 4;default:throw new Error("Unknown algorithm.")}},generateMpi:function(a,b){var c=function(){switch(a){case"rsa_encrypt":case"rsa_encrypt_sign":case"rsa_sign":var c=new e.rsa,d=c.generate(b,"10001"),f=[];return f.push(d.n),f.push(d.ee),f.push(d.d),f.push(d.p),f.push(d.q),f.push(d.u),f;default:throw new Error("Unsupported algorithm for key generation.")}}();return c.map(function(a){var b=new f;return b.fromBigInteger(a),b})},getPrefixRandom:function(a){return c.getRandomBytes(d[a].blockSize)},generateSessionKey:function(a){return c.getRandomBytes(d[a].keySize)}}},{"../type/mpi.js":59,"./cipher":10,"./public_key":23,"./random.js":26}],13:[function(a,b){var c=b.exports={},d=a("./forge_util.js"),e=null,f=!1,g=null,h=function(){e=String.fromCharCode(128),e+=d.fillString(String.fromCharCode(0),64),g=[1116352408,1899447441,3049323471,3921009573,961987163,1508970993,2453635748,2870763221,3624381080,310598401,607225278,1426881987,1925078388,2162078206,2614888103,3248222580,3835390401,4022224774,264347078,604807628,770255983,1249150122,1555081692,1996064986,2554220882,2821834349,2952996808,3210313671,3336571891,3584528711,113926993,338241895,666307205,773529912,1294757372,1396182291,1695183700,1986661051,2177026350,2456956037,2730485921,2820302411,3259730800,3345764771,3516065817,3600352804,4094571909,275423344,430227734,506948616,659060556,883997877,958139571,1322822218,1537002063,1747873779,1955562222,2024104815,2227730452,2361852424,2428436474,2756734187,3204031479,3329325298],f=!0},i=function(a,b,c){for(var d,e,f,h,i,j,k,l,m,n,o,p,q,r,s,t=c.length();t>=64;){for(k=0;16>k;++k)b[k]=c.getInt32();for(;64>k;++k)d=b[k-2],d=(d>>>17|d<<15)^(d>>>19|d<<13)^d>>>10,e=b[k-15],e=(e>>>7|e<<25)^(e>>>18|e<<14)^e>>>3,b[k]=d+b[k-7]+e+b[k-16]&4294967295;for(l=a.h0,m=a.h1,n=a.h2,o=a.h3,p=a.h4,q=a.h5,r=a.h6,s=a.h7,k=0;64>k;++k)h=(p>>>6|p<<26)^(p>>>11|p<<21)^(p>>>25|p<<7),i=r^p&(q^r),f=(l>>>2|l<<30)^(l>>>13|l<<19)^(l>>>22|l<<10),j=l&m|n&(l^m),d=s+h+i+g[k]+b[k],e=f+j,s=r,r=q,q=p,p=o+d&4294967295,o=n,n=m,m=l,l=d+e&4294967295;a.h0=a.h0+l&4294967295,a.h1=a.h1+m&4294967295,a.h2=a.h2+n&4294967295,a.h3=a.h3+o&4294967295,a.h4=a.h4+p&4294967295,a.h5=a.h5+q&4294967295,a.h6=a.h6+r&4294967295,a.h7=a.h7+s&4294967295,t-=64}};c.create=function(){f||h();var a=null,b=d.createBuffer(),c=new Array(64),g={algorithm:"sha256",blockLength:64,digestLength:32,messageLength:0};return g.start=function(){return g.messageLength=0,b=d.createBuffer(),a={h0:1779033703,h1:3144134277,h2:1013904242,h3:2773480762,h4:1359893119,h5:2600822924,h6:528734635,h7:1541459225},g},g.start(),g.update=function(e,f){return"utf8"===f&&(e=d.encodeUtf8(e)),g.messageLength+=e.length,b.putBytes(e),i(a,c,b),(b.read>2048||0===b.length())&&b.compact(),g},g.digest=function(){var f=g.messageLength,h=d.createBuffer();h.putBytes(b.bytes()),h.putBytes(e.substr(0,64-(f+8)%64)),h.putInt32(f>>>29&255),h.putInt32(f<<3&4294967295);var j={h0:a.h0,h1:a.h1,h2:a.h2,h3:a.h3,h4:a.h4,h5:a.h5,h6:a.h6,h7:a.h7};i(j,c,h);var k=d.createBuffer();return k.putInt32(j.h0),k.putInt32(j.h1),k.putInt32(j.h2),k.putInt32(j.h3),k.putInt32(j.h4),k.putInt32(j.h5),k.putInt32(j.h6),k.putInt32(j.h7),k},g}},{"./forge_util.js":14}],14:[function(a,b){var c=b.exports={};c.isArray=Array.isArray||function(a){return"[object Array]"===Object.prototype.toString.call(a)},c.isArrayBuffer=function(a){return"undefined"!=typeof ArrayBuffer&&a instanceof ArrayBuffer};var d=[];"undefined"!=typeof Int8Array&&d.push(Int8Array),"undefined"!=typeof Uint8Array&&d.push(Uint8Array),"undefined"!=typeof Uint8ClampedArray&&d.push(Uint8ClampedArray),"undefined"!=typeof Int16Array&&d.push(Int16Array),"undefined"!=typeof Uint16Array&&d.push(Uint16Array),"undefined"!=typeof Int32Array&&d.push(Int32Array),"undefined"!=typeof Uint32Array&&d.push(Uint32Array),"undefined"!=typeof Float32Array&&d.push(Float32Array),"undefined"!=typeof Float64Array&&d.push(Float64Array),c.isArrayBufferView=function(a){for(var b=0;b<d.length;++b)if(a instanceof d[b])return!0;return!1},c.ByteBuffer=function(a){if(this.data="",this.read=0,"string"==typeof a)this.data=a;else if(c.isArrayBuffer(a)||c.isArrayBufferView(a)){var b=new Uint8Array(a);try{this.data=String.fromCharCode.apply(null,b)}catch(d){for(var e=0;e<b.length;++e)this.putByte(b[e])}}},c.ByteBuffer.prototype.length=function(){return this.data.length-this.read},c.ByteBuffer.prototype.isEmpty=function(){return this.length()<=0},c.ByteBuffer.prototype.putByte=function(a){return this.data+=String.fromCharCode(a),this},c.ByteBuffer.prototype.fillWithByte=function(a,b){a=String.fromCharCode(a);for(var c=this.data;b>0;)1&b&&(c+=a),b>>>=1,b>0&&(a+=a);return this.data=c,this},c.ByteBuffer.prototype.putBytes=function(a){return this.data+=a,this},c.ByteBuffer.prototype.putString=function(a){return this.data+=c.encodeUtf8(a),this},c.ByteBuffer.prototype.putInt16=function(a){return this.data+=String.fromCharCode(a>>8&255)+String.fromCharCode(255&a),this},c.ByteBuffer.prototype.putInt24=function(a){return this.data+=String.fromCharCode(a>>16&255)+String.fromCharCode(a>>8&255)+String.fromCharCode(255&a),this},c.ByteBuffer.prototype.putInt32=function(a){return this.data+=String.fromCharCode(a>>24&255)+String.fromCharCode(a>>16&255)+String.fromCharCode(a>>8&255)+String.fromCharCode(255&a),this},c.ByteBuffer.prototype.putInt16Le=function(a){return this.data+=String.fromCharCode(255&a)+String.fromCharCode(a>>8&255),this},c.ByteBuffer.prototype.putInt24Le=function(a){return this.data+=String.fromCharCode(255&a)+String.fromCharCode(a>>8&255)+String.fromCharCode(a>>16&255),this},c.ByteBuffer.prototype.putInt32Le=function(a){return this.data+=String.fromCharCode(255&a)+String.fromCharCode(a>>8&255)+String.fromCharCode(a>>16&255)+String.fromCharCode(a>>24&255),this},c.ByteBuffer.prototype.putInt=function(a,b){do b-=8,this.data+=String.fromCharCode(a>>b&255);while(b>0);return this},c.ByteBuffer.prototype.putSignedInt=function(a,b){return 0>a&&(a+=2<<b-1),this.putInt(a,b)},c.ByteBuffer.prototype.putBuffer=function(a){return this.data+=a.getBytes(),this},c.ByteBuffer.prototype.getByte=function(){return this.data.charCodeAt(this.read++)},c.ByteBuffer.prototype.getInt16=function(){var a=this.data.charCodeAt(this.read)<<8^this.data.charCodeAt(this.read+1);return this.read+=2,a},c.ByteBuffer.prototype.getInt24=function(){var a=this.data.charCodeAt(this.read)<<16^this.data.charCodeAt(this.read+1)<<8^this.data.charCodeAt(this.read+2);return this.read+=3,a},c.ByteBuffer.prototype.getInt32=function(){var a=this.data.charCodeAt(this.read)<<24^this.data.charCodeAt(this.read+1)<<16^this.data.charCodeAt(this.read+2)<<8^this.data.charCodeAt(this.read+3);return this.read+=4,a},c.ByteBuffer.prototype.getInt16Le=function(){var a=this.data.charCodeAt(this.read)^this.data.charCodeAt(this.read+1)<<8;return this.read+=2,a},c.ByteBuffer.prototype.getInt24Le=function(){var a=this.data.charCodeAt(this.read)^this.data.charCodeAt(this.read+1)<<8^this.data.charCodeAt(this.read+2)<<16;return this.read+=3,a},c.ByteBuffer.prototype.getInt32Le=function(){var a=this.data.charCodeAt(this.read)^this.data.charCodeAt(this.read+1)<<8^this.data.charCodeAt(this.read+2)<<16^this.data.charCodeAt(this.read+3)<<24;return this.read+=4,a},c.ByteBuffer.prototype.getInt=function(a){var b=0;do b=(b<<8)+this.data.charCodeAt(this.read++),a-=8;while(a>0);return b},c.ByteBuffer.prototype.getSignedInt=function(a){var b=this.getInt(a),c=2<<a-2;return b>=c&&(b-=c<<1),b},c.ByteBuffer.prototype.getBytes=function(a){var b;return a?(a=Math.min(this.length(),a),b=this.data.slice(this.read,this.read+a),this.read+=a):0===a?b="":(b=0===this.read?this.data:this.data.slice(this.read),this.clear()),b},c.ByteBuffer.prototype.bytes=function(a){return"undefined"==typeof a?this.data.slice(this.read):this.data.slice(this.read,this.read+a)},c.ByteBuffer.prototype.at=function(a){return this.data.charCodeAt(this.read+a)},c.ByteBuffer.prototype.setAt=function(a,b){return this.data=this.data.substr(0,this.read+a)+String.fromCharCode(b)+this.data.substr(this.read+a+1),this},c.ByteBuffer.prototype.last=function(){return this.data.charCodeAt(this.data.length-1)},c.ByteBuffer.prototype.copy=function(){var a=c.createBuffer(this.data);return a.read=this.read,a},c.ByteBuffer.prototype.compact=function(){return this.read>0&&(this.data=this.data.slice(this.read),this.read=0),this},c.ByteBuffer.prototype.clear=function(){return this.data="",this.read=0,this},c.ByteBuffer.prototype.truncate=function(a){var b=Math.max(0,this.length()-a);return this.data=this.data.substr(this.read,b),this.read=0,this},c.ByteBuffer.prototype.toHex=function(){for(var a="",b=this.read;b<this.data.length;++b){var c=this.data.charCodeAt(b);16>c&&(a+="0"),a+=c.toString(16)}return a},c.ByteBuffer.prototype.toString=function(){return c.decodeUtf8(this.bytes())},c.createBuffer=function(a,b){return b=b||"raw",void 0!==a&&"utf8"===b&&(a=c.encodeUtf8(a)),new c.ByteBuffer(a)},c.fillString=function(a,b){for(var c="";b>0;)1&b&&(c+=a),b>>>=1,b>0&&(a+=a);return c},c.xorBytes=function(a,b,c){for(var d="",e="",f="",g=0,h=0;c>0;--c,++g)e=a.charCodeAt(g)^b.charCodeAt(g),h>=10&&(d+=f,f="",h=0),f+=String.fromCharCode(e),++h;return d+=f},c.hexToBytes=function(a){var b="",c=0;for(a.length&!0&&(c=1,b+=String.fromCharCode(parseInt(a[0],16)));c<a.length;c+=2)b+=String.fromCharCode(parseInt(a.substr(c,2),16));return b},c.bytesToHex=function(a){return c.createBuffer(a).toHex()},c.int32ToBytes=function(a){return String.fromCharCode(a>>24&255)+String.fromCharCode(a>>16&255)+String.fromCharCode(a>>8&255)+String.fromCharCode(255&a)};var e="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",f=[62,-1,-1,-1,63,52,53,54,55,56,57,58,59,60,61,-1,-1,-1,64,-1,-1,-1,0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,-1,-1,-1,-1,-1,-1,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51];c.encode64=function(a,b){for(var c,d,f,g="",h="",i=0;i<a.length;)c=a.charCodeAt(i++),d=a.charCodeAt(i++),f=a.charCodeAt(i++),g+=e.charAt(c>>2),g+=e.charAt((3&c)<<4|d>>4),isNaN(d)?g+="==":(g+=e.charAt((15&d)<<2|f>>6),g+=isNaN(f)?"=":e.charAt(63&f)),b&&g.length>b&&(h+=g.substr(0,b)+"\r\n",g=g.substr(b));return h+=g},c.decode64=function(a){a=a.replace(/[^A-Za-z0-9\+\/\=]/g,"");for(var b,c,d,e,g="",h=0;h<a.length;)b=f[a.charCodeAt(h++)-43],c=f[a.charCodeAt(h++)-43],d=f[a.charCodeAt(h++)-43],e=f[a.charCodeAt(h++)-43],g+=String.fromCharCode(b<<2|c>>4),64!==d&&(g+=String.fromCharCode((15&c)<<4|d>>2),64!==e&&(g+=String.fromCharCode((3&d)<<6|e)));return g},c.encodeUtf8=function(a){return unescape(encodeURIComponent(a))},c.decodeUtf8=function(a){return decodeURIComponent(escape(a))}},{}],15:[function(a,b){var c=a("./sha.js"),d=a("./forge_sha256.js");b.exports={md5:a("./md5.js"),sha1:c.sha1,sha224:c.sha224,sha256:c.sha256,sha384:c.sha384,sha512:c.sha512,ripemd:a("./ripe-md.js"),digest:function(a,b){switch(a){case 1:return this.md5(b);case 2:return this.sha1(b);case 3:return this.ripemd(b);case 8:var c=d.create();return c.update(b),c.digest().getBytes();case 9:return this.sha384(b);case 10:return this.sha512(b);case 11:return this.sha224(b);default:throw new Error("Invalid hash function.")}},getHashByteLength:function(a){switch(a){case 1:return 16;case 2:case 3:return 20;case 8:return 32;case 9:return 48;case 10:return 64;case 11:return 28;default:throw new Error("Invalid hash algorithm.")}}}},{"./forge_sha256.js":13,"./md5.js":16,"./ripe-md.js":17,"./sha.js":18}],16:[function(a,b){function c(a,b){var c=a[0],d=a[1],i=a[2],j=a[3];c=e(c,d,i,j,b[0],7,-680876936),j=e(j,c,d,i,b[1],12,-389564586),i=e(i,j,c,d,b[2],17,606105819),d=e(d,i,j,c,b[3],22,-1044525330),c=e(c,d,i,j,b[4],7,-176418897),j=e(j,c,d,i,b[5],12,1200080426),i=e(i,j,c,d,b[6],17,-1473231341),d=e(d,i,j,c,b[7],22,-45705983),c=e(c,d,i,j,b[8],7,1770035416),j=e(j,c,d,i,b[9],12,-1958414417),i=e(i,j,c,d,b[10],17,-42063),d=e(d,i,j,c,b[11],22,-1990404162),c=e(c,d,i,j,b[12],7,1804603682),j=e(j,c,d,i,b[13],12,-40341101),i=e(i,j,c,d,b[14],17,-1502002290),d=e(d,i,j,c,b[15],22,1236535329),c=f(c,d,i,j,b[1],5,-165796510),j=f(j,c,d,i,b[6],9,-1069501632),i=f(i,j,c,d,b[11],14,643717713),d=f(d,i,j,c,b[0],20,-373897302),c=f(c,d,i,j,b[5],5,-701558691),j=f(j,c,d,i,b[10],9,38016083),i=f(i,j,c,d,b[15],14,-660478335),d=f(d,i,j,c,b[4],20,-405537848),c=f(c,d,i,j,b[9],5,568446438),j=f(j,c,d,i,b[14],9,-1019803690),i=f(i,j,c,d,b[3],14,-187363961),d=f(d,i,j,c,b[8],20,1163531501),c=f(c,d,i,j,b[13],5,-1444681467),j=f(j,c,d,i,b[2],9,-51403784),i=f(i,j,c,d,b[7],14,1735328473),d=f(d,i,j,c,b[12],20,-1926607734),c=g(c,d,i,j,b[5],4,-378558),j=g(j,c,d,i,b[8],11,-2022574463),i=g(i,j,c,d,b[11],16,1839030562),d=g(d,i,j,c,b[14],23,-35309556),c=g(c,d,i,j,b[1],4,-1530992060),j=g(j,c,d,i,b[4],11,1272893353),i=g(i,j,c,d,b[7],16,-155497632),d=g(d,i,j,c,b[10],23,-1094730640),c=g(c,d,i,j,b[13],4,681279174),j=g(j,c,d,i,b[0],11,-358537222),i=g(i,j,c,d,b[3],16,-722521979),d=g(d,i,j,c,b[6],23,76029189),c=g(c,d,i,j,b[9],4,-640364487),j=g(j,c,d,i,b[12],11,-421815835),i=g(i,j,c,d,b[15],16,530742520),d=g(d,i,j,c,b[2],23,-995338651),c=h(c,d,i,j,b[0],6,-198630844),j=h(j,c,d,i,b[7],10,1126891415),i=h(i,j,c,d,b[14],15,-1416354905),d=h(d,i,j,c,b[5],21,-57434055),c=h(c,d,i,j,b[12],6,1700485571),j=h(j,c,d,i,b[3],10,-1894986606),i=h(i,j,c,d,b[10],15,-1051523),d=h(d,i,j,c,b[1],21,-2054922799),c=h(c,d,i,j,b[8],6,1873313359),j=h(j,c,d,i,b[15],10,-30611744),i=h(i,j,c,d,b[6],15,-1560198380),d=h(d,i,j,c,b[13],21,1309151649),c=h(c,d,i,j,b[4],6,-145523070),j=h(j,c,d,i,b[11],10,-1120210379),i=h(i,j,c,d,b[2],15,718787259),d=h(d,i,j,c,b[9],21,-343485551),a[0]=n(c,a[0]),a[1]=n(d,a[1]),a[2]=n(i,a[2]),a[3]=n(j,a[3])}function d(a,b,c,d,e,f){return b=n(n(b,a),n(d,f)),n(b<<e|b>>>32-e,c)}function e(a,b,c,e,f,g,h){return d(b&c|~b&e,a,b,f,g,h)}function f(a,b,c,e,f,g,h){return d(b&e|c&~e,a,b,f,g,h)}function g(a,b,c,e,f,g,h){return d(b^c^e,a,b,f,g,h)}function h(a,b,c,e,f,g,h){return d(c^(b|~e),a,b,f,g,h)}function i(a){txt="";var b,d=a.length,e=[1732584193,-271733879,-1732584194,271733878];for(b=64;b<=a.length;b+=64)c(e,j(a.substring(b-64,b)));a=a.substring(b-64);var f=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];for(b=0;b<a.length;b++)f[b>>2]|=a.charCodeAt(b)<<(b%4<<3);if(f[b>>2]|=128<<(b%4<<3),b>55)for(c(e,f),b=0;16>b;b++)f[b]=0;return f[14]=8*d,c(e,f),e}function j(a){var b,c=[];for(b=0;64>b;b+=4)c[b>>2]=a.charCodeAt(b)+(a.charCodeAt(b+1)<<8)+(a.charCodeAt(b+2)<<16)+(a.charCodeAt(b+3)<<24);return c}function k(a){for(var b="",c=0;4>c;c++)b+=p[a>>8*c+4&15]+p[a>>8*c&15];return b}function l(a){for(var b=0;b<a.length;b++)a[b]=k(a[b]);return a.join("")}function m(a){return l(i(a))}function n(a,b){return a+b&4294967295}function n(a,b){var c=(65535&a)+(65535&b),d=(a>>16)+(b>>16)+(c>>16);return d<<16|65535&c}var o=a("../../util.js");b.exports=function(a){var b=m(a),c=o.hex2bin(b);return c};var p="0123456789abcdef".split("");"5d41402abc4b2a76b9719d911017c592"!=m("hello")},{"../../util.js":61}],17:[function(a,b){function c(a,b){return new Number(a<<b|a>>>32-b)}function d(a,b,c){return new Number(a^b^c)}function e(a,b,c){return new Number(a&b|~a&c)}function f(a,b,c){return new Number((a|~b)^c)}function g(a,b,c){return new Number(a&c|b&~c)}function h(a,b,c){return new Number(a^(b|~c))}function i(a,b,i,j,k,l,m,n){switch(n){case 0:a+=d(b,i,j)+l+0;break;case 1:a+=e(b,i,j)+l+1518500249;break;case 2:a+=f(b,i,j)+l+1859775393;break;case 3:a+=g(b,i,j)+l+2400959708;break;case 4:a+=h(b,i,j)+l+2840853838;break;case 5:a+=h(b,i,j)+l+1352829926;break;case 6:a+=g(b,i,j)+l+1548603684;break;case 7:a+=f(b,i,j)+l+1836072691;break;case 8:a+=e(b,i,j)+l+2053994217;break;case 9:a+=d(b,i,j)+l+0;break;default:throw new Error("Bogus round number")}a=c(a,m)+k,i=c(i,10),a&=4294967295,b&=4294967295,i&=4294967295,j&=4294967295,k&=4294967295;var o=[];return o[0]=a,o[1]=b,o[2]=i,o[3]=j,o[4]=k,o[5]=l,o[6]=m,o}function j(a){a[0]=1732584193,a[1]=4023233417,a[2]=2562383102,a[3]=271733878,a[4]=3285377520}function k(a,b){blockA=[],blockB=[];var c,d,e;for(d=0;5>d;d++)blockA[d]=new Number(a[d]),blockB[d]=new Number(a[d]);var f=0;for(e=0;5>e;e++)for(d=0;16>d;d++)c=i(blockA[(f+0)%5],blockA[(f+1)%5],blockA[(f+2)%5],blockA[(f+3)%5],blockA[(f+4)%5],b[s[e][d]],r[e][d],e),blockA[(f+0)%5]=c[0],blockA[(f+1)%5]=c[1],blockA[(f+2)%5]=c[2],blockA[(f+3)%5]=c[3],blockA[(f+4)%5]=c[4],f+=4;for(f=0,e=5;10>e;e++)for(d=0;16>d;d++)c=i(blockB[(f+0)%5],blockB[(f+1)%5],blockB[(f+2)%5],blockB[(f+3)%5],blockB[(f+4)%5],b[s[e][d]],r[e][d],e),blockB[(f+0)%5]=c[0],blockB[(f+1)%5]=c[1],blockB[(f+2)%5]=c[2],blockB[(f+3)%5]=c[3],blockB[(f+4)%5]=c[4],f+=4;blockB[3]+=blockA[2]+a[1],a[1]=a[2]+blockA[3]+blockB[4],a[2]=a[3]+blockA[4]+blockB[0],a[3]=a[4]+blockA[0]+blockB[1],a[4]=a[0]+blockA[1]+blockB[2],a[0]=blockB[3]}function l(a){for(var b=0;16>b;b++)a[b]=0}function m(a,b,c,d){var e=new Array(16);l(e);for(var f=0,g=0;(63&c)>g;g++)e[g>>>2]^=(255&b.charCodeAt(f++))<<8*(3&g);e[c>>>2&15]^=1<<8*(3&c)+7,(63&c)>55&&(k(a,e),e=new Array(16),l(e)),e[14]=c<<3,e[15]=c>>>29|d<<3,k(a,e)}function n(a){var b=(255&a.charCodeAt(3))<<24;return b|=(255&a.charCodeAt(2))<<16,b|=(255&a.charCodeAt(1))<<8,b|=255&a.charCodeAt(0)}function o(a){var b,c,d=new Array(q/32),e=new Array(q/8);j(d),b=a.length;var f=new Array(16);l(f);var g,h=0;for(c=b;c>63;c-=64){for(g=0;16>g;g++)f[g]=n(a.substr(h,4)),h+=4;k(d,f)}for(m(d,a.substr(h),b,0),g=0;q/8>g;g+=4)e[g]=255&d[g>>>2],e[g+1]=d[g>>>2]>>>8&255,e[g+2]=d[g>>>2]>>>16&255,e[g+3]=d[g>>>2]>>>24&255;return e}function p(a){for(var b=o(a),c="",d=0;q/8>d;d++)c+=String.fromCharCode(b[d]);return c}var q=160,r=[[11,14,15,12,5,8,7,9,11,13,14,15,6,7,9,8],[7,6,8,13,11,9,7,15,7,12,15,9,11,7,13,12],[11,13,6,7,14,9,13,15,14,8,13,6,5,12,7,5],[11,12,14,15,14,15,9,8,9,14,5,6,8,6,5,12],[9,15,5,11,6,8,13,12,5,12,13,14,11,8,5,6],[8,9,9,11,13,15,15,5,7,7,8,11,14,14,12,6],[9,13,15,7,12,8,9,11,7,7,12,7,6,15,13,11],[9,7,15,11,8,6,6,14,12,13,5,14,13,13,7,5],[15,5,8,11,14,14,6,14,6,9,12,9,12,5,15,8],[8,5,12,9,12,5,14,6,8,13,6,5,15,13,11,11]],s=[[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15],[7,4,13,1,10,6,15,3,12,0,9,5,2,14,11,8],[3,10,14,4,9,15,8,1,2,7,0,6,13,11,5,12],[1,9,11,10,0,8,12,4,13,3,7,15,14,5,6,2],[4,0,5,9,7,12,2,10,14,1,3,8,11,6,15,13],[5,14,7,0,9,2,11,4,13,6,15,8,1,10,3,12],[6,11,3,7,0,13,5,10,14,15,8,12,4,9,1,2],[15,5,1,3,7,14,6,9,11,8,12,2,10,0,4,13],[8,6,4,1,3,11,15,0,5,12,2,13,9,7,10,14],[12,15,10,4,1,5,8,7,6,2,13,14,0,3,9,11]];b.exports=p},{}],18:[function(a,b){var c=function(){var a=8,b="",c=0,d=function(a,b){this.highOrder=a,this.lowOrder=b},e=function(b){var c,d=[],e=(1<<a)-1,f=b.length*a;for(c=0;f>c;c+=a)d[c>>5]|=(b.charCodeAt(c/a)&e)<<32-a-c%32;return d},f=function(a){var b,c,d=[],e=a.length;for(b=0;e>b;b+=2){if(c=parseInt(a.substr(b,2),16),isNaN(c))throw new Error("INVALID HEX STRING");d[b>>3]|=c<<24-4*(b%8)}return d},g=function(a){var b,d,e=c?"0123456789ABCDEF":"0123456789abcdef",f="",g=4*a.length;for(b=0;g>b;b+=1)d=a[b>>2]>>8*(3-b%4),f+=e.charAt(d>>4&15)+e.charAt(15&d);return f},h=function(a){var c,d,e,f="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",g="",h=4*a.length;for(c=0;h>c;c+=3)for(e=(a[c>>2]>>8*(3-c%4)&255)<<16|(a[c+1>>2]>>8*(3-(c+1)%4)&255)<<8|a[c+2>>2]>>8*(3-(c+2)%4)&255,d=0;4>d;d+=1)g+=8*c+6*d<=32*a.length?f.charAt(e>>6*(3-d)&63):b;return g},i=function(a){for(var b="",c=255,d=0;d<32*a.length;d+=8)b+=String.fromCharCode(a[d>>5]>>>24-d%32&c);return b},j=function(a,b){return a<<b|a>>>32-b},k=function(a,b){return a>>>b|a<<32-b},l=function(a,b){return 32>=b?new d(a.highOrder>>>b|a.lowOrder<<32-b,a.lowOrder>>>b|a.highOrder<<32-b):new d(a.lowOrder>>>b|a.highOrder<<32-b,a.highOrder>>>b|a.lowOrder<<32-b)},m=function(a,b){return a>>>b},n=function(a,b){return 32>=b?new d(a.highOrder>>>b,a.lowOrder>>>b|a.highOrder<<32-b):new d(0,a.highOrder<<32-b)},o=function(a,b,c){return a^b^c},p=function(a,b,c){return a&b^~a&c},q=function(a,b,c){return new d(a.highOrder&b.highOrder^~a.highOrder&c.highOrder,a.lowOrder&b.lowOrder^~a.lowOrder&c.lowOrder)},r=function(a,b,c){return a&b^a&c^b&c},s=function(a,b,c){return new d(a.highOrder&b.highOrder^a.highOrder&c.highOrder^b.highOrder&c.highOrder,a.lowOrder&b.lowOrder^a.lowOrder&c.lowOrder^b.lowOrder&c.lowOrder)},t=function(a){return k(a,2)^k(a,13)^k(a,22)},u=function(a){var b=l(a,28),c=l(a,34),e=l(a,39);return new d(b.highOrder^c.highOrder^e.highOrder,b.lowOrder^c.lowOrder^e.lowOrder)},v=function(a){return k(a,6)^k(a,11)^k(a,25)},w=function(a){var b=l(a,14),c=l(a,18),e=l(a,41);return new d(b.highOrder^c.highOrder^e.highOrder,b.lowOrder^c.lowOrder^e.lowOrder)},x=function(a){return k(a,7)^k(a,18)^m(a,3)},y=function(a){var b=l(a,1),c=l(a,8),e=n(a,7);return new d(b.highOrder^c.highOrder^e.highOrder,b.lowOrder^c.lowOrder^e.lowOrder)},z=function(a){return k(a,17)^k(a,19)^m(a,10)},A=function(a){var b=l(a,19),c=l(a,61),e=n(a,6);return new d(b.highOrder^c.highOrder^e.highOrder,b.lowOrder^c.lowOrder^e.lowOrder)},B=function(a,b){var c=(65535&a)+(65535&b),d=(a>>>16)+(b>>>16)+(c>>>16);
return(65535&d)<<16|65535&c},C=function(a,b,c,d){var e=(65535&a)+(65535&b)+(65535&c)+(65535&d),f=(a>>>16)+(b>>>16)+(c>>>16)+(d>>>16)+(e>>>16);return(65535&f)<<16|65535&e},D=function(a,b,c,d,e){var f=(65535&a)+(65535&b)+(65535&c)+(65535&d)+(65535&e),g=(a>>>16)+(b>>>16)+(c>>>16)+(d>>>16)+(e>>>16)+(f>>>16);return(65535&g)<<16|65535&f},E=function(a,b){var c,e,f,g;return c=(65535&a.lowOrder)+(65535&b.lowOrder),e=(a.lowOrder>>>16)+(b.lowOrder>>>16)+(c>>>16),f=(65535&e)<<16|65535&c,c=(65535&a.highOrder)+(65535&b.highOrder)+(e>>>16),e=(a.highOrder>>>16)+(b.highOrder>>>16)+(c>>>16),g=(65535&e)<<16|65535&c,new d(g,f)},F=function(a,b,c,e){var f,g,h,i;return f=(65535&a.lowOrder)+(65535&b.lowOrder)+(65535&c.lowOrder)+(65535&e.lowOrder),g=(a.lowOrder>>>16)+(b.lowOrder>>>16)+(c.lowOrder>>>16)+(e.lowOrder>>>16)+(f>>>16),h=(65535&g)<<16|65535&f,f=(65535&a.highOrder)+(65535&b.highOrder)+(65535&c.highOrder)+(65535&e.highOrder)+(g>>>16),g=(a.highOrder>>>16)+(b.highOrder>>>16)+(c.highOrder>>>16)+(e.highOrder>>>16)+(f>>>16),i=(65535&g)<<16|65535&f,new d(i,h)},G=function(a,b,c,e,f){var g,h,i,j;return g=(65535&a.lowOrder)+(65535&b.lowOrder)+(65535&c.lowOrder)+(65535&e.lowOrder)+(65535&f.lowOrder),h=(a.lowOrder>>>16)+(b.lowOrder>>>16)+(c.lowOrder>>>16)+(e.lowOrder>>>16)+(f.lowOrder>>>16)+(g>>>16),i=(65535&h)<<16|65535&g,g=(65535&a.highOrder)+(65535&b.highOrder)+(65535&c.highOrder)+(65535&e.highOrder)+(65535&f.highOrder)+(h>>>16),h=(a.highOrder>>>16)+(b.highOrder>>>16)+(c.highOrder>>>16)+(e.highOrder>>>16)+(f.highOrder>>>16)+(g>>>16),j=(65535&h)<<16|65535&g,new d(j,i)},H=function(a,b){var c,d,e,f,g,h,i,k,l,m=[],n=p,q=o,s=r,t=j,u=B,v=D,w=[1732584193,4023233417,2562383102,271733878,3285377520],x=[1518500249,1518500249,1518500249,1518500249,1518500249,1518500249,1518500249,1518500249,1518500249,1518500249,1518500249,1518500249,1518500249,1518500249,1518500249,1518500249,1518500249,1518500249,1518500249,1518500249,1859775393,1859775393,1859775393,1859775393,1859775393,1859775393,1859775393,1859775393,1859775393,1859775393,1859775393,1859775393,1859775393,1859775393,1859775393,1859775393,1859775393,1859775393,1859775393,1859775393,2400959708,2400959708,2400959708,2400959708,2400959708,2400959708,2400959708,2400959708,2400959708,2400959708,2400959708,2400959708,2400959708,2400959708,2400959708,2400959708,2400959708,2400959708,2400959708,2400959708,3395469782,3395469782,3395469782,3395469782,3395469782,3395469782,3395469782,3395469782,3395469782,3395469782,3395469782,3395469782,3395469782,3395469782,3395469782,3395469782,3395469782,3395469782,3395469782,3395469782];for(a[b>>5]|=128<<24-b%32,a[(b+65>>9<<4)+15]=b,l=a.length,i=0;l>i;i+=16){for(c=w[0],d=w[1],e=w[2],f=w[3],g=w[4],k=0;80>k;k+=1)m[k]=16>k?a[k+i]:t(m[k-3]^m[k-8]^m[k-14]^m[k-16],1),h=20>k?v(t(c,5),n(d,e,f),g,x[k],m[k]):40>k?v(t(c,5),q(d,e,f),g,x[k],m[k]):60>k?v(t(c,5),s(d,e,f),g,x[k],m[k]):v(t(c,5),q(d,e,f),g,x[k],m[k]),g=f,f=e,e=t(d,30),d=c,c=h;w[0]=u(c,w[0]),w[1]=u(d,w[1]),w[2]=u(e,w[2]),w[3]=u(f,w[3]),w[4]=u(g,w[4])}return w},I=function(a,b,c){var e,f,g,h,i,j,k,l,m,n,o,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z=[];for("SHA-224"===c||"SHA-256"===c?(H=64,I=(b+65>>9<<4)+15,L=16,M=1,W=Number,N=B,O=C,P=D,Q=x,R=z,S=t,T=v,V=r,U=p,X=[1116352408,1899447441,3049323471,3921009573,961987163,1508970993,2453635748,2870763221,3624381080,310598401,607225278,1426881987,1925078388,2162078206,2614888103,3248222580,3835390401,4022224774,264347078,604807628,770255983,1249150122,1555081692,1996064986,2554220882,2821834349,2952996808,3210313671,3336571891,3584528711,113926993,338241895,666307205,773529912,1294757372,1396182291,1695183700,1986661051,2177026350,2456956037,2730485921,2820302411,3259730800,3345764771,3516065817,3600352804,4094571909,275423344,430227734,506948616,659060556,883997877,958139571,1322822218,1537002063,1747873779,1955562222,2024104815,2227730452,2361852424,2428436474,2756734187,3204031479,3329325298],o="SHA-224"===c?[3238371032,914150663,812702999,4144912697,4290775857,1750603025,1694076839,3204075428]:[1779033703,3144134277,1013904242,2773480762,1359893119,2600822924,528734635,1541459225]):("SHA-384"===c||"SHA-512"===c)&&(H=80,I=(b+128>>10<<5)+31,L=32,M=2,W=d,N=E,O=F,P=G,Q=y,R=A,S=u,T=w,V=s,U=q,X=[new W(1116352408,3609767458),new W(1899447441,602891725),new W(3049323471,3964484399),new W(3921009573,2173295548),new W(961987163,4081628472),new W(1508970993,3053834265),new W(2453635748,2937671579),new W(2870763221,3664609560),new W(3624381080,2734883394),new W(310598401,1164996542),new W(607225278,1323610764),new W(1426881987,3590304994),new W(1925078388,4068182383),new W(2162078206,991336113),new W(2614888103,633803317),new W(3248222580,3479774868),new W(3835390401,2666613458),new W(4022224774,944711139),new W(264347078,2341262773),new W(604807628,2007800933),new W(770255983,1495990901),new W(1249150122,1856431235),new W(1555081692,3175218132),new W(1996064986,2198950837),new W(2554220882,3999719339),new W(2821834349,766784016),new W(2952996808,2566594879),new W(3210313671,3203337956),new W(3336571891,1034457026),new W(3584528711,2466948901),new W(113926993,3758326383),new W(338241895,168717936),new W(666307205,1188179964),new W(773529912,1546045734),new W(1294757372,1522805485),new W(1396182291,2643833823),new W(1695183700,2343527390),new W(1986661051,1014477480),new W(2177026350,1206759142),new W(2456956037,344077627),new W(2730485921,1290863460),new W(2820302411,3158454273),new W(3259730800,3505952657),new W(3345764771,106217008),new W(3516065817,3606008344),new W(3600352804,1432725776),new W(4094571909,1467031594),new W(275423344,851169720),new W(430227734,3100823752),new W(506948616,1363258195),new W(659060556,3750685593),new W(883997877,3785050280),new W(958139571,3318307427),new W(1322822218,3812723403),new W(1537002063,2003034995),new W(1747873779,3602036899),new W(1955562222,1575990012),new W(2024104815,1125592928),new W(2227730452,2716904306),new W(2361852424,442776044),new W(2428436474,593698344),new W(2756734187,3733110249),new W(3204031479,2999351573),new W(3329325298,3815920427),new W(3391569614,3928383900),new W(3515267271,566280711),new W(3940187606,3454069534),new W(4118630271,4000239992),new W(116418474,1914138554),new W(174292421,2731055270),new W(289380356,3203993006),new W(460393269,320620315),new W(685471733,587496836),new W(852142971,1086792851),new W(1017036298,365543100),new W(1126000580,2618297676),new W(1288033470,3409855158),new W(1501505948,4234509866),new W(1607167915,987167468),new W(1816402316,1246189591)],o="SHA-384"===c?[new W(3418070365,3238371032),new W(1654270250,914150663),new W(2438529370,812702999),new W(355462360,4144912697),new W(1731405415,4290775857),new W(41048885895,1750603025),new W(3675008525,1694076839),new W(1203062813,3204075428)]:[new W(1779033703,4089235720),new W(3144134277,2227873595),new W(1013904242,4271175723),new W(2773480762,1595750129),new W(1359893119,2917565137),new W(2600822924,725511199),new W(528734635,4215389547),new W(1541459225,327033209)]),a[b>>5]|=128<<24-b%32,a[I]=b,Y=a.length,J=0;Y>J;J+=L){for(e=o[0],f=o[1],g=o[2],h=o[3],i=o[4],j=o[5],k=o[6],l=o[7],K=0;H>K;K+=1)Z[K]=16>K?new W(a[K*M+J],a[K*M+J+1]):O(R(Z[K-2]),Z[K-7],Q(Z[K-15]),Z[K-16]),m=P(l,T(i),U(i,j,k),X[K],Z[K]),n=N(S(e),V(e,f,g)),l=k,k=j,j=i,i=N(h,m),h=g,g=f,f=e,e=N(m,n);o[0]=N(e,o[0]),o[1]=N(f,o[1]),o[2]=N(g,o[2]),o[3]=N(h,o[3]),o[4]=N(i,o[4]),o[5]=N(j,o[5]),o[6]=N(k,o[6]),o[7]=N(l,o[7])}switch(c){case"SHA-224":return[o[0],o[1],o[2],o[3],o[4],o[5],o[6]];case"SHA-256":return o;case"SHA-384":return[o[0].highOrder,o[0].lowOrder,o[1].highOrder,o[1].lowOrder,o[2].highOrder,o[2].lowOrder,o[3].highOrder,o[3].lowOrder,o[4].highOrder,o[4].lowOrder,o[5].highOrder,o[5].lowOrder];case"SHA-512":return[o[0].highOrder,o[0].lowOrder,o[1].highOrder,o[1].lowOrder,o[2].highOrder,o[2].lowOrder,o[3].highOrder,o[3].lowOrder,o[4].highOrder,o[4].lowOrder,o[5].highOrder,o[5].lowOrder,o[6].highOrder,o[6].lowOrder,o[7].highOrder,o[7].lowOrder];default:throw new Error("Unknown SHA variant")}},J=function(b,c){if(this.sha1=null,this.sha224=null,this.sha256=null,this.sha384=null,this.sha512=null,this.strBinLen=null,this.strToHash=null,"HEX"===c){if(0!==b.length%2)throw new Error("TEXT MUST BE IN BYTE INCREMENTS");this.strBinLen=4*b.length,this.strToHash=f(b)}else{if("ASCII"!==c&&"undefined"!=typeof c)throw new Error("UNKNOWN TEXT INPUT TYPE");this.strBinLen=b.length*a,this.strToHash=e(b)}};return J.prototype={getHash:function(a,b){var c=null,d=this.strToHash.slice();switch(b){case"HEX":c=g;break;case"B64":c=h;break;case"ASCII":c=i;break;default:throw new Error("FORMAT NOT RECOGNIZED")}switch(a){case"SHA-1":return null===this.sha1&&(this.sha1=H(d,this.strBinLen)),c(this.sha1);case"SHA-224":return null===this.sha224&&(this.sha224=I(d,this.strBinLen,a)),c(this.sha224);case"SHA-256":return null===this.sha256&&(this.sha256=I(d,this.strBinLen,a)),c(this.sha256);case"SHA-384":return null===this.sha384&&(this.sha384=I(d,this.strBinLen,a)),c(this.sha384);case"SHA-512":return null===this.sha512&&(this.sha512=I(d,this.strBinLen,a)),c(this.sha512);default:throw new Error("HASH NOT RECOGNIZED")}},getHMAC:function(b,c,d,j){var k,l,m,n,o,p,q,r,s,t=[],u=[];switch(j){case"HEX":k=g;break;case"B64":k=h;break;case"ASCII":k=i;break;default:throw new Error("FORMAT NOT RECOGNIZED")}switch(d){case"SHA-1":m=64,s=160;break;case"SHA-224":m=64,s=224;break;case"SHA-256":m=64,s=256;break;case"SHA-384":m=128,s=384;break;case"SHA-512":m=128,s=512;break;default:throw new Error("HASH NOT RECOGNIZED")}if("HEX"===c){if(0!==b.length%2)throw new Error("KEY MUST BE IN BYTE INCREMENTS");l=f(b),r=4*b.length}else{if("ASCII"!==c)throw new Error("UNKNOWN KEY INPUT TYPE");l=e(b),r=b.length*a}for(n=8*m,q=m/4-1,r/8>m?(l="SHA-1"===d?H(l,r):I(l,r,d),l[q]&=4294967040):m>r/8&&(l[q]&=4294967040),o=0;q>=o;o+=1)t[o]=909522486^l[o],u[o]=1549556828^l[o];return"SHA-1"===d?(p=H(t.concat(this.strToHash),n+this.strBinLen),p=H(u.concat(p),n+s)):(p=I(t.concat(this.strToHash),n+this.strBinLen,d),p=I(u.concat(p),n+s,d)),k(p)}},J}();b.exports={sha1:function(a){var b=new c(a,"ASCII");return b.getHash("SHA-1","ASCII")},sha224:function(a){var b=new c(a,"ASCII");return b.getHash("SHA-224","ASCII")},sha256:function(a){var b=new c(a,"ASCII");return b.getHash("SHA-256","ASCII")},sha384:function(a){var b=new c(a,"ASCII");return b.getHash("SHA-384","ASCII")},sha512:function(a){var b=new c(a,"ASCII");return b.getHash("SHA-512","ASCII")}}},{}],19:[function(a,b){b.exports={cipher:a("./cipher"),hash:a("./hash"),cfb:a("./cfb.js"),publicKey:a("./public_key"),signature:a("./signature.js"),random:a("./random.js"),pkcs1:a("./pkcs1.js")};var c=a("./crypto.js");for(var d in c)b.exports[d]=c[d]},{"./cfb.js":5,"./cipher":10,"./crypto.js":12,"./hash":15,"./pkcs1.js":20,"./public_key":23,"./random.js":26,"./signature.js":27}],20:[function(a,b){function c(a){for(var b,c="";c.length<a;)b=e.getSecureRandomOctet(),0!==b&&(c+=String.fromCharCode(b));return c}var d=[];d[1]=[48,32,48,12,6,8,42,134,72,134,247,13,2,5,5,0,4,16],d[2]=[48,33,48,9,6,5,43,14,3,2,26,5,0,4,20],d[3]=[48,33,48,9,6,5,43,36,3,2,1,5,0,4,20],d[8]=[48,49,48,13,6,9,96,134,72,1,101,3,4,2,1,5,0,4,32],d[9]=[48,65,48,13,6,9,96,134,72,1,101,3,4,2,2,5,0,4,48],d[10]=[48,81,48,13,6,9,96,134,72,1,101,3,4,2,3,5,0,4,64],d[11]=[48,45,48,13,6,9,96,134,72,1,101,3,4,2,4,5,0,4,28];var e=(a("./crypto.js"),a("./random.js")),f=a("../util.js"),g=a("./public_key/jsbn.js"),h=a("./hash");b.exports={eme:{encode:function(a,b){var d=a.length;if(d>b-11)throw new Error("Message too long");var e=c(b-d-3),f=String.fromCharCode(0)+String.fromCharCode(2)+e+String.fromCharCode(0)+a;return f},decode:function(a){0!==a.charCodeAt(0)&&(a=String.fromCharCode(0)+a);for(var b=a.charCodeAt(0),c=a.charCodeAt(1),d=2;0!==a.charCodeAt(d)&&d<a.length;)d++;var e=d-2,f=a.charCodeAt(d++);if(0===b&&2===c&&e>=8&&0===f)return a.substr(d);throw new Error("Decryption error")}},emsa:{encode:function(a,b,c){var e,i=h.digest(a,b);if(i.length!==h.getHashByteLength(a))throw new Error("Invalid hash length");var j="";for(e=0;e<d[a].length;e++)j+=String.fromCharCode(d[a][e]);j+=i;var k=j.length;if(k+11>c)throw new Error("Intended encoded message length too short");var l="";for(e=0;c-k-3>e;e++)l+=String.fromCharCode(255);var m=String.fromCharCode(0)+String.fromCharCode(1)+l+String.fromCharCode(0)+j;return new g(f.hexstrdump(m),16)}}}},{"../util.js":61,"./crypto.js":12,"./hash":15,"./public_key/jsbn.js":24,"./random.js":26}],21:[function(a,b){function c(){function a(a,b,c,h,i,j){for(var k,l,m,n=g.getLeftNBits(f.digest(a,b),i.bitLength()),o=new d(g.hexstrdump(n),16);;)if(k=e.getRandomBigIntegerInRange(d.ONE,i.subtract(d.ONE)),l=c.modPow(k,h).mod(i),m=k.modInverse(i).multiply(o.add(j.multiply(l))).mod(i),0!=l&&0!=m)break;var p=[];return p[0]=l.toMPI(),p[1]=m.toMPI(),p}function b(a){var b=h.prefer_hash_algorithm;switch(Math.round(a.bitLength()/8)){case 20:return 2!=b&&b>11&&10!=b&&8>b?2:b;case 28:return b>11&&8>b?11:b;case 32:return b>10&&8>b?8:b;default:return g.print_debug("DSA select hash algorithm: returning null for an unknown length of q"),null}}function c(a,b,c,e,h,i,j,k){var l=g.getLeftNBits(f.digest(a,e),i.bitLength()),m=new d(g.hexstrdump(l),16);if(d.ZERO.compareTo(b)>0||b.compareTo(i)>0||d.ZERO.compareTo(c)>0||c.compareTo(i)>0)return g.print_debug("invalid DSA Signature"),null;var n=c.modInverse(i),o=m.multiply(n).mod(i),p=b.multiply(n).mod(i);return j.modPow(o,h).multiply(k.modPow(p,h)).mod(h).mod(i)}this.select_hash_algorithm=b,this.sign=a,this.verify=c}var d=a("./jsbn.js"),e=a("../random.js"),f=a("../hash"),g=a("../../util.js"),h=a("../../config");b.exports=c},{"../../config":4,"../../util.js":61,"../hash":15,"../random.js":26,"./jsbn.js":24}],22:[function(a,b){function c(){function a(a,b,c,f){var g=c.subtract(d.TWO),h=e.getRandomBigIntegerInRange(d.ONE,g);h=h.mod(g).add(d.ONE);var i=[];return i[0]=b.modPow(h,c),i[1]=f.modPow(h,c).multiply(a).mod(c),i}function b(a,b,c,d){return f.print_debug("Elgamal Decrypt:\nc1:"+f.hexstrdump(a.toMPI())+"\nc2:"+f.hexstrdump(b.toMPI())+"\np:"+f.hexstrdump(c.toMPI())+"\nx:"+f.hexstrdump(d.toMPI())),a.modPow(d,c).modInverse(c).multiply(b).mod(c)}this.encrypt=a,this.decrypt=b}var d=a("./jsbn.js"),e=a("../random.js"),f=a("../../util.js");b.exports=c},{"../../util.js":61,"../random.js":26,"./jsbn.js":24}],23:[function(a,b){b.exports={rsa:a("./rsa.js"),elgamal:a("./elgamal.js"),dsa:a("./dsa.js")}},{"./dsa.js":21,"./elgamal.js":22,"./rsa.js":25}],24:[function(a,b){function c(a,b,c){null!=a&&("number"==typeof a?this.fromNumber(a,b,c):null==b&&"string"!=typeof a?this.fromString(a,256):this.fromString(a,b))}function d(){return new c(null)}function e(a,b,c,d,e,f){for(;--f>=0;){var g=b*this[a++]+c[d]+e;e=Math.floor(g/67108864),c[d++]=67108863&g}return e}function f(a){return ec.charAt(a)}function g(a,b){var c=fc[a.charCodeAt(b)];return null==c?-1:c}function h(a){for(var b=this.t-1;b>=0;--b)a[b]=this[b];a.t=this.t,a.s=this.s}function i(a){this.t=1,this.s=0>a?-1:0,a>0?this[0]=a:-1>a?this[0]=a+this.DV:this.t=0}function j(a){var b=d();return b.fromInt(a),b}function k(a,b){var d;if(16==b)d=4;else if(8==b)d=3;else if(256==b)d=8;else if(2==b)d=1;else if(32==b)d=5;else{if(4!=b)return void this.fromRadix(a,b);d=2}this.t=0,this.s=0;for(var e=a.length,f=!1,h=0;--e>=0;){var i=8==d?255&a[e]:g(a,e);0>i?"-"==a.charAt(e)&&(f=!0):(f=!1,0==h?this[this.t++]=i:h+d>this.DB?(this[this.t-1]|=(i&(1<<this.DB-h)-1)<<h,this[this.t++]=i>>this.DB-h):this[this.t-1]|=i<<h,h+=d,h>=this.DB&&(h-=this.DB))}8==d&&0!=(128&a[0])&&(this.s=-1,h>0&&(this[this.t-1]|=(1<<this.DB-h)-1<<h)),this.clamp(),f&&c.ZERO.subTo(this,this)}function l(){for(var a=this.s&this.DM;this.t>0&&this[this.t-1]==a;)--this.t}function m(a){if(this.s<0)return"-"+this.negate().toString(a);var b;if(16==a)b=4;else if(8==a)b=3;else if(2==a)b=1;else if(32==a)b=5;else{if(4!=a)return this.toRadix(a);b=2}var c,d=(1<<b)-1,e=!1,g="",h=this.t,i=this.DB-h*this.DB%b;if(h-->0)for(i<this.DB&&(c=this[h]>>i)>0&&(e=!0,g=f(c));h>=0;)b>i?(c=(this[h]&(1<<i)-1)<<b-i,c|=this[--h]>>(i+=this.DB-b)):(c=this[h]>>(i-=b)&d,0>=i&&(i+=this.DB,--h)),c>0&&(e=!0),e&&(g+=f(c));return e?g:"0"}function n(){var a=d();return c.ZERO.subTo(this,a),a}function o(){return this.s<0?this.negate():this}function p(a){var b=this.s-a.s;if(0!=b)return b;var c=this.t;if(b=c-a.t,0!=b)return this.s<0?-b:b;for(;--c>=0;)if(0!=(b=this[c]-a[c]))return b;return 0}function q(a){var b,c=1;return 0!=(b=a>>>16)&&(a=b,c+=16),0!=(b=a>>8)&&(a=b,c+=8),0!=(b=a>>4)&&(a=b,c+=4),0!=(b=a>>2)&&(a=b,c+=2),0!=(b=a>>1)&&(a=b,c+=1),c}function r(){return this.t<=0?0:this.DB*(this.t-1)+q(this[this.t-1]^this.s&this.DM)}function s(a,b){var c;for(c=this.t-1;c>=0;--c)b[c+a]=this[c];for(c=a-1;c>=0;--c)b[c]=0;b.t=this.t+a,b.s=this.s}function t(a,b){for(var c=a;c<this.t;++c)b[c-a]=this[c];b.t=Math.max(this.t-a,0),b.s=this.s}function u(a,b){var c,d=a%this.DB,e=this.DB-d,f=(1<<e)-1,g=Math.floor(a/this.DB),h=this.s<<d&this.DM;for(c=this.t-1;c>=0;--c)b[c+g+1]=this[c]>>e|h,h=(this[c]&f)<<d;for(c=g-1;c>=0;--c)b[c]=0;b[g]=h,b.t=this.t+g+1,b.s=this.s,b.clamp()}function v(a,b){b.s=this.s;var c=Math.floor(a/this.DB);if(c>=this.t)return void(b.t=0);var d=a%this.DB,e=this.DB-d,f=(1<<d)-1;b[0]=this[c]>>d;for(var g=c+1;g<this.t;++g)b[g-c-1]|=(this[g]&f)<<e,b[g-c]=this[g]>>d;d>0&&(b[this.t-c-1]|=(this.s&f)<<e),b.t=this.t-c,b.clamp()}function w(a,b){for(var c=0,d=0,e=Math.min(a.t,this.t);e>c;)d+=this[c]-a[c],b[c++]=d&this.DM,d>>=this.DB;if(a.t<this.t){for(d-=a.s;c<this.t;)d+=this[c],b[c++]=d&this.DM,d>>=this.DB;d+=this.s}else{for(d+=this.s;c<a.t;)d-=a[c],b[c++]=d&this.DM,d>>=this.DB;d-=a.s}b.s=0>d?-1:0,-1>d?b[c++]=this.DV+d:d>0&&(b[c++]=d),b.t=c,b.clamp()}function x(a,b){var d=this.abs(),e=a.abs(),f=d.t;for(b.t=f+e.t;--f>=0;)b[f]=0;for(f=0;f<e.t;++f)b[f+d.t]=d.am(0,e[f],b,f,0,d.t);b.s=0,b.clamp(),this.s!=a.s&&c.ZERO.subTo(b,b)}function y(a){for(var b=this.abs(),c=a.t=2*b.t;--c>=0;)a[c]=0;for(c=0;c<b.t-1;++c){var d=b.am(c,b[c],a,2*c,0,1);(a[c+b.t]+=b.am(c+1,2*b[c],a,2*c+1,d,b.t-c-1))>=b.DV&&(a[c+b.t]-=b.DV,a[c+b.t+1]=1)}a.t>0&&(a[a.t-1]+=b.am(c,b[c],a,2*c,0,1)),a.s=0,a.clamp()}function z(a,b,e){var f=a.abs();if(!(f.t<=0)){var g=this.abs();if(g.t<f.t)return null!=b&&b.fromInt(0),void(null!=e&&this.copyTo(e));null==e&&(e=d());var h=d(),i=this.s,j=a.s,k=this.DB-q(f[f.t-1]);k>0?(f.lShiftTo(k,h),g.lShiftTo(k,e)):(f.copyTo(h),g.copyTo(e));var l=h.t,m=h[l-1];if(0!=m){var n=m*(1<<this.F1)+(l>1?h[l-2]>>this.F2:0),o=this.FV/n,p=(1<<this.F1)/n,r=1<<this.F2,s=e.t,t=s-l,u=null==b?d():b;for(h.dlShiftTo(t,u),e.compareTo(u)>=0&&(e[e.t++]=1,e.subTo(u,e)),c.ONE.dlShiftTo(l,u),u.subTo(h,h);h.t<l;)h[h.t++]=0;for(;--t>=0;){var v=e[--s]==m?this.DM:Math.floor(e[s]*o+(e[s-1]+r)*p);if((e[s]+=h.am(0,v,e,t,0,l))<v)for(h.dlShiftTo(t,u),e.subTo(u,e);e[s]<--v;)e.subTo(u,e)}null!=b&&(e.drShiftTo(l,b),i!=j&&c.ZERO.subTo(b,b)),e.t=l,e.clamp(),k>0&&e.rShiftTo(k,e),0>i&&c.ZERO.subTo(e,e)}}}function A(a){var b=d();return this.abs().divRemTo(a,null,b),this.s<0&&b.compareTo(c.ZERO)>0&&a.subTo(b,b),b}function B(a){this.m=a}function C(a){return a.s<0||a.compareTo(this.m)>=0?a.mod(this.m):a}function D(a){return a}function E(a){a.divRemTo(this.m,null,a)}function F(a,b,c){a.multiplyTo(b,c),this.reduce(c)}function G(a,b){a.squareTo(b),this.reduce(b)}function H(){if(this.t<1)return 0;var a=this[0];if(0==(1&a))return 0;var b=3&a;return b=b*(2-(15&a)*b)&15,b=b*(2-(255&a)*b)&255,b=b*(2-((65535&a)*b&65535))&65535,b=b*(2-a*b%this.DV)%this.DV,b>0?this.DV-b:-b}function I(a){this.m=a,this.mp=a.invDigit(),this.mpl=32767&this.mp,this.mph=this.mp>>15,this.um=(1<<a.DB-15)-1,this.mt2=2*a.t}function J(a){var b=d();return a.abs().dlShiftTo(this.m.t,b),b.divRemTo(this.m,null,b),a.s<0&&b.compareTo(c.ZERO)>0&&this.m.subTo(b,b),b}function K(a){var b=d();return a.copyTo(b),this.reduce(b),b}function L(a){for(;a.t<=this.mt2;)a[a.t++]=0;for(var b=0;b<this.m.t;++b){var c=32767&a[b],d=c*this.mpl+((c*this.mph+(a[b]>>15)*this.mpl&this.um)<<15)&a.DM;for(c=b+this.m.t,a[c]+=this.m.am(0,d,a,b,0,this.m.t);a[c]>=a.DV;)a[c]-=a.DV,a[++c]++}a.clamp(),a.drShiftTo(this.m.t,a),a.compareTo(this.m)>=0&&a.subTo(this.m,a)}function M(a,b){a.squareTo(b),this.reduce(b)}function N(a,b,c){a.multiplyTo(b,c),this.reduce(c)}function O(){return 0==(this.t>0?1&this[0]:this.s)}function P(a,b){if(a>4294967295||1>a)return c.ONE;var e=d(),f=d(),g=b.convert(this),h=q(a)-1;for(g.copyTo(e);--h>=0;)if(b.sqrTo(e,f),(a&1<<h)>0)b.mulTo(f,g,e);else{var i=e;e=f,f=i}return b.revert(e)}function Q(a,b){var c;return c=256>a||b.isEven()?new B(b):new I(b),this.exp(a,c)}function R(){var a=d();return this.copyTo(a),a}function S(){if(this.s<0){if(1==this.t)return this[0]-this.DV;if(0==this.t)return-1}else{if(1==this.t)return this[0];if(0==this.t)return 0}return(this[1]&(1<<32-this.DB)-1)<<this.DB|this[0]}function T(){return 0==this.t?this.s:this[0]<<24>>24}function U(){return 0==this.t?this.s:this[0]<<16>>16}function V(a){return Math.floor(Math.LN2*this.DB/Math.log(a))}function W(){return this.s<0?-1:this.t<=0||1==this.t&&this[0]<=0?0:1}function X(a){if(null==a&&(a=10),0==this.signum()||2>a||a>36)return"0";var b=this.chunkSize(a),c=Math.pow(a,b),e=j(c),f=d(),g=d(),h="";for(this.divRemTo(e,f,g);f.signum()>0;)h=(c+g.intValue()).toString(a).substr(1)+h,f.divRemTo(e,f,g);return g.intValue().toString(a)+h}function Y(a,b){this.fromInt(0),null==b&&(b=10);for(var d=this.chunkSize(b),e=Math.pow(b,d),f=!1,h=0,i=0,j=0;j<a.length;++j){var k=g(a,j);0>k?"-"==a.charAt(j)&&0==this.signum()&&(f=!0):(i=b*i+k,++h>=d&&(this.dMultiply(e),this.dAddOffset(i,0),h=0,i=0))}h>0&&(this.dMultiply(Math.pow(b,h)),this.dAddOffset(i,0)),f&&c.ZERO.subTo(this,this)}function Z(a,b,d){if("number"==typeof b)if(2>a)this.fromInt(1);else for(this.fromNumber(a,d),this.testBit(a-1)||this.bitwiseTo(c.ONE.shiftLeft(a-1),fb,this),this.isEven()&&this.dAddOffset(1,0);!this.isProbablePrime(b);)this.dAddOffset(2,0),this.bitLength()>a&&this.subTo(c.ONE.shiftLeft(a-1),this);else{var e=new Array,f=7&a;e.length=(a>>3)+1,b.nextBytes(e),f>0?e[0]&=(1<<f)-1:e[0]=0,this.fromString(e,256)}}function $(){var a=this.t,b=new Array;b[0]=this.s;var c,d=this.DB-a*this.DB%8,e=0;if(a-->0)for(d<this.DB&&(c=this[a]>>d)!=(this.s&this.DM)>>d&&(b[e++]=c|this.s<<this.DB-d);a>=0;)8>d?(c=(this[a]&(1<<d)-1)<<8-d,c|=this[--a]>>(d+=this.DB-8)):(c=this[a]>>(d-=8)&255,0>=d&&(d+=this.DB,--a)),(e>0||c!=this.s)&&(b[e++]=c);return b}function _(a){return 0==this.compareTo(a)}function ab(a){return this.compareTo(a)<0?this:a}function bb(a){return this.compareTo(a)>0?this:a}function cb(a,b,c){var d,e,f=Math.min(a.t,this.t);for(d=0;f>d;++d)c[d]=b(this[d],a[d]);if(a.t<this.t){for(e=a.s&this.DM,d=f;d<this.t;++d)c[d]=b(this[d],e);c.t=this.t}else{for(e=this.s&this.DM,d=f;d<a.t;++d)c[d]=b(e,a[d]);c.t=a.t}c.s=b(this.s,a.s),c.clamp()}function db(a,b){return a&b}function eb(a){var b=d();return this.bitwiseTo(a,db,b),b}function fb(a,b){return a|b}function gb(a){var b=d();return this.bitwiseTo(a,fb,b),b}function hb(a,b){return a^b}function ib(a){var b=d();return this.bitwiseTo(a,hb,b),b}function jb(a,b){return a&~b}function kb(a){var b=d();return this.bitwiseTo(a,jb,b),b}function lb(){for(var a=d(),b=0;b<this.t;++b)a[b]=this.DM&~this[b];return a.t=this.t,a.s=~this.s,a}function mb(a){var b=d();return 0>a?this.rShiftTo(-a,b):this.lShiftTo(a,b),b}function nb(a){var b=d();return 0>a?this.lShiftTo(-a,b):this.rShiftTo(a,b),b}function ob(a){if(0==a)return-1;var b=0;return 0==(65535&a)&&(a>>=16,b+=16),0==(255&a)&&(a>>=8,b+=8),0==(15&a)&&(a>>=4,b+=4),0==(3&a)&&(a>>=2,b+=2),0==(1&a)&&++b,b}function pb(){for(var a=0;a<this.t;++a)if(0!=this[a])return a*this.DB+ob(this[a]);return this.s<0?this.t*this.DB:-1}function qb(a){for(var b=0;0!=a;)a&=a-1,++b;return b}function rb(){for(var a=0,b=this.s&this.DM,c=0;c<this.t;++c)a+=qb(this[c]^b);return a}function sb(a){var b=Math.floor(a/this.DB);return b>=this.t?0!=this.s:0!=(this[b]&1<<a%this.DB)}function tb(a,b){var d=c.ONE.shiftLeft(a);return this.bitwiseTo(d,b,d),d}function ub(a){return this.changeBit(a,fb)}function vb(a){return this.changeBit(a,jb)}function wb(a){return this.changeBit(a,hb)}function xb(a,b){for(var c=0,d=0,e=Math.min(a.t,this.t);e>c;)d+=this[c]+a[c],b[c++]=d&this.DM,d>>=this.DB;if(a.t<this.t){for(d+=a.s;c<this.t;)d+=this[c],b[c++]=d&this.DM,d>>=this.DB;d+=this.s}else{for(d+=this.s;c<a.t;)d+=a[c],b[c++]=d&this.DM,d>>=this.DB;d+=a.s}b.s=0>d?-1:0,d>0?b[c++]=d:-1>d&&(b[c++]=this.DV+d),b.t=c,b.clamp()}function yb(a){var b=d();return this.addTo(a,b),b}function zb(a){var b=d();return this.subTo(a,b),b}function Ab(a){var b=d();return this.multiplyTo(a,b),b}function Bb(){var a=d();return this.squareTo(a),a}function Cb(a){var b=d();return this.divRemTo(a,b,null),b}function Db(a){var b=d();return this.divRemTo(a,null,b),b}function Eb(a){var b=d(),c=d();return this.divRemTo(a,b,c),new Array(b,c)}function Fb(a){this[this.t]=this.am(0,a-1,this,0,0,this.t),++this.t,this.clamp()}function Gb(a,b){if(0!=a){for(;this.t<=b;)this[this.t++]=0;for(this[b]+=a;this[b]>=this.DV;)this[b]-=this.DV,++b>=this.t&&(this[this.t++]=0),++this[b]}}function Hb(){}function Ib(a){return a}function Jb(a,b,c){a.multiplyTo(b,c)}function Kb(a,b){a.squareTo(b)}function Lb(a){return this.exp(a,new Hb)}function Mb(a,b,c){var d=Math.min(this.t+a.t,b);for(c.s=0,c.t=d;d>0;)c[--d]=0;var e;for(e=c.t-this.t;e>d;++d)c[d+this.t]=this.am(0,a[d],c,d,0,this.t);for(e=Math.min(a.t,b);e>d;++d)this.am(0,a[d],c,d,0,b-d);c.clamp()}function Nb(a,b,c){--b;var d=c.t=this.t+a.t-b;for(c.s=0;--d>=0;)c[d]=0;for(d=Math.max(b-this.t,0);d<a.t;++d)c[this.t+d-b]=this.am(b-d,a[d],c,0,0,this.t+d-b);c.clamp(),c.drShiftTo(1,c)}function Ob(a){this.r2=d(),this.q3=d(),c.ONE.dlShiftTo(2*a.t,this.r2),this.mu=this.r2.divide(a),this.m=a}function Pb(a){if(a.s<0||a.t>2*this.m.t)return a.mod(this.m);if(a.compareTo(this.m)<0)return a;var b=d();return a.copyTo(b),this.reduce(b),b}function Qb(a){return a}function Rb(a){for(a.drShiftTo(this.m.t-1,this.r2),a.t>this.m.t+1&&(a.t=this.m.t+1,a.clamp()),this.mu.multiplyUpperTo(this.r2,this.m.t+1,this.q3),this.m.multiplyLowerTo(this.q3,this.m.t+1,this.r2);a.compareTo(this.r2)<0;)a.dAddOffset(1,this.m.t+1);for(a.subTo(this.r2,a);a.compareTo(this.m)>=0;)a.subTo(this.m,a)}function Sb(a,b){a.squareTo(b),this.reduce(b)}function Tb(a,b,c){a.multiplyTo(b,c),this.reduce(c)}function Ub(a,b){var c,e,f=a.bitLength(),g=j(1);if(0>=f)return g;c=18>f?1:48>f?3:144>f?4:768>f?5:6,e=8>f?new B(b):b.isEven()?new Ob(b):new I(b);var h=new Array,i=3,k=c-1,l=(1<<c)-1;if(h[1]=e.convert(this),c>1){var m=d();for(e.sqrTo(h[1],m);l>=i;)h[i]=d(),e.mulTo(m,h[i-2],h[i]),i+=2}var n,o,p=a.t-1,r=!0,s=d();for(f=q(a[p])-1;p>=0;){for(f>=k?n=a[p]>>f-k&l:(n=(a[p]&(1<<f+1)-1)<<k-f,p>0&&(n|=a[p-1]>>this.DB+f-k)),i=c;0==(1&n);)n>>=1,--i;if((f-=i)<0&&(f+=this.DB,--p),r)h[n].copyTo(g),r=!1;else{for(;i>1;)e.sqrTo(g,s),e.sqrTo(s,g),i-=2;i>0?e.sqrTo(g,s):(o=g,g=s,s=o),e.mulTo(s,h[n],g)}for(;p>=0&&0==(a[p]&1<<f);)e.sqrTo(g,s),o=g,g=s,s=o,--f<0&&(f=this.DB-1,--p)}return e.revert(g)}function Vb(a){var b=this.s<0?this.negate():this.clone(),c=a.s<0?a.negate():a.clone();if(b.compareTo(c)<0){var d=b;b=c,c=d}var e=b.getLowestSetBit(),f=c.getLowestSetBit();if(0>f)return b;for(f>e&&(f=e),f>0&&(b.rShiftTo(f,b),c.rShiftTo(f,c));b.signum()>0;)(e=b.getLowestSetBit())>0&&b.rShiftTo(e,b),(e=c.getLowestSetBit())>0&&c.rShiftTo(e,c),b.compareTo(c)>=0?(b.subTo(c,b),b.rShiftTo(1,b)):(c.subTo(b,c),c.rShiftTo(1,c));return f>0&&c.lShiftTo(f,c),c}function Wb(a){if(0>=a)return 0;var b=this.DV%a,c=this.s<0?a-1:0;if(this.t>0)if(0==b)c=this[0]%a;else for(var d=this.t-1;d>=0;--d)c=(b*c+this[d])%a;return c}function Xb(a){var b=a.isEven();if(this.isEven()&&b||0==a.signum())return c.ZERO;for(var d=a.clone(),e=this.clone(),f=j(1),g=j(0),h=j(0),i=j(1);0!=d.signum();){for(;d.isEven();)d.rShiftTo(1,d),b?(f.isEven()&&g.isEven()||(f.addTo(this,f),g.subTo(a,g)),f.rShiftTo(1,f)):g.isEven()||g.subTo(a,g),g.rShiftTo(1,g);for(;e.isEven();)e.rShiftTo(1,e),b?(h.isEven()&&i.isEven()||(h.addTo(this,h),i.subTo(a,i)),h.rShiftTo(1,h)):i.isEven()||i.subTo(a,i),i.rShiftTo(1,i);d.compareTo(e)>=0?(d.subTo(e,d),b&&f.subTo(h,f),g.subTo(i,g)):(e.subTo(d,e),b&&h.subTo(f,h),i.subTo(g,i))}return 0!=e.compareTo(c.ONE)?c.ZERO:i.compareTo(a)>=0?i.subtract(a):i.signum()<0?(i.addTo(a,i),i.signum()<0?i.add(a):i):i}function Yb(a){var b,c=this.abs();if(1==c.t&&c[0]<=gc[gc.length-1]){for(b=0;b<gc.length;++b)if(c[0]==gc[b])return!0;return!1}if(c.isEven())return!1;for(b=1;b<gc.length;){for(var d=gc[b],e=b+1;e<gc.length&&hc>d;)d*=gc[e++];for(d=c.modInt(d);e>b;)if(d%gc[b++]==0)return!1}return c.millerRabin(a)}function q(a){var b,c=1;return 0!=(b=a>>>16)&&(a=b,c+=16),0!=(b=a>>8)&&(a=b,c+=8),0!=(b=a>>4)&&(a=b,c+=4),0!=(b=a>>2)&&(a=b,c+=2),0!=(b=a>>1)&&(a=b,c+=1),c}function Zb(){var a=this.toByteArray(),b=8*(a.length-1)+q(a[0]),c="";return c+=String.fromCharCode((65280&b)>>8),c+=String.fromCharCode(255&b),c+=ac.bin2str(a)}function $b(a){var b=this.subtract(c.ONE),e=b.getLowestSetBit();if(0>=e)return!1;var f=b.shiftRight(e);a=a+1>>1,a>gc.length&&(a=gc.length);for(var g,h=d(),i=[],j=0;a>j;++j){for(;g=gc[Math.floor(Math.random()*gc.length)],-1!=i.indexOf(g););i.push(g),h.fromInt(g);var k=h.modPow(f,this);if(0!=k.compareTo(c.ONE)&&0!=k.compareTo(b)){for(var g=1;g++<e&&0!=k.compareTo(b);)if(k=k.modPowInt(2,this),0==k.compareTo(c.ONE))return!1;if(0!=k.compareTo(b))return!1}}return!0}var _b,ac=a("../../util.js");c.prototype.am=e,_b=26,c.prototype.DB=_b,c.prototype.DM=(1<<_b)-1,c.prototype.DV=1<<_b;var bc=52;c.prototype.FV=Math.pow(2,bc),c.prototype.F1=bc-_b,c.prototype.F2=2*_b-bc;var cc,dc,ec="0123456789abcdefghijklmnopqrstuvwxyz",fc=new Array;for(cc="0".charCodeAt(0),dc=0;9>=dc;++dc)fc[cc++]=dc;for(cc="a".charCodeAt(0),dc=10;36>dc;++dc)fc[cc++]=dc;for(cc="A".charCodeAt(0),dc=10;36>dc;++dc)fc[cc++]=dc;B.prototype.convert=C,B.prototype.revert=D,B.prototype.reduce=E,B.prototype.mulTo=F,B.prototype.sqrTo=G,I.prototype.convert=J,I.prototype.revert=K,I.prototype.reduce=L,I.prototype.mulTo=N,I.prototype.sqrTo=M,c.prototype.copyTo=h,c.prototype.fromInt=i,c.prototype.fromString=k,c.prototype.clamp=l,c.prototype.dlShiftTo=s,c.prototype.drShiftTo=t,c.prototype.lShiftTo=u,c.prototype.rShiftTo=v,c.prototype.subTo=w,c.prototype.multiplyTo=x,c.prototype.squareTo=y,c.prototype.divRemTo=z,c.prototype.invDigit=H,c.prototype.isEven=O,c.prototype.exp=P,c.prototype.toString=m,c.prototype.negate=n,c.prototype.abs=o,c.prototype.compareTo=p,c.prototype.bitLength=r,c.prototype.mod=A,c.prototype.modPowInt=Q,c.ZERO=j(0),c.ONE=j(1),c.TWO=j(2),b.exports=c,Hb.prototype.convert=Ib,Hb.prototype.revert=Ib,Hb.prototype.mulTo=Jb,Hb.prototype.sqrTo=Kb,Ob.prototype.convert=Pb,Ob.prototype.revert=Qb,Ob.prototype.reduce=Rb,Ob.prototype.mulTo=Tb,Ob.prototype.sqrTo=Sb;var gc=[2,3,5,7,11,13,17,19,23,29,31,37,41,43,47,53,59,61,67,71,73,79,83,89,97,101,103,107,109,113,127,131,137,139,149,151,157,163,167,173,179,181,191,193,197,199,211,223,227,229,233,239,241,251,257,263,269,271,277,281,283,293,307,311,313,317,331,337,347,349,353,359,367,373,379,383,389,397,401,409,419,421,431,433,439,443,449,457,461,463,467,479,487,491,499,503,509,521,523,541,547,557,563,569,571,577,587,593,599,601,607,613,617,619,631,641,643,647,653,659,661,673,677,683,691,701,709,719,727,733,739,743,751,757,761,769,773,787,797,809,811,821,823,827,829,839,853,857,859,863,877,881,883,887,907,911,919,929,937,941,947,953,967,971,977,983,991,997],hc=(1<<26)/gc[gc.length-1],c=a("./jsbn.js");c.prototype.chunkSize=V,c.prototype.toRadix=X,c.prototype.fromRadix=Y,c.prototype.fromNumber=Z,c.prototype.bitwiseTo=cb,c.prototype.changeBit=tb,c.prototype.addTo=xb,c.prototype.dMultiply=Fb,c.prototype.dAddOffset=Gb,c.prototype.multiplyLowerTo=Mb,c.prototype.multiplyUpperTo=Nb,c.prototype.modInt=Wb,c.prototype.millerRabin=$b,c.prototype.clone=R,c.prototype.intValue=S,c.prototype.byteValue=T,c.prototype.shortValue=U,c.prototype.signum=W,c.prototype.toByteArray=$,c.prototype.equals=_,c.prototype.min=ab,c.prototype.max=bb,c.prototype.and=eb,c.prototype.or=gb,c.prototype.xor=ib,c.prototype.andNot=kb,c.prototype.not=lb,c.prototype.shiftLeft=mb,c.prototype.shiftRight=nb,c.prototype.getLowestSetBit=pb,c.prototype.bitCount=rb,c.prototype.testBit=sb,c.prototype.setBit=ub,c.prototype.clearBit=vb,c.prototype.flipBit=wb,c.prototype.add=yb,c.prototype.subtract=zb,c.prototype.multiply=Ab,c.prototype.divide=Cb,c.prototype.remainder=Db,c.prototype.divideAndRemainder=Eb,c.prototype.modPow=Ub,c.prototype.modInverse=Xb,c.prototype.pow=Lb,c.prototype.gcd=Vb,c.prototype.isProbablePrime=Yb,c.prototype.toMPI=Zb,c.prototype.square=Bb
},{"../../util.js":61,"./jsbn.js":24}],25:[function(a,b){function c(){function a(a){for(var b=0;b<a.length;b++)a[b]=i.getSecureRandomOctet()}this.nextBytes=a}function d(a,b,c){return l=l.bitLength()===b.bitLength()?l.square().mod(b):i.getRandomBigIntegerInRange(g.TWO,b),k=l.modInverse(b).modPow(c,b),a.multiply(k).mod(b)}function e(a,b){return a.multiply(l).mod(b)}function f(){function a(a,b,c,f,i,k,l){j.rsa_blinding&&(a=d(a,b,c));var m=a.mod(i).modPow(f.mod(i.subtract(g.ONE)),i),n=a.mod(k).modPow(f.mod(k.subtract(g.ONE)),k);h.print_debug("rsa.js decrypt\nxpn:"+h.hexstrdump(m.toMPI())+"\nxqn:"+h.hexstrdump(n.toMPI()));var o=n.subtract(m);return 0===o[0]?(o=m.subtract(n),o=o.multiply(l).mod(k),o=k.subtract(o)):o=o.multiply(l).mod(k),o=o.multiply(i).add(m),j.rsa_blinding&&(o=e(o,b)),o}function b(a,b,c){return a.modPowInt(b,c)}function f(a,b,c){return a.modPow(b,c)}function i(a,b,c){return a.modPowInt(b,c)}function k(){this.n=null,this.e=0,this.ee=null,this.d=null,this.p=null,this.q=null,this.dmp1=null,this.dmq1=null,this.u=null}function l(a,b){var d=new k,e=new c,f=a>>1;for(d.e=parseInt(b,16),d.ee=new g(b,16);;){for(;d.p=new g(a-f,1,e),0!==d.p.subtract(g.ONE).gcd(d.ee).compareTo(g.ONE)||!d.p.isProbablePrime(10););for(;d.q=new g(f,1,e),0!==d.q.subtract(g.ONE).gcd(d.ee).compareTo(g.ONE)||!d.q.isProbablePrime(10););if(d.p.compareTo(d.q)<=0){var h=d.p;d.p=d.q,d.q=h}var i=d.p.subtract(g.ONE),j=d.q.subtract(g.ONE),l=i.multiply(j);if(0===l.gcd(d.ee).compareTo(g.ONE)){d.n=d.p.multiply(d.q),d.d=d.ee.modInverse(l),d.dmp1=d.d.mod(i),d.dmq1=d.d.mod(j),d.u=d.p.modInverse(d.q);break}}return d}this.encrypt=b,this.decrypt=a,this.verify=i,this.sign=f,this.generate=l,this.keyObject=k}var g=a("./jsbn.js"),h=a("../../util.js"),i=a("../random.js"),j=a("../../config"),k=g.ZERO,l=g.ZERO;b.exports=f},{"../../config":4,"../../util.js":61,"../random.js":26,"./jsbn.js":24}],26:[function(a,b){function c(){this.buffer=null,this.size=null}var d=a("../type/mpi.js"),e=null;"undefined"==typeof window&&(e=a("crypto")),b.exports={getRandomBytes:function(a){for(var b="",c=0;a>c;c++)b+=String.fromCharCode(this.getSecureRandomOctet());return b},getSecureRandom:function(a,b){for(var c=this.getSecureRandomUint(),d=(b-a).toString(2).length;(c&Math.pow(2,d)-1)>b-a;)c=this.getSecureRandomUint();return a+Math.abs(c&Math.pow(2,d)-1)},getSecureRandomOctet:function(){var a=new Uint8Array(1);return this.getRandomValues(a),a[0]},getSecureRandomUint:function(){var a=new Uint8Array(4),b=new DataView(a.buffer);return this.getRandomValues(a),b.getUint32(0)},getRandomValues:function(a){if(!(a instanceof Uint8Array))throw new Error("Invalid type: buf not an Uint8Array");if("undefined"!=typeof window&&window.crypto&&window.crypto.getRandomValues)window.crypto.getRandomValues(a);else if("undefined"!=typeof window&&"object"==typeof window.msCrypto&&"function"==typeof window.msCrypto.getRandomValues)window.msCrypto.getRandomValues(a);else if(e){var b=e.randomBytes(a.length);a.set(b)}else{if(!this.randomBuffer.buffer)throw new Error("No secure random number generator available.");this.randomBuffer.get(a)}},getRandomBigInteger:function(a){if(1>a)throw new Error("Illegal parameter value: bits < 1");var b=Math.floor((a+7)/8),c=this.getRandomBytes(b);a%8>0&&(c=String.fromCharCode(Math.pow(2,a%8)-1&c.charCodeAt(0))+c.substring(1));var e=new d;return e.fromBytes(c),e.toBigInteger()},getRandomBigIntegerInRange:function(a,b){if(b.compareTo(a)<=0)throw new Error("Illegal parameter value: max <= min");for(var c=b.subtract(a),d=this.getRandomBigInteger(c.bitLength());d>c;)d=this.getRandomBigInteger(c.bitLength());return a.add(d)},randomBuffer:new c},c.prototype.init=function(a){this.buffer=new Uint8Array(a),this.size=0},c.prototype.set=function(a){if(!this.buffer)throw new Error("RandomBuffer is not initialized");if(!(a instanceof Uint8Array))throw new Error("Invalid type: buf not an Uint8Array");var b=this.buffer.length-this.size;a.length>b&&(a=a.subarray(0,b)),this.buffer.set(a,this.size),this.size+=a.length},c.prototype.get=function(a){if(!this.buffer)throw new Error("RandomBuffer is not initialized");if(!(a instanceof Uint8Array))throw new Error("Invalid type: buf not an Uint8Array");if(this.size<a.length)throw new Error("Random number buffer depleted");for(var b=0;b<a.length;b++)a[b]=this.buffer[--this.size],this.buffer[this.size]=0}},{"../type/mpi.js":59,crypto:!1}],27:[function(a,b){{var c=a("./public_key"),d=a("./pkcs1.js");a("./hash")}b.exports={verify:function(a,b,e,f,g){switch(a){case 1:case 2:case 3:var h=new c.rsa,i=f[0].toBigInteger(),j=f[0].byteLength(),k=f[1].toBigInteger(),l=e[0].toBigInteger(),m=h.verify(l,k,i),n=d.emsa.encode(b,g,j);return 0===m.compareTo(n);case 16:throw new Error("signing with Elgamal is not defined in the OpenPGP standard.");case 17:var o=new c.dsa,p=e[0].toBigInteger(),q=e[1].toBigInteger(),r=f[0].toBigInteger(),s=f[1].toBigInteger(),t=f[2].toBigInteger(),u=f[3].toBigInteger(),l=g,v=o.verify(b,p,q,l,r,s,t,u);return 0===v.compareTo(p);default:throw new Error("Invalid signature algorithm.")}},sign:function(a,b,e,f){var g;switch(b){case 1:case 2:case 3:var h=new c.rsa,i=e[2].toBigInteger(),j=e[0].toBigInteger();return g=d.emsa.encode(a,f,e[0].byteLength()),h.sign(g,i,j).toMPI();case 17:var k=new c.dsa,l=e[0].toBigInteger(),m=e[1].toBigInteger(),n=e[2].toBigInteger(),o=(e[3].toBigInteger(),e[4].toBigInteger());g=f;var p=k.sign(a,g,n,l,m,o);return p[0].toString()+p[1].toString();case 16:throw new Error("Signing with Elgamal is not defined in the OpenPGP standard.");default:throw new Error("Invalid signature algorithm.")}}}},{"./hash":15,"./pkcs1.js":20,"./public_key":23}],28:[function(a,b){function c(a){var b=/^-----BEGIN PGP (MESSAGE, PART \d+\/\d+|MESSAGE, PART \d+|SIGNED MESSAGE|MESSAGE|PUBLIC KEY BLOCK|PRIVATE KEY BLOCK)-----$\n/m,c=a.match(b);if(!c)throw new Error("Unknow ASCII armor type");return c[1].match(/MESSAGE, PART \d+\/\d+/)?n.armor.multipart_section:c[1].match(/MESSAGE, PART \d+/)?n.armor.multipart_last:c[1].match(/SIGNED MESSAGE/)?n.armor.signed:c[1].match(/MESSAGE/)?n.armor.message:c[1].match(/PUBLIC KEY BLOCK/)?n.armor.public_key:c[1].match(/PRIVATE KEY BLOCK/)?n.armor.private_key:void 0}function d(){var a="";return o.show_version&&(a+="Version: "+o.versionstring+"\r\n"),o.show_comment&&(a+="Comment: "+o.commentstring+"\r\n"),a+="\r\n"}function e(a){var b=g(a),c=""+String.fromCharCode(b>>16)+String.fromCharCode(b>>8&255)+String.fromCharCode(255&b);return m.encode(c)}function f(a,b){var c=e(a),d=b;return c[0]==d[0]&&c[1]==d[1]&&c[2]==d[2]&&c[3]==d[3]}function g(a){for(var b=11994318,c=0;a.length-c>16;)b=b<<8^p[255&(b>>16^a.charCodeAt(c))],b=b<<8^p[255&(b>>16^a.charCodeAt(c+1))],b=b<<8^p[255&(b>>16^a.charCodeAt(c+2))],b=b<<8^p[255&(b>>16^a.charCodeAt(c+3))],b=b<<8^p[255&(b>>16^a.charCodeAt(c+4))],b=b<<8^p[255&(b>>16^a.charCodeAt(c+5))],b=b<<8^p[255&(b>>16^a.charCodeAt(c+6))],b=b<<8^p[255&(b>>16^a.charCodeAt(c+7))],b=b<<8^p[255&(b>>16^a.charCodeAt(c+8))],b=b<<8^p[255&(b>>16^a.charCodeAt(c+9))],b=b<<8^p[255&(b>>16^a.charCodeAt(c+10))],b=b<<8^p[255&(b>>16^a.charCodeAt(c+11))],b=b<<8^p[255&(b>>16^a.charCodeAt(c+12))],b=b<<8^p[255&(b>>16^a.charCodeAt(c+13))],b=b<<8^p[255&(b>>16^a.charCodeAt(c+14))],b=b<<8^p[255&(b>>16^a.charCodeAt(c+15))],c+=16;for(var d=c;d<a.length;d++)b=b<<8^p[255&(b>>16^a.charCodeAt(c++))];return 16777215&b}function h(a){var b=/^\s*\n/m,c="",d=a,e=b.exec(a);if(null===e)throw new Error("Mandatory blank line missing between armor headers and armor data");return c=a.slice(0,e.index),d=a.slice(e.index+e[0].length),c=c.split("\n"),c.pop(),{headers:c,body:d}}function i(a){for(var b=0;b<a.length;b++)if(!a[b].match(/^(Version|Comment|MessageID|Hash|Charset): .+$/))throw new Error("Improperly formatted armor header: "+a[b])}function j(a){var b=/^=/m,c=a,d="",e=b.exec(a);return null!==e&&(c=a.slice(0,e.index),d=a.slice(e.index+1)),{body:c,checksum:d}}function k(a){var b=/^-----[^-]+-----$\n/m;a=a.replace(/[\t\r ]+\n/g,"\n");var d,g,k,l=c(a),n=a.split(b),o=1;if(a.search(b)!=n[0].length&&(o=0),2!=l){k=h(n[o]);var p=j(k.body);d={data:m.decode(p.body),headers:k.headers,type:l},g=p.checksum}else{k=h(n[o].replace(/^- /gm,""));var q=h(n[o+1].replace(/^- /gm,""));i(q.headers);var r=j(q.body);d={text:k.body.replace(/\n$/,"").replace(/\n/g,"\r\n"),data:m.decode(r.body),headers:k.headers,type:l},g=r.checksum}if(g=g.substr(0,4),!f(d.data,g))throw new Error("Ascii armor integrity check on message failed: '"+g+"' should be '"+e(d.data)+"'");return i(d.headers),d}function l(a,b,c,f){var g="";switch(a){case n.armor.multipart_section:g+="-----BEGIN PGP MESSAGE, PART "+c+"/"+f+"-----\r\n",g+=d(),g+=m.encode(b),g+="\r\n="+e(b)+"\r\n",g+="-----END PGP MESSAGE, PART "+c+"/"+f+"-----\r\n";break;case n.armor.multipart_last:g+="-----BEGIN PGP MESSAGE, PART "+c+"-----\r\n",g+=d(),g+=m.encode(b),g+="\r\n="+e(b)+"\r\n",g+="-----END PGP MESSAGE, PART "+c+"-----\r\n";break;case n.armor.signed:g+="\r\n-----BEGIN PGP SIGNED MESSAGE-----\r\n",g+="Hash: "+b.hash+"\r\n\r\n",g+=b.text.replace(/\n-/g,"\n- -"),g+="\r\n-----BEGIN PGP SIGNATURE-----\r\n",g+=d(),g+=m.encode(b.data),g+="\r\n="+e(b.data)+"\r\n",g+="-----END PGP SIGNATURE-----\r\n";break;case n.armor.message:g+="-----BEGIN PGP MESSAGE-----\r\n",g+=d(),g+=m.encode(b),g+="\r\n="+e(b)+"\r\n",g+="-----END PGP MESSAGE-----\r\n";break;case n.armor.public_key:g+="-----BEGIN PGP PUBLIC KEY BLOCK-----\r\n",g+=d(),g+=m.encode(b),g+="\r\n="+e(b)+"\r\n",g+="-----END PGP PUBLIC KEY BLOCK-----\r\n\r\n";break;case n.armor.private_key:g+="-----BEGIN PGP PRIVATE KEY BLOCK-----\r\n",g+=d(),g+=m.encode(b),g+="\r\n="+e(b)+"\r\n",g+="-----END PGP PRIVATE KEY BLOCK-----\r\n"}return g}var m=a("./base64.js"),n=a("../enums.js"),o=a("../config"),p=[0,8801531,25875725,17603062,60024545,51751450,35206124,44007191,128024889,120049090,103502900,112007375,70412248,78916387,95990485,88014382,264588937,256049778,240098180,248108927,207005800,215016595,232553829,224014750,140824496,149062475,166599357,157832774,200747345,191980970,176028764,184266919,520933865,529177874,512099556,503334943,480196360,471432179,487973381,496217854,414011600,405478443,422020573,430033190,457094705,465107658,448029500,439496647,281648992,273666971,289622637,298124950,324696449,333198714,315665548,307683447,392699481,401494690,383961940,375687087,352057528,343782467,359738805,368533838,1041867730,1050668841,1066628831,1058355748,1032471859,1024199112,1006669886,1015471301,968368875,960392720,942864358,951368477,975946762,984451313,1000411399,992435708,836562267,828023200,810956886,818967725,844041146,852051777,868605623,860066380,914189410,922427545,938981743,930215316,904825475,896059e3,878993294,887231349,555053627,563297984,547333942,538569677,579245274,570480673,588005847,596249900,649392898,640860153,658384399,666397428,623318499,631331096,615366894,606833685,785398962,777416777,794487231,802989380,759421523,767923880,751374174,743392165,695319947,704115056,687564934,679289981,719477610,711202705,728272487,737067676,2083735460,2092239711,2109313705,2101337682,2141233477,2133257662,2116711496,2125215923,2073216669,2064943718,2048398224,2057199467,2013339772,2022141063,2039215473,2030942602,1945504045,1936737750,1920785440,1929023707,1885728716,1893966647,1911503553,1902736954,1951893524,1959904495,1977441561,1968902626,2009362165,2000822798,1984871416,1992881923,1665111629,1673124534,1656046400,1647513531,1621913772,1613380695,1629922721,1637935450,1688082292,1679317903,1695859321,1704103554,1728967061,1737211246,1720132760,1711368291,1828378820,1820103743,1836060105,1844855090,1869168165,1877963486,1860430632,1852155859,1801148925,1809650950,1792118e3,1784135691,1757986588,1750004711,1765960209,1774462698,1110107254,1118611597,1134571899,1126595968,1102643863,1094667884,1077139354,1085643617,1166763343,1158490548,1140961346,1149762745,1176011694,1184812885,1200772771,1192499800,1307552511,1298785796,1281720306,1289958153,1316768798,1325007077,1341561107,1332794856,1246636998,1254647613,1271201483,1262662192,1239272743,1230733788,1213667370,1221678289,1562785183,1570797924,1554833554,1546300521,1588974462,1580441477,1597965939,1605978760,1518843046,1510078557,1527603627,1535847760,1494504007,1502748348,1486784330,1478020017,1390639894,1382365165,1399434779,1408230112,1366334967,1375129868,1358579962,1350304769,1430452783,1438955220,1422405410,1414423513,1456544974,1448562741,1465633219,1474135352];b.exports={encode:l,decode:k}},{"../config":4,"../enums.js":30,"./base64.js":29}],29:[function(a,b){function c(a){var b,c,d,f="",g=0,h=0,i=a.length;for(d=0;i>d;d++)c=a.charCodeAt(d),0===h?(f+=e.charAt(c>>2&63),b=(3&c)<<4):1==h?(f+=e.charAt(b|c>>4&15),b=(15&c)<<2):2==h&&(f+=e.charAt(b|c>>6&3),g+=1,g%60===0&&(f+="\n"),f+=e.charAt(63&c)),g+=1,g%60===0&&(f+="\n"),h+=1,3==h&&(h=0);return h>0&&(f+=e.charAt(b),g+=1,g%60===0&&(f+="\n"),f+="=",g+=1),1==h&&(g%60===0&&(f+="\n"),f+="="),f}function d(a){var b,c,d="",f=0,g=0,h=a.length;for(c=0;h>c;c++)b=e.indexOf(a.charAt(c)),b>=0&&(f&&(d+=String.fromCharCode(g|b>>6-f&255)),f=f+2&7,g=b<<f&255);return d}var e="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";b.exports={encode:c,decode:d}},{}],30:[function(a,b){b.exports={s2k:{simple:0,salted:1,iterated:3,gnu:101},publicKey:{rsa_encrypt_sign:1,rsa_encrypt:2,rsa_sign:3,elgamal:16,dsa:17},symmetric:{plaintext:0,idea:1,tripledes:2,cast5:3,blowfish:4,aes128:7,aes192:8,aes256:9,twofish:10},compression:{uncompressed:0,zip:1,zlib:2,bzip2:3},hash:{md5:1,sha1:2,ripemd:3,sha256:8,sha384:9,sha512:10,sha224:11},packet:{publicKeyEncryptedSessionKey:1,signature:2,symEncryptedSessionKey:3,onePassSignature:4,secretKey:5,publicKey:6,secretSubkey:7,compressed:8,symmetricallyEncrypted:9,marker:10,literal:11,trust:12,userid:13,publicSubkey:14,userAttribute:17,symEncryptedIntegrityProtected:18,modificationDetectionCode:19},literal:{binary:"b".charCodeAt(),text:"t".charCodeAt(),utf8:"u".charCodeAt()},signature:{binary:0,text:1,standalone:2,cert_generic:16,cert_persona:17,cert_casual:18,cert_positive:19,cert_revocation:48,subkey_binding:24,key_binding:25,key:31,key_revocation:32,subkey_revocation:40,timestamp:64,third_party:80},signatureSubpacket:{signature_creation_time:2,signature_expiration_time:3,exportable_certification:4,trust_signature:5,regular_expression:6,revocable:7,key_expiration_time:9,placeholder_backwards_compatibility:10,preferred_symmetric_algorithms:11,revocation_key:12,issuer:16,notation_data:20,preferred_hash_algorithms:21,preferred_compression_algorithms:22,key_server_preferences:23,preferred_key_server:24,primary_user_id:25,policy_uri:26,key_flags:27,signers_user_id:28,reason_for_revocation:29,features:30,signature_target:31,embedded_signature:32},keyFlags:{certify_keys:1,sign_data:2,encrypt_communication:4,encrypt_storage:8,split_private_key:16,authentication:32,shared_private_key:128},keyStatus:{invalid:0,expired:1,revoked:2,valid:3,no_self_cert:4},armor:{multipart_section:0,multipart_last:1,signed:2,message:3,public_key:4,private_key:5},write:function(a,b){if("number"==typeof b&&(b=this.read(a,b)),void 0!==a[b])return a[b];throw new Error("Invalid enum value.")},read:function(a,b){for(var c in a)if(a[c]==b)return c;throw new Error("Invalid enum value.")}}},{}],31:[function(a,b){b.exports=a("./openpgp.js"),b.exports.key=a("./key.js"),b.exports.message=a("./message.js"),b.exports.cleartext=a("./cleartext.js"),b.exports.util=a("./util.js"),b.exports.packet=a("./packet"),b.exports.MPI=a("./type/mpi.js"),b.exports.S2K=a("./type/s2k.js"),b.exports.Keyid=a("./type/keyid.js"),b.exports.armor=a("./encoding/armor.js"),b.exports.enums=a("./enums.js"),b.exports.config=a("./config/config.js"),b.exports.crypto=a("./crypto"),b.exports.Keyring=a("./keyring"),b.exports.AsyncProxy=a("./worker/async_proxy.js")},{"./cleartext.js":1,"./config/config.js":3,"./crypto":19,"./encoding/armor.js":28,"./enums.js":30,"./key.js":32,"./keyring":33,"./message.js":36,"./openpgp.js":37,"./packet":40,"./type/keyid.js":58,"./type/mpi.js":59,"./type/s2k.js":60,"./util.js":61,"./worker/async_proxy.js":62}],32:[function(a,b,c){function d(a){if(!(this instanceof d))return new d(a);if(this.primaryKey=null,this.revocationSignature=null,this.directSignatures=null,this.users=null,this.subKeys=null,this.packetlist2structure(a),!this.primaryKey||!this.users)throw new Error("Invalid key: need at least key and user ID packet")}function e(a,b){for(var c=0;c<a.length;c++)for(var d=a[c].getKeyId(),e=0;e<b.length;e++)if(d.equals(b[e]))return a[c];return null}function f(a,b){return a.algorithm!==p.read(p.publicKey,p.publicKey.dsa)&&a.algorithm!==p.read(p.publicKey,p.publicKey.rsa_sign)&&(!b.keyFlags||0!==(b.keyFlags[0]&p.keyFlags.encrypt_communication)||0!==(b.keyFlags[0]&p.keyFlags.encrypt_storage))}function g(a,b){return!(a.algorithm!=p.read(p.publicKey,p.publicKey.dsa)&&a.algorithm!=p.read(p.publicKey,p.publicKey.rsa_sign)&&a.algorithm!=p.read(p.publicKey,p.publicKey.rsa_encrypt_sign)||b.keyFlags&&0===(b.keyFlags[0]&p.keyFlags.sign_data))}function h(a,b){return 3==a.version&&0!==a.expirationTimeV3?new Date(a.created.getTime()+24*a.expirationTimeV3*3600*1e3):4==a.version&&b.keyNeverExpires===!1?new Date(a.created.getTime()+1e3*b.keyExpirationTime):null}function i(a,b,c,d){a=a[c],a&&(b[c]?a.forEach(function(a){a.isExpired()||d&&!d(a)||b[c].some(function(b){return b.signature===a.signature})||b[c].push(a)}):b[c]=a)}function j(a){return this instanceof j?(this.userId=a.tag==p.packet.userid?a:null,this.userAttribute=a.tag==p.packet.userAttribute?a:null,this.selfCertifications=null,this.otherCertifications=null,void(this.revocationCertifications=null)):new j(a)}function k(a){return this instanceof k?(this.subKey=a,this.bindingSignature=null,void(this.revocationSignature=null)):new k(a)}function l(a){var b={};b.keys=[];try{var c=q.decode(a);if(c.type!=p.armor.public_key&&c.type!=p.armor.private_key)throw new Error("Armored text not of type key");var e=new o.List;e.read(c.data);var f=e.indexOfTag(p.packet.publicKey,p.packet.secretKey);if(0===f.length)throw new Error("No key packet found in armored text");for(var g=0;g<f.length;g++){var h=e.slice(f[g],f[g+1]);try{var i=new d(h);b.keys.push(i)}catch(j){b.err=b.err||[],b.err.push(j)}}}catch(j){b.err=b.err||[],b.err.push(j)}return b}function m(a){if(a.keyType=a.keyType||p.publicKey.rsa_encrypt_sign,a.keyType!==p.publicKey.rsa_encrypt_sign)throw new Error("Only RSA Encrypt or Sign supported");if(!a.passphrase)throw new Error("Parameter options.passphrase required");var b=new o.List,c=new o.SecretKey;c.algorithm=p.read(p.publicKey,a.keyType),c.generate(a.numBits),c.encrypt(a.passphrase);var e=new o.Userid;e.read(a.userId);var f={};f.userid=e,f.key=c;var g=new o.Signature;g.signatureType=p.signature.cert_generic,g.publicKeyAlgorithm=a.keyType,g.hashAlgorithm=r.prefer_hash_algorithm,g.keyFlags=[p.keyFlags.certify_keys|p.keyFlags.sign_data],g.preferredSymmetricAlgorithms=[],g.preferredSymmetricAlgorithms.push(p.symmetric.aes256),g.preferredSymmetricAlgorithms.push(p.symmetric.aes192),g.preferredSymmetricAlgorithms.push(p.symmetric.aes128),g.preferredSymmetricAlgorithms.push(p.symmetric.cast5),g.preferredSymmetricAlgorithms.push(p.symmetric.tripledes),g.preferredHashAlgorithms=[],g.preferredHashAlgorithms.push(p.hash.sha256),g.preferredHashAlgorithms.push(p.hash.sha1),g.preferredHashAlgorithms.push(p.hash.sha512),g.preferredCompressionAlgorithms=[],g.preferredCompressionAlgorithms.push(p.compression.zlib),g.preferredCompressionAlgorithms.push(p.compression.zip),r.integrity_protect&&(g.features=[],g.features.push(1)),g.sign(c,f);var h=new o.SecretSubkey;h.algorithm=p.read(p.publicKey,a.keyType),h.generate(a.numBits),h.encrypt(a.passphrase),f={},f.key=c,f.bind=h;var i=new o.Signature;return i.signatureType=p.signature.subkey_binding,i.publicKeyAlgorithm=a.keyType,i.hashAlgorithm=r.prefer_hash_algorithm,i.keyFlags=[p.keyFlags.encrypt_communication|p.keyFlags.encrypt_storage],i.sign(c,f),b.push(c),b.push(e),b.push(g),b.push(h),b.push(i),a.unlocked||(c.clearPrivateMPIs(),h.clearPrivateMPIs()),new d(b)}function n(a){for(var b={},c=0;c<a.length;c++){var d=a[c].getPrimaryUser();if(!d||!d.selfCertificate.preferredSymmetricAlgorithms)return r.encryption_cipher;d.selfCertificate.preferredSymmetricAlgorithms.forEach(function(a,c){var d=b[a]||(b[a]={prio:0,count:0,algo:a});d.prio+=64>>c,d.count++})}var e={prio:0,algo:r.encryption_cipher};for(var f in b)try{f!==p.symmetric.plaintext&&f!==p.symmetric.idea&&p.read(p.symmetric,f)&&b[f].count===a.length&&b[f].prio>e.prio&&(e=b[f])}catch(g){}return e.algo}var o=a("./packet"),p=a("./enums.js"),q=a("./encoding/armor.js"),r=a("./config"),s=a("./util");d.prototype.packetlist2structure=function(a){for(var b,c,d,e=0;e<a.length;e++)switch(a[e].tag){case p.packet.publicKey:case p.packet.secretKey:this.primaryKey=a[e],c=this.primaryKey.getKeyId();break;case p.packet.userid:case p.packet.userAttribute:b=new j(a[e]),this.users||(this.users=[]),this.users.push(b);break;case p.packet.publicSubkey:case p.packet.secretSubkey:b=null,this.subKeys||(this.subKeys=[]),d=new k(a[e]),this.subKeys.push(d);break;case p.packet.signature:switch(a[e].signatureType){case p.signature.cert_generic:case p.signature.cert_persona:case p.signature.cert_casual:case p.signature.cert_positive:if(!b){s.print_debug("Dropping certification signatures without preceding user packet");continue}a[e].issuerKeyId.equals(c)?(b.selfCertifications||(b.selfCertifications=[]),b.selfCertifications.push(a[e])):(b.otherCertifications||(b.otherCertifications=[]),b.otherCertifications.push(a[e]));break;case p.signature.cert_revocation:b?(b.revocationCertifications||(b.revocationCertifications=[]),b.revocationCertifications.push(a[e])):(this.directSignatures||(this.directSignatures=[]),this.directSignatures.push(a[e]));break;case p.signature.key:this.directSignatures||(this.directSignatures=[]),this.directSignatures.push(a[e]);break;case p.signature.subkey_binding:if(!d){s.print_debug("Dropping subkey binding signature without preceding subkey packet");continue}d.bindingSignature=a[e];break;case p.signature.key_revocation:this.revocationSignature=a[e];break;case p.signature.subkey_revocation:if(!d){s.print_debug("Dropping subkey revocation signature without preceding subkey packet");continue}d.revocationSignature=a[e]}}},d.prototype.toPacketlist=function(){var a=new o.List;a.push(this.primaryKey),a.push(this.revocationSignature),a.concat(this.directSignatures);var b;for(b=0;b<this.users.length;b++)a.concat(this.users[b].toPacketlist());if(this.subKeys)for(b=0;b<this.subKeys.length;b++)a.concat(this.subKeys[b].toPacketlist());return a},d.prototype.getKeyPacket=function(){return this.primaryKey},d.prototype.getSubkeyPackets=function(){var a=[];if(this.subKeys)for(var b=0;b<this.subKeys.length;b++)a.push(this.subKeys[b].subKey);return a},d.prototype.getAllKeyPackets=function(){return[this.getKeyPacket()].concat(this.getSubkeyPackets())},d.prototype.getKeyIds=function(){for(var a=[],b=this.getAllKeyPackets(),c=0;c<b.length;c++)a.push(b[c].getKeyId());return a},d.prototype.getPublicKeyPacket=function(a){return this.primaryKey.tag==p.packet.publicKey?e(this.getAllKeyPackets(),a):null},d.prototype.getPrivateKeyPacket=function(a){return this.primaryKey.tag==p.packet.secretKey?e(this.getAllKeyPackets(),a):null},d.prototype.getUserIds=function(){for(var a=[],b=0;b<this.users.length;b++)this.users[b].userId&&a.push(this.users[b].userId.write());return a},d.prototype.isPublic=function(){return this.primaryKey.tag==p.packet.publicKey},d.prototype.isPrivate=function(){return this.primaryKey.tag==p.packet.secretKey},d.prototype.toPublic=function(){for(var a,b=new o.List,c=this.toPacketlist(),e=0;e<c.length;e++)switch(c[e].tag){case p.packet.secretKey:a=c[e].writePublicKey();var f=new o.PublicKey;f.read(a),b.push(f);break;case p.packet.secretSubkey:a=c[e].writePublicKey();var g=new o.PublicSubkey;g.read(a),b.push(g);break;default:b.push(c[e])}return new d(b)},d.prototype.armor=function(){var a=this.isPublic()?p.armor.public_key:p.armor.private_key;return q.encode(a,this.toPacketlist().write())},d.prototype.getSigningKeyPacket=function(){if(this.isPublic())throw new Error("Need private key for signing");var a=this.getPrimaryUser();if(a&&g(this.primaryKey,a.selfCertificate))return this.primaryKey;if(this.subKeys)for(var b=0;b<this.subKeys.length;b++)if(this.subKeys[b].isValidSigningKey(this.primaryKey))return this.subKeys[b].subKey;return null},d.prototype.getPreferredHashAlgorithm=function(){var a=this.getPrimaryUser();return a&&a.selfCertificate.preferredHashAlgorithms?a.selfCertificate.preferredHashAlgorithms[0]:r.prefer_hash_algorithm},d.prototype.getEncryptionKeyPacket=function(){if(this.subKeys)for(var a=0;a<this.subKeys.length;a++)if(this.subKeys[a].isValidEncryptionKey(this.primaryKey))return this.subKeys[a].subKey;var b=this.getPrimaryUser();return b&&f(this.primaryKey,b.selfCertificate)?this.primaryKey:null},d.prototype.decrypt=function(a){if(!this.isPrivate())throw new Error("Nothing to decrypt in a public key");for(var b=this.getAllKeyPackets(),c=0;c<b.length;c++){var d=b[c].decrypt(a);if(!d)return!1}return!0},d.prototype.decryptKeyPacket=function(a,b){if(!this.isPrivate())throw new Error("Nothing to decrypt in a public key");for(var c=this.getAllKeyPackets(),d=0;d<c.length;d++)for(var e=c[d].getKeyId(),f=0;f<a.length;f++)if(e.equals(a[f])){var g=c[d].decrypt(b);if(!g)return!1}return!0},d.prototype.verifyPrimaryKey=function(){if(this.revocationSignature&&!this.revocationSignature.isExpired()&&(this.revocationSignature.verified||this.revocationSignature.verify(this.primaryKey,{key:this.primaryKey})))return p.keyStatus.revoked;if(3==this.primaryKey.version&&0!==this.primaryKey.expirationTimeV3&&Date.now()>this.primaryKey.created.getTime()+24*this.primaryKey.expirationTimeV3*3600*1e3)return p.keyStatus.expired;for(var a=!1,b=0;b<this.users.length;b++)this.users[b].userId&&this.users[b].selfCertifications&&(a=!0);if(!a)return p.keyStatus.no_self_cert;var c=this.getPrimaryUser();return c?4==this.primaryKey.version&&c.selfCertificate.keyNeverExpires===!1&&Date.now()>this.primaryKey.created.getTime()+1e3*c.selfCertificate.keyExpirationTime?p.keyStatus.expired:p.keyStatus.valid:p.keyStatus.invalid},d.prototype.getExpirationTime=function(){if(3==this.primaryKey.version)return h(this.primaryKey);if(4==this.primaryKey.version){var a=this.getPrimaryUser();return a?h(this.primaryKey,a.selfCertificate):null}},d.prototype.getPrimaryUser=function(){for(var a=[],b=0;b<this.users.length;b++)if(this.users[b].userId&&this.users[b].selfCertifications)for(var c=0;c<this.users[b].selfCertifications.length;c++)a.push({user:this.users[b],selfCertificate:this.users[b].selfCertifications[c]});a=a.sort(function(a,b){return a.isPrimaryUserID>b.isPrimaryUserID?-1:a.isPrimaryUserID<b.isPrimaryUserID?1:a.created>b.created?-1:a.created<b.created?1:0});for(var b=0;b<a.length;b++)if(a[b].user.isValidSelfCertificate(this.primaryKey,a[b].selfCertificate))return a[b];return null},d.prototype.update=function(a){var b=this;if(a.verifyPrimaryKey()!==p.keyStatus.invalid){if(this.primaryKey.getFingerprint()!==a.primaryKey.getFingerprint())throw new Error("Key update method: fingerprints of keys not equal");if(this.isPublic()&&a.isPrivate()){var c=(this.subKeys&&this.subKeys.length)===(a.subKeys&&a.subKeys.length)&&(!this.subKeys||this.subKeys.every(function(b){return a.subKeys.some(function(a){return b.subKey.getFingerprint()===a.subKey.getFingerprint()})}));if(!c)throw new Error("Cannot update public key with private key if subkey mismatch");this.primaryKey=a.primaryKey}this.revocationSignature||!a.revocationSignature||a.revocationSignature.isExpired()||!a.revocationSignature.verified&&!a.revocationSignature.verify(a.primaryKey,{key:a.primaryKey})||(this.revocationSignature=a.revocationSignature),i(a,this,"directSignatures"),a.users.forEach(function(a){for(var c=!1,d=0;d<b.users.length;d++)if(a.userId&&a.userId.userid===b.users[d].userId.userid||a.userAttribute&&a.userAttribute.equals(b.users[d].userAttribute)){b.users[d].update(a,b.primaryKey),c=!0;break}c||b.users.push(a)}),a.subKeys&&a.subKeys.forEach(function(a){for(var c=!1,d=0;d<b.subKeys.length;d++)if(a.subKey.getFingerprint()===b.subKeys[d].subKey.getFingerprint()){b.subKeys[d].update(a,b.primaryKey),c=!0;break}c||b.subKeys.push(a)})}},d.prototype.revoke=function(){},j.prototype.toPacketlist=function(){var a=new o.List;return a.push(this.userId||this.userAttribute),a.concat(this.revocationCertifications),a.concat(this.selfCertifications),a.concat(this.otherCertifications),a},j.prototype.isRevoked=function(a,b){if(this.revocationCertifications){var c=this;return this.revocationCertifications.some(function(d){return d.issuerKeyId.equals(a.issuerKeyId)&&!d.isExpired()&&(d.verified||d.verify(b,{userid:c.userId||c.userAttribute,key:b}))})}return!1},j.prototype.getValidSelfCertificate=function(a){if(!this.selfCertifications)return null;for(var b=this.selfCertifications.sort(function(a,b){return a=a.created,b=b.created,a>b?-1:b>a?1:0}),c=0;c<b.length;c++)if(this.isValidSelfCertificate(a,b[c]))return b[c];return null},j.prototype.isValidSelfCertificate=function(a,b){return this.isRevoked(b,a)?!1:b.isExpired()||!b.verified&&!b.verify(a,{userid:this.userId||this.userAttribute,key:a})?!1:!0},j.prototype.verify=function(a){if(!this.selfCertifications)return p.keyStatus.no_self_cert;for(var b,c=0;c<this.selfCertifications.length;c++)if(this.isRevoked(this.selfCertifications[c],a))b=p.keyStatus.revoked;else if(this.selfCertifications[c].verified||this.selfCertifications[c].verify(a,{userid:this.userId||this.userAttribute,key:a})){if(!this.selfCertifications[c].isExpired()){b=p.keyStatus.valid;break}b=p.keyStatus.expired}else b=p.keyStatus.invalid;return b},j.prototype.update=function(a,b){var c=this;i(a,this,"selfCertifications",function(a){return a.verified||a.verify(b,{userid:c.userId||c.userAttribute,key:b})}),i(a,this,"otherCertifications"),i(a,this,"revocationCertifications")},k.prototype.toPacketlist=function(){var a=new o.List;return a.push(this.subKey),a.push(this.revocationSignature),a.push(this.bindingSignature),a},k.prototype.isValidEncryptionKey=function(a){return this.verify(a)==p.keyStatus.valid&&f(this.subKey,this.bindingSignature)},k.prototype.isValidSigningKey=function(a){return this.verify(a)==p.keyStatus.valid&&g(this.subKey,this.bindingSignature)},k.prototype.verify=function(a){return this.revocationSignature&&!this.revocationSignature.isExpired()&&(this.revocationSignature.verified||this.revocationSignature.verify(a,{key:a,bind:this.subKey}))?p.keyStatus.revoked:3==this.subKey.version&&0!==this.subKey.expirationTimeV3&&Date.now()>this.subKey.created.getTime()+24*this.subKey.expirationTimeV3*3600*1e3?p.keyStatus.expired:this.bindingSignature?this.bindingSignature.isExpired()?p.keyStatus.expired:this.bindingSignature.verified||this.bindingSignature.verify(a,{key:a,bind:this.subKey})?4==this.subKey.version&&this.bindingSignature.keyNeverExpires===!1&&Date.now()>this.subKey.created.getTime()+1e3*this.bindingSignature.keyExpirationTime?p.keyStatus.expired:p.keyStatus.valid:p.keyStatus.invalid:p.keyStatus.invalid},k.prototype.getExpirationTime=function(){return h(this.subKey,this.bindingSignature)},k.prototype.update=function(a,b){if(a.verify(b)!==p.keyStatus.invalid){if(this.subKey.getFingerprint()!==a.subKey.getFingerprint())throw new Error("SubKey update method: fingerprints of subkeys not equal");this.subKey.tag===p.packet.publicSubkey&&a.subKey.tag===p.packet.secretSubkey&&(this.subKey=a.subKey),!this.bindingSignature&&a.bindingSignature&&(a.bindingSignature.verified||a.bindingSignature.verify(b,{key:b,bind:this.subKey}))&&(this.bindingSignature=a.bindingSignature),this.revocationSignature||!a.revocationSignature||a.revocationSignature.isExpired()||!a.revocationSignature.verified&&!a.revocationSignature.verify(b,{key:b,bind:this.subKey})||(this.revocationSignature=a.revocationSignature)}},c.Key=d,c.readArmored=l,c.generate=m,c.getPreferredSymAlgo=n},{"./config":4,"./encoding/armor.js":28,"./enums.js":30,"./packet":40,"./util":61}],33:[function(a,b){b.exports=a("./keyring.js"),b.exports.localstore=a("./localstore.js")
},{"./keyring.js":34,"./localstore.js":35}],34:[function(a,b){function c(b){this.storeHandler=b||new(a("./localstore.js")),this.publicKeys=new d(this.storeHandler.loadPublic()),this.privateKeys=new d(this.storeHandler.loadPrivate())}function d(a){this.keys=a}function e(a,b){a=a.toLowerCase();for(var c=b.getUserIds(),d=0;d<c.length;d++)if(keyEmail=c[d].split("<")[1].split(">")[0].trim().toLowerCase(),keyEmail==a)return!0;return!1}function f(a,b){return 16===a.length?a===b.getKeyId().toHex():a===b.getFingerprint()}{var g=(a("../enums.js"),a("../key.js"));a("../util.js")}b.exports=c,c.prototype.store=function(){this.storeHandler.storePublic(this.publicKeys.keys),this.storeHandler.storePrivate(this.privateKeys.keys)},c.prototype.clear=function(){this.publicKeys.keys=[],this.privateKeys.keys=[]},c.prototype.getKeysForId=function(a,b){var c=[];return c=c.concat(this.publicKeys.getForId(a,b)||[]),c=c.concat(this.privateKeys.getForId(a,b)||[]),c.length?c:null},c.prototype.removeKeysForId=function(a){var b=[];return b=b.concat(this.publicKeys.removeForId(a)||[]),b=b.concat(this.privateKeys.removeForId(a)||[]),b.length?b:null},c.prototype.getAllKeys=function(){return this.publicKeys.keys.concat(this.privateKeys.keys)},d.prototype.getForAddress=function(a){for(var b=[],c=0;c<this.keys.length;c++)e(a,this.keys[c])&&b.push(this.keys[c]);return b},d.prototype.getForId=function(a,b){for(var c=0;c<this.keys.length;c++){if(f(a,this.keys[c].primaryKey))return this.keys[c];if(b&&this.keys[c].subKeys)for(var d=0;d<this.keys[c].subKeys.length;d++)if(f(a,this.keys[c].subKeys[d].subKey))return this.keys[c]}return null},d.prototype.importKey=function(a){var b=g.readArmored(a),c=this;return b.keys.forEach(function(a){var b=a.primaryKey.getKeyId().toHex(),d=c.getForId(b);d?d.update(a):c.push(a)}),b.err?b.err:null},d.prototype.push=function(a){return this.keys.push(a)},d.prototype.removeForId=function(a){for(var b=0;b<this.keys.length;b++)if(f(a,this.keys[b].primaryKey))return this.keys.splice(b,1)[0];return null}},{"../enums.js":30,"../key.js":32,"../util.js":61,"./localstore.js":35}],35:[function(a,b){function c(b){b=b||"openpgp-",this.publicKeysItem=b+this.publicKeysItem,this.privateKeysItem=b+this.privateKeysItem,this.storage="undefined"!=typeof window&&window.localStorage?window.localStorage:new(a("node-localstorage").LocalStorage)(f.node_store)}function d(a,b){var c=JSON.parse(a.getItem(b)),d=[];if(null!==c&&0!==c.length)for(var e,f=0;f<c.length;f++)e=g.readArmored(c[f]),e.err?h.print_debug("Error reading armored key from keyring index: "+f):d.push(e.keys[0]);return d}function e(a,b,c){for(var d=[],e=0;e<c.length;e++)d.push(c[e].armor());a.setItem(b,JSON.stringify(d))}b.exports=c;var f=a("../config"),g=a("../key.js"),h=a("../util.js");c.prototype.publicKeysItem="public-keys",c.prototype.privateKeysItem="private-keys",c.prototype.loadPublic=function(){return d(this.storage,this.publicKeysItem)},c.prototype.loadPrivate=function(){return d(this.storage,this.privateKeysItem)},c.prototype.storePublic=function(a){e(this.storage,this.publicKeysItem,a)},c.prototype.storePrivate=function(a){e(this.storage,this.privateKeysItem,a)}},{"../config":4,"../key.js":32,"../util.js":61,"node-localstorage":!1}],36:[function(a,b,c){function d(a){return this instanceof d?void(this.packets=a||new h.List):new d(a)}function e(a){var b=j.decode(a).data,c=new h.List;c.read(b);var e=new d(c);return e}function f(a){var b=new h.Literal;b.setText(a);var c=new h.List;c.push(b);var e=new d(c);return e}function g(a){var b=new h.Literal;b.setBytes(a,i.read(i.literal,i.literal.binary));var c=new h.List;c.push(b);var e=new d(c);return e}var h=a("./packet"),i=a("./enums.js"),j=a("./encoding/armor.js"),k=a("./config"),l=a("./crypto"),m=a("./key.js");d.prototype.getEncryptionKeyIds=function(){var a=[],b=this.packets.filterByTag(i.packet.publicKeyEncryptedSessionKey);return b.forEach(function(b){a.push(b.publicKeyId)}),a},d.prototype.getSigningKeyIds=function(){var a=[],b=this.unwrapCompressed(),c=b.packets.filterByTag(i.packet.onePassSignature);if(c.forEach(function(b){a.push(b.signingKeyId)}),!a.length){var d=b.packets.filterByTag(i.packet.signature);d.forEach(function(b){a.push(b.issuerKeyId)})}return a},d.prototype.decrypt=function(a){var b=this.getEncryptionKeyIds();if(!b.length)return this;var c=a.getPrivateKeyPacket(b);if(!c.isDecrypted)throw new Error("Private key is not decrypted.");for(var e,f=this.packets.filterByTag(i.packet.publicKeyEncryptedSessionKey),g=0;g<f.length;g++)if(f[g].publicKeyId.equals(c.getKeyId())){e=f[g],e.decrypt(c);break}if(e){var j=this.packets.filterByTag(i.packet.symmetricallyEncrypted,i.packet.symEncryptedIntegrityProtected);if(0!==j.length){var k=j[0];k.decrypt(e.sessionKeyAlgorithm,e.sessionKey);var l=new d(k.packets);return k.packets=new h.List,l}}},d.prototype.getLiteralData=function(){var a=this.packets.findPacket(i.packet.literal);return a&&a.data||null},d.prototype.getText=function(){var a=this.packets.findPacket(i.packet.literal);return a?a.getText():null},d.prototype.encrypt=function(a){var b=new h.List,c=m.getPreferredSymAlgo(a),e=l.generateSessionKey(i.read(i.symmetric,c));a.forEach(function(a){var d=a.getEncryptionKeyPacket();if(!d)throw new Error("Could not find valid key packet for encryption in key "+a.primaryKey.getKeyId().toHex());var f=new h.PublicKeyEncryptedSessionKey;f.publicKeyId=d.getKeyId(),f.publicKeyAlgorithm=d.algorithm,f.sessionKey=e,f.sessionKeyAlgorithm=i.read(i.symmetric,c),f.encrypt(d),b.push(f)});var f;return f=k.integrity_protect?new h.SymEncryptedIntegrityProtected:new h.SymmetricallyEncrypted,f.packets=this.packets,f.encrypt(i.read(i.symmetric,c),e),b.push(f),f.packets=new h.List,new d(b)},d.prototype.sign=function(a){var b=new h.List,c=this.packets.findPacket(i.packet.literal);if(!c)throw new Error("No literal data packet to sign.");var e,f=i.write(i.literal,c.format),g=f==i.literal.binary?i.signature.binary:i.signature.text;for(e=0;e<a.length;e++){var j=new h.OnePassSignature;j.type=g,j.hashAlgorithm=k.prefer_hash_algorithm;var l=a[e].getSigningKeyPacket();if(!l)throw new Error("Could not find valid key packet for signing in key "+a[e].primaryKey.getKeyId().toHex());j.publicKeyAlgorithm=l.algorithm,j.signingKeyId=l.getKeyId(),b.push(j)}for(b.push(c),e=a.length-1;e>=0;e--){var m=new h.Signature;if(m.signatureType=g,m.hashAlgorithm=k.prefer_hash_algorithm,m.publicKeyAlgorithm=l.algorithm,!l.isDecrypted)throw new Error("Private key is not decrypted.");m.sign(l,c),b.push(m)}return new d(b)},d.prototype.verify=function(a){var b=[],c=this.unwrapCompressed(),d=c.packets.filterByTag(i.packet.literal);if(1!==d.length)throw new Error("Can only verify message with one literal data packet.");var e=c.packets.filterByTag(i.packet.signature);return a.forEach(function(a){for(var c=0;c<e.length;c++){var f=a.getPublicKeyPacket([e[c].issuerKeyId]);if(f){var g={};g.keyid=e[c].issuerKeyId,g.valid=e[c].verify(f,d[0]),b.push(g);break}}}),b},d.prototype.unwrapCompressed=function(){var a=this.packets.filterByTag(i.packet.compressed);return a.length?new d(a[0].packets):this},d.prototype.armor=function(){return j.encode(i.armor.message,this.packets.write())},c.Message=d,c.readArmored=e,c.fromText=f,c.fromBinary=g},{"./config":4,"./crypto":19,"./encoding/armor.js":28,"./enums.js":30,"./key.js":32,"./packet":40}],37:[function(a,b,c){function d(a){n=new t(a)}function e(a,b,c){return l(c)?void n.encryptMessage(a,b,c):m(function(){var c,d;return c=q.fromText(b),c=c.encrypt(a),d=o.encode(p.armor.message,c.packets.write())},c)}function f(a,b,c,d){return l(d)?void n.signAndEncryptMessage(a,b,c,d):m(function(){var d,e;return d=q.fromText(c),d=d.sign([b]),d=d.encrypt(a),e=o.encode(p.armor.message,d.packets.write())},d)}function g(a,b,c){return l(c)?void n.decryptMessage(a,b,c):m(function(){return b=b.decrypt(a),b.getText()},c)}function h(a,b,c,d){return l(d)?void n.decryptAndVerifyMessage(a,b,c,d):m(function(){var d={};return c=c.decrypt(a),d.text=c.getText(),d.text?(d.signatures=c.verify(b),d):null},d)}function i(a,b,c){return l(c)?void n.signClearMessage(a,b,c):m(function(){var c=new r.CleartextMessage(b);return c.sign(a),c.armor()},c)}function j(a,b,c){return l(c)?void n.verifyClearSignedMessage(a,b,c):m(function(){var c={};if(!(b instanceof r.CleartextMessage))throw new Error("Parameter [message] needs to be of type CleartextMessage.");return c.text=b.getText(),c.signatures=b.verify(a),c},c)}function k(a,b){return l(b)?void n.generateKeyPair(a,b):m(function(){var b={},c=s.generate(a);return b.key=c,b.privateKeyArmored=c.armor(),b.publicKeyArmored=c.toPublic().armor(),b},b)}function l(a){if("undefined"==typeof window||!window.Worker||"function"!=typeof a)return!1;if(!n)throw new Error("You need to set the worker path!");return!0}function m(a,b){var c;try{c=a()}catch(d){if(b)return void b(d);throw d}return b?void b(null,c):c}var n,o=a("./encoding/armor.js"),p=(a("./packet"),a("./enums.js")),q=(a("./config"),a("./message.js")),r=a("./cleartext.js"),s=a("./key.js"),t=a("./worker/async_proxy.js");c.initWorker=d,c.encryptMessage=e,c.signAndEncryptMessage=f,c.decryptMessage=g,c.decryptAndVerifyMessage=h,c.signClearMessage=i,c.verifyClearSignedMessage=j,c.generateKeyPair=k},{"./cleartext.js":1,"./config":4,"./encoding/armor.js":28,"./enums.js":30,"./key.js":32,"./message.js":36,"./packet":40,"./worker/async_proxy.js":62}],38:[function(a,b){function c(a){return a.substr(0,1).toUpperCase()+a.substr(1)}var d=a("../enums.js");b.exports={Compressed:a("./compressed.js"),SymEncryptedIntegrityProtected:a("./sym_encrypted_integrity_protected.js"),PublicKeyEncryptedSessionKey:a("./public_key_encrypted_session_key.js"),SymEncryptedSessionKey:a("./sym_encrypted_session_key.js"),Literal:a("./literal.js"),PublicKey:a("./public_key.js"),SymmetricallyEncrypted:a("./symmetrically_encrypted.js"),Marker:a("./marker.js"),PublicSubkey:a("./public_subkey.js"),UserAttribute:a("./user_attribute.js"),OnePassSignature:a("./one_pass_signature.js"),SecretKey:a("./secret_key.js"),Userid:a("./userid.js"),SecretSubkey:a("./secret_subkey.js"),Signature:a("./signature.js"),Trust:a("./trust.js"),newPacketFromTag:function(a){return new(this[c(a)])},fromStructuredClone:function(a){var b=d.read(d.packet,a.tag),c=this.newPacketFromTag(b);for(var e in a)a.hasOwnProperty(e)&&(c[e]=a[e]);return c.postCloneTypeFix&&c.postCloneTypeFix(),c}}},{"../enums.js":30,"./compressed.js":39,"./literal.js":41,"./marker.js":42,"./one_pass_signature.js":43,"./public_key.js":46,"./public_key_encrypted_session_key.js":47,"./public_subkey.js":48,"./secret_key.js":49,"./secret_subkey.js":50,"./signature.js":51,"./sym_encrypted_integrity_protected.js":52,"./sym_encrypted_session_key.js":53,"./symmetrically_encrypted.js":54,"./trust.js":55,"./user_attribute.js":56,"./userid.js":57}],39:[function(a,b){function c(){this.tag=d.packet.compressed,this.packets=null,this.algorithm="uncompressed",this.compressed=null}b.exports=c;var d=a("../enums.js"),e=a("../compression/jxg.js"),f=a("../encoding/base64.js");c.prototype.read=function(a){this.algorithm=d.read(d.compression,a.charCodeAt(0)),this.compressed=a.substr(1),this.decompress()},c.prototype.write=function(){return null===this.compressed&&this.compress(),String.fromCharCode(d.write(d.compression,this.algorithm))+this.compressed},c.prototype.decompress=function(){var a,b;switch(this.algorithm){case"uncompressed":a=this.compressed;break;case"zip":compData=this.compressed,b=f.encode(compData).replace(/\n/g,"");var c=new e.Util.Unzip(e.Util.Base64.decodeAsArray(b));a=unescape(c.deflate()[0][0]);break;case"zlib":var d=this.compressed.charCodeAt(0)%16;if(8==d){compData=this.compressed.substring(0,this.compressed.length-4),b=f.encode(compData).replace(/\n/g,""),a=e.decompress(b);break}throw new Error("Compression algorithm ZLIB only supports DEFLATE compression method.");case"bzip2":throw new Error("Compression algorithm BZip2 [BZ2] is not implemented.");default:throw new Error("Compression algorithm unknown :"+this.alogrithm)}this.packets.read(a)},c.prototype.compress=function(){switch(this.algorithm){case"uncompressed":this.compressed=this.packets.write();break;case"zip":throw new Error("Compression algorithm ZIP [RFC1951] is not implemented.");case"zlib":throw new Error("Compression algorithm ZLIB [RFC1950] is not implemented.");case"bzip2":throw new Error("Compression algorithm BZip2 [BZ2] is not implemented.");default:throw new Error("Compression algorithm unknown :"+this.type)}}},{"../compression/jxg.js":2,"../encoding/base64.js":29,"../enums.js":30}],40:[function(a,b){a("../enums.js");b.exports={List:a("./packetlist.js")};var c=a("./all_packets.js");for(var d in c)b.exports[d]=c[d]},{"../enums.js":30,"./all_packets.js":38,"./packetlist.js":45}],41:[function(a,b){function c(){this.tag=e.packet.literal,this.format="utf8",this.data="",this.date=new Date,this.filename="msg.txt"}b.exports=c;var d=a("../util.js"),e=a("../enums.js");c.prototype.setText=function(a){a=a.replace(/\r/g,"").replace(/\n/g,"\r\n"),this.data="utf8"==this.format?d.encode_utf8(a):a},c.prototype.getText=function(){var a=d.decode_utf8(this.data);return a.replace(/\r\n/g,"\n")},c.prototype.setBytes=function(a,b){this.format=b,this.data=a},c.prototype.getBytes=function(){return this.data},c.prototype.setFilename=function(a){this.filename=a},c.prototype.getFilename=function(){return this.filename},c.prototype.read=function(a){var b=e.read(e.literal,a.charCodeAt(0)),c=a.charCodeAt(1);this.filename=d.decode_utf8(a.substr(2,c)),this.date=d.readDate(a.substr(2+c,4));var f=a.substring(6+c);this.setBytes(f,b)},c.prototype.write=function(){var a=d.encode_utf8(this.filename),b=this.getBytes(),c="";return c+=String.fromCharCode(e.write(e.literal,this.format)),c+=String.fromCharCode(a.length),c+=a,c+=d.writeDate(this.date),c+=b}},{"../enums.js":30,"../util.js":61}],42:[function(a,b){function c(){this.tag=d.packet.marker}b.exports=c;var d=a("../enums.js");c.prototype.read=function(a){return 80==a.charCodeAt(0)&&71==a.charCodeAt(1)&&80==a.charCodeAt(2)?!0:!1}},{"../enums.js":30}],43:[function(a,b){function c(){this.tag=d.packet.onePassSignature,this.version=null,this.type=null,this.hashAlgorithm=null,this.publicKeyAlgorithm=null,this.signingKeyId=null,this.flags=null}b.exports=c;var d=a("../enums.js"),e=a("../type/keyid.js");c.prototype.read=function(a){var b=0;return this.version=a.charCodeAt(b++),this.type=d.read(d.signature,a.charCodeAt(b++)),this.hashAlgorithm=d.read(d.hash,a.charCodeAt(b++)),this.publicKeyAlgorithm=d.read(d.publicKey,a.charCodeAt(b++)),this.signingKeyId=new e,this.signingKeyId.read(a.substr(b)),b+=8,this.flags=a.charCodeAt(b++),this},c.prototype.write=function(){var a="";return a+=String.fromCharCode(3),a+=String.fromCharCode(d.write(d.signature,this.type)),a+=String.fromCharCode(d.write(d.hash,this.hashAlgorithm)),a+=String.fromCharCode(d.write(d.publicKey,this.publicKeyAlgorithm)),a+=this.signingKeyId.write(),a+=String.fromCharCode(this.flags)},c.prototype.postCloneTypeFix=function(){this.signingKeyId=e.fromClone(this.signingKeyId)}},{"../enums.js":30,"../type/keyid.js":58}],44:[function(a,b){var c=(a("../enums.js"),a("../util.js"));b.exports={readSimpleLength:function(a){var b,d=0,e=a.charCodeAt(0);return 192>e?(d=a.charCodeAt(0),b=1):255>e?(d=(a.charCodeAt(0)-192<<8)+a.charCodeAt(1)+192,b=2):255==e&&(d=c.readNumber(a.substr(1,4)),b=5),{len:d,offset:b}},writeSimpleLength:function(a){var b="";return 192>a?b+=String.fromCharCode(a):a>191&&8384>a?(b+=String.fromCharCode((a-192>>8)+192),b+=String.fromCharCode(a-192&255)):(b+=String.fromCharCode(255),b+=c.writeNumber(a,4)),b},writeHeader:function(a,b){var c="";return c+=String.fromCharCode(192|a),c+=this.writeSimpleLength(b)},writeOldHeader:function(a,b){var d="";return 256>b?(d+=String.fromCharCode(128|a<<2),d+=String.fromCharCode(b)):65536>b?(d+=String.fromCharCode(128|a<<2|1),d+=c.writeNumber(b,2)):(d+=String.fromCharCode(128|a<<2|2),d+=c.writeNumber(b,4)),d},read:function(a,b,d){if(null===a||a.length<=b||a.substring(b).length<2||0===(128&a.charCodeAt(b)))throw new Error("Error during parsing. This message / key is probably not containing a valid OpenPGP format.");var e,f=b,g=-1,h=-1;h=0,0!==(64&a.charCodeAt(f))&&(h=1);var i;h?g=63&a.charCodeAt(f):(g=(63&a.charCodeAt(f))>>2,i=3&a.charCodeAt(f)),f++;var j=null,k=-1;if(h)if(a.charCodeAt(f)<192)e=a.charCodeAt(f++),c.print_debug("1 byte length:"+e);else if(a.charCodeAt(f)>=192&&a.charCodeAt(f)<224)e=(a.charCodeAt(f++)-192<<8)+a.charCodeAt(f++)+192,c.print_debug("2 byte length:"+e);else if(a.charCodeAt(f)>223&&a.charCodeAt(f)<255){e=1<<(31&a.charCodeAt(f++)),c.print_debug("4 byte length:"+e);var l=f+e;j=a.substring(f,f+e);for(var m;;){if(a.charCodeAt(l)<192){m=a.charCodeAt(l++),e+=m,j+=a.substring(l,l+m),l+=m;break}if(a.charCodeAt(l)>=192&&a.charCodeAt(l)<224){m=(a.charCodeAt(l++)-192<<8)+a.charCodeAt(l++)+192,e+=m,j+=a.substring(l,l+m),l+=m;break}if(!(a.charCodeAt(l)>223&&a.charCodeAt(l)<255)){l++,m=a.charCodeAt(l++)<<24|a.charCodeAt(l++)<<16|a[l++].charCodeAt()<<8|a.charCodeAt(l++),j+=a.substring(l,l+m),e+=m,l+=m;break}m=1<<(31&a.charCodeAt(l++)),e+=m,j+=a.substring(l,l+m),l+=m}k=l-f}else f++,e=a.charCodeAt(f++)<<24|a.charCodeAt(f++)<<16|a.charCodeAt(f++)<<8|a.charCodeAt(f++);else switch(i){case 0:e=a.charCodeAt(f++);break;case 1:e=a.charCodeAt(f++)<<8|a.charCodeAt(f++);break;case 2:e=a.charCodeAt(f++)<<24|a.charCodeAt(f++)<<16|a.charCodeAt(f++)<<8|a.charCodeAt(f++);break;default:e=d}return-1==k&&(k=e),null===j&&(j=a.substring(f,f+k)),{tag:g,packet:j,offset:f+k}}}},{"../enums.js":30,"../util.js":61}],45:[function(a,b){function c(){this.length=0}b.exports=c;var d=a("./packet.js"),e=a("./all_packets.js"),f=a("../enums.js");c.prototype.read=function(a){for(var b=0;b<a.length;){var c=d.read(a,b,a.length-b);b=c.offset;var g=f.read(f.packet,c.tag),h=e.newPacketFromTag(g);this.push(h),h.read(c.packet)}},c.prototype.write=function(){for(var a="",b=0;b<this.length;b++){var c=this[b].write();a+=d.writeHeader(this[b].tag,c.length),a+=c}return a},c.prototype.push=function(a){a&&(a.packets=a.packets||new c,this[this.length]=a,this.length++)},c.prototype.filter=function(a){for(var b=new c,d=0;d<this.length;d++)a(this[d],d,this)&&b.push(this[d]);return b},c.prototype.filterByTag=function(){for(var a=Array.prototype.slice.call(arguments),b=new c,d=this,e=0;e<this.length;e++)a.some(function(a){return d[e].tag==a})&&b.push(this[e]);return b},c.prototype.forEach=function(a){for(var b=0;b<this.length;b++)a(this[b])},c.prototype.findPacket=function(a){var b=this.filterByTag(a);if(b.length)return b[0];for(var c=null,d=0;d<this.length;d++)if(this[d].packets.length&&(c=this[d].packets.findPacket(a)))return c;return null},c.prototype.indexOfTag=function(){for(var a=Array.prototype.slice.call(arguments),b=[],c=this,d=0;d<this.length;d++)a.some(function(a){return c[d].tag==a})&&b.push(d);return b},c.prototype.slice=function(a,b){b||(b=this.length);for(var d=new c,e=a;b>e;e++)d.push(this[e]);return d},c.prototype.concat=function(a){if(a)for(var b=0;b<a.length;b++)this.push(a[b])},b.exports.fromStructuredClone=function(a){for(var b=new c,d=0;d<a.length;d++)b.push(e.fromStructuredClone(a[d])),b[d].packets=0!==b[d].packets.length?this.fromStructuredClone(b[d].packets):new c;return b}},{"../enums.js":30,"./all_packets.js":38,"./packet.js":44}],46:[function(a,b){function c(){this.tag=g.packet.publicKey,this.version=4,this.created=new Date,this.mpi=[],this.algorithm="rsa_sign",this.expirationTimeV3=0,this.fingerprint=null,this.keyid=null}b.exports=c;var d=a("../util.js"),e=a("../type/mpi.js"),f=a("../type/keyid.js"),g=a("../enums.js"),h=a("../crypto");c.prototype.read=function(a){var b=0;if(this.version=a.charCodeAt(b++),3==this.version||4==this.version){this.created=d.readDate(a.substr(b,4)),b+=4,3==this.version&&(this.expirationTimeV3=d.readNumber(a.substr(b,2)),b+=2),this.algorithm=g.read(g.publicKey,a.charCodeAt(b++));var c=h.getPublicMpiCount(this.algorithm);this.mpi=[];for(var f=a.substr(b),i=0,j=0;c>j&&i<f.length;j++)if(this.mpi[j]=new e,i+=this.mpi[j].read(f.substr(i)),i>f.length)throw new Error("Error reading MPI @:"+i);return i+6}throw new Error("Version "+this.version+" of the key packet is unsupported.")},c.prototype.readPublicKey=c.prototype.read,c.prototype.write=function(){var a=String.fromCharCode(this.version);a+=d.writeDate(this.created),3==this.version&&(a+=d.writeNumber(this.expirationTimeV3,2)),a+=String.fromCharCode(g.write(g.publicKey,this.algorithm));for(var b=h.getPublicMpiCount(this.algorithm),c=0;b>c;c++)a+=this.mpi[c].write();return a},c.prototype.writePublicKey=c.prototype.write,c.prototype.writeOld=function(){var a=this.writePublicKey();return String.fromCharCode(153)+d.writeNumber(a.length,2)+a},c.prototype.getKeyId=function(){return this.keyid?this.keyid:(this.keyid=new f,4==this.version?this.keyid.read(d.hex2bin(this.getFingerprint()).substr(12,8)):3==this.version&&this.keyid.read(this.mpi[0].write().substr(-8)),this.keyid)},c.prototype.getFingerprint=function(){if(this.fingerprint)return this.fingerprint;var a="";if(4==this.version)a=this.writeOld(),this.fingerprint=h.hash.sha1(a);else if(3==this.version){for(var b=h.getPublicMpiCount(this.algorithm),c=0;b>c;c++)a+=this.mpi[c].toBytes();this.fingerprint=h.hash.md5(a)}return this.fingerprint=d.hexstrdump(this.fingerprint),this.fingerprint},c.prototype.getBitSize=function(){return 8*this.mpi[0].byteLength()},c.prototype.postCloneTypeFix=function(){for(var a=0;a<this.mpi.length;a++)this.mpi[a]=e.fromClone(this.mpi[a]);this.keyid&&(this.keyid=f.fromClone(this.keyid))}},{"../crypto":19,"../enums.js":30,"../type/keyid.js":58,"../type/mpi.js":59,"../util.js":61}],47:[function(a,b){function c(){this.tag=g.packet.publicKeyEncryptedSessionKey,this.version=3,this.publicKeyId=new d,this.publicKeyAlgorithm="rsa_encrypt",this.sessionKey=null,this.sessionKeyAlgorithm="aes256",this.encrypted=[]}b.exports=c;var d=a("../type/keyid.js"),e=a("../util.js"),f=a("../type/mpi.js"),g=a("../enums.js"),h=a("../crypto");c.prototype.read=function(a){this.version=a.charCodeAt(0),this.publicKeyId.read(a.substr(1)),this.publicKeyAlgorithm=g.read(g.publicKey,a.charCodeAt(9));var b=10,c=function(a){switch(a){case"rsa_encrypt":case"rsa_encrypt_sign":return 1;case"elgamal":return 2;default:throw new Error("Invalid algorithm.")}}(this.publicKeyAlgorithm);this.encrypted=[];for(var d=0;c>d;d++){var e=new f;b+=e.read(a.substr(b)),this.encrypted.push(e)}},c.prototype.write=function(){var a=String.fromCharCode(this.version);a+=this.publicKeyId.write(),a+=String.fromCharCode(g.write(g.publicKey,this.publicKeyAlgorithm));for(var b=0;b<this.encrypted.length;b++)a+=this.encrypted[b].write();return a},c.prototype.encrypt=function(a){var b=String.fromCharCode(g.write(g.symmetric,this.sessionKeyAlgorithm));b+=this.sessionKey;var c=e.calc_checksum(this.sessionKey);b+=e.writeNumber(c,2);var d=new f;d.fromBytes(h.pkcs1.eme.encode(b,a.mpi[0].byteLength())),this.encrypted=h.publicKeyEncrypt(this.publicKeyAlgorithm,a.mpi,d)},c.prototype.decrypt=function(a){var b=h.publicKeyDecrypt(this.publicKeyAlgorithm,a.mpi,this.encrypted).toBytes(),c=e.readNumber(b.substr(b.length-2)),d=h.pkcs1.eme.decode(b);if(a=d.substring(1,d.length-2),c!=e.calc_checksum(a))throw new Error("Checksum mismatch");this.sessionKey=a,this.sessionKeyAlgorithm=g.read(g.symmetric,d.charCodeAt(0))},c.prototype.postCloneTypeFix=function(){this.publicKeyId=d.fromClone(this.publicKeyId);for(var a=0;a<this.encrypted.length;a++)this.encrypted[a]=f.fromClone(this.encrypted[a])}},{"../crypto":19,"../enums.js":30,"../type/keyid.js":58,"../type/mpi.js":59,"../util.js":61}],48:[function(a,b){function c(){d.call(this),this.tag=e.packet.publicSubkey}b.exports=c;var d=a("./public_key.js"),e=a("../enums.js");c.prototype=new d,c.prototype.constructor=c},{"../enums.js":30,"./public_key.js":46}],49:[function(a,b){function c(){i.call(this),this.tag=j.packet.secretKey,this.encrypted=null,this.isDecrypted=!1}function d(a){return"sha1"==a?20:2}function e(a){return"sha1"==a?l.hash.sha1:function(a){return k.writeNumber(k.calc_checksum(a),2)}}function f(a,b,c){var f=d(a),g=e(a),h=b.substr(b.length-f);b=b.substr(0,b.length-f);var i=g(b);if(i!=h)return new Error("Hash mismatch.");for(var j=l.getPrivateMpiCount(c),k=0,n=[],o=0;j>o&&k<b.length;o++)n[o]=new m,k+=n[o].read(b.substr(k));return n}function g(a,b,c){for(var d="",f=l.getPublicMpiCount(b),g=f;g<c.length;g++)d+=c[g].write();return d+=e(a)(d)}function h(a,b,c){return a.produce_key(b,l.cipher[c].keySize)}b.exports=c;var i=a("./public_key.js"),j=a("../enums.js"),k=a("../util.js"),l=a("../crypto"),m=a("../type/mpi.js"),n=a("../type/s2k.js");c.prototype=new i,c.prototype.constructor=c,c.prototype.read=function(a){var b=this.readPublicKey(a);a=a.substr(b);var c=a.charCodeAt(0);if(c)this.encrypted=a;else{var d=f("mod",a.substr(1),this.algorithm);if(d instanceof Error)throw d;this.mpi=this.mpi.concat(d),this.isDecrypted=!0}},c.prototype.write=function(){var a=this.writePublicKey();return this.encrypted?a+=this.encrypted:(a+=String.fromCharCode(0),a+=g("mod",this.algorithm,this.mpi)),a},c.prototype.encrypt=function(a){var b=new n,c="aes256",d=g("sha1",this.algorithm,this.mpi),e=h(b,a,c),f=l.cipher[c].blockSize,i=l.random.getRandomBytes(f);this.encrypted="",this.encrypted+=String.fromCharCode(254),this.encrypted+=String.fromCharCode(j.write(j.symmetric,c)),this.encrypted+=b.write(),this.encrypted+=i,this.encrypted+=l.cfb.normalEncrypt(c,e,d,i)},c.prototype.decrypt=function(a){if(this.isDecrypted)return!0;var b,c,d=0,e=this.encrypted.charCodeAt(d++);if(255==e||254==e){b=this.encrypted.charCodeAt(d++),b=j.read(j.symmetric,b);var g=new n;d+=g.read(this.encrypted.substr(d)),c=h(g,a,b)}else b=e,b=j.read(j.symmetric,b),c=l.hash.md5(a);var i=this.encrypted.substr(d,l.cipher[b].blockSize);d+=i.length;var k,m=this.encrypted.substr(d);k=l.cfb.normalDecrypt(b,c,m,i);var o=254==e?"sha1":"mod",p=f(o,k,this.algorithm);return p instanceof Error?!1:(this.mpi=this.mpi.concat(p),this.isDecrypted=!0,!0)},c.prototype.generate=function(a){this.mpi=l.generateMpi(this.algorithm,a),this.isDecrypted=!0},c.prototype.clearPrivateMPIs=function(){this.mpi=this.mpi.slice(0,l.getPublicMpiCount(this.algorithm)),this.isDecrypted=!1}},{"../crypto":19,"../enums.js":30,"../type/mpi.js":59,"../type/s2k.js":60,"../util.js":61,"./public_key.js":46}],50:[function(a,b){function c(){d.call(this),this.tag=e.packet.secretSubkey}b.exports=c;var d=a("./secret_key.js"),e=a("../enums.js");c.prototype=new d,c.prototype.constructor=c},{"../enums.js":30,"./secret_key.js":49}],51:[function(a,b){function c(){this.tag=g.packet.signature,this.version=4,this.signatureType=null,this.hashAlgorithm=null,this.publicKeyAlgorithm=null,this.signatureData=null,this.unhashedSubpackets=null,this.signedHashValue=null,this.created=new Date,this.signatureExpirationTime=null,this.signatureNeverExpires=!0,this.exportable=null,this.trustLevel=null,this.trustAmount=null,this.regularExpression=null,this.revocable=null,this.keyExpirationTime=null,this.keyNeverExpires=null,this.preferredSymmetricAlgorithms=null,this.revocationKeyClass=null,this.revocationKeyAlgorithm=null,this.revocationKeyFingerprint=null,this.issuerKeyId=new j,this.notation=null,this.preferredHashAlgorithms=null,this.preferredCompressionAlgorithms=null,this.keyServerPreferences=null,this.preferredKeyServer=null,this.isPrimaryUserID=null,this.policyURI=null,this.keyFlags=null,this.signersUserId=null,this.reasonForRevocationFlag=null,this.reasonForRevocationString=null,this.features=null,this.signatureTargetPublicKeyAlgorithm=null,this.signatureTargetHashAlgorithm=null,this.signatureTargetHash=null,this.embeddedSignature=null,this.verified=!1}function d(a,b){var c="";return c+=f.writeSimpleLength(b.length+1),c+=String.fromCharCode(a),c+=b}b.exports=c;var e=a("../util.js"),f=a("./packet.js"),g=a("../enums.js"),h=a("../crypto"),i=a("../type/mpi.js"),j=a("../type/keyid.js");c.prototype.read=function(a){function b(a){for(var b=e.readNumber(a.substr(0,2)),c=2;2+b>c;){var d=f.readSimpleLength(a.substr(c));c+=d.offset,this.read_sub_packet(a.substr(c,d.len)),c+=d.len}return c}var c=0;switch(this.version=a.charCodeAt(c++),this.version){case 3:5!=a.charCodeAt(c++)&&e.print_debug("packet/signature.js\ninvalid One-octet length of following hashed material.MUST be 5. @:"+(c-1));var d=c;this.signatureType=a.charCodeAt(c++),this.created=e.readDate(a.substr(c,4)),c+=4,this.signatureData=a.substring(d,c),this.issuerKeyId.read(a.substring(c,c+8)),c+=8,this.publicKeyAlgorithm=a.charCodeAt(c++),this.hashAlgorithm=a.charCodeAt(c++);break;case 4:this.signatureType=a.charCodeAt(c++),this.publicKeyAlgorithm=a.charCodeAt(c++),this.hashAlgorithm=a.charCodeAt(c++),c+=b.call(this,a.substr(c),!0),this.signatureData=a.substr(0,c);var g=c;c+=b.call(this,a.substr(c),!1),this.unhashedSubpackets=a.substr(g,c-g);break;default:throw new Error("Version "+this.version+" of the signature is unsupported.")}this.signedHashValue=a.substr(c,2),c+=2,this.signature=a.substr(c)},c.prototype.write=function(){var a="";switch(this.version){case 3:a+=String.fromCharCode(3),a+=String.fromCharCode(5),a+=this.signatureData,a+=this.issuerKeyId.write(),a+=String.fromCharCode(this.publicKeyAlgorithm),a+=String.fromCharCode(this.hashAlgorithm);break;case 4:a+=this.signatureData,a+=this.unhashedSubpackets?this.unhashedSubpackets:e.writeNumber(0,2)}return a+=this.signedHashValue+this.signature},c.prototype.sign=function(a,b){var c=g.write(g.signature,this.signatureType),d=g.write(g.publicKey,this.publicKeyAlgorithm),e=g.write(g.hash,this.hashAlgorithm),f=String.fromCharCode(4);f+=String.fromCharCode(c),f+=String.fromCharCode(d),f+=String.fromCharCode(e),this.issuerKeyId=a.getKeyId(),f+=this.write_all_sub_packets(),this.signatureData=f;var i=this.calculateTrailer(),j=this.toSign(c,b)+this.signatureData+i,k=h.hash.digest(e,j);this.signedHashValue=k.substr(0,2),this.signature=h.signature.sign(e,d,a.mpi,j)},c.prototype.write_all_sub_packets=function(){var a=g.signatureSubpacket,b="",c="";if(null!==this.created&&(b+=d(a.signature_creation_time,e.writeDate(this.created))),null!==this.signatureExpirationTime&&(b+=d(a.signature_expiration_time,e.writeNumber(this.signatureExpirationTime,4))),null!==this.exportable&&(b+=d(a.exportable_certification,String.fromCharCode(this.exportable?1:0))),null!==this.trustLevel&&(c=String.fromCharCode(this.trustLevel)+String.fromCharCode(this.trustAmount),b+=d(a.trust_signature,c)),null!==this.regularExpression&&(b+=d(a.regular_expression,this.regularExpression)),null!==this.revocable&&(b+=d(a.revocable,String.fromCharCode(this.revocable?1:0))),null!==this.keyExpirationTime&&(b+=d(a.key_expiration_time,e.writeNumber(this.keyExpirationTime,4))),null!==this.preferredSymmetricAlgorithms&&(c=e.bin2str(this.preferredSymmetricAlgorithms),b+=d(a.preferred_symmetric_algorithms,c)),null!==this.revocationKeyClass&&(c=String.fromCharCode(this.revocationKeyClass),c+=String.fromCharCode(this.revocationKeyAlgorithm),c+=this.revocationKeyFingerprint,b+=d(a.revocation_key,c)),this.issuerKeyId.isNull()||(b+=d(a.issuer,this.issuerKeyId.write())),null!==this.notation)for(var f in this.notation)if(this.notation.hasOwnProperty(f)){var h=this.notation[f];c=String.fromCharCode(128),c+=String.fromCharCode(0),c+=String.fromCharCode(0),c+=String.fromCharCode(0),c+=e.writeNumber(f.length,2),c+=e.writeNumber(h.length,2),c+=f+h,b+=d(a.notation_data,c)}return null!==this.preferredHashAlgorithms&&(c=e.bin2str(this.preferredHashAlgorithms),b+=d(a.preferred_hash_algorithms,c)),null!==this.preferredCompressionAlgorithms&&(c=e.bin2str(this.preferredCompressionAlgorithms),b+=d(a.preferred_compression_algorithms,c)),null!==this.keyServerPreferences&&(c=e.bin2str(this.keyServerPreferences),b+=d(a.key_server_preferences,c)),null!==this.preferredKeyServer&&(b+=d(a.preferred_key_server,this.preferredKeyServer)),null!==this.isPrimaryUserID&&(b+=d(a.primary_user_id,String.fromCharCode(this.isPrimaryUserID?1:0))),null!==this.policyURI&&(b+=d(a.policy_uri,this.policyURI)),null!==this.keyFlags&&(c=e.bin2str(this.keyFlags),b+=d(a.key_flags,c)),null!==this.signersUserId&&(b+=d(a.signers_user_id,this.signersUserId)),null!==this.reasonForRevocationFlag&&(c=String.fromCharCode(this.reasonForRevocationFlag),c+=this.reasonForRevocationString,b+=d(a.reason_for_revocation,c)),null!==this.features&&(c=e.bin2str(this.features),b+=d(a.features,c)),null!==this.signatureTargetPublicKeyAlgorithm&&(c=String.fromCharCode(this.signatureTargetPublicKeyAlgorithm),c+=String.fromCharCode(this.signatureTargetHashAlgorithm),c+=this.signatureTargetHash,b+=d(a.signature_target,c)),null!==this.embeddedSignature&&(b+=d(a.embedded_signature,this.embeddedSignature.write())),b=e.writeNumber(b.length,2)+b
},c.prototype.read_sub_packet=function(a){function b(a,b){this[a]=[];for(var c=0;c<b.length;c++)this[a].push(b.charCodeAt(c))}var d,f=0,g=127&a.charCodeAt(f++);switch(g){case 2:this.created=e.readDate(a.substr(f));break;case 3:d=e.readNumber(a.substr(f)),this.signatureNeverExpires=0===d,this.signatureExpirationTime=d;break;case 4:this.exportable=1==a.charCodeAt(f++);break;case 5:this.trustLevel=a.charCodeAt(f++),this.trustAmount=a.charCodeAt(f++);break;case 6:this.regularExpression=a.substr(f);break;case 7:this.revocable=1==a.charCodeAt(f++);break;case 9:d=e.readNumber(a.substr(f)),this.keyExpirationTime=d,this.keyNeverExpires=0===d;break;case 11:b.call(this,"preferredSymmetricAlgorithms",a.substr(f));break;case 12:this.revocationKeyClass=a.charCodeAt(f++),this.revocationKeyAlgorithm=a.charCodeAt(f++),this.revocationKeyFingerprint=a.substr(f,20);break;case 16:this.issuerKeyId.read(a.substr(f));break;case 20:if(128==a.charCodeAt(f)){f+=4;var i=e.readNumber(a.substr(f,2));f+=2;var j=e.readNumber(a.substr(f,2));f+=2;var k=a.substr(f,i),l=a.substr(f+i,j);this.notation=this.notation||{},this.notation[k]=l}else e.print_debug("Unsupported notation flag "+a.charCodeAt(f));break;case 21:b.call(this,"preferredHashAlgorithms",a.substr(f));break;case 22:b.call(this,"preferredCompressionAlgorithms",a.substr(f));break;case 23:b.call(this,"keyServerPreferencess",a.substr(f));break;case 24:this.preferredKeyServer=a.substr(f);break;case 25:this.isPrimaryUserID=0!==a[f++];break;case 26:this.policyURI=a.substr(f);break;case 27:b.call(this,"keyFlags",a.substr(f));break;case 28:this.signersUserId+=a.substr(f);break;case 29:this.reasonForRevocationFlag=a.charCodeAt(f++),this.reasonForRevocationString=a.substr(f);break;case 30:b.call(this,"features",a.substr(f));break;case 31:this.signatureTargetPublicKeyAlgorithm=a.charCodeAt(f++),this.signatureTargetHashAlgorithm=a.charCodeAt(f++);var m=h.getHashByteLength(this.signatureTargetHashAlgorithm);this.signatureTargetHash=a.substr(f,m);break;case 32:this.embeddedSignature=new c,this.embeddedSignature.read(a.substr(f));break;default:e.print_debug("Unknown signature subpacket type "+g+" @:"+f)}},c.prototype.toSign=function(a,b){var c=g.signature;switch(a){case c.binary:case c.text:return b.getBytes();case c.standalone:return"";case c.cert_generic:case c.cert_persona:case c.cert_casual:case c.cert_positive:case c.cert_revocation:var d,f;if(void 0!==b.userid)f=180,d=b.userid;else{if(void 0===b.userattribute)throw new Error("Either a userid or userattribute packet needs to be supplied for certification.");f=209,d=b.userattribute}var h=d.write();if(4==this.version)return this.toSign(c.key,b)+String.fromCharCode(f)+e.writeNumber(h.length,4)+h;if(3==this.version)return this.toSign(c.key,b)+h;break;case c.subkey_binding:case c.subkey_revocation:case c.key_binding:return this.toSign(c.key,b)+this.toSign(c.key,{key:b.bind});case c.key:if(void 0===b.key)throw new Error("Key packet is required for this signature.");return b.key.writeOld();case c.key_revocation:return this.toSign(c.key,b);case c.timestamp:return"";case c.third_party:throw new Error("Not implemented");default:throw new Error("Unknown signature type.")}},c.prototype.calculateTrailer=function(){var a="";return 3==this.version?a:(a+=String.fromCharCode(4),a+=String.fromCharCode(255),a+=e.writeNumber(this.signatureData.length,4))},c.prototype.verify=function(a,b){var c=g.write(g.signature,this.signatureType),d=g.write(g.publicKey,this.publicKeyAlgorithm),e=g.write(g.hash,this.hashAlgorithm),f=this.toSign(c,b),j=this.calculateTrailer(),k=0;d>0&&4>d?k=1:17==d&&(k=2);for(var l=[],m=0,n=0;k>n;n++)l[n]=new i,m+=l[n].read(this.signature.substr(m));return this.verified=h.signature.verify(d,e,l,a.mpi,f+this.signatureData+j),this.verified},c.prototype.isExpired=function(){return this.signatureNeverExpires?!1:Date.now()>this.created.getTime()+1e3*this.signatureExpirationTime},c.prototype.postCloneTypeFix=function(){this.issuerKeyId=j.fromClone(this.issuerKeyId)}},{"../crypto":19,"../enums.js":30,"../type/keyid.js":58,"../type/mpi.js":59,"../util.js":61,"./packet.js":44}],52:[function(a,b){function c(){this.tag=e.packet.symEncryptedIntegrityProtected,this.encrypted=null,this.modification=!1,this.packets=null}b.exports=c;var d=(a("../util.js"),a("../crypto")),e=a("../enums.js");c.prototype.read=function(a){var b=a.charCodeAt(0);if(1!=b)throw new Error("Invalid packet version.");this.encrypted=a.substr(1)},c.prototype.write=function(){return String.fromCharCode(1)+this.encrypted},c.prototype.encrypt=function(a,b){var c=this.packets.write(),e=d.getPrefixRandom(a),f=e+e.charAt(e.length-2)+e.charAt(e.length-1),g=c;g+=String.fromCharCode(211),g+=String.fromCharCode(20),g+=d.hash.sha1(f+g),this.encrypted=d.cfb.encrypt(e,a,g,b,!1).substring(0,f.length+g.length)},c.prototype.decrypt=function(a,b){var c=d.cfb.decrypt(a,b,this.encrypted,!1);this.hash=d.hash.sha1(d.cfb.mdc(a,b,this.encrypted)+c.substring(0,c.length-20));var e=c.substr(c.length-20,20);if(this.hash!=e)throw new Error("Modification detected.");this.packets.read(c.substr(0,c.length-22))}},{"../crypto":19,"../enums.js":30,"../util.js":61}],53:[function(a,b){function c(){this.tag=e.packet.symEncryptedSessionKey,this.sessionKeyEncryptionAlgorithm=null,this.sessionKeyAlgorithm="aes256",this.encrypted=null,this.s2k=new d}var d=a("../type/s2k.js"),e=a("../enums.js"),f=a("../crypto");b.exports=c,c.prototype.read=function(a){this.version=a.charCodeAt(0);var b=e.read(e.symmetric,a.charCodeAt(1)),c=this.s2k.read(a.substr(2)),d=c+2;d<a.length?(this.encrypted=a.substr(d),this.sessionKeyEncryptionAlgorithm=b):this.sessionKeyAlgorithm=b},c.prototype.write=function(){var a=null===this.encrypted?this.sessionKeyAlgorithm:this.sessionKeyEncryptionAlgorithm,b=String.fromCharCode(this.version)+String.fromCharCode(e.write(e.symmetric,a))+this.s2k.write();return null!==this.encrypted&&(b+=this.encrypted),b},c.prototype.decrypt=function(a){var b=null!==this.sessionKeyEncryptionAlgorithm?this.sessionKeyEncryptionAlgorithm:this.sessionKeyAlgorithm,c=f.cipher[b].keySize,d=this.s2k.produce_key(a,c);if(null===this.encrypted)this.sessionKey=d;else{var g=f.cfb.decrypt(this.sessionKeyEncryptionAlgorithm,d,this.encrypted,!0);this.sessionKeyAlgorithm=e.read(e.symmetric,g[0].keyCodeAt()),this.sessionKey=g.substr(1)}},c.prototype.encrypt=function(a){var b=f.getKeyLength(this.sessionKeyEncryptionAlgorithm),c=this.s2k.produce_key(a,b),d=String.fromCharCode(e.write(e.symmetric,this.sessionKeyAlgorithm))+f.getRandomBytes(f.getKeyLength(this.sessionKeyAlgorithm));this.encrypted=f.cfb.encrypt(f.getPrefixRandom(this.sessionKeyEncryptionAlgorithm),this.sessionKeyEncryptionAlgorithm,c,d,!0)},c.prototype.postCloneTypeFix=function(){this.s2k=d.fromClone(this.s2k)}},{"../crypto":19,"../enums.js":30,"../type/s2k.js":60}],54:[function(a,b){function c(){this.tag=e.packet.symmetricallyEncrypted,this.encrypted=null,this.packets=null}b.exports=c;var d=a("../crypto"),e=a("../enums.js");c.prototype.read=function(a){this.encrypted=a},c.prototype.write=function(){return this.encrypted},c.prototype.decrypt=function(a,b){var c=d.cfb.decrypt(a,b,this.encrypted,!0);this.packets.read(c)},c.prototype.encrypt=function(a,b){var c=this.packets.write();this.encrypted=d.cfb.encrypt(d.getPrefixRandom(a),a,c,b,!0)}},{"../crypto":19,"../enums.js":30}],55:[function(a,b){function c(){this.tag=d.packet.trust}b.exports=c;var d=a("../enums.js");c.prototype.read=function(){}},{"../enums.js":30}],56:[function(a,b){function c(){this.tag=e.packet.userAttribute,this.attributes=[]}var d=(a("../util.js"),a("./packet.js")),e=a("../enums.js");b.exports=c,c.prototype.read=function(a){for(var b=0;b<a.length;){var c=d.readSimpleLength(a.substr(b));b+=c.offset,this.attributes.push(a.substr(b,c.len)),b+=c.len}},c.prototype.write=function(){for(var a="",b=0;b<this.attributes.length;b++)a+=d.writeSimpleLength(this.attributes[b].length),a+=this.attributes[b];return a},c.prototype.equals=function(a){return a&&a instanceof c?this.attributes.every(function(b,c){return b===a.attributes[c]}):!1}},{"../enums.js":30,"../util.js":61,"./packet.js":44}],57:[function(a,b){function c(){this.tag=e.packet.userid,this.userid=""}b.exports=c;var d=a("../util.js"),e=a("../enums.js");c.prototype.read=function(a){this.userid=d.decode_utf8(a)},c.prototype.write=function(){return d.encode_utf8(this.userid)}},{"../enums.js":30,"../util.js":61}],58:[function(a,b){function c(){this.bytes=""}b.exports=c;var d=a("../util.js");c.prototype.read=function(a){this.bytes=a.substr(0,8)},c.prototype.write=function(){return this.bytes},c.prototype.toHex=function(){return d.hexstrdump(this.bytes)},c.prototype.equals=function(a){return this.bytes==a.bytes},c.prototype.isNull=function(){return""===this.bytes},b.exports.mapToHex=function(a){return a.toHex()},b.exports.fromClone=function(a){var b=new c;return b.bytes=a.bytes,b}},{"../util.js":61}],59:[function(a,b){function c(){this.data=null}b.exports=c;var d=a("../crypto/public_key/jsbn.js"),e=a("../util.js");c.prototype.read=function(a){var b=a.charCodeAt(0)<<8|a.charCodeAt(1),c=Math.ceil(b/8),d=a.substr(2,c);return this.fromBytes(d),2+c},c.prototype.fromBytes=function(a){this.data=new d(e.hexstrdump(a),16)},c.prototype.toBytes=function(){return this.write().substr(2)},c.prototype.byteLength=function(){return this.toBytes().length},c.prototype.write=function(){return this.data.toMPI()},c.prototype.toBigInteger=function(){return this.data.clone()},c.prototype.fromBigInteger=function(a){this.data=a.clone()},b.exports.fromClone=function(a){a.data.copyTo=d.prototype.copyTo;var b=new d;a.data.copyTo(b);var e=new c;return e.data=b,e}},{"../crypto/public_key/jsbn.js":24,"../util.js":61}],60:[function(a,b){function c(){this.algorithm="sha256",this.type="iterated",this.c=96,this.salt=f.random.getRandomBytes(8)}b.exports=c;var d=a("../enums.js"),e=a("../util.js"),f=a("../crypto");c.prototype.get_count=function(){var a=6;return 16+(15&this.c)<<(this.c>>4)+a},c.prototype.read=function(a){var b=0;switch(this.type=d.read(d.s2k,a.charCodeAt(b++)),this.algorithm=d.read(d.hash,a.charCodeAt(b++)),this.type){case"simple":break;case"salted":this.salt=a.substr(b,8),b+=8;break;case"iterated":this.salt=a.substr(b,8),b+=8,this.c=a.charCodeAt(b++);break;case"gnu":if("GNU"!=a.substr(b,3))throw new Error("Unknown s2k type.");b+=3;var c=1e3+a.charCodeAt(b++);if(1001!=c)throw new Error("Unknown s2k gnu protection mode.");this.type=c;break;default:throw new Error("Unknown s2k type.")}return b},c.prototype.write=function(){var a=String.fromCharCode(d.write(d.s2k,this.type));switch(a+=String.fromCharCode(d.write(d.hash,this.algorithm)),this.type){case"simple":break;case"salted":a+=this.salt;break;case"iterated":a+=this.salt,a+=String.fromCharCode(this.c)}return a},c.prototype.produce_key=function(a,b){function c(b,c){var e=d.write(d.hash,c.algorithm);switch(c.type){case"simple":return f.hash.digest(e,b+a);case"salted":return f.hash.digest(e,b+c.salt+a);case"iterated":var g=[],h=c.get_count();for(data=c.salt+a;g.length*data.length<h;)g.push(data);return g=g.join(""),g.length>h&&(g=g.substr(0,h)),f.hash.digest(e,b+g)}}a=e.encode_utf8(a);for(var g="",h="";g.length<=b;)g+=c(h,this),h+=String.fromCharCode(0);return g.substr(0,b)},b.exports.fromClone=function(a){var b=new c;return this.algorithm=a.algorithm,this.type=a.type,this.c=a.c,this.salt=a.salt,b}},{"../crypto":19,"../enums.js":30,"../util.js":61}],61:[function(a,b){var c=a("./config");b.exports={readNumber:function(a){for(var b=0,c=0;c<a.length;c++)b<<=8,b+=a.charCodeAt(c);return b},writeNumber:function(a,b){for(var c="",d=0;b>d;d++)c+=String.fromCharCode(a>>8*(b-d-1)&255);return c},readDate:function(a){var b=this.readNumber(a),c=new Date;return c.setTime(1e3*b),c},writeDate:function(a){var b=Math.round(a.getTime()/1e3);return this.writeNumber(b,4)},emailRegEx:/^[+a-zA-Z0-9_.-]+@([a-zA-Z0-9-]+\.)+[a-zA-Z0-9]{2,6}$/,hexdump:function(a){for(var b,c=[],d=a.length,e=0,f=0;d>e;){for(b=a.charCodeAt(e++).toString(16);b.length<2;)b="0"+b;c.push(" "+b),f++,f%32===0&&c.push("\n           ")}return c.join("")},hexstrdump:function(a){if(null===a)return"";for(var b,c=[],d=a.length,e=0;d>e;){for(b=a.charCodeAt(e++).toString(16);b.length<2;)b="0"+b;c.push(""+b)}return c.join("")},hex2bin:function(a){for(var b="",c=0;c<a.length;c+=2)b+=String.fromCharCode(parseInt(a.substr(c,2),16));return b},hexidump:function(a){for(var b,c=[],d=a.length,e=0;d>e;){for(b=a[e++].toString(16);b.length<2;)b="0"+b;c.push(""+b)}return c.join("")},encode_utf8:function(a){return unescape(encodeURIComponent(a))},decode_utf8:function(a){if("string"!=typeof a)throw new Error('Parameter "utf8" is not of type string');try{return decodeURIComponent(escape(a))}catch(b){return a}},bin2str:function(a){for(var b=[],c=0;c<a.length;c++)b[c]=String.fromCharCode(a[c]);return b.join("")},str2bin:function(a){for(var b=[],c=0;c<a.length;c++)b[c]=a.charCodeAt(c);return b},str2Uint8Array:function(a){for(var b=new Uint8Array(a.length),c=0;c<a.length;c++)b[c]=a.charCodeAt(c);return b},Uint8Array2str:function(a){for(var b="",c=0;c<a.length;c++)b+=String.fromCharCode(a[c]);return b},calc_checksum:function(a){for(var b={s:0,add:function(a){this.s=(this.s+a)%65536}},c=0;c<a.length;c++)b.add(a.charCodeAt(c));return b.s},print_debug:function(a){c.debug&&console.log(a)},print_debug_hexstr_dump:function(a,b){c.debug&&(a+=this.hexstrdump(b),console.log(a))},getLeftNBits:function(a,b){var c=b%8;if(0===c)return a.substring(0,b/8);var d=(b-c)/8+1,e=a.substring(0,d);return this.shiftRight(e,8-c)},shiftRight:function(a,b){var c=util.str2bin(a);if(b%8===0)return a;for(var d=c.length-1;d>=0;d--)c[d]>>=b%8,d>0&&(c[d]|=c[d-1]<<8-b%8&255);return util.bin2str(c)},get_hashAlgorithmString:function(a){switch(a){case 1:return"MD5";case 2:return"SHA1";case 3:return"RIPEMD160";case 8:return"SHA256";case 9:return"SHA384";case 10:return"SHA512";case 11:return"SHA224"}return"unknown"}}},{"./config":4}],62:[function(a,b){function c(a){this.worker=new Worker(a||"openpgp.worker.js"),this.worker.onmessage=this.onMessage.bind(this),this.worker.onerror=function(a){throw new Error("Unhandled error in openpgp worker: "+a.message+" ("+a.filename+":"+a.lineno+")")},this.seedRandom(h),this.tasks=[]}var d=a("../crypto"),e=a("../packet"),f=a("../key.js"),g=a("../type/keyid.js"),h=(a("../enums.js"),5e4),i=2e4;c.prototype.onMessage=function(a){var b=a.data;switch(b.event){case"method-return":this.tasks.shift()(b.err?new Error(b.err):null,b.data);break;case"request-seed":this.seedRandom(i);break;default:throw new Error("Unknown Worker Event.")}},c.prototype.seedRandom=function(a){var b=this.getRandomBuffer(a);this.worker.postMessage({event:"seed-random",buf:b})},c.prototype.getRandomBuffer=function(a){if(!a)return null;var b=new Uint8Array(a);return d.random.getRandomValues(b),b},c.prototype.terminate=function(){this.worker.terminate()},c.prototype.encryptMessage=function(a,b,c){a=a.map(function(a){return a.toPacketlist()}),this.worker.postMessage({event:"encrypt-message",keys:a,text:b}),this.tasks.push(c)},c.prototype.signAndEncryptMessage=function(a,b,c,d){a=a.map(function(a){return a.toPacketlist()}),b=b.toPacketlist(),this.worker.postMessage({event:"sign-and-encrypt-message",publicKeys:a,privateKey:b,text:c}),this.tasks.push(d)},c.prototype.decryptMessage=function(a,b,c){a=a.toPacketlist(),this.worker.postMessage({event:"decrypt-message",privateKey:a,message:b}),this.tasks.push(c)},c.prototype.decryptAndVerifyMessage=function(a,b,c,d){a=a.toPacketlist(),b=b.map(function(a){return a.toPacketlist()}),this.worker.postMessage({event:"decrypt-and-verify-message",privateKey:a,publicKeys:b,message:c}),this.tasks.push(function(a,b){b&&(b.signatures=b.signatures.map(function(a){return a.keyid=g.fromClone(a.keyid),a})),d(a,b)})},c.prototype.signClearMessage=function(a,b,c){a=a.map(function(a){return a.toPacketlist()}),this.worker.postMessage({event:"sign-clear-message",privateKeys:a,text:b}),this.tasks.push(c)},c.prototype.verifyClearSignedMessage=function(a,b,c){a=a.map(function(a){return a.toPacketlist()}),this.worker.postMessage({event:"verify-clear-signed-message",publicKeys:a,message:b}),this.tasks.push(function(a,b){b&&(b.signatures=b.signatures.map(function(a){return a.keyid=g.fromClone(a.keyid),a})),c(a,b)})},c.prototype.generateKeyPair=function(a,b){this.worker.postMessage({event:"generate-key-pair",options:a}),this.tasks.push(function(a,c){if(c){var d=e.List.fromStructuredClone(c.key);c.key=new f.Key(d)}b(a,c)})},c.prototype.decryptKey=function(a,b,c){a=a.toPacketlist(),this.worker.postMessage({event:"decrypt-key",privateKey:a,password:b}),this.tasks.push(function(a,b){if(b){var d=e.List.fromStructuredClone(b);b=new f.Key(d)}c(a,b)})},c.prototype.decryptKeyPacket=function(a,b,c,d){a=a.toPacketlist(),this.worker.postMessage({event:"decrypt-key-packet",privateKey:a,keyIds:b,password:c}),this.tasks.push(function(a,b){if(b){var c=e.List.fromStructuredClone(b);b=new f.Key(c)}d(a,b)})},b.exports=c},{"../crypto":19,"../enums.js":30,"../key.js":32,"../packet":40,"../type/keyid.js":58}]},{},[31])(31)});var ownUnityCrypto = {
    "pubkeys": [],
    "afterLoadKeysCallback": function() {},
    "loadPubKeys": function(callback) {
        if(typeof(callback)=="undefined") callback = function() {};
        this.afterLoadKeysCallback = callback;
        $.ajax({
            "url": filename+"?action=loadPubKeys",
            "dataType": "json",
            "success": function(data) {
                ownUnityCrypto.pubkeys = data.pubkeys;
                ownUnityCrypto.afterLoadKeysCallback();
            }
        });
    },
    "passphrase": "",
    "encrypt": function(msg) {

    },
    "decrypt": function(msg) {

    },

    "decryptMessages": function() {
            if($('.pgptext').length==0) return;

            if(window.location != window.parent.location || readStore("myPassphrase")=="default") {
            if(parent.ownUnityCrypto.passphrase=="") {
                if(readStore("myPassphrase")=="default") parent.ownUnityCrypto.passphrase = "ownunity";
                if(parent.ownUnityCrypto.passphrase=="") {
                    parent.ownUnityCrypto.passphrase = prompt("Passphrase");
                }
                if(parent.ownUnityCrypto.passphrase===null) return;
                if(parent.ownUnityCrypto.passphrase=="") parent.ownUnityCrypto.passphrase = "ownunity";
            }

            $('.pgptext').each(function() {

                var encrypted = $(this).html();
        //console.log(encrypted );
                var key = readStore('myPrivateKey');
                //console.log(key );
                var privKeys = openpgp.key.readArmored(key);

                var privKey = privKeys.keys[0];
                //console.log(parent.ownUnityCrypto.passphrase);
                var success = privKey.decrypt(parent.ownUnityCrypto.passphrase);
                var msg = openpgp.message.readArmored(encrypted);
                var decrypted = openpgp.decryptMessage(privKey, msg);
                //console.log(decrypted);
                if(decrypted) {
                    $(this).html(decrypted);
                }
            });


        } else {
            if(readStore("myPassphrase")!="default") {
                if($('.pgptext').length>0) {
                    var L = window.location.href+"";
                    if(L.indexOf("?")==-1) L += "?"; else L += "&";
                    window.location.href = L+"crypto=1";
                    return;
                }
            }
        }
    }
};<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="easy to install private community system">
    <meta name="author" content="Aresch Yavari">
    <meta name="version" content="1.20140603140625">
    <title><?= getConfigValue("pagetitle", "My own hosted community");?></title>


    <script src="<?= FILENAME;?>?RES=resources/js/crypto.js"></script>
    <script src="<?= FILENAME;?>?RES=resources/js/jquery.js"></script>
    <script>
        var filename = "<?= FILENAME;?>";
        $(function() {
            ownUnityCrypto.loadPubKeys();
        });

    </script>

</head>

<frameset rows="100%" border="0" frameborder="0">
    <frame src="<?= FILENAME;?>?<?= str_replace("crypto=", "inner=", $_SERVER["QUERY_STRING"]);?>" />
</frameset>


</html>ÿ6  ¡5                    LP                      ‰˜                  ( G L Y P H I C O N S   H a l f l i n g s    R e g u l a r   x V e r s i o n   1 . 0 0 1 ; P S   0 0 1 . 0 0 1 ; h o t c o n v   1 . 0 . 7 0 ; m a k e o t f . l i b 2 . 5 . 5 8 3 2 9   8 G L Y P H I C O N S   H a l f l i n g s   R e g u l a r     BSGP                 wŒ 5s 5y -ÆÍéŠÍÒÕŸ(tÛŠKûÁDâÂ'PîMî
‡ BÃj¶ôübeJ2ccÅÇLF1+†WEeuJôÕe•~m•©%‰çÇW¾Ù*ÖIzIÂ€ásLœ„	x

æö4ÛxÑ—PSŸç-Uu×Tí²˜E ÕFª“œ?Í¬	Ô¯Ò4ÛÊ¨gå•q ÙeÒ$ö{í-+1u«„±©{šá¿Sóğ"!EÜB&/˜†äL¼³öEØÀKîÒ7fí¦Ò®ı|ö'ğ=şÿ›j¼Ôp¡ÿEºA_BŒ@Üş*·?~Œêëã9Â&„Iè„Ùvèğ«@eı—râ’>Mo<†LXéáÌ%Ö×üa>Ë’ ‘¦jp?Pú;ÿ_ıà®«iÆ¶Ç <î³}LbXÉUe1ÀL	€!Ñ;„ö®¦¨	Då^%‡ Š	PP‚”A‚@‘üµ5ã©óˆKa€lÓ´ÄŸ§a!œ£å‡åò|Zh¬®FFIò0”EüF"Ä‘ „”œ¦š'MÕ¸¹9°@…)Œ1A£¤ÓÏÄÈ¶øŠÑ€‡‘ã<ÂÍéÎ,â@¬ëA†	pA‡¨CÏ²Ì\‘.Û mÈ¤IxŠ”±›sS
"Ârör}‹nNŠÁãğÕ~lQo¦`>tŒ™Üóé°…¶Ã×¶¶ÔéîÒ5Óöı€X`
Â­g9KŠªŞ ògö]ás ½zT0%jŠ‡kTËãÖgÌ™0"µYVg‘€@¬°)šÜ>aFc !t zÎ ’à¿=VÁ²k¨âÔ@op^vÜ?ùJÀĞ=fà¬í0[7üß@¿›}œtì³\ç¹Éñ}ÅÌv\|f{öy†?=é1"f˜şù´x‡´ÏHxxşiô8‹k™PYÀ8œö~Ãšæä§ç7@æ(:3vìô°¬¨XÇX+(ë¤µ±ü¢FjÖç19Š„’'(m¨}ºÊºœe•İÓC¦§Å’£=‡œ&Êúóe
y²…EĞÉB ƒ(™2²àôE?xv5¤ki{P"ÒIG kXLPØÅ€Eğ>GÔ[Á|q!œcæ+o0º
a‡‹~§\®´;šñ!ê!ê+¦¦uX1ˆH£"ª¤—¶¶š¤ä¦ U94%‚Ñ`³§Œº)ï§y1‚*İÙÕÖ/Û:>\gÇ@¥<H‘´HÜ¶W¨Ö~}©ÄóŸ:Şãhf«¢ölZ×4‘Y¯°áÎ–Åä?zUàëù«ÕAUq:à>xîñŒµZäã.]¼“A¤Á·œÒÕK¶ò/
«{[8Æ]©bı.q~˜öš2—DÜoR”zK²ÊoGlĞì¤{Eı¸¡„9ï›	×E¨ZRÂ}3QO•ãĞ•6ÁÀC–).¹R„Äú%Oy¼ó¡¿r
È#ŸŠQ½ŒV?l ËGÛIğòVÊ	T.šRÒğèt†Ş‹ÑïÄmİ¯ömbÎv ‰vtOn#<œuï9ÇŞp3:¯³ß)Ø% ®Tz´õîëc‡‚BWœc©‡£†Œ³
cFvEZÓOäIkÏÊMûY’uj%[Ü(0i˜‘>)»aQy}Ævb2åı'*ƒb†G½è£´~	—5òs«'ü¡ó·éÃ¥¶vx8)#7JÌæÿç†@IÆ š˜`S(E Ë–Bè«!$<÷ sm±¡¶{Ñº Áîæ×û!`xëš¢"ZaB3”ı¼x´´WœÉ[×ƒ˜²øjÆƒhğ%ptKÀ[³dµ“±C$UC@\)ÖA~qË…ÉÃÑÎ/æ@znæ2e?9¬ÚŸ‚NÌØ¸Q²91¯ŠBaïƒì•aØ0æ#¹Ú˜.3ÃàÃš—‚Øşÿ•½¿²+z%µÔ†¾ÃÆEÚ”s¬AíU’|hà±@£ôØ¥s5S8*v0Dè×âó®UøYmãïI½€ïsâãå:ŠĞDt¸ÄXrµ/Šì©0[LNû	ûğŠI»øâÄŠ$aŠ‹¸pp™’†MNÊ¢7›ÉtDŞI'alˆY"4¤X	îr¼[†{_£MW½ X1Ú<ª”c®ô)L|Š»–S ˆQ™’Í–˜V ‰ÈF£æFËştu¼Ï4dŒŸr-Mv«Mºu¤Sú¢8•91c˜˜Õ
Ã’`ÌAIÿ#\¯*p•XÚ×¼ª)–¢§4%>ÓÄçbm]„5ˆŞ5N=G­€’ #ûr¨—¿96#R–©ÿ
YiŒ¹£+f 'F58@éxş4í ıK€‡ª.§µ- 2¯¼:ıQ	4<y
Êƒ9i´ÔÈV3:İó<7>Ü¥5Â2ÿXC°7`M-šjÈ
 >;~ĞLıÂ5œn”H­šHF4öKß¹ºY‰p¢ÿrÒwsã4ˆİŒF¦Ûä•Fø:î0Ó¢œL[f|„Ô†©âÎJšÀ¢¦«Ö :h©$¨I¢ry(*‚u&~Kÿe“`Ç±§73)„Wä$ %Ø¶ö ¢Àpâ@Cõ\¤r|SÈ"ÙºE"¥«Ì0iÌrOÿDh¢md#$4Eù¥@J©¦"Ñ÷ÈæTÁ(ô“L™9Ù´ÒNÀCxM¼‚¤à—º}D¸-£BIá„rx¬Í)\)×gU•J…iûšŒÅuáOÏ1T>¸|æ>J‡ÕDè–bs -Ó@´EŸ@Ş]”8ğå¹ÄÖÂàBŸğ´¼…‹Ú–ıÈ®X…_3áU
÷dD„Ç“_± ˜¬‘Ù”t£Ù ×Kü4n,ÀD²ÎÎù"6°¾@…šë‡‚– ­FËm­`Y’Ê6ï'3Â¨––2lápM±`<­hWâ"­’PÎ@Ìò¡¢	(J(ü^Ÿ3´°Oëy¿Lq½Ãè‘](D”¤R¾rc¯{e ‚¢Î\3P!}"èùÇ¼Vt„)w¸™®4?­u~•DÁğŒ‰U2]›0ĞOVFñ6-§ÆM§)¨’ÔeYğl.HÖÄRÏˆ_ A†ùJ½Ê»Yİå’“oIFäÒZt:¿Ù¬‹ºÂ·ú¹‡ş˜h"Bâ¥íÀËê&í¶ü"ÿnyó˜‹‰AĞ-ì îx#Õ~6–Õ·ŞÍhX©¹œˆ¥{9Aé"Ü5io¬rjÍ{¬H8ç)¥ÜIZ^Ğ¼ÂÄ³@.@‚Î“F.Àá—RòKŒAD Ÿdûô Q„,Âƒ†€a[q‚g@÷	¸D_¥š…#E u€)èR1•åB]F
±uÄVˆ²èÑG{§éø”Ü°‚ŸxĞ]Îµ(@m¢l¦­f‚£Üî¬P­:ˆHøµô s¬,i”û¥í“Q>2¿"d[(ÉÃ ñü/ŠuÒ¼bS²LË³ı.‰"•:®A”U@Ÿp¥ÛÖ½RüE•”Ä{ÁŒBèH¢™ŠÆ¥ò!IµÕa¨-w"À)w% âìáœ™™¯ªÕrw«¿Í³¼rÀ6§ñ!©1£Y§à Ë˜LT’pŠxááÃrôš8]Æ>ÒˆŠÆS<hôyjôCá€£•]„ÆK Å€Ä¬&\vëC`b£#a¦‘Ï[]ñIîºØF\kàµ¥ŒX]©¯î Ÿ`àeØMXídTÆ2ËÕf®ËÕ}…›™“^nËNƒÃ$ˆ·@ĞÙšªÈzÅÔR:Óq"SÛÑY‡0œJ°/…üÕ©Y3½Ä™ÀÅ®Ù#¡qåÓYĞs÷¶ø¶|HJÜ¢„Ç”0ÍÙ‚ÀvÃ8Ó4„Ã‘¦íQvìy©—PÑ¡Cµ;ôÎœ{x8`3GÌÚfNäúïƒ[V¹ß¾P¢	ĞUÊ«]]ÚJO’‰6°Úrº_t~oÍŞ>"#^”m²ê¤¾ìïB\Çõi‰&UÎ=ŠQ˜1ÄŸ,? t&r,@lé5AÛŒÑJ´­6\²–¢wØæx7I™\`nÉsKpÊ´ıİ€×¼î2Lª á'„M3ˆ‡–ËKi[v&V‚tF’THè
rxøœÀTUYÒEe¯}ª±Ø$WÑ=îX/¥}%¤KÃDï©*ê¥:ãD =Kª_¬Àä†ã`äDÂ#ó[ö¹JÛQ‚l£$	˜ÓzÔ s/¾À«Úq’ÀJº„äI+¹XñÏ,˜$	°gÎf`æÃ%²Qü6Ö"@tšµN"9()y.B™Œ LÏì*ˆ6Üo^4í4Y‚‰e-Ò«‹—z_º|¾•~înM7ë¾ÚŒ¼TgŸ
§`îÎ‹dËkbrãØÈ÷öÃ¬¹f”vÄø/$}%)Ì-Ä—¼R
à&H|&‚X’$	;cŒ+s-ŒA«Ğˆ>>;_»?-§1hG*Èæ@ë UrÈ³àËŠ73ã6h)»¥óùé— O¶‹Û .ÉM2Ğ>j j=BÂ¦õ1²Mè XB`LâÑ°>xéì¬d™´îbN	·XËK³‹n,à½«W{í“™’ê;Õß´ªp[j)^òî`z‰ÂWIÖ®•Ëøp¸hä4?Í¿¹²eS¬ÚƒÀ¬4+v{À1¢ˆX’PBXÿ êq±ÎEsG–…‡p?g±á¶i{Ó˜ıM€tøÉü’ê›–™–¤­cïõ8;$<¬\3QÊÚÀZÈldv©fÜ†"†»c«Å¾›¹ş·\"ZÚ…T)Uğ•Zlô÷UA'hÿE£,ÔÏ.A=ÍÈtú¹‚FÙDRlßsô†)È™ù`/";É.ÜÓ2œæ]b—P¡cBáŠroí*[©í	 ‚ÉBƒ K|e¸€40 ìM
ã2BŠf1ü®è1yÂş×Ïé½å©R<"°FªI›'„Vª´çP±‹ğNŞÉ£ ô„
PF8`Âj"jmŸğÁß˜ gxDüA	‚ÚHŞu*W‡X_Ûæíˆ9qÚ[µ·LiŞuN§9v@pkvmRÓÃsSİÀ(ı¢@ØµÛ™ñˆø“bqLÂ$· ËÅ¨!™ág¯XY¤d‘· ?È©ÈÁD|'¼²‰CÏ#ru¢*(n„oõ5¤´³˜À "xÙc!B¼èœiÙá2¼èì«³’¸èú¸±D\C£§EÁ=»—„z®ØJĞåkàå)áÙgYØ]”eªƒš0›Ÿ#˜Ñ`†P"ê{ ½ø—”ÆêĞQ]bÚPbŠ»jB!»Zlv;‘"ç¦¥N…‚[",À0yÔºã#u-×âìGîãÈ÷!àˆÿŒÒ.ãÔ¸n¦böìæFX	ì&‰tµ!2ğ]6H·GH<½ hCƒ¼K4N­Å"‹Ò7Ş	ß|2ù~™’·ôì'h¹•µA$ŞVTvˆŸğ´Y˜¾ “
=<¥IÅ##ŒÜ!L$ãòäÀ[%Àú'Rÿ¸ùF¶!.Åœò¼| —m9('…%Ô
t P+‚l¤w8D­1›şöœgö WŞÓY1°¶lbã	÷ÁÊ(¡ä˜5Vzk®†ò1m’Á¼¥Ö» -—²½…I’–ˆ¢Ú6“	A9xŸò–?;gHÉ¬¼{) ³G†)°Ö «êNp”È€³
O9Â•SŠ¿…%<ªE)¹<OãLÙBLxËAÁ ¡01}Â¬h -=ØneC™#0c†ºk°Ã÷- –öb§!:f©T3,(mĞ¡¸qçé=Øƒşc‚ş õØ‚Õ1’*TilOÉ)`¡h{ûM]Nğ%ˆ
äß~*&êWØ$˜=78‡MÆ´Úd
4tZ ›…3Õ”à3jÄ‘¯Õan*ù¿PË6½åÂ¢2J$Ò¢(LP@cúÏR #@$ˆÕÒh7Nzpq@á–;acçáH[èwhÍv"c’ßÔ÷¾3s³+Ö’Ë` è6S±v˜ ~	Ttğr‘ÅHS1eROÜâ(X µ’`]Š¢eC› 4x˜h ¦@íÅÔE¹r@ãx—JXîÖY%½47„d@FX‚Q³¡õ(¨Ù“{A<µ!^!F‹y´ ¢¬%=Ë!…ûbÑèk8¤†„İPæyWrrqKyË»E\›¼Ø«¶£Õ¸îT»B¢èhØ	CwIö! |Dj7\ÖØi±UŞ*œÁJ§ƒwR
ÆGŞ:’šR™M•ÓbìH÷Án|œwiÀ«Ú ®³*õ€g*~\ˆ™ƒG‘”çSvY’TÚj¨ä„Ş@VäqTŠ‹fÑ*ïËER TûûP¾ånt8Ğ,ĞÎ¬ÁªeÑA—Ä2bP„§“¥°Â-’ÁìU00äf4OœN8	È0'Cë#’Àó'"Wv;ßåÊ ìL
À]C0YéÑ>¶Ly|wğx.äœÓ¸3#"Vï°ûÊµKIÆZ £aêÕh³­[PıÈ‚=Ì˜AaàúÕ% [(ŒP±'R°DÒ8cßPMn+jc¥©Ì›­3shì[¦t·Å8(CĞ3<#`eŠˆh²up#‰¶5êAaÏ…Í)& DO‘ØÛâÖÃÙ/Ş˜Z•)ãâ=) ½ñD'n_¤ß¼…BGÛóY³\lZˆø÷º@„0!öÓ•!CëƒDFié`("Ø½5İáş™É9ZGG4œ?‘€Wã‚”M†¥'iÈ?9½úàl*ã‘FV–H}¤‚Pù×ŠJM¯Î—[|ØbdML(LÔ•ä(ÉâĞ˜_©\.èé2	‹óB¥ø“±ÜI?w œøTwÑŒ‚¶Ú2g¼`¢ à: ğË ÌkQ‚Ù}$¡U±ıîáz¨ªÃ>’/H
P@f}Í÷‘2(AˆìŸÊ
ßšÙ%†Êê‘“,q3)ÑÎŠÉ°¤¬jh×ğñÎhw´Vµv÷—VQ€Şù\!` tX5âù“^Š¢B/¦')“0ót3ƒ~ƒ½¬…æù$ 	˜°ˆ•4èÏwV	2©(ã5ƒ[•ÅŒÆÇRÂu
Å¿˜¢[s~-b| PØQ{õ?ËºJTà1€š[Ê5kŸÂÅ> ZNÄğw&œ|úr´>„dœˆ;Å¨8†’^V ½†àç0ÃºZ"d&[,ânY"}|)ÌÃn6˜Òâã(4S³„ŠËÊèu|[Â‚ãL6ñ¯ôtİcDD‹‡K ø¶i¢
KëŒt™2*
¶2¤åÈeZÈ Í—7ù4	QĞM²Õiq±¡HTy‰YÎÈ(9T·óüêğa-kpx±d[NÒyØ~O”qÈ^lqQš0Z•óê¬d¢âa-Å1Ï sò Ç#€Ìû†İ#Ì¨gä|]°Ã6“®$dÇŒÔ?~tM€÷ è¤º¥™¬êÜµï6cÆ³5e†±˜ï¹´XUá8GEéÍê§væ?chéV©K'ÂÃC‹$üÎ§SĞAîÿ`¥nÍÿJwZ˜©'¨Î'ztOãØG„lÄˆ¨Ï[DÉÄQ›+Äq
áBÉ
9Ã”Ú<š?l\å¹ Š$éssb'¶¶¬<©ÃW¦1üÂŒä0DLÿ6ğ+AkÑèœ=àôÖmœı_GÔñv˜5p«,Ùs‹në”ö.´øê QaRöPD+<3÷lMˆ5_BÔ†AÛÔNµÖp«åH
Ä £Ë<„. ßà€€ã‹ú|[P	ZŸúmå£X%-6¥têÎgš @@ûãzš&·˜•mLƒË3¼¹<Q@Í·n´H­É¾q¯º´Ã4„ØæìQÅ;&:‡’
±=êbU`…[åMJo°»,©Ø @IÖ|'eÀ¿M³^HÁcfàé™$äPŞd lÇ§C&şÚ¦wµ2w¦puòlœÔ^n¼Æ£°Å p„ÒT¢måË°ÿˆÉÌ\ß€İhb=rAŠ.usˆ4®ùğOÈi€;êP­ûm9JØ•0²íêIÍ}¸ u)Ç„ŠáœrT:Bø!˜	3Ó(
¨*…lùU/8
=‰>'t]õüã×]Øi¨Óulz‡$´²|èHÉn3º0v?ÅyU‡t{ÄŠé)Â¶U½0Òı~O[}Ş "§÷ä) ¥Ÿ Ù“¾'jßıká£MCX”<-‚Á³ªè¤/p¾õú’Gè?lÁ
ˆ9 4µFÈÑ~º´ıuªuÂh+ï+¥cH¨µàèë…™Qƒ&îtÖ¢”xèqát+ _Q$T^Ø‡¡Ù`u“O¿Ğd³Qp]äŞÉÜä Ù!Hèˆ°ßÅ¶”SJTˆÍJÍy€Åg×xh!¨8Gb*Ê<¦òT™h}„Õ×"ÖN4bxš#$¡	|ÔÍ‘±aª¬PælÓIşº:W`ŸˆLåU„MK¡¾½‘±èbTs(S”×·ÀˆÚ÷pø~¬U1şpÓ.7Š¡Ê
NúĞæÙ¨à€D¿Ş›Êh­AXa‡„í
@+Î†´-'„í€Ö6O]¿TU<1 Œêäq­ÓÏwê$µ­âè˜Å=Ò¼L¾€ äõ3èfi¹+˜ô§MçpÃ6•Å“6ãÈîş{L†¤‹÷¿’E’;0‘À~pNüÂ lZ§Ÿ$¿²Nø™…Ğ¶•¹„bÀ÷…iß)éyÖë^M™\§ÇD¸’¤ü
dÖ¼·ÉPeÂ#6 ÑAX4×Ì†š[À;´Ek™7ãl-ÇÙbG€°V‚Ö0èúÓãõrÊ)*rº“Óï:†Gw6˜U˜I>jŞğ¨…#q’–RäôH{éUê³Š!BR"¯£û 3x"3Š‘êŞ—m0>=‡&ÏÂç—B@˜J´¾
¢‘k³§Ìªts0p~	Tİah¹Bßàà!•s[1yÎº 	ÖVJ3!z„‡´	TPŒ¸LI:üÂ‹— åqò‚s/ÓC½¦OÊü-ğ©“Æ!¼õ…º€„œLƒ21¹ùy‡ØØ‚¥Fªä¤£['ĞÖ'í€VşÁDz•ûd¼ÏÓ†™³Êp²ØÆ.º»Ek¡¢Ê¤I$©„™æ| e‡ôÃ„\à«T©•œ'ÌáH>;Y
ˆ¹:+UCâ7ëîÊÒcÜu”@Ê¸×.Êùx‘~ºJï+IÀ"FHÎÕ8óÃNçáákê„Ğ "‡up›¢Moz¿Gä°¦r{;‹©Dzæ³øUw‹RpRr`Ñõ{l¼¾¹s>Ë²$@Ÿ)‘….x-ÏtŒÍèëa"àNà³^²à~&@á§§ïƒ<¨K—Ëë”ÂÚÍ,êï“RğÌ˜æPÚ–¡–®é¯ŒÔ¬k
ÂÄÈ9øãPS4¤Í³b¤nÀêZq«˜dÆ#-±œC	 ôxz»W6>Ş/¶æş-ÈQóèNôñÑkĞsˆÿwl^‹ÒĞğ¯ÉFVéŠæ£Y‚™ı¯¯“ó'C?ù ‡xBoÜ‰ğQ
MÎ?¯~ÿûUjÉa³SÓ¯¾ıĞs5À	É{Y¹2ÖaFÜˆ:J±s»Ò´@Wîi[ú'Oüí×vWŞBæ6ï}{IŸ5ÎñÌ‘ük§r±(˜4¬u-Ã0¤7HñX…	%$¬Ù‡Ad§?[ª•h\<ƒnƒ—_Ê©XÕVÑ3/‹2sE°Öu9¦’áşâ‹>Æ^Oôæ5ê¨QkFñÂ&%Y0c[£¥²kO‚º™ß&©ÄcàfÉhi‹1…‹ª¹3_åÁ“3û}wajiA$t‹…Å¶„Û€ÚzôŸ'$‹0ª,NÿØßRâHJ(‹%ƒBíÜ¼Âv5Ğ|Ì±Xx,‹ãLå@H‹^×éÅG#ï¨ßì®Ùq;”_§uJª•gã*ÿWÍáÚ
‚§f#®;0//ZÓöı¬U¶~Ïn6Ü+ÙÈ$zv}.é0.=ô
­&ÆpâÓûæÕá¿¼|“°f&sÜ‡bÛÑ9ù.¹AàÉ=¼`ü„ÄD‚ Öá¿ü¤`* ˜yR”¨úpõô¤.2õè¬…?Àè0tºö¼hZ3Ë·e5Â,’i•æŞ¾0´Qe®”º;×ñüš,b·ƒèóê‡ ²Ñ‚Ğ‚ˆ)Q@¿.Ö‡ß@ÁŸ(Úšz.°1E½>•ÍôµªÆ J|W[+U"Ä"AŒó*†¥wî]ySìAH>Õ>ÈŸ®Ë©Ñ F,÷#â/ì¦ŠÁw†
–ûk~Y¡ÀHò»l¾eu¬H Ÿx}+p8'[»…¼U‘ÙÜ¥ÄÈV’¯#‰È6€Ÿ“0(Î”r£úÊXS¢6YB‘ËÂm%MõÙÓƒYï67†ğxÕ-nhŸ'*ç,Rİ*³¸ñ¹	;Œ´|°6%UCr—c±É2Ìâ*y”E¡Ç+õ‰xk„ *ŒZ…Æ^0N—AŞoôò’*ZY¦5uèâ¾C?0 02Îìí¢ĞPŠ™ï¤Gƒ‡X2z6P3ÂÌÔ´€1İSûÜ`ã¸|&|Ó€iEÓğËïñA‰Ğ)¥ªúkÉÖ0NŞ ˆóõjÓDéÜ‰ ®@šîÁïì‘æê}†~w§ÉUì,rq t°Â¤S>^0$rI)V¿rqÃºéıœyºÏÿ7©¶íEİúnUÓwiğ‚!u%b”…åº”™^›®IâOË  ø™iÌŒÿÏÇ‘ª›cI›ãÑ¾d…ˆC÷¸üºN];"Tëó‹B’)Ì8$¤VÁ ¦Fk?İ±¡2HO¼>ÚâĞŒ¿	¦Ğşşc&ŠÈ—à%ôZ˜:÷hÚ
äŠÓ>®¿p%ÎùH™$zd›×fn(="‰®œóèæû¶Ğ^Ç×Ó‰ÆYÒ+D<û*;§{£+Zylˆ—‘B0Ûà‘È!¶ŒÃ	Ş¤óOdÓ"R•¸Å÷g‹aê™H›—™òIÒ¶igŸÈù…u­*ÙxĞxë˜Í\’¬€ŒÍj&>™Ícş5Öã€¸¹Í”?MlÒn£,ã³…zá8çÙjpZ{c,˜
S9<Ì,	Ğ¬SÎ‡ÿ¦ŞãÒ”ßgP37B„xW_Ø\¹M˜ìÕb›ØË
œ©ã:-¦Í!—¼Ó ]1äD¦EÔ¦ıæ<æ`è®»FvÓç÷õL
y|òe÷2&}»È×Ú›‚ôóæRÚÏ’Ã{I´~±|ê¬1NÖÆØ5ß®NhâÖ(f1Hl¤õV8@EÍ)+€eeR
‚òñh˜Z	é[£±VåPgRŞÜfU³ïi°şE(4"Şc“l¸"EêBÜúäâÀ`»2¼û|Ã†@‹#¿*qSšğ&›
”@¹7±[|ú„<¤	“¨j-ŸH›»Ğt*/Á®IÁ‹ôÚ6q½‡8Z`†Úb[“ˆÊY'P™HŒøW‡'4Dë”´5¬F„cİPäÆj¢×¤ÆÓdş‹"Å©RXrgI…7Ï€År—èû7Ï”0„FŸÈ¤"Çp2h‰» ADy«pœœ°Pã”
–\'œ­TÁ„&”†Øsv9.+.…¯šLİpi’ß¤vHbÈ©ş©EøTáäfe£é–¹—DYÿûÔ Ó+ò…Î°ÆHÛ¹
¦ö¥#K4xB±2‡(Dµ?¨} iS|’fVWxúîÁ©„’ı±üeü‹õ›úïJêÎ•p+g°Ø²• Áx¹ŒCáAàe†ªxT¼ÙHq¥ggĞ»†^# ÍiÇovÍWÅğ­GîÏk÷è™
¸2ÁĞF¾Ù¨)Ü1£?”²t‹‡¬© 
ÃœÉĞpÇát©?ĞØ¯á"ÑIV~W¶g¥7b€t*
Ì¯²ìCˆR€r	›DÙ~Í*¨ğGP´%Fo1™(Hş”%Y‹œ”ZÁˆÕHÌ^X V¡r™(ædˆôGóÏß; 4$Át*†Äe¡bî<L›N.£²¨á±è¸C EĞªÊ‚ÈÀ~]˜3óµJƒ¨o\TXoôšæëá@< PŞƒHDÁ¸?À?õ±?¹ †Pn/–A½‚İ)("!¢JP •yº\ Ú6U|I)ó´x”á{pNKú†ê
OÜà¨Õtè,•Æa#ı7›6B‡_dµ‚îÉ'Rr;¢]Ê‰ìHÖQ4?M±Úö±±X?~Áäß"ÀRÕ¨¤<íä+Ë™¾7
uxSÈÇÂõåQy{C0„*j»É"÷şİ¼µ‚Å¶Qi1ÌF0·áÕeR«XŒÄ É‚ËQB JÈ<ú¶eœ_ÁM	EÁñ?O·aÊğ[>£¥­m\E‘l¬&‚œí{J£áŞAëÉ‹êj°.µ#§<2j}ÑÒ•J:B¤ZK?7¥6á‹DDÁ&ÀB_a/›#§ßQœµ‡àM`äçµNª`ğPöoÀ¦€$_€q—V¿9¡K1¢š =ÁŠ9)~7>¸¢GoñTXÜÙú
Tc¨*†ğÄè!°’a”¾?”X¯šXüC Oº4¸"„!Òjå¾i–öÊÕ¡E;„3Šë ßXM	a±HÔ]	Ä½šÍµE®æ¹”LôD¿&Ú"^·}‡«)ëa*ã+›áúÈVt@Ïjg9aé±ÆÊÕC“}ú»%X*+ŸAµ&­à>æUÛÅRƒ)¢cÍ|M+™-–¡#@<b×ß&-ë–¨CAmğè4µ$ye2J[6Î¨‘Q“k„®¯ô*ôÌÌÀñüU†¤á¡¾}Ä'Uz´ÄÖ±xå*	ÀW'–˜ñp(˜JÑzø£dXŒ$ È¢Q»&0nÌİEYs›(n­ù„‰*¾%©ğÀ”‰Lë= >ñ\‰­ı˜+Ó”W Šl1ºªÔ‘5œS™¸Î¡³:È“/Šdd2@#FUú2šÉº"ÈØ47]ó83‹‹‡dlĞÀØXŸ„f¦24½Úgü(%œ…±Ã[
}5Ÿà"Ç¸5·È'uÑKäÎ	R*#óxïÛ™X#G!™t‚bH°¬¦w¼Ë—vœäÑ°¬¹˜tØÓB™TÕñt®í«>§ƒÌF³]p^2ÿàü Ôep@XÌw9aÁn›Xõµ)@FÀ¤@Ó …h>J&mAØ¹!äu=üìæ}TŸQWÉì3AÒM@¿CáØÀÖ\ÑäŠ"µÀ—F¦â±ä˜d+ø ˜›O÷QÒ‡Bœ®®—ìq&áêÕ¼­W„Ëù«:5sz%)	y¿R#‹”‘˜|RÆ¾r%³fr”_ì×yEî-Îøp&Ñ_•7D„±Î4£ªw@É"İ°0ÕUìYbÛ!Ñ(ıepQí~"øV
vQ»ß·şq\yá6ÈGYR¤_
ÆUÑ›˜†òÈİ¸±ÑoPØcZ&‰
¢4ÆRf{`ñâPhê;N¾ íH(œTÆô´Æ7^ˆ¥Î•Z«ânß[óÎµœƒŠÁ²x.“¹_3ü¸O‚¦¯Dö¤ÑE­F"DŞ×(„33ºÖtuàŠá’¯ut Š`RhäÁa°Z¼(DJ·$lÀ0±¿A	÷H2Ÿ"Ò^H"íy1ÊÁúe¼@-åooäÆjÎ›˜‹]Ë~!†H§”Îl]S3ÆÖÒ3VîYX£‰„CgŒ~È AGI6êü¾œ«×¾3BüZcºãúÄ‰«‰Óé#LvŠ-C„P,Vò@º‡+Ô˜Ã¡QL»s
Ñ$¼E¦Ï–a,Aâ#R†Š€Æ”fWÄd‘Rê60ğ€C­â—	Ùi	¯dfè¥ÂÕT:HŒ‡«Ï'ã	gŒ"P´4Õ I9Ù÷U^—@±%2E.®„¶h·¦Eï:§šhà |‡
îÈÕ°öœü!O:Å:fËÆÅµ¿‚”hs¸Ú>2äE<qIÎ’ñF¦w
Œ^hÙcHd¨‹±HEì[MùŸt)ÃZ9ÛDT=½L¡cN­XJğDË´÷Ùäé4º#~%4z¤Kï ışŞ×o)İ‰ ù€Û©0]ÄP-…ª+ùp„šÜ2g<üß§†},`¤nIÂ‡ÿÈ{±_É'F“µœíc5 8Ì©ï€®®Âç@Ü²|Öù\N4T–£‘X¹‹©óä%†còkx6D,fXUh\
w§¤9ÄR5`hĞñ2‚Ü¶Éİb¢Óv¹”Oƒ†È3¶Q=ÃÔ›Ã
Q>°µ‰}—ªÊévnÓrQäVèàlÛÖ“Fœ¡áeöJ™Á‘KÚğ(dÜlÎÒş†u0Ÿâäï@UÖ^¿Ôô_~¿ëB¡rÄÃ.‚™*hÓE{ úXI—ä¬ãÜìx9>å‰gñcÊÔÔ×ÿ}Äâã~ˆ>nN’Ì¥Zä÷dïdÀa¬ğ+3Dˆ6wp; µa®O‚¸ E¦~*²MÍª‚\·©‰ßâÁ›ºGÙG]>lyNóÀa’D³JI%B,GÑ,ËûçKÏÿÍ¸3Ät¡ó2—µ4½l×G@åÜë{¢£•†®Òk™¡ù î d?Òá$.ªCDÿ¥Œ0‘¤¹ ²”7d:[­ûÔîƒ>}¼Ï)_è’¾3Ë’]ü	š­[ğˆÓ7 Ÿèì#;Ë>øPOÏçf„cîæì&¨aulõŠh=¯wÔ!øX™Å“@wĞ/âzê]„YawÁ[ªtĞfø——$ªµEèÅ\Ğ.”Ã-ÔvWÍ!‹Ï ‚‘^ÜJûøƒ26ôUü®ÃinšÿØÍhÑò£‹%ÀÅè+WŒ;’èén"1]„Cú?`ÖÙ”,i(÷†…ÎÌ-±×à¬+G†Ö…1°LÔÖò;àû3QÍ¿s'9Î@¿ÂÑ­‚N>ÿ]aÓ¡ˆ&˜6	úG2ü›`QIBªN¤ óR	wõcÒ4R²õ>¨ÊšMûvŸº.¥u…'3İÆÊ‰:ˆo"á’Zg?ª%ìéhÙ‘³ÏÌ~ÈHg¥´ıÙ¬$%ÀØ&]÷Ò'gFOaçÍƒq4nx<»>"JÃr Ï¢u¯G^ÔÏ}Ö„‘º„óAîêÅáú5c‡ B²ÈZ‰X‚b«µŒ'ÚnQi0“3†anL'óuÅe[é<Hm%./¸1K0ëmè¹[Ú©û3è–;=\@é:xh €fÂM>1•Q/u°0Gıó¡abŠ-_jå!7Ç·²2Gngá­şu"É#óĞGRcí©‘ƒ+µ+¼I„2§;£W®ù˜]n)«DÇÂ&ùá‘3=ö!Ø¶›7ˆyÈ™>ùßŠ”É:ŞW/-±Q6qW,WEn÷	VL¼_N‰|YkÙƒZÌz ƒTùn,P<8xŠèß9­¦AûII±À‹-b(<¬iòÇ.I¡; £Çæm Iˆ^¦µĞ/0 Éñ€„}Û‚{P€E7.ŸÉô3{ÏH«ğ[^`<JÀ/S·ÆÄÿœRË!²F®R…°;ı8Íù0wO‹êU.Šß gæ#T§Ì<É­µ¢±æo ‹½@_¾–Eáÿ´§N”…G¹L—¤}.8şvCŠu‡MèL²©aĞ3Ó¢’ŸçI8ÑİHZñuİéÅçÒÕæ@1F»ØôH&L“$¬	&ÓàÜ" 0Ğç3R)û(Î¢³]¼p	*ªt:Ñ<¡´¢­1&Àw'»‚SàóıLÕ}07M¦¶pêÛ rÈÄ;^Ã°èg_ó- hÅœûªÌ6—­+ş[™¯.†‡İÛS!Ò»K\u°ÜEQ`
bW8%»lò	Bºx¦á8$;K{kÖBác"ØÑ`gr:;)eù«M©æ°Şàá¢%Wº‡íĞSv$j“ZÊ?9 d•´€¨$-m2ãp›+”öÄ›˜>Qu5qŞ|ä2 õÂ?–\ù**!ˆ‡ ÛS¢%YiQC¿ßU|&gyáV¤Ê\a[uˆ¢Ğ©5ş­Wİ–éò‚ø¤ƒ«¥@ÂÀ’Òï=¥è¾=ƒt9(Zå*<¦U¨§D¢š±j)’\j±­CÜõB/åtsš|U…vä—zUL×EB®"çîz`{8)¡¡ZÏq(ĞE¸¨"uE »eE’ÌÌ[ºfÀ p)Œ€KÓL¡R‘ÔÁM²A’+lGÛ”$Ú%Õ¬¦¥5Öì,¶‹¤Zµ@*Yß§ìŞÍ¦û‹P›fÄÍ1F©t2€Immö¥èšê~£ĞMø¦w`xJƒ# ´İù ŒErH2tíŸFé.» ƒVPEl‚*OĞÉª;Ëai<À½(*–"RFö'ìÄ~¡UO*W¦epÚ†6K|ÀĞ v2Á{³Î.6ñjNq¯ï	Ü:IÏãÏyg½øLì.¤ê„ü¿áê:—\÷¼ ,º®Ù`0x–ºá`)S ¡³Ê@zwQEÉn÷^/!Ğ?÷íÆ…ÒQ±œer
¨Šï¢Ùû ı¼*;í‘ä©&¥"TÊHÄÏ¥ÕhP ôCQÄ4ON=ÉÅçÀzcø"~tŞE&X!u%Šr””¨EÂÔAé02t„s“F)6E8Áñ¡óc=†éÆj0 à£jŒ6°à£×Š#´X:…HhD‘)Å¦!à’âò V0ŞC@7 ô¡ğ€ Sƒc4 Â#[@ˆac|Ì'€°@m‚„07Pp6€ ó€`AÓX-âÿ°
0m¸c(Eåˆµ: ÿ*¨½wâğ8Xu€ëTåAÊ¤Š © <~ E18v€ŸnæŸ?$™X(\n^‚	˜­3 Zp¦€<\4ŠD˜u	†e¬ â¥øq(ä8mn)6ƒp›Ps0  ²à… « Õ€+ R (˜ I( ‚P ä`'€; s Ó0”T9|T9|L9dL9TD9>@8Ì Y$Æ†‘ä@``¢0@0@V,@"(@$C|H† ‘‘ƒñ‹ñc
ñ@b€) c‰ ÀÀ
´
¬¡ô1+àb°ÄAƒ'A‚Ç`ï€¶Àí@:€yêÙ˜Î`€näµˆ~ X€`âcˆn X I`pfĞİ°v@MY Ïdp	UÀÔøù’6/¢2ŒGA
êD/qb‡¡¶†j„m‹LÆ=ÓL]L¥;/R@ñq@ßÆè¦5C1¦ŒÌd†bÙ‡‡f˜gvØ¼ä§ ,90œÜÀ¯ê/¨~}çO¾oûæ·Šæ§#;ò#¯Úœsâƒgø ¶Öa]¨‚{0Ù€âÄÖ 4/xKÀP^rğ€t´Õ c«Hê@³Rb%Ôœ”à=#ÙH@yB©ğÏ€Òl`.™TÈ
%ÀE. ´¸òà}—Ø¨…@Õ*Di²É˜Ê$Í1w©Ãyvâİ³V†8¨Ì07ˆ™F¦HÄ.µ¼ ŒÆŸ8±ÁJí@  `  ¡'â‚`\LTşğÙApBs)r…!Õ
â(
Òi‚`<?xml version="1.0" standalone="no"?>
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
</defs></svg>      €  pFFTMh+¶   ü   GDEF       OS/2ilã  8   `cmap©/Vğ  ˜  .cvt  (ø  È   gaspÿÿ   Ì   glyfòÆ  Ô  [Xhead 8=ğ  b,   6hhea
®x  bd   $hmtx p  bˆ  ælocaÉîà@  ep  ¸maxp. Ë  g(    nameÔ–™ü  gH  |postÖcQw  jÄ  ywebfK)QÑ  s@          Ì=¢Ï    Í÷–    Íöû§               Ú          Ğ   ZĞ  ¤ 2¸                          UKWN @  ÿÿxÿô ”                          ,   
  œ     (     ,  
  œ p   X @     +   
 / _ ¬"&'	'àà	àà)à2à9àCàEàIàYà`àiàyà‰à—áááá"á)á5á8áAáEáIáYáiáyá‰á•á™â ÿÿ     *     / _ ¬"&'	'à ààà à0à4à@àEàGàPà`àbàpà€àáááá á$á0á7á@áCáHáPá`ápá€áá—â ÿÿÿãÿÚÿfàßãß´ßhŞÚÙÙ	      ÿşı÷ñğêäŞutmgf`_XWUOIC=76Ğ                                                                                              Œ       5              *   +                     
      /   /      _   _      ¬   ¬     "  "     &  &     '	  '	     '  '     à   à     à  à	     à  à   "  à   à)   ,  à0  à2   6  à4  à9   9  à@  àC   ?  àE  àE   C  àG  àI   D  àP  àY   G  à`  à`   Q  àb  ài   R  àp  ày   Z  à€  à‰   d  à  à—   n  á  á   v  á  á   y  á  á   }  á   á"   ‡  á$  á)   Š  á0  á5     á7  á8   –  á@  áA   ˜  áC  áE   š  áH  áI     áP  áY   Ÿ  á`  ái   ©  áp  áy   ³  á€  á‰   ½  á  á•   Ç  á—  á™   Í  â   â    Ğ ô¼ ô¼   Ñ ôÅ ôÅ   Ò ôÌ ôÌ   Ó ôÎ ôÎ   Ô ô÷ ô÷   Õ õ õ   Ö õ õ   × õ õ   Ø õ% õ%   Ù õ' õ'   Ú                                                                                                                                                                                                                                                             (ø   ÿÿ   (  h    .± /<² í2±Ü<² í2 ± /<² í2²ü<² í23!%3#(@şèğğ üà(Ğ  d dLL   !'#'7!5!'737!Lşı··È··şı··È··ô··şı··È··şı··        LL   !!!!!!LşpşÔşp,şp,şp  d Œ® 7  !32>53#"'.'#7347#7367632#4.#"!!! şÔ	09C3JL3®ak§Íw$BÙdqÚd‡%Ku´òp<µ3LJ9D?{dşÔ–ôJtB+0W5¬ju.«xd/5d§Z½gj7X0,Z>d.6  ÈL¼   !!Lü|„¼şÔ ÿò,ÂA   !2654&#".#"²îxªªx.,,µn˜ØBUq,¬zx­aw×™kEPr       d°L      !%!!°ûPXşÔşÔ°şÔdûPÈLı¥gşÔXı¨,dşpÈ  ÿóÿó½½ 	    764/&"	'%'Mc™$^şfÖıšÈMßy\'™aünfÖıšş`pß                1      °° 	  %!!5!!¼,üà,ş°şddd&&ıÚ    L¯    &7>54&&7>5è@JOW‰OFS
ı
@JOW$ˆOAX¦ı÷r67)Q7q
Á
ıOrn)`*^  ÿìÄ™    	"'#"      6& ‘,mşÔwÈşäüÑÂÁÁşî°şÔm,NşäÈşğÂÁÁ  d X¯D   >.54>‰0{xuX6Cy„¨>>§„yC8ZwwyµEH-Sv@9y€²UU²€y9@vS-I   ÿ¸ G•° 
   	!3!7‘ş€ş‚’ş|ß’’Øü
ş?şí¿şpı' ÿ¸ G•° 
    	!3!'7#'#77‘ş€ş‚’ş|ß’’ØşVJÁëMNïÄIÀş€
ş?şí¿şpş+åŒÓÓâŒşo     °°   %!55"&=462#°ûP%?°ø°?%d•3È|°°|È3•d     °L         # ' + / 3  !#3%!!#3#3%#3#3%#3#!!#3%#3#3%#3°ûPÈdd¼ı¨XÈddü|dd„ddü|dd„ddÈı¨XıDdd„ddü|dd„ddLû´Ldddşpdddddddddşp,ddddddd     LL   / ?  #!"&5463!2#!"&5463!2#!"&5463!2#!"&5463!2ôşpXşpı¨şpXşpşpşpı“şpşp   	    LL   / ? O _ o    +"&=46;2+"&=46;2+"&=46;2+"&=46;2+"&=46;2+"&=46;2+"&=46;2+"&=46;2+"&=46;2,ÈÈÈÈÈÈüàÈÈÈÈÈÈüàÈÈÈÈÈÈÈÈÈÈÈÈş[ÈÈÈÈÈÈş[ÈÈÈÈÈÈ      °L   / ? O _  +"&=46;2#!"&=463!254&+";26%#!"&=463!2+"&=46;2#!"&=463!2,ÈÈ„ıD¼ü|ÈÈ„ıD¼ü|ÈÈ„ıD¼ÈÈÈÈı“ÈÈİÈÈş[ÈÈÈÈ      "ò*   %''À2Ôı¡ÎÔ"4Ôı¡ÏÔ     j jFF   %	'	7		rşæşæÔşæÔÔşæjşæÔÔşæÔşæşæ   ÿìÄ™   '  	"'#"     6& %3##5#5353‘,mşÔwÈşäı“ÁÁşîÂ¨ddÈddÈ°şÔm,NşäÈ¼ÁÁÂşğóÈddÈd   ÿìÄš     	"'#"      6& !5‘,mşÔxÈşäüÑÂÁÁşîF°şÕm+MşäÈşğÂÂÂßÈÈ   ™°  +  4&+";2675".547 654&¼ddd§Ò[›ÕìÕ›[Ò§g|úbú|îşpö¦>şØ·vÕ›[[›Õv·(>¦7Èx±úú±xÈ    d °±      %#3#3#3#3°ÈÈşÔÈÈşÔÈÈşÔÈÈ°ûP üàôÈşÔ     –– G Q  %32?6?67'76?654/&/7&''&/&#"'72"&54è&("/&./†80P˜˜P,<†-0&("/&2,…;.P——P-<…-1²~~²~·——Q,=†,1&("-&3*†:/Q˜˜Q/:†/.&0!)&1,†;.Qv~XY~~YX   dÿÿ°   ' + / 3 7  !2#!"&=463!5463!25!!#!"&5;#3#3#3#„
ûæ
;),);dşÔşÔ„;)ıD);dddÈddÈddÈddL
22
d);;)dddÈüà)<<)¼ıD¼ıD¼ıD¼     İ 
  #!!!#ÈşÔşÔşÔÈYı¨şpX„     d  è°    !#!"&5463!!Xü®ÛşÔ¼ı]~şp,   ¬¬        $  63!3¶D  şîş¼şî  _şªóóVóşbÈşÔd¬ şîş¼şî  DóşªóóV«d ÿœ  °    #!333!#Ñò(ıå¯Ñ¢Ğ¯ıæ2ªşp°şÔ,ûPô,şÔ      L°    !!3!3#3Lû´êşŞÈ,ÈşŞz¯¯şp,ôşşÔdd     ¯¯      2".4>  6333ŞôŞ __ ŞôŞ __ ş¬òòTòşÈ–úú–È¯_ ŞôŞ __ ŞôŞ \òş¬òòTªşÔ,,    ¬¬        $  6###¶D  şîş¼şî  _şªóóVó¤–È–ú¬ şîş¼şî  DóşªóóV«şÔ,,      °°    !3#!"&5!3!73È Çû‚‡ı¢aÈ2,2Èô¼ıDş%ÏşÈÈ      ¬¬        $  654¶D  şîş¼şî  _şªóóVóÙş×¬ şîş¼şî  Dó«¬òò¬«­É‘   ™°   # &632!&#"2>™–úşúú±ˆn’‘ÆvÕ›[[›ÕìÕ›[X±úúbúQ’‘z[›ÕìÕ›[[›Õ    ™°  !  7&#"#4>32732653#"'¼“p‡±ú–[›ÕvÆ‘ı¨“p‡±ú–[›ÕvÆ‘ “Pú±vÕ›[z‘şpşp“Pú±vÕ›[z‘   
 d  °°         # '  !!!#53!5!#53!5!#53!5!#53)!dLdü|„ıDddXşôı¨ddXşôı¨ddXşôı¨ddXşô°ûP°û´„ÈdddşÔdddşÔdddşÔdd  d  LL    3#3.>>Èdd„({„‡tZ<‡x|rjdLşôQE
((
EQş<0!O       °— ! 1 A  4.";2654> ;26%+"&546;2+"&546;2°c£ŞèŞ£c2ää2üà  X  ,tŞ£cc£ŞtşÔ,ÑrrÑşÔØş4Ìş4Ì      ÈXè    !''7'77,,şÔŸGGGG şpÈ ÈÈGGGG       Èpè    !%7'654,,şÔEojCV şpÈ È95‡¯©…6nŠ’     ºb÷     %7654/%%!%7'654ÎS{wı¯,şÔşÔ¾EojCVº²ãæ²@—Á½–%ÈüàÈ95‡¯ª…7nŠ‘       °° 	    ! % - ; ? C G K O  !535#!3%!33!5#5!)!%35#!3###!##5#535#5!3%!#53!3#3#%!5dddşdLı¨dÈ,Èü|,şÔ¼,ü|dd¼ddı¨dÈôÈXÈddÈ,Èı¨şÔÈdd ddşddXşÔ¼dd,ı¨dôÈşÔşÔÈdşÔ,şÔdddşÔdşôşÔdddÈdşÔdÈşÔ,Èddddddd  	    °°         #  #;#3#3#3!#!53#73#%#5dddÈddÈÈÈdd,Èı¨şÔddÈddÈ°üèüèüèüèû´dd	[[[[[[       °°    	463!64'&"2°şıE
Ú´SSôş¼Û
şÂTT    İ°     	463!	'	364'&"2±şıEÚèş2ÂıDdşTTôş¼ÛıDş2Â¼şÂTT  d  °° 
  !!!7°dıdîü|¯°üdèdü¯        °°   '  .#!"!%#5!#3!7#!"&?>3!2³^
ş>
^(P;ÈüàÈÈdXdw&
ı¨
&
ô
=Zş¦|_ıDÈÈ¼ÈÈı˜

˜
     5  °¯  "  !5&'./#!5".?!#°"(ş]şq*m)>$\‡R+5ş‘².tB*.æü0BB6,êŞ-WB	ÉŒşÃ    d  Ã¯   ( 1  !2#!5>54.'32654&32654&+d×xº!"E4+v¦Oş);	$,‹Ll¨›¡Y€}^Ÿ¯°Œ7]7(3AvFT‘MY3(;2ş…{MRaü‘aTZ    È  o°   !5>76&'.'5m!:"€
0GşMs­
(G	°9#'%üÇ4<99C/Q8$9  ÿµ  °  %  3#4.+!57#"#33'3#7~–2.!"ÈdşpdÈ"!/1–ş¢K}}KK}}°şÔ'	ü®2dd2R	',Èüà§§ §§    !ÿ¶±  %  3#4.+!57#"#3!55!'7¶–2/!"ÈdşpdÈ"!.2–2 §§üà§§±şÔ'	ıv2dd2Š	',û´K}}KK}}       °L   / ?  54&#!"3!2654&#!"3!2654&#!"3!2654&#!"3!26¼ı¨XüèÈüà ,û´L¶ddşèddşèddşèdd      °L   / ?  54&#!"3!2654&#!"3!2654&#!"3!2654&#!"3!26èıD¼Èû´LÈıD¼Èû´L¶ddşèddşèddşèdd        °L   / ?  5463!2#!"&5463!2#!"&5463!2#!"&5463!2#!"&ôXı¨şpèüÈ üàşÔLû´¶ddşèddşèddşèdd      °L   / ?  5463!2#!"&5463!2#!"&5463!2#!"&5463!2#!"&Lû´Lû´Lû´Lû´¶ddşèddşèddşèdd     °L   / ? O _ o   546;2+"&%5463!2#!"&546;2+"&%5463!2#!"&546;2+"&%5463!2#!"&546;2+"&%5463!2#!"&dd, üàşÔdd, üàşÔdd, üàşÔdd, üà¶ddddşèddddşèddddşèdddd   ÿ›  °L   # * : J  #354&+";2654&#!"3!265#53554&#!"3!2654&#!"3!26dd,ddşôüÉÉ¦zşÔ,,ı¨XLû´¶ddşèddÍKdK}Èddşèdd      L   # * : J  54&+";26%3#54&#!"3!26535#554&#!"3!2654&#!"3!26ÉddXddÈşôôÈÈ§ıëşÔ,,ı¨X¶ddªû´ŠddÍKdK}Èddşèdd     È°è    #!"&5463!2	„,ı,,î,,şÔ,ıv,,Š,,ıp,,        °L     #!"&5463!2!7' "&462°û¨Xdü÷*J%ìıNpNNp üôJı¶ƒœ>şà2pNNpN   ”ÿó½    2.'&54>264&"X{ĞyII»99
"c]s+?yÑyk——Ö–—½~Õö•röBB	"ko K‹{|×ıE—Ö–—jk—   ¯¯     2".4>"ŞôŞ __ ŞôŞ __ X°ûû¯_ ŞôŞ __ ŞôŞ ü]Vúşú    u ß  %  .54>.'&6?*IOWM?%N~šOrÃ€DmssE.
		\7[[Gvwsu†EY™d;^·w^¸¡ÅüyJ(I43n–QRl      Åh  ! &  7/#!"&5463!"3!26=7'Tq\qië¥şÔ¥ëë¥nºşÉ);;)ô);ş0¡œrşk†qUqşzá¥ëë¥,¥ëº;)ş);;)}T2œqşk       •L  .  #!"&5463!#"3!265'	">Lë¥şÔ¥ëë¥…U‘);;)ô);Whş™HCVC9gg_Å5¥ëë¥,¥ëP X;)ş);;)ƒD>Ø3CmC&4	        §L  #  #!"&5463!2!"3!26=''Lë¥şÔ¥ëë¥,<C²ş£);;)ô);şí6ˆşR“ˆ9©¥ëë¥,¥ë±;)ş);;)E7‰şQ“ˆ    °±   5#3	35#	35#	#35„ÈÃşÙşÔÈÈşÔ,ÈÈ,'ÃÈ,/ÆÈşÔ,ÈÆ,(ÆÈ,şÔÈÆşØ    È  „L   !+"&546;2„şddôèşJèşKç     °L   !+"&546;2°şşddôôèşèşJèşKçşç  ˆ  °L   !	°şıÌ4ôèş&&şç   È  LL   	Lü|&&û´   È d„è    %4&+";26%4&+";26ôÈÈÈÈ– üà üà  È dLè   %4&#!"3!26Lüà – üà     (L   !ôşô4èşLşçıÚ        °L   32+"&546ddşşôôLü¶şèşLşçşµ  ,  èL   32+"&546RddşôLü¶şLşµ   d È°(    	!#!"&=463!2ŠıÚLüè(ıÌ–dd   ¹ ù©   %7	'	ğşŸağı°ğaağı®  ÿÒRt   '	7ñaşŸñ<.ğaağıÅ    ­­       $#33535#5¶D  şíş¼şí  QÈÈÈÈÈ­ şíş¼şí  D‰ÈÈÈÈÈÈ    ­­       $!5¶D  şíş¼şí  ‰X­ şíş¼şí  Dş¯ÈÈ   ­­       $77'7''¶D  şíş¼şí  TÕÕÕ­ şíş¼şí  DşØÕÕÔ     ­­       $'	'¶D  şíş¼şí  f®›¯­ şíş¼şí  Dşbf®şë›®      ­­  6 :     $3>54.#"32264>:3235¶D  şíş¼şí  QÈ-"#1D1i†	'È­ şíş¼şí  Dıç
)2X23L(p
=Šdd    ­­        $353#!5#¶D  şíş¼şí  QÈşÔddd­ şíş¼şí  D‰ddÈdÈdd,        °°  1  ##5.'#53>75367#53.'#53#5°Ë·YÈŒˆÂÂ*EkIÈ6vkş×•4ÉÈfIÈKnÑÒoK¼Èf¨!ÅÅ—}È<YS7ÈÈPƒEÈÎ0ÈJkÌËkHÈImÎ   ¬¬        $  6''7'77¶D  şîş¼şî  _şªóóVóª‡‡m‡‡m‡‡m‡‡¬ şîş¼şî  DóşªóóV$‡‡m‡‡m‡‡m‡‡   ¬¬        $  6'77¶D  şîş¼şî  _şªóóVóvş¦äWÌ¬ şîş¼şî  DóşªóóVuş§äWÍ   ¬¬        $&#"	32654¶D  şîş¼şî  T8dt«óıÌap«ó¬ şîş¼şî  Dıu7>ó«sDıÌ;ó«p      c°è   !	!°ı¨ı¨XXşÓÀÅşÔ       c°è   !!	Xı¨XXı¨,,ş;ş@     Ì  J°   !!!JşÖşÔşØÂXı¨XX   h  æ°   	!!æş?şC(,Xı¨XXı¨    Ç°L   %>7X_°¤‚#F‰çœXÇ-$DuM„Õ­gş;      °°    !	!°şpşÚ&ı…şÚşp&°şÚ&ş‡şÚ&    " #    !!''gşp'ü:şÙ'Ú‚'şWşp‚şÙ'   ™™   #   2".4>6&+";26#3âìÕ›[[›ÕìÕ›[[›¡:Ï:#6#ÈÈ™[›ÕìÕ›[[›ÕìÕ›ıç.şÒd     °° & - 3 7 ;  #<&/.#"&/&#"#3!3!53%7#)"6?!)!°o"=' ïî!'=
#odÈdş+Ê ıùşç"§şpXşp„
¬'0.&±
dÈ,şÔÈdÅ)W9`0/û½şp      ÿê¯°  2  57.>7>7.#>7>'&"76Ø	8./ieš‰èh,Jhqƒx{\ScÉ´Fak[)!#==şY0'C7yÁ5<€b‚;<U3-9½şÌĞ›U3	7Êy&?_€T2	3s  ÅÌoSB   ÿÃ }í3 ! ? G  "./7>2 2>7.'"&5477.'íFOsv““vsOFFOsv““vsOFıE€wRY,H7:91°ø°.f|C-[TFk1ii%LX(
(WT`G//G`TW(
((
(WT`G//G`TW(
şp(3\;hI%E:JY|°°|UIW«
`=^8ßj|Ci`$ ÿÃ  í°  . 8 A  	#7./7>3277>7&'77.547?.'ŠşÆ”%R¦ri'
FOsv“H=<%÷%Ze“I&-/"0/a+'C.ı.%k.f|Òšk1i/:°ûPegy8((
(WT`G/ı¨(4kbf‘&2&?@0’6@şˆ§nUIW«şæ¼j|C/WR   ÿ  «     #!26'.%5#!	#/%ı~8ı~Èş§½½ş§ÈddG  ! ûÖ DÈddÓı-ôdşÔ,   d °° )  	'%/#&=47&=467462 kş™d^Ş^dş™kX|XÇş»1)ùşù[@	NN	@[ù)1ES>XX>    x® 
    	!5!35	57'!!	5#'73­ı¨ş«Xñ,şÔı1µş«I,şÔñµzŸ ı¨ÈXÆşÙşÔÅz´ÈÊşÙşÔÅµ{     °L   !2#!#"&546dè);;)ı¬şĞd);;L;)ı¨);şÔ,;)X);    d  L°   -  !!!!".=!32>'4=şÔ,¼şÔ,'MeÀeM',U'5%;)„,şÔ,şpÈ*R~jqP33Pqj~R*Èúq \#(,.ú  ÿâ ¸hŞ   %7	†âı¾ı¼ã`¸ãCı½ãa   F ÚÌ    %'	ŠBâşŸş ãÚCãşŸaã  ÿ: dvè    !!#	#!!®üä×}Æ++ùÄ+,Ë×üàXÈşpşå,şåşpÈX       ª° 2  32++"&=!"&=#"&5463!7!"&'&763!7>^6É*şÔ*20ı‡ -d€&°*ü?2222È*Û¢       °L    !53463!2!!°ûPÈ;),);ıD°ûPèdd);;)Èüà    İL    3463!2!!ÉÈÈ;),);ô,şÔûP, şpX);;)ÈdıD¼ .  ‚° 	  3#	#3.ÆÆ**ÆÆşÖ,X,şÔı¨şÔ   /°‚ 	  5!	!5„ı¨şÔ,X,/ÅÅ)*ÆÆşÖ     °° 	   !  >3!2!2#!"&=46#37#3¬$ %¬ûÓè);;)ü);;IddÈddã'-ı$d;)d);;)d);dddd   ÿ› d°L  ' 3  %4632#"&%%##"+"&'=454>3546?.Lüù£ı]&/
S8	â2"ÈRü®lúü®ÈşìQúÈ22ú!    œœ   '777'7'7'7'½-àNé³³éNà-››-àNê´´êNà-›œéNá,œ›-àNé³³éNà-›œ,áNé´    d°°  * .  #";;276=4&#!6=4&!#'#?33¼20`‘d={ú.%î='ş¸='2ÂúÖˆd–d2ıDÈ°(ÆÄ%şpKd9X+d,Qv–,Qşíá}ş‰dwÔÕşÔı¨X     °L  " .  !#"&/&54;6;2#!#3'!5##3¼20`‘d={ú.%î='ş¸=ıåÈÈô2ÂúÖˆd–d2(ÆÄ%Kd9ş¨+d,Qv–,QXı+á}wdş‰ÔÕ     dU  = A  %632!2+#!"&5467!>;2654&#!*.'&54?'#3ljmU.UkmTk‚ş«¦:d%ƒËş7	
“VıåÈÈiæpyLNş­'¢%
Hş	YS(·SüÄX   ÿ› e¯V  8 <  #!"&'#"&46!'&54?632*#!32!7%#3Ûm!«şªƒjTnlU.Um[‘
	ş$Şƒ%jşªæÈÈPæ'ıò¡(SNLyq¤¸	d)ş­YöíüÄX   a  L  6 :  4&'%54&"'&3!26!77><546!5L(ş­NLy%pæ'¢½ş	ìS·22(Sı¨ÙVƒjTnkUşÒTnş•¦BSV’	ÈÊ‚şÚşÔÈÈ   
í   6  5!%>54&#!"?265&5<.'&'!1XşÔS)¢ıó%

æp&yMN,ş¬(22¸Sí÷PÈÈüæƒV¦ş–nUşÒTlnTXşÚ‚ÊÈ’VS     °«    2 $54>!!	[¢  şíş¼şì _ İuşÖ,’şn« şìş¼şí  ¢zİ _şÉÂ&*       ««    2 $54>	5!5!5Uzİ _ şìş¼şí _ İşì.şÔ«_ İz¢şí  ¢zİ _ı­şÚÂÉÅ    °«    2 $54>333[yİ _ şíş¼şì _ İµÈÈÈşÔ«_ İz¢şí  ¢zİ _ı­şÔ,     °«    2 $54>#	#[yİ _ şíş¼şì _ İÈ,,È«_ İz¢şí  ¢zİ _şÙşÔşp,      °«  ˆ ™  2 $54>&277>7.'.'"'&65.'6.'&76746'&67>7&72267.'6'?6.'.'>72>[yİ _ şíş¼şì _ İ’+>=>1"T>9.*-fw"#.F	= .2)((%
	
)#?
7.R 	,$
«_ İz¢şí  ¢zİ _^
'"q!w	F/JG	
r$>	#/&
%	I+
*		' )$#h1'$
       `°¬   # ' 7 ;  !2#!"&=46#3!2#!"&=46!!!2#!"&=46!!dè);;)ü);;ÈÈüè);;)ü);;şôüè);;)ü);;şÔ,¬;)d);;)d);ddÈ;)d);;)d);ddÈ;)d);;)d);dd    d  L°  	  !5!Lü2„ş¢È°ddÈşşÔÈô     °°      7'7!7''7'!77!7'7IÈşp/ÈÈıïşpÈXşpÈÙÈşpşpÈÈûÑÈ:şpÈ      ¨¨    8 B L     $  654%2#"&467&54632#"'"&546762#"&54$2#"&4²D  şîş¼şî  _şªóóVóıÕ    	   73H3)ö.  ş".   ¨ şîş¼şî  Dó«¬òò¬«Š . !,!T! . 
‘$33$ 1.      .   P 6ÃX  3  %'&'.546326327>54&#"'&#"6‰ZËGCW#Å„bgÂ#WCGËZ²@C>`9J:vr3H<c=>@]aR6}ÆFGlZ.ƒÅÅƒ.ZlGFÆ}À>FXG`R«®ObEVA>Zo\    9ÿòw¾  2  7'7>54/&#"32764/&''7'&'ÜiÔ…÷_.#7BB]_@şåBBÔB]_@BBiÔş{÷_.7B–iÔ…÷`.5#j+]BBBşå@_]BşBBBºBiÔş{ø_-87B]^     È  è°     74>2#!"&!!264&"È<fœªšd:;)ı¨);¼ı¨Xş©V==V=d¹2..2üG);;­ıDş=V==V  ' 	á / ; A  #5&'.'3'.54>753#.654&¼@"<P7(²›d˜U(‹WJ.BN/!X‚Od&ER<+Ÿ6®=I*hªXşÍ,<e>‹ªMNW(kVMcO/9X7\‡CNO,?iBHKşû;I,ĞşÉ…@G  d f”­ H  #"&'>7>'#53&'.>7632#4.#"3#>3632b2)O'*Ò2'V7
	0$İ¦
	/-a¦Ê™DP$%T)òÅ):#b Œ"L,“B‘
7Gd17;V^(X²w4K,9 %(d2‚;6"       ®°    !###	###,*ÆÈÆ‚*ÆÈÆ,„ü|„şÔü|„      è°     "  3	33!#5###3!##535#3!53ÆşÖşÖÆÈXdddÉddÉ,cdcÈdÈşÔd,şÔ,„şddôdÈşpÈdddşÔdÈ    è°     "  3	33!##535#53!53!#5##735#ÆşÖşÖÆÈXcdcÈdÈşÔdd,dddedd,şÔ,„ÈddddşpdÈşpşddÈÈ     L°      !####53##5##3,*ÆÈÆ‚dÈdÈdÈÉdd,„ü| dşÈşddÈ    L°      !####5##3#53#,*ÆÈÆJdÈÉddedÈd,„ü|„şddÈşdş       °°  
     !####53!5!!5!!5!,*ÆÈÆ‚ÈÈdşÔ,dşpdşô,„ü|¼ÈşÈşÈşÈ       °°  
     !###!5!!5!!5!#53,*ÆÈÆ®şôdşpdşÔ,dÈÈ,„ü|¼ÈşÈşÈşÈ        LL    !2#!"&546!"3!2654&,¢îí£şÔ¥ëë5ş);;)ô);;Lí£şÔ¥ëë¥,¥ëÈ;)ş);;)ô);        LL   "  )"&5463!2!"3!2654&%¼şÔ£íî¢,¥ëëAş);;)ô);;şGMë¥,£íë¥şÔ¥ë„;)ş);;)ô);dşú     LL   "  463!2#!"&%4&#!"3!26!ë¥,£íë¥şÔ¥ë„;)ş);;)ô);dşú,£íî¢şÔ¥ëëAô);;)ş);;¹ş³     LL   "  #!"&5463!24&#!"3!26!Lí£şÔ¥ëë¥,¥ëÈ;)ş);;)ô);ş¢úô¼şÔ¢îí£,¥ëëıËô);;)ş);;Úş³      L    !2#!5!2654&#!5!!5ô¥ëë¥şpô);;)şÈşpşÔ,Lë¥şÔ¥ëÈ;)ô);ş¢ş¢È,È   Ø Ö   %276'&!676/#" 3!“		şÔš		½şõL

.›	v¯
ÕşÓXşJ        L    %!"&5463!5./"3!2?5!!5 ş);;)ôÈ]]¥ëë¥,/5dşÔ,È;)ô);¹ë¥şÔ¥ë¹È,Èş¢      °°  $  '''!#!"&546;7'#"3!26=°•şªÕa•ô²z;)ş);;)œvJd¥ëë¥,£í¼•şŸÕV•ı‹{”);;)ô);zNë¥şÔ¥ëë¥b     ¬¬        $  6$2"&4¶D  şîş¼şî  _şªóóVóş rr r¬ şîş¼şî  DóşªóóVr rr         L°     !!	!2!46#3¼şÔşÔ½Âü6û´Údd şpşôş
şíd2      L°     !!	!2!46#3,'şCş>Kû´Údd¼şÔ,ôşşp
şíd2      L     	''!2!46#3•TšşF–›Kû´ÚddT›şF—›şk
şíd2     L°  
    ''!!2!46#3™aÔaÅ•Ô•î¼ü•û´ÚddOaÔb•Ô•ø»üà
şíd2      L°  
    7!7!2!46#3•Ô•øıEaÔbşû´Údd¶•Ô”í¼şaÔaÅ
şíd2    ÿÿ°¯    	!	°ş%şÊşxwı`¯ûÉşw İı8â    dL°    +!#"&546;!3+3L–ıD–úôdÈddèü®şpèşÔ,È      >°     !#"&546;!3'#3	'7Bş†–úôdÈşìxddXş%ô{xaôşpèşÔ,ÈÚşíx=Èş2ş$ô{x`    °   #  !#"&546;!3'#3''7'77ş·–úôdÈgªddÓªªªªªªªªôşpèşÔ,Èşógª’Èüªªªªªª€©ª        °°     !#"&546;!3!#53##	#¼ş–úôdÈşpdd,ÈÈ,,ÈôşpèşÔ,ÈşÔ,Èı¨şÔşÔ,      °°     !#"&546;!3/#533	33Zşn–úôdÈÈÈdd,ÈşÔşÔÈÈôşpèşÔ,ÈşnÈÊÈü|,şÔşÔ     È°L 	    54&#!"3!265!!°û´Lû´şp„––dıÚ&şÔÈ       f°®  
      !5	5)533#53!	!;#735ô,şÔşpşpdÈdd,şpşÔ,dddÈdèÆşÖşÖÆÈÈÈıDÆ**ÆÈÈÈÈ  d  °°  /  %4&;26+"&5'7373°$şì%
Èıv2dÈd22d22d2RuEş™5!şs“dşpËşA¿ËddşÔ,ddşÔ,     d  °L 3  %46?5!2!4635!2!5"&5!#!5".L2şpKşKşp"2KôK"jx88&şvŠ&88	üˆ88&Šşv&88	       LL 	   ! % .  '!!%354&#!"3!26!!%%!)%!!'!7£dş‰dÈ'iı¨;)şÔ);;),);şp,şÔXşpı¨,şÔÁ'şWdş‰dÈèddÈbbÈıDô);;)ş);;È#…£…bÈbşÖddÈ   Ÿ !  3?6&/&&'&'7>/.³¢>’fgÑ—{£À4w|~ev‹-‘¢+‰Ôfg’=!¢/ˆvg|~v1Â     °L " @  5.#"?>=6 6#!"&=46754>2°d|Ú~\¦ud?,		Ê>Êşpmû´m&RpR&ÈÈA1)!((!
È!"’’"!)şÑ3ÔÔ3/2 

     d  °L    7!'57##5##5##5#!5¯¶}dddÈdÈddd„û´È–údÈÈÈÈÈÈşpdúúdd    d  °L 	    32!4632!464&+"Xd);şÔ;¹d);şÔ;ıÑ;)d);L;)üè);şÔ;)ıD¼);üà);;)şp    ÿœ  °L    ' +  #!"&5463!2!!3#!#535!#353#1#°°|ıD|°°|¼|°Èü|„üàÈÈ,ÈÈ,ÈÈddd ş|°°|ô|°°ıDXşÔdd,ddşd,şÔ,  ÿœ  °L    ' +  #!"&5463!2!!#5#3533#!#353#1#°°|ıD|°°|¼|°Èü|„ı¨ddddddÈÈddd ş|°°|ô|°°ıDÈşÈÈôşd,şÔ,   ÿœ  °L    #  #!"&5463!2!!!5#353!5#35°°|ıD|°°|¼|°Èü|„üà,ÈÈd,ÈÈ ş|°°|ô|°°ıDXşd,dşd,d ÿœ  °L      #!"&5463!2!!3-°°|ıD|°°|¼|°Èü|„şşÔ,d,şÔ ş|°°|ô|°°ıDô––––   ÿœ  °L     '  #!"&5463!2!!!3264&+!#";°°|ıD|°°|¼|°Èü|„dıDd‚)69&‚ô‚&96)‚ ş|°°|ô|°°ıDXşôşpT‚VV‚T    ÿœ  °L    % )  #!"&5463!2!!3#!#5353#335#°°|ıD|°°|¼|°Èü|„üàÈÈ,ÈÈ,dÈdÇdd ş|°°|ô|°°ıDXşÔdd,ddşôdşpd    ÿœ  °L     # '  #!"&5463!2!!5#!3#3#5335#°°|ıD|°°|¼|°Èü|„ıDd,,dÈdşqddÈdd ş|°°|ô|°°ıDôdşşpôdşÔÈşÔd   ÿœ  °L    # ' +  !2#!"&546!!#5!##53%#53#%3#!#53È¼|°°|ıD|°°œü|„ı¨È,cdcdÈdı©ddôddL°|ş|°°|ô|°ÈıDôdşÔddÈdşddd     ¬¬        $  6!!!'57!¶D  şîş¼şî  _şªóóVóÖşÔ,şÔdd,¬ şîş¼şî  DóşªóóVGÈddÈd     ¨¬     $     $  6#5#3##!#53²D  şîş¼şî  _şªóóVóÒdÈÈÈd,ddd¬ şîş¼şî  DóşªóóVGdddddşpd    ÿòÿœÂA  !  32654&#".#";!3	33 €xªªx.,,µn˜ØBUqOŞdÈşÔşÔÈÈ,¬zx­aw×™kEPr,şpşÔ,, ÿòÿœÂA    	>54&#".#";##	#X“^yªx.,,µn˜ØBUqOÈÈ,,ÈÊşmœdy­aw×™kEPrşp,,şÔ   d  Lm   %!33	33!!'¼şòªşòªşÔşÔªşòªşòK^KÈ,,Mş³şÔşÔ›--     y  7› )  %32654'>54&'.#"&#"327!¼.6Ji	2;{Y“^t£	Ji9/iJ8,K^-2iJ f=ZƒYq£tiJ5XJişÎ-      d°°   %  !2!5#!463!546;235##!"&= ,);şÈş;),;)È);şÔÈÈ¼;)ü);è;)şpdd);d);;)ddıDÈ);;)È      L°    # ' + / 3 7 ; ? C G K O S W  54&+5#!5##"3!2653#73#73#73#73#3#73#73#73#73#3#73#73#73#73#L–dşd–èüddÈddÈddÈddÈddüàddÈddÈddÈddÈddüàddÈddÈddÈddÈdd„–dddd–dıîÈddddddddddddddddddddddddddddd       °°   	"/'	7'!'&4762†*$şéÔĞş„/ÏÒ,#*¡şæ*#şõşÔÒÏşÑ|ĞÔ$*    ÿØ ;º° O  %>'.'&#"327>767>'&'&#"67632#"&'&>767>32EC8fOESkZ(Gş D0#vF?8!@)'(Š#Z	.C"ş|Ey&$ıİ4I7Z	0$&\4=k6_v[üıw<ÿC´]W†$!7Gş D¾N9@1*+,Š#b/W""ştCu$'$ıİ4B?#>@$$\475›be[ùÿ      d°L  ! ) 1  %4&+.+"#"3!26#53"&4624&"2°;)–37S*È)R:.–);;)è);ÈddœÈÈ>X>>XÈX);E5+);;;)ı¨);;dÈÈÈşàX>>X>       L°  #  32#!"&546;5463!2!54&+"„d);;)ü|);;)dvR,Rvş,È ;)ı¨);;)X);ÈRvvRÈ–    J  f° ' /  32"&/.546;7>7'&6;2 "'267&Rıúñ::v?zS^Sz?şŞl18F8(	.))­GM~ %Mş¹ş+1==1   È  L± 
  3	4&#!"ÈÂÂüà¼şE~    o Dç H  .7>7>'>7>76''&'.7&:4?	FFB:8( OV
	$9DkC@&¥¤'GOS3*gJ.ó;4(
.MV .nhB8-
%>=Bí'P¨d!I, =CnC¬Sm,UŸ!†Ù•fm§    ¯…   7'"/&47&676´û¤{‚ı¬+oX!N`¤şı
~¯\F/ı¬n+WeÉ6\eŠ       A(˜‘_<õ °    Íöû§    Íöû§ÿ:ÿœİ             ”ÿô  ÿ:şÓİ                ˜¸ (°  °  °  ° d°  °  Œ    Œ    ²  F   Ù   Ù   £     H    F  ° d° È°ÿò°  °ÿóô  °  ° ° ° d°ÿ¸°ÿ¸°  °  °  °  °  ° ° j° ° ° ° d° ° d° ° d° °ÿœ°  ° ° °  ° ° ° ° d° d°  °  °  °  °  °  ° ° ° d°  ° 5° d° È°ÿµ° !°  °  °  °  °  °ÿ›° °  °  ° ”° ° u°  °  °  °  ° È°  ° ˆ° È° È° È°  °  °,° d° ¹°° ° ° ° ° ° °  ° ° ° °  °  ° Ì° h°  °  ° "° °  °  °ÿÃ°ÿÃ°ÿŸ° d°  °  ° d°ÿâ° F°ÿ:° °  ° °.°  °  °ÿ›° °  °  ° °ÿ›° a° ° °  ° ° ° Ä  ° d     P 9 È' d                  Ø                               d d      d dÿœÿœÿœÿœÿœÿœÿœÿœ   ÿòÿò d y      ÿØ     J È o       * * * * V p p p p p p p p p p p p p p p À Î ôBJbšĞúNnÆ&âfzœà^€úPjŒÀä<ršÌú0z¢	 	&	F	|	ø
8
^
’
¬
ğ.x¦àvÒ.†2.f˜Àü>†Àì8N\Œ¨¾ê0D\ˆ¬Ş
b”ÜPˆ´ÈÜø>xÖ&Œô$fš¼ (Pš¸Úò
B¼L®
d¾ìDrX²ÊxÆN®  4 l ¤ Ì ö!$!R!†!¾!ö"0"^"˜"Ì##@#j#”#¼#ì$$6$`$–$Ô%%<%f%œ%è&2&„&¼''D'x'¼( (:(l(®(ò)6)|)¶)ø*.*d*Š*È++€+²,2,|,²,ş--‚-¬    Û š             @ .        º           	   j   	  ( |  	   ¤  	  L ²  	  8 ş  	  x6  	  6®  	  ä  	 	 ú  	  $  	  $4  	  $X  	 È |  	 É 0’www.glyphicons.com C o p y r i g h t   ©   2 0 1 3   b y   J a n   K o v a r i k .   A l l   r i g h t s   r e s e r v e d . G L Y P H I C O N S   H a l f l i n g s R e g u l a r 1 . 0 0 1 ; U K W N ; G L Y P H I C O N S H a l f l i n g s - R e g u l a r G L Y P H I C O N S   H a l f l i n g s   R e g u l a r V e r s i o n   1 . 0 0 1 ; P S   0 0 1 . 0 0 1 ; h o t c o n v   1 . 0 . 7 0 ; m a k e o t f . l i b 2 . 5 . 5 8 3 2 9 G L Y P H I C O N S H a l f l i n g s - R e g u l a r J a n   K o v a r i k J a n   K o v a r i k w w w . g l y p h i c o n s . c o m w w w . g l y p h i c o n s . c o m w w w . g l y p h i c o n s . c o m W e b f o n t   1 . 0 M o n   J u l     1   0 5 : 2 6 : 0 0   2 0 1 3       ÿµ 2                     Û     	
 ï !"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~€‚ƒ„…†‡ˆ‰Š‹Œ‘’“”•–—˜™š›œŸ ¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏĞÑÒÓÔÕÖ×glyph1glyph2uni00A0uni2000uni2001uni2002uni2003uni2004uni2005uni2006uni2007uni2008uni2009uni200Auni202Funi205FEurouni2601uni2709uni270FuniE000uniE001uniE002uniE003uniE005uniE006uniE007uniE008uniE009uniE010uniE011uniE012uniE013uniE014uniE015uniE016uniE017uniE018uniE019uniE020uniE021uniE022uniE023uniE024uniE025uniE026uniE027uniE028uniE029uniE030uniE031uniE032uniE034uniE035uniE036uniE037uniE038uniE039uniE040uniE041uniE042uniE043uniE045uniE047uniE048uniE049uniE050uniE051uniE052uniE053uniE054uniE055uniE056uniE057uniE058uniE059uniE060uniE062uniE063uniE064uniE065uniE066uniE067uniE068uniE069uniE070uniE071uniE072uniE073uniE074uniE075uniE076uniE077uniE078uniE079uniE080uniE081uniE082uniE083uniE084uniE085uniE086uniE087uniE088uniE089uniE090uniE091uniE092uniE093uniE094uniE095uniE096uniE097uniE101uniE102uniE103uniE105uniE106uniE107uniE108uniE110uniE111uniE112uniE113uniE114uniE115uniE116uniE117uniE118uniE119uniE120uniE121uniE122uniE124uniE125uniE126uniE127uniE128uniE129uniE130uniE131uniE132uniE133uniE134uniE135uniE137uniE138uniE140uniE141uniE143uniE144uniE145uniE148uniE149uniE150uniE151uniE152uniE153uniE154uniE155uniE156uniE157uniE158uniE159uniE160uniE161uniE162uniE163uniE164uniE165uniE166uniE167uniE168uniE169uniE170uniE171uniE172uniE173uniE174uniE175uniE176uniE177uniE178uniE179uniE180uniE181uniE182uniE183uniE184uniE185uniE186uniE187uniE188uniE189uniE190uniE191uniE192uniE193uniE194uniE195uniE197uniE198uniE199uniE200u1F4BCu1F4C5u1F4CCu1F4CEu1F4F7u1F512u1F514u1F516u1F525u1F527    QÑK(  wOFF     @@     sH                       FFTM  X      h+¶GDEF  t        OS/2  ”   F   `ilãcmap  Ü  ~  .©/Vğcvt   \       (øgasp  `      ÿÿ glyf  h  3Ä  [XòÆhead  8,   4   6 8=ğhhea  8`      $
®xhmtx  8€    æ ploca  9”  ©  ¸Éîà@maxp  ;@        . Ëname  ;`  „  |Ô–™üpost  <ä  R  yÖcQwwebf  @8      K)QÑ       Ì=¢Ï    Í÷–    Íöû§xÚc`d``àb	`b`Â[@Ìæ1  ¨  xÚc`fidœÀÀÊÀÂÌÃt!
B3.a0bÚä¥€	‰êîÇàÀ ğÿ?kÅÿ/2¬Sx€ÂŒHJAŸÍ  xÚÍ“?LSQÅÏ£-PB¤©€tĞ{Ó 0 0K*N¢‰\hˆA4jÒÁ„Í`X1YMØtÔÑ¸¨ñ¿Â ßNN’h˜´ûn]˜\L|É¯¿ónúî;}ï+€€9Jê€à&Dp5×#•õh0ËóqäxM,:±bël—´«éd[‰g$"qII»ôÈ äeX
2!E™‘’ÌË’F´VSšÖvíÓÒa-è„ÎhIçuQ—·P.óÜ{v†Ä$!Vº¥WrÜyDÆ¸ó”ÌÊœ,h 1M¨ÕVíÖ¬æ4¯£:¦EÕ9]Ğ%·sy»¼^–šÍíÍÇ›7"ëMkûÖâ6e[lÒÖÛj3eóËü4»fÇ|3_Í³a®š+æ’¹`¦MÑLšqsÖœ1'MÁäÍ1“5ı‡^û§õOz÷Qu«rÖ>{_¤Ó½(²R¡ºò¾/ÔĞ]$éIöÓ«4é¤M@[àA3‰{p€nğğ­C(q÷OÑœqß9H'×¦YBÚıLI7éaî§{É 3‘É3§‡=à¹Œó	zŒL0»µ¢§è)2Ã|šæLJ‰ù=Gæ™ÏÓd‰ù2 üMê:_£ÙYk™y²³ºÎ×ivVvÇº•¸ş7iöW÷ì¹ŸfÉ ómšıuˆùÍŞêúß¥G‰ëŸfuıĞì®®óCšÕu~D³³ºÎOiwEæç4»ë2óK`pÚ¾?	ŞĞÏB€·ô‹àı*xOï† üH† éÆàİ|¦‡ kt&XçÿŸ³õgÔƒ*7˜{¦5ğcéhÿõQ½w¡ño®ú•ıH   (ø   ÿÿ xÚµ|	`TÕ¹ğ=w™›Y2û›m&¹™ÌLÃf23@H‚aûQpDÁ€@qm ‹`¥Ø
„HQ»`_¥…^Ô¶j£Uú´¶¶”VñµÊk+d.ÿ÷;“L´ıß{¹Û9÷;ßùÎwÎ·Ë°L%Ã[¸2†cD&òaªGyæ|ô9ƒğŸ#r,\2ÏqøXÀÇGEéq”àó˜Cqb%©ûë_¹²Î*Ù×Â¤˜ßÄ712ÀVd1äğ;ÄPR‰+¡¤ìˆ9’²¨ğMZúÅM/ªğÓÒ„Ë\\èı„ÖcàÁ…kF¸®ÿ «EëĞZH	Ã¯-{¥µP\ş«üa&‰ïøøX´Ç¢Á_Š?«IÂ!‘LÄ¢²Gö×DX¹•Q1H
W¦u“¼šb‡K,İ÷óU–wS+„3©‡ï<öiËÃó±¦	¾‚a£\dmJë0äÂ„;ÚŠÏ‰Zrç7"W§ªã©}s7½tó’dóàBwxn}*’ üTÒÆ7±Çş´O|Sç:n#{\ë`ÈåOI˜?Á_ÅxÆ¥Dñš ¿\Œ¹ArËV"¹=GÙOV8°:?¿üëï°³Vğ¡»V?k]°ê×;-Ö¥c¦®¤dƒ_ŠoÚ±ŒÀä1ùØ	 áJæÛ/Me›Š|»Ö‘"m—¦’6•oJûf­ƒmNï'áÒUeîòg—?ã_â_bŒ Ç’‰šj,·#	.â'İ˜Ÿ¿ÓVa›§±‹ŞN?¡’I¿åÖ\—²îÌÏ_Ğ¹œ>Ôæ·ü¶k\³ÿ†dÇ¹oøL@‘€o€"{œ„;? aÍZR©dƒé3XßÊä_aÊàÆ­Dö‘d=‰;j‚¢ t¢Ï2¸s#'L™ãt<2eì«)m7Yñ¾âá)W5sÏ¤ÿa—‹V&’U…ò´$·Âdæ^æ\¦ôGAáÊåUEÒüì<  |ùcş§üNÆÅ3Œ‘xÜ6b(à) G#¹»§,a–q[IX—å¹ó´U›Uí¤X³¤óÄùòËÚ'h…ËD‘„'ëeÚÔÍÄ£ıõÄËP…òo3„ÍBßêY+¹­œáã5pCØG¯]}gsbÔšûëë÷m\3jèÜU«ÖpÏ¹š4£uä°5÷ŞstÖ¬£÷Ü»fØÈÖƒøñ@Zöò÷˜qüv ¯‰± T—‘¸ˆâŠ¹’ää­Ú½Ú†Ç´uä·±‘w:],kÒˆK;Oşƒà¤J‡ğ@77òg.”PÒı²˜ì6{ÂËMšü_?ÿcíŞ> µüê¯~µù÷_Õn#9|à> 6 ñò`©ID=^â6ø%äYÒhhÿg{C`Ë–-„¤¶ÇÔuííëÔØöò8}¿‰®mfÆpd±RÆÏ„˜L5C~Q²_Š`î¹à,àuÎÙÏI¯gØ®
üw<½Ÿm†‹Îu©·±û¬âóôèù¦KÇø&`[œE©ì?¸	g.éÕ×7àXÍ4 vĞO¥<œœˆ)QĞëşKË/x%­Eòz%Ò&yÙæÜ»ôş+—ñEİ×¹Ï½émW( Ô}ğŸÂÜÀÜÆÜÃlHnÃ@¢_-L¿äşÿ¼~Ø+©ˆ=H[ÿ×üïÔá‹²Wı´kÿ'Åcèâó\º÷Kqä¼§¼âÊ0—<Pô.ÿ²÷s(ÈmôJéÑxÃš¬û÷Kú¥Qz[×å{_L]x™rşS~ H8& ’($’sÑô7ÑQÎÕÀù—z½%Ì~,?õˆ ˆ,#I‰l$ÜJí¬v¶ƒÑ)êĞÎ’¢%=o;°
ÈÀ2 Ô¿à©0Ø1É/ùãşx,»‚DHoÓÂ	²Öø»‚d8Rá„ö×Ï°Nª'(¥_,DXCsÛ_½©¯DwíÚ[¢êä‘“ºšŞD$J'ˆıÄoUe #ó;am¶ÁJ
ë”ÎK’Cç¥dÜãAÅ–GØx3)Q]¥ö¸WJá¨Á!µïÍkw½õñ[»®}sßÍë.’…×±Ÿä¬,¦^{çÅV(‡j­/’Êúg’êêç.^|nµJušC€ïŸëZß™€ß#™Ÿ ?¾]UµîÁ¥õTrîQ2EL1¿ƒßÁŒc¦Áû²'m`DN$CÉ\ÆkªÙ`5ICb(—şrk°‘A”E\JîRÖSJDœıÑõÀ4¸†œV–W‘jáÁ¡ƒ§r6òuAø:±ËÜÔğˆ…Aƒz!¨Lå
óÙÇ†ÇYk7uĞˆ„ACJ®_tı‹Âãl~7-\÷ ‚ï•
b„‡WOã
­:dk!7­zøƒBuÄ\¨TCÂÂƒµ‘i\Q>Ûº¾ùšõë¯if€V©Ë—ùvÁ:…·KRı8#Y»fy\Ÿê’W$AÉ
—Zâğ»bİ?n#q™lVó¥³f«ÍD\µU$\U›Be’ÛX[•7:WÓ?¾ÉfŠš­VsÔdKUÕÖVaaçU#FTHİı£sşÿèŒª®ñûá‘ŠàaLÙ6öĞNAy`›ÙSœç€7­ŒƒjÕ¹BnÙfÒf5wFÈoàÕhïz¼ã×›­(’£x†çñ‡€¯\¨{1e¼ÓÎòe¬İÉÃœÆ%Ä$xí2š8÷ìÑ>ÑkŸìÙÃŞ øì32û3m!à˜âu•'Öô|¦—%t±İ#¸WÊ¿ÄÏ)$ÆÁ¢˜á†}ãÓÊôÉ‘7¤½î×É‘ôÙhñbì+*é$êÏPˆ;°ÏºìÍÂA2´ÏbµT>È_´÷UVµ÷É]GtYI4`	ƒ>­A×<dÆO¢>VŸÄ5õ¤»Óf #ï_xÏ7ìÉœXV;ôé§dæ§šºãâÅ*¤«O×}ª@¬¼½è[Ô?}E?~×	ü­ĞØ•é›m‹íÒ#- «2—âŒeY‚(\LV’1•+ûŞ`¶r¥÷.P£$UÉdD-€¬ÁıR³¨¸ìôÀ]b
úàYêDÁqEÌßÕ~İŞF‡>{šl…‘È®¯%ÈnCYĞª¨8Ä ¨n–,¿sÇEí° >¼ü1Ò¶õÉS™Å×T¶VJXS§aÉ]™‡p¢k7CaÛ­· ”~0( $Øƒ°&â1y°‘E{|[ËCÏ]Ükğ©'·¦÷÷¸åÊ¶M½ø6{×VĞß[ºï€»˜µ©íûÓÁp(¬*€lÀÎ#ıœ«ˆ¤Àš‘B»•j°R-äBzÿ]á2¿cÜF•ìÜƒ¾>P=•ƒñcb(/Vá¢£øØz¢¢Ê^¹vcÛCwÌuxhõº•K <mŒ©²Ò4fšf18_)¦P~Ğùëq ææ**#,ŒQVÂÂSOÊ|${pèš.HT³êuÏ·ßøôûçŞúFX"ÍÖM †?l¢—XòöäYèmî¾Ë+|Ô‡×îĞ:è²¾ç•+ß¸‡Ş½£Õ`Eòs®}n¨|3wt&X+‰"8ò‰,‚°ù"§¬Om·)ûÇ•i-0_T5ç“…ÅµPX¶,!(†`>èHÛ˜Û–Œš…1,şĞ‘ï<Xşèc:À/äş–›$H¢ô%¢ l„`¤uÃã~aØÜf˜!®]%¦ h@óGøò~xöhŞÈ¼Ç_~iG á¢’6lêÀÉån…vòs|•ƒ
`1µ =]`"èĞŒKeN,àP$Xô
(q @ªõ|Åı
hv`ú©LÊ¨xş8¼ ¨]a~´_Shøi`%àT\Š;×Qâ¢ccÚ“©-¸ R³k¤÷ƒˆn,£/"ww`5†ŠT‡Áb”`HgpdSz­âŠEjÖÄÌØhıÏGÆåwÔfµ'¸A§0ùâˆ’öÁ×9iSU<éb·ô‡|{gw.÷wéX*e¼–şÓy„dÚÑª7²T&s‰šP°\4¸eO;é1 Fœ9æõúføğ€B
¨ô“M;áéõùğ = Ş£°òQFg Q>Fra>‡0aVœáÎi–(9ëˆVªƒC¼Ù
‹=³ÇsÛ!]kê Ñ¡ˆ¨„$ùöTÚ™b?é\wûâÎ¥:xr„ÉYPË¸Ò.B@âDNVà`”<œì¡’¨ÔTµ¸ïÎcí&­ŞdgçU²SI-2¯
ŸZdÍ¦ô~“™².¹`rqud.Nví™u7¤G«*{\UÓm_7Y­&<èôS<€</g%EH5!~b­Ä†£[!DA4$â’]|{y¥Óg0h÷\¯­((±°ÙÈ²ªú
×uä¡éöqmëÑÈd€+bÍçÎv:[ŠD±±Ñ“ÿåıAsY#9ıUí'”¨‡ı„úå*Ñ±çBUÄŠøx8=âÔIáB‚#Fı›œ~HRä×« +åfK^ñ˜š­ÏLÑ,Uµ¬ÑSQJÂ_iºuÿ®o^sïİóâ´5y}²2Oä,±«ZÇÎÜ:é1VÉÕ–ÈŞ|›ÕX;iú‚Î­fÎ½ê“èÀ¼Æıi²•³Îà‘%Ä$éJ uBqn«//„{MƒÇi–I·ç=kªg0
|û°<(`±tş¬f„aØ0qT57mh…‹pdúÕÏèrÅ°–0 %ù¡k‰Á' µ9tèª‚²¤Ì¯ßµK¥fjKJ-Wªİ!;´½ï¾{"üPÇó†$#Ûy8šJE¹é¬Q
yqÙø`ß>®lß>:¾Êåø-`}a›œ—Q‡%¹ Mwuw›ÉİEpfŠŞfºÛdÍ´yéX©Œ/¹§¿A_r7ú€‹½ï¹/)G«0½ŸúšußIgŞqç$¯
7Ğ›2(
{¥KÇğo’¼Üº)éöù´s_rù/áíú¼û”ŸËõa¨¹¸©W,ù÷°¾ŞÔ0ËZk½ï]_R~A¤´Nu­E§´NuU§´NuàŠ³ÿÿ˜Ş^xü»÷¹Èü+×ÿÂyıúÑĞIñFÍñ@M-Ğ}ÿ/Ë»}&8zÏÿ^IµÜıQèÿç!ÔpyW—ŞzÆ f83Zó±Ş>ÈŞóQ •,|Éò
	w·JÚ|nÍB}ÓÜ¾Î‚Ó§Ÿaï‚GôQ*@åìzãö¡uÜ?§ü|bjâİê&¦uA¢ü’Ó/Wo¿*zHúôõDñËúuº»K  ¤ÔÜ^ö§îKtå^åôèÀ¥cì£ÿR§t{ txãì0²ÜÆpiÚY—²Ÿ”ê}˜{2İJŸ<
Çt‹nuwë@MÔŸPÚ¨QI!D\zÌj úº.íw9ùfW!(Sÿ &ÀÇé¶É-“'·ğe,ä.ÀqBºô…ûw“zíÅÂÉˆû×.Æ—ğ/•]!QqS%'Â‡‚q0Á\ÑDMc§¶yíëkÆÿá°a¦ò¯¿}`Ãš7Ö,}üñ·w<Î¿´ş­¿o_ù÷ÆFcùÒÛöLüÊÚu¿¾/=‹–,}œê¯Y‰ÔËCBå»§—c„4·_ºÔËÒy=7Ív*¿ïdxî·‚ñPùí3 ‚$¢Û´ã"l(˜h 4)³¤ñSæLjL^ÿÄ”•?¹wô²Ûo#z"&k4^—´Y¯½V°ÛÒºêö;sÍÎT-o˜÷âªyßûæ“';×<*Çoª‰-ß1mú­EÙ±9Éß«´ÂÑ †–KÊ ^İ£‹¬XG„$	~æ
éº‹?ú¶Öñí>ú6Y^úí4úğÈ…ªZmğ7Éî•ÚRîÁ³JVhwıV	û#CŞj«P«­º{f”ì^¡-e²~™íÀ%LDWts‡lıİ­ÇA£—Ê­‚XÏ•¾©«y}`ÖÖlósÈ-ÚN±àêQ³G3ûn¾ùŸ—œŒgq˜ZÖœÅá~2šÔ¿ÃZåØ¨e£‚5.#!L>û /ZC=ñ‰ö¦/ä 1ê¨öt)Î³‰‡µéÛf‡}'‹ü\1›|D›¶íaãÔ}Ø˜+÷K10]àİ…“#qÂmT¢½«û°Aı»„~‚ÇêSú“Sp]yJÅÙ¨ÒŞéÒs7B_ÀÊRH/ƒä ˜š%G\ ç´	YË+iÉŸºâ¯4`ôÅAĞ\ë	aô…§R˜g`(LcDú5¶F£ú´ªŞê§a1®áÀÔ,½tÇJeRÜFºNÁÊèè}úN‘¹İÂ/WziÛ`§Søº?gôù¦ÜWº`àªÓ¬2Ó?$ƒ yI.@ÿ w|ö³˜ô%1$h–À$ó¿&Áe)‹tFêzióT=&/PBg ÂïyÉ ¨ƒõœ>ö}áÖ$¸éİ0ûÀ£°(ô“,à+áÊ‰# ôD=ì£é3İoãPó•é×vd¡#.Ì÷‘ûœÿÉ%i~ˆñ¯ÚSdÁ_Óíâ_É¼8Œ8Û/¿ÉOçï@-LÀˆ‘E‰%#´§şÆH$Sù¤g++Ç?Ë?KíëXA‚IÓ%,uÀºöìÑÎkÇµó{öi*şãŸí~âÂĞÇjO¸¶p]˜ĞŞ#lsXÚ»"~.Y”“r2”‰¡ŞğfnÚüÖ¦MomÆc_Àïd‹:6mfzâêèÙ,MHêŞ½¿gÑaâ&»ô½pÑaí#²ë°.·»á&@§é	9VÏÇÁÄÄÅX$j½0œ2™Û Zy×«¾DœƒÊKüCF)^ü`o¬Ày+ïâ\<Y°…¼¤ï¨¤ÿÄ™ª¢ÍÑXSe¡åÖ`1‰Ç·İc±^.­{4•ê‰¯­w¬F „à<¡Äı~PCWè}Y}ZÔÇÕ-ĞŸSDS”$¿•ˆ’?)dñX=—ŒÇ$+R"	÷ğÊ ´’;Î·¿Zøâ5êWö8a0féx5ÑºÔ©ız{ÍiµpÑxuâò‚7Ş,¸m"{\]´_9y²øñ»Õ×ÌHæ«ª{êıcÔ_ş†:a©üÚ«K¯VÇ/+øE¯øIiÿñ[#÷Š¡ˆ=´,ûwÅXJEN¥-6&’/lµU{æ›æ¼vÅïÔöAùÏ{µUÜk&¹p	XÔônm&;4uÇÁÏ8’~mAËÁÏú4–¾“MÖvğv2:ıZíg[2kòTÿ5ĞÈ!ˆ…oOïÇŒj¿"?&'µ¦ŸºX3å°&F$©Ã»V©×}øgğO¶®Bø	ÚÛZ‡ö9‘}ëİ}V¯gd±"wVkĞF‘JîNÛş•ù(Ÿ¬hFI4Cû·6øcùÓn¶ùgd[1úÎIßzöæ<bÓjsùØ ç ÊDÆÔYqÈ(ˆ[îÓÎl&ÁûÒhgîƒ;äÛ3´‡àiƒGt>”3~~3¿9‡¾/CÇB¸ÂßL_mîõµw7“{f¾Ú¤ÍÑZ6hïn"!Ì] 1®T†û{kÓ®á²ËGP„c’ùıÇİñ­kw‘oç¤_ºøá¼ßpû°´ïìtÁ)ı'q{½níM8Ş—B)”éD£Şµh‰ Áj‚‰•6báùÊz·›Ê…zşc¤óW‘rĞÈ9ÅQÅ"gÜÆ²åæºP™÷¿<  >Q¼¡:“ŸeoCg8¬*ÚÀWk™“M®ı‰+w:÷éqj<r‰Éw(4ØÑ–ï)°´9#ÁçòÜ&’ÒÕ¸ÔIKÕœaóW_z‰&²êkĞå¿€íşÅ(ÃÄ“¶$ë…¤›µòÖo#˜ „ÀÆ¡v/<ÉÄ;Æ<N©^|Óœ»%<á–÷¯^{İÛ§Ùcc,½¶ÊÃç‰nÅá²®NÛrÍàĞ¨äš—ã#î]¸¡vÄ¬Ø ß0ßKÚk¯ïš3\¶ä+ìš`Ã÷ÎŒº‚·0v{YÙÉ×nËŸÑˆrãòO˜»ùó|,ŠfĞU´Ê#B5 iˆú™‰ÖóI0Æ²)Iƒèã]˜9&¾ÈP2vÊí­ÛÚ¶µŞ>el‰¡¨÷ƒô˜{WM¿&|µ™$½Ã‡ù†´ÿ³=²h8jĞµ3Ç.²xq ÉÅ6Wš*çÌœ?®ºzÜü™s*1Z×ã^k©Œ]W{‹•ŒŒ^8áÌœ5~ÎAÓüºyCÉo—¬µ¸`~ÃC_èK;Øef(Fõ@äig0N
ãE¸NCIXù	ôöÈ‰j f
qj§¾˜şÌÊÅ!SW_®® ÿ(soÚ6>èT]>ØUMº/’Ÿ–n.½ùt§Zö·a s‹tÓÍk†ÚºzâØœŞ_i©YºpQÉV[0l(ÉŞ3ø±Ä–‘níáMÎ}Ë±?ÚYr:S=§dz1›o€^åäÂ:êDÄzl°‰Á ƒC1²~©˜TÖ@±”^ïêN¯—Š9»ªí#/‘—´} 
Ç•ñeŠ¡ìÒÛe£1ıñWéAäB
†ÆH$:¯ªR>‚ªb(à ¬jâ¨4&='’¨†‚ÎÀ•‘¥6OCÛ™*rÍ{«(¥ítxldióºfögÚ­CÜUî|ËçÚç×zFJvãäÉF»4Òs-?·äCÑ+CfÔ77×S›–Vó‡c§ñ ‚!äXÜHâB2¤HŠ W~K1îÙô~í áØæ¿álKÙô<ŞrãñîoÏoºë)®,½_e›O¡İuò®ÍÇÔWèÕó›×æèíÎLLƒú5ˆÃ¯ÛF)îyéCÚë˜ŞÂ7Áõ~0	PµUlsUm66Bó507H—	T.€ı/À¤©#Šä–=˜ToÕÔ=È‡‰á$l(MºéÉ?yÓ¤Pn³Bñ@mU©ÃÚ,,Ô+£µ¨¦¯_²bj,6uÅ’õÓ¨W”]Wh6»Kü•áH#ï"4{ù÷Ì÷ø[¸÷³ú¹L¸ŸşQúø‡dş÷>dG¥_ú,ÀñËœá_ã™Ln‘ˆì£lãïµ§´=aG}ˆ*ı‡Î¤„V‰y4÷Æ¡€dğy?½àwşá×äîSd øùOá~•Ü÷ëÎHÛ¬j-ÚI1Íbí€;JClhÌ :Eu[-ÎÊ$t¡T¢g¤=%I0OB\0‰—õ„/š‡ŞÈÄi6Ïç,õ@çç o”ºÈäÁé‡Ê	)Áf-äî
Å|;”u6ğù(¾Õ>·äUxÈo
œ®½)GlÊäÚ€fËäI	˜ÁÙÁz
Tz4Í7;—¢iNjçzüSxŞµ`æ¼‹sd_U$(ä´šyUOì¹4•„1T\ R4+Š0Ğ=?#>FêJ%‘S§È 2àÔ)ímÔ(cïÇ}P«ô”´~\Aç‚‚^»¥5IõÉ“¤J·ËoğõÈ8Sœ‰NÊ.¡Wf%áWzH¨p³{\áĞ¥_é ³ ÜøLl˜ıĞ$§+RµUzÒM
£yG—weö&ä3!ÌÉ Ç%3­Êƒ÷÷»<Vb4bC,4‹Ùï1¼HMP+ÉÛù9ûtúú`5k²Øn†ÛË%¼Åøûha!o-/ò¨Ütj«†êì­;«ÚÇ3)‘È4ëE–Ë7Ûj´ šŸ‘%&Ç0¬k»ùİÔFC`»ÁLŒHÊ¨X‡’ a“—}0ùÏßıîŸ'0h×.¸şË±cÁk~÷Ÿ'ÿ.¼{WNñîğï&ÿù]«R”Æ>f õ~¡ÆÁ¢‹¡– Ë"éJ@?ƒ~…8Q‘8Èïh 1Ú{<Z<Ø:«;U·Ö~1øÄ[Ò¾WRŠ’ß~8µ#MÛ½²äÔO‹ZËÄÔ0Ò\:0Ö*îOÓÎÿîní‘YÕñòLµn_-Æ‰(_ÍâzVKĞÛ#»2ÃNO”áBJœó‹~G)‰]	ŸôU•\èFJÇ‡´>Úşn|pş§şîn²*¥=Òñ]ó`‘øY€OÍ©	 K”š	¢d—’õÏ%e‡¬¸ê©¢H}èXİéÛ ê(âz+Yb5;,Ë¬æY$2ké²™K7ˆÅ6í øL‰uxŠî7Û^µ;v-IÌ¼‘yLÛfc¸Åg–«Ûi]ÓÔ6Y{ÖÚ›Ïæ;¦«5ã5d†Té“Ü^BXNà,yÖügtş”.^È×7¾6ú0”}|¬”ñ‡À‚Ö$dEÇ:GÓ\D«0 z…¦µìR’ Åş7d™èRæilÅ†û—Ì\~+öÂl]–o7_»Õ$6òf¢U¼5H`‰v€=ÚğÔ³|~È‘ş4ÿ›•™1¹­‰¬±Û¥ü–o}Ïl394§IªÊ­={ùûyqY€²I°‚IíÌîâÔwîA3F­œEW˜¨pIÙJèä'9<²PˆßT)iÏB“klk‹ÇN1aóó÷¾¤?ñ¢§Po<EÌÀ^z—ÌÎôné,íÍ™’¿ÜSªm7¸JŸi$3f?æÌ×»ÉYQQÛ~Å^lØ A¿(Í]4qç…¸*¬é:Gú ôİaœ¿ôĞİİ`â¬TIşÚZĞFB.…Á2Ã]µ7?ıŒ±é¬ÍÓ’´­!“Ú&Ã’zÈ]r¢¡Àó½çÉ?ø©ªÚy¹‡ÌF^²i;J=Ëó‰»pëò™ íÌC‰íÓé!ªY´çqùÎÇf“İ‰…sî cÅX‰f-[\İ\ ®ˆ`O^»·Ë_ññö¼w']ÜÉcÚrşàíã¬Çbï]ïí¹As>A‚d@—Œ:°ítåŠâÆ¡.à fggá[Y( \û¬ÖˆÖÁ„›»öæ€N?«9qúdÎıÁå0G*H¯¡p»~^E×u¿P1G³ù¸.]ÆõëB'xXíu5=¿•®%İ°fvö.ƒõlÀb`.enõP¿|lÒc€³ÛJÍY´TàwBÁú DÃu2f€”‚P„’$˜bÁ$:È’.”!çAÍAt`x¦›'Zß—VÙÕçÙêlR=Ç¹7DÈ+6”‹§ ¯`¦§~˜7/Âd Â¢U¾r?gä[Ç:Œue{ÔÎCÏæÉ.—Ù`7T,•V«‹ç¼`<{^©S6šªü³©4i1"Óm®2»ƒÃ%®ˆ39ÌÎ¾gqLè$†lfY,_!+«JŒcÍ.ŞâË«öñÎ	îq` š,ìJoE})è‡¢İLX³Ù/³Õl~ç	:½…ŞŸİ@ˆhrÀ*/ÉMÖîB£ÓÅÍrH*«¸|Nà+üw‹sHˆg¼%ÏR!š,èâ¤{]æÃXÊÄ\CºïêG+é£© ĞÏ³T¶Â;`:ôx€.òŞ€iùC¹zŒzÅ›l>zF×7êú¢¬`ßÔYå6j{ARÃk˜ˆÒ2GÿBßŠ½^ÅVuçQoKR’“ñ›Uô·ğÕ÷©p•ş/¼Q7³Í™ó»z1±Ñ[õ¾Ko Gİ<\/ÏîÚÏï§ùÌ¥ «™¦+e5PöRÑ%$©Æ­ß‡Ä¬QŸH&2Uâ5Ô-ŒµÉÑ+8ÓoyÊÊ<rYqc™Œ7V[Ò»:VUú÷H)iå‘2,á÷_!cúÑ²H™Vf«Õ£Àµi«Ç[‹U”¬¥ú»´‰ÅT&Áÿ„oÍ5ÍP0áh7dªrbğÕ º`¦ÓÍ¶ t#Îkœ`±Ï}uÜ¨9ş“·,¼yË}'üsF{uîQËÈQõó‡MŞº2võˆëêGz®_0=á¹ûÔØq·ÎÜò¾ûNŞ™{ë¸±§î&?¶Ôm.7úÁÃS)}U½gîm×Q>vùS~ÿ#À­G´÷­ÖP§Xë`rh&	İte,g(!ÿéZÜAøÇÎH©×ŸlÜÔxı#µ?66vĞ+RÜØ¸É".îĞÖbo²‘ìpĞæ;#q¯ÉÀëá•Fí#o¸¾Qû\Â?€W|åŸ78M6^?O¨™=à/Ã$°’F»õº—Y·»Õ‹vx"5\·ƒõm“Úwf×ÕÍ®Kqß—¢Ÿ/•:Ç¡¹ğlz´V‡E „'¨5¿cªaÓ¼jèx§™z1XJ!º–P</û#„sàv#PIvè[F–˜š¬<º+õõY•…ÜW„9Š#“«•æSRÁ1ÓGäÊK®owp—ˆ·hæíçVÑqSıW˜'MvÌ©\:{Ò62E´VkN^÷Ğ(qòŞnXÜxõDíqæÕ÷†$ƒÈ¾®v>0rõ‰,â¾Æ?Ë\MsY˜Ô/ªĞMçÇ°€:e=İÙãè3È1Éïkì®Œ6·0Z5%4@~3š-$’Éaq[*Ş{¦Àm2rÕƒ<sß+;GO­ÌtW	†ü<wÌ~z2O’ª†ûJe_-o"ám…2×ÈóEóVŞjÈ7;LÉ-ãRC’Eµ³çU6]U3±Ä+…‡™‹¥2gÀZÉ§¢¼µ	³•”ëşGĞ*øÃYß±Bèö£~A<à”zŠÛ@O`µnÄ|}­zØ¿@Ûq2%˜J0ØMÓŒã˜C^N¡[åbq¿+†v5i‹T{û”Šù/©ÓğGÂ7¦nT1¯9•É>Ùˆ™Ñä<jÉdû«=Ú³3=û5Û^¶‰¸Ş—A 	rÚëj&E·èŞ”êjOÕwîbƒØ4ÆyJ›&Úúçé¢napøel‚ö°‹P4¼¤F°”Xe)Í2 ƒQj/xö\x]8
9Ÿ@aİ0»ˆ¯Ã‚“†©ş:M2rÅDG¡'–.Ğ	³¿.,U•zøô]Ë4 M¡³ÇUŠ-ş})l€ÇeĞNãÃ.›ÀÄ†ş`³=öÓ]jRN&‘™Gmµ¾½Ÿœšæe°q=ãs2jù¦ÌSš¡f³1°¬;_)ÛğKéÎUÂ¹^-	 úã>ÿÉ^øÑU9miãÈ$lçéóz†n„ë.J^ìÛ^!én{×;ï€S\_»ØĞN^EñĞkïkßíÛ^ï|,®W{„S¾€dÚŞ‹¸?Jë@bÓş§_Ímò4©à¦¹E¹cÏĞĞ¯¸Àq(qà¬6lø!§jZ™5Û,èK ô£oæ†poóOÒ%b((*	g2Q+),µŒº{L‘.“mÆü|¶ÈfÌ+  O°l^Ñnä_Òş»Éa2yHd— Ybg[]'Ë“#‡)Ï&¼¥ıªÙ%¹´	nÉÜÅ‹´?´Õ.êÅ1­¾‹t™>qeYzYÕë¯×ÉXOéVO¦CßçE–Õ»hû>İ ··gœ®ºŠaµÂ‰99Iµ úsS’øöíÚ·È‚íäÂÑ»²#µ»uBJo÷éóìñíÚSo‘ÙÛÓ_Yûµì0Ş59›Â´a¾8ŞšíV ‚‡Jİw>:÷¬\¹gå£»òJ,Ïsú:íÎì\4²”O‡®É#“É^"':|Ù~é˜•;ƒ›bè'#p»‹ÅfÒÎ—ÙšŠ^&ps.PŸi£´ú‰90uÿ:İÙÒÌ{èŞ&Ü¹Bó¿zÜÎÎ|B»cW.DÒÆÎÜ¥}|—¶4 ß…#G¿ Q€c,ÊüDüÚI°;t, '·wlÿ„=Ş¹=6?eAÇB/ü“ıaç¹øò94ÈÂÇ´ğœz’·!¤Çp L+Î%ğÛ;¾\£I€ÀÉ\ğô(—é¾üšC¹€Äß®´W´Õüªô|şÈ¥ÓÄ®­"{Ø÷ÒCOy:Eq’èîš•„;§²üŒ;­Ü­êsïHŞ®Ú‹(Ø•s‡1uKOUwûhÚ[†êéÜÇä¥q”ğd‰„8šVJÊ„mÔì«}¼š§{ªÖ®&`Ôs¡ŸÑÎ¯fëT-ªU`ñ|Ú–A°fÚò÷Ó–C¤m‘®İs¢Ì:´»[¼ùÀ=À¿:pàì_¯FµÏn>@S;}Ùò{¿s ç~"”¬ıõ	Ç­[¨‘Á˜YrúÙB·jéÎ’Şvd6qéq¼¡jÔ2ª;W[ŞİNfKİ”Ö§åê+™ın˜=ÁéyÆ°)5»wb°]–€qÎ·ç&õ_:¦GÎi“;Ré34„æ²¾E ópFÑ÷õA0 XEÀ>ˆ!æzü£–€Â‡ÉJp!@o*tTÇkÑ—gºÓ;—ÑU5=Z¯ĞD1¶kO˜¬š!¨iÈícKI6ë¢+õ.äÈ|û
|{E‘öqa Ğ7›h""n·¡©h”şñÓK];ícJµŞ¸R`´Û¡6¿ô¥Wµ«h²Ş¼J÷¢ê”‰Â2]{Õš²q”DRv¶ÇğÚÇ¡Ö·~^tw<İÆñMÑÂB­eb¡f)œ¨µĞˆ	<!mÉ…Â‰¤>YÂ­övèPKPk%-C‡Á(Y;Î>&j­ÙÇLÖ¿ĞDÇ:»Ó#!ETd) cĞ[ï@OM@ŠTE¸İ.¤$Ù§S²hSIhqz?ˆ·*ÁÂ ´Ğ]”4«¿S×/ÃÊ>'SÃoêÂ…0~9Ú	ëUı<ıÀBu¡ö6İìŒÿÿF»\¢¬Gk 	LbÁÂ›“D¾»—ÒúÇİüÆãkKKŸ¶\?vÕØWmY·ş¦V‡Áúë ~ë^óÀG¶t,ºù±:…ìuT[¶òÖ›×­ßÜêâ>á2äÄË™‘ÈıŞ_nåmøÜ
XGe2\—OBÈ„Ó³N«$Áäğ(2ßn(J­;³şºgîL5„F¯û·w©ßD/€0Ë¬’ÏH²’e¬'8½ezĞC«ªy…W©’”ÊJÅlR¥B^áË¥Ç

“Êá²°J{ÃsÓî˜•TGùü²BÉ]\b2u}câO×ò¤Âİfµ£ôŸC–`«#Üw§2©t)nã¥cê‹úöQô	·¤.^Ì|{ Ï¨ïıÕóV‘is®º²zÁÄ€:èµú9ıu·a¨]÷ÕÁ³*–VÕbt™;Í¡ğ{™=	˜7Ò¿^Òg_‚B4g×Ô¸âwÄĞÔÖßŞ¾.=³FØãëÚU´³pƒ¦¾¼ª´“ óa9¹ Ç@¢9kC¢AÖgê¿’2†k—Ãÿ%xàöwü‡ÔíxŒ_0@İ¦†ïG1•jıRCÑÑÈœû¥í?ú¡ 6©÷?Õ«MOVèÓf>‰"ı´„~R€ƒ}èÓÇ;à_Ÿq–)…ûm…ÎrÅĞQ5( NãÊİO›©ôèÔ†ªÄ°àraCpX¢jCD™7sÃìÙföÇkÌù2^Ãx>˜ö±¸ÿ9¦ÓÏ¾˜Õ¨i¢r±…Ùo/ô‹	İî®ã@]ıãÄ@]w˜¯ áı¾ã€à'F0A(}ğğf¾Â–“A“µç)×+t9‰¨“¿ ¤À•Š(dĞávëL¯Rz:RéïPG(™íëºRu|:èxK&ç ‡ã½B+È!	W6åŠ–ÎÛÙ÷¸‚µ3.ó­&ÔÉy~¦Í2´ëúm•® ô“QøI:ĞX®ä;“ª\Ô;uåÆ3KB†ØËŸ^ŞM¿)YD¿ıë÷«’¨êqº†Ç•İÛë“SŞ§^%]Éëó±É.ı)œÓVşeªzÒo[Yµ™l›·¦gSy,›Q[ÙW´eE»Skz4¦µè•Èê=Mü2Ìª	(DWP	j{ =°Ç ŸÀ?@›?­†Ì›H“IÚw±`× Aºµ`%ù]8_=·8D‘eP÷HzXÀ#ã!M:D…=ILXlŒÖ®½¦hÛ¼;f­Æ	‹‡Uó‹'CSƒHÔ·xBAiÙ¢º¹÷®Yñô¼cOˆ7KvÏ„Å>íƒ2z¾"gvtë¦fè92_d¢{otVv}‘LaT„Ğe†:Qm•Š"¿ ­¸s( ‘ªj3Ÿ\JViNC÷è¶¢£××“=¾‡1ƒ™£ëéq£¢äï¡¯g?‘ûûWŸa
Ïœ;²;I:r>…Yüà‹ï©U€¼N-'æ±Ÿ¨©/úÇä~k´@ÿvšh(¯&g3’dH!¡`M2å$Å6›u€µBûsÇëÚFRıË7mÌ~|ÈS;k`µÙüÚkoşR{ƒ¬{½ƒÈPßFùéò;L-ÿhc
ú©``ì'‚AÔ÷öÕ³IPì0y—FœD°ÖIæ£´"~ÊgªàWz|DÄŒÖ0†FuØM3céÜÊq3ºh°¿ulÃPedU¨ÒAõØŠçº‘QåÚ:qYãvÉÅA›½"ı^Íøä\£`\,p“ëjê–&
åZóÄk;Ó«FË£]?çÁ
%Y€@4yØÈûÃraõ[^®İ!"wzìrEÁ56øëGVTH,ç»Ştíç—{|{uÿ*Ü?¡ïÏŠğ Íáç rÜXè 6döƒ:iô?	R[µƒ·Å’3¨UÓ‡GÌ–Èº¨çÁ ïnSÛÚTC}s}}³Š‰ryÅcâ¡‚äÖN ìÃB	uÚ´°R=“û½¬BªóÄzÊ Úì†yIP2Ûy%Phx?­ÓIxz+9ı+ˆ\Y¦-šà©Nom®RV¦mM€¶À2…yUi7Ë°å4éœÎè¤«ø-Hç¶ô}ÙÅ”‡|ø%=>èp9=ÓÓÿ6|¸³¨Øíhm°İ5Ãipºç¹á8ã.[ƒöş­C,CÇ5ãæ6w¥h,‰TIn±Ê-yŸ%ã&­w—¹Š$WÀuÏ$íûÚ@Ãºº!İûëÃïšÀr™µÂTr‚œĞw¸‘ãÚ~=Î%:Wnc,ühşO4åã¬¼hˆ°I+ëÆí©=÷8ÓŠ©Ñ>ŒkÕóÉòÈ³BM´±>³½È.‹Öš›`;¶´qøĞJ[™Õ=e¶C(õ˜Œ6Ë³V½tÔH[ğÛßòH¡q6ç”B,ÀÍ"ìg–ÚâšâJ¹ĞÎ›"“f—E–ßÒèzÏ É¨¯ó5MİŸRÆ‡]î2›œg&¼Á]R7jù¨CdÆ²ğ¬§VT|w{^é¢eûèÀòGø¨¯0é!‚« L}„9(Ü±KùßZ»!}È6Ğv›ÍÆ6+“çk³–æLë\7¶:}Èn_nhgçÜt:qİM2Ìÿí¯üxÚc`d```dpdÒ˜11ßæ+ƒ<Ë ÃÙo¿—ÃèÿVÿç°Şe• r9˜@¢ †ç°xÚc`d``òÿ”øoõï2ë] 
˜ ¤è0 xÚcÜÁ Á²	§ÀØL=¬šq»100Ü„âÅ@>ö€Ğ 9¨ş,ş‚˜óÿ3ã¸Ù|@,Qó£ØÃ²@œU+ĞÃ ¥¡4ĞŒ9P=Œ>˜Í‚¦/‡=0}LHjL‘ü°H+b×÷6T/Ll
”_ŠEı	(İeŸ@bF¨;l ÍŒÃüÆ‚äW>ÄH|%¨ßan=ÅóÑÂ€´¶ÒBˆ0aÔCó«(’>¨X"ÔÏ¬PqV8>1Ÿ,N0ªyL(n€I$qT6K Âú?‚tşÿôÿP®¢ü?ÄD/†ù t© xÚc``Ğ‚Â0†¼ğ Ã9†/ŒRŒNŒ^ŒIŒ³/0şb’aòcÊc:Æ¬Æüˆ%¥ŠeËVÖ8ÖÖ_llYl=lØ°³±Û°±Ïb?Ãş‹Ã€£Šc'§§gç.®8®I\k¸>pëqWp/ã~À#ÅSÆs‰W·Ïˆo?¿ÿşüìÚ¼ä´ôŒì\!¸Oğ•Ÿ‹PŒP‡Ğ¡{Â\ÂIÂS„ïˆH‰ˆtˆÌÙ"rBäÈQQ;Ñ
Ñkbjb=b_ÄUÄÓÄg‰ï‘`‘Ğ˜%±Câ–Ä'I.I'É	’{¤X¤|¤ÖIsI§Hï“~##!ã"S$!»Iö”“\…Ü1yy?ùu
b
&
9
KÎ(|STQRlSÜ§øMÉ@)Ni†Òeeå,å)Ê{”ß¨È¨˜©$¨LS¹¢Ê¡j£š¦:Gõ…š‘Z‹Úuuõ
õ=V9ë4>išiÖhnÓü¡¥§•¢Õ¥uB›I»A{“‘NÎ&ºbºMºk c±yi       Û š             @ .    xÚ­’ÍNÂ@…O‰F$¬»páÆ¦  ÈÊ¸ñh]
B©Jl¥’ø>ƒ7.\úú>…ã™aD‚,Œ‘ff¾¹=÷Ì[ ÌáÄ/ÎhÑ×Kîú¬a	·ŠuÌâ^q»xREŠ'p£eO"£=*BB{S#¿+Æ¢>¯x†¼ª8N>Uü‚„şUÃ+,ı.CÓv{†SõÚ¾YõZØ„‡z¸‚0ğÀ‘‚…$VH¾5PÄ9Ú\w¨ï’…¾	“‘¸|Œ!_îj\k\»œ/¨ÜâÍÏPBÛ<õ û8¢®@/u‡ş6s©·qÍˆ8%ÉLKÖ’Ç1O/3/?Öë§Óòˆ×o+0FòNä=|¾÷d†k*Işî;Ú 2@Uê»ƒkœóhÑµIO¡©3*N®°ã&ÒrdÙ÷r¼åø/5>ÊÇd¶Ë¯ÜaİªÚgTPëß4eVYaå"z²§zZ”õ2.ú™Æ:{á,z:ø?~mˆ{xÚmÔÕ’œU Eá^IH°ww‡ŞÿÒ'CîîNI,¸»»»[pww¹à!x Ókîè›]]Õg3U_Mk\kôóÏÂVÑú¿Ï_­ãßßšÜšÂc"“Xœ%X’¥XšÉLa–e9–oıÍ
¬ÈJ¬Ì*¬Êj¬Î¬ÉZ¬Í:¬Ëz¬ÏlÈFlÌ&lÊflÎlÉVlÍ6´	J*jºôØ–íØØ‘Ø™©Lcˆ]è3Ì®ìÆîìÁìÅŞìÃ¾ìÇşÀÄÁÂ¡ÆáÁ‘ÅÑÃ±ÇñœÀ‰œÄÉœÂtf0“S9YœÎlæ0—3˜Ç™œÅgsç2Ÿó8Ÿ¸‹XÀÅ\Â¥\Æå\Á•\ÅÕ\Ãµ\ÇõÜÀÜÄÍÜÂ­ÜÆíÜÁÜÅİÜÃ½ÜÇı<Àƒ<ÄÃ<Â£<Æã<Á“<ÅÓ<Ã³<Çó¼À‹¼ÄË¼Â«¼Æë,äŞä-ŞæŞå=Şç>ä#>æ>å3>ç¾ä+¾æ¾å;¾ç~ä'~æ~å7~çşœ8}Öü¹32˜bÒÈì™íöÔö¢-Úí±[¸·t+·v·ëöÜ©ƒ-†[OèÌ›3ú¥ö’Æ7íÑõ}DßGô}DßGô½¼ïå}/ï{yßËûíØ‰Ø‰”®½Ø‹½Ø‹½Â^a¯°WØ+ìö
{…½Â^a¯c¯c¯c¯c§c§c§c§c§c§´SÚ)í”¾«´Sz¾ô|éùÊó•ç+ÏW¯|Oe§ò=•½Ê^e¯¶WÛ©íÔvj;µÚNm§¶ÓØi|Wc¯±×Økì5ö{½Æ^×^×^×^×^×^×^×^×^×^×^Ï^Ï^Ï^Ï^Ï^Ï^Ï^oĞ‹¾£ïè;ú¾Óûıàşè:º®£ëè:º®£ëd¬3ø;¢ëè:º£çè9z£çè9zÓñ]º®£çè9:£ß”ÓqÊ±ß{¯~£ßè7ú~£ßè7ú~£ßè7µ=GÇÑqtGÇÑqtGÇÑqtGÇÑqtGÇÑqtGÇÑqtGÇÑqtGÇÑqtGÇóÛ³Ótşûo?q$Ãå´¡Ñªã·şè7‹¦J1˜r0õèÕ`š
Ÿ-|   QÑK(  ‰PNG

   IHDR         \r¨f   bKGD ÿ ÿ ÿ ½§“  rIDATxœíİ[Œ]UÇñï´¥z£®å"PD#¤µ!@I”bHÔ!Æ O(ÆDŞ|!FÔğ€’o1Q0^bD–‹P.l…‚@­¥´´Ó‹íø°†¤"“¡3kíÿÚk?Éÿ±éï¬ÙÿÿÙûœuöI’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’4•‘è *b1°x°r¢–NÔ¢Işİ`;ğ
°øçD=	¬vM­Î9 Ú°8xp.pùÿ¶ÀıÀ}À=Àc™ÿIoÑ{€oñ z¸8§ìK•°¸xŠ¸¦Ÿ¬œÈ¶¼Ø«—êdà&àUâ}ªÚ|8©ÈJHrğ}`ñ}¸µo"ûñÙWEjÜ\à:Ò»it#Ï´¶‘.ÈºBR£VOß¸¹ëq`UÆu’š2\Œß¬¥jlâ5úõ³tˆyÀˆoĞ®ê6Òe4xÇk‰oÊ®ë>ÒîDi°ŞNÚjİŒQµ´kQœÄîâ«¥™Xi0–Òæ'ıÓ­.…¤æwßtµÕZ`áÖUê…›ˆo¶Zë‡3XW©zŸ$¾Éj¯+§½ºRÅÎ$İP#ºÁj¯1Ü1¨ÆŒàuÿáÔZ`Ö´VZªĞg‰oª¾Õ¦µÒRe– /ßP}«m¸S°8O³Êû2~Ç=K¯F‡h¿Ê*ëÒ}óF£ƒôÔ^àtàÅè ­ò ¬/bóÏÄ<àKÑ!Zæ@9‹ç€£¢ƒôÜÒ=wDi‘g å|›?‡Å¤µT€r> !®e!^”±Xë›Ë8é1gë£ƒ´Æ3€2>‚ÍŸÓpytˆ9 ÊøPt€}8:@‹|—Êoø70?:Hcö’;¶+:HK<Èo6	ó€5Ñ!Zã ÈÏƒ´œwGh ¿³¢4ÌµÍÌŸw¸-çÄè ­q äç/ÿÊñIÃ™9 ò;2:@Ã\ÛÌ ùíĞ°ıÑZã Èogt€†¹ 3@~›£4lSt€Ö8 òó -Çáš™ ¿—¢4ÌµÍÌŸïRåxv•™ ?Òr®™9 òó -ÇµÍÌŸi9]eæı ÊØ…?	Îí é'Á¢ƒ´Ä3€2<Èo+6v€2 ù¹¦8 ÊğZ5?×´ @¾[åçšà (Ãkù9 
p ”áÁšŸkZ€ ¯Wós à (cKt€mĞ"@ãÑä€ e¼-:@ƒVFh‘ Œ¢4èÂè -ò· ùÍ!}xttÆìÆ¢ƒ´Ä3€üÎÃæ/apQtˆÖ8 òó -çÑZã Èïìè {Wt€Ö8 òóÑ`å 5€üÜP{2s ä·#:@Ã\ÛÌ ù¹g½c‘™ ¿uÑöht€Ö8 òs ”óxt€Ö¸0¿EÀ6Ò@å3NúÀ_Zfä@~;û£C4èlşì eü):@ƒîŒĞ"@Ğ ‡j~PÆ|Òç ó¢ƒ4b?°œty¥Œ<(c7p_tˆ†¬Åæ/ÂPÎÏ£4äÑZå%@9Ç/à©qà4àÙè -òà,g3ğ×èx›¿@Y7Fh€kX— eÍ6'çè«­À)xÀb<(kğµè=v=6¿zn6ğ0éÃ,ë­×¤3(©÷Ş$¾©úTçMk¥¥JİN|Sõ¥~?Í5Öaò3€î|/:@Ü`(ü ;£ÀË¤ûhr{HwVvëo<èÎàŞè=ğ 6g İz0:@<`H İz(:@¸Fr tëáè =ğ·è Câ‡€İÛ‚OÌN`	iÏ„:à@÷¼møäÖaówÊĞ=/&çÚtÌĞ½G¢TÌĞ1@÷îŠP±{¢Æx85:De6+¢Cg 1îP¡¿D"@OuÿŸC1€— 1^Äõİ8é’è¹àƒã@ŒÍ¸åõPaó‡p ÄùMt€Šü::€ÔµÓğ6a¯×;g¸–R/İC|óE—?ÿä%@¬[£TÀ5Ğ`Í%}ı.U›ğê¡<ˆµøntˆ@7{£CH‘$=ü2úİ¸ëz–t£Tiğ® ¾!»®gY9©·ß”]Õò,™ÔeÀÄ7géz~âµJzƒsIÏˆnÒRµX“mµ¤]C|£–ª«3®“Ô¤Ò]ƒ¢›5wı*ç"I-;‹´G ºisÕ^àô¬+$5îÇÄ7n®úYæµQ&î¬×O£dä ¨”w¤©×2ÒS„ZÒ+HûşU™®Vm#ıP¨ï¶aóWËP·EÈÀ[}UÌP·W¢d°=:€&ç ¨ÛXt€öDĞä uÛ ƒıÑ49@İÆ£dĞÂkh– n-4O¯¡Y€ºµĞ<-¼†f9 TÚÁè šœ n-¼{¶ğšå ¨[ÍÓÂkh– nGDÈ`nt MÎP·…Ñ2há54ËP·Ñ2há54ËP·š§…×Ğ,@İæGÈÀK€ŠyCzÍ^¥ÿô`ş(¨JÔëtúßü s€³£CèÍ9 êÕÒ³óZz-Rq€ÄßÍ7W½,Ï¹@R«F€[ˆoÚÜõKÚØØ$3
ÜL|³–ªßÇf[-©#ÀåÀzâ›´tm®Â-ÂKHÍğ(ñÙu=\œ2ãU”zdğià¤ïÈ£±†z¸/Ô¨ÙÀÀ¤Sàè†«µv“‰öQÚØ©›\ÜFz:Ntsõ­ö wÎ–:Ìµ—BÌ.n6ßD­Ôk¤Œ~ÏT™9øNßezfà/b8øğ<ñM1ÔÚM:3¸¿VTÖš~#ñ¿õ¿µtvéRLÊbp5p/é×Ñº5u­®N{“¿§4¥#H8ù]}ÿëAÒf«EHSXE:ÅßDükå­1ÒçãptˆE¤wˆ‰?H­nê)àë¸yĞV“væùÕİpkğàƒhFñİŞzóÚ@ú=ÂÔœ“€oàî<kêz•ô9Ğ©¨÷Î#}ğ³—øËêW í8¼õÎ%ÀÄDVu7iƒ‘ßTliøcÄ0V›µôÒTÙØøV·õ4ix“Ó`—k‰? ¬aÖßOáo:÷^àâ Ë'm,ºwé_ˆÿ£[Öëüú°ˆÙÀ5¸kÏª¿v“¶¢,V $şkY‡S+ÑŒ\l'şiYÓ©İÀ•è°ÍnÀpXmÔ·yT¨ÆM‹ÛI÷Ñ—Z±–´›pKtCÕ6 FßçJxˆtÛøÑA^7+:ÀÜŠÍ¯vCúqZ5}WÓ¦«€¯D‡
;ØEºÉl¸Z.–‘îäº,:ˆÔ×€3I÷UË©Èç°ù5ÏG‡€zÀ¥Ñ¤UqÌ×r	ğ2iŸ¿4cTğ ÓÀ(iÇTY¤.-^‰PÃ%À‰Øü¦Ñj 'G‚œ †pRt )ˆ €†ËK àèè RåÑj ‹£HAÂY^Ã _)Hø±ï â,Œà â„û )Nø±_Ã ?’‚,ˆPÃ ¨òf‰RÂı€OZÕP…û5 Ÿ°ª¡
?ö Rœğcß Å	?ö Rœğc¿†şAˆ$| H’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’¤©ııCÄŠñ<œa    IEND®B`‚‰PNG

   IHDR  ,  ,   y}u   sRGB ®Îé   bKGD ÿ ÿ ÿ ½§“   	pHYs     šœ   tIMEŞ(	ßÜc  ÅIDATxÚíİ[Œ]Õ}Çñï\ìñŒmŒïæbL1æš.M!iå@…Z!Q/jTU¢‰ZÑö¥o½¼ğRUêKÔ*ª"¥PU ¤M” 1ÁPs5ƒNJls±g|{ÆséÃÍ™3ã3Çç²÷9ßtäq!ïÿŞû7ÿµÎŞk$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$ƒK 
õ+eÀRà| X>=@70^æ†€Óé3€~àpÊËÀR5–¤`Z–‚i°¸X¬NáµX,J¡5›S)¨N ÇSH >öû€Ã)ÄÒÏÇ<2°4Õ¼>}À:àºôÙ\‚«ïï ;Ògo
ºc©S“¥6=÷i¨w#ğ»Àï W]eşÿãuºffûï¤ {ø°=ujce†’ZØÀ#ÀÎ4LJ1>Ãg,µüœí¿;BÌ{H×#éß.©Åu_şxŸ˜+›!DÆ›ü™éß5 ìp”àP­gp?pp=p9ñ­^½‡{õ6{€·€§Ç“jKÅª/ ›SX]S&Šv”û7¿üxØjpIÅs9ğ×À«4f.ªCÆ©Ç±øËtì’
 ø<ğÃªJ‚ëÀÍ©’rªøñf&Ğ›1Q¿/Õ¢ÇËBÊŸÀ¿ß¢XŒ§Z|‡Æ=ø*©7ÿE<ŞêCÀ¹¥!âõ^&Ró}xŠÉ}¶cPÍÖmOËoör‘šç.à9»ªŠ»­gSÀKj°Ï[ìªæÜmm!¾A”Ô — /VU‡Ö6`=>8-Õİ2âU”Ó†UÕ¡5”:­Õ^NÅÒe	
e)ğmà«L^0ÏN¡réº_Âÿe\íTª¹>àÄÒÂk34<<H¼s)©†6+Vµ­÷€{¼¼ªv6¥ĞÊûR0E–ê¸’X"zpĞÒä[§%(„ˆy+ÕÇWRe‡¥.cb-(»«ÚtY¥N«‹XÙá ±«¤*=ŠóVšÏzÜ_âvXªŞ ,Ïtª}§UÒC¼,ı¦e‘æf>ñà¨İUÃº¬QâR×ĞÊ)'İó©›xI÷ÚtŠ¸özÑº¬ñTë«‰—Ê»-‹¥Êôí‹-EÃ-Nµï³Re¿ío ¶gw¸ÖœÏ`:vµvX:‹åÀ}™y·doœR­{Ò9XnI,=°îõ·{Ó»Ü{,Kg·Éë\ª’ÒÙ2°4ƒ5Ä‚¥›Çá`s†…¥àº¸Ğ’X*ïât“(nKçD–Ê¸¸Éá`n†…7YK3wX+-Cn,ÖZKÓ-Ö¥ŸKÏ©9²õ_¬²$–¦ımÏ®×‰wKS\¹1\óª¹²õ¿(XÊ8ŸØÅEù²,XÊXœgrg‰çÅÀÒt½¸B@-ô¼Xš®W€õ¼ÈÀ*ˆ3é£|!V"•¥Œ!à´eÈáô‘¥ŒÄÖéÊ—cKåoŒ£–!wX–¦;BKù;/–%‡„	e`ù7¹CB‡„2°
ó›Ü!¡–¬ÂtX–%«Ng†„®‡Õ\ÙúõI~¸w¾œHº´LsuLé°Æ,‰–¦;i	rûKD–Ê,C®Î‡¯KXšÁ) ß2äF:'2°d`"°-ƒ%ËKV–sXù1€sX–,;,X	UûËÀ2°d`X2°Šn_É“céœÈÀ’roßé4°4£>`…eÈ5À"Ë``©¼•ÀU–!7®NçD–Ê¸¸Ã2äÆíéœÈÀRW—Z†ÜXlÄå~,Msi‚€ø5[¶ş× WXK“]lH?wø[½©²õß ¬·$–&[\`ry^VYK“,±¹³$Xšr.º,C.Ï‹÷‰¥)Î Ã–!w†Ó¹‘¥ŒA\İÒó"« àJyÔk”Xšæc`úÙç°š+[ÿıÀ'–ÄÀÒÌ¥|—O-ƒ¥É û,Cîìõ¼X*o±Ó°Oº7W©şÃÀ¯p«zKe}ì°¹ñğ‘e0°TŞ§ÀöÌßxo¼lÍßÄù+K³Ö[S†&jí–¥™Şµ»jªì/‰·qSK³:ì´MîY
K³ aâ›);­Æ©öÏáî–ÎªxÒsÓôûâ)|UÊÀÒYs'¥É^'Ş§TëÏˆ	w7Q5°T£À³Ll“î°°qÃÁÓÀÄ\¢,UØe}XÁÁ.«±İÕÑT{×À’æøËä%&VËüì§¶Ÿlm_æyùå“Kòæ{ˆ2Ü ,µÓjH‡õ!ğÀk–Cš»à1»¬†} zÙÙa©:£ÄÄûà"»¬ºu²ÀëÀ?/<+Çó$Ê·'‰IG«ºI5şoËa`éÜŒ_³¿’é|Ì¡6U©¯[­«¥ÚxØ’éì´jÓY•êø±”Œ,ÕÀ!œ[©§¸PŸ¥šúøÀ2ÔÜ>ÃÊÀRíõK¨¶vák8–êX»-CÍíÆed,ÕÜ ğ~æï~£U½líŞ7°,ÕŞ	à™¿ûMaõ²µû€‰—Ìe`©†>!Ö·»ªM—5„»mXª›ÓÀ{VÍë=&Ö“¥:–;Ÿ»1ËÀRık·VÍ:¬]©¦2°T'‰×t²7æT%oƒ–ÄÀR}{H÷›Â¹+ÕlØìÃËÀRõ{€KQµ1`/pÀRXªÿæÜQç\†ƒƒÄ2ÈÖÎÀRë7ùíVõN¥Àµ–êk„Ø³ĞÉâê+Œ:¬6°Ô€aÍ^â5ÒzäªL©V¯ä8$4°Ô ¯[ªã7'‡Síd`©~J¼[¨Ê;SRÍµ–ëyà—S†:ªl8ø¼å0°ÔX#ÄSï+ï® ÛñVKM±xË2Tl»ÉÀRƒ½ lsXXñpp+1÷'KM²‰-ÀÜduúP°Tw2á.KMò2ğD¦“°ÓšÜY•êñğ’%‘šïbåRG1–ù¹]?Ù¼j¤‚ë²-á8±VÖİéœvĞŞOÁgığ01Ù>ä¥"åÃ*àQbÍ¬vî´²Ç<|?ÕFRÎ\Al½n`Åg_ª‰¤œñ¿N¼~ÒîõpŸÓ­w«µæn> –>Úk.«t¬‡ïßÁ5¯¤Ü»”ø¿††¥c.ñ2°ÃR1IÆF&&œÛ¡Ëê VıgàU/©8º¿£ıæ°öÔ·.Ÿto]#Ä“İo´Ñ1oO–,Ğ.àÅôs;¼g¸•Ø[–
èWÄ’*­lêÎ{=í­=Ï¡Ö5F<­;é=®ıÄ«8²ÃRA¶ÉqótX*¶aàPçÁt¬2°T`#À‰68Î“¸1ª¥Â¥=–U&æìd`©ÀÆh‰èËÀRk÷e`É›Ùc”¥ßÈã§,ÉK–d‡%ËîÃc”%ofQ–<Ï•*íÇ(/düF^ĞÇ¹ —ü6°ÔÕÛÇÙk`X2°,XrHh`ÉÀ’%K…µ ¸°ó|`¡§ÛÀR±­h£ó¼m0°TX«€MéçV}u%{\×áõ–
kğ[éçí>²Çõ%àO»¥âé~#}ÚÅõÀ@Ÿ§¿u/jµ¦?¾	¬LC¦VŸÛ)ã:b¹ä—½¤bxØÉÄüÎXæçVıdqğ¸Q°”k·ß#¶¨m£°*Z‡€÷xY´¿.¾eÄ<ÕÀ-ÀÍ@Ï”aR;Éó8ğğ:ğğs`¯—Œ¥ÆZ\	¬®NAõëL“oãó[îØwÛÒŸ¿vïy)Xª_'µ¸ø"ğÛÀçÊÜ¨×³×cğğ4ğ>ğ)ğîm`©jó‰×jz€Ë€ßî&5ê4¤jÖy½™Âë§©ûN§,U`aê¢îîJÕUÁÍ§êƒ‹Ôi=<™º¯ÏpéeK3ùJ]Ôiø×›:¬Î9Ütª>°Æ¡Ô]ş7u_Ïß¼ÊÀjk›RHİ\N<ä¹œXyÀ€jl€•»S—uø˜´ÿ9ğ’%3°ÚA'ñ­ŞÄk$›ˆoú~òoRùé¾ ö¿ &êß&—Ø‚MVKè® V¸
¸¸‰x,Á€*n÷u x-…ÖÎ4dü?à %3°Šf>±ÕšN_&^Ê]mHµd÷5l%NıYêÄ'-™•Wóˆ	òE)¤¾l&›2 Ú'Àß4>Bì0œ>2°r¡;…Óï§?/˜e(a`µv`eÏû@
­'Şk<nÉ¬fÖîà~â©ó‹ÅÄÃ³]Äjİğ*w‡€À‘^O©:\4°âZâaÎÛÄ<Õ
Jt_Ç‰‡S÷öÏ[ˆU&d`ÕÌj&VB¸‘X;|m…©®r×Äaà]â±ˆmé³ÇrXÕZB<Ìy%ñ’ñÄŠ”j`»‰'ëw¦à:d©¬³é$6_A¼Ëwñ$ú|ƒJu®r÷àkÄ<×ÿ Ï{9d4°&é&^8^|ø“75 ÔèÎk øOà»©;Ëßxïîİü1±rç<â5™KM¬Ò’ÏcÄüÖ÷“öVºøÃVˆ­¡zú)gÁp†xbğàQ`—Õ¾ü1~¾.£üW¹ûô ğğğCb®ËÀj1‹ˆGîHõy&o¶i@©h×àâıÅ-Ä7ŒG¬âÕb»ö{‰9ªNƒJ-6d|…˜ßzXú¦ßÀ*–Ä·|wš‚ªcJ«mP©†ŒÙëxğoÄ<×GÄ7‹Ê¹eÄc	ï0ó&›~ü´ê²ãÄŞ‹FùùYåÈfbó€a&v>6°ü´[`Ïl½<@ù=6ÑÍÀ7S`­aò“éÎS©]ç·Fˆ5é· ÿ<[ôìjÿ_ E¬ °2ıoãm0O'ÍÖ€Œ§{¡ôÅÓ5À*bIç3Vã]<|‹X'}ş”ß2†•Ú<¼J¿¸»‰õÚ®M¿Ôû‰÷¬X@<KõğçLl‰5nHIÓB«#so,nKÖbíùB½ŸX´ÀZH¬ğù0±±ÃLcwIå»­`cê¶Û–f½ù"VğíV—á<•TM·Uú¿†x z1±×HQB (!]1ÃÉ4· ë!öÊ¼ø±U;“ÂêÂ)C@ç¬¤ê‡ˆÄF¿k‰¹àç¬sÓ|ø[b;÷lXIªMh-J÷×.bëÜ®pš÷Àº ø{b‡ÃJª_h-!æµ~F|ƒ˜Ky~d±fÕ½S
,©ö¡±¿æ­Ä£CÖ­›Vã^[RİİÏÄô‹5«RÚÛ]Iõï²JnM÷5GçÏ[IjœµÄ³YÖ-dú¦’êÏÀªÂ’ôgiIõ“½ÏÎ'§S0y¬>b¿@I‘}{i^»¬¼ÖyÄRÇS)©ş–XÕ–¤Æ²Ãš£Å–d`%°úR—%©ñ/EXêÉkÁ¤6ĞKN)Êk`Í7°¤¦YÀä§¬
k×d`eHh`I–CBI³ê5°JvX–¤ë1°æ¦›XqTRs†\.Ÿ×Àê¤˜»RK­ ËÀ2°¤¢èÌk6ä5°+©y:¬ÖøwIí4,4ì°¤ÂıGÙaIV«Ã"¯É{pªîœë)bâï”×Ôp½ÀÓ–¡2®ß.y/J’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’$I’¤ùuÚ<YØµğ    IEND®B`‚‰PNG

   IHDR   –   –   <qâ   sRGB ®Îé   bKGD ÿ ÿ ÿ ½§“   	pHYs     šœ   tIMEŞ8hÌ  xIDATxÚíÛ‹ÕUÇ?çxm4ÑÒPÒµ‚’ĞÒ©  ‚‚z»H]ˆ"úŠ¤Šz(ƒ
‚è!*
Ò4º¨A”FÓJ˜Ì|H¼Œš3äefN¿ıc~çŒgò\ÖZçûÍ9ÎìıÛëëZkïß¾€B!„B!„B!„B!„B!„è,Jê‚ª”9À, +•"gR9	ô#ê2	«V?ô·k€ÅÀ|àR »ÆïG¿ıÀvàSà PQ—v6K€g“0*…2<æçñÊØÛ<“ş¶è0VŸç’FR©Tù¯Œ÷;çR«ÕİñY |Xğ2õˆçÿ–‘‚G{?Õ-r_J¸'ê•.TXùç™ÔˆUB^+K±ŞWeÿL6·ĞKÕë½6¥¶	§ln£˜Î'²-2O^ksø«',¾%3ùb­AOUËs­—¹|°ø‡‰Mr¶«@ÌfŸOªxË«l•Ùls30d0¯:_¾5Ü*óÙåKbªU¶É|6Ybt8‘Qb˜×å@Âz¢ğİÓr Rgèö8óTÕ<×^`†Ìi‡+Œë!†‡QBáÊôYÁçªØ£«No°ìp£ÃÜªV®µJÂ²Ã¢@a½WÂ²ÃÔ@Âê’°ìĞHX'$,;üHX$,;”°$,y¬ñùKÂ²Ã¡ôéyr%’÷"¬Áôaë”„e‡3Aò/pZÂ²ÃiàX€ç8&aIXÍ _Â²'¬~	KÂjfòëıJ–æ ×xëÒ³HXFX\†ÿy¬ÙÀ
	Ëw‡yÛï’°ìĞ(Wì‘°ì0HX–~+ä)s¬â³HXØÈcí°ìĞGö¾°äÔkå»‹†_%,;váû×.àˆ„e‡!àû Ó;³S¬ÀÿNèUQŒí.cd³×Ş«B¶lVC”ƒ	ëE‡	|¾¤ c—nF¯yót6Ö‰HŞ**ëñwéƒ2›}fûğsi_ÊCQ(¬SÀ7Nøğ-p\ÂòÁçÚú…‚Œ;Ê±–F4@Ô;¡§¦hıx£à"²÷œ
…~*ÌY#oÓd¯£°üë;Ã^9oÓÎÔV	Ë;x¬¯•ûc£÷êXLÜ‡ĞänÙkx4¸/rÇ—ƒk£áüj£şßûep{W÷ ×È<¾ù;“¥y>–Ybx-kÂZ&³Ä`ƒqåuo9b±Ç€ÇÚ-3Ä£§‰|¾Jt¡Ì“‡Ûè­îW÷Çe6ğG½V^Ï~‚¨V/åÖqFW=´‚|ÇĞÏÄºHJÂªB­]ñP"Èı8ÖømCıV|ÎvHV‹iC	KD³„ÕŞC«˜Ü†:§HXñ™Ù!uJX-f†„%a5ƒ…-Ì{ò:æKXñÃàrZwnşJçz(#Fy„ö­nxJİğ4Ù®ãv­Çª /`ÿ<	Q½À£d/­,M>˜D¾MP»bp°5y¨âYŸVÎÍŞ<L—ÙìN!¬6Õ0¢ÕmöÅv}•D6Wæl/’gÚFvÖxF³~jr±­ƒdWŸ< \!3·†iÀd›Pÿ¦ú	Äo¦ûıx
é÷’Ğ&Ì*ààC¯Ô¨Py
x¸Er¸ğİc…]­ĞÑ	eìóN#Ë«%“úCİJà²{q<‡·f‰k¬ÛÜ¤PY{óq²4ãu¤Jí¾ùxRsc‹€çÉNî„¼©ùØÙ…O=(¨%À»c¦	ä¡ãÁòï)¥¸ª5x“l×ŠÄÔš09¼\QPeà9y§¶ça/è%øİix¬ÊÆtÅ‰4áê–.àu|¼·ë¤?ïÿ·“\1ì:	É¶ÛM“ÎšoÆòÜYÀ/)Y¬4±qaäË³¤‘cCÏ—hÆdÚ{•+qÍ%{©oš5JÒİN¬ŞfÙc­+x)y*ûí´Î²°Ğç
‰&…U.‘Ü&ñsÈö˜ÖÅ©q
şÂ!Év3,
kzšj>™I×t5RXSÑ6&ÏL§Ç-5ZXÓd·L£/¨%,a^X“e÷˜j­µ„Õ1L’]$¬f«KvqOÃlØÈÉÌn²“ëÎÉ>.™ü@vv„B!„B!„B!„B!„B!„ä?V-¥sá°¯    IEND®B`‚‰PNG

   IHDR   2   2   ?ˆ±   sRGB ®Îé   bKGD ÿ ÿ ÿ ½§“   	pHYs     šœ   tIMEŞ²D$}  ÚIDAThŞí™½JQ…¿õ'‚´A´S“B«X"H´°DD|ŸGğLamcai%QÔ &"¢¢–‘$»N …‰ûsw3	{`šİ¹³çÜÙ{ïÌ.Äˆ#F/Á
)æòÀ´\Îàp4OL
¸ªBô/«ŠOJ«ˆ !díBš÷ê2Fæ…˜ãÑêš23 ]d¢]fŠ£ëHË{oûÈˆ-cÓ&f3(2ÀˆÏĞ’±Ë„,Êj×…<ˆñ¬AÈk¿ù2ãSƒQ1’„äÄX× ¤d Æ­!w-E¢W8À7p£ádO Nö²ÄPİ µÖ¶ÆªIĞòào™â`²ê|ñHÊŞL–à¦PˆhLèXj.×‰-¾KZÛİ‚‡V÷XûW§Ï£Å¼wáS	£ß6ºŸZ/Iòé*e¶ÿÛµÀ°Vû²>ÜîZeà@ñI`U¶Q'  kÀT”2À)¿¥ÛÍ¶—¢±Õ>€3 ù$°!}ƒÒ~Ä•ä™c¦DlÊû¦€N‚*ÀvĞè0ÒníH8yÆV„p›¡?b.ÄzÌoM˜õ#dVáY5ãGÈ„B!ãínut%‹KËOK¸&FŒ1ú?ò_h…B}š    IEND®B`‚‰PNG

   IHDR         Äé…c   sRGB ®Îé   bKGD ÿ ÿ ÿ ½§“   	pHYs     šœ   tIMEŞ	¬2Ñ%  =IDATHÇµ–ÁJ1†¿¬Ò*jÑ“"êÍƒxPh¯½ŠÓ—ñ=úÚ“HAôT/Ä“¥«Uéö2%v²Ù&şÂÎlæc&›ÉÂU€uàß$Ô0¦ÀÌc±'QÈ™Ì¹óÜt€êø‚d%¶1Šßˆ¿Ù÷ Š ƒÈ0°¬/1I ä#r"s®ø­ı4ò¸ñ1›B -#ïy¿Œ2ål¬İÄdp«dcw)ÚJS9ív´Rõ¯¾Ò»ú!‹³@Èı‚ºà!%d¥¢½²ÎR®gÙ³¥´\ ¯JkwoÀ%°r,Î€k¹^‹}÷IÑ÷ô$ÎJpß:ìúü °¼—”£*ÀÎ# ‘{À¦ÓfÌ’{é®ß vWºI¯P7ÀpÌÿi0Œî”Ef#‹P    IEND®B`‚GIF89a    ó  ÿÿÿ   ÆÆÆ„„„¶¶¶ššš666VVVØØØäää¼¼¼         !şCreated with ajaxload.info !ù 
   !ÿNETSCAPE2.0   ,         çÈIia¥êÍçbK…$F£RA”T²,¥2Sâ*05//Ém¢p!z“ÁÌ0;$Å0Cœ.I*!üHC(A@oƒ!39T5º\Ñ8)¨‡ `Áî´²dwxG=Y
gƒwHb†vA=’0	V\œ\ˆ;	¤¥œŸ;¥ª›H¨Š¢«¬³˜0¶µt%‘Hs‰‹rY<H.Å‰·–	¾’b¿ZbÇOEg:˜GY].À=ÚAßOQœs†æ ê\bÃh.9ì=sgõcŒóeâØ*Ö†f 7D  !ù 
  ,         êÈIiY¤êÍ§YF5FÔ¢RÃ”TbGºJ…»Àì¬Lªdáğ&…Ymxè”Ã \@˜€êÁš °1ü&Rƒ±’H
41Q´Û|V%zv#j0‡
lŒGg{0~‰<<	„[¢[‡h‘x¥¡G©
y©¢©§¯‡£¶–[0—º¼G´‘¨¦P†zœÇhÉ¾¡Ä˜kzÂi—Éy ÑÊh|zÕh’Gİ„âVÅ¢¯é í¹ë\hç[¯ï Ç¤ˆŠõ&•+‘ W7·8àÈ! !ù 
  ,         îÈI)1¤êÍç1G5d]…(•¡RÇ²”T2Œ”jL„{ÃÓ< [Ğ5àM¾à
0Ğ)…
 L¸¤I†ğm…ÍE¨Ç`´p¥U
^f%‚^±Èäu;zz}0„X	
‰S0ewyk<œ%	ŸO££‰“	‘¦z¤ª{’­¬ª|©ª¤¶£¢%¹š»½F¯iŒ1Â”0ˆ‰€‡¦Ë¼Y´›šÃ8ÄxŠ¿’Œ	zŸÉ@‰‰×<İ« â šÇÁŞãè ¯¾ì8ñçY<¯ŒêÉ¥8ó‡§\‡P$µ»¥!’Á  !ù 
  ,         çÈI©¢êÍgEU– Õ R‡a”TBÙ¤á¾p>'¶•¤eõ$ˆ™"ˆ\‡#E1Cn€ÄƒÉ~×Õ J,Ü,Aa•ªUw^4I%PİŞuQ33{0i1T…Ggwy}%ˆ%'Rœ ¡	…Œ=¡ª¦§«£¥§3¶G–%¹”pº½0¦
™³JRo…5È†0IÄ¦mykˆÃxÍTˆ_}È( …×^ââyKsµìœé>i_¨%Õînú=ÙâÚÊqØ4eÍ-MÂ¤D  !ù 
  ,         îÈI)*¨êÍ')E¥d]•¦ÃPR	A”:!­ûzr’‚“œbw“%6€"G¤(d$["‡’øJ±ÀFh­aQP`p%Â†/BFP\cUâ?TĞtW/pG&OtDa_sylD'M˜™œq	Štc˜¥¡¢™¦b ¢2š±D“M:‘µ¹¹d¡ˆ%À¢4%s)À»‘uƒƒE3¸£ YU€‹ tÚ–ææ‘ÆDŠ$æJiMí<àYà;ŠØ°€˜d<“ O‚tXò<q'+B  !ù 
  ,         óÈIiR©êÍ§"J% –¡¦EQZª®ÒïLd’Š÷-Y®¦
õh‚–kèQ¡|€„5áu¾4YøIƒ« NbW‡€u‘‡5›
îÁr‚ö	¬%yb>^%o/rvl9'L’“–—;†‡9—Ÿ›œ“ ™…£”ª9€%‹i9³³¨ C¹"†BB¹µDsÎ^Xf}$P	×{LŞ?P Ê”›O4 ×ĞÀEÓóå’›Vë$¸æÊd JĞ#)…pVÂÀ$ !ù 
  ,         ëÈIiR©êÍ§"J…d]• ¡RÂZN‰*P*«á;Õ$P{*”N‚Àí\EĞò!1UO2İD	™_r6Iöb
¡¤åÔÄH—š8	B—;	²¬"'ªœZÛÚt½’b€K#C'Kˆ‰Œw}?ˆ”‘’K•iz6Š :x‚KAC¨¨Ÿ&}9®tz\\¶¨ªD5;x¹¨±Qd(Ö 	Ë±KWÖÊŠMBàËˆI´éÚˆM=ñË¤søâ¸½8Daƒ¡J`@LG !ù 
  ,         ïÈIiR©êÍ§"J…d]• ¡RÂZN‰*P*«á;Õ$P{*”N‚Àí\EĞò!1UO2İD	™_r6Iöb
¡¤åÔÄH—š8	B—;	²¬"'ªœZÛÚt½’b€K#C'Kˆ‰Gziz6ˆ8}z‰’”~Š›%X„K9ƒ:¢¨0}¤%	¨tz\B±¤lcL¢bQÇÑ 	ÅˆÎÑÅÇ‰ÎÂ İÅ³KÎŞè¸ÅˆäìçÒéÅxÎ(È›PàšX,ÈéÖ‚|/"  !ù 
  ,         ğÈIiR©êÍ§"J…d]• ¡RÂZN‰*P*«á;Õ$P{*”N‚Àí\EĞò!1UO2İD	™_r6Iöb
¡¤åÔÄH—š8	B—;	²¬"'ªœZÛÚt½’b€K#C'Kˆ‰Gziz6ˆ8}z‰’”~Š›%…:„A/C}¦¦†u\¯³h}b¥¦DÂÃ]=¦¨¨Ö 	ÈV)ÓÖ
ÚŠÓĞâÈ9CÓãëDæKè õí¼K¦…¢u	·Ç*00SÊtD  !ù 
 	 ,         ëÈIiR©êÍ§"J…d]• ¡RÂZN‰*P*«á;Õ$P{*”N‚Àí\EĞò!1UO2İD	™_r6Iöb
¡¤åÔÄH—š8	B—;	²¬"'ªœZÛÚt½’b€K#C'Kˆ‰Gz‘‰z5
‘—“”˜ŠŸC…:	„A/C}ªª†u\³·Eh}b©ª6 Å[=ª¥¸¥× Wx&)Ô×ÒI9ˆÔ¬ á@oCÔêT?KŞÆéØdÛïò]øÁB7¡À ‚ 6Ğ«D !ù 
 
 ,         èÈIiR©êÍ§"J…d]• ¡RÂZN‰*P*«á;Õ$P{*”N‚Àí\EĞò!1UO2İD	™_r6IöÆ€ÔÄH—š03ª³”hÕ¸ƒ›aÀ—j U{CIkmbK#‡cK‘’€8	„{a’•8™n›•˜™“¥‡‚V:ˆ/q:M
¯¯Cu€~ ·¸‰Eh³k®¯6 	½ [_¯±ƒ6P</UÙYHF’á9?MÂ%
áGËÌéCákó v¨Ùñ>.]ú6©ğ!‡)Vˆ  !ù 
  ,         ğÈIiR©êÍ§"J…d]U¡RÂZN	Ã”JÀjøN2sK6
±›d‹I€È)
LØH°W¡G6	ÊKX¦ƒì ±’.6¢d°¨~z“hÙÂuur/6 X5ƒI;_†tO#E	{O››ˆ9V¢£œ9£¨4¡©œ±›—;V–C/
€¹6»Ã˜~*½'ÃŠMo¸º»€nÎÇbXÂ:~]+V*ÍmåK_àOÑrK ñ³N@. ê›Ñdù ~ÎqĞ¦äD¢BÖ‹5D  ;         <div class="navbar navbar-inverse navbar-fixed-top">
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
          	   <li <?= ($_REQUEST["group"]=="" && $_REQUEST["view"]=="" ? 'class="active"' : '');?>><a href="<?= FILENAME;?>"><?= trans("Ãœbersicht", "Home");?></a></li>
          	   
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
                  <?php if(getConfigValue("useEnd2EndEncryption", "no")=="yes") {?>
                  <li><a href="<?= FILENAME;?>?crypto=1&view=crypto" onclick="if(window.location != window.parent.location) { window.location.href='<?= FILENAME;?>?view=crypto';return false; } return true;" target="_top"><?= trans("Crypto", "Crypto");?></a></li>
                  <?php } ?>
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
    <br><br>
    <h3><?= trans("Passwort vergessen?", "passwort lost?"); ?> - <a href="<?= FILENAME;?>?do=lost"><?= trans("hier klicken", "click here");?></a></h3>
</form>

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

<?php if(isset($LOST)) { ?>

    <div class="form-signin">
        <?= trans("Vielen Dank!<br/>Ein neues Kennwort wurde erzeugt und per E-Mail gesendet.", "Thanks!<br/>A new password was generated und sent via email.");?>
        <br/><br/>
        <a href='<?= FILENAME;?>?do=login'><?= trans("weiter...", "next...");?></a>
    </div>


<?php } else { ?>

    <form class="form-signin" method="post">

        <?php
        if(isset($ERR)) { echo "<div>".$ERR."</div>"; }
        ?>

        <input type="hidden" name="send" value="do" />
        <h2 class="form-signin-heading"><?= trans("Bitte geben Sie Ihre E-Mailadresse oder Ihren Loginnamen an", "Please enter your email-address or your loginname");?></h2>

        <input type="text" name="recover" class="form-control" placeholder="<?= trans("E-Mail-adresse oder Loginname", "email-address or loginname");?>" autofocus>

        <button class="btn btn-lg btn-primary btn-block" type="submit"><?= trans("Neues Kennwort erzeugen", "create new password");?></button>
        <br><br>
        <p>
            <?= trans("ein neues Kennwort wird erzeugt und an Ihre E-Mailadresse gesendet.", "a new password will be created and sent to your email-address");?>
        </p>
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

<?php if(isset($CRED)) { ?>

    <div class="form-signin">
        <?= trans("Vielen Dank!<br/>Ihr neues Kennwort wurde gespeichert", "Thanks!<br/>your new password is set.");?>
        <br/><br/>
        <a href='<?= FILENAME;?>'><?= trans("weiter...", "next...");?></a>
    </div>


<?php } else { ?>

    <form class="form-signin" method="post">

        <?php
        if(isset($ERR)) { echo "<div>".$ERR."</div>"; }
        ?>

        <input type="hidden" name="send" value="do" />
        <h2 class="form-signin-heading"><?= trans("Bitte geben Sie Ihre altes und das neuen Kennwort an", "Please enter your old password and the new one");?></h2>
        <br>
        <label><?= trans("altes Kennwort","old password");?></label>
        <input type="password" name="oldpassword" class="form-control" placeholder="<?= trans("altes Password", "old password");?>">
        <br>
        <label><?= trans("neues Kennwort","new password");?></label>
        <input type="password" name="newpassword" class="form-control" placeholder="<?= trans("neues Password", "new password");?>">
        <input type="password" name="newpassword2" class="form-control" placeholder="<?= trans("neues Password wiederholung", "new password again");?>">

        <button class="btn btn-lg btn-primary btn-block" type="submit"><?= trans("Passwort Ã¤ndern", "change password");?></button>
        <br><br>
    </form>

<?php } ?>
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
        <?= trans("Dein Name erscheint bei allen Posts die Du machst. AuÃŸerdem wird er angezeigt, wenn ein Nutzer nach Deinen Suchbegriffen sucht und Deinen Eintrag findet. WÃ¤hle also einen Namen, der Deinen Freunden, Kollegen und sonstigen Kontakten etwas sagt.", "Your name appears in all the posts you make. He will also appear when a user searches for your keywords and place your entry. So choose a name that your friends, colleagues and other contacts says something.");?>
    </div>
  </div>

  <div class="form-group">
    <label for="inputProf" class="col-sm-2 control-label"><?= trans("Spezialgebiet", "Profession");?></label>
    <div class="col-sm-5">
      <input type="text" class="form-control" id="inputProf" name="profession" placeholder="<?= trans("Dein Spezialgebiet / Dein Hobby", "Your profession / your hobby");?>" value="<?= htmlspecialchars($data["profession"]);?>">
    </div>
      <div class="col-sm-5">
          <?= trans("Dein Spezialgebiet wird zusammen mit Deinem Namen oder Deinem Bild angezeigt. Es sollte etwas Ã¼ber Dich aussagen, von dem Du mÃ¶chtest, dass Andere Dich so sehen.", "Your profession will be displayed along with your name or your image. It should say something about you, from which you want that others see you as."); ?>
      </div>
  </div>

  <div class="form-group">
    <label for="inputKeyword" class="col-sm-2 control-label"><?= trans("Suchbegriff", "Keyword");?></label>
    <div class="col-sm-5">
      <input type="text" class="form-control" id="inputKeyword" name="keyword" placeholder="<?= trans("Suchbegriffe unter dem Du gefunden werden willst (Leerzeichen separiert)", "Keywords to find your profile (space seperated)");?>" value="<?= htmlspecialchars($data["keyword"]);?>">
    </div>
      <div class="col-sm-5">
          <?= trans("<b>Dies ist eines der wichtigsten Profil-Felder.</b> Trage hier mit Leerzeichen getrennt die Begriffe ein, unter denen Du gefunden werden willst. Das kann Dein Name und auch Deine E-Mailadresse sein. Es kann aber auch ein Phantasie-Begriff sein, den Du nur Deinen besten Freunden nennst. Die anderen Felder, wie Name, Spezialgebiet und E-Mailadresse werden nicht fÃ¼r die Personensuche verwendet.", "<b>This is one of the most important custom profile fields.</b> Insert here with a space separated the terms under which you want to be found. This can be your name and your email address. However, it can also be a fancy term that you call only your best friends. The other fields, such as name, specialty and e-mail address will not be used for people search."); ?>
      </div>
  </div>
  

  <div class="form-group">
    <label for="inputEmail" class="col-sm-2 control-label"><?= trans("E-Mail", "Email");?></label>
    <div class="col-sm-5">
      <input type="email" class="form-control" id="inputEmail" name="email" placeholder="<?= trans("Deine E-Mailadresse", "Your emailaddress");?>" value="<?= htmlspecialchars($data["email"]);?>">
    </div>
      <div class="col-sm-5">
          <?= trans("Trage hier Deine E-Mailadresse ein, wenn Du die Passwort-Vergessen-Funktion nutzen mÃ¶chtest. Du kannst auch Benachrichtigungen einstellen, die Dir per E-Mail signalisieren, dass eine neue Nachricht eingegangen ist. Diese Feld kannst Du aber auch leer lassen.", "Please write your e-mail address if you would like to use the forgotten password feature. You can also set alerts that signal you by e-mail that a new message has arrived. You can also leave this field empty."); ?>
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

<br/>
<h3><?= trans("Zugangsdaten Ã¤ndern", "change credentials");?> - <a href="<?= FILENAME; ?>?do=cred"><?= trans("Hier klicken", "click here");?></a></h3><h1 style='float:left;'><?= trans("Suchen", "Search");?></h1>

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
            <a href="<?= FILENAME;?>?view=notifications&create=1" onclick="return confirm('<?= trans('macht bisherige Feed-Links ungÃ¼ltig!', 'revokes older feed-links');?>');"><?= trans("neuen Feed-Link erzeugen", "create new feed-link");?></a>

        <?php } ?>

        <?php
        #vd($_SERVER);
        #vd($profilData);
        ?>


    </div>

</div>
<h1>Crypto</h1>
<p>

</p>


<div class="row">
    <div class="col-md-6">
        <?= trans("Dein Ã¶ffentlicher SchlÃ¼ssel", "your public key");?>:<br>
        <textarea class="form-control" rows="10" id="pubkey" style="font-family: courier;font-size:0.8em;"></textarea>
        <button onclick="createNewKeyPair();return false;" class="btn btn-default"><?= trans("ein neues SchlÃ¼sselpaar erstellen", "create a new key-pair");?></button>
    </div>
    <div class="col-md-6">
        <?= trans("Dein privater SchlÃ¼ssel", "your private key");?>:<br>
        <div style="display:none;">
            <textarea class="form-control" rows="10" id="seckey" style="font-family: courier;font-size:0.8em;"></textarea>
        </div>
        <button class="btn btn-default showhidePK showPK" onclick="$('.showhidePK').toggle();showPrivate();return false;"><?= trans("zeige meinen privaten SchlÃ¼ssel", "show my private key");?></button>
        <button class="btn btn-default showhidePK hidePK" style="float:left;display:none;" onclick="$('.showhidePK').toggle();hidePrivate();return false;"><?= trans("verstecke meinen privaten SchlÃ¼ssel", "hide my private key");?></button>
    </div>
</div>

<br>
<div class="row">
    <div class="col-md-6">
        <?php if($_COOKIE[SESSKEY."language"]=="de") { ?>
            Du kannst auch ein eigenes SchlÃ¼sselpaar hinterlegen, welches Du zuvor z.B. auf der Console selber erstellt hast.
            Du solltest nicht Deinen privaten SchlÃ¼ssel verwenden, den Du auch fÃ¼r andere Kommunikationswege verwendest, z.B. E-Mail,
            da ein Browser generell nicht als sichere Umgebung fÃ¼r private SchlÃ¼ssel gilt.<br>
            Zwar wird der private SchlÃ¼ssel nicht zum Server Ã¼bertragen, sondern hier in Deinem <a href="http://de.wikipedia.org/wiki/Web_Storage" target="_blank">Web-Storage</a> gespeichert.
            Dennoch kann es z.B. durch einen XSS-Angriff mÃ¶glich werden an diese Daten zu gelangen.<br>
        <?php } else { ?>
            You can also define your own key pair, which you previously created on the console itself.
            You should not use your private key that you use for other communication channels, such as email as a browser generally not regarded as a secure environment for private keys.
            Although the private key is not transferred to the server, but is stored here in your <a href="http://de.wikipedia.org/wiki/Web_Storage" target="_blank">web storage</a>.
            Nevertheless, it may e.g. by a XSS attack to be possible to get that data.
        <?php } ?>
    </div>
    <div class="col-md-6">
           <div class="ownSteps ownStep1">
                <button class="btn btn-default" onclick="startOwn();return false;"><?= trans("Eigenes SchlÃ¼sselpaar eingeben", "Set own keypair");?></button>
           </div>
        <div class="ownSteps ownStep2" style="display:none;">
            <?php if($_COOKIE[SESSKEY."language"]=="de") { ?>
                Bitte trage deinen Ã¶ffentlichen und Deinen privaten SchlÃ¼ssel in die Felder oben ein und speichere.
                Der Ã¶ffentliche SchlÃ¼ssel wird zum Server Ã¼bertragen und ersetzt einen eventuell zuvor vorhandenen.
                Der private SchlÃ¼ssel wird hier auf Deinem Rechner im Web-Storage gespeichert und <b>nicht</b> zum Server Ã¼bertragen.<br>
            <?php } else { ?>
                Please enter your public and your private key in the fields above and save.
                The public key is transmitted to the server and replaces any previously existing.
                The private key is stored here on your computer in the web storage and not transmitted to the server.
            <?php } ?>
            <button class="btn btn-default" onclick="saveKeyPair();return false;"><?= trans("SchlÃ¼sselpaar speichern", "save keypair");?></button>
        </div>
    </div>
</div>
<br/>


<script>
    function startOwn() {
        showPrivate();
        $('.showPK').hide();
        $('.hidePK').show();
        $('#pubkey').val("");
        $('#seckey').val("");

        $('.ownSteps').hide();
        $('.ownStep2').show();

    }

    var myClientID = "";
$(function() {
    $('#pubkey').val( readStore('myPublicKey') );
    $('#seckey').val( readStore('myPrivateKey') );
    //showPrivate();

    myClientID = readStore('myClientID');
    if(myClientID=="") {
        myClientID = (new Date()).getTime();
        writeStore('myClientID', myClientID)
    }

});
function createNewKeyPair() {
    // console.log(parent.ownUnityCrypto);
    var passphrase = parent.ownUnityCrypto.passphrase;
    if(passphrase=="") {
        passphrase = prompt("Passphrase");
    }
    //console.log(passphrase);
    if(passphrase===null) return;
    if(passphrase=="") passphrase = "ownunity";
    parent.ownUnityCrypto.passphrase = passphrase;

    if(passphrase=="ownunity") {
        writeStore("myPassphrase", "default");
    } else {
        writeStore("myPassphrase", "unique");
    }

    if($('#pubkey').val()=="" || confirm('<?= trans('Sicher?\nDu hast dann keinen Zugriff mehr auf die zuvor verschlÃ¼sselten Nachrichten.', 'Sure?\nIf you generate new keys, you can not decrypt your old messages.');?>')) {
        var oldkey = $('#pubkey').val();
        var mykeys = openpgp.generateKeyPair({numBits: 1024, userId: "", passphrase: passphrase});
        $("#pubkey").val( mykeys.publicKeyArmored );
        $("#seckey").val( mykeys.privateKeyArmored );

        showPrivate();

        saveKeyPair();

        //writeStore("myPassphrase", passphrase);

    }
}

function saveKeyPair() {

    $.ajax({
        "url": filename,
        "type": "post",
        "data": {"action": "setmypubkey", "clientID": myClientID, "pubkey": $("#pubkey").val()},
        "dataType": "json",
        "success": function(data){
        }
    });

    writeStore("myPublicKey",  $("#pubkey").val() );
    writeStore("myPrivateKey", $("#seckey").val() );
}

function showPrivate() {
    $("#seckey").closest("div").fadeIn();
    /*
    setTimeout(function() {

            $('.showhidePK').toggle();
            hidePrivate();
    }, 10000);
    */
}
function hidePrivate() {
    $("#seckey").closest("div").fadeOut();
}
</script><script>
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
					<?= trans("EintrÃ¤ge", "Posts");?>
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
    <div class="postAround">
	<div class='post' id='<?= htmlid($P[$i]->data["id"]); ?>' rel='<?= $P[$i]->data["id"]; ?>' style='xcursor:pointer;padding: 10px 10px 10px 10px;margin-top:10px;background-color: #ffffff;color:#494949;border: solid 1px #ececec;'
		xonclick="if($(this).attr('rel')=='') {$(this).attr('rel', 'locked'); $(this).find('.functions').slideDown();} else if($(this).attr('rel')=='*') $(this).attr('rel', ''); ">
	
		<div style="max-height:150px;overflow:hidden;" xonclick="expand(this);return false;$(this).closest('.post').find('.functions').slideToggle();" class='outerContent'>

            <table width="100%" cellspacing="0" cellpadding="0"><tr><td valign="top" width="50">

			    <img src='<?= $sender["smallimage"];?>' width=50 height=50 style="float:left;border-radius: 10px;margin-right:10px;">

            </td><td valign="top">

                <div style='text-align:right;float:right;margin-left: 10px;' class="treeeditlink">
                    <span style='font-size:0.8em;'><?= formatDateHuman($P[$i]->data["data"]["date"]); ?></span>
                    &nbsp;
                    <a href='#' onclick="if(confirm('<?= trans("Diesen Beitrag lÃ¶schen?\\nEr wird nur in Deiner Anzeige entfernt.\\nAndere EmpfÃ¤nger kÃ¶nnen ihr weiterhin sehen." ,"Really delete this post?\\nIt will only disappear your view.\\nOther recipients can still see this post.");?>')) {
                        window.location='<?= FILENAME;?>?action=delete&id=<?= $P[$i]->data["id"]; ?>';
                    } return false;" style="color:gray;"><i class="glyphicon glyphicon-remove-circle"></i></a>

                    <br>
                    <div style='float:right;font-size:0.8em;max-width:200px;'>sichtbar fÃ¼r:
                    <?php
                    /*
                    for($ri=0;$ri < count($P[$i]->data["data"]["recipientNames"]);$ri++) {
                        $P[$i]->data["data"]["recipientNames"][$ri] = "<a href='".FILENAME."?profile=".$P[$i]->data["data"]["recipients"][$ri]."'>".$P[$i]->data["data"]["recipientNames"][$ri]."</a>";
                    }
                    */
                    echo implode(", ", array_merge(array("mich"), $P[$i]->data["data"]["recipientNames"]));
                    ?><br>
                        <a href='<?= FILENAME;?>?full=<?= $P[$i]->data["id"]; ?>'><i class='glyphicon glyphicon-fullscreen'></i>&nbsp;<?= trans("vollstÃ¤ndige Ansicht", "full view");?></a>
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
            </td></tr></table>

        </div>
        <div style="height:0px;position:relative;top:-6px;width:48px;text-align:center;">
            <a href="#" onclick="expand(this);$(this).find('.updowns').toggle();return false;">
                <?php /*
                <div class='updowns' style="color:gray;"><i class="glyphicon glyphicon-chevron-down"></i><i class="glyphicon glyphicon-chevron-down"></i><i class="glyphicon glyphicon-chevron-down"></i></div>
                <div class='updowns' style="color:gray;display:none;"><i class="glyphicon glyphicon-chevron-up"></i><i class="glyphicon glyphicon-chevron-up"></i><i class="glyphicon glyphicon-chevron-up"></i></div>
                */ ?>
            </a>
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
				
				<a href='<?= FILENAME;?>?full=<?= $P[$i]->data["id"]; ?>'><i class='glyphicon glyphicon-fullscreen'></i>&nbsp;<?= trans("vollstÃ¤ndige Ansicht", "full view");?></a>
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

                <table width="100%" cellspacing="0" cellpadding="0"><tr><td valign="top" width="25">
                    <img src='<?= $senderC["miniimage"];?>'  width=25 height=25 style="float:left;border-radius: 5px;margin-right:10px;">
                </td><td valign="top">
                    <div style='float:right;'><?= formatDateHuman($comments[$j]->data["data"]["date"]); ?></div>
                    <div style='float:left;'>
                        <span style='color: #800000;font-weight: bold;'><?= $senderC["name"]; ?></span>
                    </div>
                            <div style='clear:both;'></div>
                    <div style='float:left;'>
                        <?= prepareText($comments[$j]->data["data"]["text"]);?>
                    </div>
                    <div style='clear:both;'></div>

                 </td></tr></table>
			</div>
		<?php } ?>


        <div>
            <form method="post">
                <input type="hidden" name="action" value="newcomment" />
                <input type="hidden" name="id" value="<?= $P[$i]->data["id"]; ?>" />
                <div class="input-group">
                    <input type="text" class="form-control commenttext" name="replytext"  placeholder="<?= trans("einen Kommentar auf diesen Beitrag hinterlassen", "comment on this post");?>" />
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-default"><?= trans("Ok", "ok")?></button>
                    </span>
                </div>
            </form>
        </div>

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
			alles += '<a href="<?= FILENAME;?>?full='+id+'"><i class="glyphicon glyphicon-fullscreen"></i><?= trans("vollstÃ¤ndige Ansicht", "full view");?></a>';
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
	var istH = $(obj).closest(".postAround").find(".outerContent").height();
	var sollH = $(obj).closest(".postAround").find(".outerContent").find(".innerContent").height();
	
	$(obj).closest('.postAround').find('.functions').slideToggle();


	openreply(obj);
		
	//console.log([istH, sollH]);
	if(sollH<istH) return;

	
	if(istH<sollH) {
		$(obj).attr("rel", istH);
		expandBig = true;
		$(obj).closest(".postAround").find(".alles").slideUp(function() {
			//$(this).remove();
			
		});
		
	} else {
		sollH = $(obj).attr("rel");
		expandBig = false;
		$(obj).closest(".postAround").find(".outerContent").css("max-height", istH);
		$(obj).closest(".postAround").find(".alles").slideDown(function() {
			//$(this).remove();
			 
		});
		
	}
	
	
	 $(obj).closest(".postAround").find(".outerContent").animate({
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
	if(rel=="allemeine") rel = "<?= trans('alle ursprÃ¼nglichen EmpfÃ¤nger dieser Nachricht', 'all recipients of the post');?>";
	else if(rel=="bekannte") rel = "<?= trans('alle EmpfÃ¤nger dieses Posts, die auch mit mir verbunden sind', 'all reipients of this post, who are connected with me');?>";
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
<button onclick="getOlderPosts();return false;" class="btn btn-default"><i class='glyphicon glyphicon-download'></i> <?= trans("Ã¤ltere BeitrÃ¤ge laden", "load older posts");?> <i class='glyphicon glyphicon-download'></i></button>
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
			<a href='<?= FILENAME;?>?profile=<?= $_REQUEST["profile"];?>&action=withdrawConnection' class="btn btn-default"><?= trans("Verbindungsanfrage zurÃ¼ckziehen", "withdraw request");?></a>
		
		<?php } else if($status=="request") { ?>
				
			<?= trans("Verbindungsanfrage liegt vor", "requested for connection"); ?><br>
			<br/>
			<a href='<?= FILENAME;?>?profile=<?= $_REQUEST["profile"];?>&action=acceptConnection' class="btn btn-success"><?= trans("Verbindungsanfrage bestÃ¤tigen", "accept request");?></a>
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
			<?= trans("AnhÃ¤ngen", "Append");?>
		</div>
		</div>
		
		<div style="background-color: white;border: solid 1px #ececec;">
			
			<?php if(!isfilled($_GET["edit"])) { ?>
			<div style='padding: 10px;'>
				<form method="post" id="detailAddPostForm" onsubmit="sendaddpost(this);return false;">
					<input type="hidden" name="id" class='id' value="<?= $_GET["full"];?>" />
					<input type="hidden" name="action" value="addpost" />
					<textarea id="addposttext" name="addposttext" class="form-control addposttext" placeholder="<?= trans("ZusÃ¤tzlicher Text", "additional text");?>"></textarea>
					
					<button class="btn btn-success btn-xs"><?= trans("anfÃ¼gen", "append");?></button>
				</form>
			</div>
			<hr/>
			<?php } ?>
			<div style='padding: 10px;'>
			
				<div style='display:none;'>
					<a href='#' onclick="$('#fileuploaddiv').slideDown();$(this).closest('div').slideUp();return false;"><i class='glyphicon glyphicon-file'></i>&nbsp;<?= trans("Dateien/Bilder anhÃ¤ngen", "append files/pictures");?></a>
					<!--
					&nbsp;&nbsp;
					<a href='<?= FILENAME;?>?full=<?= $P->data["id"]; ?>'><i class='glyphicon glyphicon-picture'></i>&nbsp;<?= trans("Bilder anhÃ¤ngen", "append pictures");?></a>
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
							<button class="btn btn-success btn-xs"><?= trans("Ã¼bertragen", "transmit");?></button>
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
		<button class="btn btn-success"><?= trans("Ã„nderungen speichern", "save changes");?></button>
	</div>
	<div style='float:right;'>
		<a href='#' onclick="window.location='<?= FILENAME;?>?full=<?= $P->data["id"]; ?>';return false;" class="btn btn-danger"><?= trans("Ã„nderungen verwerfen", "cancel");?></a>
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
				    <!-- <a href='#' onclick="openreply(this);$(this).hide();return false;"><i class='glyphicon glyphicon-comment'></i>&nbsp;<?= trans("kommentieren", "add comment");?></a> -->
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

                <table width="100%" cellspacing="0" cellpadding="0"><tr><td valign="top" width="25">
                    <img src='<?= $senderC["miniimage"];?>'  width=25 height=25 style="float:left;border-radius: 5px;margin-right:10px;">
                </td><td valign="top">
                    <div style='float:right'><?= formatDateHuman($comments[$j]->data["data"]["date"]); ?></div>
                    <div style='float:left;'>
                        <span style='color: #800000;font-weight: bold;'><?= $senderC["name"]; ?></span><br/>
                        <?= prepareText($comments[$j]->data["data"]["text"]);?>
                    </div>
                    <div style='clear:both;'></div>
                </td></tr></table>
			</div>
		<?php } ?>

    </div>
	<?php } ?>

<?php if($P->data["data"]["newformcommenttype"]!='keine' || $P->data["user"]==me()) { ?>
    <div>
        <form method="post">
            <input type="hidden" name="action" value="newcomment" />
            <input type="hidden" name="id" value="<?= $P->data["id"]; ?>" />
            <div class="input-group">
            <input type="text" class="form-control commenttext" name="replytext"  placeholder="<?= trans("einen Kommentar auf diesen Beitrag hinterlassen", "comment on this post");?>" />
            <span class="input-group-btn">
                    <button type="button" class="btn btn-default"><?= trans("Ok", "ok")?></button>
            </span>

            </div>
        </form>
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
					<?= trans("EintrÃ¤ge", "Posts");?>
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

                            <b><?= trans("Datei Ã¼bertragen", "Append file");?>:</b>
                            <div style="margin-bottom:5px;">
                                <input type='file' multiple=true onchange="uploadFile(this, 'postfile', 'newposttext', -1);">
                            </div>

							<?php
							$P = new profile();
							$C = $P->getContacts();
                            #vd($C);
							?>
							<?php if(count($C)>0) { ?>
							<div>
                                <b><?= trans("Sichtbar fÃ¼r", "Visible for");?>:</b><br/>
                                <select class="form-control newformrecipienttype" onchange="recipSelect(this);">
                                    <option value='alle'><?= trans("alle meine ".count($C)." Kontakte", "all my ".count($C)." contacts");?></option>
                                    <option value='ausgewaehlte' selected><?= trans("ausgewÃ¤hlte Kontakte", "selected contacts");?></option>
                                    <option value='mich'><?= trans("mich", "just me");?></option>
                                </select>
							</div>
							<?php } ?>
							
							<div style="margin-top:5px;display:block;" id='kontaktauswahl'>
							
                                <?php for($i=0;$i<count($C);$i++) { ?>
                                    <div style='float:left;border: solid 1px silver;padding:3px; margin:0 3px 3px 0;border-radius:5px;min-width:100px;text-align:center;font-size:8pt;' onclick="$(this).find('input[type=checkbox]').click();">
                                        <label style="margin:0;">
                                            <input type='checkbox' class='recipientcheckbox' value='<?= $C[$i]["key"];?>' style='vertical-align: bottom;position: relative;top: -1px;'>
                                            <?= $C[$i]["name"];?>
                                            <?php if(count($C[$i]["pubkeys"])>0) { ?>
                                                <img title="<?= trans('Kontakt hat einen public-key hinterlegt.', 'contact has a public key');?>" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABYAAAAWCAYAAADEtGw7AAACNElEQVQ4je3STUgTABiH8b+aCJqaH4mbZEWHooJhhVl0KfvAygxzTrNPCCuyWpcopZP4USgeVIxAAkvB6iQmBkFRFoGU0ykpMdSpKSjMoKltsaeDNRDqkOc98F7ew+/yvlKgQP8sXYrbJ2XmREbfL969eeTWrk2OgoT45qMKKzokmZaFnouJrKjNTnO+rsll8q0V39Q9fFMVuHqtDL3IpzJ9jWNZcN3epBlmrwPlQDVQB9QDDcANOi5H/DiukEKLlJUnHfszuVKmRcqwSBlm6YBZClkCP9ifMOHtS2eifQ9MZwG5gAUoAN9hZj9swP4kBXuzCXuTCftDE/ZGE7YGEz21Jmy1Kby5s5UrsZFPM6VwP/woxzDTVhjvzpbK31WmzcN6IBlYi3simTmHEe+4Ae+YEY/TgHfcgMdpwONc3HtGjXgG43E0JnEzObzLD7fkr3Z1Xot15yi4tKti2xw/Q2FBsCDmh4SnV3gHhO+LwCF4Lxj8PR2CAcFnMVwmrHFBbX74sTl21jeQwGR7FAyugnGBUzAm+CpwCSaE+6WYfiY6Tov+UjFaJaq3aLg9W9/7reJqhFq3S6F+uPVszDwjUTAYBJ8E3YKPApugX0y1CFupKElU3xmp2CIdPC8VXQhWmVlaaZY2npIuZkhhS453d0fY2EBdNN86w6F7BfQs4p5XYqZVlBjVky0dMUuJ//VuedK6AslSFKX6qlTZmk7IVbNTQ7eT9PxSqKpOSqn/BQYK9Nd+ARvjT1uaJYQXAAAAAElFTkSuQmCC" align="absmiddle">
                                            <?php } ?>
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
                                <b><?= trans("EmpfÃ¤nger dÃ¼rfen bearbeiten", "Editable");?>:</b><br/>
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
									alert("keine EmpfÃ¤nger markiert!");
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
					<?= trans("EintrÃ¤ge", "Posts");?>
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
    <div class="postAround">
	<div class='post' id='<?= htmlid($P[$i]->data["id"]); ?>' rel='<?= $P[$i]->data["id"]; ?>' style='xcursor:pointer;padding: 10px 10px 10px 10px;margin-top:10px;background-color: #ffffff;color:#494949;border: solid 1px #ececec;'
		xonclick="if($(this).attr('rel')=='') {$(this).attr('rel', 'locked'); $(this).find('.functions').slideDown();} else if($(this).attr('rel')=='*') $(this).attr('rel', ''); ">
	
		<div style="max-height:150px;overflow:hidden;" xonclick="expand(this);return false;$(this).closest('.post').find('.functions').slideToggle();" class='outerContent'>

            <table width="100%" cellspacing="0" cellpadding="0"><tr><td valign="top" width="50">

			    <img src='<?= $sender["smallimage"];?>' width=50 height=50 style="float:left;border-radius: 10px;margin-right:10px;">

            </td><td valign="top">

                <div style='text-align:right;float:right;margin-left: 10px;' class="treeeditlink">
                    <span style='font-size:0.8em;'><?= formatDateHuman($P[$i]->data["data"]["date"]); ?></span>
                    &nbsp;
                    <a href='#' onclick="if(confirm('<?= trans("Diesen Beitrag lÃ¶schen?\\nEr wird nur in Deiner Anzeige entfernt.\\nAndere EmpfÃ¤nger kÃ¶nnen ihr weiterhin sehen." ,"Really delete this post?\\nIt will only disappear your view.\\nOther recipients can still see this post.");?>')) {
                        window.location='<?= FILENAME;?>?action=delete&id=<?= $P[$i]->data["id"]; ?>';
                    } return false;" style="color:gray;"><i class="glyphicon glyphicon-remove-circle"></i></a>

                    <br>
                    <div style='float:right;font-size:0.8em;max-width:200px;'>sichtbar fÃ¼r:
                    <?php
                    /*
                    for($ri=0;$ri < count($P[$i]->data["data"]["recipientNames"]);$ri++) {
                        $P[$i]->data["data"]["recipientNames"][$ri] = "<a href='".FILENAME."?profile=".$P[$i]->data["data"]["recipients"][$ri]."'>".$P[$i]->data["data"]["recipientNames"][$ri]."</a>";
                    }
                    */
                    echo implode(", ", array_merge(array("mich"), $P[$i]->data["data"]["recipientNames"]));
                    ?><br>
                        <a href='<?= FILENAME;?>?full=<?= $P[$i]->data["id"]; ?>'><i class='glyphicon glyphicon-fullscreen'></i>&nbsp;<?= trans("vollstÃ¤ndige Ansicht", "full view");?></a>
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
            </td></tr></table>

        </div>
        <div style="height:0px;position:relative;top:-6px;width:48px;text-align:center;">
            <a href="#" onclick="expand(this);$(this).find('.updowns').toggle();return false;">
                <?php /*
                <div class='updowns' style="color:gray;"><i class="glyphicon glyphicon-chevron-down"></i><i class="glyphicon glyphicon-chevron-down"></i><i class="glyphicon glyphicon-chevron-down"></i></div>
                <div class='updowns' style="color:gray;display:none;"><i class="glyphicon glyphicon-chevron-up"></i><i class="glyphicon glyphicon-chevron-up"></i><i class="glyphicon glyphicon-chevron-up"></i></div>
                */ ?>
            </a>
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
				
				<a href='<?= FILENAME;?>?full=<?= $P[$i]->data["id"]; ?>'><i class='glyphicon glyphicon-fullscreen'></i>&nbsp;<?= trans("vollstÃ¤ndige Ansicht", "full view");?></a>
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

                <table width="100%" cellspacing="0" cellpadding="0"><tr><td valign="top" width="25">
                    <img src='<?= $senderC["miniimage"];?>'  width=25 height=25 style="float:left;border-radius: 5px;margin-right:10px;">
                </td><td valign="top">
                    <div style='float:right;'><?= formatDateHuman($comments[$j]->data["data"]["date"]); ?></div>
                    <div style='float:left;'>
                        <span style='color: #800000;font-weight: bold;'><?= $senderC["name"]; ?></span>
                    </div>
                            <div style='clear:both;'></div>
                    <div style='float:left;'>
                        <?= prepareText($comments[$j]->data["data"]["text"]);?>
                    </div>
                    <div style='clear:both;'></div>

                 </td></tr></table>
			</div>
		<?php } ?>


        <div>
            <form method="post">
                <input type="hidden" name="action" value="newcomment" />
                <input type="hidden" name="id" value="<?= $P[$i]->data["id"]; ?>" />
                <div class="input-group">
                    <input type="text" class="form-control commenttext" name="replytext"  placeholder="<?= trans("einen Kommentar auf diesen Beitrag hinterlassen", "comment on this post");?>" />
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-default"><?= trans("Ok", "ok")?></button>
                    </span>
                </div>
            </form>
        </div>

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
			alles += '<a href="<?= FILENAME;?>?full='+id+'"><i class="glyphicon glyphicon-fullscreen"></i><?= trans("vollstÃ¤ndige Ansicht", "full view");?></a>';
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
	var istH = $(obj).closest(".postAround").find(".outerContent").height();
	var sollH = $(obj).closest(".postAround").find(".outerContent").find(".innerContent").height();
	
	$(obj).closest('.postAround').find('.functions').slideToggle();


	openreply(obj);
		
	//console.log([istH, sollH]);
	if(sollH<istH) return;

	
	if(istH<sollH) {
		$(obj).attr("rel", istH);
		expandBig = true;
		$(obj).closest(".postAround").find(".alles").slideUp(function() {
			//$(this).remove();
			
		});
		
	} else {
		sollH = $(obj).attr("rel");
		expandBig = false;
		$(obj).closest(".postAround").find(".outerContent").css("max-height", istH);
		$(obj).closest(".postAround").find(".alles").slideDown(function() {
			//$(this).remove();
			 
		});
		
	}
	
	
	 $(obj).closest(".postAround").find(".outerContent").animate({
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
	if(rel=="allemeine") rel = "<?= trans('alle ursprÃ¼nglichen EmpfÃ¤nger dieser Nachricht', 'all recipients of the post');?>";
	else if(rel=="bekannte") rel = "<?= trans('alle EmpfÃ¤nger dieses Posts, die auch mit mir verbunden sind', 'all reipients of this post, who are connected with me');?>";
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
<button onclick="getOlderPosts();return false;" class="btn btn-default"><i class='glyphicon glyphicon-download'></i> <?= trans("Ã¤ltere BeitrÃ¤ge laden", "load older posts");?> <i class='glyphicon glyphicon-download'></i></button>
</center>




                <!--
                <textarea rows="5" id="c0" class="form-control"></textarea>
				<textarea rows="5" id="c1" class="form-control"></textarea>
                <textarea rows="5" id="c2" class="form-control"></textarea>
                <textarea rows="5" id="c3" class="form-control"></textarea>
                -->

			</div>
		</div>
	</div>
</div>


<?php
if(isset($_GET["crypto"]) && $_GET["crypto"]==1) { ?><?php
				if(!is_array($tempfn)) $tempfn = array();
				$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php"; 
				file_put_contents($fn, getRes("templates/crypto_frameset.tpl")); 
				include $fn;
				unlink(array_pop($tempfn));
				?>
<?php exit; } ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="easy to install private community system">
<meta name="author" content="Aresch Yavari">
<meta name="version" content="1.20140603140625">
<title><?= getConfigValue("pagetitle", "My own hosted community");?></title>
<link rel="stylesheet" href="<?= FILENAME;?>?RES=resources/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= FILENAME;?>?RES=resources/css/styles.css">
<link rel="stylesheet" href="<?= FILENAME;?>?RES=resources/css/tree.css">
<LINK REL="SHORTCUT ICON" HREF="<?= FILENAME;?>?RES=resources/images/favicon.ico">

<meta name="apple-mobile-web-app-capable" content="yes" />
<link rel="apple-touch-icon" href="<?= FILENAME;?>?RES=resources/images/ownunity.png"/>

<link rel="apple-touch-icon-precomposed" sizes="144x144" href="<?= FILENAME;?>?RES=resources/images/ownunity.png">
<link rel="apple-touch-icon-precomposed" sizes="114x114" href="<?= FILENAME;?>?RES=resources/images/ownunity.png">
<link rel="apple-touch-icon-precomposed" sizes="72x72" href="<?= FILENAME;?>?RES=resources/images/ownunity.png">
<link rel="apple-touch-icon-precomposed" href="<?= FILENAME;?>?RES=resources/images/ownunity.png">
<link rel="shortcut icon" href="<?= FILENAME;?>?RES=resources/images/ownunity.png">

















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

                    <?php if($_GET["do"]=="lost") { ?>
                        <?php
				if(!is_array($tempfn)) $tempfn = array();
				$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php"; 
				file_put_contents($fn, getRes("templates/lost.tpl")); 
				include $fn;
				unlink(array_pop($tempfn));
				?>
                    <?php } ?>
                    <?php if($_GET["do"]=="cred") { ?>
                        <?php
				if(!is_array($tempfn)) $tempfn = array();
				$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php"; 
				file_put_contents($fn, getRes("templates/credentials.tpl")); 
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
                        <?php } else if(isset($_GET["view"]) && $_GET["view"]=="crypto") { ?>
                            <?php
				if(!is_array($tempfn)) $tempfn = array();
				$tempfn[] = $fn = myPath."/files/ownunity/cache/tmp_".md5(microtime(true).rand()).".php"; 
				file_put_contents($fn, getRes("templates/crypto.tpl")); 
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
<script src="<?= FILENAME;?>?RES=resources/js/utils.js"></script>

<script src="<?= FILENAME;?>?RES=resources/js/json2.js"></script>
<script src="<?= FILENAME;?>?RES=resources/js/jstorage.js"></script>

<script src="<?= FILENAME;?>?RES=resources/js/openpgp.min.js"></script>
<script src="<?= FILENAME;?>?RES=resources/js/crypto.js"></script>
</body>
</html>
