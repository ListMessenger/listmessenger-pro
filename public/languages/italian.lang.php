<?php
/*
<language name="Italian" version="2.2.0">
    <translator_name>Unknown</translator_name>
    <translator_email></translator_email>
    <translator_url>https://listmessenger.com/index.php/languages</translator_url>
    <updated>2005-10-13</updated>
    <notes></notes>
</language>
*/
$LANGUAGE_PACK = [];

$LANGUAGE_PACK['default_page_title'] = 'ListMessenger Mailing List Management System';
$LANGUAGE_PACK['default_page_message'] = 'Si prega di visitare il nostro sito web principale per registrarsi o per essere cancellato da una o da pi� delle nostre mailing lists (liste di indirizzi e-mail).';
$LANGUAGE_PACK['error_default_title'] = 'Errore nella Sua Richiesta ';
$LANGUAGE_PACK['error_invalid_action'] = 'L�operazione richiesta non � valida, si prega di accertarsi di accedere correttamente a questo sistema attraverso un modulo di registrazione visionabile sul nostro sito web. Qualora necessitasse di ulteriore assistenza, si prega di contattare l�amministratore del sito web.';

$LANGUAGE_PACK['error_subscribe_no_groups'] = 'Lei deve selezionare almeno una mailing list (lista di indirizzi e-mail) a cui registrarsi. Qualora necessitasse di ulteriore assistenza, si prega di contattare l�amministratore del sito web.';
$LANGUAGE_PACK['error_subscribe_group_not_found'] = 'Una mailing list (lista di indirizzi e-mail) a cui Lei provava di registrarsi non esiste pi� in questo sistema. Qualora necessitasse di ulteriore assistenza, si prega di contattare l�amministratore del sito web.';
$LANGUAGE_PACK['error_subscribe_email_exists'] = 'L�indirizzo e-mail da Lei fornito esiste gi� nella(e) mailing list(s) (lista/e di indirizzi e-mail) a cui Lei ha scelto di registrarsi. Qualora necessitasse di ulteriore assistenza, si prega di contattare l�amministratore del sito web.';
$LANGUAGE_PACK['error_subscribe_no_email'] = 'Si prega di scrivere l�indirizzo e-mail che Lei desidera registrare nella nostra mailing list (lista di indirizzi e-mail).';
$LANGUAGE_PACK['error_subscribe_invalid_email'] = 'L�indirizzo e-mail da Lei fornito non � riconosciuto come un indirizzo e-mail valido.';
$LANGUAGE_PACK['error_subscribe_banned_email'] = 'In questo momento non � consentito registrarsi al sistema con l�indirizzo e-mail da Lei fornito.';
$LANGUAGE_PACK['error_subscribe_banned_ip'] = 'Non � consentito iscriversi a questo sistema con la denominazione dell�indirizzo e-mail da Lei fornito.';
$LANGUAGE_PACK['error_subscribe_invalid_domain'] = 'la denominazione dell�indirizzo e-mail da Lei fornito non sembra valido.';
$LANGUAGE_PACK['error_subscribe_required_cfield'] = '[cfield_name] questo � un campo obligatorio. Si prega di tornare indietro ed inserire questa informazione.'; // Requires [cfield_name] variable in sentence.
$LANGUAGE_PACK['error_subscribe_failed_optin'] = 'Purtroppo non abbiamo potuto inviarLe un�e-mail di conferma di registrazione alla mailing list (lista di indirizzi e-mail). Si prega di contattare il nostro amministratore del sito web e di informarlo di questo problema.';
$LANGUAGE_PACK['error_subscribe_failed'] = 'Purtroppo non abbiamo potuto registrare il Suo indirizzo e-mail nella nostra mailing list (lista di indirizzi e-mail). Si prega di contattare il nostro amministratore del sito web e di informarlo di questo problema.';
$LANGUAGE_PACK['success_subscribe_optin_title'] = 'Opt-in messaggio di conferma di registrazione alla lista, inviato ';
$LANGUAGE_PACK['success_subscribe_optin_message'] = 'La ringraziamo per l�interesse dimostrato nei confronti della nostra mailing list (lista di indirizzi e-mail). Ricever� un breve messaggio di conferma  mailing list (lista di indirizzi e-mail), si prega di riconfermare la Sua registrazione seguendo il link di conferma incluso in quell�e-mail.';
$LANGUAGE_PACK['success_subscribe_title'] = 'Conferma di registrazione mailing list (lista di indirizzi e-mail)';
$LANGUAGE_PACK['success_subscribe_message'] = 'La ringraziamo per l�interesse dimostrato nei confronti della nostra mailing list (lista di indirizzi e-mail). Il Suo indirizzo e-mail � stato aggiunto con successo alla nostra mailing list (lista di indirizzi e-mail) e su qesto ricever� i prossimi messaggi.';

