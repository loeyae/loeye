<?php

/**
 * XmlRender.php
 *
 * PHP version 7
 *
 * Licensed under the Apache License, Version 2.0 (the "License"),
 * see LICENSE for more details: http://www.apache.org/licenses/LICENSE-2.0.
 *
 * @category PHP
 * @package  LOEYE
 * @author   Zhang Yi <loeyae@gmail.com>
 * @version  2018-07-23 22:44:28
 * @link     https://github.com/loeyae/loeye.git
 */

namespace loeye\render;

use loeye\std\Render;
use loeye\std\Response;
use SimpleXMLElement;

/**
 * Description of XmlRender
 *
 * @author   Zhang Yi <loeyae@gmail.com>
 */
class XmlRender extends Render
{

    private $_rootNodeName;
    private $_hasCDATA;
    private $_defaultNodeName;

    /**
     * __construct
     *
     * @param Response $response
     * @param string $rootNodeName root node name
     * @param boolean $hasCDATA has CDATA
     * @param string $defaultNodeName default node name prefix (num key of array will add prefix)
     *
     */
    public function __construct(
            Response $response, $rootNodeName = 'xml', $hasCDATA = false,
            $defaultNodeName = 'item'
    )
    {
        parent::__construct($response);
        $this->_rootNodeName    = $rootNodeName;
        $this->_hasCDATA        = $hasCDATA;
        $this->_defaultNodeName = $defaultNodeName;
    }

    /**
     * header
     *
     * @return array|null
     */
    public function header(): ?array
    {
        $this->response->addHeader('Content-Type', 'application/xml; charset=UTF-8');
        return $this->response->getHeaders();
    }

    /**
     * output
     *
     * @return string|null
     */
    public function output(): ?string
    {
        $output = $this->response->getOutput();

        return $this->array2xml($output);
    }

    /**
     * array2xml
     *
     * @param array $array data array
     *
     * @return string
     */
    public function array2xml($array): string
    {
        $xmlRoot      = <<<XML
        <$this->_rootNodeName>
        </$this->_rootNodeName>
XML;
        $simpleXmlObj = simplexml_load_string($xmlRoot);
        $this->_addXMLChild($simpleXmlObj, $array);

        return $simpleXmlObj->asXML();
    }

    /**
     * xml2array
     *
     * @param string $xmlString xml string
     *
     * @return array
     */
    public function xml2array($xmlString): array
    {
        $simpleXmlObj = simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA);
        return $this->_getXMLChild($simpleXmlObj);
    }

    /**
     * _getXMLChild
     *
     * @param SimpleXMLElement $simpleXmlObj SimpleXMLElement
     *
     * @return array
     */
    private function _getXMLChild(SimpleXMLElement $simpleXmlObj): array
    {
        $data = array();
        foreach ($simpleXmlObj->children() as $child) {
            if (count($child) > 0) {
                $value = $this->_getXMLChild($child);
            } else {
                $value = (string) $child;
            }
            if (isset($data[$child->getName()])) {
                $data[$child->getName()] = array_merge_recursive(array($data[$child->getName()]), array($value));
            } else {
                $data[$child->getName()] = $value;
            }
        }
        return $data;
    }

    /**
     * _addXMLChild
     *
     * @param SimpleXMLElement $simpleXmlObj    SimpleXMLElement
     * @param array             $children        children
     * @param SimpleXMLElement $parentObj       parent SimpleXMLElement
     * @param string            $defaultNodeName default Node Name
     *
     * @return void
     */
    private function _addXMLChild(
        SimpleXMLElement $simpleXmlObj, $children, $parentObj = null, $defaultNodeName = null
    ): void
    {
        if (empty($defaultNodeName)) {
            $defaultNodeName = $this->_defaultNodeName;
        }
        foreach ($children as $key => $child) {
            $nodeName = is_numeric($key) ? $defaultNodeName : $key;
            if (is_numeric($key) && $parentObj instanceof SimpleXMLElement) {
                if ($key == 0) {
                    $childObj = $simpleXmlObj;
                } else {
                    $childObj = $parentObj->addChild($nodeName);
                }
            } else {
                $childObj = $simpleXmlObj->addChild($nodeName);
            }
            if (is_array($child)) {
                $this->_addXMLChild($childObj, $child, $simpleXmlObj, $nodeName);
            } else {
                $childNode = dom_import_simplexml($childObj);
                $childDom  = $childNode->ownerDocument;
                if ($this->_hasCDATA) {
                    $childNode->appendChild($childDom->createCDATASection($child));
                } else {
                    $childNode->appendChild($childDom->createTextNode((string)$child));
                }
            }
        }
    }

}
