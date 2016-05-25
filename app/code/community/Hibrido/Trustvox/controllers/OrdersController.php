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
                $clientArray = $this->helper()->mountClientInfoToSend($order->getCustomerFirstname(), $order->getCustomerLastname(), $order->getCustomerEmail());

                $enabled = $this->helper()->checkStoreIdEnabled();
                $productArray = array();


                foreach ($order->getAllItems() as $item) {
                    $_product = Mage::getModel('catalog/product');

                    if ($item->getProductType() == 'simple' || $item->getProductType() == 'grouped') {
                        $parents = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($item->getProductId());
                        if(count($parents) >= 1){
                            $productId = $parents[0];
                        }else{
                            $productId = $item->getProductId();
                        }
                    }else{
                        $productId = $item->getProductId();
                    }

                    if ($item->getParentItemId()) {
                        $parent_product_type = Mage::getModel('sales/order_item')->load($item->getParentItemId())->getProductType();
                        if ($parent_product_type == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                            $productId = $item->getParentItemId();
                        }
                    }

                    $_item = $_product->load($productId);
                    $product_url = $_item->getProductUrl();

                    $images = array();

                    foreach ($_item->getMediaGalleryImages() as $image) {
                        array_push($images, $image->getUrl());
                    }

                    if($_item->getId()){
                        $productArray[$_item->getId()] = array(
                            'name' => $_item->getName(),
                            'id' => $_item->getId(),
                            'price' => $_item->getPrice(),
                            'url' => $product_url,
                            'type' => $item->getProductType(),
                            'photos_urls' => is_null($images[0]) ? '' : array($images[0]),
                        );
                    }
                }

            $shippingDate = '';
            foreach($order->getShipmentsCollection() as $shipment){
                $shippingDate = $shipment->getCreatedAt();
            }

            if(!$shippingDate || $shippingDate == ''){
                $shippingDate = $order->getCreatedAt();
            }

            array_push($json, $this->helper()->forJSON($order->getId(), $shippingDate, $clientArray, $productArray));
            }

            return $this->getResponse()->setBody(json_encode($json));
        } else {
            $jsonArray = array(
                'error' => true,
                'message' => 'not authorized',
            );
            $this->getResponse()->setBody(json_encode($jsonArray));
        }
    }
}
