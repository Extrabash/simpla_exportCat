<?php

chdir('../..');
require_once('api/Simpla.php');

class ExportAjax extends Simpla
{	
	private $columns_names = array(
			'category'=>         '���������',
			'name'=>             '�����',
			'price'=>            '����',
			'currency'=>         '������',
			'url'=>              '�����',
			'visible'=>          '�����',
			'featured'=>         '�������������',
			'brand'=>            '�����',
			'variant'=>          '�������',
			'compare_price'=>    '������ ����',
			'sku'=>              '�������',
			'stock'=>            '�����',
			'meta_title'=>       '��������� ��������',
			'meta_keywords'=>    '�������� �����',
			'meta_description'=> '�������� ��������',
			'annotation'=>       '���������',
			'body'=>             '��������',
			'images'=>           '�����������'
			);
			
	private $column_delimiter = ';';
	private $subcategory_delimiter = '/';
	private $products_count = 5;
	private $export_files_dir = 'simpla/files/export/';
	private $filename = 'export.csv';

	public function fetch()
	{

		if(!$this->managers->access('export'))
			return false;

		// ������ ������ ������ 1251
		setlocale(LC_ALL, 'ru_RU.1251');
		$this->db->query('SET NAMES cp1251');
	
		// ��������, ������� ������������
		$page = $this->request->get('page');
		if(empty($page) || $page==1)
		{
			$page = 1;
			// ���� ������ ������� - ������ ������ ���� ��������
			if(is_writable($this->export_files_dir.$this->filename))
				unlink($this->export_files_dir.$this->filename);
		}
		
		// ��������� ���� �������� �� ����������
		$f = fopen($this->export_files_dir.$this->filename, 'ab');
		
		// ������� � ������ ������� �������� �������
		$features = $this->features->get_features();
		foreach($features as $feature)
			$this->columns_names[$feature->name] = $feature->name;
		
		// ���� ������ ������� - ������� � ������ ������ �������� �������
		if($page == 1)
		{
			fputcsv($f, $this->columns_names, $this->column_delimiter);
		}
		
		// ��� ������
		$products = array();

		// ������� ��������� ���� �����, ������� ������
		$filter_categories = $this->request->get('filter_categories');
		$filter = array('page'=>$page, 'limit'=>$this->products_count);
		if(!empty($filter_categories))
			$filter['category_id'] = $filter_categories;

 		foreach($this->products->get_products($filter) as $p)
 		{
 			$products[$p->id] = (array)$p;
 			
	 		// �������� �������
	 		$options = $this->features->get_product_options($p->id);
	 		foreach($options as $option)
	 		{
	 			if(!isset($products[$option->product_id][$option->name]))
					$products[$option->product_id][$option->name] = $option->value;
	 		}

 			
 		}
 		
 		if(empty($products))
 			return false;
 		
 		// ��������� �������
 		foreach($products as $p_id=>&$product)
 		{
	 		$categories = array();
	 		$cats = $this->categories->get_product_categories($p_id);
	 		foreach($cats as $category)
	 		{
	 			$path = array();
	 			$cat = $this->categories->get_category((int)$category->category_id);
	 			if(!empty($cat))
 				{
	 				// ��������� ������������ ���������
	 				foreach($cat->path as $p)
	 					$path[] = str_replace($this->subcategory_delimiter, '\\'.$this->subcategory_delimiter, $p->name);
	 				// ��������� ��������� � ������ 
	 				$categories[] = implode('/', $path);
 				}
	 		}
	 		$product['category'] = implode(', ', $categories);
 		}
 		
 		// ����������� �������
 		$images = $this->products->get_images(array('product_id'=>array_keys($products)));
		$img_domain = $this->request->get('img_domain');
 		foreach($images as $image)
 		{
			if(!empty($img_domain))
				$image->filename = urlencode($img_domain.'/files/originals/'.$image->filename);
			
 			// ��������� ����������� � ������ ����� �������
 			if(empty($products[$image->product_id]['images']))
 				$products[$image->product_id]['images'] = $image->filename;
 			else
 				$products[$image->product_id]['images'] .= ', '.$image->filename;
 		}
 
 		$variants = $this->variants->get_variants(array('product_id'=>array_keys($products)));

		foreach($variants as $variant)
 		{
 			if(isset($products[$variant->product_id]))
 			{
	 			$v                    = array();
	 			$v['variant']         = $variant->name;
	 			$v['price']           = $variant->price;
				$v['currency'] 		  = $variant->currency;
					
				if ($variant->base_price)
					$v['price']           = $variant->base_price;
				else
					$v['price']           = $variant->price;
					
				if ($variant->base_price)
					$v['base_compare_price']           = $variant->base_compare_price;
				else
					$v['compare_price']   = $variant->compare_price;
					
	 			$v['sku']             = $variant->sku;
	 			$v['stock']           = $variant->stock;
	 			if($variant->infinity)
	 				$v['stock']           = '';
				$products[$variant->product_id]['variants'][] = $v;
	 		}
		}
		
		foreach($products as &$product)
 		{
 			$variants = $product['variants'];
 			unset($product['variants']);
 			
 			if(isset($variants))
 			foreach($variants as $variant)
 			{
 				$result = array();
 				$result =  $product;
 				foreach($variant as $name=>$value)
 					$result[$name]=$value;

	 			foreach($this->columns_names as $internal_name=>$column_name)
	 			{
	 				if(isset($result[$internal_name]))
		 				$res[$internal_name] = $result[$internal_name];
	 				else
		 				$res[$internal_name] = '';
	 			}
	 			fputcsv($f, $res, $this->column_delimiter);

	 		}
		}
		
		$count_filter = array();
		if(!empty($filter_categories))
			$count_filter['category_id'] = $filter_categories;
			
		$total_products = $this->products->count_products($count_filter);
		
		if($this->products_count*$page < $total_products)
			return array('end'=>false, 'page'=>$page, 'totalpages'=>$total_products/$this->products_count);
		else
			return array('end'=>true, 'page'=>$page, 'totalpages'=>$total_products/$this->products_count);		

		fclose($f);

	}
	
}

$export_ajax = new ExportAjax();
$data = $export_ajax->fetch();
if($data)
{
	header("Content-type: application/json; charset=utf-8");
	header("Cache-Control: must-revalidate");
	header("Pragma: no-cache");
	header("Expires: -1");
	$json = json_encode($data);
	print $json;
}