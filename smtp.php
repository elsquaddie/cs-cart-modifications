<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

$config = file_get_contents(__DIR__ . '/config.php');
preg_match('/define\(\'PRODUCT_VERSION\', \'(.*)\'\);/m', $config, $matches);
$version = $matches[1];
$version_array = explode('.', $version);

if ($version_array[0] == 4 && $version_array[1] < 12) { //prior to 4.12.x
    include (__DIR__ . '/app/lib/vendor/phpmailer/phpmailer/class.phpmailer.php'); //default libraries in CS-Cart. Place script inside root CS-Cart directory.
    include (__DIR__ . '/app/lib/vendor/phpmailer/phpmailer/class.smtp.php');
    $mailer = new PHPMailer();
} elseif ($version_array[0] == 4 && $version_array[1] >= 12) { //4.12.1 and newer
    include (__DIR__ . '/app/lib/vendor/phpmailer/phpmailer/src/PHPMailer.php');
    include (__DIR__ . '/app/lib/vendor/phpmailer/phpmailer/src/SMTP.php');
    include (__DIR__ . '/app/lib/vendor/phpmailer/phpmailer/src/Exception.php');
    $mailer = new PHPMailer\PHPMailer\PHPMailer();
}

$mailer->IsSMTP();
$mailer->SMTPAuth = true;
$mailer->SMTPSecure = 'tls'; // comment this line if your mail server does not use ssl encryption
$mailer->Host = 'email-smtp.us-west-2.amazonaws.com:587'; // mail server host
$mailer->Port = 587; // mail server port 
$mailer->Timeout = 10;
$mailer->SMTPDebug = 3; // SMTP class debug output mode. Options: `0` No output; `1` Commands; `2` Data and commands; `3` As 2 plus connection status; `4` Low-level data output
$mailer->Debugoutput = 'html';
$mailer->Username = 'AKIAV5QLPTQ3P2HW6EGX'; // email account
$mailer->Password = 'BPFA3R2+W8SqbvQjx6rxKs1XlIxN+4NiDxxQaMlBvrBL'; // email server password
$mailer->SetFrom('brad@mail.netting.dev'); // sent from field
$mailer->FromName = 'From';
$mailer->AddAddress('dlotochkov@cs-cart.com'); // sent to
$mailer->Subject = 'Test message';
$mailer->Body = '<H3>This is a test message!</H3>';
$mailer->IsHTML (true);

if ($mailer->Send()) {
    if (!empty($mailer->ErrorInfo)) {
	echo "</br>";
	echo "-----------------";
	echo "</br>";
	echo "Error: $mailer->ErrorInfo";
	echo "</br>";
	echo "-----------------";
	echo "</br>";
    } else {
	echo "</br>";
	echo "-----------------";
	echo "</br>";
	echo "Successfully sent";
	echo "</br>";
	echo "-----------------";
	echo "</br>";
    }
} elseif (!empty($mailer->ErrorInfo)) {
	echo "</br>";
	echo "-----------------";
	echo "</br>";
	echo "Error: $mailer->ErrorInfo";
	echo "</br>";
	echo "-----------------";
	echo "</br>";
}
// var_dump($mailer);
