<?php
//BOTRANGO v. 1.3
// 
//Basicamente ele escolhe uma Subreddit com imagem e devolve para você.
//
//Quando tiver fome, é só ir em um chat privado ou canal e escrever /rango. 
//Você ainda pode fazer sugestões como /rango pizza.


$prefix = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://";
$url = $prefix . $_SERVER['SERVER_NAME'] . substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/'));
$input = ucfirst($_GET['text']);
$command = $_GET['command'] . " " . $_GET['text'];
$channel_id = $_GET["channel_id"];
$user_name = $_GET["user_name"];
$channel_name = $_Get["channel_name"];
$datahoraf = date('d/m/Y h:m:s', time());
$team_domain = $_GET["team_domain"];
$enterprise_name = $_GET["enterprise_name"];


//lista de sites
$fontes = [
"Pizza" => "https://www.reddit.com/r/Pizza",
"Carne" => "https://www.reddit.com/r/steak",
"Hambúrguer" => "https://www.reddit.com/r/burgers",
"Gordice" => "https://www.reddit.com/r/FoodPorn",
"Sushi" => "https://www.reddit.com/r/sushi",
"Pizza sexy" => "https://www.reddit.com/r/sexypizza",
"Ovo" => "https://www.reddit.com/r/PutAnEggOnIt",
"Pizza com cerveja" => "https://www.reddit.com/r/beerandpizza",
"Cerveja" => "https://www.reddit.com/r/beerporn",
"Doce" => "https://www.reddit.com/r/DessertPorn",
"Massa" => "https://www.reddit.com/r/pasta",
"Crepe" => "https://www.reddit.com/r/Crepes",
"Pão de alho" => "https://www.reddit.com/r/garlicbread/",
"Paleo" => "https://www.reddit.com/r/Paleo/"
];

//compara o que o cidadao escreveu com a lista
foreach ($fontes as $chave => $fonte){
	$similar = similar_text($input, $chave, $percent);
	$ranking[$chave] = $percent;
	$urls[$chave] = $fonte;
}

//determina o escolhido (maior % de similaridade)
$chave = array_keys($ranking, max($ranking))[0]; //array key
$url = $urls[$chave];							 //array value
	
//definir uma gracinha pra falar
//100% exato, acima de 50% você quis dizer, 
//abaixo de 50% eu escolho aleatório, 0% aleatório
if (max($ranking) == 100){
	$mensagem = "Boa escolha! Hoje é dia de " . 
	array_keys($ranking, max($ranking))[0] . "!";
}
elseif (max($ranking) > 50){
	$mensagem = $input . "?! Você quis dizer " . $chave . 
	"? Se sim, hoje é dia de " . $chave . "!";
}
elseif (max($ranking) < 50 && max($ranking) != 0) {
	$chave = array_rand($fontes);
	$url = $fontes[$chave];
	$mensagem = "Não tenho nada sobre " . $input . 
	"... Então, eu decido! Hoje é dia de " . $chave . "!";
}
else //0%
{
	$chave = array_rand($fontes);
	$url = $fontes[$chave];
	$mensagem = "Hoje é dia de " . $chave . "!";
	$mensagem .= "\n\n";
	$mensagem .= "Você pode sugerir " . 
				 join(', ', array_slice(array_keys($fontes), 0, -1)) .
			     " e " . end(array_keys($fontes)) .
				 ", que é o que eu conheço até agora.";
}

//faz o download do conteudo
$json = file_get_contents($url . ".json?limit=100"); //100 posts/pgna
$post = cataPost($json); 


//checa se há imagem e gera JSON. Se não, devolve json de erro
$i = 1; //limita while loop 100 posts 
while ($i < 100 || $post['image_url'] == "" || $post['image_url'] == "self"){
	$post = cataPost($json);
	$i++;
}
devolveJSON("Abrir original", $post['title_link'], $post['author_name'], 
	$post['author_link'], $post['image_url'], $mensagem);
	
logger($datahoraf . " | " . $command . " | " . $user_name . 
	" | " . $channel_id . " | " . $post['title_link']);

	
//cata post random
function cataPost($j){
	$obj = json_decode($j);
	
	//Escolher um dos 100 posts aleatoriam. da primeira página
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

//retorno de JSON bom
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
