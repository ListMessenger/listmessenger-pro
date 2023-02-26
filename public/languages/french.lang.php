<?php
/*
<language name="French" version="2.2.0">
    <translator_name>Erik Geurts / Babelfish</translator_name>
    <translator_email>info@listmessenger.net</translator_email>
    <translator_url>http://listmessenger.net</translator_url>
    <updated>2006-01-03</updated>
    <notes></notes>
</language>
*/
$LANGUAGE_PACK = [];

$LANGUAGE_PACK['default_page_title'] = 'Syst&egrave;me de gestion de la liste de diffusion';
$LANGUAGE_PACK['default_page_message'] = "Veuillez visiter notre site principal pour souscrire &agrave; ou pour vous d&eacute;sinscrire de nos listes d'envoi.";
$LANGUAGE_PACK['error_default_title'] = 'Erreur de requ&ecirc;te';
$LANGUAGE_PACK['error_invalid_action'] = "L'action demand&eacute;e est impossible, assurez-vous svp de bien avoir acc&egrave;s &agrave; ce syst&egrave;me par un abonnement souscrit sur notre site. Vous avez besoin d'aide? SVP contactez l'administrateur du site.";

$LANGUAGE_PACK['error_subscribe_no_groups'] = "Vous devez choisir au moins une liste de diffusion &agrave; laquelle souscrire. Vous avez besoin d'aide? SVP contactez l'administrateur du site.";
$LANGUAGE_PACK['error_subscribe_group_not_found'] = "La liste de diffusion &agrave; laquelle vous essayez de souscrire n'existe plus. Vous avez besoin d'aide? SVP contactez l'administrateur du site.";
$LANGUAGE_PACK['error_subscribe_email_exists'] = "L'adresse email que vous avez fournie, existe d&eacute;j&agrave; dans notre base de donn&eacute;es. Vous avez besoin d'aide? SVP contactez l'administrateur du site.";
$LANGUAGE_PACK['error_subscribe_no_email'] = "Veuillez entrer l'adresse email que vous voudriez inscrire sur notre liste de diffusion.<p><a href='javascript:back()'>Cliquez ici pour revenir &agrave; l'&eacute;cran pr&eacute;c&eacute;dent.</a>";
$LANGUAGE_PACK['error_subscribe_invalid_email'] = "L'adresse email que vous avez fournie n'est pas une adresse email valide.";
$LANGUAGE_PACK['error_subscribe_banned_email'] = "L'adresse email que vous avez fournie est interdite sur ce syst&egrave;me actuellement.";
$LANGUAGE_PACK['error_subscribe_banned_ip'] = "Le Domaine de l'adresse email que vous avez fournie est interdit sur ce syst&egrave;me.";
$LANGUAGE_PACK['error_subscribe_invalid_domain'] = "Le Domaine de l'adresse email que vous avez fournie ne semble pas &ecirc;tre un domaine valide.";
$LANGUAGE_PACK['error_subscribe_required_cfield'] = "&quot;[cfield_name]&quot; est une zone d'information obligatoire. Veuillez revenir et entrer correctement cette information.";	// Requires [cfield_name] variable in sentence.
$LANGUAGE_PACK['error_subscribe_failed_optin'] = "Nous ne pouvons malheureusement pas vous envoyer d'email de confirmation d'inscription &agrave; notre liste de diffusion. Veuillez contacter l'administrateur du site et l'informer de ce probl&egrave;me.";
$LANGUAGE_PACK['error_subscribe_failed'] = "Nous ne pouvons malheureusement pas inscrire votre adresse email sur notre liste de diffusion. Veuillez contacter l'administrateur du site et l'informer de ce probl&egrave;me.";
$LANGUAGE_PACK['success_subscribe_optin_title'] = 'Un email de confirmation vous a &eacute;t&eacute; envoy&eacute;';
$LANGUAGE_PACK['success_subscribe_optin_message'] = "Merci de votre int&eacute;r&ecirc;t pour notre liste de diffusion. Vous recevrez une confirmation d'inscription sous peu, merci de confirmer votre abonnement en suivant le lien de confirmation inclus dans cet email.";
$LANGUAGE_PACK['success_subscribe_title'] = "L'abonnement &agrave; la liste de diffusion est effectu&eacute;";
$LANGUAGE_PACK['success_subscribe_message'] = "Merci de votre int&eacute;r&ecirc;t pour notre liste de diffusion. Votre adresse email a &eacute;t&eacute; ajout&eacute;e &agrave; notre liste de diffusion, vous recevrez tous les futurs messages &agrave; l'adresse que vous avez fournie.";

