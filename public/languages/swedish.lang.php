<?php
/*
<language name="Swedish" version="2.2.0">
    <translator_name>Micael Weckman</translator_name>
    <translator_email>wanchman@mac.com</translator_email>
    <translator_url>https://listmessenger.com/index.php/languages</translator_url>
    <updated>2008-09-30</updated>
    <notes></notes>
</language>
*/
$LANGUAGE_PACK = [];

$LANGUAGE_PACK['default_page_title'] = 'ListMessenger Mailing List Management System';
$LANGUAGE_PACK['default_page_message'] = 'Besök vår webbplats för att prenumerera på eller avbeställa e-postutskick.';
$LANGUAGE_PACK['error_default_title'] = 'Ett fel uppstod vid din förfrågan';
$LANGUAGE_PACK['error_invalid_action'] = 'Ogiltig förfrågan. Försäkra dig om att du kommit hit genom ett prenumerationsformulär ifrån vår webbplats. För ytterligare hjälp kontakta webbansvarig.';

$LANGUAGE_PACK['error_subscribe_no_groups'] = 'Du måste välja minst ett e-postutskick att prenumerera på. För ytterligare hjälp kontakta webbansvarig.';
$LANGUAGE_PACK['error_subscribe_group_not_found'] = 'Ett e-postutskick som du försökte att prenumerera på finns inte längre i det här systemet. För ytterligare hjälp kontakta webbansvarig.';
$LANGUAGE_PACK['error_subscribe_email_exists'] = 'E-postadressen du angett finns redan i e-postutskicket/en som du valt att prenumerera på. För ytterligare hjälp kontakta webbansvarig.';
$LANGUAGE_PACK['error_subscribe_no_email'] = 'Var vänlig ange en e-postadress som du vill prenumerera på e-postutskicket med.';
$LANGUAGE_PACK['error_subscribe_invalid_email'] = 'E-postadressen du angivit kan inte verifieras som en giltig e-postadress.';
$LANGUAGE_PACK['error_subscribe_banned_email'] = 'En e-postadress som du angivit är för närvarande ej tillåten att prenumerera på det här utskicket.';
$LANGUAGE_PACK['error_subscribe_banned_ip'] = 'Domännamnet till en e-postadress som du angivit är ej tillåten att prenumerera på det här utskicket.';
$LANGUAGE_PACK['error_subscribe_invalid_domain'] = 'Domännamnet till en e-postadress som du angivit verkar inte vara korrekt.';
$LANGUAGE_PACK['error_subscribe_required_cfield'] = '&quot;[cfield_name]&quot; är ett obligatoriskt fält, vänligen skriv in rätt information.';	// Requires [cfield_name] variable in sentence.
$LANGUAGE_PACK['error_subscribe_failed_optin'] = 'Vi kunde tyvärr inte skicka en verifikation på e-postutskick. Vänligen kontakta webbansvarig om problemet.';
$LANGUAGE_PACK['error_subscribe_failed'] = 'Vi kunde tyvärr inte lägga till din e-postadress i vårt utskick. Vänligen kontakta webbansvarig om problemet.';
$LANGUAGE_PACK['success_subscribe_optin_title'] = 'Aktiveringslänk skickad.';
$LANGUAGE_PACK['success_subscribe_optin_message'] = 'Tack för din anmälan. Du kommer få en aktiveringslänk skickad till dig inom kort. Aktivera din prenumeration genom att klicka på länken som kommer med e-postmeddelandet.';
$LANGUAGE_PACK['success_subscribe_title'] = 'Prenumerationen på e-postutskicket lyckades';
$LANGUAGE_PACK['success_subscribe_message'] = 'Tack för din anmälan. Din e-postadress har lagts till vår e-postlista och du kommer att få alla kommande utskick till adressen du angivit.';

