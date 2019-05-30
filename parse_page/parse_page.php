<?php

class parse_page {
	
	/**
	 * Парсинг категорий
	 */
	function parse_categories() {
		$url = 'https://www.avito.ru/moskva/';
		$file = get_contents($url);
		$doc = phpQuery::newDocument($file);
		
		//!Массив категорий
		$categoryArr = [];
		foreach ($doc->find('.catalog-counts__section  .js-catalog-counts__link') as $cat) {
			$cat = pq($cat);
			$categoryArr[] = trim($cat->html());
		}

		//!Массив ссылок к этим категориям $linksArr
		$link = $doc->find('.catalog-counts__row');
		$link = pq($link);
		$link = $link->html();
		preg_match_all('#["]/.+?[?]s_trg=10"#uis', $link, $linksArr);
		foreach ($linksArr as &$value) {
			$value = str_replace('"/moskva', '', $value);
			$value = str_replace('?s_trg=10"', '', $value);
		}
		unset($value);
		$linksArr = array_shift($linksArr);

		//!Массив вида: [название категории] => ссылка к ней
		$categoryArr = array_combine($categoryArr, $linksArr);
		return $categoryArr;
	}
	
	
	/**
	 * Парсинг страниц
	 * @param type $__nameOfCategory
	 * @param type $__from
	 * @param type $__to
	 * @return type Возвращает массив, в котором содеражаться ассоциативные массивы с информацией об объявлении
	 */
	function parsePage($__nameOfCategory, $__from, $__to)
	{
		$result = [];
		$url = 'https://www.avito.ru/moskva'.$__nameOfCategory;

		//Проверка на заданный диапазон цен
		$url .= $__to != ''
			? '&pmax='.$__to
			:'';
		$url .= $__from != ''
			? '&pmin='.$__from
			: '';

		//Забирает количество найденных объявлений
		$file = get_contents($url);
		$doc = phpQuery::newDocument($file);
		$find = $doc->find('.layout-internal .l-content .page-title span:eq(1)');
		$find = pq($find)->text();
		$find = str_replace(' ', '', $find);
		$countOfPages = ceil((int)$find / 60) > 100
			? 100
			: ceil((int)$find / 60);

		//Непосредственный парсинг объявлений
		for($numberOfPage = 1; $numberOfPage < $countOfPages + 1; $numberOfPage++) {
			//Присоединяет номер страницы к адресу
			$url .= '&p='.$numberOfPage;

			//Нумерация объявлений на странице
			$i = 0;

			//html-код страницы
			$file = $this->get_contents($url);

			$doc = phpQuery::newDocument($file);

			foreach ( $doc->find('.catalog-content .catalog_table .catalog-list .js-catalog_serp .js-catalog-item-enum') as $ad) {
				$ad = pq($ad);
				$info = $ad->find('.description');
				$link = $info->find('.item_table-header .title a')->attr('href');
				$name = $info->find('.item_table-header h3 a')->text();
				$price = $info->find('.item_table-header .about .price')->text();
				$picture = $ad->find('.item-photo .item-slider .item-slider-list .item-slider-item .item-slider-image img')->attr('src');
				$metro = $info->find('.data p')->text();
				$date = $info->find('.data div')->attr('data-absolute-date');

				//Парсинг мобильного телефона
				$newFile = $this->get_contents('https://m.avito.ru'.$link);
				$newDoc = phpQuery::newDocument($newFile);
				$newDoc = pq($newDoc);
				$phone = $newDoc->find('.cWHK8 ._1avFw ._1BFyF ._3vWKQ a')->attr('href');

				// Был плохой инет, все объявления не мог загрузить, поэтому загружал по 3-5 с каждой страницы
				//Номер объявления на странице
				$i++;
				if ($i > 2)
					break;
				else

				//Формируется массив по каждому объявлению
				$result[] = [
					'ссылка' => 'https://avito.ru/'.trim($link),
					'название' => trim($name),
					'цена' => trim($price),
					'картинка' => trim($picture),
					'метро' => trim($metro),
					'телефон' => trim($phone),
					'время' => trim($date)
				];
			}

			//Убирает значение страницы, чтобы присоединить другое
			$url = str_replace('&p='.$numberOfPage, '', $url);
		}

		return $result; 
	}
	
	private function get_contents($__url)
	{
		$ch = curl_init($__url);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
			curl_close($ch);
		return $result;
	}
}

