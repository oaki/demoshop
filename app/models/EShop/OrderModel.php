<?

class OrderException extends Exception
{

}

class OrderModel extends NObject
{

    private static $instance;

    private $status, $payment_method;

    private function  __construct()
    {
        $this->status = array(
            0 => _('Nezaplatené'),

            1 => _('Zaplatené'),
            2 => _('Odoslané'),
            3 => _('Spracováva sa'),

        );

        $this->payment_method = array(
            '0' => _('Hotovosti'),
            '1' => _('Predfaktúra')
        );
    }

    static function getStatus($status = null)
    {
        return ($status === null) ? self::getInstance()->status : self::getInstance()->status[$status];
    }

    static function updateStatus($id_order, $status)
    {

        dibi::query("UPDATE [order] SET", array('order_status' => $status), "WHERE id_order = %i", $id_order);
    }

    static function getPaymentMethod($type = null)
    {

        return ($type === null) ? self::getInstance()->payment_method : self::getInstance()->payment_method[$type];
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            return self::$instance = new self();
        } else {
            return self::$instance;
        }
    }

    static function createOrder($values, $id_product_params, $id_lang, $user)
    {
        dibi::begin();
//		print_r($values);exit;
        dibi::query("INSERT INTO [order]", $values);
        $order_id = dibi::insertId();

        foreach ($id_product_params as $id_product_param => $count) {
            self::insertProduct($id_product_param, $count, $order_id, $id_lang, $user);
        }
        dibi::commit();

        return $order_id;
    }

    static function insertProduct($id_product_param, $count, $id_order, $id_lang, $user)
    {
        $product = ProductModel::getProductIdentifyByParam($id_product_param, $id_lang, $user);

        if (!$product)
            throw new OrderException(_('Produkt neexistuje'));

        $order_product = array(
            'id_order'         => $id_order,
            'id_product'       => $product['id_product'],
            'id_product_param' => $id_product_param,
            'name'             => $product['name'],
            'code'             => $product['code'],
            'price'            => $product['price_array']['price'],
            'price_with_tax'   => $product['price_array']['price_with_tax'],
            'count'            => $count,
            'tax'              => $product['price_array']['tax'],

        );

        dibi::query("INSERT INTO [order_product]", $order_product);
    }

    static function get($id_order, $id_user = null)
    {
        $order = dibi::fetch("SELECT * FROM [order] WHERE id_order = %i", $id_order, "%if", $id_user != null, "AND id_user = %i", $id_user, "AND deleted = 0 %end");

        if (!empty($order))
            $order['products'] = dibi::fetchAll("SELECT * FROM [order_product] WHERE id_order = %i", $id_order);

        return $order;
    }

    static function getDatasource()
    {
        return dibi::dataSource("
			SELECT
				*
			FROM
				[order]
			WHERE 
				deleted = 0
			");

    }

    static function getTotalPrice($products, $payment_method, $id_lang, $user)
    {
        $param = array(
            'price'          => 0,
            'price_with_tax' => 0,
            'weight'         => 0,
            'payment_price'  => 0,
            'delivery_price' => 0
        );

        foreach ($products as $id_product_param => $count) {
            $product = ProductModel::getProductIdentifyByParam($id_product_param, $id_lang, $user);
            $param['price'] += $product['price_array']['price'] * $count;
            $param['price_with_tax'] += $product['price_array']['price_with_tax'] * $count;
            $param['weight'] += $product['weight'] * $count;
        }

        ///$payment_method
        $param['payment_price'] = self::getPaymentPrice($payment_method, $param['price']);

//		$delivery_price = ProductWeightModel::getPrice($weight);

        $param['delivery_price'] = ProductWeightModel::getPrice($param['weight']);

        $conf = NEnvironment::getContext()->parameters;
        if ($conf['DELIVERY_IS_WITH_TAX'] == 1) {
            $param['price'] += $param['delivery_price'] / (1 + $conf['DELIVERY_TAX'] / 100);
            $param['price_with_tax'] += $param['delivery_price'];

        } else {
            $param['price'] += $param['delivery_price'];
            $param['price_with_tax'] += $param['delivery_price'] * (1 + $conf['DELIVERY_TAX'] / 100);
        }

        return $param;
    }

    static function getCartInfo($products, $id_delivery = false, $id_payment = false, $context, $isCache = false)
    {

        $param = array(
            'total_sum'          => 0,
            'total_sum_tax'      => 0,
            'total_sum_with_tax' => 0,
            'delivery_title'     => '',
            'delivery_price'     => array(),
            'payment_title'      => '',
            'payment_price'      => array(),
            'taxies'             => array(),
            'product_count'      => 0,
            'products'           => array()
        );

        $taxies = $context->getService('Vat')->getFluent()->fetchPairs('value', 'value');

        foreach ($taxies as $k => $t) {
            $taxies[$k] = 0;
        }

        foreach ($products as $id_product_param => $count) {
            $product = ProductModel::getProductIdentifyByParam($id_product_param, $context->application->presenter->id_lang, $context->user);

            //pripocitanie do produktu
            $product['sum_price'] = $product['price_array']['price'] * $count;

            $product['count'] = $count;

            //pripocitanie do celkovej ceny
            $param['total_sum'] += $product['price_array']['price'] * $count;

            //tax
            $taxies[$product['price_array']['tax']] += $product['price_array']['tax_price'] * $count;

            //spocitanie produktov
            $param['product_count'] += $count;

            $param['products'][$id_product_param] = $product;
        }

        if ($id_delivery) {
            $delivery = $context->getService('Delivery')->getDeliveryWithPrice($id_delivery);

            $param['delivery_title'] = $delivery['name'];

            $taxies[$delivery['price_array']['tax']] += $delivery['price_array']['tax_price'];

            $param['total_sum'] += $delivery['price_array']['price'];

            $param['delivery_price'] = $delivery['price_array'];
        }

        if ($id_payment) {
            $payment = $context->getService('Payment')->getDeliveryWithPrice($id_payment);

            $param['payment_title'] = $payment['name'];

            $taxies[$payment['price_array']['tax']] += $payment['price_array']['tax_price'];

            $param['total_sum'] += $payment['price_array']['price'];

            $param['payment_price'] = $payment['price_array'];
        }

        $param['taxies'] = $taxies;

        foreach ($taxies as $k => $tax_sum_price) {
            $param['total_sum_tax'] += $tax_sum_price;
        }

        $param['total_sum_with_tax'] = $param['total_sum'] + $param['total_sum_tax'];

        return $param;
    }

    static function getPaymentPrice($type, $price)
    {
        throw new Exception('depricated');
    }

    static function deleteOrder($id_order)
    {
        dibi::query("UPDATE [order] SET ", array('deleted' => '1'), "WHERE id_order = %i", $id_order);
    }
}