$LANGUAGE_PACK['error_unsubscribe_no_groups'] = 'Du måste välja minst ett e-postutskick att avbeställa. För ytterligare hjälp kontakta webbansvarig.';
$LANGUAGE_PACK['error_unsubscribe_group_not_found'] = 'Ett e-postutskick som du försökte avbeställa finns inte längre i det här systemet. För ytterligare hjälp kontakta webbansvarig.';
$LANGUAGE_PACK['error_unsubscribe_email_not_found'] = 'E-postadressen du angivit finns inte i vår databas. För ytterligare hjälp kontakta webbansvarig.';
$LANGUAGE_PACK['error_unsubscribe_email_not_exists'] = 'E-postadressen du angivit finns inte i e-postutskicket som du valt att avbeställa. För ytterligare hjälp kontakta webbansvarig.';
$LANGUAGE_PACK['error_unsubscribe_no_email'] = 'Ange din e-postadress så vi kan ta bort din prenumeration ifrån vårt utskickssystem.';
$LANGUAGE_PACK['error_unsubscribe_invalid_email'] = 'E-postadressen du angivit kan inte verifieras som en giltig e-postadress.';
$LANGUAGE_PACK['error_unsubscribe_failed_optout'] = 'På grund utav ett problem kunde vi tyvärr inte skicka dig ett meddelande med en avbeställningslänk. Vänligen kontakta webbansvarig och informera om att du har problem med att avregistrera dig.';
$LANGUAGE_PACK['error_update_profile'] = 'På grund utav ett problem kunde vi tyvärr inte skicka dig en verifikationslänk för uppdatering av din profil. Vänligen kontakta webbansvarig och informera om att du har problem med att uppdatera din profil.';
$LANGUAGE_PACK['success_unsubscribe_optout_title'] = 'Avbeställningslänk skickad.';
$LANGUAGE_PACK['success_unsubscribe_optout_message'] = 'Det är tråkigt att du inte längre vill vara med i vår e-postlista. För att avsluta avbeställningen, klicka på länken i avbeställningsmeddelandet som vi skickar till din e-postadress.';
$LANGUAGE_PACK['success_unsubscribe_title'] = 'Avbeställningen av e-postutskick lyckades';
$LANGUAGE_PACK['success_unsubscribe_message'] = 'Det är tråkigt att du inte längre vill vara med i vår e-postlista. Din e-postadress har tagits bort ifrån valda utskick. Vill du åter prenumerera på våra utskick kan du göra det ifrån vår webbplats.';

$LANGUAGE_PACK['error_expired_code'] = 'Den här bekräftelsekoden har gått ut efter 7 dagar. Beställ en ny kod för att uppdatera din profil.';
$LANGUAGE_PACK['error_confirm_invalid_request'] = 'Vi kunde inte hitta giltig information i din förfrågan. Om du klickade på en aktiveringslänk ifrån ett e-postmeddelande som du fått kan du prova att kopiera länken och klistra in den i adressfältet på din webbläsare.';
$LANGUAGE_PACK['error_confirm_completed'] = 'Det verkar som om du redan har bekräftat. Allt är klart!';
$LANGUAGE_PACK['error_confirm_unable_request'] = 'Tyvärr kan vi inte ta emot din förfrågan just nu. Vänligen kontakta webbansvarig och informera om problemet.';
$LANGUAGE_PACK['error_confirm_unable_find_info'] = 'Tyvärr kan vi inte hitta något utskick för din e-postadress i vår databas. Vänligen kontakta webbansvarig och informera om problemet.';
$LANGUAGE_PACK['page_confirm_title'] = 'Bekräftelse krävs';

$LANGUAGE_PACK['page_confirm_message_sentence'] = 'Vänligen kontrollera följande innan du klickar vidare på fortsätt-knappen.';
$LANGUAGE_PACK['page_confirm_firstname'] = 'Förnamn';
$LANGUAGE_PACK['page_confirm_lastname'] = 'Efternamn';
$LANGUAGE_PACK['page_confirm_email_address'] = 'E-postadress';
$LANGUAGE_PACK['page_confirm_group_info'] = 'Gruppinformation';
$LANGUAGE_PACK['page_confirm_cancel_button'] = 'Avbryt';
$LANGUAGE_PACK['page_confirm_submit_button'] = 'Fortsätt';

$LANGUAGE_PACK['page_captcha_invalid'] = 'Säkerhetskoden du angivit är ej korrekt. Vänligen ange texten som syns i säkerhetsbilden igen.';
$LANGUAGE_PACK['page_captcha_title'] = 'CAPTCHA Säkerhetsbild';
$LANGUAGE_PACK['page_captcha_message_sentence'] = 'För att undvika att automatiserade bots kommer åt vårt e-postssystem måste du ange texten som du ser i bilden nedan.';
$LANGUAGE_PACK['page_captcha_label'] = 'Säkerhetskod';

