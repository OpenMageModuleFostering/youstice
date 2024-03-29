<?php
/**
 * Class representing one shop item (order or product)
 *
 * @author    Youstice
 * @copyright (c) 2015, Youstice
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html  Apache License, Version 2.0
 */

abstract class Youstice_ShopItem {

	protected $data = array(
		'description' => '',
		'name' => '',
		'currency' => '',
		'price' => 0.0,
		'id' => -1,
		'deliveryDate' => '',
		'orderDate' => '',
		'image' => '',
		'other' => '',
		'products' => array(),
		'href' => ''
	);
	
	protected $mime_types = array(
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml'
	);

	public function __construct($description, $name = '', $currency = 'EUR', $price = 0.0, $id = null, $delivery_date = null,
			$order_date = null, $image = null, $other_info = '', $products = array())
	{
		//one array parameter
		if (is_array($description) && count($description))
		{
			$this->setDescription($description['description']);
			$this->setName($description['name']);
			$this->setCurrency($description['currency']);
			$this->setPrice($description['price']);
			$this->setId($description['id']);
			$this->setDeliveryDate($description['deliveryDate']);
			$this->setOrderDate($description['orderDate']);
			if (isset($description['image']) && is_readable($description['image']))
				$this->setImagePath($description['image']);
			else
				$this->setImageRawBytes($description['image']);
			$this->setOtherInfo($description['otherInfo']);
			$this->setProducts($description['products']);
		}

		$this->setDescription($description);
		$this->setName($name);
		$this->setCurrency($currency);
		$this->setPrice($price);
		$this->setId($id);
		$this->setDeliveryDate($delivery_date);
		$this->setOrderDate($order_date);
		if (isset($image) && is_readable($image))
			$this->setImagePath($image);
		else
			$this->setImageRawBytes($image);
		$this->setOtherInfo($other_info);
		$this->setProducts($products);

		return $this;
	}

	public function getDescription()
	{
		return $this->data['description'];
	}

	public function getName()
	{
		return $this->data['name'];
	}

	public function getCurrency()
	{
		return $this->data['currency'];
	}

	public function getPrice()
	{
		return $this->data['price'];
	}

	public function getId()
	{
		return $this->data['id'];
	}

	public function getDeliveryDate()
	{
		return $this->data['deliveryDate'];
	}

	public function getOrderDate()
	{
		return $this->data['orderDate'];
	}

	public function getImage()
	{
		return $this->data['image'];
	}

	public function getOtherInfo()
	{
		return $this->data['other'];
	}

	public function getProducts()
	{
		return $this->data['products'];
	}

	public function getOrderId()
	{
		return $this->data['orderId'];
	}

	public function getHref()
	{
		return $this->data['href'];
	}

	public function setDescription($description = '')
	{
		$this->data['description'] = $description;

		return $this;
	}

	public function setName($name = '')
	{
		$this->data['name'] = $name;

		return $this;
	}

	public function setCurrency($currency = '')
	{
		$this->data['currency'] = $currency;

		return $this;
	}

	public function setPrice($price = 0.0)
	{
		if ($price < 0)
			throw new InvalidArgumentException('Price cannot be negative number.');

		$this->data['price'] = $price;

		return $this;
	}

	public function setId($id = null)
	{
		$this->data['id'] = $id;

		return $this;
	}

	public function setDeliveryDate($delivery_date)
	{
		if (Youstice_Tools::strlen($delivery_date > 1)) {
			$timestamp = strtotime($delivery_date);
			$this->data['deliveryDate'] = date('c', $timestamp);
			return $this;
		}

		return $this;
	}

	public function setOrderDate($order_date)
	{
		if (Youstice_Tools::strlen($order_date > 1)) {
			$timestamp = strtotime($order_date);
			$this->data['orderDate'] = date('c', $timestamp);
			return $this;
		}

		return $this;
	}

	public function setImage($image = '')
	{
		if (is_readable($image))
			$this->setImagePath($image);
		else
			$this->setImageRawBytes($image);

		return $this;
	}

	public function setImagePath($image = '')
	{
		$this->data['image'] = $this->loadImage($image);

		return $this;
	}

	public function setImageRawBytes($image = '')
	{
		if (Youstice_Tools::strlen($image) > 0)
		{
			$image_data = $this->resize($image, 300, 300);
			$this->data['image'] = base64_encode($image_data);
		}

		return $this;
	}

	public function setOtherInfo($other_info = '')
	{
		$this->data['other'] = $other_info;

		return $this;
	}

	public function setProducts($products = array())
	{
		$this->data['products'] = $products;

		return $this;
	}

