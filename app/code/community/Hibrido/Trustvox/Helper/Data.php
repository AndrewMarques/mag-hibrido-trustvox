<?php

class Hibrido_Trustvox_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function mostrarEstrelas($id){
        $mostrar = Mage::getStoreConfig('hibridotrustvox/configuracoes/mostrar_estrelas');

        $html = '';
        if($mostrar == 'sim'){
            $html .= '<style type="text/css">';
                $html .= '.trustvox-widget-rating .ts-shelf-container,';
                $html .= '.trustvox-widget-rating .trustvox-shelf-container{';
                $html .= 'display: inline-block;';
            $html .= '}';
            $html .= '.trustvox-widget-rating span.rating-click-here{';
                $html .= 'top: -3px;';
                $html .= 'display: inline-block;';
                $html .= 'position: relative;';
                $html .= 'color: #DAA81D;';
            $html .= '}';
            $html .= '.trustvox-widget-rating:hover span.rating-click-here{';
                $html .= 'text-decoration: underline;';
            $html .= '}';
            $html .= '</style>';

            $html .= '<a class="trustvox-fluid-jump trustvox-widget-rating" href="#trustvox-reviews" title="Pergunte e veja opiniões de quem já comprou">';
                $html .= '<div class="trustvox-shelf-container" data-trustvox-product-code="'. $id .'" data-trustvox-should-skip-filter="true" data-trustvox-display-rate-schema="true"></div>';
            $html .= '</a>';
        }