$LANGUAGE_PACK['page_forward_title'] = 'Vidarebefordra meddelandet till en vän';
$LANGUAGE_PACK['page_forward_closed_title'] = 'Vidarebefordra meddelande till en vän är inte tillgängligt';
$LANGUAGE_PACK['page_forward_closed_message_sentence'] = 'Vidarebefordra meddelande till en vän-funktionen är för närvarande inte tillgänglig. För mer hjälp kontakta webbansvarig på [abuse_address].';	// Requires [abuse_address] variable in sentence.
$LANGUAGE_PACK['page_forward_error_no_message'] = 'Vi kunde inte hitta meddelandet du försökte skicka till en vän.';
$LANGUAGE_PACK['page_forward_error_private'] = 'Meddelandet som du försöker skicka vidare var bara skickat till privata e-postlistor och var ej menat att skickas vidare till vänner.';
$LANGUAGE_PACK['page_forward_message_sentence'] = 'För att kunna skicka det här meddelandet till vänner måste du skriva in deras kontaktinformation och eventuellt också ett personligt meddelande i formuläret nedan.';
$LANGUAGE_PACK['page_forward_from_header'] = 'Din information';
$LANGUAGE_PACK['page_forward_from_name'] = 'Ditt namn';
$LANGUAGE_PACK['page_forward_from_email'] = 'Din e-postadress';
$LANGUAGE_PACK['page_forward_friend_header'] = 'Din väns information';
$LANGUAGE_PACK['page_forward_friend_name'] = 'Vännens namn';
$LANGUAGE_PACK['page_forward_friend_email'] = 'Vännens e-postadress';
$LANGUAGE_PACK['page_forward_optional_message'] = 'Ej obligatoriskt meddelande';
$LANGUAGE_PACK['page_forward_cancel_button'] = 'Avbryt';
$LANGUAGE_PACK['page_forward_submit_button'] = 'Skicka';
$LANGUAGE_PACK['page_forward_error_from_name'] = 'Vänligen ange ditt namn i namnfältet';
$LANGUAGE_PACK['page_forward_error_from_email'] = 'Vänligen ange din e-postadress.';
$LANGUAGE_PACK['page_forward_error_friend_name'] = 'Vänligen ange din väns namn.';
$LANGUAGE_PACK['page_forward_error_friend_email'] = 'Vänligen ange din väns e-postadress.';
$LANGUAGE_PACK['page_forward_error_failed_send'] = 'Vi kunde tyvärr inte skicka det här meddelandet till din vän, vi ber om ursäkt för besväret.';
$LANGUAGE_PACK['page_forward_successful_send'] = 'Det här meddelandet har skickats till [email_address].';
$LANGUAGE_PACK['page_forward_subject_prefix'] = '[FWD: ';
$LANGUAGE_PACK['page_forward_subject_suffix'] = ']';

$LANGUAGE_PACK['page_forward_text_message_prefix'] = <<<TEXTPREFIX
    Hej [name]!

    [from_name] tror att du kan vara intresserad av det här e-postmeddelandet.
    [optional_message]
    [subscribe_paragraph]
    TEXTPREFIX;

$LANGUAGE_PACK['page_forward_text_subscribe_paragraph'] = <<<SUBSCRIBEPARAGRAPH
    Du har inte blivit lagd till någon e-postlista. Om du skulle vilja läggas till den här e-postlistan vänligen besök:
    [subscribe_url]
    SUBSCRIBEPARAGRAPH;

$LANGUAGE_PACK['page_forward_text_message_suffix'] = '';

$LANGUAGE_PACK['page_forward_html_message_prefix'] = <<<HTMLPREFIX
    Hej <strong>[name]</strong>!
    <br /><br />
    [from_name] tänkte att du kan vara intresserad av det här e-postmeddelandet.<br />
    [optional_message]
    [subscribe_paragraph]
    HTMLPREFIX;

$LANGUAGE_PACK['page_forward_html_subscribe_paragraph'] = <<<SUBSCRIBEPARAGRAPH
    Du har inte blivit lagd till någon e-postlista. Om du skulle vilja läggas till den här e-postlistan vänligen besök:<br />
    <a href="[subscribe_url]">[subscribe_url]</a>
    SUBSCRIBEPARAGRAPH;

$LANGUAGE_PACK['page_forward_html_message_suffix'] = '';

$LANGUAGE_PACK['page_unsubscribe_title'] = 'Bekräftelse på avbeställning av e-postutskick';
$LANGUAGE_PACK['page_unsubscribe_message_sentence'] = 'Vänligen välj e-postlistan som du vill avbeställa.';
$LANGUAGE_PACK['page_unsubscribe_list_groups'] = '[email] ifrån [groupname].';	// Requires [email] and [groupname] variable in sentence.
$LANGUAGE_PACK['page_unsubscribe_cancel_button'] = 'Avbryt';
$LANGUAGE_PACK['page_unsubscribe_submit_button'] = 'Avbeställ';