$LANGUAGE_PACK['error_unsubscribe_no_groups'] = "Vous devez choisir au moins une liste de diffusion pour vous en d&eacute;sinscrire. Vous avez besoin d'aide? SVP contactez l'administrateur du site.";
$LANGUAGE_PACK['error_unsubscribe_group_not_found'] = "La liste de diffusion dont vous essayez de vous d&eacute;sinscrire n'existe plus sur notre syst&egrave;me. Vous avez besoin d'aide? SVP contactez l'administrateur du site.";
$LANGUAGE_PACK['error_unsubscribe_email_not_found'] = "L'adresse email que vous avez fournie n'existe pas dans notre base de donn&eacute;es. Vous avez besoin d'aide? SVP contactez l'administrateur du site.";
$LANGUAGE_PACK['error_unsubscribe_email_not_exists'] = "L'adresse email que vous avez fournie n'existe plus dans notre base de donn&eacute;es. Vous avez besoin d'aide? SVP contactez l'administrateur du site.";
$LANGUAGE_PACK['error_unsubscribe_no_email'] = 'Veuillez fournir votre adresse email pour vous d&eacute;sinscrire de notre liste de diffusion.';
$LANGUAGE_PACK['error_unsubscribe_invalid_email'] = "L'adresse email que vous avez fournie n'est pas valide.";
$LANGUAGE_PACK['error_unsubscribe_failed_optout'] = "Nous ne pouvons pas malheureusement vous envoyer un email de confirmation &agrave; cause d'un probl&egrave;me que nous rencontrons actuellement. Veuillez informer l'administrateur du site que vous avez un probl&egrave;me en essayant de vous d&eacute;sinscrire de notre liste de diffusion.";
$LANGUAGE_PACK['error_update_profile'] = "Nous ne pouvons pas malheureusement vous envoyer un email de confirmation &agrave; cause d'un probl&egrave;me que nous rencontrons actuellement. Merci de contacter l'administrateur du site et de l'informer des difficult&eacute;s que vous avez rencontr&eacute;es pour mettre &agrave; jour votre profil.";
$LANGUAGE_PACK['success_unsubscribe_optout_title'] = 'Un email de confirmation vous a &eacute;t&eacute; envoy&eacute;';
$LANGUAGE_PACK['success_unsubscribe_optout_message'] = "Nous sommes d&eacute;sol&eacute;s de votre d&eacute;sinscription &agrave; notre liste de diffusion. Pour la confirmer, merci de suivre le lien qui se trouve dans l'email de confirmation que nous vous avons envoy&eacute;.";
$LANGUAGE_PACK['success_unsubscribe_title'] = 'Le changement de liste de diffusion est effectu&eacute;.';
$LANGUAGE_PACK['success_unsubscribe_message'] = 'Nous sommes d&eacute;sol&eacute;s de votre d&eacute;sinscription &agrave; notre liste de diffusion. Votre adresse email a &eacute;t&eacute; enlev&eacute;e de la liste de diffusion. Vous pouvez vous r&eacute;inscrire &agrave; tout moment.';

