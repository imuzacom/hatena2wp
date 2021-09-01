<?php
session_start();
$token = isset($_POST["token"]) ? $_POST["token"] : "";
$flg = false;
if ($token == ''){
	$token = uniqid('', true);
	$_SESSION['token'] = $token;
}else{
	$session_token = isset($_SESSION["token"]) ? $_SESSION["token"] : "";
	unset($_SESSION["token"]);
	if($token == $session_token) {
		$flg = true;
	}else{
		$token = uniqid('', true);
		$_SESSION['token'] = $token;
	}
}
?>

<!DOCTYPE html>
<html>
<head>
	<title>はてなブログからWordPressへ</title>
</head>
<body>
	<p>はてなブログから WordPress 移行のためのサポートツールです</p>
	<ul>
		<li>フォトライフ画像を wp-contents/uploads 以下の yyyy/mm ディレクトリにコピーします</li>
		<li>エクスポートファイルを WordPress 用に整形します
			<ul>
				<li>画像の URL 変更、alt, title 属性削除</li>
				<li>見出しの変更 h3->h2, h4->h3, h5->h4</li>
				<li>キーワードリンク削除</li>
				<li>Youtube リンクを https に変更</li>
			</ul>
		</li>
	</ul>
	<hr>
	<p>はてなブログのエクスポートファイルを指定してください</p>
	<p>画像ファイルのコピーは進行状態を 100ファイルずつ表示します<br>ただし、サーバーの設定によっては逐次表示されずに終了後一括表示される場合があります</p>
	<form enctype="multipart/form-data" action="hatena2wp.php" method="POST">
		<input type="hidden" name="token" value="<?php echo $token;?>">
		<input type="file" name="uploaded_file"></input><br>
		<input type="submit" value="Upload"></input>
	</form>
<?PHP
if($flg && !empty($_FILES['uploaded_file'])){
	$path = './';
	$path = $path . basename( $_FILES['uploaded_file']['name']);

	if(move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $path)) {
		echo "ファイル " . basename( $_FILES['uploaded_file']['name']) . " がアップロードされました<br><br>";

		$pattern1 = '/(https?:\/\/cdn-ak\.f\.st-hatena.com\/images\/fotolife\/.+?(jpg|gif|png))/';
		$pattern2 = '/^https?:\/\/cdn-ak\.f\.st-hatena.com\/images\/fotolife\/.+?\/.+?\/(\d{4})(\d{2})\d{2}\/(.+)$/';
		$array = array();

		$hatena_text = file_get_contents($path);
		preg_match_all($pattern1, $hatena_text, $matches);
		$img_urls = array_unique($matches[1]);	
		$total = count($img_urls);
		$count = 0;

		echo "画像ファイルは " . $total . " ファイルあります<br>";
		echo "表示は 100ファイル移動ごとに更新します<br><br>";
		ob_flush();
		flush();
	
		foreach($img_urls as $url){
			if($img_data = @file_get_contents($url)){
				preg_match($pattern2, $url, $matches);
				$directory_name = '../wp-content/uploads/' . $matches[1] . '/' . $matches[2];
				$img_file_name = $directory_name . '/' . $matches[3];

				if(!is_dir($directory_name)){
					mkdir($directory_name, 0705, true);
				}
				file_put_contents($img_file_name, $img_data);
				$array[] = $img_file_name;
				$count++;
				if($count % 100 == 0){
					echo $count . " / " . $total . "<br>";
					ob_flush();
					flush();
				}
			}
		}
		echo $total . " / " . $total . "<br><br><hr>";

		$new_file = implode("\n", $array);
		$new_file_name = "images_list.txt";
		file_put_contents($new_file_name, $new_file);
		echo "アップロードしたファイル一覧を " . $new_file_name . " に保存しました<br><br><hr>";
      
		$patterns = array (
			'/https?:\/\/cdn-ak\.f\.st-hatena.com\/images\/fotolife\/.+?\/.+?\/(\d{4})(\d{2})\d{2}\/(.+\.(jpg|gif|png))/', 
			'/alt="f:id:.+?"/', 
			'/title="f:id:.+?"/', 
			'/ figure-image-fotolife mceNonEditable/', 
			'/ class="mceEditable"/', 
			'/<h3 /', 
			'/<\/h3>/', 
			'/<h4 /', 
			'/<\/h4>/', 
			'/<h5 /', 
			'/<\/h5>/', 
			'/<a .*href="http:\/\/d\.hatena\.ne\.jp\/keyword\/.+?".*?>(.+)?<\/a>/', 
			'/<iframe.+?youtube\.com\/embed\/(.+)?\?.+?<\/iframe>/'
			);
		$replace = array (
			'https://ausnichts.shop/wp-content/uploads/$1/$2/$3', 
			'alt=""', 
			'title=""', 
			'', 
			'', 
			'<h2 ', 
			'</h2>', 
			'<h3 ', 
			'</h3>', 
			'<h4 ', 
			'</h4>', 
			'$1', 
			'<iframe src="https://www.youtube.com/embed/$1?enablejsapi=1" width="560" height="315" frameborder="0" allowfullscreen></iframe>'
			);

		$new_file = preg_replace($patterns, $replace, $hatena_text);

		$new_file_name = "../wp-content/mt-export.txt";
		file_put_contents($new_file_name, $new_file);
		echo "WordPress 用に整形したファイルを " . $new_file_name . " に保存しました<br>
			プラグイン「Movable Type・TypePad インポートツール」を使い、「mt-export.txt のインポート」をクリックしてください";

    } else{
        echo "ファイルをアップロードできませんでした";
    }
}
?>
</body>
</html>