$LANGUAGE_PACK['error_unsubscribe_no_groups'] = 'Lei deve selezionare almeno una mailing list (lista di indirizzi e-mail) a cui registrarsi. Qualora necessitasse di ulteriore assistenza, si prega di contattare l�amministratore del sito web.';
$LANGUAGE_PACK['error_unsubscribe_group_not_found'] = 'Una mailing list (lista di indirizzi e-mail) a cui Lei stava tentando di registrarsi non esiste pi� nel nostro sistema. Qualora necessitasse di ulteriore assistenza, si prega di contattare l�amministratore del sito web.';
$LANGUAGE_PACK['error_unsubscribe_email_not_found'] = 'L�indirizzo e-mail da Lei fornito non esiste nel nostro database. Qualora necessitasse di ulteriore assistenza, si prega di contattare l�amministratore del sito web.';
$LANGUAGE_PACK['error_unsubscribe_email_not_exists'] = 'L�indirizzo e-mail da Lei fornito non esiste nella mailing list (lista di indirizzi e-mail)  dalla quale Lei ha richiesto di essere cancellato. Qualora necessitasse di ulteriore assistenza, si prega di contattare l�amministratore del sito web.';
$LANGUAGE_PACK['error_unsubscribe_no_email'] = 'Si prega di comunicarci il Suo indirizzo e-mail da poterla cancellare dal nostro sistema di mailing list (lista di indirizzi e-mail).';
$LANGUAGE_PACK['error_unsubscribe_invalid_email'] = 'L�indirizzo e-mail da Lei fornito non � riconosciuto come un indirizzo e-mail valido.';
$LANGUAGE_PACK['error_unsubscribe_failed_optout'] = 'Non possiamo purtroppo inviarLe un�e-mail di conferma di cancellazione a causa di un problema che stiamo affrontando. Si prega di contattare l�amministratore del sito web e di comunicargli che ha incontrato difficolt� durante questi tentativi.';
$LANGUAGE_PACK['error_update_profile'] = 'We were unfortunately unable to send you an update profile confirmation notice due to a problem that we are currently experiencing. Please contact the website administrator and let the know you are having difficulty while trying to update your profile.';
$LANGUAGE_PACK['success_unsubscribe_optout_title'] = 'Messaggio di conferma di cancellazione dalla lista inviato Opt-out';
$LANGUAGE_PACK['success_unsubscribe_optout_message'] = 'Siamo spiacenti di constatare che Lei sta lasciando la nostra mailing list (lista di indirizzi e-mail). Per completare il processo di cancellazione, si prega di seguire il link presente nell�e-mail di conferma cancellazione che abbiamo spedito al Suo indirizzo.';
$LANGUAGE_PACK['success_unsubscribe_title'] = 'Cancellazione dalla mailing list (lista di indirizzi e-mail) avvenuta con successo';
$LANGUAGE_PACK['success_unsubscribe_message'] = 'Siamo spiacenti di constatare che Lei sta lasciando la nostra mailing list (lista di indirizzi e-mail). Il suo indirizzo e-mail � stato cancellato con successo dalla mailing list (lista di indirizzi e-mail) selezionata, ma se desidera registrarsi di nuovo lo pu� fare in ogni momento visitando il nostro sito web.';