        return $html;
    }

    // Pega o ID do atributo passando o seu nome
    public function getAttributeId($name)
    {
        $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
        $code = $eavAttribute->getIdByCode('catalog_product', $name);

        return $code;
    }

    public function getFullProductUrl(Mage_Catalog_Model_Product $product = null)
    {

        // Force display deepest child category as request path.
        $categories = $product->getCategoryCollection();
        $deepCatId = 0;
        $path = '';
        $productPath = false;

        foreach ($categories as $category) {
            // Look for the deepest path and save.
            if (substr_count($category->getData('path'), '/') > substr_count($path, '/')) {
                $path = $category->getData('path');
                $deepCatId = $category->getId();
            }
        }

        // Load category.
        $category = Mage::getModel('catalog/category')->load($deepCatId);

        // Remove .html from category url_path.
        $categoryPath = str_replace('.html', '', $category->getData('url_path'));

        // Get product url path if set.
        $productUrlPath = $product->getData('url_path');

        // Get product request path if set.
        $productRequestPath = $product->getData('request_path');

        // If URL path is not found, try using the URL key.
        if ($productUrlPath === null && $productRequestPath === null) {
            $productUrlPath = $product->getData('url_key');
        }

        // Now grab only the product path including suffix (if any).
        if ($productUrlPath) {
            $path = explode('/', $productUrlPath);
            $productPath = array_pop($path);
        } elseif ($productRequestPath) {
            $path = explode('/', $productRequestPath);
            $productPath = array_pop($path);
        }

        // Now set product request path to be our full product url including deepest category url path.
        if ($productPath !== false) {
            if ($categoryPath) {
                // Only use the category path is one is found.
                if ($product->getTypeId() == 'configurable') {
                    $product->setData('request_path', $productPath);
                } else {
                    $product->setData('request_path', $categoryPath.'/'.$productPath);
                }
            } else {
                $product->setData('request_path', $productPath);
            }
        }

        return $product->getProductUrl();
    }

    public function getOrdersByLastDays($days)
    {
        $customStatus = Mage::getResourceModel('sales/order_status_collection')
            ->addStateFilter(array('complete'))
            ->toOptionHash();

        $statusCompleted = array('complete', 'completo', 'despachado', 'enviado', 'sent');

        foreach ($customStatus as $statusKey => $status) {
            if(!in_array($statusKey, $statusCompleted)){
                $statusCompleted[] = $statusKey;
            }
        }

        $orders = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('status', array('in' => $statusCompleted) )
            ->addFieldToFilter('updated_at', array(
                'from' => date('m/d/Y', strtotime("-$days days")),
                'to ' => date('m/d/Y', time()),
                'date' => true,
            ));

        return $orders;
    }

    public function checkStoreIdEnabled()
    {
        // Verifica no banco se store_id está habilitado
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $table = $resource->getTableName('core_config_data');
        $query = 'SELECT value FROM '.$table.' WHERE path = "web/url/use_store" LIMIT 1';

        return $readConnection->fetchOne($query);
    }

    /**
     * @param $item
     * @param $enabledStoreId
     *
     * @return array
     *
     * Retorna os parâmetros do produto necessários para enviar à Trustvox
     * verificando se store_id está habilitado.
     */
    public function getProductParamsToSend($item, $enabledStoreId)
    {
        $skuId = '';
        $_product = Mage::getModel('catalog/product');

        if ($item->product_type == 'configurable') {
            /*
             * Pega o ID do atributo sku para adicionar ao final da URL.
             * Este é o mecanismo que o Magento utiliza para diferenciar cada produto configurável.
             * No final da URl, passa-se algum parâmetro de identificação.
             * Neste plugin, foi utilizado o SKU.
             */
            $skuId = $this->getAttributeId('sku');
        }

        if ($enabledStoreId == '1') {
            $_item = $_product->setStoreId($item->getStoreId())->load($item->getProductId());
        } else {
            $_item = $_product->load($item->getProductId());
        }

        if ($item->product_type == 'grouped') {
            // Pega a URL do grupo de produtos
            $parents = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($item->getProductId());
            $parent = $_product->load($parents[0]);
            $product_url = $this->getFullProductUrl($parent);
        } else {
            $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($item->getProductId());
            $skuId = $this->getAttributeId('sku');

            if (count($parentIds) > 0) {
                $_item = $_product->load($parentIds[0]);
            }

            $product_url = $this->getFullProductUrl($_item).'#'.$skuId.'='.$item->getSku();
        }

        $images = array();

        foreach ($_item->getMediaGalleryImages() as $image) {
            array_push($images, $image->getUrl());
        }

        $productArray[] = array(
            'name' => $item->getName(),
            'id' => $item->getProductId(),
            'price' => $item->getPrice(),
            'url' => $product_url,
            'photos_urls' => is_null($images[0]) ? null : array($images[0]),
          );

        return $productArray;
    }

    /**
     * @param $firstname
     * @param $lastname
     * @param $email
     *
     * @return array
     *
     * Monta um array com os dados do cliente
     */
    public function mountClientInfoToSend($firstname, $lastname, $email)
    {
        $clientArray = array(
            'first_name' => $firstname,
            'last_name' => $lastname,
            'email' => $email,
          );

        return $clientArray;
    }

    /**
     * @param $orderId
     * @param $orderCreatedAt
     * @param $client
     * @param $product
     *
     * @return string
     *
     * Transforma em JSON todos os dados do pedido
     */
    public function forJSON($orderId, $orderCreatedAt, $client, $product)
    {
        $moduleVersion = Mage::getConfig()->getModuleConfig('Hibrido_Trustvox')->version;

        $data = array(
            'moduleVersion' => $moduleVersion,
            'order_id' => $orderId,
            'delivery_date' => $orderCreatedAt,
            'client' => $client,
            'items' => $product,
        );

        return $data;
    }

    /**
     * @param $json
     *
     * @return Zend_Http_Client
     *
     * @throws Zend_Http_Client_Exception
     *
     * Insere o cabeçalho para envio dos dados para Trustvox
     */
    public function setJSON($json)
    {
        /* Cabeçalhos necessários com um token de autenticação */
        $url = $this->getEndPoint().'stores/'.$this->getTrustvoxId().'/orders';
        $varienClient = new Varien_Http_Client($url);
        $varienClient->setHeaders('Accept', 'application/vnd.trustvox.com; version=1');
        $varienClient->setHeaders('Content-Type', 'application/json; charset=utf-8');
        $varienClient->setHeaders('Authorization', 'token '.$this->getToken());
        $varienClient->setMethod(Varien_Http_Client::POST);

        /* Envio dos dados em JSON */
        return $varienClient->setRawData($json)->setEncType('application/json');
    }

    public function clean_url($url)
    {
        return substr($url, 0, strrpos($url, '/'));
    }

    public function getEndPoint()
    {
        return 'https://trustvox.com.br/api/';
    }

    public function getToken()
    {
        return Mage::getStoreConfig('hibridotrustvox/configuracoes/token');
    }

    public function getTrustvoxId()
    {
        return Mage::getStoreConfig('hibridotrustvox/configuracoes/numero_da_loja');
    }

    public function getPeriod()
    {
        return Mage::getStoreConfig('hibridotrustvox/configuracoes/periodo');
    }

    public function log($message)
    {
        return Mage::log($message);
    }

}
