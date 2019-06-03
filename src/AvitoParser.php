<?php
require_once 'vendor/autoload.php';


class AvitoParser {
	function  page_parsing($__html, $__base, $__req) {
		$avito_page = get_html_from_avito($__html);
		do {
			$pqdoc_page = phpQuery::newDocument($avito_page[0]);
			$ads = $pqdoc_page->find('.js-catalog-item-enum');
			
			foreach($ads as $ad) {
				$pq_ad = pq($ad);
				$link = ($pq_ad->find('.item-description-title-link')->attr('href')!='')?trim($pq_ad->find('.item-description-title-link')->attr('href'))
						:trim($pq_ad->find('.description-title-link')->attr('href'));
				$name = ($pq_ad->find('.item-description-title-link>span')->text()!='') ? trim($pq_ad->find('.item-description-title-link>span')->text())
						:trim($pq_ad->find('.description-title-link>span')->text());
				$price = trim($pq_ad->find('.price')->attr('content'));
				$description = ($pq_ad->find('.specific-params_block')->text()!='')?trim($pq_ad->find('.specific-params_block')->text())
						:  str_replace("\n"," ",trim($pq_ad->find('span.option')->text()));
				$time_send = trim($pq_ad->find('.js-item-date')->attr('data-absolute-date'));
				$link_to_pic = 'https:'.trim($pq_ad->find('.large-picture-img')[0]->attr('src'));
				$time_req = strftime('%d %B %Y %X');
				$req = $__req;
				$avito_ad = get_html_from_avito( 'https://m.avito.ru'.$link );
				$link ='https://www.avito.ru'.$link;
				$pqdoc_ad = phpQuery::newDocument($avito_ad[0]);
				$phone = $pqdoc_ad->find('a[class="BPWk2"]')->attr('href');
				$address = $pqdoc_ad->find('span[data-marker="delivery/location"]')->text();
				$query = "INSERT INTO AvitoTable (Link,Name,Address,Price,Descr,LinkToPic,Phone,CTime,RTime,Request) VALUES ('$link','$name','$address','$price','$description','$link_to_pic','$phone','$time_send','$time_req','$req')
					  ON DUPLICATE KEY UPDATE Name='$name',Address='$address',Price='$price',Descr='$description',LinkToPic='$link_to_pic',Phone='$phone',CTime='$time_send',RTime='$time_req',Request='$req';";
				mysqli_query($__base, $query);
			}
				
				
			$buf = 'https://www.avito.ru'.$pqdoc_page->find('.js-pagination-next')->attr('href');
			$avito_page = get_html_from_avito($buf);
			
		}while($buf!='https://www.avito.ru');
	}
	
	function parse_avito() {
		$base = mysqli_connect("localhost", "root", '', "avitoparsing");
		$base->set_charset("utf8");
		ini_set('max_execution_time', 900);
		$resText =  '<link rel="stylesheet" type="text/css" href="style.css"><form method="post">
			<select id="category" name="category_id">
			<option value="" selected="selected">Любая категория</option>       <option value="/moskva/transport">Транспорт</option>
			<option value="/moskva/nedvizhimost">Недвижимость</option>          <option value="/moskva/rabota">Работа</option>
			<option value="/moskva/uslugi">Услуги</option>                      <option value="/moskva/lichnye_veschi">Личные вещи</option>
			<option value="/moskva/dlya_doma_i_dachi">Для дома и дачи</option>  <option value="/moskva/bytovaya_elektronika">Бытовая электроника</option>
			<option value="/moskva/hobbi_i_otdyh">Хобби и отдых</option>        <option value="/moskva/zhivotnye">Животные</option>
			<option value="/moskva/dlya_biznesa">Для бизнеса</option>
			</select>
			<input id="obj" name="req" type="text" required="required">
			<input name="offlain" type="radio" >Вывести базу
			<input type="submit" name="butt" value="Парсить">
			</form>';

		session_start();

		//Поготовка переменных
		if(isset($_POST['butt'])) {
			
			$_SESSION['category'] = $_POST['category_id'];
			$_SESSION['req'] = $_POST['req'];
			$_SESSION['radio'] = (isset($_POST['offlain']))?$_POST['offlain']:'';
			unset($_POST['category_id']);
			unset($_POST['req']);
			unset($_POST['butt']);
			header('Location: ./');
			exit();
		}

		//В случае необходимости онлайн парсинга
		if(isset($_SESSION['category']) && isset($_SESSION['radio']) && $_SESSION['radio'] ==''  ) {
			$adress = 'https://www.avito.ru'.$_SESSION['category'];
			unset($_SESSION['category']);
			$adress .= '?q='.$_SESSION['req'];
			page_parsing($adress,$base,strtolower($_SESSION['req']));
			mysqli_close($link);
			unset($_SESSION['req']);
			header('Location: ./');
			exit();
		}

		//При оффлайн просмотре базы
		if(isset($_SESSION['radio']) && $_SESSION['radio']!='') {
			$query = "SELECT * FROM AvitoTable WHERE '".strtolower($_SESSION['req'])."' = Request;";
			$rows = mysqli_query($base, $query);
			if($rows) {
				while ( $row = mysqli_fetch_row($rows)) {
					$resText.= '<div class="Ad"><div><img src="'.$row[5].'"></div>
							<div><h3>'.$row[1].'</h3><h3>Стоимость: '.$row[3].'</h3>'.$row[6].'<br>'.$row[2].'</div>
						   <div>Ссылка на объявление: <a href="'.$row[0].'">'.$row[0].'</a><br><i>'.$row[4].'</i><br> Время размещения объявления: '.$row[7].'<br>Время обновления базы: '.$row[8].'<br>Имя запроса: '.$row[9].'</div></div>';
				}
			}
			unset($_SESSION['radio']);
		}

		print $resText;
	}

