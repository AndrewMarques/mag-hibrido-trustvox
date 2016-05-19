<?php

class Hibrido_Trustvox_OrdersController extends Mage_Core_Controller_Front_Action {

    private function helper()
    {
        return Mage::helper('hibridotrustvox');
    }

    public function indexAction()
    {
        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);

        if ($this->getRequest()->getHeader('trustvox-token') == $this->helper()->getToken()) {
            $counter = 0;
            $sent = 0;
            $clientArray = array();
            $productArray = array();

            if($this->getRequest()->getHeader('date-period') && $this->getRequest()->getHeader('date-period') >= 1){
                $period = intval($this->getRequest()->getHeader('date-period'));
            }else{
                $period = 15;
            }

            $orders = $this->helper()->getOrdersByLastDays($period);

            $json = array();

            $i = -1;
            foreach ($orders as $order) {
                ++$i;
                $clientArray = array(
                    'first_name' => $order->getCustomerFirstname(),
                    'last_name' => $order->getCustomerLastname(),
                    'email' => $order->getCustomerEmail()
                );

                $enabled = $this->helper()->checkStoreIdEnabled();
                $productArray = array();

                foreach ($order->getAllItems() as $item) {
                    $_product = Mage::getModel('catalog/product');

                    if ($item->getProductType() == 'simple') {
                        $parents = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($item->getProductId());
                        if(count($parents) >= 1){
                            $productId = $parents[0];
                        }else{
                            $productId = $item->getProductId();
                        }
                    }else{
                        $productId = $item->getProductId();
                    }

                    $_item = $_product->load($productId);
                    $product_url = $item->getProductUrl();

                    $images = array();

                    foreach ($_item->getMediaGalleryImages() as $image) {
                        array_push($images, $image->getUrl());
                    }

                    $productArray[$productId] = array(
                        'name' => $item->getProductName(),
                        'id' => $productId,
                        'price' => $item->getProductPrice(),
                        'url' => $product_url,
                        'photos_urls' => is_null($images[0]) ? '' : array($images[0]),
                    );
                }

            $shippingDate = '';
            foreach($order->getShipmentsCollection() as $shipment){
                $shippingDate = $shipment->getCreatedAt();
            }

            if(!$shippingDate || $shippingDate == ''){
                $shippingDate = $order->getCreatedAt();
            }

            $data = array(
                'order_id' => $order->getId(),
                'delivery_date' => $shippingDate,
                'client' => $clientArray,
                'items' => $productArray
            );

            array_push($json, $data);

            return $this->getResponse()->setBody(json_encode($json));
        } else {
            $this->getResponse()->setBody(json_encode([
                'error' => true,
                'message' => 'not authorized',
            ]));
        }
    }
}
