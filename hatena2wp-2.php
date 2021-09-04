<?php
session_start();
$token1 = isset($_POST["token1"]) ? $_POST["token1"] : "";
$token2 = isset($_POST["token2"]) ? $_POST["token2"] : "";
$flg = false;
if ($token1 == '' && $token2 == ''){
	$token1 = uniqid('', true);
	$_SESSION['token1'] = $token1;
}elseif($token1 != ''){
	$session_token1 = isset($_SESSION["token1"]) ? $_SESSION["token1"] : "";
	unset($_SESSION["token1"]);
	if($token1 == $session_token1) {
		$flg = true;
		$token1 = '';
		$token2 = uniqid('', true);
		$_SESSION['token2'] = $token2;
	}else{
		$token1 = uniqid('', true);
		$_SESSION['token1'] = $token1;
	}
}elseif($token2 != ''){
	$session_token2 = isset($_SESSION["token2"]) ? $_SESSION["token2"] : "";
	unset($_SESSION["token2"]);
	if($token2 == $session_token2 && isset($_POST['submit'])){
		$flg = true;
	}elseif(isset($_POST['submit'])){
		$token2 = uniqid('', true);
		$_SESSION['token2'] = $token2;
	}else{
		$token2 = '';
		$token1 = uniqid('', true);
		$_SESSION['token1'] = $token1;
	}			
}
?>

<!DOCTYPE html>
<html>
<head>
	<title>はてなブログからWordPressへ</title>
</head>
<body>
	<p>はてなブログから WordPress 移行のためのサポートツール-２です</p>
	<p style="margin-bottom:0;">エクスポートファイルを WordPress 用に整形します</p>
	<ul style="margin-top:0;">
		<li>画像の URL 変更、alt, title 等不要属性削除</li>
		<li>見出しの変更 h3->h2, h4->h3, h5->h4</li>
		<li>キーワードリンク削除</li>
		<li>Youtube リンクを https に変更</li>
	</ul>
	<hr>
	<p>はてなエクスポートファイルの画像 URL をドメイン付きにするかしないか、また運用はドメイン直下かサブディレクトリかを指定してください</p>
	<table border="1" style="border-collapse: collapse">
	<thead>
		<tr>
			<th>指定（例）</th>
			<th>画像 URL</th>
		</tr>
    </thead>
	<tbody>
		<tr>
			<td>https://hogehoge</td>
			<td>https://hogehoge/wp-content/uploads/(yyyy)/(mm)/(ファイル名)</td>
		</tr>
		<tr>
			<td>https://hogehoge/wordpress</td>
			<td>https://hogehoge/wordpress/wp-content/uploads/(yyyy)/(mm)/(ファイル名)</td>
		</tr>
		<tr>
			<td>blankと入力</td>
			<td>/wp-content/uploads/(yyyy)/(mm)/(ファイル名)</td>
		</tr>
		<tr>
			<td>/wordpress</td>
			<td>/wordpress/wp-content/uploads/(yyyy)/(mm)/(ファイル名)</td>
		</tr>
	</tbody>
	</table><br>
	<form action="hatena2wp-2.php" method="POST">
		<input type="hidden" name="token1" value="<?php echo $token1 ?>">
		<input type="text" name="wp_path"><br>
		<input type="submit" value="Submit">
	</form>
<?php
if($flg && isset($_POST['submit'])){
	$wp_path = $_POST['wp_path'];
	$path = file_get_contents('uploadfile.txt');
	$hatena_text = file_get_contents($path);
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
		$wp_path . '/wp-content/uploads/$1/$2/$3', 
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
		プラグイン「Movable Type・TypePad インポートツール」を使い、「mt-export.txt のインポート」をクリックしてください<br><br>";
	
	$image_list = file_get_contents("images_list.txt");
	$new_file = str_replace('..', $wp_path, $image_list);
	$new_file_name = "images_list.txt";
	file_put_contents($new_file_name, $new_file);	
	echo "アップロードしたファイル一覧を " . $new_file_name . " に保存しました";

}elseif($flg && !empty($_POST['wp_path'])){
	$path = $_POST['wp_path'] == 'blank' ? '' : $_POST['wp_path'];
	echo "画像 URL は " . $path . "/wp-content/uploads/(yyyy)/(mm)/(ファイル名) でいいですか？<br>";
	echo "<form action='hatena2wp-2.php' method='POST'>";
	echo '<input type="hidden" name="token2" value="' . $token2 . '">';
	echo "<input type='hidden' name='wp_path' value='" . $path . "'>"; 
	echo "<input type='submit' name='submit' value='YES'> ";
	echo "<input type='submit' value='NO'><br>";
	echo "</form>";
}
?>
</body>
</html>