$LANGUAGE_PACK['error_expired_code'] = 'Ce code a expir&eacute; apr&egrave;s 7 jours, pour mettre &agrave; jour votre profil, merci de nous demander un nouveau code de confirmation.';
$LANGUAGE_PACK['error_confirm_invalid_request'] = "Nous ne pouvons interpr&eacute;ter demande. Si vous cliquez sur le lien de confirmation que vous avez re�u sans succ&egrave;s, vous pouvez essayer de copier-coller ce m&ecirc;me lien dans votre barre d'adresse.";
$LANGUAGE_PACK['error_confirm_completed'] = "Vous avez d&eacute;j&agrave; confirm&eacute; cette requ&ecirc;te. Aucune action suppl&eacute;mentaire n'est n&eacute;cessaire, merci.";
$LANGUAGE_PACK['error_confirm_unable_request'] = "Nous vous prions d'accepter no excuses pour la g&ecirc;ne occasionn&eacute;e; cependant, nous ne pouvons traiter votre demande actuellement. Veuillez contacter l'administrateur du site et l'informer de ce probl&egrave;me.";
$LANGUAGE_PACK['error_confirm_unable_find_info'] = "Nous vous prions d'accepter nos excuses pour la g&ecirc;ne occasionn&eacute;e; cependant, nous ne pouvons trouver aucune information valide concernant votre adresse email dans notre base de donn&eacute;es. Veuillez contacter l'administrateur du site et l'informer de ce probl&egrave;me.";

$LANGUAGE_PACK['page_confirm_title'] = "Confirmation d'abonnement &agrave; notre liste de diffusion";
$LANGUAGE_PACK['page_confirm_message_sentence'] = "Veuillez confirmer l'information suivante avant de cliquer sur le bouton de confirmation.";
$LANGUAGE_PACK['page_confirm_firstname'] = 'Pr&eacute;nom:';
$LANGUAGE_PACK['page_confirm_lastname'] = 'Nom:';
$LANGUAGE_PACK['page_confirm_email_address'] = 'Adresse email:';
$LANGUAGE_PACK['page_confirm_group_info'] = 'Groupe:';
$LANGUAGE_PACK['page_confirm_cancel_button'] = 'Annulation';
$LANGUAGE_PACK['page_confirm_submit_button'] = 'Confirmation';

$LANGUAGE_PACK['page_captcha_invalid'] = "Le code de s&eacute;curit&eacute; que vous avez fourni n'est pas correct, merci de revenir et de l'entrer &agrave; nouveau.";
$LANGUAGE_PACK['page_captcha_title'] = 'CAPTCHA Image de S&eacute;curit&eacute;';
$LANGUAGE_PACK['page_captcha_message_sentence'] = "Pour aider &agrave; pr&eacute;venir les robots automatis&eacute;s d'acc&eacute;der &agrave; notre mailing liste, nous avons besoin que vous entrez le texte que vous voyez dans l'image ci-dessous.";
$LANGUAGE_PACK['page_captcha_label'] = 'Code de S&eacute;curit&eacute;';

