# Hibrido Trustvox

Este módulo foi desenvolvido com o objetivo de integrar de uma maneira mais simplificada o widget da Trustvox.

Necessário Magento 1.9+

### Version
1.0.0

### Como usar

Basta instalar o módulo usando composer ou manualmente.
Em seguida, configurar as informações da Trustvox, como ID da loja, token, etc.

Automaticamente o módulo adicionará o widget da Trustvox na página do produto (se a configuração "posição do widget" estiver como "Padrão"). Caso você queira colocar o widget em um local diferente na página do produto, basta colocar a configuração "posição do widget" como "Personalizado" e colar o código abaixo aonde desejar.

```php
<?php
echo $this
    ->getLayout()
    ->createBlock('core/template')
    ->setTemplate('hibrido/widget_trustvox.phtml')
    ->tohtml();
?>
```

#### Estrelas
Para colocar as estrelas de avaliação do produto, basta colocar o código abaixo na listagem de produtos ou na página do produto.

```php
<?php
echo Mage::helper('hibridotrustvox')->mostrarEstrelas($_product->getId());
?>
```