$LANGUAGE_PACK['error_expired_code'] = 'This confirmation code has expired after 7 days. To update your profile, please request a new confirmation code.';
$LANGUAGE_PACK['error_confirm_invalid_request'] = 'Non possiamo individuare un�informazione di conferma valida nella Sua richiesta. Se Lei ha cliccato su un link in un�e-mail di conferma che Lei ha ricevuto, dovrebbe provare a copiare e ad incollare il link.';
$LANGUAGE_PACK['error_confirm_completed'] = 'Appare che Lei abbia gi� confermato questa richiesta. Non serve nessun�altra operazione, grazie.';
$LANGUAGE_PACK['error_confirm_unable_request'] = 'Ci scusiamo per l�inconveniente; tuttavia, non possiamo processare la Sua richiesta in questo momento. Si prega di contattare il nostro amministratore del sito web e di informarlo di questo problema.';
$LANGUAGE_PACK['error_confirm_unable_find_info'] = 'Ci scusiamo per l�inconveniente; tuttavia, non potevamo trovare alcuna informazione valida nella mailing list (lista di indirizzi e-mail) per il Suo indirizzo e-mail nel nostro database. Si prega di contattare il nostro amministratore del sito web e di informarlo di questo problema.';

$LANGUAGE_PACK['page_confirm_title'] = 'Conferma di Registrazione alla mailing list (lista di indirizzi e-mail)';
$LANGUAGE_PACK['page_confirm_message_sentence'] = 'Si prega di confermare le seguenti informazioni prima di  cliccare sul pulsante di conferma.';
$LANGUAGE_PACK['page_confirm_firstname'] = 'Nome:';
$LANGUAGE_PACK['page_confirm_lastname'] = 'Cognome:';
$LANGUAGE_PACK['page_confirm_email_address'] = 'Indirizzo e-mail:';
$LANGUAGE_PACK['page_confirm_group_info'] = 'Informazione di gruppo:';
$LANGUAGE_PACK['page_confirm_cancel_button'] = 'Cancella';
$LANGUAGE_PACK['page_confirm_submit_button'] = 'Conferma';

$LANGUAGE_PACK['page_captcha_invalid'] = 'The security code you entered was not correct, please go back and re-enter the text that appears in the security image.';
$LANGUAGE_PACK['page_captcha_title'] = 'CAPTCHA Security Image';
$LANGUAGE_PACK['page_captcha_message_sentence'] = 'To help prevent automated bots from accessing our mailing list system we require that you enter the text that you see in the image below.';
$LANGUAGE_PACK['page_captcha_label'] = 'Security Code';

$LANGUAGE_PACK['page_forward_title'] = 'Forward Message to a Friend';
$LANGUAGE_PACK['page_forward_closed_title'] = 'Forward Message to a Friend Not Available';
$LANGUAGE_PACK['page_forward_closed_message_sentence'] = 'The forward to a friend feature is currently closed. If you require assistance, please contact an administrator at [abuse_address].';	// Requires [abuse_address] variable in sentence.
$LANGUAGE_PACK['page_forward_error_no_message'] = 'We were unable to find the message that you were attempting to forward to a friend.';
$LANGUAGE_PACK['page_forward_error_private'] = 'The message you are attempting to forward was sent only to private lists, and was not intended to be forwarded to friends.';
$LANGUAGE_PACK['page_forward_message_sentence'] = 'In order to send this message to your friends, please enter their contact information and optionally a personalised message using the form below.';
$LANGUAGE_PACK['page_forward_from_header'] = 'Your Information';
$LANGUAGE_PACK['page_forward_from_name'] = 'Your Name';
$LANGUAGE_PACK['page_forward_from_email'] = 'Your E-Mail Address';
$LANGUAGE_PACK['page_forward_friend_header'] = 'Your Friends Information';
$LANGUAGE_PACK['page_forward_friend_name'] = "Friend's Name";
$LANGUAGE_PACK['page_forward_friend_email'] = "Friend's E-Mail Address";
$LANGUAGE_PACK['page_forward_optional_message'] = 'Optional Message';
$LANGUAGE_PACK['page_forward_cancel_button'] = 'Cancel';
$LANGUAGE_PACK['page_forward_submit_button'] = 'Submit';
$LANGUAGE_PACK['page_forward_error_from_name'] = 'Please provide your name in the Your Name field.';
$LANGUAGE_PACK['page_forward_error_from_email'] = 'Please provide your e-mail address in the Your E-Mail Address field.';
$LANGUAGE_PACK['page_forward_error_friend_name'] = "Please provide your friend's name in the Friend's Name field.";
$LANGUAGE_PACK['page_forward_error_friend_email'] = "Please provide your friend's e-mail address in the Friend's E-Mail Address field.";
$LANGUAGE_PACK['page_forward_error_failed_send'] = 'We were unable to send this message to your friend at this time, we apologize for any inconvenience this may cause.';
$LANGUAGE_PACK['page_forward_successful_send'] = 'This message has been successfully sent to [email_address].';
$LANGUAGE_PACK['page_forward_subject_prefix'] = '[FWD: ';
$LANGUAGE_PACK['page_forward_subject_suffix'] = ']';