$LANGUAGE_PACK['page_forward_title'] = 'Transf&eacute;rer un Message &agrave; un Ami';
$LANGUAGE_PACK['page_forward_closed_title'] = 'Transf&eacute;rer un Message &agrave; un Ami Non Disponible';
$LANGUAGE_PACK['page_forward_closed_message_sentence'] = "La partie avant &agrave; un ami fonctionnalit&eacute; est actuellement ferm&eacute;e. Si vous avez besoin d'aide, s'il vous pla�t contacter un administrateur au [abuse_address].";	// Requires [abuse_address] variable in sentence.
$LANGUAGE_PACK['page_forward_error_no_message'] = "Nous n'avons pas pu trouver le message que vous essayiez de transmettre &agrave; un ami.";
$LANGUAGE_PACK['page_forward_error_private'] = "Le message que vous essayez de transmettre a &eacute;t&eacute; envoy&eacute; uniquement &agrave; des listes, et n'&eacute;tait pas destin&eacute; &agrave; &ecirctre transmis &agrave; des amis.";
$LANGUAGE_PACK['page_forward_message_sentence'] = "Afin d'envoyer ce message &agrave; vos amis, s'il vous pla�t entrer leurs coordonn&eacute;es et &eacute;ventuellement un message personnalis&eacute; en utilisant le formulaire ci-dessous.";
$LANGUAGE_PACK['page_forward_from_header'] = 'Votre Information';
$LANGUAGE_PACK['page_forward_from_name'] = 'Votre Nom';
$LANGUAGE_PACK['page_forward_from_email'] = 'Votre E-mail';
$LANGUAGE_PACK['page_forward_friend_header'] = "Vos Amis D'information";
$LANGUAGE_PACK['page_forward_friend_name'] = 'Amis Nom';
$LANGUAGE_PACK['page_forward_friend_email'] = 'Amis E-mail';
$LANGUAGE_PACK['page_forward_optional_message'] = 'Message Facultatif';
$LANGUAGE_PACK['page_forward_cancel_button'] = 'Annulation';
$LANGUAGE_PACK['page_forward_submit_button'] = 'Confirmation';
$LANGUAGE_PACK['page_forward_error_from_name'] = "S'il vous pla&icirc;t fournir votre nom dans le champ Nom Votre.";
$LANGUAGE_PACK['page_forward_error_from_email'] = "S'il vous pla&icirc;t fournir votre adresse e-mail dans le Votre E-Mail champ Adresse.";
$LANGUAGE_PACK['page_forward_error_friend_name'] = "S'il vous pla&icirc;t fournir vos amis nom dans le champ Nom Amis.";
$LANGUAGE_PACK['page_forward_error_friend_email'] = "S'il vous pla&icirc;t fournir vos amis adresse e-mail dans les Amis E-Mail champ Adresse.";
$LANGUAGE_PACK['page_forward_error_failed_send'] = "Nous n'avons pas pu envoyer ce message &agrave; votre ami &agrave; ce moment-l&agrave;, nous nous excusons de tout inconv&eacute;nient que cela cause mai.";
$LANGUAGE_PACK['page_forward_successful_send'] = 'Ce message a &eacute;t&eacute; envoy&eacute; avec succ&egrave;s &agrave; [email_address].';
$LANGUAGE_PACK['page_forward_subject_prefix'] = '[FWD: ';
$LANGUAGE_PACK['page_forward_subject_suffix'] = ']';

$LANGUAGE_PACK['page_forward_text_message_prefix'] = <<<TEXTPREFIX
    Bonjour [name],

    [from_name] pensais que vous en mai &ecirc;tre int&eacute;ress&eacute; par les e-mail.
    [optional_message]
    [subscribe_paragraph]
    TEXTPREFIX;

$LANGUAGE_PACK['page_forward_text_subscribe_paragraph'] = <<<SUBSCRIBEPARAGRAPH
    Vous n'avez pas &ecirc;t&ecirc; inscrit &agrave; aucune liste, mais si vous souhaitez vous abonner &agrave; cette liste s'il vous pla�t, visitez le site:
    [subscribe_url]
    SUBSCRIBEPARAGRAPH;

$LANGUAGE_PACK['page_forward_text_message_suffix'] = '';

$LANGUAGE_PACK['page_forward_html_message_prefix'] = <<<HTMLPREFIX
    Bonjour <strong>[name]</strong>,
    <br /><br />
    [from_name] pensais que vous en mai &ecirc;tre int&eacute;ress&eacute; par les e-mail.<br />
    [optional_message]
    [subscribe_paragraph]
    HTMLPREFIX;

