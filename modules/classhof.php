<?php
function classhof_getmoduleinfo() {
    $info = array(
        "name" => "Class Hall of Fame",
        "author" => "Aelia, with DaveS<br>modifications by blarg",
        "version" => "1.02+bw",
        "download" => "http://dragonprime.net",
        "category" => "AW-Specialties",
        "settings"=> array(
					"Class HoF Settings,title",
					"pp"=>"Number of players to show per page on the HoF?,int|25",
        ),
    );
    return $info;
}

function classhof_install() {
	module_addhook("footer-hof");
	return true;
}

function classhof_uninstall() {
    return true;
}




function classhof_dohook($hookname,$args) {
    global $session,$resline;
    switch ($hookname) {
		case "footer-hof":
			addnav("Warrior Rankings");
			addnav("Class HoF","runmodule.php?module=classhof&op=run");
		break;
    }
    return $args;
}

function classhof_run(){
	global $session;
	$op = httpget('op');
	page_header("Hall of Fame");
	if ($op=="run"){
		addnav("Classes");
		$any = array(
			'any'=>"0"
		);
		$any = modulehook("classhof",$any);
		if ($any['any'] == 0)
			output("`\$There are no modules installed that offer a Class Hall of Fame!`0`n");
		else
			output("`1Select an option to view its hall of fame`0`n");
	}
	if ($op=="hof"){
		$spec = httpget('spec');
		$specargs = array(
			'modulename'=>"",
			'spec'=>$spec,
			'levelname'=>"",
			'classname'=>"",
			'ccode'=>""
		);
		$specargs = modulehook("classhofspec",$specargs);
		$modulename = $specargs['modulename'];
		$levelname = $specargs['levelname'];
		$classname = $specargs['classname'];
		$ccode = $specargs['ccode'];
		$pp = get_module_setting("pp");
		$page = httpget('page');
		$pageoffset = (int)$page;
		if ($pageoffset > 0) $pageoffset--;
		$pageoffset *= $pp;
		$limit = "LIMIT $pageoffset,$pp";
		$sql = "SELECT COUNT(*) AS c FROM " . db_prefix("module_userprefs") . " WHERE modulename = '$modulename' AND setting = '$levelname' AND value > 0";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
		$total = $row['c'];
		$count = db_num_rows($result);
		if (($pageoffset + $pp) < $total){
			$cond = $pageoffset + $pp;
		}else{
			$cond = $total;
		}
		$sql = "SELECT ".db_prefix("module_userprefs").".value, ".db_prefix("module_userprefs").".userid, ".db_prefix("accounts").".name FROM " . db_prefix("module_userprefs") . "," . db_prefix("accounts") . " WHERE acctid = userid AND modulename = '$modulename' AND setting = '$levelname"."points' AND value > 0 ORDER BY (value+0) DESC $limit";
		$result = db_query($sql);
		$rank = translate_inline("Rank");
		$name = translate_inline("Name");
		$level = translate_inline("Level");
		$none = translate_inline("No $classname".(substr($classname,-1)=="s"?"":"s")." in the land!");
		output("`b`c$ccode"."Most Powerful $classname".(substr($classname,-1)=="s"?"":"s")." In The Land`c`b`n`n");
		rawoutput("<table border='0' cellpadding='2' cellspacing='1' align='center' bgccode='#999999'>");
		rawoutput("<tr class='trhead'><td>$rank</td><td>$name</td><td>$level</td></tr>");
		if ($total == 0) output_notl("<tr class='trlight'><td colspan='3' align='center'>`&$none`0</td></tr>",true);
		else{
			for($i = $pageoffset; $i < $cond && $count; $i++) {
				$row = db_fetch_assoc($result);
				if ($row['name']==$session['user']['name']){
					rawoutput("<tr class='trhilight'><td>");
				}else{
					rawoutput("<tr class='".($i%2?"trdark":"trlight")."'><td>");
				}
				$j=$i+1;
				output_notl("$ccode$j.");
				rawoutput("</td><td>");
				output_notl("$ccode%s`0",$row['name']);
				rawoutput("</td><td>");			
				output_notl("`c`b$ccode%s`c`b`0",get_module_pref($levelname,$modulename,$row['userid']));
				rawoutput("</td></tr>");
			}
		}
		rawoutput("</table>");
		if ($total>$pp){
			addnav("Pages");
			for ($p=0;$p<$total;$p+=$pp){
				addnav(array("Page %s (%s-%s)", ($p/$pp+1), ($p+1), min($p+$pp,$total)), "runmodule.php?module=classhof&op=hof&spec=".$spec."&page=".($p/$pp+1));
			}
		}
		addnav("Return");
		addnav("Back to Class HoF","runmodule.php?module=classhof&op=run");
	}
	addnav("Return");
	addnav("Back to HoF", "hof.php");
	villagenav();
	page_footer();
}
?>