$LANGUAGE_PACK['page_forward_text_message_prefix'] = <<<TEXTPREFIX
    Hello [name],

    [from_name] thought that you may be interested in the following e-mail message.
    [optional_message]
    [subscribe_paragraph]
    TEXTPREFIX;

$LANGUAGE_PACK['page_forward_text_subscribe_paragraph'] = <<<SUBSCRIBEPARAGRAPH
    You have not been added to any mailing list, but if you would like to subscribe to this list please visit:
    [subscribe_url]
    SUBSCRIBEPARAGRAPH;

$LANGUAGE_PACK['page_forward_text_message_suffix'] = '';

$LANGUAGE_PACK['page_forward_html_message_prefix'] = <<<HTMLPREFIX
    Hello <strong>[name]</strong>,
    <br /><br />
    [from_name] thought that you may be interested in the following e-mail message.<br />
    [optional_message]
    [subscribe_paragraph]
    HTMLPREFIX;

$LANGUAGE_PACK['page_forward_html_subscribe_paragraph'] = <<<SUBSCRIBEPARAGRAPH
    You have not been added to any mailing list, but if you would like to subscribe to this list please visit:<br />
    <a href="[subscribe_url]">[subscribe_url]</a>
    SUBSCRIBEPARAGRAPH;

$LANGUAGE_PACK['page_forward_html_message_suffix'] = '';

$LANGUAGE_PACK['page_unsubscribe_title'] = 'Conferma di Cancellazione dalla mailing list (lista di indirizzi e-mail)';
$LANGUAGE_PACK['page_unsubscribe_message_sentence'] = ' Si prega di selezionare la o le mailing list(s) (lista/e di indirizzi e-mail) da cui Lei desidera essere cancellato:';
$LANGUAGE_PACK['page_unsubscribe_list_groups'] = '[email] da [groupname].'; // Requires [email] and [groupname] variable in sentence.
$LANGUAGE_PACK['page_unsubscribe_cancel_button'] = 'Cancella';
$LANGUAGE_PACK['page_unsubscribe_submit_button'] = 'Rimuovere';