$LANGUAGE_PACK['page_forward_html_subscribe_paragraph'] = <<<SUBSCRIBEPARAGRAPH
    Vous n'avez pas &ecirc;t&ecirc; inscrit &agrave; aucune liste, mais si vous souhaitez vous abonner &agrave; cette liste s'il vous pla�t, visitez le site:<br />
    <a href="[subscribe_url]">[subscribe_url]</a>
    SUBSCRIBEPARAGRAPH;

$LANGUAGE_PACK['page_forward_html_message_suffix'] = '';

$LANGUAGE_PACK['page_unsubscribe_title'] = 'Confirmation de d&eacute;sinscription &agrave; notre liste de diffusion';
$LANGUAGE_PACK['page_unsubscribe_message_sentence'] = 'Veuillez choisir la liste ou les listes de diffusion dont vous souhaitez vous d&eacute;sinscrire:';
$LANGUAGE_PACK['page_unsubscribe_list_groups'] = '[email] de [groupname].';	// Requires [email] and [groupname] variable in sentence.
$LANGUAGE_PACK['page_unsubscribe_cancel_button'] = 'Annulation';
$LANGUAGE_PACK['page_unsubscribe_submit_button'] = 'Confirmation';

$LANGUAGE_PACK['page_help_title'] = 'Aide concernant notre liste de diffusion';
$LANGUAGE_PACK['page_help_message_sentence'] = "Bienvenus &agrave; la rubrique d'aide de notre liste de diffusion. Ce dossier d'aide essayera de r&eacute;pondre aux questions que vous pouvez avoir, en tant qu'abonn&eacute;, au sujet de cette liste de diffusion. Si vous avez une question dont vous ne trouvez pas la r&eacute;ponse dans la rubrique d'aide, merci de contacter un administrateur &agrave; [abuse_address].";	// Requires [abuse_address] variable in sentence.
$LANGUAGE_PACK['page_help_subtitle'] = 'Questions g&eacute;n&eacute;rales:';
$LANGUAGE_PACK['page_help_question_1'] = 'Comment ai-je souscrit &agrave; cette liste de diffusion?';
$LANGUAGE_PACK['page_help_answer_1_optin'] = "Notre application de liste de diffusion demande aux abonn&eacute;s une confirmation avant qu'ils soient inscrits &agrave; nos listes de diffusion. Ceci signifie que vous ou quelqu'un utilisant votre adresse email a demand&eacute; d'&ecirc;tre ajout&eacute; &agrave; notre liste de diffusion, notre syst&egrave;me a envoy&eacute; un email de demande de confirmation. Si vous n'avez pas confirm&eacute; votre abonnement vous-m&ecirc;me, il est possible que notre administrateur l'ait fait manuellement. Les d&eacute;tails sur cette transaction peuvent &ecirc;tre obtenus sur demande &agrave; l'administrateur: [abuse_address].";	// Requires [abuse_address] variable in sentence.
$LANGUAGE_PACK['page_help_answer_1_no_optin'] = "Notre liste de diffusion n'exige pas des abonn&eacute;s de double confirmation avant d'y &ecirc;tre inscrit. Ceci signifie que vous ou quelqu'un employant votre adresse email l'a inscrite dans notre syst&egrave;me et vous n'avez pas &eacute;t&eacute; sollicit&eacute; de confirmer votre abonnement. Les d&eacute;tails sur cette transaction sont disponibles sur demande en exp&eacute;diant un mail &agrave; l'administrateur: [abuse_address].";	// Requires [abuse_address] variable in sentence.
$LANGUAGE_PACK['page_help_question_2'] = 'Comment puis-je me d&eacute;sinscrire de cette liste de diffusion?';
$LANGUAGE_PACK['page_help_answer_2_optout'] = "Si vous voudriez vous enlever d'un ou plusieurs de nos listes de diffusion, vous &ecirc;tes libre pour faire ainsi en remplissant le formulaire suivant. Une fois que vous avez &eacute;crit votre adresse email et avez choisi que liste ou listes de diffusion que vous souhaitez &ecirc;tre enlev&eacute;s de, vous serez requis de confirmer votre demande de confirmation en suivant l'hyperlien dans un email que vous recevrez.";
$LANGUAGE_PACK['page_help_answer_2_no_optout'] = "Si vous voulez vous d&eacute;sinscrire d'un ou plusieurs de nos listes de diffusion, vous &ecirc;tes libre de le faire en remplissant le formulaire suivant. Une fois que vous avez tap&eacute; votre adresse email et choisi la ou les listes de diffusion dont vous souhaitez vous d&eacute;sinscrire, vous serez imm&eacute;diatement effac&eacute; de notre syst&egrave;me.";
$LANGUAGE_PACK['page_help_question_3'] = "Quel est cet email que j'ai re�u?";
$LANGUAGE_PACK['page_help_answer_3'] = "Cette rubrique d'aide ne peut pas d&eacute;terminer la teneur du message que vous avez re�u; cependant, si vous &ecirc;tes en haut de cette page il est probable que le message que vous avez re�u ait &eacute;t&eacute; envoy&eacute; en utilisant notre logiciel de gestion de liste de diffusion. Si vous croyez vous avez re�u un message par erreur, contactez svp un administrateur &agrave; [abuse_address] pour l'informez de votre situation.";	// Requires [abuse_address] variable in sentence.
$LANGUAGE_PACK['page_help_question_4'] = 'Comment puis-je mettre mon profil &agrave; jour?';
$LANGUAGE_PACK['page_help_answer_4'] = 'Vous pouvez mettre votre pofil &agrave; jour en visitant: <a href="[URL]">Mise &agrave; jour de mon profil</a> page.'; // Requires [URL] variable.

