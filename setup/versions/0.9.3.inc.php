<?php
/**
 * ListMessenger Pro - Classic Mailing List Management
 * For the most recent version, visit https://listmessenger.com.
 *
 * @copyright 2002-2022 Silentweb https://silentweb.ca
 * @author Matt Simpson <msimpson@listmessenger.com>
 * @license /licence.html ListMessenger Software Licence Agreement
 */
if (!defined('IN_SETUP')) {
    exit;
}

// Confirmation
$query = 'SELECT * FROM `'.TABLES_PREFIX."confirmation` WHERE `confirm_id`='-1';";
$rs = $db->Execute($query);

$query = 'SELECT * FROM `'.TABLES_PREFIX.'user_queue` ORDER BY `id` ASC';
$results = $db->GetAll($query);
if ($results) {
    foreach ($results as $result) {
        if ((($result['action'] != 'usr-subscribe') && ($result['action'] != 'usr-unsubscribe')) || ($result['confirm_value'] == '1') || (($result['confirm_value'] == '0') && ($result['date'] >= (time() - 604800)))) {
            $groups = explode(';', trim($result['user_groups']));
            $record = [];
            $record['confirm_id'] = addslashes($result['id']);
            $record['date'] = addslashes($result['date']);
            $record['action'] = addslashes($result['action']);
            $record['remote_ip'] = addslashes($result['orgin_ip']);
            $record['referrer'] = addslashes($result['orgin_url']);
            $record['user_agent'] = addslashes($result['user_agent']);
            $record['email_address'] = addslashes($result['user_email']);
            $record['lastname'] = addslashes($result['user_name']);
            $record['group_ids'] = addslashes(serialize($groups));
            $record['hash'] = addslashes($result['confirm_string']);
            $record['confirmed'] = addslashes($result['confirm_value']);

            $query = $db->GetInsertSQL($rs, $record, true);
            if ($query != '') {
                if (!$db->Execute($query)) {
                    echo "Confirmation: Didn't work [".$result['id'].']: '.$db->ErrorMsg()."<br />\n";
                }
            }
        } else {
            // Unconfirmed action caught in maintenance.
        }
    }
} else {
    // Nothing to do, great!
}

// Groups
$query = 'SELECT * FROM `'.TABLES_PREFIX."groups` WHERE `groups_id`='-1';";
$rs = $db->Execute($query);

$query = 'SELECT * FROM `'.TABLES_PREFIX.'user_groups` ORDER BY `group_id` ASC';
$results = $db->GetAll($query);
if ($results) {
    foreach ($results as $result) {
        $record = [];
        $record['groups_id'] = addslashes($result['group_id']);
        $record['group_name'] = addslashes($result['group_name']);
        $record['group_parent'] = addslashes($result['belongs_to']);

        $query = $db->GetInsertSQL($rs, $record, true);
        if ($query != '') {
            if (!$db->Execute($query)) {
                echo "Groups: Didn't work [".$result['id'].']: '.$db->ErrorMsg()."<br />\n";
            }
        }
    }
} else {
    // Nothing to do, great!
}

// Messages
$query = 'SELECT * FROM `'.TABLES_PREFIX."messages` WHERE `message_id`='-1';";
$rs = $db->Execute($query);

$query = 'SELECT * FROM `'.TABLES_PREFIX.'email_messages` ORDER BY `message_id` ASC';
$results = $db->GetAll($query);
if ($results) {
    foreach ($results as $result) {
        $record = [];
        $record['message_id'] = addslashes($result['message_id']);
        $record['message_date'] = addslashes($result['date']);
        $record['message_title'] = addslashes($result['title']);
        $record['message_subject'] = addslashes($result['subject']);
        $record['message_from'] = '"'.addslashes($result['from']).'" <'.addslashes($result['fromemail']).'>';
        $record['message_reply'] = '"'.addslashes($result['from']).'" <'.addslashes($result['replyemail']).'>';
        $record['message_priority'] = addslashes($result['priority']);
        $record['text_message'] = addslashes($result['text']);
        $record['html_message'] = addslashes($result['html']);
        $record['html_template'] = addslashes($result['template_id']);

        $query = $db->GetInsertSQL($rs, $record, true);
        if ($query != '') {
            if (!$db->Execute($query)) {
                echo "Messages: Didn't work [".$result['id'].']: '.$db->ErrorMsg()."<br />\n";
            }
        }
    }
} else {
    // Nothing to do, great!
}