	public function setHref($href = '')
	{
		$this->data['href'] = $href;

		return $this;
	}

	public function toArray()
	{
		return $this->data;
	}

	protected function loadImage($path)
	{
		if ($path == null || !trim($path))
			return;

		if (is_readable($path))
		{
			$image_data = Youstice_Tools::file_get_contents($path);

			if ($image_data === false)
				throw new Exception('Image does not exists');

			$mime_type = $this->getMimeType($path);

			//correct image
			if (Youstice_Tools::strlen($image_data) > 0)
			{
				$image_data = $this->resize($image_data, 300, 300, false, $mime_type);
				return base64_encode($image_data);
			}

			return null;
		}
		else
			throw new Exception('Image path is not readable');
	}

	protected function resize($image_data, $width = 100, $height = 100, $stretch = false, $mime_type = null)
	{
		$file = tempnam(sys_get_temp_dir(), md5(time().'YRS'));

		if ($file === false)
			throw new Exception('Creating temporary file failed. Temporary Directory: '.sys_get_temp_dir());

		$file_handle = fopen($file, 'w');
		fwrite($file_handle, $image_data);
		fclose($file_handle);

		if(!$mime_type)
			$mime_type = $this->getMimeType($file);

		switch ($mime_type)
		{
			case 'image/bmp':
				$handle = imagecreatefromwbmp($file);
				break;
			case 'image/jpeg':
				$handle = imagecreatefromjpeg($file);
				break;
			case 'image/gif':
				$handle = imagecreatefromgif($file);
				break;
			case 'image/png':
				$handle = imagecreatefrompng($file);
				break;
			default:
				throw new Exception('Unsupported image type '.$mime_type);
		}

		$dimensions = getimagesize($file);

		if (!$dimensions)
			throw new Exception('Reading of temporary file failed');

		$offset_x = 0;
		$offset_y = 0;
		$dst_w = $width;
		$dst_h = $height;

		$bnd_x = $width / $dimensions[0];
		$bnd_y = $height / $dimensions[1];

		if ($stretch)
		{
			if ($bnd_x > $bnd_y)
			{
				$ratio = $height / $width;
				$temp = floor($dimensions[1] / $ratio);

				if ($temp > $dimensions[0])
					$dimensions[1] -= ($temp - $dimensions[0]) * $ratio;
				else
					$dimensions[0] = $temp;
			}
			else
			{
				$ratio = $width / $height;
				$temp = floor($dimensions[0] / $ratio);
				if ($temp > $dimensions[1])
					$dimensions[0] -= ($temp - $dimensions[1]) * $ratio;
				else
					$dimensions[1] = $temp;
			}
		}
		else
		{
			if ($bnd_x > $bnd_y)
			{
				# height reaches boundary first, modify width
				$offset_x = ($width - $dimensions[0] * $bnd_y) / 2;
				$dst_w = $dimensions[0] * $bnd_y;
			}
			else
			{
				# width reaches boundary first (or equal), modify height
				$offset_y = ($height - $dimensions[1] * $bnd_x) / 2;
				$dst_h = $dimensions[1] * $bnd_x;
			}
		}

		$preview = imagecreatetruecolor($width, $height);

		if (!$preview)
			throw new Exception('Creating thumbnail failed');

		# draw white background -> opravene na transparent
		$c = imagecolorallocatealpha($preview, 255, 255, 255, 0);
		if ($c !== false)
		{
			imagefilledrectangle($preview, 0, 0, $width, $height, $c);
			imagecolortransparent($preview, $c);
			imagecolordeallocate($preview, $c);
		}

		if (!imagecopyresampled($preview, $handle, $offset_x, $offset_y, 0, 0, $dst_w, $dst_h, $dimensions[0], $dimensions[1]))
			throw new Exception('Creating thumbnail failed');

		unlink($file);
		imagedestroy($handle);

		ob_start();
		imagejpeg($preview);
		imagedestroy($preview);
		return ob_get_clean();
	}

	protected function getMimeType($filename)
	{
		if(function_exists('finfo_open') && function_exists('finfo_file'))
		{
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime = finfo_file($finfo, $filename);
			finfo_close($finfo);
			
			return $mime;
		}
		
		if(function_exists('mime_content_type'))
			return mime_content_type($filename);
		
		$extension = Tools::strtolower(array_pop(explode('.',$filename)));
		
		if(array_key_exists($extension, $this->mime_types)){
			return $this->mime_types[$extension];
        }
		
		throw new Exception('Please install PECL fileinfo extension');
	}

}