	//Парсинг страницы сайта
	function  page_parsing($__html, $__base, $__req) {
		$avito_page = get_html_from_avito($__html);
		do {
			$pqdoc_page = phpQuery::newDocument($avito_page[0]);
			$ads = $pqdoc_page->find('.js-catalog-item-enum');
			
			foreach($ads as $ad) {
				$pq_ad = pq($ad);
				$link = ($pq_ad->find('.item-description-title-link')->attr('href')!='')?trim($pq_ad->find('.item-description-title-link')->attr('href'))
						:trim($pq_ad->find('.description-title-link')->attr('href'));
				$name = ($pq_ad->find('.item-description-title-link>span')->text()!='') ? trim($pq_ad->find('.item-description-title-link>span')->text())
						:trim($pq_ad->find('.description-title-link>span')->text());
				$price = trim($pq_ad->find('.price')->attr('content'));
				$description = ($pq_ad->find('.specific-params_block')->text()!='')?trim($pq_ad->find('.specific-params_block')->text())
						:  str_replace("\n"," ",trim($pq_ad->find('span.option')->text()));
				$time_send = trim($pq_ad->find('.js-item-date')->attr('data-absolute-date'));
				$link_to_pic = 'https:'.trim($pq_ad->find('.large-picture-img')[0]->attr('src'));
				$time_req = strftime('%d %B %Y %X');
				$req = $__req;
				$avito_ad = get_html_from_avito( 'https://m.avito.ru'.$link );
				$link ='https://www.avito.ru'.$link;
				$pqdoc_ad = phpQuery::newDocument($avito_ad[0]);
				$phone = $pqdoc_ad->find('a[class="BPWk2"]')->attr('href');
				$address = $pqdoc_ad->find('span[data-marker="delivery/location"]')->text();
				$query = "INSERT INTO AvitoTable (Link,Name,Address,Price,Descr,LinkToPic,Phone,CTime,RTime,Request) VALUES ('$link','$name','$address','$price','$description','$link_to_pic','$phone','$time_send','$time_req','$req')
					  ON DUPLICATE KEY UPDATE Name='$name',Address='$address',Price='$price',Descr='$description',LinkToPic='$link_to_pic',Phone='$phone',CTime='$time_send',RTime='$time_req',Request='$req';";
				mysqli_query($__base, $query);
			}
				
				
			$buf = 'https://www.avito.ru'.$pqdoc_page->find('.js-pagination-next')->attr('href');
			$avito_page = get_html_from_avito($buf);
			
		}while($buf!='https://www.avito.ru');
	}

	//Функция получения кода страницы
	function get_html_from_avito($__url) {
		$ch = curl_init( $__url );
		
		curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookie.txt');
		curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookie.txt');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_ENCODING, "");
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36');
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
	  
		$content = curl_exec( $ch );
		$err     = curl_errno( $ch );
		$errmsg  = curl_error( $ch );
		$header  = curl_getinfo( $ch );
	  
		curl_close( $ch );
	  
		return [$content, $err, $errmsg, $header];
	}
}