$LANGUAGE_PACK['page_archive_closed_title'] = 'Les archives de liste de diffusion sont ferm&eacute;es';
$LANGUAGE_PACK['page_archive_closed_message_sentence'] = "Nos archives de liste de diffusion sont actuellement ferm&eacute;es au public. Si vous avez besoin d'un exemplaire d'un pr&eacute;c&eacute;dent envoi ou avez besoin d'aide, merci de contacter un administrateur &agrave;: [abuse_address].";	// Requires [abuse_address] variable in sentence.
$LANGUAGE_PACK['page_archive_opened_title'] = 'Archives publiques de liste de diffusion';
$LANGUAGE_PACK['page_archive_opened_message_sentence'] = "Bienvenus &agrave; l'archive de notre liste de diffusion. Vous pouvez voir ici  l'archivage des pr&eacute;c&eacute;dentes lettres. Vous pouvez aussi syndiquer notre fil RSS: [rssfeed_url]."; // Requires [rssfeed_url] in sentence.
$LANGUAGE_PACK['page_archive_view_title'] = 'Archive publique des lettres de diffusion - Message';
$LANGUAGE_PACK['page_archive_error_html_title'] = 'Erreur dans le contenu du message HTML';
$LANGUAGE_PACK['page_archive_error_no_message'] = "Le message demand&eacute; n'a pas pu &ecirc;tre trouv&eacute; dans notre liste de diffusion. Merci de retourner aux archives.";
$LANGUAGE_PACK['page_archive_error_no_messages'] = "Il n'y a actuellement aucun message &agrave; consulter dans les archives; merci d'essayer un peu plus tard.";
$LANGUAGE_PACK['page_archive_view_from'] = 'De:';
$LANGUAGE_PACK['page_archive_view_subject'] = 'Sujet:';
$LANGUAGE_PACK['page_archive_view_date'] = 'Date:';
$LANGUAGE_PACK['page_archive_view_to'] = '�:';
$LANGUAGE_PACK['page_archive_view_attachments'] = 'Attachements:';
$LANGUAGE_PACK['page_archive_view_missing_attachment'] = "L'attachement n'est pas disponible";
$LANGUAGE_PACK['page_archive_view_message_from'] = 'Message De';
$LANGUAGE_PACK['page_archive_view_message_subject'] = 'Sujet';
$LANGUAGE_PACK['page_archive_view_message_sent'] = "Date d'envoi";
$LANGUAGE_PACK['page_archive_rss_title'] = 'Newsletter RSS Feed';
$LANGUAGE_PACK['page_archive_rss_description'] = 'Bienvenus sur la version RSS de notre archivage de lettres de diffusion. Vous pouvez consuter ici la ccollection de lettres envoy&eacute;es par le pass&eacute;.';
$LANGUAGE_PACK['page_archive_rss_link'] = ''; // You can optionally set this to the web-address of your website.
$LANGUAGE_PACK['page_archive_pagination'] = 'Pages';

