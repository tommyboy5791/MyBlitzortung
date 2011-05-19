<?php

/*
    MyBlitzortung - a tool for participants of blitzortung.org
	to display lightning data on their web sites.

    Copyright (C) 2011  Tobias Volgnandt

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


if (!isset($_SESSION['bo_user']))
	$_SESSION['bo_user'] = 0;


function bo_show_login()
{
	if (!defined('BO_LOGIN_ALLOW') || (BO_LOGIN_ALLOW != 1 && BO_LOGIN_ALLOW != 2))
	{
		echo _BL('Login not allowed');
		return;
	}

	$login_fail = false;

	if (isset($_POST['bo_do_login']))
	{
		$login_name = BoDb::esc($_POST['bo_user']);
		$login_pass = BoDb::esc($_POST['bo_pass']);

		if (!bo_user_do_login($login_name, $login_pass))
			$login_fail = true;
	}

	if (isset($_GET['bo_logout']))
		bo_user_do_logout();

	$level = bo_user_get_level();

	
	$remove_vars = array('bo_action','bo_action2','login','id');
	
	if ($level)
	{

		if (bo_user_get_id() == 1)
		{
			include 'update.inc.php';
			
			if (bo_check_for_update() == true)
				return;
		}
		
	
		echo '<ul id="bo_menu">';

		echo '<li><a href="'.bo_insert_url($remove_vars).'&bo_action=">'._BL('Start').'</a>';
		if (bo_user_get_id() > 1)
			echo '<li><a href="'.bo_insert_url($remove_vars).'&bo_action=password">'._BL('Password').'</a>';
		
		if (BO_PERM_ADMIN & $level)
			echo '<li><a href="'.bo_insert_url($remove_vars).'&bo_action=user_settings">'._BL('Add/Remove User').'</a>';
		
		if (BO_PERM_SETTINGS & $level)
			echo '<li><a href="'.bo_insert_url($remove_vars).'&bo_action=calibrate">'._BL('Calibrate Antennas').'</a>';


		if (defined('BO_ALERTS') && BO_ALERTS && ($level & BO_PERM_ALERT))
			echo '<li><a href="'.bo_insert_url($remove_vars).'&bo_action=alert" class="bo_navi'.($show == 'alert' ? '_active' : '').'">'._BL('Strike alert').'</a></li>';
		
		echo '</ul>';

		switch($_GET['bo_action'])
		{

			case 'user_settings':
				if (BO_PERM_ADMIN & $level)
					bo_user_show_admin();

				break;

			case 'password':
				if (bo_user_get_id() > 1)
					bo_user_show_passw_change();
				break;
				
			case 'calibrate':
				if (BO_PERM_SETTINGS & $level)
					bo_show_calibrate_antennas();
				break;

			case 'alert':
				if (BO_PERM_ALERT & $level)
					bo_alert_settings();
				break;
				
			default:
				echo '<ul class="bo_login_info">
						<li>'._BL('user_welcome_text').': <strong>'.bo_user_get_name().'</strong></li>
						<li>'._BL('MyBlitzortung version').': <strong>'.bo_get_conf('version').'</strong></li>';
				
				if (BO_PERM_ADMIN & $level)
					echo '<li><a href="'.bo_insert_url($remove_vars).'&bo_action=update">'._BL('Do manual update').'</a></li>';
				
				echo '</ul>';
				
				break;
		}
		
		if ( (BO_PERM_ADMIN & $level) && $_GET['bo_action'] == 'update')
		{
			echo flush();
			echo '<div style="font-family: Courier; font-size: 0.7em; border: 1px solid #999; padding: 10px; ">';
			bo_update_all(true);
			echo '</div>';
		}

	}
	else
	{
		bo_show_login_form($login_fail);
	}

	bo_copyright_footer();

}


function bo_show_login_form($fail = false)
{
	echo '<div id="bo_login">';

	if ($fail)
		echo '<div class="bo_info_fail">'._BL('Login fail!').'</div>';

	echo '<form action="'.bo_insert_url('bo_logout').'" method="POST" class="bo_login_form">';

	echo '<fieldset class="bo_login_fieldset">';
	echo '<legend>'._BL('login_legend').'</legend>';

	echo '<span class="bo_form_descr">'._BL('Login').':</span>';
	echo '<input type="text" name="bo_user" id="bo_login_user" class="bo_form_text bo_login_input">';

	echo '<span class="bo_form_descr">'._BL('Password').':</span>';
	echo '<input type="password" name="bo_pass" id="bo_login_pass" class="bo_form_text bo_login_input">';

	echo '<input type="submit" name="ok" value="'._BL('Login').'" id="bo_login_submit" class="bo_form_submit">';

	echo '<input type="hidden" name="bo_do_login" value="1">';

	echo '</fieldset>';

	echo '</form>';

	echo '</div>';

}

function bo_user_do_login($user, $pass)
{
	if (BO_LOGIN_ALLOW > 0 && $user == BO_USER && defined('BO_USER') && strlen(BO_USER))
	{
		if ($pass == BO_PASS && defined('BO_PASS') && strlen(BO_PASS))
		{
			bo_user_set_session(1, pow(2, BO_PERM_COUNT) - 1);
			return true;
		}
	}

	if (BO_LOGIN_ALLOW == 2)
	{
		$pass = md5($pass);
		$erg = bo_db("SELECT id, login, level FROM ".BO_DB_PREF."user WHERE login='$user' AND password='$pass'");

		if ($erg->num_rows == 1)
		{
			$row = $erg->fetch_assoc();
			bo_user_set_session($row['id'], $row['level']);
			return true;
		}

	}

	return false;
}

function bo_user_do_logout()
{
	$_SESSION['bo_user'] = 0;
	$_SESSION['bo_user_level'] = 0;
}

function bo_user_set_session($user, $level)
{
	$_SESSION['bo_user'] = $user;
	$_SESSION['bo_user_level'] = $level;
}

function bo_user_get_id()
{
	return $_SESSION['bo_user'];
}

function bo_user_get_level($user_id = 0)
{
	if (!$user_id)
		return $_SESSION['bo_user_level'];

	if ($user_id == 1)
		return pow(2, BO_PERM_COUNT) - 1;

	$erg = bo_db("SELECT level FROM ".BO_DB_PREF."user WHERE id='".intval($user_id)."'");
	$row = $erg->fetch_assoc();

	return $row['level'];
}

function bo_user_get_name($user_id = 0)
{
	static $names;
	
	if (!$user_id)
		$user_id = $_SESSION['bo_user'];

	if ($user_id == 1)
		return BO_USER;

	if (!isset($names[$user_id]))
	{
		$erg = bo_db("SELECT login FROM ".BO_DB_PREF."user WHERE id='".intval($user_id)."'");
		$row = $erg->fetch_assoc();
		$names[$user_id] = $row['login'];
	}
	
	return $names[$user_id];
}

function bo_user_get_mail($user_id = 0)
{
	static $mails;
	
	if (!$user_id)
		$user_id = $_SESSION['bo_user'];

	if (!isset($mails[$user_id]))
	{
		$erg = bo_db("SELECT mail FROM ".BO_DB_PREF."user WHERE id='".intval($user_id)."'");
		$row = $erg->fetch_assoc();
		$mails[$user_id] = $row['mail'];
	}
	
	return $mails[$user_id];
}

function bo_user_show_passw_change()
{
	if ($_POST['ok'])
	{
		$pass1 = trim(stripslashes($_POST['pass1']));
		$pass2 = trim(stripslashes($_POST['pass2']));
		
		if ($pass1 && $pass2 && $pass1 == $pass2)
		{
			$pass = md5($pass1);
			bo_db("UPDATE ".BO_DB_PREF."user SET password='$pass' WHERE id='".bo_user_get_id()."'");
			echo '<div class="bo_info_ok">';
			echo _BL('Password changed!');
			echo '</div>';
		}
		else
		{
			echo '<div class="bo_info_fail">';
			echo _BL('Password was not changed!');
			echo '</div>';
		}
	}
	
	echo '<h3>'._BL('Change password').'</h3>';
	
	echo '<form action="'.bo_insert_url(array()).'" method="POST" class="bo_admin_user_form">';

	echo '<fieldset class="bo_admin_user_fieldset">';
	echo '<legend>'._BL('user_change_passw_legend').'</legend>';

	echo '<span class="bo_form_descr">'._BL('New password').':</span>';
	echo '<input type="password" name="pass1" value="" id="bo_change_pass1" class="bo_form_text bo_form_input">';

	echo '<span class="bo_form_descr">'._BL('Repeat password').':</span>';
	echo '<input type="password" name="pass2" value="" id="bo_change_pass1" class="bo_form_text bo_form_input">';

	echo '<input type="submit" name="ok" value="'._BL('Change').'" id="bo_user_admin_submit" class="bo_form_submit">';
	echo '</fieldset>';
	
	echo '</form>';
}

function bo_user_show_admin()
{
	$user_id = intval($_GET['id']);

	if (isset($_POST['bo_admin_user']) && (bo_user_get_level() & BO_PERM_ADMIN) )
	{
		$user_id = intval($_POST['user_id']);
		$new_user_login = BoDb::esc($_POST['bo_user_login']);

		if ($user_id == 1 || $new_user_login)
		{
			$new_user_level = 0;
			if (is_array($_POST['bo_user_perm']))
			{
				foreach($_POST['bo_user_perm'] as $perm => $checked)
				{
					if ($checked)
						$new_user_level += $perm; 
				}
			}
			
			$new_user_pass = BoDb::esc($_POST['bo_user_pass']);
			$new_user_mail = BoDb::esc($_POST['bo_user_mail']);
			//$new_user_level = BoDb::esc($_POST['bo_user_level']);
			
			
			$sql = " ".BO_DB_PREF."user SET login='$new_user_login', mail='$new_user_mail', level='$new_user_level' ";

			if (strlen(trim($new_user_pass)))
			{
				$new_user_pass = md5($new_user_pass);
				$sql .= ", password='$new_user_pass'";
			}

			if ($user_id)
				$ok = bo_db("UPDATE $sql WHERE id='$user_id'");
			else
				bo_db("INSERT INTO $sql");

			$user_id = 0;
		}
	}

	if (isset($_GET['bo_delete']) && $user_id > 1 && (bo_user_get_level() & BO_PERM_ADMIN) )
	{
		bo_db("DELETE FROM ".BO_DB_PREF."user WHERE id='$user_id'");
		bo_db("DELETE FROM ".BO_DB_PREF."conf WHERE name LIKE 'alert_$user_id%'");
	}


	echo '<div id="bo_user_admin">';

	echo '<h3>'._BL('User list').'</h3>';
	echo '<table class="bo_table" id="bo_user_table">';
	echo '<tr>
			<th>ID</th>
			<th>'._BL('Login').'</th>
			<th>'._BL('Level').'</th>
			<th>'._BL('E-Mail').'</th>
			<th>'._BL('Delete').'</th>
			<th>'._BL('Alert').'</th>
			</tr>';

	$sql = "SELECT id, login, password, level, mail
			FROM ".BO_DB_PREF."user
			";
	$erg = bo_db($sql);
	while ($row = $erg->fetch_assoc())
	{
		if ($row['id'] == 1)
		{
			$row['login'] = BO_USER;
			$row['pass'] = BO_PASS;
			$row['level'] = 1;
		}

		echo '<tr>
			<td><a href="'.bo_insert_url(array('id')).'&id='.$row['id'].'">'.$row['id'].'</a></td>
			<td>'.$row['login'].'</td>
			<td>'.$row['level'].'</td>
			<td>'.$row['mail'].'</td>
			<td>';

		if ($row['id'] > 1)
			echo '<a href="'.bo_insert_url(array('user_id','bo_delete')).'&id='.$row['id'].'&bo_delete" onclick="return confirm(\''._BL('Sure?').'\');">X</a>';

		echo '</td>';
		
		echo '<td><a href="'.bo_insert_url(array('bo_action', 'bo_action2')).'&bo_action=alert&bo_action2=alert_form%2C'.$row['id'].'">'._BL('new').'</a></td>';
		
		echo '</tr>';

		if ($user_id == $row['id'])
		{
			$user_mail = $row['mail'];
			$user_level = $row['level'];
			$user_login = $row['login'];
		}

	}

	echo '</table>';

	if ($user_id == 1)
	{
		$disabled = ' disabled="disabled"';
		$user_level = pow(2, BO_PERM_COUNT) - 1;
	}

	echo '<form action="'.bo_insert_url(array('bo_logout', 'user_id', 'id', 'delete')).'" method="POST" class="bo_admin_user_form">';

	echo '<fieldset class="bo_admin_user_fieldset">';
	echo '<legend>'._BL('admin_user_legend').'</legend>';

	echo '<span class="bo_form_descr">'._BL('Login').':</span>';
	echo '<input type="text" name="bo_user_login" value="'.htmlentities($user_login).'" id="bo_user_login" class="bo_form_text bo_admin_input" '.$disabled.'>';

	echo '<span class="bo_form_descr">'._BL('Password').':</span>';
	echo '<input type="password" name="bo_user_pass" id="bo_user_login" class="bo_form_text bo_admin_input" '.$disabled.'>';

	//echo '<span class="bo_form_descr">'._BL('Level').':</span>';
	//echo '<input type="text" name="bo_user_level" value="'.htmlentities($user_level).'" id="bo_user_level" class="bo_form_text bo_admin_input" '.$disabled.'>';

	echo '<span class="bo_form_descr">'._BL('E-Mail').':</span>';
	echo '<input type="text" name="bo_user_mail"  value="'.htmlentities($user_mail).'" id="bo_user_mail" class="bo_form_text bo_login_input">';

	echo '<span class="bo_form_descr">'._BL('Level').':</span>';
	echo '<div class="bo_input_container">';
	for ($i=0; $i<BO_PERM_COUNT;$i++)
	{
		$l = pow(2, $i);
		
		echo '<span class="bo_form_checkbox_text">';
		echo '<input type="checkbox" value="1" name="bo_user_perm['.$l.']" id="bo_user_perm'.$i.'" class="bo_form_checkbox" '.$disabled.(($user_level & $l) ? ' checked="checked"' : '').'>';
		echo '<label for="bo_user_perm'.$i.'" class="bo_form_descr_checkbox">'._BL('user_perm'.$i).'</label>';
		echo '</span>';
	}
	echo '</div>';
	echo '<input type="hidden" name="bo_admin_user" value="1">';
	echo '<input type="hidden" name="user_id" value="'.$user_id.'">';

	echo '<input type="submit" name="ok" value="'._BL('Add/Edit').'" id="bo_user_admin_submit" class="bo_form_submit">';


	echo '</fieldset>';

	echo '</form>';


	echo '</div>';

}

function bo_show_calibrate_antennas()
{
	if (!$_POST['bo_calibrate'])
	{
		if ($_POST['bo_calibrate_manual'])
		{
			bo_set_conf('antenna1_bearing', (double)$_POST['bo_antenna1_bearing']);	
			bo_set_conf('antenna2_bearing', (double)$_POST['bo_antenna2_bearing']);	
			bo_set_conf('antenna1_bearing_elec', (double)$_POST['bo_antenna1_bearing_elec']);
			bo_set_conf('antenna2_bearing_elec', (double)$_POST['bo_antenna2_bearing_elec']);
		}
		
		echo '<h3>'._BL('Manual antenna calibration').'</h3>';
		
		echo '<form action="'.bo_insert_url(array()).'" method="POST" class="bo_admin_user_form">';

		echo '<fieldset class="bo_admin_user_fieldset">';
		echo '<legend>'._BL('admin_calibrate_manual_legend').'</legend>';

		echo '<span class="bo_form_descr">'._BL('Antenna 1 bearing').' (0-180�):</span>';
		echo '<input type="text" name="bo_antenna1_bearing" value="'.(double)bo_get_conf('antenna1_bearing').'" id="bo_antenna1_bearing_id" class="bo_form_text bo_form_input">';
		echo '<span class="bo_form_descr">'._BL('Antenna 2 bearing').' (0-180�):</span>';
		echo '<input type="text" name="bo_antenna2_bearing" value="'.(double)bo_get_conf('antenna2_bearing').'" id="bo_antenna2_bearing_id" class="bo_form_text bo_form_input">';

		echo '<span class="bo_form_descr">'._BL('Antenna 1 electrical bearing').' (0-360�):</span>';
		echo '<input type="text" name="bo_antenna1_bearing_elec" value="'.(double)bo_get_conf('antenna1_bearing_elec').'" id="bo_antenna1_elec_bearing_id" class="bo_form_text bo_form_input">';
		echo '<span class="bo_form_descr">'._BL('Antenna 2 electrical bearing').' (0-360�):</span>';
		echo '<input type="text" name="bo_antenna2_bearing_elec" value="'.(double)bo_get_conf('antenna2_bearing_elec').'" id="bo_antenna2_elec_bearing_id" class="bo_form_text bo_form_input">';


		echo '<input type="submit" name="bo_calibrate_manual" value="'._BL('Ok').'" id="bo_admin_submit" class="bo_form_submit">';

		echo '</fieldset>';
		echo '</form>';
	}

	/*** Auto-calibration begins here ***/
	
	$dist = intval($_POST['bo_max_dist']) * 1000;
	$age = (double)$_POST['bo_max_age'];
	$limit = intval($_POST['bo_limit']);
	$limit = $limit ? $limit : 5000;

	echo '<h3>'._BL('Automatic antenna calibration').'</h3>';
	
	echo '<form action="'.bo_insert_url(array()).'" method="POST" class="bo_admin_user_form">';

	echo '<fieldset class="bo_admin_user_fieldset">';
	echo '<legend>'._BL('admin_calibrate_legend').'</legend>';

	echo '<span class="bo_form_descr">'._BL('Limit').':</span>';
	echo '<input type="text" name="bo_limit" value="'.$limit.'" id="bo_calibrate_limit" class="bo_form_text bo_form_input">';

	echo '<span class="bo_form_descr">'._BL('Max Distance (Kilometers)').':</span>';
	echo '<input type="text" name="bo_max_dist" value="'.($dist ? $dist : '').'" id="bo_calibrate_dist" class="bo_form_text bo_form_input">';

	echo '<span class="bo_form_descr">'._BL('Max Age (Days)').':</span>';
	echo '<input type="text" name="bo_max_age" value="'.($age ? $age : '').'" id="bo_calibrate_age" class="bo_form_text bo_form_input">';


	echo '<input type="submit" name="bo_calibrate" value="'._BL('Calculate').'" id="bo_admin_submit" class="bo_form_submit">';

	echo '</fieldset>';
	echo '</form>';

	$count = null;
	if ($_POST['bo_calibrate'])
	{
		$min_sig = 0.1;
		$count = 0;
		$ant_alpha[0] = array();
		$ant_alpha[1] = array();

		$sql = "SELECT r.id raw_id, s.time strike_time, s.lat lat, s.lon lon, s.current current, r.data data
				FROM ".BO_DB_PREF."raw r, ".BO_DB_PREF."strikes s
				WHERE r.strike_id=s.id
					".($dist ? " AND s.distance < $dist " : '')."
					".($age ? " AND s.time > '".gmdate('Y-m-d H:i:s', time() - 3600 * 24 * $age)."' " : '')."
				ORDER BY RAND()
				LIMIT $limit";
		$erg = bo_db($sql);
		while ($row = $erg->fetch_assoc())
		{
			$bearing = bo_latlon2bearing($row['lat'], $row['lon']);

			/*** find direction (0-180�) ***/
			for($i=0;$i<2;$i++)
			{
				//only the first sample of each channel
				$signal = (ord(substr($row['data'],$i,1)) - 128) / 128;
				$ant[$i] = $signal;

			}

			if ( ($ant[0] == 0 && $ant[1] == 0) || (abs($ant[0]) < $min_sig && abs($ant[1]) < $min_sig) )
				continue;

			if (abs($ant[0]) < abs($ant[1]))
			{
				$calc_ant = 0;
				$ratio = $ant[0] / ($ant[1] ? $ant[1] : 1E-9);
			}
			else
			{
				$calc_ant = 1;
				$ratio = $ant[1] / ($ant[0] ? $ant[0] : 1E-9);
			}

			if (abs($ratio) < 0.02)
				$ant_alpha[$calc_ant][] = $bearing % 180;

			/*
			echo '<p><img src="'.BO_FILE.'?graph='.$row['raw_id'].'" style="width:'.BO_GRAPH_RAW_W.'px;height:'.BO_GRAPH_RAW_H.'px"></p>';
			*/

			for($i=0;$i<2;$i++)
			{
				if ($signal != 0)
				{
					$sign[$i][intval($bearing)][$signal > 0 ? 1 : -1]++;
					$current[$i][intval($bearing)][$signal > 0 ? 1 : -1][] = $row['current'];
				}
			}

			$count++;
		}
	}



	if ($count === 0)
	{
		echo '<h3>'._BL('Results').'</h3>';
		echo '<ul>';
		echo '<li>'._BL('No strikes found!').'</li>';
		echo '</ul>';
	}
	elseif ($count)
	{
		$alpha[0] = null;
		if (count($ant_alpha[0]))
			$alpha[0] = round(array_sum($ant_alpha[0]) / count($ant_alpha[0]),1);

		$alpha[1] = null;
		if (count($ant_alpha[1]))
			$alpha[1] = round(array_sum($ant_alpha[1]) / count($ant_alpha[1]),1);

		echo '<h3>'._BL('Results').'</h3>';

		echo '<ul>';
		echo '<li>'._BL('Analyzed').': '.$count.' '._BL('random datasets').'</li>';
		echo '</ul>';

		echo '<h4>'._BL('Direction').'</h4>';
		echo '<ul>';
		echo '<li>'._BL('Antenna').' 1: '.$alpha[0].'&deg; ('._BL(bo_bearing2direction($alpha[0])).' <-> '._BL(bo_bearing2direction($alpha[0] + 180)).')</li>';
		echo '<li>'._BL('Antenna').' 2: '.$alpha[1].'&deg; ('._BL(bo_bearing2direction($alpha[1])).' <-> '._BL(bo_bearing2direction($alpha[1] + 180)).')</li>';
		echo '<li>'._BL('Difference').': '.abs($alpha[1]-$alpha[0]).'&deg;</li>';


		if ((bo_user_get_level() & BO_PERM_ADMIN))
		{
			if ($alpha[0] !== null)
				bo_set_conf('antenna1_bearing', $alpha[0]);

			if ($alpha[1] !== null)
				bo_set_conf('antenna2_bearing', $alpha[1]);

			echo '<li>'._BL('Antenna directions saved to database').'.</li>';
		}
		else
			echo '<li>'._BL('No permisson for saving to DB!').'</li>';


		echo '</ul>';

		//find polarity (+/-) from statistics (suppose: more negative lightning than positve)
		echo '<h4>'._BL('Polarity').' ('._BL('Very experimental').')</h4>';

		for ($i=0;$i<2;$i++)
		{
			echo '<h6>'._BL('Antenna').' '.($i+1)." (".$alpha[$i]."�)</h6>";
			echo '<ul>';


			$deltas = array(90, 270);
			$arc = 45;

			$c = 0;
			foreach($deltas as $delta)
			{

				//count positive/negative lighning in a arc verticaly to the antenna
				$beta1 = ($alpha[$i] + $delta + $arc/2) % 360;
				$beta2 = ($alpha[$i] + $delta - $arc/2) % 360;

				if ($beta1 > $beta2)
				{
					$tmp = $beta2;
					$beta2 = $beta1;
					$beta1 = $tmp;
				}

				$neg = 0;
				$pos = 0;

				$j = 0;
				for ($a=$beta1;$a<$beta2;$a++)
				{
					$neg += $sign[$i][$a][-1];
					$pos += $sign[$i][$a][1];

					$cur_neg += count($current[$i][$a][-1]) ? array_sum($current[$i][$a][-1]) / count($current[$i][$a][-1]) : 0;
					$cur_pos += count($current[$i][$a][1]) ? array_sum($current[$i][$a][1]) / count($current[$i][$a][1]) : 0;

					$j++;
				}

				$cur_neg /= $j;
				$cur_pos /= $j;

				if ($neg)
					$pol_ratio[$c] = $pos / $neg;

				$c++;

				echo '<li>'.round($beta1).'� to '.round($beta2).'� :';
				echo ' '._BL('Positive').": $pos / "._BL('Negative').": $neg ";
				//echo " (Current: ".round($cur_pos,1)." / ".round($cur_neg,1)." kA/perStrike) ";
				echo '</li>';
			}

			if ($pol_ratio[0] > 1 && $pol_ratio[1] < 1)
				$pos_dir[$i] = ($alpha[$i] + $deltas[1]) % 360;
			else if ($pol_ratio[0] < 1 && $pol_ratio[1] > 1)
				$pos_dir[$i] = ($alpha[$i] + $deltas[0]) % 360;
			else
				$pos_dir[$i] = null;

			echo '<li>'._BL('Positive electrical direction').': ';

			if ($pos_dir[$i] === null)
				echo _BL('Not definite').' :-(';
			else
			{

				echo $pos_dir[$i].'&deg';

				if ((bo_user_get_level() & BO_PERM_ADMIN))
				{
					echo ' ('._BL('Saved to DB').')';
					bo_set_conf('antenna'.($i+1).'_bearing_elec', $pos_dir[$i]);
				}
				else
					echo ' ('._BL('No permisson for saving to DB!').')';
			}

			echo '</li>';

			echo '</ul>';

		}
	}
}

?>