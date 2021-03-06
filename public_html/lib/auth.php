<?php


 function encryptpwd($pass){
    global $config;
    return trim(base64_encode(openssl_encrypt($pass,$config['osslcipher'],$config['ckey'],$options=0,$config['civ'])));
  }

  function decryptpwd($pass){
    global $config;
    return trim(openssl_decrypt(base64_decode($pass),$config['osslcipher'],$config['ckey'],$options=0,$config['civ']));
  }

  function packlcookie($pass){
    $a=func_get_args();
    $exstr=implode(",",array_slice($a,1));
    if(strlen($exstr)) $exstr=",$exstr";
    return encryptpwd($_SERVER['REMOTE_ADDR'].",".$pass.$exstr);
  }

  function ipmatch($mask,$ip){
    $pos=strpos($mask,"*");
    if($pos===false) {
      $pos=strlen($mask);
      if(strlen($ip)>$pos) return false;
    }
    $mask=substr($mask,0,strpos($mask,"*"));
    if($mask==substr($ip,0,$pos)) return true;
    return false;
  }

  function unpacklcookie($pass){
    $p=decryptpwd($pass);
    $pa=explode(",",$p);
    $p1=explode(".",$pa[0]);
    $p2=explode(".",$_SERVER['REMOTE_ADDR']);
    if(!strlen($pa[2]) && (!($p1[0]==$p2[0] && $p1[1]==$p2[1]))) {
      // old-style lcookie, no /16 match
      return "";
    } else if(!strlen($pa[2])) {
      return $pa[1];
    }
    $i=2;
    while(strlen($pa[$i])){
      if(ipmatch($pa[$i],$_SERVER['REMOTE_ADDR']))
        return $pa[1];
      ++$i;
    }
    return "";
  }


  function packsafenumeric($i){
    global $loguser;
    return encryptpwd($i.",".$loguser[id]);
  }

  function unpacksafenumeric($s,$fallback=-1){
    global $loguser;
    $a=explode(",",decryptpwd($s));
    if($a[1]!=$loguser[id]) return $fallback;
    else return $a[0];
  }
  
  // if you need to use tokens, please use these functions (and make sure the auth key is either in $_GET['auth'] or $_POST['auth'])
  // DO NOT USE MD5 STUFF DIRECTLY
  function check_token(&$check, $var = '') {
    if ($check != generate_token($var)) {
      error("Error", "Invalid token");
    }
  }
  function generate_token($var = '') {
    global $pwdsalt, $pwdsalt2, $loguser;
	return md5($pwdsalt2 . checkvar('loguser','pass') . $var . $pwdsalt);
	// Leaving this alternate hash commented for consistency with other md5 token checks. It can still be enabled just fine.
    //return hash('sha256', $pwdsalt2 . $loguser['pass'] . $_SERVER['REMOTE_ADDR'] . $var . $pwdsalt);
  }
  function auth_tag($var = '') { return "<input type='hidden' name='auth' value=\"".generate_token($var)."\">"; }
  function auth_url($var = '') { return "&auth=".generate_token($var); }
?>