$LANGUAGE_PACK['page_help_title'] = 'Informazioni di aiuto alla mailing list (lista di indirizzi e-mail)';
$LANGUAGE_PACK['page_help_message_sentence'] = 'Benvenuto nell�area di aiuto della mailing list (lista di indirizzi e-mail). Questa area di aiuto ha lo scopo di rispondere ad alcune domande basilari che Lei in quanto sottoscrittore potrebbe avere riguardo a questa mailing list (lista di indirizzi e-mail). Se Lei ha una domanda che non trova risposta in quest�area di aiuto, La preghiamo di contattare l�amministratore del sito web [abuse_address].'; // Requires [abuse_address] variable in sentence.
$LANGUAGE_PACK['page_help_subtitle'] = 'Domande comuni:';
$LANGUAGE_PACK['page_help_question_1'] = ' Come mi registro a questa mailing list (lista di indirizzi e-mail)?';
$LANGUAGE_PACK['page_help_answer_1_optin'] = 'La nostra mailing list (lista di indirizzi e-mail) attualmente richiede ai sottoscrittori di registrarsi 2 volte prima di considerarsi registrato a qualsiasi delle nostre mailing lists (liste di indirizzi e-mail). Ci� significa che Lei o qualcun�altro che utilizza il Suo indirizzo e-mail ha richiesto di essere aggiunto alla nostra mailing list (lista di indirizzi e-mail), allora il nostro sistema ha inviato un�e-mail di conferma che � stata confermata. Se Lei non conferma da solo la conferma di registrazione, � possible che il nostro amministratore di sito abbia aggiunto manualmente il Suo indirizzo e-mail al nostro sistema. I dettagli di questa transazione sono disponibili su richiesta, contattando per e-mail il nostro amministratore [abuse_address].'; // Requires [abuse_address] variable in sentence.
$LANGUAGE_PACK['page_help_answer_1_no_optin'] = 'L�iscrizione alla nostra mailing list (lista di indirizzi e-mail) attualmente non richiede ai sottoscrittori di registrarsi 2 volte per essere inseriti in qualsiasi delle nostre mailing lists (liste di indirizzi e-mail). Ci� significa che Lei o qualcun�altro che utilizza il Suo indirizzo e-mail ha avuto accesso al Suo indirizzo e-mail nel nostro sistema e non era richiesto di confermare la Sua registrazione. I dettagli di questa transazione sono disponibili su richiesta, contattando per e-mail il nostro amministratore all�indirizzo [abuse_address].'; // Requires [abuse_address] variable in sentence.
$LANGUAGE_PACK['page_help_question_2'] = 'Come posso cancellarmi da questa mailing list (lista di indirizzi e-mail)?';
$LANGUAGE_PACK['page_help_answer_2_optout'] = 'Se desidera cancellarsi da una o pi� delle nostre mailing lists (liste di indirizzi e-mail), � libero di farlo, completando il modulo seguente. Una volta che Lei ha avuto accesso al Suo indirizzo e-mail e selezionato da quale o quali mailing list(s) (liste di indirizzi e-mail) vuole essere cancellato, Le verr� chiesto di confermare la cancellazione, seguendo il link contenuto in un�e-mail che ricever�.';
$LANGUAGE_PACK['page_help_answer_2_no_optout'] = 'Se desidera cancellarsi da una o pi� delle nostre mailing lists (liste di indirizzi e-mail), � libero di farlo, completando il modulo seguente. Una volta che Lei ha inserito il Suo indirizzo e-mail e selezionato da quale o quali mailing list(s) (liste di indrizzi e-mail) vuole essere cancellato, sar� immediatamente cancellato dal nostro sistema.';
$LANGUAGE_PACK['page_help_question_3'] = 'Cos�� questa e-mail che ho ricevuto?';
$LANGUAGE_PACK['page_help_answer_3'] = 'Quest�area di aiuto della mailing list (lista di indirizzi e-mail) non pu� determinare il contenuto del message che ha ricevuto; tuttavia, se ha terminato questa pagina, allora il messaggio da Lei ricevuto � stato inviato usando il nostro programma di gestione della mailing list (lista di indirizzi e-mail). Se crede di avere ricevuto questo messaggio per errore, si prega di contattare l�amministratore all�indirizzo [abuse_address] e di informarlo della Sua situazione.'; // Requires [abuse_address] variable in sentence.
$LANGUAGE_PACK['page_help_question_4'] = 'How do I update my personal details for this mailing list?';
$LANGUAGE_PACK['page_help_answer_4'] = 'You can update your personal details by visiting the <a href="[URL]">Update User Profile</a> page.'; // Requires [URL] variable.