// Queue
$query = 'SELECT * FROM `'.TABLES_PREFIX."queue` WHERE `queue_id`='-1';";
$rs = $db->Execute($query);

$query = 'SELECT * FROM `'.TABLES_PREFIX.'email_queues` ORDER BY `queue_id` ASC';
$results = $db->GetAll($query);
if ($results) {
    foreach ($results as $result) {
        $record = [];
        $record['queue_id'] = addslashes($result['queue_id']);
        $record['message_id'] = addslashes($result['message_id']);
        $record['date'] = addslashes($result['date']);
        $record['touch'] = addslashes($result['touch']);
        $record['target'] = addslashes(serialize([trim($result['target'])]));
        $record['progress'] = addslashes(($result['status'] == 'Complete') ? $result['total'] : $result['is_on']);
        $record['total'] = addslashes($result['total']);
        $record['status'] = addslashes($result['status']);

        $query = $db->GetInsertSQL($rs, $record, true);
        if ($query != '') {
            if (!$db->Execute($query)) {
                echo "Queue: Didn't work [".$result['id'].']: '.$db->ErrorMsg()."<br />\n";
            }
        }
    }
} else {
    // Nothing to do, great!
}

// Templates
$query = 'SELECT * FROM `'.TABLES_PREFIX."templates` WHERE `template_id`='-1';";
$rs = $db->Execute($query);

$query = 'SELECT * FROM `'.TABLES_PREFIX.'email_templates` ORDER BY `template_id` ASC';
$results = $db->GetAll($query);
if ($results) {
    foreach ($results as $result) {
        $record = [];
        $record['template_id'] = addslashes($result['template_id']);
        $record['template_name'] = addslashes($result['template_name']);
        $record['template_type'] = 'html';
        $record['template_description'] = addslashes($result['template_description']);
        $record['template_content'] = addslashes(html_decode($result['template_html']));

        $query = $db->GetInsertSQL($rs, $record, true);
        if ($query != '') {
            if (!$db->Execute($query)) {
                echo "Templates: Didn't work [".$result['id'].']: '.$db->ErrorMsg()."<br />\n";
            }
        }
    }
} else {
    // Nothing to do, great!
}

// Users
$query = 'SELECT * FROM `'.TABLES_PREFIX."users` WHERE `users_id`='-1';";
$rs = $db->Execute($query);

$query = 'SELECT * FROM `'.TABLES_PREFIX.'user_list` ORDER BY `user_id` ASC';
$results = $db->GetAll($query);
if ($results) {
    foreach ($results as $result) {
        // Try to figure out when the user subscribed.
        $query = 'SELECT `date` FROM `'.TABLES_PREFIX."user_queue` WHERE `user_email`='".addslashes($result['user_address'])."' AND (`action`='adm-subscribe' OR `action`='usr-subscribe') AND `user_groups` LIKE '%".$result['group_id']."%' ORDER BY `date` DESC";
        $date_result = $db->GetRow($query);
        if ($date_result) {
            $signup_date = $date_result['date'];
        } else {
            $signup_date = time();
        }

        // Try to fix the fullname into first and last name.
        $firstname = '';
        $lastname = '';
        if ($result['user_name'] != '') {
            $fullname = explode(' ', $result['user_name']);
            $pieces = count($fullname);
            switch ($pieces) {
                case 1:
                    $lastname = $fullname[0];
                    break;
                case 2:
                    $firstname = $fullname[0];
                    $lastname = $fullname[1];
                    break;
                default:
                    $firstname = $fullname[0];
                    for ($i = 1; $i <= $pieces; ++$i) {
                        $lastname .= $fullname[$i].(($i < $pieces) ? ' ' : '');
                    }
                    break;
            }
        }

        $record = [];
        $record['users_id'] = addslashes($result['user_id']);
        $record['group_id'] = addslashes($result['group_id']);
        $record['signup_date'] = $signup_date;
        $record['firstname'] = addslashes($firstname);
        $record['lastname'] = addslashes($lastname);
        $record['email_address'] = addslashes($result['user_address']);

        $query = $db->GetInsertSQL($rs, $record, true);
        if ($query != '') {
            if (!$db->Execute($query)) {
                echo "Users: Didn't work [".$result['id'].']: '.$db->ErrorMsg()."<br />\n";
            }
        }
    }
} else {
    // Nothing to do, great!
}
