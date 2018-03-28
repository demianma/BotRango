<?php
// BOTRANGO v. 1.3
// Always in doubt of what to have for lunch today?! This is a slash 
// command was created to help you decide what you’ll have for lunch!
//
// It’ll choose a random Subreddits picture and throw it back to you.
//
// Whenever you’re hungry, just go to a private chat or a channel and 
// type /food. You can also make suggestions like /food pizza.

$input = ucfirst($_GET['text']);
$command = $_GET['command'] . " " . $_GET['text'];
$channel_id = $_GET["channel_id"];
$user_name = $_GET["user_name"];
$datahoraf = date('d/m/Y h:m:s', time());
$url = 'http://'.$_SERVER['HTTP_HOST'] . "/rango/";

//subreddit list (you may edit this list)
$fontes = [
"Pizza" => "https://www.reddit.com/r/Pizza",
"Steak" => "https://www.reddit.com/r/steak",
"Hamburguer" => "https://www.reddit.com/r/burgers",
"Food Porn" => "https://www.reddit.com/r/FoodPorn",
"Sushi" => "https://www.reddit.com/r/sushi",
"Sexy Pizza" => "https://www.reddit.com/r/sexypizza",
"Egg" => "https://www.reddit.com/r/PutAnEggOnIt",
"Beer and Pizza" => "https://www.reddit.com/r/beerandpizza",
"Beer" => "https://www.reddit.com/r/beerporn",
"Dessert" => "https://www.reddit.com/r/DessertPorn",
"Pasta" => "https://www.reddit.com/r/pasta",
"Crèpe" => "https://www.reddit.com/r/Crepes",
"Garlic Bread" => "https://www.reddit.com/r/garlicbread/",
"Paleo" => "https://www.reddit.com/r/Paleo/"
];

//Compares the command attributes to the word list above
foreach ($fontes as $chave => $fonte){
	$similar = similar_text($input, $chave, $percent);
	$ranking[$chave] = $percent;
	$urls[$chave] = $fonte;
}

//Chooser the closest match
$chave = array_keys($ranking, max($ranking))[0]; //array key
$url = $urls[$chave];							 //array value
	
//Choose something to say
//100% exact, higher than 50% did you mean, 
//lower than 50% I pick one randomly, 0% random
if (max($ranking) == 100){
	$mensagem = "Good choice! Today is " . 
	array_keys($ranking, max($ranking))[0] . " day!";
}
elseif (max($ranking) > 50){
	$mensagem = $input . "?! Did you mean " . $chave . 
	"? If so, today is " . $chave . " day!";
}
elseif (max($ranking) < 50 && max($ranking) != 0) {
	$chave = array_rand($fontes);
	$url = $fontes[$chave];
	$mensagem = "I don't have anything related to " . $input . 
	"... So I pick! Today is " . $chave . " day!";
}
else //0%
{
	$chave = array_rand($fontes);
	$url = $fontes[$chave];
	$mensagem = "Today is " . $chave . " day!";
	$mensagem .= "\n\n";
	$mensagem .= "You may suggest " . 
				 join(', ', array_slice(array_keys($fontes), 0, -1)) .
			     " and " . end(array_keys($fontes)) .
				 ", that is what I know so far.";
}

//download contents
$json = file_get_contents($url . ".json?limit=100"); //100 posts/page
$post = cataPost($json); 

//check if there is any image and gen JSON. if not, reply with json error msg
$i = 1; //limit while loop to 100 posts 
while ($i < 100 || $post['image_url'] == "" || $post['image_url'] == "self"){
	$post = cataPost($json);
	$i++;
}
devolveJSON("Abrir original", $post['title_link'], $post['author_name'], 
	$post['author_link'], $post['image_url'], $mensagem);
	
logger($datahoraf . " | " . $command . " | " . $user_name . 
	" | " . $channel_id . " | " . $post['title_link']);
	
	
//pick random
function cataPost($j){
	$obj = json_decode($j);
	
	//Choose one of the 100 posts randomly from the 1st page
	$i = rand (0, 99);

	//Traz os dados
	$out['title_link'] = $obj->data->children[$i]->data->url;
	$out['author_name'] = $obj->data->children[$i]->data->title;
	$out['author_link'] = "https://www.reddit.com" . 
						  $obj->data->children[$i]->data->permalink;
	$out['image_url'] = $obj->data->children[$i]->data->thumbnail;
	$out['subreddit_name_prefixed'] = $obj->data->children[$i]->
									  data->subreddit_name_prefixed;
	
	return $out; 
}

//good JSON return
function devolveJSON($t, $tl, $an, $al, $iu, $txt){
	$attachment = array(
		"title" => $t,
		"title_link" => $tl,
		"author_name" => $an,
		"author_link" => $al,
		"image_url" => $iu,
		"ts" => 123456789
	);

	$attachments = array($attachment);

	$final  = array(
		"text" => $txt,
		"response_type" => "in_channel", 
		"attachments" => $attachments
	);

	$myJSON = json_encode($final);
	 
	header("Content-type:application/json");
	echo $myJSON; 
}
//logger
function logger($txt){
    $file = 'log.txt';
    $current = file_get_contents($file);
    $current .= $txt . "\n";
    file_put_contents($file, $current);
}
?>