$LANGUAGE_PACK['page_profile_closed_title'] = 'La mise &agrave; jour des profils est ferm&eacute;e.';
$LANGUAGE_PACK['page_profile_closed_message_sentence'] = "Cette section est ferm&eacute;e pour l'instant. Vous avez besoin d'aide? SVP contactez l'administrateur du site: [abuse_address].";	// Requires [abuse_address] variable in sentence.
$LANGUAGE_PACK['page_profile_opened_title'] = 'Mise &agrave; jour de profil.';
$LANGUAGE_PACK['page_profile_instructions'] = 'Merci de garder vos donn&eacute;es &agrave; jour, pour ce faire, merci de renseigner votre adresse email ci-dessous. Le syst&egrave;me vous fera parvenir un email de mise &agrave; jour.';
$LANGUAGE_PACK['page_profile_submit_button'] = 'Continuer';
$LANGUAGE_PACK['page_profile_update_button'] = 'Mise &agrave; jour';
$LANGUAGE_PACK['page_profile_close_button'] = 'Fermer';
$LANGUAGE_PACK['page_profile_cancel_button'] = 'Annuler';
$LANGUAGE_PACK['page_profile_email_address'] = 'Adesse email:';
$LANGUAGE_PACK['page_profile_step1_complete'] = 'Pour prot&eacute;ger votre vie priv&eacute;e, nous vous prions de v&eacute;rifier votre adresse email. Vous allez recevoir un email de confirmation en bref. Merci de suivre le lien pr&eacute;sent dans cet email pour proc&eacute;der.';
$LANGUAGE_PACK['page_profile_step2_instructions'] = 'Pour mettre votre profil &agrave; jour merci de compl&eacute;ter ce formulaire.';
$LANGUAGE_PACK['page_profile_step2_complete'] = 'Vos informations ont &eacute;t&eacute; mises &agrave; jour, nous vous en remerciont.';
$LANGUAGE_PACK['update_profile_confirmation_subject'] = 'Instructions de mises &agrave; jour';
$LANGUAGE_PACK['update_profile_confirmation_message'] = <<<UPDATEPROFILE
    Bonjour [name],
    merci pour cette requ&ecirc;te de mise &agrave; jour:
    [url]

    Si vous n'avez pas pos&eacute; de requ&ecirc;te, merci d'ignorer cet email et de nous signaler tout abus: [abuse_address].

    Sinc&egrave;rement,
    [from]
    UPDATEPROFILE;

$LANGUAGE_PACK['unsubscribe_message'] = <<<UNSUBSCRIBEMSG
    -------------------------------------------------------------------
    Ce email a &eacute;t&eacute; envoy&eacute; [email] parce que vous &ecirc;tes inscrit &agrave;  au moins une de nos listes de diffusion. Vous pouvez vous d&eacute;sinscrire &agrave; tout moement:
    [unsubscribeurl]
    UNSUBSCRIBEMSG;

