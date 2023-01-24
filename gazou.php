<?php
/*************************************
  * Image BBS by ToR
  *
  * http://php.s3.to/
  *
  * Image upload BBS.
  *
  * Prepare a directory img for storage and set it to 777.
  * Prepare an empty log file imglog.log and set it to 666.
  * Some servers cannot be uploaded.
  *
  * 2001/09/27 v2.4 change image save name from local to time name, paging
  * 2001/10/31 v3.0 Rebuild. Created a posting page for administrators. Forms can be separated.
  * 2001/11/05 v3.1 Fixed a lot of bugs, added a response.
  * 2002/05/19 v3.2 Fixed a bug related to deletion. copy -> move_uploded_file (if not, copy it)
  * 2002/06/15 v3.3 uploaded files other than images were available... sorry, line 298
  * 2002/01/25 v3.4 Anti-Uploading
  * 2002/02/11 v3.5 fix for garbled cookies

  **************************************/
if(phpversion()>="4.1.0"){
  extract($_REQUEST);
  extract($_COOKIE);
  $upfile_name=$_FILES["upfile"]["name"];
  $upfile=$_FILES["upfile"]["tmp_name"];
}
//----設定--------
define(LOGFILE, 'imglog.log');		// Name for logfile
define(IMG_DIR, 'img/');		// Img folder for gazoubbs

define(TITLE, 'Image BBS');		// Title of your BBS（<title>とTOP）
define(HOME,  '../');	// Clicked upon clicking "Index"

define(MAX_KB, '100');			//Max kilobytes a file can have
define(MAX_W,  '250');			//Max width a file can have
define(MAX_H,  '250');			//Max height a file can have

define(PAGE_DEF, '7');			// Number of posts that can appear on one page
define(LOG_MAX,  '200');		// Mazimum number of posts in the log

define(ADMIN_PASS, '0123');		// The admin password
define(CHECK, 0);			//Make uploaded images have to go through approval by admins? yes=1
define(SOON_ICON, '/soon.jpg');		// See CHECK. Image that appears as a replacement for photos going through the approval proccess
define(RE_COL, '789922');               // Color of greentext

define(NIKKI, 0);			//Close the bulletin board to users? Yes=1 No=0

define(PHP_SELF, "gazou.php");		//Script name;


//画像保存絶対パス $path="/home/public_html/***/img/";
//$path = dirname($_SERVER[PATH_TRANSLATED]).IMG_DIR;
$path = IMG_DIR;

