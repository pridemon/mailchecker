<?php

require_once('vendor/autoload.php');

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

// 4. argument is the directory into which attachments are to be saved:
$mailbox = new PhpImap\Mailbox(getenv('IMAP_MAILBOX'), getenv('IMAP_USERNAME'), getenv('IMAP_PASSWORD'), __DIR__);

$date = date ( "d M Y", time());

// Read all messaged into an array:
$mailsIds = $mailbox->searchMailbox("FROM \"".getenv('MAIL_FROM')."\" SINCE \"$date\"");
/*if(!$mailsIds) {
    die("Mailbox is empty\n");
}*/

$mails = array_reduce($mailsIds, function($carry, $mailId) use ($mailbox) {
    $mail = $mailbox->getMail($mailId);

    if (strtotime($mail->date) > strtotime('-1 hour')) {
        $carry++;
    }

    return $carry;
}, 0);

var_dump($mails);

if ($mails == 0) {
    $message = 'Warning: workers maybe down';
    exec("export DISPLAY=:0; notify-send -u critical 'emailchecker' '$message' ");

    $client = new Maknz\Slack\Client(getenv('SLACK_ENDPOINT'));

    $client->to(getenv('SLACK_CHANNEL'))->attach([
        'fallback' => 'Workers maybe broken',
        'text' => 'Workers maybe broken',
        'color' => 'danger',
    ])->send('New alert from the monitoring system');
}