$LANGUAGE_PACK['page_archive_closed_title'] = 'Archivio della mailing list (lista di indirizzi e-mail) chiusa';
$LANGUAGE_PACK['page_archive_closed_message_sentence'] = 'L�archivio della nostra mailing list (lista di indirizzi e-mail) � attualmente chiuso al pubblico. Se deve richiedere un�e-mail precedente o ha bisogno di assistenza, si prega di contattare l�amministratore [abuse_address].'; // Requires [abuse_address] variable in sentence.
$LANGUAGE_PACK['page_archive_opened_title'] = 'Archivio pubblico della mailing list (lista di indirizzi e-mail)';
$LANGUAGE_PACK['page_archive_opened_message_sentence'] = 'Welcome to our public mailing list archive. Here you can view our collection of e-mail newsletters that have previously been sent to our subscriber base. As a matter of convenience you can also subscribe to our [rssfeed_url].'; // Requires [rssfeed_url] in sentence.
$LANGUAGE_PACK['page_archive_view_title'] = 'Archivio pubblico della mailing list (lista di indirizzi e-mail) - Messaggio visionabile';
$LANGUAGE_PACK['page_archive_error_html_title'] = 'Errore nella visualizzazione del contenuto del messaggio HTML';
$LANGUAGE_PACK['page_archive_error_no_message'] = 'Il messaggio richiesto non pu� essere trovato nella nostra mailing list (lista di indirizzi e-mail). Si prega di tornare all�archivio.';
$LANGUAGE_PACK['page_archive_error_no_messages'] = 'Attualmente non ci sono messaggi da visualizzare nell�archivio; si prega di riprovare pi� tardi.';
$LANGUAGE_PACK['page_archive_view_from'] = 'Da:';
$LANGUAGE_PACK['page_archive_view_subject'] = 'Oggetto:';
$LANGUAGE_PACK['page_archive_view_date'] = 'Data:';
$LANGUAGE_PACK['page_archive_view_to'] = 'A:';
$LANGUAGE_PACK['page_archive_view_attachments'] = 'Allegati:';
$LANGUAGE_PACK['page_archive_view_missing_attachment'] = 'L�Allegato non � pi� disponibile';
$LANGUAGE_PACK['page_archive_view_message_from'] = 'Messaggio da';
$LANGUAGE_PACK['page_archive_view_message_subject'] = 'Oggetto del messaggio';
$LANGUAGE_PACK['page_archive_view_message_sent'] = 'Data di invio';
$LANGUAGE_PACK['page_archive_rss_title'] = 'Newsletter RSS Feed';
$LANGUAGE_PACK['page_archive_rss_description'] = 'Welcome to the RSS version of our mailing list archive. Here you can view our collection of e-mail newsletters that have previously been sent to our subscriber base.';
$LANGUAGE_PACK['page_archive_rss_link'] = ''; // You can optionally set this to the web-address of your website.
$LANGUAGE_PACK['page_archive_pagination'] = 'Pagine';

$LANGUAGE_PACK['page_profile_closed_title'] = 'Subscriber Profile Update Closed';
$LANGUAGE_PACK['page_profile_closed_message_sentence'] = 'Our subscriber profile update section is currently closed. If you require assistance, please contact an administrator at [abuse_address].';	// Requires [abuse_address] variable in sentence.
$LANGUAGE_PACK['page_profile_opened_title'] = 'Update Subscriber Profile';
$LANGUAGE_PACK['page_profile_instructions'] = 'Thank-you for keeping your subscriber information up to date. To proceed with updating your information please enter your e-mail address in the form below. The system will then send you an e-mail containing a customized link that you can follow to make the changes to your account.';
$LANGUAGE_PACK['page_profile_submit_button'] = 'Continue';
$LANGUAGE_PACK['page_profile_update_button'] = 'Update';
$LANGUAGE_PACK['page_profile_close_button'] = 'Close';
$LANGUAGE_PACK['page_profile_cancel_button'] = 'Cancel';
$LANGUAGE_PACK['page_profile_email_address'] = 'E-Mail Address:';
$LANGUAGE_PACK['page_profile_step1_complete'] = 'In order to protect your privacy, we require that you verify that you are the owner of this e-mail address. You will receive an update profile confirmation notice shortly. Please follow the link included in that e-mail to continue.';
$LANGUAGE_PACK['page_profile_step2_instructions'] = 'To proceed with updating your information please review the form below and make any required changes.';
$LANGUAGE_PACK['page_profile_step2_complete'] = 'Your subscriber information had been updated. Thank-you for keeping your subscriber information up to date.';
$LANGUAGE_PACK['update_profile_confirmation_subject'] = 'Instructions for Updating Subscriber Information';
$LANGUAGE_PACK['update_profile_confirmation_message'] = <<<UPDATEPROFILE
    Hello [name],
    Thank you for your recent request to update your subscriber information.

    To review and update your information, please follow the link below:
    [url]

    If you did not submit a request to update your information, please ignore this e-mail and do not follow the above link. If requests persist, you may wish to notify our abuse account at [abuse_address].

    Sincerely,
    [from]
    UPDATEPROFILE;