$LANGUAGE_PACK['subscribe_confirmation_subject'] = "Notification d'abonnement &agrave; la liste de diffusion";
$LANGUAGE_PACK['subscribe_confirmation_message'] = <<<SUBSCRIBEEMAIL
    Bonjour [name]
    Quelqu'un (vous-m&ecirc;me ou l'administrateur) a demand&eacute; que votre adresse email soit inscrite &agrave; une ou plusieurs de nos listes de diffusion.

    Ce email est envoy&eacute; pour confirmer que vous souhaitez bien &ecirc;tre inscrit sur la liste. Si vous voulez confirmer votre abonnement, merci de suivre le lien ci-dessous:
    [url]

    Si vous ne voulez &ecirc;tre inscrit sur aucune liste de diffusion, merci d'ignorer cet email; ne suivez pas le lien ci-contre. Si les demandes persistent, vous pouvez rapporter ces abus &agrave;: [abuse_address].

    Sinc&egrave;rement,
    [from]
    SUBSCRIBEEMAIL;

$LANGUAGE_PACK['unsubscribe_confirmation_subject'] = 'Notification de changement de liste de diffusion';
$LANGUAGE_PACK['unsubscribe_confirmation_message'] = <<<UNSUBSCRIBEEMAIL
    Quelqu'un (vous-m&ecirc;me ou l'administrateur) a demand&eacute; que votre adresse email soit enlev&eacute;e d'une ou plusieurs de nos listes de diffusion.

    Ce email pour confirmer que vous souhaitez bien &ecirc;tre retir&eacute; de notre syst&egrave;me. Si vous voulez confirmer, merci de suivre le lien ci-dessous:
    [url]

    Si vous ne demandez pas &agrave; &ecirc;tre enlev&eacute; de nos listes de diffusion, merci d'ignorer cet email; ne suivez pas le lien ci-dessus. Si les demandes persistent, vous pouvez notifier ces abus &agrave;: [abuse_address].

    Sinc&egrave;rement,
    [from]
    UNSUBSCRIBEEMAIL;

$LANGUAGE_PACK['subscribe_notification_subject'] = '[ListMessenger Notification] Nouvel abonn&eacute;';
$LANGUAGE_PACK['subscribe_notification_message'] = <<<SUBSCRIBENOTICEEMAIL
    Cet email pour vous informer qu'un nouvel abonn&eacute; a rejoint une ou plusieurs de vos listes de diffusion de ListMessenger.

    Informations sur le nouvel abonn&eacute;:
    Nom:\t[firstname] [lastname]
    Adresse email:\t[email_address]
    Inscrit &agrave;:
    [group_ids]

    -------------------------------------------------------------------
    Vous recevez cet avis parce que l'inscription de nouvel abonn&eacute; fonctionne dans le panneau de commande de ListMessenger. Si vous souhaitez neutraliser les avis, merci de le renseigner dans ListMessenger, panneau de commande >> pr&eacute;f&eacute;rences d'utilisateur et placez l'avis de nouvel abonn&eacute; sur handicap&eacute;.
    SUBSCRIBENOTICEEMAIL;

$LANGUAGE_PACK['unsubscribe_notification_subject'] = '[ListMessenger Notification] Abonn&eacute; d&eacute;sinscrit';
$LANGUAGE_PACK['unsubscribe_notification_message'] = <<<UNSUBSCRIBENOTICEEMAIL
    C'est un email pour vous informer que que la personne suivante s'est d&eacute;sinscrit d'une ou plusieurs de vos listes de diffusion de ListMessenger.

    Informations sur l'abonn&eacute;:
    Nom:\t[firstname] [lastname]
    Adresse email:\t[email_address]
    Desinscrit:
    [group_ids]

    -------------------------------------------------------------------
    Vous recevez cet avis parce que la d&eacute;sinscription est autoris&eacute;e dans le panneau de commande de ListMessenger. Si vous souhaitez neutraliser les avis, merci de simplement le notifier dans ListMessenger, panneau de commande >> pr&eacute;f&eacute;rences d'utilisateur et placez l'avis sur handicap&eacute;.
    UNSUBSCRIBENOTICEEMAIL;