$LANGUAGE_PACK['page_help_title'] = 'Hjälpinformation för e-postutskick';
$LANGUAGE_PACK['page_help_message_sentence'] = 'Välkommen till hjälpfilen för e-postlistan. Den här hjälpfilen är till för att försöka besvara de mest grundläggande frågorna för dig som prenumerant. Om du har en fråga som inte besvaras av den här hjälpfilen vänligen kontakta en webbansvarig på [abuse_address].';	// Requires [abuse_address] variable in sentence.
$LANGUAGE_PACK['page_help_subtitle'] = 'Vanliga frågor';
$LANGUAGE_PACK['page_help_question_1'] = 'Hur blev jag prenumerant av detta e-postutskick?';
$LANGUAGE_PACK['page_help_answer_1_optin'] = 'Vårt e-postprogram kräver att prenumeranter aktiverar sin prenumeration innan den kommer igång. Det betyder att du eller någon som använder din adress bett om att prenumerera på vårt e-postutskick och en aktiveringslänk skickades till dig som sedan aktiverades genom den bifogade länken. Om du inte bekräftade prenumerationen genom att klicka på aktiveringslänken är det möjligt att ansvarig för e-postutskick har lagt till din adress manuellt. Information om detta kan erhållas på begäran genom att kontakta en webbansvarig på [abuse_address].';	// Requires [abuse_address] variable in sentence.
$LANGUAGE_PACK['page_help_answer_1_no_optin'] = 'Vårt e-postprogram kräver för närvarande inte att prenumeranter aktiverar sin prenumeration innan den kommer igång. Det betyder att du eller någon som använder din e-postadress har angett din adress i vårt system och du var inte tvungen att aktivera prenumerationen. Information om detta kan erhållas på begäran genom att kontakta en webbansvarig på [abuse_address].';	// Requires [abuse_address] variable in sentence.
$LANGUAGE_PACK['page_help_question_2'] = 'Hur tar jag bort mig ifrån den här e-postlistan?';
$LANGUAGE_PACK['page_help_answer_2_optout'] = 'Om du vill bli borttagen ifrån den här en eller flera e-postlistor kan du göra det genom att fylla i följande formulär. När du har skrivit in din e-postadress och vilka listor du vill avbeställa kommer du behöva bekräfta din avbeställning genom att klicka på avbeställningslänken som skickas till din e-postadress.';
$LANGUAGE_PACK['page_help_answer_2_no_optout'] = 'Om du vill bli borttagen ifrån den här eller flera e-postlistor kan du göra det genom att fylla i följande formulär. När du har skrivit in din e-postadress och vilka listor du vill avbeställa kommer du att omedelbart bli borttagen ifrån vårt system.';
$LANGUAGE_PACK['page_help_question_3'] = 'Vad är det här e-postmeddelandet som jag fått?';
$LANGUAGE_PACK['page_help_answer_3'] = 'Den här hjälpfilen kan inte avgöra innehållet av meddelandet som du fått. Det är dock troligt att du kommit till den här sidan genom att vårt e-postutskicksprogram skickat meddelandet. Om du tror att du har fått det här meddelandet av misstag, vänligen kontakta webbansvarig på [abuse_address] och informera om situationen.';	// Requires [abuse_address] variable in sentence.
$LANGUAGE_PACK['page_help_question_4'] = 'Hur uppdaterar jag mina personuppgifter för den här e-postlistan?';
$LANGUAGE_PACK['page_help_answer_4'] = 'Du kan uppdatera din personliga profil genom att besöka <a href="[URL]">Uppdatera användarprofil</a>-sidan.'; // Requires [URL] variable.

