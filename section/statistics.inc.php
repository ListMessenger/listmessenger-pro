<?php
/**
 * ListMessenger Pro - Classic Mailing List Management
 * For the most recent version, visit https://listmessenger.com.
 *
 * @copyright 2002-2022 Silentweb https://silentweb.ca
 * @author Matt Simpson <msimpson@listmessenger.com>
 * @license /licence.html ListMessenger Software Licence Agreement
 */
if (!defined('PARENT_LOADED')) {
    exit;
}
if (!$_SESSION['isAuthenticated']) {
    exit;
}

$i = count($SIDEBAR);
$SIDEBAR[$i] = "<table style=\"width: 100%\" cellspacing=\"0\" cellpadding=\"1\" border=\"0\">\n";
$SIDEBAR[$i] .= "<tr>\n";
$SIDEBAR[$i] .= "	<td><img src=\"./images/icon-man-users.gif\" width=\"16\" height=\"16\" alt=\"Add Subscriber\" title=\"Add Subscriber\" /></td>\n";
$SIDEBAR[$i] .= "	<td><a href=\"index.php?section=subscribers&action=add\">Add Subscriber</a></td>\n";
$SIDEBAR[$i] .= "</tr>\n";
$SIDEBAR[$i] .= "<tr>\n";
$SIDEBAR[$i] .= "	<td><img src=\"./images/icon-del-users.gif\" width=\"16\" height=\"16\" alt=\"Bulk Removal Tool\" title=\"Bulk Removal Tool\" /></td>\n";
$SIDEBAR[$i] .= "	<td><a href=\"index.php?section=subscribers&action=bulkremoval\" style=\"white-space: nowrap\">Bulk Removal Tool</a></td>\n";
$SIDEBAR[$i] .= "</tr>\n";
$SIDEBAR[$i] .= "<tr>\n";
$SIDEBAR[$i] .= "	<td><img src=\"./images/icon-man-groups.gif\" width=\"16\" height=\"16\" alt=\"Manage Groups\" title=\"Manage Groups\" /></td>\n";
$SIDEBAR[$i] .= "	<td><a href=\"index.php?section=manage-groups\">Manage Groups</a></td>\n";
$SIDEBAR[$i] .= "</tr>\n";
$SIDEBAR[$i] .= "<tr>\n";
$SIDEBAR[$i] .= "	<td><img src=\"./images/icon-man-fields.gif\" width=\"16\" height=\"16\" alt=\"Manage Fields\" title=\"Manage Fields\" /></td>\n";
$SIDEBAR[$i] .= "	<td><a href=\"index.php?section=manage-fields\">Manage Fields</a></td>\n";
$SIDEBAR[$i] .= "</tr>\n";
$SIDEBAR[$i] .= "<tr>\n";
$SIDEBAR[$i] .= "	<td><img src=\"./images/icon-stats.gif\" width=\"16\" height=\"16\" alt=\"Basic Subscriber Stats\" title=\"Basic Subscriber Stats\" /></td>\n";
$SIDEBAR[$i] .= "	<td><a href=\"index.php?section=statistics\">Subscriber Stats</a></td>\n";
$SIDEBAR[$i] .= "</tr>\n";
$SIDEBAR[$i] .= "</table>\n";

$HEAD[] = '<script type="text/javascript" src="./javascript/jquery/jquery.flot.js"></script>';
$HEAD[] = '<script type="text/javascript" src="./javascript/jquery/excanvas.min.js"></script>';

$STATISTICS = [];

if ((!empty($_GET['date'])) && (strlen($_GET['date']) == 10) && ($tmp_date = strtotime($_GET['date']))) {
    $STATISTICS_DATE = $tmp_date;
} else {
    $STATISTICS_DATE = time();
}

$month = date('n', $STATISTICS_DATE);
$year = date('Y', $STATISTICS_DATE);

