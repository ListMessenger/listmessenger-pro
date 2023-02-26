<?php
/**
 * ListMessenger Pro - Classic Mailing List Management
 * For the most recent version, visit https://listmessenger.com.
 *
 * @copyright 2002-2022 Silentweb https://silentweb.ca
 * @author Matt Simpson <msimpson@listmessenger.com>
 * @license /licence.html ListMessenger Software Licence Agreement
 */

// Change the $LM_PATH variable in the public_config.inc.php file in this directory.
require_once './public_config.inc.php';

if (!file_exists($LM_PATH.'includes/config.inc.php')) {
    echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"DTD/xhtml1-transitional.dtd\">\n";
    echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n";
    echo "<head>\n";
    echo "	<title>ListMessenger Path Error</title>\n";
    echo "	<style type=\"text/css\">\n";
    echo "	div.error-message {\n";
    echo "		background-color:	#FFD9D0;\n";
    echo "		border:				1px #CC0000 solid;\n";
    echo "		padding:			8px;\n";
    echo "		color:				#333333;\n";
    echo "		font-family:		Verdana, Arial, Helvetica, sans-serif;\n";
    echo "		font-size:			12px;\n";
    echo "		margin-bottom:		10px;\n";
    echo "	}\n";
    echo "	</style>\n";
    echo "</head>\n";
    echo "<body>\n";
    echo "<div class=\"error-message\">\n";
    echo '	The path to the ListMessenger directory that you have provided [<strong>'.$LM_PATH.'</strong>] appears to be invalid or PHP does not have permission to read files from this directory. Please ensure that you provide the full path from root to your ListMessenger program directory in the $LM_PATH variable within this file [<strong>'.__FILE__.'</strong>].';
    echo "</div>\n";
    echo "</body>\n";
    echo "</html>\n";
    exit;
} else {
    ini_set('include_path', str_replace('\\', '/', $LM_PATH.'/includes'));
    ini_set('allow_url_fopen', 1);
    ini_set('error_reporting', E_ALL ^ E_NOTICE);
    ini_set('magic_quotes_runtime', 0);

    define('TOOLS_LOADED', true);
    require_once 'eu_header.inc.php';

    $MESSAGE_ID = 0;
    $QUEUE_ID = 0;

    if (empty($LM_REQUEST['view'])) {
        $LM_REQUEST['view'] = false;
    }

    if ($config[ENDUSER_ARCHIVE] == 'yes') {
        switch ($LM_REQUEST['view']) {
            case 'html':
                $display_message = false;
                $content_displayed = false;

                /*
                 * Check if there is a message_id and queue_id in the request.
                 */
                if ((!empty($LM_REQUEST['id'])) && ($LM_REQUEST['id'] = clean_input($LM_REQUEST['id'], 'nows'))) {
                    if (is_array($pieces = explode(':', $LM_REQUEST['id']))) {
                        if ((!empty($pieces[0])) && ($tmp_input = clean_input($pieces[0], ['int']))) {
                            $MESSAGE_ID = $tmp_input;
                        }
                        if ((!empty($pieces[1])) && ($tmp_input = clean_input($pieces[1], ['int']))) {
                            $QUEUE_ID = $tmp_input;
                        }
                    }
                }

                if ($MESSAGE_ID) {
                    $query = '
								SELECT a.`html_template`, a.`html_message`, a.`text_template`, a.`text_message`, b.`target`
								FROM `'.TABLES_PREFIX.'messages` AS a
								LEFT JOIN `'.TABLES_PREFIX.'queue` AS b
								ON b.`message_id` = a.`message_id`
								WHERE a.`message_id` = '.$db->qstr($MESSAGE_ID).'
								'.(($QUEUE_ID) ? 'AND b.`queue_id` = '.$db->qstr($QUEUE_ID) : '')."
								AND b.`status` = 'Complete'";
                    $results = $db->GetAll($query);
                    if ($results) {
                        foreach ($results as $result) {
                            $target = unserialize($result['target']);

                            if (is_array($target)) {
                                $target_groups = groups_information($target);

                                if (is_array($target_groups) && ($total_groups = count($target_groups))) {
                                    $private_groups = 0;

                                    foreach ($target_groups as $group) {
                                        if (empty($group['private']) || $group['private'] == 'true') {
                                            ++$private_groups;
                                        }
                                    }

                                    if ($private_groups < $total_groups) {
                                        $display_message = true;

                                        break;
                                    }
                                }
                            }
                        }

                        /*
                         * If this message was sent to only private groups,
                         * do not display the message.
                         */
                        if ($display_message) {
                            $html_version = insert_template('html', $result['html_template'], $result['html_message'])."\n";
                            if (trim(strip_tags($html_version))) {
                                $content_displayed = true;

                                echo $html_version;
                            } else {
                                $text_version = insert_template('text', $result['text_template'], $result['text_message'])."\n";
                                if (trim(strip_tags($text_version))) {
                                    $content_displayed = true;

                                    echo nl2br($text_version);
                                }
                            }
                        }
                    }
                }

                if (!$content_displayed) {
                    echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"DTD/xhtml1-transitional.dtd\">\n";
                    echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n";
                    echo "<head>\n";
                    echo '	<title>'.$LANGUAGE_PACK['page_archive_error_html_title']."</title>\n";
                    echo "</head>\n";
                    echo "<body>\n";
                    echo "<div style=\"font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 12px; background-color: #FFFFCC; border: 1px #FFCC00 solid; padding: 5px\">\n";
                    echo '	'.$LANGUAGE_PACK['page_archive_error_no_message']."\n";
                    echo "</div>\n";
                    echo "</body>\n";
                    echo "</html>\n";
                }
                exit;
                break;
            case 'feed':
            case 'rss':
                $FEED_FORMAT = 'RSS0.91';

                $VALID_FORMATS = [];
                $VALID_FORMATS['rss091'] = 'RSS0.91';
                $VALID_FORMATS['rss10'] = 'RSS1.0';
                $VALID_FORMATS['rss20'] = 'RSS2.0';
                $VALID_FORMATS['opml'] = 'OPML';
                $VALID_FORMATS['atom'] = 'ATOM';

                /*
                 * If the format is specified via the URL, set the format.
                 */
                if ((!empty($LM_REQUEST['f'])) && array_key_exists($LM_REQUEST['f'] = clean_input($LM_REQUEST['f'], ['trim', 'lower']), $VALID_FORMATS)) {
                    $FEED_FORMAT = $VALID_FORMATS[$LM_REQUEST['f']];
                }

                require_once 'classes/feedcreator/feedcreator.class.php';

                $rss = new UniversalFeedCreator();
                $rss->useCached();

                $rss->title = $LANGUAGE_PACK['page_archive_rss_title'];
                $rss->description = $LANGUAGE_PACK['page_archive_rss_description'];
                $rss->link = (($LANGUAGE_PACK['page_archive_rss_link']) ? $LANGUAGE_PACK['page_archive_rss_link'] : $config[PREF_PUBLIC_URL].$config[ENDUSER_HELP_FILENAME]);
                $rss->syndicationURL = $config[PREF_PUBLIC_URL].$config[ENDUSER_ARCHIVE].'?view=feed'.(($FEED_FORMAT != 'RSS0.91') ? '&f='.$LM_REQUEST['f'] : '');

                $query = '	SELECT a.*, b.*
							FROM `'.TABLES_PREFIX.'messages` AS a
							LEFT JOIN `'.TABLES_PREFIX."queue` AS b
							ON b.`message_id` = a.`message_id`
							WHERE b.`status` = 'Complete'
							ORDER BY b.`date` DESC
							LIMIT 0, 50";
                $results = $db->GetAll($query);
                if ($results) {
                    foreach ($results as $result) {
                        $display_message = false;
                        $target = unserialize($result['target']);

                        if (is_array($target)) {
                            $target_groups = groups_information($target);

                            if (is_array($target_groups) && ($total_groups = count($target_groups))) {
                                $private_groups = 0;

                                foreach ($target_groups as $group) {
                                    if (empty($group['private']) || $group['private'] == 'true') {
                                        ++$private_groups;
                                    }
                                }

                                if ($private_groups < $total_groups) {
                                    $display_message = true;
                                }
                            }
                        }

                        /*
                         * If this message was sent to only private groups,
                         * do not display the message.
                         */
                        if ($display_message) {
                            if (trim(strip_tags($result['html_message']))) {
                                $rss_description = insert_template('html', $result['html_template'], $result['html_message']);
                            } else {
                                $rss_description = nl2br(insert_template('text', $result['text_template'], $result['text_message']));
                            }

                            $item = new FeedItem();
                            $item->title = $result['message_subject'];
                            $item->link = $config[PREF_PUBLIC_URL].$config[ENDUSER_ARCHIVE_FILENAME].'?id='.$result['message_id'].':'.$result['queue_id'];
                            $item->description = $rss_description;
                            $item->date = (int) display_date('U', $result['date']);
                            $item->author = $result['message_reply'];
                            $rss->addItem($item);
                        }
                    }
                }
                echo $rss->createFeed($FEED_FORMAT);
                exit;
                break;
            default:
                $display_message = false;

                /*
                 * Check if there is a message_id and queue_id in the request.
                 */
                if ((!empty($LM_REQUEST['id'])) && ($LM_REQUEST['id'] = clean_input($LM_REQUEST['id'], 'nows'))) {
                    if (is_array($pieces = explode(':', $LM_REQUEST['id']))) {
                        if ((!empty($pieces[0])) && ($tmp_input = clean_input($pieces[0], ['int']))) {
                            $MESSAGE_ID = $tmp_input;
                        }
                        if ((!empty($pieces[1])) && ($tmp_input = clean_input($pieces[1], ['int']))) {
                            $QUEUE_ID = $tmp_input;
                        }
                    }
                }

                if ($MESSAGE_ID) {
                    $TITLE = $LANGUAGE_PACK['page_archive_view_title'];
                    $MESSAGE = '';

                    $query = '	SELECT a.*, b.*
									FROM `'.TABLES_PREFIX.'messages` AS a
									LEFT JOIN `'.TABLES_PREFIX.'queue` AS b
									ON b.`message_id` = a.`message_id`
									WHERE a.`message_id` = '.$db->qstr($MESSAGE_ID).'
									'.(($QUEUE_ID) ? 'AND b.`queue_id` = '.$db->qstr($QUEUE_ID) : '')."
									AND b.`status` = 'Complete'";
                    $results = $db->GetAll($query);
                    if ($results) {
                        foreach ($results as $result) {
                            $target = unserialize($result['target']);
                            $attachments = unserialize($result['attachments']);

                            if (is_array($target)) {
                                $target_groups = groups_information($target);

                                if (is_array($target_groups) && ($total_groups = count($target_groups))) {
                                    $private_groups = 0;

                                    foreach ($target_groups as $group) {
                                        if (empty($group['private']) || $group['private'] == 'true') {
                                            ++$private_groups;
                                        }
                                    }

                                    if ($private_groups < $total_groups) {
                                        $display_message = true;

                                        break;
                                    }
                                }
                            }
                        }

                        /*
                         * If this message was sent to only private groups,
                         * do not display the message.
                         */
                        if ($display_message) {
                            $MESSAGE .= "<table style=\"width: 100%\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\">\n";
                            $MESSAGE .= "<tbody>\n";
                            $MESSAGE .= "	<tr>\n";
                            $MESSAGE .= '		<td style="width: 12%; white-space: nowrap; text-align: right; padding-right: 5px; color: #666666; font-weight: bold">'.$LANGUAGE_PACK['page_archive_view_from']."</td>\n";
                            $MESSAGE .= '		<td style="width: 88%; padding-left: 3px">'.public_html_encode($result['message_from'])."</td>\n";
                            $MESSAGE .= "	</tr>\n";
                            $MESSAGE .= "	<tr>\n";
                            $MESSAGE .= '		<td style="white-space: nowrap; text-align: right; padding-right: 5px; color: #666666; font-weight: bold">'.$LANGUAGE_PACK['page_archive_view_subject']."</td>\n";
                            $MESSAGE .= '		<td style="padding-left: 3px; font-weight: bold">'.public_html_encode($result['message_subject'])."</td>\n";
                            $MESSAGE .= "	</tr>\n";
                            $MESSAGE .= "	<tr>\n";
                            $MESSAGE .= '		<td style="white-space: nowrap; text-align: right; padding-right: 5px; color: #666666; font-weight: bold">'.$LANGUAGE_PACK['page_archive_view_date']."</td>\n";
                            $MESSAGE .= '		<td style="padding-left: 3px">'.display_date($config[PREF_DATEFORMAT], $result['date'], false)."</td>\n";
                            $MESSAGE .= "	</tr>\n";
                            $MESSAGE .= "	<tr>\n";
                            $MESSAGE .= '		<td style="white-space: nowrap; text-align: right; padding-right: 5px; color: #666666; font-weight: bold; vertical-align: top">'.$LANGUAGE_PACK['page_archive_view_to']."</td>\n";
                            $MESSAGE .= "		<td style=\"padding-left: 3px\">\n";
                            foreach ($target_groups as $group) {
                                $MESSAGE .= '		&rarr; '.$group['name']."<br />\n";
                            }
                            $MESSAGE .= "		</td>\n";
                            $MESSAGE .= "	</tr>\n";
                            if (is_array($attachments) && (count($attachments) > 0)) {
                                $MESSAGE .= "<tr>\n";
                                $MESSAGE .= '	<td style="white-space: nowrap; text-align: right; padding-right: 5px; color: #666666; font-weight: bold; vertical-align: top">'.$LANGUAGE_PACK['page_archive_view_attachments']."</td>\n";
                                $MESSAGE .= "	<td style=\"padding-left: 3px\">\n";
                                foreach ($attachments as $attachment) {
                                    if (file_exists($config[PREF_PUBLIC_PATH].'files/'.$attachment)) {
                                        $MESSAGE .= '&rarr; <a href="'.$config[PREF_PUBLIC_URL].'files/'.public_html_encode($attachment).'">'.public_html_encode($attachment).'</a> <span style="color: #666666; font-style: oblique">('.readable_size(filesize($config[PREF_PUBLIC_PATH].'files/'.$attachment)).")</span><br />\n";
                                    } else {
                                        $MESSAGE .= '<div style="background-color: #FFFFCC; border: 1px #FFCC00 solid; padding: 2px">'.$LANGUAGE_PACK['page_archive_view_missing_attachment'].' ('.$attachment.").</div>\n";
                                    }
                                }
                                $MESSAGE .= "	</td>\n";
                                $MESSAGE .= "</tr>\n";
                            }
                            $MESSAGE .= "<tr>\n";
                            $MESSAGE .= "	<td colspan=\"2\" style=\"border-bottom: 1px #CCCCCC solid\">&nbsp;</td>\n";
                            $MESSAGE .= "</tr>\n";
                            $MESSAGE .= "<tr>\n";
                            $MESSAGE .= "	<td colspan=\"2\" style=\"padding-top: 10px\">\n";
                            if (trim(strip_tags($result['html_message'])) == '') {
                                $MESSAGE .= checkslashes(nl2br(public_html_encode(trim(insert_template('text', $result['text_template'], $result['text_message'])))), 1);
                            } else {
                                $MESSAGE .= '<iframe name="HTMLMessage" style="width: 100%; height: 400px; margin: 0px; padding: 0px; border: 0px" src="'.$config[PREF_PUBLIC_URL].$config[ENDUSER_ARCHIVE_FILENAME].'?view=html&id='.$MESSAGE_ID.(($QUEUE_ID) ? ':'.$QUEUE_ID : '')."\"></iframe>\n";
                            }
                            $MESSAGE .= "	</td>\n";
                            $MESSAGE .= "</tr>\n";
                            $MESSAGE .= "</table>\n";
                        } else {
                            $MESSAGE .= "<div class=\"error-message\">\n";
                            $MESSAGE .= '	'.$LANGUAGE_PACK['page_archive_error_no_message']."\n";
                            $MESSAGE .= "</div>\n";
                            $MESSAGE .= "<script language=\"JavaScript\" type=\"text/javascript\">\n";
                            $MESSAGE .= "setTimeout('window.location=\'".$config[PREF_PUBLIC_URL].$config[ENDUSER_ARCHIVE_FILENAME]."\'', 5000);\n";
                            $MESSAGE .= "</script>\n";
                        }
                    } else {
                        $MESSAGE .= "<div class=\"error-message\">\n";
                        $MESSAGE .= '	'.$LANGUAGE_PACK['page_archive_error_no_message']."\n";
                        $MESSAGE .= "</div>\n";
                        $MESSAGE .= "<script language=\"JavaScript\" type=\"text/javascript\">\n";
                        $MESSAGE .= "setTimeout('window.location=\'".$config[PREF_PUBLIC_URL].$config[ENDUSER_ARCHIVE_FILENAME]."\'', 5000);\n";
                        $MESSAGE .= "</script>\n";
                    }
                } else {
                    /**
                     * Include the pagination class.
                     */
                    require_once 'classes/pagination/pagination.class.php';

                    $TITLE = $LANGUAGE_PACK['page_archive_opened_title'];
                    $MESSAGE = str_replace('[rssfeed_url]', '<a href="'.$config[PREF_PUBLIC_URL].$config[ENDUSER_ARCHIVE_FILENAME].'?view=feed">'.$LANGUAGE_PACK['page_archive_rss_title'].'</a>', $LANGUAGE_PACK['page_archive_opened_message_sentence']).'<br /><br />';

                    $messages = [];
                    $total_rows = 0;
                    $total_pages = 1;
                    $page_current = 1;

                    /**
                     * Get the total number of results using the generated queries above and calculate the total number
                     * of pages that are available based on the results per page preferences.
                     */
                    $query = '	SELECT a.*, b.*
								FROM `'.TABLES_PREFIX.'messages` AS a
								LEFT JOIN `'.TABLES_PREFIX."queue` AS b
								ON b.`message_id` = a.`message_id`
								WHERE b.`status` = 'Complete'
								ORDER BY b.`date` DESC";
                    $results = $db->GetAll($query);
                    if ($results) {
                        foreach ($results as $result) {
                            if (!empty($result['target']) && ($target = unserialize($result['target'])) && is_array($target)) {
                                $target_groups = groups_information($target);

                                if (is_array($target_groups) && ($total_groups = count($target_groups))) {
                                    $private_groups = 0;

                                    foreach ($target_groups as $group) {
                                        if (empty($group['private']) || $group['private'] == 'true') {
                                            ++$private_groups;
                                        }
                                    }

                                    if ($private_groups < $total_groups) {
                                        $messages[] = $result;
                                    }
                                }
                            }
                        }

                        $total_rows = count($messages);

                        if ($total_rows <= $config[PREF_PERPAGE_ID]) {
                            $total_pages = 1;
                        } elseif (($total_rows % $config[PREF_PERPAGE_ID]) == 0) {
                            $total_pages = (int) ($total_rows / $config[PREF_PERPAGE_ID]);
                        } else {
                            $total_pages = (int) ($total_rows / $config[PREF_PERPAGE_ID]) + 1;
                        }

                        /*
                         * Check if pv variable is set and see if it's a valid page, other wise page 1 it is.
                         */
                        if (!empty($LM_REQUEST['pv'])) {
                            $page_current = (int) trim($LM_REQUEST['pv']);

                            if (($page_current < 1) || ($page_current > $total_pages)) {
                                $page_current = 1;
                            }
                        }

                        if ($total_pages > 1) {
                            $pagination = new Pagination($page_current, $config[PREF_PERPAGE_ID], $total_rows, $config[PREF_PUBLIC_URL].$config[ENDUSER_ARCHIVE_FILENAME], replace_query());
                        }
                    }

                    $page_previous = (($page_current > 1) ? ($page_current - 1) : false);
                    $page_next = (($page_current < $total_pages) ? ($page_current + 1) : false);
                    $limit = (int) (($config[PREF_PERPAGE_ID] * $page_current) - $config[PREF_PERPAGE_ID]);

                    if (!empty($messages)) {
                        if ($total_pages > 1) {
                            $MESSAGE .= "<div class=\"pagination\">\n";
                            $MESSAGE .= '<span class="label">'.$LANGUAGE_PACK['page_archive_pagination'].'</span>'.$pagination->GetPageLinks();
                            $MESSAGE .= "</div>\n";
                        }
                        $MESSAGE .= "<table style=\"width: 100%\" cellspacing=\"0\" cellpadding=\"3\" border=\"0\">\n";
                        $MESSAGE .= "<colgroup>\n";
                        $MESSAGE .= "	<col style=\"width: 50%\" />\n";
                        $MESSAGE .= "	<col style=\"width: 25%\" />\n";
                        $MESSAGE .= "	<col style=\"width: 25%\" />\n";
                        $MESSAGE .= "</colgroup>\n";
                        $MESSAGE .= "<thead>\n";
                        $MESSAGE .= "	<tr>\n";
                        $MESSAGE .= '		<td style="border-bottom: 2px #CCCCCC solid; padding-left: 3px; font-weight: bold">'.$LANGUAGE_PACK['page_archive_view_message_subject']."</td>\n";
                        $MESSAGE .= '		<td style="border-bottom: 2px #CCCCCC solid; padding-left: 3px; font-weight: bold">'.$LANGUAGE_PACK['page_archive_view_message_from']."</td>\n";
                        $MESSAGE .= '		<td style="border-bottom: 2px #CCCCCC solid; padding-left: 3px; font-weight: bold">'.$LANGUAGE_PACK['page_archive_view_message_sent']."</td>\n";
                        $MESSAGE .= "	</tr>\n";
                        $MESSAGE .= "</thead>\n";
                        $MESSAGE .= "<tbody>\n";
                        foreach (range($limit, $limit + ($config[PREF_PERPAGE_ID] - 1)) as $key) {
                            if (!empty($messages[$key])) {
                                $result = $messages[$key];

                                $pieces = explode('" <', $result['message_from']);
                                $name = substr($pieces[0], 1);
                                $address = substr($pieces[1], 0, -1);

                                $MESSAGE .= "<tr>\n";
                                $MESSAGE .= '	<td><a href="'.$config[PREF_PUBLIC_URL].$config[ENDUSER_ARCHIVE_FILENAME].'?id='.$result['message_id'].':'.$result['queue_id'].'">'.public_html_encode($result['message_subject'])."</a></td>\n";
                                $MESSAGE .= '	<td><a href="'.$config[PREF_PUBLIC_URL].$config[ENDUSER_ARCHIVE_FILENAME].'?id='.$result['message_id'].':'.$result['queue_id'].'">'.public_html_encode($name)."</a></td>\n";
                                $MESSAGE .= '	<td>'.display_date($config[PREF_DATEFORMAT], $result['date'], false)."</td>\n";
                                $MESSAGE .= "</tr>\n";
                            }
                        }
                        $MESSAGE .= "</tbody>\n";
                        $MESSAGE .= "</table>\n";
                    } else {
                        $MESSAGE .= "<div style=\"background-color: #FFFFCC; border: 1px #FFCC00 solid; padding: 5px\">\n";
                        $MESSAGE .= '	'.$LANGUAGE_PACK['page_archive_error_no_messages']."\n";
                        $MESSAGE .= "</div>\n";
                    }
                }
                break;
        }
    } else {
        $abuse = encode_address($config[PREF_ABUEMAL_ID]);
        $TITLE = $LANGUAGE_PACK['page_archive_closed_title'];
        $MESSAGE = $LANGUAGE_PACK['page_archive_closed_message_sentence'];
        $MESSAGE = str_replace('[abuse_address]', '<a href="mailto:'.$abuse['address'].'" style="font-weight: strong">'.$abuse['text'].'</a>', $MESSAGE);
    }

    require_once 'eu_footer.inc.php';
}