$LANGUAGE_PACK['page_archive_closed_title'] = 'E-postlistans arkiv är stängt';
$LANGUAGE_PACK['page_archive_closed_message_sentence'] = 'E-postlistans arkiv är för närvarande stängd för allmänheten. Om du vill ha ett tidigare utskick eller behöver hjälp kan du kontakta en webbansvarig på [abuse_address].';	// Requires [abuse_address] variable in sentence.
$LANGUAGE_PACK['page_archive_opened_title'] = 'Publikt arkiv för e-postutskick';
$LANGUAGE_PACK['page_archive_opened_message_sentence'] = 'Välkommen till vårt publika arkiv för e-postutskick. Här kan du se våra tidigare utskick. Du kan även prenumerera på vår [rssfeed_url].'; // Requires [rssfeed_url] in sentence.
$LANGUAGE_PACK['page_archive_view_title'] = 'Publikt arkiv för e-postutskick - Meddelande-visning';
$LANGUAGE_PACK['page_archive_error_html_title'] = 'Fel vid visning av HTML-meddelande';
$LANGUAGE_PACK['page_archive_error_no_message'] = 'Det begärda e-postutskicket kunde inte hittas i vår e-postlista. Vänligen gå tillbaka till arkivet.';
$LANGUAGE_PACK['page_archive_error_no_messages'] = 'Det finns för närvarande inga e-postutskick att se i arkivet. Vänligen försök senare.';
$LANGUAGE_PACK['page_archive_view_from'] = 'Ifrån';
$LANGUAGE_PACK['page_archive_view_subject'] = 'Ämne';
$LANGUAGE_PACK['page_archive_view_date'] = 'Datum';
$LANGUAGE_PACK['page_archive_view_to'] = 'Till';
$LANGUAGE_PACK['page_archive_view_attachments'] = 'Billagor';
$LANGUAGE_PACK['page_archive_view_missing_attachment'] = 'Den här billagan är inte längre tillgänglig.';
$LANGUAGE_PACK['page_archive_view_message_from'] = 'Meddelande ifrån';
$LANGUAGE_PACK['page_archive_view_message_subject'] = 'Meddelande ämne';
$LANGUAGE_PACK['page_archive_view_message_sent'] = 'Datum skickat';
$LANGUAGE_PACK['page_archive_rss_title'] = 'E-postutskick RSS feed';
$LANGUAGE_PACK['page_archive_rss_description'] = 'Välkommen till RSS versionen av vårt arkiv av e-postutskick. Här kan du se våra tidigare utskick.';
$LANGUAGE_PACK['page_archive_rss_link'] = ''; // You can optionally set this to the web-address of your website.
$LANGUAGE_PACK['page_archive_pagination'] = 'Sidor';

$LANGUAGE_PACK['page_profile_closed_title'] = 'Uppdatering av profiler för prenumeranter är stängd';
$LANGUAGE_PACK['page_profile_closed_message_sentence'] = 'Vår avdelning för uppdatering av prenumeranters profiler är för närvarande stängd. Om du behöver hjälp vänligen kontakta en webbansvarig på [abuse_address].';	// Requires [abuse_address] variable in sentence.
$LANGUAGE_PACK['page_profile_opened_title'] = 'Uppdatera prenumerantprofil';
$LANGUAGE_PACK['page_profile_instructions'] = 'Tack för att du håller din prenumerantinformation uppdaterad. För att fortsätta uppdatera din profil ange din e-postadress nedan. Systemet kommer att skicka ett meddelande till din e-postadress med en länk som du ska klicka på för att göra ändringar av ditt konto.';
$LANGUAGE_PACK['page_profile_submit_button'] = 'Fortsätt';
$LANGUAGE_PACK['page_profile_update_button'] = 'Uppdatera';
$LANGUAGE_PACK['page_profile_close_button'] = 'Stäng';
$LANGUAGE_PACK['page_profile_cancel_button'] = 'Avbryt';
$LANGUAGE_PACK['page_profile_email_address'] = 'E-postadress';
$LANGUAGE_PACK['page_profile_step1_complete'] = 'För att skydda din identitet kräver vi att du verifierar att du är innehaveren av denna e-postadress. Du kommer att få ett meddelande med en aktiveringslänk för uppdatering av din profil skickat till din adress som du ska klicka på.';
$LANGUAGE_PACK['page_profile_step2_instructions'] = 'För att fortsätta uppdatera din profil, gå igenom formuläret nedan och gör nödvändiga ändringar.';
$LANGUAGE_PACK['page_profile_step2_complete'] = 'Din prenumereringsinformation har uppdaterats. Tack för att du håller din profil uppdaterad.';

