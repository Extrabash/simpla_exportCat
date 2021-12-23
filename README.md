# simpla_exportCat
Экспорт с выбором категорий

## Установка
Для установки необходимо скопировать в соответствующие папки файлы из репозитория,
плюс пара манипуляций чтобы завелось

в файле /simpla/IndexAdmin.php - права доступа к экспорту категорий
```
'ExportAdmin'         => 'export',
'ExportCatAdmin'      => 'export',
```

в файлах /simpla/deisgn/html/backup.tpl, import.tpl, export.tpl соответствующим образом шапку
```
{* Вкладки *}
{capture name=tabs}
	{if in_array('import', $manager->permissions)}<li><a href="index.php?module=ImportAdmin">Импорт</a></li>{/if}
	{if in_array('export', $manager->permissions)}<li><a href="index.php?module=ExportAdmin">Экспорт</a></li>{/if}
	{if in_array('export', $manager->permissions)}<li><a href="index.php?module=ExportCatAdmin">Экспорт по Категориям</a></li></li>{/if}
	<li class="active"><a href="index.php?module=BackupAdmin">Бекап</a></li>		
{/capture}
```



## Импорт картинок
Для импорта оригиналов своего проекта, нужно разрешить туда доступв файле files/originals/.htaccess,
добавьте нужный ip проекта вместо xxx.xxx.xxx.xxx
```
order deny,allow 
deny from all
allow from xxx.xxx.xxx.xxx
```

Обратите внимание на файл импорта, импортируем картинки...
```
// Изображения товаров
if(isset($item['images']))
{
	// Изображений может быть несколько, через запятую
	$images = explode(',', $item['images']);
	foreach($images as $image)
	{
		$image = trim($image);
		$download_image = false;

		if(stristr($image, 'http') && stristr($image, '%2F'))
		{
			$image = urldecode($image);
			// Если это ссылка, то нужно скачать файл
			$download_image = true;
		}
		
		if(!empty($image))
		{
			// Имя файла
			$image_filename = pathinfo($image, PATHINFO_BASENAME);
			
			// Добавляем изображение только если такого еще нет в этом товаре
			$this->db->query('SELECT filename FROM __images WHERE product_id=? AND (filename=? OR filename=?) LIMIT 1', $product_id, $image_filename, $image);
			if(!$this->db->result('filename'))
			{
				$this->products->add_image($product_id, $image);
				if($download_image)
					$this->image->download_image($image);
			}
		}
	}
}
```