/* 未定
$badstring = array("dummy_string","dummy_string2"); //拒絶する文字列
$badfile = array("dummy","dummy2"); //拒絶するファイルのmd5
$badip = array("addr.dummy.com","addr2.dummy.com"); //拒絶するホスト
*/
/* ヘッダ */
function head(&$dat){
  $dat.='
<html><head>
<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=Shift_JIS">
<STYLE TYPE="text/css">
<!--
body,tr,td,th { font-size:10pt }
a:hover { color:#DD0000; }
span { font-size:20pt }
small { font-size:8pt }
-->
</STYLE>
<title>'.TITLE.'</title></head>
<body bgcolor="#FFFFEE" text="#800000" link="#0000EE" vlink="#0000EE">
<p align=right>
[<a href="'.HOME.'" target="_top">Go Home</a>]
[<a href="'.PHP_SELF.'?mode=admin">Admin</a>]
<p align=center>
<font color="#800000" face="MS PGothic" size=5>
<b><SPAN>'.TITLE.'</SPAN></b><br></font>
<hr width="90%" size=1>
';
}
/* 投稿フォーム */
function form(&$dat,$resno,$admin=""){
  global $gazoubbs;

  if (get_magic_quotes_gpc()) $gazoubbs = stripslashes($gazoubbs);
  list($cname,$cemail,$cpass) = explode(",", $gazoubbs);

  $maxbyte = MAX_KB * 1024;
  if($resno){
    $find = false;
    $line = file(LOGFILE);
    for($i = 0; $i < count($line); $i++){
      list($no,$now,$name,$email,$sub,$com,) = explode(",", $line[$i]);
      if($no == $resno){
        $find = true;
        break;
      }
    }
    if(!$find) error("The post you are trying to view cannot be found!");

	if(preg_match("/Re\[([0-9])\]:/", $sub, $reg)){
      $reg[1]++;
      $r_sub=preg_replace("/Re\[([0-9])\]:/", "Re[$reg[1]]:", $sub);
    }elseif(preg_match("/^Re:/", $sub)){ 
      $r_sub=preg_replace("/^Re:/", "Re[2]:", $sub);
    }else{
      $r_sub = "Re:$sub";
    }
    $r_com = "&gt;$com";
    $r_com = preg_replace("/<br( \/)?>/","\r&gt;",$r_com);
    $msg = "<h5>Response to post No. $no</h5>";
  }
  if($admin){
    $hidden = "<input type=hidden name=admin value=\"".ADMIN_PASS."\">";
    $msg = "<h4>You can use HTML tags in your posts.</h4>";
  }
  $dat.='
<center>'.$msg.'
<form action="'.PHP_SELF.'" method="POST" enctype="multipart/form-data">
<input type=hidden name=mode value="regist">
'.$hidden.'
<input type=hidden name="MAX_FILE_SIZE" value="'.$maxbyte.'">
<table cellpadding=1 cellspacing=1>
<tr>
  <td bgcolor=#eeaa88><b>Name</b></td>
  <td><input type=text name=name size="28" value="'.$cname.'"></td>
</tr>
<tr>
  <td bgcolor=#eeaa88><b>E-Mail</b></td>
  <td><input type=text name=email size="28" value="'.$cemail.'"></td>
</tr>
<tr>
  <td bgcolor=#eeaa88><b>Title</b></td>
  <td>
    <input type=text name=sub size="35" value="'.$r_sub.'">
    <input type=submit value="Submit"><input type=reset value="Reset">
  </td>
</tr>
<tr>
  <td bgcolor=#eeaa88><b>Contents</b></td>
  <td><textarea name=com cols="48" rows="4" wrap=soft>'.$r_com.'</textarea>
  </td>
</tr>
<tr>
  <td bgcolor=#eeaa88><b>URL</b></td>
  <td><input type=text name=url size="63" value="http://"></td>
</tr>
<tr>
  <td bgcolor=#eeaa88><b>File</b></td>
  <td><input type=file name=upfile size="35"></td>
</tr>
<tr>
  <td bgcolor=#eeaa88><b>Delkey</b></td>
  <td>
    <input type=password name=pwd size=8 maxlength=8 value="'.$cpass.'">
    <small>(Used for deleting your posts. Alphanumeric, up to 8 characters)</small>
  </td>
</tr>
<tr><td colspan=2>
<small>
<LI>Allowed file extensions： GIF, JPG, PNG<br>
<LI>Some browsers may not attach properly.<br>
<LI>Files you submit must be less than '.MAX_KB.' KB in size.<br>
<LI>If the image you submit is larger than '.MAX_W.'x'.MAX_H.', it will be thumbnailed.
</small>
</td></tr></table></form></center>
<hr>
  ';
}
/* 記事部分 */
function main(&$dat, $page){
  global $path;

  $line = file(LOGFILE);
  $st = ($page) ? $page : 0;

  for($i = $st; $i < $st+PAGE_DEF; $i++){
    if($line[$i]=="") continue;
    list($no,$now,$name,$email,$sub,$com,$url,
         $host,$pwd,$ext,$w,$h,$time,$chk) = explode(",", $line[$i]);
    // URLとメールにリンク
    if($url)   $url = "<a href=\"http://$url\" target=_blank>Link</a>";
    if($email) $name = "<a href=\"mailto:$email\">$name</a>";
    $com = auto_link($com);
	$com = preg_replace("/(^|>)(&gt;[^<]*)/", "\\1<font color=".RE_COL.">\\2</font>", $com);
    // 画像ファイル名
    $img = $path.$time.$ext;
    $src = IMG_DIR.$time.$ext;
/* 自由に変更してください["]=[\"]に */
    // <imgタグ作成
    $imgsrc = "";
    if($ext && is_file($img)){
      $size = ceil(filesize($img) / 1024);//altにサイズ表示
      if(CHECK && $chk != 1){//未チェック
        $imgsrc = "<img src=".SOON_ICON." hspace=20>";
      }elseif($w && $h){//サイズがある時
        $imgsrc = "<a href=\"".$src."\" target=_blank><img src=".$src."
			border=0 align=left width=$w height=$h hspace=20 alt=\"".$size." KB\"></a>";
      }else{//それ以外
        $imgsrc = "<a href=\"".$src."\" target=_blank><img src=".$src."
			border=0 align=left hspace=20 alt=\"".$size." KB\"></a>";
      }
    }
    // メイン作成
    $dat.="No.$no <font color=#cc1105 size=+1><b>$sub</b></font><br> ";
    $dat.="Name <font color=#117743><b>$name</b></font> Date $now &nbsp; $url [<a href=".PHP_SELF."?res=$no>Reply</a>]";
    $dat.="<p><blockquote>$imgsrc $com</blockquote><br clear=left><hr>\n";

    $p++;
    clearstatcache();//ファイルのstatをクリア
  }
  $prev = $st - PAGE_DEF;
  $next = $st + PAGE_DEF;
  // 改ページ処理
  $dat.="<table align=left><tr>\n";
  if($prev >= 0){
    $dat.="<td><form action=\"".PHP_SELF."\" method=POST>";
    $dat.="<input type=hidden name=page value=$prev>";
    $dat.="<input type=submit value=\"Previous\" name=submit>\n";
    $dat.="</form></td>\n";
  }
  if($p >= PAGE_DEF && count($line) > $next){
    $dat.="<td><form action=\"".PHP_SELF."\" method=POST>";
    $dat.="<input type=hidden name=page value=$next>";
    $dat.=" <input type=submit value=\"Next\" name=submit>\n";
    $dat.="</form></td>\n";
  }
  $dat.="</td>\n</tr></table>\n";
}
/* フッタ */
function foot(&$dat){
  $dat.='
<table align=right><tr>
<td nowrap align=center><form action="'.PHP_SELF.'" method=POST>
<input type=hidden name=mode value=usrdel>
"Post deletion"<br>
Post No.<input type=text name=no size=3>
Delkey<input type=password name=pwd size=4 maxlength=8>
<input type=submit value="Gelete">
</form></td>
</tr></table><br clear=all>
<center><P><small><!-- GazouBBS v3.5 -->
- <a href="http://php.loglog.jp" target=_top>GazouBBS</a> -
</small></center>
</body></html>
  ';
}
/* 記事書き込み */
function regist($name,$email,$sub,$com,$url,$pwd,$upfile,$upfile_name){
  global $REQUEST_METHOD,$path;

  // フォーム内容をチェック
  if(!$name || preg_match("/^( |　)*$/u",$name)) error("Name not written in."); 
  if(!$com||preg_match("/^( |　|\t)*$/u",$com)) error("No text has been written."); 
  if(!$sub||preg_match("/^( |　)*$/u",$sub))   $sub="（Untitled）"; 
  if(strlen($com) > 1000) error("Contents of the post are too long! Please shorten it to under 1000 characters.");

  $line = file(LOGFILE);
  // 時間とホスト取得
  $tim = time();
  $host = gethostbyaddr(getenv("REMOTE_ADDR"));
  // 連続投稿チェック
  list($lastno,,$lname,,,$lcom,,$lhost,,,,,$ltime,) = explode(",", $line[0]);
  if(RENZOKU && $host == $lhost && $tim - $ltime < RENZOKU)
    error("Please allow a few more minutes for continuous posting.");
  // No.とパスと時間とURLフォーマット
  $no = $lastno + 1;
  $c_pass = $pwd;
  $pass = ($pwd) ? substr(md5($pwd),2,8) : "*";
  $now = gmdate("Y/m/d(D) H:i",$tim+9*60*60);
  $url = preg_replace("/^http:\/\//", "", $url);
  //テキスト整形
  $name = CleanStr($name);
  $email= CleanStr($email);
  $sub  = CleanStr($sub);
  $url  = CleanStr($url);
  $com  = CleanStr($com);
  // 改行文字の統一。 
  $com = str_replace( "\r\n",  "\n", $com); 
  $com = str_replace( "\r",  "\n", $com);
  // 連続する空行を一行
  while (strstr($com, "\n\n\n") == true) {
	  $com = str_replace("\n\n\n", "\n\n", $com);
  }
  $com = nl2br($com);										//改行文字の前に<br>を代入する
  $com = str_replace("\n",  "", $com);	//\nを文字列から消す。
  // 二重投稿チェック
  if($name == $lname && $com == $lcom)
	  error("Double posting is not allowed. <br><br><a href=$PHP_SELF>Reload</a>?");
  // ログ行数オーバー
  if(count($line) >= LOG_MAX){
    for($d = count($line)-1; $d >= LOG_MAX-1; $d--){
      list($dno,,,,,,,,,$ext,,,$dtime,) = explode(",", $line[$d]);
      if(is_file($path.$dtime.$ext)) unlink($path.$dtime.$ext);
      $line[$d] = "";
    }
  }
  // アップロード処理
  if(file_exists($upfile)){
    $dest = $path.$upfile_name;
    move_uploaded_file($upfile, $dest);
    //↑でエラーなら↓に変更s
    //copy($upfile, $dest);
    if(!is_file($dest)) error("Upload was unsuccessful.<br>The server may not support the file you were uploading.");
    $size = @getimagesize($dest);
    if($size[2]=="") error("Upload was unsuccessful.<br>Only image files will be accepted.");
    $W = $size[0];
    $H = $size[1];
    $ext = strtolower(substr($upfile_name,-4));
    //if ($ext == ".php" || $ext == "php3" || $ext == "php4" || $ext == "html") error("アップロードに失敗しました。<br>画像ファイル以外は受け付けません");
	// ^ who thought this was a good idea
	if ($ext !== ".png" && $ext !== ".jpg" && $ext !== ".gif") error("Upload was unsuccessful<br>Only image files will be accepted.");
    rename($dest,$path.$tim.$ext);
    // 画像表示縮小
    if($W > MAX_W || $H > MAX_H){
      $W2 = MAX_W / $W;
      $H2 = MAX_H / $H;

      ($W2 < $H2) ? $key = $W2 : $key = $H2;

      $W = $W * $key;
      $H = $H * $key;
    }
    $mes = "Image $upfile_name has uploaded successfully!<br><br>";
  }
  $chk = (CHECK) ? 0 : 1;//未チェックは0

    //クッキー保存
  $cookvalue = implode(",", array($name,$email,$c_pass));
  setcookie ("gazoubbs", $cookvalue,time()+14*24*3600);  /* 2週間で期限切れ */

  $newline = "$no,$now,$name,$email,$sub,$com,$url,$host,$pass,$ext,$W,$H,$tim,$chk,\n";

  $fp = fopen(LOGFILE, "w");
  flock($fp, 2);
  fputs($fp, $newline);
  fputs($fp, implode('', $line));
  fclose($fp);

  echo "$msg The screen will change to your post in a few seconds";
  echo "<META HTTP-EQUIV=\"refresh\" content=\"1;URL=".PHP_SELF."?\">";
}
/* テキスト整形 */
function CleanStr($str){
  global $admin;

  $str = trim($str);//先頭と末尾の空白除去
  if (get_magic_quotes_gpc()) {//￥を削除
    $str = stripslashes($str);
  }
  if($admin!=ADMIN_PASS){//管理者はタグ可能
    $str = htmlspecialchars($str);//タグっ禁止
    $str = str_replace("&amp;", "&", $str);//特殊文字
  }
  return str_replace(",", "&#44;", $str);//カンマを変換
}
/* ユーザー削除 */
function usrdel($no,$pwd){
  global $path;

  if($no == "") error("Deletion No. was not entered.");

  $line = file(LOGFILE);
  $flag = FALSE;

  for($i = 0; $i<count($line); $i++){
    list($dno,,,,,,,,$pass,$dext,,,$dtim,) = explode(",", $line[$i]);
    if($no == $dno) {
      if(substr(md5($pwd),2,8) == $pass || ($pwd == '' && $pass == '*')){
        $flag = TRUE;
        $line[$i] = "";			//パスワードがマッチした行は空に
        $delfile = $path.$dtim.$dext;	//削除ファイル
        break;
      }
    }
  }
  if(!$flag) error("Cannot find the post in question, or your password was incorrect.");
  // ログ更新
  $fp = fopen(LOGFILE, "w");
  flock($fp, 2);
  fputs($fp, implode('', $line));
  fclose($fp);

  if(is_file($delfile)) unlink($delfile);//削除
}
/* パス認証 */
function valid($pass){
  if($pass && $pass != ADMIN_PASS) error("The adminstration password does not match what you entered!");

  head($dat);
  echo $dat;
  echo "[<a href=\"".PHP_SELF."\">Return</a>]\n";
  echo "<table width='100%'><tr><th bgcolor=#E08000>\n";
  echo "<font color=#FFFFFF>Adminstration</font>\n";
  echo "</th></tr></table>\n";
  echo "<p><form action=\"".PHP_SELF."\" method=POST>\n";
  // ログインフォーム
  if(!$pass){
    echo "<center><input type=radio name=admin value=del checked>Delete Article ";
    echo "<input type=radio name=admin value=post>Admin Post<p>";
    echo "<input type=hidden name=mode value=admin>\n";
    echo "<input type=password name=pass size=8>";
    echo "<input type=submit value=\" Verify \"></form></center>\n";
    die("</body></html>");
  }
}
/* 管理者削除 */
function admindel($delno,$chkno,$pass){
  global $path;

  if($chkno || $delno){
    $line = file(LOGFILE);
    $find = FALSE;
    for($i = 0; $i < count($line); $i++){
      list($no,$now,$name,$email,$sub,$com,$url,
           $host,$pw,$ext,$w,$h,$tim,$chk) = explode(",",$line[$i]);
      if($chkno == $no){//画像チェック$chk=1に
        $find = TRUE;
        $line[$i] = "$no,$now,$name,$email,$sub,$com,$url,$host,$pw,$ext,$w,$h,$tim,1,\n";
        break;
      }
      if($delno == $no){//削除の時は空に
        $find = TRUE;
        $line[$i] = "";
        $delfile = $path.$tim.$ext;	//削除ファイル
        break;
      }
    }
    if($find){//ログ更新
      $fp = fopen(LOGFILE, "w");
      flock($fp, 2);
      fputs($fp, implode('', $line));
      fclose($fp);

      if(is_file($delfile)) unlink($delfile);//削除
    }
  }
  // 削除画面を表示
  echo "<input type=hidden name=mode value=admin>\n";
  echo "<input type=hidden name=admin value=del>\n";
  echo "<input type=hidden name=pass value=\"$pass\">\n";
  echo "<center><P>Check the checkboxes of the posts you wish to delete and press the Delete button. \n";
  echo "<P><table border=1 cellspacing=0>\n";
  echo "<tr bgcolor=6080f6><th>Delete</th><th>Post No.</th><th>Posted</th><th>Title</th>";
  echo "<th>Poster</th><th>Contents</th><th>Host name</th><th>File<br>(Bytes)</th>";
  if(CHECK) echo "<th>Image<br>Approval</th>";
  echo "</tr>\n";

  $line = file(LOGFILE);

  for($j = 0; $j < count($line); $j++){
    $img_flag = FALSE;
    list($no,$now,$name,$email,$sub,$com,$url,
         $host,$pw,$ext,$w,$h,$time,$chk) = explode(",",$line[$j]);
    // フォーマット
    list($now, $dmy) = explode("(", $now);
    if($email) $name="<a href=\"mailto:$email\">$name</a>";
    $com = str_replace("<br />"," ",$com);
    $com = htmlspecialchars($com);
    if(strlen($com) > 40) $com = substr($com,0,38) . " ...";
    // 画像があるときはリンク
    if($ext && is_file($path.$time.$ext)){
      $img_flag = TRUE;
      $clip = "<a href=\".".IMG_DIR.$time.$ext."\" target=_blank>".$time.$ext."</a>";
      $size = filesize($path.$time.$ext);
      $all += $size;			//合計計算
    }else{
      $clip = "";
      $size = 0;
    }
    $bg = ($j % 2) ? "d6d6f6" : "f6f6f6";//背景色

    echo "<tr bgcolor=$bg><th><input type=checkbox name=del value=\"$no\"></th>";
    echo "<th>$no</th><td><small>$now</small></td><td>$sub</td>";
    echo "<td><b>$name</b></td><td><small>$com</small></td>";
    echo "<td>$host</td><td align=center>$clip<br>($size)</td>\n";
	

    if(CHECK){//画像チェック
      if($img_flag && $chk == 1){
        echo "<th><font color=red>OK</font></th>";
      }elseif($img_flag && $chk != 1) {
        echo "<th><input type=checkbox name=chk value=$no></th>";
      }else{
        echo "<td><br></td>";
      }
    }
    echo "</tr>\n";
  }
  if(CHECK) $msg = "or allow";

  echo "</table><p><input type=submit value=\"Delete $msg\">";
  echo "<input type=reset value=\"Reset\"></form>";

  $all = (int)($all / 1024);
  echo "【 Total file size of your board : <b>$all</b> KB 】";
  die("</center></body></html>");
}
/* オートリンク */
function auto_link($proto){
  $proto = preg_replace("|([\w\d]*)\s?(https?://([\d\w\.-]+\.[\w\.]{2,6})[^\s\]\[\<\>]*/?)|i", "$1 <a href='$2'>$3</a>", $proto);
  return $proto;
}
/* エラー画面 */
function error($mes){
  global $upfile_name,$path;

  if(is_file($path.$upfile_name)) unlink($path.$upfile_name);

  head($dat);
  echo $dat;
  echo "<br><br><hr size=1><br><br>
        <center><font color=red size=5><b>$mes</b></font></center>
        <br><br><hr size=1>";
  die("</body></html>");
}
/*-----------Main-------------*/
switch($mode){
  case 'regist':
    regist($name,$email,$sub,$com,$url,$pwd,$upfile,$upfile_name);
    break;
  case 'admin':
    valid($pass);
    if($admin=="del") admindel($del,$chk,$pass);
    if($admin=="post"){
      echo "</form>";
      form($post,$res,1);
      echo $post;
      die("</body></html>");
    }
    break;
  case 'usrdel':
    usrdel($no,$pwd);
  default:
    head($buf);
    if(!NIKKI) form($buf,$res);
    main($buf,$page);
    foot($buf);
    echo $buf;
}
?>
