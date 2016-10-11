<?php

/*

REQUIREMENTS

* A custom slash command on a Slack team
* A web server running PHP5 with cURL enabled

USAGE

* Place this script on a server running PHP5 with cURL.
* Set up a new custom slash command on your Slack team: 
  http://my.slack.com/services/new/slash-commands
* Under "Choose a command", enter whatever you want for 
  the command. /isitup is easy to remember.
* Under "URL", enter the URL for the script on your server.
* Leave "Method" set to "Post".
* Decide whether you want this command to show in the 
  autocomplete list for slash commands.
* If you do, enter a short description and usage hint.

*/


# Grab some of the values from the slash command, create vars for post back to Slack
$command = $_POST['command'];
$token = $_POST['token'];
$channel = $_POST['channel_id'];

# Check the token and make sure the request is from our team 
if($token != 'XJYNcqdbgelpmZNFZxxLXGOV'){ #replace this with the token from your slash command configuration page
  $msg = "The token for the slash command doesn't match. Check your script.";
  die($msg);
  echo $msg;
}

# isitup.org doesn't require you to use API keys, but they do require that any automated script send in a user agent string.
# You can keep this one, or update it to something that makes more sense for you
//$user_agent = "IsitupForSlack/1.0 (https://github.com/mccreath/istiupforslack; mccreath@gmail.com)";
$user_agent = "Dokk1-Menu/1.0 (Martinyde; https://github.com/martinyde/menu-slack-bot)";

# We're just taking the text exactly as it's typed by the user. If it's not a valid domain, isitup.org will respond with a `3`.
# We want to get the JSON version back (you can also get plain text).
$url_to_check = "http://dokk1.mikkelricky.dk/menu.json";

# Set up cURL 
$ch = curl_init($url_to_check);

# Set up options for cURL 
# We want to get the value back from our query 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
# Send in our user agent string
curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

# Make the call and get the response 
$ch_response = curl_exec($ch);
# Close the connection 
curl_close($ch);

# Decode the JSON array sent back by isitup.org
$response_array = json_decode($ch_response,true);

# Build our response 
# Note that we're using the text equivalent for an emoji at the start of each of the responses.
# You can use any emoji that is available to your Slack team, including the custom ones.
if($ch_response === FALSE){
  # isitup.org could not be reached 
  $reply = "Ironically, Dokk1-menu could not be reached.";
}
else {
  $img_key = rand(0, 9);
  $fields = array();
  $daily_img = 'http://dokk1.dk/sites/all/themes/dokk/logo.png';
  $current_time = date('Ymd');
  foreach ($response_array as $key => $value) {
    $timestamp = $value['date'];
    $day = date('D', $timestamp);
    $fields[] = array(
      'title' => $day,
      'value' => $value['name'] . ' ' . $value['details'],
      'short' => false,
    );

    if ($current_time === date('Ymd', strtotime($timestamp))) {
      $daily_img = $value['images'][$img_key];
    }
  }

  slack($fields, $daily_img, $channel);
}

// (string) $message - message to be passed to Slack
// (string) $icon - You can set up custom emoji icons to use with each message
function slack($fields, $daily_img, $channel) {
  $data = "payload=" . json_encode(array(
      'channel' => $channel,
      'text' =>  '*Ugens menu*',
      'icon_emoji'    =>  ':knife_fork_plate:',
      'username' => 'Dokk1 Kantinen',
      'mrkdwn' => true,
      'attachments' => array(array (
        'title' => 'Ugens menu på Dokk1',
        'fallback' => 'Ugens menu',
        'color' => '#1e528a',
        'fields' => $fields,
        'image_url' => $daily_img,
        'footer' => 'Tank op på http://tankop5172.fazer.dk/',
        'ts' => time(),
      )),
    ));

  // You can get your webhook endpoint from your Slack settings
  $ch = curl_init("https://hooks.slack.com/services/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");
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $result = curl_exec($ch);
  curl_close($ch);

  return $result;
}