$LANGUAGE_PACK['unsubscribe_message'] = <<<UNSUBSCRIBEMSG
    -------------------------------------------------------------------
    Questa e-mail stata spedita a [email] perch� � stato registrato a uno o pi� nostre mailing list (lista di indirizzi e-mail). In qualsiasi momento se si vuole cancellare dalla nostra mailing list (lista di indirizzi e-mail) lo pu� fare visitando:
    [unsubscribeurl]
    UNSUBSCRIBEMSG;

$LANGUAGE_PACK['subscribe_confirmation_subject'] = 'Conferma di Registrazione alla mailing list (lista di indirizzi e-mail)';
$LANGUAGE_PACK['subscribe_confirmation_message'] = <<<SUBSCRIBEEMAIL
    Gentile Signore/a [name]
    Qualcuno (lei o l�amministratore della lista) ha richiesto che il suo indirizzo e-mail venga registrato su una o pi� delle nostre mailing list (lista di indirizzi e-mail).

    Questa e-mail le � stata inviata per confermare la sua iscrizione alla nostra lista. Se vuole confermarla segua il seguente link:
    [url]

    Se lei non ha richiesto di essere iscritto a nessuna di queste mailing list (lista di indirizzi e-mail), ignori questa e-mail e non segua il link precedente. Se queste richieste persistono si pu� notificare l�illecito a:
    [abuse_address].

    Distinti saluti,
    [from]
    SUBSCRIBEEMAIL;

$LANGUAGE_PACK['unsubscribe_confirmation_subject'] = 'Conferma di Cancellazione dalla mailing list (lista di indirizzi e-mail)';
$LANGUAGE_PACK['unsubscribe_confirmation_message'] = <<<UNSUBSCRIBEEMAIL
    Qualcuno (lei o l�amministratore della lista) ha richiesto che il suo indirizzo e-mail (lista di indirizzi e-mail) venga cancellato da una o pi� delle nostre mailing list.

    Questa e-mail le � stata inviata per confermare la sua cancellazione al nostro sistema. Se vuole confermarla seguire il link seguente:
    [url]

    Se lei non ha richiesto di essere cancellato a nessuna di queste mailing list (lista di indirizzi e-mail), ignori questa e-mail e non segua il link precedente. Se queste richieste persistono si pu� notificare l�illecito a [abuse_address].

    Distinti saluti,
    [from]
    UNSUBSCRIBEEMAIL;

$LANGUAGE_PACK['subscribe_notification_subject'] = '[ListMessenger Notice] Nuova sottoscrizione';
$LANGUAGE_PACK['subscribe_notification_message'] = <<<SUBSCRIBENOTICEEMAIL
    Questa e-mail � per informarla che c�� una nuova sottoscrizione ad una o pi� della sua ListMessenger mailing lists (lista di indirizzi e-mail).

    Dati della nuova sottoscrizione:
    Full Name:\t[firstname] [lastname]
    Indirizzo e-mail:\t[email_address]
    Sottoscrizione a:
    [group_ids]

    -------------------------------------------------------------------
    Lei sta ricevendo questo messaggio perch� l�avviso �sottoscrizione nuova� � stato attivato nel pannello di controllo di ListMessenger. Se vuole disattivare gli avvisi, entri nella ListMessenger, cliccando Control Panel, End-User Preferences e cambia l�avviso �sottoscrizione nuova� a disattivare.
    SUBSCRIBENOTICEEMAIL;

$LANGUAGE_PACK['unsubscribe_notification_subject'] = '[ListMessenger Notice] Utente rimosso';
$LANGUAGE_PACK['unsubscribe_notification_message'] = <<<UNSUBSCRIBENOTICEEMAIL
    Questa � una e-mail che informa che il seguente utente si � cancellato da una o pi� liste.

    Dati utente rimossi:
    Full Name:\t[firstname] [lastname]
    Indirizzo e-mail:\t[email_address]
    Rimosso da:
    [group_ids]

    -------------------------------------------------------------------
    Lei sta ricevendo questo messaggio perch� l�avviso �utente cancellato� � stata attivato nel pannello di controllo di ListMessenger. Se vuole disattivare gli avvisi, entri nella ListMessenger, cliccando Control Panel, End-User Preferences e cambi avviso �utente cancellato� a disattivare.
    UNSUBSCRIBENOTICEEMAIL;
