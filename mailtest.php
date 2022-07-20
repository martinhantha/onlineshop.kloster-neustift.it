<?php
$to = "test-l3bs5psk0@srv1.mail-tester.com";
$subject = "My subject";
$txt = "Hello world!";
$headers = "From: info@kloster-neustift.com" . "\r\n";

return mail($to,$subject,$txt,$headers);
?>