$LANGUAGE_PACK['update_profile_confirmation_subject'] = 'Instruktioner för att uppdatera prenumereringsinformation';
$LANGUAGE_PACK['update_profile_confirmation_message'] = <<<UPDATEPROFILE
    Hej [name]!
    Tack för din förfrågan om att uppdatera din prenumereringsinformation.

    Klicka på länken nedan för att gå igenom och uppdatera din profil:
    [url]

    Vänligen ignorera detta e-postmeddelande och klicka inte på länken om du inte skickade en förfrågan om att uppdatera din profil. Om du fortsätter att få liknande meddelanden kan du kontakta webbansvarig på [abuse_address].

    Vänliga hälsningar,
    [from]
    UPDATEPROFILE;

$LANGUAGE_PACK['unsubscribe_message'] = <<<UNSUBSCRIBEMSG
    -------------------------------------------------------------------
    Det här e-postmeddelandet är skickat till [email] för att du prenumererar på minst ett av våra e-postutskick. Om du vill avbeställa prenumerationen och vill tas bort ifrån vår e-postlista kan du göra det genom att besöka:[unsubscribeurl]
    UNSUBSCRIBEMSG;

$LANGUAGE_PACK['subscribe_confirmation_subject'] = 'Aktivering av e-postutskick';
$LANGUAGE_PACK['subscribe_confirmation_message'] = <<<SUBSCRIBEEMAIL
    Hej [name]!
    Någon (antingen du själv eller webbansvarig) har bett om att din e-postadress ska läggas till ett eller flera av våra e-postutskick.

    Det här meddelandet skickas för att verifiera att du önskar prenumerera på vårt e-postutskick. Om det stämmer måste du klicka aktiveringslänken nedan:
    [url]

    Om du inte önskar prenumerera på något av våra e-postutskick är det bara att ignorera detta meddelande och inte klicka på länken ovan. Om du får ytterligare liknande meddelanden kan du kontakta webbansvarig på [abuse_address].

    Vänliga hälsningar,
    [from]
    SUBSCRIBEEMAIL;

$LANGUAGE_PACK['unsubscribe_confirmation_subject'] = 'Avbeställning av e-postutskick';
$LANGUAGE_PACK['unsubscribe_confirmation_message'] = <<<UNSUBSCRIBEEMAIL
    Någon (antingen du själv eller webbansvarig) har bett om att din e-postadress ska tas bort ifrån en eller flera av våra e-postlistor.

    Det här e-postmeddelandet skickas för att verifiera att du önskar bli borttagen ifrån vårt system. Om det stämmer måste du klicka på avbeställningslänken nedan:
    [url]

    Om du inte önskar bli borttagen ifrån något av våra e-postutskick är det bara att ignorera det här meddelandet och inte klicka på länken ovan. Om du får ytterligare liknande meddelanden kan du kontakta webbansvarig på [abuse_address].

    Vänliga hälsningar,
    [from]
    UNSUBSCRIBEEMAIL;

$LANGUAGE_PACK['subscribe_notification_subject'] = '[ListMessenger Notice] Ny prenumerant';
$LANGUAGE_PACK['subscribe_notification_message'] = <<<SUBSCRIBENOTICEEMAIL
    Detta är ett meddelande för att informera dig om att en ny prenumerant har anslutit sig till ett eller flera av dina e-postutskick.

    Kort information:
    Namn:\t[firstname] [lastname]
    E-postadress:\t[email_address]
    Prenumererar på:
    [group_ids]

    -------------------------------------------------------------------
    Du får det här meddelandet därför att New Subscriber Notification är aktiverad i ListMessenger Control Panel. Om du vill avaktivera den här funktionen, logga in i ListMessenger, klicka på Control Panel, End-User Preferences och sätt New Subscriber Notification till Disabled. 
    SUBSCRIBENOTICEEMAIL;

$LANGUAGE_PACK['unsubscribe_notification_subject'] = '[ListMessenger Notice] Avregistrerad prenumerant';
$LANGUAGE_PACK['unsubscribe_notification_message'] = <<<UNSUBSCRIBENOTICEEMAIL
    Det här är ett meddelande för att informera dig om att följande person har avregistrerat sig ifrån en eller flera av dina e-postlistor.

    Kort information:
    Namn:\t[firstname] [lastname]
    E-postadress:\t[email_address]
    Avregistrerade sig ifrån:
    [group_ids]

    -------------------------------------------------------------------
    Du får det här meddelandet därför att Unsubscribe Notification är aktiverad i ListMessenger Control Panel. Om du vill avaktivera den här funktionen, logga in i ListMessenger, klicka på Control Panel, End-User Preferences och sätt Unsubscribe Notification till Disabled.
    UNSUBSCRIBENOTICEEMAIL;