for ($day = 1; $day <= date('t', $STATISTICS_DATE); ++$day) {
    $timestamp_start = mktime(0, 0, 0, $month, $day, $year);
    $timestamp_end = mktime(23, 59, 59, $month, $day, $year);

    $query = '
			SELECT COUNT(*) AS `total`
			FROM `'.TABLES_PREFIX.'confirmation`
			WHERE `date` BETWEEN '.$db->qstr($timestamp_start).' AND '.$db->qstr($timestamp_end)."
			AND (`action` = 'usr-subscribe' OR `action` = 'adm-subscribe' OR `action` = 'adm-import')";
    $result = $db->GetRow($query);
    if ($result) {
        $STATISTICS['total-subscribers'][$day] = $result['total'];
    } else {
        $STATISTICS['total-subscribers'][$day] = 0;
    }

    $query = '
			SELECT COUNT(*) AS `total`
			FROM `'.TABLES_PREFIX.'confirmation`
			WHERE `date` BETWEEN '.$db->qstr($timestamp_start).' AND '.$db->qstr($timestamp_end)."
			AND (`action` = 'usr-subscribe' OR `action` = 'adm-subscribe' OR `action` = 'adm-import')
			AND `confirmed` = '1'";
    $result = $db->GetRow($query);
    if ($result) {
        $STATISTICS['confirmed-subscribers'][$day] = $result['total'];
    } else {
        $STATISTICS['confirmed-subscribers'][$day] = 0;
    }

    $query = '
			SELECT COUNT(*) AS `total`
			FROM `'.TABLES_PREFIX.'confirmation`
			WHERE `date` BETWEEN '.$db->qstr($timestamp_start).' AND '.$db->qstr($timestamp_end)."
			AND (`action` = 'usr-subscribe' OR `action` = 'adm-subscribe' OR `action` = 'adm-import')
			AND `confirmed` = '0'";
    $result = $db->GetRow($query);
    if ($result) {
        $STATISTICS['unconfirmed-subscribers'][$day] = $result['total'];
    } else {
        $STATISTICS['unconfirmed-subscribers'][$day] = 0;
    }

    $query = '
			SELECT COUNT(*) AS `total`
			FROM `'.TABLES_PREFIX.'confirmation`
			WHERE `date` BETWEEN '.$db->qstr($timestamp_start).' AND '.$db->qstr($timestamp_end)."
			AND `action` = 'usr-unsubscribe'";
    $result = $db->GetRow($query);
    if ($result) {
        $STATISTICS['unsubscribe'][$day] = $result['total'];
    } else {
        $STATISTICS['unsubscribe'][$day] = 0;
    }
}

$i = count($SIDEBAR);
$SIDEBAR[$i] = "<fieldset style=\"margin-top: 20px; padding: 7px 0px 15px 0px\">\n";
$SIDEBAR[$i] .= "	<legend class=\"page-subheading\">Displaying</legend>\n";
$SIDEBAR[$i] .= "	<span id=\"display\"></span>\n";
$SIDEBAR[$i] .= "</fieldset>\n";

$i = count($SIDEBAR);
$SIDEBAR[$i] = "<fieldset style=\"margin-top: 20px; padding: 7px 2px 15px 4px\">\n";
$SIDEBAR[$i] .= "	<legend class=\"page-subheading\">Timeframe</legend>\n";
$SIDEBAR[$i] .= "	<select name=\"date\" id=\"change_date\" style=\"width: 95%\" onchange=\"window.location = 'index.php?section=statistics&date=' + this.options[this.selectedIndex].value\">\n";
for ($month = 0; $month <= 12; ++$month) {
    $tmp_stat_date = strtotime('-'.$month.' months', time());

    $value = date('Y', $tmp_stat_date).'-'.date('m', $tmp_stat_date).'-01';
    $timestamp = strtotime($value);
    $SIDEBAR[$i] .= '<option value="'.$value.'"'.((date('Y-m', $STATISTICS_DATE) == date('Y-m', $tmp_stat_date)) ? ' selected="selected"' : '').'>'.date('F Y', $timestamp)."</option>\n";
}
$SIDEBAR[$i] .= "	</select>\n";
$SIDEBAR[$i] .= "</fieldset>\n";

?>

<h1>Statistics During <?php echo date('F Y', $STATISTICS_DATE); ?></h1>

<div id="statistics01" style="width: 600px; height: 325px"></div>

<script type="text/javascript">
$(function () {
	$('#statistics01').width($('#lm-body-tag').width() + 'px');

    var graphable = {
        "total-subscribers": {
            label: "New Subscribers",
            color: 5,
            data: [<?php echo generate_statistics_values($STATISTICS['total-subscribers']); ?>]
        },
        "confirmed-subscribers": {
            label: "&nbsp;&nbsp;&nbsp;New: Confirmed",
            color: 3,
            data: [<?php echo generate_statistics_values($STATISTICS['confirmed-subscribers']); ?>]
        },
        "unconfirmed-subscribers": {
            label: "&nbsp;&nbsp;&nbsp;New: Unconfirmed",
            color: 0,
            data: [<?php echo generate_statistics_values($STATISTICS['unconfirmed-subscribers']); ?>]
        },
        "unsubscribe": {
            label: "Unsubscribed",
            color: 2,
            data: [<?php echo generate_statistics_values($STATISTICS['unsubscribe']); ?>]
        }
    };

    var choiceContainer = $("#display");
    $.each(graphable, function(key, val) {
        choiceContainer.append('<input type="checkbox" id="display_' + key + '" name="' + key + '" checked="checked" style="vertical-align: middle" /><label for="display_' + key + '" style="vertical-align: middle; font-size: 10px">' + val.label + '</label><br />');
    });

    choiceContainer.find("input").click(plotAccordingToSelection);
    
    function plotAccordingToSelection() {
        var data = [];

        choiceContainer.find("input:checked").each(function () {
            var key = $(this).attr("name");
            if (key && graphable[key]) {
                data.push(graphable[key]);
			}
        });

        if (data.length > 0) {
            $.plot($("#statistics01"), data, {
				lines: { show: true },
            	points: { show: true },
                yaxis: { min: 0 },
                xaxis: {
                	ticks: <?php echo date('t', $STATISTICS_DATE); ?>,
                	tickDecimals: 0
                }
            });
		}
    }

    plotAccordingToSelection();
});
</script>